<?php
require_once('match_by_score.php');
ini_set("memory_limit", "999M");
ini_set('max_execution_time', -1);
class Query {
    public array $match_ids;
    public string $sql;
    private UserSession $session;
    public bool $cacheHit = false;

    public function __construct(UserSession $session) {
        $this->session = $session;
        $this->match_ids = array();
        $session->toggleConnection(UserSession::LOCAL_DB);
    }


    public function match_by_score(array $period, string $operation, string $homeaway, string $operator, string $score): void {
        $intersect = [];
        if ($homeaway == 'homeaway') {
            if (in_array('full_time_et_opt', $period)) {
                $sql = "(" . $this->match_score_helper(['full_time'], $intersect, $operation, 'home', $operator, $score);
                $sql .= " AND m.id IN (" . $this->match_score_helper(['full_time'], $intersect, $operation, 'away', $operator, $score) . "))";
                $sql .= " UNION (" . $this->match_score_helper(['et_full_time'], $intersect, $operation, 'home', $operator, $score);
                $sql .= " AND m.id IN (" . $this->match_score_helper(['et_full_time'], $intersect, $operation, 'away', $operator, $score) . "))";
                $intersect = [];
            } else {
                $sql = $this->match_score_helper($period, $intersect, $operation, 'home', $operator, $score);
                $sql .= " AND m.id IN (" . $this->match_score_helper($period, $intersect, $operation, 'away', $operator, $score) . ")";
            }
        } else if ($homeaway == 'homeoraway') {
            if (in_array('full_time_et_opt', $period)) {
                // Figure out why it takes so long to get here
                $sql = $this->match_score_helper(['full_time'], $intersect, $operation, 'home', $operator, $score);
                $sql .= " UNION " . $this->match_score_helper(['full_time'], $intersect, $operation, 'away', $operator, $score);
                $sql .= " UNION " . $this->match_score_helper(['et_full_time'], $intersect, $operation, 'home', $operator, $score);
                $sql .= " UNION " . $this->match_score_helper(['et_full_time'], $intersect, $operation, 'away', $operator, $score);
                $intersect = [];
            } else {
                $sql = "(" . $this->match_score_helper($period, $intersect, $operation, 'home', $operator, $score) . ")";
                $sql .= " UNION (" . $this->match_score_helper($period, $intersect, $operation, 'away', $operator, $score) . ")";
            }
        } else {
            if (in_array('full_time_et_opt', $period)) {
                $sql = $this->match_score_helper(['full_time'], $intersect, $operation, $homeaway, $operator, $score);
                $sql .= " UNION " . $this->match_score_helper(['et_full_time'], $intersect, $operation, $homeaway, $operator, $score);
                $intersect = [];
            } else {
                $sql = $this->match_score_helper($period, $intersect, $operation, $homeaway, $operator, $score);
            }
        }
        $checkCache = $this->check_cache($sql);
        if (!$checkCache) {
            $this->unpack_result($this->session->query($sql, getcwd()), 'id', $this->match_ids);
            if (count($intersect) > 0) $this->match_ids = array_intersect($intersect, $this->match_ids);
        } else {
            $this->cacheHit = True;
            $this->match_ids = json_decode($checkCache, true);
        }
        $this->sql = $sql;
    }

    public function match_score_helper(array $period, array &$intersect, string $operation, string $homeaway, string $operator, string $score): string {
        $sql = "SELECT m.id FROM {$this->session->overview} m JOIN {$this->session->attributes} ma on ma.match_id = m.id AND ma.attribute_id IN (1,2,3,4,5,6,7,8) GROUP BY m.id having ";
        if (in_array('penalties', $period)) {
            build_query("penalties", $homeaway, $sql, $operation, $period);
            $this->unpack_result($this->session->query("SELECT DISTINCT match_id FROM {$this->session->attributes} WHERE attribute_id in (7,8)", getcwd()), 'match_id', $intersect);
        }
        if (in_array('et_full_time', $period)) {
            build_query("et_full_time", $homeaway, $sql, $operation, $period);
            $this->unpack_result($this->session->query("SELECT DISTINCT match_id FROM {$this->session->attributes} WHERE attribute_id in (5,6)", getcwd()), 'match_id', $intersect);
        }
        if (in_array('et', $period)) {
            build_query("et", $homeaway, $sql, $operation, $period);
            $this->unpack_result($this->session->query("SELECT DISTINCT match_id FROM {$this->session->attributes} WHERE attribute_id in (5,6)", getcwd()), 'match_id', $intersect);
        }
        if (in_array('full_time', $period)) {
            build_query("full_time", $homeaway, $sql, $operation, $period);
            $this->unpack_result($this->session->query("SELECT DISTINCT match_id FROM {$this->session->attributes} WHERE match_id not in(SELECT DISTINCT match_id FROM {$this->session->attributes} WHERE attribute_id IN (5,6))", getcwd()), 'match_id', $intersect);
        }
        if (in_array('second_half', $period)) build_query("second_half", $homeaway, $sql, $operation, $period);
        if (in_array('first_half', $period)) build_query("first_half", $homeaway, $sql, $operation, $period);
        if (count($period) == 0 || in_array('full_time_et_opt', $period)) {
            build_query("full_time", $homeaway, $sql, $operation, $period);
        }
        $sql = substr($sql, 0, -1);
        $sql .= $operator . ' ' . $score;
        return $sql;
    }

    public function match_by_team(string $location, array $name): void {
        if (strcasecmp($location, "homeaway") == 0) {
            $sql = "SELECT wco.id FROM {$this->session->overview} wco WHERE (wco.home_id in (" . implode(', ', $name) . ") or wco.away_id in (" . implode(', ', $name) . ")) AND status = 'Played'";
        } else {
            $location = $location == "home" ? "home_id" : "away_id";
            $sql = "SELECT wco.id FROM {$this->session->overview} wco WHERE wco.$location IN  (" . implode(', ', $name) . ") AND status = 'Played'";
        }
        $this->unpack_result($this->session->query($sql, getcwd()), 'id', $this->match_ids);
        $this->sql = $sql;
    }

    public function match_by_date(string $comparator, string $date): void {
        if ($comparator == '>' || $comparator == '>=' || $comparator == '=' || $comparator == '<' || $comparator == '<=') {
            $query_sql = "SELECT id FROM {$this->session->overview} WHERE CAST(date_time AS DATE) $comparator ? AND status = 'Played'";
            $stmt = $this->session->prepare($query_sql, getcwd(), [$date]);
            $stmt->bind_param("s", $date);
            $this->session->execute($stmt, getcwd(), $query_sql, [$date]);
            $stmt_result = $stmt->get_result();
            $this->unpack_result($stmt_result, 'id', $this->match_ids);
            $this->sql = "SELECT id FROM {$this->session->overview} WHERE CAST(date_time AS DATE) $comparator $date AND status = 'Played'";
        }
    }

    public function match_by_event(
        string $comparator,
        $operation,
        string $event,
        int $comparator_value,
        array $qualifiers,
        string $homeaway,
        array $periods,
        int $start,
        int $end,
        ?array $attributes
    ): void {
        $types = array();
        $quals = array();
        $join = "";
        $conditional = "";
        $period = "";
        $starttime = "";
        $endtime = "";
        $special = "";
        $attribute = "";
        $equalzero = false;
        if ($comparator == "=" and $comparator_value == 0) {
            $comparator = ">";
            $equalzero = true;
        }
        if ($event == 'shots') {
            if (count($qualifiers) > 0) {
                if (in_array('SOffT', $qualifiers)) {
                    array_push($types, 13, 14);
                }
                if (in_array('SOnT', $qualifiers)) {
                    array_push($types, 15, 16);
                    $special = "AND wce.id not IN ( SELECT wce.id FROM {$this->session->events} wce JOIN {$this->session->qualifiers} wcq ON wcq.eventid = wce.id WHERE typeid IN (15,16) AND wcq.qualifierId IN (82,28))";
                }
                if (in_array('blocked', $qualifiers)) {
                    array_push($types, 15, 10);
                }
            } else {
                $types = [13, 14, 15, 16];
            }
        } else if ($event == 'goals') {
            $types = [16];
            if (count($qualifiers) > 0) {
                if (in_array('freekick', $qualifiers)) {
                    array_push($quals, 26);
                }
                if (in_array('penalty', $qualifiers)) {
                    array_push($quals, 9);
                }
                if (in_array('owngoal', $qualifiers)) {
                    array_push($quals, 28);
                }
                $conditional = "AND wcq.qualifierid IN";
                $join = "JOIN {$this->session->qualifiers} wcq ON wcq.eventid = wce.id";
            } else {
                // Add in code to convert own goals to be in the benefit of the other team 
            }
        } else if ($event == 'passes') {
            $types = [1];
            if (count($qualifiers) > 0) {
                if (in_array('successful', $qualifiers)) {
                    $special = "AND wce.outcome = 1";
                }
                if (in_array('unsuccessful', $qualifiers)) {
                    $special = "AND wce.outcome = 0";
                }
                if (in_array('assist', $qualifiers)) {
                    array_push($quals, 900);
                }
                if (in_array('corner', $qualifiers)) {
                    array_push($quals, 6);
                }
                if (in_array('passforward', $qualifiers)) {
                    array_push($quals, 140);
                    $special .= " AND wce.x < wcq.value";
                }
                if (in_array('passbackward', $qualifiers)) {
                    array_push($quals, 140);
                    $special .= " AND wce.x > wcq.value";
                }
                if (in_array('passsideway', $qualifiers)) {
                    array_push($quals, 140);
                    $special .= " AND ABS(wce.x - wcq.value) <= 3";
                }
                if (count($quals) > 0) {
                    $conditional = "AND wcq.qualifierid IN";
                    $join = "JOIN {$this->session->qualifiers} wcq ON wcq.eventid = wce.id";
                }
            }
        }
        if (count($periods) > 0) {
            $period .= "AND wce.period IN (" . implode(', ', $periods) . ")";
            if (!in_array(5, $periods)) {
                $period .= " AND wce.period != 5";
            }
        } else {
            $period .= "AND wce.period != 5";
        }
        if (strlen($conditional) > 0) {
            $conditional .= " (" . implode(', ', $quals) . ")";
        }
        if ($start >= 0) {
            $starttime .= "AND wce.timeMin >= $start";
        }
        if ($end >= 0) {
            $endtime .= "AND wce.timeMin <= $end";
        }
        if ($attributes) {
            foreach ($attributes as $attr) {
                if ($attr['functionName'] == 'games_by_team' && $attr['loaded']) {
                    $attribute .= " AND teamid in (" . implode(', ', $attr['params']['teams']) . ")";
                }
            }
        }
        $intersect = [];
        if (in_array("3", $periods) || in_array("4", $periods)) {
            $this->unpack_result($this->session->query("SELECT DISTINCT matchid FROM {$this->session->events} WHERE period IN (3,4)", getcwd()), 'matchid', $intersect);
        }
        if ($homeaway == 'homemathaway') {
            $sql = "SELECT matchid FROM (SELECT wce.matchid, wco.home_id, wco.away_id, count(DISTINCT wce.id) num FROM {$this->session->events} wce $join JOIN {$this->session->overview} wco ON wce.matchid = wco.id WHERE typeid in (" . implode(', ', $types) . ") $conditional $period $starttime $endtime $special GROUP BY wce.matchid) temp WHERE num $comparator $comparator_value $attribute";
        } else if ($homeaway == 'homeaway') {
            $sql = "SELECT matchid FROM (SELECT wce.matchid, wce.teamid, wco.home_id, wco.away_id, count(DISTINCT wce.id) num FROM {$this->session->events} wce $join JOIN {$this->session->overview} wco ON wce.matchid = wco.id WHERE typeid in (" . implode(', ', $types) . ") $conditional $period $starttime $endtime $special GROUP BY matchid, teamid) temp WHERE num $comparator $comparator_value $attribute GROUP BY matchid having count(*) = 2";
        } else {
            $column = "home_id = teamid";
            if ($homeaway == "away") {
                $column = "away_id = teamid";
            }
            if ($homeaway == "homeoraway") {
                $column = "(home_id = teamid or away_id = teamid)";
            }
            $sql = "SELECT matchid FROM (SELECT wce.matchid, wce.teamid, wco.home_id, wco.away_id, count(DISTINCT wce.id) num FROM {$this->session->events} wce $join JOIN {$this->session->overview} wco ON wce.matchid = wco.id WHERE typeid in (" . implode(', ', $types) . ") $conditional $period $starttime $endtime $special GROUP BY matchid, teamid) temp WHERE $column AND num $comparator $comparator_value $attribute";
        }
        if ($equalzero) {
            $sql = "SELECT id as matchid FROM {$this->session->overview} WHERE id not in (" . $sql . ")";
        }
        $checkCache = $this->check_cache($sql);
        if (!$checkCache) {
            $this->unpack_result($this->session->query($sql, getcwd()), 'matchid', $this->match_ids);
            if (count($intersect) > 0) $this->match_ids = array_intersect($intersect, $this->match_ids);
        } else {
            $this->cacheHit = True;
            $this->match_ids = json_decode($checkCache, true);
        }
        $this->sql = $sql;
    }

    public function match_by_result(string $result, array $attributes): void {
        if ($result == 'homeoraway') {
            $attribute = "";
            foreach ($attributes as $attr) {
                if ($attr['functionName'] == 'games_by_team_result' && $attr['loaded']) {
                    if ($attr['params']['result'] == 'win') {
                        $attribute .= " AND ( (result = 'home' and home_id in (" . implode(', ', $attr['params']['teams']) . ")) or (result = 'away' and away_id in (" . implode(', ', $attr['params']['teams']) . ")))";
                    } else if ($attr['params']['result'] == 'loss') {
                        $attribute .= " AND ( (result = 'home' and away_id in (" . implode(', ', $attr['params']['teams']) . ")) or (result = 'away' and home_id in (" . implode(', ', $attr['params']['teams']) . ")))";
                    } else {
                        $attribute .= " AND (result = 'draw' and (away_id in (" . implode(', ', $attr['params']['teams']) . ") or home_id in (" . implode(', ', $attr['params']['teams']) . ")))";
                    }
                }
            }
            $this->unpack_result($this->session->query("SELECT id FROM {$this->session->overview} WHERE 1=1 $attribute", getcwd()), 'id', $this->match_ids);
            $this->sql = "SELECT id FROM {$this->session->overview} WHERE 1=1 $attribute";
        }

        if ($result == "home" || $result == "away" || $result == "draw") {
            $this->unpack_result($this->session->query("SELECT id FROM {$this->session->overview} WHERE result = '$result'", getcwd()), 'id', $this->match_ids);
            $this->sql = "SELECT id FROM {$this->session->overview} WHERE result = '$result'";
        }
    }

    public function match_by_comp(string $compType) {
        $compids = [];
        if ($compType == 'worldcup') $compids[] = 1180;
        if ($compType == 'friendly') $compids[] = 1147;
        if (count($compids) > 0) {
            $sql = "SELECT wmo.id FROM {$this->session->overview} wmo JOIN " . DATA_DB . ".leagues l ON wmo.season_id = l.id where l.compid in (" . implode(', ', $compids) . ") and wmo.status = 'Played'";
            $this->unpack_result($this->session->query($sql, getcwd()), 'id', $this->match_ids);
            $this->sql = $sql;
        }
    }

    private function flatten($array): array {
        $return = array();
        array_walk_recursive($array, function ($a) use (&$return) {
            $return[] = $a;
        });
        return $return;
    }

    private function unpack_result(mysqli_result $rows, string $key, array &$game_ids): void {
        while ($result = $rows->fetch_assoc()) {
            array_push($game_ids, $result[$key]);
        }
    }

    public function results(): String {
        $this->limit_results();
        $table = "<table id='myTable'>
                    <thead><tr>
                        <th>Home Team</th>
                        <th>Away Team</th>
                        <th>FTG_H</th>
                        <th>FTG_A</th>
                        <th>FTG_TOT</th>
                        <th>FHG_H</th>
                        <th>FHG_A</th>
                        <th>FHG_TOT</th>
                        <th>FT+ETG_H</th>
                        <th>FT+ETG_A</th>
                        <th>FT+ETG_TOT</th>
                        <th>PENSG_H</th>
                        <th>PENSG_A</th>
                        <th>PENSG_TOT</th>
                        <th>SHG_H</th>
                        <th>SHG_A</th>    
                        <th>SHG_TOT</th> 
                        <th>ETG_H</th>
                        <th>ETG_A</th>
                        <th>ETG_TOT</th>
                        <th>PENG_H</th>
                        <th>PENG_A</th>
                        <th>PENG_TOT</th>
                        <th>FKG_H</th>
                        <th>FKG_A</th>
                        <th>FKG_TOT</th>
                        <th>Shots_H</th>
                        <th>Shots_A</th>
                        <th>Shots_TOT</th>
                        <th>SOT_H</th>
                        <th>SOT_A</th>
                        <th>SOT_TOT</th>
                        <th>SOff_H</th>
                        <th>SOff_A</th>
                        <th>SOff_TOT</th>
                        <th>Wood_H</th>
                        <th>Wood_A</th>
                        <th>Wood_TOT</th>
                        <th>Saves_H</th>
                        <th>Saves_A</th>
                        <th>Saves_TOT</th>
                        <th>Assist_H</th>
                        <th>Assist_A</th>
                        <th>Asisst_TOT</th>
                        <th>TotP_H</th>
                        <th>TotP_A</th>
                        <th>TotP_TOT</th>
                        <th>SuccP_H</th>
                        <th>SuccP_A</th>
                        <th>SuccP_TOT</th>
                        <th>CornerP_H</th>
                        <th>CornerP_A</th>
                        <th>CornerP_TOT</th>
                        <th>Off_H</th>
                        <th>Off_A</th>
                        <th>OF_TOT</th>
                        <th>YF_H</th>
                        <th>YF_A</th>
                        <th>YF_TOT</th>
                        <th>RF_H</th>
                        <th>RF_A</th>
                        <th>RF_TOT</th>
                        <th>Fouls_H</th>
                        <th>Fouls_A</th>
                        <th>Fouls_TOT</th>
                        <th>SuccPR_H</th>
                        <th>SuccPR_A</th>
                        <th>Date</th>
                        <th>Stage Name</th>
                        <th>Link</th>
                    </tr></thead>
                    <tbody>";
        if ($this->match_ids == [] ||  $this->match_ids == [""]) {
            return $table;
        }
        $game_list = $this->session->query("SELECT ov.id, hteam.name as hteam,ateam.name as ateam, ov.result, ov.date_time, ov.stage_name FROM {$this->session->overview} ov JOIN " . DATA_DB . ".teams hteam ON ov.home_id = hteam.id JOIN " . DATA_DB . ".teams ateam ON ov.away_id = ateam.id WHERE ov.id in (" . implode(', ', $this->match_ids) . ")", getcwd());
        while ($game = $game_list->fetch_assoc()) {
            $attributes = $this->session->query("SELECT attribute_id, attribute_value, attribute_name FROM {$this->session->attributes} JOIN " . MATCHES_DB . ".attributes ON attribute_id = attributes.id where match_id = $game[id] ORDER BY attribute_id ASC", getcwd());
            $hteam = 0;
            $ateam = 0;
            while ($attr = $attributes->fetch_assoc()) {
                if ($attr['attribute_id'] == 3) $hteam = $attr['attribute_value'];
                if ($attr['attribute_id'] == 4) $ateam = $attr['attribute_value'];
            }
            $sum = $hteam + $ateam;
            if ($game['result'] == 'home') $game['hteam'] = "<b>$game[hteam]</b>";
            if ($game['result'] == 'away') $game['ateam'] = "<b>$game[ateam]</b>";
            if ($game['result'] == 'draw') {
                $game['hteam'] = "<i>$game[hteam]</i>";
                $game['ateam'] = "<i>$game[ateam]</i>";
            }
            $table .= "<tr><td>$game[hteam]</td><td>$game[ateam]</td><td>$hteam</td><td>$ateam</td><td>$sum</td>";
            $attributes = $this->session->query("SELECT attribute_id, attribute_value, attribute_name FROM " . MATCHES_DB . ".calculatedMatchAttributes cma JOIN " . MATCHES_DB . ".attributes a ON a.id = cma.attribute_id where cma.match_id = $game[id] ORDER BY attribute_id ASC", getcwd());
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
                $table .= "<td>$attr[attribute_value]</td>";
                if ($count % 2 != 0) {
                    $sum = $attr['attribute_value'] + $prev_value;
                    $table .= "<td>$sum</td>";
                    $count = 0;
                } else {
                    $prev_value = $attr['attribute_value'];
                    $count = 1;
                }
            }
            $passr_h = null;
            $passr_a = null;
            if ($totp_h && $succp_h) {
                $passr_h = round($succp_h / $totp_h, 2);
            }
            if ($totp_a && $succp_a) {
                $passr_a = round($succp_a / $totp_a, 2);
            }
            $table .= "<td>$passr_h</td><td>$passr_a</td><td>$game[date_time]</td><td>$game[stage_name]</td><td><a href='/game.php?id=$game[id]' target='_blank'>Link</a></tr>";
        }
        $table .= "</tbody></table>";
        return $table;
    }

    public function limit_results(): void {
        if (count($this->match_ids) > 15000) {
            $this->match_ids = array_slice($this->match_ids, 0, 15000);
        }
    }

    private function check_cache($sql): false|string {
        $query = "SELECT fc.results as results, f.id as fid FROM " . QUERY_DB . ".functions f JOIN " . QUERY_DB . ".function_cache fc ON f.id = fc.function_id WHERE sql_query = '$sql'";
        $result = $this->session->query($query, getcwd());
        if ($result->num_rows == 1) {
            $assoc = $result->fetch_assoc();
            $data = $assoc['results'];
            $this->session->query("UPDATE " . QUERY_DB . ".function_cache SET hits = hits + 1 WHERE function_id = $assoc[fid]", getcwd());
            return gzuncompress(base64_decode($data));
        } else return false;
    }
    /*
    function __destruct() {
        $this->match_ids = "";
        $this->sql = "";
    }
    */
}
