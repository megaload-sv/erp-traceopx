<?php
$taskId = (int) ($taskId ?? 0);
$title = $title ?? '';
$status = $status ?? 'pending';
$dueAt = $dueAt ?? null;
$isOverdue = (bool) ($isOverdue ?? false);
$completionNote = $completionNote ?? null;
$rescheduleReason = $rescheduleReason ?? null;
$statusLabels = ['pending' => 'Pendiente', 'in_progress' => 'En progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada'];
$badge = $status === 'completed'
    ? 'bg-emerald-100 text-emerald-700'
    : ($status === 'in_progress'
        ? 'bg-cyan-100 text-cyan-700'
        : ($isOverdue ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700'));
$isActive = in_array($status, ['pending', 'in_progress'], true);
?>
<article class="rounded-xl border <?= $isOverdue ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-white' ?> p-4">
    <div class="flex items-start justify-between gap-3">
        <p class="font-semibold"><?= esc($title) ?></p>
        <span class="rounded-full <?= esc($badge) ?> px-2 py-1 text-[11px] font-semibold"><?= esc($statusLabels[$status] ?? $status) ?></span>
    </div>

    <?php if ($dueAt): ?>
        <p class="mt-2 text-xs <?= $isOverdue ? 'font-semibold text-red-700' : 'text-slate-500' ?>">Vence <?= esc(date('d/m/Y H:i', strtotime((string) $dueAt))) ?></p>
    <?php endif ?>

    <?php if ($completionNote): ?><p class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-xs text-emerald-800"><?= esc($completionNote) ?></p><?php endif ?>
    <?php if ($rescheduleReason && $isActive): ?><p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">Reprogramación: <?= esc($rescheduleReason) ?></p><?php endif ?>

    <?php if ($taskId > 0 && $isActive): ?>
        <div class="mt-4 space-y-3 border-t border-slate-200 pt-4">
            <?php if ($status === 'pending'): ?>
                <form method="post" action="<?= route_to('tasks.start', $taskId) ?>">
                    <?= csrf_field() ?>
                    <button class="w-full rounded-lg bg-cyan-600 px-3 py-2 text-xs font-semibold text-white hover:bg-cyan-700">Iniciar tarea</button>
                </form>
            <?php endif ?>

            <details class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <summary class="cursor-pointer text-xs font-semibold text-slate-700">Completar tarea</summary>
                <form method="post" action="<?= route_to('tasks.complete', $taskId) ?>" class="mt-3 space-y-2">
                    <?= csrf_field() ?>
                    <textarea required name="completion_note" rows="2" placeholder="Resultado de la tarea" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs"></textarea>
                    <button class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Marcar como completada</button>
                </form>
            </details>

            <details class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <summary class="cursor-pointer text-xs font-semibold text-slate-700">Reprogramar</summary>
                <form method="post" action="<?= route_to('tasks.reschedule', $taskId) ?>" class="mt-3 space-y-2">
                    <?= csrf_field() ?>
                    <input required type="datetime-local" name="due_at" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">
                    <textarea required name="reschedule_reason" rows="2" placeholder="Motivo de la reprogramación" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs"></textarea>
                    <button class="w-full rounded-lg border border-amber-300 bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-900 hover:bg-amber-200">Guardar nueva fecha</button>
                </form>
            </details>
        </div>
    <?php endif ?>
</article>
