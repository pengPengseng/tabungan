/**
 * Keuangan App - Pure Client-Side Application (Zero-Database / LocalStorage + Excel Export)
 * Designed for static hosting on Vercel, Netlify, GitHub Pages, or Localhost.
 */

// Initial Seed Data for first time load
const DEFAULT_CATEGORIES = [
    { id: 1, nama_kategori: 'Gaji', tipe: 'pemasukan' },
    { id: 2, nama_kategori: 'Pemasukan Saham', tipe: 'pemasukan' },
    { id: 3, nama_kategori: 'Pemasukan Usaha', tipe: 'pemasukan' },
    { id: 4, nama_kategori: 'Lainnya (Pemasukan)', tipe: 'pemasukan' },
    { id: 5, nama_kategori: 'Makan & Minum', tipe: 'pengeluaran' },
    { id: 6, nama_kategori: 'Transportasi', tipe: 'pengeluaran' },
    { id: 7, nama_kategori: 'Tagihan & Utilitas', tipe: 'pengeluaran' },
    { id: 8, nama_kategori: 'Pengeluaran Usaha', tipe: 'pengeluaran' },
    { id: 9, nama_kategori: 'Hiburan & Belanja', tipe: 'pengeluaran' },
    { id: 10, nama_kategori: 'Lainnya (Pengeluaran)', tipe: 'pengeluaran' }
];

const DEFAULT_BUSINESSES = [];

const getTodayDateString = () => {
    const d = new Date();
    return d.toISOString().split('T')[0];
};

const DEFAULT_TRANSACTIONS = [];

// LocalStorage Keys
const STORAGE_KEYS = {
    CATEGORIES: 'keuangan_categories_v1',
    BUSINESSES: 'keuangan_businesses_v1',
    TRANSACTIONS: 'keuangan_transactions_v1'
};

// DB Manager
const DB = {
    init() {
        if (!localStorage.getItem(STORAGE_KEYS.CATEGORIES)) {
            localStorage.setItem(STORAGE_KEYS.CATEGORIES, JSON.stringify(DEFAULT_CATEGORIES));
        }
        if (!localStorage.getItem(STORAGE_KEYS.BUSINESSES)) {
            localStorage.setItem(STORAGE_KEYS.BUSINESSES, JSON.stringify(DEFAULT_BUSINESSES));
        }
        if (!localStorage.getItem(STORAGE_KEYS.TRANSACTIONS)) {
            localStorage.setItem(STORAGE_KEYS.TRANSACTIONS, JSON.stringify(DEFAULT_TRANSACTIONS));
        }
    },
    getCategories() {
        return JSON.parse(localStorage.getItem(STORAGE_KEYS.CATEGORIES) || '[]');
    },
    saveCategories(data) {
        localStorage.setItem(STORAGE_KEYS.CATEGORIES, JSON.stringify(data));
    },
    getBusinesses() {
        return JSON.parse(localStorage.getItem(STORAGE_KEYS.BUSINESSES) || '[]');
    },
    saveBusinesses(data) {
        localStorage.setItem(STORAGE_KEYS.BUSINESSES, JSON.stringify(data));
    },
    getTransactions() {
        return JSON.parse(localStorage.getItem(STORAGE_KEYS.TRANSACTIONS) || '[]');
    },
    saveTransactions(data) {
        localStorage.setItem(STORAGE_KEYS.TRANSACTIONS, JSON.stringify(data));
    },
    resetData() {
        localStorage.setItem(STORAGE_KEYS.CATEGORIES, JSON.stringify(DEFAULT_CATEGORIES));
        localStorage.setItem(STORAGE_KEYS.BUSINESSES, JSON.stringify(DEFAULT_BUSINESSES));
        localStorage.setItem(STORAGE_KEYS.TRANSACTIONS, JSON.stringify(DEFAULT_TRANSACTIONS));
    }
};

// Utility Helpers
const Utils = {
    formatRupiah(amount, withPrefix = true) {
        const num = parseFloat(amount) || 0;
        const formatted = num.toLocaleString('id-ID');
        return withPrefix ? 'Rp ' + formatted : formatted;
    },
    getNamaBulan(monthNumber) {
        const months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        return months[parseInt(monthNumber, 10) - 1] || '';
    },
    showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const isError = type === 'error';
        const isWarning = type === 'warning';
        const bg = isError ? 'bg-rose-50 border-rose-200 text-rose-800' : (isWarning ? 'bg-amber-50 border-amber-200 text-amber-800' : 'bg-emerald-50 border-emerald-200 text-emerald-800');
        const icon = isError ? 'error' : (isWarning ? 'warning' : 'check_circle');

        const toast = document.createElement('div');
        toast.className = `p-4 rounded-xl border ${bg} flex items-center justify-between shadow-sm transition-all duration-300 transform translate-y-0 opacity-100 mb-2`;
        toast.innerHTML = `
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl">${icon}</span>
                <span class="text-sm font-medium">${message}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-current opacity-70 hover:opacity-100">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        `;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('opacity-0', '-translate-y-2');
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    }
};

// Chart Instances Global Tracker
let cashflowChartInstance = null;
let salaryChartInstance = null;
let reportSalaryChartInstance = null;

// App Core Controller
const App = {
    currentView: 'dashboard',
    selectedUsahaDetailId: null,

    init() {
        DB.init();
        this.bindEvents();
        this.renderView('dashboard');
    },

    bindEvents() {
        // Navigation clicks
        document.querySelectorAll('[data-nav]').forEach(el => {
            el.addEventListener('click', (e) => {
                e.preventDefault();
                const view = el.getAttribute('data-nav');
                this.renderView(view);
                this.closeMobileSidebar();
            });
        });
    },

    closeMobileSidebar() {
        const sidebar = document.getElementById('mobileSidebar');
        if (sidebar && !sidebar.classList.contains('hidden')) {
            sidebar.classList.add('hidden');
        }
    },

    renderView(viewName, params = {}) {
        this.currentView = viewName;
        
        // Hide all views
        document.querySelectorAll('.view-section').forEach(v => v.classList.add('hidden'));

        // Update nav links active state
        document.querySelectorAll('[data-nav]').forEach(el => {
            const target = el.getAttribute('data-nav');
            if (target === viewName) {
                el.classList.add('bg-primary-container', 'text-on-primary-container', 'font-semibold');
                el.classList.remove('text-on-surface-variant');
            } else {
                el.classList.remove('bg-primary-container', 'text-on-primary-container', 'font-semibold');
                el.classList.add('text-on-surface-variant');
            }
        });

        // Set Top Header Title
        const titleMap = {
            'dashboard': 'Dashboard Keuangan',
            'transaksi': 'Transaksi Keuangan',
            'usaha': 'Usaha Saya',
            'laporan': 'Laporan Keuangan Bulanan',
            'kategori': 'Kelola Kategori'
        };
        const headerTitle = document.getElementById('headerPageTitle');
        if (headerTitle) headerTitle.innerText = titleMap[viewName] || 'Keuangan';

        // Render specific view
        if (viewName === 'dashboard') {
            this.renderDashboard();
        } else if (viewName === 'transaksi') {
            this.renderTransaksi();
        } else if (viewName === 'usaha') {
            this.renderUsaha(params.detailId);
        } else if (viewName === 'laporan') {
            this.renderLaporan();
        } else if (viewName === 'kategori') {
            this.renderKategori();
        }

        const targetView = document.getElementById(`view-${viewName}`);
        if (targetView) targetView.classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    // -------------------------------------------------------------------------
    // 1. DASHBOARD VIEW
    // -------------------------------------------------------------------------
    renderDashboard() {
        const transactions = DB.getTransactions();
        const categories = DB.getCategories();
        const businesses = DB.getBusinesses();

        const now = new Date();
        const currentMonth = now.getMonth() + 1;
        const currentYear = now.getFullYear();

        // 1. Total Saldo (All time)
        let totalInAll = 0;
        let totalOutAll = 0;
        let totalInMonth = 0;
        let totalOutMonth = 0;
        let totalGajiMonth = 0;

        const expenseByCategoryMonth = {};

        transactions.forEach(t => {
            const tDate = new Date(t.tanggal);
            const tMonth = tDate.getMonth() + 1;
            const tYear = tDate.getFullYear();
            const amount = parseFloat(t.jumlah) || 0;

            if (t.tipe === 'pemasukan') {
                totalInAll += amount;
                if (tMonth === currentMonth && tYear === currentYear) {
                    totalInMonth += amount;
                    const cat = categories.find(c => c.id === t.kategori_id);
                    if (cat && cat.nama_kategori.toLowerCase().includes('gaji')) {
                        totalGajiMonth += amount;
                    }
                }
            } else {
                totalOutAll += amount;
                if (tMonth === currentMonth && tYear === currentYear) {
                    totalOutMonth += amount;
                    const cat = categories.find(c => c.id === t.kategori_id);
                    const catName = cat ? cat.nama_kategori : 'Lainnya';
                    expenseByCategoryMonth[catName] = (expenseByCategoryMonth[catName] || 0) + amount;
                }
            }
        });

        const totalSaldo = totalInAll - totalOutAll;
        const sisaGaji = totalGajiMonth - totalOutMonth;
        const percentGajiUsed = totalGajiMonth > 0 ? Math.min(100, Math.round((totalOutMonth / totalGajiMonth) * 100)) : 0;

        // Set DOM Values
        document.getElementById('dashTotalSaldo').innerText = Utils.formatRupiah(totalSaldo);
        document.getElementById('dashPemasukanBulan').innerText = Utils.formatRupiah(totalInMonth);
        document.getElementById('dashPengeluaranBulan').innerText = Utils.formatRupiah(totalOutMonth);
        document.getElementById('dashMonthYearLabel').innerText = `${Utils.getNamaBulan(currentMonth)} ${currentYear}`;
        document.getElementById('dashMonthYearLabel2').innerText = `${Utils.getNamaBulan(currentMonth)} ${currentYear}`;

        // Salary Card
        document.getElementById('dashTotalGaji').innerText = Utils.formatRupiah(totalGajiMonth);
        document.getElementById('dashTotalPengeluaranGaji').innerText = Utils.formatRupiah(totalOutMonth);
        document.getElementById('dashSisaGaji').innerText = Utils.formatRupiah(sisaGaji);
        document.getElementById('dashGajiBadge').innerText = `${percentGajiUsed}% Terpakai`;
        const progressBar = document.getElementById('dashGajiProgressBar');
        if (progressBar) {
            progressBar.style.width = `${percentGajiUsed}%`;
            progressBar.className = `h-full rounded-full transition-all duration-500 ${percentGajiUsed > 90 ? 'bg-rose-600' : (percentGajiUsed > 75 ? 'bg-amber-500' : 'bg-primary')}`;
        }

        // Top Category Breakdown
        const topExpenseList = Object.entries(expenseByCategoryMonth)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 3);

        const topExpenseContainer = document.getElementById('dashTopExpenseContainer');
        if (topExpenseContainer) {
            if (topExpenseList.length === 0) {
                topExpenseContainer.innerHTML = '<p class="text-xs text-on-surface-variant italic">Belum ada pengeluaran dicatat bulan ini.</p>';
            } else {
                topExpenseContainer.innerHTML = topExpenseList.map(([cat, amt]) => `
                    <div class="flex justify-between text-xs">
                        <span class="text-on-surface">${cat}</span>
                        <span class="font-semibold text-on-surface">${Utils.formatRupiah(amt)}</span>
                    </div>
                `).join('');
            }
        }

        // Render Recent Transactions (Limit 6)
        const recentList = [...transactions].sort((a, b) => new Date(b.tanggal) - new Date(a.tanggal)).slice(0, 6);
        const recentTbody = document.getElementById('dashRecentTableBody');
        if (recentTbody) {
            if (recentList.length === 0) {
                recentTbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-on-surface-variant italic">Belum ada data transaksi.</td></tr>';
            } else {
                recentTbody.innerHTML = recentList.map(t => {
                    const cat = categories.find(c => c.id === t.kategori_id);
                    const biz = businesses.find(b => b.id === t.usaha_id);
                    const hasItems = t.items && t.items.length > 0;
                    const dateFormatted = new Date(t.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });

                    return `
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-3 text-xs font-medium text-on-surface-variant whitespace-nowrap">${dateFormatted}</td>
                            <td class="px-4 py-3 font-semibold text-on-surface">${cat ? cat.nama_kategori : '-'}</td>
                            <td class="px-4 py-3 text-xs text-on-surface-variant">${biz ? biz.nama_usaha : '-'}</td>
                            <td class="px-4 py-3 text-on-surface">
                                ${t.keterangan || '-'}
                                ${hasItems ? `<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-tertiary ml-2 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-100"><span class="material-symbols-outlined text-xs">receipt</span> ${t.items.length} Item</span>` : ''}
                            </td>
                            <td class="px-4 py-3 text-right font-bold ${t.tipe === 'pemasukan' ? 'text-primary' : 'text-secondary'} whitespace-nowrap">
                                ${t.tipe === 'pemasukan' ? '+' : '-'} ${Utils.formatRupiah(t.jumlah)}
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }

        // Render Cashflow Bar Chart (12 Months)
        this.renderCashflowChart(currentYear);
    },

    renderCashflowChart(year) {
        const transactions = DB.getTransactions();
        const monthlyIn = Array(12).fill(0);
        const monthlyOut = Array(12).fill(0);

        transactions.forEach(t => {
            const d = new Date(t.tanggal);
            if (d.getFullYear() === year) {
                const m = d.getMonth();
                const amt = parseFloat(t.jumlah) || 0;
                if (t.tipe === 'pemasukan') {
                    monthlyIn[m] += amt;
                } else {
                    monthlyOut[m] += amt;
                }
            }
        });

        const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const ctx = document.getElementById('dashCashflowCanvas');
        if (!ctx) return;

        if (cashflowChartInstance) cashflowChartInstance.destroy();

        cashflowChartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Pemasukan', data: monthlyIn, backgroundColor: '#0d631b', borderRadius: 6 },
                    { label: 'Pengeluaran', data: monthlyOut, backgroundColor: '#b6171e', borderRadius: 6 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        ticks: {
                            callback: (v) => v >= 1000000 ? 'Rp ' + (v / 1000000) + ' Jt' : (v >= 1000 ? 'Rp ' + (v / 1000) + ' Rb' : 'Rp ' + v)
                        }
                    }
                }
            }
        });
    },

    // -------------------------------------------------------------------------
    // 2. TRANSAKSI VIEW
    // -------------------------------------------------------------------------
    renderTransaksi() {
        const transactions = DB.getTransactions();
        const categories = DB.getCategories();
        const businesses = DB.getBusinesses();

        // Populate dropdowns in filter
        const filterKat = document.getElementById('filterTransaksiKategori');
        if (filterKat) {
            filterKat.innerHTML = '<option value="">Semua Kategori</option>' + 
                categories.map(c => `<option value="${c.id}">${c.nama_kategori} (${c.tipe})</option>`).join('');
        }

        // Apply filters
        const filterMonth = document.getElementById('filterTransaksiBulan')?.value || '';
        const filterYear = document.getElementById('filterTransaksiTahun')?.value || '';
        const filterType = document.getElementById('filterTransaksiTipe')?.value || '';
        const filterKatId = document.getElementById('filterTransaksiKategori')?.value || '';

        const filtered = transactions.filter(t => {
            const d = new Date(t.tanggal);
            const m = d.getMonth() + 1;
            const y = d.getFullYear();

            if (filterMonth && m !== parseInt(filterMonth, 10)) return false;
            if (filterYear && y !== parseInt(filterYear, 10)) return false;
            if (filterType && t.tipe !== filterType) return false;
            if (filterKatId && t.kategori_id !== parseInt(filterKatId, 10)) return false;
            return true;
        }).sort((a, b) => new Date(b.tanggal) - new Date(a.tanggal));

        const tbody = document.getElementById('transaksiTableBody');
        if (!tbody) return;

        if (filtered.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-on-surface-variant italic">Tidak ada transaksi ditemukan.</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(t => {
            const cat = categories.find(c => c.id === t.kategori_id);
            const biz = businesses.find(b => b.id === t.usaha_id);
            const dateFormatted = new Date(t.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const hasItems = t.items && t.items.length > 0;

            return `
                <tr class="hover:bg-surface-container-low transition-colors">
                    <td class="px-4 py-3 font-medium text-xs text-on-surface-variant whitespace-nowrap">${dateFormatted}</td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full ${t.tipe === 'pemasukan' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'} flex items-center w-max gap-1">
                            <span class="material-symbols-outlined text-xs">${t.tipe === 'pemasukan' ? 'arrow_downward' : 'arrow_upward'}</span>
                            ${t.tipe === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran'}
                        </span>
                    </td>
                    <td class="px-4 py-3 font-medium text-on-surface">${cat ? cat.nama_kategori : '-'}</td>
                    <td class="px-4 py-3 text-xs text-on-surface-variant">${biz ? biz.nama_usaha : '-'}</td>
                    <td class="px-4 py-3 text-on-surface">
                        ${t.keterangan || '-'}
                        ${hasItems ? `<button onclick="App.showItemDetailModal(${t.id})" class="inline-flex items-center gap-1 text-[11px] font-semibold text-tertiary hover:underline ml-2"><span class="material-symbols-outlined text-xs">receipt</span> ${t.items.length} Item</button>` : ''}
                    </td>
                    <td class="px-4 py-3 text-right font-bold ${t.tipe === 'pemasukan' ? 'text-primary' : 'text-secondary'} whitespace-nowrap">
                        ${t.tipe === 'pemasukan' ? '+' : '-'} ${Utils.formatRupiah(t.jumlah)}
                    </td>
                    <td class="px-4 py-3 text-center whitespace-nowrap">
                        <div class="flex items-center justify-center gap-1">
                            ${hasItems ? `<button onclick="App.showItemDetailModal(${t.id})" class="p-1 text-on-surface-variant hover:text-tertiary rounded-lg hover:bg-surface-container" title="Lihat Rincian"><span class="material-symbols-outlined text-lg">visibility</span></button>` : ''}
                            <button onclick="App.openEditTransaksiModal(${t.id})" class="p-1 text-on-surface-variant hover:text-tertiary rounded-lg hover:bg-surface-container" title="Edit"><span class="material-symbols-outlined text-lg">edit</span></button>
                            <button onclick="App.deleteTransaksi(${t.id})" class="p-1 text-on-surface-variant hover:text-secondary rounded-lg hover:bg-surface-container" title="Hapus"><span class="material-symbols-outlined text-lg">delete</span></button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    },

    openAddTransaksiModal() {
        const categories = DB.getCategories();
        const businesses = DB.getBusinesses();

        document.getElementById('modalTransaksiTitle').innerText = 'Tambah Transaksi Baru';
        document.getElementById('formTransaksiId').value = '';
        document.getElementById('formTransaksiTipe').value = 'pengeluaran';
        document.getElementById('formTransaksiTanggal').value = getTodayDateString();
        document.getElementById('formTransaksiJumlah').value = '';
        document.getElementById('formTransaksiKeterangan').value = '';
        document.getElementById('itemRowsContainer').innerHTML = '';

        this.populateFormKategoriSelect('pengeluaran');
        this.populateFormUsahaSelect();
        this.checkCategoryUsahaVisibility();

        this.openModal('transaksiModal');
    },

    openEditTransaksiModal(id) {
        const transactions = DB.getTransactions();
        const t = transactions.find(x => x.id === id);
        if (!t) return;

        document.getElementById('modalTransaksiTitle').innerText = 'Edit Transaksi';
        document.getElementById('formTransaksiId').value = t.id;
        document.getElementById('formTransaksiTipe').value = t.tipe;
        document.getElementById('formTransaksiTanggal').value = t.tanggal;
        document.getElementById('formTransaksiJumlah').value = t.jumlah;
        document.getElementById('formTransaksiKeterangan').value = t.keterangan || '';

        this.populateFormKategoriSelect(t.tipe, t.kategori_id);
        this.populateFormUsahaSelect(t.usaha_id);
        this.checkCategoryUsahaVisibility();

        const container = document.getElementById('itemRowsContainer');
        container.innerHTML = '';

        if (t.items && t.items.length > 0) {
            t.items.forEach(it => {
                this.addItemRow(it.nama_item, it.jumlah_qty, it.harga_satuan);
            });
        }

        this.openModal('transaksiModal');
    },

    populateFormKategoriSelect(tipe, selectedId = null) {
        const categories = DB.getCategories().filter(c => c.tipe === tipe);
        const sel = document.getElementById('formTransaksiKategori');
        if (!sel) return;

        sel.innerHTML = '<option value="">-- Pilih Kategori --</option>' +
            categories.map(c => `<option value="${c.id}" ${selectedId === c.id ? 'selected' : ''}>${c.nama_kategori}</option>`).join('');
    },

    populateFormUsahaSelect(selectedId = null) {
        const businesses = DB.getBusinesses();
        const sel = document.getElementById('formTransaksiUsaha');
        if (!sel) return;

        sel.innerHTML = '<option value="">-- Tidak Terkait Usaha --</option>' +
            businesses.map(b => `<option value="${b.id}" ${selectedId === b.id ? 'selected' : ''}>${b.nama_usaha}</option>`).join('');
    },

    onFormTipeChange() {
        const tipe = document.getElementById('formTransaksiTipe').value;
        this.populateFormKategoriSelect(tipe);
        this.checkCategoryUsahaVisibility();
    },

    checkCategoryUsahaVisibility() {
        const katSelect = document.getElementById('formTransaksiKategori');
        const container = document.getElementById('formUsahaContainer');
        if (!katSelect || !container) return;

        const opt = katSelect.options[katSelect.selectedIndex];
        const text = opt ? opt.text.toLowerCase() : '';

        if (text.includes('usaha')) {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
            const usahaSel = document.getElementById('formTransaksiUsaha');
            if (usahaSel) usahaSel.value = '';
        }
    },

    addItemRow(nama = '', qty = 1, harga = 0) {
        const container = document.getElementById('itemRowsContainer');
        if (!container) return;

        const rowId = Date.now() + Math.random().toString(36).substring(2, 5);
        const subtotal = (parseFloat(qty) || 0) * (parseFloat(harga) || 0);

        const rowHTML = `
            <div class="item-row grid grid-cols-12 gap-2 items-center bg-surface-container-low p-2 rounded-xl border border-outline-variant" id="row_${rowId}">
                <div class="col-span-5">
                    <input type="text" value="${nama}" placeholder="Nama item" required class="item-name w-full px-3 py-2 text-sm bg-white border border-outline-variant rounded-lg">
                </div>
                <div class="col-span-2">
                    <input type="number" step="0.01" min="0.1" value="${qty}" oninput="App.calculateItemSubtotals()" placeholder="Qty" required class="item-qty w-full px-2 py-2 text-sm bg-white border border-outline-variant rounded-lg text-center">
                </div>
                <div class="col-span-4">
                    <input type="number" step="1" min="0" value="${harga}" oninput="App.calculateItemSubtotals()" placeholder="Harga" required class="item-price w-full px-3 py-2 text-sm bg-white border border-outline-variant rounded-lg text-right">
                </div>
                <div class="col-span-1 text-center">
                    <button type="button" onclick="document.getElementById('row_${rowId}').remove(); App.calculateItemSubtotals();" class="p-1 text-secondary hover:bg-secondary-container/20 rounded-lg">
                        <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                </div>
                <div class="col-span-12 flex justify-between px-2 pt-1 text-xs text-on-surface-variant font-medium">
                    <span>Subtotal:</span>
                    <span class="item-subtotal-display font-semibold text-primary">${Utils.formatRupiah(subtotal)}</span>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', rowHTML);
        this.calculateItemSubtotals();
    },

    calculateItemSubtotals() {
        const rows = document.querySelectorAll('#itemRowsContainer .item-row');
        let grandTotal = 0;

        rows.forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
            const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
            const sub = qty * price;
            grandTotal += sub;

            const disp = row.querySelector('.item-subtotal-display');
            if (disp) disp.innerText = Utils.formatRupiah(sub);
        });

        if (rows.length > 0) {
            const jumlahInput = document.getElementById('formTransaksiJumlah');
            if (jumlahInput) jumlahInput.value = grandTotal;
        }
    },

    saveTransaksiForm(e) {
        e.preventDefault();

        const id = document.getElementById('formTransaksiId').value;
        const tipe = document.getElementById('formTransaksiTipe').value;
        const kategori_id = parseInt(document.getElementById('formTransaksiKategori').value, 10);
        const usaha_id_val = document.getElementById('formTransaksiUsaha').value;
        const usaha_id = usaha_id_val ? parseInt(usaha_id_val, 10) : null;
        const tanggal = document.getElementById('formTransaksiTanggal').value;
        const keterangan = document.getElementById('formTransaksiKeterangan').value.trim();
        const jumlahManual = parseFloat(document.getElementById('formTransaksiJumlah').value) || 0;

        if (!kategori_id || !tanggal) {
            Utils.showToast('Kategori dan tanggal wajib diisi.', 'error');
            return;
        }

        // Collect items
        const itemRows = document.querySelectorAll('#itemRowsContainer .item-row');
        const items = [];
        let itemsTotal = 0;

        itemRows.forEach(row => {
            const name = row.querySelector('.item-name')?.value.trim();
            const qty = parseFloat(row.querySelector('.item-qty')?.value) || 1;
            const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
            if (name) {
                const sub = qty * price;
                itemsTotal += sub;
                items.push({ id: Date.now() + Math.random(), nama_item: name, jumlah_qty: qty, harga_satuan: price, subtotal: sub });
            }
        });

        const finalJumlah = items.length > 0 ? itemsTotal : jumlahManual;

        const transactions = DB.getTransactions();

        if (id) {
            // Update
            const idx = transactions.findIndex(t => t.id === parseInt(id, 10));
            if (idx !== -1) {
                transactions[idx] = { ...transactions[idx], tipe, kategori_id, usaha_id, tanggal, keterangan, jumlah: finalJumlah, items };
                Utils.showToast('Transaksi berhasil diperbarui.');
            }
        } else {
            // Create
            const newId = transactions.length > 0 ? Math.max(...transactions.map(t => t.id)) + 1 : 1;
            transactions.push({ id: newId, tipe, kategori_id, usaha_id, tanggal, keterangan, jumlah: finalJumlah, items });
            Utils.showToast('Transaksi berhasil ditambahkan.');
        }

        DB.saveTransactions(transactions);
        this.closeModal('transaksiModal');
        this.renderTransaksi();
        if (this.currentView === 'dashboard') this.renderDashboard();
    },

    deleteTransaksi(id) {
        if (!confirm('Yakin ingin menghapus transaksi ini?')) return;

        let transactions = DB.getTransactions();
        transactions = transactions.filter(t => t.id !== id);
        DB.saveTransactions(transactions);
        Utils.showToast('Transaksi berhasil dihapus.');
        this.renderTransaksi();
    },

    showItemDetailModal(transaksiId) {
        const transactions = DB.getTransactions();
        const t = transactions.find(x => x.id === transaksiId);
        if (!t || !t.items || t.items.length === 0) return;

        const content = document.getElementById('itemDetailContent');
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

        let total = 0;
        t.items.forEach(it => {
            total += it.subtotal;
            html += `
                <tr>
                    <td class="px-3 py-2.5 font-medium text-on-surface">${it.nama_item}</td>
                    <td class="px-3 py-2.5 text-center">${it.jumlah_qty}</td>
                    <td class="px-3 py-2.5 text-right text-on-surface-variant">${Utils.formatRupiah(it.harga_satuan)}</td>
                    <td class="px-3 py-2.5 text-right font-semibold text-primary">${Utils.formatRupiah(it.subtotal)}</td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                    <tfoot class="border-t border-outline-variant font-bold text-sm bg-surface-container-low">
                        <tr>
                            <td colspan="3" class="px-3 py-2.5 text-right">Total Transaksi:</td>
                            <td class="px-3 py-2.5 text-right text-primary">${Utils.formatRupiah(total)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;
        content.innerHTML = html;
        this.openModal('itemDetailModal');
    },

    // -------------------------------------------------------------------------
    // 3. USAHA SAYA VIEW
    // -------------------------------------------------------------------------
    renderUsaha(detailId = null) {
        const businesses = DB.getBusinesses();
        const transactions = DB.getTransactions();

        if (detailId) {
            this.renderUsahaDetail(detailId);
            return;
        }

        document.getElementById('usahaListView').classList.remove('hidden');
        document.getElementById('usahaDetailView').classList.add('hidden');

        const container = document.getElementById('usahaCardsContainer');
        if (!container) return;

        if (businesses.length === 0) {
            container.innerHTML = `
                <div class="col-span-full bg-surface-container-lowest p-8 rounded-2xl border border-outline-variant text-center space-y-3">
                    <span class="material-symbols-outlined text-4xl text-on-surface-variant">storefront</span>
                    <p class="font-medium text-on-surface">Belum ada usaha terdaftar.</p>
                    <button onclick="App.openAddUsahaModal()" class="px-4 py-2 bg-primary text-on-primary text-sm font-semibold rounded-xl inline-flex items-center gap-2">
                        <span class="material-symbols-outlined">add</span> Tambah Usaha Pertama
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = businesses.map(u => {
            // Aggregate totals for this business
            let totalIn = 0;
            let totalOut = 0;

            transactions.filter(t => t.usaha_id === u.id).forEach(t => {
                const amt = parseFloat(t.jumlah) || 0;
                if (t.tipe === 'pemasukan') totalIn += amt;
                else totalOut += amt;
            });

            const netProfit = totalIn - totalOut;

            return `
                <div class="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                    <div class="space-y-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-primary bg-primary-container/20 p-2.5 rounded-xl">storefront</span>
                                <div>
                                    <h3 class="font-bold text-base text-on-surface">${u.nama_usaha}</h3>
                                    <span class="px-2.5 py-0.5 text-[11px] font-semibold rounded-full ${u.status === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700'}">
                                        ${u.status === 'aktif' ? 'Aktif' : 'Nonaktif'}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <button onclick="App.openEditUsahaModal(${u.id})" class="p-1 text-on-surface-variant hover:text-tertiary rounded-lg hover:bg-surface-container" title="Edit"><span class="material-symbols-outlined text-lg">edit</span></button>
                                <button onclick="App.deleteUsaha(${u.id})" class="p-1 text-on-surface-variant hover:text-secondary rounded-lg hover:bg-surface-container" title="Hapus"><span class="material-symbols-outlined text-lg">delete</span></button>
                            </div>
                        </div>

                        <p class="text-xs text-on-surface-variant line-clamp-2">${u.keterangan || 'Tidak ada keterangan'}</p>

                        <div class="pt-3 border-t border-outline-variant/60 space-y-2">
                            <div class="flex justify-between text-xs">
                                <span class="text-on-surface-variant">Total Pemasukan:</span>
                                <span class="font-semibold text-emerald-700">${Utils.formatRupiah(totalIn)}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-on-surface-variant">Total Pengeluaran:</span>
                                <span class="font-semibold text-rose-700">${Utils.formatRupiah(totalOut)}</span>
                            </div>
                            <div class="flex justify-between text-sm pt-2 border-t border-dashed border-outline-variant">
                                <span class="font-semibold text-on-surface">Laba / Rugi Bersih:</span>
                                <span class="font-bold ${netProfit >= 0 ? 'text-primary' : 'text-secondary'}">
                                    ${Utils.formatRupiah(netProfit)}
                                </span>
                            </div>
                        </div>
                    </div>

                    <button onclick="App.renderUsaha(${u.id})" class="w-full py-2.5 px-4 bg-surface-container-high text-on-surface hover:bg-primary hover:text-on-primary rounded-xl font-semibold text-xs transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-base">analytics</span> Lihat Detail & Rincian Item
                    </button>
                </div>
            `;
        }).join('');
    },

    renderUsahaDetail(id) {
        const businesses = DB.getBusinesses();
        const transactions = DB.getTransactions();
        const u = businesses.find(x => x.id === id);
        if (!u) return;

        document.getElementById('usahaListView').classList.add('hidden');
        document.getElementById('usahaDetailView').classList.remove('hidden');

        document.getElementById('detailUsahaName').innerText = u.nama_usaha;
        document.getElementById('detailUsahaKet').innerText = u.keterangan || 'Tidak ada deskripsi';

        let totalIn = 0;
        let totalOut = 0;
        const allItems = [];

        transactions.filter(t => t.usaha_id === u.id).forEach(t => {
            const amt = parseFloat(t.jumlah) || 0;
            if (t.tipe === 'pemasukan') {
                totalIn += amt;
            } else {
                totalOut += amt;
                if (t.items && t.items.length > 0) {
                    t.items.forEach(it => {
                        allItems.push({ ...it, tanggal: t.tanggal, ket_transaksi: t.keterangan });
                    });
                }
            }
        });

        const netProfit = totalIn - totalOut;
        document.getElementById('detailUsahaPemasukan').innerText = Utils.formatRupiah(totalIn);
        document.getElementById('detailUsahaPengeluaran').innerText = Utils.formatRupiah(totalOut);
        document.getElementById('detailUsahaLaba').innerText = Utils.formatRupiah(netProfit);

        const itemTbody = document.getElementById('detailUsahaItemTableBody');
        if (itemTbody) {
            if (allItems.length === 0) {
                itemTbody.innerHTML = '<tr><td colspan="5" class="px-4 py-6 text-center text-on-surface-variant italic">Belum ada rincian item pengeluaran untuk usaha ini.</td></tr>';
            } else {
                itemTbody.innerHTML = allItems.map(it => {
                    const d = new Date(it.tanggal).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
                    return `
                        <tr class="hover:bg-surface-container-low transition-colors">
                            <td class="px-4 py-3 font-medium text-xs text-on-surface-variant whitespace-nowrap">${d}</td>
                            <td class="px-4 py-3 font-semibold text-on-surface">
                                ${it.nama_item}
                                ${it.ket_transaksi ? `<span class="block text-xs font-normal text-on-surface-variant">${it.ket_transaksi}</span>` : ''}
                            </td>
                            <td class="px-4 py-3 text-center font-medium">${it.jumlah_qty}</td>
                            <td class="px-4 py-3 text-right text-on-surface-variant">${Utils.formatRupiah(it.harga_satuan)}</td>
                            <td class="px-4 py-3 text-right font-bold text-secondary">${Utils.formatRupiah(it.subtotal)}</td>
                        </tr>
                    `;
                }).join('');
            }
        }
    },

    openAddUsahaModal() {
        document.getElementById('modalUsahaTitle').innerText = 'Tambah Usaha Baru';
        document.getElementById('formUsahaId').value = '';
        document.getElementById('formUsahaNama').value = '';
        document.getElementById('formUsahaKet').value = '';
        document.getElementById('formUsahaStatus').value = 'aktif';
        this.openModal('usahaModal');
    },

    openEditUsahaModal(id) {
        const businesses = DB.getBusinesses();
        const u = businesses.find(x => x.id === id);
        if (!u) return;

        document.getElementById('modalUsahaTitle').innerText = 'Edit Usaha';
        document.getElementById('formUsahaId').value = u.id;
        document.getElementById('formUsahaNama').value = u.nama_usaha;
        document.getElementById('formUsahaKet').value = u.keterangan || '';
        document.getElementById('formUsahaStatus').value = u.status;
        this.openModal('usahaModal');
    },

    saveUsahaForm(e) {
        e.preventDefault();
        const id = document.getElementById('formUsahaId').value;
        const nama_usaha = document.getElementById('formUsahaNama').value.trim();
        const keterangan = document.getElementById('formUsahaKet').value.trim();
        const status = document.getElementById('formUsahaStatus').value;

        if (!nama_usaha) {
            Utils.showToast('Nama usaha wajib diisi.', 'error');
            return;
        }

        const businesses = DB.getBusinesses();
        if (id) {
            const idx = businesses.findIndex(x => x.id === parseInt(id, 10));
            if (idx !== -1) {
                businesses[idx] = { ...businesses[idx], nama_usaha, keterangan, status };
                Utils.showToast('Usaha berhasil diperbarui.');
            }
        } else {
            const newId = businesses.length > 0 ? Math.max(...businesses.map(b => b.id)) + 1 : 1;
            businesses.push({ id: newId, nama_usaha, keterangan, status });
            Utils.showToast('Usaha berhasil ditambahkan.');
        }

        DB.saveBusinesses(businesses);
        this.closeModal('usahaModal');
        this.renderUsaha();
    },

    deleteUsaha(id) {
        if (!confirm('Yakin menghapus usaha ini? Riwayat transaksi akan tetap tersimpan.')) return;

        let businesses = DB.getBusinesses();
        businesses = businesses.filter(b => b.id !== id);
        DB.saveBusinesses(businesses);
        Utils.showToast('Usaha berhasil dihapus.');
        this.renderUsaha();
    },

    // -------------------------------------------------------------------------
    // 4. KELOLA KATEGORI VIEW
    // -------------------------------------------------------------------------
    renderKategori() {
        const categories = DB.getCategories();
        const pemasukan = categories.filter(c => c.tipe === 'pemasukan');
        const pengeluaran = categories.filter(c => c.tipe === 'pengeluaran');

        const inContainer = document.getElementById('kategoriPemasukanContainer');
        const outContainer = document.getElementById('kategoriPengeluaranContainer');

        if (inContainer) {
            inContainer.innerHTML = pemasukan.map(k => `
                <div class="py-3 flex items-center justify-between hover:bg-surface-container-low px-3 rounded-xl transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary text-sm">label</span>
                        <span class="font-medium text-sm text-on-surface">${k.nama_kategori}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button onclick="App.openEditKategoriModal(${k.id})" class="p-1.5 text-on-surface-variant hover:text-tertiary rounded-lg hover:bg-surface-container-high transition-colors" title="Edit"><span class="material-symbols-outlined text-lg">edit</span></button>
                        <button onclick="App.deleteKategori(${k.id})" class="p-1.5 text-on-surface-variant hover:text-secondary rounded-lg hover:bg-surface-container-high transition-colors" title="Hapus"><span class="material-symbols-outlined text-lg">delete</span></button>
                    </div>
                </div>
            `).join('');
        }

        if (outContainer) {
            outContainer.innerHTML = pengeluaran.map(k => `
                <div class="py-3 flex items-center justify-between hover:bg-surface-container-low px-3 rounded-xl transition-colors">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-secondary text-sm">label</span>
                        <span class="font-medium text-sm text-on-surface">${k.nama_kategori}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button onclick="App.openEditKategoriModal(${k.id})" class="p-1.5 text-on-surface-variant hover:text-tertiary rounded-lg hover:bg-surface-container-high transition-colors" title="Edit"><span class="material-symbols-outlined text-lg">edit</span></button>
                        <button onclick="App.deleteKategori(${k.id})" class="p-1.5 text-on-surface-variant hover:text-secondary rounded-lg hover:bg-surface-container-high transition-colors" title="Hapus"><span class="material-symbols-outlined text-lg">delete</span></button>
                    </div>
                </div>
            `).join('');
        }
    },

    openAddKategoriModal() {
        document.getElementById('modalKategoriTitle').innerText = 'Tambah Kategori Baru';
        document.getElementById('formKategoriId').value = '';
        document.getElementById('formKategoriNama').value = '';
        document.getElementById('formKategoriTipe').value = 'pemasukan';
        this.openModal('kategoriModal');
    },

    openEditKategoriModal(id) {
        const categories = DB.getCategories();
        const k = categories.find(x => x.id === id);
        if (!k) return;

        document.getElementById('modalKategoriTitle').innerText = 'Edit Kategori';
        document.getElementById('formKategoriId').value = k.id;
        document.getElementById('formKategoriNama').value = k.nama_kategori;
        document.getElementById('formKategoriTipe').value = k.tipe;
        this.openModal('kategoriModal');
    },

    saveKategoriForm(e) {
        e.preventDefault();
        const id = document.getElementById('formKategoriId').value;
        const nama_kategori = document.getElementById('formKategoriNama').value.trim();
        const tipe = document.getElementById('formKategoriTipe').value;

        if (!nama_kategori) {
            Utils.showToast('Nama kategori wajib diisi.', 'error');
            return;
        }

        const categories = DB.getCategories();
        if (id) {
            const idx = categories.findIndex(x => x.id === parseInt(id, 10));
            if (idx !== -1) {
                categories[idx] = { ...categories[idx], nama_kategori, tipe };
                Utils.showToast('Kategori berhasil diperbarui.');
            }
        } else {
            const newId = categories.length > 0 ? Math.max(...categories.map(c => c.id)) + 1 : 1;
            categories.push({ id: newId, nama_kategori, tipe });
            Utils.showToast('Kategori berhasil ditambahkan.');
        }

        DB.saveCategories(categories);
        this.closeModal('kategoriModal');
        this.renderKategori();
    },

    deleteKategori(id) {
        const transactions = DB.getTransactions();
        const used = transactions.some(t => t.kategori_id === id);
        if (used) {
            Utils.showToast('Kategori tidak dapat dihapus karena sudah dipakai dalam transaksi.', 'error');
            return;
        }

        if (!confirm('Yakin ingin menghapus kategori ini?')) return;

        let categories = DB.getCategories();
        categories = categories.filter(c => c.id !== id);
        DB.saveCategories(categories);
        Utils.showToast('Kategori berhasil dihapus.');
        this.renderKategori();
    },

    // -------------------------------------------------------------------------
    // 5. LAPORAN BULANAN VIEW (Including EXCEL EXPORT)
    // -------------------------------------------------------------------------
    renderLaporan() {
        const now = new Date();
        const monthSel = document.getElementById('filterLaporanBulan');
        const yearSel = document.getElementById('filterLaporanTahun');

        const selectedMonth = monthSel ? parseInt(monthSel.value || (now.getMonth() + 1), 10) : (now.getMonth() + 1);
        const selectedYear = yearSel ? parseInt(yearSel.value || now.getFullYear(), 10) : now.getFullYear();

        document.getElementById('laporanPeriodeTitle').innerText = `Laporan Periode ${Utils.getNamaBulan(selectedMonth)} ${selectedYear}`;

        const transactions = DB.getTransactions();
        const categories = DB.getCategories();
        const businesses = DB.getBusinesses();

        let totalIn = 0;
        let totalOut = 0;
        let totalGaji = 0;
        const categorySpending = {};
        const businessSummary = {};

        transactions.forEach(t => {
            const d = new Date(t.tanggal);
            if (d.getMonth() + 1 === selectedMonth && d.getFullYear() === selectedYear) {
                const amt = parseFloat(t.jumlah) || 0;
                if (t.tipe === 'pemasukan') {
                    totalIn += amt;
                    const cat = categories.find(c => c.id === t.kategori_id);
                    if (cat && cat.nama_kategori.toLowerCase().includes('gaji')) {
                        totalGaji += amt;
                    }
                } else {
                    totalOut += amt;
                    const cat = categories.find(c => c.id === t.kategori_id);
                    const catName = cat ? cat.nama_kategori : 'Lainnya';
                    categorySpending[catName] = (categorySpending[catName] || 0) + amt;
                }

                if (t.usaha_id) {
                    if (!businessSummary[t.usaha_id]) {
                        businessSummary[t.usaha_id] = { pemasukan: 0, pengeluaran: 0 };
                    }
                    if (t.tipe === 'pemasukan') businessSummary[t.usaha_id].pemasukan += amt;
                    else businessSummary[t.usaha_id].pengeluaran += amt;
                }
            }
        });

        const surplus = totalIn - totalOut;
        const sisaGaji = totalGaji - totalOut;

        document.getElementById('laporanTotalPemasukan').innerText = Utils.formatRupiah(totalIn);
        document.getElementById('laporanTotalPengeluaran').innerText = Utils.formatRupiah(totalOut);
        document.getElementById('laporanSurplus').innerText = Utils.formatRupiah(surplus);
        document.getElementById('laporanSurplus').className = `text-2xl font-bold mt-1 ${surplus >= 0 ? 'text-emerald-700' : 'text-rose-700'}`;

        document.getElementById('laporanSisaGaji').innerText = Utils.formatRupiah(sisaGaji);

        // Gaji vs Pengeluaran Breakdown Table
        const gajiTbody = document.getElementById('laporanGajiTableBody');
        if (gajiTbody) {
            let html = `
                <tr class="bg-emerald-50/60 font-semibold">
                    <td class="px-4 py-3 text-emerald-900">Total Gaji Masuk</td>
                    <td class="px-4 py-3 text-right text-emerald-900">${Utils.formatRupiah(totalGaji)}</td>
                    <td class="px-4 py-3 text-right text-emerald-900">100%</td>
                </tr>
            `;

            const entries = Object.entries(categorySpending).sort((a, b) => b[1] - a[1]);
            if (entries.length === 0) {
                html += '<tr><td colspan="3" class="px-4 py-4 text-center text-on-surface-variant italic">Belum ada pengeluaran dicatat pada bulan ini.</td></tr>';
            } else {
                entries.forEach(([cat, amt]) => {
                    const pct = totalGaji > 0 ? Math.round((amt / totalGaji) * 100) : 0;
                    html += `
                        <tr class="hover:bg-surface-container-low">
                            <td class="px-4 py-2.5 font-medium text-on-surface">${cat}</td>
                            <td class="px-4 py-2.5 text-right text-secondary font-semibold">${Utils.formatRupiah(amt)}</td>
                            <td class="px-4 py-2.5 text-right font-medium text-on-surface-variant">${pct}%</td>
                        </tr>
                    `;
                });
            }
            gajiTbody.innerHTML = html;
        }

        // Laporan Per Kategori Table
        const catTbody = document.getElementById('laporanKategoriTableBody');
        if (catTbody) {
            const catEntries = Object.entries(categorySpending);
            if (catEntries.length === 0 && totalIn === 0) {
                catTbody.innerHTML = '<tr><td colspan="3" class="px-4 py-4 text-center text-on-surface-variant italic">Belum ada transaksi di bulan ini.</td></tr>';
            } else {
                catTbody.innerHTML = catEntries.map(([cat, amt]) => `
                    <tr class="hover:bg-surface-container-low">
                        <td class="px-4 py-2.5 font-medium text-on-surface">${cat}</td>
                        <td class="px-4 py-2.5"><span class="px-2 py-0.5 text-[11px] font-semibold rounded-full bg-rose-100 text-rose-800">Pengeluaran</span></td>
                        <td class="px-4 py-2.5 text-right font-bold text-secondary">${Utils.formatRupiah(amt)}</td>
                    </tr>
                `).join('');
            }
        }

        // Laporan Per Usaha Table
        const bizTbody = document.getElementById('laporanUsahaTableBody');
        if (bizTbody) {
            if (businesses.length === 0) {
                bizTbody.innerHTML = '<tr><td colspan="4" class="px-4 py-4 text-center text-on-surface-variant italic">Belum ada data usaha.</td></tr>';
            } else {
                bizTbody.innerHTML = businesses.map(b => {
                    const stats = businessSummary[b.id] || { pemasukan: 0, pengeluaran: 0 };
                    const net = stats.pemasukan - stats.pengeluaran;
                    return `
                        <tr class="hover:bg-surface-container-low">
                            <td class="px-4 py-2.5 font-medium text-on-surface">${b.nama_usaha}</td>
                            <td class="px-4 py-2.5 text-right text-emerald-700 font-semibold">${Utils.formatRupiah(stats.pemasukan)}</td>
                            <td class="px-4 py-2.5 text-right text-rose-700 font-semibold">${Utils.formatRupiah(stats.pengeluaran)}</td>
                            <td class="px-4 py-2.5 text-right font-bold ${net >= 0 ? 'text-primary' : 'text-secondary'}">${Utils.formatRupiah(net)}</td>
                        </tr>
                    `;
                }).join('');
            }
        }

        // Render Doughnut Chart
        this.renderReportSalaryChart(categorySpending);
    },

    renderReportSalaryChart(categorySpending) {
        const labels = Object.keys(categorySpending);
        const values = Object.values(categorySpending);
        const ctx = document.getElementById('reportSalaryDoughnutCanvas');
        if (!ctx) return;

        if (reportSalaryChartInstance) reportSalaryChartInstance.destroy();

        if (labels.length === 0) {
            reportSalaryChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Tidak ada data'],
                    datasets: [{ data: [1], backgroundColor: ['#edeeef'] }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });
            return;
        }

        reportSalaryChartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data: values,
                    backgroundColor: ['#0d631b', '#b6171e', '#00569f', '#d97706', '#7c3aed', '#059669', '#db2777', '#0891b2'],
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
    },

    // -------------------------------------------------------------------------
    // EXPORT TO EXCEL (.xlsx) WITH SHEETJS
    // -------------------------------------------------------------------------
    exportMonthlyExcel() {
        if (typeof XLSX === 'undefined') {
            Utils.showToast('Pustaka SheetJS belum termuat.', 'error');
            return;
        }

        const now = new Date();
        const monthSel = document.getElementById('filterLaporanBulan');
        const yearSel = document.getElementById('filterLaporanTahun');

        const selectedMonth = monthSel ? parseInt(monthSel.value || (now.getMonth() + 1), 10) : (now.getMonth() + 1);
        const selectedYear = yearSel ? parseInt(yearSel.value || now.getFullYear(), 10) : now.getFullYear();
        const monthName = Utils.getNamaBulan(selectedMonth);

        const transactions = DB.getTransactions();
        const categories = DB.getCategories();
        const businesses = DB.getBusinesses();

        // 1. Sheet: Daftar Transaksi Bulan Ini
        const txRows = [
            ['No', 'Tanggal', 'Tipe', 'Kategori', 'Usaha', 'Keterangan', 'Jumlah (Rp)', 'Rincian Item']
        ];

        let index = 1;
        let totalPemasukan = 0;
        let totalPengeluaran = 0;

        const filteredTx = transactions.filter(t => {
            const d = new Date(t.tanggal);
            return (d.getMonth() + 1 === selectedMonth && d.getFullYear() === selectedYear);
        }).sort((a, b) => new Date(a.tanggal) - new Date(b.tanggal));

        filteredTx.forEach(t => {
            const cat = categories.find(c => c.id === t.kategori_id);
            const biz = businesses.find(b => b.id === t.usaha_id);
            const amt = parseFloat(t.jumlah) || 0;

            if (t.tipe === 'pemasukan') totalPemasukan += amt;
            else totalPengeluaran += amt;

            let itemSummary = '-';
            if (t.items && t.items.length > 0) {
                itemSummary = t.items.map(i => `${i.nama_item} (${i.jumlah_qty}x @${i.harga_satuan} = ${i.subtotal})`).join('; ');
            }

            txRows.push([
                index++,
                t.tanggal,
                t.tipe.toUpperCase(),
                cat ? cat.nama_kategori : '-',
                biz ? biz.nama_usaha : '-',
                t.keterangan || '-',
                amt,
                itemSummary
            ]);
        });

        // 2. Sheet: Ringkasan & Gaji
        let totalGaji = 0;
        const catBreakdown = {};

        filteredTx.forEach(t => {
            const cat = categories.find(c => c.id === t.kategori_id);
            const catName = cat ? cat.nama_kategori : 'Lainnya';
            if (t.tipe === 'pemasukan' && catName.toLowerCase().includes('gaji')) {
                totalGaji += parseFloat(t.jumlah) || 0;
            } else if (t.tipe === 'pengeluaran') {
                catBreakdown[catName] = (catBreakdown[catName] || 0) + (parseFloat(t.jumlah) || 0);
            }
        });

        const summaryRows = [
            ['LAPORAN KEUANGAN BULANAN'],
            ['Periode', `${monthName} ${selectedYear}`],
            [''],
            ['RINGKASAN ARUS KAS'],
            ['Total Pemasukan', totalPemasukan],
            ['Total Pengeluaran', totalPengeluaran],
            ['Surplus / Defisit', totalPemasukan - totalPengeluaran],
            [''],
            ['ANALISIS PENGGUNAAN GAJI'],
            ['Total Gaji Masuk', totalGaji],
            ['Total Pengeluaran Bulan Ini', totalPengeluaran],
            ['Sisa Gaji', totalGaji - totalPengeluaran],
            ['Persentase Terpakai (%)', totalGaji > 0 ? Math.round((totalPengeluaran / totalGaji) * 100) : 0],
            [''],
            ['RINCIAN PENGELUARAN PER KATEGORI', 'Nominal (Rp)', '% Dari Gaji']
        ];

        Object.entries(catBreakdown).forEach(([cat, amt]) => {
            const pct = totalGaji > 0 ? Math.round((amt / totalGaji) * 100) + '%' : '0%';
            summaryRows.push([cat, amt, pct]);
        });

        // 3. Sheet: Rincian Item Usaha
        const itemRows = [
            ['Tanggal', 'Nama Usaha', 'Nama Item', 'Qty', 'Harga Satuan (Rp)', 'Subtotal (Rp)', 'Keterangan Transaksi']
        ];

        filteredTx.filter(t => t.usaha_id && t.items && t.items.length > 0).forEach(t => {
            const biz = businesses.find(b => b.id === t.usaha_id);
            t.items.forEach(it => {
                itemRows.push([
                    t.tanggal,
                    biz ? biz.nama_usaha : '-',
                    it.nama_item,
                    it.jumlah_qty,
                    it.harga_satuan,
                    it.subtotal,
                    t.keterangan || '-'
                ]);
            });
        });

        // Create Workbook
        const wb = XLSX.utils.book_new();

        const wsSummary = XLSX.utils.aoa_to_sheet(summaryRows);
        const wsTx = XLSX.utils.aoa_to_sheet(txRows);
        const wsItems = XLSX.utils.aoa_to_sheet(itemRows);

        XLSX.utils.book_append_sheet(wb, wsSummary, 'Ringkasan Bulanan');
        XLSX.utils.book_append_sheet(wb, wsTx, 'Daftar Transaksi');
        XLSX.utils.book_append_sheet(wb, wsItems, 'Rincian Item Usaha');

        // Download Excel File
        const fileName = `Laporan_Keuangan_${monthName}_${selectedYear}.xlsx`;
        XLSX.writeFile(wb, fileName);

        Utils.showToast(`Berhasil mengekspor ${fileName}`, 'success');
    },

    // -------------------------------------------------------------------------
    // BACKUP & RESTORE JSON
    // -------------------------------------------------------------------------
    backupJSON() {
        const data = {
            categories: DB.getCategories(),
            businesses: DB.getBusinesses(),
            transactions: DB.getTransactions(),
            exportedAt: new Date().toISOString()
        };

        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `backup_keuangan_${getTodayDateString()}.json`;
        a.click();
        URL.revokeObjectURL(url);
        Utils.showToast('Backup data berhasil diunduh.');
    },

    restoreJSON(inputElement) {
        const file = inputElement.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const data = JSON.parse(e.target.result);
                if (data.categories && data.businesses && data.transactions) {
                    DB.saveCategories(data.categories);
                    DB.saveBusinesses(data.businesses);
                    DB.saveTransactions(data.transactions);
                    Utils.showToast('Data berhasil dipulihkan dari file backup!');
                    this.renderView(this.currentView);
                } else {
                    Utils.showToast('Format file backup tidak valid.', 'error');
                }
            } catch (err) {
                Utils.showToast('Gagal membaca file JSON.', 'error');
            }
        };
        reader.readAsText(file);
    },

    // Modal Helpers
    openModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) {
            m.classList.remove('hidden');
            m.classList.add('flex');
        }
    },

    closeModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) {
            m.classList.add('hidden');
            m.classList.remove('flex');
        }
    }
};

// Global initialization
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
