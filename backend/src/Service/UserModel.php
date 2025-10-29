<?php
namespace App\Service;

use App\Database\Database;

class UserModel {
    protected $db;

    public function __construct(Database $database) {
        $this->db = $database->getConnection();
    }

    public function userExists(string $id): bool {
        $sql = "SELECT COUNT(*) FROM users WHERE id = $1";
        $result = pg_query_params($this->db, $sql, [$id]);

        if (!$result) {
            throw new \Exception("Query failed: " . pg_last_error($this->db));
        }

        $count = pg_fetch_result($result, 0, 0);
        return $count > 0;
    }

    public function getProbesByUser(string $id): array {
        $sql = "SELECT 
                    probes.id AS probe_id,
                    probes.name,
                    locations.name AS location,
                    probes.btr_life,
                    probes.lst_data
                FROM probes
                INNER JOIN locations ON locations.id = probes.location_id
                WHERE user_id=$1";
        $result = pg_query_params($this->db, $sql, [$id]);

        if (!$result) {
            throw new \Exception("Query failed: " . pg_last_error($this->db));
        }

        return pg_fetch_all($result) ?: [];
    }
}