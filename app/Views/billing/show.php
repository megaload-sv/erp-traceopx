<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php if(session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if(session('error')): ?><div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= esc(session('error')) ?></div><?php endif ?>

<?php
$receiverValid = ($dteDocument['receiver_validation_status'] ?? 'pending') === 'valid';
$docCode = (string)($dteDocument['document_code'] ?? '');
$activities = $receiverCatalogs['activities'] ?? [];
$departments = $receiverCatalogs['departments'] ?? [];
$municipalities = $receiverCatalogs['municipalities'] ?? [];
$receiverDocumentType = (string) old('receiver_document_type', (string)($dteDocument['receiver_document_type'] ?? ''));
$receiverActivityCode = (string) old('receiver_activity_code', (string)($dteDocument['receiver_activity_code'] ?? ''));
$receiverActivityDescription = (string) old('receiver_activity_description', (string)($dteDocument['receiver_activity_description'] ?? ''));
?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">DTE Workspace</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($billingCase['code']) ?></h2>
        <div class="mt-3 flex flex-wrap gap-3">
            <a href="<?= route_to('service_cases.show',$billingCase['service_case_id']) ?>" class="font-bold text-violet-700"><?= esc($billingCase['service_case_code']) ?> ↗</a>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Borrador fiscal</span>
            <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-bold text-cyan-800"><?= esc($docCode) ?> · MH <?= esc($dteDocument['mh_code']) ?> · v<?= esc((string)$dteDocument['schema_version']) ?></span>
        </div>
    </div>
    <a href="<?= route_to('billing.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Volver a facturación</a>
</div>

<section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase tracking-wide text-slate-500">Cliente</p><p class="mt-3 text-lg font-bold"><?= esc($billingCase['business_name']) ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase tracking-wide text-slate-500">Documento</p><p class="mt-3 text-lg font-bold"><?= esc($dteDocument['document_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($dteDocument['schema_file']) ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase tracking-wide text-slate-500">Receptor</p><p class="mt-3 text-lg font-bold <?= $receiverValid?'text-emerald-700':'text-amber-700' ?>"><?= $receiverValid?'Validado':'Pendiente' ?></p></article>
    <article class="rounded-2xl bg-slate-950 p-5 text-white"><p class="text-xs uppercase tracking-wide text-slate-400">Total operación</p><p class="mt-3 text-lg font-bold">$<?= number_format((float)$dteDocument['operation_total'],2) ?></p></article>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
<div class="space-y-6">

<section class="rounded-2xl border border-cyan-200 bg-cyan-50/40 p-6">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Origen trazable</p>
    <h3 class="mt-2 text-xl font-bold">Snapshot fiscal independiente</h3>
    <p class="mt-2 text-sm leading-6 text-cyan-900">La cotización y el maestro de clientes son fuentes de origen. El DTE conserva sus propios snapshots para que cambios posteriores no alteren el documento fiscal.</p>
    <div class="mt-5 grid gap-3 md:grid-cols-3">
        <div class="rounded-xl bg-white p-4"><p class="text-xs text-slate-500">Cotización</p><p class="mt-2 font-bold"><?= esc($billingCase['quotation_code']) ?></p></div>
        <div class="rounded-xl bg-white p-4"><p class="text-xs text-slate-500">Orden de Trabajo</p><p class="mt-2 font-bold"><?= esc($billingCase['work_order_code'] ?: 'Aún no generada') ?></p></div>
        <div class="rounded-xl bg-white p-4"><p class="text-xs text-slate-500">Condición comercial</p><p class="mt-2 font-bold"><?= esc($billingCase['payment_term_name_snapshot'] ?: 'Sin condición') ?></p></div>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Encabezado fiscal</p><h3 class="mt-2 text-xl font-bold">Identificación del DTE</h3></div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"><?= esc($dteDocument['status']) ?></span>
    </div>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Tipo DTE MH</p><p class="mt-2 font-bold"><?= esc($dteDocument['mh_code']) ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Versión</p><p class="mt-2 font-bold"><?= esc((string)$dteDocument['schema_version']) ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Ambiente</p><p class="mt-2 font-bold"><?= $dteDocument['environment']==='01'?'Producción':'Pruebas' ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Número de control</p><p class="mt-2 font-semibold"><?= esc($dteDocument['control_number'] ?: 'Pendiente de emisión') ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4 sm:col-span-2"><p class="text-xs text-slate-500">Código de generación · UUID v4</p><p class="mt-2 break-all font-mono text-sm font-bold"><?= esc($dteDocument['generation_code']) ?></p></div>
    </div>
</section>

<section id="receiver" class="rounded-2xl border <?= $receiverValid?'border-emerald-200':'border-amber-200' ?> bg-white shadow-sm">
    <div class="border-b border-slate-200 p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Receptor Fiscal</p><h3 class="mt-2 text-xl font-bold">Snapshot del receptor</h3><p class="mt-2 text-sm text-slate-500">Se inicializa desde Clientes, pero pertenece al DTE y puede completarse antes de emitir.</p></div>
            <span class="rounded-full px-3 py-1 text-xs font-bold <?= $receiverValid?'bg-emerald-100 text-emerald-800':'bg-amber-100 text-amber-800' ?>"><?= $receiverValid?'Validado':'Datos pendientes' ?></span>
        </div>
        <?php if($receiverIssues): ?><div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="font-bold text-amber-900">Validaciones pendientes</p><ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-amber-800"><?php foreach($receiverIssues as $issue): ?><li><?= esc($issue) ?></li><?php endforeach ?></ul></div><?php endif ?>
    </div>

    <form method="post" action="<?= route_to('billing.receiver.update',(int)$billingCase['id']) ?>" class="grid gap-4 p-6 md:grid-cols-2" data-processing-message="Actualizando receptor fiscal…">
        <?= csrf_field() ?>
        <label class="md:col-span-2 text-sm font-semibold text-slate-700">Nombre / razón social<input name="receiver_name" value="<?= esc(old('receiver_name',$dteDocument['receiver_name_snapshot']??'')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>
        <label class="md:col-span-2 text-sm font-semibold text-slate-700">Nombre comercial<input name="receiver_trade_name" value="<?= esc(old('receiver_trade_name',$dteDocument['receiver_trade_name_snapshot']??'')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>

        <label class="text-sm font-semibold text-slate-700">Tipo de documento
            <select id="receiver_document_type" name="receiver_document_type" data-native="true" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-normal">
                <option value="" <?= $receiverDocumentType===''?'selected':'' ?>>Sin definir</option>
                <?php foreach(['36'=>'36 · NIT','13'=>'13 · DUI','02'=>'02 · Carné de residente','03'=>'03 · Pasaporte','37'=>'37 · Otro'] as $value=>$label): ?>
                    <option value="<?= esc($value) ?>" <?= $receiverDocumentType===(string)$value?'selected':'' ?>><?= esc($label) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">Número de documento<input name="receiver_document_number" value="<?= esc(old('receiver_document_number',$dteDocument['receiver_document_number']??'')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal"></label>
        <?php if($docCode==='CCF' || $docCode==='FCF'): ?><label class="text-sm font-semibold text-slate-700">NRC<input name="receiver_nrc" value="<?= esc(old('receiver_nrc',$dteDocument['receiver_nrc']??'')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal"></label><?php else: ?><input type="hidden" name="receiver_nrc" value="<?= esc($dteDocument['receiver_nrc']??'') ?>"><?php endif ?>

        <?php if($docCode==='FEX'): ?>
            <label class="text-sm font-semibold text-slate-700">Tipo de persona<select name="receiver_person_type" data-native="true" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-normal"><option value="">Seleccionar</option><option value="1" <?= (int)($dteDocument['receiver_person_type']??0)===1?'selected':'' ?>>1 · Persona natural</option><option value="2" <?= (int)($dteDocument['receiver_person_type']??0)===2?'selected':'' ?>>2 · Persona jurídica</option></select></label>
            <label class="text-sm font-semibold text-slate-700">Código país<input name="receiver_country_code" value="<?= esc(old('receiver_country_code',$dteDocument['receiver_country_code']??'')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal"></label>
            <label class="md:col-span-2 text-sm font-semibold text-slate-700">Nombre país<input name="receiver_country_name" value="<?= esc(old('receiver_country_name',$dteDocument['receiver_country_name']??'')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>
        <?php else: ?>
            <input type="hidden" name="receiver_person_type" value="<?= esc((string)($dteDocument['receiver_person_type']??'')) ?>">
            <input type="hidden" name="receiver_country_code" value="<?= esc($dteDocument['receiver_country_code']??'') ?>">
            <input type="hidden" name="receiver_country_name" value="<?= esc($dteDocument['receiver_country_name']??'') ?>">
            <label class="text-sm font-semibold text-slate-700">Departamento<select id="receiver_department_code" name="receiver_department_code" class="mt-2 w-full" data-placeholder="Seleccionar departamento"><option value="">Seleccionar</option><?php foreach($departments as $row): ?><option value="<?= esc($row['code']) ?>" <?= (string)old('receiver_department_code',$dteDocument['receiver_department_code']??'')===(string)$row['code']?'selected':'' ?>><?= esc($row['code'].' · '.$row['name']) ?></option><?php endforeach ?></select></label>
            <label class="text-sm font-semibold text-slate-700">Municipio<select id="receiver_municipality_code" name="receiver_municipality_code" class="mt-2 w-full" data-placeholder="Seleccionar municipio"><option value="">Seleccionar</option><?php foreach($municipalities as $row): ?><option value="<?= esc($row['code']) ?>" data-department="<?= esc($row['parent_code']) ?>" <?= (string)old('receiver_municipality_code',$dteDocument['receiver_municipality_code']??'')===(string)$row['code']?'selected':'' ?>><?= esc($row['code'].' · '.$row['name']) ?></option><?php endforeach ?></select></label>
        <?php endif ?>

        <label class="md:col-span-2 text-sm font-semibold text-slate-700">Actividad económica
            <select id="receiver_activity_code" name="receiver_activity_code" class="mt-2 w-full" data-placeholder="Seleccionar actividad">
                <option value="">Sin asignar</option>
                <?php foreach($activities as $row): ?>
                    <option value="<?= esc($row['code']) ?>" data-description="<?= esc($row['name']) ?>" <?= $receiverActivityCode===(string)$row['code']?'selected':'' ?>><?= esc($row['code'].' · '.$row['name']) ?></option>
                <?php endforeach ?>
            </select>
        </label>
        <label class="md:col-span-2 text-sm font-semibold text-slate-700">Descripción actividad
            <input id="receiver_activity_description" name="receiver_activity_description" value="<?= esc($receiverActivityDescription) ?>" readonly class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-normal text-slate-600">
            <span class="mt-1 block text-xs font-normal text-slate-400">Se completa automáticamente desde CAT-019.</span>
        </label>
        <label class="text-sm font-semibold text-slate-700">Teléfono<input name="receiver_phone" value="<?= esc(old('receiver_phone',$dteDocument['receiver_phone']??'')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>
        <label class="text-sm font-semibold text-slate-700">Correo<input name="receiver_email" type="email" value="<?= esc(old('receiver_email',$dteDocument['receiver_email']??'')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>
        <label class="md:col-span-2 text-sm font-semibold text-slate-700"><?= $docCode==='FEX'?'Complemento de dirección':'Dirección fiscal' ?><textarea name="receiver_address" rows="3" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"><?= esc(old('receiver_address',$dteDocument['receiver_address']??'')) ?></textarea></label>
        <div class="md:col-span-2 flex justify-end"><button class="rounded-xl bg-slate-950 px-5 py-3 font-bold text-white">Guardar y validar receptor</button></div>
    </form>
</section>

<section id="dte-items" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-6"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Cuerpo del documento</p><h3 class="mt-2 text-xl font-bold">Detalle y clasificación fiscal</h3></div>
    <div class="divide-y divide-slate-100">
        <?php foreach($dteItems as $item): ?>
        <?php $taxCodes=json_decode((string)($item['tax_codes_json']??'[]'),true)?:[]; $selectedTax=$taxCodes[0]??''; ?>
        <article class="p-5"><div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_420px]">
            <div><div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">#<?= esc((string)$item['sequence']) ?></span><p class="font-bold"><?= esc($item['code']?:'Sin código') ?></p></div><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600"><?= esc($item['description']) ?></p><div class="mt-4 grid gap-3 sm:grid-cols-5 text-sm"><div><p class="text-xs text-slate-500">Cantidad</p><p class="font-semibold"><?= number_format((float)$item['quantity'],3) ?></p></div><div><p class="text-xs text-slate-500">Unidad</p><p class="font-semibold"><?= esc($item['unit_symbol_snapshot']?:$item['unit_name_snapshot']?:'Pendiente') ?></p><p class="text-xs <?= $item['mh_unit_code']?'text-emerald-600':'text-amber-600' ?>"><?= $item['mh_unit_code']?'CAT-014 '.$item['mh_unit_code']:'CAT-014 pendiente' ?></p></div><div><p class="text-xs text-slate-500">Precio</p><p class="font-semibold">$<?= number_format((float)$item['unit_price'],2) ?></p></div><div><p class="text-xs text-slate-500">IVA ítem</p><p class="font-semibold">$<?= number_format((float)$item['iva_item'],2) ?></p></div><div><p class="text-xs text-slate-500">Total</p><p class="font-bold">$<?= number_format((float)$item['line_total'],2) ?></p></div></div></div>
            <form method="post" action="<?= route_to('billing.items.tax.update',(int)$billingCase['id'],(int)$item['id']) ?>" class="rounded-2xl border border-slate-200 bg-slate-50 p-4" data-processing-message="Recalculando impuestos del ítem…"><?= csrf_field() ?><label class="block text-sm font-semibold text-slate-700">Clasificación fiscal<select name="fiscal_classification" data-native="true" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-normal"><option value="taxed" <?= ($item['fiscal_classification']??'taxed')==='taxed'?'selected':'' ?>>Gravada</option><option value="exempt" <?= ($item['fiscal_classification']??'')==='exempt'?'selected':'' ?>>Exenta</option><option value="non_subject" <?= ($item['fiscal_classification']??'')==='non_subject'?'selected':'' ?>>No sujeta</option></select></label><label class="mt-3 block text-sm font-semibold text-slate-700">Tributo CAT-015<select name="tax_code" class="mt-2 w-full" data-placeholder="Seleccionar tributo"><option value="">Sin tributo</option><?php foreach($taxCatalog as $tax): ?><option value="<?= esc($tax['code']) ?>" <?= $selectedTax===$tax['code']?'selected':'' ?>><?= esc($tax['code'].' · '.$tax['name']) ?></option><?php endforeach ?></select></label><button class="mt-4 w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white">Guardar y recalcular</button></form>
        </div></article>
        <?php endforeach ?>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Tax Calculation Engine</p><h3 class="mt-2 text-xl font-bold">Resumen fiscal</h3>
    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5"><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">No sujeto</p><p class="mt-2 text-lg font-bold">$<?= number_format((float)$dteDocument['non_subject_total'],2) ?></p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Exento</p><p class="mt-2 text-lg font-bold">$<?= number_format((float)$dteDocument['exempt_total'],2) ?></p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Gravado</p><p class="mt-2 text-lg font-bold">$<?= number_format((float)$dteDocument['taxed_total'],2) ?></p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">IVA</p><p class="mt-2 text-lg font-bold">$<?= number_format((float)$dteDocument['iva_total'],2) ?></p></div><div class="rounded-xl bg-slate-950 p-4 text-white"><p class="text-xs text-slate-400">Total operación</p><p class="mt-2 text-lg font-bold">$<?= number_format((float)$dteDocument['operation_total'],2) ?></p></div></div>
    <?php if($taxSummary): ?><div class="mt-5 overflow-hidden rounded-xl border border-slate-200"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-4 py-3 text-left">Código</th><th class="px-4 py-3 text-left">Tributo</th><th class="px-4 py-3 text-right">Valor</th></tr></thead><tbody><?php foreach($taxSummary as $tax): ?><tr class="border-t border-slate-100"><td class="px-4 py-3 font-mono font-bold"><?= esc($tax['codigo']) ?></td><td class="px-4 py-3"><?= esc($tax['descripcion']) ?></td><td class="px-4 py-3 text-right font-bold">$<?= number_format((float)$tax['valor'],2) ?></td></tr><?php endforeach ?></tbody></table></div><?php endif ?>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Plan financiero</p><h3 class="mt-2 text-xl font-bold">Calendario esperado de cobro</h3><div class="mt-5 space-y-3"><?php foreach($schedule as $item): ?><article class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-4"><div><p class="font-bold"><?= esc($item['concept']) ?></p><p class="text-xs text-slate-500"><?= number_format((float)$item['percentage'],2) ?>%</p></div><p class="text-lg font-bold">$<?= number_format((float)$item['amount'],2) ?></p></article><?php endforeach ?></div></section>
</div>

<aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
<section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Pre-emisión</p><h3 class="mt-3 text-xl font-bold"><?= $receiverValid?'Receptor fiscal listo':'Complete el receptor' ?></h3><p class="mt-3 text-sm leading-6 text-slate-300"><?= $receiverValid?'Emisor, receptor, unidades y cálculo tributario ya pueden alimentar el futuro JSON Builder.':'Resuelva las validaciones del receptor antes de generar el JSON DTE.' ?></p></section>
<section class="rounded-2xl border border-slate-200 bg-white p-6"><p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Resumen</p><dl class="mt-5 space-y-4 text-sm"><div class="flex justify-between"><dt class="text-slate-500">Ítems</dt><dd class="font-bold"><?= count($dteItems) ?></dd></div><div class="flex justify-between"><dt class="text-slate-500">Receptor</dt><dd class="font-bold <?= $receiverValid?'text-emerald-700':'text-amber-700' ?>"><?= $receiverValid?'Validado':'Pendiente' ?></dd></div><div class="flex justify-between"><dt class="text-slate-500">Total</dt><dd class="font-bold">$<?= number_format((float)$dteDocument['operation_total'],2) ?></dd></div></dl></section>
</aside>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const activity = document.getElementById('receiver_activity_code');
    const activityDescription = document.getElementById('receiver_activity_description');

    function syncActivityDescription() {
        if (!activity || !activityDescription) return;
        const option = activity.options[activity.selectedIndex];
        activityDescription.value = option && option.value ? (option.dataset.description || '') : '';
    }

    if (activity) {
        activity.addEventListener('change', syncActivityDescription);
        syncActivityDescription();
    }

    const department = document.getElementById('receiver_department_code');
    const municipality = document.getElementById('receiver_municipality_code');
    if (!department || !municipality) return;

    const allOptions = Array.from(municipality.options).map(option => ({
        value: option.value,
        text: option.text,
        department: option.dataset.department || '',
        selected: option.selected
    }));

    function filterMunicipalities(preserveSelection = true) {
        const selectedDepartment = department.value;
        const currentSelected = allOptions.find(option => option.selected)?.value || '';
        const previousValue = preserveSelection ? (municipality.value || currentSelected) : '';

        if (municipality.tomselect) municipality.tomselect.destroy();
        municipality.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = selectedDepartment ? 'Seleccionar municipio' : 'Seleccione primero un departamento';
        municipality.appendChild(placeholder);

        allOptions.filter(option => option.value && option.department === selectedDepartment).forEach(option => {
            const element = document.createElement('option');
            element.value = option.value;
            element.textContent = option.text;
            element.dataset.department = option.department;
            if (previousValue && option.value === previousValue) element.selected = true;
            municipality.appendChild(element);
        });

        municipality.disabled = !selectedDepartment;
        if (window.TomSelect && !municipality.disabled) new TomSelect(municipality, {create:false, allowEmptyOption:true});
    }

    department.addEventListener('change', function () { filterMunicipalities(false); });
    filterMunicipalities(true);
});
</script>

<?= $this->endSection() ?>