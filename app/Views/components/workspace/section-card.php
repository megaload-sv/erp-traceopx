<?php
/** @var string $title */
/** @var string|null $eyebrow */
/** @var string|null $description */
/** @var string|null $actionUrl */
/** @var string|null $actionLabel */
/** @var string $content */
$eyebrow ??= null;
$description ??= null;
$actionUrl ??= null;
$actionLabel ??= null;
?>
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <?php if ($eyebrow): ?><p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-600"><?= esc($eyebrow) ?></p><?php endif ?>
            <h4 class="<?= $eyebrow ? 'mt-1 ' : '' ?>text-lg font-bold text-slate-950"><?= esc($title) ?></h4>
            <?php if ($description): ?><p class="mt-1 text-sm text-slate-500"><?= esc($description) ?></p><?php endif ?>
        </div>
        <?php if ($actionUrl && $actionLabel): ?>
            <a href="<?= esc($actionUrl) ?>" class="rounded-xl bg-slate-950 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-slate-800"><?= esc($actionLabel) ?></a>
        <?php endif ?>
    </div>
    <div class="mt-5"><?= $content ?></div>
</section>
