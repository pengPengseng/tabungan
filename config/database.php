<?php
// Config Connection PDO MySQL (Supports Local XAMPP & Remote Cloud MySQL like Aiven / Railway)
define('DB_HOST', getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: 'localhost'));
define('DB_USER', getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'root'));
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : ''));
define('DB_NAME', getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'keuangan'));
define('DB_PORT', getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: '3306'));

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Connect to actual database
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            
            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If on localhost and database doesn't exist, create it (XAMPP environment)
                if (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1') {
                    $dsn_no_db = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                    $pdo_init = new PDO($dsn_no_db, DB_USER, DB_PASS, $options);
                    $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                } else {
                    throw $e;
                }
            }
            
            // Auto initialize database tables if kategori table doesn't exist yet
            $checkTable = $pdo->query("SHOW TABLES LIKE 'kategori'")->rowCount();
            if ($checkTable === 0) {
                $sqlFile = __DIR__ . '/../database/keuangan.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    $pdo->exec($sql);
                }
            }
        } catch (PDOException $e) {
            die("Koneksi Database Gagal: " . $e->getMessage() . " (Pastikan MySQL aktif atau atur Environment Variables di Vercel)");
        }
    }
    return $pdo;
}

$pdo = get_db_connection();
