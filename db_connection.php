<?php
// ─────────────────────────────────────────────────────────────
//  db_connection.php  —  MySQL Database Connection Class
//  Provides a single shared PDO connection (Singleton pattern).
//  Include this file in any PHP script that needs the database.
//
//  Usage:
//      require_once 'db_connection.php';
//      $db   = Database::getInstance();
//      $conn = $db->getConnection();   // returns PDO object
// ─────────────────────────────────────────────────────────────

class Database {

    // ── Connection credentials ───────────────────────────────
    public string $host     = '127.0.0.1';
    public string $port     = '3306';
    public string $dbname   = 'library_db';
    public string $username = 'root';
    public string $password = '';

    // ── Internal state ───────────────────────────────────────
    private static ?Database $instance = null;
    private PDO $conn;

    // ── Private constructor (Singleton) ──────────────────────
    private function __construct() {
        $dsn = "mysql:host={$this->host}"
             . ";port={$this->port}"
             . ";dbname={$this->dbname}"
             . ";charset=utf8mb4";

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password);

            // Throw exceptions on SQL errors
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Return rows as associative arrays by default
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Use native prepared-statement emulation off (more secure)
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $e) {
            // Respond with JSON error and halt — safe for API consumers
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    // ── Get (or create) the single shared instance ───────────
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // ── Return the underlying PDO connection object ──────────
    public function getConnection(): PDO {
        return $this->conn;
    }
}