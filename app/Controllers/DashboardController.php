<?php

namespace App\Controllers;

class DashboardController extends BaseController
{
    public function index(): string
    {
        return view('dashboard/index', [
            'title' => 'Dashboard',
            'metrics' => [
                ['label' => 'Cotizaciones pendientes', 'value' => 0],
                ['label' => 'Trabajos en proceso', 'value' => 0],
                ['label' => 'Pendientes de facturar', 'value' => 0],
                ['label' => 'Cobros pendientes', 'value' => 0],
            ],
        ]);
    }
}
