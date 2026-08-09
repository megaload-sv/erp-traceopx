<?php
use App\Services\WorkOrderCompletionService;

$isInProgress = isset($order['status']) && in_array($order['status'], ['in_progress','working'], true);
$isFinished = isset($order['status']) && in_array($order['status'], ['finished','completed','closed'], true);
$completionReadiness = $isInProgress ? (new WorkOrderCompletionService())->readiness((int) $order['id']) : null;
?>
<section id="operational-completion" class="rounded-2xl border <?= $isFinished ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white' ?> p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-600">Cierre de ejecución</p>
            <h3 class="mt-2 text-xl font-bold text-slate-950">Finalización operativa</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Confirma que el trabajo físico terminó. Esta acción libera los recursos para nuevas coordinaciones, conserva todo el histórico y deja el Expediente pendiente de aceptación del cliente.</p>
        </div>
        <?php if($isFinished): ?><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Trabajo finalizado</span><?php elseif($isInProgress && ($completionReadiness['ready'] ?? false)): ?><span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-bold text-cyan-800">Listo para finalizar</span><?php else: ?><span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Validaciones pendientes</span><?php endif ?>
    </div>

    <?php if($isFinished): ?>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <article class="rounded-xl border border-emerald-200 bg-white p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Finalización real</p><p class="mt-2 font-bold text-slate-950"><?= esc(!empty($order['finished_at']) ? date('d/m/Y H:i', strtotime($order['finished_at'])) : '—') ?></p></article>
            <article class="rounded-xl border border-emerald-200 bg-white p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Siguiente etapa</p><p class="mt-2 font-bold text-slate-950">Aceptación del cliente</p></article>
        </div>
        <?php if(!empty($order['completion_summary'])): ?><div class="mt-4 rounded-xl border border-emerald-200 bg-white p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Resumen del trabajo</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700"><?= esc($order['completion_summary']) ?></p><?php if(!empty($order['completion_notes'])): ?><p class="mt-3 border-t border-slate-100 pt-3 text-sm text-slate-600"><strong>Observaciones:</strong> <?= esc($order['completion_notes']) ?></p><?php endif ?></div><?php endif ?>
    <?php elseif($isInProgress): ?>
        <div class="mt-6 grid gap-3 md:grid-cols-3">
            <article class="rounded-xl border <?= ($completionReadiness['checklist_ready'] ?? false) ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' ?> p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Checklist</p><p class="mt-2 font-bold text-slate-950"><?= (int)($completionReadiness['checklist_percent'] ?? 0) ?>%</p><p class="mt-1 text-xs text-slate-500"><?= ($completionReadiness['checklist_ready'] ?? false) ? 'Conforme' : 'Requiere atención' ?></p></article>
            <article class="rounded-xl border <?= (int)($completionReadiness['critical_incidents'] ?? 0)===0 ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50' ?> p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Incidencias críticas</p><p class="mt-2 font-bold text-slate-950"><?= (int)($completionReadiness['critical_incidents'] ?? 0) ?></p><p class="mt-1 text-xs text-slate-500"><?= (int)($completionReadiness['critical_incidents'] ?? 0)===0 ? 'Sin bloqueos críticos' : 'Deben resolverse' ?></p></article>
            <article class="rounded-xl border <?= ($completionReadiness['ready'] ?? false) ? 'border-cyan-200 bg-cyan-50' : 'border-slate-200 bg-slate-50' ?> p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Estado de cierre</p><p class="mt-2 font-bold text-slate-950"><?= ($completionReadiness['ready'] ?? false) ? 'Listo' : 'Bloqueado' ?></p><p class="mt-1 text-xs text-slate-500">Validación automática</p></article>
        </div>

        <?php if(!($completionReadiness['ready'] ?? false)): ?>
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="font-bold text-amber-900">Antes de finalizar:</p>
                <ul class="mt-2 space-y-1 text-sm text-amber-800"><?php foreach(($completionReadiness['blocking_reasons'] ?? []) as $reason): ?><li>• <?= esc($reason) ?></li><?php endforeach ?></ul>
            </div>
        <?php else: ?>
            <form method="post" action="<?= route_to('work_orders.finish',$order['id']) ?>" data-processing-message="Finalizando trabajo operativo…" class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50/40 p-5">
                <?= csrf_field() ?>
                <div class="grid gap-4 md:grid-cols-2">
                    <label><span class="mb-2 block text-sm font-semibold text-slate-700">Fecha y hora real de finalización *</span><input type="datetime-local" name="finished_at" required value="<?= esc(old('finished_at') ?: date('Y-m-d\TH:i')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
                    <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900"><strong>Al confirmar:</strong><br>personal y maquinaria se liberarán operacionalmente y el Expediente avanzará a pendiente de aceptación del cliente.</div>
                    <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Resumen del trabajo realizado *</span><textarea name="completion_summary" rows="4" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3" placeholder="Describe de forma concreta el trabajo completado y el resultado final."><?= esc(old('completion_summary') ?? '') ?></textarea></label>
                    <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Observaciones finales <span class="font-normal text-slate-400">(opcional)</span></span><textarea name="completion_notes" rows="3" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3" placeholder="Condiciones especiales, recomendaciones o asuntos que deban quedar documentados."><?= esc(old('completion_notes') ?? '') ?></textarea></label>
                </div>
                <div class="mt-5 flex justify-end"><button class="rounded-xl bg-emerald-600 px-5 py-3 font-bold text-white hover:bg-emerald-500">Finalizar trabajo operativo →</button></div>
            </form>
        <?php endif ?>
    <?php else: ?>
        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">La finalización operativa estará disponible cuando la Orden de Trabajo se encuentre en ejecución.</div>
    <?php endif ?>
</section>
