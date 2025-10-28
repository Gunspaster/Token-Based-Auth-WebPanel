<?php
session_start();
require_once __DIR__ . '/db.php';
function esc($s){ return htmlspecialchars($s, ENT_QUOTES); }

if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
$u = $_SESSION['user'];
if ($u['perm'] !== 'admin') { die('Access denied. Admins only.'); }

$pdo = get_pdo();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if (isset($_POST['approve']) && isset($_POST['invoice'])) {
    $invoice_id = $_POST['invoice'];
    $stmt = $pdo->prepare('SELECT * FROM purchases WHERE invoice_id = ? LIMIT 1');
    $stmt->execute([$invoice_id]);
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($purchase) {
        $pdo->prepare('UPDATE purchases SET status = "paid" WHERE invoice_id = ?')->execute([$invoice_id]);
        if ($purchase['product_key'] === 'spoofer') {
            $pdo->prepare('UPDATE users SET perm = "spoofer" WHERE id = ?')->execute([$purchase['user_id']]);
        } elseif ($purchase['product_key'] === 'Rpexoncheat') {
            $pdo->prepare('UPDATE users SET perm = "rpexon" WHERE id = ?')->execute([$purchase['user_id']]);
        }
        $_SESSION['flash'] = '✅ Purchase approved and permission granted!';
    } else {
        $_SESSION['flash'] = '❌ Invoice not found.';
    }
    header('Location: admin.php'); exit;
}

$users = $pdo->query('SELECT id, name, perm, token FROM users ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

$pending = $pdo->query('SELECT p.*, u.name FROM purchases p JOIN users u ON u.id = p.user_id WHERE p.status = "pending" ORDER BY p.created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Panel — RpexonProject</title>
<link rel="stylesheet" href="style.css">
<style>
.admin-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px}
table{width:100%;border-collapse:collapse}
th,td{padding:8px 10px;border-bottom:1px solid #333;text-align:left}
th{background:#111;}
td small{color:#777;}
form.inline{display:inline}
</style>
</head>
<body>
<div class="wrap">
<header>
  <div class="brand"><div class="logo">⚙️</div><div><h1>Admin Panel</h1><p class="lead">Manage users & purchases</p></div></div>
  <nav><a class="muted" href="dashboard.php">Dashboard</a> · <a class="muted" href="price.php">Shop</a></nav>
</header>

<?php if ($flash): ?><div class="card flash"><?= esc($flash) ?></div><?php endif; ?>

<div class="admin-grid">
  <div class="card">
    <h2>👥 Users</h2>
    <table>
      <tr><th>ID</th><th>Name</th><th>Permission</th><th>Token</th></tr>
      <?php foreach ($users as $user): ?>
        <tr>
          <td><?= esc($user['id']) ?></td>
          <td><?= esc($user['name']) ?></td>
          <td><?= esc($user['perm']) ?></td>
          <td><small><?= esc($user['token']) ?></small></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card">
    <h2>💰 Pending Purchases</h2>
    <?php if (!$pending): ?>
      <p class="muted">No pending purchases.</p>
    <?php else: ?>
      <table>
        <tr><th>User</th><th>Product</th><th>Amount</th><th></th></tr>
        <?php foreach ($pending as $p): ?>
          <tr>
            <td><?= esc($p['name']) ?></td>
            <td><?= esc($p['product_key']) ?></td>
            <td><?= esc($p['amount']) . ' ' . esc($p['currency']) ?></td>
            <td>
              <form method="post" class="inline">
                <input type="hidden" name="invoice" value="<?= esc($p['invoice_id']) ?>">
                <button name="approve" class="btn small">Mark as Paid</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  </div>
</div>

<footer style="margin-top:28px;color:var(--muted);font-size:13px">Admin panel — RpexonProject.</footer>
</div>
</body>
</html>
