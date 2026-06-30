<?php
/** @var array $items @var array $issues @var array $stats @var string $today */
ob_start();
?>
<div class="pg-title">
  <h2>Stock & Issues</h2>
  <p>Office cupboard — track stock and issue to locations.</p>
</div>
<div class="quick-nums">
  <span><strong><?= (int)$stats['item_count'] ?></strong> item types</span>
  <span><strong><?= (int)$stats['total_stock'] ?></strong> total in cupboard</span>
  <span><strong><?= (int)$stats['issue_count'] ?></strong> issues logged</span>
</div>

<section class="panel">
  <div class="panel-title">Current stock</div>
  <div class="panel-inner">
    <?php if ($items): ?>
    <div class="tbl-scroll">
      <table>
        <thead><tr><th>Item</th><th>Qty</th><th>Update</th></tr></thead>
        <tbody>
          <?php foreach ($items as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><span class="qty-pill"><?= (int)$item['quantity'] ?></span></td>
            <td>
              <form method="post" class="inline">
                <input type="hidden" name="action" value="update_stock">
                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                <input type="number" name="quantity" value="<?= (int)$item['quantity'] ?>" min="0" class="qty-input">
                <button type="submit" class="btn btn-sm">Save</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p class="empty">Nothing in the cupboard yet.</p>
    <?php endif; ?>
    <form method="post" class="add-line">
      <input type="hidden" name="action" value="add_item">
      <input type="text" name="name" placeholder="Item name" required>
      <input type="number" name="quantity" value="0" min="0">
      <button type="submit" class="btn btn-gold">Add item</button>
    </form>
  </div>
</section>

<section class="panel">
  <div class="panel-title">Issue to location</div>
  <div class="panel-inner">
    <form method="post" id="issue-form">
      <input type="hidden" name="action" value="issue">
      <div class="issue-row">
        <div class="field"><label>Date</label><input type="date" name="issue_date" value="<?= htmlspecialchars($today) ?>" required></div>
        <div class="field"><label>Location</label><input type="text" name="location" placeholder="Site name" required></div>
      </div>
      <div class="item-block">
        <div class="row-h"><span>Item</span><span>Qty</span><span></span></div>
        <div id="item-lines">
          <div class="row">
            <select name="item_id[]" required>
              <option value="">— pick item —</option>
              <?php foreach ($items as $item): ?>
              <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['name']) ?> (<?= (int)$item['quantity'] ?> left)</option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="quantity[]" value="1" min="1" required>
            <button type="button" class="btn btn-sm btn-remove" hidden>×</button>
          </div>
        </div>
      </div>
      <div class="issue-btns">
        <button type="button" class="btn" id="add-item-line">+ another item</button>
        <button type="submit" class="btn btn-gold">Record issue</button>
      </div>
    </form>
  </div>
</section>

<template id="item-line-template">
  <div class="row">
    <select name="item_id[]" required>
      <option value="">— pick item —</option>
      <?php foreach ($items as $item): ?>
      <option value="<?= (int)$item['id'] ?>"><?= htmlspecialchars($item['name']) ?> (<?= (int)$item['quantity'] ?> left)</option>
      <?php endforeach; ?>
    </select>
    <input type="number" name="quantity[]" value="1" min="1" required>
    <button type="button" class="btn btn-sm btn-remove">×</button>
  </div>
</template>

<section class="panel">
  <div class="panel-title">Issue record</div>
  <div class="panel-inner">
    <?php if ($issues): ?>
    <div class="tbl-scroll">
      <table>
        <thead><tr><th>Date</th><th>Location</th><th>Item</th><th>Qty</th><th>By</th></tr></thead>
        <tbody>
          <?php foreach ($issues as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['issue_date']) ?></td>
            <td><span class="loc"><?= htmlspecialchars($row['location']) ?></span></td>
            <td><?= htmlspecialchars($row['item_name']) ?></td>
            <td><?= (int)$row['quantity'] ?></td>
            <td class="muted"><?= htmlspecialchars($row['issued_by']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <p class="empty">No issues yet.</p>
    <?php endif; ?>
  </div>
</section>
<?php
$content = ob_get_clean();
$pageTitle = 'Stock';
$active = 'stock';
include __DIR__ . '/layout.php';