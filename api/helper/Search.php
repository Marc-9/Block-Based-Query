<?php

class Search {
    private string $key;
    private int $limit;
    private string $column;
    private string $table;
    private UserSession $session;

    public function __construct(string $key, int $limit, string $column, string $table, UserSession $session) {
        $this->key = $key;
        $this->limit = $limit;
        $this->column = $column;
        $this->table = $table;
        $this->session = $session;
    }

    public function find_results(): array {
        $results = [];
        // I only have the IN () to include teams where we have match event data for
        $query = "SELECT id, $this->column FROM $this->table WHERE $this->column LIKE ? and id IN (10958,7024,10956,2100,10945,8454,7023,1980,6591,10913,10167,10922,2024,10955,6580,10929,8406,6534,6533,8443,10932,8357,10954,10920,10905,10950,2108,10926,10921,10951,10941,10957,2077,8435,6553,8431,8433,10939,10915,10907,6583,10910,8438,24200,10946,2062,8425,10952,10934,10933,10924,10927,10935,8446,10906,10931,10923,10940,4679,8442) LIMIT $this->limit";
        $stmt = $this->session->prepare($query, getcwd(), [$this->key]);
        $stmt->bind_param("s", $this->key);
        $this->session->execute($stmt, getcwd(), $query, [$this->key]);
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $results[] = $row['name'];
        }
        return $results;
    }
}
