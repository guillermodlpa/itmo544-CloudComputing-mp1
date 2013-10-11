#!/bin/bash

################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
#
# Student: Guillermo de la Puente
#          https://github.com/gpuenteallott
#
# Setup of environment to run PHP project
#
# Script B -
# install.sh will pull all system pre-reqs and required libraries and install AWS
# SDK library via composer as well as wget your project down and deploy it to the correct
# directory copying your custom-config.php to the correct location.
################################################

# This are the AWS credentials for the server
# They will be dinamically added from this file by installenv.sh
AWS_ACCESS_KEY=
AWS_SECRET_KEY=

sudo apt-get -y update 
sudo apt-get -y install git apache2 php5 php5-curl php5-cli curl unzip php5-gd

# To allow the load balancer to listen in port 80, we must allow apache to work in other port as well
# We will use port 8080
sed -i 's/Listen 80/Listen 80\nListen 8080/g' /etc/apache2/ports.conf

cp /etc/apache2/sites-enabled/000-default 000-default.tmp
sed -i 's/*:80/*:8080/g' 000-default.tmp
echo "" >> /etc/apache2/sites-enabled/000-default
cat 000-default.tmp >> /etc/apache2/sites-enabled/000-default
rm 000-default.tmp

# Restart Tomcat to apply port changes and recognize curl
sudo service apache2 restart

# Download composer
cd /var/www
curl -sS https://getcomposer.org/installer | php

# Get project
sudo wget https://github.com/gpuenteallott/itmo544-CloudComputing-mp1/archive/master.zip
sudo unzip master.zip
shopt -s dotglob # include hidden files in mv operation
sudo mv itmo544-CloudComputing-mp1-master/* /var/www
shopt -u dotglob # restore default behaviour
sudo rm master.zip
sudo rmdir itmo544-CloudComputing-mp1-master
sudo rm index.html # remove default apache2 welcome

# Install libraries
sudo php composer.phar install

cp -f custom-config-template.php /var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php

# Setup aws credentials file for PHP
# The string "########" is used to avoid problems with slashes while using 'sed'
sed -i "s/AWS_ACCESS_KEY/$AWS_ACCESS_KEY/g" /var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php
sed -i "s/AWS_SECRET_KEY/$AWS_SECRET_KEY/g" /var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php
# The secret came with slashes converted into ######## to avoid problems
sed -i "s/########/\//g" /var/www/vendor/aws/aws-sdk-php/src/Aws/Common/Resources/custom-config.php