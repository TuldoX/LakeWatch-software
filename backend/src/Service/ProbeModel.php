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

    public function postData(string $probe_id, string $btr_life, string $temp, string $tds, string $o2, string $ph){
        $insertSql = "
            INSERT INTO values (probe_id, temp, tds, oxygen, ph, time_recieved)
            VALUES ($1, $2, $3, $4, $5, NOW())
            RETURNING *;
        ";

        $insertResult = pg_query_params($this->db, $insertSql, [$probe_id, $temp, $tds, $o2, $ph]);
        if (!$insertResult) {
            throw new \Exception("Insert failed: " . pg_last_error($this->db));
        }

        $inserted = pg_fetch_all($insertResult);

        $updateSql = "
            UPDATE probes
            SET btr_life = $1,
            lst_data = NOW()
            WHERE id = $2;
        ";

        $updateResult = pg_query_params($this->db, $updateSql, [$btr_life,$probe_id]);
        if (!$updateResult) {
            throw new \Exception("Battery update failed: " . pg_last_error($this->db));
        }

        return $inserted ?: [];
    }

}