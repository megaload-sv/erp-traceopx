<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = [
    'draft' => 'Borrador',
    'ready_for_review' => 'Lista para revisión',
    'ready_to_send' => 'Lista para enviar',
    'sent' => 'Enviada',
    'negotiation' => 'En negociación',
    'accepted' => 'Aceptada',
    'rejected' => 'Rechazada',
    'expired' => 'Vencida',
];
?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Quotation Workspace</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($quotation['subject']) ?></h2>
        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
            <span class="font-bold text-cyan-700"><?= esc($quotation['code']) ?></span>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800"><?= esc($statusLabels[$quotation['status']] ?? $quotation['status']) ?></span>
            <span class="text-slate-500">Creada el <?= esc(date('d/m/Y', strtotime($quotation['quotation_date']))) ?></span>
        </div>
    </div>
    <a href="<?= route_to('quotations.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-center font-semibold text-slate-700">Volver al listado</a>
</div>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
    <div class="space-y-6">
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cliente</p>
                <p class="mt-3 text-lg font-bold text-slate-950"><?= esc($quotation['business_name'] ?: 'Sin cliente') ?></p>
                <?php if (! empty($quotation['trade_name'])): ?><p class="mt-1 text-sm text-slate-500"><?= esc($quotation['trade_name']) ?></p><?php endif ?>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contacto principal</p>
                <p class="mt-3 text-lg font-bold text-slate-950"><?= esc($recipient['name'] ?? 'Sin contacto') ?></p>
                <p class="mt-1 text-sm text-slate-500"><?= esc($recipient['email'] ?? 'Sin correo registrado') ?></p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Agente comercial</p>
                <p class="mt-3 text-lg font-bold text-slate-950"><?= esc($quotation['agent_name_snapshot'] ?: $quotation['assigned_user_name'] ?: 'Sin asignar') ?></p>
                <p class="mt-1 text-sm text-slate-500"><?= esc($quotation['agent_email_snapshot'] ?: 'Sin correo') ?></p>
            </article>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Conceptos cotizados</p>
                    <h3 class="mt-2 text-xl font-bold text-slate-950">La propuesta todavía no tiene conceptos</h3>
                </div>
                <button type="button" disabled class="cursor-not-allowed rounded-xl bg-slate-200 px-5 py-3 font-semibold text-slate-500">Agregar concepto · Próximo incremento</button>
            </div>
            <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
                <p class="font-semibold text-slate-800">Aquí podrás buscar en el catálogo o crear un concepto manual.</p>
                <p class="mt-2 text-sm text-slate-500">La diferencia se conservará mediante <code>source_type</code>.</p>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Condiciones comerciales</p>
                <dl class="mt-5 space-y-4 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Forma de pago</dt><dd class="text-right font-semibold text-slate-900"><?= esc($quotation['payment_term_name'] ?: 'Pendiente de definir') ?></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Vigencia</dt><dd class="font-semibold text-slate-900"><?= esc((string) $quotation['validity_days']) ?> días</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-slate-500">Válida hasta</dt><dd class="font-semibold text-slate-900"><?= esc(date('d/m/Y', strtotime($quotation['valid_until']))) ?></dd></div>
                </dl>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Términos y condiciones</p>
                <p class="mt-5 text-sm leading-6 text-slate-600">Se cargarán desde Settings y se guardará una copia histórica personalizable dentro de cada cotización.</p>
                <button type="button" disabled class="mt-5 cursor-not-allowed rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-500">Editar términos · Próximo incremento</button>
            </article>
        </section>
    </div>

    <aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
        <section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Resumen financiero</p>
            <div class="mt-6 space-y-4 text-sm">
                <div class="flex justify-between gap-4"><span class="text-slate-400">Subtotal</span><span class="font-semibold">$<?= esc(number_format((float) $quotation['subtotal'], 2)) ?></span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-400">Descuento</span><span class="font-semibold">$<?= esc(number_format((float) $quotation['discount'], 2)) ?></span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-400">Ajuste</span><span class="font-semibold">$<?= esc(number_format((float) $quotation['adjustment'], 2)) ?></span></div>
                <div class="border-t border-slate-800 pt-4 flex justify-between gap-4"><span class="text-slate-300">Total</span><span class="text-2xl font-bold">$<?= esc(number_format((float) $quotation['total'], 2)) ?></span></div>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Próxima acción</p>
            <h3 class="mt-3 text-lg font-bold text-slate-950">Agregar conceptos cotizados</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">Completa la propuesta antes de enviarla a revisión.</p>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
