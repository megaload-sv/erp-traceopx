<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = ['draft'=>'Borrador','ready_for_review'=>'Lista para revisión','ready_to_send'=>'Lista para enviar','sent'=>'Enviada','negotiation'=>'En negociación','accepted'=>'Aceptada','rejected'=>'Rechazada','expired'=>'Vencida','cancelled'=>'Cancelada'];
$nextTransitions = [
    'draft' => ['ready_for_review' => 'Enviar a revisión'],
    'ready_for_review' => ['ready_to_send' => 'Aprobar para envío', 'draft' => 'Devolver a borrador'],
    'ready_to_send' => ['sent' => 'Registrar envío', 'ready_for_review' => 'Devolver a revisión'],
    'sent' => ['negotiation' => 'Registrar negociación'],
    'negotiation' => ['ready_for_review' => 'Enviar cambios a revisión'],
];
$dbView = db_connect();
$serviceCase = $dbView->table('service_cases')->where('accepted_quotation_id', $quotation['id'])->where('delete_date', null)->get()->getRowArray();
$existingCatalogItems = [];
foreach ($items as $quotationItem) {
    if (! empty($quotationItem['commercial_item_id'])) {
        $existingCatalogItems[(string) $quotationItem['commercial_item_id']] = [
            'description' => (string) $quotationItem['description'],
            'quantity' => (float) $quotationItem['quantity'],
        ];
    }
}
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
            <?php if ($serviceCase): ?><a href="<?= route_to('service_cases.show', $serviceCase['id']) ?>" class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800"><?= esc($serviceCase['code']) ?> ↗</a><?php endif ?>
        </div>
    </div>
    <div class="flex flex-wrap gap-3">
        <?php foreach ($nextTransitions[$quotation['status']] ?? [] as $target => $label): ?>
            <form method="post" action="<?= route_to('quotations.transition', $quotation['id']) ?>">
                <?= csrf_field() ?><input type="hidden" name="target_status" value="<?= esc($target) ?>">
                <button class="rounded-xl border border-cyan-300 bg-cyan-50 px-5 py-3 text-center font-semibold text-cyan-800 hover:bg-cyan-100"><?= esc($label) ?></button>
            </form>
        <?php endforeach ?>
        <?php if (in_array($quotation['status'], ['sent','negotiation'], true) && ! $serviceCase): ?>
            <button type="button" id="open-acceptance-drawer" class="rounded-xl bg-emerald-500 px-5 py-3 font-bold text-white shadow-sm hover:bg-emerald-400">Preparar aceptación</button>
        <?php endif ?>
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

        <?php if ($serviceCase): ?>
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-700">Proceso activado</p>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><h3 class="text-xl font-bold text-slate-950">La cotización ya originó un expediente</h3><p class="mt-1 text-sm text-slate-600">El seguimiento operativo, financiero y documental continúa en <?= esc($serviceCase['code']) ?>.</p></div><a href="<?= route_to('service_cases.show', $serviceCase['id']) ?>" class="rounded-xl bg-emerald-600 px-5 py-3 text-center font-bold text-white">Abrir expediente</a></div>
        </section>
        <?php endif ?>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Conceptos cotizados</p><h3 class="mt-2 text-xl font-bold text-slate-950"><?= $items === [] ? 'Agrega el primer concepto' : count($items) . ' concepto(s) en la propuesta' ?></h3></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Sin impuestos por línea</span></div>

            <?php if ($quotation['status'] === 'draft'): ?>
            <form id="quotation-item-form" method="post" action="<?= route_to('quotations.items.store', $quotation['id']) ?>" class="mt-6 rounded-2xl border border-cyan-200 bg-cyan-50/60 p-5">
                <?= csrf_field() ?>
                <input type="hidden" name="merge_duplicate" id="merge_duplicate" value="0">
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
                <button type="submit" class="mt-5 rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Agregar concepto</button>
            </form>
            <?php endif ?>

            <div class="mt-6 space-y-4">
                <?php foreach ($items as $item): ?>
                <article class="rounded-2xl border border-slate-200 p-5 transition hover:border-cyan-300"><div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h4 class="font-bold text-slate-950"><?= esc($item['description']) ?></h4><span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $item['source_type'] === 'catalog' ? 'bg-cyan-100 text-cyan-800' : 'bg-violet-100 text-violet-800' ?>"><?= $item['source_type'] === 'catalog' ? 'Catálogo' : 'Manual' ?></span></div><?php if (! empty($item['long_description'])): ?><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600"><?= esc($item['long_description']) ?></p><?php endif ?><p class="mt-3 text-sm text-slate-500"><?= esc(number_format((float) $item['quantity'], 3)) ?> <?= esc($item['unit_symbol'] ?: $item['unit_name'] ?: '') ?> × $<?= esc(number_format((float) $item['unit_price'], 2)) ?></p></div><div class="flex shrink-0 items-center gap-4"><p class="text-xl font-bold text-slate-950">$<?= esc(number_format((float) $item['line_total'], 2)) ?></p><?php if ($quotation['status'] === 'draft'): ?><form method="post" action="<?= route_to('quotations.items.delete', $quotation['id'], $item['id']) ?>" onsubmit="return confirm('¿Eliminar este concepto de la cotización?')"><?= csrf_field() ?><button class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">Eliminar</button></form><?php endif ?></div></div></article>
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
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Próxima acción</p><h3 class="mt-3 text-lg font-bold text-slate-950"><?= $serviceCase ? 'Continuar en el expediente' : ($items === [] ? 'Agregar conceptos cotizados' : ($quotation['status'] === 'draft' ? 'Enviar a revisión' : 'Continuar flujo comercial')) ?></h3><p class="mt-2 text-sm leading-6 text-slate-600"><?= $serviceCase ? 'La ejecución y el control financiero continuarán en el Service Case.' : 'El sistema habilitará la aceptación únicamente después de registrar el envío o la negociación.' ?></p></section>
    </aside>
</div>

<?php if (in_array($quotation['status'], ['sent','negotiation'], true) && ! $serviceCase): ?>
<div id="acceptance-backdrop" class="fixed inset-0 z-[80] hidden bg-slate-950/55 backdrop-blur-sm"></div>
<aside id="acceptance-drawer" class="fixed inset-y-0 right-0 z-[90] hidden w-full max-w-xl overflow-y-auto bg-white shadow-2xl">
    <form id="acceptance-form" method="post" enctype="multipart/form-data" action="<?= route_to('quotations.accept', $quotation['id']) ?>" class="min-h-full">
        <?= csrf_field() ?>
        <header class="sticky top-0 border-b border-slate-200 bg-white/95 px-6 py-5 backdrop-blur"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-600">Aceptación comercial</p><h3 class="mt-2 text-2xl font-bold text-slate-950">Crear expediente del servicio</h3><p class="mt-2 text-sm text-slate-500"><?= esc($quotation['code']) ?> · $<?= esc(number_format((float) $quotation['total'], 2)) ?></p></div><button type="button" id="close-acceptance-drawer" class="rounded-xl border border-slate-200 px-3 py-2 text-slate-500">Cerrar</button></div></header>
        <div class="space-y-5 p-6">
            <div class="rounded-2xl bg-slate-950 p-5 text-white"><p class="text-xs uppercase tracking-wide text-slate-400">Resumen</p><p class="mt-2 text-lg font-bold"><?= esc($quotation['business_name']) ?></p><p class="mt-1 text-sm text-slate-300"><?= esc($quotation['payment_term_name'] ?: 'Forma de pago pendiente') ?></p></div>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Aceptada por *</span><input name="accepted_by_name" required maxlength="190" class="w-full rounded-xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100" placeholder="Nombre de la persona que acepta"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Fecha y hora *</span><input type="datetime-local" name="accepted_at" required value="<?= date('Y-m-d\TH:i') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Tipo de aceptación *</span><select name="acceptance_type" required data-placeholder="Seleccione"><option value="signed_document">Documento firmado</option><option value="email">Correo electrónico</option><option value="authorized_confirmation">Confirmación comercial autorizada</option><option value="other">Otra evidencia autorizada</option></select></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Documento fiscal esperado *</span><select name="fiscal_document_type" required data-placeholder="Seleccione"><option value="pending">Pendiente de definir</option><option value="tax_credit_invoice">Factura de crédito fiscal</option><option value="consumer_invoice">Factura de consumidor final</option><option value="export_invoice">Factura de exportación</option></select></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Evidencia de aceptación</span><input type="file" name="acceptance_evidence" accept="application/pdf,image/jpeg,image/png,image/webp" class="w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-4 text-sm"><span class="mt-2 block text-xs text-slate-500">PDF o imagen, máximo 10 MB. Obligatoria para documento firmado o correo.</span></label>
            <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Observaciones</span><textarea name="notes" rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Condiciones particulares o referencia de la aceptación"></textarea></label>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">Al confirmar, la cotización quedará bloqueada para edición y se creará un expediente con sus hitos, reglas de facturación y próxima acción.</div>
        </div>
        <footer class="sticky bottom-0 flex gap-3 border-t border-slate-200 bg-white px-6 py-5"><button type="button" id="cancel-acceptance" class="flex-1 rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700">Cancelar</button><button type="submit" class="flex-1 rounded-xl bg-emerald-500 px-5 py-3 font-bold text-white hover:bg-emerald-400">Aceptar y crear expediente</button></footer>
    </form>
</aside>
<div id="acceptance-overlay" class="fixed inset-0 z-[110] hidden items-center justify-center bg-slate-950/70 p-6 backdrop-blur-sm"><div class="w-full max-w-sm rounded-3xl bg-white p-8 text-center shadow-2xl"><div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-emerald-100 border-t-emerald-500"></div><h3 class="mt-5 text-xl font-bold text-slate-950">Creando expediente…</h3><p class="mt-2 text-sm leading-6 text-slate-500">Validando aceptación, generando hitos y calculando la próxima acción.</p></div></div>
<?php endif ?>

<div id="item-submit-overlay" class="fixed inset-0 z-[120] hidden items-center justify-center bg-slate-950/65 p-6 backdrop-blur-sm">
    <div class="w-full max-w-sm rounded-3xl border border-white/20 bg-white p-8 text-center shadow-2xl">
        <div class="mx-auto h-12 w-12 animate-spin rounded-full border-4 border-cyan-100 border-t-cyan-500"></div>
        <h3 class="mt-5 text-xl font-bold text-slate-950">Agregando servicio…</h3>
        <p class="mt-2 text-sm leading-6 text-slate-500">Estamos actualizando la cotización. Esto tomará solo un momento.</p>
    </div>
</div>

<div id="duplicate-item-modal" class="fixed inset-0 z-[125] hidden items-center justify-center bg-slate-950/65 p-6 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl">
        <p class="text-xs font-bold uppercase tracking-[.18em] text-amber-600">Servicio ya incluido</p>
        <h3 class="mt-2 text-2xl font-bold text-slate-950">¿Incrementar la cantidad?</h3>
        <p id="duplicate-item-message" class="mt-3 text-sm leading-6 text-slate-600"></p>
        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" id="cancel-duplicate-item" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold text-slate-700">Cancelar</button>
            <button type="button" id="confirm-duplicate-item" class="rounded-xl bg-amber-500 px-5 py-3 font-bold text-white hover:bg-amber-400">Incrementar cantidad</button>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const drawer = document.getElementById('acceptance-drawer');
    const backdrop = document.getElementById('acceptance-backdrop');
    const openDrawer = document.getElementById('open-acceptance-drawer');
    const closeButtons = [document.getElementById('close-acceptance-drawer'), document.getElementById('cancel-acceptance')];
    const setDrawer = (show) => { if (!drawer || !backdrop) return; drawer.classList.toggle('hidden', !show); backdrop.classList.toggle('hidden', !show); document.body.classList.toggle('overflow-hidden', show); };
    openDrawer?.addEventListener('click', () => setDrawer(true));
    backdrop?.addEventListener('click', () => setDrawer(false));
    closeButtons.forEach(button => button?.addEventListener('click', () => setDrawer(false)));
    document.getElementById('acceptance-form')?.addEventListener('submit', (event) => {
        if (event.currentTarget.dataset.submitting === 'true') { event.preventDefault(); return; }
        event.currentTarget.dataset.submitting = 'true';
        document.getElementById('acceptance-overlay')?.classList.replace('hidden', 'flex');
        event.currentTarget.querySelectorAll('button').forEach(button => button.disabled = true);
    });

    const catalogField = document.getElementById('catalog-field');
    const catalogSelect = document.getElementById('commercial_item_id');
    const description = document.getElementById('item_description');
    const longDescription = document.getElementById('item_long_description');
    const unitSelect = document.getElementById('item_unit_id');
    const quantity = document.getElementById('item_quantity');
    const price = document.getElementById('item_unit_price');
    const preview = document.getElementById('line_total_preview');
    const itemForm = document.getElementById('quotation-item-form');
    const mergeDuplicate = document.getElementById('merge_duplicate');
    const itemOverlay = document.getElementById('item-submit-overlay');
    const duplicateModal = document.getElementById('duplicate-item-modal');
    const duplicateMessage = document.getElementById('duplicate-item-message');
    const confirmDuplicate = document.getElementById('confirm-duplicate-item');
    const cancelDuplicate = document.getElementById('cancel-duplicate-item');
    const existingCatalogItems = <?= json_encode($existingCatalogItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    const showItemOverlayAndSubmit = () => {
        if (!itemForm || itemForm.dataset.submitting === 'true') return;

        itemForm.dataset.submitting = 'true';

        // Do not disable inputs/selects/textareas before submitting.
        // Disabled controls are omitted from the POST payload (including CSRF).
        const submitButton = itemForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.setAttribute('aria-disabled', 'true');
        }

        itemOverlay?.classList.replace('hidden', 'flex');
        HTMLFormElement.prototype.submit.call(itemForm);
    };

    if (itemForm) {
        itemForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (itemForm.dataset.submitting === 'true') return;

            const sourceType = itemForm.querySelector('[name="source_type"]:checked')?.value;
            const selectedCatalogId = catalogSelect?.value || '';
            const enteredQuantity = parseFloat(quantity?.value || '0') || 0;
            const existing = sourceType === 'catalog' ? existingCatalogItems[selectedCatalogId] : null;

            if (existing && mergeDuplicate?.value !== '1') {
                const resultingQuantity = Number(existing.quantity) + enteredQuantity;
                duplicateMessage.textContent = `“${existing.description}” ya tiene una cantidad de ${Number(existing.quantity).toLocaleString('es-SV')}. Al continuar se agregarán ${enteredQuantity.toLocaleString('es-SV')} y la nueva cantidad será ${resultingQuantity.toLocaleString('es-SV')}. Se conservará el precio de la línea existente.`;
                duplicateModal?.classList.replace('hidden', 'flex');
                return;
            }

            showItemOverlayAndSubmit();
        });
    }

    confirmDuplicate?.addEventListener('click', () => {
        if (mergeDuplicate) mergeDuplicate.value = '1';
        duplicateModal?.classList.replace('flex', 'hidden');
        showItemOverlayAndSubmit();
    });

    cancelDuplicate?.addEventListener('click', () => {
        duplicateModal?.classList.replace('flex', 'hidden');
        if (mergeDuplicate) mergeDuplicate.value = '0';
    });

    duplicateModal?.addEventListener('click', (event) => {
        if (event.target === duplicateModal) {
            duplicateModal.classList.replace('flex', 'hidden');
            if (mergeDuplicate) mergeDuplicate.value = '0';
        }
    });

    if (!description || !quantity || !price || !preview) return;
    const updatePreview = () => preview.textContent = '$' + ((parseFloat(quantity.value) || 0) * (parseFloat(price.value) || 0)).toFixed(2);
    quantity.addEventListener('input', updatePreview); price.addEventListener('input', updatePreview);
    document.querySelectorAll('[data-source-radio]').forEach(radio => radio.addEventListener('change', () => { const catalogMode = document.querySelector('[data-source-radio]:checked').value === 'catalog'; catalogField.classList.toggle('hidden', !catalogMode); if (mergeDuplicate) mergeDuplicate.value = '0'; if (!catalogMode) { description.value = ''; longDescription.value = ''; price.value = '0.00'; updatePreview(); } }));
    if (catalogSelect) catalogSelect.addEventListener('change', () => { const option = catalogSelect.options[catalogSelect.selectedIndex]; if (!option || !option.value) return; if (mergeDuplicate) mergeDuplicate.value = '0'; description.value = option.dataset.name || ''; longDescription.value = option.dataset.description || ''; price.value = option.dataset.price || '0.00'; const unitId = option.dataset.unit || ''; const instance = window.traceOpxChoices.get(unitSelect); if (instance) instance.setChoiceByValue(unitId); else unitSelect.value = unitId; updatePreview(); });
    updatePreview();
});
</script>
<?= $this->endSection() ?>