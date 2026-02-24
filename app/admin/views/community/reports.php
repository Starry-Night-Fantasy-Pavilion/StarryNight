<?php
// Variables expected: $data, $adminPrefix
?>

<style>
  .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; border:1px solid #ddd; }
  .b-pending { background:#fffbeb; border-color:#fcd34d; color:#92400e; }
  .b-valid { background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
  .b-invalid { background:#eff6ff; border-color:#bfdbfe; color:#1d4ed8; }
  .b-resolved { background:#f3f4f6; border-color:#e5e7eb; color:#374151; }
  .toolbar { display:flex; gap:12px; flex-wrap:wrap; align-items:end; margin-bottom:12px; }
  .toolbar .field { display:flex; flex-direction:column; gap:6px; }
</style>

<div class="card">
  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
    <h2 style="margin:0;">用户举报处理</h2>
    <a class="btn btn-outline-secondary" href="/<?= $adminPrefix ?>/community">返回内容列表</a>
  </div>
  <div class="card-body">
    <form method="get" class="toolbar">
      <div class="field">
        <label>状态</label>
        <?php $status = $_GET['status'] ?? ''; ?>
        <select name="status" class="form-control">
          <option value="">全部</option>
          <option value="pending" <?= $status==='pending'?'selected':'' ?>>待处理</option>
          <option value="valid" <?= $status==='valid'?'selected':'' ?>>有效</option>
          <option value="invalid" <?= $status==='invalid'?'selected':'' ?>>无效</option>
          <option value="resolved" <?= $status==='resolved'?'selected':'' ?>>已处理</option>
        </select>
      </div>
      <div class="field">
        <label>&nbsp;</label>
        <button class="btn btn-primary" type="submit">筛选</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover table-bordered align-middle mb-0">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th style="width:90px;">内容类型</th>
            <th>内容标题</th>
            <th style="width:120px;">举报人</th>
            <th style="width:140px;">原因</th>
            <th style="width:120px;">状态</th>
            <th style="width:200px;">时间</th>
            <th style="width:380px;">处理</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data['items'])): ?>
            <tr><td colspan="8" style="text-align:center; color:#777;">暂无举报</td></tr>
          <?php else: ?>
            <?php foreach ($data['items'] as $r): ?>
              <?php
                $st = (string)($r['status'] ?? 'pending');
                $badgeClass = 'b-pending';
                if ($st === 'valid') $badgeClass = 'b-valid';
                if ($st === 'invalid') $badgeClass = 'b-invalid';
                if ($st === 'resolved') $badgeClass = 'b-resolved';
              ?>
              <tr>
                <td><?= (int)$r['id'] ?></td>
                <td><?= htmlspecialchars((string)($r['content_type'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string)($r['content_title'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($r['reporter_name'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string)($r['reason'] ?? '')) ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($st) ?></span></td>
                <td><div style="font-size:12px; color:#666;"><?= htmlspecialchars((string)($r['created_at'] ?? '')) ?></div></td>
                <td>
                  <form method="post" style="display:flex; gap:8px; flex-wrap:wrap; align-items:end;">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <div style="min-width:140px;">
                      <label>状态</label>
                      <select name="status" class="form-control">
                        <option value="pending" <?= $st==='pending'?'selected':'' ?>>pending</option>
                        <option value="valid" <?= $st==='valid'?'selected':'' ?>>valid</option>
                        <option value="invalid" <?= $st==='invalid'?'selected':'' ?>>invalid</option>
                        <option value="resolved" <?= $st==='resolved'?'selected':'' ?>>resolved</option>
                      </select>
                    </div>
                    <div style="min-width:220px;">
                      <label>备注</label>
                      <input name="note" class="form-control" value="<?= htmlspecialchars((string)($r['admin_note'] ?? '')) ?>" placeholder="处理说明...">
                    </div>
                    <button class="btn btn-sm btn-outline-primary" type="submit" onclick="return confirm('确认保存处理结果？');">保存</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (($data['totalPages'] ?? 1) > 1): ?>
      <?php
        $qs = $_GET;
        $pageNow = (int)($data['page'] ?? 1);
        $totalPages = (int)($data['totalPages'] ?? 1);
      ?>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
        <div style="color:#666; font-size:13px;">共 <?= (int)$data['total'] ?> 条</div>
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
          <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <?php $qs['page'] = $p; ?>
            <a class="btn btn-sm <?= $p === $pageNow ? 'btn-primary' : 'btn-outline-secondary' ?>"
               href="?<?= http_build_query($qs) ?>"><?= $p ?></a>
          <?php endfor; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

