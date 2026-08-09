<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$isPrepared = $order['status'] === 'prepared';
$isIssued = $order['status'] === 'issued';
$statusLabels = ['prepared' => 'Preparada', 'issued' => 'Emitida', 'in_progress' => 'En ejecución', 'finished' => 'Finalizada', 'closed' => 'Cerrada'];
$statusClasses = ['prepared' => 'bg-amber-100 text-amber-800', 'issued' => 'bg-cyan-100 text-cyan-800', 'in_progress' => 'bg-violet-100 text-violet-800', 'finished' => 'bg-emerald-100 text-emerald-800', 'closed' => 'bg-slate-200 text-slate-700'];
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
<?php endif ?>

<section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Cliente</p><p class="mt-3 font-bold"><?= esc($order['business_name']??'—') ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Programación</p><p class="mt-3 font-bold"><?= esc($order['scheduled_start_at']?date('d/m/Y H:i',strtotime($order['scheduled_start_at'])):'—') ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Ubicación</p><p class="mt-3 font-bold"><?= esc($order['location']??'—') ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Responsable</p><p class="mt-3 font-bold"><?= esc($order['mission_leader_name']??'—') ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($order['mission_leader_code']??'') ?></p></article>
</section>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
<div class="space-y-6">
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Misión</p><h3 class="mt-2 text-xl font-bold"><?= esc($order['subject']??'Orden de Trabajo') ?></h3>
    <div class="mt-5 grid gap-4 md:grid-cols-2"><div><p class="text-xs uppercase text-slate-500">Inicio</p><p class="mt-2 font-semibold"><?= esc($order['scheduled_start_at']?date('d/m/Y H:i',strtotime($order['scheduled_start_at'])):'—') ?></p></div><div><p class="text-xs uppercase text-slate-500">Fin estimado</p><p class="mt-2 font-semibold"><?= esc($order['estimated_end_at']?date('d/m/Y H:i',strtotime($order['estimated_end_at'])):'—') ?></p></div></div>
    <?php if(!empty($order['scope_snapshot'])): ?><div class="mt-5 border-t border-slate-100 pt-5"><p class="text-xs uppercase text-slate-500">Alcance operativo</p><p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700"><?= esc($order['scope_snapshot']) ?></p></div><?php endif ?>
</section>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Maquinaria asignada</p><h3 class="mt-2 text-xl font-bold">Recursos físicos</h3></div><div class="mt-5 grid gap-3 md:grid-cols-2"><?php foreach($equipment as $item): ?><article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="font-bold"><?= esc($item['equipment_code_snapshot']) ?> · <?= esc($item['equipment_name_snapshot']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc(ucfirst($item['assignment_status'])) ?></p></article><?php endforeach ?></div></section>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Equipo operativo</p><h3 class="mt-2 text-xl font-bold">Personal de la misión</h3></div><div class="mt-5 grid gap-3 md:grid-cols-2"><?php foreach($team as $member): ?><article class="rounded-xl border <?= $member['allocation_type']==='mission_leader'?'border-violet-200 bg-violet-50':'border-slate-200 bg-slate-50' ?> p-4"><p class="font-bold"><?= esc($member['employee_name_snapshot']) ?></p><p class="mt-1 text-sm text-slate-600"><?= esc($member['role_name_snapshot']??'Recurso operativo') ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($member['employee_code_snapshot']) ?> · <?= esc(ucfirst($member['assignment_status'])) ?></p></article><?php endforeach ?></div></section>
</div>

<aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
<section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Próxima acción</p>
    <h3 class="mt-3 text-xl font-bold"><?= $isPrepared ? 'Emitir Orden de Trabajo' : ($isIssued ? 'Iniciar ejecución' : 'Ejecución operativa') ?></h3>
    <?php if($isPrepared): ?>
        <p class="mt-4 text-sm leading-6 text-slate-300">Formaliza la entrega de la OT al Responsable de Misión. Después de emitirla quedará lista para iniciar la operación.</p>
        <div class="mt-5 rounded-xl border border-slate-800 bg-slate-900 p-4"><p class="text-xs uppercase text-slate-400">Responsable de misión</p><p class="mt-2 font-bold text-white"><?= esc($order['mission_leader_name']??'—') ?></p><p class="mt-1 text-xs text-slate-400"><?= esc($order['mission_leader_code']??'') ?></p></div>
        <button id="open-issue-modal" type="button" class="mt-5 w-full rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Emitir Orden de Trabajo →</button>
    <?php elseif($isIssued): ?>
        <p class="mt-4 text-sm leading-6 text-emerald-200">La OT ya fue emitida. El siguiente incremento habilitará el inicio controlado de la ejecución y cambiará los recursos a estado operativo.</p>
        <div class="mt-5 rounded-xl border border-emerald-900 bg-emerald-950/30 p-4 text-sm text-emerald-200">Emitida <?= esc($order['issued_at'] ? date('d/m/Y H:i', strtotime($order['issued_at'])) : '') ?></div>
    <?php else: ?><p class="mt-4 text-sm leading-6 text-slate-300">La Orden de Trabajo avanza mediante estados controlados.</p><?php endif ?>
</section>
<section class="rounded-2xl border border-slate-200 bg-white p-6"><p class="text-xs uppercase tracking-wide text-slate-500">Trazabilidad</p><div class="mt-4 space-y-3 text-sm"><p><span class="text-slate-500">Expediente:</span> <strong><?= esc($order['service_case_code']) ?></strong></p><p><span class="text-slate-500">Coordinación:</span> <strong><?= esc($order['coordination_code']) ?></strong></p><p><span class="text-slate-500">Creada:</span> <strong><?= esc($order['entry_date']?date('d/m/Y H:i',strtotime($order['entry_date'])):'—') ?></strong></p><?php if($order['issued_at']): ?><p><span class="text-slate-500">Emitida:</span> <strong><?= esc(date('d/m/Y H:i',strtotime($order['issued_at']))) ?></strong></p><?php endif ?></div></section>
</aside>
</div>

<?php if($isPrepared): ?>
<div id="issue-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Emisión formal</p><h3 class="mt-2 text-2xl font-bold text-slate-950">Emitir <?= esc($order['code']) ?></h3></div><button id="close-issue-modal" type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">×</button></div>
        <p class="mt-4 text-sm leading-6 text-slate-600">Se registrará que esta Orden de Trabajo fue emitida y entregada a <strong><?= esc($order['mission_leader_name']??'Responsable de Misión') ?></strong>.</p>
        <form method="post" action="<?= route_to('work_orders.issue',$order['id']) ?>" class="mt-5"><?= csrf_field() ?><label><span class="mb-2 block text-sm font-semibold text-slate-700">Nota de entrega <span class="font-normal text-slate-400">(opcional)</span></span><textarea name="issuance_notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Ej. OT entregada al responsable junto con indicaciones operativas..."></textarea></label><div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button id="cancel-issue-modal" type="button" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Cancelar</button><button class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950">Confirmar emisión</button></div></form>
    </div>
</div>
<?php endif ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const modal=document.getElementById('issue-modal');
    const open=()=>{modal?.classList.remove('hidden');modal?.classList.add('flex')};
    const close=()=>{modal?.classList.add('hidden');modal?.classList.remove('flex')};
    document.getElementById('open-issue-modal')?.addEventListener('click',open);
    document.getElementById('close-issue-modal')?.addEventListener('click',close);
    document.getElementById('cancel-issue-modal')?.addEventListener('click',close);
    modal?.addEventListener('click',e=>{if(e.target===modal)close()});
    document.addEventListener('keydown',e=>{if(e.key==='Escape')close()});
});
</script>
<?= $this->endSection() ?>
