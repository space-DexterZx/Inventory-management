<?php ob_start(); ?>
<div class="pg-title">
  <h2>Members</h2>
  <p>People who can log in to this system.</p>
</div>
<section class="panel">
  <div class="panel-title">Current team</div>
  <div class="panel-inner">
    <?php if ($members): ?>
    <div class="tbl-scroll">
      <table>
        <thead><tr><th>Name</th><th>Username</th><th>Role</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($members as $m): ?>
          <tr>
            <td><?= htmlspecialchars($m['full_name']) ?><?php if ($m['id'] == $currentUser['id']): ?> <span class="muted">(you)</span><?php endif; ?></td>
            <td><?= htmlspecialchars($m['username']) ?></td>
            <td><span class="tag"><?= htmlspecialchars($ROLES[$m['role']]) ?></span></td>
            <td>
              <?php if ($m['id'] != $currentUser['id']): ?>
              <form method="post" onsubmit="return confirm('Remove this person?');">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                <button type="submit" class="btn btn-sm btn-red">Remove</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</section>
<section class="panel">
  <div class="panel-title">Add someone</div>
  <div class="panel-inner">
    <form method="post" class="member-grid">
      <input type="hidden" name="action" value="add">
      <div class="field"><label>Full name</label><input type="text" name="full_name" required></div>
      <div class="field"><label>Username</label><input type="text" name="username" required></div>
      <div class="field"><label>Password</label><input type="password" name="password" required></div>
      <div class="field">
        <label>Role</label>
        <select name="role" required>
          <option value="executive">Executive Administration</option>
          <option value="manager">Manager</option>
        </select>
      </div>
      <button type="submit" class="btn btn-gold">Add member</button>
    </form>
  </div>
</section>
<?php
$content = ob_get_clean();
$pageTitle = 'Members';
$active = 'members';
include __DIR__ . '/layout.php';