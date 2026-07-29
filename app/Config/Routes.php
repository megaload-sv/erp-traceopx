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
});
