<?php
require_once __DIR__ . '/auth/bootstrap.php';
// Always display the login screen at the project root, even with an active session.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="theme-color" content="#090d1d">
  <meta name="description" content="Sign in to Cyberweb Studio dashboard.">
  <title>Sign in · Cyberweb Studio</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/login.css">
  <script src="js/login.js" defer></script>
<style>.server-alert{background:#4a172b;color:#ffdae0;padding:12px 15px;border:1px solid #ae5271;border-radius:12px;margin:10px 0;font-size:14px}</style>
</head>
<body>
  <div class="page-noise" aria-hidden="true"></div>
  <main class="shell">
    <section class="showcase" aria-label="Cyberweb platform preview">
      <div class="ambient ambient-one" aria-hidden="true"></div><div class="ambient ambient-two" aria-hidden="true"></div>
      <div class="showcase-top">
        <a class="brand" href="index.php" aria-label="Cyberweb Studio home">
          <span class="brand-icon" aria-hidden="true"><svg viewBox="0 0 44 44" fill="none"><path d="M22 4 39 13.5v17L22 40 5 30.5v-17L22 4Z" stroke="currentColor" stroke-width="2.5"/><path d="m13 16 9-5 9 5v12l-9 5-9-5V16Z" fill="currentColor" opacity=".3"/><path d="m13 16 9 6 9-6M22 22v11" stroke="currentColor" stroke-width="2.5"/></svg></span>
          <span>cyberweb<span class="brand-dot">.</span><small>STUDIO CLOUD</small></span>
        </a>
        <span class="edition"><span class="edition-light"></span> PLATFORM 2.0</span>
      </div>
      <div class="hero-copy">
        <p class="eyebrow"><span class="eyebrow-line"></span> THE NEXT WORKSPACE</p>
        <h1>One space.<br><span>Infinite</span> possibilities.</h1>
        <p class="lead">Create, manage, and launch exceptional digital experiences from a workspace built for momentum.</p>
      </div>
      <div class="art-stage" aria-hidden="true">
        <div class="orbit orbit-a"></div><div class="orbit orbit-b"></div>
        <div class="glow-sphere"></div>
        <div class="mini-card mini-card-left"><span class="card-label">LIVE PROJECTS</span><strong>24 <span class="mini-up">↗ 12%</span></strong><div class="spark"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></div></div>
        <div class="preview-window">
          <div class="window-top"><span class="window-dots"><i></i><i></i><i></i></span><span>Overview / Workspace</span><span class="window-more">···</span></div>
          <div class="window-main"><div class="side-icons"><span class="active">▦</span><span>◈</span><span>◉</span><span>▤</span><span>⚙</span></div><div class="preview-content"><div class="preview-heading"><div><span class="muted">MONDAY, OCT 19</span><strong>Good morning, team <span>✳</span></strong></div><div class="avatar-stack"><b>A</b><b>S</b><b>+</b></div></div><div class="metric-row"><div class="metric"><span>Active websites</span><strong>32</strong><small>↗ +8.2%</small></div><div class="metric"><span>Visitors this month</span><strong>84.2k</strong><small>↗ +18.6%</small></div></div><div class="chart-panel"><div class="chart-head"><span>Performance overview</span><span class="muted">Last 7 days ⌄</span></div><svg viewBox="0 0 320 105" preserveAspectRatio="none"><defs><linearGradient id="area" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#a58cff" stop-opacity=".38"/><stop offset="1" stop-color="#a58cff" stop-opacity="0"/></linearGradient></defs><path d="M0 90C25 79 26 62 48 68S78 88 100 62 135 81 155 43 184 58 200 31 231 50 251 21 285 44 320 10V105H0Z" fill="url(#area)"/><path d="M0 90C25 79 26 62 48 68S78 88 100 62 135 81 155 43 184 58 200 31 231 50 251 21 285 44 320 10" stroke="#b19aff" stroke-width="3" fill="none" stroke-linecap="round"/></svg><div class="axis"><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span></div></div></div></div>
        </div>
        <div class="mini-card mini-card-right"><div class="success-icon">✓</div><div><strong>Deployment complete</strong><span>Your site is now live</span></div><span class="check-glow"></span></div>
      </div>
      <div class="showcase-bottom"><span><span class="sparkle">✧</span> Built for creators. Designed for scale.</span><span>© <span id="showcaseYear">2026</span> Cyberweb Studio</span></div>
    </section>
    <section class="signin" aria-labelledby="login-title">
      <div class="signin-nav"><div class="signin-mobile-brand"><span class="brand-icon">⬡</span> cyberweb<span class="brand-dot">.</span></div><span class="secure-label"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.8"/></svg> SECURE ACCESS</span></div>
      <div class="login-wrap">
        <div class="welcome-icon" aria-hidden="true">✦</div>
        <p class="form-eyebrow">WELCOME BACK</p>
        <h2 id="login-title">Sign in to your<br><span>workspace.</span></h2>
        <p class="subhead">Your projects are waiting. Enter your credentials to continue.</p>
        <form id="loginForm" action="auth/login_process.php" method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
          <?php if ($msg = pull_flash()): ?><p role="alert" class="server-alert"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
          <div class="input-group"><label for="email">Username</label><div class="input-wrap"><svg class="field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="m4.5 7 7.5 6 7.5-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg><input id="email" name="username" type="text" placeholder="admin" autocomplete="username" autocapitalize="none" spellcheck="false" required aria-describedby="emailError"></div><span class="field-error" id="emailError" aria-live="polite"></span></div>
          <div class="input-group"><div class="label-row"><label for="password">Password</label><a class="text-link" href="#" id="forgotLink">Forgot password?</a></div><div class="input-wrap"><svg class="field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="5" y="10" width="14" height="11" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7"/></svg><input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required aria-describedby="passwordError"><button type="button" class="eye-toggle" id="togglePassword" aria-label="Show password" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M2 12s3.7-6 10-6 10 6 10 6-3.7 6-10 6S2 12 2 12Z" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.7"/></svg></button></div><span class="field-error" id="passwordError" aria-live="polite"></span></div>
          <div class="options-row"><label class="remember"><input type="checkbox" name="remember" value="1"><span class="custom-check" aria-hidden="true">✓</span> Remember me</label><span class="session-caption">Private session <span class="green-dot"></span></span></div>
          <button type="submit" class="submit-btn" id="submitBtn"><span>Access dashboard</span><span class="button-arrow">↗</span></button>
          <p class="form-status" id="formStatus" role="status" aria-live="polite"></p>
        </form>
        <div class="form-divider"><span>AUTHORIZED ACCESS ONLY</span></div>
        <p class="contact-text">Admin access is managed by your workspace administrator.</p>
        <div class="help-panel" id="helpPanel" hidden><strong>Need access assistance?</strong><p>Ask your dashboard administrator to reset your password or create an account. The email address in the contact link is a placeholder—replace it with your actual support address.</p><button type="button" id="closeHelp">Got it</button></div>
      </div>
      <footer class="signin-footer"><span>© <span id="formYear">2026</span> Cyberweb Studio</span><div><span class="system-dot"></span> All systems operational</div></footer>
    </section>
  </main>
</body>
</html>
