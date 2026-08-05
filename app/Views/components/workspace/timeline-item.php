<?php
$direction = $direction ?? 'inbound';
$actor = $actor ?? 'Cliente';
$meta = $meta ?? '';
$body = $body ?? '';
$styles = [
    'outbound' => ['line' => 'border-cyan-400', 'dot' => 'bg-cyan-500', 'card' => 'border-cyan-200 bg-cyan-50/50'],
    'internal' => ['line' => 'border-violet-400', 'dot' => 'bg-violet-500', 'card' => 'border-violet-200 bg-violet-50/50'],
    'inbound' => ['line' => 'border-slate-300', 'dot' => 'bg-slate-400', 'card' => 'border-slate-200 bg-slate-50'],
];
$style = $styles[$direction] ?? $styles['inbound'];
?>
<article class="relative border-l-2 <?= esc($style['line']) ?> pb-7 pl-6 last:pb-0">
    <span class="absolute -left-[7px] top-1 h-3 w-3 rounded-full <?= esc($style['dot']) ?>"></span>
    <div class="rounded-2xl border <?= esc($style['card']) ?> p-5">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-bold"><?= esc($actor) ?></p>
            <p class="text-xs text-slate-500"><?= esc($meta) ?></p>
        </div>
        <p class="mt-3 whitespace-pre-line text-slate-700"><?= esc($body) ?></p>
    </div>
</article>
