<?php
// 会员统计报表
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>会员统计</title>
</head>
<body>
    <h1>会员统计（<?= htmlspecialchars((string)($period ?? 'month')) ?>）</h1>

    <form method="get">
        <label>
            统计周期：
            <select name="period">
                <option value="day" <?= (isset($period) && $period === 'day') ? 'selected' : '' ?>>按日</option>
                <option value="week" <?= (isset($period) && $period === 'week') ? 'selected' : '' ?>>按周</option>
                <option value="month" <?= (!isset($period) || $period === 'month') ? 'selected' : '' ?>>按月</option>
                <option value="year" <?= (isset($period) && $period === 'year') ? 'selected' : '' ?>>按年</option>
            </select>
        </label>
        <button type="submit">刷新</button>
    </form>

    <h2>会员购买统计</h2>
    <pre><?php echo htmlspecialchars(json_encode($membershipStats ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>

    <h2>充值统计</h2>
    <pre><?php echo htmlspecialchars(json_encode($rechargeStats ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>

    <h2>会员类型分布</h2>
    <pre><?php echo htmlspecialchars(json_encode($membershipTypeDistribution ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?></pre>
</body>
</html>

