<?php
/**
 * New design: shared pieces for events (cards, date badges, stop chips).
 * Dates are printed as numbers and re-formatted in the page language by assets/js/v2.js (Intl).
 */

const V2_EVENT_STYLE = [
    'act_tag_doctor'       => ['chip' => 'from-aqua to-navy',               'text' => 'text-azure'],
    'act_tag_presentation' => ['chip' => 'from-violet-400 to-violet-700',   'text' => 'text-violet-700'],
    'act_tag_exhibition'   => ['chip' => 'from-emerald-400 to-emerald-700', 'text' => 'text-emerald-700'],
    'act_tag_webinar'      => ['chip' => 'from-coral-light to-coral-ink',   'text' => 'text-coral-ink'],
];

function v2EventStyle(string $tagKey): array {
    return V2_EVENT_STYLE[$tagKey] ?? V2_EVENT_STYLE['act_tag_doctor'];
}

/** Type label in the page language (the event text itself stays in its own language). */
function v2EventTag(string $tagKey, string $extra = ''): string {
    $st = v2EventStyle($tagKey);
    return '<span lang="' . e(langHtml()) . '" dir="' . langDir() . '" class="inline-flex items-center gap-1.5 text-[0.7rem] font-bold uppercase tracking-wider ' . $st['text'] . ' ' . $extra . '">'
         . '<i class="ph-fill ' . eventIcon($tagKey) . ' text-sm" aria-hidden="true"></i>' . __($tagKey) . '</span>';
}

/** Calendar-leaf badge: month on top, day(s) below. Undated events get a calendar icon. */
function v2DateBadge(?string $start, ?string $end, bool $past = false, string $size = 'md'): string {
    $box = $size === 'lg' ? 'w-[4.5rem] h-[4.75rem] rounded-2xl' : 'w-16 h-[4.25rem] rounded-2xl';
    $tone = $past ? 'bg-surface-2 text-muted' : 'bg-gradient-to-br from-navy to-navy-950 text-white shadow-md';
    if ($size === 'lg') $tone = $past ? 'bg-white/10 text-white border border-white/20' : 'bg-white text-navy shadow-lift'; // on the dark page head
    if (!$start) {
        return '<span class="date-badge ' . $box . ' ' . $tone . '" title="' . e(__('event_date_tba')) . '"><i class="ph-fill ph-calendar-dots text-2xl" aria-hidden="true"></i></span>';
    }
    $end = $end ?: $start;
    $d1 = (int)substr($start, 8, 2);
    $d2 = (int)substr($end, 8, 2);
    $sameMonth = substr($start, 0, 7) === substr($end, 0, 7);
    $days = $start === $end ? (string)$d1 : ($sameMonth ? $d1 . '–' . $d2 : $d1 . '+');
    return '<time class="date-badge ' . $box . ' ' . $tone . '" datetime="' . e($start) . '" dir="ltr">'
         . '<span class="date-badge__m" data-month="' . e($start) . '">' . e(substr($start, 5, 2) . '/' . substr($start, 2, 2)) . '</span>'
         . '<span class="date-badge__d' . (mb_strlen($days) > 3 ? ' is-range' : '') . '">' . e($days) . '</span></time>';
}

/** Full date (range) in the page language; numeric fallback without JS. */
function v2DateText(?string $start, ?string $end = null, ?string $t1 = null, ?string $t2 = null): string {
    if (!$start) return '<span>' . __('event_date_tba') . '</span>';
    $end = $end ?: $start;
    $fmt = fn($d) => substr($d, 8, 2) . '.' . substr($d, 5, 2) . '.' . substr($d, 0, 4);
    $txt = $fmt($start) . ($end !== $start ? ' – ' . $fmt($end) : '');
    $out = '<time data-date="' . e($start) . '"' . ($end !== $start ? ' data-date-end="' . e($end) . '"' : '') . ' datetime="' . e($start) . '">' . $txt . '</time>';
    if ($t1) $out .= ' <span class="whitespace-nowrap" dir="ltr">· ' . e($t1) . ($t2 ? '–' . e($t2) : '') . '</span>';
    return $out;
}

/** Small "flag + city" chips for the stops of an event. */
function v2StopChips(array $ev, int $max = 4, string $tone = 'light'): string {
    $out = '';
    $cls = $tone === 'dark'
        ? 'bg-white/10 border-white/20 text-white'
        : 'bg-surface border-line text-ink';
    foreach (array_slice($ev['locations'], 0, $max) as $l) {
        $label = $l['is_online'] ? __('event_online') : ($l['city'] ?: $l['name']);
        $flag = $l['is_online'] || !$l['country_code']
            ? '<i class="ph-fill ' . ($l['is_online'] ? 'ph-video-camera' : 'ph-map-pin') . ' text-aqua" aria-hidden="true"></i>'
            : '<img src="' . e(getFlagUrl($l['country_code'])) . '" alt="" width="18" height="13" loading="lazy" class="w-[18px] h-[13px] object-cover rounded-[3px] flex-shrink-0">';
        $out .= '<span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold ' . $cls . '">' . $flag . '<bdi>' . e($label) . '</bdi></span>';
    }
    $more = count($ev['locations']) - $max;
    if ($more > 0) $out .= '<span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold ' . $cls . '">+' . $more . '</span>';
    return $out;
}

/** Event card used on /events, the home page and office pages. */
function v2EventCard(array $ev, string $extraClass = ''): string {
    $past = $ev['status'] === 'past';
    $dir = textDir($ev['title'] . ' ' . $ev['description']);
    ob_start(); ?>
    <a href="<?= e($ev['url']) ?>" class="event-card group card card--hover flex gap-4 p-4 sm:p-5 <?= $past ? 'is-past' : '' ?> <?= $extraClass ?>"
       data-type="<?= e($ev['tag_key']) ?>" data-cc="<?= e(implode(' ', $ev['countries'])) ?>">
        <?= v2DateBadge($ev['start'], $ev['end'], $past) ?>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <?= v2EventTag($ev['tag_key']) ?>
                <?php if ($past): ?><span class="text-[0.7rem] font-bold uppercase tracking-wider text-muted-2"><?= __('event_past_badge') ?></span><?php endif; ?>
            </div>
            <p class="event-text mt-1 font-bold text-ink leading-snug text-[1.02rem] line-clamp-2 group-hover:text-navy transition-colors" dir="<?= $dir ?>"><?= e($ev['title']) ?></p>
            <p class="mt-1 text-sm text-muted"><?= v2DateText($ev['start'], $ev['end']) ?></p>
            <?php if ($ev['locations']): ?>
            <div class="mt-3 flex flex-wrap gap-1.5"><?= v2StopChips($ev, 3) ?></div>
            <?php endif; ?>
        </div>
        <i class="ph ph-arrow-right arrow self-center text-xl text-muted-2 group-hover:text-aqua flex-shrink-0" aria-hidden="true"></i>
    </a>
    <?php
    return ob_get_clean();
}
