<?php
/** @var string $label */
/** @var string|int|float $value */
/** @var string|null $detail */
$detail ??= null;
?>
<article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <p class="text-sm font-medium text-slate-500"><?= esc($label) ?></p>
    <p class="mt-3 text-3xl font-bold text-slate-950"><?= esc((string) $value) ?></p>
    <?php if ($detail): ?><p class="mt-2 text-sm text-slate-500"><?= esc($detail) ?></p><?php endif ?>
</article>
