<?php
// Config Connection PDO MySQL (with Automatic SQLite Fallback for Vercel Demo)

define('DB_HOST', getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: 'localhost'));
define('DB_USER', getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'root'));
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : ''));
define('DB_NAME', getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'keuangan'));
define('DB_PORT', getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: '3306'));

$db_driver = 'mysql';

function get_db_connection() {
    global $db_driver;
    static $pdo = null;
    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        // 1. Try MySQL Connection (Local or Cloud like TiDB / Aiven / Railway)
        $use_mysql = (getenv('DB_HOST') || getenv('MYSQL_HOST') || DB_HOST === 'localhost');

        if ($use_mysql) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                $db_driver = 'mysql';

                // Auto initialize database tables if kategori table doesn't exist
                $checkTable = $pdo->query("SHOW TABLES LIKE 'kategori'")->rowCount();
                if ($checkTable === 0) {
                    $sqlFile = __DIR__ . '/../database/keuangan.sql';
                    if (file_exists($sqlFile)) {
                        $sql = file_get_contents($sqlFile);
                        $pdo->exec($sql);
                    }
                }
                return $pdo;
            } catch (PDOException $e) {
                // If local XAMPP and db not created yet
                if (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1') {
                    try {
                        $dsn_no_db = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                        $pdo_init = new PDO($dsn_no_db, DB_USER, DB_PASS, $options);
                        $pdo_init->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        
                        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                        
                        $sqlFile = __DIR__ . '/../database/keuangan.sql';
                        if (file_exists($sqlFile)) {
                            $sql = file_get_contents($sqlFile);
                            $pdo->exec($sql);
                        }
                        $db_driver = 'mysql';
                        return $pdo;
                    } catch (Exception $ex) {
                        // fallback to SQLite below
                    }
                }
            }
        }

        // 2. Fallback to SQLite (Guarantees Vercel web is 100% working immediately without crash)
        try {
            $sqlite_path = sys_get_temp_dir() . '/keuangan_app.db';
            $dsn_sqlite = "sqlite:" . $sqlite_path;
            $pdo = new PDO($dsn_sqlite, null, null, $options);
            $db_driver = 'sqlite';

            // Register MySQL-compatible date functions for SQLite
            if (method_exists($pdo, 'sqliteCreateFunction')) {
                $pdo->sqliteCreateFunction('MONTH', function($date) {
                    return $date ? (int)date('m', strtotime($date)) : 0;
                });
                $pdo->sqliteCreateFunction('YEAR', function($date) {
                    return $date ? (int)date('Y', strtotime($date)) : 0;
                });
            }

            // Auto initialize SQLite tables
            $checkTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='kategori'")->fetchColumn();
            if (!$checkTable) {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS kategori (
                      id INTEGER PRIMARY KEY AUTOINCREMENT,
                      nama_kategori TEXT NOT NULL,
                      tipe TEXT CHECK(tipe IN ('pemasukan', 'pengeluaran')) NOT NULL
                    );
                    CREATE TABLE IF NOT EXISTS usaha (
                      id INTEGER PRIMARY KEY AUTOINCREMENT,
                      nama_usaha TEXT NOT NULL,
                      keterangan TEXT,
                      status TEXT CHECK(status IN ('aktif', 'nonaktif')) NOT NULL DEFAULT 'aktif',
                      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                    CREATE TABLE IF NOT EXISTS transaksi (
                      id INTEGER PRIMARY KEY AUTOINCREMENT,
                      kategori_id INTEGER NOT NULL,
                      usaha_id INTEGER,
                      tipe TEXT CHECK(tipe IN ('pemasukan', 'pengeluaran')) NOT NULL,
                      jumlah REAL NOT NULL DEFAULT 0.0,
                      keterangan TEXT,
                      tanggal DATE NOT NULL,
                      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    );
                    CREATE TABLE IF NOT EXISTS item_transaksi (
                      id INTEGER PRIMARY KEY AUTOINCREMENT,
                      transaksi_id INTEGER NOT NULL,
                      nama_item TEXT NOT NULL,
                      jumlah_qty REAL NOT NULL DEFAULT 1.0,
                      harga_satuan REAL NOT NULL DEFAULT 0.0,
                      subtotal REAL NOT NULL DEFAULT 0.0
                    );

                    INSERT OR IGNORE INTO kategori (id, nama_kategori, tipe) VALUES
                    (1, 'Gaji', 'pemasukan'),
                    (2, 'Pemasukan Saham', 'pemasukan'),
                    (3, 'Pemasukan Usaha', 'pemasukan'),
                    (4, 'Lainnya (Pemasukan)', 'pemasukan'),
                    (5, 'Makan & Minum', 'pengeluaran'),
                    (6, 'Transportasi', 'pengeluaran'),
                    (7, 'Tagihan & Utilitas', 'pengeluaran'),
                    (8, 'Pengeluaran Usaha', 'pengeluaran'),
                    (9, 'Hiburan & Belanja', 'pengeluaran'),
                    (10, 'Lainnya (Pengeluaran)', 'pengeluaran');
                ");
            }
            return $pdo;
        } catch (Exception $sqlite_ex) {
            die("Koneksi Database Gagal: " . $sqlite_ex->getMessage());
        }
    }
    return $pdo;
}

$pdo = get_db_connection();
