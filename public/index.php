<?php
require_once __DIR__ . '/../php/includes/config.php';
require_once __DIR__ . '/../php/includes/db.php';
require_once __DIR__ . '/../php/includes/auth.php';
require_once __DIR__ . '/../php/includes/JavaClient.php';

init_database();

$page = $_GET['page'] ?? 'stock';
$user = current_user();

// Logout
if ($page === 'logout') {
    if ($user) log_action('logout', $user['full_name'] . ' logged out');
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

// Login
if ($page === 'login') {
    if ($user) { header('Location: index.php'); exit; }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (try_login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
            flash('Welcome back.', 'success');
            header('Location: index.php');
            exit;
        }
        flash('Invalid username or password.', 'error');
    }
    include __DIR__ . '/../php/views/login.php';
    exit;
}

require_login();
$user = current_user();

// Stock actions — Java handles inventory logic, PHP handles auth
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($page, ['stock', ''], true)) {
    $action = $_POST['action'] ?? '';
    $uid = (int)$user['id'];

    if ($action === 'add_item') {
        $res = java_post('/api/add-item', [
            'name' => $_POST['name'] ?? '',
            'quantity' => (int)($_POST['quantity'] ?? 0),
            'user_id' => $uid,
        ]);
        flash($res['ok'] ? ($res['message'] ?? 'Added.') : ($res['error'] ?? 'Failed.'), $res['ok'] ? 'success' : 'error');
    }

    if ($action === 'update_stock') {
        $res = java_post('/api/update-stock', [
            'item_id' => (int)($_POST['item_id'] ?? 0),
            'quantity' => (int)($_POST['quantity'] ?? 0),
            'user_id' => $uid,
        ]);
        flash($res['ok'] ? ($res['message'] ?? 'Updated.') : ($res['error'] ?? 'Failed.'), $res['ok'] ? 'success' : 'error');
    }

    if ($action === 'issue') {
        $itemIds = array_map('intval', $_POST['item_id'] ?? []);
        $qtys = array_map('intval', $_POST['quantity'] ?? []);
        $res = java_post('/api/issue', [
            'location' => $_POST['location'] ?? '',
            'issue_date' => $_POST['issue_date'] ?? '',
            'user_id' => $uid,
            'item_ids' => $itemIds,
            'quantities' => $qtys,
        ]);
        flash($res['ok'] ? ($res['message'] ?? 'Issued.') : ($res['error'] ?? 'Failed.'), $res['ok'] ? 'success' : 'error');
    }

    header('Location: index.php');
    exit;
}

// Members (PHP)
if ($page === 'members') {
    require_manager();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $act = $_POST['action'] ?? '';
        if ($act === 'add') {
            $username = strtolower(trim($_POST['username'] ?? ''));
            $full = trim($_POST['full_name'] ?? '');
            $pass = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? '';
            if (!$username || !$full || !$pass || !isset($ROLES[$role])) {
                flash('Fill in all fields.', 'error');
            } else {
                try {
                    $stmt = db()->prepare("INSERT INTO users (username, password_hash, full_name, role, created_at) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$username, password_hash($pass, PASSWORD_DEFAULT), $full, $role, date('c')]);
                    log_action('add_member', "Added $full ({$ROLES[$role]}) — username: $username");
                    flash("Added $full.", 'success');
                } catch (PDOException $e) {
                    flash('That username already exists.', 'error');
                }
            }
        }
        if ($act === 'remove') {
            $mid = (int)($_POST['id'] ?? 0);
            if ($mid === (int)$user['id']) {
                flash('You cannot remove your own account.', 'error');
            } else {
                $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$mid]);
                $m = $stmt->fetch();
                if ($m) {
                    db()->prepare("UPDATE users SET active = 0 WHERE id = ?")->execute([$mid]);
                    log_action('remove_member', "Removed {$m['full_name']} ({$ROLES[$m['role']]})");
                    flash("Removed {$m['full_name']}.", 'success');
                }
            }
        }
        header('Location: index.php?page=members');
        exit;
    }
    $members = db()->query("SELECT * FROM users WHERE active = 1 ORDER BY role, full_name")->fetchAll();
    $currentUser = $user;
    include __DIR__ . '/../php/views/members.php';
    exit;
}

// Logs (PHP)
if ($page === 'logs') {
    require_manager();
    $entries = db()->query("
        SELECT a.created_at, a.action, a.details, u.full_name, u.role
        FROM audit_logs a JOIN users u ON a.user_id = u.id
        ORDER BY a.created_at DESC, a.id DESC
    ")->fetchAll();
    $currentUser = $user;
    include __DIR__ . '/../php/views/logs.php';
    exit;
}

// Stock page (default) — data from Java API
$itemsRes = java_get('/api/items');
$issuesRes = java_get('/api/issues');
$items = $itemsRes['items'] ?? [];
$issues = $issuesRes['issues'] ?? [];
$stats = [
    'item_count' => count($items),
    'total_stock' => array_sum(array_column($items, 'quantity')),
    'issue_count' => count($issues),
];
$today = date('Y-m-d');
$currentUser = $user;
include __DIR__ . '/../php/views/stock.php';