<?php
$page_title = "Usaha Saya";
$page_header = "Usaha Saya";
require_once __DIR__ . '/../includes/header.php';

$selected_usaha_id = isset($_GET['detail']) ? (int)$_GET['detail'] : 0;
$usaha_list = get_usaha_summary($pdo);

// If detail view requested
$detail_usaha = null;
$item_pengeluaran = [];
if ($selected_usaha_id > 0) {
    $detail_usaha = get_usaha_summary($pdo, $selected_usaha_id);
    if ($detail_usaha) {
        $item_pengeluaran = get_item_pengeluaran_usaha($pdo, $selected_usaha_id);
    }
}
?>

<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-on-surface">Manajemen Usaha Saya</h2>
        <p class="text-sm text-on-surface-variant">Kelola daftar usaha mandiri, rincian item pengeluaran, dan perhitungan laba/rugi bersih.</p>
    </div>
    <div class="flex gap-2">
        <?php if ($selected_usaha_id > 0): ?>
            <a href="usaha.php" class="px-4 py-2.5 bg-surface-container-high text-on-surface rounded-xl font-semibold text-sm hover:bg-surface-container-highest transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">arrow_back</span> Kembali ke Daftar Usaha
            </a>
        <?php endif; ?>
        <button onclick="openAddUsahaModal()" class="px-4 py-2.5 bg-primary text-on-primary rounded-xl font-semibold text-sm hover:bg-primary-container transition-all shadow flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">add_business</span> Tambah Usaha
        </button>
    </div>
</div>

<?php if ($selected_usaha_id > 0 && $detail_usaha): ?>
    <!-- ================= DETAIL VIEW PER USAHA ================= -->
    <div class="space-y-6">
        <!-- Banner Summary Card -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-outline-variant pb-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary bg-primary-container/20 p-3 rounded-2xl text-2xl">storefront</span>
                    <div>
                        <h3 class="font-bold text-xl text-on-surface"><?= htmlspecialchars($detail_usaha['nama_usaha']); ?></h3>
                        <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($detail_usaha['keterangan'] ?: 'Tidak ada deskripsi'); ?></p>
                    </div>
                </div>
                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $detail_usaha['status'] === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700'; ?>">
                    <?= ucfirst($detail_usaha['status']); ?>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                    <p class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Total Pemasukan Usaha</p>
                    <p class="text-xl font-bold text-emerald-900 mt-1"><?= format_rupiah($detail_usaha['total_pemasukan']); ?></p>
                </div>
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-100">
                    <p class="text-xs font-semibold text-rose-700 uppercase tracking-wider">Total Pengeluaran Usaha</p>
                    <p class="text-xl font-bold text-rose-900 mt-1"><?= format_rupiah($detail_usaha['total_pengeluaran']); ?></p>
                </div>
                <div class="p-4 rounded-xl <?= $detail_usaha['laba_bersih'] >= 0 ? 'bg-blue-50 border border-blue-100' : 'bg-amber-50 border border-amber-100'; ?>">
                    <p class="text-xs font-semibold <?= $detail_usaha['laba_bersih'] >= 0 ? 'text-blue-700' : 'text-amber-700'; ?> uppercase tracking-wider">Laba / Rugi Bersih</p>
                    <p class="text-xl font-bold <?= $detail_usaha['laba_bersih'] >= 0 ? 'text-blue-900' : 'text-amber-900'; ?> mt-1">
                        <?= format_rupiah($detail_usaha['laba_bersih']); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Rincian Item Pengeluaran Table -->
        <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                        <span class="material-symbols-outlined text-secondary">shopping_bag</span> Rincian Item Pembelian & Operasional
                    </h3>
                    <p class="text-xs text-on-surface-variant">Daftar seluruh item yang dibeli/dikeluarkan untuk usaha ini.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-surface-container text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Nama Item / Keperluan</th>
                            <th class="px-4 py-3 text-center">Qty</th>
                            <th class="px-4 py-3 text-right">Harga Satuan</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/60">
                        <?php if (empty($item_pengeluaran)): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-on-surface-variant italic">Belum ada rincian item pengeluaran untuk usaha ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($item_pengeluaran as $item): ?>
                                <tr class="hover:bg-surface-container-low transition-colors">
                                    <td class="px-4 py-3 font-medium text-xs text-on-surface-variant whitespace-nowrap"><?= date('d/m/Y', strtotime($item['tanggal'])); ?></td>
                                    <td class="px-4 py-3 font-semibold text-on-surface">
                                        <?= htmlspecialchars($item['nama_item']); ?>
                                        <?php if ($item['ket_transaksi']): ?>
                                            <span class="block text-xs font-normal text-on-surface-variant"><?= htmlspecialchars($item['ket_transaksi']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center font-medium"><?= (float)$item['jumlah_qty']; ?></td>
                                    <td class="px-4 py-3 text-right text-on-surface-variant"><?= format_rupiah($item['harga_satuan']); ?></td>
                                    <td class="px-4 py-3 text-right font-bold text-secondary"><?= format_rupiah($item['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ================= MAIN BUSINESS LIST VIEW ================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($usaha_list)): ?>
            <div class="col-span-full bg-surface-container-lowest p-8 rounded-2xl border border-outline-variant text-center space-y-3">
                <span class="material-symbols-outlined text-4xl text-on-surface-variant">storefront</span>
                <p class="font-medium text-on-surface">Belum ada usaha terdaftar.</p>
                <button onclick="openAddUsahaModal()" class="px-4 py-2 bg-primary text-on-primary text-sm font-semibold rounded-xl inline-flex items-center gap-2">
                    <span class="material-symbols-outlined">add</span> Tambah Usaha Pertama
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($usaha_list as $u): ?>
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-primary bg-primary-container/20 p-2.5 rounded-xl">storefront</span>
                                <div>
                                    <h3 class="font-bold text-base text-on-surface"><?= htmlspecialchars($u['nama_usaha']); ?></h3>
                                    <span class="px-2.5 py-0.5 text-[11px] font-semibold rounded-full <?= $u['status'] === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700'; ?>">
                                        <?= ucfirst($u['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <button onclick='openEditUsahaModal(<?= json_encode($u); ?>)' class="p-1 text-on-surface-variant hover:text-tertiary rounded-lg hover:bg-surface-container" title="Edit Usaha">
                                    <span class="material-symbols-outlined text-lg">edit</span>
                                </button>
                                <a href="../actions/usaha_action.php?action=delete&id=<?= $u['id']; ?>" onclick="return confirm('Yakin menghapus usaha ini? Transaksi tidak akan terhapus.')" class="p-1 text-on-surface-variant hover:text-secondary rounded-lg hover:bg-surface-container" title="Hapus Usaha">
                                    <span class="material-symbols-outlined text-lg">delete</span>
                                </a>
                            </div>
                        </div>

                        <p class="text-xs text-on-surface-variant line-clamp-2"><?= htmlspecialchars($u['keterangan'] ?: 'Tidak ada keterangan'); ?></p>

                        <div class="pt-3 border-t border-outline-variant/60 space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-on-surface-variant">Total Pemasukan:</span>
                                <span class="font-semibold text-emerald-700"><?= format_rupiah($u['total_pemasukan']); ?></span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-on-surface-variant">Total Pengeluaran:</span>
                                <span class="font-semibold text-rose-700"><?= format_rupiah($u['total_pengeluaran']); ?></span>
                            </div>
                            <div class="flex justify-between text-sm pt-2 border-t border-dashed border-outline-variant">
                                <span class="font-semibold text-on-surface">Laba / Rugi Bersih:</span>
                                <span class="font-bold <?= $u['laba_bersih'] >= 0 ? 'text-primary' : 'text-secondary'; ?>">
                                    <?= format_rupiah($u['laba_bersih']); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <a href="usaha.php?detail=<?= $u['id']; ?>" class="w-full py-2.5 px-4 bg-surface-container-high text-on-surface hover:bg-primary hover:text-on-primary rounded-xl font-semibold text-xs transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base">analytics</span> Lihat Detail & Rincian Item
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Modal Add/Edit Usaha -->
<div id="usahaModal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-surface-container-lowest w-full max-w-md rounded-2xl shadow-xl overflow-hidden animate-scale-up">
        <div class="px-6 py-4 border-b border-outline-variant flex items-center justify-between bg-surface-container-low">
            <h3 id="modalUsahaTitle" class="font-bold text-lg text-on-surface">Tambah Usaha Baru</h3>
            <button onclick="closeModal('usahaModal')" class="text-on-surface-variant p-1 rounded-lg hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form action="../actions/usaha_action.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" id="usahaAction" value="create">
            <input type="hidden" name="id" id="usahaId" value="">

            <div>
                <label for="nama_usaha" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Nama Usaha</label>
                <input type="text" name="nama_usaha" id="nama_usaha" required placeholder="misal: Warung Kopi Berkah, Toko Online ABC"
                    class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
            </div>

            <div>
                <label for="keterangan" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Keterangan / Deskripsi</label>
                <textarea name="keterangan" id="keterangan" rows="3" placeholder="Deskripsi singkat usaha..."
                    class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
            </div>

            <div>
                <label for="status" class="block text-xs font-semibold text-on-surface-variant uppercase tracking-wider mb-2">Status Usaha</label>
                <select name="status" id="status" required
                    class="w-full px-4 py-2.5 rounded-xl border border-outline-variant text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </div>

            <div class="pt-4 flex justify-end gap-3 border-t border-outline-variant">
                <button type="button" onclick="closeModal('usahaModal')" class="px-4 py-2.5 rounded-xl border border-outline-variant text-sm font-semibold text-on-surface-variant hover:bg-surface-container">
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
function openAddUsahaModal() {
    document.getElementById('modalUsahaTitle').innerText = 'Tambah Usaha Baru';
    document.getElementById('usahaAction').value = 'create';
    document.getElementById('usahaId').value = '';
    document.getElementById('nama_usaha').value = '';
    document.getElementById('keterangan').value = '';
    document.getElementById('status').value = 'aktif';
    openModal('usahaModal');
}

function openEditUsahaModal(data) {
    document.getElementById('modalUsahaTitle').innerText = 'Edit Usaha';
    document.getElementById('usahaAction').value = 'update';
    document.getElementById('usahaId').value = data.id;
    document.getElementById('nama_usaha').value = data.nama_usaha;
    document.getElementById('keterangan').value = data.keterangan || '';
    document.getElementById('status').value = data.status;
    openModal('usahaModal');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
