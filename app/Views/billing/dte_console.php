<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$ready = (bool)($preview['structurally_ready'] ?? false);
$finalReady = (bool)($preview['final_schema_ready'] ?? false);
$issues = $preview['issues'] ?? [];
$validation = $preview['preissue_validation'] ?? ['valid'=>false,'errors'=>[],'warnings'=>[]];
$paymentSnapshot = $preview['payment_snapshot'] ?? [];
$isReadyToSign = (string)($dteDocument['status'] ?? '') === 'ready_to_sign' && !empty($dteDocument['control_number']);
$finalHash = (string)($dteDocument['final_payload_hash'] ?? '');
?>

<?php if(session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if(session('error')): ?><div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= esc(session('error')) ?></div><?php endif ?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">DTE Technical Console</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($billingCase['code']) ?> · <?= esc($dteDocument['document_code']) ?></h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600"><?= $isReadyToSign ? 'Documento fiscal congelado. El payload final ya tiene número de control y se encuentra listo para la futura etapa de firma electrónica.' : 'Consola técnica de pre-emisión. El payload incorpora el snapshot fiscal de pagos y se valida antes de reservar correlativo, firmar o transmitir.' ?></p>
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
        <p class="mt-3 text-xl font-bold"><?= $isReadyToSign?'Final congelado':'Pre-emisión' ?></p>
        <p class="mt-2 break-all text-sm text-slate-500">Número de control: <?= esc($dteDocument['control_number'] ?: 'Aún no reservado') ?></p>
    </article>
    <article class="rounded-2xl border <?= $isReadyToSign?'border-violet-200 bg-violet-50':($finalReady?'border-emerald-200 bg-emerald-50':'border-slate-200 bg-white') ?> p-5">
        <p class="text-xs font-semibold uppercase tracking-wide <?= $isReadyToSign?'text-violet-700':'text-slate-500' ?>">Emisión final</p>
        <p class="mt-3 text-xl font-bold"><?= $isReadyToSign?'READY TO SIGN':($finalReady?'Disponible':'Pendiente') ?></p>
        <p class="mt-2 text-sm text-slate-500"><?= $isReadyToSign?'Snapshot fiscal, pagos, fecha/hora y número de control están congelados.':'Se habilita cuando la pre-validación no contiene errores.' ?></p>
    </article>
</section>

<?php if($isReadyToSign): ?>
<section class="mt-6 overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm">
    <div class="border-b border-violet-100 bg-violet-50/70 p-6">
        <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-700">Documento fiscal congelado</p>
        <h3 class="mt-2 text-xl font-bold text-slate-950">Preparado para firma electrónica</h3>
        <p class="mt-2 text-sm leading-6 text-slate-600">A partir de este punto los datos fiscales no deben modificarse. El Signature Engine firmará exactamente el payload cuyo hash se muestra a continuación.</p>
    </div>
    <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl bg-slate-50 p-4 md:col-span-2"><p class="text-xs uppercase tracking-wide text-slate-500">Número de control</p><p class="mt-2 break-all font-mono font-bold text-slate-950"><?= esc($dteDocument['control_number']) ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Fecha emisión</p><p class="mt-2 font-bold text-slate-950"><?= esc((string)($dteDocument['issue_date'] ?? '')) ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Hora emisión</p><p class="mt-2 font-bold text-slate-950"><?= esc((string)($dteDocument['issue_time'] ?? '')) ?></p></div>
        <div class="rounded-xl bg-slate-950 p-4 text-white md:col-span-2 xl:col-span-4"><p class="text-xs uppercase tracking-wide text-slate-400">SHA-256 del JSON final</p><p class="mt-2 break-all font-mono text-xs font-bold text-cyan-300"><?= esc($finalHash ?: 'Hash pendiente de recarga') ?></p></div>
    </div>
</section>
<?php elseif($ready && !empty($validation['valid'])): ?>
<section class="mt-6 rounded-2xl border border-cyan-300 bg-gradient-to-br from-cyan-50 to-white p-6 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="max-w-3xl">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Siguiente compuerta fiscal</p>
            <h3 class="mt-2 text-2xl font-bold text-slate-950">Preparar emisión final</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Esta acción congelará el snapshot de pagos, reservará el correlativo anual, generará el número de control, fijará fecha/hora y almacenará el JSON final con hash SHA-256. Todavía no firma ni transmite a Hacienda.</p>
        </div>
        <form id="prepare-final-issue-form" method="post" action="<?= route_to('billing.dte.final_issue',(int)$billingCase['id']) ?>" data-processing-message="Preparando emisión final y reservando número de control…">
            <?= csrf_field() ?>
            <button type="submit" class="whitespace-nowrap rounded-xl bg-slate-950 px-6 py-3.5 font-bold text-white shadow-lg transition hover:bg-slate-800">Preparar emisión final →</button>
        </form>
    </div>
</section>
<?php endif ?>

<section class="mt-6 rounded-2xl border border-violet-200 bg-white shadow-sm">
    <div class="border-b border-violet-100 bg-violet-50/70 p-5">
        <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-700">Snapshot fiscal de pagos</p>
        <h3 class="mt-2 text-xl font-bold text-slate-950">resumen.pagos[]</h3>
        <p class="mt-1 text-sm text-slate-600"><?= ($paymentSnapshot['source']??'live')==='frozen'?'Este snapshot ya está congelado para el DTE final.':'En pre-emisión se construye desde el ledger confirmado. Al preparar emisión final se congelará y dejará de depender de movimientos posteriores.' ?></p>
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
        <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">DtePreIssueService</p><h3 class="mt-2 text-xl font-bold text-white"><?= $isReadyToSign?'JSON final preparado':'JSON técnico generado' ?></h3></div>
        <span class="rounded-full bg-slate-800 px-3 py-1 text-xs font-bold text-slate-300"><?= esc($dteDocument['schema_file']) ?></span>
    </div>
    <pre class="max-h-[70vh] overflow-auto p-6 text-xs leading-6 text-emerald-300"><code><?= esc($preview['json']) ?></code></pre>
</section>

<?php if(!$isReadyToSign): ?>
<section class="mt-6 rounded-2xl border border-cyan-200 bg-cyan-50 p-6">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Regla de emisión</p>
    <p class="mt-2 text-sm leading-6 text-cyan-950">El correlativo solo se consume al confirmar <strong>Preparar emisión final</strong>. Abrir esta consola o ejecutar la pre-validación no modifica la secuencia fiscal.</p>
</section>
<?php endif ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('prepare-final-issue-form');
    if (!form) return;

    form.addEventListener('submit', async function (event) {
        if (form.dataset.finalConfirmed === 'true') return;
        event.preventDefault();
        event.stopImmediatePropagation();

        const result = await Swal.fire({
            title: '¿Preparar emisión final?',
            html: '<p class="text-slate-600">Esta acción <strong>reservará el número de control</strong> y congelará la información fiscal del DTE.</p><p class="mt-3 text-sm text-slate-500">Después de este punto, cualquier corrección fiscal deberá seguir un flujo controlado. Todavía no se firmará ni se enviará a Hacienda.</p>',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, preparar emisión',
            cancelButtonText: 'Seguir revisando',
            reverseButtons: true,
            focusCancel: true,
            confirmButtonColor: '#0f172a',
            cancelButtonColor: '#64748b',
            customClass: {
                popup: 'rounded-3xl',
                confirmButton: 'rounded-xl px-5 py-3 font-bold',
                cancelButton: 'rounded-xl px-5 py-3 font-semibold'
            }
        });

        if (!result.isConfirmed) return;
        form.dataset.finalConfirmed = 'true';
        form.requestSubmit();
    }, true);
});
</script>
<?= $this->endSection() ?>
