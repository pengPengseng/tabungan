<?php
require_once __DIR__ . '/../includes/functions.php';

$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
        $tipe = sanitize($_POST['tipe'] ?? '');

        if (empty($nama_kategori) || !in_array($tipe, ['pemasukan', 'pengeluaran'])) {
            set_flash_message('error', 'Nama kategori dan tipe wajib diisi dengan benar.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO kategori (nama_kategori, tipe) VALUES (:nama, :tipe)");
                $stmt->execute(['nama' => $nama_kategori, 'tipe' => $tipe]);
                set_flash_message('success', "Kategori '{$nama_kategori}' berhasil ditambahkan.");
            } catch (PDOException $e) {
                set_flash_message('error', 'Gagal menambahkan kategori: ' . $e->getMessage());
            }
        }
        header("Location: ../pages/kategori.php");
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nama_kategori = sanitize($_POST['nama_kategori'] ?? '');
        $tipe = sanitize($_POST['tipe'] ?? '');

        if ($id <= 0 || empty($nama_kategori) || !in_array($tipe, ['pemasukan', 'pengeluaran'])) {
            set_flash_message('error', 'Data update kategori tidak valid.');
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE kategori SET nama_kategori = :nama, tipe = :tipe WHERE id = :id");
                $stmt->execute(['nama' => $nama_kategori, 'tipe' => $tipe, 'id' => $id]);
                set_flash_message('success', "Kategori '{$nama_kategori}' berhasil diperbarui.");
            } catch (PDOException $e) {
                set_flash_message('error', 'Gagal memperbarui kategori: ' . $e->getMessage());
            }
        }
        header("Location: ../pages/kategori.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        try {
            // Check if used in transactions
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM transaksi WHERE kategori_id = :id");
            $stmtCheck->execute(['id' => $id]);
            if ($stmtCheck->fetchColumn() > 0) {
                set_flash_message('error', 'Kategori tidak dapat dihapus karena sudah digunakan dalam transaksi.');
            } else {
                $stmt = $pdo->prepare("DELETE FROM kategori WHERE id = :id");
                $stmt->execute(['id' => $id]);
                set_flash_message('success', 'Kategori berhasil dihapus.');
            }
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menghapus kategori: ' . $e->getMessage());
        }
    }
    header("Location: ../pages/kategori.php");
    exit;
}

header("Location: ../pages/kategori.php");
exit;
