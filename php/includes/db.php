<?php
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pdo;
}

function init_database(): void {
    $pdo = db();
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            full_name TEXT NOT NULL,
            role TEXT NOT NULL CHECK(role IN ('manager', 'executive')),
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL
        );
        CREATE TABLE IF NOT EXISTS items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            quantity INTEGER NOT NULL DEFAULT 0
        );
        CREATE TABLE IF NOT EXISTS issues (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            item_id INTEGER NOT NULL,
            location TEXT NOT NULL,
            quantity INTEGER NOT NULL,
            issue_date TEXT NOT NULL,
            user_id INTEGER,
            FOREIGN KEY (item_id) REFERENCES items(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            action TEXT NOT NULL,
            details TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id)
        );
    ");

    $cols = $pdo->query("PRAGMA table_info(issues)")->fetchAll();
    $hasUser = false;
    foreach ($cols as $c) {
        if ($c['name'] === 'user_id') $hasUser = true;
    }
    if (!$hasUser) {
        $pdo->exec("ALTER TABLE issues ADD COLUMN user_id INTEGER REFERENCES users(id)");
    }

    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name, role, created_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'manager',
            password_hash('manager123', PASSWORD_DEFAULT),
            'System Manager',
            'manager',
            date('c'),
        ]);
    }
}