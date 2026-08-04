<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'ERP TraceOPX') ?> | ERP TraceOPX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <style>
        .choices { margin-bottom: 0; }
        .choices__inner { min-height: 48px; border-radius: .75rem; border-color: rgb(203 213 225); background: #fff; padding: .55rem .75rem; }
        .is-focused .choices__inner, .is-open .choices__inner { border-color: rgb(6 182 212); }
        .choices__list--dropdown, .choices__list[aria-expanded] { z-index: 50; border-radius: .75rem; }
        .choices__input { border-radius: .5rem; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
<?php $uri = uri_string(); ?>
<div class="min-h-screen lg:flex">
    <aside class="w-full bg-slate-950 px-6 py-6 text-white lg:min-h-screen lg:w-72">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-400">Megaload</p>
            <h1 class="mt-2 text-2xl font-bold">ERP TraceOPX</h1>
            <p class="mt-2 text-sm text-slate-400">Trazabilidad operativa integral</p>
        </div>
        <nav class="space-y-2">
            <a href="<?= route_to('dashboard') ?>" class="block rounded-lg px-4 py-3 font-semibold <?= $uri === 'dashboard' ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-slate-900' ?>">Dashboard</a>
            <a href="<?= route_to('commercial_requests.index') ?>" class="block rounded-lg px-4 py-3 font-semibold <?= str_starts_with($uri, 'commercial-requests') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-slate-900' ?>">Solicitudes comerciales</a>
            <a href="<?= route_to('customers.index') ?>" class="block rounded-lg px-4 py-3 font-semibold <?= str_starts_with($uri, 'customers') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-slate-900' ?>">Clientes</a>
            <span class="block rounded-lg px-4 py-3 text-slate-500">Cotizaciones</span>
            <span class="block rounded-lg px-4 py-3 text-slate-500">Órdenes de trabajo</span>
            <span class="block rounded-lg px-4 py-3 text-slate-500">Facturación</span>
        </nav>
    </aside>
    <main class="flex-1">
        <header class="border-b border-slate-200 bg-white px-6 py-5">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
                <div><p class="text-sm text-slate-500">Panel administrativo</p><h2 class="text-xl font-bold"><?= esc($title ?? 'ERP TraceOPX') ?></h2></div>
                <div class="flex items-center gap-3">
                    <div class="hidden text-right sm:block"><p class="text-sm font-semibold text-slate-900"><?= esc((string) session('auth_user_name')) ?></p><p class="text-xs text-slate-500"><?= esc((string) session('auth_user_email')) ?></p></div>
                    <form method="post" action="<?= route_to('logout') ?>"><?= csrf_field() ?><button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Salir</button></form>
                </div>
            </div>
        </header>
        <section class="mx-auto max-w-7xl p-6"><?= $this->renderSection('content') ?></section>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
window.traceOpxChoices = new Map();
window.initTraceOpxSelect = function (select) {
    if (!select || select.dataset.native === 'true' || window.traceOpxChoices.has(select)) return null;
    const instance = new Choices(select, {
        searchEnabled: true,
        shouldSort: false,
        itemSelectText: '',
        allowHTML: false,
        searchPlaceholderValue: 'Buscar por código o descripción',
        noResultsText: 'Sin resultados',
        noChoicesText: 'Sin opciones disponibles',
        placeholder: true,
        placeholderValue: select.dataset.placeholder || 'Seleccionar'
    });
    window.traceOpxChoices.set(select, instance);
    return instance;
};
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('select:not([data-native="true"])').forEach(window.initTraceOpxSelect);
});
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
