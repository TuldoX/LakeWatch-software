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
            $stmt = $this->db->prepare('
                DELETE FROM notifications
                WHERE id = :id;
            ');
            $stmt->execute(['id' => $id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception('Database query failed: ' . $e->getMessage());
        }
    }

    public function createNotification(string $type,string $heading,string $message,int $probeId,){
        try {
            $stmt = $this->db->prepare('
                
            ');
            $stmt->execute(['id' => $id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception('Database query failed: ' . $e->getMessage());
        }
    }

}