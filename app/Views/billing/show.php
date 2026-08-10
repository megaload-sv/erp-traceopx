<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php if(session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if(session('error')): ?><div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= esc(session('error')) ?></div><?php endif ?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">DTE Workspace</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($billingCase['code']) ?></h2>
        <div class="mt-3 flex flex-wrap gap-3">
            <a href="<?= route_to('service_cases.show',$billingCase['service_case_id']) ?>" class="font-bold text-violet-700"><?= esc($billingCase['service_case_code']) ?> ↗</a>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Borrador fiscal</span>
            <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-bold text-cyan-800"><?= esc($dteDocument['document_code']) ?> · MH <?= esc($dteDocument['mh_code']) ?> · v<?= esc((string)$dteDocument['schema_version']) ?></span>
        </div>
    </div>
    <a href="<?= route_to('billing.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Volver a facturación</a>
</div>

<section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase tracking-wide text-slate-500">Cliente</p><p class="mt-3 text-lg font-bold"><?= esc($billingCase['business_name']) ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase tracking-wide text-slate-500">Documento DTE</p><p class="mt-3 text-lg font-bold"><?= esc($dteDocument['document_name']) ?></p><p class="mt-1 text-xs text-slate-500">Esquema <?= esc($dteDocument['schema_file']) ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase tracking-wide text-slate-500">Total gravado</p><p class="mt-3 text-lg font-bold">$<?= number_format((float)$dteDocument['taxed_total'],2) ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase tracking-wide text-slate-500">Total operación</p><p class="mt-3 text-lg font-bold">$<?= number_format((float)$dteDocument['operation_total'],2) ?></p></article>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
<div class="space-y-6">
<section class="rounded-2xl border border-cyan-200 bg-cyan-50/40 p-6">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Origen trazable</p><h3 class="mt-2 text-xl font-bold">Snapshot fiscal independiente</h3>
    <p class="mt-2 text-sm leading-6 text-cyan-900">La cotización es el origen comercial. El encabezado y los ítems fiscales ya pertenecen al DTE y pueden clasificarse sin modificar la cotización.</p>
    <div class="mt-5 grid gap-3 md:grid-cols-3"><div class="rounded-xl bg-white p-4"><p class="text-xs text-slate-500">Cotización</p><p class="mt-2 font-bold"><?= esc($billingCase['quotation_code']) ?></p></div><div class="rounded-xl bg-white p-4"><p class="text-xs text-slate-500">Orden de Trabajo</p><p class="mt-2 font-bold"><?= esc($billingCase['work_order_code'] ?: 'Aún no generada') ?></p></div><div class="rounded-xl bg-white p-4"><p class="text-xs text-slate-500">Condición comercial</p><p class="mt-2 font-bold"><?= esc($billingCase['payment_term_name_snapshot'] ?: 'Sin condición') ?></p></div></div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Encabezado fiscal</p><h3 class="mt-2 text-xl font-bold">Identificación del DTE</h3></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">Estado: <?= esc($dteDocument['status']) ?></span></div>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Tipo DTE MH</p><p class="mt-2 font-bold"><?= esc($dteDocument['mh_code']) ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Versión schema</p><p class="mt-2 font-bold"><?= esc((string)$dteDocument['schema_version']) ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Moneda</p><p class="mt-2 font-bold"><?= esc($dteDocument['currency_code']) ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Número de control</p><p class="mt-2 font-semibold text-slate-600"><?= esc($dteDocument['control_number'] ?: 'Pendiente de emisión') ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2"><p class="text-xs text-slate-500">Código de generación · UUID v4</p><p class="mt-2 break-all font-mono text-sm font-bold text-slate-800"><?= esc($dteDocument['generation_code']) ?></p></div>
    </div>
</section>

<section id="dte-items" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-6">
        <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Cuerpo del documento</p>
        <h3 class="mt-2 text-xl font-bold">Detalle y clasificación fiscal</h3>
        <p class="mt-2 text-sm text-slate-500">Cada línea define su naturaleza fiscal y su tributo CAT-015. Los totales del encabezado se recalculan automáticamente.</p>
    </div>
    <div class="divide-y divide-slate-100">
        <?php foreach($dteItems as $item): ?>
        <?php $taxCodes = json_decode((string)($item['tax_codes_json'] ?? '[]'), true) ?: []; $selectedTax = $taxCodes[0] ?? ''; ?>
        <article class="p-5">
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
                <div>
                    <div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">#<?= esc((string)$item['sequence']) ?></span><p class="font-bold text-slate-950"><?= esc($item['code'] ?: 'Sin código') ?></p></div>
                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600"><?= esc($item['description']) ?></p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-4 text-sm"><div><p class="text-xs text-slate-500">Cantidad</p><p class="mt-1 font-semibold"><?= number_format((float)$item['quantity'],3) ?></p></div><div><p class="text-xs text-slate-500">Unidad</p><p class="mt-1 font-semibold"><?= esc($item['unit_symbol_snapshot'] ?: $item['unit_name_snapshot'] ?: 'Pendiente') ?></p><p class="text-xs <?= $item['mh_unit_code'] ? 'text-emerald-600' : 'text-amber-600' ?>"><?= $item['mh_unit_code'] ? 'CAT-014 '.$item['mh_unit_code'] : 'CAT-014 pendiente' ?></p></div><div><p class="text-xs text-slate-500">Precio</p><p class="mt-1 font-semibold">$<?= number_format((float)$item['unit_price'],2) ?></p></div><div><p class="text-xs text-slate-500">Total</p><p class="mt-1 font-bold">$<?= number_format((float)$item['line_total'],2) ?></p></div></div>
                </div>
                <form method="post" action="<?= route_to('billing.items.tax.update',(int)$billingCase['id'],(int)$item['id']) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 p-4" data-processing-message="Actualizando clasificación fiscal del ítem…">
                    <?= csrf_field() ?>
                    <label class="block text-sm font-semibold text-slate-700">Clasificación fiscal
                        <select name="fiscal_classification" data-native="true" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-normal" data-fiscal-classification>
                            <option value="taxed" <?= ($item['fiscal_classification']??'taxed')==='taxed'?'selected':'' ?>>Gravada</option>
                            <option value="exempt" <?= ($item['fiscal_classification']??'')==='exempt'?'selected':'' ?>>Exenta</option>
                            <option value="non_subject" <?= ($item['fiscal_classification']??'')==='non_subject'?'selected':'' ?>>No sujeta</option>
                        </select>
                    </label>
                    <label class="mt-3 block text-sm font-semibold text-slate-700">Tributo CAT-015
                        <select name="tax_code" class="mt-2 w-full" data-placeholder="Seleccionar tributo" data-tax-code>
                            <option value="">Sin tributo</option>
                            <?php foreach($taxCatalog as $tax): ?><option value="<?= esc($tax['code']) ?>" <?= $selectedTax===$tax['code']?'selected':'' ?>><?= esc($tax['code']) ?> · <?= esc($tax['name']) ?></option><?php endforeach ?>
                        </select>
                    </label>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center text-xs"><div class="rounded-lg bg-white p-2"><p class="text-slate-400">No sujeta</p><p class="mt-1 font-bold">$<?= number_format((float)$item['non_subject_sale'],2) ?></p></div><div class="rounded-lg bg-white p-2"><p class="text-slate-400">Exenta</p><p class="mt-1 font-bold">$<?= number_format((float)$item['exempt_sale'],2) ?></p></div><div class="rounded-lg bg-white p-2"><p class="text-slate-400">Gravada</p><p class="mt-1 font-bold">$<?= number_format((float)$item['taxed_sale'],2) ?></p></div></div>
                    <button class="mt-4 w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white">Guardar clasificación fiscal</button>
                </form>
            </div>
        </article>
        <?php endforeach ?>
        <?php if($dteItems===[]): ?><div class="p-10 text-center text-sm text-slate-500">El documento aún no tiene ítems fiscales.</div><?php endif ?>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Resumen fiscal</p><h3 class="mt-2 text-xl font-bold">Totales del DTE</h3>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">No sujeto</p><p class="mt-2 text-lg font-bold">$<?= number_format((float)$dteDocument['non_subject_total'],2) ?></p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Exento</p><p class="mt-2 text-lg font-bold">$<?= number_format((float)$dteDocument['exempt_total'],2) ?></p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Gravado</p><p class="mt-2 text-lg font-bold">$<?= number_format((float)$dteDocument['taxed_total'],2) ?></p></div><div class="rounded-xl bg-slate-950 p-4 text-white"><p class="text-xs text-slate-400">Total operación</p><p class="mt-2 text-lg font-bold">$<?= number_format((float)$dteDocument['operation_total'],2) ?></p></div></div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Plan financiero heredado</p><h3 class="mt-2 text-xl font-bold">Calendario esperado de cobro</h3><div class="mt-5 space-y-3"><?php foreach($schedule as $item): ?><article class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-bold"><?= esc($item['concept']) ?></p><p class="mt-1 text-xs text-slate-500"><?= number_format((float)$item['percentage'],2) ?>% · <?= esc(str_replace('_',' ',$item['trigger_event'] ?? '')) ?></p></div><div class="text-right"><p class="text-lg font-bold">$<?= number_format((float)$item['amount'],2) ?></p><span class="text-xs font-semibold text-amber-700">Pendiente</span></div></article><?php endforeach ?></div></section>
</div>

<aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
<section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Próxima acción</p><h3 class="mt-3 text-xl font-bold">Completar fiscalización del DTE</h3><p class="mt-3 text-sm leading-6 text-slate-300">CAT-014 y CAT-015 ya forman parte del detalle. El siguiente incremento completará receptor y reglas específicas por tipo de documento antes de validar el JSON Schema.</p></section>
<section class="rounded-2xl border border-slate-200 bg-white p-6"><p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Resumen DTE</p><dl class="mt-5 space-y-4 text-sm"><div class="flex justify-between"><dt class="text-slate-500">Ítems</dt><dd class="font-bold"><?= count($dteItems) ?></dd></div><div class="flex justify-between"><dt class="text-slate-500">Facturable</dt><dd class="font-bold">$<?= number_format((float)$billingCase['invoiceable_amount'],2) ?></dd></div><div class="flex justify-between"><dt class="text-slate-500">Pagado</dt><dd class="font-bold">$<?= number_format((float)$billingCase['paid_amount'],2) ?></dd></div><div class="flex justify-between"><dt class="text-slate-500">Pendiente</dt><dd class="font-bold">$<?= number_format((float)$billingCase['balance_amount'],2) ?></dd></div></dl></section>
</aside>
</div>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('[data-fiscal-classification]').forEach(select=>{
    const form=select.closest('form'); const tax=form?.querySelector('[data-tax-code]');
    const sync=()=>{ if(!tax)return; const disabled=select.value!=='taxed'; tax.disabled=disabled; if(disabled){ const choices=window.traceOpxChoices?.get(tax); if(choices) choices.setChoiceByValue(''); else tax.value=''; } };
    select.addEventListener('change',sync); sync();
  });
});
</script>
<?= $this->endSection() ?>
<?= $this->endSection() ?>
