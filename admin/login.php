<?php
require_once __DIR__ . '/includes/auth.php';

if (is_admin_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (is_login_locked($username)) {
        $error = 'Çok fazla hatalı deneme. Lütfen 15 dakika sonra tekrar deneyin.';
    } elseif (admin_login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Geçersiz kullanıcı adı veya şifre.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Yönetici Girişi - Acıbadem Int. Offices</title>
    <link rel="icon" href="../assets/images/favicon.svg" type="image/svg+xml">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; }
        body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #13233c;
               background: radial-gradient(120% 90% at 50% 40%, #ffffff 0%, #f4f7fc 55%, #e9eff8 100%); -webkit-font-smoothing: antialiased; }
        #login-field { position: fixed; inset: 0; width: 100%; height: 100%; display: block; }
        .wrap { position: relative; z-index: 1; min-height: 100%; display: flex; align-items: center; justify-content: center; padding: 24px 16px; }
        .card { width: 100%; max-width: 400px; padding: 36px 32px 32px; border-radius: 24px; background: rgba(255,255,255,.78);
                -webkit-backdrop-filter: blur(14px) saturate(140%); backdrop-filter: blur(14px) saturate(140%);
                border: 1px solid rgba(255,255,255,.9); box-shadow: 0 1px 2px rgba(9,44,116,.06), 0 24px 60px -12px rgba(9,44,116,.22); }
        .badge { display: inline-flex; align-items: center; justify-content: center; height: 40px; padding: 0 16px; border-radius: 999px;
                 background: linear-gradient(135deg, #092c74, #1479a8); box-shadow: 0 6px 18px -6px rgba(20,121,168,.6); }
        .badge img { height: 14px; width: auto; display: block; }
        h1 { margin: 22px 0 6px; font-size: 26px; line-height: 1.2; font-weight: 700; letter-spacing: -.02em;
             background: linear-gradient(90deg, #092c74 0%, #1479a8 45%, #7c4dff 100%); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .sub { margin: 0 0 26px; font-size: 14px; color: #566880; }
        label { display: block; font-size: 13px; font-weight: 600; color: #13233c; margin-bottom: 6px; }
        .field { margin-bottom: 16px; }
        input[type=text], input[type=password] { width: 100%; font: inherit; font-size: 15px; padding: 11px 14px; border-radius: 12px; color: #13233c;
               border: 1px solid #d5deea; background: rgba(255,255,255,.9); outline: none; transition: border-color .15s, box-shadow .15s; }
        input:focus { border-color: #25aae1; box-shadow: 0 0 0 4px rgba(37,170,225,.18); }
        button { width: 100%; margin-top: 8px; padding: 12px 16px; border: 0; border-radius: 12px; font: inherit; font-size: 15px; font-weight: 600; color: #fff; cursor: pointer;
                 background: linear-gradient(90deg, #092c74, #1479a8 55%, #25aae1); background-size: 160% 100%; background-position: 0 0;
                 box-shadow: 0 8px 20px -8px rgba(20,121,168,.7); transition: background-position .4s, transform .1s; }
        button:hover { background-position: 100% 0; }
        button:active { transform: translateY(1px); }
        button:focus-visible { outline: 3px solid rgba(37,170,225,.45); outline-offset: 2px; }
        .err { background: #fff1ed; color: #c8452c; border: 1px solid #ffd3c7; padding: 11px 14px; border-radius: 12px; font-size: 14px; margin-bottom: 18px; }
        .foot { margin-top: 22px; text-align: center; font-size: 12px; color: #8696aa; }
        @media (max-width: 420px) { .card { padding: 28px 20px 24px; border-radius: 20px; } h1 { font-size: 23px; } }
        @media (prefers-reduced-motion: reduce) { button { transition: none; } }
    </style>
</head>
<body>
    <canvas id="login-field" aria-hidden="true"></canvas>
    <main class="wrap">
        <div class="card">
            <span class="badge"><img src="../assets/images/acibadem-white-logo.webp" alt="Acıbadem" width="411" height="48"></span>
            <h1>Yönetim Paneli</h1>
            <p class="sub">Lütfen giriş bilgilerinizi giriniz.</p>

            <?php if ($error): ?>
                <div class="err" role="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="username">Kullanıcı Adı</label>
                    <input type="text" id="username" name="username" required autocomplete="username" autocapitalize="none" spellcheck="false" value="<?= htmlspecialchars($username ?? '') ?>">
                </div>
                <div class="field">
                    <label for="password">Şifre</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit">Giriş Yap</button>
            </form>
            <p class="foot">acibadem.world · Acıbadem International</p>
        </div>
    </main>
    <script src="../assets/js/login-particles.js?v=<?= @filemtime(__DIR__ . '/../assets/js/login-particles.js') ?>" defer></script>
</body>
</html>
