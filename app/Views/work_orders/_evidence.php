<?php
$isInProgress = isset($order['status']) && in_array($order['status'], ['in_progress', 'working'], true);
?>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Evidencias de la misión</p>
            <h3 class="mt-2 text-xl font-bold text-slate-950">Repositorio operativo</h3>
            <p class="mt-2 text-sm text-slate-500">Fotografías y documentos quedan protegidos, vinculados a la OT y disponibles desde el Expediente.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700"><?= count($evidence ?? []) ?> evidencia(s)</span>
    </div>

    <?php if($isInProgress): ?>
    <form method="post" enctype="multipart/form-data" action="<?= route_to('work_orders.evidence.store',$order['id']) ?>" class="mt-6 rounded-2xl border border-violet-200 bg-violet-50/40 p-5">
        <?= csrf_field() ?>
        <div class="grid gap-4 md:grid-cols-2">
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Momento *</span><select name="evidence_stage" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><option value="before">Antes</option><option value="during" selected>Durante</option><option value="after">Después</option></select></label>
            <label><span class="mb-2 block text-sm font-semibold text-slate-700">Vincular a avance <span class="font-normal text-slate-400">(opcional)</span></span><select name="mission_log_id" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><option value="">Sin vínculo específico</option><?php foreach($missionLogs as $log): ?><option value="<?= esc((string)$log['id']) ?>"><?= esc(date('d/m H:i',strtotime($log['occurred_at']))) ?> · <?= esc($log['title']) ?></option><?php endforeach ?></select></label>
            <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Archivo *</span><input type="file" name="evidence_file" required accept="image/jpeg,image/png,image/webp,application/pdf,.docx,.xlsx" class="w-full rounded-xl border border-dashed border-slate-300 bg-white px-4 py-4 text-sm"><span class="mt-2 block text-xs text-slate-500">JPG, PNG, WEBP, PDF, DOCX o XLSX · máximo 15 MB.</span></label>
            <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Descripción</span><textarea name="evidence_description" rows="3" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3" placeholder="Ej. Estado inicial del equipo al llegar al sitio..."></textarea></label>
        </div>
        <div class="mt-5 flex justify-end"><button class="rounded-xl bg-violet-600 px-5 py-3 font-bold text-white hover:bg-violet-500">Agregar evidencia</button></div>
    </form>
    <?php endif ?>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <?php foreach(($evidence ?? []) as $item):
            $stageLabels=['before'=>'Antes','during'=>'Durante','after'=>'Después'];
            $isPhoto=$item['evidence_type']==='photo';
        ?>
        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0"><p class="truncate font-bold text-slate-900"><?= esc($item['original_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= $isPhoto ? 'Fotografía' : 'Documento' ?> · <?= esc($stageLabels[$item['stage']] ?? ucfirst($item['stage'])) ?> · <?= number_format(((int)$item['size_bytes'])/1024, 0) ?> KB</p></div>
                <span class="rounded-full <?= $isPhoto?'bg-cyan-100 text-cyan-800':'bg-violet-100 text-violet-800' ?> px-2.5 py-1 text-[11px] font-bold uppercase"><?= $isPhoto?'Foto':'Archivo' ?></span>
            </div>
            <?php if(!empty($item['description'])): ?><p class="mt-3 text-sm leading-6 text-slate-600"><?= esc($item['description']) ?></p><?php endif ?>
            <div class="mt-4 flex items-center justify-between gap-3 border-t border-slate-200 pt-3"><time class="text-xs text-slate-400"><?= esc(date('d/m/Y H:i',strtotime($item['occurred_at']))) ?></time><a href="<?= route_to('work_orders.evidence.download',$order['id'],$item['id']) ?>" class="text-sm font-bold text-violet-700">Abrir / descargar →</a></div>
        </article>
        <?php endforeach ?>
        <?php if(($evidence ?? [])===[]): ?><div class="md:col-span-2 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500">Todavía no hay evidencias documentales asociadas a esta misión.</div><?php endif ?>
    </div>
</section>

<?= $this->include('work_orders/_checklist') ?>
