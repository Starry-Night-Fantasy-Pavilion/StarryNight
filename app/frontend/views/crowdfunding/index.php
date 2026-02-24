<?php $this->layout('layout', ['title' => '众筹项目']) ?>

<div class="container">
    <h1>AI 创作众筹平台</h1>
    <p>支持您喜欢的 AI 创作项目，获得独家回报。</p>
    <a href="/crowdfunding/create" class="btn btn-primary mb-4">发起我的项目</a>

    <div class="row">
        <?php if (empty($projects)): ?>
            <p>目前没有正在进行的众筹项目。</p>
        <?php else: ?>
            <?php foreach ($projects as $project): ?>
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($project->title) ?></h5>
                            <p class="card-text"><?= htmlspecialchars(mb_substr($project->description, 0, 100)) ?>...</p>
                            <p><strong>目标:</strong> <?= htmlspecialchars($project->goal_amount) ?> 星夜币</p>
                            <p><strong>已筹集:</strong> <?= htmlspecialchars($project->current_amount) ?> 星夜币</p>
                            <?php 
                                $progress = ($project->goal_amount > 0) ? ($project->current_amount / $project->goal_amount) * 100 : 0;
                            ?>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"><?= round($progress) ?>%</div>
                            </div>
                            <a href="/crowdfunding/project/<?= $project->id ?>" class="btn btn-secondary mt-3">查看详情</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
