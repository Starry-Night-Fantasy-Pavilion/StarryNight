<?php
/** @var array $novels */
?>
<div class="novel-list-page">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px;">
        <h1>我的小说</h1>
        <a href="/novel/create" class="btn btn-primary">创建新小说</a>
    </div>

    <?php if (empty($novels)): ?>
        <div class="card" style="text-align:center; padding:48px;">
            <p style="opacity:0.7; margin-bottom:16px;">还没有创建任何小说</p>
            <a href="/novel/create" class="btn btn-primary">创建第一本小说</a>
        </div>
    <?php else: ?>
        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:20px;">
            <?php foreach ($novels as $novel): ?>
                <div class="card">
                    <?php if ($novel['cover_image']): ?>
                        <img src="<?= htmlspecialchars($novel['cover_image']) ?>" alt="<?= htmlspecialchars($novel['title']) ?>" style="width:100%; height:180px; object-fit:cover; border-radius:6px 6px 0 0;">
                    <?php endif; ?>
                    <div style="padding:16px;">
                        <h3 style="margin:0 0 8px; font-size:18px;">
                            <a href="/novel/<?= (int)$novel['id'] ?>/editor" style="text-decoration:none; color:inherit;">
                                <?= htmlspecialchars($novel['title']) ?>
                            </a>
                        </h3>
                        <div style="font-size:13px; opacity:0.7; margin-bottom:12px;">
                            <?php if ($novel['genre']): ?>
                                <span class="badge"><?= htmlspecialchars($novel['genre']) ?></span>
                            <?php endif; ?>
                            <span style="margin-left:8px;"><?= number_format($novel['current_words'] ?? 0) ?> / <?= number_format($novel['target_words'] ?? 0) ?> 字</span>
                        </div>
                        <?php if ($novel['description']): ?>
                            <p style="font-size:14px; opacity:0.8; margin:0 0 12px; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                                <?= htmlspecialchars($novel['description']) ?>
                            </p>
                        <?php endif; ?>
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span class="badge" style="background:rgba(0,0,0,0.1);">
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

<style>
.novel-list-page {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
}
.card {
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    background: rgba(14, 165, 233, 0.2);
    color: #0ea5e9;
}
</style>
