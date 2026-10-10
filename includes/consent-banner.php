<?php
/**
 * Cookie consent bar + "Cookie preferences" handling (only for visitors who need to give consent, see consentRegion()).
 * Choosing "Accept" reloads the page so the server can add statistics and custom codes.
 */
if (!showConsentUi()) return;
$choice = consentChoice();
?>
<style>
.cc-bar{position:fixed;inset-inline:0;bottom:0;z-index:2147483000;background:#061a47;color:#fff;border-top:1px solid rgba(255,255,255,.15);padding:14px 16px calc(14px + env(safe-area-inset-bottom));box-shadow:0 -8px 30px rgba(0,0,0,.25)}
.cc-bar[hidden]{display:none}
.cc-in{max-width:72rem;margin:0 auto;display:flex;flex-wrap:wrap;align-items:center;gap:12px 20px;font-size:.875rem;line-height:1.45}
.cc-in p{flex:1 1 22rem;margin:0;color:#dbe6f6}
.cc-btns{display:flex;gap:10px;flex:0 0 auto}
.cc-btn{font:inherit;font-weight:700;border-radius:999px;padding:.55rem 1.25rem;cursor:pointer;border:1px solid rgba(255,255,255,.45);background:transparent;color:#fff}
.cc-btn--yes{background:linear-gradient(135deg,#25aae1,#1a4ba0);border-color:transparent}
.cc-btn:focus-visible{outline:2px solid #8fd6f3;outline-offset:2px}
</style>
<div class="cc-bar" id="cc-bar" role="region" aria-label="<?= e(__('cookie_manage')) ?>"<?= $choice !== '' ? ' hidden' : '' ?>>
    <div class="cc-in">
        <p><?= __('cookie_text') ?></p>
        <div class="cc-btns">
            <button type="button" class="cc-btn" data-cc="no"><?= __('cookie_decline') ?></button>
            <button type="button" class="cc-btn cc-btn--yes" data-cc="yes"><?= __('cookie_accept') ?></button>
        </div>
    </div>
</div>
<script>
(function () {
    var bar = document.getElementById('cc-bar');
    var was = <?= json_encode($choice) ?>;
    var advanced = <?= json_encode(consentMode() === 'advanced') ?>;
    function set(v) {
        try {
            document.cookie = 'asg_consent=' + v + '; Max-Age=15552000; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
        } catch (e) {}
        // Basic mode: nothing is loaded before consent, so the page reloads to add (or drop) the tags
        if (!advanced && (v === 'yes' || was === 'yes')) { location.reload(); return; }
        // Advanced mode: tell Google (Consent Mode v2) without reloading
        if (typeof gtag === 'function') {
            var s = v === 'yes' ? 'granted' : 'denied';
            gtag('consent', 'update', { ad_storage: s, ad_user_data: s, ad_personalization: s, analytics_storage: s });
            window.dataLayer.push({ event: 'consent_update', consent_choice: v });
        }
        was = v;
        bar.hidden = true;
    }
    bar.addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-cc]');
        if (b) set(b.getAttribute('data-cc'));
    });
    document.addEventListener('click', function (ev) {
        if (ev.target.closest('[data-consent-open]')) { ev.preventDefault(); bar.hidden = false; bar.querySelector('button').focus(); }
    });
})();
</script>
