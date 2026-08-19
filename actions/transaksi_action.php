<?php
require_once __DIR__ . '/../includes/functions.php';

$action = $_REQUEST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $kategori_id = (int)($_POST['kategori_id'] ?? 0);
        $usaha_id = !empty($_POST['usaha_id']) ? (int)$_POST['usaha_id'] : null;
        $tipe = sanitize($_POST['tipe'] ?? '');
        $tanggal = sanitize($_POST['tanggal'] ?? date('Y-m-d'));
        $keterangan = sanitize($_POST['keterangan'] ?? '');
        $jumlah_manual = (float)($_POST['jumlah'] ?? 0);
        $items = $_POST['items'] ?? [];

        // Validate basic fields
        if ($kategori_id <= 0 || !in_array($tipe, ['pemasukan', 'pengeluaran']) || empty($tanggal)) {
            set_flash_message('error', 'Kategori, tipe, dan tanggal wajib diisi.');
            header("Location: ../pages/transaksi.php");
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Calculate total from items if items exist
            $calculated_total = 0;
            $valid_items = [];

            if (is_array($items)) {
                foreach ($items as $item) {
                    $nama_item = sanitize($item['nama_item'] ?? '');
                    $qty = (float)($item['jumlah_qty'] ?? 1);
                    $harga = (float)($item['harga_satuan'] ?? 0);

                    if (!empty($nama_item) && $harga >= 0) {
                        $subtotal = $qty * $harga;
                        $calculated_total += $subtotal;
                        $valid_items[] = [
                            'nama_item' => $nama_item,
                            'jumlah_qty' => $qty,
                            'harga_satuan' => $harga,
                            'subtotal' => $subtotal
                        ];
                    }
                }
            }

            // Final total amount
            $jumlah_final = (count($valid_items) > 0) ? $calculated_total : $jumlah_manual;

            if ($action === 'create') {
                $stmt = $pdo->prepare("
                    INSERT INTO transaksi (kategori_id, usaha_id, tipe, jumlah, keterangan, tanggal)
                    VALUES (:kategori_id, :usaha_id, :tipe, :jumlah, :keterangan, :tanggal)
                ");
                $stmt->execute([
                    'kategori_id' => $kategori_id,
                    'usaha_id' => $usaha_id,
                    'tipe' => $tipe,
                    'jumlah' => $jumlah_final,
                    'keterangan' => $keterangan,
                    'tanggal' => $tanggal
                ]);
                $transaksi_id = $pdo->lastInsertId();

                set_flash_message('success', 'Transaksi berhasil ditambahkan.');
            } else {
                // Update
                if ($id <= 0) {
                    throw new Exception('ID Transaksi tidak valid.');
                }
                $stmt = $pdo->prepare("
                    UPDATE transaksi 
                    SET kategori_id = :kategori_id, usaha_id = :usaha_id, tipe = :tipe, jumlah = :jumlah, 
                        keterangan = :keterangan, tanggal = :tanggal
                    WHERE id = :id
                ");
                $stmt->execute([
                    'kategori_id' => $kategori_id,
                    'usaha_id' => $usaha_id,
                    'tipe' => $tipe,
                    'jumlah' => $jumlah_final,
                    'keterangan' => $keterangan,
                    'tanggal' => $tanggal,
                    'id' => $id
                ]);
                $transaksi_id = $id;

                // Delete old items for update
                $stmtDel = $pdo->prepare("DELETE FROM item_transaksi WHERE transaksi_id = :tid");
                $stmtDel->execute(['tid' => $transaksi_id]);

                set_flash_message('success', 'Transaksi berhasil diperbarui.');
            }

            // Insert items if present
            if (count($valid_items) > 0) {
                $stmtItem = $pdo->prepare("
                    INSERT INTO item_transaksi (transaksi_id, nama_item, jumlah_qty, harga_satuan, subtotal)
                    VALUES (:tid, :nama_item, :qty, :harga, :subtotal)
                ");
                foreach ($valid_items as $vi) {
                    $stmtItem->execute([
                        'tid' => $transaksi_id,
                        'nama_item' => $vi['nama_item'],
                        'qty' => $vi['jumlah_qty'],
                        'harga' => $vi['harga_satuan'],
                        'subtotal' => $vi['subtotal']
                    ]);
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            set_flash_message('error', 'Gagal menyimpan transaksi: ' . $e->getMessage());
        }

        header("Location: ../pages/transaksi.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = :id");
            $stmt->execute(['id' => $id]);
            set_flash_message('success', 'Transaksi berhasil dihapus.');
        } catch (PDOException $e) {
            set_flash_message('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }
    header("Location: ../pages/transaksi.php");
    exit;
}

header("Location: ../pages/transaksi.php");
exit;
