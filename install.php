<?php

################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
#
# Student: Guillermo de la Puente
#          https://github.com/gpuenteallott
#
# Script C -
# will deploy the load balancer and register your two instances with the load
# balancer. Also create a SimpleDB domain, an SQS queue, and SNS Topic – it can be the
# same script or another PHP script run from the command line 1 time
#
# Usage:
#        ./install.php name custom_config_file_path
#        name is the identificator of SDB domain,QS queue and SNS topic
#        custom_config_file_path is the path to the php file that returns information with the aws credentials
#
# Example: ./install.php itmo544 "/var/www/itmo544-CloudComputing-mp1/custom-config.php"
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

# Create SimpleDB domain
$result = $sdbclient->createDomain(array(
    'DomainName' => "$argv[1]-sdb",
));

# Create SQS queue
$result = $sqsclient->createQueue(array(
	'QueueName' => "$argv[1]-sqs",
	'Attributes' => array(),
));

# Create SNS Client with a topic that matches the argument name
$result = $snsclient->createTopic(array(
    'Name' => "$argv[1]-sns",
));