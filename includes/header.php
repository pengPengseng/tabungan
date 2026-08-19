<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= isset($page_title) ? $page_title . ' - Keuangan' : 'Keuangan - Smart Finance Tracker'; ?></title>
    
    <!-- Tailwind CSS CDN with Plugins -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Google Fonts & Material Symbols -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom App JS -->
    <script src="/assets/js/script.js"></script>

    <!-- Tailwind Config matching Stitch Fiscal Precision Design -->
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#0d631b",
                        "primary-container": "#2e7d32",
                        "on-primary": "#ffffff",
                        "on-primary-container": "#cbffc2",
                        "primary-fixed": "#a3f69c",
                        "secondary": "#b6171e",
                        "secondary-container": "#da3433",
                        "on-secondary": "#ffffff",
                        "on-secondary-container": "#fffbff",
                        "tertiary": "#00569f",
                        "tertiary-container": "#006eca",
                        "background": "#f8f9fa",
                        "surface": "#f8f9fa",
                        "surface-container-lowest": "#ffffff",
                        "surface-container-low": "#f3f4f5",
                        "surface-container": "#edeeef",
                        "surface-container-high": "#e7e8e9",
                        "surface-container-highest": "#e1e3e4",
                        "surface-variant": "#e1e3e4",
                        "on-background": "#191c1d",
                        "on-surface": "#191c1d",
                        "on-surface-variant": "#40493d",
                        "outline": "#707a6c",
                        "outline-variant": "#bfcaba"
                    },
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "md": "0.375rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "2xl": "1rem",
                        "full": "9999px"
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        'body-md': ['Inter', 'sans-serif'],
                        'numeric-display': ['Inter', 'sans-serif'],
                        'headline-lg': ['Inter', 'sans-serif'],
                        'title-md': ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; color: #191c1d; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }
        .icon-fill {
            font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f3f5; }
        ::-webkit-scrollbar-thumb { background: #bfcaba; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #707a6c; }
    </style>
</head>
<body class="flex flex-col lg:flex-row min-h-screen bg-background text-on-background antialiased">

<!-- Desktop SideNav -->
<aside class="hidden lg:flex flex-col h-screen p-4 border-r border-outline-variant bg-surface-container-lowest shadow-sm w-64 fixed left-0 top-0 z-40">
    <div class="mb-8 px-3 py-2 flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-on-primary font-bold shadow">
            <span class="material-symbols-outlined text-2xl">account_balance_wallet</span>
        </div>
        <div>
            <h2 class="font-bold text-lg text-primary tracking-tight">Keuangan</h2>
            <p class="text-xs text-on-surface-variant font-medium">Personal Finance</p>
        </div>
    </div>
    
    <nav class="flex-1 space-y-1.5">
        <a href="/pages/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 <?= ($current_page == 'dashboard.php' || $current_page == 'index.php') ? 'bg-primary-container text-on-primary-container font-semibold shadow-sm translate-x-1' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'; ?>">
            <span class="material-symbols-outlined <?= ($current_page == 'dashboard.php' || $current_page == 'index.php') ? 'icon-fill' : ''; ?>">dashboard</span>
            Dashboard
        </a>
        <a href="/pages/transaksi.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 <?= ($current_page == 'transaksi.php') ? 'bg-primary-container text-on-primary-container font-semibold shadow-sm translate-x-1' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'; ?>">
            <span class="material-symbols-outlined <?= ($current_page == 'transaksi.php') ? 'icon-fill' : ''; ?>">receipt_long</span>
            Transaksi
        </a>
        <a href="/pages/usaha.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 <?= ($current_page == 'usaha.php') ? 'bg-primary-container text-on-primary-container font-semibold shadow-sm translate-x-1' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'; ?>">
            <span class="material-symbols-outlined <?= ($current_page == 'usaha.php') ? 'icon-fill' : ''; ?>">storefront</span>
            Usaha Saya
        </a>
        <a href="/pages/laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 <?= ($current_page == 'laporan.php') ? 'bg-primary-container text-on-primary-container font-semibold shadow-sm translate-x-1' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'; ?>">
            <span class="material-symbols-outlined <?= ($current_page == 'laporan.php') ? 'icon-fill' : ''; ?>">assessment</span>
            Laporan
        </a>
        <a href="/pages/kategori.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm transition-all duration-200 <?= ($current_page == 'kategori.php') ? 'bg-primary-container text-on-primary-container font-semibold shadow-sm translate-x-1' : 'text-on-surface-variant hover:bg-surface-container-high hover:text-on-surface'; ?>">
            <span class="material-symbols-outlined <?= ($current_page == 'kategori.php') ? 'icon-fill' : ''; ?>">category</span>
            Kelola Kategori
        </a>
    </nav>
    
    <div class="mt-auto pt-4 space-y-2 border-t border-outline-variant">
        <a href="/pages/transaksi.php?action=new" class="w-full py-3 px-4 bg-primary text-on-primary rounded-xl font-semibold text-sm hover:bg-primary-container transition-all shadow flex items-center justify-center gap-2">
            <span class="material-symbols-outlined">add_circle</span>
            Tambah Transaksi
        </a>
    </div>
</aside>

<!-- Mobile Navigation Drawer Overlay & SideNav -->
<div id="mobileSidebar" class="fixed inset-0 bg-black/40 z-50 hidden transition-opacity lg:hidden">
    <div class="w-64 bg-surface-container-lowest h-full p-4 flex flex-col shadow-2xl transform transition-transform">
        <div class="flex items-center justify-between mb-6 px-2">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-2xl">account_balance_wallet</span>
                <span class="font-bold text-lg text-primary">Keuangan</span>
            </div>
            <button onclick="toggleMobileSidebar()" class="text-on-surface-variant p-1 rounded-lg hover:bg-surface-container">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <nav class="space-y-2 flex-1">
            <a href="/pages/dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm <?= ($current_page == 'dashboard.php') ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant'; ?>">
                <span class="material-symbols-outlined">dashboard</span> Dashboard
            </a>
            <a href="/pages/transaksi.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm <?= ($current_page == 'transaksi.php') ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant'; ?>">
                <span class="material-symbols-outlined">receipt_long</span> Transaksi
            </a>
            <a href="/pages/usaha.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm <?= ($current_page == 'usaha.php') ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant'; ?>">
                <span class="material-symbols-outlined">storefront</span> Usaha Saya
            </a>
            <a href="/pages/laporan.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm <?= ($current_page == 'laporan.php') ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant'; ?>">
                <span class="material-symbols-outlined">assessment</span> Laporan
            </a>
            <a href="/pages/kategori.php" class="flex items-center gap-3 px-4 py-3 rounded-xl font-medium text-sm <?= ($current_page == 'kategori.php') ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant'; ?>">
                <span class="material-symbols-outlined">category</span> Kelola Kategori
            </a>
        </nav>
    </div>
</div>

<!-- Main Content Area -->
<div class="flex-1 lg:ml-64 flex flex-col min-h-screen">
    <!-- Top Bar Header -->
    <header class="flex justify-between items-center px-4 sm:px-8 h-16 sticky top-0 z-30 bg-surface border-b border-outline-variant shadow-sm backdrop-blur-md bg-white/90">
        <div class="flex items-center gap-4">
            <button onclick="toggleMobileSidebar()" class="lg:hidden text-on-surface p-2 rounded-xl hover:bg-surface-container transition-colors">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="font-bold text-xl text-primary tracking-tight flex items-center gap-2">
                <?= isset($page_header) ? $page_header : 'Dashboard'; ?>
            </h1>
        </div>
        
        <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <p class="text-xs font-semibold text-on-surface"><?= date('d F Y'); ?></p>
                <p class="text-[11px] text-on-surface-variant">Lokal Server</p>
            </div>
            <div class="w-9 h-9 rounded-full bg-primary-container/30 border border-primary/20 flex items-center justify-center text-primary font-bold">
                <span class="material-symbols-outlined text-xl">person</span>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto space-y-6">
        
    <?php
    $flash = get_flash_message();
    if ($flash):
        $bg_color = 'bg-emerald-50 border-emerald-200 text-emerald-800';
        $icon = 'check_circle';
        if ($flash['type'] === 'error') {
            $bg_color = 'bg-rose-50 border-rose-200 text-rose-800';
            $icon = 'error';
        } elseif ($flash['type'] === 'warning') {
            $bg_color = 'bg-amber-50 border-amber-200 text-amber-800';
            $icon = 'warning';
        }
    ?>
        <div class="p-4 rounded-xl border <?= $bg_color; ?> flex items-center justify-between shadow-sm animate-fade-in">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-xl"><?= $icon; ?></span>
                <span class="text-sm font-medium"><?= htmlspecialchars($flash['message']); ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-current opacity-70 hover:opacity-100">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>
    <?php endif; ?>

