<?php
namespace App\Service;

use App\Database\Database;
use PDO;
use PDOException;

class UserModel {
    private PDO $db;

    public function __construct(Database $database){
        $this->db = $database->getConnection();
    }

    public function getProbes(string $id){
        try {
            $stmt = $this->db->prepare('
            SELECT
                p.id AS probe_id,
                p.name AS probe_name,
                l.name AS location_name,
                p.btr_life AS battery_life,
                p.lst_data
            FROM probes p
            JOIN locations l ON p.location_id = l.id
            WHERE p.user_id = :user_id
            ORDER BY p.lst_data DESC;
            ');
            $stmt->execute(['user_id' => $id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception('Database query failed: ' . $e->getMessage());
        }
    }
}