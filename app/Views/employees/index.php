<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
  <div><p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Resource Engine</p><h2 class="mt-2 text-3xl font-bold text-slate-950">Personal operativo</h2><p class="mt-2 text-sm text-slate-500">Capacidades, certificaciones y disponibilidad para Coordinación.</p></div>
  <a href="<?= route_to('employees.create') ?>" class="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950">Nuevo colaborador</a>
</div>
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
<table data-trace-table="true" data-export-title="Personal operativo" class="w-full">
<thead><tr><th>Código</th><th>Colaborador</th><th>Estado laboral</th><th>Disponibilidad</th><th>Contacto</th><th></th></tr></thead>
<tbody><?php foreach($employees as $employee): ?><tr>
<td class="font-bold text-cyan-700"><?= esc($employee['employee_code']) ?></td>
<td class="font-semibold"><?= esc($employee['name']) ?></td>
<td><?= esc(ucfirst(str_replace('_',' ',$employee['employment_status']))) ?></td>
<td><?= esc(ucfirst(str_replace('_',' ',$employee['availability_status']))) ?></td>
<td><?= esc($employee['email'] ?: $employee['phone'] ?: '—') ?></td>
<td><a class="font-semibold text-cyan-700" href="<?= route_to('employees.edit',$employee['id']) ?>">Configurar ↗</a></td>
</tr><?php endforeach ?></tbody>
</table></div>
<?= $this->endSection() ?>
