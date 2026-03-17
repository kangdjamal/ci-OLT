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
