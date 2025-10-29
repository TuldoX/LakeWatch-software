<?php
namespace App\Service;

use Psr\Container\ContainerInterface;

class UserModel {
    protected $db;

    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');

        if (!$this->db) {
            throw new \Exception("Database connection not available");
        }
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
        $sql = "SELECT * FROM probes WHERE user_id = $1";
        $result = pg_query_params($this->db, $sql, [$id]);

        if (!$result) {
            throw new \Exception("Query failed: " . pg_last_error($this->db));
        }

        return pg_fetch_all($result) ?: [];
    }
}