<?php $this->layout('layout', ['title' => '发起新项目']) ?>

<div class="container">
    <h1>发起您的 AI 创作众筹项目</h1>
    <p>分享您的创意，让大家帮助您实现！</p>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/crowdfunding/create" id="create-project-form">
        <div class="form-group">
            <label for="title">项目标题</label>
            <input type="text" class="form-control" id="title" name="title" required>
        </div>

        <div class="form-group">
            <label for="description">项目详情</label>
            <textarea class="form-control" id="description" name="description" rows="10" required></textarea>
        </div>

        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="goal_amount">众筹目标 (星夜币)</label>
                <input type="number" class="form-control" id="goal_amount" name="goal_amount" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group col-md-6">
                <label for="start_date">开始日期</label>
                <input type="date" class="form-control" id="start_date" name="start_date" required>
            </div>
            <div class="form-group col-md-6">
                <label for="end_date">结束日期</label>
                <input type="date" class="form-control" id="end_date" name="end_date" required>
            </div>
        </div>

        <hr>

        <h3>设置回报</h3>
        <div id="rewards-container">
            <!-- Reward fields will be added here dynamically -->
        </div>
        <button type="button" class="btn btn-secondary" id="add-reward-btn">添加一个回报</button>

        <hr>

        <button type="submit" class="btn btn-primary btn-lg">提交项目审核</button>
    </form>
</div>

<template id="reward-template">
    <div class="reward-item card mb-3">
        <div class="card-body">
            <div class="form-group">
                <label>回报标题</label>
                <input type="text" class="form-control" name="rewards[][title]" required>
            </div>
            <div class="form-group">
                <label>回报描述</label>
                <textarea class="form-control" name="rewards[][description]" rows="3" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>支持金额 (星夜币)</label>
                    <input type="number" class="form-control" name="rewards[][pledge_amount]" required>
                </div>
                <div class="form-group col-md-4">
                    <label>限量 (可选)</label>
                    <input type="number" class="form-control" name="rewards[][limit]">
                </div>
                <div class="form-group col-md-4">
                    <label>预计回报发放日期 (可选)</label>
                    <input type="date" class="form-control" name="rewards[][delivery_date]">
                </div>
            </div>
            <button type="button" class="btn btn-danger btn-sm remove-reward-btn">移除此回报</button>
        </div>
    </div>
</template>

<?php $this->push('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rewardsContainer = document.getElementById('rewards-container');
    const addRewardBtn = document.getElementById('add-reward-btn');
    const rewardTemplate = document.getElementById('reward-template');

    addRewardBtn.addEventListener('click', function() {
        const rewardNode = rewardTemplate.content.cloneNode(true);
        rewardsContainer.appendChild(rewardNode);
    });

    rewardsContainer.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-reward-btn')) {
            e.target.closest('.reward-item').remove();
        }
    });
});
</script>
<?php $this->end() ?>
