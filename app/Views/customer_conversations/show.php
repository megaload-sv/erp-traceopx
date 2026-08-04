<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = [
    'new' => 'Nueva',
    'in_attention' => 'En atención',
    'waiting_customer' => 'Esperando cliente',
    'information_complete' => 'Información completa',
    'converted' => 'Convertida',
    'discarded' => 'Descartada',
];
$channelLabels = ['whatsapp' => 'WhatsApp', 'email' => 'Correo', 'manual' => 'Manual', 'phone' => 'Teléfono', 'visit' => 'Visita'];
$taskStatusLabels = ['pending' => 'Pendiente', 'in_progress' => 'En progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada'];
$now = time();
$firstResponseDue = ! empty($conversation['first_response_due_at']) ? strtotime((string) $conversation['first_response_due_at']) : null;
$firstResponseCompleted = ! empty($conversation['first_responded_at']);
$slaState = $firstResponseCompleted ? 'fulfilled' : (($firstResponseDue !== null && $firstResponseDue < $now) ? 'overdue' : (($firstResponseDue !== null && ($firstResponseDue - $now) <= 900) ? 'warning' : 'on_time'));
$slaLabels = ['fulfilled' => 'Primera respuesta cumplida', 'overdue' => 'Fuera de SLA', 'warning' => 'Próxima a vencer', 'on_time' => 'En tiempo'];
$slaClasses = ['fulfilled' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'overdue' => 'border-red-200 bg-red-50 text-red-800', 'warning' => 'border-amber-200 bg-amber-50 text-amber-800', 'on_time' => 'border-cyan-200 bg-cyan-50 text-cyan-800'];
$pendingTasks = array_values(array_filter($tasks, static fn (array $task): bool => $task['status'] === 'pending'));
usort($pendingTasks, static fn (array $a, array $b): int => strtotime((string) $a['due_at']) <=> strtotime((string) $b['due_at']));
$nextTask = $pendingTasks[0] ?? null;
$nextAction = $nextTask['title'] ?? match ($conversation['attention_status']) {
    'new' => 'Responder al cliente',
    'in_attention' => 'Continuar recopilando información',
    'waiting_customer' => 'Esperar y dar seguimiento al cliente',
    'information_complete' => 'Crear solicitud comercial',
    'converted' => 'Continuar desde la solicitud comercial',
    default => 'Sin próxima acción',
};
?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<section class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm lg:p-8">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="<?= route_to('customer_conversations.index') ?>" class="text-sm font-semibold text-cyan-300">← Volver a la bandeja</a>
            <p class="mt-5 text-xs font-bold uppercase tracking-[.22em] text-cyan-400"><?= esc($conversation['code']) ?></p>
            <h3 class="mt-2 text-3xl font-bold lg:text-4xl"><?= esc($conversation['subject']) ?></h3>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold"><?= esc($channelLabels[$conversation['primary_channel']] ?? ucfirst($conversation['primary_channel'])) ?></span>
                <span class="rounded-full bg-cyan-400/15 px-3 py-1 text-xs font-semibold text-cyan-200"><?= esc($statusLabels[$conversation['attention_status']] ?? $conversation['attention_status']) ?></span>
                <span class="rounded-full bg-violet-400/15 px-3 py-1 text-xs font-semibold text-violet-200"><?= esc(ucfirst($conversation['priority'])) ?></span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-5 py-4 text-sm text-slate-300">
            <p>Responsable</p>
            <p class="mt-1 text-lg font-bold text-white"><?= esc($conversation['assigned_user_name'] ?: 'Sin asignar') ?></p>
        </div>
    </div>
</section>

<div class="mt-6 grid gap-4 md:grid-cols-3">
    <article class="rounded-2xl border <?= esc($slaClasses[$slaState]) ?> p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[.16em]">SLA de primera respuesta</p>
        <p class="mt-2 text-xl font-bold"><?= esc($slaLabels[$slaState]) ?></p>
        <p class="mt-2 text-sm"><?= $firstResponseCompleted ? 'Respondida el ' . esc(date('d/m/Y H:i', strtotime((string) $conversation['first_responded_at']))) : 'Límite: ' . esc(date('d/m/Y H:i', $firstResponseDue ?: $now)) ?></p>
    </article>
    <article class="rounded-2xl border border-violet-200 bg-violet-50 p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[.16em] text-violet-700">Próxima acción</p>
        <p class="mt-2 text-xl font-bold text-violet-950"><?= esc($nextAction) ?></p>
        <p class="mt-2 text-sm text-violet-800"><?= $nextTask ? 'Vence ' . esc(date('d/m/Y H:i', strtotime((string) $nextTask['due_at']))) : 'No existe una tarea pendiente asociada.' ?></p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-500">Actividad</p>
        <p class="mt-2 text-xl font-bold"><?= count($interactions) ?> interacciones</p>
        <p class="mt-2 text-sm text-slate-600"><?= count($pendingTasks) ?> tareas pendientes</p>
    </article>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[280px_minmax(0,1fr)_320px]">
    <aside class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Contexto comercial</p>
            <dl class="mt-5 space-y-5">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Cliente</dt><dd class="mt-1 font-semibold"><?= esc($conversation['business_name'] ?: 'Prospecto sin asociar') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Contacto</dt><dd class="mt-1 font-semibold"><?= esc($conversation['contact_name'] ?: 'Sin seleccionar') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Inicio</dt><dd class="mt-1 font-semibold"><?= esc(date('d/m/Y H:i', strtotime((string) $conversation['started_at']))) ?></dd></div>
                <?php if (! empty($conversation['next_follow_up_at'])): ?><div><dt class="text-xs font-semibold uppercase text-slate-500">Próximo seguimiento</dt><dd class="mt-1 font-semibold text-amber-700"><?= esc(date('d/m/Y H:i', strtotime((string) $conversation['next_follow_up_at']))) ?></dd></div><?php endif ?>
            </dl>
        </section>
        <?php if ($conversation['summary']): ?>
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Resumen</p>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700"><?= esc($conversation['summary']) ?></p>
            </section>
        <?php endif ?>
    </aside>

    <main class="space-y-6">
        <?php if ($conversation['attention_status'] === 'converted' && ! empty($conversation['commercial_request_code'])): ?>
            <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-700">Trazabilidad comercial</p>
                <h4 class="mt-2 text-xl font-bold text-emerald-950">Solicitud <?= esc($conversation['commercial_request_code']) ?> creada</h4>
                <p class="mt-2 text-sm text-emerald-800">La conversación permanece como origen y evidencia del proceso comercial.</p>
            </section>
        <?php endif ?>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Timeline omnicanal</p><h4 class="mt-1 text-xl font-bold">Historia de la atención</h4></div>
            <div class="mt-6 space-y-0">
                <?php foreach ($interactions as $index => $item): ?>
                    <article class="relative border-l-2 <?= $item['direction'] === 'outbound' ? 'border-cyan-400' : ($item['direction'] === 'internal' ? 'border-violet-400' : 'border-slate-300') ?> pb-7 pl-6 last:pb-0">
                        <span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full <?= $item['direction'] === 'outbound' ? 'bg-cyan-500' : ($item['direction'] === 'internal' ? 'bg-violet-500' : 'bg-slate-400') ?>"></span>
                        <div class="rounded-2xl border <?= $item['direction'] === 'outbound' ? 'border-cyan-200 bg-cyan-50/50' : ($item['direction'] === 'internal' ? 'border-violet-200 bg-violet-50/50' : 'border-slate-200 bg-slate-50') ?> p-5">
                            <div class="flex flex-wrap items-center justify-between gap-2"><p class="text-sm font-bold"><?= $item['direction'] === 'outbound' ? 'Empresa' : ($item['direction'] === 'internal' ? 'Nota interna' : 'Cliente') ?></p><p class="text-xs text-slate-500"><?= esc($channelLabels[$item['channel']] ?? ucfirst($item['channel'])) ?> · <?= esc(date('d/m/Y H:i', strtotime((string) $item['occurred_at']))) ?></p></div>
                            <p class="mt-3 whitespace-pre-line text-slate-700"><?= esc($item['body']) ?></p>
                        </div>
                    </article>
                <?php endforeach ?>
                <?php if ($interactions === []): ?><p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">Aún no existen interacciones.</p><?php endif ?>
            </div>

            <?php if (! in_array($conversation['attention_status'], ['converted', 'discarded'], true)): ?>
                <form method="post" action="<?= route_to('customer_conversations.interactions.store', $conversation['id']) ?>" class="mt-7 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <?= csrf_field() ?>
                    <div class="grid gap-4 md:grid-cols-3">
                        <label><span class="mb-2 block text-sm font-semibold">Dirección</span><select name="direction"><option value="outbound">Respuesta de la empresa</option><option value="inbound">Respuesta del cliente</option><option value="internal">Nota interna</option></select></label>
                        <label><span class="mb-2 block text-sm font-semibold">Canal</span><select name="channel"><option value="whatsapp">WhatsApp</option><option value="email">Correo</option><option value="phone">Teléfono</option><option value="manual">Manual</option><option value="visit">Visita</option></select></label>
                        <label><span class="mb-2 block text-sm font-semibold">Tipo</span><select name="interaction_type"><option value="message">Mensaje</option><option value="email">Correo</option><option value="call">Llamada</option><option value="note">Nota</option></select></label>
                    </div>
                    <label class="mt-4 block"><span class="mb-2 block text-sm font-semibold">Contenido</span><textarea required name="body" rows="4" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></textarea></label>
                    <input type="hidden" name="occurred_at" value="<?= esc(date('Y-m-d H:i:s')) ?>">
                    <div class="mt-4 flex justify-end"><button class="rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white">Registrar interacción</button></div>
                </form>
            <?php endif ?>
        </section>
    </main>

    <aside class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Trabajo pendiente</p>
            <h4 class="mt-1 text-lg font-bold">Tareas y próximas acciones</h4>
            <div class="mt-5 space-y-4">
                <?php foreach ($tasks as $task): ?>
                    <?php $taskOverdue = $task['status'] === 'pending' && strtotime((string) $task['due_at']) < $now; ?>
                    <article class="rounded-xl border <?= $taskOverdue ? 'border-red-200 bg-red-50' : 'border-slate-200' ?> p-4">
                        <div class="flex items-start justify-between gap-3"><p class="font-semibold"><?= esc($task['title']) ?></p><span class="rounded-full <?= $task['status'] === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($taskOverdue ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700') ?> px-2 py-1 text-[11px] font-semibold"><?= esc($taskStatusLabels[$task['status']] ?? $task['status']) ?></span></div>
                        <p class="mt-2 text-xs <?= $taskOverdue ? 'font-semibold text-red-700' : 'text-slate-500' ?>">Vence <?= esc(date('d/m/Y H:i', strtotime((string) $task['due_at']))) ?></p>
                    </article>
                <?php endforeach ?>
                <?php if ($tasks === []): ?><p class="text-sm text-slate-500">Sin tareas asociadas.</p><?php endif ?>
            </div>
        </section>

        <?php if (! in_array($conversation['attention_status'], ['information_complete', 'converted', 'discarded'], true)): ?>
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6">
                <h4 class="font-bold text-amber-950">Esperando información</h4>
                <p class="mt-2 text-sm text-amber-800">Programa la siguiente fecha para evitar que la oportunidad quede abandonada.</p>
                <form method="post" action="<?= route_to('customer_conversations.wait_customer', $conversation['id']) ?>" class="mt-4"><?= csrf_field() ?><input required type="datetime-local" name="next_follow_up_at" class="w-full rounded-xl border border-amber-300 bg-white px-4 py-3"><button class="mt-3 w-full rounded-xl bg-amber-500 px-4 py-3 font-semibold">Programar seguimiento</button></form>
            </section>
            <form method="post" action="<?= route_to('customer_conversations.complete_information', $conversation['id']) ?>"><?= csrf_field() ?><button class="w-full rounded-xl bg-emerald-600 px-5 py-3 font-semibold text-white">Información completa</button></form>
        <?php endif ?>

        <?php if ($conversation['attention_status'] === 'information_complete' && empty($conversation['commercial_request_id'])): ?>
            <section class="rounded-2xl border border-emerald-300 bg-emerald-50 p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-700">Formalización</p>
                <h4 class="mt-2 text-lg font-bold text-emerald-950">Crear solicitud comercial</h4>
                <p class="mt-2 text-sm text-emerald-800">Se trasladarán cliente, contacto, responsable, canal, prioridad y resumen.</p>
                <form method="post" action="<?= route_to('customer_conversations.convert', $conversation['id']) ?>" class="mt-4" onsubmit="return confirm('¿Confirmas que la información está completa y deseas crear la solicitud comercial?')"><?= csrf_field() ?><button class="w-full rounded-xl bg-emerald-700 px-5 py-3 font-semibold text-white">Confirmar y crear solicitud</button></form>
            </section>
        <?php endif ?>
    </aside>
</div>
<?= $this->endSection() ?>
