<?php
require_once('UserSession.php');
$session = UserSession::getInstance();
$gameid = $_GET['id'];

$events = $session->query("SELECT timeMin,timeSec,tm.name as tName,pl.matchName, x, y,ev.name FROM " . MATCHES_DB . ".wcmatchEvents JOIN " . MATCHES_DB . ".events ev ON typeid = ev.id JOIN " . DATA_DB . ".teams tm ON tm.id = teamid JOIN " . DATA_DB . ".players pl ON pl.id = playerid WHERE matchid = $gameid", getcwd());
$game_list = $session->query("SELECT ov.id, hteam.name as hteam,ateam.name as ateam, ov.result, ov.date_time, ov.stage_name FROM {$session->overview} ov JOIN " . DATA_DB . ".teams hteam ON ov.home_id = hteam.id JOIN " . DATA_DB . ".teams ateam ON ov.away_id = ateam.id WHERE ov.id = $gameid", getcwd());

$game = $game_list->fetch_assoc();
$table = "<table id='myTable'><thead><tr><th>Event</th><th>Value</th></tr></thead>";

$attributes = $session->query("SELECT attribute_id, attribute_value, attribute_name FROM {$session->attributes} JOIN " . MATCHES_DB . ".attributes ON attribute_id = attributes.id where match_id = $game[id] ORDER BY attribute_id ASC", getcwd());
$hteam = 0;
$ateam = 0;
while ($attr = $attributes->fetch_assoc()) {
	if ($attr['attribute_id'] == 3) $hteam = $attr['attribute_value'];
	if ($attr['attribute_id'] == 4) $ateam = $attr['attribute_value'];
}
$sum = $hteam + $ateam;
$table .= "<tr><td>Full Time Home</td><td>$hteam</td></tr>";
$table .= "<tr><td>Full Time Away</td><td>$ateam</td></tr>";
$attributes = $session->query("SELECT attribute_id, attribute_value, attribute_name FROM " . MATCHES_DB . ".calculatedMatchAttributes cma JOIN " . MATCHES_DB . ".attributes a ON a.id = cma.attribute_id where cma.match_id = $game[id] ORDER BY attribute_id ASC", getcwd());
$totp_h = 0;
$totp_a = 0;
$succp_h = 0;
$succp_a = 0;
$prev_value = 0;
$count = 0;
while ($attr = $attributes->fetch_assoc()) {
	if ($attr['attribute_id'] == 40) $totp_h = $attr['attribute_value'];
	if ($attr['attribute_id'] == 41) $totp_a = $attr['attribute_value'];
	if ($attr['attribute_id'] == 42) $succp_h = $attr['attribute_value'];
	if ($attr['attribute_id'] == 43) $succp_a = $attr['attribute_value'];
	if ($attr['attribute_value'] === null) continue;

	$table .= "<tr><td>$attr[attribute_name]</td><td>$attr[attribute_value]</td></tr>";
}
$passr_h = null;
$passr_a = null;
if ($totp_h && $succp_h) {
	$passr_h = round($succp_h / $totp_h, 2);
	$table .= "<tr><td>Succesfull Pass (home)</td><td>$passr_h</td></tr>";
}
if ($totp_a && $succp_a) {
	$passr_a = round($succp_a / $totp_a, 2);
	$table .= "<tr><td>Succesfull Pass (away)</td><td>$passr_a</td></tr>";
}


?>
<html>
<script src="js/third-party/jquery-3.6.0.min.js"></script>
<script src="js/third-party/jquery.dataTables.min.js"></script>
<link rel="stylesheet" href="css/third-party/jquery.dataTables.min.css">
<?= $table ?>

<script>
	$('#myTable').DataTable();
</script>

</html>