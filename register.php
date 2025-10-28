<?php
// register.php
session_start();
require_once __DIR__ . '/db.php';

function uuid_v4() {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function esc($s){ return htmlspecialchars($s, ENT_QUOTES); }

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        $_SESSION['flash'] = 'Podaj nazwę użytkownika.';
        header('Location: ./register.php'); exit;
    }
    $pdo = get_pdo();
    $token = uuid_v4();
    $stmt = $pdo->prepare('INSERT INTO users (name, token, created_at) VALUES (?, ?, ?)');
    $stmt->execute([$name, $token, date('c')]);

    $filename = 'token_' . preg_replace('/[^a-z0-9_-]/i','', $name) . '_' . time() . '.txt';
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "TOKEN: " . $token . "\n" . "USER: " . $name . "\n" . "NOTE: This token will NOT be visible on the website. Keep it safe.\n";
    exit;
}

?><!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registration — Token Auth</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
  <header>
    <div class="brand">
      <div class="logo">TL</div>
      <div>
        <h1>Registration</h1>
        <p class="lead">eseses</p>
      </div>
    </div>
    <nav>
      <a class="muted" href="login.php">Login</a>
    </nav>
  </header>

  <?php if ($flash): ?><div class="card flash"><?= esc($flash) ?></div><?php endif; ?>

  <div class="grid">
    <div class="card">
      <h2>Create an account</h2>
      <p class="muted">Enter your username</p>
      <form method="post">
        <label class="small">Name</label><br>
        <input name="name" class="login-input" placeholder="etc. matrix" required>
        <div style="height:12px"></div>
        <button class="btn">Register and download the token</button>
      </form>
    </div>

    <aside class="card">
      <h3>Attention</h3>
      <p class="muted">The token will not be visible on any website. Download the token file and keep it in a safe place.</p>
      <p class="muted small">If you lose your token, the administrator can generate a new entry or you can register a new account.</p>
    </aside>
  </div>

  <footer style="margin-top:28px;color:#666;font-size:13px;text-align:center">
    © <?= date('Y') ?> RpexonProject — Token-based Access Panel
  </footer>
</div>
</body>
</html>
