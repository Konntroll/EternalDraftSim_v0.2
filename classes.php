<?PHP

class Drafter {
	public $pool, $factions;
	function __construct() {
		$this->pool = array(); //stores all the picked cards
		$this->factions = array( //keeps track of the player's factions
			"F" => 0,
			"T" => 0,
			"J" => 0,
			"P" => 0,
			"S" => 0
		);
	}
}

class Card {
	public $number, $name, $rarity, $cost, $faction, $infreq, $card_type, $RNG;
	function __construct($num, $conn) {
		$this->number = $num;
		$sql = "SELECT name, rarity, cost, faction, infreq, card_type, RNG, sets FROM TET WHERE number=\"" . $this->number . "\"";
		if ($result = $conn->query($sql)) {
			while ($row = $result->fetch_row()) {
				$this->name = $row[0];
				$this->rarity = $row[1];
				$this->cost = $row[2];
				$this->faction = $row[3];
				$this->infreq = $row[4];
				$this->card_type = $row[5];
				$this->RNG = $row[6];
				$this->sets = $row[7];
			}
		}
	}
}

class Benchmark {
	public $selector, $value;
	function __construct() {
		$this->selector = 0;
		$this->value = 0;
	}
}
?>