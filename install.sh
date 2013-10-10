#!/bin/bash

################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
#
# Setup of environment to run PHP project
#
# Script B -
# install.sh will pull all system pre-reqs and required libraries and install AWS
# SDK library via composer as well as wget your project down and deploy it to the correct
# directory copying your custom-config.php to the correct location.
################################################

sudo apt-get -y update 
sudo apt-get -y install git apache2 php5 php5-curl php5-cli curl unzip php5-gd

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