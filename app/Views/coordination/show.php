<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$completion=(int)($resourceWorkspace['completion_percent']??0);
$ready=(bool)($resourceWorkspace['ready_for_approval']??false);
?>
<?php if (session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Coordination Workspace</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($plan['code']) ?></h2>
        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
            <a href="<?= route_to('service_cases.show',$plan['service_case_id']) ?>" class="font-bold text-violet-700"><?= esc($plan['service_case_code']) ?> ↗</a>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800"><?= esc(ucfirst($plan['status'])) ?></span>
            <span class="rounded-full <?= $ready?'bg-emerald-100 text-emerald-800':'bg-slate-100 text-slate-700' ?> px-3 py-1 text-xs font-bold">Recursos <?= $completion ?>%</span>
        </div>
    </div>
    <a href="<?= route_to('coordination.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700">Volver a coordinación</a>
</div>

<section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Cliente</p><p class="mt-3 font-bold text-slate-950"><?= esc($plan['business_name']) ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Programación</p><p class="mt-3 font-bold text-slate-950"><?= esc($plan['scheduled_start_at'] ? date('d/m/Y H:i',strtotime($plan['scheduled_start_at'])) : 'Pendiente') ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Ubicación</p><p class="mt-3 font-bold text-slate-950"><?= esc($plan['location'] ?: 'Pendiente') ?></p></article>
    <article class="rounded-2xl border border-slate-200 bg-white p-5"><p class="text-xs uppercase text-slate-500">Prioridad</p><p class="mt-3 font-bold text-slate-950"><?= esc(ucfirst($plan['priority'])) ?></p></article>
</section>

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
<div class="space-y-6">
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Maquinaria prevista</p>
    <div class="mt-5 space-y-3"><?php foreach($equipment as $item): ?><article class="rounded-xl border border-slate-200 p-4"><div class="flex items-center justify-between gap-4"><div><p class="font-bold text-slate-950"><?= esc($item['code']) ?> · <?= esc($item['name']) ?></p><p class="mt-1 text-xs text-slate-500">Operación: <?= esc(str_replace('_',' ',$item['operational_status'])) ?> · Mantenimiento: <?= esc(str_replace('_',' ',$item['maintenance_status'])) ?></p></div><span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-bold text-cyan-800">Planificado</span></div></article><?php endforeach ?><?php if($equipment===[]): ?><p class="text-sm text-slate-500">Todavía no se asignó maquinaria al plan.</p><?php endif ?></div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Resource Allocation Engine</p><h3 class="mt-2 text-xl font-bold text-slate-950">Equipo operativo</h3><p class="mt-2 text-sm text-slate-500">TraceOPX muestra únicamente colaboradores con la habilidad requerida, certificación válida y sin conflicto de horario.</p></div><span class="rounded-full <?= $ready?'bg-emerald-100 text-emerald-800':'bg-amber-100 text-amber-800' ?> px-3 py-1 text-xs font-bold"><?= $ready?'Requisitos cubiertos':'Asignación pendiente' ?></span></div>

    <div class="mt-6 space-y-4">
    <?php foreach($requirements as $req): ?>
        <?php $remaining=max(0,(int)$req['max_quantity']-(int)$req['assigned_count']); ?>
        <article class="rounded-2xl border <?= $req['requirement_type']==='required'?'border-red-200':'border-slate-200' ?> bg-slate-50 p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-bold text-slate-950"><?= esc($req['equipment_code']) ?> · <?= esc($req['role_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($req['equipment_name']) ?> · <?= $req['requirement_type']==='required'?'Obligatorio':'Opcional' ?> · Mín. <?= esc((string)$req['min_quantity']) ?> / Máx. <?= esc((string)$req['max_quantity']) ?></p></div><span class="rounded-full px-3 py-1 text-xs font-bold <?= (int)$req['assigned_count'] >= (int)$req['min_quantity'] ? 'bg-emerald-100 text-emerald-800':'bg-red-100 text-red-800' ?>"><?= esc((string)$req['assigned_count']) ?> asignado(s)</span></div>

            <?php if($req['allocations']!==[]): ?><div class="mt-4 grid gap-3 md:grid-cols-2"><?php foreach($req['allocations'] as $allocation): ?><div class="rounded-xl border border-emerald-200 bg-white p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-bold text-slate-900"><?= esc($allocation['employee_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($allocation['employee_code']) ?> · Reservado</p></div><form method="post" action="<?= route_to('coordination.resources.release',$plan['id'],$allocation['id']) ?>"><?= csrf_field() ?><button class="text-xs font-bold text-red-600 hover:text-red-800">Liberar</button></form></div></div><?php endforeach ?></div><?php endif ?>

            <?php if($plan['status']==='draft' && $remaining>0): ?>
            <form method="post" action="<?= route_to('coordination.resources.role',$plan['id']) ?>" class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                <?= csrf_field() ?><input type="hidden" name="equipment_id" value="<?= esc((string)$req['equipment_id']) ?>"><input type="hidden" name="role_id" value="<?= esc((string)$req['role_id']) ?>">
                <label class="block"><span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Colaborador compatible</span><select name="employee_id" required data-placeholder="Seleccionar colaborador"><option value="">Seleccione</option><?php foreach($req['candidates'] as $candidate): ?><option value="<?= esc((string)$candidate['id']) ?>"><?= esc($candidate['employee_code']) ?> — <?= esc($candidate['name']) ?> — <?= esc(ucfirst($candidate['proficiency_level'])) ?><?= !empty($candidate['valid_until'])?' · vence '.$candidate['valid_until']:'' ?></option><?php endforeach ?></select></label>
                <button class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300" <?= $req['candidates']===[]?'disabled':'' ?>>Reservar</button>
            </form>
            <?php if($req['candidates']===[]): ?><p class="mt-3 text-xs font-semibold text-amber-700">No hay colaboradores compatibles y disponibles para este perfil.</p><?php endif ?>
            <?php endif ?>
        </article>
    <?php endforeach ?>
    <?php if($requirements===[]): ?><p class="text-sm text-slate-500">Los equipos seleccionados no tienen requisitos humanos configurados.</p><?php endif ?>
    </div>
</section>

<section class="rounded-2xl border border-violet-200 bg-violet-50 p-6 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-700">Responsable de misión</p>
    <h3 class="mt-2 text-xl font-bold text-slate-950">Dirección del equipo operativo</h3>
    <p class="mt-2 text-sm text-slate-600">Es una responsabilidad única y puede coincidir con un miembro de la cuadrilla si también posee esta capacidad.</p>
    <?php if($resourceWorkspace['mission_leader']): $leader=$resourceWorkspace['mission_leader']; ?>
        <div class="mt-5 rounded-xl border border-violet-200 bg-white p-4"><div class="flex items-center justify-between gap-4"><div><p class="font-bold text-slate-950"><?= esc($leader['employee_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($leader['employee_code']) ?> · Responsable reservado</p></div><form method="post" action="<?= route_to('coordination.resources.release',$plan['id'],$leader['id']) ?>"><?= csrf_field() ?><button class="text-xs font-bold text-red-600">Cambiar / liberar</button></form></div></div>
    <?php elseif($plan['status']==='draft'): ?>
        <form method="post" action="<?= route_to('coordination.resources.mission_leader',$plan['id']) ?>" class="mt-5 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end"><?= csrf_field() ?><label><span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Responsable disponible</span><select name="employee_id" required data-placeholder="Seleccionar responsable"><option value="">Seleccione</option><?php foreach($resourceWorkspace['mission_leader_candidates'] as $candidate): ?><option value="<?= esc((string)$candidate['id']) ?>"><?= esc($candidate['employee_code']) ?> — <?= esc($candidate['name']) ?></option><?php endforeach ?></select></label><button class="rounded-xl bg-violet-600 px-5 py-3 font-bold text-white" <?= $resourceWorkspace['mission_leader_candidates']===[]?'disabled':'' ?>>Asignar</button></form>
        <?php if($resourceWorkspace['mission_leader_candidates']===[]): ?><p class="mt-3 text-xs font-semibold text-amber-700">No hay colaboradores disponibles con la capacidad Responsable de misión.</p><?php endif ?>
    <?php endif ?>
</section>

<?php if(!empty($plan['scope_notes'])): ?><section class="rounded-2xl border border-slate-200 bg-white p-6"><p class="text-xs uppercase text-slate-500">Alcance operativo</p><p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700"><?= esc($plan['scope_notes']) ?></p></section><?php endif ?>
</div>

<aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
<section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Próxima acción</p>
    <h3 class="mt-3 text-xl font-bold"><?= $ready?'Aprobar coordinación':'Completar equipo operativo' ?></h3>
    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-800"><div class="h-full bg-cyan-400" style="width:<?= esc((string)$completion) ?>%"></div></div>
    <p class="mt-2 text-xs font-bold text-cyan-300"><?= esc((string)$completion) ?>% de requisitos obligatorios cubiertos</p>
    <?php if(!$ready): ?><div class="mt-5 space-y-2"><?php foreach($resourceWorkspace['missing_required'] as $missing): ?><p class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-slate-300">○ <?= esc($missing) ?></p><?php endforeach ?></div><?php else: ?><p class="mt-4 text-sm leading-6 text-emerald-200">La coordinación ya tiene cubiertos sus requisitos humanos obligatorios. El siguiente incremento habilitará la aprobación operativa y conversión de reservas en asignaciones formales.</p><?php endif ?>
</section>
<section class="rounded-2xl border border-slate-200 bg-white p-6"><p class="text-xs uppercase tracking-wide text-slate-500">Regla de proceso</p><p class="mt-3 text-sm leading-6 text-slate-600">Una reserva impide utilizar al colaborador en otra coordinación que se traslape en horario. Liberar la reserva devuelve su disponibilidad cuando no mantiene otros compromisos activos.</p></section>
</aside>
</div>
<?= $this->endSection() ?>
