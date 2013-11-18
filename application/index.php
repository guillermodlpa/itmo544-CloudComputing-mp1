<?php
################################################
# Illinois Institute of Technology
# ITMO 544 Cloud Computing - Mini Project 1 
#
# Student: Guillermo de la Puente
#          https://github.com/gpuenteallott
#
# index.php
# - will have a basic form that asks for email, phone, and a picture to upload and post this
#   data to process.php
# - Send a subscription notice to the user so that they may receive SMS later at the end of
#   the project ( this could be done in part 1C as well)
#
################################################
?>

<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" href="style.css"/>
	<title>ITMO 544 - Index.php</title>
</head>
<body>

	<div id="main">
		<header>
			<h1>Picture Uploader</h1>

			<p>A mini project for ITMO 544 - Cloud Computing</p>
			<p>Illinois Institute of Technology</p>
			<p><a href="https://github.com/gpuenteallott/itmo544-CloudComputing-mp1">Project in GitHub</a></p>
		</header>
		<h2>Fill the following form</h2>

		<form action="process.php" method="post" enctype="multipart/form-data">
		  <p><label>Email: <input type="text" name="email" required/></label></p>
		  <p><label>Cell Number: <input type="text" name="phone" placeholder="1-333-555-7777" required/></label></p>
		  <p><label>Choose Image: <input type="file" name="uploaded_file" id="uploaded_file" required/></label></p>
		  <input type="submit"  value="submit it!"/>
		</form>
		<p>Note: You may receive notifications via SMS</p>
	</div>
</body>

</html>
