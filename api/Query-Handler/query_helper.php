<?php
function create_query(array &$json, int $complex_id, int|null $parent_id, int &$running_time, UserSession $session) {
    $stmt = null;
    if ($json['id'] != "root" && $json['function'] != "" && $json['function'] != "complex") {
        $query_sql = "INSERT INTO " . QUERY_DB . ".functions (function_name, complex_query_id) VALUES (?,?)";
        $stmt = $session->prepare($query_sql, getcwd(), [$json['function'], $complex_id]);
        $stmt->bind_param("si", $json['function'], $complex_id);
        $session->execute($stmt, getcwd(), $query_sql, [$json['function'], $complex_id]);
        if ($parent_id != null) {
            $session->query("INSERT INTO " . QUERY_DB . ".function_linker (from_function, to_function) VALUES ($stmt->insert_id, $parent_id)", getcwd());
        }
    }
    foreach ($json['children'] as &$child) {
        if ($child == null) {
            continue;
        }
        if ($stmt != null) {
            $running_time += create_query($child, $complex_id, $stmt->insert_id, $running_time, $session);
        } else $running_time += create_query($child, $complex_id, null, $running_time, $session);
    }
    if ($json['function'] == 'complex') {
        $ran = json_decode(base64_decode($json['params']['complex_function']['base64']), true);
        if ($ran == null) {
            return;
        }
        if ($stmt != null) {
            $running_time += create_query($ran, $complex_id, $stmt->insert_id, $running_time, $session);
        } else $running_time += create_query($ran, $complex_id, null, $running_time, $session);
        $json['result'] = parseExpression($ran, []);
    }

    $start = microtime(true);
    $query = new Query($session);
    if ($json['function'] == 'games_by_team') {
        $all_numbers = true;
        foreach ($json['params']['team'] as $team) {
            if (!is_numeric($team)) {
                $all_numbers = false;
                break;
            }
        }
        if ($all_numbers) {
            $query->match_by_team($json['params']['homeaway'], $json['params']['team']);
            $json['result'] = $query->match_ids;
        }
    } else if ($json['function'] == 'games_by_date') {
        $query->match_by_date($json['params']['operator'], $json['params']['date']);
        $json['result'] = $query->match_ids;
    } else if ($json['function'] == 'games_by_score') {
        $operation = "+";
        if (isset($json['params']['operation']) && $json['params']['operation'] == 'difference') $operation = "-";
        $comparator = $json['params']['operator'];
        if (is_numeric($json['params']['score']) && ($comparator == '>' || $comparator == '>=' || $comparator == '=' || $comparator == '<' || $comparator == '<=')) {
            $query->match_by_score($json['params']['period'], $operation, $json['params']['homeaway'], $comparator, $json['params']['score']);
            $json['result'] = $query->match_ids;
        }
    } else if ($json['function'] == 'games_by_event') {
        $comparator = $json['params']['operator'];
        foreach ($json['params']['period'] as $period) {
            if (!is_numeric($period)) return;
        }
        $valid_comparators = ['>', '>=', '=', '<', '<='];
        if (is_numeric($json['params']['value']) && in_array($comparator, $valid_comparators)) {
            if (!isset($json['params']['start']) || strlen($json['params']['start']) == 0 || !is_numeric($json['params']['start'])) {
                $json['params']['start'] = -1;
            }
            if (!isset($json['params']['end']) || strlen($json['params']['end']) == 0 || !is_numeric($json['params']['end'])) {
                $json['params']['end'] = -1;
            }
            $query->match_by_event($comparator, $json['params']['operation'] ?? "sum", $json['params']['event'], $json['params']['value'], $json['params']['qualifiers'], $json['params']['homeaway'], $json['params']['period'], $json['params']['start'], $json['params']['end'], $json['attributes']);
            $json['result'] = $query->match_ids;
        }
    } else if ($json['function'] == 'games_by_result') {
        $query->match_by_result($json['params']['result'], $json['attributes']);
        $json['result'] = $query->match_ids;
    } else if ($json['function'] == 'games_by_comp') {
        $query->match_by_comp($json['params']['comp']);
        $json['result'] = $query->match_ids;
    }
    $end = microtime(true);
    $time = $end - $start;
    if ($time > 5 && !$query->cacheHit) {
        $compressed_results = base64_encode(gzcompress(json_encode($json['result']), 9));
        $query_sql = "INSERT INTO " . QUERY_DB . ".function_cache (function_id, results) VALUES ($stmt->insert_id,?)";
        $ins_compress = $session->prepare($query_sql, getcwd(), [$compressed_results]);
        $ins_compress->bind_param("s", $compressed_results);
        $session->execute($ins_compress, getcwd(), $query_sql, [$compressed_results]);
    }
    if ($stmt != null) {
        $query_sql = "UPDATE " . QUERY_DB . ".functions SET sql_query = ?, query_time = $time, total_results = ?, params = ?, attributes = ? WHERE id = $stmt->insert_id";
        $tot_records = count($query->match_ids);
        $json_param =  json_encode($json['params']);
        $attributes = $json['attributes'] != null ? json_encode($json['attributes']) : null;
        $stmt2 = $session->prepare($query_sql, getcwd(), [$query->sql, $tot_records, $json_param, $attributes]);
        $stmt2->bind_param("siss", $query->sql, $tot_records, $json_param, $attributes);
        $session->execute($stmt2, getcwd(), $query_sql, [$query->sql, $tot_records, $json_param, $attributes]);
        $stmt2->close();
        $stmt->close();
    }
    return $time;
}

function parseExpression(array|null $root, array $arr): array {
    if ($root == null) return $arr;
    if (count($root['children']) == 0 || $root == null) {
        return $root['result'];
    }
    $childArray = [];
    foreach ($root['children'] as $child) {
        $childArray = array_merge($childArray, parseExpression($child, $arr));
    }
    if ($root['id'] == 'root') {
        return $childArray;
    } else {
        return array_intersect($root['result'], $childArray);
    }
}
