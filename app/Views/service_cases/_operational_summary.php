<?php
$summary = $operationalSummary ?? [];
if (!($summary['active'] ?? false)) {
    return;
}
$risk = $summary['incident_risk'] ?? 'Sin incidencias abiertas';
$riskClass = match ($risk) {
    'Crítico' => 'bg-red-100 text-red-800',
    'Alto' => 'bg-orange-100 text-orange-800',
    'Medio' => 'bg-amber-100 text-amber-800',
    'Bajo' => 'bg-cyan-100 text-cyan-800',
    default => 'bg-emerald-100 text-emerald-800',
};
$checklistPercent = $summary['checklist_percent'];
?>
<section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Resumen operativo</p>
            <h3 class="mt-2 text-xl font-bold text-slate-950">Estado vivo del servicio</h3>
            <p class="mt-1 text-sm text-slate-500">Consolidado desde la Orden de Trabajo, recursos, Mission Log, checklist, evidencias e incidencias.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-800"><?= esc($summary['status_label']) ?></span>
            <span class="rounded-full px-3 py-1 text-xs font-bold <?= $riskClass ?>"><?= esc($risk) ?></span>
        </div>
    </div>

    <div class="grid divide-y divide-slate-100 md:grid-cols-2 md:divide-x md:divide-y-0 xl:grid-cols-4">
        <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Inicio / tiempo</p>
            <p class="mt-3 font-bold text-slate-950"><?= !empty($summary['started_at']) ? esc(date('d/m/Y H:i', strtotime($summary['started_at']))) : 'Pendiente' ?></p>
            <p class="mt-1 text-sm text-slate-500"><?= esc($summary['elapsed_label']) ?></p>
        </div>
        <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Responsable de misión</p>
            <p class="mt-3 font-bold text-slate-950"><?= esc($summary['mission_leader']) ?></p>
            <p class="mt-1 text-sm text-slate-500"><?= (int)$summary['personnel_count'] ?> persona(s) · <?= (int)$summary['equipment_count'] ?> equipo(s)</p>
        </div>
        <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Control operativo</p>
            <p class="mt-3 font-bold text-slate-950"><?= $checklistPercent === null ? 'Checklist no disponible' : ((int)$checklistPercent . '% checklist') ?></p>
            <p class="mt-1 text-sm text-slate-500"><?= (int)$summary['checklist_required_pending'] ?> pendiente(s) · <?= (int)$summary['checklist_required_failed'] ?> no conforme(s)</p>
        </div>
        <div class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Trazabilidad</p>
            <p class="mt-3 font-bold text-slate-950"><?= (int)$summary['open_incidents'] ?> incidencia(s) abierta(s)</p>
            <p class="mt-1 text-sm text-slate-500"><?= (int)$summary['mission_log_count'] ?> movimientos · <?= (int)$summary['evidence_count'] ?> evidencias</p>
        </div>
    </div>

    <?php if(($summary['critical_incidents'] ?? 0) > 0): ?>
        <div class="border-t border-red-100 bg-red-50 px-6 py-4 text-sm text-red-800"><strong>Atención inmediata:</strong> existe <?= (int)$summary['critical_incidents'] ?> incidencia(s) crítica(s) abierta(s). La próxima acción del Expediente ha sido priorizada para su atención.</div>
    <?php elseif(($summary['high_incidents'] ?? 0) > 0): ?>
        <div class="border-t border-orange-100 bg-orange-50 px-6 py-4 text-sm text-orange-800"><strong>Seguimiento requerido:</strong> existe <?= (int)$summary['high_incidents'] ?> incidencia(s) de severidad alta abierta(s).</div>
    <?php endif ?>
</section>
