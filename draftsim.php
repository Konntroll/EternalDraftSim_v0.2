<?PHP
require_once "login.php";

$sql = "SELECT value FROM metrics WHERE param='player'"; //this is to get the key of the first draft string in the drafts table
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);

while ($row = $result->fetch_row()) {
	$line = $row[0];
}

$conn->query("UPDATE metrics SET value = value + 1 WHERE param = 'player'"); //this is to update the number of players for future passes

$sql = "SELECT draft FROM drafts WHERE line=" . $line . ""; //get a draft string to work with
$result = $conn->query($sql);
if (!$result) die ("Failed: " . $conn->error);

while ($row = $result->fetch_row()) {
	$array = $row[0];
}

$conn->query("DELETE FROM drafts WHERE line=" . $line . ""); //delete the first string so that the next player is working with a different one
$conn->query("INSERT INTO drafts (draft) VALUES (" . "\"" . $array . "\"" . ")"); //append the first string to the end of the database to retain it in case the draft is not completed

?>

<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width">
		<title>Eternal Draft Sim | Simulator</title>
		<link rel="stylesheet" href="../style.css">
		<!--<link rel="stylesheet" href="style.css">-->
		<link rel="icon" type="image/png" href="./icon.PNG">
	</head>
	<body>
		<header>
			<div class="container">
				<div id="branding">
					<h1><span class="highlight">Eternal</span> Draft Sim</h1>
				</div>
				<nav>
					<ul>
						<li><a href="http://localhost/limgen/showcase/index.html">Background</a></li>
						<li><a href="http://localhost/limgen/showcase/structure.html">Structure</a></li>
						<li class="current"><a href="http://localhost/limgen/showcase/overhaul/draftsim.php">Simulator</a></li>
					</ul>
				</nav>
			</div>
		</header>
		<section id="draftBackground">
			<div class="all" id="all">
				<table id="table" align="center" style="padding-top:1em;">
					<tr>
						<td id="deck">
							<div id="booster" style="height:950px; width:950px"></div>
						</td>
						<td id="pool" rowspan="3">
							<table>
								<tr>
									<td colspan="2"><div id="chart" style="width:350px; height:175px;"></div></td>
								</tr>
								<tr>
									<td width="150" align="center" bgcolor="green" onclick="mainDeck(mainDeckOutput)">Maindeck</td>
									<td width="150" align="center" bgcolor="grey" onclick="sideBoard()">Sideboard</td>
								</tr>
								<tr>
									<td id="cardsInDeck" width="150" align="center" bgcolor="tan"></td>
									<td id="pickNumber" width="150" align="center" bgcolor="white"></td>
								</tr>
								<tr>
									<td colspan="2" width="240">
										<div id="poolList" style="overflow:auto; height:700px; width:350px">
										</div>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
		</section>
	</body>
</html>

<script type="text/javascript" src="./Flotr2-master/flotr2.min.js"></script>
<script type="text/javascript">

	var draft = <?PHP echo json_encode($array); ?>; //this provides a string taken by PHP from a MySQL DB
	var player = <?PHP echo json_encode($line); ?>; //this will be used to make adjustments to the drafts database if this draft process is completed
	draft = draft.split(" * "); //this and the following loop split the string into an array of four subarrays containing twelve further subarrays of twelve sets of numbers, each set containing one less number
	for (i = 0; i < 4; i++) {
		draft[i] = draft[i].split(" / ");
		for (y = 0; y < 12; y++) {
			draft[i][y] = draft[i][y].split(", ");
		}
	}

	var jsonCall = new XMLHttpRequest(); //this is to pull all card data from a JSON file based on TET DB
	jsonCall.open ('GET', 'tetcards.json');
	jsonCall.onload = function() {
		cardData = JSON.parse(jsonCall.responseText); //Why does the program throw an error with "var"?
	}
	jsonCall.send();

	var pool = []; //creates an array to hold all of the user's cards
	var deck = []; //creates an array to hold the user's maindeck
	var side = []; //creates an array to hold the user's sideboard
	for (i = 0; i < 26; i++) {
		deck[i] = [];
		side[i] = [];
	}
	var costs = []; //array for building of the card cost graph
	for (i = 0; i < 8; i++) {
		costs[i] = [i, 0];
	}
	var round = 0; //an index to navigate the 4 subarrays
	var sequence = 0; //an index to navigate the 12 sub-subarrays
	var factions = {
		F: '#B9231E',
		J: '#508232',
		P: '#415FE1',
		S: '#A541B9',
		T: '#FFAA46',
		N: '#969BAA'
	}
	var mainDeckOutput = "poolList"; //this is to store a DOM element ID for the mainDeck(ID) function as it changes its output target after the draft is over and deck building begins

	window.onload = costGraph();
	window.onload = cardCounter();
	window.onload = boosterDisplay(draft[0][0]);

function cardPick(pick) { //this adds the user's selection to the pool array and displays the next set of images in the current subarray or moves to the next subarray if the last set is empty
	var addCard = cloneCard(cardData[draft[round][sequence][pick]-1]);
	for (faction in factions) {
		if (addCard.faction.length == 1) {
			if (addCard.faction == faction) {
				addCard.color = factions[faction];
			}
		} else {
			addCard.faction = 'N';
			addCard.color = factions.N;
		}
	}
	if (addCard.cost == "N") addCard.cost = 25;
	pool.push(addCard);
	if (deck[addCard.cost].length != 0) {
		result = deck[addCard.cost].filter(function(card){return card.number == addCard.number;});
	} else {
		result = 0;
	}
	if (result != 0) {
		index = deck[addCard.cost].findIndex(function(card){
			return card.number == addCard.number;
		});
		deck[addCard.cost][index].quantity++;
	} else {deck[addCard.cost].push(addCard);}
	mainDeck(mainDeckOutput);
	cardCounter();
	draft[round][sequence].splice(pick, 1);
	if (draft[round][sequence].length != 0) {
		sequence++;
	} else {
		sequence = 0;
		if (round != 3) round++;
	}
	boosterDisplay(draft[round][sequence]);
	if (pool.length == 48) { //displays all of the images selected by the user
		draft[0][11][0] = player;
		params = ('draft=' + JSON.stringify(draft));
		var draftReturn = new XMLHttpRequest();
		draftReturn.open('POST', 'receiver.php');
		draftReturn.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
		draftReturn.send(params);
		draftEditor();
		draftDeck("output");
	}
}

function boosterDisplay(array) {
	document.getElementById("booster").innerHTML = "";
	for (card = 0; card < array.length; card++) {
		var display = document.getElementById("booster");
		var showCard = document.createElement('div');
		showCard.innerHTML = "<img id=" + "\"" + array[card] + "\"" + "onclick=cardPick(" + card + ")" + " src=\"./TET/" + array[card] + ".png\" width=\"230\" height=\"315\">";
		while (showCard.firstChild) {
			display.appendChild(showCard.firstChild);
		}
	}
}

function mainDeck(ID) {
	document.getElementById(ID).innerHTML = "";
	for (cost = 0; cost < deck.length; cost++) {
		for (card = 0; card < deck[cost].length; card++) {
			deck[cost].sort(function(a, b) {
					if(a.name < b.name) return -1;
					if(a.name > b.name) return 1;
					return 0;
				}
			);
			var spoiler = document.getElementById(ID);
			var addPick = document.createElement('div');
			h = (deck[cost][card].cost < 25 ? 55 : 45);
			icon = (deck[cost][card].card_type == "power" ? deck[cost][card].faction : deck[cost][card].cost);
			var id = idConverter(deck[cost][card].number); //this is to address the issue with CSS not working with number IDs
			addPick.innerHTML =
			['<table id="' + id + '">',
				'<style>',
					//'[id=' + id + ']:hover { position: relative; }',
					'[id=' + id + ']:hover:after {',
						'content: url(TET/' + deck[cost][card].number + '.png);',
						'display: block;',
						'position: absolute;',
						'left: 20%;',
						'top: 20%;',
					'}',
				'</style>',
				'<tr bgcolor="' + deck[cost][card].color + '">',
					'<td onclick=setAside(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"Assets/' + icon + '.png" width="45" height="' + h + '">',
					'</td>',
					'<td onclick=setAside(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"thumbs/' + deck[cost][card].number + '.png" width="55" height="55">',
					'</td>',
					'<td onclick=setAside(\'' + cost + '\',\'' + card + '\')>',
						'<div style="width:155px;font-weight:bold;font-size:1em;color:white;">',
							deck[cost][card].name,
						'</div>',
					'</td>',
					'<td bgcolor="black" style="font-weight:bold;font-size:2.5em;color:white;text-align:center;" width="55" height="55">',
						deck[cost][card].quantity,
					'</td>',
				'</tr>',
			'</table>'].join('\n');
			while(addPick.firstChild) {
				spoiler.appendChild(addPick.firstChild);
			}
			//document.getElementById("text").style.content = "url(TET/" + deck[cost][card].number + ".png)";
		}
	}
	costGraph();
	cardCounter();
}

function idConverter(ID) { //this converts card numbers to strings of letters to produce CSS-compatible IDs
	var letters = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J"];
	ID = ID.toString().split("");
	for (var digit in ID) {
		ID[digit] = letters[ID[digit]];
	}
	return ID.join("");
}

function draftDeck(ID) {
	document.getElementById(ID).innerHTML = "";
	for (cost = 0; cost < deck.length; cost++) {
		for (card = 0; card < deck[cost].length; card++) {
			deck[cost].sort(function(a, b) {
					if(a.name < b.name) return -1;
					if(a.name > b.name) return 1;
					return 0;
				}
			);
			var spoiler = document.getElementById(ID);
			var addPick = document.createElement('div');
			h = (deck[cost][card].cost < 25 ? 55 : 45);
			icon = (deck[cost][card].card_type == "power" ? deck[cost][card].faction : deck[cost][card].cost);
			var id = idConverter(deck[cost][card].number); //this is to address the issue with CSS not working with number IDs
			addPick.innerHTML =
			['<table id="' + id + '">',
				'<style>',
					//'[id=' + id + ']:hover { position: relative; }',
					'[id=' + id + ']:hover:after {',
						'content: url(TET/' + deck[cost][card].number + '.png);',
						'display: block;',
						'position: absolute;',
						'left: 70%;',
						'top: 20%;',
					'}',
				'</style>',
				'<tr bgcolor="' + deck[cost][card].color + '">',
					'<td onclick=returnToPool(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"Assets/' + icon + '.png" width="45" height="' + h + '">',
					'</td>',
					'<td onclick=returnToPool(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"thumbs/' + deck[cost][card].number + '.png" width="55" height="55">',
					'</td>',
					'<td onclick=returnToPool(\'' + cost + '\',\'' + card + '\')>',
						'<div style="width:155px;font-weight:bold;font-size:1em;color:white;">',
							deck[cost][card].name,
						'</div>',
					'</td>',
					'<td bgcolor="black" style="font-weight:bold;font-size:2.5em;color:white;text-align:center;" width="55" height="55">',
						deck[cost][card].quantity,
					'</td>',
				'</tr>',
			'</table>'].join('\n');
			while(addPick.firstChild) {
				spoiler.appendChild(addPick.firstChild);
			}
		}
	}
	postDraftCounter();
	costGraph();
	deckFactions();
	deckCardTypes();
}

function sideBoard() {
	document.getElementById("poolList").innerHTML = "";
	for (cost = 0; cost < deck.length; cost++) {
		for (card = 0; card < side[cost].length; card++) {
			side[cost].sort(function(a, b) {
					if(a.name < b.name) return -1;
					if(a.name > b.name) return 1;
					return 0;
				}
			);
			var spoiler = document.getElementById("poolList");
			var addPick = document.createElement('div');
			h = (side[cost][card].cost < 25 ? 55 : 45);
			icon = (side[cost][card].card_type == "power" ? side[cost][card].faction : side[cost][card].cost);
			var id = idConverter(side[cost][card].number); //this is to address the issue with CSS not working with number IDs
			addPick.innerHTML =
			['<table id="' + id + '">',
				'<style>',
					//'[id=' + id + ']:hover { position: relative; }',
					'[id=' + id + ']:hover:after {',
						'content: url(TET/' + side[cost][card].number + '.png);',
						'display: block;',
						'position: absolute;',
						'left: 20%;',
						'top: 20%;',
					'}',
				'</style>',
				'<tr bgcolor="' + side[cost][card].color + '">',
					'<td onclick=backToMain(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"Assets/' + icon + '.png" width="45" height="' + h + '">',
					'</td>',
					'<td onclick=backToMain(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"thumbs/' + side[cost][card].number + '.png" width="55" height="55">',
					'</td>',
					'<td onclick=backToMain(\'' + cost + '\',\'' + card + '\')>',
						'<div style="width:155px;font-weight:bold;font-size:1em;color:white;">',
							side[cost][card].name,
						'</div>',
					'</td>',
					'<td bgcolor="black" style="font-weight:bold;font-size:2.5em;color:white;text-align:center;" width="55" height="55">',
						side[cost][card].quantity,
					'</td>',
				'</tr>',
			'</table>'].join('\n');
			while(addPick.firstChild) {
				spoiler.appendChild(addPick.firstChild);
			}
		}
	}
	cardCounter();
}

function sidePool() {
	document.getElementById("poolList").innerHTML = "";
	for (cost = 0; cost < deck.length; cost++) {
		for (card = 0; card < side[cost].length; card++) {
			side[cost].sort(function(a, b) {
					if(a.name < b.name) return -1;
					if(a.name > b.name) return 1;
					return 0;
				}
			);
			var spoiler = document.getElementById("poolList");
			var addPick = document.createElement('div');
			h = (side[cost][card].cost < 25 ? 55 : 45);
			icon = (side[cost][card].card_type == "power" ? side[cost][card].faction : side[cost][card].cost);
			var id = idConverter(side[cost][card].number); //this is to address the issue with CSS not working with number IDs
			addPick.innerHTML =
			['<table id="' + id + '">',
				'<style>',
					//'[id=' + id + ']:hover { position: relative; }',
					'[id=' + id + ']:hover:after {',
						'content: url(TET/' + side[cost][card].number + '.png);',
						'display: block;',
						'position: absolute;',
						'left: 20%;',
						'top: 20%;',
					'}',
				'</style>',
				'<tr bgcolor="' + side[cost][card].color + '">',
					'<td onclick=returnToMain(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"Assets/' + icon + '.png" width="45" height="' + h + '">',
					'</td>',
					'<td onclick=returnToMain(\'' + cost + '\',\'' + card + '\')>',
						'<img src=\"thumbs/' + side[cost][card].number + '.png" width="55" height="55">',
					'</td>',
					'<td onclick=returnToMain(\'' + cost + '\',\'' + card + '\')>',
						'<div style="width:155px;font-weight:bold;font-size:1em;color:white;">',
							side[cost][card].name,
						'</div>',
					'</td>',
					'<td bgcolor="black" style="font-weight:bold;font-size:2.5em;color:white;text-align:center;" width="55" height="55">',
						side[cost][card].quantity,
					'</td>',
				'</tr>',
			'</table>'].join('\n');
			while(addPick.firstChild) {
				spoiler.appendChild(addPick.firstChild);
			}
		}
	}
}

function setAside(cost, cardAside) {
	cardClone = cloneCard(deck[cost][cardAside]); //clone the card object for subsequent operations to take effect on a different object from the one stored in the original array (i.e. deck)
	if (side[cost].length != 0) {
		result = side[cost].filter(function(card){return card.number == cardClone.number;});
	} else {
		result = 0;
	}
	if (result != 0) {
		index = side[cost].findIndex(function(card){
			return card.number == cardClone.number;
		});
		side[cost][index].quantity++;
	} else {
		side[cost].push(cardClone);
		side[cost][side[cost].length - 1].quantity = 1;
	}
	if (deck[cost][cardAside].quantity > 1) {
		deck[cost][cardAside].quantity--;
	} else {
		deck[cost].splice(cardAside, 1);
	}
	mainDeck("poolList"); //to refresh the maindeck list
	cardCounter();
}

function returnToPool(cost, cardAside) {
	cardClone = cloneCard(deck[cost][cardAside]); //clone the card object for subsequent operations to take effect on a different object from the one stored in the original array (i.e. deck)
	if (side[cost].length != 0) {
		result = side[cost].filter(function(card){return card.number == cardClone.number;});
	} else {
		result = 0;
	}
	if (result != 0) {
		index = side[cost].findIndex(function(card){
			return card.number == cardClone.number;
		});
		side[cost][index].quantity++;
	} else {
		side[cost].push(cardClone);
		side[cost][side[cost].length - 1].quantity = 1;
	}
	if (deck[cost][cardAside].quantity > 1) {
		deck[cost][cardAside].quantity--;
	} else {
		deck[cost].splice(cardAside, 1);
	}
	document.getElementById("output").setAttribute("style", "grid-template-rows:repeat(" + rowCounter() + ",55px);");
	draftDeck("output"); //to refresh the maindeck list
	sidePool();
	postDraftCounter();
	deckFactions();
	deckCardTypes();
}

function backToMain(cost, cardToMain) {
	cardClone = cloneCard(side[cost][cardToMain]); //clone the card object for subsequent operations to take effect on a different object from the one stored in the original array (i.e. side)
	if (side[cost].length != 0) {
		result = deck[cost].filter(function(card){return card.number == cardClone.number;});
	} else {
		result = 0;
	}
	if (result != 0) {
		index = deck[cost].findIndex(function(card){
			return card.number == cardClone.number;
		});
		deck[cost][index].quantity++;
	} else {
		deck[cost].push(cardClone);
		deck[cost][deck[cost].length - 1].quantity = 1;
	}
	if (side[cost][cardToMain].quantity > 1) {
		side[cost][cardToMain].quantity--;
	} else {
		side[cost].splice(cardToMain, 1);
	}
	costGraph(); //to adjust the maindeck costs graph according to changes made to the maindeck
	sideBoard(); //to refresh the sideboard list
	cardCounter();
}

function returnToMain(cost, cardToMain) {
	cardClone = cloneCard(side[cost][cardToMain]); //clone the card object for subsequent operations to take effect on a different object from the one stored in the original array (i.e. side)
	if (side[cost].length != 0) {
		result = deck[cost].filter(function(card){return card.number == cardClone.number;});
	} else {
		result = 0;
	}
	if (result != 0) {
		index = deck[cost].findIndex(function(card){
			return card.number == cardClone.number;
		});
		deck[cost][index].quantity++;
	} else {
		deck[cost].push(cardClone);
		deck[cost][deck[cost].length - 1].quantity = 1;
	}
	if (side[cost][cardToMain].quantity > 1) {
		side[cost][cardToMain].quantity--;
	} else {
		side[cost].splice(cardToMain, 1);
	}
	document.getElementById("output").setAttribute("style", "grid-template-rows:repeat(" + rowCounter() + ",55px);");
	draftDeck("output");
	sidePool(); //to refresh the sideboard list
	postDraftCounter();
	costGraph(); //to adjust the maindeck costs graph according to changes made to the maindeck
	deckFactions();
	deckCardTypes();
}

function costGraph() {
	qtyIncr = 0; //quantity increment to account for multiple copies of a single card
	costs[7][1] = 0; //this has to be reset because this value incerements across several cost levels instead of being set to a single specific one every time the function is called
	maxCost = 7; //relative graph cieling to be replaced by the number of cards of a single cost once that number exceeds 7
	for (cost = 0; cost < 25; cost++) {
		if (cost < 7) {
				for (card = 0; card < deck[cost].length; card++) {
					qtyIncr += deck[cost][card].quantity - 1;
				}
				costs[cost] = [cost, deck[cost].length + qtyIncr];
				qtyIncr = 0;
		} else {
			if (deck[cost].length > 0) {
				for (card = 0; card < deck[cost].length; card++) {
					qtyIncr += deck[cost][card].quantity - 1;
				}
				costs[7][1] += (deck[cost].length + qtyIncr);
				qtyIncr = 0;
			}
		}
	}
	for (max = 0; max < 8; max++) {
		if (costs[max][1] > maxCost) maxCost = costs[max][1];
	}
	allCosts = []; //wrapper array to make the costs readable by the Flotr.draw function
	allCosts[0] = costs; //wrap the costs array for the Flotr.draw function, which requires array structure of [[[],..[]]]
	marks = [];
	for (tick = 0; tick < 8; tick++) {
		if (tick < 7) {
			marks[tick] = [tick, tick];
		} else marks[tick] = [tick, "7+"];
	}
	Flotr.draw(
		document.getElementById("chart"),
		allCosts,
		{
			bars: {
				show: true,
				barWidth: 0.5
			},
			yaxis: {
				min: 0,
				max: maxCost,
				ticks: []
			},
			xaxis: {
				min: -0.75,
				max: 7.75,
				ticks: marks
			},
			grid: {
				horizontalLines: false,
				verticalLines: false
			}
		}
	);
}

function deckFactions() {
	var facs = ['#B9231E', '#508232', '#415FE1', '#A541B9', '#FFAA46', '#969BAA'];
	var data = [
		{data: [["F",0]], label: "Fire"},
		{data: [["J",0]], label: "Justice"},
		{data: [["P",0]], label: "Primal"},
		{data: [["S",0]], label: "Shadow"},
		{data: [["T",0]], label: "Time"},
		{data: [["N",0]], label: "Neutral"},
	];
	for (var cost in deck) {
		for (var card in deck[cost]) {
			if (deck[cost][card].card_type != "power") {
				deck[cost][card].faction = cardData[deck[cost][card].number-1].faction;
			}
			switch (deck[cost][card].faction) {
				case "F":
					data[0].data[0][1]++;
					break;
				case "J":
					data[1].data[0][1]++;
					break;
				case "P":
					data[2].data[0][1]++;
					break;
				case "S":
					data[3].data[0][1]++;
					break;
				case "T":
					data[4].data[0][1]++;
					break;
				case "FJ":
					data[0].data[0][1]++;
					data[1].data[0][1]++;
					break;
				case "FP":
					data[0].data[0][1]++;
					data[2].data[0][1]++;
					break;
				case "FS":
					data[0].data[0][1]++;
					data[3].data[0][1]++;
					break;
				case "FT":
					data[0].data[0][1]++;
					data[4].data[0][1]++;
					break;
				case "JP":
					data[1].data[0][1]++;
					data[2].data[0][1]++;
					break;
				case "JS":
					data[1].data[0][1]++;
					data[3].data[0][1]++;
					break;
				case "TJ":
					data[1].data[0][1]++;
					data[4].data[0][1]++;
					break;
				case "PS":
					data[2].data[0][1]++;
					data[3].data[0][1]++;
					break;
				case "TP":
					data[2].data[0][1]++;
					data[4].data[0][1]++;
					break;
				case "ST":
					data[3].data[0][1]++;
					data[4].data[0][1]++;
					break;
				case "N":
					data[5].data[0][1]++;
					break;
				default: break;
			}
		}
	}
	for (var i = 0; i < data.length; i++) {
		if (data[i].data[0][1] == 0) {
			data.splice(i, 1);
			facs.splice(i, 1);
			i--;
		}
	}
	Flotr.draw(document.getElementById("factions"), data, {
		title: "Factions",
		colors: facs,
		pie: {
			show: true,
			sizeRatio: 0.99,
			fillOpacity: 1.0,
			shadowSize: 0,
			explode: 0,
			labelFormatter: function (total, value) {
				return;
			}
		},
		yaxis: {
			showLabels: false
		},
		xaxis: {
			showLabels: false
		},
		grid: {
			horizontalLines: false,
			verticalLines: false,
			outlineWidth: 0
		},
		legend: {
			show:true,
			position: 'nw',
			backgroundColor: '#D2E8FF',
			labelBoxBorderColor: '#cccccc',
			labelBoxWidth: 14,
			labelBoxHeight: 10,
			labelBoxMargin: 5
		}
	});
}

function deckCardTypes() {
	var typeColors = ['#FFFF00', '#FF0000', '#00FFFF', '#00FF00'];
	var data = [
		{data: [["F",0]], label: "Power"},
		{data: [["J",0]], label: "Units"},
		{data: [["P",0]], label: "Spells"},
		{data: [["S",0]], label: "Attach."},
	];
	for (var cost in deck) {
		for (var card in deck[cost]) {
			switch (deck[cost][card].card_type) {
				case "power":
					data[0].data[0][1]++;
					break;
				case "unit":
					data[1].data[0][1]++;
					break;
				case "spell":
					data[2].data[0][1]++;
					break;
				case "attachment":
					data[3].data[0][1]++;
					break;
				default: break;
			}
		}
	}
	for (var i = 0; i < data.length; i++) {
		if (data[i].data[0][1] == 0) {
			data.splice(i, 1);
			typeColors.splice(i, 1);
			i--;
		}
	}
	Flotr.draw(document.getElementById("types"), data, {
		title: "Card types",
		colors: typeColors,
		pie: {
			show: true,
			sizeRatio: 0.99,
			fillOpacity: 1.0,
			shadowSize: 0,
			explode: 0,
			labelFormatter: function (total, value) {
				return;
			}
		},
		yaxis: {
			showLabels: false
		},
		xaxis: {
			showLabels: false
		},
		grid: {
			horizontalLines: false,
			verticalLines: false,
			outlineWidth: 0
		},
		legend: {
			show:true,
			position: 'ne',
			backgroundColor: '#D2E8FF',
			labelBoxBorderColor: '#cccccc',
			labelBoxWidth: 14,
			labelBoxHeight: 10,
			labelBoxMargin: 5
		}
	});
}

function cloneCard(original) {
	clone = {
		name: original.name,
		cost: original.cost,
		faction: original.faction,
		infreq: original.infreq,
		card_type: original.card_type,
		number: original.number,
		quantity: 1, //this is always set to 1 because no copies of the object exist in the new location, otherwise only the quantity of copies in the new location is changed
		color: original.color
	}
	return clone;
}

function cardCounter() {
	var cardsInDeck = 0;
	for (cost in deck) {
		cardsInDeck += deck[cost].length;
		for (card = 0; card < deck[cost].length; card++) {
			cardsInDeck += deck[cost][card].quantity - 1;
		}
	}
	document.getElementById("cardsInDeck").innerHTML = "Cards in deck: " + cardsInDeck + "";
	pick = (pool.length < 48 ? 1 + pool.length : pool.length);
	document.getElementById("pickNumber").innerHTML = "Pick " + pick + "/48";
}

function rowCounter() {
	var rows = 0;
	for (cost in deck) {
		for (card in deck[cost]) {
			rows++;
		}
	}
	return Math.ceil(rows / 3);
}

function postDraftCounter() {
	var cardsInDeck = 0;
	for (cost in deck) {
		cardsInDeck += deck[cost].length;
		for (card = 0; card < deck[cost].length; card++) {
			cardsInDeck += deck[cost][card].quantity - 1;
		}
	}
	var power = 0;
	var units = 0;
	var spells = 0;
	var attachments = 0;
	for (var cost in deck) {
		for (var card in deck[cost]) {
			switch (deck[cost][card].card_type) {
				case "power":
					power += deck[cost][card].quantity;
					break;
				case "unit":
					units += deck[cost][card].quantity;
					break;
				case "spell":
					spells += deck[cost][card].quantity;
					break;
				case "attachment":
					attachments += deck[cost][card].quantity;
					break;
				default: break;
			}
		}
	}
	document.getElementById("deckStats").innerHTML =
	['<b>Cards in deck: </b>' + cardsInDeck + '<br>',
	'<b>Power: </b>' + power + '<br>',
	'<b>Units: </b>' + units + '<br>',
	'<b>Spells: </b>' + spells + '<br>',
	'<b>Attachments: </b>' + attachments + '<br>',
	].join('\n');
}

function addPower() {
	var power = 0;
	for (var cost in deck) {
		for (var card in deck[cost]) {
			switch (deck[cost][card].card_type) {
				case "power":
					power += deck[cost][card].quantity;
					break;
				default: break;
			}
		}
	}
	for (var card in side[25]) { //this checks if power was already added so as to avoid repeat additions, enough power is now available for tweaking in the sideboard
		if (side[25][card].quantity > 15) {
			alert("You already have enough power. Check in the pool.");
			return;
		}
	}
	var cardsInDeck = 0;
	for (cost in deck) {
		cardsInDeck += deck[cost].length;
		for (card = 0; card < deck[cost].length; card++) {
			cardsInDeck += deck[cost][card].quantity - 1;
		}
	}
	var pwrreq = 0;
	if (45 - cardsInDeck >= 15) pwrreq = 45 - cardsInDeck;
	else pwrreq = Math.ceil((cardsInDeck + 15 - power) / 3);
	var pwrColors = {
		Fire: 0,
		Justice: 0,
		Primal: 0,
		Shadow: 0,
		Time: 0,
		total: 0
	};
	for (var cost in deck) {
		for (var card in deck[cost]) {
			if (deck[cost][card].card_type != "power") {
				var eval = deck[cost][card].infreq.split("");
				for (var color in eval) {
					switch (eval[color]) {
						case "F":
							pwrColors.Fire++;
							pwrColors.total++;
							break;
						case "J":
							pwrColors.Justice++;
							pwrColors.total++;
							break;
						case "P":
							pwrColors.Primal++;
							pwrColors.total++;
							break;
						case "S":
							pwrColors.Shadow++;
							pwrColors.total++;
							break;
						case "T":
							pwrColors.Time++;
							pwrColors.total++;
							break;
						default: break;
					}
				}
			}
		}
	}
	var colors = {
		F: '#B9231E',
		J: '#508232',
		P: '#415FE1',
		S: '#A541B9',
		T: '#FFAA46',
		N: '#969BAA'
	}
	var counter = 0;
	for (var faction in pwrColors) {
		if (pwrColors[faction] != 0 && faction != "total") {
			pwrColors[faction] = Math.round(pwrreq / 100 * Math.ceil(pwrColors[faction] * 100 / pwrColors.total));
			deck[25].push({
				name: faction + " Sigil",
				cost: "N",
				faction: faction[0],
				infreq: faction[0],
				card_type: "power",
				number: 666 + counter,
				quantity: pwrColors[faction],
				color: colors[faction[0]]
			});
		}
		counter++;
	}
	counter = 0;
	for (var card in side[25]) {
		if (side[25][card].quantity > 10) {
			document.getElementById("output").setAttribute("style", "grid-template-rows:repeat(" + rowCounter() + ",55px);");
			draftDeck("output");
			sidePool();
			return;
		}
	}
	for (var faction in pwrColors) {
		if (faction != "total") {
			side[25].push({
				name: faction + " Sigil",
				cost: "N",
				faction: faction[0],
				infreq: faction[0],
				card_type: "power",
				number: 666 + counter,
				quantity: 20,
				color: colors[faction[0]]
			});
		}
		counter++;
	}
	document.getElementById("output").setAttribute("style", "grid-template-rows:repeat(" + rowCounter() + ",55px);");
	draftDeck("output");
	sidePool();
}

function newDraft() { location.reload(); }

function draftEditor() {
	document.getElementById('all').innerHTML = 
	['<head>',
		'<title>Draft Deck Editor</title>',
		'<link rel="stylesheet" href="../style.css?ver11">',
	'</head>',
	'<body>',
		'<table style="border:none;padding-top:1em;" align="center">',
			'<tr>',
				'<td id="factions" style="width:350px; height:175px"></td>',
				'<td id="types" style="width:350px; height:175px"></td>',
				'<td id="chart" style="width:350px; height:175px"></td>',
				'<td style="width:350px; height:175px">',
					'<div id="statWrap">',
						'<div id="deckStats" style="text-align:right">',
						'</div>',
						'<div id="addPower" onclick=addPower() style="text-align:center">Add Power</div>',
						'<div id="newDraft" onclick=newDraft() style="text-align:center">New Draft</div>',
					'</div>',
				'</td>',
			'</tr>',
		'</table>',
		'<div id="main" style="width:80%;margin:auto;">',
			'<div id="output" style="height:700px;display:grid;grid-template-columns:1fr1fr1fr;grid-template-rows:repeat(' + rowCounter() + ',55px);grid-auto-flow:column;grid-gap:3px;padding:3px;"></div>',
			'<div id="poolList" style="overflow:auto; height:700px; width:350px; border: 1px solid black;"></div>',
		'</div>',
	'</body>'].join('\n');
	sidePool();
}

</script>