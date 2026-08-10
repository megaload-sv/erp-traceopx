<?php
use App\Services\WorkOrderClosureService;

$isAcceptedForClosure = isset($order['status']) && $order['status'] === 'accepted';
$isClosed = isset($order['status']) && $order['status'] === 'closed';
$closureReadiness = $isAcceptedForClosure ? (new WorkOrderClosureService())->readiness((int) $order['id']) : null;
?>
<section id="formal-closure" class="rounded-2xl border <?= $isClosed ? 'border-slate-300 bg-slate-50' : 'border-cyan-200 bg-cyan-50/30' ?> p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">10.5 · Cierre formal</p>
            <h3 class="mt-2 text-xl font-bold text-slate-950">Cierre de la Orden de Trabajo</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Confirma que la ejecución terminó, la aceptación del cliente está documentada y la OT puede abandonar el dominio operativo para pasar a facturación.</p>
        </div>
        <?php if($isClosed): ?>
            <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-bold text-slate-700">OT cerrada</span>
        <?php elseif($isAcceptedForClosure && ($closureReadiness['ready'] ?? false)): ?>
            <span class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-bold text-cyan-800">Lista para cierre</span>
        <?php else: ?>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Pendiente</span>
        <?php endif ?>
    </div>

    <?php if($isClosed): ?>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <article class="rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Cierre formal</p>
                <p class="mt-2 font-bold text-slate-950"><?= esc(!empty($order['closed_at']) ? date('d/m/Y H:i', strtotime($order['closed_at'])) : '—') ?></p>
            </article>
            <article class="rounded-xl border border-cyan-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Próxima etapa</p>
                <p class="mt-2 font-bold text-slate-950">Preparar facturación</p>
            </article>
        </div>
        <?php if(!empty($order['closure_notes'])): ?>
            <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Notas de cierre</p>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700"><?= esc($order['closure_notes']) ?></p>
            </div>
        <?php endif ?>
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            <strong>Ciclo operativo completado.</strong> La OT ya no requiere acciones de campo. El siguiente dominio será facturación, conservando toda la trazabilidad en el Expediente.
        </div>
    <?php elseif($isAcceptedForClosure): ?>
        <?php if(!($closureReadiness['ready'] ?? false)): ?>
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="font-bold text-amber-900">No es posible cerrar todavía:</p>
                <ul class="mt-2 space-y-1 text-sm text-amber-800">
                    <?php foreach(($closureReadiness['blocking_reasons'] ?? []) as $reason): ?><li>• <?= esc($reason) ?></li><?php endforeach ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <article class="rounded-xl border border-emerald-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Aceptación del cliente</p>
                    <p class="mt-2 font-bold text-slate-950"><?= esc($closureReadiness['acceptance']['receiver_name'] ?? 'Registrada') ?></p>
                    <p class="mt-1 text-xs text-slate-500"><?= !empty($closureReadiness['acceptance']['accepted_at']) ? esc(date('d/m/Y H:i', strtotime($closureReadiness['acceptance']['accepted_at']))) : '—' ?></p>
                </article>
                <article class="rounded-xl border border-cyan-200 bg-white p-4 text-sm leading-6 text-cyan-900">
                    <strong>Al cerrar:</strong><br>la OT pasará a estado <strong>Cerrada</strong>, el Expediente registrará el movimiento y la próxima acción será preparar la facturación.
                </article>
            </div>
            <form method="post" action="<?= route_to('work_orders.close',$order['id']) ?>" data-processing-message="Cerrando formalmente la Orden de Trabajo…" class="mt-5">
                <?= csrf_field() ?>
                <label>
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Notas de cierre <span class="font-normal text-slate-400">(opcional)</span></span>
                    <textarea name="closure_notes" rows="3" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3" placeholder="Observaciones administrativas u operativas que deban quedar en el expediente."><?= esc(old('closure_notes') ?? '') ?></textarea>
                </label>
                <div class="mt-5 flex justify-end">
                    <button class="rounded-xl bg-cyan-500 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-400">Cerrar Orden de Trabajo →</button>
                </div>
            </form>
        <?php endif ?>
    <?php else: ?>
        <div class="mt-5 rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">El cierre formal se habilita después de registrar una aceptación válida del cliente.</div>
    <?php endif ?>
</section>
