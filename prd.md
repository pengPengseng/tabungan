Plan Pengembangan — Website Pencatatan Keuangan Bulanan
1. Ringkasan

Website pencatatan keuangan pribadi/bulanan tanpa fitur login (single-user, untuk penggunaan pribadi). Mendukung pencatatan pemasukan dan pengeluaran umum, ditambah kategori khusus seperti pemasukan dari saham dan pemasukan dari usaha sendiri — termasuk:

Menu khusus untuk mengelola satu atau beberapa usaha sendiri berdasarkan nama usaha, lengkap dengan rincian item pengeluaran (pembelian apa saja, berapa jumlahnya) dan perhitungan laba/rugi bersih otomatis per usaha.
Ringkasan khusus untuk Gaji: berapa pemasukan gaji tiap bulan, dan ke mana saja uang tersebut terpakai (rincian pengeluaran per kategori pada bulan yang sama).
2. Tech Stack
Backend: PHP native (tanpa framework)
Frontend: Bootstrap 5 + HTML/CSS/JS secukupnya
Database: MySQL
Chart: Chart.js (untuk grafik ringkasan bulanan, per usaha, dan distribusi pengeluaran gaji)
Server lokal: XAMPP / Laragon (Apache + MySQL + PHP)
3. Struktur Folder
keuangan-app/
├── config/
│   └── database.php          # koneksi PDO ke MySQL
├── includes/
│   ├── header.php            # navbar + link Bootstrap
│   ├── footer.php            # penutup halaman + link JS
│   └── functions.php         # helper (format rupiah, hitung saldo, hitung laba/rugi, dll)
├── assets/
│   ├── css/style.css
│   └── js/script.js          # termasuk script tambah/hapus baris item pembelian secara dinamis
├── pages/
│   ├── dashboard.php         # ringkasan saldo & grafik bulanan + ringkasan gaji bulan berjalan
│   ├── transaksi.php         # list transaksi + form tambah/edit (dengan rincian item opsional)
│   ├── kategori.php          # kelola kategori pemasukan/pengeluaran
│   ├── usaha.php             # list usaha + detail per usaha (rincian pengeluaran & laba/rugi)
│   └── laporan.php           # laporan & filter per bulan/tahun, termasuk laporan gaji
├── actions/
│   ├── transaksi_action.php  # proses CRUD transaksi + simpan rincian item pembelian
│   ├── kategori_action.php   # proses CRUD kategori
│   └── usaha_action.php      # proses CRUD usaha (insert/update/delete)
├── database/
│   └── keuangan.sql          # struktur & seed database
└── index.php                  # entry point, redirect ke dashboard.php
4. Skema Database (MySQL)

Tabel kategori

Kolom	Tipe	Keterangan
id	INT (PK, AI)	
nama_kategori	VARCHAR(100)	mis. Gaji, Saham, Usaha, Makan, Transportasi
tipe	ENUM('pemasukan','pengeluaran')	

Tabel usaha

Kolom	Tipe	Keterangan
id	INT (PK, AI)	
nama_usaha	VARCHAR(150)	nama usaha, mis. "Warung Kopi Berkah", "Toko Online ABC"
keterangan	VARCHAR(255)	deskripsi singkat usaha (opsional)
status	ENUM('aktif','nonaktif')	default 'aktif'
created_at	TIMESTAMP	default current_timestamp

Tabel transaksi

Kolom	Tipe	Keterangan
id	INT (PK, AI)	
kategori_id	INT (FK -> kategori.id)	
usaha_id	INT (FK -> usaha.id), NULLABLE	diisi jika transaksi terkait usaha tertentu
tipe	ENUM('pemasukan','pengeluaran')	
jumlah	DECIMAL(15,2)	nominal total (otomatis dihitung dari rincian item jika ada)
keterangan	VARCHAR(255)	deskripsi tambahan (opsional)
tanggal	DATE	tanggal transaksi
created_at	TIMESTAMP	default current_timestamp

Tabel item_transaksi (baru — rincian pembelian per transaksi)

Kolom	Tipe	Keterangan
id	INT (PK, AI)	
transaksi_id	INT (FK -> transaksi.id, ON DELETE CASCADE)	rincian ini milik transaksi mana
nama_item	VARCHAR(150)	nama barang/keperluan, mis. "Kopi Arabika 5kg", "Sewa Tempat", "Gaji Karyawan"
jumlah_qty	DECIMAL(10,2)	kuantitas, default 1 (bisa dikosongkan/diisi 1 utk pengeluaran non-barang seperti sewa)
harga_satuan	DECIMAL(15,2)	harga per satuan
subtotal	DECIMAL(15,2)	jumlah_qty × harga_satuan

Cara kerja rincian item: saat mengisi form transaksi (khususnya pengeluaran usaha, tapi bisa dipakai untuk pengeluaran apa pun), user bisa klik "+ Tambah Item" untuk mencatat beberapa barang/keperluan sekaligus dalam satu transaksi (mis. belanja bahan baku: kopi, gula, gas — masing-masing dengan harga sendiri). Kolom jumlah di tabel transaksi otomatis dijumlahkan dari seluruh subtotal item. Jika tidak perlu rincian (transaksi sederhana), field jumlah bisa diisi manual tanpa item.

Kategori pemasukan contoh: Gaji, Pemasukan Saham, Pemasukan Usaha, Lainnya. Kategori pengeluaran contoh: Makan, Transportasi, Tagihan, Pengeluaran Usaha (bahan baku/operasional/sewa/gaji karyawan/marketing), Hiburan, Lainnya.

Catatan relasi: usaha_id opsional di level database, tapi di form transaksi, field "Pilih Usaha" otomatis muncul/wajib diisi ketika kategori yang dipilih adalah "Pemasukan Usaha" atau "Pengeluaran Usaha", sehingga mendukung banyak usaha sekaligus.

5. Menu Utama
Dashboard — ringkasan saldo bulan berjalan, total pemasukan, total pengeluaran, grafik tren bulanan (Chart.js), plus kartu ringkasan "Gaji Bulan Ini" (jumlah gaji masuk & sisa setelah pengeluaran).
Input Transaksi — form tambah transaksi (tipe, kategori, usaha terkait bila kategori usaha, jumlah/tanggal/keterangan) + opsi "Tambah Item" untuk mencatat rincian pembelian (nama item, qty, harga satuan) yang otomatis menjumlah ke total transaksi. Tabel daftar transaksi dengan aksi lihat rincian/edit/hapus.
Kelola Kategori — tambah/edit/hapus kategori pemasukan dan pengeluaran, termasuk kategori khusus (saham, usaha sendiri, dll).
Usaha Saya — kelola daftar usaha sendiri:
Tambah/edit/hapus usaha (nama usaha, keterangan, status aktif/nonaktif).
Daftar semua usaha (card/tabel) dengan ringkasan: total pemasukan, total pengeluaran, dan laba/rugi bersih (bulan berjalan & total keseluruhan).
Halaman detail per usaha: rincian pengeluaran (daftar semua item pembelian dari transaksi pengeluaran usaha tsb — nama item, qty, harga, subtotal), grafik pie distribusi pengeluaran per item/jenis, dan grafik tren pemasukan vs pengeluaran per bulan.
Laporan Bulanan — filter berdasarkan bulan/tahun, rekap total per kategori, rekap total per usaha (termasuk laba/rugi), grafik perbandingan pemasukan vs pengeluaran, rincian "Gaji vs Pengeluaran" (gaji masuk bulan tsb dibandingkan breakdown pengeluaran per kategori bulan yang sama, dalam tabel & pie chart), opsional export ke Excel/PDF.
6. Alur Aplikasi

Dashboard Utama → (Input Transaksi / Kelola Kategori / Usaha Saya / Laporan Bulanan) → semua data tersimpan di Database MySQL → Dashboard diperbarui otomatis dengan saldo & grafik terbaru.

Alur menu Usaha Saya: Tambah nama usaha di usaha.php → usaha muncul sebagai pilihan di form Input Transaksi saat kategori "Pemasukan Usaha"/"Pengeluaran Usaha" dipilih → untuk pengeluaran, user isi rincian item pembelian (opsional tapi disarankan) → transaksi & item tersimpan dengan usaha_id terkait → usaha.php menghitung otomatis total pemasukan, total pengeluaran (dari SUM subtotal item), dan laba/rugi bersih = pemasukan usaha − pengeluaran usaha.

Alur ringkasan Gaji: User input transaksi pemasukan kategori "Gaji" (jumlah gaji bulan tsb) → sepanjang bulan, transaksi pengeluaran lain (Makan, Transportasi, Tagihan, dll) tercatat seperti biasa → Dashboard/Laporan menjumlahkan total gaji masuk bulan tsb dan menampilkan breakdown ke mana pengeluaran bulan itu terpakai (per kategori, dengan persentase terhadap gaji) → sisa saldo dari gaji juga ditampilkan.

7. Tahapan Pengembangan (Sprint)
Setup awal: install XAMPP/Laragon, buat database keuangan.sql, buat koneksi config/database.php.
Layout dasar: buat header.php, footer.php dengan Bootstrap navbar untuk 5 menu utama.
CRUD Kategori: halaman kategori.php + kategori_action.php.
CRUD Usaha: halaman usaha.php + usaha_action.php — form tambah/edit nama usaha, keterangan, status.
CRUD Transaksi + Rincian Item: halaman transaksi.php + transaksi_action.php, dropdown kategori dinamis, dropdown usaha otomatis muncul untuk kategori usaha, dan form dinamis "+ Tambah Item" (JS) untuk rincian pembelian (nama item, qty, harga satuan, auto-hitung subtotal & total).
Dashboard: query total saldo, pemasukan, pengeluaran bulan berjalan + integrasi Chart.js + kartu ringkasan Gaji Bulan Ini.
Rekap & Rincian per Usaha: query agregasi pemasukan/pengeluaran per usaha_id, hitung laba/rugi bersih, tampilkan rincian item pengeluaran per usaha + grafik pie distribusi item, di halaman usaha.php.
Laporan Bulanan: filter tanggal, query agregasi per kategori & per usaha, tambahkan bagian "Gaji vs Pengeluaran" (tabel breakdown kategori pengeluaran bulan tsb + persentase terhadap gaji), tampilkan tabel + grafik.
Polish UI: styling Bootstrap (card, badge warna untuk pemasukan/pengeluaran/per-usaha), responsive check.
Testing: uji input data & rincian item, validasi form (jumlah/harga harus angka positif, tanggal wajib diisi, nama usaha wajib jika kategori usaha dipilih), cek perhitungan saldo, laba/rugi per usaha, dan breakdown gaji vs pengeluaran.
8. Catatan
Tidak ada sistem login/autentikasi — aplikasi ditujukan untuk penggunaan pribadi di localhost/private server.
Gunakan PDO dengan prepared statement untuk semua query agar aman dari SQL injection.
Format mata uang gunakan Rupiah (Rp) dengan pemisah ribuan.
Fitur "Usaha Saya" mendukung lebih dari satu usaha sekaligus, masing-masing dengan rincian pengeluaran dan laba/rugi terpisah.
Jika usaha dihapus, transaksi terkait sebaiknya tidak ikut terhapus (gunakan ON DELETE SET NULL pada foreign key usaha_id di tabel transaksi, bukan CASCADE), agar riwayat keuangan tetap utuh. Sebaliknya, jika satu transaksi dihapus, rincian item_transaksi miliknya boleh ikut terhapus (ON DELETE CASCADE) karena item tidak berarti tanpa transaksi induknya.
Rincian item pembelian bersifat opsional — cocok dipakai untuk transaksi usaha yang butuh detail (misal belanja bahan baku banyak item), tapi tidak wajib untuk transaksi sederhana sehari-hari.
Ringkasan "Gaji vs Pengeluaran" murni dihitung dari data yang sudah ada (kategori "Gaji" sebagai pemasukan, kategori-kategori pengeluaran lain pada rentang tanggal yang sama) — tidak perlu tabel baru khusus gaji.