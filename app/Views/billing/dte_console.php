<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$ready = (bool)($preview['structurally_ready'] ?? false);
$finalReady = (bool)($preview['final_schema_ready'] ?? false);
$issues = $preview['issues'] ?? [];
?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">DTE Technical Console</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($billingCase['code']) ?> · <?= esc($dteDocument['document_code']) ?></h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Vista técnica de pre-emisión. Construye el JSON desde los snapshots del DTE sin firmar, transmitir ni consumir un correlativo si el número de control todavía no existe.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= route_to('billing.dte_json.preview',(int)$billingCase['id']) ?>" target="_blank" class="rounded-xl border border-cyan-300 bg-cyan-50 px-4 py-2.5 text-sm font-bold text-cyan-800">Abrir JSON puro ↗</a>
        <a href="<?= route_to('billing.show',(int)$billingCase['id']) ?>" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700">← Volver al DTE</a>
    </div>
</div>

<section class="grid gap-4 md:grid-cols-3">
    <article class="rounded-2xl border <?= $ready?'border-emerald-200 bg-emerald-50':'border-amber-200 bg-amber-50' ?> p-5">
        <p class="text-xs font-semibold uppercase tracking-wide <?= $ready?'text-emerald-700':'text-amber-700' ?>">Pre-Issue Builder</p>
        <p class="mt-3 text-xl font-bold"><?= $ready?'Estructura preparada':'Requiere revisión' ?></p>
        <p class="mt-2 text-sm leading-6"><?= $ready?'Emisor, receptor, ítems y configuración base están disponibles.':'Hay datos que deben completarse antes de preparar la emisión final.' ?></p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modo</p>
        <p class="mt-3 text-xl font-bold"><?= ($preview['mode']??'pre_issue')==='final'?'Final':'Pre-emisión' ?></p>
        <p class="mt-2 text-sm text-slate-500">Número de control: <?= esc($dteDocument['control_number'] ?: 'Aún no reservado') ?></p>
    </article>
    <article class="rounded-2xl border <?= $finalReady?'border-emerald-200 bg-emerald-50':'border-slate-200 bg-white' ?> p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Validación final Schema</p>
        <p class="mt-3 text-xl font-bold"><?= $finalReady?'Disponible':'Pendiente' ?></p>
        <p class="mt-2 text-sm text-slate-500">La validación estricta se habilitará después de reservar el número de control y congelar fecha/hora de emisión.</p>
    </article>
</section>

<?php if($issues): ?>
<section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Pre-Issue Validation</p>
    <h3 class="mt-2 text-xl font-bold text-amber-950">Observaciones detectadas</h3>
    <ul class="mt-4 space-y-2 text-sm text-amber-900">
        <?php foreach($issues as $issue): ?><li class="flex gap-2"><span>•</span><span><?= esc($issue) ?></span></li><?php endforeach ?>
    </ul>
</section>
<?php endif ?>

<section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 shadow-xl">
    <div class="flex flex-col gap-3 border-b border-slate-800 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">DteJsonBuilderService</p><h3 class="mt-2 text-xl font-bold text-white">JSON técnico generado</h3></div>
        <span class="rounded-full bg-slate-800 px-3 py-1 text-xs font-bold text-slate-300"><?= esc($dteDocument['schema_file']) ?></span>
    </div>
    <pre class="max-h-[70vh] overflow-auto p-6 text-xs leading-6 text-emerald-300"><code><?= esc($preview['json']) ?></code></pre>
</section>

<section class="mt-6 rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Regla de emisión</p>
    <p class="mt-2 text-sm leading-6 text-cyan-950">Esta consola no firma ni transmite el documento. El próximo incremento reservará <strong>numeroControl</strong>, congelará fecha/hora y ejecutará la validación estricta contra el JSON Schema antes de permitir firma electrónica.</p>
</section>

<?= $this->endSection() ?>
