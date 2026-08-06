<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'ERP TraceOPX') ?> | ERP TraceOPX</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/3.2.6/css/buttons.dataTables.min.css">
    <style>
        .choices { margin-bottom: 0; }
        .choices__inner { min-height: 48px; border-radius: .75rem; border-color: rgb(203 213 225); background: #fff; padding: .55rem .75rem; }
        .is-focused .choices__inner, .is-open .choices__inner { border-color: rgb(6 182 212); }
        .choices__list--dropdown, .choices__list[aria-expanded] { z-index: 50; border-radius: .75rem; }
        .choices__input { border-radius: .5rem; }

        .dt-container { color: rgb(51 65 85); }
        .dt-container .dt-layout-row { margin: 0; padding: 1rem 1.25rem; gap: .75rem; align-items: center; }
        .dt-container .dt-layout-row:first-child,
        .dt-container .dt-layout-row:nth-child(2) { border-bottom: 1px solid rgb(226 232 240); }
        .dt-container .dt-layout-row:last-child { border-top: 1px solid rgb(226 232 240); }
        .dt-container .dt-search label,
        .dt-container .dt-length label,
        .dt-container .dt-info { font-size: .8125rem; color: rgb(100 116 139); }
        .dt-container .dt-search input,
        .dt-container .dt-length select {
            min-height: 40px;
            border: 1px solid rgb(203 213 225) !important;
            border-radius: .75rem !important;
            background: #fff !important;
            padding: .5rem .75rem !important;
            outline: none;
        }
        .dt-container .dt-search input:focus,
        .dt-container .dt-length select:focus { border-color: rgb(6 182 212) !important; box-shadow: 0 0 0 3px rgb(6 182 212 / .12); }
        .dt-container .dt-buttons { display: flex; flex-wrap: wrap; gap: .5rem; }
        .dt-container button.dt-button,
        .dt-container div.dt-button,
        .dt-container a.dt-button {
            margin: 0 !important;
            border: 1px solid rgb(203 213 225) !important;
            border-radius: .7rem !important;
            background: #fff !important;
            color: rgb(51 65 85) !important;
            font-size: .75rem !important;
            font-weight: 700 !important;
            padding: .6rem .85rem !important;
            box-shadow: none !important;
        }
        .dt-container button.dt-button:hover,
        .dt-container div.dt-button:hover,
        .dt-container a.dt-button:hover { border-color: rgb(6 182 212) !important; background: rgb(236 254 255) !important; color: rgb(14 116 144) !important; }
        .dt-container .dt-paging .dt-paging-button { border-radius: .65rem !important; min-width: 38px; }
        .dt-container .dt-paging .dt-paging-button.current { border-color: rgb(6 182 212) !important; background: rgb(207 250 254) !important; color: rgb(14 116 144) !important; }
        table.dataTable thead th { color: rgb(71 85 105); font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; }
        div.dt-button-collection { border-radius: 1rem !important; border-color: rgb(226 232 240) !important; box-shadow: 0 20px 45px rgb(15 23 42 / .16) !important; }
        @media (max-width: 767px) {
            .dt-container .dt-layout-row { display: flex; flex-direction: column; align-items: stretch; }
            .dt-container .dt-search input { width: 100%; margin-left: 0; }
        }
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
            <a href="<?= route_to('customer_conversations.index') ?>" class="block rounded-lg px-4 py-3 font-semibold <?= str_starts_with($uri, 'attention') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-slate-900' ?>">Atención comercial</a>
            <a href="<?= route_to('commercial_requests.index') ?>" class="block rounded-lg px-4 py-3 font-semibold <?= str_starts_with($uri, 'commercial-requests') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-slate-900' ?>">Solicitudes comerciales</a>
            <a href="<?= route_to('customers.index') ?>" class="block rounded-lg px-4 py-3 font-semibold <?= str_starts_with($uri, 'customers') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-slate-900' ?>">Clientes</a>
            <a href="<?= route_to('quotations.index') ?>" class="block rounded-lg px-4 py-3 font-semibold <?= str_starts_with($uri, 'quotations') ? 'bg-cyan-500 text-slate-950' : 'text-slate-300 hover:bg-slate-900' ?>">Cotizaciones</a>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.6/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/buttons/3.2.6/js/buttons.colVis.min.js"></script>
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

window.traceOpxTables = new Map();
window.initTraceOpxTable = function (table) {
    if (!table || window.traceOpxTables.has(table)) return null;

    const exportTitle = table.dataset.exportTitle || document.title;
    const instance = new DataTable(table, {
        stateSave: true,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [],
        layout: {
            topStart: 'pageLength',
            topEnd: { search: { placeholder: 'Buscar en el listado' } },
            top2Start: {
                buttons: [
                    { extend: 'colvis', text: 'Columnas' },
                    { extend: 'csvHtml5', text: 'CSV', title: exportTitle, exportOptions: { columns: ':visible' } },
                    { extend: 'excelHtml5', text: 'Excel', title: exportTitle, exportOptions: { columns: ':visible' } },
                    { extend: 'pdfHtml5', text: 'PDF', title: exportTitle, orientation: 'landscape', pageSize: 'A4', exportOptions: { columns: ':visible' } },
                    { extend: 'print', text: 'Imprimir', title: exportTitle, exportOptions: { columns: ':visible' } }
                ]
            },
            bottomStart: 'info',
            bottomEnd: 'paging'
        },
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Sin registros disponibles',
            infoFiltered: '(filtrado de _MAX_ registros)',
            zeroRecords: 'No se encontraron coincidencias',
            emptyTable: 'No hay información disponible',
            paginate: { first: 'Primero', previous: 'Anterior', next: 'Siguiente', last: 'Último' },
            buttons: { colvis: 'Columnas', copy: 'Copiar', copyTitle: 'Copiado', copySuccess: { _: '%d filas copiadas', 1: '1 fila copiada' } }
        }
    });

    window.traceOpxTables.set(table, instance);
    return instance;
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('select:not([data-native="true"])').forEach(window.initTraceOpxSelect);
    document.querySelectorAll('table[data-trace-table="true"]').forEach(window.initTraceOpxTable);
});
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
