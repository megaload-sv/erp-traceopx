<?php
$title = $title ?? '';
$status = $status ?? 'pending';
$dueAt = $dueAt ?? null;
$isOverdue = (bool) ($isOverdue ?? false);
$statusLabels = ['pending' => 'Pendiente', 'in_progress' => 'En progreso', 'completed' => 'Completada', 'cancelled' => 'Cancelada'];
$badge = $status === 'completed'
    ? 'bg-emerald-100 text-emerald-700'
    : ($isOverdue ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-700');
?>
<article class="rounded-xl border <?= $isOverdue ? 'border-red-200 bg-red-50' : 'border-slate-200 bg-white' ?> p-4">
    <div class="flex items-start justify-between gap-3">
        <p class="font-semibold"><?= esc($title) ?></p>
        <span class="rounded-full <?= esc($badge) ?> px-2 py-1 text-[11px] font-semibold"><?= esc($statusLabels[$status] ?? $status) ?></span>
    </div>
    <?php if ($dueAt): ?><p class="mt-2 text-xs <?= $isOverdue ? 'font-semibold text-red-700' : 'text-slate-500' ?>">Vence <?= esc(date('d/m/Y H:i', strtotime((string) $dueAt))) ?></p><?php endif ?>
</article>
