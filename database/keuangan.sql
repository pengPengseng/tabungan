-- Database Schema for Keuangan App


-- 1. Tabel Kategori
CREATE TABLE IF NOT EXISTS `kategori` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kategori` VARCHAR(100) NOT NULL,
  `tipe` ENUM('pemasukan', 'pengeluaran') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel Usaha
CREATE TABLE IF NOT EXISTS `usaha` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_usaha` VARCHAR(150) NOT NULL,
  `keterangan` VARCHAR(255) NULL,
  `status` ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel Transaksi
CREATE TABLE IF NOT EXISTS `transaksi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kategori_id` INT NOT NULL,
  `usaha_id` INT NULL DEFAULT NULL,
  `tipe` ENUM('pemasukan', 'pengeluaran') NOT NULL,
  `jumlah` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `keterangan` VARCHAR(255) NULL,
  `tanggal` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_transaksi_kategori` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_transaksi_usaha` FOREIGN KEY (`usaha_id`) REFERENCES `usaha` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabel Item Transaksi
CREATE TABLE IF NOT EXISTS `item_transaksi` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaksi_id` INT NOT NULL,
  `nama_item` VARCHAR(150) NOT NULL,
  `jumlah_qty` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
  `harga_satuan` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_item_transaksi` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEED DATA
-- Insert Default Kategori
INSERT INTO `kategori` (`id`, `nama_kategori`, `tipe`) VALUES
(1, 'Gaji', 'pemasukan'),
(2, 'Pemasukan Saham', 'pemasukan'),
(3, 'Pemasukan Usaha', 'pemasukan'),
(4, 'Lainnya (Pemasukan)', 'pemasukan'),
(5, 'Makan & Minum', 'pengeluaran'),
(6, 'Transportasi', 'pengeluaran'),
(7, 'Tagihan & Utilitas', 'pengeluaran'),
(8, 'Pengeluaran Usaha', 'pengeluaran'),
(9, 'Hiburan & Belanja', 'pengeluaran'),
(10, 'Lainnya (Pengeluaran)', 'pengeluaran')
ON DUPLICATE KEY UPDATE `nama_kategori` = VALUES(`nama_kategori`);
