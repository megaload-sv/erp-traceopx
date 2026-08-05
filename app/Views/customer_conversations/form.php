<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<div class="mx-auto max-w-4xl">
    <a href="<?= route_to('customer_conversations.index') ?>" class="text-sm font-semibold text-cyan-700">← Volver a atención comercial</a>
    <h3 class="mt-4 text-3xl font-bold">Nueva atención comercial</h3>
    <p class="mt-2 text-slate-600">Registra el primer contacto, asigna responsable e inicia el SLA de respuesta.</p>

    <?php if (session('error')): ?><div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>
    <?php if (session('errors')): ?><div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><?php foreach (session('errors') as $error): ?><p><?= esc($error) ?></p><?php endforeach ?></div><?php endif ?>

    <form method="post" action="<?= route_to('customer_conversations.store') ?>" class="mt-6 space-y-6">
        <?= csrf_field() ?>
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Origen</p>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label><span class="mb-2 block text-sm font-semibold">Canal principal *</span><select name="primary_channel" required><option value="whatsapp">WhatsApp</option><option value="email">Correo electrónico</option><option value="manual">Ingreso manual</option><option value="phone">Llamada telefónica</option><option value="visit">Visita comercial</option></select></label>
                <label><span class="mb-2 block text-sm font-semibold">Política SLA *</span><select name="sla_policy_id" required><option value="">Seleccionar</option><?php foreach ($policies as $policy): ?><option value="<?= esc($policy['id']) ?>"><?= esc($policy['name']) ?></option><?php endforeach ?></select></label>
                <label><span class="mb-2 block text-sm font-semibold">Fecha y hora de inicio</span><input type="datetime-local" name="started_at" value="<?= esc(old('started_at', date('Y-m-d\TH:i'))) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold">Prioridad</span><select name="priority"><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option><option value="low">Baja</option></select></label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-violet-600">Relación</p>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <label><span class="mb-2 block text-sm font-semibold">Cliente o prospecto</span><select name="customer_id"><option value="">Sin asociar todavía</option><?php foreach ($customers as $customer): ?><option value="<?= esc($customer['id']) ?>"><?= esc($customer['code'] . ' - ' . $customer['business_name']) ?></option><?php endforeach ?></select></label>
                <label><span class="mb-2 block text-sm font-semibold">Contacto</span><select name="contact_id"><option value="">Sin seleccionar</option><?php foreach ($contacts as $contact): ?><option value="<?= esc($contact['id']) ?>"><?= esc($contact['name'] . ($contact['email'] ? ' - ' . $contact['email'] : '')) ?></option><?php endforeach ?></select></label>
                <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold">Responsable *</span><select name="assigned_user_id" required><option value="">Seleccionar colaborador</option><?php foreach ($users as $user): ?><option value="<?= esc($user['id']) ?>"><?= esc($user['name'] . ' - ' . $user['email']) ?></option><?php endforeach ?></select></label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.2em] text-emerald-600">Necesidad</p>
            <div class="mt-5 space-y-5">
                <label><span class="mb-2 block text-sm font-semibold">Asunto *</span><input required name="subject" value="<?= esc(old('subject')) ?>" class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold">Mensaje inicial *</span><textarea required name="initial_message" rows="5" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('initial_message')) ?></textarea></label>
                <label><span class="mb-2 block text-sm font-semibold">Resumen inicial</span><textarea name="summary" rows="3" class="w-full rounded-xl border border-slate-300 px-4 py-3"><?= esc(old('summary')) ?></textarea></label>
            </div>
        </section>
        <div class="flex justify-end gap-3"><a href="<?= route_to('customer_conversations.index') ?>" class="rounded-xl border border-slate-300 px-5 py-3 font-semibold">Cancelar</a><button class="rounded-xl bg-slate-950 px-6 py-3 font-semibold text-white">Iniciar atención</button></div>
    </form>
</div>
<?= $this->endSection() ?>
