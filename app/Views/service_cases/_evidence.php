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
