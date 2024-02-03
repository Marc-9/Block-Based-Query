<?php
header('Content-Type: application/json; charset=utf-8');

$url = $_GET['url'];
$body = file_get_contents('php://input');

$json = json_decode($body, true);
$requestType = $_SERVER['REQUEST_METHOD'];
$validRequestTypes = ["GET", "POST"];
$validEndpoints = [
	"POST" => ["query", "analysis", "workspace"],
	"GET" => ["teamsearch", "playersearch", "workspace", "queryStatus", "queryResult"]
];
$response = ["response_code" => null, "message" => null];

/* <----------------Error Checking------------------> */

if (in_array($requestType, $validRequestTypes)) {
	if (!in_array($url, $validEndpoints[$requestType])) {
		$response['response_code'] = 404;
		$response['message'] = "Invalid Endpoint - $requestType | $url";
		echo json_encode($response);
		exit();
	}
} else {
	$response['response_code'] = 404;
	$response['message'] = "Invalid Request Type - $requestType";
	echo json_encode($response);
	exit();
}
/* <----------------End of Error Checking------------------> */

require_once('../UserSession.php');
$session = UserSession::getInstance();

if ($requestType == "GET") {
	if ($url == 'teamsearch' || $url == 'eventsearch') {
		if (!isset($_GET['key'])) {
			$response['response_code'] = 400;
			$response['message'] = "Missing Value";
			echo json_encode($response);
			exit();
		}
		if ($url == 'teamsearch') $table = DATA_DB . '.teams';
		else $table = MATCHES_DB . 'events';
		include('helper/Search.php');
		$team_search = new Search("%{$_GET['key']}%", 10, "name", $table, $session);
		$response['response_code'] = 200;
		$response['message'] = $team_search->find_results();
		echo json_encode($response);
		exit();
	}

	if ($url == 'workspace') {
		$session->toggleConnection(UserSession::LOCAL_DB);
		$rows = $session->query("SELECT * FROM " . QUERY_DB . ".workspace WHERE name is not null ORDER BY created DESC", getcwd());
		$response_array = [];
		while ($row = $rows->fetch_assoc()) {
			$response_array[] = ["id" => $row['id'], "name" => addslashes($row['name']), "description" => addslashes($row['description']), "json_object" => addslashes($row['json_object'])];
		}
		$response['response_code'] = 200;
		$response['message'] = json_encode($response_array);
		echo json_encode($response);
		exit();
	}

	if ($url == 'queryStatus') {
		//$session->toggleConnection(UserSession::REMOTE_DB);
		$query_sql = "SELECT uid, finished FROM " . QUERY_DB . ".queue WHERE uid = ?";
		$stmt = $session->prepare($query_sql, getcwd(), [$_GET['uid']]);
		$stmt->bind_param("s", $_GET['uid']);
		$session->execute($stmt, getcwd(), $query_sql, [$_GET['uid']]);
		$result = $stmt->get_result()->fetch_assoc();
		if ($result['finished'] == 1) {
			$response['response_code'] = 200;
			$response['message'] = 'finished';
		} else {
			$num_ahead = $session->query("SELECT count(id) as unfinished FROM " . QUERY_DB . ".queue WHERE finished = 0 AND id < (SELECT id FROM " . QUERY_DB . ".queue WHERE uid = '$result[uid]')", getcwd());
			$unfinished = $num_ahead->fetch_assoc()['unfinished'];
			$response['response_code'] = 200;
			if ($unfinished == 0) {
				$response['message'] = "Processing your query now";
			} else {
				$response['message'] = "There are $unfinished queries ahead";
			}
		}
		echo json_encode($response);
	}

	if ($url == 'queryResult') {
		//$session->toggleConnection(UserSession::REMOTE_DB);
		$query_sql = "SELECT uid, results FROM " . QUERY_DB . ".queue WHERE uid = ?";
		$stmt = $session->prepare($query_sql, getcwd(), [$_GET['uid']]);
		$stmt->bind_param("s", $_GET['uid']);
		$session->execute($stmt, getcwd(), $query_sql, [$_GET['uid']]);
		$result = $stmt->get_result()->fetch_assoc();
		$session->query("UPDATE " . QUERY_DB . ".queue SET results = null WHERE uid = '$result[uid]'", getcwd());
		include('Query-Handler/Query.php');
		$query = new Query($session);
		$query->match_ids = explode(",", $result['results']);
		$response = ["table" => $query->results(), "games" => $query->match_ids];
		echo json_encode($response);
	}
}
if ($requestType == "POST") {
	if ($url == 'query') {
		//$session->toggleConnection(UserSession::REMOTE_DB);
		$uid = substr(md5(rand()), 0, 12);
		$base64 = str_replace('=', '', strtr(base64_encode($body), '+/', '-_'));
		$query_sql = "INSERT INTO " . QUERY_DB . ".queue (json_object, finished, uid) VALUES (?, 0, ?)";
		$stmt = $session->prepare($query_sql, getcwd(), [$base64, $uid]);
		$stmt->bind_param("ss", $base64, $uid);
		$session->execute($stmt, getcwd(), $query_sql, [$base64, $uid]);
		$response['response_code'] = 200;
		$response['message'] = $uid;
		echo json_encode($response);
	}
	if ($url == 'analysis') {
		include('helper/MatchAnalysis.php');
		$match = new MatchAnalysis($json, $session);
		$match->generateOverview();
		echo json_encode($match->return_data);
	}
	// Verify user has access to change this workspace
	if ($url == 'workspace') {
		$date = date('Y-m-d H:i:s');
		if (isset($_GET['workspace_id'])) $query = "INSERT INTO " . QUERY_DB . ".workspace (name, description, created, json_object) VALUES (?, ?, ?, ?)";
		else $query = "UPDATE " . QUERY_DB . ".workspace SET name = ?, description = ?, updated = ?, json_object = ? WHERE id = $_POST[wid]";
		$stmt = $session->prepare($query, getcwd(), [$_POST['workspace_name'], $_POST['workspace_description'], $date, $_POST['json_object']]);
		$stmt->bind_param("ssss", $_POST['workspace_name'], $_POST['workspace_description'], $date, $_POST['json_object']);
		$session->execute($stmt, getcwd(), $query, [$_POST['workspace_name'], $_POST['workspace_description'], $date, $_POST['json_object']]);
		$response['response_code'] = 200;
		$response['message'] = ["id" => $stmt->insert_id == 0 ? $_POST['wid'] : $stmt->insert_id, "name" => $_POST['workspace_name'], "description" => $_POST['workspace_description'], "json_object" => $_POST['json_object'], "updated" => $_POST['wid'] != -1];
		echo json_encode($response);
	}
}

http_response_code(200);
