#!/bin/bash

################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
#
# Student: Guillermo de la Puente
#          https://github.com/gpuenteallott
#
# Script A - 
# installenv.sh will run and launch ec2-run-instances quantity of 2 instances
# launching an instance of Ubuntu server and passing the install.sh to it via -f
#
# Script C -
# will deploy the load balancer and register your two instances with the load
# balancer. Also create a SimpleDB domain, an SQS queue, and SNS Topic – it can be the
# same script or another PHP script run from the command line 1 time
#
# Usage:
#        ./installenv.sh name name AWS_ACCESS_KEY AWS_SECRET KEY
#        name is the identificator of instances, keypair and security group
#
# Example: ./installenv.sh itmo544 key secret
################################################ 

EC2_API_TOOLS=~/ec2-api-tools-1.6.10.1
AWS_ELB_TOOLS=~/ElasticLoadBalancing-1.0.17.0
SCRIPT_FILE=install.sh
INSTANCE_TYPE=t1.micro
REGION=us-east-1
AVAIL_ZONES=us-east-1a,us-east-1b,us-east-1c,us-east-1d
AMI=ami-ad83d7c4
NUMBER_OF_INSTANCES=2
# http://cloud-images.ubuntu.com/locator/ec2/

# Check number of arguments
if [ $# != 3 ]; then
	echo 'Usage:'
	echo '        name ./installenv.sh AWS_ACCESS_KEY AWS_SECRET KEY'
	echo 'name is the identificator of instances, keypair and security group'
	exit 1
fi

# Export variables
NAME=$1
NAME_ELB=$NAME-elb
NAME_GRP=$NAME-grp
export AWS_ACCESS_KEY=$2
export AWS_SECRET_KEY=$3
export JAVA_HOME=/usr
export EC2_HOME=$EC2_API_TOOLS
export AWS_ELB_HOME=$AWS_ELB_TOOLS
export PATH=$PATH:$EC2_HOME/bin:$AWS_ELB_HOME/bin


# Create keypair, or use existing one
if [ -e "$NAME.priv" ]
then
	echo "EC2: Using existing keypair $NAME.priv"
fi
if [ ! -e "$NAME.priv" ]
then
	ec2addkey $NAME > $NAME.priv
	chmod 600 $NAME.priv
	echo "EC2: Keypair $NAME.priv created"
fi

# Create security group, or use existing one
# Access to ports 80 and 22 from all IP adresses
printf "EC2: Security group   " && ec2addgrp --region=$REGION $NAME_GRP -d "Security group for the instances with identifier $NAME"
printf "EC2: Security group   " && ec2-authorize $NAME_GRP -p 80 -s 0.0.0.0/0
printf "EC2: Security group   " && ec2-authorize $NAME_GRP -p 22 -s 0.0.0.0/0
printf "EC2: Security group   " && ec2-authorize $NAME_GRP -p 8080 -s 0.0.0.0/0

# Launch instances
# using the selected keypair and security group
# Save the ID of the new instances
ec2-run-instances --region $REGION $AMI -n $NUMBER_OF_INSTANCES -t $INSTANCE_TYPE -f $SCRIPT_FILE -k $NAME -g $NAME_GRP | awk '{print $2}' | grep -E -o i-[0-9a-zA-Z]* > instance_ids
echo "EC2: $NUMBER_OF_INSTANCES instances started"

# Change name to the instances
i=1
while read line
do
    printf "EC2: " && ec2tag $line --tag Name=$NAME-$i
    line=
    i=$(($i + 1))
done < instance_ids

#Create aws credentials file
# ELB needs this file, the environment variables wont work :(
echo AWSAccessKeyId=$2 > aws_credentials_file
echo AWSSecretKey=$3 >> aws_credentials_file

# Create Elastic Load Balancer
printf "Load Balancer:  " &&  elb-create-lb $NAME_ELB --listener "lb-port=80,instance-port=8080,protocol=http" --region $REGION --aws-credential-file aws_credentials_file -z $AVAIL_ZONES

# Configure healthchek, otherwise it will fail automatically
# Thresholds, interval and timeout set to default values
printf "Load Balancer Health Check:  " &&  elb-configure-healthcheck $NAME_ELB --healthy-threshold 10 --interval 30 -t http:80/index.php --timeout 5 --unhealthy-threshold 2 --aws-credential-file aws_credentials_file

i=1
while read line
do
    echo "ELB: Step $i - Registered instances"
    elb-register-instances-with-lb $NAME_ELB --instances $line --aws-credential-file aws_credentials_file
    line=
    i=$(($i + 1))
done < instance_ids

# Remove aws credentials file
rm aws_credentials_file
rm instance_ids

echo "Load Balancer: setup completed. It might take a few minutes to the system to be ready"

# Setup aws credentials file for PHP script
cp custom-config-template.php custom-config.php
sed -i "s/AWS_ACCESS_KEY/$2/g" custom-config.php
sed -i "s/AWS_SECRET_KEY/$3/g" custom-config.php

# Execute PHP setup script
php install.php itmo544 "/var/www/itmo544-CloudComputing-mp1/custom-config.php"
