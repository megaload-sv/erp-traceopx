<?php
$isInProgress = isset($order['status']) && in_array($order['status'], ['in_progress','working'], true);
$typeLabels = $incidentTypes ?? [];
$severityLabels = $incidentSeverities ?? [];
$severityClasses = [
    'low' => 'bg-slate-100 text-slate-700',
    'medium' => 'bg-amber-100 text-amber-800',
    'high' => 'bg-orange-100 text-orange-800',
    'critical' => 'bg-red-100 text-red-800',
];
?>
<section id="operational-incidents" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-red-600">Control operativo</p>
            <h3 class="mt-2 text-xl font-bold text-slate-950">Incidencias y sustituciones</h3>
            <p class="mt-2 text-sm text-slate-500">Registra desviaciones de la misión. Los cambios de recursos requieren propuesta y aprobación separadas.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"><?= count($incidents ?? []) ?> incidencia(s)</span>
    </div>

    <?php if($isInProgress): ?>
    <form method="post" action="<?= route_to('work_orders.incidents.store',$order['id']) ?>" data-processing-message="Registrando incidencia operativa…" class="mt-6 rounded-2xl border border-red-200 bg-red-50/40 p-5">
        <?= csrf_field() ?>
        <div class="grid gap-4 md:grid-cols-2">
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Tipo *</span><select name="incident_type" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><option value="">Seleccionar</option><?php foreach($typeLabels as $code=>$label): ?><option value="<?= esc($code) ?>"><?= esc($label) ?></option><?php endforeach ?></select></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Severidad *</span><select name="severity" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><?php foreach($severityLabels as $code=>$label): ?><option value="<?= esc($code) ?>" <?= $code==='medium'?'selected':'' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Fecha y hora *</span><input type="datetime-local" name="incident_occurred_at" required value="<?= esc(date('Y-m-d\TH:i')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Maquinaria afectada <span class="font-normal text-slate-400">(opcional)</span></span><select name="incident_equipment_id" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><option value="">No aplica</option><?php foreach($equipment as $item): ?><option value="<?= esc((string)$item['equipment_id']) ?>"><?= esc($item['equipment_code_snapshot'].' · '.$item['equipment_name_snapshot']) ?></option><?php endforeach ?></select></label>
            <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Colaborador afectado <span class="font-normal text-slate-400">(opcional)</span></span><select name="incident_team_id" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><option value="">No aplica</option><?php foreach($team as $member): ?><option value="<?= esc((string)$member['id']) ?>"><?= esc($member['employee_name_snapshot'].' · '.($member['role_name_snapshot']??'Recurso operativo')) ?></option><?php endforeach ?></select></label>
            <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Título *</span><input type="text" name="incident_title" required maxlength="180" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3" placeholder="Ej. Operador indispuesto durante la maniobra"></label>
            <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Descripción *</span><textarea name="incident_description" rows="4" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3" placeholder="Describe qué ocurrió, impacto observado y condición actual."></textarea></label>
        </div>
        <div class="mt-5 flex justify-end"><button class="rounded-xl bg-red-600 px-5 py-3 font-bold text-white hover:bg-red-500">Registrar incidencia</button></div>
    </form>
    <?php endif ?>

    <div class="mt-6 space-y-4">
        <?php foreach(($incidents ?? []) as $incident): ?>
        <article class="rounded-2xl border <?= $incident['status']==='open'?'border-red-200 bg-red-50/30':'border-emerald-200 bg-emerald-50/30' ?> p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2"><h4 class="font-bold text-slate-950"><?= esc($incident['title']) ?></h4><span class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase <?= esc($severityClasses[$incident['severity']] ?? 'bg-slate-100 text-slate-700') ?>"><?= esc($severityLabels[$incident['severity']] ?? $incident['severity']) ?></span><span class="rounded-full <?= $incident['status']==='open'?'bg-red-100 text-red-700':'bg-emerald-100 text-emerald-700' ?> px-2.5 py-1 text-[11px] font-bold uppercase"><?= $incident['status']==='open'?'Abierta':'Resuelta' ?></span></div>
                    <p class="mt-2 text-sm text-slate-500"><?= esc($typeLabels[$incident['incident_type']] ?? $incident['incident_type']) ?> · <?= esc(date('d/m/Y H:i',strtotime($incident['occurred_at']))) ?></p>
                    <p class="mt-3 text-sm leading-6 text-slate-700"><?= esc($incident['description']) ?></p>
                </div>
            </div>

            <?php foreach(($incident['changes'] ?? []) as $change): ?>
                <div class="mt-4 rounded-xl border border-violet-200 bg-violet-50 p-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-violet-700">Sustitución <?= esc($change['status']) ?></p>
                    <p class="mt-2 font-semibold text-slate-900"><?= esc(($change['outgoing_name']??'Recurso saliente').' → '.($change['incoming_name']??'Recurso entrante')) ?></p>
                    <p class="mt-1 text-sm text-slate-600"><?= esc($change['reason']) ?></p>
                    <?php if($change['status']==='proposed'): ?><form method="post" action="<?= route_to('work_orders.substitutions.approve',$order['id'],$change['id']) ?>" data-processing-message="Aprobando sustitución de personal…" class="mt-4"><?= csrf_field() ?><button class="rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-bold text-white">Aprobar y ejecutar sustitución</button></form><?php endif ?>
                </div>
            <?php endforeach ?>

            <?php
            $hasPending = false;
            foreach(($incident['changes'] ?? []) as $change){ if(in_array($change['status'],['proposed','approved'],true)){$hasPending=true;break;} }
            ?>
            <?php if($isInProgress && $incident['status']==='open' && !empty($incident['work_order_team_id']) && !$hasPending): ?>
                <?php if(($incident['replacement_candidates'] ?? []) !== []): ?>
                <form method="post" action="<?= route_to('work_orders.substitutions.propose',$order['id'],$incident['id']) ?>" data-processing-message="Proponiendo sustitución de personal…" class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                    <?= csrf_field() ?>
                    <p class="text-sm font-bold text-slate-900">Proponer sustituto</p>
                    <div class="mt-3 grid gap-3 md:grid-cols-2"><select name="incoming_employee_id" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><option value="">Seleccionar colaborador compatible</option><?php foreach($incident['replacement_candidates'] as $candidate): ?><option value="<?= esc((string)$candidate['id']) ?>"><?= esc($candidate['employee_code'].' · '.$candidate['name']) ?><?= !empty($candidate['proficiency_level'])?' · '.esc(ucfirst($candidate['proficiency_level'])):'' ?></option><?php endforeach ?></select><input type="text" name="substitution_reason" required maxlength="500" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3" placeholder="Motivo de la sustitución"></div>
                    <div class="mt-3 flex justify-end"><button class="rounded-xl border border-violet-300 bg-violet-50 px-4 py-2.5 text-sm font-bold text-violet-800">Proponer sustitución</button></div>
                </form>
                <?php else: ?><div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">No hay colaboradores compatibles y disponibles para sustituir este recurso en este momento.</div><?php endif ?>
            <?php endif ?>
        </article>
        <?php endforeach ?>
        <?php if(($incidents ?? [])===[]): ?><div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">No se han registrado incidencias durante esta misión.</div><?php endif ?>
    </div>
</section>

<?= $this->include('work_orders/_completion') ?>
