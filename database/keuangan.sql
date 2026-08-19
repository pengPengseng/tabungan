-- Database Schema for Keuangan App
CREATE DATABASE IF NOT EXISTS `keuangan` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `keuangan`;

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

-- Insert Default Usaha
INSERT INTO `usaha` (`id`, `nama_usaha`, `keterangan`, `status`) VALUES
(1, 'Warung Kopi Berkah', 'Usaha kedai kopi dan camilan harian', 'aktif'),
(2, 'Toko Online ABC', 'Toko fashion online di marketplace', 'aktif')
ON DUPLICATE KEY UPDATE `nama_usaha` = VALUES(`nama_usaha`);

-- Insert Sample Transaksi
INSERT INTO `transaksi` (`id`, `kategori_id`, `usaha_id`, `tipe`, `jumlah`, `keterangan`, `tanggal`) VALUES
(1, 1, NULL, 'pemasukan', 12000000.00, 'Gaji Bulan Ini', CURRENT_DATE()),
(2, 3, 1, 'pemasukan', 4500000.00, 'Penjualan Kopi Minggu 1 & 2', CURRENT_DATE()),
(3, 8, 1, 'pengeluaran', 1250000.00, 'Belanja bahan baku kopi & sewa meja', CURRENT_DATE()),
(4, 5, NULL, 'pengeluaran', 850000.00, 'Makan harian keluarga', CURRENT_DATE()),
(5, 7, NULL, 'pengeluaran', 600000.00, 'Listrik & Wifi Rumah', CURRENT_DATE())
ON DUPLICATE KEY UPDATE `jumlah` = VALUES(`jumlah`);

-- Insert Sample Items untuk Transaksi #3 (Pengeluaran Usaha)
INSERT INTO `item_transaksi` (`transaksi_id`, `nama_item`, `jumlah_qty`, `harga_satuan`, `subtotal`) VALUES
(3, 'Biji Kopi Arabika (kg)', 5.00, 150000.00, 750000.00),
(3, 'Susu UHT Full Cream (karton)', 2.00, 200000.00, 400000.00),
(3, 'Sirup Vanilla (botol)', 2.00, 50000.00, 100000.00);
