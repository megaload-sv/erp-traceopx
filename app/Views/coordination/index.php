<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div><p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Coordination Workspace</p><h2 class="mt-2 text-3xl font-bold text-slate-950">Coordinación operativa</h2><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Planifica la ejecución del servicio antes de generar una Orden de Trabajo. Aquí se definen fechas, ubicación y maquinaria requerida.</p></div>
</div>
<section class="mb-6 grid gap-4 md:grid-cols-3">
    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Planes</p><p class="mt-3 text-3xl font-bold text-slate-950"><?= esc((string)$metrics['total']) ?></p></article>
    <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-amber-700">En preparación</p><p class="mt-3 text-3xl font-bold text-amber-950"><?= esc((string)$metrics['draft']) ?></p></article>
    <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"><p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Listos</p><p class="mt-3 text-3xl font-bold text-emerald-950"><?= esc((string)$metrics['ready']) ?></p></article>
</section>
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
<table data-trace-table="true" data-export-title="Coordinación operativa" class="w-full">
<thead><tr><th>Código</th><th>Expediente</th><th>Cliente</th><th>Programación</th><th>Ubicación</th><th>Prioridad</th><th>Estado</th></tr></thead>
<tbody><?php foreach($plans as $plan): ?><tr><td><a class="font-bold text-cyan-700" href="<?= route_to('coordination.show',$plan['id']) ?>"><?= esc($plan['code']) ?> ↗</a></td><td><?= esc($plan['service_case_code']) ?></td><td><?= esc($plan['business_name']) ?></td><td><?= esc($plan['scheduled_start_at'] ? date('d/m/Y H:i',strtotime($plan['scheduled_start_at'])) : 'Pendiente') ?></td><td><?= esc($plan['location'] ?: 'Pendiente') ?></td><td><?= esc(ucfirst($plan['priority'])) ?></td><td><?= esc(ucfirst(str_replace('_',' ',$plan['status']))) ?></td></tr><?php endforeach ?></tbody>
</table>
</section>
<?= $this->endSection() ?>
