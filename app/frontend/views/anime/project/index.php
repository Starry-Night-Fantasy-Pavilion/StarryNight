<?php
/** @var array $projects */
?>
<div class="anime-list-page">
    <div class="list-page-header">
        <h1>我的动漫项目</h1>
        <a href="/anime/project/create" class="btn btn-primary">创建新动漫项目</a>
    </div>

    <?php if (empty($projects)): ?>
        <div class="card empty-state-card">
            <p>还没有创建任何动漫项目</p>
            <a href="/anime/project/create" class="btn btn-primary">创建第一个动漫项目</a>
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
                            <a href="/anime/project/<?= (int)$project['id'] ?>">
                                <?= htmlspecialchars($project['title']) ?>
                            </a>
                        </h3>
                        <div class="card-meta">
                            <?php if ($project['genre']): ?>
                                <span class="badge"><?= htmlspecialchars($project['genre']) ?></span>
                            <?php endif; ?>
                            <?php if ($project['production_mode']): ?>
                                <span class="badge badge-anime"><?= $project['production_mode'] === 'long' ? '长篇' : '短剧' ?></span>
                            <?php endif; ?>
                            <?php if ($project['episode_count']): ?>
                                <span><?= (int)$project['episode_count'] ?> 集</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($project['description']): ?>
                            <p class="card-desc"><?= htmlspecialchars($project['description']) ?></p>
                        <?php endif; ?>
                        <div class="card-footer">
                            <span class="badge badge-status">
                                <?php
                                $statusMap = [
                                    'draft' => '草稿',
                                    'planning' => '企划中',
                                    'in_production' => '制作中',
                                    'completed' => '已完成',
                                    'cancelled' => '已取消'
                                ];
                                echo $statusMap[$project['status']] ?? $project['status'];
                                ?>
                            </span>
                            <a href="/anime/project/<?= (int)$project['id'] ?>" class="btn btn-sm btn-primary">进入项目</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
