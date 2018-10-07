<?PHP
require_once "login.php";
require_once "classes.php";

function pick(&$booster, $player, &$drafters) {
	$benchmark = new Benchmark;
	if (count($drafters[$player]->pool) < 5) {
		for ($card = 0; $card < count($booster); $card++) {
			if ($booster[$card]->RNG > $benchmark->value) {
				$benchmark->value = $booster[$card]->RNG;
				$benchmark->selector = $card;
			}
		}
		$pick = clone $booster[$benchmark->selector];
		array_push($drafters[$player]->pool, $pick);
		if (strlen($booster[$benchmark->selector]->faction) == 1) {
			$drafters[$player]->factions[$booster[$benchmark->selector]->faction] += $booster[$benchmark->selector]->RNG;
		} else {
			$factions = preg_split('//', $booster[$benchmark->selector]->faction, -1, PREG_SPLIT_NO_EMPTY);
			foreach ($factions as $faction) {
				$drafters[$player]->factions[$faction] += $booster[$benchmark->selector]->RNG / count($factions);
			}
		}
		array_splice($booster, $benchmark->selector, 1); //removes the card picked by that player from the booster before it is passed to the next player 
	} else {
		arsort($drafters[$player]->factions);
		$allegiance = array_keys($drafters[$player]->factions);
		for ($card = 0; $card < count($booster); $card++) {
			if ($booster[$card]->faction == "N" && $booster[$card]->RNG > 20) {
				if ($booster[$card]->RNG > $benchmark->value) {
					$benchmark->value = $booster[$card]->RNG;
					$benchmark->selector = $card;
				}
			} elseif (strlen($booster[$card]->faction) == 1) {
				if ($booster[$card]->RNG > $benchmark->value && ($booster[$card]->faction == $allegiance[0] || $booster[$card]->faction == $allegiance[1]) && $booster[$card]->RNG > 20) {
					$benchmark->value = $booster[$card]->RNG;
					$benchmark->selector = $card;
				}
			} else {
				if ($booster[$card]->faction == $allegiance[0] . $allegiance[1] || $booster[$card]->faction == $allegiance[1] . $allegiance[0]) {
					if ($booster[$card]->RNG > $benchmark->value) {
						$benchmark->value = $booster[$card]->RNG;
						$benchmark->selector = $card;
					}
				}
			}
		}
		if ($benchmark->value == 0) {
			for ($card = 0; $card < count($booster); $card++) {
				if (strlen($booster[$card]->faction) == 1 && $booster[$card]->RNG > 20) {
					if ($booster[$card]->RNG > $benchmark->value) {
						$benchmark->value = $booster[$card]->RNG;
						$benchmark->selector = $card;
					}
				} elseif (strlen($booster[$card]->infreq) == 2 && $booster[$card]->RNG > 20) {
					$factions = preg_split('//', $booster[$card]->faction, -1, PREG_SPLIT_NO_EMPTY);
					foreach ($factions as $faction) {
						if (($faction == $allegiance[0] || $faction == $allegiance[1]) && $booster[$card]->RNG > $benchmark->value) {
							$benchmark->value = $booster[$card]->RNG;
							$benchmark->selector = $card;
						}
					}
				} else {
					$infreq = preg_split('//', $booster[$card]->infreq, -1, PREG_SPLIT_NO_EMPTY);
					$infcheck = array ("F" => 0, "T" => 0, "J" => 0, "P" => 0, "S" => 0);
					foreach ($infreq as $faction) {
						$infcheck[$faction]++;
					}
					arsort($infcheck);
					$splash = array_keys($infcheck);
					if (($splash[0] == $allegiance[0] || $splash[0] == $allegiance[1]) && $infcheck[$splash[1]] == 1  && $booster[$card]->RNG > 20) {
						if ($booster[$card]->RNG > $benchmark->value) {
							$benchmark->value = $booster[$card]->RNG;
							$benchmark->selector = $card;
						}
					}
				}
			}
		}
		if ($benchmark->value == 0) {
			for ($card = 0; $card < count($booster); $card++) {
				if ($booster[$card]->RNG > $benchmark->value) {
					$benchmark->value = $booster[$card]->RNG;
					$benchmark->selector = $card;
				}
			}
		}
		$pick = clone $booster[$benchmark->selector];
		array_push($drafters[$player]->pool, $pick);
		if (strlen($booster[$benchmark->selector]->faction) == 1) {
			$drafters[$player]->factions[$booster[$benchmark->selector]->faction] += $booster[$benchmark->selector]->RNG;
		} else {
			$factions = preg_split('//', $booster[$benchmark->selector]->faction, -1, PREG_SPLIT_NO_EMPTY);
			foreach ($factions as $faction) {
				$drafters[$player]->factions[$faction] += $booster[$benchmark->selector]->RNG / count($factions);
			}
		}
		array_splice($booster, $benchmark->selector, 1); //removes the card picked by the player from the booster before it is passed to the next player 
	}
	//return($booster);
}

?>