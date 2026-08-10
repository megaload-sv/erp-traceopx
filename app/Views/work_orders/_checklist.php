<?php
$isChecklistEditable = isset($order['status']) && in_array($order['status'], ['issued', 'in_progress'], true);
$summary = $checklist['summary'] ?? ['total'=>0,'answered'=>0,'percent'=>0,'required_pending'=>0,'required_failed'=>0,'ready'=>false];
?>
<section id="operational-checklist" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-600">Control operativo</p>
            <h3 class="mt-2 text-xl font-bold text-slate-950">Checklist de la misión</h3>
            <p class="mt-2 text-sm text-slate-500"><?= esc($checklist['template_name_snapshot'] ?? 'Checklist operativo') ?> · las verificaciones obligatorias formarán parte del cierre operativo.</p>
        </div>
        <div class="min-w-36 rounded-2xl bg-slate-950 px-4 py-3 text-white">
            <div class="flex items-center justify-between gap-3"><span class="text-xs uppercase tracking-wide text-slate-400">Avance</span><strong><?= (int)$summary['percent'] ?>%</strong></div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-800"><div class="h-full rounded-full bg-emerald-400" style="width:<?= (int)$summary['percent'] ?>%"></div></div>
        </div>
    </div>

    <?php if(($summary['required_failed'] ?? 0) > 0): ?>
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><strong><?= (int)$summary['required_failed'] ?> requisito(s) obligatorio(s) no cumplen.</strong> Deben resolverse o documentarse mediante el flujo de incidencias antes del cierre.</div>
    <?php elseif(($summary['required_pending'] ?? 0) > 0): ?>
        <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Quedan <strong><?= (int)$summary['required_pending'] ?> verificaciones obligatorias pendientes</strong>.</div>
    <?php elseif(($summary['ready'] ?? false)): ?>
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><strong>Checklist operativo conforme.</strong> Todas las verificaciones obligatorias están cubiertas.</div>
    <?php endif ?>

    <div class="mt-6 space-y-4">
        <?php foreach(($checklist['items'] ?? []) as $item):
            $response = $item['response'] ?? null;
            $statusClass = $response === 'yes' ? 'border-emerald-200 bg-emerald-50/50' : ($response === 'no' ? 'border-red-200 bg-red-50/50' : ($response === 'na' ? 'border-slate-200 bg-slate-50' : 'border-amber-200 bg-amber-50/30'));
            $statusLabel = ['yes'=>'Cumple','no'=>'No cumple','na'=>'No aplica'][$response] ?? 'Pendiente';
        ?>
        <article class="rounded-2xl border <?= $statusClass ?> p-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2"><p class="font-bold text-slate-950"><?= esc($item['item_label_snapshot']) ?></p><?php if((int)$item['is_required']===1): ?><span class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-bold uppercase text-red-700">Obligatorio</span><?php else: ?><span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase text-slate-500">Opcional</span><?php endif ?></div>
                    <?php if(!empty($item['item_description_snapshot'])): ?><p class="mt-2 text-sm leading-6 text-slate-600"><?= esc($item['item_description_snapshot']) ?></p><?php endif ?>
                    <?php if(!empty($item['notes'])): ?><p class="mt-3 rounded-xl bg-white/80 px-3 py-2 text-sm text-slate-600"><strong>Observación:</strong> <?= esc($item['notes']) ?></p><?php endif ?>
                    <?php if(!empty($item['answered_at'])): ?><p class="mt-2 text-xs text-slate-400">Actualizado <?= esc(date('d/m/Y H:i', strtotime($item['answered_at']))) ?> · <?= esc($item['answered_by'] ?? '') ?></p><?php endif ?>
                </div>
                <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-700 shadow-sm"><?= esc($statusLabel) ?></span>
            </div>

            <?php if($isChecklistEditable): ?>
            <form method="post" action="<?= route_to('work_orders.checklist.answer',$order['id'],$item['id']) ?>" data-processing-message="Actualizando checklist operativo…" class="mt-4 border-t border-slate-200/80 pt-4">
                <?= csrf_field() ?>
                <div class="grid gap-3 md:grid-cols-[180px_minmax(0,1fr)_auto] md:items-end">
                    <label><span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Resultado</span><select name="response" required data-native="true" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5"><option value="">Seleccionar</option><option value="yes" <?= $response==='yes'?'selected':'' ?>>Cumple</option><option value="no" <?= $response==='no'?'selected':'' ?>>No cumple</option><?php if((int)$item['is_required']!==1): ?><option value="na" <?= $response==='na'?'selected':'' ?>>No aplica</option><?php endif ?></select></label>
                    <label><span class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">Observación</span><input type="text" name="notes" value="<?= esc($item['notes'] ?? '') ?>" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5" placeholder="Obligatoria si no cumple"></label>
                    <button class="rounded-xl bg-slate-950 px-4 py-2.5 font-bold text-white hover:bg-slate-800">Guardar</button>
                </div>
            </form>
            <?php endif ?>
        </article>
        <?php endforeach ?>
        <?php if(empty($checklist['items'])): ?><div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">No hay verificaciones configuradas para esta Orden de Trabajo.</div><?php endif ?>
    </div>
</section>
