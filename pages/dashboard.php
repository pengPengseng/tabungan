<?php
$page_title = "Dashboard";
$page_header = "Dashboard Keuangan";
require_once __DIR__ . '/../includes/header.php';

$bulan_ini = (int)date('m');
$tahun_ini = (int)date('Y');

// Summaries
$summary = get_dashboard_summary($pdo, $bulan_ini, $tahun_ini);
$gaji_summary = get_gaji_summary($pdo, $bulan_ini, $tahun_ini);

// Recent Transactions
$stmtRecent = $pdo->prepare("
    SELECT t.*, k.nama_kategori, u.nama_usaha,
           (SELECT COUNT(*) FROM item_transaksi it WHERE it.transaksi_id = t.id) as total_items
    FROM transaksi t
    JOIN kategori k ON t.kategori_id = k.id
    LEFT JOIN usaha u ON t.usaha_id = u.id
    ORDER BY t.tanggal DESC, t.id DESC
    LIMIT 6
");
$stmtRecent->execute();
$recent_transactions = $stmtRecent->fetchAll();

// Monthly Trend Data for Chart.js (Current Year)
$chart_labels = [];
$chart_pemasukan = [];
$chart_pengeluaran = [];

for ($m = 1; $m <= 12; $m++) {
    $chart_labels[] = get_nama_bulan($m);

    $stmtIn = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM transaksi WHERE tipe = 'pemasukan' AND MONTH(tanggal) = :m AND YEAR(tanggal) = :y");
    $stmtIn->execute(['m' => $m, 'y' => $tahun_ini]);
    $chart_pemasukan[] = (float)$stmtIn->fetchColumn();

    $stmtOut = $pdo->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM transaksi WHERE tipe = 'pengeluaran' AND MONTH(tanggal) = :m AND YEAR(tanggal) = :y");
    $stmtOut->execute(['m' => $m, 'y' => $tahun_ini]);
    $chart_pengeluaran[] = (float)$stmtOut->fetchColumn();
}

// Expense Category Distribution Data
$pie_labels = [];
$pie_data = [];
foreach ($gaji_summary['breakdown'] as $b) {
    $pie_labels[] = $b['nama_kategori'];
    $pie_data[] = (float)$b['total_pengeluaran'];
}
?>

<!-- 1. Summary Cards -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Total Saldo -->
    <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-outline-variant hover:shadow-md transition-all flex flex-col justify-between">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="font-semibold text-sm text-on-surface-variant">Total Saldo (Akumulasi)</h3>
                <p class="text-xs text-on-surface-variant">Seluruh Kas & Rekening</p>
            </div>
            <span class="material-symbols-outlined text-primary bg-primary-container/20 p-2.5 rounded-xl">account_balance_wallet</span>
        </div>
        <div class="font-bold text-2xl sm:text-3xl text-on-surface mb-2 tracking-tight">
            <?= format_rupiah($summary['total_saldo']); ?>
        </div>
        <div class="flex items-center gap-1 text-xs font-semibold text-primary">
            <span class="material-symbols-outlined text-base">verified</span>
            <span>Update Realtime Data</span>
        </div>
    </div>

    <!-- Pemasukan Bulan Ini -->
    <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-outline-variant border-l-4 border-l-primary hover:shadow-md transition-all flex flex-col justify-between">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="font-semibold text-sm text-on-surface-variant">Pemasukan Bulan Ini</h3>
                <p class="text-xs text-on-surface-variant"><?= get_nama_bulan($bulan_ini) . ' ' . $tahun_ini; ?></p>
            </div>
            <span class="material-symbols-outlined text-primary bg-primary-container/20 p-2.5 rounded-xl">arrow_downward</span>
        </div>
        <div class="font-bold text-2xl sm:text-3xl text-on-surface mb-2 tracking-tight">
            <?= format_rupiah($summary['pemasukan_bulan_ini']); ?>
        </div>
        <div class="flex items-center gap-1 text-xs font-semibold text-emerald-700">
            <span class="material-symbols-outlined text-base">trending_up</span>
            <span>Kas Masuk Periode Ini</span>
        </div>
    </div>

    <!-- Pengeluaran Bulan Ini -->
    <div class="bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-outline-variant border-l-4 border-l-secondary hover:shadow-md transition-all flex flex-col justify-between">
        <div class="flex justify-between items-start mb-4">
            <div>
                <h3 class="font-semibold text-sm text-on-surface-variant">Pengeluaran Bulan Ini</h3>
                <p class="text-xs text-on-surface-variant"><?= get_nama_bulan($bulan_ini) . ' ' . $tahun_ini; ?></p>
            </div>
            <span class="material-symbols-outlined text-secondary bg-secondary-container/20 p-2.5 rounded-xl">arrow_upward</span>
        </div>
        <div class="font-bold text-2xl sm:text-3xl text-on-surface mb-2 tracking-tight">
            <?= format_rupiah($summary['pengeluaran_bulan_ini']); ?>
        </div>
        <div class="flex items-center gap-1 text-xs font-semibold text-rose-700">
            <span class="material-symbols-outlined text-base">trending_down</span>
            <span>Total Pengeluaran Periode Ini</span>
        </div>
    </div>
</section>

<!-- 2. Bento Grid Section: Salary Card & Charts -->
<section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Ringkasan Gaji Card -->
    <div class="lg:col-span-1 bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-outline-variant flex flex-col justify-between space-y-6">
        <div class="flex items-center justify-between border-b border-outline-variant pb-4">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary bg-primary-container/20 p-2 rounded-xl">payments</span>
                <div>
                    <h3 class="font-bold text-base text-on-surface">Ringkasan Gaji Bulan Ini</h3>
                    <p class="text-xs text-on-surface-variant"><?= get_nama_bulan($bulan_ini); ?></p>
                </div>
            </div>
            <span class="px-2.5 py-1 text-[11px] font-semibold bg-emerald-100 text-emerald-800 rounded-full">
                <?= $gaji_summary['persen_terpakai']; ?>% Terpakai
            </span>
        </div>

        <div class="space-y-4">
            <div class="flex justify-between items-center text-sm">
                <span class="text-on-surface-variant">Total Gaji Masuk:</span>
                <span class="font-bold text-primary"><?= format_rupiah($gaji_summary['total_gaji']); ?></span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-on-surface-variant">Total Pengeluaran:</span>
                <span class="font-bold text-secondary"><?= format_rupiah($gaji_summary['total_pengeluaran_bulan']); ?></span>
            </div>

            <!-- Progress Bar -->
            <div class="space-y-1.5 pt-2">
                <div class="flex justify-between text-xs font-semibold text-on-surface">
                    <span>Sisa Alokasi Gaji</span>
                    <span><?= format_rupiah($gaji_summary['sisa_gaji']); ?></span>
                </div>
                <div class="w-full bg-surface-container-high rounded-full h-3 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500 <?= $gaji_summary['persen_terpakai'] > 90 ? 'bg-rose-600' : ($gaji_summary['persen_terpakai'] > 75 ? 'bg-amber-500' : 'bg-primary'); ?>"
                        style="width: <?= min(100, $gaji_summary['persen_terpakai']); ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- Top Category Spend -->
        <div class="pt-4 border-t border-outline-variant space-y-2">
            <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Distribusi Pengeluaran Terbesar:</p>
            <?php if (empty($gaji_summary['breakdown'])): ?>
                <p class="text-xs text-on-surface-variant italic">Belum ada pengeluaran dicatat bulan ini.</p>
            <?php else: ?>
                <?php foreach (array_slice($gaji_summary['breakdown'], 0, 3) as $bk): ?>
                    <div class="flex justify-between text-xs">
                        <span class="text-on-surface"><?= htmlspecialchars($bk['nama_kategori']); ?></span>
                        <span class="font-semibold text-on-surface"><?= format_rupiah($bk['total_pengeluaran']); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Monthly Cashflow Chart -->
    <div class="lg:col-span-2 bg-surface-container-lowest p-6 rounded-2xl shadow-sm border border-outline-variant space-y-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-outline-variant pb-4">
            <div>
                <h3 class="font-bold text-base text-on-surface">Grafik Arus Kas Bulanan (<?= $tahun_ini; ?>)</h3>
                <p class="text-xs text-on-surface-variant">Perbandingan Pemasukan vs Pengeluaran per Bulan</p>
            </div>
            <div class="flex items-center gap-4 text-xs font-semibold">
                <span class="flex items-center gap-1 text-primary"><span class="w-3 h-3 rounded-full bg-primary inline-block"></span> Pemasukan</span>
                <span class="flex items-center gap-1 text-secondary"><span class="w-3 h-3 rounded-full bg-secondary inline-block"></span> Pengeluaran</span>
            </div>
        </div>

        <div class="h-64 sm:h-72 w-full">
            <canvas id="cashflowChart"></canvas>
        </div>
    </div>
</section>

<!-- 3. Recent Transactions Table -->
<section class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="font-bold text-lg text-on-surface">Transaksi Terakhir</h3>
            <p class="text-xs text-on-surface-variant">6 Transaksi terbaru dalam sistem</p>
        </div>
        <a href="transaksi.php" class="px-3.5 py-2 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl font-semibold text-xs transition-colors flex items-center gap-1">
            Lihat Semua Transaksi <span class="material-symbols-outlined text-sm">arrow_forward</span>
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-surface-container text-xs font-semibold text-on-surface-variant uppercase tracking-wider border-b border-outline-variant">
                <tr>
                    <th class="px-4 py-3">Tanggal</th>
                    <th class="px-4 py-3">Kategori</th>
                    <th class="px-4 py-3">Usaha</th>
                    <th class="px-4 py-3">Keterangan</th>
                    <th class="px-4 py-3 text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant/60">
                <?php if (empty($recent_transactions)): ?>
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-on-surface-variant italic">Belum ada data transaksi.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_transactions as $rt): ?>
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-3 text-xs font-medium text-on-surface-variant whitespace-nowrap"><?= date('d/m/Y', strtotime($rt['tanggal'])); ?></td>
                            <td class="px-4 py-3 font-semibold text-on-surface">
                                <?= htmlspecialchars($rt['nama_kategori']); ?>
                            </td>
                            <td class="px-4 py-3 text-xs text-on-surface-variant"><?= $rt['nama_usaha'] ? htmlspecialchars($rt['nama_usaha']) : '-'; ?></td>
                            <td class="px-4 py-3 text-on-surface">
                                <?= htmlspecialchars($rt['keterangan'] ?: '-'); ?>
                                <?php if ($rt['total_items'] > 0): ?>
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-tertiary ml-2 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100">
                                        <span class="material-symbols-outlined text-xs">receipt</span> <?= $rt['total_items']; ?> Item
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right font-bold <?= $rt['tipe'] === 'pemasukan' ? 'text-primary' : 'text-secondary'; ?> whitespace-nowrap">
                                <?= ($rt['tipe'] === 'pemasukan' ? '+' : '-') . ' ' . format_rupiah($rt['jumlah']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Chart.js Script Integration -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('cashflowChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Pemasukan',
                    data: <?= json_encode($chart_pemasukan); ?>,
                    backgroundColor: '#0d631b',
                    borderRadius: 6,
                },
                {
                    label: 'Pengeluaran',
                    data: <?= json_encode($chart_pengeluaran); ?>,
                    backgroundColor: '#b6171e',
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return 'Rp ' + (value/1000000) + ' Jt';
                            if (value >= 1000) return 'Rp ' + (value/1000) + ' Rb';
                            return 'Rp ' + value;
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
