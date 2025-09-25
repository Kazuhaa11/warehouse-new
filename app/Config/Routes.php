<?php

use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */

// Landing: login page
$routes->get('/', 'Api\AuthApiController::loginPage');

// AUTH API
$routes->group('api/v1/auth', ['namespace' => 'App\Controllers\Api'], static function ($r) {
    $r->post('login', 'AuthApiController::login');
    $r->post('refresh', 'AuthApiController::refresh');
    // gunakan alias gabungan 'auth' (jwtcookie + jwt)
    $r->post('logout', 'AuthApiController::logout', ['filter' => 'auth']);
    $r->get('me', 'AuthApiController::me', ['filter' => 'auth']);
});

// ADMIN PAGES (HTML) → butuh admin
$routes->group('admin', ['filter' => 'authadmin'], static function ($routes) {
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('barang', 'BarangController::index');
    $routes->get('peminjaman', 'PeminjamanController::index');
    $routes->get('opname', 'OpnameController::index');
    $routes->get('opname/(:num)', 'OpnameController::items/$1');
    $routes->get('storages', 'StorageController::index');
    $routes->get('import-export', 'ImportExportController::index');
});

// BARANG API (admin)
$routes->group('api/v1', ['filter' => 'authadmin'], static function ($routes) {
    $routes->get('barang', 'Api\BarangApi::index');
    $routes->post('barang/import', 'Api\BarangApi::import');
    $routes->get('barang/export', 'Api\BarangApi::export');
    $routes->post('barang/create', 'Api\BarangApi::create');
    $routes->get('barang/(:num)', 'Api\BarangApi::show/$1');
    $routes->put('barang/(:num)', 'Api\BarangApi::update/$1');
    $routes->delete('barang/(:num)', 'Api\BarangApi::delete/$1');
});

// STATS API (admin)
$routes->group('api/v1', ['filter' => 'authadmin'], static function ($routes) {
    $routes->get('stats/dashboard', 'Api\StatsApi::dashboard');
    $routes->get('stats/material', 'Api\StatsChartsApi::material');
    $routes->get('stats/peminjaman', 'Api\StatsChartsApi::peminjaman');
    $routes->get('stats/stock-opname', 'Api\StatsChartsApi::stockOpname');
});

// PEMINJAMAN API
$routes->group('api/v1', static function ($routes) {
    $routes->get('peminjaman', 'Api\PeminjamanApi::index', ['filter' => 'authadmin']);
    $routes->post('peminjaman', 'Api\PeminjamanApi::create', ['filter' => 'authmobile']);
    $routes->get('peminjaman/(:num)', 'Api\PeminjamanApi::show/$1', ['filter' => 'authadmin']);
    $routes->get('peminjaman/report/pdf', 'Api\PeminjamanApi::reportPdf', ['filter' => 'authadmin']);
});

// STORAGES API (admin)
$routes->group('api/v1', [
    'namespace' => 'App\Controllers\Api',
    'filter' => 'authadmin',
], static function ($routes) {
    $routes->get('storages', 'StorageControllerApi::index');
    $routes->post('storages', 'StorageControllerApi::store');
    $routes->get('storages/(:num)', 'StorageControllerApi::show/$1');
    $routes->put('storages/(:num)', 'StorageControllerApi::update/$1');
    $routes->delete('storages/(:num)', 'StorageControllerApi::delete/$1');
    $routes->get('storages/presets/storage-location-desc', 'StorageControllerApi::presetStorLocDesc');
});

// STOCK OPNAME API (admin)
$routes->group('api/v1/stock-opname', [
    'namespace' => 'App\Controllers\Api',
    'filter' => 'authadmin',
], static function ($routes) {
    $routes->get('sessions', 'StockOpnameController::index');
    $routes->post('sessions', 'StockOpnameController::create');
    $routes->get('sessions/(:num)', 'StockOpnameController::show/$1');
    $routes->post('sessions/(:num)/finalize', 'StockOpnameController::finalize/$1');

    $routes->get('sessions/(:num)/items', 'StockOpnameController::items/$1');
    $routes->post('sessions/(:num)/items', 'StockOpnameController::storeItem/$1');
    $routes->put('sessions/(:num)/items/(:num)', 'StockOpnameController::updateItem/$1/$2');
    $routes->delete('sessions/(:num)/items/(:num)', 'StockOpnameController::deleteItem/$1/$2');
    $routes->post('sessions/(:num)/items/import', 'StockOpnameController::importItems/$1');

    $routes->get('sessions/(:num)/recap', 'StockOpnameController::recap/$1');
    $routes->get('sessions/(:num)/export', 'StockOpnameController::export/$1');
});
