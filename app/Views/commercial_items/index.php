<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<?= view('components/workspace/page-header', [
    'eyebrow' => 'Quotation Engine',
    'title' => 'Catálogo comercial',
    'description' => 'Administra únicamente los servicios y conceptos aprobados para utilizar en el sistema nuevo.',
    'actionUrl' => route_to('quotations.index'),
    'actionLabel' => 'Volver a cotizaciones',
]) ?>

<div class="grid gap-6 xl:grid-cols-[380px_minmax(0,1fr)]">
    <form method="post" action="<?= route_to('commercial_items.store') ?>" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:sticky xl:top-6 xl:self-start">
        <?= csrf_field() ?>
        <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Nuevo concepto</p>
        <h3 class="mt-2 text-xl font-bold text-slate-950">Agregar al catálogo</h3>
        <div class="mt-6 space-y-5">
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Código *</span><input name="code" value="<?= esc((string) old('code')) ?>" maxlength="50" required class="w-full rounded-xl border border-slate-300 px-4 py-3 uppercase outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="SERV-001"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Nombre *</span><input name="name" value="<?= esc((string) old('name')) ?>" maxlength="190" required class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Ej. Alquiler de grúa telescópica"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Tipo</span><select name="item_type" data-placeholder="Seleccione tipo"><option value="service">Servicio</option><option value="product">Producto</option><option value="equipment_rental">Alquiler de equipo</option><option value="transport">Transporte</option><option value="labor">Mano de obra</option><option value="other">Otro</option></select></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Unidad predeterminada</span><select name="default_unit_id" data-placeholder="Seleccione unidad"><option value="">Sin unidad</option><?php foreach ($units as $unit): ?><option value="<?= esc((string) $unit['id']) ?>"><?= esc($unit['code']) ?> — <?= esc($unit['name']) ?></option><?php endforeach ?></select></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Precio sugerido</span><input type="number" name="suggested_price" value="<?= esc((string) old('suggested_price', '0.00')) ?>" min="0" step="0.01" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Descripción ampliada</span><textarea name="long_description" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"><?= esc((string) old('long_description')) ?></textarea></label>
        </div>
        <button class="mt-6 w-full rounded-xl bg-slate-950 px-5 py-3 font-bold text-white hover:bg-slate-800">Guardar concepto</button>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <?php if ($items === []): ?><div class="p-10 text-center text-slate-500">El catálogo está vacío. Agrega únicamente los conceptos que se utilizarán en TraceOPX.</div><?php else: ?>
        <div class="overflow-x-auto"><table data-trace-table="true" data-export-title="Catálogo comercial TraceOPX" class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><th class="px-5 py-4 text-left font-semibold text-slate-700">Código</th><th class="px-5 py-4 text-left font-semibold text-slate-700">Concepto</th><th class="px-5 py-4 text-left font-semibold text-slate-700">Tipo</th><th class="px-5 py-4 text-left font-semibold text-slate-700">Unidad</th><th class="px-5 py-4 text-right font-semibold text-slate-700">Precio</th></tr></thead><tbody class="divide-y divide-slate-100"><?php foreach ($items as $item): ?><tr class="hover:bg-slate-50"><td class="px-5 py-4 font-bold text-cyan-700"><?= esc($item['code']) ?></td><td class="px-5 py-4"><p class="font-semibold text-slate-950"><?= esc($item['name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($item['long_description'] ?: 'Sin descripción ampliada') ?></p></td><td class="px-5 py-4"><?= esc($item['item_type']) ?></td><td class="px-5 py-4"><?= esc($item['unit_name'] ?: 'Sin unidad') ?></td><td class="px-5 py-4 text-right font-semibold">$<?= esc(number_format((float) $item['suggested_price'], 2)) ?></td></tr><?php endforeach ?></tbody></table></div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
