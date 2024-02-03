<?php
session_start();
require_once('config.php');
function elog(mixed $contents): void {
    if (gettype($contents) == 'array') error_log(json_encode($contents) . PHP_EOL, 3, LOG_LOCATION);
    else error_log($contents . PHP_EOL, 3, LOG_LOCATION);
}
class UserSession {
    private static ?UserSession $instance = null;
    private mysqli|false $db_local;
    private mysqli|false $db_remote;
    private mysqli|false $db;
    // Table names
    public string $overview = MATCHES_DB . "";
    public string $attributes = MATCHES_DB . "";
    public string $qualifiers = MATCHES_DB . "";
    public string $events = MATCHES_DB . "";

    const LOCAL_DB = "local";
    const REMOTE_DB = "remote";

    private function __construct() {
        $this->db_local = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DATA_DB);
        try {
            // $this->db_remote = mysqli_connect(DB_HOST_REMOTE, DB_USER, DB_PASSWORD_REMOTE, DATA_DB);
        } catch (Exception $e) {
        }
        $this->db = $this->db_local;
    }

    public static function getInstance(): ?UserSession {
        if (!self::$instance) {
            self::$instance = new UserSession();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli|bool {
        return $this->db;
    }

    public function toggleConnection($flag) {
        if ($flag == self::LOCAL_DB) $this->db = $this->db_local;
        else if ($flag ==  self::REMOTE_DB) $this->db = $this->db_remote;
    }

    public function prepare(string $stmt, string $location, array $args): mysqli_stmt|bool {
        try {
            $result = $this->db->prepare($stmt);
        } catch (Exception $e) {
            error_log("prepare() failed: in $location - " . $e->getMessage() . "\n$stmt - args: " . json_encode($args) . "\n", 3, LOG_LOCATION);
            return false;
        }
        if (!$result) {
            error_log("prepare() failed: in $location - " . htmlspecialchars($this->db->error) . "\n$stmt - args: " . json_encode($args) . "\n", 3, LOG_LOCATION);
        }
        return $result;
    }

    public function query(string $stmt, string $location, bool $flag = false): mysqli_result|bool {
        try {
            $result = $this->db->query($stmt);
        } catch (Exception $e) {
            error_log("query() failed: in $location - " . $e->getMessage() . "\n$stmt\n", 3, LOG_LOCATION);
            return false;
        }
        if (!$result && !$flag) {
            error_log("query() failed: in $location - " . htmlspecialchars($this->db->error) . "\n$stmt\n", 3, LOG_LOCATION);
        }

        return $result;
    }

    public function execute(mysqli_stmt &$stmt, string $location, string $query, array $args): bool {
        try {
            if (!$stmt->execute()) {
                error_log("execute() failed: in $location - " . htmlspecialchars($this->db->error) . "\n$query - args: " . json_encode($args) . "\n", 3, LOG_LOCATION);
            }
        } catch (Exception $e) {
            error_log("execute() failed: in $location - " . $e->getMessage() . "\n$query - args: " . json_encode($args) . "\n", 3, LOG_LOCATION);
            return false;
        }
        return true;
    }

    public function getResults(mysqli_stmt &$stmt) {
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $result;
    }

    public function __destruct() {
        $this->db->close();
    }

    private function cookieSet($name, $data) {
        setcookie($name, $data, time() + 31536000, "/");
    }

    public function getUserIP() {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];
        return filter_var($client, FILTER_VALIDATE_IP) ? $client : (filter_var($forward, FILTER_VALIDATE_IP) ? $forward : $remote);
    }
}
