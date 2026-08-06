<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?= view('components/workspace/page-header', [
    'eyebrow' => 'Trace Workspace',
    'title' => 'Expedientes de servicio',
    'description' => 'Controla cada servicio desde la aceptación comercial hasta su cierre financiero y archivo definitivo.',
]) ?>

<section class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ([['Total', $metrics['total']], ['Saludable', $metrics['healthy']], ['Requiere atención', $metrics['attention']], ['Crítico', $metrics['critical']]] as [$label, $value]): ?>
    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-500"><?= esc($label) ?></p><p class="mt-3 text-3xl font-bold text-slate-950"><?= esc((string) $value) ?></p></article>
    <?php endforeach ?>
</section>

<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
<?php if ($cases === []): ?>
    <div class="p-12 text-center"><p class="text-lg font-bold text-slate-900">Todavía no existen expedientes.</p><p class="mt-2 text-sm text-slate-500">Se crearán automáticamente cuando una cotización complete su proceso de aceptación.</p></div>
<?php else: ?>
<div class="overflow-x-auto"><table data-trace-table="true" data-export-title="Expedientes de servicio TraceOPX" class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr><th class="px-5 py-4 text-left font-bold text-slate-700">Expediente</th><th class="px-5 py-4 text-left font-bold text-slate-700">Cliente</th><th class="px-5 py-4 text-left font-bold text-slate-700">Etapa</th><th class="px-5 py-4 text-left font-bold text-slate-700">Próxima acción</th><th class="px-5 py-4 text-center font-bold text-slate-700">Salud</th><th class="px-5 py-4 text-left font-bold text-slate-700">Responsable</th></tr></thead>
<tbody class="divide-y divide-slate-100">
<?php foreach ($cases as $case): $score=(int)$case['health_score']; $healthClass=$score>=80?'bg-emerald-100 text-emerald-800':($score>=50?'bg-amber-100 text-amber-800':'bg-red-100 text-red-800'); ?>
<tr class="hover:bg-slate-50"><td class="px-5 py-4"><a href="<?= route_to('service_cases.show', $case['id']) ?>" class="inline-flex items-center gap-2 font-bold text-cyan-700 hover:text-cyan-900"><?= esc($case['code']) ?><span aria-hidden="true">↗</span></a><p class="mt-1 text-xs text-slate-500"><?= esc($case['quotation_code'] ?: 'Sin cotización') ?></p></td><td class="px-5 py-4 font-semibold text-slate-900"><?= esc($case['business_name'] ?: 'Sin cliente') ?></td><td class="px-5 py-4"><?= esc(str_replace('_', ' ', $case['current_stage'])) ?></td><td class="px-5 py-4"><?= esc($case['next_action_label'] ?: 'Revisar expediente') ?></td><td class="px-5 py-4 text-center"><span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?= $healthClass ?>"><?= $score ?>%</span></td><td class="px-5 py-4"><?= esc($case['responsible_user_name'] ?: 'Sin asignar') ?></td></tr>
<?php endforeach ?>
</tbody></table></div>
<?php endif ?>
</section>
<?= $this->endSection() ?>
