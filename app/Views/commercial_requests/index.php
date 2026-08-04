<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = ['new'=>'Nueva','assigned'=>'Asignada','in_progress'=>'En atención','waiting_customer'=>'Esperando cliente','ready_to_quote'=>'Lista para cotizar','quotation_preparation'=>'Cotización en preparación','quotation_sent'=>'Cotización enviada','converted'=>'Convertida','discarded'=>'Descartada'];
$channelLabels = ['whatsapp'=>'WhatsApp','email'=>'Correo','manual'=>'Manual'];
$slaClasses = ['on_time'=>'bg-emerald-100 text-emerald-800','warning'=>'bg-amber-100 text-amber-800','overdue'=>'bg-red-100 text-red-800'];
$slaLabels = ['on_time'=>'En tiempo','warning'=>'Próxima a vencer','overdue'=>'Fuera de SLA'];
?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>

<?= view('components/workspace/page-header', [
    'eyebrow' => 'Atención comercial',
    'title' => 'Solicitudes comerciales',
    'description' => 'Centraliza entradas de WhatsApp, correo y registro manual con responsable, tarea y fecha límite.',
    'actionUrl' => route_to('commercial_requests.create'),
    'actionLabel' => 'Nueva solicitud',
]) ?>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?= view('components/workspace/kpi-card', ['label'=>'Total','value'=>$metrics['total']]) ?>
    <?= view('components/workspace/kpi-card', ['label'=>'Nuevas','value'=>$metrics['new']]) ?>
    <?= view('components/workspace/kpi-card', ['label'=>'Esperando cliente','value'=>$metrics['waiting']]) ?>
    <?= view('components/workspace/kpi-card', ['label'=>'Fuera de SLA','value'=>$metrics['overdue']]) ?>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Solicitud</th><th class="px-5 py-4">Canal</th><th class="px-5 py-4">Cliente</th><th class="px-5 py-4">Responsable</th><th class="px-5 py-4">Estado</th><th class="px-5 py-4">SLA</th><th class="px-5 py-4">Límite</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($requests as $request): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-5 py-4"><p class="font-semibold text-slate-950"><?= esc($request['subject']) ?></p><p class="mt-1 text-xs font-bold text-cyan-700"><?= esc($request['code']) ?></p></td>
                    <td class="px-5 py-4"><?= esc($channelLabels[$request['channel']] ?? $request['channel']) ?></td>
                    <td class="px-5 py-4"><?= esc($request['business_name'] ?: 'Prospecto sin asociar') ?></td>
                    <td class="px-5 py-4"><?= esc($request['assigned_user_name'] ?: 'Sin asignar') ?></td>
                    <td class="px-5 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"><?= esc($statusLabels[$request['status']] ?? $request['status']) ?></span></td>
                    <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold <?= esc($slaClasses[$request['runtime_sla_status']] ?? '') ?>"><?= esc($slaLabels[$request['runtime_sla_status']] ?? '') ?></span></td>
                    <td class="px-5 py-4 text-slate-600"><?= esc(date('d/m/Y H:i', strtotime($request['first_responded_at'] ? $request['quotation_due_at'] : $request['first_response_due_at']))) ?></td>
                </tr>
                <?php endforeach ?>
                <?php if ($requests === []): ?><tr><td colspan="7" class="px-6 py-12"><?= view('components/workspace/empty-state', ['title'=>'No hay solicitudes comerciales','description'=>'Registra la primera entrada para iniciar su SLA y tarea de atención.','actionUrl'=>route_to('commercial_requests.create'),'actionLabel'=>'Registrar solicitud']) ?></td></tr><?php endif ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
