<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$statusLabels = ['new'=>'Nueva','assigned'=>'Asignada','in_progress'=>'En atención','waiting_customer'=>'Esperando cliente','ready_to_quote'=>'Lista para cotizar','quotation_preparation'=>'Cotización creada','quotation_sent'=>'Cotización enviada','converted'=>'Convertida','discarded'=>'Descartada'];
$channelLabels = ['whatsapp'=>'WhatsApp','email'=>'Correo','manual'=>'Manual'];
$priorityLabels = ['low'=>'Baja','normal'=>'Normal','high'=>'Alta','urgent'=>'Urgente'];
$quotationStatusLabels = ['draft'=>'Borrador','ready_for_review'=>'Lista para revisión','ready_to_send'=>'Lista para enviar','sent'=>'Enviada','negotiation'=>'En negociación','accepted'=>'Aceptada','rejected'=>'Rechazada','expired'=>'Vencida','cancelled'=>'Cancelada'];
$now = time();
$activeTasks = array_values(array_filter($tasks, static fn (array $task): bool => in_array($task['status'], ['pending','in_progress'], true)));
usort($activeTasks, static fn (array $a, array $b): int => strtotime((string) $a['due_at']) <=> strtotime((string) $b['due_at']));
$nextTask = $activeTasks[0] ?? null;
$canQuote = ! empty($request['customer_id']) && $quotation === null && ! in_array($request['status'], ['discarded','converted'], true);
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
        </div>
    </div>
</section>

<section class="mt-6 rounded-2xl border border-cyan-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Trazabilidad de origen</p>
            <h2 class="mt-2 text-xl font-bold text-slate-950">Ruta comercial de esta oportunidad</h2>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <?php if (! empty($request['source_conversation_code'])): ?>
                <a href="<?= route_to('customer_conversations.show', $request['source_conversation_id']) ?>" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-700 hover:border-cyan-300 hover:text-cyan-700">Atención <?= esc($request['source_conversation_code']) ?> ↗</a>
                <span class="text-slate-300">→</span>
            <?php else: ?>
                <span class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 font-semibold text-slate-500">Entrada manual</span>
                <span class="text-slate-300">→</span>
            <?php endif ?>
            <span class="rounded-xl bg-cyan-100 px-4 py-3 font-bold text-cyan-900">Solicitud <?= esc($request['code']) ?></span>
            <?php if ($quotation !== null): ?>
                <span class="text-slate-300">→</span>
                <a href="<?= route_to('quotations.show', $quotation['id']) ?>" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 font-semibold text-emerald-800 hover:bg-emerald-100">Cotización <?= esc($quotation['code']) ?> ↗</a>
                <?php if (! empty($quotation['service_case_id'])): ?>
                    <span class="text-slate-300">→</span>
                    <a href="<?= route_to('service_cases.show', $quotation['service_case_id']) ?>" class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-3 font-semibold text-violet-800 hover:bg-violet-100">Expediente <?= esc($quotation['service_case_code']) ?> ↗</a>
                <?php endif ?>
            <?php endif ?>
        </div>
    </div>
</section>

<div class="mt-6 grid gap-4 md:grid-cols-3">
    <?= view('components/workspace/metric-card', ['label'=>'Estado comercial','value'=>$statusLabels[$request['status']] ?? $request['status'],'description'=>$quotation ? 'La solicitud ya produjo una cotización.' : 'Solicitud formalizada y trazable.','tone'=>'cyan']) ?>
    <?= view('components/workspace/metric-card', ['label'=>'Próxima acción','value'=>$quotation ? ($quotationStatusLabels[$quotation['status']] ?? $quotation['status']) : ($nextTask['title'] ?? 'Preparar cotización'),'description'=>$quotation ? 'Continúa el flujo desde la cotización ' . $quotation['code'] . '.' : ($nextTask ? 'Vence ' . date('d/m/Y H:i', strtotime((string) $nextTask['due_at'])) : 'Lista para continuar el flujo comercial.'),'tone'=>'violet']) ?>
    <?= view('components/workspace/metric-card', ['label'=>'Actividad','value'=>count($events) . ' eventos','description'=>count($activeTasks) . ' tareas activas','tone'=>'slate']) ?>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[300px_minmax(0,1fr)_340px]">
    <aside class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Contexto comercial</p>
            <dl class="mt-5 space-y-5">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Cliente</dt><dd class="mt-1 font-semibold text-slate-950"><?= esc($request['business_name'] ?: 'Prospecto sin asociar') ?></dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Contacto</dt><dd class="mt-1 font-semibold text-slate-950"><?= esc($request['contact_name'] ?: 'Sin seleccionar') ?></dd><?php if (! empty($request['contact_email'])): ?><dd class="mt-1 text-xs text-slate-500"><?= esc($request['contact_email']) ?></dd><?php endif ?></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Recibida</dt><dd class="mt-1 font-semibold"><?= esc(date('d/m/Y H:i', strtotime((string) $request['received_at']))) ?></dd></div>
            </dl>
        </section>
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
                <?php foreach ($events as $event): ?><?= view('components/workspace/timeline-item', ['direction'=>'internal','actor'=>$event['title'],'meta'=>date('d/m/Y H:i', strtotime((string) $event['occurred_at'])) . ' · ' . ($event['actor_user'] ?: 'system'),'body'=>$event['description'] ?: $event['event_key']]) ?><?php endforeach ?>
                <?php if ($events === []): ?><p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">Aún no existen eventos registrados.</p><?php endif ?>
            </div>
        </section>
    </main>

    <aside class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Trabajo pendiente</p>
            <h2 class="mt-1 text-lg font-bold">Tareas y próximas acciones</h2>
            <div class="mt-5 space-y-4">
                <?php foreach ($tasks as $task): ?><?= view('components/workspace/task-item', ['id'=>$task['id'],'title'=>$task['title'],'status'=>$task['status'],'dueAt'=>$task['due_at'],'isOverdue'=>in_array($task['status'], ['pending','in_progress'], true) && strtotime((string) $task['due_at']) < $now,'completionNote'=>$task['completion_note'] ?? null,'rescheduleReason'=>$task['reschedule_reason'] ?? null,'showActions'=>true]) ?><?php endforeach ?>
                <?php if ($tasks === []): ?><p class="text-sm text-slate-500">Sin tareas asociadas.</p><?php endif ?>
            </div>
        </section>

        <section class="rounded-2xl border <?= $quotation ? 'border-emerald-200 bg-emerald-50' : ($canQuote ? 'border-cyan-200 bg-cyan-50' : 'border-amber-200 bg-amber-50') ?> p-6 shadow-sm">
            <?php if ($quotation !== null): ?>
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-700">Resultado comercial</p>
                <h2 class="mt-2 text-lg font-bold text-emerald-950">Cotización <?= esc($quotation['code']) ?></h2>
                <p class="mt-2 text-sm leading-6 text-emerald-800">Esta solicitud ya completó la etapa de preparación de cotización. Continúa el proceso desde su Workspace.</p>
                <a href="<?= route_to('quotations.show', $quotation['id']) ?>" class="mt-4 block w-full rounded-xl bg-emerald-700 px-5 py-3 text-center font-bold text-white hover:bg-emerald-800">Abrir cotización ↗</a>
            <?php elseif ($canQuote): ?>
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Siguiente etapa</p>
                <h2 class="mt-2 text-lg font-bold text-cyan-950">Preparar cotización</h2>
                <p class="mt-2 text-sm leading-6 text-cyan-800">Inicia una cotización con cliente, contacto, agente y asunto precargados desde esta solicitud.</p>
                <a href="<?= route_to('quotations.create') ?>?commercial_request_id=<?= esc((string) $request['id']) ?>" class="mt-4 block w-full rounded-xl bg-cyan-500 px-5 py-3 text-center font-bold text-white hover:bg-cyan-600">Preparar cotización ↗</a>
            <?php else: ?>
                <p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Siguiente etapa</p>
                <h2 class="mt-2 text-lg font-bold text-amber-950">Cotización no disponible</h2>
                <p class="mt-2 text-sm leading-6 text-amber-800">Para cotizar primero debes asociar un cliente válido y mantener la solicitud activa.</p>
            <?php endif ?>
        </section>
    </aside>
</div>
<?= $this->endSection() ?>
