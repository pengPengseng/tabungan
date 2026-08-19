<?php
$page_title = "Kelola Kategori";
$page_header = "Kelola Kategori";
require_once __DIR__ . '/../includes/header.php';

// Fetch all categories
$kategori_list = get_kategori_list($pdo);

// Group categories by type
$pemasukan_list = array_filter($kategori_list, fn($k) => $k['tipe'] === 'pemasukan');
$pengeluaran_list = array_filter($kategori_list, fn($k) => $k['tipe'] === 'pengeluaran');
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-on-surface">Kategori Keuangan</h2>
        <p class="text-sm text-on-surface-variant">Atur kategori pemasukan dan pengeluaran untuk klasifikasi transaksi yang rapi.</p>
    </div>
    <button onclick="openAddKategoriModal()" class="px-4 py-2.5 bg-primary text-on-primary rounded-xl font-semibold text-sm hover:bg-primary-container transition-all shadow flex items-center gap-2">
        <span class="material-symbols-outlined text-lg">add</span> Tambah Kategori
    </button>
</div>

<!-- Categories Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Pemasukan Card -->
    <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-outline-variant pb-4">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-primary bg-primary-container/20 p-2.5 rounded-xl">arrow_downward</span>
                <div>
                    <h3 class="font-bold text-base text-on-surface">Kategori Pemasukan</h3>
                    <p class="text-xs text-on-surface-variant"><?= count($pemasukan_list); ?> Kategori Terdaftar</p>
                </div>
            </div>
            <span class="px-3 py-1 bg-emerald-100 text-emerald-800 text-xs font-semibold rounded-full">Pemasukan</span>
        </div>

        <div class="divide-y divide-outline-variant/60">
            <?php if (empty($pemasukan_list)): ?>
                <p class="py-4 text-center text-sm text-on-surface-variant italic">Belum ada kategori pemasukan.</p>
            <?php else: ?>
                <?php foreach ($pemasukan_list as $kat): ?>
                    <div class="py-3 flex items-center justify-between hover:bg-surface-container-low px-3 rounded-xl transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary text-sm">label</span>
                            <span class="font-medium text-sm text-on-surface"><?= htmlspecialchars($kat['nama_kategori']); ?></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button onclick='openEditKategoriModal(<?= json_encode($kat); ?>)' class="p-1.5 text-on-surface-variant hover:text-tertiary rounded-lg hover:bg-surface-container-high transition-colors" title="Edit">
                                <span class="material-symbols-outlined text-lg">edit</span>
                            </button>
                            <a href="../actions/kategori_action.php?action=delete&id=<?= $kat['id']; ?>" onclick="return confirm('Yakin ingin menghapus kategori ini?')" class="p-1.5 text-on-surface-variant hover:text-secondary rounded-lg hover:bg-surface-container-high transition-colors" title="Hapus">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pengeluaran Card -->
    <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-outline-variant pb-4">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-secondary bg-secondary-container/20 p-2.5 rounded-xl">arrow_upward</span>
                <div>
                    <h3 class="font-bold text-base text-on-surface">Kategori Pengeluaran</h3>
                    <p class="text-xs text-on-surface-variant"><?= count($pengeluaran_list); ?> Kategori Terdaftar</p>
                </div>
            </div>
            <span class="px-3 py-1 bg-rose-100 text-rose-800 text-xs font-semibold rounded-full">Pengeluaran</span>
        </div>

        <div class="divide-y divide-outline-variant/60">
            <?php if (empty($pengeluaran_list)): ?>
                <p class="py-4 text-center text-sm text-on-surface-variant italic">Belum ada kategori pengeluaran.</p>
            <?php else: ?>
                <?php foreach ($pengeluaran_list as $kat): ?>
                    <div class="py-3 flex items-center justify-between hover:bg-surface-container-low px-3 rounded-xl transition-colors">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-secondary text-sm">label</span>
                            <span class="font-medium text-sm text-on-surface"><?= htmlspecialchars($kat['nama_kategori']); ?></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button onclick='openEditKategoriModal(<?= json_encode($kat); ?>)' class="p-1.5 text-on-surface-variant hover:text-tertiary rounded-lg hover:bg-surface-container-high transition-colors" title="Edit">
                                <span class="material-symbols-outlined text-lg">edit</span>
                            </button>
                            <a href="../actions/kategori_action.php?action=delete&id=<?= $kat['id']; ?>" onclick="return confirm('Yakin ingin menghapus kategori ini?')" class="p-1.5 text-on-surface-variant hover:text-secondary rounded-lg hover:bg-surface-container-high transition-colors" title="Hapus">
                                <span class="material-symbols-outlined text-lg">delete</span>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Form Add/Edit Kategori -->
<div id="kategoriModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-md rounded-2xl shadow-xl overflow-hidden animate-scale-up">
        <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between bg-surface-container-low">
            <h3 id="modalTitle" class="font-bold text-lg text-on-surface">Tambah Kategori Baru</h3>
            <button onclick="closeModal('kategoriModal')" class="text-on-surface-variant p-1 rounded-lg hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form action="../actions/kategori_action.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" id="kategoriAction" value="create">
            <input type="hidden" name="id" id="kategoriId" value="">

            <div>
                <label for="nama_kategori" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" name="nama_kategori" id="nama_kategori" required placeholder="misal: Investasi, Makan, Transportasi"
                    class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <div>
                <label for="tipe" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Tipe Kategori</label>
                <select name="tipe" id="tipe" required
                    class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="pemasukan">Pemasukan (+)</option>
                    <option value="pengeluaran">Pengeluaran (-)</option>
                </select>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-outline-variant">
                <button type="button" onclick="closeModal('kategoriModal')" class="px-4 py-2.5 rounded-xl border border-outline-variant text-sm font-semibold text-on-surface-variant hover:bg-surface-container">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary font-semibold text-sm hover:bg-primary-container shadow">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddKategoriModal() {
    document.getElementById('modalTitle').innerText = 'Tambah Kategori Baru';
    document.getElementById('kategoriAction').value = 'create';
    document.getElementById('kategoriId').value = '';
    document.getElementById('nama_kategori').value = '';
    document.getElementById('tipe').value = 'pemasukan';
    openModal('kategoriModal');
}

function openEditKategoriModal(data) {
    document.getElementById('modalTitle').innerText = 'Edit Kategori';
    document.getElementById('kategoriAction').value = 'update';
    document.getElementById('kategoriId').value = data.id;
    document.getElementById('nama_kategori').value = data.nama_kategori;
    document.getElementById('tipe').value = data.tipe;
    openModal('kategoriModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
