<?php
require_once('../UserSession.php');
require_once('../api/Query-Handler/Handler.php');
require_once('../api/Query-Handler/query_helper.php');
require_once('../api/Query-Handler/Query.php');

// increase the maximum execution time to 43200 seconds (12 hours)
set_time_limit(120);

function runTask() {
    $session = UserSession::getInstance();
    $result = $session->query("SELECT * FROM " . QUERY_DB . ".queue WHERE finished = 0 ORDER BY id ASC LIMIT 1", getcwd());
    if (mysqli_num_rows($result) == 0) {
        return;
    }
    $result = $result->fetch_assoc();
    $json =  json_decode(base64_decode(strtr($result['json_object'], '-_', '+/')), true);
    $queryHandler = new Handler($session, $json, $result['uid']);

    $queryHandler->post_query($result['json_object']);
    $games = implode(',', $queryHandler->response['games']);
    $session->query("UPDATE " . QUERY_DB . ".queue SET finished = 1, results = '$games' WHERE uid = '$result[uid]'", getcwd());
}

runTask(); // fire up the loop
// Send request to API
// Insert into queue with a unique id which is returned to the end user
// JS script send the unique id to the api to get an update on where it stands in the queue, and the results when it is finished
// Queue
// Data is inserted into the queue with finished at 0
// ATM data will be processed FIFO
// Script will grab first query that has a finished of 0
// On completion it will set finished to 1
