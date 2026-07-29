<?php

namespace App\Controllers;

class DashboardController extends BaseController
{
    public function index(): string
    {
        return view('dashboard/index', [
            'title' => 'Dashboard',
            'metrics' => [
                ['label' => 'Cotizaciones del mes', 'value' => 24, 'detail' => '7 pendientes de aprobación'],
                ['label' => 'Órdenes en ejecución', 'value' => 9, 'detail' => '2 con atención prioritaria'],
                ['label' => 'Pendientes de facturar', 'value' => 5, 'detail' => 'US$ 6,480.00 estimados'],
                ['label' => 'Cobros pendientes', 'value' => 8, 'detail' => 'US$ 3,250.00 por recuperar'],
            ],
            'workflow' => [
                ['label' => 'Solicitudes', 'value' => 12],
                ['label' => 'Cotizaciones', 'value' => 7],
                ['label' => 'Aprobadas', 'value' => 5],
                ['label' => 'Órdenes', 'value' => 9],
                ['label' => 'Finalizadas', 'value' => 6],
                ['label' => 'Facturación', 'value' => 5],
                ['label' => 'Cobros', 'value' => 8],
            ],
            'alerts' => [
                ['title' => 'Órdenes retrasadas', 'detail' => '2 órdenes superaron su fecha estimada.'],
                ['title' => 'Cotizaciones por vencer', 'detail' => '4 cotizaciones vencen en los próximos 3 días.'],
                ['title' => 'Facturas pendientes', 'detail' => '3 trabajos completados aún no han sido facturados.'],
            ],
            'recentActivity' => [
                ['time' => '09:20', 'text' => 'Se creó la cotización COT-2026-0024 para Cliente Demo, S.A. de C.V.'],
                ['time' => '10:05', 'text' => 'La orden OT-2026-0018 pasó a estado En ejecución.'],
                ['time' => '11:40', 'text' => 'El trabajo OT-2026-0014 fue marcado como completado.'],
                ['time' => '13:15', 'text' => 'Se registró un pago parcial para la factura FE-2026-0108.'],
            ],
            'latestQuotations' => [
                ['code' => 'COT-2026-0024', 'customer' => 'Cliente Demo, S.A. de C.V.', 'amount' => 'US$ 2,450.00', 'status' => 'Pendiente'],
                ['code' => 'COT-2026-0023', 'customer' => 'Distribuidora Central', 'amount' => 'US$ 1,875.00', 'status' => 'Aprobada'],
                ['code' => 'COT-2026-0022', 'customer' => 'Servicios Industriales SV', 'amount' => 'US$ 3,920.00', 'status' => 'En revisión'],
            ],
        ]);
    }
}
