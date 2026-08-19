<?php
$page_title = "Transaksi";
$page_header = "Transaksi Keuangan";
require_once __DIR__ . '/../includes/header.php';

// Filter parameters
$bulan = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) && $_GET['tahun'] !== '' ? (int)$_GET['tahun'] : (int)date('Y');
$tipe_filter = sanitize($_GET['tipe'] ?? '');
$kategori_filter = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : 0;
$usaha_filter = isset($_GET['usaha_id']) ? (int)$_GET['usaha_id'] : 0;

// Fetch master data for dropdowns
$kategori_list = get_kategori_list($pdo);
$usaha_list = get_usaha_list($pdo);

// Query transactions
$where_clauses = ["1=1"];
$params = [];

if ($bulan > 0) {
    $where_clauses[] = "MONTH(t.tanggal) = :bulan";
    $params['bulan'] = $bulan;
}
if ($tahun > 0) {
    $where_clauses[] = "YEAR(t.tanggal) = :tahun";
    $params['tahun'] = $tahun;
}
if (!empty($tipe_filter)) {
    $where_clauses[] = "t.tipe = :tipe";
    $params['tipe'] = $tipe_filter;
}
if ($kategori_filter > 0) {
    $where_clauses[] = "t.kategori_id = :kategori_id";
    $params['kategori_id'] = $kategori_filter;
}
if ($usaha_filter > 0) {
    $where_clauses[] = "t.usaha_id = :usaha_id";
    $params['usaha_id'] = $usaha_filter;
}

$where_sql = implode(" AND ", $where_clauses);

$sql = "
    SELECT 
        t.*, 
        k.nama_kategori, 
        u.nama_usaha,
        (SELECT COUNT(*) FROM item_transaksi it WHERE it.transaksi_id = t.id) AS total_items
    FROM transaksi t
    JOIN kategori k ON t.kategori_id = k.id
    LEFT JOIN usaha u ON t.usaha_id = u.id
    WHERE {$where_sql}
    ORDER BY t.tanggal DESC, t.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transaksi_list = $stmt->fetchAll();

// Open add modal automatically if requested via URL action=new
$auto_open_add = isset($_GET['action']) && $_GET['action'] === 'new';
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-on-surface">Daftar Transaksi</h2>
        <p class="text-sm text-on-surface-variant">Catat dan kelola riwayat pemasukan, pengeluaran, serta rincian item belanja.</p>
    </div>
    <button onclick="openAddTransaksiModal()" class="px-4 py-2.5 bg-primary text-on-primary rounded-xl font-semibold text-sm hover:bg-primary-container transition-all shadow flex items-center gap-2">
        <span class="material-symbols-outlined text-lg">add_circle</span> Tambah Transaksi
    </button>
</div>

<!-- Filters Bar -->
<div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant shadow-sm mb-6">
    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
        <div>
            <label class="block text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Bulan</label>
            <select name="bulan" class="w-full px-3 py-2 text-sm border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20">
                <option value="">Semua Bulan</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m; ?>" <?= $bulan === $m ? 'selected' : ''; ?>><?= get_nama_bulan($m); ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Tahun</label>
            <select name="tahun" class="w-full px-3 py-2 text-sm border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y; ?>" <?= $tahun === $y ? 'selected' : ''; ?>><?= $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Tipe</label>
            <select name="tipe" class="w-full px-3 py-2 text-sm border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20">
                <option value="">Semua Tipe</option>
                <option value="pemasukan" <?= $tipe_filter === 'pemasukan' ? 'selected' : ''; ?>>Pemasukan (+)</option>
                <option value="pengeluaran" <?= $tipe_filter === 'pengeluaran' ? 'selected' : ''; ?>>Pengeluaran (-)</option>
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-semibold text-on-surface-variant uppercase tracking-wider mb-1">Kategori</label>
            <select name="kategori_id" class="w-full px-3 py-2 text-sm border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20">
                <option value="">Semua Kategori</option>
                <?php foreach ($kategori_list as $k): ?>
                    <option value="<?= $k['id']; ?>" <?= $kategori_filter === $k['id'] ? 'selected' : ''; ?>><?= htmlspecialchars($k['nama_kategori']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="w-full py-2 px-4 bg-surface-container-high text-on-surface hover:bg-surface-container-highest rounded-xl font-semibold text-sm transition-colors flex items-center justify-center gap-1">
                <span class="material-symbols-outlined text-lg">filter_alt</span> Filter
            </button>
            <a href="transaksi.php" class="py-2 px-3 bg-surface-container text-on-surface-variant hover:bg-surface-container-high rounded-xl text-sm" title="Reset Filter">
                <span class="material-symbols-outlined text-lg">refresh</span>
            </a>
        </div>
    </form>
</div>

<!-- Transactions Data Table -->
<div class="bg-surface-container-lowest rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-container text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant">
                <tr>
                    <th class="px-4 py-3.5">Tanggal</th>
                    <th class="px-4 py-3.5">Tipe</th>
                    <th class="px-4 py-3.5">Kategori</th>
                    <th class="px-4 py-3.5">Usaha</th>
                    <th class="px-4 py-3.5">Keterangan</th>
                    <th class="px-4 py-3.5 text-right">Jumlah</th>
                    <th class="px-4 py-3.5 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/60">
                <?php if (empty($transaksi_list)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-on-surface-variant italic">Tidak ada transaksi ditemukan.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transaksi_list as $t): ?>
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-3 font-medium text-xs text-on-surface-variant whitespace-nowrap">
                                <?= date('d/m/Y', strtotime($t['tanggal'])); ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <?php if ($t['tipe'] === 'pemasukan'): ?>
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 flex items-center w-max gap-1">
                                        <span class="material-symbols-outlined text-xs">arrow_downward</span> Pemasukan
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-800 flex items-center w-max gap-1">
                                        <span class="material-symbols-outlined text-xs">arrow_upward</span> Pengeluaran
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 font-medium text-on-surface">
                                <?= htmlspecialchars($t['nama_kategori']); ?>
                            </td>
                            <td class="px-4 py-3 text-xs text-on-surface-variant">
                                <?= $t['nama_usaha'] ? htmlspecialchars($t['nama_usaha']) : '-'; ?>
                            </td>
                            <td class="px-4 py-3 text-on-surface">
                                <?= htmlspecialchars($t['keterangan'] ?: '-'); ?>
                                <?php if ($t['total_items'] > 0): ?>
                                    <button onclick="fetchAndShowItemDetails(<?= $t['id']; ?>)" class="inline-flex items-center gap-1 text-[11px] font-semibold text-tertiary hover:underline ml-2">
                                        <span class="material-symbols-outlined text-xs">receipt</span> <?= $t['total_items']; ?> Item
                                    </button>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold <?= $t['tipe'] === 'pemasukan' ? 'text-primary' : 'text-secondary'; ?> whitespace-nowrap">
                                <?= ($t['tipe'] === 'pemasukan' ? '+' : '-') . ' ' . format_rupiah($t['jumlah']); ?>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1">
                                    <?php if ($t['total_items'] > 0): ?>
                                        <button onclick="fetchAndShowItemDetails(<?= $t['id']; ?>)" class="p-1 text-on-surface-variant hover:text-tertiary rounded-lg hover:bg-surface-container" title="Lihat Rincian Item">
                                            <span class="material-symbols-outlined text-lg">visibility</span>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick='editTransaksi(<?= json_encode($t); ?>)' class="p-1 text-on-surface-variant hover:text-tertiary rounded-lg hover:bg-surface-container" title="Edit">
                                        <span class="material-symbols-outlined text-lg">edit</span>
                                    </button>
                                    <a href="../actions/transaksi_action.php?action=delete&id=<?= $t['id']; ?>" onclick="return confirm('Yakin menghapus transaksi ini?')" class="p-1 text-on-surface-variant hover:text-secondary rounded-lg hover:bg-surface-container" title="Hapus">
                                        <span class="material-symbols-outlined text-lg">delete</span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add/Edit Transaksi -->
<div id="transaksiModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-2xl rounded-2xl shadow-xl overflow-hidden animate-scale-up max-h-[90vh] flex flex-col">
        <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between bg-surface-container-low shrink-0">
            <h3 id="modalTransaksiTitle" class="font-bold text-lg text-on-surface">Tambah Transaksi</h3>
            <button onclick="closeModal('transaksiModal')" class="text-on-surface-variant p-1 rounded-lg hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form action="../actions/transaksi_action.php" method="POST" class="p-6 space-y-4 overflow-y-auto flex-1">
            <input type="hidden" name="action" id="transaksiAction" value="create">
            <input type="hidden" name="id" id="transaksiId" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="tipe" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Tipe Transaksi</label>
                    <select name="tipe" id="tipe" required onchange="filterKategoriByTipe()"
                        class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="pemasukan">Pemasukan (+)</option>
                        <option value="pengeluaran" selected>Pengeluaran (-)</option>
                    </select>
                </div>

                <div>
                    <label for="kategori_id" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Kategori</label>
                    <select name="kategori_id" id="kategori_id" required onchange="checkCategoryType()"
                        class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($kategori_list as $k): ?>
                            <option value="<?= $k['id']; ?>" data-tipe="<?= $k['tipe']; ?>" data-nama="<?= htmlspecialchars($k['nama_kategori']); ?>">
                                <?= htmlspecialchars($k['nama_kategori']); ?> (<?= ucfirst($k['tipe']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div id="usaha_container" class="hidden">
                <label for="usaha_id" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Terkait Usaha (Opsional)</label>
                <select name="usaha_id" id="usaha_id"
                    class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="">-- Tidak Terkait Usaha --</option>
                    <?php foreach ($usaha_list as $u): ?>
                        <option value="<?= $u['id']; ?>"><?= htmlspecialchars($u['nama_usaha']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="tanggal" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Tanggal Transaksi</label>
                    <input type="date" name="tanggal" id="tanggal" required value="<?= date('Y-m-d'); ?>"
                        class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>

                <div>
                    <label for="jumlah" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Total Jumlah Nominal (Rp)</label>
                    <input type="number" step="1" name="jumlah" id="jumlah" min="0" required placeholder="0"
                        class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm font-bold text-primary focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
            </div>

            <div>
                <label for="keterangan" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Keterangan Catatan</label>
                <input type="text" name="keterangan" id="keterangan" placeholder="Catatan transaksi..."
                    class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <!-- Rincian Item Pembelian Dynamic Form Section -->
            <div class="pt-4 border-t border-outline-variant space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-sm text-on-surface flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-primary text-base">shopping_bag</span> Rincian Item Pembelian (Opsional)
                        </h4>
                        <p class="text-[11px] text-on-surface-variant">Catat rincian barang/keperluan. Total otomatis dihitung dari item jika diisi.</p>
                    </div>
                    <button type="button" onclick="addItemRow()" class="px-3 py-1.5 bg-surface-container-high text-primary hover:bg-primary/10 rounded-xl font-semibold text-xs transition-colors flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">add</span> Tambah Item
                    </button>
                </div>

                <div id="itemRowsContainer" class="space-y-2">
                    <!-- Dynamic rows will be inserted here -->
                </div>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-outline-variant shrink-0">
                <button type="button" onclick="closeModal('transaksiModal')" class="px-4 py-2.5 rounded-xl border border-outline-variant text-sm font-semibold text-on-surface-variant hover:bg-surface-container">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary font-semibold text-sm hover:bg-primary-container shadow">
                    Simpan Transaksi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Item Pembelian -->
<div id="itemDetailModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-lg rounded-2xl shadow-xl overflow-hidden animate-scale-up">
        <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between bg-surface-container-low">
            <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">receipt_long</span> Rincian Item Pembelian
            </h3>
            <button onclick="closeModal('itemDetailModal')" class="text-on-surface-variant p-1 rounded-lg hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="itemDetailContent" class="p-6">
            <!-- Dynamic item breakdown loaded via AJAX or JS -->
            <p class="text-center text-sm text-on-surface-variant">Memuat data item...</p>
        </div>
    </div>
</div>

<script>
function filterKategoriByTipe() {
    const tipe = document.getElementById('tipe').value;
    const kategoriSelect = document.getElementById('kategori_id');
    const options = kategoriSelect.options;

    for (let i = 0; i < options.length; i++) {
        const optionTipe = options[i].getAttribute('data-tipe');
        if (!optionTipe) continue;

        if (optionTipe === tipe) {
            options[i].style.display = '';
        } else {
            options[i].style.display = 'none';
        }
    }
}

function openAddTransaksiModal() {
    document.getElementById('modalTransaksiTitle').innerText = 'Tambah Transaksi Baru';
    document.getElementById('transaksiAction').value = 'create';
    document.getElementById('transaksiId').value = '';
    document.getElementById('tipe').value = 'pengeluaran';
    document.getElementById('kategori_id').value = '';
    document.getElementById('usaha_id').value = '';
    document.getElementById('tanggal').value = '<?= date('Y-m-d'); ?>';
    document.getElementById('jumlah').value = '';
    document.getElementById('keterangan').value = '';
    document.getElementById('itemRowsContainer').innerHTML = '';
    filterKategoriByTipe();
    checkCategoryType();
    openModal('transaksiModal');
}

function editTransaksi(data) {
    document.getElementById('modalTransaksiTitle').innerText = 'Edit Transaksi';
    document.getElementById('transaksiAction').value = 'update';
    document.getElementById('transaksiId').value = data.id;
    document.getElementById('tipe').value = data.tipe;
    document.getElementById('kategori_id').value = data.kategori_id;
    document.getElementById('usaha_id').value = data.usaha_id || '';
    document.getElementById('tanggal').value = data.tanggal;
    document.getElementById('jumlah').value = data.jumlah;
    document.getElementById('keterangan').value = data.keterangan || '';
    document.getElementById('itemRowsContainer').innerHTML = '';
    filterKategoriByTipe();
    checkCategoryType();
    
    // Fetch items via AJAX if exists
    fetch(`../actions/get_items.php?transaksi_id=${data.id}`)
        .then(res => res.json())
        .then(items => {
            if (Array.isArray(items) && items.length > 0) {
                items.forEach(item => {
                    const rowId = Date.now() + Math.random();
                    const container = document.getElementById('itemRowsContainer');
                    const rowHTML = `
                        <div class="item-row grid grid-cols-12 gap-2 items-center bg-surface-container-low p-2 rounded-xl border border-outline-variant" id="row_${rowId}">
                            <div class="col-span-5">
                                <input type="text" name="items[${rowId}][nama_item]" value="${item.nama_item}" placeholder="Nama item" required class="w-full px-3 py-2 text-sm bg-white border border-outline-variant rounded-lg">
                            </div>
                            <div class="col-span-2">
                                <input type="number" step="0.01" min="0.1" name="items[${rowId}][jumlah_qty]" value="${item.jumlah_qty}" oninput="calculateRowSubtotal('${rowId}')" required class="w-full px-2 py-2 text-sm bg-white border border-outline-variant rounded-lg text-center">
                            </div>
                            <div class="col-span-4">
                                <input type="number" step="1" min="0" name="items[${rowId}][harga_satuan]" value="${item.harga_satuan}" oninput="calculateRowSubtotal('${rowId}')" required class="w-full px-3 py-2 text-sm bg-white border border-outline-variant rounded-lg text-right">
                            </div>
                            <div class="col-span-1 text-center">
                                <button type="button" onclick="removeItemRow('${rowId}')" class="p-1 text-secondary hover:bg-secondary-container/20 rounded-lg">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </button>
                            </div>
                            <div class="col-span-12 flex justify-between px-2 pt-1 text-xs text-on-surface-variant font-medium">
                                <span>Subtotal:</span>
                                <span id="subtotal_display_${rowId}" class="font-semibold text-primary">Rp ${(item.subtotal).toLocaleString('id-ID')}</span>
                            </div>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', rowHTML);
                });
            }
            openModal('transaksiModal');
        })
        .catch(() => openModal('transaksiModal'));
}

function fetchAndShowItemDetails(transaksiId) {
    const content = document.getElementById('itemDetailContent');
    content.innerHTML = '<p class="text-center text-sm text-on-surface-variant">Memuat data item...</p>';
    openModal('itemDetailModal');

    fetch(`../actions/get_items.php?transaksi_id=${transaksiId}`)
        .then(res => res.json())
        .then(items => {
            if (!Array.isArray(items) || items.length === 0) {
                content.innerHTML = '<p class="text-center text-sm text-on-surface-variant">Tidak ada rincian item.</p>';
                return;
            }

            let html = `
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-surface-container text-xs font-semibold text-on-surface-variant uppercase border-b border-outline-variant">
                            <tr>
                                <th class="px-3 py-2">Nama Item</th>
                                <th class="px-3 py-2 text-center">Qty</th>
                                <th class="px-3 py-2 text-right">Harga</th>
                                <th class="px-3 py-2 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant/60">
            `;

            let grandTotal = 0;
            items.forEach(it => {
                const sub = parseFloat(it.subtotal);
                grandTotal += sub;
                html += `
                    <tr>
                        <td class="px-3 py-2.5 font-medium text-on-surface">${it.nama_item}</td>
                        <td class="px-3 py-2.5 text-center">${parseFloat(it.jumlah_qty)}</td>
                        <td class="px-3 py-2.5 text-right text-on-surface-variant">Rp ${parseFloat(it.harga_satuan).toLocaleString('id-ID')}</td>
                        <td class="px-3 py-2.5 text-right font-semibold text-primary">Rp ${sub.toLocaleString('id-ID')}</td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                        <tfoot class="border-t border-outline-variant font-bold text-sm bg-surface-container-low">
                            <tr>
                                <td colspan="3" class="px-3 py-2.5 text-right">Total Transaksi:</td>
                                <td class="px-3 py-2.5 text-right text-primary">Rp ${grandTotal.toLocaleString('id-ID')}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            `;
            content.innerHTML = html;
        })
        .catch(err => {
            content.innerHTML = '<p class="text-center text-sm text-secondary">Gagal memuat rincian item.</p>';
        });
}

<?php if ($auto_open_add): ?>
    document.addEventListener('DOMContentLoaded', openAddTransaksiModal);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
