<?php
function get_pdo() {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dbFile = __DIR__ . '/data.sqlite';
    try {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            token TEXT UNIQUE NOT NULL,
            role TEXT DEFAULT 'user',
            permissions TEXT DEFAULT '[]',
            created_at TEXT NOT NULL
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS purchases (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            product_key TEXT NOT NULL,
            amount REAL DEFAULT 0,
            currency TEXT DEFAULT 'EUR',
            invoice_id TEXT,
            status TEXT DEFAULT 'pending',
            created_at TEXT NOT NULL,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )");

        return $pdo;
    } catch (Exception $e) {
        die('DB error: ' . htmlspecialchars($e->getMessage()));
    }
}
?>
