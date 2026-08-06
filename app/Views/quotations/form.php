<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>
<?php
$requestCustomerId = (int) ($commercialRequest['customer_id'] ?? 0);
$requestContactId = (int) ($commercialRequest['contact_id'] ?? 0);
$oldCustomer = (int) (old('customer_id') ?: $requestCustomerId);
$oldContact = (int) (old('contact_id') ?: $requestContactId);
$oldUser = (int) (old('assigned_user_id') ?: $defaultUserId);
$oldPaymentTerm = (int) old('payment_term_id');
$defaultSubject = (string) ($commercialRequest['subject'] ?? '');
?>
<?php if (session('error')): ?><div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800"><?= esc(session('error')) ?></div><?php endif ?>

<?= view('components/workspace/page-header', [
    'eyebrow' => 'Quotation Engine',
    'title' => 'Nueva cotización',
    'description' => $commercialRequest ? 'La propuesta se inicia desde la solicitud ' . $commercialRequest['code'] . ' y conserva su trazabilidad.' : 'Crea una cotización directa para un cliente existente.',
    'actionUrl' => $commercialRequest ? route_to('commercial_requests.show', $commercialRequest['id']) : route_to('quotations.index'),
    'actionLabel' => $commercialRequest ? 'Volver a la solicitud' : 'Volver al listado',
]) ?>

<?php if ($commercialRequest): ?>
<div class="mb-6 rounded-2xl border border-cyan-200 bg-cyan-50 p-5">
    <p class="text-xs font-bold uppercase tracking-[.18em] text-cyan-700">Solicitud de origen</p>
    <p class="mt-2 font-bold text-cyan-950"><?= esc($commercialRequest['code']) ?> · <?= esc($commercialRequest['subject']) ?></p>
    <p class="mt-1 text-sm text-cyan-800">Cliente, contacto, agente y asunto fueron precargados desde la solicitud comercial.</p>
</div>
<?php endif ?>

<form method="post" action="<?= route_to('quotations.store') ?>" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
    <?= csrf_field() ?>
    <input type="hidden" name="commercial_request_id" value="<?= esc((string) old('commercial_request_id', $commercialRequestId)) ?>">

    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">1. Cliente y destinatario</p>
            <h3 class="mt-2 text-xl font-bold text-slate-950">¿Para quién se prepara la propuesta?</h3>
            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Cliente *</span>
                    <select id="customer_id" name="customer_id" data-placeholder="Buscar cliente" required>
                        <option value="">Seleccione un cliente</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= esc((string) $customer['id']) ?>" <?= $oldCustomer === (int) $customer['id'] ? 'selected' : '' ?>><?= esc($customer['business_name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Contacto principal</span>
                    <select id="contact_id" name="contact_id" data-placeholder="Seleccione un contacto">
                        <option value="">Sin contacto seleccionado</option>
                        <?php foreach ($contacts as $contact): ?>
                            <option value="<?= esc((string) $contact['id']) ?>" data-customer-id="<?= esc((string) $contact['customer_id']) ?>" data-primary="<?= esc((string) $contact['is_primary']) ?>" <?= $oldContact === (int) $contact['id'] ? 'selected' : '' ?>><?= esc($contact['name']) ?><?= ! empty($contact['position']) ? ' — ' . esc($contact['position']) : '' ?><?= (int) $contact['is_primary'] === 1 ? ' · Principal' : '' ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">2. Datos de la propuesta</p>
            <h3 class="mt-2 text-xl font-bold text-slate-950">Identidad comercial del borrador</h3>
            <div class="mt-6 grid gap-5 lg:grid-cols-2">
                <label class="block lg:col-span-2">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Asunto *</span>
                    <input type="text" name="subject" value="<?= esc((string) old('subject', $defaultSubject)) ?>" maxlength="190" required class="w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100">
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Agente comercial *</span>
                    <select name="assigned_user_id" data-placeholder="Seleccione un agente" required>
                        <option value="">Seleccione un agente</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= esc((string) $user['id']) ?>" <?= $oldUser === (int) $user['id'] ? 'selected' : '' ?>><?= esc($user['name']) ?><?= ! empty($user['email']) ? ' — ' . esc($user['email']) : '' ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
                <label class="block">
                    <span class="mb-2 block text-sm font-semibold text-slate-700">Forma de pago</span>
                    <select name="payment_term_id" data-placeholder="Buscar forma de pago">
                        <option value="">Pendiente de definir</option>
                        <?php foreach ($paymentTerms as $term): ?>
                            <option value="<?= esc((string) $term['id']) ?>" <?= $oldPaymentTerm === (int) $term['id'] ? 'selected' : '' ?>><?= esc($term['code']) ?> — <?= esc($term['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </label>
                <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Fecha de cotización *</span><input type="date" name="quotation_date" value="<?= esc((string) old('quotation_date', date('Y-m-d'))) ?>" required class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
                <label class="block"><span class="mb-2 block text-sm font-semibold text-slate-700">Vigencia en días *</span><input type="number" name="validity_days" value="<?= esc((string) old('validity_days', 30)) ?>" min="1" max="365" required class="w-full rounded-xl border border-slate-300 px-4 py-3"></label>
            </div>
        </section>
    </div>

    <aside class="xl:sticky xl:top-6 xl:self-start">
        <div class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl">
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Resumen inicial</p>
            <h3 class="mt-3 text-xl font-bold">Borrador de cotización</h3>
            <div class="mt-6 space-y-4 border-y border-slate-800 py-5 text-sm">
                <div class="flex justify-between"><span class="text-slate-400">Origen</span><span class="font-semibold"><?= $commercialRequest ? 'Solicitud comercial' : 'Directa' ?></span></div>
                <div class="flex justify-between"><span class="text-slate-400">Estado</span><span class="font-semibold text-amber-300">Borrador</span></div>
                <div class="flex justify-between"><span class="text-slate-400">Total</span><span class="text-xl font-bold">$0.00</span></div>
            </div>
            <button type="submit" class="mt-6 w-full rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Guardar borrador</button>
        </div>
    </aside>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const customerSelect = document.getElementById('customer_id');
    const contactSelect = document.getElementById('contact_id');
    if (!customerSelect || !contactSelect) return;
    const allContacts = Array.from(contactSelect.options).slice(1).map(option => ({ value: option.value, label: option.textContent, customerId: option.dataset.customerId, primary: option.dataset.primary === '1', selected: option.selected }));
    const rebuildContacts = () => {
        const customerId = customerSelect.value;
        const currentValue = contactSelect.value;
        const matching = allContacts.filter(contact => contact.customerId === customerId);
        const preferred = matching.find(contact => contact.value === currentValue) || matching.find(contact => contact.selected) || matching.find(contact => contact.primary);
        const instance = window.traceOpxChoices.get(contactSelect);
        if (instance) { instance.destroy(); window.traceOpxChoices.delete(contactSelect); }
        contactSelect.innerHTML = '<option value="">Sin contacto seleccionado</option>';
        matching.forEach(contact => { const option = document.createElement('option'); option.value = contact.value; option.textContent = contact.label; option.selected = preferred ? preferred.value === contact.value : false; contactSelect.appendChild(option); });
        window.initTraceOpxSelect(contactSelect);
    };
    customerSelect.addEventListener('change', rebuildContacts);
    rebuildContacts();
});
</script>
<?= $this->endSection() ?>
