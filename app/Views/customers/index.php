<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<?php if (session('success')): ?>
    <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div>
<?php endif ?>
<?php if (session('error')): ?>
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div>
<?php endif ?>

<div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-600">Gestión comercial</p>
        <h3 class="mt-2 text-3xl font-bold text-slate-950">Clientes</h3>
        <p class="mt-2 text-slate-600">Directorio central para cotizaciones, órdenes, facturación y cobros.</p>
    </div>
    <a href="<?= route_to('customers.create') ?>" class="rounded-xl bg-slate-950 px-5 py-3 text-center text-sm font-semibold text-white">Nuevo cliente</a>
</div>

<form method="get" action="<?= route_to('customers.index') ?>" class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
    <input type="search" name="q" value="<?= esc($search) ?>" placeholder="Código, nombre, NIT, correo o teléfono" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-cyan-500">
    <button class="rounded-xl bg-cyan-500 px-5 py-3 font-semibold text-slate-950">Buscar</button>
    <?php if ($search !== ''): ?>
        <a href="<?= route_to('customers.index') ?>" class="rounded-xl border border-slate-300 px-5 py-3 text-center font-semibold text-slate-700">Limpiar</a>
    <?php endif ?>
</form>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-5 py-4">Código</th>
                    <th class="px-5 py-4">Cliente</th>
                    <th class="px-5 py-4">Documento</th>
                    <th class="px-5 py-4">Contacto</th>
                    <th class="px-5 py-4">Estado</th>
                    <th class="px-5 py-4 text-right">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($customers as $customer): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4 font-bold text-cyan-700"><?= esc($customer['code']) ?></td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-slate-950"><?= esc($customer['business_name']) ?></p>
                            <?php if ($customer['trade_name']): ?><p class="text-slate-500"><?= esc($customer['trade_name']) ?></p><?php endif ?>
                        </td>
                        <td class="px-5 py-4 text-slate-600"><?= esc($customer['tax_id'] ?: '—') ?></td>
                        <td class="px-5 py-4 text-slate-600">
                            <p><?= esc($customer['email'] ?: '—') ?></p>
                            <p><?= esc($customer['phone'] ?: '') ?></p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold <?= (int) $customer['status'] === 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' ?>">
                                <?= (int) $customer['status'] === 1 ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right"><a href="<?= route_to('customers.show', $customer['id']) ?>" class="font-semibold text-cyan-700">Ver detalle</a></td>
                    </tr>
                <?php endforeach ?>
                <?php if ($customers === []): ?>
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-500">No se encontraron clientes.</td></tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
