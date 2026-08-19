<?php
$page_title = "Laporan Bulanan";
$page_header = "Laporan Keuangan Bulanan";
require_once __DIR__ . '/../includes/header.php';

// Filter month and year
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Fetch summaries
$summary = get_dashboard_summary($pdo, $bulan, $tahun);
$gaji_summary = get_gaji_summary($pdo, $bulan, $tahun);
$usaha_summary = get_usaha_summary($pdo, null, $bulan, $tahun);

// Rekap Per Kategori for selected month/year
$stmtKatBreakdown = $pdo->prepare("
    SELECT k.nama_kategori, k.tipe, COALESCE(SUM(t.jumlah), 0) AS total
    FROM kategori k
    JOIN transaksi t ON t.kategori_id = k.id
    WHERE MONTH(t.tanggal) = :bulan AND YEAR(t.tanggal) = :tahun
    GROUP BY k.id, k.nama_kategori, k.tipe
    ORDER BY k.tipe DESC, total DESC
");
$stmtKatBreakdown->execute(['bulan' => $bulan, 'tahun' => $tahun]);
$kategori_rekap = $stmtKatBreakdown->fetchAll();

// Doughnut chart data for Gaji vs Pengeluaran
$pie_labels = [];
$pie_values = [];
foreach ($gaji_summary['breakdown'] as $b) {
    $pie_labels[] = $b['nama_kategori'];
    $pie_values[] = (float)$b['total_pengeluaran'];
}
?>

<!-- Filter & Action Bar -->
<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 print:hidden">
    <div>
        <h2 class="text-xl font-bold text-on-surface">Laporan Periode <?= get_nama_bulan($bulan) . ' ' . $tahun; ?></h2>
        <p class="text-sm text-on-surface-variant">Ringkasan transaksi, rekap per kategori, per usaha, dan analisis penggunaan gaji.</p>
    </div>
    
    <div class="flex items-center gap-2">
        <button onclick="window.print()" class="px-4 py-2.5 bg-surface-container-high hover:bg-surface-container-highest text-on-surface rounded-xl font-semibold text-sm transition-all flex items-center gap-2 border border-outline-variant">
            <span class="material-symbols-outlined text-lg">print</span> Cetak / PDF
        </button>
    </div>
</div>

<!-- Month & Year Selector -->
<div class="bg-surface-container-lowest p-4 rounded-2xl border border-outline-variant shadow-sm mb-6 print:hidden">
    <form method="GET" class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-on-surface-variant uppercase">Bulan:</label>
            <select name="bulan" class="px-3 py-2 text-sm border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m; ?>" <?= $bulan === $m ? 'selected' : ''; ?>><?= get_nama_bulan($m); ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <label class="text-xs font-semibold text-on-surface-variant uppercase">Tahun:</label>
            <select name="tahun" class="px-3 py-2 text-sm border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20">
                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                    <option value="<?= $y; ?>" <?= $tahun === $y ? 'selected' : ''; ?>><?= $y; ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <button type="submit" class="px-4 py-2 bg-primary text-on-primary font-semibold text-sm rounded-xl hover:bg-primary-container transition-colors flex items-center gap-1">
            <span class="material-symbols-outlined text-lg">search</span> Tampilkan Laporan
        </button>
    </form>
</div>

<!-- Printable Header -->
<div class="hidden print:block mb-6 text-center border-b pb-4">
    <h1 class="text-2xl font-bold">LAPORAN KEUANGAN BULANAN</h1>
    <p class="text-sm">Periode: <?= get_nama_bulan($bulan) . ' ' . $tahun; ?></p>
</div>

<!-- Monthly Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Pemasukan</p>
        <p class="text-2xl font-bold text-primary mt-1"><?= format_rupiah($summary['pemasukan_bulan_ini']); ?></p>
    </div>
    <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Total Pengeluaran</p>
        <p class="text-2xl font-bold text-secondary mt-1"><?= format_rupiah($summary['pengeluaran_bulan_ini']); ?></p>
    </div>
    <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm">
        <p class="text-xs font-semibold text-on-surface-variant uppercase tracking-wider">Surplus / Defisit</p>
        <p class="text-2xl font-bold <?= $summary['surplus_bulan_ini'] >= 0 ? 'text-emerald-700' : 'text-rose-700'; ?> mt-1">
            <?= format_rupiah($summary['surplus_bulan_ini']); ?>
        </p>
    </div>
</div>

<!-- Special Section: Gaji vs Pengeluaran -->
<div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm mb-6 space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-outline-variant pb-4">
        <div>
            <h3 class="font-bold text-lg text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">pie_chart</span> Rincian Gaji vs Pengeluaran Bulan Ini
            </h3>
            <p class="text-xs text-on-surface-variant">Analisis ke mana saja pemasukan gaji terpakai pada bulan yang sama.</p>
        </div>
        <div class="text-right">
            <span class="text-xs text-on-surface-variant font-medium">Sisa Gaji: </span>
            <span class="font-bold text-base text-primary"><?= format_rupiah($gaji_summary['sisa_gaji']); ?></span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
        <!-- Table Breakdown -->
        <div class="lg:col-span-7 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-container text-xs font-semibold text-on-surface-variant uppercase border-b border-outline-variant">
                    <tr>
                        <th class="px-4 py-2.5">Kategori Pengeluaran</th>
                        <th class="px-4 py-2.5 text-right">Jumlah</th>
                        <th class="px-4 py-2.5 text-right">% Dari Gaji</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    <tr class="bg-emerald-50/60 font-semibold">
                        <td class="px-4 py-3 text-emerald-900">Total Gaji Masuk</td>
                        <td class="px-4 py-3 text-right text-emerald-900"><?= format_rupiah($gaji_summary['total_gaji']); ?></td>
                        <td class="px-4 py-3 text-right text-emerald-900">100%</td>
                    </tr>
                    <?php if (empty($gaji_summary['breakdown'])): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-center text-on-surface-variant italic">Belum ada pengeluaran dicatat pada bulan ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($gaji_summary['breakdown'] as $bk): ?>
                            <?php 
                                $pct = $gaji_summary['total_gaji'] > 0 ? round(($bk['total_pengeluaran'] / $gaji_summary['total_gaji']) * 100, 1) : 0;
                            ?>
                            <tr class="hover:bg-surface-container-low">
                                <td class="px-4 py-2.5 font-medium text-on-surface"><?= htmlspecialchars($bk['nama_kategori']); ?></td>
                                <td class="px-4 py-2.5 text-right text-secondary font-semibold"><?= format_rupiah($bk['total_pengeluaran']); ?></td>
                                <td class="px-4 py-2.5 text-right font-medium text-on-surface-variant"><?= $pct; ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Doughnut Chart -->
        <div class="lg:col-span-5 flex flex-col items-center justify-center p-4">
            <div class="h-56 w-56 relative">
                <canvas id="salaryDoughnutChart"></canvas>
            </div>
            <p class="text-xs text-on-surface-variant mt-2 font-medium">Distribusi Persentase Pengeluaran Gaji</p>
        </div>
    </div>
</div>

<!-- Rekap Per Kategori & Per Usaha Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Table Rekap Per Kategori -->
    <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm space-y-4">
        <h3 class="font-bold text-base text-on-surface border-b border-outline-variant pb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">category</span> Rekap Per Kategori Bulan Ini
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-container text-xs font-semibold text-on-surface-variant uppercase border-b border-outline-variant">
                    <tr>
                        <th class="px-4 py-2.5">Kategori</th>
                        <th class="px-4 py-2.5">Tipe</th>
                        <th class="px-4 py-2.5 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    <?php if (empty($kategori_rekap)): ?>
                        <tr>
                            <td colspan="3" class="px-4 py-4 text-center text-on-surface-variant italic">Belum ada transaksi di bulan ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($kategori_rekap as $kr): ?>
                            <tr class="hover:bg-surface-container-low">
                                <td class="px-4 py-2.5 font-medium text-on-surface"><?= htmlspecialchars($kr['nama_kategori']); ?></td>
                                <td class="px-4 py-2.5">
                                    <span class="px-2 py-0.5 text-[11px] font-semibold rounded-full <?= $kr['tipe'] === 'pemasukan' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'; ?>">
                                        <?= ucfirst($kr['tipe']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-bold <?= $kr['tipe'] === 'pemasukan' ? 'text-primary' : 'text-secondary'; ?>">
                                    <?= format_rupiah($kr['total']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Table Rekap Per Usaha -->
    <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm space-y-4">
        <h3 class="font-bold text-base text-on-surface border-b border-outline-variant pb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary">storefront</span> Rekap Per Usaha Bulan Ini
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-surface-container text-xs font-semibold text-on-surface-variant uppercase border-b border-outline-variant">
                    <tr>
                        <th class="px-4 py-2.5">Nama Usaha</th>
                        <th class="px-4 py-2.5 text-right">Pemasukan</th>
                        <th class="px-4 py-2.5 text-right">Pengeluaran</th>
                        <th class="px-4 py-2.5 text-right">Laba/Rugi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant/60">
                    <?php if (empty($usaha_summary)): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-on-surface-variant italic">Belum ada data usaha.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usaha_summary as $us): ?>
                            <tr class="hover:bg-surface-container-low">
                                <td class="px-4 py-2.5 font-medium text-on-surface"><?= htmlspecialchars($us['nama_usaha']); ?></td>
                                <td class="px-4 py-2.5 text-right text-emerald-700 font-semibold"><?= format_rupiah($us['total_pemasukan']); ?></td>
                                <td class="px-4 py-2.5 text-right text-rose-700 font-semibold"><?= format_rupiah($us['total_pengeluaran']); ?></td>
                                <td class="px-4 py-2.5 text-right font-bold <?= $us['laba_bersih'] >= 0 ? 'text-primary' : 'text-secondary'; ?>">
                                    <?= format_rupiah($us['laba_bersih']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('salaryDoughnutChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($pie_labels); ?>,
            datasets: [{
                data: <?= json_encode($pie_values); ?>,
                backgroundColor: ['#0d631b', '#b6171e', '#00569f', '#d97706', '#7c3aed', '#059669', '#db2777'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
