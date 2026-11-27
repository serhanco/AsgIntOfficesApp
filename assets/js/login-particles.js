/**
 * Admin login background: a quiet lattice of tiny dashes on white. A soft ring of them lights up around a
 * centre that drifts after the pointer; dashes point away from the centre, take their colour from their angle
 * around it and ripple outward. Plain canvas, no libraries.
 * Static single frame for prefers-reduced-motion; pauses while the tab is hidden.
 */
(function () {
    'use strict';
    var canvas = document.getElementById('login-field');
    if (!canvas || !canvas.getContext) return;
    var ctx = canvas.getContext('2d');
    var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    // Colour sweep around the centre: mostly blues and violets, a warm arc on one side
    var COLORS = ['#2f6df6', '#3d5afe', '#5b4dff', '#7c4dff', '#2f6df6', '#1a8fc4', '#e8453c', '#f26b50', '#f59e0b', '#f7b500', '#3d5afe', '#2f6df6'];
    var LEVELS = [0.28, 0.55, 0.9]; // alpha steps (few paths per frame)
    var W = 0, H = 0, dpr = 1, pts = [], paths = [];
    var pointer = { x: 0, y: 0, on: false }, c = { x: 0, y: 0 };
    var raf = 0, t0 = performance.now();

    function build() {
        dpr = Math.min(window.devicePixelRatio || 1, 2);
        W = canvas.clientWidth; H = canvas.clientHeight;
        canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        var gap = W < 600 ? 20 : 24; // lattice spacing in CSS px
        pts = [];
        for (var y = gap / 2; y < H + gap; y += gap) {
            for (var x = gap / 2; x < W + gap; x += gap) {
                pts.push({ x: x + (Math.random() - 0.5) * gap * 0.7, y: y + (Math.random() - 0.5) * gap * 0.7, j: Math.random() });
            }
        }
        c.x = W / 2; c.y = H / 2;
        paths = [];
        for (var i = 0; i < COLORS.length * LEVELS.length; i++) paths.push([]);
    }

    function frame(now) {
        var t = (now - t0) / 1000;
        var m = Math.sqrt(W * H) * 0.85; // works for wide screens and tall phones alike
        // the centre follows the pointer, or circles slowly around the middle
        var tx = pointer.on ? pointer.x : W / 2 + Math.cos(t * 0.25) * m * 0.12;
        var ty = pointer.on ? pointer.y : H / 2 + Math.sin(t * 0.25) * m * 0.08;
        c.x += (tx - c.x) * 0.035; c.y += (ty - c.y) * 0.035;
        var ring = m * 0.36 * (1 + 0.07 * Math.sin(t * 0.8));
        var band = m * 0.26;
        var nC = COLORS.length, nL = LEVELS.length;
        for (var k = 0; k < paths.length; k++) paths[k].length = 0;

        for (var i = 0; i < pts.length; i++) {
            var p = pts[i];
            var dx = p.x - c.x, dy = p.y - c.y;
            var d = Math.sqrt(dx * dx + dy * dy) || 1;
            var q = (d - ring) / band;
            var glow = Math.exp(-q * q) * (0.65 + 0.35 * Math.sin(d * 0.03 - t * 2.4 + p.j)); // ring + outward ripple
            if (glow < 0.12) continue;
            var ux = dx / d, uy = dy / d;
            var push = glow * 7 * Math.sin(t * 1.3 + d * 0.01); // ring breathes in and out
            var x = p.x + ux * push, y = p.y + uy * push;
            var len = 0.8 + glow * 2.2;
            var ang = Math.atan2(dy, dx) + 0.35 * Math.sin(t * 0.3); // colours turn slowly
            var ci = Math.floor((((ang / (Math.PI * 2)) % 1 + 1) % 1) * nC + p.j * 0.9) % nC;
            var li = glow > 0.75 ? 2 : glow > 0.4 ? 1 : 0;
            paths[ci * nL + li].push(x - ux * len, y - uy * len, x + ux * len, y + uy * len);
        }

        ctx.clearRect(0, 0, W, H);
        ctx.lineCap = 'round';
        ctx.lineWidth = 1.5;
        for (var ci2 = 0; ci2 < nC; ci2++) {
            ctx.strokeStyle = COLORS[ci2];
            for (var li2 = 0; li2 < nL; li2++) {
                var a = paths[ci2 * nL + li2];
                if (!a.length) continue;
                ctx.globalAlpha = LEVELS[li2];
                ctx.beginPath();
                for (var s = 0; s < a.length; s += 4) { ctx.moveTo(a[s], a[s + 1]); ctx.lineTo(a[s + 2], a[s + 3]); }
                ctx.stroke();
            }
        }
        ctx.globalAlpha = 1;
        if (!still) raf = requestAnimationFrame(frame);
    }

    function start() { if (!raf) raf = requestAnimationFrame(frame); }
    function stop() { cancelAnimationFrame(raf); raf = 0; }

    var rt;
    window.addEventListener('resize', function () {
        clearTimeout(rt);
        rt = setTimeout(function () { build(); if (still) frame(t0 + 3000); }, 150);
    });
    build();
    if (still) { frame(t0 + 3000); return; }
    window.addEventListener('pointermove', function (e) { if (!e.clientX && !e.clientY) return; pointer.x = e.clientX; pointer.y = e.clientY; pointer.on = true; }, { passive: true });
    document.addEventListener('pointerleave', function () { pointer.on = false; });
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });
    start();
})();
