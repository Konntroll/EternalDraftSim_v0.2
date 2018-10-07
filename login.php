<?PHP
	//establish connection to MySQL and access the eternal DB
	$servername = "localhost";
	$username = "root";
	$password = "";
	$dbname = "eternal";
	
	$conn = new mysqli($servername, $username, $password, $dbname);
	
	//provisional clause for output in case of connection failure
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
?>