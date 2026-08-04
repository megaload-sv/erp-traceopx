<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('auth', static function (RouteCollection $routes): void {
    $routes->get('login', 'AuthController::login', ['as' => 'login']);
    $routes->post('login', 'AuthController::attempt', ['as' => 'login.attempt']);
    $routes->post('logout', 'AuthController::logout', ['as' => 'logout']);
});

$routes->group('system', static function (RouteCollection $routes): void {
    $routes->get('migrate', 'SystemController::migrate');
    $routes->get('seed/(:segment)', 'SystemController::seed/$1');
    $routes->get('setup', 'SystemController::setup');
});

$routes->group('', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'DashboardController::index', ['as' => 'dashboard']);

    $routes->group('commercial-requests', static function (RouteCollection $routes): void {
        $routes->get('', 'CommercialRequestsController::index', ['as' => 'commercial_requests.index']);
        $routes->get('create', 'CommercialRequestsController::create', ['as' => 'commercial_requests.create']);
        $routes->post('', 'CommercialRequestsController::store', ['as' => 'commercial_requests.store']);
    });

    $routes->group('customers', static function (RouteCollection $routes): void {
        $routes->get('', 'CustomersController::index', ['as' => 'customers.index']);
        $routes->get('create', 'CustomersController::create', ['as' => 'customers.create']);
        $routes->post('', 'CustomersController::store', ['as' => 'customers.store']);
        $routes->get('(:num)', 'CustomersController::show/$1', ['as' => 'customers.show']);
        $routes->get('(:num)/edit', 'CustomersController::edit/$1', ['as' => 'customers.edit']);
        $routes->post('(:num)', 'CustomersController::update/$1', ['as' => 'customers.update']);

        $routes->get('(:num)/contacts/create', 'CustomerContactsController::create/$1', ['as' => 'customers.contacts.create']);
        $routes->post('(:num)/contacts', 'CustomerContactsController::store/$1', ['as' => 'customers.contacts.store']);
        $routes->get('(:num)/contacts/(:num)/edit', 'CustomerContactsController::edit/$1/$2', ['as' => 'customers.contacts.edit']);
        $routes->post('(:num)/contacts/(:num)', 'CustomerContactsController::update/$1/$2', ['as' => 'customers.contacts.update']);
        $routes->post('(:num)/contacts/(:num)/primary', 'CustomerContactsController::makePrimary/$1/$2', ['as' => 'customers.contacts.primary']);

        $routes->get('(:num)/addresses/create', 'CustomerAddressesController::create/$1', ['as' => 'customers.addresses.create']);
        $routes->post('(:num)/addresses', 'CustomerAddressesController::store/$1', ['as' => 'customers.addresses.store']);
        $routes->get('(:num)/addresses/(:num)/edit', 'CustomerAddressesController::edit/$1/$2', ['as' => 'customers.addresses.edit']);
        $routes->post('(:num)/addresses/(:num)', 'CustomerAddressesController::update/$1/$2', ['as' => 'customers.addresses.update']);
        $routes->post('(:num)/addresses/(:num)/primary', 'CustomerAddressesController::makePrimary/$1/$2', ['as' => 'customers.addresses.primary']);
    });
});
