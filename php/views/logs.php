<?php ob_start(); ?>
<div class="pg-title">
  <h2>Activity log</h2>
  <p>Who changed what, and when.</p>
</div>
<section class="panel">
  <div class="panel-title">All activity</div>
  <div class="panel-inner">
    <?php if ($entries): ?>
    <div class="tbl-scroll">
      <table>
        <thead><tr><th>When</th><th>Who</th><th>Role</th><th>Action</th><th>Details</th></tr></thead>
        <tbody>
          <?php foreach ($entries as $row): ?>
          <tr>
            <td class="muted"><?= htmlspecialchars(substr(str_replace('T', ' ', $row['created_at']), 0, 16)) ?></td>
            <td><?= htmlspecialchars($row['full_name']) ?></td>
            <td><span class="tag"><?= htmlspecialchars($ROLES[$row['role']]) ?></span></td>
            <td><span class="tag-mono"><?= htmlspecialchars($row['action']) ?></span></td>
            <td><?= htmlspecialchars($row['details']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p class="empty">Nothing logged yet.</p>
    <?php endif; ?>
  </div>
</section>
<?php
$content = ob_get_clean();
$pageTitle = 'Activity Log';
$active = 'logs';
include __DIR__ . '/layout.php';