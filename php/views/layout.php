<?php
/** @var string $pageTitle */
/** @var string $content */
/** @var ?array $currentUser */
/** @var array $ROLES */
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?> — Nextgen Shield</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="shell">
    <aside class="side">
      <div class="side-top">
        <img src="assets/img/logo.png" alt="Nextgen Shield">
        <h1>NEXTGEN SHIELD</h1>
        <p>Stationery Management</p>
      </div>
      <?php if ($currentUser): ?>
      <nav class="side-links">
        <a href="index.php" class="<?= ($active ?? '') === 'stock' ? 'on' : '' ?>">Stock & Issues</a>
        <?php if ($currentUser['role'] === 'manager'): ?>
        <a href="index.php?page=members" class="<?= ($active ?? '') === 'members' ? 'on' : '' ?>">Members</a>
        <a href="index.php?page=logs" class="<?= ($active ?? '') === 'logs' ? 'on' : '' ?>">Activity Log</a>
        <?php endif; ?>
      </nav>
      <div class="side-foot">
        <div class="who"><?= htmlspecialchars($currentUser['full_name']) ?></div>
        <div class="role"><?= htmlspecialchars($ROLES[$currentUser['role']]) ?></div>
        <a href="index.php?page=logout">Sign out</a>
      </div>
      <?php endif; ?>
    </aside>
    <div class="work">
      <?php foreach (get_flashes() as $f): ?>
        <div class="flash <?= htmlspecialchars($f['type']) ?>"><?= htmlspecialchars($f['msg']) ?></div>
      <?php endforeach; ?>
      <?= $content ?>
    </div>
  </div>
  <script src="assets/js/app.js"></script>
</body>
</html>