<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$operationalLabels = ['available'=>'Disponible','reserved'=>'Reservado','assigned'=>'Asignado','in_operation'=>'En operación','returning'=>'Retornando','out_of_service'=>'Fuera de servicio'];
$maintenanceLabels = ['ok'=>'Mantenimiento al día','preventive_due'=>'Preventivo próximo','preventive'=>'En preventivo','corrective'=>'En correctivo','out_of_service'=>'Fuera de servicio'];
?>
<div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div><p class="text-xs font-bold uppercase tracking-[.2em] text-cyan-600">Resource Engine · 9.1</p><h2 class="mt-2 text-3xl font-bold text-slate-950">Maquinaria y equipo</h2><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Catálogo maestro de recursos físicos, su disponibilidad y los perfiles humanos necesarios para operarlos.</p></div>
    <a href="<?= route_to('equipment.create') ?>" class="rounded-xl bg-cyan-500 px-5 py-3 text-center font-bold text-slate-950 shadow-sm hover:bg-cyan-400">+ Registrar equipo</a>
</div>
<div class="mb-6 grid gap-4 md:grid-cols-3">
    <?= view('components/workspace/metric-card', ['label'=>'Total de equipos','value'=>(string)$metrics['total'],'description'=>'Recursos físicos registrados','tone'=>'slate']) ?>
    <?= view('components/workspace/metric-card', ['label'=>'Disponibles','value'=>(string)$metrics['available'],'description'=>'Listos para planificación','tone'=>'cyan']) ?>
    <?= view('components/workspace/metric-card', ['label'=>'Atención mantenimiento','value'=>(string)$metrics['maintenance'],'description'=>'Próximos, preventivos o correctivos','tone'=>'amber']) ?>
</div>
<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table data-trace-table="true" data-export-title="Maquinaria y equipo" class="display w-full">
        <thead><tr><th>Código</th><th>Equipo</th><th>Categoría</th><th>Estado operativo</th><th>Mantenimiento</th><th>Medidor</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($equipment as $row): ?>
            <tr>
                <td class="font-bold text-cyan-700"><?= esc($row['code']) ?></td>
                <td><div class="font-semibold text-slate-950"><?= esc($row['name']) ?></div><div class="text-xs text-slate-500"><?= esc(trim(($row['brand'] ?? '') . ' ' . ($row['model'] ?? ''))) ?></div></td>
                <td><?= esc($row['category_name'] ?: 'Sin categoría') ?></td>
                <td><?= esc($operationalLabels[$row['operational_status']] ?? $row['operational_status']) ?></td>
                <td><?= esc($maintenanceLabels[$row['maintenance_status']] ?? $row['maintenance_status']) ?></td>
                <td><?= $row['current_meter'] !== null ? esc(number_format((float)$row['current_meter'],2)) . ' ' . esc($row['meter_type'] ?? '') : '—' ?></td>
                <td class="text-right"><a href="<?= route_to('equipment.edit', $row['id']) ?>" class="font-semibold text-cyan-700">Abrir ↗</a></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</section>
<?= $this->endSection() ?>
