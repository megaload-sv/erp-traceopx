<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="mb-8">
    <h3 class="text-3xl font-bold">Resumen operativo</h3>
    <p class="mt-2 text-slate-600">Este es el primer incremento funcional del ERP TraceOPX. Los indicadores se conectarán con datos reales en las siguientes entregas.</p>
</div>

<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($metrics as $metric): ?>
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-500"><?= esc($metric['label']) ?></p>
            <p class="mt-4 text-4xl font-bold text-slate-950"><?= esc((string) $metric['value']) ?></p>
        </article>
    <?php endforeach ?>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="text-lg font-bold">Flujo principal</h4>
        <p class="mt-2 text-sm text-slate-600">Solicitud → Cotización → Orden de trabajo → Facturación → Cobro.</p>
    </article>
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h4 class="text-lg font-bold">Estado del incremento</h4>
        <p class="mt-2 text-sm text-slate-600">Layout administrativo, navegación inicial y dashboard disponibles para validación local.</p>
    </article>
</div>
<?= $this->endSection() ?>
