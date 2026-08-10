<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= esc(session('error')) ?></div><?php endif ?>

<div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div><p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">DTE Engine</p><h2 class="mt-2 text-3xl font-bold text-slate-950">Emisor y parámetros de emisión</h2><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Centraliza los datos fiscales que alimentarán el bloque <code>emisor</code> de cada DTE y define desde qué sucursal/punto de venta se emitirán los documentos por defecto.</p></div>
    <a href="<?= route_to('dte_settings.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">← Configuración DTE</a>
</div>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
<div class="space-y-6">
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Emisor</p><h3 class="mt-2 text-xl font-bold">Identidad fiscal de MEGALOAD</h3><p class="mt-2 text-sm text-slate-500">Estos datos se copiarán como snapshot al momento de preparar definitivamente cada DTE.</p></div>
    <form method="post" action="<?= route_to('dte_settings.issuer.save') ?>" class="mt-6 grid gap-4 md:grid-cols-2" data-processing-message="Guardando configuración fiscal del emisor…">
        <?= csrf_field() ?>
        <label class="md:col-span-2 text-sm font-semibold text-slate-700">Nombre o razón social<input name="legal_name" required maxlength="250" value="<?= esc(old('legal_name', $issuer['legal_name'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>
        <label class="md:col-span-2 text-sm font-semibold text-slate-700">Nombre comercial<input name="trade_name" maxlength="150" value="<?= esc(old('trade_name', $issuer['trade_name'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>
        <label class="text-sm font-semibold text-slate-700">NIT<input name="nit" required maxlength="20" value="<?= esc(old('nit', $issuer['nit'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal uppercase"></label>
        <label class="text-sm font-semibold text-slate-700">NRC<input name="nrc" maxlength="15" value="<?= esc(old('nrc', $issuer['nrc'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal uppercase"></label>
        <label class="text-sm font-semibold text-slate-700">Código actividad económica<input name="activity_code" required maxlength="10" value="<?= esc(old('activity_code', $issuer['activity_code'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal"></label>
        <label class="text-sm font-semibold text-slate-700">Tipo establecimiento MH<input name="establishment_type_code" maxlength="4" value="<?= esc(old('establishment_type_code', $issuer['establishment_type_code'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal uppercase"></label>
        <label class="md:col-span-2 text-sm font-semibold text-slate-700">Descripción actividad económica<input name="activity_description" required maxlength="200" value="<?= esc(old('activity_description', $issuer['activity_description'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>
        <label class="text-sm font-semibold text-slate-700">Departamento MH<input name="department_code" required maxlength="4" value="<?= esc(old('department_code', $issuer['department_code'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal"></label>
        <label class="text-sm font-semibold text-slate-700">Municipio MH<input name="municipality_code" required maxlength="4" value="<?= esc(old('municipality_code', $issuer['municipality_code'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal"></label>
        <label class="md:col-span-2 text-sm font-semibold text-slate-700">Dirección complementaria<input name="address_complement" required maxlength="300" value="<?= esc(old('address_complement', $issuer['address_complement'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>
        <label class="text-sm font-semibold text-slate-700">Teléfono<input name="phone" required maxlength="30" value="<?= esc(old('phone', $issuer['phone'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>
        <label class="text-sm font-semibold text-slate-700">Correo electrónico<input name="email" type="email" required maxlength="120" value="<?= esc(old('email', $issuer['email'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-normal"></label>
        <label class="text-sm font-semibold text-slate-700">Código establecimiento MH<input name="mh_establishment_code" maxlength="10" value="<?= esc(old('mh_establishment_code', $issuer['mh_establishment_code'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal uppercase"></label>
        <label class="text-sm font-semibold text-slate-700">Código establecimiento alterno<input name="mh_establishment_code_alt" maxlength="10" value="<?= esc(old('mh_establishment_code_alt', $issuer['mh_establishment_code_alt'] ?? '')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 px-4 py-3 font-mono font-normal uppercase"></label>
        <div class="md:col-span-2"><button class="rounded-xl bg-slate-950 px-5 py-3 font-bold text-white">Guardar configuración del emisor</button></div>
    </form>
</section>

<section id="emission" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Emisión</p><h3 class="mt-2 text-xl font-bold">Parámetros predeterminados</h3><p class="mt-2 text-sm text-slate-500">Se utilizarán al crear nuevos DTE y podrán convertirse en snapshot específico del documento antes de numerarlo.</p>
    <form method="post" action="<?= route_to('dte_settings.emission.save') ?>" class="mt-6 grid gap-4 md:grid-cols-2" data-processing-message="Actualizando parámetros de emisión DTE…">
        <?= csrf_field() ?>
        <label class="text-sm font-semibold text-slate-700">Ambiente<select name="environment" data-native="true" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-normal"><option value="00" <?= ($emission['environment'] ?? '00')==='00'?'selected':'' ?>>00 · Pruebas</option><option value="01" <?= ($emission['environment'] ?? '')==='01'?'selected':'' ?>>01 · Producción</option></select></label>
        <div></div>
        <label class="md:col-span-2 text-sm font-semibold text-slate-700">Sucursal predeterminada<select name="default_establishment_id" required class="mt-2 w-full" data-placeholder="Seleccionar sucursal"><option value="">Seleccionar</option><?php foreach($establishments as $row): ?><option value="<?= (int)$row['id'] ?>" <?= (int)($emission['default_establishment_id'] ?? 0)===(int)$row['id']?'selected':'' ?>><?= esc($row['name']) ?> · <?= esc($row['mh_code']) ?></option><?php endforeach ?></select></label>
        <label class="md:col-span-2 text-sm font-semibold text-slate-700">Punto de venta predeterminado<select name="default_point_of_sale_id" required class="mt-2 w-full" data-placeholder="Seleccionar punto de venta"><option value="">Seleccionar</option><?php foreach($pointsOfSale as $row): ?><option value="<?= (int)$row['id'] ?>" data-establishment="<?= (int)$row['establishment_id'] ?>" <?= (int)($emission['default_point_of_sale_id'] ?? 0)===(int)$row['id']?'selected':'' ?>><?= esc($row['establishment_name']) ?> · <?= esc($row['name']) ?> · <?= esc($row['establishment_mh_code'].$row['mh_code']) ?></option><?php endforeach ?></select></label>
        <div class="md:col-span-2"><button class="rounded-xl bg-cyan-600 px-5 py-3 font-bold text-white">Guardar parámetros de emisión</button></div>
    </form>
</section>
</div>

<aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
<section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Estado de configuración</p><h3 class="mt-3 text-xl font-bold"><?= $issuer ? 'Emisor configurado' : 'Emisor pendiente' ?></h3><p class="mt-3 text-sm leading-6 text-slate-300"><?= $issuer ? 'La identidad fiscal base está registrada. Aún validaremos estos códigos contra los catálogos MH antes de habilitar emisión.' : 'Complete la información del emisor antes de generar el JSON definitivo.' ?></p></section>
<section class="rounded-2xl border border-amber-200 bg-amber-50 p-6"><p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Seguridad</p><h3 class="mt-2 font-bold text-amber-950">Credenciales fuera de esta tabla</h3><p class="mt-3 text-sm leading-6 text-amber-900">Firma electrónica, token/API y secretos de Hacienda se incorporarán mediante configuración protegida. No se almacenarán como texto plano dentro de <code>dte_settings</code>.</p></section>
<section class="rounded-2xl border border-slate-200 bg-white p-6"><p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Pendientes de catálogo</p><p class="mt-3 text-sm leading-6 text-slate-600">Actividad económica, departamento, municipio y tipo de establecimiento todavía se capturan por código. En el siguiente incremento los conectaremos a los catálogos MH del Excel para evitar digitación manual.</p></section>
</aside>
</div>

<?= $this->endSection() ?>
