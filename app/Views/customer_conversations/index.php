<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = ['new' => 'Nueva', 'in_attention' => 'En atención', 'waiting_customer' => 'Esperando cliente', 'information_complete' => 'Información completa', 'converted' => 'Convertida', 'discarded' => 'Descartada'];
$channelLabels = ['whatsapp' => 'WhatsApp', 'email' => 'Correo', 'manual' => 'Manual', 'phone' => 'Teléfono', 'visit' => 'Visita'];
$slaLabels = ['on_time' => 'En tiempo', 'warning' => 'Próxima a vencer', 'overdue' => 'Fuera de SLA', 'fulfilled' => 'Primera respuesta cumplida'];
?>
<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div><p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Customer Attention Engine</p><h3 class="mt-2 text-3xl font-bold">Atención comercial</h3><p class="mt-2 text-slate-600">Centraliza conversaciones, próximas acciones y SLA sin importar el canal.</p></div>
    <a href="<?= route_to('customer_conversations.create') ?>" class="rounded-xl bg-slate-950 px-5 py-3 text-center font-semibold text-white">Nueva atención</a>
</div>

<div class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ([['Total', $metrics['total']], ['Nuevas', $metrics['new']], ['Esperando cliente', $metrics['waiting']], ['Fuera de SLA', $metrics['overdue']]] as [$label, $value]): ?>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500"><?= esc($label) ?></p><p class="mt-3 text-3xl font-bold"><?= esc((string) $value) ?></p></article>
    <?php endforeach ?>
</div>

<div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <?php if ($conversations === []): ?>
        <div class="p-10 text-center text-slate-500">Aún no existen atenciones comerciales.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table data-trace-table="true" data-export-title="Atenciones comerciales TraceOPX" class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-5 py-4 text-left font-semibold text-slate-700">Atención</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-700">Canal</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-700">Cliente</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-700">Responsable</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-700">Estado</th>
                        <th class="px-5 py-4 text-left font-semibold text-slate-700">SLA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($conversations as $row): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4"><a class="font-bold text-cyan-700" href="<?= route_to('customer_conversations.show', $row['id']) ?>"><?= esc($row['code']) ?></a><p class="mt-1 text-slate-600"><?= esc($row['subject']) ?></p></td>
                            <td class="px-5 py-4"><?= esc($channelLabels[$row['primary_channel']] ?? $row['primary_channel']) ?></td>
                            <td class="px-5 py-4"><?= esc($row['business_name'] ?: 'Prospecto sin asociar') ?></td>
                            <td class="px-5 py-4"><?= esc($row['assigned_user_name'] ?: 'Sin asignar') ?></td>
                            <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold"><?= esc($statusLabels[$row['attention_status']] ?? $row['attention_status']) ?></span></td>
                            <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold <?= $row['runtime_sla_status'] === 'overdue' ? 'bg-red-100 text-red-700' : ($row['runtime_sla_status'] === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') ?>"><?= esc($slaLabels[$row['runtime_sla_status']] ?? '') ?></span></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
