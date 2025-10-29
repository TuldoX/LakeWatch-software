<?php
namespace App\Database;

class Database {
    private $connection;

    public function __construct() {
        $host = getenv('POSTGRES_HOST');
        $db   = getenv('POSTGRES_DB');
        $user = getenv('POSTGRES_USER');
        $pass = getenv('POSTGRES_PASSWORD');

        $this->connection = @pg_connect("host=$host dbname=$db user=$user password=$pass");
        if (!$this->connection) {
            throw new \Exception("Could not connect to PostgreSQL database.");
        }
    }

    public function getConnection() {
        return $this->connection;
    }
}
