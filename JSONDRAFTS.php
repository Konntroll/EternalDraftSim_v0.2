<?PHP
require_once "login.php";

$sql = "SELECT draft FROM drafts";
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);

$array = array();
$index = 0;
while ($row = $result->fetch_assoc()) {
	$array[$index] = $row;
	$index++;
}

$fp = fopen('drafts.json', 'w');
fwrite($fp, json_encode($array));
fclose($fp);

?>