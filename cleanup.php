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
print "started";
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
var_export($sqs_queue_url->getkeys());
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
    echo "SQS: " . $messageBody . "\n";
    $mbody=$messageBody;
}

if ( is_null($mbody) ) {
    echo "variable mbody is null. Reload after a few seconds";
    exit("variable mbody is null. Reload after a few seconds");
}

####################################################################
# Select in the SimpleDB using the id retrieved from SQS
####################################################################
$exp = "select * from $NAME_SDB where id = '$mbody'";
echo "\n".$exp."\n";

try {
$iterator = $sdbclient->getIterator('Select', array(
    'SelectExpression' => $exp,
));
} catch(InvalidQueryExpression $i) {
 echo 'Invalid query: '. $i->getMessage() . "\n";
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
    echo "Item: " . $item['Name'] . "\n";
     foreach ($item['Attributes'] as $attribute) {
        switch ($attribute['Name']) {
            case "id": 
                echo "id Value is: ". $attribute['Value']."\n";
                $id = $attribute['Value'];
                break;
            case "email":
                echo "Email Value is: ". $attribute['Value']."\n";
                $email = $attribute['Value']; 
                break;
            case "bucket":
                echo "Bucket Value is: ". $attribute['Value']."\n";
                $bucket = $attribute['Value'];
                break;
           case "rawurl":
                echo "RawURL Value is: ". $attribute['Value']."\n";
                $rawurl = $attribute['Value'];
                break;
           case "finishedurl":
                echo "Finished URL Value is: ". $attribute['Value']."\n";
                $finishedurl = $attribute['Value'];
                break;
            case "receiptHandle":
                echo "Receipt Handle is: ". $attribute['Value']."\n";
                $receiptHandle = $attribute['Value'];
                break;
           case "filename":
                echo "Filename Value is: ". $attribute['Value']."\n";
                $filename = $attribute['Value'];
                break;
           case "phone":
                echo "Phone Value is: ". $attribute['Value']."\n";
                $phone = $attribute['Value'];
                break;
           default: 
                echo "Unable to figure out - " . $attribute['Name'] ." = " . $attribute['Value'];

        }
    }
}

################################################
# SQS
# Delete the message to make sure it won't be processed two times
# The receipt handle is necessary to perform this
################################################
echo "Deleting handle.";
$result = $sqsclient->deleteMessage(array(
    'QueueUrl' => $sqs_queue_url,
    'ReceiptHandle' => $receiptHandle,
));
echo "Handle deleted?";

#############################################
# Create SNS Simple Notification Service Topic for subscription
##############################################

$topic = "$NAME-sns";

$result = $snsclient->createTopic(array(
    // Name is required
    'Name' => $topic,
));

$topicArn = $result['TopicArn'];

$result = $snsclient->setTopicAttributes(array(
    // TopicArn is required
    'TopicArn' => $topicArn,
    // AttributeName is required
    'AttributeName' => 'DisplayName',
    'AttributeValue' => "$NAME",
));

try {
$result = $snsclient->subscribe(array(

    'TopicArn' => $topicArn,

    'Protocol' => 'sms',
    'Endpoint' => $phone,
)); } catch(InvalidParameterException $i) {
 echo 'Invalid parameter: '. $i->getMessage() . "\n";
} 

#####################################################
# SNS publishing of message to topic - which will be sent via SMS
#####################################################

$result = $snsclient->publish(array(
    'TopicArn' => $topicArn,
    'TargetArn' => $topicArn,
    // Message is required
    'Message' => "Done! $NAME",
    'Subject' => "Done! Your image has been processed. Download it from $finishedurl",
    'MessageStructure' => 'sms',
));



