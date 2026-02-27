<?php
use app\services\ThemeManager;
use app\config\FrontendConfig;

// 获取当前主题CSS路径
$themeManager = new ThemeManager();
$activeThemeId = $themeManager->getActiveThemeId('web') ?? FrontendConfig::THEME_DEFAULT;
$themeBasePath = FrontendConfig::getThemePath($activeThemeId);
$cssPath = FrontendConfig::getAssetUrl(FrontendConfig::PATH_STATIC_FRONTEND_WEB_CSS . '/pages/novel-creation.css');
?>
<div class="page-novel-creation">
    <div class="page-header">
        <div class="container">
            <h1>AI小说创作工具集</h1>
            <p>专业的AI创作助手，帮助您提升写作效率和质量</p>
        </div>
    </div>

    <div class="container">
        <div class="tool-section">
            <h2>章节分析系统</h2>
            <div class="tool-grid">
                <a href="/novel_creation/chapter_analysis" class="tool-card">
                    <div class="tool-icon">📊</div>
                    <h3>章节质量评估</h3>
                    <p>对章节进行专业评估，包括情节、角色、文笔等多个维度</p>
                </a>
            </div>
        </div>

        <div class="tool-section">
            <h2>拆书仿写功能</h2>
            <div class="tool-grid">
                <a href="/novel_creation/book_analysis" class="tool-card">
                    <div class="tool-icon">📖</div>
                    <h3>拆书分析</h3>
                    <p>分析优秀作品的写作技巧、风格特点和结构特点</p>
                </a>
                <a href="/novel_creation/imitation_writing" class="tool-card">
                    <div class="tool-icon">✍️</div>
                    <h3>仿写创作</h3>
                    <p>基于分析结果，仿照原文风格进行创作练习</p>
                </a>
            </div>
        </div>

        <div class="tool-section">
            <h2>智能编辑器</h2>
            <div class="tool-grid">
                <a href="/novel_creation/editor" class="tool-card">
                    <div class="tool-icon">📝</div>
                    <h3>智能编辑器</h3>
                    <p>富文本编辑器，支持实时保存、AI辅助写作、章节管理、版本历史</p>
                </a>
            </div>
        </div>

        <div class="tool-section">
            <h2>大纲生成系统</h2>
            <div class="tool-grid">
                <a href="/novel_creation/outline_generator" class="tool-card">
                    <div class="tool-icon">🗂️</div>
                    <h3>大纲生成</h3>
                    <p>根据题材、类型生成多级大纲，支持章节级、情节点级、细纲级</p>
                </a>
            </div>
        </div>

        <div class="tool-section">
            <h2>角色管理系统</h2>
            <div class="tool-grid">
                <a href="/novel_creation/character_manager" class="tool-card">
                    <div class="tool-icon">👥</div>
                    <h3>角色管理</h3>
                    <p>角色档案管理、关系图可视化、AI辅助生成、角色一致性检查</p>
                </a>
            </div>
        </div>

        <div class="tool-section">
            <h2>创作辅助工具</h2>
            <div class="tool-grid">
                <a href="/novel_creation/opening_generator" class="tool-card">
                    <div class="tool-icon">✨</div>
                    <h3>黄金开篇</h3>
                    <p>生成引人入胜的小说开篇，奠定作品基调</p>
                </a>
                <a href="/novel_creation/title_generator" class="tool-card">
                    <div class="tool-icon">📚</div>
                    <h3>书名生成器</h3>
                    <p>根据内容生成吸引读者的爆款书名</p>
                </a>
                <a href="/novel_creation/description_generator" class="tool-card">
                    <div class="tool-icon">📝</div>
                    <h3>简介生成器</h3>
                    <p>创作精炼吸睛的小说简介，吸引读者</p>
                </a>
                <a href="/novel_creation/cheat_generator" class="tool-card">
                    <div class="tool-icon">⚡</div>
                    <h3>金手指生成器</h3>
                    <p>设计新颖有趣的特殊能力或设定</p>
                </a>
                <a href="/novel_creation/name_generator" class="tool-card">
                    <div class="tool-icon">🏷️</div>
                    <h3>名字生成器</h3>
                    <p>生成独特而富有寓意的人名、地名、势力名</p>
                </a>
                <a href="/novel_creation/cover_generator" class="tool-card">
                    <div class="tool-icon">🎨</div>
                    <h3>封面描述</h3>
                    <p>根据小说内容生成精美的封面描述</p>
                </a>
            </div>
        </div>

        <div class="tool-section">
            <h2>短篇创作</h2>
            <div class="tool-grid">
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
