<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Format Angka ke Rupiah
 */
function format_rupiah($nominal, $with_cents = false) {
    $val = (float)$nominal;
    if ($with_cents) {
        return 'Rp ' . number_format($val, 2, ',', '.');
    }
    return 'Rp ' . number_format($val, 0, ',', '.');
}

/**
 * Sanitize User Input
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Flash Message Notification
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_notification'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash_notification'])) {
        $flash = $_SESSION['flash_notification'];
        unset($_SESSION['flash_notification']);
        return $flash;
    }
    return null;
}

/**
 * Get Daftar Kategori
 */
function get_kategori_list($pdo, $tipe = null) {
    if ($tipe) {
        $stmt = $pdo->prepare("SELECT * FROM kategori WHERE tipe = :tipe ORDER BY nama_kategori ASC");
        $stmt->execute(['tipe' => $tipe]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM kategori ORDER BY tipe DESC, nama_kategori ASC");
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

/**
 * Get Daftar Usaha
 */
function get_usaha_list($pdo, $status = null) {
    if ($status) {
        $stmt = $pdo->prepare("SELECT * FROM usaha WHERE status = :status ORDER BY nama_usaha ASC");
        $stmt->execute(['status' => $status]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usaha ORDER BY status ASC, nama_usaha ASC");
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

/**
 * Get Ringkasan Dashboard (Total Saldo Akumulasi, Pemasukan & Pengeluaran Bulan Ini)
 */
function get_dashboard_summary($pdo, $bulan = null, $tahun = null) {
    $bulan = $bulan ? (int)$bulan : (int)date('m');
    $tahun = $tahun ? (int)$tahun : (int)date('Y');

    // Total Saldo Keseluruhan (Akumulasi Semua Waktu)
    $stmtTotalIn = $pdo->query("SELECT COALESCE(SUM(jumlah), 0) AS total FROM transaksi WHERE tipe = 'pemasukan'");
    $totalInAll = (float)$stmtTotalIn->fetchColumn();

    $stmtTotalOut = $pdo->query("SELECT COALESCE(SUM(jumlah), 0) AS total FROM transaksi WHERE tipe = 'pengeluaran'");
    $totalOutAll = (float)$stmtTotalOut->fetchColumn();

    $total_saldo = $totalInAll - $totalOutAll;

    // Pemasukan Bulan Ini
    $stmtInMonth = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) AS total FROM transaksi WHERE tipe = 'pemasukan' AND MONTH(tanggal) = :bulan AND YEAR(tanggal) = :tahun");
    $stmtInMonth->execute(['bulan' => $bulan, 'tahun' => $tahun]);
    $pemasukan_bulan_ini = (float)$stmtInMonth->fetchColumn();

    // Pengeluaran Bulan Ini
    $stmtOutMonth = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) AS total FROM transaksi WHERE tipe = 'pengeluaran' AND MONTH(tanggal) = :bulan AND YEAR(tanggal) = :tahun");
    $stmtOutMonth->execute(['bulan' => $bulan, 'tahun' => $tahun]);
    $pengeluaran_bulan_ini = (float)$stmtOutMonth->fetchColumn();

    return [
        'total_saldo' => $total_saldo,
        'pemasukan_bulan_ini' => $pemasukan_bulan_ini,
        'pengeluaran_bulan_ini' => $pengeluaran_bulan_ini,
        'surplus_bulan_ini' => $pemasukan_bulan_ini - $pengeluaran_bulan_ini,
        'bulan' => $bulan,
        'tahun' => $tahun
    ];
}

/**
 * Get Ringkasan Gaji & Pengeluaran (Bulan Terkait)
 */
function get_gaji_summary($pdo, $bulan = null, $tahun = null) {
    $bulan = $bulan ? (int)$bulan : (int)date('m');
    $tahun = $tahun ? (int)$tahun : (int)date('Y');

    // Query Gaji (Pemasukan dengan Kategori 'Gaji')
    $stmtGaji = $pdo->prepare("
        SELECT COALESCE(SUM(t.jumlah), 0) AS total_gaji
        FROM transaksi t
        JOIN kategori k ON t.kategori_id = k.id
        WHERE k.tipe = 'pemasukan' 
          AND LOWER(k.nama_kategori) LIKE '%gaji%'
          AND MONTH(t.tanggal) = :bulan 
          AND YEAR(t.tanggal) = :tahun
    ");
    $stmtGaji->execute(['bulan' => $bulan, 'tahun' => $tahun]);
    $total_gaji = (float)$stmtGaji->fetchColumn();

    // Breakdown Pengeluaran per Kategori pada Bulan Yang Sama
    $stmtExpBreakdown = $pdo->prepare("
        SELECT k.id, k.nama_kategori, COALESCE(SUM(t.jumlah), 0) as total_pengeluaran
        FROM kategori k
        LEFT JOIN transaksi t ON t.kategori_id = k.id 
            AND MONTH(t.tanggal) = :bulan 
            AND YEAR(t.tanggal) = :tahun 
            AND t.tipe = 'pengeluaran'
        WHERE k.tipe = 'pengeluaran'
        GROUP BY k.id, k.nama_kategori
        HAVING total_pengeluaran > 0
        ORDER BY total_pengeluaran DESC
    ");
    $stmtExpBreakdown->execute(['bulan' => $bulan, 'tahun' => $tahun]);
    $breakdown_pengeluaran = $stmtExpBreakdown->fetchAll();

    // Total Seluruh Pengeluaran Bulan Ini
    $stmtTotalExp = $pdo->prepare("
        SELECT COALESCE(SUM(jumlah), 0) 
        FROM transaksi 
        WHERE tipe = 'pengeluaran' AND MONTH(tanggal) = :bulan AND YEAR(tanggal) = :tahun
    ");
    $stmtTotalExp->execute(['bulan' => $bulan, 'tahun' => $tahun]);
    $total_pengeluaran_bulan = (float)$stmtTotalExp->fetchColumn();

    $sisa_gaji = $total_gaji - $total_pengeluaran_bulan;
    $persen_terpakai = $total_gaji > 0 ? min(100, round(($total_pengeluaran_bulan / $total_gaji) * 100, 1)) : 0;

    return [
        'total_gaji' => $total_gaji,
        'total_pengeluaran_bulan' => $total_pengeluaran_bulan,
        'sisa_gaji' => $sisa_gaji,
        'persen_terpakai' => $persen_terpakai,
        'breakdown' => $breakdown_pengeluaran
    ];
}

/**
 * Get Ringkasan & Detail Usaha
 */
function get_usaha_summary($pdo, $usaha_id = null, $bulan = null, $tahun = null) {
    $whereUsaha = $usaha_id ? " WHERE u.id = :usaha_id " : "";
    $params = [];
    if ($usaha_id) {
        $params['usaha_id'] = (int)$usaha_id;
    }

    $joinCondition = " ON t.usaha_id = u.id ";
    if ($bulan && $tahun) {
        $joinCondition .= " AND MONTH(t.tanggal) = :bulan AND YEAR(t.tanggal) = :tahun ";
        $params['bulan'] = (int)$bulan;
        $params['tahun'] = (int)$tahun;
    }

    $sql = "
        SELECT 
            u.id, 
            u.nama_usaha, 
            u.keterangan, 
            u.status,
            COALESCE(SUM(CASE WHEN t.tipe = 'pemasukan' THEN t.jumlah ELSE 0 END), 0) AS total_pemasukan,
            COALESCE(SUM(CASE WHEN t.tipe = 'pengeluaran' THEN t.jumlah ELSE 0 END), 0) AS total_pengeluaran
        FROM usaha u
        LEFT JOIN transaksi t {$joinCondition}
        {$whereUsaha}
        GROUP BY u.id, u.nama_usaha, u.keterangan, u.status
        ORDER BY u.status ASC, u.nama_usaha ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($usaha_id) {
        $data = $stmt->fetch();
        if ($data) {
            $data['laba_bersih'] = (float)$data['total_pemasukan'] - (float)$data['total_pengeluaran'];
        }
        return $data;
    }

    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $row['laba_bersih'] = (float)$row['total_pemasukan'] - (float)$row['total_pengeluaran'];
    }
    return $rows;
}

/**
 * Get Rincian Item Pengeluaran per Usaha
 */
function get_item_pengeluaran_usaha($pdo, $usaha_id) {
    $stmt = $pdo->prepare("
        SELECT 
            it.id AS item_id,
            it.nama_item,
            it.jumlah_qty,
            it.harga_satuan,
            it.subtotal,
            t.tanggal,
            t.keterangan AS ket_transaksi,
            t.id AS transaksi_id
        FROM item_transaksi it
        JOIN transaksi t ON it.transaksi_id = t.id
        WHERE t.usaha_id = :usaha_id AND t.tipe = 'pengeluaran'
        ORDER BY t.tanggal DESC, it.id DESC
    ");
    $stmt->execute(['usaha_id' => (int)$usaha_id]);
    return $stmt->fetchAll();
}

/**
 * Nama Bulan Bahasa Indonesia
 */
function get_nama_bulan($bln) {
    $bulanMap = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    return $bulanMap[(int)$bln] ?? '';
}
