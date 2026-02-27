<?php
/** @var array $projects */
?>
<div class="music-center-page">
    <!-- 顶部区域 -->
    <div class="page-header">
        <div class="container">
            <div class="center-header-row">
                <div>
                    <h1>音乐创作中心</h1>
                    <p>歌词、旋律、编曲、混音、母带一站式音乐制作</p>
                </div>
                <div class="center-header-btns">
                    <a href="/music/project/list" class="btn btn-outline">我的音乐项目</a>
                    <a href="/music/project/create" class="btn btn-primary">新建音乐项目</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- 当前音乐项目卡片 -->
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
                    <div class="card-progress">
                        <?php
                        $progress = [];
                        if ($project['has_lyrics']) $progress[] = '歌词';
                        if ($project['has_melody']) $progress[] = '旋律';
                        if ($project['has_arrangement']) $progress[] = '编曲';
                        if ($project['has_mix']) $progress[] = '混音';
                        echo implode(' / ', $progress ?: ['未开始']);
                        ?>
                    </div>
                    <div class="card-actions">
                        <a href="/music/project/<?= (int)$project['id'] ?>" class="btn btn-sm btn-primary">进入项目</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 灵感与歌词区块 -->
        <div class="tool-section">
            <h2>灵感 & 歌词</h2>
            <div class="tool-grid">
                <a href="/music/project/lyrics_generator" class="tool-card">
                    <div class="tool-icon">📝</div>
                    <h3>歌词生成</h3>
                    <p>AI生成歌词，支持主题、风格、情感设定</p>
                </a>
                <a href="/music/project/lyrics_upload" class="tool-card">
                    <div class="tool-icon">📤</div>
                    <h3>歌词上传 & 情感分析</h3>
                    <p>上传歌词并分析情感、主题</p>
                </a>
                <a href="/music/project/inspiration" class="tool-card">
                    <div class="tool-icon">💡</div>
                    <h3>主题 / 情绪灵感板</h3>
                    <p>收集和管理创作灵感</p>
                </a>
                <a href="/music/project/sheet_upload" class="tool-card">
                    <div class="tool-icon">🎼</div>
                    <h3>曲谱上传识别</h3>
                    <p>MIDI/XML/PDF/图片曲谱识别</p>
                </a>
            </div>
        </div>

        <!-- 旋律与编曲区块 -->
        <div class="tool-section">
            <h2>旋律 & 编曲</h2>
            <div class="tool-grid">
                <a href="/music/project/melody_generator" class="tool-card">
                    <div class="tool-icon">🎵</div>
                    <h3>旋律生成</h3>
                    <p>AI生成旋律，支持风格、调式设定</p>
                </a>
                <a href="/music/project/humming_recognition" class="tool-card">
                    <div class="tool-icon">🎤</div>
                    <h3>哼唱识别成旋律</h3>
                    <p>将哼唱转换为MIDI旋律</p>
                </a>
                <a href="/music/project/auto_arrangement" class="tool-card">
                    <div class="tool-icon">🎹</div>
                    <h3>自动编曲</h3>
                    <p>AI自动编曲，生成多轨伴奏</p>
                </a>
                <a href="/music/project/chord_suggestion" class="tool-card">
                    <div class="tool-icon">🎸</div>
                    <h3>和弦进行优化 / 替代建议</h3>
                    <p>和弦进行分析与优化建议</p>
                </a>
            </div>
        </div>

        <!-- 音轨与人声区块 -->
        <div class="tool-section">
            <h2>音轨 & 人声</h2>
            <div class="tool-grid">
                <a href="/music/project/multi_track" class="tool-card">
                    <div class="tool-icon">🎚️</div>
                    <h3>多轨编辑器</h3>
                    <p>项目内打开，多轨音频编辑</p>
                </a>
                <a href="/music/project/vocal_synthesis" class="tool-card">
                    <div class="tool-icon">🎙️</div>
                    <h3>AI 歌声合成</h3>
                    <p>AI生成人声，支持多种音色</p>
                </a>
                <a href="/music/project/vocal_tuning" class="tool-card">
                    <div class="tool-icon">🔧</div>
                    <h3>人声修音 / 降噪</h3>
                    <p>人声修音、降噪、音质优化</p>
                </a>
                <a href="/music/project/stem_separation" class="tool-card">
                    <div class="tool-icon">🔀</div>
                    <h3>AI 音轨分离 / 融合</h3>
                    <p>音轨分离、融合、混音</p>
                </a>
            </div>
        </div>

        <!-- 混音、母带与导出区块 -->
        <div class="tool-section">
            <h2>混音 & 母带 & 导出</h2>
            <div class="tool-grid">
                <a href="/music/project/auto_mix" class="tool-card">
                    <div class="tool-icon">🎛️</div>
                    <h3>自动混音</h3>
                    <p>AI自动混音，平衡各轨音量</p>
                </a>
                <a href="/music/project/mastering" class="tool-card">
                    <div class="tool-icon">✨</div>
                    <h3>自动母带</h3>
                    <p>AI母带处理，提升音质</p>
                </a>
                <a href="/music/project/export" class="tool-card">
                    <div class="tool-icon">💾</div>
                    <h3>导出设置（格式 / 码率）</h3>
                    <p>导出音频，支持多种格式</p>
                </a>
                <a href="/music/project/mv_generator" class="tool-card">
                    <div class="tool-icon">🎬</div>
                    <h3>生成音乐视频</h3>
                    <p>AI生成音乐视频</p>
                </a>
            </div>
        </div>
    </div>
</div>
