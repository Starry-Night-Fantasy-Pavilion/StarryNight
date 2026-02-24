<?php
use app\models\Setting;
$siteName = Setting::get('site_name') ?: (string)get_env('APP_NAME', '星夜阁');
?>
<div class="page-header">
    <div class="container">
        <h1 class="page-title">提示词库</h1>
        <p class="page-subtitle">海量专业AI提示词，助力创作更优质内容</p>
    </div>
</div>

<div class="container">
    <!-- 搜索和筛选 -->
    <div class="prompts-filters">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="promptSearch" placeholder="搜索提示词...">
        </div>
        <div class="filter-tabs">
            <button class="filter-tab active" data-category="all">全部</button>
            <button class="filter-tab" data-category="character">角色描写</button>
            <button class="filter-tab" data-category="scene">场景描述</button>
            <button class="filter-tab" data-category="emotion">情感表达</button>
            <button class="filter-tab" data-category="dialogue">对话生成</button>
            <button class="filter-tab" data-category="plot">情节转折</button>
            <button class="filter-tab" data-category="worldview">世界观构建</button>
        </div>
    </div>

    <!-- 热门提示词 -->
    <section class="prompts-section">
        <div class="section-header">
            <h2 class="section-title">热门提示词</h2>
            <div class="section-actions">
                <button class="btn btn-outline btn-sm" id="toggleView">
                    <i class="fas fa-th"></i>
                </button>
            </div>
        </div>
        
        <div class="prompts-grid" id="promptsGrid">
            <!-- 角色描写提示词 -->
            <div class="prompt-card" data-category="character">
                <div class="prompt-header">
                    <div class="prompt-category">角色描写</div>
                    <div class="prompt-stats">
                        <span><i class="fas fa-star"></i> 4.9</span>
                        <span><i class="fas fa-download"></i> 2.3k</span>
                    </div>
                </div>
                <h3 class="prompt-title">立体角色塑造</h3>
                <p class="prompt-description">创建一个立体、有深度的角色，包括其背景故事、性格特点、行为模式和成长轨迹</p>
                <div class="prompt-tags">
                    <span class="tag">角色塑造</span>
                    <span class="tag">人物描写</span>
                    <span class="tag">专业级</span>
                </div>
                <div class="prompt-actions">
                    <button class="btn btn-primary btn-sm use-prompt">使用提示词</button>
                    <button class="btn btn-outline btn-sm save-prompt">收藏</button>
                </div>
            </div>

            <!-- 场景描述提示词 -->
            <div class="prompt-card" data-category="scene">
                <div class="prompt-header">
                    <div class="prompt-category">场景描述</div>
                    <div class="prompt-stats">
                        <span><i class="fas fa-star"></i> 4.8</span>
                        <span><i class="fas fa-download"></i> 1.8k</span>
                    </div>
                </div>
                <h3 class="prompt-title">沉浸式场景描写</h3>
                <p class="prompt-description">用丰富的感官描写创建一个身临其境的场景，包括视觉、听觉、嗅觉等多维度</p>
                <div class="prompt-tags">
                    <span class="tag">场景描写</span>
                    <span class="tag">感官体验</span>
                    <span class="tag">环境描写</span>
                </div>
                <div class="prompt-actions">
                    <button class="btn btn-primary btn-sm use-prompt">使用提示词</button>
                    <button class="btn btn-outline btn-sm save-prompt">收藏</button>
                </div>
            </div>

            <!-- 情节转折提示词 -->
            <div class="prompt-card" data-category="plot">
                <div class="prompt-header">
                    <div class="prompt-category">情节转折</div>
                    <div class="prompt-stats">
                        <span><i class="fas fa-star"></i> 4.7</span>
                        <span><i class="fas fa-download"></i> 1.5k</span>
                    </div>
                </div>
                <h3 class="prompt-title">出人意料的情节转折</h3>
                <p class="prompt-description">设计出人意料但合乎逻辑的情节转折，在保持故事连贯性的同时增加戏剧张力</p>
                <div class="prompt-tags">
                    <span class="tag">情节设计</span>
                    <span class="tag">转折技巧</span>
                    <span class="tag">戏剧张力</span>
                </div>
                <div class="prompt-actions">
                    <button class="btn btn-primary btn-sm use-prompt">使用提示词</button>
                    <button class="btn btn-outline btn-sm save-prompt">收藏</button>
                </div>
            </div>

            <!-- 对话生成提示词 -->
            <div class="prompt-card" data-category="dialogue">
                <div class="prompt-header">
                    <div class="prompt-category">对话生成</div>
                    <div class="prompt-stats">
                        <span><i class="fas fa-star"></i> 4.6</span>
                        <span><i class="fas fa-download"></i> 1.2k</span>
                    </div>
                </div>
                <h3 class="prompt-title">生动对话创作</h3>
                <p class="prompt-description">创作符合角色性格的生动对话，体现人物关系和情感状态</p>
                <div class="prompt-tags">
                    <span class="tag">对话技巧</span>
                    <span class="tag">人物关系</span>
                    <span class="tag">情感表达</span>
                </div>
                <div class="prompt-actions">
                    <button class="btn btn-primary btn-sm use-prompt">使用提示词</button>
                    <button class="btn btn-outline btn-sm save-prompt">收藏</button>
                </div>
            </div>

            <!-- 情感表达提示词 -->
            <div class="prompt-card" data-category="emotion">
                <div class="prompt-header">
                    <div class="prompt-category">情感表达</div>
                    <div class="prompt-stats">
                        <span><i class="fas fa-star"></i> 4.8</span>
                        <span><i class="fas fa-download"></i> 1.6k</span>
                    </div>
                </div>
                <h3 class="prompt-title">细腻情感描写</h3>
                <p class="prompt-description">通过细节描写和内心独白，深刻表达角色的复杂情感状态</p>
                <div class="prompt-tags">
                    <span class="tag">情感描写</span>
                    <span class="tag">心理活动</span>
                    <span class="tag">细腻表达</span>
                </div>
                <div class="prompt-actions">
                    <button class="btn btn-primary btn-sm use-prompt">使用提示词</button>
                    <button class="btn btn-outline btn-sm save-prompt">收藏</button>
                </div>
            </div>

            <!-- 世界观构建提示词 -->
            <div class="prompt-card" data-category="worldview">
                <div class="prompt-header">
                    <div class="prompt-category">世界观构建</div>
                    <div class="prompt-stats">
                        <span><i class="fas fa-star"></i> 4.7</span>
                        <span><i class="fas fa-download"></i> 1.0k</span>
                    </div>
                </div>
                <h3 class="prompt-title">完整世界观设定</h3>
                <p class="prompt-description">构建完整的故事世界观，包括地理环境、社会制度、文化背景等要素</p>
                <div class="prompt-tags">
                    <span class="tag">世界观</span>
                    <span class="tag">设定构建</span>
                    <span class="tag">背景设定</span>
                </div>
                <div class="prompt-actions">
                    <button class="btn btn-primary btn-sm use-prompt">使用提示词</button>
                    <button class="btn btn-outline btn-sm save-prompt">收藏</button>
                </div>
            </div>
        </div>

        <!-- 加载更多 -->
        <div class="load-more">
            <button class="btn btn-outline" id="loadMorePrompts">加载更多</button>
        </div>
    </section>

    <!-- 创建提示词 -->
    <section class="create-prompt-section">
        <div class="section-header">
            <h2 class="section-title">创建提示词</h2>
            <p class="section-subtitle">分享您的创作经验，帮助更多创作者</p>
        </div>
        
        <div class="create-prompt-form">
            <div class="form-group">
                <label for="promptTitle">提示词标题</label>
                <input type="text" id="promptTitle" class="form-control" placeholder="输入提示词标题">
            </div>
            
            <div class="form-group">
                <label for="promptCategory">分类</label>
                <select id="promptCategory" class="form-control">
                    <option value="character">角色描写</option>
                    <option value="scene">场景描述</option>
                    <option value="emotion">情感表达</option>
                    <option value="dialogue">对话生成</option>
                    <option value="plot">情节转折</option>
                    <option value="worldview">世界观构建</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="promptDescription">描述</label>
                <textarea id="promptDescription" class="form-control" rows="4" placeholder="详细描述这个提示词的用途和效果"></textarea>
            </div>
            
            <div class="form-group">
                <label for="promptContent">提示词内容</label>
                <textarea id="promptContent" class="form-control" rows="6" placeholder="输入完整的提示词内容"></textarea>
            </div>
            
            <div class="form-group">
                <label for="promptTags">标签</label>
                <input type="text" id="promptTags" class="form-control" placeholder="输入标签，用逗号分隔">
            </div>
            
            <div class="form-actions">
                <button class="btn btn-primary" id="submitPrompt">提交提示词</button>
                <button class="btn btn-outline" id="saveDraft">保存草稿</button>
            </div>
        </div>
    </section>
</div>

<!-- 提示词详情模态框 -->
<div class="modal" id="promptModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">提示词详情</h3>
            <button class="modal-close" id="closeModal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="prompt-detail">
                <div class="prompt-detail-header">
                    <h4 class="prompt-detail-title"></h4>
                    <div class="prompt-detail-meta">
                        <span class="prompt-detail-category"></span>
                        <div class="prompt-detail-stats">
                            <span><i class="fas fa-star"></i> <span class="rating"></span></span>
                            <span><i class="fas fa-download"></i> <span class="downloads"></span></span>
                        </div>
                    </div>
                </div>
                <div class="prompt-detail-description"></div>
                <div class="prompt-detail-content">
                    <h5>提示词内容</h5>
                    <div class="prompt-content-box"></div>
                </div>
                <div class="prompt-detail-tags"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" id="usePromptInModal">使用提示词</button>
            <button class="btn btn-outline" id="savePromptInModal">收藏</button>
        </div>
    </div>
</div>