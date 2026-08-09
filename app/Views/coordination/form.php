<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>
<div class="mb-6"><p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Plan Operativo</p><h2 class="mt-2 text-3xl font-bold text-slate-950">Preparar coordinación</h2><p class="mt-2 text-sm text-slate-500"><?= esc($case['code']) ?> · <?= esc($case['business_name']) ?> · <?= esc($case['quotation_subject'] ?: 'Servicio aceptado') ?></p></div>
<form id="coordination-form" method="post" action="<?= route_to('coordination.store',$case['id']) ?>" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
<?= csrf_field() ?>
<div class="space-y-6">
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Programación</p><div class="mt-5 grid gap-5 md:grid-cols-2">
<label><span class="mb-2 block text-sm font-semibold text-slate-700">Fecha solicitada</span><input type="datetime-local" name="requested_start_at" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
<label><span class="mb-2 block text-sm font-semibold text-slate-700">Fecha programada</span><input type="datetime-local" name="scheduled_start_at" required class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
<label><span class="mb-2 block text-sm font-semibold text-slate-700">Fin estimado</span><input type="datetime-local" name="estimated_end_at" required class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
<label><span class="mb-2 block text-sm font-semibold text-slate-700">Prioridad</span><select name="priority"><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select></label>
<label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Lugar de ejecución</span><input name="location" required maxlength="255" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Dirección, proyecto o sitio de trabajo"></label>
<label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Referencia del lugar</span><input name="location_reference" maxlength="255" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Indicaciones de acceso o punto de encuentro"></label>
<label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Alcance operativo</span><textarea name="scope_notes" required rows="4" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Qué debe ejecutarse y consideraciones importantes"></textarea></label>
</div></section>
<section id="equipment-section" class="rounded-2xl border border-cyan-200 bg-white p-6 shadow-sm"><div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Maquinaria y equipo</p><h3 class="mt-2 text-xl font-bold text-slate-950">Recursos físicos previstos <span class="text-red-500">*</span></h3><p class="mt-2 text-sm text-slate-500">Selecciona al menos un equipo. Su configuración determinará automáticamente los perfiles humanos que deberá cubrir la misión.</p></div><span id="equipment-status" class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Selección requerida</span></div>
<div class="mt-5 grid gap-3 md:grid-cols-2"><?php foreach($equipment as $item): ?><label class="equipment-card flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-cyan-300"><input type="checkbox" name="equipment_ids[]" value="<?= esc((string)$item['id']) ?>" class="equipment-checkbox mt-1"><span><span class="block font-bold text-slate-900"><?= esc($item['code']) ?> · <?= esc($item['name']) ?></span><span class="mt-1 block text-xs text-slate-500"><?= esc(str_replace('_',' ',$item['maintenance_status'])) ?></span></span></label><?php endforeach ?><?php if($equipment===[]): ?><div class="md:col-span-2 rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="font-bold text-amber-900">No hay maquinaria disponible.</p><p class="mt-1 text-sm text-amber-700">No es posible crear una coordinación operativa hasta contar con al menos un equipo disponible y habilitado para planificación.</p></div><?php endif ?></div>
<p id="equipment-error" class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">Selecciona al menos una maquinaria o equipo para continuar.</p></section>
</div>
<aside class="space-y-6 xl:sticky xl:top-6 xl:self-start"><section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Próxima acción</p><h3 class="mt-3 text-xl font-bold">Crear plan operativo</h3><p class="mt-3 text-sm leading-6 text-slate-300">La coordinación debe quedar programada y contener al menos una maquinaria. Después, TraceOPX derivará los perfiles humanos requeridos.</p><button id="create-plan-button" type="submit" class="mt-5 w-full rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300 disabled:cursor-not-allowed disabled:bg-slate-700 disabled:text-slate-400" <?= $equipment===[]?'disabled':'' ?>>Crear plan operativo</button></section><a href="<?= route_to('service_cases.show',$case['id']) ?>" class="block rounded-xl border border-slate-300 bg-white px-5 py-3 text-center font-semibold text-slate-700">Volver al expediente</a></aside>
</form>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const form=document.getElementById('coordination-form');
    const boxes=[...document.querySelectorAll('.equipment-checkbox')];
    const error=document.getElementById('equipment-error');
    const status=document.getElementById('equipment-status');
    const section=document.getElementById('equipment-section');
    const sync=()=>{
        const count=boxes.filter(box=>box.checked).length;
        if(count>0){
            error?.classList.add('hidden');
            status.textContent=count===1?'1 equipo seleccionado':`${count} equipos seleccionados`;
            status.className='rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800';
            section.classList.remove('border-red-300');
            section.classList.add('border-cyan-200');
        }else{
            status.textContent='Selección requerida';
            status.className='rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800';
        }
    };
    boxes.forEach(box=>box.addEventListener('change',sync));
    form?.addEventListener('submit',(event)=>{
        if(boxes.length===0 || !boxes.some(box=>box.checked)){
            event.preventDefault();
            error?.classList.remove('hidden');
            section.classList.remove('border-cyan-200');
            section.classList.add('border-red-300');
            section.scrollIntoView({behavior:'smooth',block:'center'});
        }
    });
    sync();
});
</script>
<?= $this->endSection() ?>