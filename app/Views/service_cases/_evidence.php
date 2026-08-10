<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-700">Evidencias</p><h3 class="mt-2 text-xl font-bold text-slate-950">Repositorio documental del servicio</h3><p class="mt-1 text-sm text-slate-600">Las evidencias cargadas desde la operación permanecen vinculadas a este expediente.</p></div>
        <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-800"><?= count($evidence ?? []) ?> archivo(s)</span>
    </div>
    <div class="mt-5 grid gap-3 md:grid-cols-2">
        <?php foreach(($evidence ?? []) as $item): $stages=['before'=>'Antes','during'=>'Durante','after'=>'Después']; ?>
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="truncate font-bold text-slate-900"><?= esc($item['original_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($stages[$item['stage']] ?? ucfirst($item['stage'])) ?> · <?= esc(date('d/m/Y H:i',strtotime($item['occurred_at']))) ?></p><?php if(!empty($item['description'])): ?><p class="mt-2 text-sm text-slate-600"><?= esc($item['description']) ?></p><?php endif ?><a href="<?= route_to('work_orders.evidence.download',$item['work_order_id'],$item['id']) ?>" class="mt-3 inline-block text-sm font-bold text-violet-700">Abrir evidencia →</a></article>
        <?php endforeach ?>
        <?php if(($evidence ?? [])===[]): ?><div class="md:col-span-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">Todavía no existen evidencias documentales para este servicio.</div><?php endif ?>
    </div>
</section>

<?php if(!empty($checklist)):
    $summary = $checklist['summary'] ?? [];
    $ready = !empty($summary['ready']);
    $failed = (int)($summary['required_failed'] ?? 0);
?>
<section class="rounded-2xl border <?= $ready?'border-emerald-200 bg-emerald-50/40':($failed>0?'border-red-200 bg-red-50/40':'border-amber-200 bg-amber-50/40') ?> p-6 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-700">Control operativo</p>
            <h3 class="mt-2 text-xl font-bold text-slate-950">Checklist de la misión</h3>
            <p class="mt-1 text-sm text-slate-600"><?= esc($checklist['template_name_snapshot']) ?> · <?= (int)($summary['answered'] ?? 0) ?> de <?= (int)($summary['total'] ?? 0) ?> verificaciones registradas.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right"><p class="text-xs uppercase text-slate-500">Avance</p><p class="text-2xl font-bold text-slate-950"><?= (int)($summary['percent'] ?? 0) ?>%</p></div>
            <?php if(!empty($workOrder)): ?><a href="<?= route_to('work_orders.show',$workOrder['id']) ?>#operational-checklist" class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white">Abrir checklist →</a><?php endif ?>
        </div>
    </div>
    <div class="mt-5 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl bg-white/80 p-4"><p class="text-xs uppercase text-slate-500">Obligatorios pendientes</p><p class="mt-2 text-xl font-bold text-slate-950"><?= (int)($summary['required_pending'] ?? 0) ?></p></div>
        <div class="rounded-xl bg-white/80 p-4"><p class="text-xs uppercase text-slate-500">No conformes</p><p class="mt-2 text-xl font-bold <?= $failed>0?'text-red-700':'text-slate-950' ?>"><?= $failed ?></p></div>
        <div class="rounded-xl bg-white/80 p-4"><p class="text-xs uppercase text-slate-500">Estado</p><p class="mt-2 font-bold <?= $ready?'text-emerald-700':($failed>0?'text-red-700':'text-amber-700') ?>"><?= $ready?'Conforme':($failed>0?'Requiere atención':'Pendiente') ?></p></div>
    </div>
</section>
<?php endif ?>
