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

################################################
# SQS
# Delete the message to make sure it won't be processed two times
################################################
/*
$result = $client->deleteMessage(array(
    // QueueUrl is required
    'QueueUrl' => "$NAME-sqs",
    // ReceiptHandle is required
    'ReceiptHandle' => 'string',
));
*/



#############################################
# Create SNS Simple Notification Service Topic for subscription
##############################################
/*
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
    'Message' => 'Your image has been uploaded',
    'Subject' => $url,
    'MessageStructure' => 'sms',
));
*/