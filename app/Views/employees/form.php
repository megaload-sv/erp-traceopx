<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$isEdit = ! empty($employee);
$employmentStatus = (string) old('employment_status', $employee['employment_status'] ?? 'active');
$availabilityStatus = (string) old('availability_status', $employee['availability_status'] ?? 'available');
?>
<div class="mb-6 flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Resource Engine</p><h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($title) ?></h2><p class="mt-2 text-sm text-slate-500">Define capacidades reales del colaborador sin confundirlas con su cuenta de acceso al ERP.</p></div><a href="<?= route_to('employees.index') ?>" class="rounded-xl border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-700">Volver</a></div>
<?php if(session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if(session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>
<form method="post" action="<?= $isEdit?route_to('employees.update',$employee['id']):route_to('employees.store') ?>" class="space-y-6"><?= csrf_field() ?>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="grid gap-5 md:grid-cols-2">
<label><span class="mb-2 block text-sm font-semibold">Código *</span><input name="employee_code" required value="<?= esc(old('employee_code',$employee['employee_code']??'')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
<label><span class="mb-2 block text-sm font-semibold">Nombre *</span><input name="name" required value="<?= esc(old('name',$employee['name']??'')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
<label><span class="mb-2 block text-sm font-semibold">Correo</span><input type="email" name="email" value="<?= esc(old('email',$employee['email']??'')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
<label><span class="mb-2 block text-sm font-semibold">Teléfono</span><input name="phone" value="<?= esc(old('phone',$employee['phone']??'')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
<label><span class="mb-2 block text-sm font-semibold">Estado laboral</span><select name="employment_status"><option value="active" <?= $employmentStatus==='active'?'selected':'' ?>>Activo</option><option value="vacation" <?= $employmentStatus==='vacation'?'selected':'' ?>>Vacaciones</option><option value="leave" <?= $employmentStatus==='leave'?'selected':'' ?>>Permiso / incapacidad</option><option value="inactive" <?= $employmentStatus==='inactive'?'selected':'' ?>>Inactivo</option></select></label>
<label><span class="mb-2 block text-sm font-semibold">Disponibilidad</span><select name="availability_status"><option value="available" <?= $availabilityStatus==='available'?'selected':'' ?>>Disponible</option><option value="reserved" <?= $availabilityStatus==='reserved'?'selected':'' ?>>Reservado</option><option value="assigned" <?= $availabilityStatus==='assigned'?'selected':'' ?>>Asignado</option><option value="working" <?= $availabilityStatus==='working'?'selected':'' ?>>En operación</option><option value="unavailable" <?= $availabilityStatus==='unavailable'?'selected':'' ?>>No disponible</option></select></label>
<label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold">Observaciones</span><textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('notes',$employee['notes']??'')) ?></textarea></label>
</div></section>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Habilidades y certificaciones</p><h3 class="mt-2 text-xl font-bold">¿Qué puede desempeñar este colaborador?</h3><p class="mt-2 text-sm text-slate-500">Marca únicamente las capacidades reales. El nivel puede dejarse sin asignar hasta que exista una evaluación formal.</p></div>
<div class="mt-5 space-y-3">
<?php foreach($skills as $skill):
    $a=$assignedSkills[(int)$skill['id']]??null;
    $level=(string)($a['proficiency_level']??'');
    $checked=$a!==null;
?>
<article data-skill-card class="rounded-2xl border border-slate-200 p-4 transition-all duration-200 <?= $checked?'bg-white':'bg-slate-50/60' ?>">
    <div class="grid gap-4 lg:grid-cols-[32px_1.2fr_.8fr_1fr_1fr] lg:items-end">
        <label class="pb-3"><input data-skill-toggle type="checkbox" name="skill_id[]" value="<?= esc($skill['id']) ?>" <?= $checked?'checked':'' ?>></label>
        <div><p class="font-bold text-slate-950"><?= esc($skill['name']) ?></p><p class="text-xs text-slate-500"><?= $skill['requires_certification']?'Certificación requerida':'Capacidad operativa' ?></p></div>
        <label data-skill-field class="transition-opacity duration-200 <?= $checked?'':'opacity-45' ?>"><span class="mb-1 block text-xs font-semibold">Nivel</span><select data-skill-level name="proficiency_level[<?= esc($skill['id']) ?>]" <?= $checked?'':'disabled' ?>><option value="" <?= $level===''?'selected':'' ?>>Sin asignar nivel</option><option value="trainee" <?= $level==='trainee'?'selected':'' ?>>En formación</option><option value="qualified" <?= $level==='qualified'?'selected':'' ?>>Calificado</option><option value="senior" <?= $level==='senior'?'selected':'' ?>>Senior</option></select></label>
        <label data-skill-field class="transition-opacity duration-200 <?= $checked?'':'opacity-45' ?>"><span class="mb-1 block text-xs font-semibold">Certificación</span><input name="certification_number[<?= esc($skill['id']) ?>]" value="<?= esc($a['certification_number']??'') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100" <?= $checked?'':'disabled' ?>></label>
        <label data-skill-field class="transition-opacity duration-200 <?= $checked?'':'opacity-45' ?>"><span class="mb-1 block text-xs font-semibold">Válida hasta</span><input type="date" name="valid_until[<?= esc($skill['id']) ?>]" value="<?= esc($a['valid_until']??'') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100" <?= $checked?'':'disabled' ?>></label>
    </div>
</article>
<?php endforeach ?>
</div></section>
<div class="flex justify-end"><button class="rounded-xl bg-cyan-400 px-6 py-3 font-bold text-slate-950">Guardar colaborador</button></div></form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-skill-card]').forEach((card) => {
        const toggle = card.querySelector('[data-skill-toggle]');
        const fields = card.querySelectorAll('[data-skill-field]');
        const controls = card.querySelectorAll('[data-skill-field] select, [data-skill-field] input');
        const levelSelect = card.querySelector('[data-skill-level]');

        const sync = () => {
            const active = toggle.checked;
            card.classList.toggle('bg-slate-50/60', !active);
            card.classList.toggle('bg-white', active);
            fields.forEach((field) => field.classList.toggle('opacity-45', !active));
            controls.forEach((control) => { control.disabled = !active; });

            if (levelSelect && window.traceOpxChoices) {
                const choices = window.traceOpxChoices.get(levelSelect);
                if (choices) {
                    if (active) {
                        choices.enable();
                    } else {
                        choices.disable();
                    }
                }
            }
        };

        toggle.addEventListener('change', sync);
        sync();
    });
});
</script>
<?= $this->endSection() ?>
