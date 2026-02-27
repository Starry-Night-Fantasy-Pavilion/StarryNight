<?php
/** @var array $projects */
?>
<div class="music-list-page">
    <div class="list-page-header">
        <h1>我的音乐项目</h1>
        <a href="/music/project/create" class="btn btn-primary">创建新音乐项目</a>
    </div>

    <?php if (empty($projects)): ?>
        <div class="card empty-state-card">
            <p>还没有创建任何音乐项目</p>
            <a href="/music/project/create" class="btn btn-primary">创建第一个音乐项目</a>
        </div>
    <?php else: ?>
        <div class="list-project-grid">
            <?php foreach ($projects as $project): ?>
                <div class="card">
                    <?php if ($project['cover_image']): ?>
                        <img src="<?= htmlspecialchars($project['cover_image']) ?>" alt="<?= htmlspecialchars($project['title']) ?>" class="card-cover-img">
                    <?php endif; ?>
                    <div class="card-body">
                        <h3>
                            <a href="/music/project/<?= (int)$project['id'] ?>">
                                <?= htmlspecialchars($project['title']) ?>
                            </a>
                        </h3>
                        <div class="card-meta">
                            <?php if ($project['genre']): ?>
                                <span class="badge"><?= htmlspecialchars($project['genre']) ?></span>
                            <?php endif; ?>
                            <?php if ($project['bpm']): ?>
                                <span><?= (int)$project['bpm'] ?> BPM</span>
                            <?php endif; ?>
                            <?php if ($project['key_signature']): ?>
                                <span><?= htmlspecialchars($project['key_signature']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($project['description']): ?>
                            <p class="card-desc"><?= htmlspecialchars($project['description']) ?></p>
                        <?php endif; ?>
                        <div class="card-footer">
                            <span class="badge badge-status">
                                <?php
                                $statusMap = [
                                    1 => '草稿',
                                    2 => '创作中',
                                    3 => '已完成',
                                    4 => '已发布'
                                ];
                                echo $statusMap[$project['status']] ?? '未知';
                                ?>
                            </span>
                            <a href="/music/project/<?= (int)$project['id'] ?>" class="btn btn-sm btn-primary">进入项目</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
