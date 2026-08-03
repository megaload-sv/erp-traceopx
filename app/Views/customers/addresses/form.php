<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php $editing = is_array($address); ?>

<div class="mx-auto max-w-3xl">
    <a href="<?= route_to('customers.show', $customer['id']) ?>" class="text-sm font-semibold text-cyan-700">← Volver al perfil</a>
    <div class="mt-4">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-cyan-600">Ubicaciones del cliente</p>
        <h3 class="mt-2 text-3xl font-bold text-slate-950"><?= $editing ? 'Editar dirección' : 'Agregar dirección' ?></h3>
        <p class="mt-2 text-slate-600"><?= esc($customer['business_name']) ?></p>
    </div>

    <?php if (session('errors')): ?><div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?php foreach (session('errors') as $error): ?><p><?= esc($error) ?></p><?php endforeach ?></div><?php endif ?>

    <form method="post" action="<?= $editing ? route_to('customers.addresses.update', $customer['id'], $address['id']) : route_to('customers.addresses.store', $customer['id']) ?>" class="mt-6 space-y-6">
        <?= csrf_field() ?>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 md:grid-cols-2">
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Nombre de la ubicación</span><input name="label" value="<?= esc(old('label', $address['label'] ?? '')) ?>" placeholder="Casa matriz, Sucursal Santa Ana..." class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Tipo</span><select name="address_type" class="w-full rounded-xl border border-slate-300 px-4 py-3"><option value="fiscal" <?= old('address_type', $address['address_type'] ?? 'operational') === 'fiscal' ? 'selected' : '' ?>>Fiscal</option><option value="operational" <?= old('address_type', $address['address_type'] ?? 'operational') === 'operational' ? 'selected' : '' ?>>Operativa / sucursal</option><option value="other" <?= old('address_type', $address['address_type'] ?? '') === 'other' ? 'selected' : '' ?>>Otra</option></select></label>
                <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Dirección completa *</span><textarea required name="address_line" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('address_line', $address['address_line'] ?? '')) ?></textarea></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Municipio</span><input name="municipality" value="<?= esc(old('municipality', $address['municipality'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Departamento</span><input name="department" value="<?= esc(old('department', $address['department'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">País</span><input name="country" value="<?= esc(old('country', $address['country'] ?? 'El Salvador')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <?php if ($editing): ?><label><span class="mb-2 block text-sm font-semibold text-slate-700">Estado</span><select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3"><option value="1" <?= (int) old('status', $address['status']) === 1 ? 'selected' : '' ?>>Activa</option><option value="0" <?= (int) old('status', $address['status']) === 0 ? 'selected' : '' ?>>Inactiva</option></select></label><?php endif ?>
            </div>
        </section>
        <div class="flex justify-end gap-3"><a href="<?= route_to('customers.show', $customer['id']) ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Cancelar</a><button class="rounded-xl bg-slate-950 px-6 py-3 font-semibold text-white"><?= $editing ? 'Guardar cambios' : 'Agregar dirección' ?></button></div>
    </form>
</div>
<?= $this->endSection() ?>
