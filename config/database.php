<?php
// Config Connection PDO MySQL
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'keuangan');

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // First connect without dbname to ensure database exists
            $dsn_no_db = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo_init = new PDO($dsn_no_db, DB_USER, DB_PASS, $options);
            $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Connect to actual database
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Auto initialize database tables if kategori table doesn't exist
            $checkTable = $pdo->query("SHOW TABLES LIKE 'kategori'")->rowCount();
            if ($checkTable === 0) {
                $sqlFile = __DIR__ . '/../database/keuangan.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    $pdo->exec($sql);
                }
            }
        } catch (PDOException $e) {
            die("Koneksi Database Gagal: " . $e->getMessage());
        }
    }
    return $pdo;
}

$pdo = get_db_connection();
