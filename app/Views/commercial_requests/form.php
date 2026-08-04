<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>
<?php if (session('errors')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?php foreach (session('errors') as $error): ?><p><?= esc($error) ?></p><?php endforeach ?></div><?php endif ?>

<div class="mb-7"><a href="<?= route_to('commercial_requests.index') ?>" class="text-sm font-semibold text-cyan-700">← Volver a solicitudes</a><h3 class="mt-3 text-3xl font-bold">Nueva solicitud comercial</h3><p class="mt-2 text-slate-600">Toda entrada debe quedar asociada a un responsable, una política de SLA y una próxima acción.</p></div>

<form method="post" action="<?= route_to('commercial_requests.store') ?>" class="space-y-6">
<?= csrf_field() ?>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
<div class="grid gap-5 md:grid-cols-2">
<label><span class="mb-2 block text-sm font-semibold">Canal *</span><select name="channel" required><option value="whatsapp">WhatsApp</option><option value="email">Correo electrónico</option><option value="manual">Ingreso manual</option></select></label>
<label><span class="mb-2 block text-sm font-semibold">Política SLA *</span><select name="sla_policy_id" required><option value="">Seleccionar</option><?php foreach ($policies as $policy): ?><option value="<?= esc($policy['id']) ?>"><?= esc($policy['code'].' - '.$policy['name']) ?></option><?php endforeach ?></select></label>
<label><span class="mb-2 block text-sm font-semibold">Cliente</span><select name="customer_id"><option value="">Prospecto sin asociar</option><?php foreach ($customers as $customer): ?><option value="<?= esc($customer['id']) ?>"><?= esc($customer['code'].' - '.$customer['business_name']) ?></option><?php endforeach ?></select></label>
<label><span class="mb-2 block text-sm font-semibold">Contacto</span><select name="contact_id"><option value="">Sin contacto</option><?php foreach ($contacts as $contact): ?><option value="<?= esc($contact['id']) ?>"><?= esc($contact['name'].' - '.($contact['email'] ?: 'sin correo')) ?></option><?php endforeach ?></select></label>
<label><span class="mb-2 block text-sm font-semibold">Responsable</span><select name="assigned_user_id"><option value="">Sin asignar</option><?php foreach ($users as $user): ?><option value="<?= esc($user['id']) ?>"><?= esc($user['name'].' - '.$user['email']) ?></option><?php endforeach ?></select></label>
<label><span class="mb-2 block text-sm font-semibold">Prioridad</span><select name="priority"><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option><option value="low">Baja</option></select></label>
<label><span class="mb-2 block text-sm font-semibold">Fecha y hora de recepción</span><input type="datetime-local" name="received_at" value="<?= esc(old('received_at', date('Y-m-d\TH:i'))) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
<label><span class="mb-2 block text-sm font-semibold">Origen específico</span><input name="source_detail" value="<?= esc(old('source_detail')) ?>" placeholder="Número, buzón, llamada, visita..." class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
<label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold">Asunto *</span><input required name="subject" value="<?= esc(old('subject')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
<label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold">Necesidad del cliente *</span><textarea required name="description" rows="5" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('description')) ?></textarea></label>
<label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold">Servicios solicitados</span><textarea name="requested_services" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('requested_services')) ?></textarea></label>
</div>
</section>
<div class="flex justify-end gap-3"><a href="<?= route_to('commercial_requests.index') ?>" class="rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold">Cancelar</a><button class="rounded-xl bg-slate-950 px-6 py-3 font-semibold text-white">Registrar y activar SLA</button></div>
</form>
<?= $this->endSection() ?>
