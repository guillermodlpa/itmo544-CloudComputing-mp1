#!/bin/bash

################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
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
#        ./installenv.sh name AWS_ACCESS_KEY AWS_SECRET KEY
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
	echo '        ./installenv.sh AWS_ACCESS_KEY AWS_SECRET KEY'
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
	echo 'Using existing keypair'
fi
if [ ! -e "$NAME.priv" ]
then
	ec2addkey $NAME > $NAME.priv
	chmod 600 $NAME.priv
	echo 'Keypair $NAME.priv created'
fi

# Create security group, or use existing one
# Access to ports 80 and 22 from all IP adresses
ec2addgrp --region=$REGION $NAME_GRP -d "Security group for the instances with identifier $NAME"
ec2-authorize $NAME_GRP -p 80 -s 0.0.0.0/0
ec2-authorize $NAME_GRP -p 22 -s 0.0.0.0/0

# Launch instances
# using the selected keypair and security group
# Save the ID of the new instances
ec2-run-instances --region $REGION $AMI -n $NUMBER_OF_INSTANCES -t $INSTANCE_TYPE -f $SCRIPT_FILE -k $NAME -g $NAME_GRP | awk '{print $2}' | grep -E -o i-[0-9a-zA-Z]* > instance_ids

#Create aws credentials file
# ELB needs this file, the environment variables wont work :(
echo AWSAccessKeyId=$2 > aws_credentials_file
echo AWSSecretKey=$3 >> aws_credentials_file

# Create Elastic Load Balancer
elb-create-lb $NAME_ELB --listener "lb-port=80,instance-port=8080,protocol=http" --region $REGION --aws-credential-file aws_credentials_file -z $AVAIL_ZONES

while read line
do
    echo "Registering $line in the load balancer"
    elb-register-instances-with-lb $NAME_ELB --instances $line --aws-credential-file aws_credentials_file
    line=
done < instance_ids

# Remove aws credentials file
rm aws_credentials_file