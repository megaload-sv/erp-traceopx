<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php if(session('success')): ?><div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800"><?= esc(session('success')) ?></div><?php endif ?>
<?php if(session('error')): ?><div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-800"><?= esc(session('error')) ?></div><?php endif ?>
<?php $paymentReady=(bool)($payment_readiness['allowed']??false); $paymentIssues=$payment_readiness['issues']??[]; ?>

<div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[.2em] text-cyan-600">Payment Ledger</p>
        <h2 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($billingCase['code']) ?> · Pagos</h2>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">La condición comercial define cuánto debe cobrarse; los movimientos del ledger definen cómo se recibió. Una misma cuota puede completarse con transferencia, cheque, tarjeta, efectivo u otros métodos en tantos movimientos como sean necesarios.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= route_to('billing.show',(int)$billingCase['id']) ?>" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700">← Volver al DTE</a>
        <a href="<?= route_to('service_cases.show',(int)$billingCase['service_case_id']) ?>" class="rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-bold text-violet-700">Ver expediente ↗</a>
    </div>
</div>

<?php if(!$paymentReady): ?>
<section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-amber-700">Pagos todavía no habilitados</p>
    <h3 class="mt-2 text-lg font-bold text-amber-950">Complete la estructura financiera antes de registrar movimientos</h3>
    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-amber-900"><?php foreach($paymentIssues as $issue): ?><li><?= esc($issue) ?></li><?php endforeach ?></ul>
    <p class="mt-3 text-xs text-amber-700">La validación fiscal del receptor no bloquea los anticipos; estas reglas son exclusivamente comerciales y financieras.</p>
</section>
<?php endif ?>

<section class="grid gap-4 md:grid-cols-3">
    <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs uppercase tracking-wide text-slate-500">Total por cobrar</p><p class="mt-2 text-3xl font-bold">$<?= number_format((float)$target_amount,2) ?></p><p class="mt-2 text-xs text-slate-500">Se utiliza el total DTE cuando está disponible.</p></article>
    <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5"><p class="text-xs uppercase tracking-wide text-emerald-700">Pagado confirmado</p><p class="mt-2 text-3xl font-bold text-emerald-950">$<?= number_format((float)$paid_amount,2) ?></p></article>
    <article class="rounded-2xl <?= $balance_amount>0?'border-amber-200 bg-amber-50':'border-emerald-200 bg-emerald-50' ?> p-5"><p class="text-xs uppercase tracking-wide <?= $balance_amount>0?'text-amber-700':'text-emerald-700' ?>">Saldo pendiente</p><p class="mt-2 text-3xl font-bold">$<?= number_format((float)$balance_amount,2) ?></p><p class="mt-2 text-xs"><?= $balance_amount>0?'Aún existen fondos por recibir.':'Cuenta completamente pagada.' ?></p></article>
</section>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
<div class="space-y-6">
<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-6"><p class="text-xs font-semibold uppercase tracking-[.18em] text-violet-600">Real vs esperado</p><h3 class="mt-2 text-xl font-bold">Calendario financiero</h3><p class="mt-2 text-sm text-slate-500">Cada cuota puede recibir múltiples pagos con diferentes métodos hasta completar su monto esperado.</p></div>
    <div class="divide-y divide-slate-100">
        <?php foreach($schedule as $row): ?>
        <article class="p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div><div class="flex flex-wrap items-center gap-2"><p class="font-bold"><?= esc($row['concept']) ?></p><span class="rounded-full px-2.5 py-1 text-xs font-bold <?= $row['status']==='paid'?'bg-emerald-100 text-emerald-800':($row['status']==='partial'?'bg-amber-100 text-amber-800':'bg-slate-100 text-slate-700') ?>"><?= esc(ucfirst((string)$row['status'])) ?></span></div><p class="mt-1 text-sm text-slate-500"><?= number_format((float)$row['percentage'],2) ?>% · <?= esc($row['installment_type']) ?></p></div>
                <p class="text-xl font-bold">$<?= number_format((float)$row['amount'],2) ?></p>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2"><div class="rounded-xl bg-emerald-50 p-3"><p class="text-xs text-emerald-700">Aplicado a esta cuota</p><p class="mt-1 font-bold text-emerald-950">$<?= number_format((float)($row['applied_amount']??0),2) ?></p></div><div class="rounded-xl bg-slate-50 p-3"><p class="text-xs text-slate-500">Pendiente de esta cuota</p><p class="mt-1 font-bold">$<?= number_format((float)($row['remaining_amount']??$row['amount']),2) ?></p></div></div>
        </article>
        <?php endforeach ?>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 p-6"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-600">Trazabilidad financiera</p><h3 class="mt-2 text-xl font-bold">Pagos registrados</h3></div>
    <?php if($payments===[]): ?><div class="p-8 text-center text-sm text-slate-500">Todavía no existen pagos registrados para esta preparación.</div><?php else: ?>
    <div class="divide-y divide-slate-100">
        <?php foreach($payments as $payment): ?>
        <article class="p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div><div class="flex flex-wrap items-center gap-2"><p class="font-bold"><?= esc($payment['payment_method']) ?></p><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">Confirmado</span><?php if($payment['mh_payment_code']): ?><span class="rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-bold text-cyan-800">MH <?= esc($payment['mh_payment_code']) ?></span><?php endif ?></div><p class="mt-2 text-sm text-slate-500"><?= esc(date('d/m/Y H:i',strtotime((string)$payment['payment_date']))) ?><?= $payment['schedule_concept']?' · '.esc($payment['schedule_concept']):' · Pago general' ?></p><?php if($payment['reference']): ?><p class="mt-1 text-sm text-slate-600">Referencia: <span class="font-mono font-semibold"><?= esc($payment['reference']) ?></span></p><?php endif ?></div>
                <p class="text-2xl font-bold text-emerald-700">$<?= number_format((float)$payment['amount'],2) ?></p>
            </div>
            <?php if($payment['electronic_payment_number']): ?><p class="mt-3 text-xs text-slate-500">Pago electrónico: <?= esc($payment['electronic_payment_number']) ?></p><?php endif ?>
        </article>
        <?php endforeach ?>
    </div><?php endif ?>
</section>
</div>

<aside class="space-y-6 xl:sticky xl:top-6 xl:self-start">
<section class="rounded-2xl bg-slate-950 p-6 text-white shadow-xl">
    <p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-400">Registrar movimiento</p>
    <h3 class="mt-2 text-xl font-bold">Confirmar pago recibido</h3>
    <p class="mt-2 text-sm leading-6 text-slate-400">Registre un movimiento por cada método utilizado. Ejemplo: $500 transferencia + $250 cheque + $250 tarjeta = $1,000 pagados.</p>
    <?php if(!$paymentReady): ?>
        <div class="mt-5 rounded-xl border border-amber-500/30 bg-amber-500/10 p-4 text-sm text-amber-200">El registro está bloqueado hasta completar los requisitos financieros indicados arriba.</div>
    <?php elseif($balance_amount<=0): ?>
        <div class="mt-5 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-200">El documento se encuentra completamente pagado. No se permiten pagos adicionales.</div>
    <?php else: ?>
    <form method="post" action="<?= route_to('billing.payments.store',(int)$billingCase['id']) ?>" class="mt-5 space-y-4" data-processing-message="Registrando y confirmando pago…" data-payment-confirm="true">
        <?= csrf_field() ?>
        <label class="block text-sm font-semibold text-slate-200">Aplicar a cuota
            <select name="payment_schedule_id" class="mt-2 w-full" data-placeholder="Opcional · seleccionar cuota"><option value="">Pago general / varias cuotas</option><?php foreach($schedule as $row): ?><option value="<?= (int)$row['id'] ?>" <?= (int)old('payment_schedule_id')===(int)$row['id']?'selected':'' ?>>#<?= (int)$row['sequence'] ?> · <?= esc($row['concept']) ?> · pendiente $<?= number_format((float)($row['remaining_amount']??$row['amount']),2) ?></option><?php endforeach ?></select>
        </label>
        <label class="block text-sm font-semibold text-slate-200">Forma de pago *<input name="payment_method" required maxlength="120" value="<?= esc(old('payment_method')) ?>" placeholder="Transferencia bancaria, cheque, tarjeta…" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 font-normal text-white"></label>
        <label class="block text-sm font-semibold text-slate-200">Código MH forma de pago <span class="font-normal text-slate-500">(opcional por ahora)</span><input name="mh_payment_code" maxlength="2" pattern="(0[1-9]|1[0-4]|99)" value="<?= esc(old('mh_payment_code')) ?>" placeholder="01-14 / 99" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 font-mono font-normal text-white"><span class="mt-1 block text-xs font-normal text-slate-500">Se conectará al CAT de forma de pago antes de emitir el DTE.</span></label>
        <label class="block text-sm font-semibold text-slate-200">Monto recibido *<input name="amount" type="number" min="0.01" max="<?= esc(number_format((float)$balance_amount,2,'.','')) ?>" step="0.01" required value="<?= esc(old('amount')) ?>" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 font-normal text-white"></label>
        <label class="block text-sm font-semibold text-slate-200">Fecha del pago *<input name="payment_date" type="datetime-local" required value="<?= esc(old('payment_date',date('Y-m-d\TH:i'))) ?>" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 font-normal text-white"></label>
        <label class="block text-sm font-semibold text-slate-200">Referencia<input name="reference" maxlength="50" value="<?= esc(old('reference')) ?>" placeholder="No. transferencia / cheque / voucher" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 font-normal text-white"></label>
        <label class="block text-sm font-semibold text-slate-200">Número de pago electrónico<input name="electronic_payment_number" maxlength="100" value="<?= esc(old('electronic_payment_number')) ?>" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 font-normal text-white"></label>
        <label class="block text-sm font-semibold text-slate-200">Notas<textarea name="notes" rows="2" maxlength="500" class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900 px-4 py-3 font-normal text-white"><?= esc(old('notes')) ?></textarea></label>
        <button class="w-full rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950">Registrar y confirmar pago</button>
    </form>
    <?php endif ?>
</section>

<section class="rounded-2xl border border-cyan-200 bg-cyan-50 p-5"><p class="text-xs font-semibold uppercase tracking-[.18em] text-cyan-700">Regla financiera</p><p class="mt-3 text-sm leading-6 text-cyan-950">Los pagos confirmados alimentan <strong>Financial Policy Engine</strong>. Si alcanzan el anticipo obligatorio, TraceOPX libera automáticamente la aprobación de Coordinación. El saldo de una cuota se calcula sumando todos los movimientos aplicados a ella, independientemente de su método de pago.</p></section>
</aside>
</div>

<?= $this->endSection() ?>