<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = [
    'new' => 'Nueva',
    'assigned' => 'Asignada',
    'in_progress' => 'En atención',
    'waiting_customer' => 'Esperando cliente',
    'ready_to_quote' => 'Lista para cotizar',
    'quotation_preparation' => 'Cotización en preparación',
    'quotation_sent' => 'Cotización enviada',
    'converted' => 'Convertida',
    'discarded' => 'Descartada',
];
$channelLabels = ['whatsapp' => 'WhatsApp', 'email' => 'Correo', 'manual' => 'Manual'];
$priorityLabels = ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
$now = time();
$quotationDue = ! empty($request['quotation_due_at']) ? strtotime((string) $request['quotation_due_at']) : null;
$slaState = $quotationDue !== null && $quotationDue < $now ? 'overdue' : ($quotationDue !== null && ($quotationDue - $now) <= 3600 ? 'warning' : 'on_time');
$slaLabels = ['overdue' => 'Fuera de SLA', 'warning' => 'Próxima a vencer', 'on_time' => 'En tiempo'];
$slaTone = ['overdue' => 'red', 'warning' => 'amber', 'on_time' => 'emerald'];
$activeTasks = array_values(array_filter($tasks, static fn (array $task): bool => in_array($task['status'], ['pending', 'in_progress'], true)));
usort($activeTasks, static fn (array $a, array $b): int => strtotime((string) $a['due_at']) <=> strtotime((string) $b['due_at']));
$nextTask = $activeTasks[0] ?? null;
?>

<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<section class="rounded-3xl bg-slate-950 p-6 text-white shadow-sm lg:p-8">
    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="<?= route_to('commercial_requests.index') ?>" class="text-sm font-semibold text-cyan-300">← Volver a solicitudes</a>
            <p class="mt-5 text-xs font-bold uppercase tracking-[.22em] text-cyan-400"><?= esc($request['code']) ?></p>
            <h1 class="mt-2 text-3xl font-bold lg:text-4xl"><?= esc($request['subject']) ?></h1>
            <div class="mt-4 flex flex-wrap gap-2">
                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold"><?= esc($channelLabels[$request['channel']] ?? ucfirst($request['channel'])) ?></span>
                <span class="rounded-full bg-cyan-400/15 px-3 py-1 text-xs font-semibold text-cyan-200"><?= esc($statusLabels[$request['status']] ?? $request['status']) ?></span>
                <span class="rounded-full bg-violet-400/15 px-3 py-1 text-xs font-semibold text-violet-200"><?= esc($priorityLabels[$request['priority']] ?? ucfirst($request['priority'])) ?></span>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-800 bg-slate-900/80 px-5 py-4 text-sm text-slate-300">
            <p>Responsable comercial</p>
            <p class="mt-1 text-lg font-bold text-white"><?= esc($request['assigned_user_name'] ?: 'Sin asignar') ?></p>
            <?php if (! empty($request['assigned_user_email'])): ?><p class="mt-1 text-xs text-slate-400"><?= esc($request['assigned_user_email']) ?></p><?php endif ?>
        </div>
    </div>
</section>

<div class="mt-6 grid gap-4 md:grid-cols-3">
    <?= view('components/workspace/metric-card', [
        'label' => 'SLA de cotización',
        'value' => $slaLabels[$slaState],
        'description' => $quotationDue ? 'Límite: ' . date('d/m/Y H:i', $quotationDue) : 'Sin fecha límite configurada.',
        'tone' => $slaTone[$slaState],
    ]) ?>
    <?= view('components/workspace/metric-card', [
        'label' => 'Próxima acción',
        'value' => $nextTask['title'] ?? 'Preparar cotización',
        'description' => $nextTask ? 'Vence ' . date('d/m/Y H:i', strtotime((string) $nextTask['due_at'])) : 'El Quotation Workspace se habilitará en la PR #6.',
        'tone' => 'violet',
    ]) ?>
    <?= view('components/workspace/metric-card', [
        'label' => 'Actividad',
        'value' => count($events) . ' eventos',
        'description' => count($activeTasks) . ' tareas activas',
        'tone' => 'slate',
    ]) ?>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[300px_minmax(0,1fr)_340px]">
    <aside class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Contexto comercial</p>
            <dl class="mt-5 space-y-5">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Cliente</dt><dd class="mt-1 font-semibold text-slate-950"><?= esc($request['business_name'] ?: 'Prospecto sin asociar') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Contacto</dt><dd class="mt-1 font-semibold text-slate-950"><?= esc($request['contact_name'] ?: 'Sin seleccionar') ?></dd><?php if (! empty($request['contact_email'])): ?><dd class="mt-1 text-xs text-slate-500"><?= esc($request['contact_email']) ?></dd><?php endif ?></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Recibida</dt><dd class="mt-1 font-semibold"><?= esc(date('d/m/Y H:i', strtotime((string) $request['received_at']))) ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Política SLA</dt><dd class="mt-1 font-semibold"><?= esc($request['sla_policy_name'] ?: 'Sin política') ?></dd></div>
                <?php if (! empty($request['source_conversation_code'])): ?>
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Atención de origen</dt><dd class="mt-1"><a class="font-semibold text-cyan-700 hover:text-cyan-900" href="<?= route_to('customer_conversations.show', $request['source_conversation_id']) ?>"><?= esc($request['source_conversation_code']) ?> ↗</a></dd></div>
                <?php endif ?>
            </dl>
        </section>

        <?php if (! empty($request['source_detail'])): ?>
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-slate-500">Origen</p>
                <p class="mt-3 text-sm leading-6 text-slate-700"><?= esc($request['source_detail']) ?></p>
            </section>
        <?php endif ?>
    </aside>

    <main class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Necesidad formalizada</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">Descripción comercial</h2>
            <p class="mt-5 whitespace-pre-line leading-7 text-slate-700"><?= esc($request['description']) ?></p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Servicios solicitados</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">Alcance preliminar</h2>
            <p class="mt-5 whitespace-pre-line leading-7 text-slate-700"><?= esc($request['requested_services'] ?: 'Pendiente de detallar durante la preparación de la cotización.') ?></p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Timeline</p>
            <h2 class="mt-1 text-xl font-bold text-slate-950">Historia de la solicitud</h2>
            <div class="mt-6">
                <?php foreach ($events as $event): ?>
                    <?= view('components/workspace/timeline-item', [
                        'direction' => 'internal',
                        'actor' => $event['title'],
                        'meta' => date('d/m/Y H:i', strtotime((string) $event['occurred_at'])) . ' · ' . ($event['actor_user'] ?: 'system'),
                        'body' => $event['description'] ?: $event['event_key'],
                    ]) ?>
                <?php endforeach ?>
                <?php if ($events === []): ?><p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">Aún no existen eventos registrados.</p><?php endif ?>
            </div>
        </section>
    </main>

    <aside class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Trabajo pendiente</p>
            <h2 class="mt-1 text-lg font-bold">Tareas y próximas acciones</h2>
            <div class="mt-5 space-y-4">
                <?php foreach ($tasks as $task): ?>
                    <?= view('components/workspace/task-item', [
                        'id' => $task['id'],
                        'title' => $task['title'],
                        'status' => $task['status'],
                        'dueAt' => $task['due_at'],
                        'isOverdue' => in_array($task['status'], ['pending', 'in_progress'], true) && strtotime((string) $task['due_at']) < $now,
                        'completionNote' => $task['completion_note'] ?? null,
                        'rescheduleReason' => $task['reschedule_reason'] ?? null,
                        'showActions' => true,
                    ]) ?>
                <?php endforeach ?>
                <?php if ($tasks === []): ?><p class="text-sm text-slate-500">Sin tareas asociadas.</p><?php endif ?>
            </div>
        </section>

        <section class="rounded-2xl border border-cyan-200 bg-cyan-50 p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Siguiente etapa</p>
            <h2 class="mt-2 text-lg font-bold text-cyan-950">Preparar cotización</h2>
            <p class="mt-2 text-sm leading-6 text-cyan-800">La solicitud ya contiene el contexto necesario para iniciar el Quotation Workspace. Esta acción se habilitará en la PR #6.</p>
            <button type="button" disabled class="mt-4 w-full cursor-not-allowed rounded-xl bg-cyan-200 px-5 py-3 font-semibold text-cyan-700">Preparar cotización — Próximamente</button>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
