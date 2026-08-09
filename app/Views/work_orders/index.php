<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="mb-6 flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Work Order Engine</p><h2 class="mt-2 text-3xl font-bold text-slate-950">Órdenes de trabajo</h2><p class="mt-2 text-sm text-slate-500">Misiones operativas generadas desde coordinaciones aprobadas.</p></div></div>
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
<table data-trace-table="true" data-export-title="Órdenes de trabajo" class="display w-full">
<thead><tr><th>Orden</th><th>Cliente</th><th>Programación</th><th>Ubicación</th><th>Estado</th><th>Expediente</th></tr></thead>
<tbody><?php foreach($workOrders as $row): ?><tr><td><a class="font-bold text-cyan-700" href="<?= route_to('work_orders.show',$row['id']) ?>"><?= esc($row['code']) ?> ↗</a><div class="text-xs text-slate-500"><?= esc($row['subject']??'') ?></div></td><td><?= esc($row['business_name']??'—') ?></td><td><?= esc($row['scheduled_start_at']?date('d/m/Y H:i',strtotime($row['scheduled_start_at'])):'Pendiente') ?></td><td><?= esc($row['location']??'—') ?></td><td><?= esc(ucfirst(str_replace('_',' ',$row['status']))) ?></td><td><?= esc($row['service_case_code']??'—') ?></td></tr><?php endforeach ?></tbody>
</table>
</section>
<?= $this->endSection() ?>
