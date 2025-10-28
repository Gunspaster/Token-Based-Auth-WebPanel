<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user']['perm']) && isset($_SESSION['user']['role'])) {
    $_SESSION['user']['perm'] = $_SESSION['user']['role'];
} elseif (!isset($_SESSION['user']['perm'])) {
    $_SESSION['user']['perm'] = 'user';
}

function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES); }

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$u = $_SESSION['user'];
$pdo = get_pdo();

$stmt = $pdo->prepare('SELECT perm FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$u['id']]);
$db_perm = $stmt->fetchColumn();
if ($db_perm) {
    $_SESSION['user']['perm'] = $db_perm;
}
?>
<!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>User panel</title>
<link rel="stylesheet" href="style.css">
<style>
.wrap{max-width:800px;margin:auto;padding:20px}
.card{background:#111;padding:20px;border-radius:12px;margin-top:20px;box-shadow:0 0 10px rgba(0,0,0,0.4)}
.btn{background:#333;color:#fff;border:none;padding:10px 18px;border-radius:8px;cursor:pointer}
.btn:hover{background:#444}
.muted{color:#888}
.token{font-family:monospace;background:#222;padding:8px;border-radius:8px}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <h1>User panel</h1>
    <p class="muted">Hi, <?= esc($u['name'] ?? '') ?> — Your permissions: <strong><?= esc($_SESSION['user']['perm']) ?></strong></p>
    <nav>
      <a href="dashboard.php">Home</a> · 
      <a href="price.php">Shop</a> · 
      <?php if ($_SESSION['user']['perm'] === 'admin'): ?>
        <a href="admin.php">Admin</a> ·
      <?php endif; ?>
      <a href="logout.php">Logout</a>
    </nav>
  </header>

  <div class="card">
    <h2>🎮 RpexonCheat</h2>
    <p class="muted">Our premium FiveM cheat — <strong>SOON</strong>.</p>
    <?php if ($_SESSION['user']['perm'] === 'rpexon' || $_SESSION['user']['perm'] === 'admin'): ?>
      <button class="btn" disabled>Download (Coming soon)</button>
    <?php else: ?>
      <p class="muted">You don't have access to this yet. Please purchase it in the shop.</p>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>🛡️ Spoofer</h2>
    <p class="muted">HWID Spoofer — hide or reset your hardware ID safely.</p>
    <?php if ($_SESSION['user']['perm'] === 'spoofer' || $_SESSION['user']['perm'] === 'admin'): ?>
      <a class="btn" href="downloads/rpexon.zip">Download Spoofer</a>
    <?php else: ?>
      <p class="muted">You don't have access to the Spoofer yet. Purchase it in the shop to unlock.</p>
    <?php endif; ?>
  </div>

  <footer style="margin-top:28px;color:#666;font-size:13px;text-align:center">
    © <?= date('Y') ?> RpexonProject — Token-based Access Panel
  </footer>
</div>
</body>
</html>
