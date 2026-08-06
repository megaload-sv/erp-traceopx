<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = ['draft'=>'Borrador','ready_for_review'=>'Lista para revisión','ready_to_send'=>'Lista para enviar','sent'=>'Enviada','negotiation'=>'En negociación','accepted'=>'Aceptada','rejected'=>'Rechazada','expired'=>'Vencida'];
?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Quotation Workspace</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($quotation['subject']) ?></h2>
        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
            <span class="font-bold text-cyan-700"><?= esc($quotation['code']) ?></span>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800"><?= esc($statusLabels[$quotation['status']] ?? $quotation['status']) ?></span>
            <?php if (! empty($quotation['commercial_request_code'])): ?><a href="<?= route_to('commercial_requests.show', $quotation['commercial_request_id']) ?>" class="font-semibold text-violet-700"><?= esc($quotation['commercial_request_code']) ?> ↗</a><?php endif ?>
        </div>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="<?= route_to('commercial_items.index') ?>" class="rounded-xl border border-cyan-300 bg-cyan-50 px-5 py-3 text-center font-semibold text-cyan-800">Administrar catálogo</a>
        <a href="<?= route_to('quotations.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-center font-semibold text-slate-700">Volver al listado</a>
    </div>
</div>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</p><p class="mt-3 text-lg font-bold text-slate-950"><?= esc($quotation['business_name'] ?: 'Sin cliente') ?></p><?php if (! empty($quotation['trade_name'])): ?><p class="mt-1 text-sm text-slate-500"><?= esc($quotation['trade_name']) ?></p><?php endif ?></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contacto principal</p><p class="mt-3 text-lg font-bold text-slate-950"><?= esc($recipient['name'] ?? 'Sin contacto') ?></p><p class="mt-1 text-sm text-slate-500"><?= esc($recipient['email'] ?? 'Sin correo registrado') ?></p></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Agente comercial</p><p class="mt-3 text-lg font-bold text-slate-950"><?= esc($quotation['agent_name_snapshot'] ?: $quotation['assigned_user_name'] ?: 'Sin asignar') ?></p><p class="mt-1 text-sm text-slate-500"><?= esc($quotation['agent_email_snapshot'] ?: 'Sin correo') ?></p></article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Conceptos cotizados</p><h3 class="mt-2 text-xl font-bold text-slate-950"><?= $items === [] ? 'Agrega el primer concepto' : count($items) . ' concepto(s) en la propuesta' ?></h3></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Sin impuestos por línea</span></div>

            <?php if ($quotation['status'] === 'draft'): ?>
            <form method="post" action="<?= route_to('quotations.items.store', $quotation['id']) ?>" class="mt-6 rounded-2xl border border-cyan-200 bg-cyan-50/60 p-5">
                <?= csrf_field() ?>
                <div class="flex flex-wrap gap-3 border-b border-cyan-200 pb-4">
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm"><input type="radio" name="source_type" value="catalog" checked data-source-radio> Desde catálogo</label>
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm"><input type="radio" name="source_type" value="manual" data-source-radio> Concepto manual</label>
                </div>
                <div class="mt-5 grid gap-5 lg:grid-cols-2">
                    <label class="block lg:col-span-2" id="catalog-field"><span class="mb-2 block text-sm font-semibold text-slate-700">Buscar concepto en catálogo</span><select id="commercial_item_id" name="commercial_item_id" data-placeholder="Buscar por código o nombre"><option value="">Seleccione un concepto</option><?php foreach ($catalogItems as $catalogItem): ?><option value="<?= esc((string) $catalogItem['id']) ?>" data-name="<?= esc($catalogItem['name']) ?>" data-description="<?= esc($catalogItem['long_description'] ?? '') ?>" data-unit="<?= esc((string) ($catalogItem['default_unit_id'] ?? '')) ?>" data-price="<?= esc((string) $catalogItem['suggested_price']) ?>"><?= esc($catalogItem['code']) ?> — <?= esc($catalogItem['name']) ?> — $<?= esc(number_format((float) $catalogItem['suggested_price'], 2)) ?></option><?php endforeach ?></select><?php if ($catalogItems === []): ?><p class="mt-2 text-xs text-amber-700">El catálogo está vacío. Usa “Administrar catálogo” o agrega una línea manual.</p><?php endif ?></label>
                    <label class="block lg:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Descripción *</span><input id="item_description" name="description" required maxlength="255" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" placeholder="Descripción del servicio o producto"></label>
                    <label class="block lg:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Descripción ampliada</span><textarea id="item_long_description" name="long_description" rows="3" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"></textarea></label>
                    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Unidad</span><select id="item_unit_id" name="unit_id" data-placeholder="Seleccione unidad"><option value="">Sin unidad</option><?php foreach ($units as $unit): ?><option value="<?= esc((string) $unit['id']) ?>"><?= esc($unit['code']) ?> — <?= esc($unit['name']) ?></option><?php endforeach ?></select></label>
                    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Cantidad *</span><input id="item_quantity" type="number" name="quantity" value="1" min="0.001" step="0.001" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"></label>
                    <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Precio unitario *</span><input id="item_unit_price" type="number" name="unit_price" value="0.00" min="0" step="0.01" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100"></label>
                    <div class="rounded-xl bg-slate-950 p-4 text-white"><p class="text-xs uppercase tracking-wide text-slate-400">Total estimado</p><p id="line_total_preview" class="mt-2 text-2xl font-bold">$0.00</p></div>
                </div>
                <button class="mt-5 rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Agregar concepto</button>
            </form>
            <?php endif ?>

            <div class="mt-6 space-y-4">
                <?php foreach ($items as $item): ?>
                <article class="rounded-2xl border border-slate-200 p-5 transition hover:border-cyan-300">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h4 class="font-bold text-slate-950"><?= esc($item['description']) ?></h4><span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $item['source_type'] === 'catalog' ? 'bg-cyan-100 text-cyan-800' : 'bg-violet-100 text-violet-800' ?>"><?= $item['source_type'] === 'catalog' ? 'Catálogo' : 'Manual' ?></span></div><?php if (! empty($item['long_description'])): ?><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600"><?= esc($item['long_description']) ?></p><?php endif ?><p class="mt-3 text-sm text-slate-500"><?= esc(number_format((float) $item['quantity'], 3)) ?> <?= esc($item['unit_symbol'] ?: $item['unit_name'] ?: '') ?> × $<?= esc(number_format((float) $item['unit_price'], 2)) ?></p></div>
                        <div class="flex shrink-0 items-center gap-4"><p class="text-xl font-bold text-slate-950">$<?= esc(number_format((float) $item['line_total'], 2)) ?></p><?php if ($quotation['status'] === 'draft'): ?><form method="post" action="<?= route_to('quotations.items.delete', $quotation['id'], $item['id']) ?>" onsubmit="return confirm('¿Eliminar este concepto de la cotización?')"><?= csrf_field() ?><button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Eliminar</button></form><?php endif ?></div>
                    </div>
                </article>
                <?php endforeach ?>
                <?php if ($items === []): ?><div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center"><p class="font-semibold text-slate-800">La cotización todavía no tiene conceptos.</p><p class="mt-2 text-sm text-slate-500">Selecciona un concepto del catálogo o crea uno manual.</p></div><?php endif ?>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Condiciones comerciales</p><dl class="mt-5 space-y-4 text-sm"><div class="flex justify-between gap-4"><dt class="text-slate-500">Forma de pago</dt><dd class="text-right font-semibold text-slate-900"><?= esc($quotation['payment_term_name'] ?: 'Pendiente de definir') ?></dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500">Vigencia</dt><dd class="font-semibold text-slate-900"><?= esc((string) $quotation['validity_days']) ?> días</dd></div><div class="flex justify-between gap-4"><dt class="text-slate-500">Válida hasta</dt><dd class="font-semibold text-slate-900"><?= esc(date('d/m/Y', strtotime($quotation['valid_until']))) ?></dd></div></dl></article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Términos y condiciones</p><p class="mt-5 text-sm leading-6 text-slate-600">Se cargarán desde Settings y se guardará una copia histórica personalizable dentro de cada cotización.</p></article>
        </section>
    </div>

    <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
        <section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Resumen financiero</p><div class="mt-6 space-y-4 text-sm"><div class="flex justify-between gap-4"><span class="text-slate-400">Subtotal</span><span class="font-semibold">$<?= esc(number_format((float) $quotation['subtotal'], 2)) ?></span></div><div class="flex justify-between gap-4"><span class="text-slate-400">Descuento</span><span class="font-semibold">$<?= esc(number_format((float) $quotation['discount'], 2)) ?></span></div><div class="flex justify-between gap-4"><span class="text-slate-400">Ajuste</span><span class="font-semibold">$<?= esc(number_format((float) $quotation['adjustment'], 2)) ?></span></div><div class="flex justify-between gap-4 border-t border-slate-800 pt-4"><span class="text-slate-300">Total</span><span class="text-2xl font-bold">$<?= esc(number_format((float) $quotation['total'], 2)) ?></span></div></div></section>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Próxima acción</p><h3 class="mt-3 text-lg font-bold text-slate-950"><?= $items === [] ? 'Agregar conceptos cotizados' : 'Revisar condiciones comerciales' ?></h3><p class="mt-2 text-sm leading-6 text-slate-600"><?= $items === [] ? 'Completa la propuesta antes de enviarla a revisión.' : 'Verifica precios, forma de pago y vigencia antes del siguiente estado.' ?></p></section>
    </aside>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const catalogField = document.getElementById('catalog-field');
    const catalogSelect = document.getElementById('commercial_item_id');
    const description = document.getElementById('item_description');
    const longDescription = document.getElementById('item_long_description');
    const unitSelect = document.getElementById('item_unit_id');
    const quantity = document.getElementById('item_quantity');
    const price = document.getElementById('item_unit_price');
    const preview = document.getElementById('line_total_preview');
    if (!description || !quantity || !price || !preview) return;

    const updatePreview = () => preview.textContent = '$' + ((parseFloat(quantity.value) || 0) * (parseFloat(price.value) || 0)).toFixed(2);
    quantity.addEventListener('input', updatePreview);
    price.addEventListener('input', updatePreview);

    document.querySelectorAll('[data-source-radio]').forEach(radio => radio.addEventListener('change', () => {
        const catalogMode = document.querySelector('[data-source-radio]:checked').value === 'catalog';
        catalogField.classList.toggle('hidden', !catalogMode);
        if (!catalogMode) {
            description.value = '';
            longDescription.value = '';
            price.value = '0.00';
            updatePreview();
        }
    }));

    if (catalogSelect) catalogSelect.addEventListener('change', () => {
        const option = catalogSelect.options[catalogSelect.selectedIndex];
        if (!option || !option.value) return;
        description.value = option.dataset.name || '';
        longDescription.value = option.dataset.description || '';
        price.value = option.dataset.price || '0.00';
        const unitId = option.dataset.unit || '';
        const instance = window.traceOpxChoices.get(unitSelect);
        if (instance) instance.setChoiceByValue(unitId);
        else unitSelect.value = unitId;
        updatePreview();
    });
    updatePreview();
});
</script>
<?= $this->endSection() ?>
