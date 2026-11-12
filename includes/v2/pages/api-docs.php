<?php
/** API documentation. Variables come from api-docs.php. Plain English, not translated (developer audience). */
require __DIR__ . '/../header.php';
$fields = [
    ['id', 'integer', 'Stable numeric id of the office.'],
    ['name', 'string', 'Official office name.'],
    ['display_name', 'string', 'Short name shown on the site.'],
    ['slug', 'string', 'URL key. The office page is at /office/{slug}.'],
    ['country', 'string', 'Country name in English.'],
    ['country_code', 'string', 'Two-letter ISO 3166-1 country code.'],
    ['address', 'string', 'Street address of the office.'],
    ['phone', 'string', 'Office phone number, international format.'],
    ['whatsapp', 'string', 'Office WhatsApp number. Empty string when the office has none or WhatsApp is switched off site-wide.'],
    ['email', 'string', 'Office e-mail address.'],
    ['latitude', 'number', 'Latitude in decimal degrees (WGS 84).'],
    ['longitude', 'number', 'Longitude in decimal degrees (WGS 84).'],
];
?>

<section class="page-head">
    <div class="wrap py-14 sm:py-20 text-center">
        <h1 class="rise text-3xl sm:text-5xl font-extrabold max-w-3xl mx-auto">Offices API</h1>
        <p class="rise mt-4 text-[#cfdcef] text-base sm:text-lg max-w-2xl mx-auto leading-relaxed" style="--d:.06s">Contact details of the Acıbadem international offices as JSON, always current. Use it so every site shows the same, correct office information.</p>
    </div>
</section>

<div class="wrap py-10 sm:py-14 max-w-4xl" dir="ltr" lang="en">
    <section aria-labelledby="api-overview">
        <h2 id="api-overview" class="text-xl font-extrabold text-navy">Overview</h2>
        <p class="mt-3 text-ink leading-relaxed">Right now the API returns <strong><?= (int)$officeCount ?></strong> active offices in <strong><?= (int)$countryCount ?></strong> countries. It is public and read-only: no key or sign-in is needed. Changes made in the admin panel appear in the API within a few minutes.</p>
    </section>

    <section aria-labelledby="api-request" class="mt-10">
        <h2 id="api-request" class="text-xl font-extrabold text-navy">Request</h2>
        <pre class="mt-3 card p-4 overflow-x-auto text-sm"><code>GET <?= e($endpoint) ?></code></pre>
        <ul class="mt-3 list-disc ps-6 space-y-1 text-ink">
            <li>Method: <code>GET</code> only (other methods return <code>405</code>).</li>
            <li>No parameters: the response always lists every active office, ordered by country and name.</li>
            <li>Format: JSON, UTF-8. Texts are not translated.</li>
            <li>CORS is open (<code>Access-Control-Allow-Origin: *</code>), so browsers can call it directly.</li>
            <li>Responses are cached for 5 minutes (<code>Cache-Control: public, max-age=300</code>). Cache on your side for the same time at most, and do not call it on every page view.</li>
        </ul>
        <p class="mt-4 text-sm text-ink/70">Example:</p>
        <pre class="mt-2 card p-4 overflow-x-auto text-sm"><code>curl -s <?= e($endpoint) ?></code></pre>
        <p class="mt-4"><a class="btn btn--sm" href="<?= e(getBaseUrl()) ?>/api/offices" target="_blank" rel="noopener"><i class="ph-fill ph-code" aria-hidden="true"></i> Open the live JSON</a></p>
    </section>

    <section aria-labelledby="api-response" class="mt-10">
        <h2 id="api-response" class="text-xl font-extrabold text-navy">Response</h2>
        <p class="mt-3 text-ink">A real record from the current data (the list is shortened here):</p>
        <pre class="mt-3 card p-4 overflow-x-auto text-sm"><code><?= e($sampleJson) ?></code></pre>
        <p class="mt-4 text-ink">Top level fields: <code>success</code> (boolean), <code>count</code> (number of offices), <code>generated_at</code> (ISO 8601 time of the response), <code>data</code> (list of offices).</p>
    </section>

    <section aria-labelledby="api-fields" class="mt-10">
        <h2 id="api-fields" class="text-xl font-extrabold text-navy">Office fields</h2>
        <div class="mt-3 card overflow-x-auto">
            <table class="w-full text-sm text-start">
                <thead><tr class="border-b border-black/10"><th class="p-3 text-start">Field</th><th class="p-3 text-start">Type</th><th class="p-3 text-start">Description</th></tr></thead>
                <tbody>
                <?php foreach ($fields as $f): ?>
                    <tr class="border-b border-black/5 last:border-0"><td class="p-3 font-mono font-bold"><?= e($f[0]) ?></td><td class="p-3"><?= e($f[1]) ?></td><td class="p-3"><?= e($f[2]) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section aria-labelledby="api-errors" class="mt-10">
        <h2 id="api-errors" class="text-xl font-extrabold text-navy">Errors</h2>
        <p class="mt-3 text-ink">On a problem the response is <code>{"success": false, "error": "..."}</code> with status <code>405</code> (wrong method) or <code>500</code> (server error). Try again later on <code>500</code>.</p>
    </section>

    <section aria-labelledby="api-notes" class="mt-10">
        <h2 id="api-notes" class="text-xl font-extrabold text-navy">Usage notes</h2>
        <ul class="mt-3 list-disc ps-6 space-y-1 text-ink">
            <li>Treat this API as the single source of truth for office contact details. Do not copy the numbers into your own pages; read them from the API so they stay correct.</li>
            <li>Link to the office page on this site (<code><?= e(rtrim(getBaseUrl(), '/')) ?>/office/{slug}</code>) for team, events and directions.</li>
            <li>The API covers offices only. Events live on this site; link to them instead of copying them.</li>
            <li>Questions or a new field you need: write to <a class="underline" href="mailto:international@acibadem.com">international@acibadem.com</a>.</li>
        </ul>
    </section>
</div>

<?php require __DIR__ . '/../footer.php'; ?>
