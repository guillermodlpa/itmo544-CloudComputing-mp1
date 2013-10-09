#!/bin/bash

################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
#
# Script A - 
# installenv.sh will run and launch ec2-run-instances quantity of 2 instances
# launching an instance of Ubuntu server and passing the install.sh to it via -f
#
# Usage:
#        ./installenv.sh name AWS_ACCESS_KEY AWS_SECRET KEY
#        name is the identificator of instances, keypair and security group
#
# Example: ./installenv.sh itmo544 key secret
################################################


EC2_API_TOOLS=~/ec2-api-tools-1.6.10.1
SCRIPT_FILE=install.sh
INSTANCE_TYPE=t1.micro
REGION=us-east-1 
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
export AWS_ACCESS_KEY=$2
export AWS_SECRET_KEY=$3
export JAVA_HOME=/usr
export EC2_HOME=$EC2_API_TOOLS
export PATH=$PATH:$EC2_HOME/bin


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
ec2addgrp --region=$REGION $NAME -d "Security group for the instances with identifier $NAME"
ec2-authorize $NAME -p 80 -s 0.0.0.0/0
ec2-authorize $NAME -p 22 -s 0.0.0.0/0

# Launch instances
# using the selected keypair and security group
ec2-run-instances --region $REGION $AMI -n $NUMBER_OF_INSTANCES -t $INSTANCE_TYPE -f $SCRIPT_FILE -k $NAME -g $NAME 

# Name instances
#> tmp
#cat tmp | grep -o -E [[:space:]]i-[A-Za-z_0-9]{8} > tmp
