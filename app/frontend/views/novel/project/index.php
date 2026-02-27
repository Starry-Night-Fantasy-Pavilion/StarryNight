<?php
/** @var array $novels */
?>
<div class="novel-list-page">
    <div class="list-page-header">
        <h1>我的小说</h1>
        <a href="/novel/create" class="btn btn-primary">创建新小说</a>
    </div>

    <?php if (empty($novels)): ?>
        <div class="card empty-state-card">
            <p>还没有创建任何小说</p>
            <a href="/novel/create" class="btn btn-primary">创建第一本小说</a>
        </div>
    <?php else: ?>
        <div class="list-project-grid">
            <?php foreach ($novels as $novel): ?>
                <div class="card">
                    <?php if ($novel['cover_image']): ?>
                        <img src="<?= htmlspecialchars($novel['cover_image']) ?>" alt="<?= htmlspecialchars($novel['title']) ?>" class="card-cover-img">
                    <?php endif; ?>
                    <div class="card-body">
                        <h3>
                            <a href="/novel/<?= (int)$novel['id'] ?>/editor">
                                <?= htmlspecialchars($novel['title']) ?>
                            </a>
                        </h3>
                        <div class="card-meta">
                            <?php if ($novel['genre']): ?>
                                <span class="badge"><?= htmlspecialchars($novel['genre']) ?></span>
                            <?php endif; ?>
                            <span><?= number_format($novel['current_words'] ?? 0) ?> / <?= number_format($novel['target_words'] ?? 0) ?> 字</span>
                        </div>
                        <?php if ($novel['description']): ?>
                            <p class="card-desc"><?= htmlspecialchars($novel['description']) ?></p>
                        <?php endif; ?>
                        <div class="card-footer">
                            <span class="badge badge-status">
                                <?php
                                $statusMap = [
                                    'draft' => '草稿',
                                    'writing' => '创作中',
                                    'completed' => '已完成',
                                    'published' => '已发布'
                                ];
                                echo $statusMap[$novel['status']] ?? $novel['status'];
                                ?>
                            </span>
                            <a href="/novel/<?= (int)$novel['id'] ?>/editor" class="btn btn-sm btn-primary">编辑</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
