<?php
/** @var array $projects */
?>
<div class="anime-center-page">
    <!-- 顶部区域 -->
    <div class="page-header">
        <div class="container">
            <div class="center-header-row">
                <div>
                    <h1>动漫 / 短剧创作中心</h1>
                    <p>企划、脚本、视觉设定、动画制作一站式服务</p>
                </div>
                <div class="center-header-btns">
                    <a href="/anime/project/list" class="btn btn-outline">我的动漫项目</a>
                    <a href="/anime/project/create" class="btn btn-primary">新建动漫项目</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- 项目概览卡片 -->
        <?php if (!empty($projects)): ?>
        <div class="tool-section">
            <h2>最近项目</h2>
            <div class="center-project-grid">
                <?php foreach (array_slice($projects, 0, 2) as $project): ?>
                <div class="card">
                    <?php if ($project['cover_image']): ?>
                        <img src="<?= htmlspecialchars($project['cover_image']) ?>" alt="<?= htmlspecialchars($project['title']) ?>" class="card-cover">
                    <?php endif; ?>
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
                    <div class="card-actions">
                        <a href="/anime/project/<?= (int)$project['id'] ?>" class="btn btn-sm btn-primary">企划 / 概览</a>
                        <a href="/anime/project/<?= (int)$project['id'] ?>/script" class="btn btn-sm btn-outline">进入脚本</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 企划与结构区块 -->
        <div class="tool-section">
            <h2>企划 & 结构</h2>
            <div class="tool-grid">
                <a href="/anime/project/create" class="tool-card">
                    <div class="tool-icon">📋</div>
                    <h3>企划方案生成</h3>
                    <p>题材、受众、卖点、分集规划</p>
                </a>
                <a href="/anime/project/script_generator" class="tool-card">
                    <div class="tool-icon">📝</div>
                    <h3>分集脚本生成</h3>
                    <p>按集数生成脚本，支持批量生成</p>
                </a>
                <a href="/anime/project/storyline" class="tool-card">
                    <div class="tool-icon">🌳</div>
                    <h3>主线 / 支线管理</h3>
                    <p>管理故事主线与支线剧情</p>
                </a>
                <a href="/anime/project/foreshadowing" class="tool-card">
                    <div class="tool-icon">🔗</div>
                    <h3>伏笔管理</h3>
                    <p>记录和管理伏笔线索</p>
                </a>
            </div>
        </div>

        <!-- 设定与视觉区块 -->
        <div class="tool-section">
            <h2>视觉设定</h2>
            <div class="tool-grid">
                <a href="/anime/project/character_design" class="tool-card">
                    <div class="tool-icon">👤</div>
                    <h3>角色设计</h3>
                    <p>立绘描述+设定，角色档案管理</p>
                </a>
                <a href="/anime/project/scene_design" class="tool-card">
                    <div class="tool-icon">🏞️</div>
                    <h3>场景设计</h3>
                    <p>场景描述与设定管理</p>
                </a>
                <a href="/anime/project/storyboard" class="tool-card">
                    <div class="tool-icon">🎬</div>
                    <h3>分镜生成</h3>
                    <p>从脚本到镜头描述</p>
                </a>
                <a href="/anime/project/action_suggestion" class="tool-card">
                    <div class="tool-icon">🎭</div>
                    <h3>动作 / 表情建议</h3>
                    <p>AI生成动作和表情描述</p>
                </a>
            </div>
        </div>

        <!-- 动画与音视频区块 -->
        <div class="tool-section">
            <h2>动画 & 音视频</h2>
            <div class="tool-grid">
                <a href="/anime/project/keyframe" class="tool-card">
                    <div class="tool-icon">🎨</div>
                    <h3>动画关键帧方案</h3>
                    <p>关键帧设计与规划</p>
                </a>
                <a href="/anime/project/audio" class="tool-card">
                    <div class="tool-icon">🎵</div>
                    <h3>配音 / 音效 / 背景音乐方案</h3>
                    <p>音频制作方案管理</p>
                </a>
                <a href="/anime/project/video_synthesis" class="tool-card">
                    <div class="tool-icon">🎞️</div>
                    <h3>视频合成方案</h3>
                    <p>视频合成与后期处理</p>
                </a>
                <a href="/anime/project/review" class="tool-card">
                    <div class="tool-icon">✅</div>
                    <h3>审核与发布配置</h3>
                    <p>审核流程与发布设置</p>
                </a>
            </div>
        </div>

        <!-- 短剧快速通道区块 -->
        <div class="tool-section">
            <h2>短剧快速生成</h2>
            <div class="tool-grid tool-grid-single">
                <a href="/anime/project/quick_generate" class="tool-card tool-card-large">
                    <div class="tool-icon tool-icon-large">⚡</div>
                    <h3>一键生成短剧</h3>
                    <p>文本描述 → 短剧方案，适合 TikTok/B 站短剧快速产出</p>
                </a>
            </div>
        </div>
    </div>
</div>
