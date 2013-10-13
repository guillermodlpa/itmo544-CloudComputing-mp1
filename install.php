<?php
################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
#
# Student: Guillermo de la Puente
#          https://github.com/gpuenteallott
#
# process.php
# - add uploaded photo to S3 bucket - set metadata tags for a md5 hash and an epoch
#   timestamp
# - return S3 URI for uploaded object
# - create Item in SimpleDB that contains:
#     rawurl, email, bucketname, filename, phone, id (using uniqid), finishedurl
# - Use SQS to place a queue with the id as the sqs body
# - use the system sendmail to send an email (really easy) thanking them for submitting the
#   altered image
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
$NAME_SDB = str_replace("-", "_", $NAME);

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