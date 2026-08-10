<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$ready = (bool)($preview['structurally_ready'] ?? false);
$finalReady = (bool)($preview['final_schema_ready'] ?? false);
$issues = $preview['issues'] ?? [];
$validation = $preview['preissue_validation'] ?? ['valid'=>false,'errors'=>[],'warnings'=>[]];
$paymentSnapshot = $preview['payment_snapshot'] ?? [];
?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">DTE Technical Console</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($billingCase['code']) ?> · <?= esc($dteDocument['document_code']) ?></h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Consola técnica de pre-emisión. El payload incorpora el snapshot fiscal de pagos y se valida antes de reservar correlativo, firmar o transmitir.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= route_to('billing.dte_json.preview',(int)$billingCase['id']) ?>" target="_blank" class="rounded-xl border border-cyan-300 bg-cyan-50 px-4 py-2.5 text-sm font-bold text-cyan-800">Abrir JSON puro ↗</a>
        <a href="<?= route_to('billing.show',(int)$billingCase['id']) ?>" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700">← Volver al DTE</a>
    </div>
</div>

<section class="grid gap-4 md:grid-cols-4">
    <article class="rounded-2xl border <?= $ready?'border-emerald-200 bg-emerald-50':'border-amber-200 bg-amber-50' ?> p-5">
        <p class="text-xs font-semibold uppercase tracking-wide <?= $ready?'text-emerald-700':'text-amber-700' ?>">Pre-Issue Engine</p>
        <p class="mt-3 text-xl font-bold"><?= $ready?'Preparado':'Requiere revisión' ?></p>
        <p class="mt-2 text-sm leading-6"><?= $ready?'La estructura y las reglas previas de schema están conformes.':'Existen errores que deben corregirse antes de preparar emisión.' ?></p>
    </article>
    <article class="rounded-2xl border <?= !empty($validation['valid'])?'border-emerald-200 bg-emerald-50':'border-rose-200 bg-rose-50' ?> p-5">
        <p class="text-xs font-semibold uppercase tracking-wide <?= !empty($validation['valid'])?'text-emerald-700':'text-rose-700' ?>">Validación Schema-aware</p>
        <p class="mt-3 text-xl font-bold"><?= !empty($validation['valid'])?'Sin errores':'Con errores' ?></p>
        <p class="mt-2 text-sm"><?= count($validation['errors']??[]) ?> error(es) · <?= count($validation['warnings']??[]) ?> advertencia(s)</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Modo</p>
        <p class="mt-3 text-xl font-bold"><?= ($preview['mode']??'pre_issue')==='final'?'Final':'Pre-emisión' ?></p>
        <p class="mt-2 text-sm text-slate-500">Número de control: <?= esc($dteDocument['control_number'] ?: 'Aún no reservado') ?></p>
    </article>
    <article class="rounded-2xl border <?= $finalReady?'border-emerald-200 bg-emerald-50':'border-slate-200 bg-white' ?> p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Emisión final</p>
        <p class="mt-3 text-xl font-bold"><?= $finalReady?'Disponible':'Pendiente' ?></p>
        <p class="mt-2 text-sm text-slate-500">Se habilitará al reservar numeroControl y congelar fecha/hora.</p>
    </article>
</section>

<section class="mt-6 rounded-2xl border border-violet-200 bg-white shadow-sm">
    <div class="border-b border-violet-100 bg-violet-50/70 p-5">
        <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-700">Snapshot fiscal de pagos</p>
        <h3 class="mt-2 text-xl font-bold text-slate-950">resumen.pagos[]</h3>
        <p class="mt-1 text-sm text-slate-600">En pre-emisión se construye desde el ledger confirmado. Al preparar emisión final se congelará y dejará de depender de movimientos posteriores.</p>
    </div>
    <div class="grid gap-4 p-5 md:grid-cols-4">
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">CAT-016</p><p class="mt-2 text-lg font-bold"><?= esc((string)($paymentSnapshot['condition_code']??'—')) ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Pagos incluidos</p><p class="mt-2 text-lg font-bold"><?= is_array($paymentSnapshot['payments']??null)?count($paymentSnapshot['payments']):0 ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Total confirmado</p><p class="mt-2 text-lg font-bold">$<?= number_format((float)($paymentSnapshot['confirmed_total']??0),2) ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Origen</p><p class="mt-2 text-lg font-bold"><?= ($paymentSnapshot['source']??'live')==='frozen'?'Congelado':'Ledger actual' ?></p></div>
    </div>
    <?php if(is_array($paymentSnapshot['payments']??null) && $paymentSnapshot['payments']): ?>
    <div class="overflow-x-auto border-t border-slate-100">
        <table class="min-w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">CAT-017</th><th class="px-5 py-3">Monto</th><th class="px-5 py-3">Referencia</th><th class="px-5 py-3">CAT-018</th><th class="px-5 py-3">Período</th></tr></thead><tbody class="divide-y divide-slate-100">
        <?php foreach($paymentSnapshot['payments'] as $payment): ?><tr><td class="px-5 py-3 font-bold"><?= esc($payment['codigo']) ?></td><td class="px-5 py-3">$<?= number_format((float)$payment['montoPago'],2) ?></td><td class="px-5 py-3"><?= esc($payment['referencia']??'—') ?></td><td class="px-5 py-3"><?= esc($payment['plazo']??'—') ?></td><td class="px-5 py-3"><?= esc((string)($payment['periodo']??'—')) ?></td></tr><?php endforeach ?>
        </tbody></table>
    </div>
    <?php endif ?>
</section>

<?php if(!empty($validation['errors'])): ?>
<section class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-6">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-rose-700">Errores bloqueantes</p>
    <h3 class="mt-2 text-xl font-bold text-rose-950">Corrija antes de preparar emisión</h3>
    <div class="mt-4 space-y-3"><?php foreach($validation['errors'] as $error): ?><div class="rounded-xl border border-rose-200 bg-white p-4"><p class="font-mono text-xs font-bold text-rose-700"><?= esc($error['path']) ?></p><p class="mt-1 text-sm text-rose-950"><?= esc($error['message']) ?></p></div><?php endforeach ?></div>
</section>
<?php endif ?>

<?php if(!empty($validation['warnings'])): ?>
<section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-6">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Advertencias</p>
    <div class="mt-4 space-y-3"><?php foreach($validation['warnings'] as $warning): ?><div><p class="font-mono text-xs font-bold text-amber-700"><?= esc($warning['path']) ?></p><p class="mt-1 text-sm text-amber-950"><?= esc($warning['message']) ?></p></div><?php endforeach ?></div>
</section>
<?php endif ?>

<?php if($issues): ?>
<section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Observaciones del Builder</p>
    <ul class="mt-4 space-y-2 text-sm text-slate-700"><?php foreach($issues as $issue): ?><li class="flex gap-2"><span>•</span><span><?= esc($issue) ?></span></li><?php endforeach ?></ul>
</section>
<?php endif ?>

<section class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 shadow-xl">
    <div class="flex flex-col gap-3 border-b border-slate-800 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">DtePreIssueService</p><h3 class="mt-2 text-xl font-bold text-white">JSON técnico generado</h3></div>
        <span class="rounded-full bg-slate-800 px-3 py-1 text-xs font-bold text-slate-300"><?= esc($dteDocument['schema_file']) ?></span>
    </div>
    <pre class="max-h-[70vh] overflow-auto p-6 text-xs leading-6 text-emerald-300"><code><?= esc($preview['json']) ?></code></pre>
</section>

<section class="mt-6 rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Siguiente compuerta</p>
    <p class="mt-2 text-sm leading-6 text-cyan-950">Cuando esta pre-validación quede sin errores, el siguiente incremento podrá congelar el snapshot de pagos, reservar <strong>numeroControl</strong>, fijar fecha/hora y ejecutar la validación final antes de firma electrónica.</p>
</section>

<?= $this->endSection() ?>
