<?php
namespace App\Service;

use App\Database\Database;
use PDO;
use PDOException;

class ProbeModel {
    private PDO $db;

    public function __construct(Database $database){
        $this->db = $database->getConnection();
    }

    public function probeExists(int $id): bool {
        try {
            $stmt = $this->db->prepare('SELECT EXISTS(SELECT 1 FROM probes WHERE id = :id)');
            $stmt->execute(['id' => $id]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new \Exception('Database query failed: ' . $e->getMessage());
        }
    }

    public function getToken(string $token, int $id): bool {
        try {
            $stmt = $this->db->prepare('SELECT auth FROM probes WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) return false;
            
            $storedHash = $row['auth'];

            return password_verify($token, $storedHash);
        } catch (PDOException $e) {
            throw new \Exception('Database query failed: ' . $e->getMessage());
        }
    }

    public function insertData(int $id, int $batteryLife, float $temperature, int $tds, int $oxygen, float $ph): bool {
        try {
            $this->db->beginTransaction();
            
            $stmt1 = $this->db->prepare('
                INSERT INTO values (probe_id, temp, tds, oxygen, ph, time_recieved) 
                VALUES (:probe_id, :temp, :tds, :oxygen, :ph, NOW())
            ');
            
            $stmt1->execute([
                'probe_id' => $id,
                'temp' => $temperature,
                'tds' => $tds,
                'oxygen' => $oxygen,
                'ph' => $ph
            ]);
            
            $stmt2 = $this->db->prepare('
                UPDATE probes
                SET btr_life = :battery_life,
                    lst_data = NOW()
                WHERE id = :id
            ');

            
            $stmt2->execute([
                'battery_life' => $batteryLife,
                'id' => $id
            ]);
            
            $this->db->commit();
            
            return true;
            
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function getData(int $id,int $hours){
        try {
            $stmt = $this->db->prepare('
                SELECT *
                FROM values
                WHERE probe_id = :probe_id
                AND time_recieved >= NOW() - (:hours * INTERVAL \'1 hour\')
                ORDER BY time_recieved
            ');

            $stmt->execute([
                'probe_id' => $id,
                'hours'    => $hours
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \Exception('Database query failed: ' . $e->getMessage());
        }
    }
}