<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="mb-6 flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Work Order Engine</p><h2 class="mt-2 text-3xl font-bold text-slate-950">Órdenes de trabajo</h2><p class="mt-2 text-sm text-slate-500">Misiones operativas generadas desde coordinaciones aprobadas.</p></div></div>

<?php if(!empty($pendingCoordinations)): ?>
<section class="mb-6 rounded-2xl border border-cyan-200 bg-cyan-50/50 p-6 shadow-sm">
    <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Próximas acciones</p><h3 class="mt-2 text-xl font-bold text-slate-950">Coordinaciones listas para generar OT</h3><p class="mt-2 text-sm text-slate-600">Estas misiones ya fueron aprobadas y tienen recursos formalmente asignados.</p></div>
    <div class="mt-5 grid gap-4 lg:grid-cols-2">
        <?php foreach($pendingCoordinations as $coordination): ?>
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><a href="<?= route_to('coordination.show',$coordination['id']) ?>" class="font-bold text-cyan-700"><?= esc($coordination['code']) ?> ↗</a><p class="mt-2 text-lg font-bold text-slate-950"><?= esc($coordination['business_name']??'Cliente') ?></p><p class="mt-2 text-sm text-slate-500"><?= esc($coordination['scheduled_start_at']?date('d/m/Y H:i',strtotime($coordination['scheduled_start_at'])):'Sin programación') ?> · <?= esc($coordination['location']??'Sin ubicación') ?></p><p class="mt-1 text-xs text-violet-700"><?= esc($coordination['service_case_code']??'') ?></p></div><form method="post" action="<?= route_to('work_orders.from_coordination',$coordination['id']) ?>"><?= csrf_field() ?><button class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Generar OT →</button></form></div>
        </article>
        <?php endforeach ?>
    </div>
</section>
<?php endif ?>

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
<table data-trace-table="true" data-export-title="Órdenes de trabajo" class="display w-full">
<thead><tr><th>Orden</th><th>Cliente</th><th>Programación</th><th>Ubicación</th><th>Estado</th><th>Expediente</th></tr></thead>
<tbody><?php foreach($workOrders as $row): ?><tr><td><a class="font-bold text-cyan-700" href="<?= route_to('work_orders.show',$row['id']) ?>"><?= esc($row['code']) ?> ↗</a><div class="text-xs text-slate-500"><?= esc($row['subject']??'') ?></div></td><td><?= esc($row['business_name']??'—') ?></td><td><?= esc($row['scheduled_start_at']?date('d/m/Y H:i',strtotime($row['scheduled_start_at'])):'Pendiente') ?></td><td><?= esc($row['location']??'—') ?></td><td><?= esc(ucfirst(str_replace('_',' ',$row['status']))) ?></td><td><?= esc($row['service_case_code']??'—') ?></td></tr><?php endforeach ?></tbody>
</table>
</section>
<?= $this->endSection() ?>
