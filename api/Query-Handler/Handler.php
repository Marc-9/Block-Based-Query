<?php

class Handler {
    private UserSession $session;
    public array $response = ["response_code" => null, "message" => null];
    private array $args;

    public function __construct(UserSession $session, array $body) {
        $this->session = $session;
        $this->args = $body;
    }

    public function post_query(string $body) {
        $query = new Query($this->session);
        $running_time = 0;
        $base64 = str_replace('=', '', strtr($body, '+/', '-_'));
        $query_sql = "INSERT INTO " . QUERY_DB . ".complex_query (query_time, total_results, json_object) VALUES (0, 0, ?)";
        $stmt = $this->session->prepare($query_sql, getcwd(), [$base64]);
        $stmt->bind_param("s", $base64);
        $this->session->execute($stmt, getcwd(), $query_sql, [$base64]);
        create_query($this->args, $stmt->insert_id, null, $running_time, $this->session);
        $query->match_ids = parseExpression($this->args, []);
        $query->match_ids = array_unique($query->match_ids);
        $this->session->query("UPDATE " . QUERY_DB . ".complex_query SET query_time = $running_time, total_results = " . count($query->match_ids) . " WHERE id = " . $stmt->insert_id, getcwd());
        $query->limit_results();
        $this->response = ["games" => $query->match_ids];
    }
}
