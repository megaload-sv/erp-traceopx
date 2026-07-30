<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>

<div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <a href="<?= route_to('customers.index') ?>" class="text-sm font-semibold text-cyan-700">← Volver a clientes</a>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <h3 class="text-3xl font-bold text-slate-950"><?= esc($customer['business_name']) ?></h3>
            <span class="rounded-full px-3 py-1 text-xs font-semibold <?= (int) $customer['status'] === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' ?>"><?= (int) $customer['status'] === 1 ? 'Activo' : 'Inactivo' ?></span>
        </div>
        <p class="mt-2 font-semibold text-cyan-700"><?= esc($customer['code']) ?></p>
    </div>
    <a href="<?= route_to('customers.edit', $customer['id']) ?>" class="rounded-xl bg-slate-950 px-5 py-3 text-center text-sm font-semibold text-white">Editar cliente</a>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="text-lg font-bold text-slate-950">Información general</h4>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nombre comercial</dt><dd class="mt-1 text-slate-900"><?= esc($customer['trade_name'] ?: '—') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo</dt><dd class="mt-1 text-slate-900"><?= $customer['customer_type'] === 'company' ? 'Empresa' : 'Persona natural' ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NIT / Documento</dt><dd class="mt-1 text-slate-900"><?= esc($customer['tax_id'] ?: '—') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">NRC / Registro</dt><dd class="mt-1 text-slate-900"><?= esc($customer['registration_number'] ?: '—') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Correo</dt><dd class="mt-1 text-slate-900"><?= esc($customer['email'] ?: '—') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Teléfono</dt><dd class="mt-1 text-slate-900"><?= esc($customer['phone'] ?: '—') ?></dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Observaciones</dt><dd class="mt-1 whitespace-pre-line text-slate-900"><?= esc($customer['notes'] ?: 'Sin observaciones.') ?></dd></div>
            </dl>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="text-lg font-bold text-slate-950">Contactos</h4>
                <div class="mt-5 space-y-4">
                    <?php foreach ($contacts as $contact): ?><article class="rounded-xl bg-slate-50 p-4"><p class="font-semibold text-slate-950"><?= esc($contact['name']) ?><?= (int) $contact['is_primary'] === 1 ? ' · Principal' : '' ?></p><p class="text-sm text-slate-500"><?= esc($contact['position'] ?: 'Sin cargo') ?></p><p class="mt-2 text-sm text-slate-700"><?= esc($contact['email'] ?: '—') ?></p><p class="text-sm text-slate-700"><?= esc($contact['phone'] ?: '—') ?></p></article><?php endforeach ?>
                    <?php if ($contacts === []): ?><p class="text-sm text-slate-500">No hay contactos registrados.</p><?php endif ?>
                </div>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h4 class="text-lg font-bold text-slate-950">Direcciones</h4>
                <div class="mt-5 space-y-4">
                    <?php foreach ($addresses as $address): ?><article class="rounded-xl bg-slate-50 p-4"><p class="font-semibold capitalize text-slate-950"><?= esc($address['address_type']) ?><?= (int) $address['is_primary'] === 1 ? ' · Principal' : '' ?></p><p class="mt-2 text-sm text-slate-700"><?= esc($address['address_line']) ?></p><p class="text-sm text-slate-500"><?= esc(implode(', ', array_filter([$address['municipality'], $address['department'], $address['country']]))) ?></p></article><?php endforeach ?>
                    <?php if ($addresses === []): ?><p class="text-sm text-slate-500">No hay direcciones registradas.</p><?php endif ?>
                </div>
            </section>
        </div>
    </div>

    <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-600">Bitácora funcional</p>
        <h4 class="mt-2 text-xl font-bold text-slate-950">Historia del cliente</h4>
        <div class="mt-6 space-y-6">
            <?php foreach ($activities as $activity): ?>
                <article class="relative border-l-2 border-cyan-500 pl-5">
                    <span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full bg-cyan-500"></span>
                    <p class="font-semibold text-slate-950"><?= esc($activity['title']) ?></p>
                    <?php if ($activity['description']): ?><p class="mt-1 text-sm text-slate-600"><?= esc($activity['description']) ?></p><?php endif ?>
                    <p class="mt-2 text-xs text-slate-500"><?= esc(date('d/m/Y H:i', strtotime($activity['occurred_at']))) ?> · <?= esc($activity['actor_user']) ?></p>
                </article>
            <?php endforeach ?>
            <?php if ($activities === []): ?><p class="text-sm text-slate-500">Aún no existen eventos funcionales.</p><?php endif ?>
        </div>
    </aside>
</div>
<?= $this->endSection() ?>
