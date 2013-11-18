<h1>Cloud Computing Mini Project 1 Fall 2013</h1>

Mini Project 1 for ITMO 544 Cloud Computing at Illinois Institute of Technology

Student: Guillermo de la Puente
Professor: Jeremy Hajek

Fall 2013

See MP1-assignment-v2.pdf

To execute:

1. using the command line, go to the deployment directory
2. execute this command: 
    ./installenv.sh IDENTIFIER YOUR_AWS_KEY YOUR_AWS_SECRET
    IDENTIFIER is just a name used to tag your resources

3. Go to the AWS Console and wait until the resources are ready
4. You can access the service using the load balancer public IP or using the IP of an individual server

The default paths for the EC2 and ELB toolkits are:

~/ec2-api-tools-1.6.10.1
~/ElasticLoadBalancing-1.0.17.0

If you wish to change them, modify the paths at the begining of the installenv.sh script
