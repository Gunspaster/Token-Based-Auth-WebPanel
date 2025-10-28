<?php
session_start();
require_once __DIR__ . '/db.php';
function esc($s){ return htmlspecialchars($s, ENT_QUOTES); }

if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
$u = $_SESSION['user'];
$pdo = get_pdo();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$products = [
    'spoofer' => [
        'title' => 'Rpexon Spoofer',
        'price' => 0.00,
        'currency' => 'USD',
        'duration' => 'Lifetime',
        'description' => 'A reliable HWID spoofer designed for compatibility and ease of use. After purchase you will be granted access to download the spoofer. Manual verification required.',
        'previews' => ['preview1.jpg','preview2.jpg','preview3.jpg']
    ],
];

$rpexon_tiers = [
    'Rpexoncheat_1d' => [
        'title' => 'RpexonCheat — 1 Day Access',
        'price_usd' => 10,
        'currency' => 'USD',
        'duration' => '1 day',
        'description' => 'Temporary 24-hour access to RpexonCheat. Ideal for testing features and short-term use. Note: RpexonCheat core files are currently marked as SOON; purchasing creates a pending invoice and will be available when released.'
    ],
    'rpexoncheat_1m' => [
        'title' => 'RpexonCheat — 1 Month Access',
        'price_usd' => 30,
        'currency' => 'USD',
        'duration' => '30 days',
        'description' => 'Full 30-day access to RpexonCheat (when released). Best for regular users who want a monthly subscription-like access. Manual confirmation required by admin.'
    ],
    'rpexoncheat_lifetime' => [
        'title' => 'RpexonCheat — Lifetime Access',
        'price_usd' => 50,
        'currency' => 'USD',
        'duration' => 'lifetime',
        'description' => 'One-time purchase granting lifetime access to RpexonCheat (when released). Ideal for long-term users. Manual confirmation required by admin.'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product'])) {
    $product_key = $_POST['product'];

    if (isset($products[$product_key])) {
        $prod = $products[$product_key];
        $amount = $prod['price'];
        $currency = $prod['currency'];
        $stored_product_key = $product_key;
        $display_title = $prod['title'];
    } elseif (isset($rpexon_tiers[$product_key])) {
        $prod = $rpexon_tiers[$product_key];
        $amount = $prod['price_usd'];
        $currency = $prod['currency'];
        $stored_product_key = $product_key;
        $display_title = $prod['title'];
    } else {
        $_SESSION['flash'] = 'Unknown product.';
        header('Location: price.php'); exit;
    }

    $invoice_id = bin2hex(random_bytes(8));
    $stmt = $pdo->prepare('INSERT INTO purchases (user_id, product_key, amount, currency, invoice_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$u['id'], $stored_product_key, $amount, $currency, $invoice_id, 'pending', date('c')]);

    header('Location: price.php?invoice=' . $invoice_id);
    exit;
}

$invoice = null;
$invoice_product_meta = null;
if (isset($_GET['invoice'])) {
    $stmt = $pdo->prepare('SELECT p.*, u.name FROM purchases p JOIN users u ON u.id = p.user_id WHERE invoice_id = ? LIMIT 1');
    $stmt->execute([$_GET['invoice']]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($invoice) {
        $pk = $invoice['product_key'];
        if (isset($products[$pk])) $invoice_product_meta = $products[$pk];
        elseif (isset($rpexon_tiers[$pk])) $invoice_product_meta = $rpexon_tiers[$pk];
    }
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Shop — RpexonProject</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="wrap">
  <header>
    <div class="brand"><div class="logo">TL</div><div><h1>Shop</h1><p class="lead">Purchase access to rpexon products. Payments are handled via crypto or fiat instructions (manual confirmation by admin).</p></div></div>
    <nav><a class="muted" href="dashboard.php">Dashboard</a> · <a class="muted" href="?">Refresh</a></nav>
  </header>

  <?php if ($flash): ?><div class="card flash"><?= esc($flash) ?></div><?php endif; ?>

  <?php if ($invoice): ?>
    <div class="card">
      <h2>Invoice #<?= esc($invoice['invoice_id']) ?></h2>
      <?php if ($invoice_product_meta): ?>
        <p class="muted">Product: <strong><?= esc($invoice_product_meta['title'] ?? $invoice['product_key']) ?></strong></p>
      <?php else: ?>
        <p class="muted">Product: <strong><?= esc($invoice['product_key']) ?></strong></p>
      <?php endif; ?>
      <p class="muted">Amount: <strong><?= esc($invoice['amount']) ?> <?= esc($invoice['currency']) ?></strong></p>
      <p class="muted">Status: <strong><?= esc($invoice['status']) ?></strong></p>
      <hr>
      <h3>Payment instructions</h3>

      <?php
      if ($invoice['currency'] === 'BTC') {
        ?>
        <p class="muted">Send <strong><?= esc($invoice['amount']) ?> <?= esc($invoice['currency']) ?></strong> to the following Bitcoin address:</p>
        <div class="token">39yAy913j12h2oEfLx4uXioJYZs613GZaA</div>
        <p class="muted small">After sending, please wait for confirmations. Admin will manually verify the payment and mark your invoice as <em>paid</em>, at which point access will be granted.</p>
        <?php
      } else {
        ?>
        <p class="muted">Amount due: <strong><?= esc($invoice['amount']) ?> <?= esc($invoice['currency']) ?></strong></p>
        <p class="muted">For USD payments, please use your preferred crypto-fiat on-ramp or contact admin for alternative payment instructions.</p>
        <p class="muted small">This is a manual flow — admin confirmation required.</p>
        <?php
      }
      ?>

      <?php if (!empty($invoice_product_meta) && isset($invoice_product_meta['description'])): ?>
        <hr>
        <h4>Product details</h4>
        <p class="muted"><?= esc($invoice_product_meta['description']) ?></p>
      <?php endif; ?>

      <div style="height:10px"></div>
      <a class="btn" href="dashboard.php">Back to dashboard</a>
    </div>
  <?php else: ?>

    <div class="grid">
      <div class="card">
        <h2>Products</h2>

        <div style="margin-bottom:18px">
          <h3><?= esc($products['spoofer']['title']) ?></h3>
          <p class="muted"><?= esc($products['spoofer']['description']) ?></p>
          <p class="muted">Price: <?= esc($products['spoofer']['price']) ?> <?= esc($products['spoofer']['currency']) ?></p>

          <div style="display:flex;gap:8px;margin-top:10px;">
            <?php foreach ($products['spoofer']['previews'] as $img): 
              $imgPath = __DIR__ . '/' . $img;
              if (file_exists($imgPath)): ?>
                <img src="<?= esc($img) ?>" alt="Preview" style="width:120px;height:80px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,0.04)">
              <?php else: ?>
                <div style="width:120px;height:80px;border-radius:8px;background:rgba(255,255,255,0.02);display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:12px">preview</div>
              <?php endif;
            endforeach; ?>
          </div>

          <div style="height:10px"></div>
          <form method="post" style="display:inline">
            <input type="hidden" name="product" value="spoofer">
            <button class="btn">Buy Spoofer — <?= esc($products['spoofer']['price']) ?> <?= esc($products['spoofer']['currency']) ?></button>
          </form>
        </div>

        <hr style="margin:18px 0">

        <h3>RpexonCheat (SOON)</h3>
        <p class="muted">RpexonCheat is a premium feature set targeted at advanced users. The release is scheduled as <em>SOON</em>. Purchasing now will create an invoice and reserve your access once the product is available. Manual confirmation by the administrator is required to grant access.</p>

        <div style="margin-top:12px">
          <?php foreach ($rpexon_tiers as $key => $tier): ?>
            <div style="margin-bottom:14px;padding:12px;border-radius:10px;background:rgba(255,255,255,0.015)">
              <strong style="display:block;font-size:16px"><?= esc($tier['title']) ?></strong>
              <p class="muted small" style="margin:6px 0"><?= esc($tier['description']) ?></p>
              <p class="muted">Price: <strong><?= esc($tier['price_usd']) ?> <?= esc($tier['currency']) ?></strong> — Duration: <strong><?= esc($tier['duration']) ?></strong></p>
              <form method="post" style="display:inline">
                <input type="hidden" name="product" value="<?= esc($key) ?>">
                <button class="btn" <?php if (strpos($key,'rpexoncheat') !== false) echo ''; ?>>Purchase</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>

      </div>

      <aside class="card">
        <h3>Important information</h3>
        <p class="muted small">
          • This shop creates invoices and expects manual verification by the administrator. Automatic blockchain polling / payment gateway integration is not included. <br>
          • Spoofer downloads are granted after payment confirmation by the admin. <br>
          • RpexonCheat is currently marked as <strong>SOON</strong> — purchases reserve access and will be activated when the product is released. <br>
          • Always keep your token safe; it is the only login method. The site never displays tokens in HTML.
        </p>
      </aside>
    </div>

  <?php endif; ?>

  <footer style="margin-top:28px;color:#666;font-size:13px;text-align:center">
    © <?= date('Y') ?> RpexonProject — Token-based Access Panel
  </footer>
</div>
</body>
</html>
