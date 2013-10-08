<!DOCTYPE html>
<?php

// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';
header("Content-type: text/plain; charset=utf-8");

use Aws\SimpleDb\SimpleDbClient;
use Aws\S3\S3Client;
use Aws\Sns\SnsClient;
use Aws\Sqs\sqsclient;
use Aws\Sns\Exception\InvalidParameterException;


//aws factory
$aws = Aws::factory('/var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php');

$client = $aws->get('S3'); 

$sdbclient = $aws->get('SimpleDb'); 

$snsclient = $aws->get('Sns'); 

$sqsclient = $aws->get('Sqs');


$email = str_replace("@","-",$_POST["email"]); 
$bucket = str_replace("@", "-",$_POST["email"]).time(); 
$phone = $_POST["phone"];
$topic = explode("-",$email );
#echo $topic[0]."\n";
#############################################
# Create SNS Simple Notification Service Topic for subscription
##############################################
$result = $snsclient->createTopic(array(
    // Name is required
    'Name' => $topic[0],
));

$topicArn = $result['TopicArn'];

echo $topicArn ."\n";
echo $phone ."\n";

$result = $snsclient->setTopicAttributes(array(
    // TopicArn is required
    'TopicArn' => $topicArn,
    // AttributeName is required
    'AttributeName' => 'DisplayName',
    'AttributeValue' => 'aws544',
));

try {
$result = $snsclient->subscribe(array(
    // TopicArn is required
    'TopicArn' => $topicArn,
    // Protocol is required
    'Protocol' => 'sms',
    'Endpoint' => $phone,
)); } catch(InvalidParameterException $i) {
 echo 'Invalid parameter: '. $i->getMessage() . "\n";
} 
/*
$result = $snsclient->createPlatformApplication(array(
    // Name is required
    'Name' => 'pix',
    // Platform is required
    'Platform' => 'ADM',
    // Attributes is required
    'Attributes' => array(
        // Associative array of custom 'String' key names
        'EventEndpointCreated' => $topicArn,
        // ... repeated
    ),
));
$PlatformApplicationArn=$result['PlatformApplicationArn'];

$result = $snsclient->createPlatformEndpoint(array(
    // PlatformApplicationArn is required
    'PlatformApplicationArn' => $PlatformApplicationArn,
    // Token is required
    'Token' => 'string',
    'CustomUserData' => 'string',
    'Attributes' => array(
        // Associative array of custom 'String' key names
        'String' => 'string',
        // ... repeated
    ),
));
# see send for actual sending of text message
*/
###############################################################
# Create S3 bucket
############################################################
$result = $client->createBucket(array(
    'Bucket' => $bucket
));

// Wait until the bucket is created
$client->waitUntil('BucketExists', array('Bucket' => $bucket));

$uploaddir = '/tmp/';
$uploadfile = $uploaddir . basename($_FILES['uploaded_file']['name']);
echo $uploadfile. "\n";
if (move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $uploadfile)) {
    echo "File is valid, and was successfully uploaded.\n";
} else {
    echo "Possible file upload attack!\n";
}
$pathToFile = $uploaddir.$_FILES['uploaded_file']['name'];
#echo 'Here is some more debugging info:';
#print_r($_FILES);

// Upload an object by streaming the contents of a file
// $pathToFile should be absolute path to a file on disk
$result = $client->putObject(array(
    'Bucket'     => $bucket,
    'Key'        => $_FILES['uploaded_file']['name'],
    'SourceFile' => $pathToFile,
    'Metadata'   => array(
        'timestamp' => time(),
        'md5' =>  md5_file($pathToFile),
    )
));
print "#############################\n";
var_export($result->getkeys());
// this gets all the key value pairs and exports them as system variables making our lives nice so we don't have to do this manually. 

$url= $result['ObjectURL'];
####################################################
# SimpleDB create here - note no error checking
###################################################
$result = $sdbclient->createDomain(array(
    // DomainName is required
    'DomainName' => $email, 
));

$result = $sdbclient->putAttributes(array(
    // DomainName is required
    'DomainName' => $email,
   // ItemName is required
    'ItemName' => 'images',
    // Attributes is required
    'Attributes' => array(
        array(
            // Name is required
           'Name' => 'rawurl',
            // Value is required
            'Value' => $url,
        ),
       
    ),
));

$domains = $sdbclient->getIterator('ListDomains')->toArray();
var_export($domains);
// Lists an array of domain names, including "mydomain"

$exp="select * from  `$email`";

$result = $sdbclient->select(array(
    'SelectExpression' => $exp 
));
foreach ($result['Items'] as $item) {
    echo $item['Name'] . "\n";
    var_export($item['Attributes']);
}
#####################################################
# SNS publishing of message to topic - which will be sent via SMS
#####################################################
$result = $snsclient->publish(array(
    'TopicArn' => $topicArn,
    'TargetArn' => $topicArn,
    // Message is required
    'Message' => 'Your image has been uploaded',
    'Subject' => $url,
    'MessageStructure' => 'sms',
));
?>
<html>
<head>
<title>Title of the document</title>
</head>

<body>
Thank you <? echo $bucket ?>
</body>
</html>

</html>
ubuntu@ip-10-151-65-11:/var/www$ 

