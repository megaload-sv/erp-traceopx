<?php
$label = $label ?? '';
$value = $value ?? '';
$description = $description ?? null;
$tone = $tone ?? 'slate';
$tones = [
    'slate' => 'border-slate-200 bg-white text-slate-950',
    'cyan' => 'border-cyan-200 bg-cyan-50 text-cyan-950',
    'violet' => 'border-violet-200 bg-violet-50 text-violet-950',
    'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-950',
    'amber' => 'border-amber-200 bg-amber-50 text-amber-950',
    'red' => 'border-red-200 bg-red-50 text-red-950',
];
?>
<article class="rounded-2xl border <?= esc($tones[$tone] ?? $tones['slate']) ?> p-5 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-[.16em] opacity-70"><?= esc($label) ?></p>
    <p class="mt-2 text-xl font-bold"><?= esc((string) $value) ?></p>
    <?php if ($description): ?><p class="mt-2 text-sm opacity-75"><?= esc($description) ?></p><?php endif ?>
</article>
