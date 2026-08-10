<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php if(session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if(session('error')): ?><div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= esc(session('error')) ?></div><?php endif ?>

<?php
$receiverValid = ($dteDocument['receiver_validation_status'] ?? 'pending') === 'valid';
$receiverModified = (bool)($receiverMeta['is_modified'] ?? false);
$docCode = (string)($dteDocument['document_code'] ?? '');
$activities = $receiverCatalogs['activities'] ?? [];
$departments = $receiverCatalogs['departments'] ?? [];
$municipalities = $receiverCatalogs['municipalities'] ?? [];
$receiverDocumentType = (string) old('receiver_document_type', (string)($dteDocument['receiver_document_type'] ?? ''));
$receiverActivityCode = (string) old('receiver_activity_code', (string)($dteDocument['receiver_activity_code'] ?? ''));
$receiverActivityDescription = (string) old('receiver_activity_description', (string)($dteDocument['receiver_activity_description'] ?? ''));
$receiverDepartmentCode = (string) old('receiver_department_code', (string)($dteDocument['receiver_department_code'] ?? ''));
$receiverMunicipalityCode = (string) old('receiver_municipality_code', (string)($dteDocument['receiver_municipality_code'] ?? ''));
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
    <div class="flex flex-wrap gap-3">
        <a href="<?= route_to('billing.dte_json.preview', (int)$billingCase['id']) ?>?view=console" class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-slate-800" title="Abrir validaciones y JSON técnico del DTE">
            <span aria-hidden="true">⚙</span>
            <span>Consola técnica DTE</span>
        </a>
        <a href="<?= route_to('billing.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Volver a facturación</a>
    </div>
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

<!-- Remaining DTE workspace content is intentionally unchanged. -->
<?= $this->include('billing/_show_workspace_content') ?>

</div>
</div>

<?= $this->endSection() ?>
