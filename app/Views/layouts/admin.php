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
        .choices{margin-bottom:0}.choices__inner{min-height:48px;border-radius:.75rem;border-color:#cbd5e1;background:#fff;padding:.55rem .75rem}.is-focused .choices__inner,.is-open .choices__inner{border-color:#06b6d4}.choices__list--dropdown,.choices__list[aria-expanded]{z-index:50;border-radius:.75rem}
        .dt-container{color:#334155}.dt-container .dt-layout-row{margin:0;padding:1rem 1.25rem;gap:.75rem;align-items:center}.dt-container .dt-layout-row:first-child,.dt-container .dt-layout-row:nth-child(2){border-bottom:1px solid #e2e8f0}.dt-container .dt-layout-row:last-child{border-top:1px solid #e2e8f0}.dt-container .dt-search input,.dt-container .dt-length select{min-height:40px;border:1px solid #cbd5e1!important;border-radius:.75rem!important;background:#fff!important;padding:.5rem .75rem!important}.dt-container .dt-buttons{display:flex;flex-wrap:wrap;gap:.5rem}.dt-container button.dt-button,.dt-container a.dt-button{margin:0!important;border:1px solid #cbd5e1!important;border-radius:.7rem!important;background:#fff!important;color:#334155!important;font-size:.75rem!important;font-weight:700!important;padding:.6rem .85rem!important}table.dataTable thead th{color:#475569;font-size:.75rem;text-transform:uppercase;letter-spacing:.04em}
        @media(max-width:767px){.dt-container .dt-layout-row{display:flex;flex-direction:column;align-items:stretch}.dt-container .dt-search input{width:100%;margin-left:0}}
    </style>
</head>
<body class="bg-slate-100 text-slate-900">
<?php $uri=uri_string(); ?>
<div class="min-h-screen lg:flex">
<aside class="w-full bg-slate-950 px-6 py-6 text-white lg:min-h-screen lg:w-72">
    <div class="mb-8"><p class="text-xs font-semibold uppercase tracking-[.3em] text-cyan-400">Megaload</p><h1 class="mt-2 text-2xl font-bold">ERP TraceOPX</h1><p class="mt-2 text-sm text-slate-400">Trazabilidad operativa integral</p></div>
    <nav class="space-y-2">
        <?php $links=[['dashboard','Dashboard','dashboard'],['customer_conversations.index','Atención comercial','attention'],['commercial_requests.index','Solicitudes comerciales','commercial-requests'],['customers.index','Clientes','customers'],['quotations.index','Cotizaciones','quotations'],['service_cases.index','Expedientes de servicio','service-cases']]; ?>
        <?php foreach($links as [$route,$label,$prefix]): ?><a href="<?= route_to($route) ?>" class="block rounded-lg px-4 py-3 font-semibold <?= ($uri===$prefix||str_starts_with($uri,$prefix.'/'))?'bg-cyan-500 text-slate-950':'text-slate-300 hover:bg-slate-900' ?>"><?= esc($label) ?></a><?php endforeach ?>
        <span class="block rounded-lg px-4 py-3 text-slate-500">Coordinación y recursos</span><span class="block rounded-lg px-4 py-3 text-slate-500">Órdenes de trabajo</span><span class="block rounded-lg px-4 py-3 text-slate-500">Facturación y cobros</span>
    </nav>
</aside>
<main class="flex-1"><header class="border-b border-slate-200 bg-white px-6 py-5"><div class="mx-auto flex max-w-7xl items-center justify-between gap-4"><div><p class="text-sm text-slate-500">Panel administrativo</p><h2 class="text-xl font-bold"><?= esc($title??'ERP TraceOPX') ?></h2></div><div class="flex items-center gap-3"><div class="hidden text-right sm:block"><p class="text-sm font-semibold"><?= esc((string)session('auth_user_name')) ?></p><p class="text-xs text-slate-500"><?= esc((string)session('auth_user_email')) ?></p></div><form method="post" action="<?= route_to('logout') ?>"><?= csrf_field() ?><button class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Salir</button></form></div></div></header><section class="mx-auto max-w-7xl p-6"><?= $this->renderSection('content') ?></section></main>
</div>
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/pdfmake.min.js"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.12/vfs_fonts.js"></script><script src="https://cdn.datatables.net/2.3.8/js/dataTables.min.js"></script><script src="https://cdn.datatables.net/buttons/3.2.6/js/dataTables.buttons.min.js"></script><script src="https://cdn.datatables.net/buttons/3.2.6/js/buttons.html5.min.js"></script><script src="https://cdn.datatables.net/buttons/3.2.6/js/buttons.print.min.js"></script><script src="https://cdn.datatables.net/buttons/3.2.6/js/buttons.colVis.min.js"></script>
<script>
window.traceOpxChoices=new Map();window.initTraceOpxSelect=function(s){if(!s||s.dataset.native==='true'||window.traceOpxChoices.has(s))return null;const i=new Choices(s,{searchEnabled:true,shouldSort:false,itemSelectText:'',allowHTML:false,searchPlaceholderValue:'Buscar por código o descripción',noResultsText:'Sin resultados',noChoicesText:'Sin opciones disponibles',placeholder:true,placeholderValue:s.dataset.placeholder||'Seleccionar'});window.traceOpxChoices.set(s,i);return i};
window.traceOpxTables=new Map();window.initTraceOpxTable=function(t){if(!t||window.traceOpxTables.has(t))return null;const title=t.dataset.exportTitle||document.title;const i=new DataTable(t,{stateSave:true,pageLength:25,lengthMenu:[10,25,50,100],order:[],layout:{topStart:'pageLength',topEnd:{search:{placeholder:'Buscar en el listado'}},top2Start:{buttons:[{extend:'colvis',text:'Columnas'},{extend:'csvHtml5',text:'CSV',title},{extend:'excelHtml5',text:'Excel',title},{extend:'pdfHtml5',text:'PDF',title,orientation:'landscape',pageSize:'A4'},{extend:'print',text:'Imprimir',title}]},bottomStart:'info',bottomEnd:'paging'},language:{search:'Buscar:',lengthMenu:'Mostrar _MENU_ registros',info:'Mostrando _START_ a _END_ de _TOTAL_ registros',infoEmpty:'Sin registros disponibles',infoFiltered:'(filtrado de _MAX_ registros)',zeroRecords:'No se encontraron coincidencias',emptyTable:'No hay información disponible',paginate:{first:'Primero',previous:'Anterior',next:'Siguiente',last:'Último'}}});window.traceOpxTables.set(t,i);return i};

// Preserve the CSRF field for the quotation item form even when its UI controls are disabled.
document.addEventListener('submit',(event)=>{
    const form=event.target;
    if(!(form instanceof HTMLFormElement)||form.id!=='quotation-item-form')return;
    const token=form.querySelector('input[type="hidden"]:not([name="merge_duplicate"])');
    if(!token||!token.name)return;
    let mirror=document.getElementById('quotation-item-csrf-mirror');
    if(!mirror){
        mirror=document.createElement('input');
        mirror.type='hidden';
        mirror.id='quotation-item-csrf-mirror';
        mirror.setAttribute('form','quotation-item-form');
        document.body.appendChild(mirror);
    }
    mirror.name=token.name;
    mirror.value=token.value;
},true);

document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('select:not([data-native="true"])').forEach(window.initTraceOpxSelect);document.querySelectorAll('table[data-trace-table="true"]').forEach(window.initTraceOpxTable)});
</script>
<?= $this->renderSection('scripts') ?>
</body></html>
