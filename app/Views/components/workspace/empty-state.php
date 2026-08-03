<?php
/** @var string $title */
/** @var string $description */
/** @var string|null $actionUrl */
/** @var string|null $actionLabel */
$actionUrl ??= null;
$actionLabel ??= null;
?>
<div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 p-8 text-center">
    <p class="font-semibold text-slate-900"><?= esc($title) ?></p>
    <p class="mx-auto mt-1 max-w-xl text-sm text-slate-500"><?= esc($description) ?></p>
    <?php if ($actionUrl && $actionLabel): ?>
        <a href="<?= esc($actionUrl) ?>" class="mt-4 inline-flex rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
            <?= esc($actionLabel) ?>
        </a>
    <?php endif ?>
</div>
