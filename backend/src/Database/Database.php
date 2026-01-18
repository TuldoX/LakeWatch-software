<?php
namespace App\Database;

use PDO;
use PDOException;

class Database
{
    private ?PDO $connection = null;

    public function __construct()
    {
        $host = $_ENV['POSTGRES_HOST'];
        $port = $_ENV['POSTGRES_PORT'];
        $dbname = $_ENV['POSTGRES_DB'];
        $username = $_ENV['POSTGRES_USER'];
        $password = $_ENV['POSTGRES_PASSWORD'];

        try {
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $this->connection = new PDO($dsn, $username, $password, $options);
            
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }
}