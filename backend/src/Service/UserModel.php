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

    public function getNotifications(string $id) {
        try {
            $stmt = $this->db->prepare('
            SELECT 
                n.id AS id, 
                n.type, 
                n.message, 
                n.heading, 
                l.name AS location, 
                p.name AS probe,
                n.time AS time
            FROM notifications n
            JOIN probes p ON n.probe_id = p.id
            JOIN locations l ON p.location_id = l.id
            WHERE n.user_id = :user_id
            ORDER BY n.time DESC
        ');

            $stmt->execute(['user_id' => $id]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch and return notifications

        } catch (PDOException $e) {
            throw new \Exception('Database query failed: ' . $e->getMessage());
        }
    }

    public function getNews(string $id){
        try {
            $stmt = $this->db->prepare('
                SELECT EXISTS (
                SELECT 1 
                FROM notifications
                WHERE user_id = :user_id
                LIMIT 1
            );
            ');
            $stmt->execute(['user_id' => $id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception('Database query failed: ' . $e->getMessage());
        }
    }
}