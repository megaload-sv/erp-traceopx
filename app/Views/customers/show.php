<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$stageLabels = ['potential' => 'Potencial', 'active' => 'Activo', 'inactive' => 'Inactivo / por reactivar'];
$tierLabels = ['standard' => 'Estándar', 'preferential' => 'Preferencial', 'strategic' => 'Estratégico'];
$roleLabels = ['commercial' => 'Comercial', 'technical' => 'Técnico', 'billing' => 'Facturación', 'other' => 'Otro'];
?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<section class="mb-6 rounded-3xl bg-slate-950 p-6 text-white shadow-sm lg:p-8">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="<?= route_to('customers.index') ?>" class="text-sm font-semibold text-cyan-300">← Volver a clientes</a>
            <p class="mt-5 text-sm font-bold uppercase tracking-[0.22em] text-cyan-400"><?= esc($customer['code']) ?></p>
            <h3 class="mt-2 text-3xl font-bold lg:text-4xl"><?= esc($customer['business_name']) ?></h3>
            <?php if ($customer['trade_name']): ?><p class="mt-2 text-slate-300"><?= esc($customer['trade_name']) ?></p><?php endif ?>
            <div class="mt-5 flex flex-wrap gap-2">
                <span class="rounded-full bg-cyan-400/15 px-3 py-1 text-xs font-semibold text-cyan-200"><?= esc($stageLabels[$customer['lifecycle_stage']] ?? $customer['lifecycle_stage']) ?></span>
                <span class="rounded-full bg-violet-400/15 px-3 py-1 text-xs font-semibold text-violet-200"><?= esc($tierLabels[$customer['relationship_tier']] ?? $customer['relationship_tier']) ?></span>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="<?= route_to('customers.contacts.create', $customer['id']) ?>" class="rounded-xl border border-slate-700 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-900">Agregar contacto</a>
            <span class="rounded-xl border border-slate-700 px-4 py-3 text-sm text-slate-300">Nueva cotización · Próximamente</span>
            <a href="<?= route_to('customers.edit', $customer['id']) ?>" class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-950">Editar perfil</a>
        </div>
    </div>
</section>

<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ([['Cotizaciones', $commercialSummary['quotations']], ['Órdenes activas', $commercialSummary['activeOrders']], ['Facturado', '$' . number_format($commercialSummary['invoiced'], 2)], ['Pendiente de cobro', '$' . number_format($commercialSummary['receivable'], 2)]] as [$label, $value]): ?>
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm text-slate-500"><?= esc($label) ?></p><p class="mt-3 text-3xl font-bold text-slate-950"><?= esc((string) $value) ?></p></article>
    <?php endforeach ?>
</div>

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="rounded-2xl border border-cyan-200 bg-cyan-50/40 p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">Relación comercial</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Ejecutivo responsable</p><p class="mt-1 font-semibold text-slate-950"><?= esc($customer['assigned_sales_user'] ?: 'Sin asignar') ?></p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Próximo seguimiento</p><p class="mt-1 font-semibold text-slate-950"><?= $customer['next_follow_up_date'] ? esc(date('d/m/Y', strtotime($customer['next_follow_up_date']))) : 'Sin programar' ?></p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Etapa</p><p class="mt-1 font-semibold text-slate-950"><?= esc($stageLabels[$customer['lifecycle_stage']] ?? $customer['lifecycle_stage']) ?></p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Categoría</p><p class="mt-1 font-semibold text-slate-950"><?= esc($tierLabels[$customer['relationship_tier']] ?? $customer['relationship_tier']) ?></p></div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h4 class="text-lg font-bold text-slate-950">Información general</h4>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Tipo</dt><dd class="mt-1"><?= $customer['customer_type'] === 'company' ? 'Empresa' : 'Persona natural' ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">NIT / Documento</dt><dd class="mt-1"><?= esc($customer['tax_id'] ?: '—') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">NRC / Registro</dt><dd class="mt-1"><?= esc($customer['registration_number'] ?: '—') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Correo</dt><dd class="mt-1"><?= esc($customer['email'] ?: '—') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Teléfono</dt><dd class="mt-1"><?= esc($customer['phone'] ?: '—') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Sitio web</dt><dd class="mt-1"><?= esc($customer['website'] ?: '—') ?></dd></div>
                <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase text-slate-500">Observaciones</dt><dd class="mt-1 whitespace-pre-line"><?= esc($customer['notes'] ?: 'Sin observaciones.') ?></dd></div>
            </dl>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-600">Relación humana</p><h4 class="mt-1 text-lg font-bold text-slate-950">Contactos del cliente</h4><p class="mt-1 text-sm text-slate-500">El contacto principal será sugerido por defecto en cotizaciones y comunicaciones.</p></div>
                <a href="<?= route_to('customers.contacts.create', $customer['id']) ?>" class="rounded-xl bg-slate-950 px-4 py-2.5 text-center text-sm font-semibold text-white">Agregar contacto</a>
            </div>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <?php foreach ($contacts as $contact): ?>
                    <article class="rounded-2xl border <?= (int) $contact['is_primary'] === 1 ? 'border-cyan-300 bg-cyan-50/50' : 'border-slate-200 bg-slate-50' ?> p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="font-bold text-slate-950"><?= esc($contact['name']) ?></p><p class="mt-1 text-sm text-slate-500"><?= esc($contact['position'] ?: 'Sin cargo') ?></p></div>
                            <?php if ((int) $contact['is_primary'] === 1): ?><span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-800">Principal</span><?php endif ?>
                        </div>
                        <span class="mt-4 inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200"><?= esc($roleLabels[$contact['contact_role']] ?? 'Otro') ?></span>
                        <div class="mt-4 space-y-1 text-sm text-slate-700"><p><?= esc($contact['email'] ?: 'Sin correo') ?></p><p><?= esc($contact['phone'] ?: 'Sin teléfono') ?></p></div>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <a href="<?= route_to('customers.contacts.edit', $customer['id'], $contact['id']) ?>" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Editar</a>
                            <?php if ((int) $contact['is_primary'] !== 1 && (int) $contact['status'] === 1): ?>
                                <form method="post" action="<?= route_to('customers.contacts.primary', $customer['id'], $contact['id']) ?>"><?= csrf_field() ?><button class="rounded-lg bg-cyan-500 px-3 py-2 text-xs font-semibold text-slate-950">Usar como principal</button></form>
                            <?php endif ?>
                        </div>
                    </article>
                <?php endforeach ?>
                <?php if ($contacts === []): ?>
                    <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center md:col-span-2"><p class="font-semibold text-slate-900">Este cliente aún no tiene contactos.</p><p class="mt-1 text-sm text-slate-500">Agrega la primera persona con quien se gestionará la relación comercial.</p><a href="<?= route_to('customers.contacts.create', $customer['id']) ?>" class="mt-4 inline-flex rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white">Agregar primer contacto</a></div>
                <?php endif ?>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h4 class="text-lg font-bold text-slate-950">Sucursales y direcciones</h4><div class="mt-5 space-y-4"><?php foreach ($addresses as $address): ?><article class="rounded-xl bg-slate-50 p-4"><p class="font-semibold capitalize text-slate-950"><?= esc($address['address_type']) ?><?= (int) $address['is_primary'] === 1 ? ' · Principal' : '' ?></p><p class="mt-2 text-sm"><?= esc($address['address_line']) ?></p><p class="text-sm text-slate-500"><?= esc(implode(', ', array_filter([$address['municipality'], $address['department'], $address['country']]))) ?></p></article><?php endforeach ?><?php if ($addresses === []): ?><p class="text-sm text-slate-500">No hay direcciones registradas.</p><?php endif ?></div></section>
    </div>

    <aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-600">Bitácora funcional</p><h4 class="mt-2 text-xl font-bold text-slate-950">Historia del cliente</h4>
        <div class="mt-6 space-y-6"><?php foreach ($activities as $activity): ?><article class="relative border-l-2 border-cyan-500 pl-5"><span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full bg-cyan-500"></span><p class="font-semibold text-slate-950"><?= esc($activity['title']) ?></p><?php if ($activity['description']): ?><p class="mt-1 text-sm text-slate-600"><?= esc($activity['description']) ?></p><?php endif ?><p class="mt-2 text-xs text-slate-500"><?= esc(date('d/m/Y H:i', strtotime($activity['occurred_at']))) ?> · <?= esc($activity['actor_user']) ?></p></article><?php endforeach ?><?php if ($activities === []): ?><p class="text-sm text-slate-500">Aún no existen eventos funcionales.</p><?php endif ?></div>
    </aside>
</div>
<?= $this->endSection() ?>
