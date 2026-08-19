<?php
require_once __DIR__ . '/../includes/functions.php';

$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $nama_usaha = sanitize($_POST['nama_usaha'] ?? '');
        $keterangan = sanitize($_POST['keterangan'] ?? '');
        $status = sanitize($_POST['status'] ?? 'aktif');

        if (empty($nama_usaha)) {
            set_flash_message('error', 'Nama usaha wajib diisi.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO usaha (nama_usaha, keterangan, status) VALUES (:nama, :ket, :status)");
                $stmt->execute(['nama' => $nama_usaha, 'ket' => $keterangan, 'status' => $status]);
                set_flash_message('success', "Usaha '{$nama_usaha}' berhasil ditambahkan.");
            } catch (PDOException $e) {
                set_flash_message('error', 'Gagal menambahkan usaha: ' . $e->getMessage());
            }
        }
        header("Location: ../pages/usaha.php");
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $nama_usaha = sanitize($_POST['nama_usaha'] ?? '');
        $keterangan = sanitize($_POST['keterangan'] ?? '');
        $status = sanitize($_POST['status'] ?? 'aktif');

        if ($id <= 0 || empty($nama_usaha)) {
            set_flash_message('error', 'Data update usaha tidak valid.');
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE usaha SET nama_usaha = :nama, keterangan = :ket, status = :status WHERE id = :id");
                $stmt->execute(['nama' => $nama_usaha, 'ket' => $keterangan, 'status' => $status, 'id' => $id]);
                set_flash_message('success', "Usaha '{$nama_usaha}' berhasil diperbarui.");
            } catch (PDOException $e) {
                set_flash_message('error', 'Gagal memperbarui usaha: ' . $e->getMessage());
            }
        }
        header("Location: ../pages/usaha.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        try {
            // Note: As per PRD line 117, ON DELETE SET NULL is used in foreign key so transactions are preserved.
            $stmt = $pdo->prepare("DELETE FROM usaha WHERE id = :id");
            $stmt->execute(['id' => $id]);
            set_flash_message('success', 'Usaha berhasil dihapus (riwayat transaksi tetap tersimpan).');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menghapus usaha: ' . $e->getMessage());
        }
    }
    header("Location: ../pages/usaha.php");
    exit;
}

header("Location: ../pages/usaha.php");
exit;
