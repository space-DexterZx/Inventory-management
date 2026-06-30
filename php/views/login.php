<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in — Nextgen Shield</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
  <div class="login-box">
    <img src="assets/img/logo.png" alt="Nextgen Shield">
    <h1>Nextgen Shield</h1>
    <p class="sub">Stationery Management System</p>
    <?php foreach (get_flashes() as $f): ?>
      <div class="flash <?= htmlspecialchars($f['type']) ?>"><?= htmlspecialchars($f['msg']) ?></div>
    <?php endforeach; ?>
    <form method="post" action="index.php?page=login">
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" required autofocus>
      </div>
      <div class="field">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-gold btn-full">Sign in</button>
    </form>
    <p class="hint">Manager and Executive Administration logins</p>
  </div>
</body>
</html>