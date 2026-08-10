<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<style>
.payment-entry-panel .choices__inner{background:#fff!important;border:1px solid #67e8f9!important;color:#0f172a!important;box-shadow:0 0 0 1px rgba(34,211,238,.08)}
.payment-entry-panel .choices__placeholder{color:#64748b!important;opacity:1!important}
.payment-entry-panel .choices__list--single .choices__item{color:#0f172a!important}
.payment-entry-panel .choices__input{background:#fff!important;color:#0f172a!important}
.payment-entry-panel .choices__list--dropdown,.payment-entry-panel .choices__list[aria-expanded]{background:#fff!important;border-color:#67e8f9!important;color:#0f172a!important}
.payment-entry-panel .choices__list--dropdown .choices__item,.payment-entry-panel .choices__list[aria-expanded] .choices__item{color:#334155!important}
.payment-entry-panel .choices__list--dropdown .choices__item--selectable.is-highlighted,.payment-entry-panel .choices__list[aria-expanded] .choices__item--selectable.is-highlighted{background:#cffafe!important;color:#083344!important}
.payment-entry-panel .choices.is-focused .choices__inner,.payment-entry-panel .choices.is-open .choices__inner{border-color:#22d3ee!important;box-shadow:0 0 0 4px rgba(34,211,238,.16)}
</style>

<?php if(session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if(session('error')): ?><div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= esc(session('error')) ?></div><?php endif ?>
<?php
$paymentReady=(bool)($payment_readiness['allowed']??false);
$paymentIssues=$payment_readiness['issues']??[];
$operationCondition=$payment_context['operation_condition']??null;
$creditTerm=$payment_context['credit_term']??null;
$creditPeriod=$payment_context['credit_period']??null;
$plannedMethods=$payment_context['planned_methods']??[];
?>

<div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Payment Ledger</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($billingCase['code']) ?> · Pagos</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">La condición comercial define cuánto debe cobrarse; los movimientos del ledger definen cómo se recibió. Cada movimiento conserva además su evidencia financiera.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= route_to('billing.show',(int)$billingCase['id']) ?>" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700">← Volver al DTE</a>
        <a href="<?= route_to('service_cases.show',(int)$billingCase['service_case_id']) ?>" class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-bold text-violet-700">Ver expediente ↗</a>
    </div>
</div>

<section class="mb-6 overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm">
    <div class="border-b border-violet-100 bg-violet-50/70 p-5">
        <p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-700">Condiciones heredadas de la cotización</p>
        <h3 class="mt-2 text-lg font-bold text-slate-950">Cómo se pactó la operación</h3>
        <p class="mt-1 text-sm text-slate-600">Esta información es de consulta. El ledger registra lo realmente recibido, incluso cuando se combina más de una forma de pago.</p>
    </div>
    <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">Plan comercial</p><p class="mt-2 font-bold text-slate-950"><?= esc($billingCase['payment_term_name_snapshot'] ?: 'No definido') ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">CAT-016 · Operación</p><p class="mt-2 font-bold <?= $operationCondition?'text-slate-950':'text-amber-700' ?>"><?= $operationCondition?esc($operationCondition['code'].' · '.$operationCondition['name']):'No definido en cotización' ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">CAT-018 · Plazo</p><p class="mt-2 font-bold text-slate-950"><?php if($creditTerm): ?><?= esc(($creditPeriod?$creditPeriod.' ':'').$creditTerm['name']) ?><?php else: ?>No aplica / no definido<?php endif ?></p></div>
        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs uppercase tracking-wide text-slate-500">CAT-017 · Formas previstas</p><?php if($plannedMethods): ?><div class="mt-2 flex flex-wrap gap-1.5"><?php foreach($plannedMethods as $method): ?><span class="rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-bold text-cyan-800"><?= esc(($method['codigo']??$method['code']??'').' · '.($method['descripcion']??$method['name']??'')) ?></span><?php endforeach ?></div><?php else: ?><p class="mt-2 font-bold text-amber-700">No definidas en cotización</p><?php endif ?></div>
    </div>
</section>

<?php if(!$paymentReady): ?>
<section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Pagos todavía no habilitados</p>
    <h3 class="mt-2 text-lg font-bold text-amber-950">Complete la estructura financiera antes de registrar movimientos</h3>
    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-amber-900"><?php foreach($paymentIssues as $issue): ?><li><?= esc($issue) ?></li><?php endforeach ?></ul>
</section>
<?php endif ?>

<section class="grid gap-4 md:grid-cols-3">
    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs uppercase tracking-wide text-slate-500">Total por cobrar</p><p class="mt-2 text-3xl font-bold">$<?= number_format((float)$target_amount,2) ?></p></article>
    <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"><p class="text-xs uppercase tracking-wide text-emerald-700">Pagado confirmado</p><p class="mt-2 text-3xl font-bold text-emerald-950">$<?= number_format((float)$paid_amount,2) ?></p></article>
    <article class="rounded-2xl <?= $balance_amount>0?'border-amber-200 bg-amber-50':'border-emerald-200 bg-emerald-50' ?> p-5"><p class="text-xs uppercase tracking-wide <?= $balance_amount>0?'text-amber-700':'text-emerald-700' ?>">Saldo pendiente</p><p class="mt-2 text-3xl font-bold">$<?= number_format((float)$balance_amount,2) ?></p><p class="mt-2 text-xs"><?= $balance_amount>0?'Aún existen fondos por recibir.':'Cuenta completamente pagada.' ?></p></article>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
<div class="space-y-6">
<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-6"><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Real vs esperado</p><h3 class="mt-2 text-xl font-bold">Calendario financiero</h3><p class="mt-2 text-sm text-slate-500">Cada cuota puede recibir múltiples pagos con diferentes métodos hasta completar su monto esperado.</p></div>
    <div class="divide-y divide-slate-100">
        <?php foreach($schedule as $row): ?>
        <article class="p-5"><div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><div class="flex flex-wrap items-center gap-2"><p class="font-bold"><?= esc($row['concept']) ?></p><span class="rounded-full px-2.5 py-1 text-xs font-bold <?= $row['status']==='paid'?'bg-emerald-100 text-emerald-800':($row['status']==='partial'?'bg-amber-100 text-amber-800':'bg-slate-100 text-slate-700') ?>"><?= esc(ucfirst((string)$row['status'])) ?></span></div><p class="mt-1 text-sm text-slate-500"><?= number_format((float)$row['percentage'],2) ?>% · <?= esc($row['installment_type']) ?></p></div><p class="text-xl font-bold">$<?= number_format((float)$row['amount'],2) ?></p></div><div class="mt-4 grid gap-3 sm:grid-cols-2"><div class="rounded-xl bg-emerald-50 p-3"><p class="text-xs text-emerald-700">Aplicado</p><p class="mt-1 font-bold text-emerald-950">$<?= number_format((float)($row['applied_amount']??0),2) ?></p></div><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Pendiente</p><p class="mt-1 font-bold">$<?= number_format((float)($row['remaining_amount']??$row['amount']),2) ?></p></div></div></article>
        <?php endforeach ?>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-6"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Trazabilidad financiera</p><h3 class="mt-2 text-xl font-bold">Pagos registrados</h3></div>
    <?php if($payments===[]): ?><div class="p-8 text-center text-sm text-slate-500">Todavía no existen pagos registrados.</div><?php else: ?>
    <div class="divide-y divide-slate-100">
        <?php foreach($payments as $payment): ?>
        <article class="p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><div class="flex flex-wrap items-center gap-2"><p class="font-bold"><?= esc($payment['payment_method']) ?></p><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Confirmado</span><?php if($payment['mh_payment_code']): ?><span class="rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-bold text-cyan-800">CAT-017 <?= esc($payment['mh_payment_code']) ?></span><?php endif ?></div><p class="mt-2 text-sm text-slate-500"><?= esc(date('d/m/Y H:i',strtotime((string)$payment['payment_date']))) ?><?= $payment['schedule_concept']?' · '.esc($payment['schedule_concept']):' · Pago general' ?></p><?php if($payment['reference']): ?><p class="mt-1 text-sm text-slate-600">Referencia: <span class="font-mono font-semibold"><?= esc($payment['reference']) ?></span></p><?php endif ?></div><p class="text-2xl font-bold text-emerald-700">$<?= number_format((float)$payment['amount'],2) ?></p></div>
            <?php if(!empty($payment['evidence'])): ?><div class="mt-4 flex flex-wrap gap-2"><?php foreach($payment['evidence'] as $evidence): ?><a href="<?= route_to('billing.payments.index',(int)$billingCase['id']) ?>?download_evidence=<?= (int)$evidence['id'] ?>" class="rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-2 text-xs font-bold text-cyan-800">Comprobante · <?= esc($evidence['original_name']) ?> ↓</a><?php endforeach ?></div><?php endif ?>
        </article>
        <?php endforeach ?>
    </div><?php endif ?>
</section>
</div>

<aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
<section class="payment-entry-panel rounded-2xl bg-slate-950 p-6 text-white shadow-xl">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Registrar movimiento</p>
    <h3 class="mt-2 text-xl font-bold">Confirmar pago recibido</h3>
    <p class="mt-2 text-sm leading-6 text-slate-300">Registre un movimiento por cada forma realmente utilizada. En transferencia o depósito bancario, el comprobante es obligatorio.</p>
    <?php if(!$paymentReady): ?>
        <div class="mt-5 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-200">El registro está bloqueado hasta completar los requisitos financieros.</div>
    <?php elseif($balance_amount<=0): ?>
        <div class="mt-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-200">El documento se encuentra completamente pagado. No se permiten pagos adicionales.</div>
    <?php else: ?>
    <form method="post" enctype="multipart/form-data" action="<?= route_to('billing.payments.store',(int)$billingCase['id']) ?>" class="mt-5 space-y-4" data-processing-message="Registrando y confirmando pago…" data-payment-confirm="true">
        <?= csrf_field() ?>
        <label class="block text-sm font-semibold text-slate-100">Aplicar a cuota<select name="payment_schedule_id" class="mt-2 w-full" data-placeholder="Opcional · seleccionar cuota"><option value="">Pago general / varias cuotas</option><?php foreach($schedule as $row): ?><option value="<?= (int)$row['id'] ?>" <?= (int)old('payment_schedule_id')===(int)$row['id']?'selected':'' ?>>#<?= (int)$row['sequence'] ?> · <?= esc($row['concept']) ?> · pendiente $<?= number_format((float)($row['remaining_amount']??$row['amount']),2) ?></option><?php endforeach ?></select></label>
        <label class="block text-sm font-semibold text-slate-100">CAT-017 · Forma de pago utilizada *<select id="mh_payment_code" name="mh_payment_code" required class="mt-2 w-full" data-placeholder="Seleccionar forma de pago"><option value="">Seleccione</option><?php foreach($payment_methods_catalog as $method): ?><option value="<?= esc($method['code']) ?>" <?= old('mh_payment_code')===(string)$method['code']?'selected':'' ?>><?= esc($method['code'].' · '.$method['name']) ?></option><?php endforeach ?></select></label>
        <label class="block text-sm font-semibold text-slate-100">Comprobante de pago <span id="receipt-required-label" class="hidden text-amber-300">*</span><input id="payment_receipt" name="payment_receipt" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="mt-2 block w-full rounded-xl border border-slate-400 bg-white px-3 py-3 text-sm font-normal text-slate-900 file:mr-3 file:rounded-lg file:border-0 file:bg-cyan-100 file:px-3 file:py-2 file:font-bold file:text-cyan-900"><span class="mt-1 block text-xs font-normal text-slate-400">JPG, PNG, WEBP o PDF · máximo 10 MB. Obligatorio para CAT-017 05.</span></label>
        <label class="block text-sm font-semibold text-slate-100">Monto recibido *<input name="amount" type="number" min="0.01" max="<?= esc(number_format((float)$balance_amount,2,'.','')) ?>" step="0.01" required value="<?= esc(old('amount')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-normal text-slate-950 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/20"></label>
        <label class="block text-sm font-semibold text-slate-100">Fecha del pago *<input name="payment_date" type="datetime-local" required value="<?= esc(old('payment_date',date('Y-m-d\TH:i'))) ?>" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-normal text-slate-950 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/20"></label>
        <label class="block text-sm font-semibold text-slate-100">Referencia<input name="reference" maxlength="50" value="<?= esc(old('reference')) ?>" placeholder="No. transferencia / cheque / voucher" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-normal text-slate-950 placeholder:text-slate-400 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/20"></label>
        <label class="block text-sm font-semibold text-slate-100">Número de pago electrónico<input name="electronic_payment_number" maxlength="100" value="<?= esc(old('electronic_payment_number')) ?>" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-normal text-slate-950 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/20"></label>
        <label class="block text-sm font-semibold text-slate-100">Notas<textarea name="notes" rows="2" maxlength="500" class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 font-normal text-slate-950 outline-none focus:border-cyan-400 focus:ring-4 focus:ring-cyan-400/20"><?= esc(old('notes')) ?></textarea></label>
        <button class="w-full rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">Registrar y confirmar pago</button>
    </form>
    <?php endif ?>
</section>
<section class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Sincronización del Expediente</p><p class="mt-3 text-sm leading-6 text-cyan-950">Cuando el saldo llega a cero, TraceOPX completa automáticamente el hito <strong>Cobro completado</strong>. El hito <strong>Facturación completada</strong> requiere que el documento haya sido emitido formalmente; una preparación DTE todavía en borrador no se considera factura emitida.</p></section>
</aside>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const method=document.getElementById('mh_payment_code');
    const receipt=document.getElementById('payment_receipt');
    const label=document.getElementById('receipt-required-label');
    if(!method||!receipt)return;
    const sync=()=>{const required=method.value==='05';receipt.required=required;label?.classList.toggle('hidden',!required)};
    method.addEventListener('change',sync);sync();
});
</script>
<?= $this->endSection() ?>
