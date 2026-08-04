<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = ['new' => 'Nueva', 'in_attention' => 'En atención', 'waiting_customer' => 'Esperando cliente', 'information_complete' => 'Información completa', 'converted' => 'Convertida', 'discarded' => 'Descartada'];
?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<section class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm lg:p-8">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"><div><a href="<?= route_to('customer_conversations.index') ?>" class="text-sm font-semibold text-cyan-300">← Volver a la bandeja</a><p class="mt-5 text-xs font-bold uppercase tracking-[.22em] text-cyan-400"><?= esc($conversation['code']) ?></p><h3 class="mt-2 text-3xl font-bold"><?= esc($conversation['subject']) ?></h3><div class="mt-4 flex flex-wrap gap-2"><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold"><?= esc(ucfirst($conversation['primary_channel'])) ?></span><span class="rounded-full bg-cyan-400/15 px-3 py-1 text-xs font-semibold text-cyan-200"><?= esc($statusLabels[$conversation['attention_status']] ?? $conversation['attention_status']) ?></span></div></div><div class="text-sm text-slate-300"><p>Responsable</p><p class="mt-1 text-lg font-bold text-white"><?= esc($conversation['assigned_user_name'] ?: 'Sin asignar') ?></p></div></div>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 sm:grid-cols-2"><div><p class="text-xs font-semibold uppercase text-slate-500">Cliente</p><p class="mt-1 font-semibold"><?= esc($conversation['business_name'] ?: 'Prospecto sin asociar') ?></p></div><div><p class="text-xs font-semibold uppercase text-slate-500">Contacto</p><p class="mt-1 font-semibold"><?= esc($conversation['contact_name'] ?: 'Sin seleccionar') ?></p></div><div><p class="text-xs font-semibold uppercase text-slate-500">Inicio</p><p class="mt-1 font-semibold"><?= esc(date('d/m/Y H:i', strtotime($conversation['started_at']))) ?></p></div><div><p class="text-xs font-semibold uppercase text-slate-500">Límite primera respuesta</p><p class="mt-1 font-semibold"><?= esc(date('d/m/Y H:i', strtotime($conversation['first_response_due_at']))) ?></p></div></div>
            <?php if ($conversation['summary']): ?><div class="mt-5 border-t border-slate-200 pt-5"><p class="text-xs font-semibold uppercase text-slate-500">Resumen</p><p class="mt-2 whitespace-pre-line text-slate-700"><?= esc($conversation['summary']) ?></p></div><?php endif ?>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Conversación omnicanal</p><h4 class="mt-1 text-xl font-bold">Interacciones</h4></div>
            <div class="mt-6 space-y-4"><?php foreach ($interactions as $item): ?><article class="rounded-2xl border <?= $item['direction'] === 'outbound' ? 'border-cyan-200 bg-cyan-50/50' : 'border-slate-200 bg-slate-50' ?> p-5"><div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-bold"><?= $item['direction'] === 'outbound' ? 'Empresa' : ($item['direction'] === 'internal' ? 'Nota interna' : 'Cliente') ?></p><p class="text-xs text-slate-500"><?= esc(ucfirst($item['channel'])) ?> · <?= esc(date('d/m/Y H:i', strtotime($item['occurred_at']))) ?></p></div><p class="mt-3 whitespace-pre-line text-slate-700"><?= esc($item['body']) ?></p></article><?php endforeach ?></div>

            <form method="post" action="<?= route_to('customer_conversations.interactions.store', $conversation['id']) ?>" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                <?= csrf_field() ?><div class="grid gap-4 md:grid-cols-3"><label><span class="mb-2 block text-sm font-semibold">Dirección</span><select name="direction"><option value="outbound">Respuesta de la empresa</option><option value="inbound">Respuesta del cliente</option><option value="internal">Nota interna</option></select></label><label><span class="mb-2 block text-sm font-semibold">Canal</span><select name="channel"><option value="whatsapp">WhatsApp</option><option value="email">Correo</option><option value="phone">Teléfono</option><option value="manual">Manual</option><option value="visit">Visita</option></select></label><label><span class="mb-2 block text-sm font-semibold">Tipo</span><select name="interaction_type"><option value="message">Mensaje</option><option value="email">Correo</option><option value="call">Llamada</option><option value="note">Nota</option></select></label></div><label class="mt-4 block"><span class="mb-2 block text-sm font-semibold">Contenido</span><textarea required name="body" rows="4" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></textarea></label><input type="hidden" name="occurred_at" value="<?= esc(date('Y-m-d H:i:s')) ?>"><div class="mt-4 flex justify-end"><button class="rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white">Registrar interacción</button></div>
            </form>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Siguientes acciones</p><div class="mt-5 space-y-4"><?php foreach ($tasks as $task): ?><article class="rounded-xl border border-slate-200 p-4"><div class="flex items-start justify-between gap-3"><p class="font-semibold"><?= esc($task['title']) ?></p><span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-semibold"><?= esc($task['status']) ?></span></div><p class="mt-2 text-xs text-slate-500">Vence <?= esc(date('d/m/Y H:i', strtotime($task['due_at']))) ?></p></article><?php endforeach ?><?php if ($tasks === []): ?><p class="text-sm text-slate-500">Sin tareas pendientes.</p><?php endif ?></div></section>

        <?php if (! in_array($conversation['attention_status'], ['information_complete', 'converted', 'discarded'], true)): ?><section class="rounded-2xl border border-amber-200 bg-amber-50 p-6"><h4 class="font-bold">Esperando información</h4><form method="post" action="<?= route_to('customer_conversations.wait_customer', $conversation['id']) ?>" class="mt-4"><?= csrf_field() ?><input required type="datetime-local" name="next_follow_up_at" class="w-full rounded-xl border border-amber-300 bg-white px-4 py-3"><button class="mt-3 w-full rounded-xl bg-amber-500 px-4 py-3 font-semibold">Programar seguimiento</button></form></section><?php endif ?>

        <?php if (! in_array($conversation['attention_status'], ['information_complete', 'converted', 'discarded'], true)): ?><form method="post" action="<?= route_to('customer_conversations.complete_information', $conversation['id']) ?>"><?= csrf_field() ?><button class="w-full rounded-xl bg-emerald-600 px-5 py-3 font-semibold text-white">Información completa</button></form><?php endif ?>
    </aside>
</div>
<?= $this->endSection() ?>
