<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php $editing = is_array($contact); ?>

<div class="mb-7">
    <a href="<?= route_to('customers.show', $customer['id']) ?>" class="text-sm font-semibold text-cyan-700">← Volver al cliente</a>
    <p class="mt-4 text-xs font-semibold uppercase tracking-[0.24em] text-cyan-600">Contactos del cliente</p>
    <h3 class="mt-2 text-3xl font-bold text-slate-950"><?= $editing ? 'Editar contacto' : 'Agregar contacto' ?></h3>
    <p class="mt-2 text-slate-600"><?= esc($customer['business_name']) ?> · registra únicamente la información necesaria para comunicarnos y coordinar el servicio.</p>
</div>

<?php if (session('errors')): ?>
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
        <?php foreach (session('errors') as $error): ?><p><?= esc($error) ?></p><?php endforeach ?>
    </div>
<?php endif ?>

<form method="post" action="<?= $editing ? route_to('customers.contacts.update', $customer['id'], $contact['id']) : route_to('customers.contacts.store', $customer['id']) ?>" class="mx-auto max-w-4xl space-y-6">
    <?= csrf_field() ?>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid gap-5 md:grid-cols-2">
            <label class="block md:col-span-2">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Nombre completo *</span>
                <input required name="name" value="<?= esc(old('name', $contact['name'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-cyan-500 focus:outline-none">
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Cargo</span>
                <input name="position" value="<?= esc(old('position', $contact['position'] ?? '')) ?>" placeholder="Ej. Gerente de proyecto" class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Función dentro de la relación</span>
                <select name="contact_role" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                    <?php foreach (['commercial' => 'Comercial', 'technical' => 'Técnico', 'billing' => 'Facturación', 'other' => 'Otro'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= old('contact_role', $contact['contact_role'] ?? 'commercial') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach ?>
                </select>
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Correo</span>
                <input type="email" name="email" value="<?= esc(old('email', $contact['email'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <label class="block">
                <span class="mb-2 block text-sm font-semibold text-slate-700">Teléfono</span>
                <input name="phone" value="<?= esc(old('phone', $contact['phone'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3">
            </label>
            <?php if ($editing): ?>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Estado</span>
                    <select name="status" class="w-full rounded-xl border border-slate-300 px-4 py-3">
                        <option value="1" <?= (int) old('status', $contact['status']) === 1 ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= (int) old('status', $contact['status']) === 0 ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </label>
            <?php endif ?>
            <label class="flex items-start gap-3 rounded-xl border border-cyan-200 bg-cyan-50 p-4 md:col-span-2">
                <input type="checkbox" name="is_primary" value="1" <?= (int) old('is_primary', $contact['is_primary'] ?? 0) === 1 ? 'checked' : '' ?> class="mt-1 h-4 w-4 rounded border-slate-300">
                <span><strong class="block text-slate-900">Usar como contacto principal</strong><small class="text-slate-600">Será el contacto sugerido por defecto al crear una cotización o comunicación. El primer contacto del cliente se selecciona automáticamente.</small></span>
            </label>
        </div>
    </section>

    <div class="flex justify-end gap-3">
        <a href="<?= route_to('customers.show', $customer['id']) ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Cancelar</a>
        <button class="rounded-xl bg-slate-950 px-6 py-3 font-semibold text-white"><?= $editing ? 'Guardar contacto' : 'Agregar contacto' ?></button>
    </div>
</form>
<?= $this->endSection() ?>
