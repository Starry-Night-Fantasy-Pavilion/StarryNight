 <?php
$h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$shareId = (int)($resource['id'] ?? 0);
$price = (int)($resource['price'] ?? 0);
$isPurchased = !empty($userPurchase);
$isFavorited = !empty($userFavorite);

$avgRating = (float)($ratingStats['avg_rating'] ?? 0);
$totalRatings = (int)($ratingStats['total_ratings'] ?? 0);

// 资源类型标签映射
$typeKey = (string)($resource['resource_type'] ?? '');
$typeLabels = [
    'knowledge' => '知识库',
    'prompt' => '提示词',
    'template' => '模板',
    'agent' => '智能体',
];
$typeLabel = $typeLabels[$typeKey] ?? $typeKey;
?>
<div
    class="page-share-detail"
    data-share-id="<?= $shareId ?>"
    data-price="<?= $price ?>"
    data-purchased="<?= $isPurchased ? '1' : '0' ?>"
    data-favorited="<?= $isFavorited ? '1' : '0' ?>"
>
    <div class="container">
            <div class="share-detail-header">
                <a href="/share" class="back-link">← 返回资源分享</a>
                <h1 class="share-detail-title"><?= $h($resource['title'] ?? '') ?></h1>
                <div class="share-detail-meta">
                    <span class="resource-type"><?= $h($typeLabel) ?></span>
                    <span class="resource-author">作者: <?= $h($resource['user_nickname'] ?? $resource['username'] ?? '匿名') ?></span>
                    <span>浏览：<?= (int)($resource['view_count'] ?? 0) ?></span>
                    <span>下载：<?= (int)($resource['download_count'] ?? 0) ?></span>
                </div>
            </div>

        <div class="share-detail-content">
            <div class="resource-description">
                <h2>资源描述</h2>
                <p><?= nl2br($h($resource['description'] ?? '暂无描述')) ?></p>

                <?php if (!empty($detail)): ?>
                    <h2 style="margin-top:16px;font-size:1rem;">资源详情预览</h2>

                    <?php $type = $resource['resource_type'] ?? ''; ?>

                    <?php if ($type === 'knowledge'): ?>
                        <p style="font-size:0.9rem;color:#9ca3af;margin-bottom:8px;">
                            以下为公开知识库的基础信息预览，完整内容可在导入后在知识库模块中查看。
                        </p>
                        <ul style="font-size:0.9rem;color:#e5e7eb;line-height:1.7;margin-left:1.2em;">
                            <li>知识库名称：<?= $h($detail['title'] ?? $detail['name'] ?? '') ?></li>
                            <li>简介：<?= $h($detail['description'] ?? '') ?></li>
                            <li>条目数量：<?= (int)($detail['item_count'] ?? 0) ?></li>
                        </ul>
                    <?php elseif ($type === 'prompt'): ?>
                        <p style="font-size:0.9rem;color:#9ca3af;margin-bottom:8px;">
                            以下为提示词的主要内容片段，具体使用效果以实际调用为准。
                        </p>
                        <pre style="background:rgba(15,23,42,0.9);border-radius:8px;padding:10px 12px;font-size:0.9rem;white-space:pre-wrap;line-height:1.6;border:1px solid rgba(148,163,184,0.4);">
<?= nl2br($h(mb_substr($detail['content'] ?? ($detail['prompt'] ?? ''), 0, 400))) ?><?= mb_strlen($detail['content'] ?? ($detail['prompt'] ?? '')) > 400 ? '…' : '' ?>
                        </pre>
                    <?php elseif ($type === 'template'): ?>
                        <p style="font-size:0.9rem;color:#9ca3af;margin-bottom:8px;">
                            模板关键信息预览，应用后可在对应创作流程中看到完整字段。
                        </p>
                        <ul style="font-size:0.9rem;color:#e5e7eb;line-height:1.7;margin-left:1.2em;">
                            <li>模板名称：<?= $h($detail['title'] ?? '') ?></li>
                            <li>模板类型：<?= $h($detail['category'] ?? '') ?></li>
                            <li>适用场景：<?= $h($detail['scene'] ?? $detail['usage'] ?? '') ?></li>
                        </ul>
                    <?php elseif ($type === 'agent'): ?>
                        <p style="font-size:0.9rem;color:#9ca3af;margin-bottom:8px;">
                            智能体的角色设定与能力简介预览，导入后可在智能体中心查看完整配置。
                        </p>
                        <ul style="font-size:0.9rem;color:#e5e7eb;line-height:1.7;margin-left:1.2em;">
                            <li>智能体名称：<?= $h($detail['name'] ?? '') ?></li>
                            <li>角色定位：<?= $h($detail['role'] ?? '') ?></li>
                            <li>能力说明：<?= $h($detail['description'] ?? '') ?></li>
                        </ul>
                    <?php else: ?>
                        <p style="font-size:0.9rem;color:#9ca3af;">
                            该资源类型暂未提供更详细的预览，导入或购买后可在对应模块中完整使用。
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="resource-sidebar">
                <div class="resource-actions-card">
                    <div class="resource-price">
                        <?php if ($price <= 0): ?>
                            <span class="resource-price-free">免费资源</span>
                        <?php else: ?>
                            <span class="resource-price-paid"><?= $price ?> 星夜币</span>
                        <?php endif; ?>
                    </div>
                    <div class="resource-actions-buttons">
                        <?php if ($price > 0 && !$isPurchased): ?>
                            <button class="btn btn-primary js-share-purchase">立即购买</button>
                        <?php elseif ($price > 0 && $isPurchased): ?>
                            <button class="btn btn-disabled" disabled>已购买</button>
                        <?php else: ?>
                            <button class="btn btn-disabled" disabled>免费资源</button>
                        <?php endif; ?>

                        <button class="btn btn-outline js-share-favorite">
                            <?= $isFavorited ? '已收藏' : '收藏' ?>
                        </button>

                        <button class="btn btn-ghost js-share-import">
                            导入到我的库
                        </button>
                    </div>
                    <div class="share-meta-secondary">
                        <div>评分：<?= $avgRating ?> / 5（共 <?= $totalRatings ?> 条评价）</div>
                        <div>收藏：<?= (int)($resource['favorite_count'] ?? 0) ?></div>
                    </div>
                </div>

                <div class="resource-rating-card">
                    <div class="share-rating-summary">
                        <div class="share-rating-score"><?= $avgRating ?></div>
                        <div class="share-rating-count"><?= $totalRatings ?> 条评价</div>
                    </div>

                    <div class="share-rating-stars">
                        <?php
                        $userRatingValue = isset($userRating['rating']) ? (int)$userRating['rating'] : 0;
                        for ($i = 1; $i <= 5; $i++):
                            $inactive = $i > $userRatingValue;
                        ?>
                            <button
                                type="button"
                                class="js-share-rating-star<?= $inactive ? ' inactive' : '' ?>"
                                data-value="<?= $i ?>"
                                aria-label="评分 <?= $i ?> 星"
                            >★</button>
                        <?php endfor; ?>
                    </div>

                    <form class="share-rating-form" onsubmit="return false;">
                        <input type="hidden" name="rating" value="<?= $userRatingValue ?: 0 ?>">
                        <textarea
                            name="rating_comment"
                            placeholder="写下你的评价（可选）"
                        ><?= $h($userRating['comment'] ?? '') ?></textarea>
                        <button class="btn btn-primary btn-sm js-share-rate-submit" type="button">
                            提交评价
                        </button>
                    </form>

                    <?php if (!empty($ratings)): ?>
                        <div class="share-rating-list">
                            <?php foreach ($ratings as $item): ?>
                                <div class="share-rating-item">
                                    <div class="share-rating-item-header">
                                        <span><?= $h($item['user_nickname'] ?? $item['username'] ?? '用户') ?></span>
                                        <span><?= (int)$item['rating'] ?> ★</span>
                                    </div>
                                    <?php if (!empty($item['comment'])): ?>
                                        <div class="share-rating-item-comment">
                                            <?= nl2br($h($item['comment'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <a href="/share" class="btn btn-outline">返回列表</a>
            </div>
        </div>
    </div>
</div>
