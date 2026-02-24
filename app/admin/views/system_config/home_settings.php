<?php $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/'); ?>
<?php require __DIR__ . '/_nav.php'; ?>

<style>
.home-settings-form {
    max-width: 100%;
}
.settings-section {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.settings-section h3 {
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    color: #333;
    font-size: 16px;
}
.settings-section h3 i {
    margin-right: 8px;
    color: #667eea;
}
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #555;
}
.form-group input,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
.form-group textarea {
    resize: vertical;
    min-height: 80px;
}
.form-group .hint {
    font-size: 12px;
    color: #888;
    margin-top: 4px;
}
.btn-save {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border: none;
    padding: 12px 30px;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
}
.btn-save:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}
.section-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 6px;
    color: #fff;
    margin-right: 10px;
    font-size: 14px;
}
</style>

<div class="home-settings-form">
    <form method="POST">
        <!-- 英雄区域设置 -->
        <div class="settings-section">
            <h3><span class="section-icon"><i class="fas fa-star"></i></span>英雄区域设置</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>徽章文字</label>
                    <input type="text" name="home_hero_badge" value="<?= htmlspecialchars($data['home_hero_badge'] ?? '') ?>" placeholder="例如：AI创作革命">
                    <div class="hint">显示在标题上方的小标签</div>
                </div>
                <div class="form-group">
                    <label>主标题</label>
                    <input type="text" name="home_hero_title" value="<?= htmlspecialchars($data['home_hero_title'] ?? '') ?>" placeholder="例如：AI智能创作平台">
                    <div class="hint">网站名称下方的副标题</div>
                </div>
            </div>
            <div class="form-group">
                <label>描述文字</label>
                <textarea name="home_hero_subtitle" rows="3" placeholder="详细介绍您的平台优势"><?= htmlspecialchars($data['home_hero_subtitle'] ?? '') ?></textarea>
                <div class="hint">显示在标题下方的详细描述</div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>主按钮文字</label>
                    <input type="text" name="home_hero_cta_primary" value="<?= htmlspecialchars($data['home_hero_cta_primary'] ?? '') ?>" placeholder="例如：立即免费试用">
                    <div class="hint">主要行动按钮的文字</div>
                </div>
                <div class="form-group">
                    <label>次按钮文字</label>
                    <input type="text" name="home_hero_cta_secondary" value="<?= htmlspecialchars($data['home_hero_cta_secondary'] ?? '') ?>" placeholder="例如：观看演示">
                    <div class="hint">次要行动按钮的文字</div>
                </div>
            </div>
        </div>

        <!-- 统计数据设置 -->
        <div class="settings-section">
            <h3><span class="section-icon"><i class="fas fa-chart-bar"></i></span>统计数据设置</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>活跃创作者数量</label>
                    <input type="number" name="home_stat_users" value="<?= htmlspecialchars($data['home_stat_users'] ?? '') ?>" placeholder="例如：10000">
                    <div class="hint">显示在首页统计区域</div>
                </div>
                <div class="form-group">
                    <label>创作作品数量</label>
                    <input type="number" name="home_stat_novels" value="<?= htmlspecialchars($data['home_stat_novels'] ?? '') ?>" placeholder="例如：50000">
                    <div class="hint">显示在首页统计区域</div>
                </div>
                <div class="form-group">
                    <label>生成字数</label>
                    <input type="number" name="home_stat_words" value="<?= htmlspecialchars($data['home_stat_words'] ?? '') ?>" placeholder="例如：10000000">
                    <div class="hint">显示在首页统计区域，超过1万会自动转换为"万"单位</div>
                </div>
            </div>
        </div>

        <!-- SEO设置 -->
        <div class="settings-section">
            <h3><span class="section-icon"><i class="fas fa-search"></i></span>SEO设置</h3>
            <div class="form-group">
                <label>Meta描述</label>
                <textarea name="home_meta_description" rows="2" placeholder="用于搜索引擎优化的页面描述"><?= htmlspecialchars($data['home_meta_description'] ?? '') ?></textarea>
                <div class="hint">建议150-160个字符，用于搜索引擎结果展示</div>
            </div>
            <div class="form-group">
                <label>Meta关键词</label>
                <input type="text" name="home_meta_keywords" value="<?= htmlspecialchars($data['home_meta_keywords'] ?? '') ?>" placeholder="例如：AI写作,小说创作,智能创作">
                <div class="hint">多个关键词用英文逗号分隔</div>
            </div>
        </div>

        <!-- 联系信息设置 -->
        <div class="settings-section">
            <h3><span class="section-icon"><i class="fas fa-phone-alt"></i></span>联系信息设置</h3>
            <div class="form-row">
                <div class="form-group">
                    <label>联系邮箱</label>
                    <input type="email" name="site_contact_email" value="<?= htmlspecialchars($data['site_contact_email'] ?? '') ?>" placeholder="例如：support@example.com">
                    <div class="hint">显示在页脚联系信息区域</div>
                </div>
                <div class="form-group">
                    <label>联系电话</label>
                    <input type="text" name="site_contact_phone" value="<?= htmlspecialchars($data['site_contact_phone'] ?? '') ?>" placeholder="例如：400-888-8888">
                    <div class="hint">显示在页脚联系信息区域</div>
                </div>
                <div class="form-group">
                    <label>工作时间</label>
                    <input type="text" name="site_contact_hours" value="<?= htmlspecialchars($data['site_contact_hours'] ?? '') ?>" placeholder="例如：工作日 9:00-18:00">
                    <div class="hint">显示在页脚联系信息区域</div>
                </div>
            </div>
        </div>

        <!-- 社交媒体设置 -->
        <div class="settings-section">
            <h3><span class="section-icon"><i class="fas fa-share-alt"></i></span>社交媒体链接</h3>
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fab fa-weixin" style="color: #07c160;"></i> 微信</label>
                    <input type="text" name="site_social_wechat" value="<?= htmlspecialchars($data['site_social_wechat'] ?? '') ?>" placeholder="微信公众号链接或二维码图片URL">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-weibo" style="color: #e6162d;"></i> 微博</label>
                    <input type="text" name="site_social_weibo" value="<?= htmlspecialchars($data['site_social_weibo'] ?? '') ?>" placeholder="微博主页链接">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-qq" style="color: #12b7f5;"></i> QQ</label>
                    <input type="text" name="site_social_qq" value="<?= htmlspecialchars($data['site_social_qq'] ?? '') ?>" placeholder="QQ群链接或QQ号">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-bilibili" style="color: #00a1d6;"></i> 哔哩哔哩</label>
                    <input type="text" name="site_social_bilibili" value="<?= htmlspecialchars($data['site_social_bilibili'] ?? '') ?>" placeholder="B站主页链接">
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <button type="submit" class="btn-save">
                <i class="fas fa-save"></i> 保存设置
            </button>
        </div>
    </form>
</div>
