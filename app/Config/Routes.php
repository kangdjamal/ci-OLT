<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
//$routes->get('/', 'Home::index');
//$
//$
// Halaman Utama & Login
$routes->get('/', 'Olt::index');
$routes->get('olt', 'Olt::index'); // Route tambahan agar seragam

// Proses Auth & Logout
$routes->post('olt/auth', 'Olt::auth');
$routes->get('olt/logout', 'Olt::logout');

$routes->get('/', 'Olt::index');
$routes->post('olt/dashboard', 'Olt::dashboard');
$routes->get('olt/dashboard', 'Olt::dashboard');
$routes->post('olt/sync', 'Olt::sync');
$routes->get('olt/settings', 'Olt::settings');
$routes->post('olt/clear_database', 'Olt::clear_database');

// Ubah dari (:segment) menjadi (:any)
$routes->post('olt/update_card/(:any)', 'Olt::update_card/$1');

$routes->get('olt/unconfig', 'Olt::unconfig');
$routes->get('olt/auth_page/(:any)/(:any)', 'Olt::auth_page/$1/$2');
$routes->post('olt/activate_process', 'Olt::activate_process');
// app/Config/Routes.php
// Tambah (:any) satu lagi di ujung, dan $3 di ujungnya juga
$routes->match(['get', 'post'], 'olt/auth_page/(:any)/(:any)/(:any)', 'Olt::auth_page/$1/$2/$3');

// Format: $routes->get('url-di-browser', 'NamaController::NamaFunction/$1');
$routes->get('olt/manage/(:any)', 'Olt::manage/$1');

//Tombol simpan perubahan di halaman manage onu
$routes->post('olt/update_config', 'Olt::update_config');


//tombol HAPUS ONU
$routes->post('olt/delete_onu', 'Olt::delete_onu');
$routes->get('olt/unconfig', 'Olt::unconfig');
