<?php $this->layout('layout', ['title' => htmlspecialchars($project->title)]) ?>

<div class="container">
    <div class="row">
        <div class="col-md-8">
            <h1><?= htmlspecialchars($project->title) ?></h1>
            <p class="lead">由 <?= htmlspecialchars($project->user_id) // In a real app, you'd fetch the user's name ?> 发起</p>
            <hr>
            
            <div class="project-description mb-4">
                <?= nl2br(htmlspecialchars($project->description)) ?>
            </div>

            <hr>

            <h3>项目更新</h3>
            <div class="updates-section">
                <?php if (empty($updates)): ?>
                    <p>暂无项目更新。</p>
                <?php else: ?>
                    <?php foreach ($updates as $update): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($update->title) ?></h5>
                                <p class="card-text"><?= nl2br(htmlspecialchars($update->content)) ?></p>
                                <p class="card-text"><small class="text-muted">发布于 <?= $update->created_at ?></small></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">项目进度</h4>
                    <p>
                        <strong>已筹集:</strong> <?= htmlspecialchars($project->current_amount) ?> 星夜币<br>
                        <strong>目标:</strong> <?= htmlspecialchars($project->goal_amount) ?> 星夜币
                    </p>
                    <?php 
                        $progress = ($project->goal_amount > 0) ? ($project->current_amount / $project->goal_amount) * 100 : 0;
                    ?>
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"><?= round($progress) ?>%</div>
                    </div>
                    <p>
                        <strong>支持者:</strong> <?= count($pledges) ?> 人<br>
                        <strong>剩余时间:</strong> <?= (new DateTime($project->end_date))->diff(new DateTime())->format('%a 天') ?>
                    </p>

                    <hr>

                    <h4>选择回报</h4>
                    <form id="pledge-form">
                        <input type="hidden" name="project_id" value="<?= $project->id ?>">
                        <?php if (empty($rewards)): ?>
                            <p>该项目没有设置回报。</p>
                            <div class="form-group">
                                <label for="pledge-amount">支持任意金额</label>
                                <input type="number" class="form-control" id="pledge-amount" name="amount" placeholder="输入支持金额" required>
                            </div>
                        <?php else: ?>
                            <?php foreach ($rewards as $reward): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="reward_id" id="reward-<?= $reward->id ?>" value="<?= $reward->id ?>" data-amount="<?= $reward->pledge_amount ?>">
                                    <label class="form-check-label" for="reward-<?= $reward->id ?>">
                                        <strong><?= htmlspecialchars($reward->title) ?> - <?= htmlspecialchars($reward->pledge_amount) ?> 星夜币</strong>
                                        <p><?= htmlspecialchars($reward->description) ?></p>
                                        <?php if ($reward->limit): ?>
                                            <small class="text-muted">限量 <?= $reward->limit ?> 份</small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <div class="form-group mt-3">
                                <label for="pledge-amount">或者支持任意金额</label>
                                <input type="number" class="form-control" id="pledge-amount" name="amount" placeholder="输入支持金额">
                            </div>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-success btn-block mt-3">立即支持</button>
                    </form>
                    <div id="pledge-result" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->push('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('pledge-form');
    const resultDiv = document.getElementById('pledge-result');
    const amountInput = document.getElementById('pledge-amount');

    // Update amount when a reward is selected
    form.querySelectorAll('input[name="reward_id"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                amountInput.value = this.dataset.amount;
                amountInput.readOnly = true;
            }
        });
    });
    
    // Allow custom amount if text input is focused
    amountInput.addEventListener('focus', function() {
        amountInput.readOnly = false;
        form.querySelectorAll('input[name="reward_id"]').forEach(radio => {
            radio.checked = false;
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        resultDiv.innerHTML = '';

        const formData = new FormData(form);
        const finalAmount = amountInput.value;
        formData.set('amount', finalAmount); // Ensure the correct amount is set

        if (!finalAmount || finalAmount <= 0) {
            resultDiv.innerHTML = '<div class="alert alert-danger">请输入有效的支持金额。</div>';
            return;
        }

        fetch('/crowdfunding/pledge/' + formData.get('project_id'), {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                form.reset();
                // Optionally, update project stats on the page
                location.reload(); // Simple way to show updated stats
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultDiv.innerHTML = '<div class="alert alert-danger">发生错误，请稍后再试。</div>';
        });
    });
});
</script>
<?php $this->end() ?>
