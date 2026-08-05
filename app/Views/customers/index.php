<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php
$stageLabels = ['potential' => 'Potencial', 'active' => 'Activo', 'inactive' => 'Inactivo'];
$tierLabels = ['standard' => 'Estándar', 'preferential' => 'Preferencial', 'strategic' => 'Estratégico'];
?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<?= view('components/workspace/page-header', [
    'eyebrow' => 'Relación comercial',
    'title' => 'Clientes',
    'description' => 'Encuentra clientes, identifica oportunidades de reactivación y administra la relación comercial desde un solo lugar.',
    'actionUrl' => route_to('customers.create'),
    'actionLabel' => 'Nuevo cliente',
]) ?>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
    <?php foreach ([
        ['Total', $metrics['total']],
        ['Activos', $metrics['active']],
        ['Potenciales', $metrics['potential']],
        ['Inactivos', $metrics['inactive']],
        ['Estratégicos', $metrics['strategic']],
    ] as [$label, $value]): ?>
        <?= view('components/workspace/kpi-card', ['label' => $label, 'value' => $value]) ?>
    <?php endforeach ?>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <?php if ($customers === []): ?>
        <div class="p-6">
            <?= view('components/workspace/empty-state', [
                'title' => $search !== '' ? 'No encontramos clientes con esos criterios.' : 'Aún no hay clientes registrados.',
                'description' => $search !== '' ? 'Prueba con otro nombre, código, documento, contacto o ejecutivo comercial.' : 'Crea el primer perfil comercial para comenzar a construir la relación y su trazabilidad.',
                'actionUrl' => $search !== '' ? route_to('customers.index') : route_to('customers.create'),
                'actionLabel' => $search !== '' ? 'Limpiar búsqueda' : 'Crear primer cliente',
            ]) ?>
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table data-trace-table="true" data-export-title="Directorio de clientes TraceOPX" class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                    <tr><th class="px-5 py-4">Cliente</th><th class="px-5 py-4">Contacto</th><th class="px-5 py-4">Relación</th><th class="px-5 py-4">Seguimiento</th><th class="px-5 py-4 text-right" data-dt-order="disable">Acción</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($customers as $customer): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-950"><?= esc($customer['business_name']) ?></p>
                                <p class="mt-1 text-xs font-bold text-cyan-700"><?= esc($customer['code']) ?><?= $customer['trade_name'] ? ' · ' . esc($customer['trade_name']) : '' ?></p>
                            </td>
                            <td class="px-5 py-4 text-slate-600"><p><?= esc($customer['email'] ?: 'Sin correo') ?></p><p><?= esc($customer['phone'] ?: 'Sin teléfono') ?></p></td>
                            <td class="px-5 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-800"><?= esc($stageLabels[$customer['lifecycle_stage']] ?? $customer['lifecycle_stage']) ?></span>
                                    <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-semibold text-violet-800"><?= esc($tierLabels[$customer['relationship_tier']] ?? $customer['relationship_tier']) ?></span>
                                </div>
                            </td>
                            <td data-order="<?= esc($customer['next_follow_up_date'] ? strtotime($customer['next_follow_up_date']) : 0) ?>" class="px-5 py-4 text-slate-600"><p><?= esc($customer['assigned_sales_user'] ?: 'Sin ejecutivo') ?></p><p class="text-xs text-slate-500"><?= $customer['next_follow_up_date'] ? esc(date('d/m/Y', strtotime($customer['next_follow_up_date']))) : 'Sin fecha programada' ?></p></td>
                            <td class="px-5 py-4 text-right"><a href="<?= route_to('customers.show', $customer['id']) ?>" class="font-semibold text-cyan-700">Abrir perfil</a></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
