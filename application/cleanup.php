<?php
################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
#
# Student: Guillermo de la Puente
#          https://github.com/gpuenteallott
#
# cleanup.php
# - Send SMS via SNS to let the user know the URL for downloading the finished picture (first time
#   they will have to register to receive sms)
# - Mark the ACL for this object as Public::Read is S3
# - Mark the Object expiration time for the S3 bucket as 10 minutes
# - Display all the images in the S3 bucket on the screen. (Before and After)
# - Consume/Destroy Queue
################################################

// Actions:
// add code to consume the Queue to make sure the job is done
// add code to send the SMS message of the finished S3 URL
// Set object expire to remove the image in one day
// set ACL to public


// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';

# Uncomment this for better debugging
#header("Content-type: text/plain; charset=utf-8");

use Aws\Common\Aws;
use Aws\SimpleDb\SimpleDbClient;
use Aws\S3\S3Client;
use Aws\Sns\SnsClient;
use Aws\Sqs\sqsclient;
use Aws\Sns\Exception\InvalidParameterException;

$aws = Aws::factory('./vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php');

$client = $aws->get('S3'); 

$sdbclient = $aws->get('SimpleDb'); 

$snsclient = $aws->get('Sns'); 

$sqsclient = $aws->get('Sqs');


# Read the name file
# Name is the resources identifier in AWS for this system
$NAME = file_get_contents("name.txt");
$NAME_SDB = str_replace("-", "", $NAME)."sdb";

// sqs message body will contain the id
$mbody="";

#####################################################
# SQS 
# Read the queue for some information -- we will consume the queue later
# The SQS message will contain the id for the job to be cleaned up
#####################################################

# The URL must be obtained
# Obtain the SQS url for the given name
$sqs_queue_url = $sqsclient->getQueueUrl(array(
    'QueueName' => "$NAME-sqs",
));
//var_export($sqs_queue_url->getkeys());
$sqs_queue_url = $sqs_queue_url['QueueUrl'];


$result = $sqsclient->receiveMessage(array(
    'QueueUrl' => $sqs_queue_url,
    'MaxNumberOfMessages' => 1, 
));

######################################
# Probably need some logic in here to handle delays
######################################

foreach ($result->getPath('Messages/*/Body') as $messageBody) {
    // Do something with the message
    //echo "SQS: " . $messageBody . "\n";
    $mbody=$messageBody;
}


if ( $mbody === "" ) {
    ?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css"/>
    <title>ITMO 544 - Cleanup.php</title>
    
</head>
<body>
    <div id="main">
        <header>
            <h1>Picture Uploader</h1>

            <p>A mini project for ITMO 544 - Cloud Computing</p>
            <p>Illinois Institute of Technology</p>
            <p><a href="https://github.com/gpuenteallott/itmo544-CloudComputing-mp1">Project in GitHub</a></p>
        </header>

        <h2>Impatient!</h2>
        <p>The value in SQS isn't readable yet because of its eventually consistent behaviour.</p>
        <p>You can refresh this view in a few seconds to try again</p>

         <p class="next"><a href="" onClick="window.location.reload">Refresh</a></p>
    </div>
</body>
</html>
<?php
    exit;
}


####################################################################
# Select in the SimpleDB using the id retrieved from SQS
####################################################################
$exp = "select * from $NAME_SDB where id = '$mbody'";
//echo "\n".$exp."\n";

try {
$iterator = $sdbclient->getIterator('Select', array(
    'SelectExpression' => $exp,
));
} catch(InvalidQueryExpression $i) {
 //echo 'Invalid query: '. $i->getMessage() . "\n";
}

####################################################################
# Declare some variables as place holders for the select object
####################################################################
$email = '';
$rawurl = '';
$finishedurl = '';
$bucket = '';
$id = '';
$phone = '';
$receiptHandle = '';
$filename = '';
$localfilename = ""; // this is a local variabel used to store the content of the s3 object

###################################################################
# Now we are going to loop through the response object to get the 
# values of the returned object
##################################################################
foreach ($iterator as $item) {
    //echo "Item: " . $item['Name'] . "\n";
     foreach ($item['Attributes'] as $attribute) {
        switch ($attribute['Name']) {
            case "id": 
                //echo "id Value is: ". $attribute['Value']."\n";
                $id = $attribute['Value'];
                break;
            case "email":
                //echo "Email Value is: ". $attribute['Value']."\n";
                $email = $attribute['Value']; 
                break;
            case "bucket":
                //echo "Bucket Value is: ". $attribute['Value']."\n";
                $bucket = $attribute['Value'];
                break;
           case "rawurl":
                //echo "RawURL Value is: ". $attribute['Value']."\n";
                $rawurl = $attribute['Value'];
                break;
           case "finishedurl":
                //echo "Finished URL Value is: ". $attribute['Value']."\n";
                $finishedurl = $attribute['Value'];
                break;
            case "receiptHandle":
                //echo "Receipt Handle is: ". $attribute['Value']."\n";
                $receiptHandle = $attribute['Value'];
                break;
           case "filename":
                //echo "Filename Value is: ". $attribute['Value']."\n";
                $filename = $attribute['Value'];
                break;
           case "phone":
                //echo "Phone Value is: ". $attribute['Value']."\n";
                $phone = $attribute['Value'];
                break;
          // default: 
                //echo "Unable to figure out - " . $attribute['Name'] ." = " . $attribute['Value'];

        }
    }
}

################################################
# S3
# Mark object for expiration in 10 minutes
################################################

/*
    The expiration date is currently set to ONE DAY
    Couldn't make it work with 10 minutes, as requested
*/
$expiry = "" . gmdate('r',strtotime('+10 minutes')) . "";
$client->putBucketLifecycle(
    array(
        'Bucket' => $bucket, 
        'Rules' => array( array(
                'ID' => $NAME,
                'Expiration' => array(
                        ######################################
                        # Cant make this work
                        # Results in malformed XML error
                        ######################################
                        //'Date' => $expiry,
                        'Days' => '1'
                ),
                'Prefix' => '',
                'Status' => 'Enabled',
        )),
    )
);

################################################
# SQS
# Delete the message to make sure it won't be processed two times
# The receipt handle is necessary to perform this
################################################
//echo "Deleting handle.";
$result = $sqsclient->deleteMessage(array(
    'QueueUrl' => $sqs_queue_url,
    'ReceiptHandle' => $receiptHandle,
));
//echo "Handle deleted?";

#############################################
# Create SNS Simple Notification Service Topic for subscription
##############################################

$topic = "$NAME-sns";

$result = $snsclient->listTopics();
$topicArn="";

foreach ($result->getPath('Topics/*/TopicArn') as $topicArnTmp) {

    if ( strstr($topicArnTmp,$topic) ) {
        $topicArn=$topicArnTmp;
    }
}

//echo "TOPIC ARN=($topicArn)";

try {
$result = $snsclient->subscribe(array(
    'TopicArn' => $topicArn,
    'Protocol' => 'sms',
    'Endpoint' => $phone,
)); } catch(InvalidParameterException $i) {
 //echo 'Invalid parameter: '. $i->getMessage() . "\n";
} 

//echo " Suscribed . Sending ";
#####################################################
# SNS publishing of message to topic - which will be sent via SMS
#####################################################
try {
$result = $snsclient->publish(array(
    'TopicArn' => $topicArn,
    'TargetArn' => $topicArn,
    'Message' => "Image uploaded",
    'Subject' => "$finishedurl",
    'MessageStructure' => 'sms',
)); } catch(SqsException $i) {
 //echo '(gpa)Invalid parameter: '. $i->getMessage() . "\n";
} 

//echo " Sent ";

?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css"/>
    <title>ITMO 544 - Cleanup.php</title>
    
</head>
<body>
    <div id="main">
        <header>
            <h1>Picture Uploader</h1>

            <p>A mini project for ITMO 544 - Cloud Computing</p>
            <p>Illinois Institute of Technology</p>
            <p><a href="https://github.com/gpuenteallott/itmo544-CloudComputing-mp1">Project in GitHub</a></p>
        </header>

        <h2>Clean Up</h2>
        <p>What have you done?!</p>
        <ol>
            <li>Value retrieved from the SQS message</li>
            <li>Information about the image retrieved from SimpleDB</li>
            <li>Image expiration date set to one day</li>
            <li>SQS message deleted</li>
            <li>Given phone suscribed to receive text messages</li>
            <li>Text message sent to <? echo $phone ?> including URL to the new image</li>
        </ol>

        <p>If you didn't receive a text message with the URL, make sure you subscribe and then try again.</p>
        <p>Thanks!</p>

         <p class="next">Start again --> <a href="index.php">Index</a></p>
    </div>
</body>
</html>