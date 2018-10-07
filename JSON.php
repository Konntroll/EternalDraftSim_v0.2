<?PHP
require_once "login.php";

$sql = "SELECT * FROM TET";
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);

$array = array();
$index = 0;
while ($row = $result->fetch_assoc()) {
	$array[$index] = $row;
	$index++;
}

$fp = fopen('tetcards.json', 'w');
fwrite($fp, json_encode($array));
fclose($fp);

?>