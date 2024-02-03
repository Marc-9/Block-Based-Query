<?php
ini_set("memory_limit", "999M");
ini_set('max_execution_time', -1);

class MatchAnalysis {
    public UserSession $session;
    public array $stats = array();
    public array $analysis = ["teams" => array()];
    public array $return_data = ["html" => null, "ids" => []];


    public function __construct(array $match_ids, UserSession $session) {
        $this->session = $session;
        foreach ($match_ids as $id) {
            $this->fillStats($id);
        }
    }
    function fillStats(int $match_id): void {
        $query = "SELECT wco.id, wco.home_id, hteam.name as 'Home Team', wco.away_id, ateam.name as 'Away Team', wco.date_time, wco.result, wco.stage_name, wco.series_name, league.year, comp.name FROM {$this->session->overview} wco JOIN " . DATA_DB . ".teams hteam ON hteam.id = wco.home_id JOIN " . DATA_DB . ".teams ateam ON ateam.id = wco.away_id JOIN " . DATA_DB . ".leagues league ON league.id = wco.season_id JOIN " . DATA_DB . ".competitions comp ON league.compId = comp.id WHERE wco.id = ?";
        $stmt = $this->session->prepare($query, getcwd(), [$match_id]);
        $stmt->bind_param("i", $match_id);
        $this->session->execute($stmt, getcwd(), $query, [$match_id]);
        $stmt_result = $stmt->get_result()->fetch_assoc();
        // This should not be necessary
        if (!isset($stmt_result['id'])) {
            return;
        }
        $attributes = $this->session->query("SELECT attr.attribute_name, wca.attribute_value FROM {$this->session->attributes} wca JOIN " . MATCHES_DB . ".attributes attr ON wca.attribute_id = attr.id WHERE wca.match_id = $stmt_result[id]", getcwd());
        while ($attr = $attributes->fetch_assoc()) {
            $this->stats[$match_id][$attr['attribute_name']] = $attr['attribute_value'];
        }
        if (array_key_exists($match_id, $this->stats)) {
            $this->stats[$match_id] += $stmt_result;
        }
    }

    function generateOverview(): void {
        foreach ($this->stats as $match) {
            if ($match['result'] == 'home') {
                $this->check_and_increment('teams', $match['home_id'], [$match['Home Team'], 1, 1, 0, 0], 1);
                $this->check_and_increment('teams', $match['away_id'], [$match['Away Team'], 1, 0, 0, 1], 1);
            } else if ($match['result'] == 'away') {
                $this->check_and_increment('teams', $match['home_id'], [$match['Home Team'], 1, 0, 0, 1], 1);
                $this->check_and_increment('teams', $match['away_id'], [$match['Away Team'], 1, 1, 0, 0], 1);
            } else {
                $this->check_and_increment('teams', $match['home_id'], [$match['Home Team'], 1, 0, 1, 0], 1);
                $this->check_and_increment('teams', $match['away_id'], [$match['Away Team'], 1, 0, 1, 0], 1);
            }
        }
        $this->buildModal();
    }

    function check_and_increment(string $key, int|string $object, array $value, int $start) {
        if (array_key_exists($object, $this->analysis[$key])) {
            for ($start; $start < count($value); $start++) {
                $this->analysis[$key][$object][$start] += $value[$start];
            }
        } else {
            $this->analysis[$key][$object] = $value;
        }
    }

    function buildModal() {
        $html = '<div id="analysisModal" class="modal" tabindex="-1" role="dialog" aria-labelledby="confirm-modal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4>Analysis</h4>
                </div>
                <div class="modal-body">
                <button type="button" onclick="percentage()">Switch</button>

                <p>Filter by Count</p>
                <table class="table table-bordered">
                    <tbody><tr>
                        <td>Minimum:</td>
                        <td><input type="integer" id="min" name="max"></td>
                    </tr>
                    <tr>
                        <td>Maximum:</td>
                        <td><input type="integer" id="max" name="max"></td>
                    </tr>
                    </tbody>
                </table>';
        $html .= $this->buildTable($this->analysis['teams'], ['Team', 'Count', 'Wins', 'Draws', 'Losses'], "teams");
        $html .= '</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="reset_values()" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>';

        $this->return_data['html'] = $html;
        array_push($this->return_data['ids'], 'teams');
    }

    function buildTable(array $rows, array $columns, string $id) {
        $table = "<table id='$id'><thead><tr>";
        foreach ($columns as $col) {
            $table .= "<th>$col</th>";
        }
        $table .= "</tr></thead><tbody>";
        foreach ($rows as $row) {
            $table .= "<tr>";
            foreach ($row as $data) {
                $table .= "<td>$data</td>";
            }
            $table .= "</tr>";
        }
        $table .= "</tbody></table>";
        return $table;
    }
}
