<?php
namespace App\Service;

use App\Database\Database;

class ProbeModel {
    protected $db;

    public function __construct(Database $database) {
        $this->db = $database->getConnection();
    }

    public function probeExists(string $id) {
        $sql = "SELECT COUNT(*) FROM probes WHERE id = $1";
        $result = pg_query_params($this->db, $sql, [$id]);

        if (!$result) {
            throw new \Exception("Query failed: " . pg_last_error($this->db));
        }

        $count = pg_fetch_result($result, 0, 0);
        return $count > 0;
    }

    public function getProbeValues(string $probe_id, string $hours) : array{
         $hours = (int)$hours;

        $sql = "
            SELECT
                temp,
                tds,
                ph,
                oxygen,
                time_recieved
            FROM values
            WHERE time_recieved >= NOW() - INTERVAL '$hours hours'
            AND probe_id = $1
        ";
        
        $result = pg_query_params($this->db, $sql, [$probe_id]);

        if (!$result) {
            throw new \Exception("Query failed: " . pg_last_error($this->db));
        }

        return pg_fetch_all($result) ?: [];
    }
}