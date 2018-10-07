<?PHP
require_once "login.php";
require_once "boostgen.php";
if (isset($_POST['draft'])) {
	$array = json_decode($_POST['draft']);
	$line = $array[0][11][0] + 100;
	$conn->query("UPDATE metrics SET value = value + 1 WHERE param = 'compl'");
	foreach ($array as $round => &$subarray) {
		array_pop($subarray);
		if ($round % 2 == 0) {
			$append = boostgen("OOP");
		} else {
			$append = boostgen("TET");
		}
		foreach ($append as &$card) {
			$card = $card->number;
		}
		array_unshift($subarray, $append);
		foreach ($subarray as &$pack) {
			$pack = implode(", ", $pack);
		}
		$subarray = implode(" / ", $subarray);
	}
	if ($line % 2 == 0) {
		$oddline = $line - 1;
		$sql = "SELECT draft FROM drafts WHERE line=" . $oddline . "";
		$result = $conn->query($sql);
		if (!$result) die ("Failed: " . $conn->error);
		while ($row = $result->fetch_row()) {
			$prev_draft = $row[0];
		}
		$prev_draft = explode(" * ", $prev_draft);
		$pack_one = $prev_draft[1];
		$pack_three = $prev_draft[3];
		$prev_draft[1] = $array[1];
		$prev_draft[3] = $array[3];
		$array[1] = $pack_one;
		$array[3] = $pack_three;
		$prev_draft = implode (" * ", $prev_draft);
		$conn->query("UPDATE drafts SET draft = \""	. $prev_draft . "\" WHERE line = " . $oddline . ""); 
		$array = implode(" * ", $array);
		$conn->query("UPDATE drafts SET draft = \""	. $array . "\" WHERE line = " . $line . "");
	} else {
		$array = implode(" * ", $array);
		$conn->query("UPDATE drafts SET draft = \""	. $array . "\" WHERE line = " . $line . "");
	}
}

?>