<?php
################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
#
# Student: Guillermo de la Puente
#          https://github.com/gpuenteallott
#
# install.php
# Is executed after deploying all the environment
# 
# Tasks performed:
#      1 - Create SDB domain
#      2 - Create SQS queue
#      3 - Create SNS topic and set its attributes
#
# Usage:
#        php install.php name credentials_file
#        name is the identifier for SDB domain, SQS queue and SNS topic
#        credentials_file is the path to the credentials file using PHP api convention
#
# Example: ./install.php itmo544 "/var/www/itmo544-CloudComputing-mp1/custom-config.php"
#
################################################

require 'vendor/autoload.php';

use Aws\Common\Aws;
use Aws\SimpleDb\SimpleDbClient;
use Aws\S3\S3Client;
use Aws\Sns\SnsClient;
use Aws\Sqs\sqsclient;
use Aws\Sns\Exception\InvalidParameterException;

# Use a config file
$aws = Aws::factory($argv[2]);

$sdbclient = $aws->get('SimpleDb'); 
$snsclient = $aws->get('Sns'); 
$sqsclient = $aws->get('Sqs');

$NAME=$argv[1];
$NAME_SDB = str_replace("-", "", $NAME)."sdb";

# Create SimpleDB domain
# sdb must use _ instead of - because the query syntax doesn't allow dashes
$result = $sdbclient->createDomain(array(
    'DomainName' => "$NAME_SDB",
));
echo "SDB: domain $NAME_SDB created\n";

# Create SQS queue
$result = $sqsclient->createQueue(array(
	'QueueName' => "$NAME-sqs",
	'Attributes' => array(),
));
echo "SQS: queue $NAME-sqs created\n";

# Create SNS Client with a topic that matches the argument name
$result = $snsclient->createTopic(array(
    'Name' => "$NAME-sns",
));
echo "SNS: topic $NAME-sns created\n";

# Set SNS topic attributes
$topicArn = $result['TopicArn'];
$result = $snsclient->setTopicAttributes(array(
    'TopicArn' => $topicArn,
    'AttributeName' => 'DisplayName',
    'AttributeValue' => "$NAME",
));