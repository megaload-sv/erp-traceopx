<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $editing = is_array($customer); ?>
<?php if (session('error')): ?>
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div>
<?php endif ?>
<?php if (session('errors')): ?>
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
        <?php foreach (session('errors') as $error): ?><p><?= esc($error) ?></p><?php endforeach ?>
    </div>
<?php endif ?>

<div class="mb-7">
    <a href="<?= route_to('customers.index') ?>" class="text-sm font-semibold text-cyan-700">← Volver a clientes</a>
    <h3 class="mt-3 text-3xl font-bold text-slate-950"><?= $editing ? 'Editar cliente' : 'Nuevo cliente' ?></h3>
    <p class="mt-2 text-slate-600">Registra los datos generales y, opcionalmente, el contacto y la dirección fiscal principal.</p>
</div>

<form method="post" action="<?= $editing ? route_to('customers.update', $customer['id']) : route_to('customers.store') ?>" class="space-y-6">
    <?= csrf_field() ?>
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="text-lg font-bold text-slate-950">Datos generales</h4>
        <div class="mt-5 grid gap-5 md:grid-cols-2">
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Tipo de cliente</span><select name="customer_type" class="w-full rounded-xl border border-slate-300 px-4 py-3"><option value="company" <?= old('customer_type', $customer['customer_type'] ?? 'company') === 'company' ? 'selected' : '' ?>>Empresa</option><option value="person" <?= old('customer_type', $customer['customer_type'] ?? '') === 'person' ? 'selected' : '' ?>>Persona natural</option></select></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Razón social o nombre *</span><input required name="business_name" value="<?= esc(old('business_name', $customer['business_name'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Nombre comercial</span><input name="trade_name" value="<?= esc(old('trade_name', $customer['trade_name'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">NIT / Documento</span><input name="tax_id" value="<?= esc(old('tax_id', $customer['tax_id'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">NRC / Registro</span><input name="registration_number" value="<?= esc(old('registration_number', $customer['registration_number'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Correo</span><input type="email" name="email" value="<?= esc(old('email', $customer['email'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Teléfono</span><input name="phone" value="<?= esc(old('phone', $customer['phone'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Sitio web</span><input name="website" value="<?= esc(old('website', $customer['website'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <?php if ($editing): ?><label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Estado</span><select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3"><option value="1" <?= (int) old('status', $customer['status']) === 1 ? 'selected' : '' ?>>Activo</option><option value="0" <?= (int) old('status', $customer['status']) === 0 ? 'selected' : '' ?>>Inactivo</option></select></label><?php endif ?>
            <label class="block md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Observaciones</span><textarea name="notes" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('notes', $customer['notes'] ?? '')) ?></textarea></label>
        </div>
    </section>

    <?php if (! $editing): ?>
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="text-lg font-bold text-slate-950">Contacto principal</h4>
            <div class="mt-5 space-y-4">
                <input name="contact_name" value="<?= esc(old('contact_name')) ?>" placeholder="Nombre completo" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                <input name="contact_position" value="<?= esc(old('contact_position')) ?>" placeholder="Cargo" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                <input type="email" name="contact_email" value="<?= esc(old('contact_email')) ?>" placeholder="Correo" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                <input name="contact_phone" value="<?= esc(old('contact_phone')) ?>" placeholder="Teléfono" class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>
        </section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="text-lg font-bold text-slate-950">Dirección fiscal</h4>
            <div class="mt-5 space-y-4">
                <textarea name="address_line" rows="3" placeholder="Dirección completa" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('address_line')) ?></textarea>
                <input name="municipality" value="<?= esc(old('municipality')) ?>" placeholder="Municipio" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                <input name="department" value="<?= esc(old('department')) ?>" placeholder="Departamento" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                <input name="country" value="<?= esc(old('country', 'El Salvador')) ?>" placeholder="País" class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </div>
        </section>
    </div>
    <?php endif ?>

    <div class="flex justify-end gap-3"><a href="<?= route_to('customers.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Cancelar</a><button class="rounded-xl bg-slate-950 px-6 py-3 font-semibold text-white"><?= $editing ? 'Guardar cambios' : 'Crear cliente' ?></button></div>
</form>
<?= $this->endSection() ?>
