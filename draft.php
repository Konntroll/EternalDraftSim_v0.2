<?PHP
require_once "login.php";
require_once "classes.php";
require_once "boostgen.php";
require_once "pick.php";

$sql = "CREATE TABLE IF NOT EXISTS drafts (
	line SMALLINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	draft VARCHAR(1605) NOT NULL
	)";
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);

set_time_limit(0);

for ($index = 0; $index < 100; $index++) {
	
	$odds = array(); //this creates the twelve simulated players that will be passing to the player in rounds 1 & 3
	for ($odd = 0; $odd < 12; $odd++) {
		$odds[$odd] = new Drafter;
	}

	$evens = array(); //this creates the twelve simulated players that will be passing to the player in rounds 2 & 4
	for ($even = 0; $even < 12; $even++) {
		$evens[$even] = new Drafter;
	}

	$packs = array(); //this will hold packs for simulated players to make picks from
	for ($clip = 0; $clip < 8; $clip++) {
		if ($clip % 2 == 0) {
			for ($pack = 0; $pack < 12; $pack++) {
				$packs[$clip][$pack] = boostgen('OOP');
			}
		} else {
			for ($pack = 0; $pack < 12; $pack++) {
				$packs[$clip][$pack] = boostgen('TET');
			}
		}
	}

	$pass = array(); //this will hold packs passed by simulated players to the player as well as unopened packs that the player picks from at the start of each round
	for ($clip = 0; $clip < 4; $clip++) {
		$pass[$clip] = array();
		if ($clip % 2 == 0) {
			$pass[$clip][0] = boostgen('OOP');
		} else {
			$pass[$clip][0] = boostgen('TET');
		}
		for ($pack = 1; $pack < 12; $pack++) {
			$pass[$clip][$pack] = array();
		}
	}
	
	$oddrnd = 0; //round index for boosters shown to simulated players that will be passing to the player in rounds 1 & 3
	$evenrnd = 4; //round index for boosters shown to simulated players that will be passing to the player in rounds 2 & 4
	
	for ($pick = 0; $pick < 48; $pick++) { //this will populate the packs to be displayed to the player with what remains of the packs picked from by simulated players
		for ($drafter = 0; $drafter < 12; $drafter++) { //a player carrousel that makes each simulated player take his pick from a corresponding booster
			if (count($packs[$oddrnd][$drafter]) == 0) $oddrnd++; //checks if the current pack for odd players is empty and, if so, moves on to the next pack
			pick($packs[$oddrnd][$drafter], $drafter, $odds); //feeds the pack into the pick function, which returns the index of the card picked by the simulated player
			if (count($packs[$oddrnd][$drafter]) != 0 && $oddrnd % 2 == 0) { //if there is at least one card in the pack and it is either round 1 or 3, the pack is saved to be displayed to the player
				//$index = 12 - count($packs[$oddrnd][$drafter]); //determines the index of a corresponding pick array to store the pack
				$pass[$oddrnd][12 - count($packs[$oddrnd][$drafter])] = $packs[$oddrnd][$drafter]; //assigns the pack to the corresponding pick array (position 0 is filled with a full booster)
			}
			if (count($packs[$evenrnd][$drafter]) == 0) $evenrnd++; //checks if the current pack for even players is empty and, if so, moves on to the next pack
			pick($packs[$evenrnd][$drafter], $drafter, $evens); //feeds the pack into the pick function, which returns the index of the card picked by the simulated player
			if (count($packs[$evenrnd][$drafter]) != 0 && $evenrnd % 2 == 1) { //if there is at least one card in the pack and it is either round 2 or 4, the pack is saved to be displayed to the player
				//$index = 12 - count($packs[$evenrnd][$drafter]); //determines the index of a corresponding pick array to store the pack
				$pass[$evenrnd - 4][12 - count($packs[$evenrnd][$drafter])] = $packs[$evenrnd][$drafter]; //assigns the pack to the corresponding pick array (position 0 is filled with a full booster)
			}	
		}
		array_push($packs[$oddrnd], array_shift($packs[$oddrnd])); //rotates the packs for odd players to make their next pick
		array_push($packs[$evenrnd], array_shift($packs[$evenrnd])); //rotates the packs for even players to make their next pick
	}

	foreach ($pass as &$clip) { //this will replace each card with its number as we no longer need objects, just numbers, and then collapse the matrix into a single string of numbers
		foreach ($clip as &$pack) {
			foreach ($pack as &$card) {
				$card = $card->number;
			}
			$pack = implode(", ", $pack);
		}
		$clip = implode(" / ", $clip);
	}
	$pass = implode(" * ", $pass);

	$sql = "INSERT INTO drafts (draft) VALUES (" . "\"" . $pass . "\"" . ")";
	$result = $conn->query($sql);
	if (!$result) die ("Failed: " . $conn->error);
}

?>

<script>

//var draft = <?PHP echo json_encode($pass); ?>

</script>