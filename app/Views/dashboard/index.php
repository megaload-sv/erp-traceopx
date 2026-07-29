<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-600">Visión ejecutiva</p>
        <h3 class="mt-2 text-3xl font-bold text-slate-950">Resumen operativo</h3>
        <p class="mt-2 max-w-3xl text-slate-600">Datos simulados para validar la dirección visual y funcional del ERP antes de conectar los módulos reales.</p>
    </div>
    <div class="flex flex-wrap gap-3">
        <button class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white">Nueva cotización</button>
        <button class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-700">Nueva orden</button>
    </div>
</div>

<div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($metrics as $metric): ?>
        <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-medium text-slate-500"><?= esc($metric['label']) ?></p>
            <p class="mt-4 text-4xl font-bold text-slate-950"><?= esc((string) $metric['value']) ?></p>
            <p class="mt-3 text-sm text-slate-500"><?= esc($metric['detail']) ?></p>
        </article>
    <?php endforeach ?>
</div>

<section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-6">
        <h4 class="text-lg font-bold text-slate-950">Flujo operativo</h4>
        <p class="mt-1 text-sm text-slate-500">Cantidad de procesos actualmente ubicados en cada etapa.</p>
    </div>
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
        <?php foreach ($workflow as $step): ?>
            <article class="rounded-xl bg-slate-50 p-4 text-center">
                <p class="text-2xl font-bold text-slate-950"><?= esc((string) $step['value']) ?></p>
                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500"><?= esc($step['label']) ?></p>
            </article>
        <?php endforeach ?>
    </div>
</section>

<div class="mt-8 grid gap-6 xl:grid-cols-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">
        <div class="mb-5 flex items-center justify-between">
            <div>
                <h4 class="text-lg font-bold text-slate-950">Últimas cotizaciones</h4>
                <p class="mt-1 text-sm text-slate-500">Vista preliminar del seguimiento comercial.</p>
            </div>
            <span class="text-sm font-semibold text-cyan-700">Ver todas</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-3">Código</th>
                        <th class="px-3 py-3">Cliente</th>
                        <th class="px-3 py-3">Monto</th>
                        <th class="px-3 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($latestQuotations as $quotation): ?>
                        <tr>
                            <td class="px-3 py-4 font-semibold text-slate-900"><?= esc($quotation['code']) ?></td>
                            <td class="px-3 py-4 text-slate-600"><?= esc($quotation['customer']) ?></td>
                            <td class="px-3 py-4 text-slate-600"><?= esc($quotation['amount']) ?></td>
                            <td class="px-3 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"><?= esc($quotation['status']) ?></span></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl border border-amber-200 bg-amber-50 p-6 shadow-sm">
        <h4 class="text-lg font-bold text-amber-950">Alertas operativas</h4>
        <div class="mt-5 space-y-4">
            <?php foreach ($alerts as $alert): ?>
                <article class="rounded-xl border border-amber-200 bg-white p-4">
                    <p class="font-semibold text-slate-900"><?= esc($alert['title']) ?></p>
                    <p class="mt-1 text-sm text-slate-600"><?= esc($alert['detail']) ?></p>
                </article>
            <?php endforeach ?>
        </div>
    </section>
</div>

<section class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <h4 class="text-lg font-bold text-slate-950">Actividad reciente</h4>
    <div class="mt-5 space-y-5">
        <?php foreach ($recentActivity as $activity): ?>
            <article class="flex gap-4 border-l-2 border-cyan-500 pl-4">
                <span class="w-12 shrink-0 text-sm font-bold text-cyan-700"><?= esc($activity['time']) ?></span>
                <p class="text-sm text-slate-600"><?= esc($activity['text']) ?></p>
            </article>
        <?php endforeach ?>
    </div>
</section>
<?= $this->endSection() ?>
