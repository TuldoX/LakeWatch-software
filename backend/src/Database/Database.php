<?php
namespace App\Database;

use PDO;

class Database {
    private $connection;
    
    public function connect() {
        $host = getenv('POSTGRES_HOST') ?? 'database';
        $dbname = getenv('POSTGRES_DB') ?? 'lakewatch';
        $user = getenv('POSTGRES_USER') ?? 'postgres';
        $password = getenv('POSTGRES_PASSWORD') ?? 'password';
        
        $dsn = "pgsql:host=$host;dbname=$dbname";
        $this->connection = new PDO($dsn, $user, $password);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        return $this->connection;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}