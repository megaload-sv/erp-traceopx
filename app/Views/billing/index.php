<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php if(session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if(session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>
<div class="mb-6"><p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Billing Engine</p><h2 class="mt-2 text-3xl font-bold text-slate-950">Facturación</h2><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Órdenes cerradas listas para iniciar el proceso fiscal. La preparación conserva la condición comercial y construye el plan financiero antes de emitir un DTE.</p></div>
<section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
<table data-trace-table="true" data-export-title="Facturación TraceOPX" class="w-full">
<thead><tr><th>Expediente</th><th>Cliente</th><th>OT</th><th>Cotización</th><th>Total</th><th>Estado</th><th>Acción</th></tr></thead>
<tbody>
<?php foreach($cases as $row): ?><tr>
<td><a href="<?= route_to('service_cases.show',$row['id']) ?>" class="font-bold text-violet-700"><?= esc($row['code']) ?> ↗</a></td>
<td><?= esc($row['business_name']) ?></td><td><?= esc($row['work_order_code']) ?></td><td><?= esc($row['quotation_code']) ?></td><td>$<?= number_format((float)$row['quotation_total'],2) ?></td>
<td><?= $row['billing_case_id'] ? '<span class="rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-bold text-cyan-800">Preparada</span>' : '<span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">Pendiente</span>' ?></td>
<td><?php if($row['billing_case_id']): ?><a href="<?= route_to('billing.show',$row['billing_case_id']) ?>" class="font-bold text-cyan-700">Abrir →</a><?php else: ?><button type="button" onclick="document.getElementById('billing-modal-<?= (int)$row['id'] ?>').classList.remove('hidden')" class="font-bold text-cyan-700">Preparar →</button><?php endif ?></td>
</tr><?php endforeach ?>
</tbody></table>
</section>
<?php foreach($cases as $row): if($row['billing_case_id']) continue; ?>
<div id="billing-modal-<?= (int)$row['id'] ?>" class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4 backdrop-blur-sm"><div class="mx-auto mt-20 max-w-xl rounded-3xl bg-white p-6 shadow-2xl"><div class="flex items-start justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Preparar facturación</p><h3 class="mt-2 text-xl font-bold"><?= esc($row['code']) ?></h3></div><button type="button" onclick="document.getElementById('billing-modal-<?= (int)$row['id'] ?>').classList.add('hidden')" class="text-2xl text-slate-400">×</button></div><form method="post" action="<?= route_to('billing.prepare',$row['id']) ?>" data-processing-message="Preparando expediente para facturación…" class="mt-5 space-y-4"><?= csrf_field() ?><label><span class="mb-2 block text-sm font-semibold">Documento fiscal objetivo *</span><select name="document_type" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><option value="">Seleccionar</option><?php foreach($documentTypes as $code=>$label): ?><option value="<?= esc($code) ?>"><?= esc($label) ?></option><?php endforeach ?></select></label><label><span class="mb-2 block text-sm font-semibold">Notas de preparación</span><textarea name="notes" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3" placeholder="Indicaciones para facturación, referencia del cliente, orden de compra, etc."></textarea></label><div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">La condición de pago y el total se heredarán automáticamente desde la cotización aceptada.</div><div class="flex justify-end"><button class="rounded-xl bg-cyan-500 px-5 py-3 font-bold text-slate-950">Crear preparación →</button></div></form></div></div>
<?php endforeach ?>
<?= $this->endSection() ?>
