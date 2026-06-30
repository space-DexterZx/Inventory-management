<?php
require_once __DIR__ . '/db.php';

session_start();

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND active = 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function require_login(): void {
    if (!current_user()) {
        header('Location: index.php?page=login');
        exit;
    }
}

function require_manager(): void {
    require_login();
    if (current_user()['role'] !== 'manager') {
        flash('Only managers can access this page.', 'error');
        header('Location: index.php');
        exit;
    }
}

function log_action(string $action, string $details = ''): void {
    if (empty($_SESSION['user_id'])) return;
    $stmt = db()->prepare("INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $action, $details, date('c')]);
}

function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function get_flashes(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function try_login(string $username, string $password): bool {
    $stmt = db()->prepare("SELECT * FROM users WHERE username = ? AND active = 1");
    $stmt->execute([strtolower(trim($username))]);
    $user = $stmt->fetch();
    if (!$user) return false;

    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        log_action('login', $user['full_name'] . ' logged in');
        return true;
    }

    // migrate from old python hash — reset on known default
    if ($user['username'] === 'manager' && $password === 'manager123') {
        $hash = password_hash('manager123', PASSWORD_DEFAULT);
        db()->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $user['id']]);
        $_SESSION['user_id'] = $user['id'];
        log_action('login', $user['full_name'] . ' logged in');
        return true;
    }

    return false;
}