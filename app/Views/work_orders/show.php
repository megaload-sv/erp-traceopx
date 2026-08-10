<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$isPrepared = $order['status'] === 'prepared';
$isIssued = $order['status'] === 'issued';
$isInProgress = in_array($order['status'], ['in_progress','working'], true);
$statusLabels = ['prepared' => 'Preparada', 'issued' => 'Emitida', 'in_progress' => 'En ejecución', 'working' => 'En ejecución', 'finished' => 'Finalizada', 'closed' => 'Cerrada'];
$statusClasses = ['prepared' => 'bg-amber-100 text-amber-800', 'issued' => 'bg-cyan-100 text-cyan-800', 'in_progress' => 'bg-violet-100 text-violet-800', 'working' => 'bg-violet-100 text-violet-800', 'finished' => 'bg-emerald-100 text-emerald-800', 'closed' => 'bg-slate-200 text-slate-700'];
$oldEventType = old('event_type') ?: 'progress';
$oldOccurredAt = old('occurred_at') ?: date('Y-m-d\TH:i');
?>
<?php if(session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if(session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Work Order Workspace</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($order['code']) ?></h2>
        <div class="mt-3 flex flex-wrap gap-3 text-sm">
            <a href="<?= route_to('service_cases.show',$order['service_case_id']) ?>" class="font-bold text-violet-700"><?= esc($order['service_case_code']) ?> ↗</a>
            <a href="<?= route_to('coordination.show',$order['coordination_plan_id']) ?>" class="font-bold text-cyan-700"><?= esc($order['coordination_code']) ?> ↗</a>
            <span class="rounded-full px-3 py-1 text-xs font-bold <?= esc($statusClasses[$order['status']] ?? 'bg-slate-200 text-slate-700') ?>"><?= esc($statusLabels[$order['status']] ?? ucfirst($order['status'])) ?></span>
        </div>
    </div>
    <a href="<?= route_to('work_orders.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Volver a órdenes</a>
</div>

<?php if($isIssued): ?>
<section class="mb-6 rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-cyan-700">Emisión formal registrada</p><p class="mt-2 font-bold text-slate-950">La Orden de Trabajo fue entregada al Responsable de Misión.</p><p class="mt-1 text-sm text-slate-600"><?= esc($order['mission_leader_name'] ?? 'Responsable de misión') ?><?= !empty($order['mission_leader_code']) ? ' · '.esc($order['mission_leader_code']) : '' ?></p></div>
        <div class="text-sm text-slate-600"><span class="block text-xs uppercase text-slate-500">Emitida</span><strong><?= esc($order['issued_at'] ? date('d/m/Y H:i', strtotime($order['issued_at'])) : '—') ?></strong></div>
    </div>
    <?php if(!empty($order['issuance_notes'])): ?><p class="mt-4 border-t border-cyan-200 pt-4 text-sm text-slate-600"><?= esc($order['issuance_notes']) ?></p><?php endif ?>
</section>
<?php elseif($isInProgress): ?>
<section class="mb-6 rounded-2xl border border-violet-200 bg-violet-50 p-5">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-[.16em] text-violet-700">Execution Engine activo</p><p class="mt-2 text-lg font-bold text-slate-950">La misión se encuentra en ejecución.</p><p class="mt-1 text-sm text-slate-600">Personal y maquinaria están comprometidos operativamente con esta OT.</p></div>
        <div class="text-sm text-slate-600"><span class="block text-xs uppercase text-slate-500">Inicio real</span><strong><?= esc($order['started_at'] ? date('d/m/Y H:i', strtotime($order['started_at'])) : '—') ?></strong></div>
    </div>
    <?php if(!empty($order['start_notes'])): ?><p class="mt-4 border-t border-violet-200 pt-4 text-sm text-slate-600"><?= esc($order['start_notes']) ?></p><?php endif ?>
</section>
<?php endif ?>

<section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Cliente</p><p class="mt-3 font-bold"><?= esc($order['business_name']??'—') ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500"><?= $isInProgress ? 'Inicio real' : 'Programación' ?></p><p class="mt-3 font-bold"><?= esc($isInProgress && $order['started_at'] ? date('d/m/Y H:i',strtotime($order['started_at'])) : ($order['scheduled_start_at']?date('d/m/Y H:i',strtotime($order['scheduled_start_at'])):'—')) ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Ubicación</p><p class="mt-3 font-bold"><?= esc($order['location']??'—') ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Responsable</p><p class="mt-3 font-bold"><?= esc($order['mission_leader_name']??'—') ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($order['mission_leader_code']??'') ?></p></article>
</section>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
<div class="space-y-6">
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Misión</p><h3 class="mt-2 text-xl font-bold"><?= esc($order['subject']??'Orden de Trabajo') ?></h3>
    <div class="mt-5 grid gap-4 md:grid-cols-2"><div><p class="text-xs uppercase text-slate-500">Inicio programado</p><p class="mt-2 font-semibold"><?= esc($order['scheduled_start_at']?date('d/m/Y H:i',strtotime($order['scheduled_start_at'])):'—') ?></p></div><div><p class="text-xs uppercase text-slate-500">Fin estimado</p><p class="mt-2 font-semibold"><?= esc($order['estimated_end_at']?date('d/m/Y H:i',strtotime($order['estimated_end_at'])):'—') ?></p></div></div>
    <?php if(!empty($order['scope_snapshot'])): ?><div class="mt-5 border-t border-slate-100 pt-5"><p class="text-xs uppercase text-slate-500">Alcance operativo</p><p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700"><?= esc($order['scope_snapshot']) ?></p></div><?php endif ?>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Maquinaria asignada</p><h3 class="mt-2 text-xl font-bold">Recursos físicos</h3></div><?php if($isInProgress): ?><span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-800">En operación</span><?php endif ?></div>
    <div class="mt-5 grid gap-3 md:grid-cols-2"><?php foreach($equipment as $item): ?><article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="font-bold"><?= esc($item['equipment_code_snapshot']) ?> · <?= esc($item['equipment_name_snapshot']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc(ucfirst(str_replace('_',' ',$item['assignment_status']))) ?></p></article><?php endforeach ?></div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Equipo operativo</p><h3 class="mt-2 text-xl font-bold">Personal de la misión</h3></div><?php if($isInProgress): ?><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Trabajando</span><?php endif ?></div>
    <div class="mt-5 grid gap-3 md:grid-cols-2"><?php foreach($team as $member): ?><article class="rounded-xl border <?= $member['allocation_type']==='mission_leader'?'border-violet-200 bg-violet-50':'border-slate-200 bg-slate-50' ?> p-4"><p class="font-bold"><?= esc($member['employee_name_snapshot']) ?></p><p class="mt-1 text-sm text-slate-600"><?= esc($member['role_name_snapshot']??'Recurso operativo') ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($member['employee_code_snapshot']) ?> · <?= esc(ucfirst(str_replace('_',' ',$member['assignment_status']))) ?></p></article><?php endforeach ?></div>
</section>

<?= $this->include('work_orders/_evidence') ?>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Mission Log</p><h3 class="mt-2 text-xl font-bold text-slate-950">Bitácora de la misión</h3><p class="mt-2 text-sm text-slate-500">Registro cronológico de eventos operativos asociados a esta Orden de Trabajo.</p></div><?php if($isInProgress): ?><span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-800">Registro activo</span><?php endif ?></div>

    <?php if($isInProgress): ?>
    <form id="mission-log-entry" method="post" action="<?= route_to('work_orders.mission_log.store',$order['id']) ?>" class="mt-6 rounded-2xl border border-cyan-200 bg-cyan-50/40 p-5">
        <?= csrf_field() ?>
        <div class="grid gap-4 md:grid-cols-2">
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Tipo de avance *</span><select name="event_type" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><?php foreach($missionLogEventTypes as $code => $definition): ?><option value="<?= esc($code) ?>" <?= $oldEventType === $code ? 'selected' : '' ?>><?= esc($definition['label']) ?></option><?php endforeach ?></select></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Fecha y hora *</span><input type="datetime-local" name="occurred_at" required value="<?= esc($oldOccurredAt) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
            <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Descripción *</span><textarea name="description" rows="4" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3" placeholder="Describe de forma concreta qué ocurrió, qué avance se logró o qué condición debe quedar registrada."><?= esc(old('description') ?? '') ?></textarea></label>
        </div>
        <label class="mt-4 flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-white p-4"><input type="checkbox" name="publish_to_case" value="1" class="mt-1" <?= old('publish_to_case') ? 'checked' : '' ?>><span><span class="block text-sm font-bold text-slate-900">Agregar también al Timeline del Expediente</span><span class="mt-1 block text-xs leading-5 text-slate-500">Úsalo para hechos relevantes que deban ser visibles desde el Service Case. Las notas rutinarias pueden permanecer únicamente en el Mission Log.</span></span></label>
        <div class="mt-5 flex justify-end"><button class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Registrar avance</button></div>
    </form>
    <?php endif ?>

    <div class="mt-6 space-y-5">
        <?php foreach($missionLogs as $log): $meta = !empty($log['metadata_json']) ? json_decode((string)$log['metadata_json'], true) : []; ?>
            <article class="relative border-l-2 border-cyan-200 pl-5"><span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full bg-cyan-500"></span><div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"><p class="font-bold text-slate-900"><?= esc($log['title']) ?></p><time class="text-xs text-slate-400"><?= esc(date('d/m/Y H:i',strtotime($log['occurred_at']))) ?></time></div><?php if(!empty($log['description'])): ?><p class="mt-2 text-sm leading-6 text-slate-600"><?= esc($log['description']) ?></p><?php endif ?><div class="mt-2 flex flex-wrap gap-2"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase text-slate-500"><?= esc($log['category']) ?></span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase text-slate-500"><?= esc($log['log_type']) ?></span><?php if(!empty($meta['published_to_case'])): ?><span class="rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-semibold uppercase text-violet-700">En expediente</span><?php endif ?></div></article>
        <?php endforeach ?>
        <?php if($missionLogs===[]): ?><div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">La bitácora iniciará automáticamente cuando comience la ejecución del servicio.</div><?php endif ?>
    </div>
</section>
</div>

<aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
<section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Próxima acción</p>
    <h3 class="mt-3 text-xl font-bold"><?= $isPrepared ? 'Emitir Orden de Trabajo' : ($isIssued ? 'Iniciar servicio' : ($isInProgress ? 'Registrar avance operativo' : 'Ejecución operativa')) ?></h3>
    <?php if($isPrepared): ?>
        <p class="mt-4 text-sm leading-6 text-slate-300">Formaliza la entrega de la OT al Responsable de Misión. Después de emitirla quedará lista para iniciar la operación.</p>
        <div class="mt-5 rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-xs uppercase text-slate-400">Responsable de misión</p><p class="mt-2 font-bold text-white"><?= esc($order['mission_leader_name']??'—') ?></p><p class="mt-1 text-xs text-slate-400"><?= esc($order['mission_leader_code']??'') ?></p></div>
        <button id="open-issue-modal" type="button" class="mt-5 w-full rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Emitir Orden de Trabajo →</button>
    <?php elseif($isIssued): ?>
        <p class="mt-4 text-sm leading-6 text-slate-300">Confirma el inicio real de la misión. TraceOPX cambiará personal y maquinaria a estado operativo y abrirá el Mission Log.</p>
        <div class="mt-5 rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-xs uppercase text-slate-400">Responsable de misión</p><p class="mt-2 font-bold text-white"><?= esc($order['mission_leader_name']??'—') ?></p><p class="mt-1 text-xs text-slate-400"><?= esc($order['mission_leader_code']??'') ?></p></div>
        <button id="open-start-modal" type="button" class="mt-5 w-full rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">▶ Iniciar servicio</button>
    <?php elseif($isInProgress): ?>
        <div class="mt-4 rounded-xl border border-violet-900 bg-violet-950/40 p-4"><p class="text-xs uppercase text-violet-300">En ejecución desde</p><p class="mt-2 font-bold text-violet-100"><?= esc($order['started_at']?date('d/m/Y H:i',strtotime($order['started_at'])):'—') ?></p></div>
        <p class="mt-4 text-sm leading-6 text-slate-300">Registra avances y evidencias mientras la misión se encuentra activa. Los archivos quedan protegidos y ligados al Expediente.</p>
        <a href="#mission-log-entry" class="mt-5 block w-full rounded-xl bg-cyan-400 px-5 py-3 text-center font-bold text-slate-950">Registrar avance →</a>
    <?php else: ?><p class="mt-4 text-sm leading-6 text-slate-300">La Orden de Trabajo avanza mediante estados controlados.</p><?php endif ?>
</section>
<section class="rounded-2xl border border-slate-200 bg-white p-6"><p class="text-xs uppercase tracking-wide text-slate-500">Trazabilidad</p><div class="mt-4 space-y-3 text-sm"><p><span class="text-slate-500">Expediente:</span> <strong><?= esc($order['service_case_code']) ?></strong></p><p><span class="text-slate-500">Coordinación:</span> <strong><?= esc($order['coordination_code']) ?></strong></p><p><span class="text-slate-500">Creada:</span> <strong><?= esc($order['entry_date']?date('d/m/Y H:i',strtotime($order['entry_date'])):'—') ?></strong></p><?php if($order['issued_at']): ?><p><span class="text-slate-500">Emitida:</span> <strong><?= esc(date('d/m/Y H:i',strtotime($order['issued_at']))) ?></strong></p><?php endif ?><?php if($order['started_at']): ?><p><span class="text-slate-500">Iniciada:</span> <strong><?= esc(date('d/m/Y H:i',strtotime($order['started_at']))) ?></strong></p><?php endif ?><p><span class="text-slate-500">Evidencias:</span> <strong><?= count($evidence ?? []) ?></strong></p></div></section>
</aside>
</div>

<?php if($isPrepared): ?>
<div id="issue-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"><div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Emisión formal</p><h3 class="mt-2 text-2xl font-bold text-slate-950">Emitir <?= esc($order['code']) ?></h3></div><button data-close-modal="issue-modal" type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">×</button></div><p class="mt-4 text-sm leading-6 text-slate-600">Se registrará que esta Orden de Trabajo fue emitida y entregada a <strong><?= esc($order['mission_leader_name']??'Responsable de Misión') ?></strong>.</p><form method="post" action="<?= route_to('work_orders.issue',$order['id']) ?>" class="mt-5"><?= csrf_field() ?><label><span class="mb-2 block text-sm font-semibold text-slate-700">Nota de entrega <span class="font-normal text-slate-400">(opcional)</span></span><textarea name="issuance_notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3"></textarea></label><div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button data-close-modal="issue-modal" type="button" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Cancelar</button><button class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950">Confirmar emisión</button></div></form></div></div>
<?php endif ?>

<?php if($isIssued): ?>
<div id="start-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"><div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Inicio de ejecución</p><h3 class="mt-2 text-2xl font-bold text-slate-950">Iniciar <?= esc($order['code']) ?></h3></div><button data-close-modal="start-modal" type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">×</button></div><p class="mt-4 text-sm leading-6 text-slate-600">Esta acción confirma el inicio real del servicio. La maquinaria pasará a <strong>En operación</strong> y el personal a <strong>Trabajando</strong>.</p><div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">El Mission Log iniciará automáticamente y el Expediente se actualizará a ejecución en curso.</div><form method="post" action="<?= route_to('work_orders.start',$order['id']) ?>" class="mt-5"><?= csrf_field() ?><label><span class="mb-2 block text-sm font-semibold text-slate-700">Nota de inicio <span class="font-normal text-slate-400">(opcional)</span></span><textarea name="start_notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Ej. Cuadrilla completa en sitio; cliente presente..."></textarea></label><div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button data-close-modal="start-modal" type="button" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Cancelar</button><button class="rounded-xl bg-violet-600 px-5 py-3 font-bold text-white">Confirmar inicio</button></div></form></div></div>
<?php endif ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const openModal=id=>{const modal=document.getElementById(id);modal?.classList.remove('hidden');modal?.classList.add('flex')};
    const closeModal=id=>{const modal=document.getElementById(id);modal?.classList.add('hidden');modal?.classList.remove('flex')};
    document.getElementById('open-issue-modal')?.addEventListener('click',()=>openModal('issue-modal'));
    document.getElementById('open-start-modal')?.addEventListener('click',()=>openModal('start-modal'));
    document.querySelectorAll('[data-close-modal]').forEach(button=>button.addEventListener('click',()=>closeModal(button.dataset.closeModal)));
    ['issue-modal','start-modal'].forEach(id=>document.getElementById(id)?.addEventListener('click',event=>{if(event.target.id===id)closeModal(id)}));
    document.addEventListener('keydown',event=>{if(event.key==='Escape'){closeModal('issue-modal');closeModal('start-modal')}});
});
</script>
<?= $this->endSection() ?>
