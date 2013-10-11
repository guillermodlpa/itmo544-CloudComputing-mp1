<!DOCTYPE html>
<html>
<head>
	<title>ITMO 544 - Index.php</title>
</head>
<body>

	<h1>Picture Uploader</h1>

	<p>A mini project for ITMO 544 - Cloud Computing</p>
	<p>Illinois Institute of Technology</p>
	<p>Student: Guillermo de la Puente</p>
	<p><a href="https://github.com/gpuenteallott/itmo544-CloudComputing-mp1">Project in GitHub</a></p>

	<h2>Fill the following form</h2>

	<form action="process.php" method="post" enctype="multipart/form-data">
	  <p><label>Email: <input type="text" name="email" ></label></p>
	  <p><label>Cell Number: <input type="text" name="phone" placeholder="1-333-555-7777"></label></p>
	  <p><label>Choose Image: <input type="file" name="uploaded_file" id="uploaded_file"></label></p>
	  <input type="submit"  value="submit it!" >
	</form>
	<p>Note: You may receive notifications via SMS</p>

</body>

</html>
