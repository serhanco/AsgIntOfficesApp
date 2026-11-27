/**
 * Admin login background: a slowly breathing field of small coloured dashes around the centre,
 * which drifts toward the pointer and parts around it. Plain canvas, no libraries.
 * Static single frame for prefers-reduced-motion; pauses while the tab is hidden.
 */
(function () {
    'use strict';
    var canvas = document.getElementById('login-field');
    if (!canvas || !canvas.getContext) return;
    var ctx = canvas.getContext('2d');
    var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // Site palette: aqua, azure, indigo, violet, coral
    var COLORS = ['#25aae1', '#1479a8', '#3d5afe', '#7c4dff', '#f26b50'];
    var W = 0, H = 0, dpr = 1, parts = [], buckets = [];
    var pointer = { x: 0, y: 0, on: false }, center = { x: 0, y: 0 };
    var raf = 0, t0 = performance.now();

    function colorAt(angle) { // smooth sweep of the palette around the circle, coral only on one side
        var u = ((angle / (Math.PI * 2)) % 1 + 1) % 1;
        var i = Math.floor(u * COLORS.length);
        return i;
    }

    function build() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        W = canvas.clientWidth; H = canvas.clientHeight;
        canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        center.x = W / 2; center.y = H / 2;
        var n = Math.max(420, Math.min(1100, Math.round(W * H / 1150)));
        var maxR = Math.hypot(W, H) * 0.55, minR = Math.min(W, H) * 0.16;
        parts = [];
        for (var i = 0; i < n; i++) {
            var a = Math.random() * Math.PI * 2;
            // denser towards a soft ring, thinning outwards
            var r = minR + Math.pow(Math.random(), 1.35) * (maxR - minR);
            parts.push({
                a: a, r: r,
                w: (0.018 + Math.random() * 0.03) * (Math.random() < 0.5 ? 1 : -1) * (minR / r + 0.3),
                ph: Math.random() * Math.PI * 2,
                len: 2 + Math.random() * 4.5,
                c: colorAt(a + (Math.random() - 0.5) * 0.9),
                ox: 0, oy: 0
            });
        }
        buckets = COLORS.map(function () { return []; });
        parts.forEach(function (p) { buckets[p.c].push(p); });
    }

    function frame(now) {
        var t = (now - t0) / 1000;
        // the centre leans a little toward the pointer
        var tx = W / 2 + (pointer.on ? (pointer.x - W / 2) * 0.12 : 0);
        var ty = H / 2 + (pointer.on ? (pointer.y - H / 2) * 0.12 : 0);
        center.x += (tx - center.x) * 0.04; center.y += (ty - center.y) * 0.04;
        ctx.clearRect(0, 0, W, H);
        ctx.lineCap = 'round';
        var maxR = Math.hypot(W, H) * 0.55, R = 130;
        for (var b = 0; b < buckets.length; b++) {
            ctx.strokeStyle = COLORS[b];
            ctx.lineWidth = b === 4 ? 1.6 : 2;
            ctx.globalAlpha = b === 4 ? 0.55 : 0.7;
            ctx.beginPath();
            var list = buckets[b];
            for (var i = 0; i < list.length; i++) {
                var p = list[i];
                var ang = p.a + p.w * t;
                var rr = p.r * (1 + 0.05 * Math.sin(t * 0.7 + p.r * 0.012 + p.ph));
                var ca = Math.cos(ang), sa = Math.sin(ang);
                var x = center.x + ca * rr, y = center.y + sa * rr;
                // part around the pointer, settle back softly
                var px = 0, py = 0;
                if (pointer.on) {
                    var dx = x - pointer.x, dy = y - pointer.y, d = Math.hypot(dx, dy);
                    if (d < R && d > 0.01) { var f = Math.pow(1 - d / R, 2) * 46; px = dx / d * f; py = dy / d * f; }
                }
                p.ox += (px - p.ox) * 0.12; p.oy += (py - p.oy) * 0.12;
                x += p.ox; y += p.oy;
                var fade = 1 - rr / maxR; // shorter dashes further out
                var l = p.len * (0.45 + fade * 0.8);
                ctx.moveTo(x - ca * l, y - sa * l);
                ctx.lineTo(x + ca * l, y + sa * l);
            }
            ctx.stroke();
        }
        ctx.globalAlpha = 1;
        if (!still) raf = requestAnimationFrame(frame);
    }

    function start() { if (!raf) raf = requestAnimationFrame(frame); }
    function stop() { cancelAnimationFrame(raf); raf = 0; }

    var rt;
    window.addEventListener('resize', function () {
        clearTimeout(rt);
        rt = setTimeout(function () { build(); if (still) frame(performance.now()); }, 150);
    });
    build();
    if (still) { frame(t0 + 4000); return; }
    window.addEventListener('pointermove', function (e) { pointer.x = e.clientX; pointer.y = e.clientY; pointer.on = e.pointerType === 'mouse'; }, { passive: true });
    document.addEventListener('pointerleave', function () { pointer.on = false; });
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
    start();
})();
