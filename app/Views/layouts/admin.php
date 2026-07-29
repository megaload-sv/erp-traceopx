<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'ERP TraceOPX') ?> | ERP TraceOPX</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-900">
<div class="min-h-screen lg:flex">
    <aside class="w-full bg-slate-950 px-6 py-6 text-white lg:min-h-screen lg:w-72">
        <div class="mb-8">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-400">Megaload</p>
            <h1 class="mt-2 text-2xl font-bold">ERP TraceOPX</h1>
            <p class="mt-2 text-sm text-slate-400">Trazabilidad operativa integral</p>
        </div>

        <nav class="space-y-2">
            <a href="<?= route_to('dashboard') ?>" class="block rounded-lg bg-cyan-500 px-4 py-3 font-semibold text-slate-950">Dashboard</a>
            <span class="block rounded-lg px-4 py-3 text-slate-400">Clientes</span>
            <span class="block rounded-lg px-4 py-3 text-slate-400">Cotizaciones</span>
            <span class="block rounded-lg px-4 py-3 text-slate-400">Órdenes de trabajo</span>
            <span class="block rounded-lg px-4 py-3 text-slate-400">Facturación</span>
        </nav>
    </aside>

    <main class="flex-1">
        <header class="border-b border-slate-200 bg-white px-6 py-5">
            <div class="mx-auto flex max-w-7xl items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">Panel administrativo</p>
                    <h2 class="text-xl font-bold"><?= esc($title ?? 'ERP TraceOPX') ?></h2>
                </div>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-700">Entorno local</span>
            </div>
        </header>

        <section class="mx-auto max-w-7xl p-6">
            <?= $this->renderSection('content') ?>
        </section>
    </main>
</div>
</body>
</html>
