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


// add code to consume the Queue to make sure the job is done
// add code to send the SMS message of the finished S3 URL
// Set object expire to remove the image in one day
// set ACL to public