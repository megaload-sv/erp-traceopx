<?php
/**
 * @var string $eyebrow
 * @var string $title
 * @var string $description
 * @var string|null $actionUrl
 * @var string|null $actionLabel
 */
$actionUrl ??= null;
$actionLabel ??= null;
?>
<div class="mb-7 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-cyan-600"><?= esc($eyebrow) ?></p>
        <h3 class="mt-2 text-3xl font-bold text-slate-950"><?= esc($title) ?></h3>
        <p class="mt-2 max-w-3xl text-slate-600"><?= esc($description) ?></p>
    </div>
    <?php if ($actionUrl && $actionLabel): ?>
        <a href="<?= esc($actionUrl) ?>" class="rounded-xl bg-slate-950 px-5 py-3 text-center text-sm font-semibold text-white transition hover:bg-slate-800">
            <?= esc($actionLabel) ?>
        </a>
    <?php endif ?>
</div>
