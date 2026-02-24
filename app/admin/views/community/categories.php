<?php
// Variables expected: $categories, $adminPrefix
?>

<div class="card">
  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
    <h2 style="margin:0;">内容分类管理</h2>
    <a class="btn btn-outline-secondary" href="/<?= $adminPrefix ?>/community">返回内容列表</a>
  </div>
  <div class="card-body">
    <h3 style="margin-top:0;">新增分类</h3>
    <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
      <input type="hidden" name="action" value="create">
      <div style="min-width:240px;">
        <label>名称</label>
        <input class="form-control" name="name" required>
      </div>
      <div style="min-width:200px;">
        <label>Slug（可选）</label>
        <input class="form-control" name="slug">
      </div>
      <div style="width:120px;">
        <label>排序</label>
        <input class="form-control" name="sort" type="number" value="0">
      </div>
      <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px;">
        <input type="checkbox" name="is_active" checked>
        <span>启用</span>
      </div>
      <button class="btn btn-primary" type="submit">新增</button>
    </form>

    <hr>

    <h3>分类列表</h3>
    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th style="width:80px;">ID</th>
            <th>名称</th>
            <th style="width:240px;">Slug</th>
            <th style="width:120px;">排序</th>
            <th style="width:120px;">启用</th>
            <th style="width:320px;">操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($categories)): ?>
            <tr><td colspan="6" style="text-align:center; color:#777;">暂无分类</td></tr>
          <?php else: ?>
            <?php foreach ($categories as $cat): ?>
              <tr>
                <td><?= (int)$cat['id'] ?></td>
                <td><?= htmlspecialchars((string)$cat['name']) ?></td>
                <td><?= htmlspecialchars((string)($cat['slug'] ?? '')) ?></td>
                <td><?= (int)($cat['sort'] ?? 0) ?></td>
                <td><?= (int)($cat['is_active'] ?? 1) === 1 ? '是' : '否' ?></td>
                <td>
                  <form method="post" style="display:flex; gap:8px; flex-wrap:wrap; align-items:end;" onsubmit="return confirm('确认执行？');">
                    <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                    <input type="hidden" name="action" value="update">
                    <input class="form-control" style="width:200px;" name="name" value="<?= htmlspecialchars((string)$cat['name']) ?>" required>
                    <input class="form-control" style="width:200px;" name="slug" value="<?= htmlspecialchars((string)($cat['slug'] ?? '')) ?>">
                    <input class="form-control" style="width:90px;" name="sort" type="number" value="<?= (int)($cat['sort'] ?? 0) ?>">
                    <label style="display:flex; align-items:center; gap:6px; margin:0 6px 0 0;">
                      <input type="checkbox" name="is_active" <?= (int)($cat['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                      <span>启用</span>
                    </label>
                    <button class="btn btn-sm btn-outline-primary" type="submit">保存</button>
                  </form>
                  <form method="post" style="margin-top:6px;" onsubmit="return confirm('确定删除该分类？');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">删除</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

