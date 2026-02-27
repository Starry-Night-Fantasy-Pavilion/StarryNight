<?php
// 从控制器传递的数据中获取最近小说
$recentNovels = $recent_novels ?? [];
?>
<div class="page-novel-creation">
    <!-- 顶部区域 -->
    <div class="page-header">
        <div class="container">
            <div class="center-header-row">
                <div>
                    <h1>小说创作中心</h1>
                    <p>管理作品、写章节、用 AI 辅助创作</p>
                </div>
                <div class="center-header-btns">
                    <a href="/novel" class="btn btn-outline">我的作品</a>
                    <a href="/novel/create" class="btn btn-primary">新建小说</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- 当前项目卡片 -->
        <?php if (!empty($recentNovels)): ?>
        <div class="tool-section">
            <h2>最近在写</h2>
            <div class="center-project-grid">
                <?php foreach ($recentNovels as $novel): ?>
                <div class="card">
                    <?php if ($novel['cover_image']): ?>
                        <img src="<?= htmlspecialchars($novel['cover_image']) ?>" alt="<?= htmlspecialchars($novel['title']) ?>" class="card-cover">
                    <?php endif; ?>
                    <h3>
                        <a href="/novel/<?= (int)$novel['id'] ?>/editor">
                            <?= htmlspecialchars($novel['title']) ?>
                        </a>
                    </h3>
                    <div class="card-meta">
                        <?php if ($novel['genre']): ?>
                            <span class="badge"><?= htmlspecialchars($novel['genre']) ?></span>
                        <?php endif; ?>
                        <span><?= number_format($novel['current_words'] ?? 0) ?> 字</span>
                        <span><?= $novel['chapter_count'] ?? 0 ?> 章</span>
                    </div>
                    <div class="card-actions">
                        <a href="/novel/<?= (int)$novel['id'] ?>/editor" class="btn btn-sm btn-primary">继续创作</a>
                        <a href="/novel/<?= (int)$novel['id'] ?>/editor" class="btn btn-sm btn-outline">章节管理</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 策划 & 设定区块 -->
        <div class="tool-section">
            <h2>策划 & 设定</h2>
            <div class="tool-grid">
                <a href="/novel_creation/outline_generator" class="tool-card">
                    <div class="tool-icon">🗂️</div>
                    <h3>生成大纲</h3>
                    <p>长篇结构规划，章节级、情节点级、细纲级</p>
                </a>
                <a href="/novel_creation/character_manager" class="tool-card">
                    <div class="tool-icon">👥</div>
                    <h3>角色管理</h3>
                    <p>人设档案、关系图、AI辅助生成</p>
                </a>
                <a href="/knowledge" class="tool-card">
                    <div class="tool-icon">📚</div>
                    <h3>世界观 / 设定库</h3>
                    <p>通用知识库（RAG），上传文档管理设定</p>
                </a>
                <a href="/novel_creation/character_consistency" class="tool-card">
                    <div class="tool-icon">🔍</div>
                    <h3>一致性检查</h3>
                    <p>整书逻辑校对，角色行为一致性</p>
                </a>
            </div>
        </div>

        <!-- 写作 AI 工具区块 -->
        <div class="tool-section">
            <h2>写作助手</h2>
            <div class="tool-grid">
                <a href="/novel_creation/editor" class="tool-card">
                    <div class="tool-icon">✍️</div>
                    <h3>智能续写</h3>
                    <p>AI辅助续写，保持风格一致</p>
                </a>
                <a href="/novel_creation/editor" class="tool-card">
                    <div class="tool-icon">🔄</div>
                    <h3>改写 / 扩写 / 润色</h3>
                    <p>AI辅助改写、扩写、润色文本</p>
                </a>
                <a href="/novel_creation/chapter_analysis" class="tool-card">
                    <div class="tool-icon">📊</div>
                    <h3>章节分析</h3>
                    <p>章节质量评估，情节、角色、文笔分析</p>
                </a>
                <a href="/novel_creation/book_analysis" class="tool-card">
                    <div class="tool-icon">📖</div>
                    <h3>拆书仿写</h3>
                    <p>分析优秀作品，仿写练习</p>
                </a>
            </div>
        </div>

        <!-- 灵感小工具区块 -->
        <div class="tool-section">
            <h2>灵感小工具</h2>
            <div class="tool-grid">
                <a href="/novel_creation/opening_generator" class="tool-card">
                    <div class="tool-icon">✨</div>
                    <h3>黄金开篇生成</h3>
                    <p>生成引人入胜的小说开篇，奠定作品基调</p>
                </a>
                <a href="/novel_creation/title_generator" class="tool-card">
                    <div class="tool-icon">📚</div>
                    <h3>书名生成</h3>
                    <p>根据内容生成吸引读者的爆款书名</p>
                </a>
                <a href="/novel_creation/description_generator" class="tool-card">
                    <div class="tool-icon">📝</div>
                    <h3>简介生成</h3>
                    <p>创作精炼吸睛的小说简介，吸引读者</p>
                </a>
                <a href="/novel_creation/cheat_generator" class="tool-card">
                    <div class="tool-icon">⚡</div>
                    <h3>金手指设定</h3>
                    <p>设计新颖有趣的特殊能力或设定</p>
                </a>
                <a href="/novel_creation/name_generator" class="tool-card">
                    <div class="tool-icon">🏷️</div>
                    <h3>名字生成</h3>
                    <p>生成独特而富有寓意的人名、地名、势力名</p>
                </a>
                <a href="/novel_creation/short_story" class="tool-card">
                    <div class="tool-icon">📖</div>
                    <h3>短篇创作</h3>
                    <p>专业的短篇小说创作模式，精炼故事</p>
                </a>
                <a href="/novel_creation/short_drama" class="tool-card">
                    <div class="tool-icon">🎬</div>
                    <h3>短剧剧本</h3>
                    <p>专业的短剧剧本创作工具，打造精彩脚本</p>
                </a>
            </div>
        </div>
    </div>
</div>
