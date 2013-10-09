#!/bin/bash

################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
#
# Setup of environment to run PHP project
################################################

sudo apt-get -y update 
sudo apt-get -y install git apache2 php5 php5-curl php5-cli curl

sudo service apache2 restart
curl -sS https://getcomposer.org/installer | php
php composer.phar install