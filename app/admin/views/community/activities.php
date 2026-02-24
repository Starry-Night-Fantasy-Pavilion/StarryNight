<?php
// Variables expected: $activities, $adminPrefix
?>

<div class="card">
  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
    <h2 style="margin:0;">社区活动管理</h2>
    <a class="btn btn-outline-secondary" href="/<?= $adminPrefix ?>/community">返回内容列表</a>
  </div>
  <div class="card-body">
    <h3 style="margin-top:0;">发布活动</h3>
    <form method="post" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
      <input type="hidden" name="action" value="create">
      <div style="min-width:260px;">
        <label>标题</label>
        <input class="form-control" name="title" required>
      </div>
      <div style="min-width:300px;">
        <label>描述</label>
        <input class="form-control" name="description" placeholder="活动简介...">
      </div>
      <div style="min-width:180px;">
        <label>开始时间</label>
        <input class="form-control" name="start_at" placeholder="2026-01-01 00:00:00">
      </div>
      <div style="min-width:180px;">
        <label>结束时间</label>
        <input class="form-control" name="end_at" placeholder="2026-01-07 23:59:59">
      </div>
      <div style="min-width:140px;">
        <label>状态</label>
        <select class="form-control" name="status">
          <option value="draft">draft</option>
          <option value="published">published</option>
          <option value="ended">ended</option>
        </select>
      </div>
      <label style="display:flex; align-items:center; gap:6px; margin-bottom:6px;">
        <input type="checkbox" name="is_pinned">
        <span>置顶</span>
      </label>
      <button class="btn btn-primary" type="submit">创建</button>
    </form>

    <hr>

    <h3>活动列表</h3>
    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th>标题</th>
            <th style="width:280px;">时间</th>
            <th style="width:120px;">状态</th>
            <th style="width:90px;">置顶</th>
            <th style="width:520px;">编辑</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($activities)): ?>
            <tr><td colspan="6" style="text-align:center; color:#777;">暂无活动</td></tr>
          <?php else: ?>
            <?php foreach ($activities as $a): ?>
              <tr>
                <td><?= (int)$a['id'] ?></td>
                <td>
                  <div style="font-weight:600;"><?= htmlspecialchars((string)$a['title']) ?></div>
                  <div style="color:#666; font-size:12px; margin-top:4px;"><?= htmlspecialchars((string)($a['description'] ?? '')) ?></div>
                </td>
                <td style="font-size:12px; color:#666;">
                  <?= htmlspecialchars((string)($a['start_at'] ?? '')) ?><br>
                  <?= htmlspecialchars((string)($a['end_at'] ?? '')) ?>
                </td>
                <td><?= htmlspecialchars((string)($a['status'] ?? '')) ?></td>
                <td><?= (int)($a['is_pinned'] ?? 0) === 1 ? '是' : '否' ?></td>
                <td>
                  <form method="post" style="display:flex; gap:8px; flex-wrap:wrap; align-items:end;" onsubmit="return confirm('确认保存？');">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <input class="form-control" style="width:220px;" name="title" value="<?= htmlspecialchars((string)$a['title']) ?>" required>
                    <input class="form-control" style="width:260px;" name="description" value="<?= htmlspecialchars((string)($a['description'] ?? '')) ?>">
                    <input class="form-control" style="width:170px;" name="start_at" value="<?= htmlspecialchars((string)($a['start_at'] ?? '')) ?>">
                    <input class="form-control" style="width:170px;" name="end_at" value="<?= htmlspecialchars((string)($a['end_at'] ?? '')) ?>">
                    <select class="form-control" style="width:120px;" name="status">
                      <?php $st = (string)($a['status'] ?? 'draft'); ?>
                      <option value="draft" <?= $st==='draft'?'selected':'' ?>>draft</option>
                      <option value="published" <?= $st==='published'?'selected':'' ?>>published</option>
                      <option value="ended" <?= $st==='ended'?'selected':'' ?>>ended</option>
                    </select>
                    <label style="display:flex; align-items:center; gap:6px; margin:0;">
                      <input type="checkbox" name="is_pinned" <?= (int)($a['is_pinned'] ?? 0) === 1 ? 'checked' : '' ?>>
                      <span>置顶</span>
                    </label>
                    <button class="btn btn-sm btn-outline-primary" type="submit">保存</button>
                  </form>
                  <form method="post" style="margin-top:6px;" onsubmit="return confirm('确定删除该活动？');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
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

