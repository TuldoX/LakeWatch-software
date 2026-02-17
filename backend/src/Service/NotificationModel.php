<?php
namespace App\Service;

use App\Database\Database;
use PDO;
use PDOException;

class NotificationModel {
    private PDO $db;

    public function __construct(Database $database){
        $this->db = $database->getConnection();
    }

    public function markRead(string $id){
        try {
            $stmt = $this->db->prepare(''); //TODO: query dorobiť
            $stmt->execute(['id' => $id]); //TODO: na toto pozor
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception('Database query failed: ' . $e->getMessage());
        }
    }
}