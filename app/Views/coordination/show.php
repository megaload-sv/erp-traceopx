<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$resourceCompletion = (int) ($resourceWorkspace['completion_percent'] ?? 0);
$resourceReady = (bool) ($resourceWorkspace['ready_for_approval'] ?? false);
$hasEquipment = ! empty($equipment);
$approvalReady = (bool) ($approvalChecklist['ready'] ?? false);
$approved = (bool) ($approvalChecklist['approved'] ?? false);
$approvalCompletion = (int) ($approvalChecklist['completion_percent'] ?? 0);
$approvalChecks = $approvalChecklist['checks'] ?? [];
$levelLabels = ['' => 'Sin nivel asignado', 'trainee' => 'En formación', 'qualified' => 'Calificado', 'senior' => 'Senior', 'specialist' => 'Especialista', 'instructor' => 'Instructor'];
?>

<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Coordination Workspace</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($plan['code']) ?></h2>
        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
            <a href="<?= route_to('service_cases.show', $plan['service_case_id']) ?>" class="font-bold text-violet-700"><?= esc($plan['service_case_code']) ?> ↗</a>
            <span class="rounded-full px-3 py-1 text-xs font-semibold <?= $approved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>"><?= $approved ? 'Aprobada' : 'Borrador' ?></span>
            <span class="rounded-full <?= $resourceReady ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' ?> px-3 py-1 text-xs font-bold">Recursos <?= $resourceCompletion ?>%</span>
            <span class="rounded-full <?= ($approvalReady || $approved) ? 'bg-cyan-100 text-cyan-800' : 'bg-slate-100 text-slate-700' ?> px-3 py-1 text-xs font-bold">Preparación <?= $approvalCompletion ?>%</span>
        </div>
    </div>
    <a href="<?= route_to('coordination.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Volver a coordinación</a>
</div>

<?php if ($approved): ?>
<div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
    <p class="text-xs font-semibold uppercase tracking-[.16em] text-emerald-700">Plan operativo congelado</p>
    <p class="mt-2 font-bold text-emerald-950">La coordinación fue aprobada y sus recursos quedaron formalmente asignados.</p>
    <p class="mt-1 text-sm text-emerald-700">Los cambios posteriores deberán pasar por el flujo controlado de sustituciones.</p>
</div>
<?php endif ?>

<section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Cliente</p><p class="mt-3 font-bold text-slate-950"><?= esc($plan['business_name']) ?></p></article>
    <article class="rounded-2xl border <?= empty($plan['scheduled_start_at']) ? 'border-amber-200' : 'border-slate-200' ?> bg-white p-5"><p class="text-xs uppercase text-slate-500">Programación</p><p class="mt-3 font-bold text-slate-950"><?= esc($plan['scheduled_start_at'] ? date('d/m/Y H:i', strtotime($plan['scheduled_start_at'])) : 'Pendiente') ?></p></article>
    <article class="rounded-2xl border <?= empty($plan['location']) ? 'border-amber-200' : 'border-slate-200' ?> bg-white p-5"><p class="text-xs uppercase text-slate-500">Ubicación</p><p class="mt-3 font-bold text-slate-950"><?= esc($plan['location'] ?: 'Pendiente') ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Prioridad</p><p class="mt-3 font-bold text-slate-950"><?= esc(ucfirst($plan['priority'])) ?></p></article>
</section>

<?php if ($plan['status'] === 'draft'): ?>
<details class="group mb-6 rounded-2xl border <?= ($approvalCompletion < 100) ? 'border-cyan-200' : 'border-slate-200' ?> bg-white shadow-sm" <?= (empty($plan['scheduled_start_at']) || empty($plan['location']) || empty($plan['scope_notes'])) ? 'open' : '' ?>>
    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-6">
        <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Planificación operativa</p><h3 class="mt-2 text-xl font-bold text-slate-950">Programación, ubicación y alcance</h3><p class="mt-1 text-sm text-slate-500">Puede modificarse mientras la coordinación permanezca en borrador.</p></div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700 group-open:bg-cyan-100 group-open:text-cyan-800">Editar</span>
    </summary>
    <form method="post" action="<?= route_to('coordination.planning.update', $plan['id']) ?>" class="border-t border-slate-100 p-6">
        <?= csrf_field() ?>
        <div class="grid gap-5 md:grid-cols-2">
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Fecha programada *</span><input type="datetime-local" name="scheduled_start_at" required value="<?= esc($plan['scheduled_start_at'] ? date('Y-m-d\TH:i', strtotime($plan['scheduled_start_at'])) : '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Fin estimado *</span><input type="datetime-local" name="estimated_end_at" required value="<?= esc($plan['estimated_end_at'] ? date('Y-m-d\TH:i', strtotime($plan['estimated_end_at'])) : '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Lugar de ejecución *</span><input name="location" required value="<?= esc($plan['location'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Referencia del lugar</span><input name="location_reference" value="<?= esc($plan['location_reference'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Prioridad</span><select name="priority" class="w-full rounded-xl border border-slate-300 px-4 py-3"><option value="normal" <?= $plan['priority'] === 'normal' ? 'selected' : '' ?>>Normal</option><option value="high" <?= $plan['priority'] === 'high' ? 'selected' : '' ?>>Alta</option><option value="critical" <?= $plan['priority'] === 'critical' ? 'selected' : '' ?>>Crítica</option></select></label>
            <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Alcance operativo *</span><textarea name="scope_notes" required rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc($plan['scope_notes'] ?? '') ?></textarea></label>
            <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Notas de coordinación</span><textarea name="coordination_notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc($plan['coordination_notes'] ?? '') ?></textarea></label>
        </div>
        <div class="mt-5 flex justify-end"><button class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Guardar planificación</button></div>
    </form>
</details>
<?php endif ?>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
<div class="space-y-6">
<section class="rounded-2xl border <?= $hasEquipment ? 'border-slate-200' : 'border-red-200' ?> bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Maquinaria prevista</p><h3 class="mt-2 text-xl font-bold text-slate-950">Recursos físicos de la misión</h3></div><span class="rounded-full <?= $hasEquipment ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?> px-3 py-1 text-xs font-bold"><?= $hasEquipment ? count($equipment) . ' equipo(s)' : 'Obligatorio' ?></span></div>
    <div class="mt-5 space-y-3">
        <?php foreach ($equipment as $item): ?>
            <article class="rounded-xl border border-slate-200 p-4"><div class="flex items-center justify-between gap-4"><div><p class="font-bold text-slate-950"><?= esc($item['code']) ?> · <?= esc($item['name']) ?></p><p class="mt-1 text-xs text-slate-500">Operación: <?= esc(str_replace('_', ' ', $item['operational_status'])) ?> · Mantenimiento: <?= esc(str_replace('_', ' ', $item['maintenance_status'])) ?></p></div><span class="rounded-full <?= $approved ? 'bg-emerald-100 text-emerald-800' : 'bg-cyan-100 text-cyan-800' ?> px-3 py-1 text-xs font-bold"><?= $approved ? 'Reservado' : 'Planificado' ?></span></div></article>
        <?php endforeach ?>
    </div>

    <?php if ($plan['status'] === 'draft' && ! empty($equipmentAvailableToAdd)): ?>
        <form id="add-equipment-form" method="post" action="<?= route_to('coordination.equipment.add', $plan['id']) ?>" class="mt-5 rounded-2xl border border-cyan-200 bg-cyan-50/40 p-5">
            <?= csrf_field() ?>
            <p class="text-sm font-bold text-slate-900"><?= $hasEquipment ? 'Agregar maquinaria adicional' : 'Completar maquinaria obligatoria' ?></p>
            <p class="mt-1 text-xs text-slate-500">Solo aparecen equipos actualmente disponibles y con mantenimiento compatible.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2"><?php foreach ($equipmentAvailableToAdd as $item): ?><label class="flex cursor-pointer gap-3 rounded-xl border border-slate-200 bg-white p-4 hover:border-cyan-300"><input class="add-equipment-checkbox mt-1" type="checkbox" name="equipment_ids[]" value="<?= esc((string) $item['id']) ?>"><span><span class="block font-bold text-slate-900"><?= esc($item['code']) ?> · <?= esc($item['name']) ?></span><span class="mt-1 block text-xs text-slate-500"><?= esc(str_replace('_', ' ', $item['maintenance_status'])) ?></span></span></label><?php endforeach ?></div>
            <p id="add-equipment-error" class="mt-3 hidden text-sm font-semibold text-red-700">Selecciona al menos un equipo.</p>
            <div class="mt-4 flex justify-end"><button class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Agregar y recalcular requisitos</button></div>
        </form>
    <?php endif ?>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Resource Allocation Engine</p><h3 class="mt-2 text-xl font-bold text-slate-950">Equipo operativo</h3><p class="mt-2 text-sm text-slate-500">Solo aparecen colaboradores compatibles, vigentes y sin conflicto de horario.</p></div><span class="rounded-full <?= $resourceReady ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?> px-3 py-1 text-xs font-bold"><?= $resourceReady ? 'Requisitos cubiertos' : 'Asignación pendiente' ?></span></div>
    <div class="mt-6 space-y-4">
        <?php foreach ($requirements as $req): $remaining = max(0, (int) $req['max_quantity'] - (int) $req['assigned_count']); ?>
            <article class="rounded-2xl border <?= $req['requirement_type'] === 'required' ? 'border-red-200' : 'border-slate-200' ?> bg-slate-50 p-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-bold text-slate-950"><?= esc($req['equipment_code']) ?> · <?= esc($req['role_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($req['equipment_name']) ?> · <?= $req['requirement_type'] === 'required' ? 'Obligatorio' : 'Opcional' ?> · Mín. <?= esc((string) $req['min_quantity']) ?> / Máx. <?= esc((string) $req['max_quantity']) ?></p></div><span class="rounded-full px-3 py-1 text-xs font-bold <?= (int) $req['assigned_count'] >= (int) $req['min_quantity'] ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>"><?= esc((string) $req['assigned_count']) ?> asignado(s)</span></div>
                <?php if ($req['allocations'] !== []): ?><div class="mt-4 grid gap-3 md:grid-cols-2"><?php foreach ($req['allocations'] as $allocation): ?><div class="rounded-xl border border-emerald-200 bg-white p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-bold text-slate-900"><?= esc($allocation['employee_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($allocation['employee_code']) ?> · <?= $allocation['allocation_status'] === 'reserved' ? 'Reservado' : 'Asignado' ?></p></div><?php if ($plan['status'] === 'draft'): ?><form method="post" action="<?= route_to('coordination.resources.release', $plan['id'], $allocation['id']) ?>"><?= csrf_field() ?><button class="text-xs font-bold text-red-600">Liberar</button></form><?php endif ?></div></div><?php endforeach ?></div><?php endif ?>
                <?php if ($plan['status'] === 'draft' && $remaining > 0): ?>
                    <form method="post" action="<?= route_to('coordination.resources.role', $plan['id']) ?>" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end"><?= csrf_field() ?><input type="hidden" name="equipment_id" value="<?= esc((string) $req['equipment_id']) ?>"><input type="hidden" name="role_id" value="<?= esc((string) $req['role_id']) ?>"><label><span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Colaborador compatible</span><select name="employee_id" required><option value="">Seleccione</option><?php foreach ($req['candidates'] as $candidate): $level = (string) ($candidate['proficiency_level'] ?? ''); ?><option value="<?= esc((string) $candidate['id']) ?>"><?= esc($candidate['employee_code']) ?> — <?= esc($candidate['name']) ?> — <?= esc($levelLabels[$level] ?? $level) ?><?= ! empty($candidate['valid_until']) ? ' · vence ' . esc($candidate['valid_until']) : '' ?></option><?php endforeach ?></select></label><button class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 disabled:opacity-40" <?= $req['candidates'] === [] ? 'disabled' : '' ?>>Reservar</button></form>
                <?php endif ?>
            </article>
        <?php endforeach ?>
        <?php if ($requirements === []): ?><div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">La maquinaria seleccionada todavía no tiene requisitos humanos configurados.</div><?php endif ?>
    </div>
</section>

<section class="rounded-2xl border border-violet-200 bg-violet-50 p-6 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-700">Responsable de misión</p><h3 class="mt-2 text-xl font-bold text-slate-950">Dirección del equipo operativo</h3><p class="mt-2 text-sm text-slate-600">Puede coincidir con un miembro de la cuadrilla si también posee esta capacidad.</p>
    <?php if ($resourceWorkspace['mission_leader']): $leader = $resourceWorkspace['mission_leader']; ?><div class="mt-5 rounded-xl border border-violet-200 bg-white p-4"><div class="flex items-center justify-between gap-4"><div><p class="font-bold text-slate-950"><?= esc($leader['employee_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($leader['employee_code']) ?> · <?= $leader['allocation_status'] === 'reserved' ? 'Responsable reservado' : 'Responsable asignado' ?></p></div><?php if ($plan['status'] === 'draft'): ?><form method="post" action="<?= route_to('coordination.resources.release', $plan['id'], $leader['id']) ?>"><?= csrf_field() ?><button class="text-xs font-bold text-red-600">Cambiar / liberar</button></form><?php endif ?></div></div>
    <?php elseif ($plan['status'] === 'draft'): ?><form method="post" action="<?= route_to('coordination.resources.mission_leader', $plan['id']) ?>" class="mt-5 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end"><?= csrf_field() ?><label><span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Responsable disponible</span><select name="employee_id" required><option value="">Seleccione</option><?php foreach ($resourceWorkspace['mission_leader_candidates'] as $candidate): ?><option value="<?= esc((string) $candidate['id']) ?>"><?= esc($candidate['employee_code']) ?> — <?= esc($candidate['name']) ?></option><?php endforeach ?></select></label><button class="rounded-xl bg-violet-600 px-5 py-3 font-bold text-white disabled:opacity-40" <?= $resourceWorkspace['mission_leader_candidates'] === [] ? 'disabled' : '' ?>>Asignar</button></form><?php endif ?>
</section>
</div>

<aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
    <section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl">
        <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Próxima acción</p>
        <h3 class="mt-3 text-xl font-bold"><?= $approved ? 'Coordinación aprobada' : ($approvalReady ? 'Aprobar coordinación' : 'Completar coordinación') ?></h3>
        <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-800"><div class="h-full bg-cyan-400" style="width:<?= esc((string) $approvalCompletion) ?>%"></div></div>
        <p class="mt-2 text-xs font-bold text-cyan-300"><?= esc((string) $approvalCompletion) ?>% de preparación operativa</p>
        <div class="mt-5 space-y-2"><?php foreach ($approvalChecks as $check): ?><div class="flex items-center gap-3 rounded-xl border <?= $check['complete'] ? 'border-emerald-900/60 bg-emerald-950/30' : 'border-slate-800 bg-slate-900' ?> px-3 py-2.5"><span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold <?= $check['complete'] ? 'bg-emerald-400 text-slate-950' : 'bg-slate-800 text-slate-400' ?>"><?= $check['complete'] ? '✓' : '○' ?></span><span class="text-sm <?= $check['complete'] ? 'text-emerald-100' : 'text-slate-300' ?>"><?= esc($check['label']) ?></span></div><?php endforeach ?></div>
        <?php if ($approvalReady && ! $approved): ?><button id="open-approval-modal" type="button" class="mt-5 w-full rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Aprobar coordinación →</button><p class="mt-3 text-xs leading-5 text-slate-400">La aprobación asignará formalmente recursos y congelará el plan.</p>
        <?php elseif ($approved): ?><div class="mt-5 rounded-xl border border-emerald-900 bg-emerald-950/30 p-4"><p class="font-bold text-emerald-200"><?= $workOrder ? 'Orden de Trabajo generada' : 'Plan listo para Orden de Trabajo' ?></p><p class="mt-1 text-xs text-emerald-300"><?= $workOrder ? esc($workOrder['code']) . ' ya está vinculada a esta coordinación.' : 'La planificación aprobada puede convertirse en Orden de Trabajo.' ?></p><?php if($workOrder): ?><a href="<?= route_to('work_orders.show',$workOrder['id']) ?>" class="mt-4 block rounded-xl bg-cyan-400 px-4 py-3 text-center text-sm font-bold text-slate-950">Abrir Orden de Trabajo →</a><?php else: ?><form method="post" action="<?= route_to('work_orders.from_coordination',$plan['id']) ?>" class="mt-4"><?= csrf_field() ?><button class="w-full rounded-xl bg-cyan-400 px-4 py-3 text-sm font-bold text-slate-950">Generar Orden de Trabajo →</button></form><?php endif ?></div>
        <?php else: ?><p class="mt-5 text-sm leading-6 text-slate-300">Completa los puntos pendientes antes de autorizar la misión.</p><?php endif ?>
    </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-6"><p class="text-xs uppercase tracking-wide text-slate-500">Regla de proceso</p><p class="mt-3 text-sm leading-6 text-slate-600">Programación, alcance, maquinaria apta, responsable y perfiles humanos obligatorios deben estar completos antes de aprobar.</p></section>
</aside>
</div>

<?php if ($approvalReady && ! $approved): ?>
<div id="approval-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm"><div class="w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Confirmación operativa</p><h3 class="mt-2 text-2xl font-bold text-slate-950">Aprobar coordinación</h3></div><button id="close-approval-modal" type="button" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-xl text-slate-500">×</button></div><p class="mt-4 text-sm leading-6 text-slate-600">La maquinaria quedará reservada y los colaboradores pasarán a asignación formal. El plan quedará congelado.</p><div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Los cambios posteriores deberán quedar documentados y autorizados.</div><div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button id="cancel-approval-modal" type="button" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Cancelar</button><form method="post" action="<?= route_to('coordination.approve', $plan['id']) ?>"><?= csrf_field() ?><button class="w-full rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950">Confirmar aprobación</button></form></div></div></div>
<?php endif ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const equipmentForm = document.getElementById('add-equipment-form');
    equipmentForm?.addEventListener('submit', (event) => {
        const error = document.getElementById('add-equipment-error');
        if (equipmentForm.querySelectorAll('.add-equipment-checkbox:checked').length === 0) { event.preventDefault(); error?.classList.remove('hidden'); } else { error?.classList.add('hidden'); }
    });
    const modal = document.getElementById('approval-modal');
    const openModal = () => { modal?.classList.remove('hidden'); modal?.classList.add('flex'); };
    const closeModal = () => { modal?.classList.add('hidden'); modal?.classList.remove('flex'); };
    document.getElementById('open-approval-modal')?.addEventListener('click', openModal);
    document.getElementById('close-approval-modal')?.addEventListener('click', closeModal);
    document.getElementById('cancel-approval-modal')?.addEventListener('click', closeModal);
    modal?.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeModal(); });
});
</script>
<?= $this->endSection() ?>
