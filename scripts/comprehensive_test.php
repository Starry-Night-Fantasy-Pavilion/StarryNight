<?php
// scripts/comprehensive_test.php

// Simple autoloader
spl_autoload_register(function ($class) {
    // Correctly escape the backslash for single-quoted strings
    $file = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use app\models\User;
use app\models\MembershipPackage;
use app\models\UserLimit;
use app\models\RechargePackage;
use app\models\RechargeRecord;
use app\models\UserTokenBalance;
use app\models\VipBenefit;
use app\services\Database;

echo <<<EOT
Starting comprehensive test...

EOT;

try {
    $pdo = Database::pdo();
    $prefix = Database::prefix();

    // 1. Create a test user
    echo "1. Creating a test user...\n";
    $testUserEmail = 'comprehensive_test@example.com';
    
    $stmt = $pdo->prepare("SELECT id FROM `{$prefix}users` WHERE email = ?");
    $stmt->execute([$testUserEmail]);
    $existingUser = $stmt->fetch();
    if ($existingUser) {
        $pdo->prepare("DELETE FROM `{$prefix}users` WHERE id = ?")->execute([$existingUser['id']]);
        echo "Cleaned up existing test user.\n";
    }
    
    $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO `{$prefix}users` (username, email, password, nickname, status, vip_type, vip_expire_at, created_at) VALUES (?, ?, ?, ?, 'active', 0, NULL, NOW())");
    $stmt->execute(['comprehensivetest', $testUserEmail, $hashedPassword, 'Comprehensive Test User']);
    $userId = $pdo->lastInsertId();
    
    if (!$userId) {
        throw new Exception("Failed to create test user.");
    }
    echo "Test user created with ID: $userId\n\n";

    // 1.5. Create a test membership level
    echo "1.5. Creating a test membership level...\n";
    $levelName = 'Test Level';
    $stmt = $pdo->prepare("SELECT id FROM `{$prefix}membership_levels` WHERE name = ?");
    $stmt->execute([$levelName]);
    $existingLevel = $stmt->fetch();
    if ($existingLevel) {
        $levelId = $existingLevel['id'];
        echo "Found existing test level with ID: $levelId\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}membership_levels` (name, `level`, description) VALUES (?, ?, ?)");
        $stmt->execute([$levelName, 1, 'Test Level Description']);
        $levelId = $pdo->lastInsertId();
        if (!$levelId) {
            throw new Exception("Failed to create test membership level.");
        }
        echo "Created test level with ID: $levelId\n";
    }
    echo "
";

    // 1.6. Creating and associating VIP benefits...
    echo "1.6. Creating and associating VIP benefits...\n";
    $benefitKey = 'ai_music_composition';
    $stmt = $pdo->prepare("SELECT id FROM `{$prefix}vip_benefits` WHERE benefit_key = ?");
    $stmt->execute([$benefitKey]);
    $existingBenefit = $stmt->fetch();
    if ($existingBenefit) {
        $benefitId = $existingBenefit['id'];
        echo "Found existing benefit '$benefitKey' with ID: $benefitId\n";
    } else {
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}vip_benefits` (benefit_key, benefit_name, benefit_type, description, is_enabled) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$benefitKey, 'AI Music Composition', 'feature', 'Access to AI music composition features.']);
        $benefitId = $pdo->lastInsertId();
        if (!$benefitId) {
            throw new Exception("Failed to create vip benefit.");
        }
        echo "Created benefit '$benefitKey' with ID: $benefitId\n";
    }

    $stmt = $pdo->prepare("SELECT * FROM `{$prefix}membership_level_benefits` WHERE membership_level_id = ? AND benefit_id = ?");
    $stmt->execute([$levelId, $benefitId]);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO `{$prefix}membership_level_benefits` (membership_level_id, benefit_id) VALUES (?, ?)");
        if (!$stmt->execute([$levelId, $benefitId])) {
            throw new Exception("Failed to associate benefit with membership level.");
        }
        echo "Associated benefit with membership level.\n";
    } else {
        echo "Benefit already associated with membership level.\n";
    }
    echo "
";

    // 2. Create a VIP membership package
    echo "2. Creating a VIP membership package...
";
    $vipPackageData = [
        'name' => 'Test VIP Package',
        'type' => 1, // Monthly
        'membership_level_id' => $levelId,
        'duration_days' => 30,
        'original_price' => 50.00,
        'features' => json_encode(['ai_music_composition', 'advanced_export']),
        'is_enabled' => 1,
    ];
    $stmt = $pdo->prepare("SELECT id FROM `{$prefix}membership_packages` WHERE name = ?");
    $stmt->execute([$vipPackageData['name']]);
    $existingPackage = $stmt->fetch();
    if ($existingPackage) {
        MembershipPackage::delete($existingPackage['id']);
        echo "Cleaned up existing VIP package.\n";
    }

    if (!MembershipPackage::save($vipPackageData)) {
        throw new Exception("Failed to save VIP package.");
    }
    
    $stmt->execute([$vipPackageData['name']]);
    $newPackage = $stmt->fetch();
    if($newPackage) {
        $vipPackageId = $newPackage['id'];
    } else {
         throw new Exception("Failed to find created VIP package.");
    }
    echo "VIP package created with ID: $vipPackageId

";

    // 3. Activate membership for the user
    echo "3. Activating membership for user ID $userId...
";
    $activationResult = MembershipPackage::activateMembership($userId, $vipPackageId, 'test_purchase');
    if (!$activationResult) {
        throw new Exception("Failed to activate membership.");
    }
    echo "Membership activated successfully.
";
    $userMembership = MembershipPackage::getUserMembership($userId);
    if (!$userMembership || $userMembership['is_expired']) {
        throw new Exception("Membership status is incorrect after activation.");
    }
    echo "User membership status is correct.

";

    // 4. Check if user limits are updated to VIP defaults
    echo "4. Checking user limits...
";
    $userLimits = UserLimit::getLimitsByUserId($userId);
    $vipDefaults = UserLimit::getVipDefaultLimits();
    if ($userLimits['max_novels'] !== $vipDefaults['max_novels']) {
        throw new Exception("User limits were not updated to VIP defaults. Expected max_novels to be {$vipDefaults['max_novels']}, but got {$userLimits['max_novels']}");
    }
    echo "User limits correctly updated to VIP defaults.

";

    // 5. Create a recharge package
    echo "5. Creating a recharge package...
";
    $rechargePackageData = [
        'name' => 'Test Recharge Package',
        'tokens' => 10000,
        'price' => 50.00,
        'bonus_tokens' => 2000,
        'is_enabled' => 1,
    ];
    $stmt = $pdo->prepare("SELECT id FROM `{$prefix}recharge_packages` WHERE name = ?");
    $stmt->execute([$rechargePackageData['name']]);
    $existingRechargePackage = $stmt->fetch();
    if ($existingRechargePackage) {
        RechargePackage::delete($existingRechargePackage['id']);
        echo "Cleaned up existing recharge package.\n";
    }
    if (!RechargePackage::save($rechargePackageData)) {
        throw new Exception("Failed to save recharge package.");
    }
    $stmt->execute([$rechargePackageData['name']]);
    $newRechargePackage = $stmt->fetch();
    if(!$newRechargePackage) {
        throw new Exception("Failed to find created recharge package.");
    }
    $rechargePackageId = $newRechargePackage['id'];
    echo "Recharge package created with ID: $rechargePackageId

";

    // 6. Simulate a recharge
    echo "6. Simulating a recharge for user ID $userId...
";
    $orderNo = RechargeRecord::create($userId, $rechargePackageId, 'test_payment');
    if (!$orderNo) {
        throw new Exception("Failed to create recharge record (order).");
    }
    echo "Recharge order created with Order No: $orderNo
";
    
    // 7. Complete the recharge
    echo "7. Completing the recharge...
";
    $updateStatusResult = RechargeRecord::updateStatus($orderNo, 'paid', 'txn_test12345');
    if (!$updateStatusResult) {
        throw new Exception("Failed to update recharge status to 'paid'.");
    }
    $rechargeRecord = RechargeRecord::getByOrderNo($orderNo);
    if ($rechargeRecord['payment_status'] !== 'paid') {
        throw new Exception("Recharge record status is not 'paid'.");
    }
    echo "Recharge completed successfully.
";

    // 8. Add tokens to user balance
    echo "8. Adding tokens to user balance...
";
    $addTokensResult = UserTokenBalance::addTokens($userId, $rechargeRecord['total_tokens'], 'recharge', 'Test Recharge', $rechargeRecord['id'], 'recharge_record');
    if (!$addTokensResult) {
        throw new Exception("Failed to add tokens to user balance.");
    }
    $userBalance = UserTokenBalance::getByUserId($userId);
    if ($userBalance['balance'] != $rechargeRecord['total_tokens']) {
        throw new Exception("User token balance is incorrect. Expected {$rechargeRecord['total_tokens']}, got {$userBalance['balance']}");
    }
    echo "User token balance updated correctly. Current balance: {$userBalance['balance']}

";

    // 9. Simulate token consumption
    echo "9. Simulating token consumption...
";
    $consumptionAmount = 500;
    $consumeResult = UserTokenBalance::consumeTokens($userId, $consumptionAmount, 'ai_generation', 'Test AI Generation');
    if (!$consumeResult) {
        throw new Exception("Failed to consume tokens.");
    }
    $userBalanceAfterConsumption = UserTokenBalance::getByUserId($userId);
    $expectedBalance = $userBalance['balance'] - $consumptionAmount;
    if ($userBalanceAfterConsumption['balance'] != $expectedBalance) {
        throw new Exception("User token balance is incorrect after consumption. Expected {$expectedBalance}, got {$userBalanceAfterConsumption['balance']}");
    }
    echo "Token consumption successful. Current balance: {$userBalanceAfterConsumption['balance']}
";
    
    // 10. Check consumption record
    echo "10. Checking consumption record...
";
    $consumptionRecords = UserTokenBalance::getConsumptionRecords($userId, 1, 1);
    $lastRecord = $consumptionRecords['records'][0];
    if ($lastRecord['tokens'] != -$consumptionAmount || $lastRecord['consumption_type'] !== 'ai_generation') {
        throw new Exception("Consumption record is incorrect.");
    }
    echo "Consumption record created correctly.

";

    // 11. Check user benefits
    echo "11. Checking user benefits...
";
    $userBenefits = VipBenefit::getUserBenefits($userId);
    if (empty($userBenefits)) {
        throw new Exception("Failed to get user benefits or user has no benefits.");
    }
    $benefitKeys = array_column($userBenefits, 'benefit_key');
    if (!in_array('ai_music_composition', $benefitKeys)) {
        throw new Exception("User is missing expected VIP benefit 'ai_music_composition'.");
    }
    echo "User benefits are correct. User has " . count($userBenefits) . " benefits.

";

    echo "Comprehensive test completed successfully!
";

} catch (Exception $e) {
    echo "\n\nTEST FAILED: " . $e->getMessage() . "\n";
    echo "At " . $e->getFile() . ":" . $e->getLine() . "\n";
}
