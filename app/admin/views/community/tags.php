<?php
// Variables expected: $tags, $adminPrefix
?>

<div class="card">
  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
    <h2 style="margin:0;">内容标签管理</h2>
    <a class="btn btn-outline-secondary" href="/<?= $adminPrefix ?>/community">返回内容列表</a>
  </div>
  <div class="card-body">
    <h3 style="margin-top:0;">新增标签</h3>
    <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
      <input type="hidden" name="action" value="create">
      <div style="min-width:240px;">
        <label>名称</label>
        <input class="form-control" name="name" required>
      </div>
      <div style="min-width:240px;">
        <label>Slug（可选）</label>
        <input class="form-control" name="slug">
      </div>
      <button class="btn btn-primary" type="submit">新增</button>
    </form>

    <hr>

    <h3>标签列表</h3>
    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th style="width:80px;">ID</th>
            <th>名称</th>
            <th style="width:280px;">Slug</th>
            <th style="width:360px;">操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($tags)): ?>
            <tr><td colspan="4" style="text-align:center; color:#777;">暂无标签</td></tr>
          <?php else: ?>
            <?php foreach ($tags as $tag): ?>
              <tr>
                <td><?= (int)$tag['id'] ?></td>
                <td><?= htmlspecialchars((string)$tag['name']) ?></td>
                <td><?= htmlspecialchars((string)($tag['slug'] ?? '')) ?></td>
                <td>
                  <form method="post" style="display:flex; gap:8px; flex-wrap:wrap; align-items:end;" onsubmit="return confirm('确认执行？');">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$tag['id'] ?>">
                    <input class="form-control" style="width:220px;" name="name" value="<?= htmlspecialchars((string)$tag['name']) ?>" required>
                    <input class="form-control" style="width:220px;" name="slug" value="<?= htmlspecialchars((string)($tag['slug'] ?? '')) ?>">
                    <button class="btn btn-sm btn-outline-primary" type="submit">保存</button>
                  </form>
                  <form method="post" style="margin-top:6px;" onsubmit="return confirm('确定删除该标签？');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$tag['id'] ?>">
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

