<?php
// Variables expected:
// $data, $categories, $tags, $adminPrefix
?>

<style>
  .community-toolbar { display:flex; gap:12px; flex-wrap:wrap; align-items:end; margin-bottom: 12px; }
  .community-toolbar .field { display:flex; flex-direction:column; gap:6px; }
  .badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:12px; border:1px solid #ddd; }
  .badge-ok { background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
  .badge-warn { background:#fffbeb; border-color:#fcd34d; color:#92400e; }
  .badge-danger { background:#fef2f2; border-color:#fecaca; color:#991b1b; }
  .btn-row { display:flex; gap:6px; flex-wrap:wrap; }
</style>

<div class="card">
  <div class="card-header">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px;">
      <div>
        <h2 style="margin:0;">社区内容列表</h2>
        <div style="color:#666; font-size:13px; margin-top:4px;">管理帖子/评论/作品分享，支持推荐、置顶、删除与恢复</div>
      </div>
      <div class="btn-row">
        <a class="btn btn-outline-secondary" href="/<?= $adminPrefix ?>/community/categories">分类管理</a>
        <a class="btn btn-outline-secondary" href="/<?= $adminPrefix ?>/community/tags">标签管理</a>
        <a class="btn btn-outline-secondary" href="/<?= $adminPrefix ?>/community/reports">举报处理</a>
        <a class="btn btn-outline-secondary" href="/<?= $adminPrefix ?>/community/activities">活动管理</a>
      </div>
    </div>
  </div>
  <div class="card-body">
    <form method="get" class="community-toolbar">
      <div class="field">
        <label>类型</label>
        <select name="type" class="form-control">
          <?php $type = $_GET['type'] ?? ''; ?>
          <option value="">全部</option>
          <option value="post" <?= $type==='post'?'selected':'' ?>>帖子</option>
          <option value="comment" <?= $type==='comment'?'selected':'' ?>>评论</option>
          <option value="work" <?= $type==='work'?'selected':'' ?>>作品</option>
        </select>
      </div>
      <div class="field">
        <label>状态</label>
        <?php $status = $_GET['status'] ?? ''; ?>
        <select name="status" class="form-control">
          <option value="">全部</option>
          <option value="published" <?= $status==='published'?'selected':'' ?>>已发布</option>
          <option value="hidden" <?= $status==='hidden'?'selected':'' ?>>已隐藏</option>
        </select>
      </div>
      <div class="field">
        <label>删除</label>
        <?php $deleted = $_GET['deleted'] ?? ''; ?>
        <select name="deleted" class="form-control">
          <option value="">全部</option>
          <option value="0" <?= $deleted==='0'?'selected':'' ?>>未删除</option>
          <option value="1" <?= $deleted==='1'?'selected':'' ?>>已删除</option>
        </select>
      </div>
      <div class="field">
        <label>分类</label>
        <?php $categoryId = (string)($_GET['category_id'] ?? ''); ?>
        <select name="category_id" class="form-control">
          <option value="">全部</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int)$cat['id'] ?>" <?= $categoryId===(string)$cat['id']?'selected':'' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>标签</label>
        <?php $tagId = (string)($_GET['tag_id'] ?? ''); ?>
        <select name="tag_id" class="form-control">
          <option value="">全部</option>
          <?php foreach ($tags as $tag): ?>
            <option value="<?= (int)$tag['id'] ?>" <?= $tagId===(string)$tag['id']?'selected':'' ?>>
              <?= htmlspecialchars($tag['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="min-width:260px;">
        <label>关键词</label>
        <input name="q" class="form-control" value="<?= htmlspecialchars((string)($_GET['q'] ?? '')) ?>" placeholder="标题/内容包含...">
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
            <th style="width:90px;">类型</th>
            <th>标题/摘要</th>
            <th style="width:120px;">作者</th>
            <th style="width:120px;">分类</th>
            <th style="width:140px;">属性</th>
            <th style="width:160px;">时间</th>
            <th style="width:260px;">操作</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($data['items'])): ?>
            <tr><td colspan="8" style="text-align:center; color:#777;">暂无数据</td></tr>
          <?php else: ?>
            <?php foreach ($data['items'] as $item): ?>
              <tr>
                <td><?= (int)$item['id'] ?></td>
                <td><?= htmlspecialchars($item['type']) ?></td>
                <td>
                  <div style="font-weight:600;"><?= htmlspecialchars((string)($item['title'] ?? '')) ?></div>
                  <div style="color:#666; font-size:12px; margin-top:4px; max-width:520px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                    <?= htmlspecialchars(mb_substr((string)($item['body'] ?? ''), 0, 80)) ?>
                  </div>
                </td>
                <td><?= htmlspecialchars((string)($item['author_name'] ?? '-')) ?></td>
                <td><?= htmlspecialchars((string)($item['category_name'] ?? '-')) ?></td>
                <td>
                  <?php if ((int)$item['is_pinned'] === 1): ?><span class="badge badge-warn">置顶</span><?php endif; ?>
                  <?php if ((int)$item['is_recommended'] === 1): ?><span class="badge badge-ok">推荐</span><?php endif; ?>
                  <?php if ((int)$item['is_deleted'] === 1): ?><span class="badge badge-danger">已删除</span><?php endif; ?>
                </td>
                <td>
                  <div style="font-size:12px; color:#666;"><?= htmlspecialchars((string)($item['created_at'] ?? '')) ?></div>
                </td>
                <td>
                  <form method="post" action="/<?= $adminPrefix ?>/community/actions" class="btn-row" onsubmit="return confirm('确认执行该操作？');">
                    <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <?php if ((int)$item['is_pinned'] === 1): ?>
                      <button class="btn btn-sm btn-outline-secondary" name="action" value="unpin" type="submit">取消置顶</button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-secondary" name="action" value="pin" type="submit">置顶</button>
                    <?php endif; ?>

                    <?php if ((int)$item['is_recommended'] === 1): ?>
                      <button class="btn btn-sm btn-outline-success" name="action" value="unrecommend" type="submit">取消推荐</button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-success" name="action" value="recommend" type="submit">推荐</button>
                    <?php endif; ?>

                    <?php if ((int)$item['is_deleted'] === 1): ?>
                      <button class="btn btn-sm btn-outline-primary" name="action" value="restore" type="submit">恢复</button>
                    <?php else: ?>
                      <button class="btn btn-sm btn-outline-danger" name="action" value="delete" type="submit">删除</button>
                    <?php endif; ?>
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

