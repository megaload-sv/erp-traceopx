<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<?= view('components/workspace/page-header', [
    'eyebrow' => 'Quotation Engine',
    'title' => 'Catálogo comercial',
    'description' => 'Conceptos normalizados por grupo y unidad para agilizar la preparación de cotizaciones.',
    'actionUrl' => route_to('quotations.index'),
    'actionLabel' => 'Volver a cotizaciones',
]) ?>

<div class="mb-6 grid gap-4 md:grid-cols-3">
    <?= view('components/workspace/metric-card', ['label'=>'Conceptos activos','value'=>count($items),'description'=>'Catálogo disponible para cotizar','tone'=>'cyan']) ?>
    <?= view('components/workspace/metric-card', ['label'=>'Grupos','value'=>count($groups),'description'=>'Clasificación comercial normalizada','tone'=>'violet']) ?>
    <?= view('components/workspace/metric-card', ['label'=>'Unidades','value'=>count($units),'description'=>'Unidades de cobro estandarizadas','tone'=>'emerald']) ?>
</div>

<div class="grid gap-6 xl:grid-cols-[390px_minmax(0,1fr)]">
    <form method="post" action="<?= route_to('commercial_items.store') ?>" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:sticky xl:top-6 xl:self-start">
        <?= csrf_field() ?>
        <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Nuevo concepto</p>
        <h3 class="mt-2 text-xl font-bold text-slate-950">Agregar al catálogo</h3>
        <p class="mt-2 text-sm leading-6 text-slate-500">La unidad elegida se precargará automáticamente al seleccionar este concepto en una cotización.</p>
        <div class="mt-6 space-y-5">
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Código *</span><input name="code" value="<?= esc((string) old('code')) ?>" maxlength="50" required class="w-full rounded-xl border border-slate-300 px-4 py-3 uppercase outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="SERV-001"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Nombre *</span><input name="name" value="<?= esc((string) old('name')) ?>" maxlength="190" required class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Ej. Alquiler de grúa telescópica"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Grupo *</span><select name="item_group_id" data-placeholder="Seleccione grupo" required><option value="">Seleccione grupo</option><?php foreach ($groups as $group): ?><option value="<?= esc((string) $group['id']) ?>"><?= esc($group['code']) ?> — <?= esc($group['name']) ?></option><?php endforeach ?></select></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Tipo</span><select name="item_type" data-placeholder="Seleccione tipo"><option value="service">Servicio</option><option value="product">Producto</option><option value="equipment_rental">Alquiler de equipo</option><option value="transport">Transporte</option><option value="labor">Mano de obra</option><option value="other">Otro</option></select></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Unidad predeterminada *</span><select name="default_unit_id" data-placeholder="Seleccione unidad" required><option value="">Seleccione unidad</option><?php foreach ($units as $unit): ?><option value="<?= esc((string) $unit['id']) ?>"><?= esc($unit['code']) ?> — <?= esc($unit['name']) ?></option><?php endforeach ?></select></label>
            <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4"><input type="checkbox" name="allows_unit_override" value="1" checked class="mt-1"><span><strong class="block text-sm text-slate-800">Permitir cambiar unidad</strong><small class="mt-1 block text-slate-500">Útil para conceptos que pueden cobrarse por hora, día u otra modalidad.</small></span></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Precio sugerido</span><input type="number" name="suggested_price" value="<?= esc((string) old('suggested_price', '0.00')) ?>" min="0" step="0.01" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Descripción ampliada</span><textarea name="long_description" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"><?= esc((string) old('long_description')) ?></textarea></label>
        </div>
        <button class="mt-6 w-full rounded-xl bg-slate-950 px-5 py-3 font-bold text-white hover:bg-slate-800">Guardar concepto</button>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <?php if ($items === []): ?><div class="p-10 text-center text-slate-500">El catálogo está vacío.</div><?php else: ?>
        <div class="overflow-x-auto"><table data-trace-table="true" data-export-title="Catálogo comercial TraceOPX" class="min-w-full divide-y divide-slate-200 text-sm"><thead class="bg-slate-50"><tr><th class="px-5 py-4 text-left font-semibold text-slate-700">Código</th><th class="px-5 py-4 text-left font-semibold text-slate-700">Concepto</th><th class="px-5 py-4 text-left font-semibold text-slate-700">Grupo</th><th class="px-5 py-4 text-left font-semibold text-slate-700">Unidad</th><th class="px-5 py-4 text-right font-semibold text-slate-700">Precio</th></tr></thead><tbody class="divide-y divide-slate-100"><?php foreach ($items as $item): ?><tr class="hover:bg-slate-50"><td class="px-5 py-4 font-bold text-cyan-700"><?= esc($item['code']) ?></td><td class="px-5 py-4"><p class="font-semibold text-slate-950"><?= esc($item['name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($item['long_description'] ?: 'Sin descripción ampliada') ?></p><?php if (! empty($item['normalization_notes'])): ?><p class="mt-2 text-xs text-amber-700"><?= esc($item['normalization_notes']) ?></p><?php endif ?></td><td class="px-5 py-4"><span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700"><?= esc($item['group_name'] ?: 'Sin grupo') ?></span></td><td class="px-5 py-4"><?= esc($item['unit_name'] ?: 'Pendiente') ?></td><td class="px-5 py-4 text-right font-semibold">$<?= esc(number_format((float) $item['suggested_price'], 2)) ?></td></tr><?php endforeach ?></tbody></table></div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
