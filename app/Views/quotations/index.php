<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = [
    'draft' => 'Borrador',
    'ready_for_review' => 'Lista para revisión',
    'ready_to_send' => 'Lista para enviar',
    'sent' => 'Enviada',
    'negotiation' => 'En negociación',
    'accepted' => 'Aceptada',
    'rejected' => 'Rechazada',
    'expired' => 'Vencida',
];
$statusClasses = [
    'draft' => 'bg-amber-100 text-amber-800',
    'ready_for_review' => 'bg-sky-100 text-sky-800',
    'ready_to_send' => 'bg-indigo-100 text-indigo-800',
    'sent' => 'bg-cyan-100 text-cyan-800',
    'negotiation' => 'bg-violet-100 text-violet-800',
    'accepted' => 'bg-emerald-100 text-emerald-800',
    'rejected' => 'bg-red-100 text-red-800',
    'expired' => 'bg-slate-200 text-slate-700',
];
?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>

<?= view('components/workspace/page-header', [
    'eyebrow' => 'Quotation Engine',
    'title' => 'Cotizaciones',
    'description' => 'Prepara propuestas comerciales trazables desde una solicitud o directamente desde un cliente existente.',
    'actionUrl' => route_to('quotations.create'),
    'actionLabel' => 'Nueva cotización',
]) ?>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?= view('components/workspace/kpi-card', ['label' => 'Total', 'value' => $metrics['total']]) ?>
    <?= view('components/workspace/kpi-card', ['label' => 'Borradores', 'value' => $metrics['draft']]) ?>
    <?= view('components/workspace/kpi-card', ['label' => 'Enviadas', 'value' => $metrics['sent']]) ?>
    <?= view('components/workspace/kpi-card', ['label' => 'Aceptadas', 'value' => $metrics['accepted']]) ?>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <?php if ($quotations === []): ?>
        <div class="p-6"><?= view('components/workspace/empty-state', [
            'title' => 'No hay cotizaciones',
            'description' => 'Crea el primer borrador para comenzar a validar el nuevo flujo comercial.',
            'actionUrl' => route_to('quotations.create'),
            'actionLabel' => 'Crear cotización',
        ]) ?></div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table data-trace-table="true" data-export-title="Cotizaciones TraceOPX" class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-5 py-4">Cotización</th>
                        <th class="px-5 py-4">Cliente</th>
                        <th class="px-5 py-4">Agente</th>
                        <th class="px-5 py-4">Estado</th>
                        <th class="px-5 py-4">Fecha</th>
                        <th class="px-5 py-4">Vigencia</th>
                        <th class="px-5 py-4">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($quotations as $quotation): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <a href="<?= route_to('quotations.show', $quotation['id']) ?>" class="group block">
                                    <p class="font-semibold text-slate-950 transition group-hover:text-cyan-700"><?= esc($quotation['subject']) ?></p>
                                    <p class="mt-1 text-xs font-bold text-cyan-700"><?= esc($quotation['code']) ?> <span aria-hidden="true">↗</span></p>
                                </a>
                            </td>
                            <td class="px-5 py-4"><?= esc($quotation['business_name'] ?: 'Sin cliente') ?></td>
                            <td class="px-5 py-4"><?= esc($quotation['assigned_user_name'] ?: 'Sin asignar') ?></td>
                            <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold <?= esc($statusClasses[$quotation['status']] ?? 'bg-slate-100 text-slate-700') ?>"><?= esc($statusLabels[$quotation['status']] ?? $quotation['status']) ?></span></td>
                            <td data-order="<?= esc(strtotime($quotation['quotation_date'])) ?>" class="px-5 py-4 text-slate-600"><?= esc(date('d/m/Y', strtotime($quotation['quotation_date']))) ?></td>
                            <td data-order="<?= esc(strtotime($quotation['valid_until'])) ?>" class="px-5 py-4 text-slate-600"><?= esc(date('d/m/Y', strtotime($quotation['valid_until']))) ?></td>
                            <td data-order="<?= esc((string) $quotation['total']) ?>" class="px-5 py-4 font-semibold text-slate-900">$<?= esc(number_format((float) $quotation['total'], 2)) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
