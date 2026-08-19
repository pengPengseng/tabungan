<?php
/**
 * Vercel PHP Router - Single Entrypoint
 * All requests routed through this file.
 */

// Base app directory (one level up from /api)
define('BASE_DIR', __DIR__ . '/..');

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);
$path = rtrim($path, '/');
if ($path === '') $path = '/';

// Route mapping
$routes = [
    '/'                            => 'pages/dashboard.php',
    '/dashboard'                   => 'pages/dashboard.php',
    '/pages/dashboard.php'         => 'pages/dashboard.php',
    '/transaksi'                   => 'pages/transaksi.php',
    '/pages/transaksi.php'         => 'pages/transaksi.php',
    '/usaha'                       => 'pages/usaha.php',
    '/pages/usaha.php'             => 'pages/usaha.php',
    '/laporan'                     => 'pages/laporan.php',
    '/pages/laporan.php'           => 'pages/laporan.php',
    '/kategori'                    => 'pages/kategori.php',
    '/pages/kategori.php'          => 'pages/kategori.php',
    '/actions/transaksi_action.php' => 'actions/transaksi_action.php',
    '/actions/kategori_action.php'  => 'actions/kategori_action.php',
    '/actions/usaha_action.php'     => 'actions/usaha_action.php',
    '/actions/get_items.php'        => 'actions/get_items.php',
];

$relative = $routes[$path] ?? null;

// Also try matching without .php stripped
if ($relative === null && !str_ends_with($path, '.php')) {
    $relative = $routes[$path . '.php'] ?? null;
}

// Default: dashboard
if ($relative === null) {
    $relative = 'pages/dashboard.php';
}

$target_file = BASE_DIR . '/' . $relative;

// Fix $_SERVER so included files see the correct current page for nav highlighting
$_SERVER['SCRIPT_FILENAME'] = $target_file;
$_SERVER['PHP_SELF'] = '/' . $relative;
$_SERVER['SCRIPT_NAME'] = '/' . $relative;

if (file_exists($target_file)) {
    require $target_file;
} else {
    http_response_code(404);
    echo "<!DOCTYPE html><html><body><h1>404 - Halaman Tidak Ditemukan</h1><p>File <code>{$relative}</code> tidak ditemukan.</p></body></html>";
}
