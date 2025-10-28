<?php
// login.php
session_start();
require_once __DIR__ . '/db.php';
function esc($s){ return htmlspecialchars($s, ENT_QUOTES); }

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    if ($token === '') {
        $_SESSION['flash'] = 'Wklej token.';
        header('Location: ./login.php'); exit;
    }
    $pdo = get_pdo();
    $s = $pdo->prepare('SELECT id,name,token FROM users WHERE token = ?');
    $s->execute([$token]);
    $u = $s->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $_SESSION['user'] = $u;
        header('Location: dashboard.php'); exit;
    } else {
        $_SESSION['flash'] = 'Niepoprawny token.';
        header('Location: ./login.php'); exit;
    }
}
?><!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — Token Auth</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
  <header>
    <div class="brand">
      <div class="logo">TL</div>
      <div>
        <h1>Login</h1>
        <p class="lead">Token-only login</p>
      </div>
    </div>
    <nav>
      <a class="muted" href="register.php">Registration</a>
    </nav>
  </header>

  <?php if ($flash): ?><div class="card flash"><?= esc($flash) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <h2>Login</h2>
      <p class="muted">Paste the token (received as a file during registration) and log in.</p>
      <form method="post">
        <label class="small">Token</label><br>
        <input name="token" class="login-input" placeholder="Token: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required>
        <div style="height:12px"></div>
        <button class="btn">Log in</button>
      </form>
    </div>

    <aside class="card">
      <h3>Don't show your token to anyoneu</h3>
      <p class="muted small">The token works like a password – no one but you should have it.</p>
    </aside>
  </div>

  <footer style="margin-top:28px;color:#666;font-size:13px;text-align:center">
    © <?= date('Y') ?> RpexonProject — Token-based Access Panel
  </footer>
</div>
</body>
</html>
