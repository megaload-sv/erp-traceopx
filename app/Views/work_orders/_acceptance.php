<?php
use App\Services\WorkOrderAcceptanceService;

$acceptanceWorkspace = (new WorkOrderAcceptanceService())->workspace((int) $order['id']);
$acceptanceRecords = $acceptanceWorkspace['records'];
$successfulAcceptance = $acceptanceWorkspace['successful'];
$customerContacts = $acceptanceWorkspace['contacts'];
$acceptanceResults = $acceptanceWorkspace['results'];
$isFinishedForAcceptance = in_array($order['status'], ['finished','completed'], true);
$resultClasses = [
    'accepted' => 'bg-emerald-100 text-emerald-800',
    'accepted_with_observations' => 'bg-cyan-100 text-cyan-800',
    'rejected' => 'bg-red-100 text-red-800',
];
?>
<section id="customer-acceptance" class="rounded-2xl border <?= $successfulAcceptance ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white' ?> p-6 shadow-sm">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Acceptance Engine</p>
            <h3 class="mt-2 text-xl font-bold text-slate-950">Aceptación del cliente</h3>
            <p class="mt-2 text-sm leading-6 text-slate-500">Documenta quién recibió el servicio y su conformidad. Una aceptación válida completa el hito del Expediente y deja la Orden de Trabajo lista para su cierre formal.</p>
        </div>
        <?php if($successfulAcceptance): ?><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">Aceptación registrada</span><?php elseif($isFinishedForAcceptance): ?><span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-800">Pendiente de cliente</span><?php else: ?><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">No disponible</span><?php endif ?>
    </div>

    <?php if($successfulAcceptance): ?>
        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-xl border border-emerald-200 bg-white p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Resultado</p><p class="mt-2 font-bold text-slate-950"><?= esc($acceptanceResults[$successfulAcceptance['result']] ?? $successfulAcceptance['result']) ?></p></article>
            <article class="rounded-xl border border-emerald-200 bg-white p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Recibido por</p><p class="mt-2 font-bold text-slate-950"><?= esc($successfulAcceptance['receiver_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc($successfulAcceptance['receiver_position'] ?: 'Cargo no indicado') ?></p></article>
            <article class="rounded-xl border border-emerald-200 bg-white p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Fecha de aceptación</p><p class="mt-2 font-bold text-slate-950"><?= esc(date('d/m/Y H:i', strtotime($successfulAcceptance['accepted_at']))) ?></p></article>
            <article class="rounded-xl border border-emerald-200 bg-white p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Firma / constancia</p><?php if(!empty($successfulAcceptance['signature_stored_name'])): ?><a href="<?= route_to('work_orders.acceptance.signature',$order['id'],$successfulAcceptance['id']) ?>" class="mt-2 inline-block font-bold text-violet-700">Abrir documento →</a><?php else: ?><p class="mt-2 font-bold text-slate-500">Sin archivo</p><?php endif ?></article>
        </div>
        <?php if(!empty($successfulAcceptance['observations'])): ?><div class="mt-4 rounded-xl border border-emerald-200 bg-white p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Observaciones de recepción</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700"><?= esc($successfulAcceptance['observations']) ?></p></div><?php endif ?>
        <div class="mt-5 rounded-xl border border-violet-200 bg-violet-50 p-4 text-sm text-violet-900"><strong>Recepción confirmada.</strong> El hito de aceptación queda completado y la Orden de Trabajo está preparada para el cierre formal antes de continuar a la etapa administrativa.</div>
        <?php if($order['status'] === 'accepted'): ?>
            <div class="mt-5 flex justify-end">
                <a href="#formal-closure" class="inline-flex items-center justify-center rounded-xl bg-cyan-500 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-400">Continuar al cierre formal →</a>
            </div>
        <?php endif ?>
    <?php elseif($isFinishedForAcceptance): ?>
        <form method="post" enctype="multipart/form-data" action="<?= route_to('work_orders.acceptance.store',$order['id']) ?>" data-processing-message="Registrando aceptación del cliente…" class="mt-6 rounded-2xl border border-violet-200 bg-violet-50/30 p-5">
            <?= csrf_field() ?>
            <div class="grid gap-4 md:grid-cols-2">
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Contacto del cliente <span class="font-normal text-slate-400">(opcional)</span></span><select id="acceptance-contact" name="customer_contact_id" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><option value="">Persona no registrada / captura manual</option><?php foreach($customerContacts as $contact): ?><option value="<?= esc((string)$contact['id']) ?>" data-name="<?= esc($contact['name']) ?>" data-position="<?= esc($contact['position'] ?? '') ?>" data-email="<?= esc($contact['email'] ?? '') ?>" data-phone="<?= esc($contact['phone'] ?? '') ?>" <?= (string)old('customer_contact_id')===(string)$contact['id']?'selected':'' ?>><?= esc($contact['name']) ?><?= !empty($contact['position'])?' · '.esc($contact['position']):'' ?><?= (int)$contact['is_primary']===1?' · Principal':'' ?></option><?php endforeach ?></select></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Resultado *</span><select name="result" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"><?php foreach($acceptanceResults as $code=>$label): ?><option value="<?= esc($code) ?>" <?= (old('result') ?: 'accepted')===$code?'selected':'' ?>><?= esc($label) ?></option><?php endforeach ?></select></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Nombre de quien recibe *</span><input id="acceptance-receiver-name" type="text" name="receiver_name" required maxlength="190" value="<?= esc(old('receiver_name') ?? '') ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Cargo</span><input id="acceptance-receiver-position" type="text" name="receiver_position" maxlength="150" value="<?= esc(old('receiver_position') ?? '') ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Correo</span><input id="acceptance-receiver-email" type="email" name="receiver_email" maxlength="190" value="<?= esc(old('receiver_email') ?? '') ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Teléfono</span><input id="acceptance-receiver-phone" type="text" name="receiver_phone" maxlength="80" value="<?= esc(old('receiver_phone') ?? '') ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Fecha y hora de recepción *</span><input type="datetime-local" name="accepted_at" required value="<?= esc(old('accepted_at') ?: date('Y-m-d\TH:i')) ?>" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3"></label>
                <label><span class="mb-2 block text-sm font-semibold text-slate-700">Firma / constancia *</span><input type="file" name="signature_file" accept="image/jpeg,image/png,image/webp,application/pdf" class="w-full rounded-xl border border-dashed border-slate-300 bg-white px-4 py-3"><span class="mt-1 block text-xs text-slate-500">Obligatoria para aceptación. JPG, PNG, WEBP o PDF · máximo 10 MB.</span></label>
                <label class="md:col-span-2"><span class="mb-2 block text-sm font-semibold text-slate-700">Observaciones</span><textarea name="observations" rows="4" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3" placeholder="Obligatorias si se acepta con observaciones o si el cliente no acepta."><?= esc(old('observations') ?? '') ?></textarea></label>
            </div>
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><strong>Importante:</strong> “No aceptado” documenta el rechazo y mantiene la OT pendiente. “Aceptado” o “Aceptado con observaciones” completa el hito de aceptación y deja la OT lista para su cierre formal.</div>
            <div class="mt-5 flex justify-end"><button class="rounded-xl bg-violet-600 px-5 py-3 font-bold text-white hover:bg-violet-500">Registrar recepción del cliente →</button></div>
        </form>
    <?php else: ?>
        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Esta etapa se habilita únicamente después de finalizar el trabajo operativo.</div>
    <?php endif ?>

    <?php if($acceptanceRecords !== []): ?>
        <div class="mt-6 border-t border-slate-200 pt-5">
            <p class="text-xs font-semibold uppercase tracking-[.16em] text-slate-500">Historial de recepción</p>
            <div class="mt-4 space-y-3"><?php foreach($acceptanceRecords as $record): ?><article class="rounded-xl border border-slate-200 bg-white p-4"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="font-bold text-slate-950"><?= esc($record['receiver_name']) ?></p><p class="mt-1 text-xs text-slate-500"><?= esc(date('d/m/Y H:i',strtotime($record['accepted_at']))) ?></p></div><span class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase <?= esc($resultClasses[$record['result']] ?? 'bg-slate-100 text-slate-700') ?>"><?= esc($acceptanceResults[$record['result']] ?? $record['result']) ?></span></div><?php if(!empty($record['observations'])): ?><p class="mt-3 text-sm text-slate-600"><?= esc($record['observations']) ?></p><?php endif ?></article><?php endforeach ?></div>
        </div>
    <?php endif ?>
</section>

<?= $this->include('work_orders/_closure') ?>

<script>
document.addEventListener('DOMContentLoaded',()=>{
    const select=document.getElementById('acceptance-contact');
    if(!select)return;

    const map={
        'acceptance-receiver-name':'name',
        'acceptance-receiver-position':'position',
        'acceptance-receiver-email':'email',
        'acceptance-receiver-phone':'phone'
    };

    const clearFields=()=>{
        Object.keys(map).forEach(id=>{
            const input=document.getElementById(id);
            if(input)input.value='';
        });
    };

    const sync=()=>{
        const option=select.options[select.selectedIndex];

        if(!option || !option.value){
            clearFields();
            return;
        }

        Object.entries(map).forEach(([id,key])=>{
            const input=document.getElementById(id);
            if(input)input.value=option.dataset[key]||'';
        });
    };

    select.addEventListener('change',sync);
    if(select.value)sync();
});
</script>
