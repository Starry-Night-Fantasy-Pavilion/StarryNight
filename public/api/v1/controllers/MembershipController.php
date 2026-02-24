<?php

namespace api\v1\controllers;

// Assuming a session utility exists to get the current user
// session_start();

use app\models\User;
use app\models\MembershipPackage;
use app\models\UserTokenBalance;
use app\models\UserLimit;
use app\models\RechargePackage;
use app\models\MembershipPurchaseRecord;
use app\models\RechargeRecord;
use app\models\TokenConsumptionRecord;
use app\models\Feature;

class MembershipController
{
    private $userId;

    public function __construct()
    {
        // In a real application, you would get the user ID from a proper authentication service.
        $this->userId = $_SESSION['user_id'] ?? null;
        if (!$this->userId) {
            $this->jsonResponse(null, false, '用户未登录', 401);
            exit;
        }
    }

    /**
     * GET /membership/info
     * 获取当前用户的会员信息
     */
    public function getInfo()
    {
        $user = User::find($this->userId);
        $membership = MembershipPackage::getUserMembership($this->userId);
        $tokenBalance = UserTokenBalance::getOrCreateByUserId($this->userId);

        $data = [
            'user_id' => (int)$user['id'],
            'username' => $user['username'],
            'membership' => $membership,
            'token_balance' => [
                'balance' => (int)$tokenBalance['balance'],
                'total_recharged' => (int)$tokenBalance['total_recharged'],
                'total_consumed' => (int)$tokenBalance['total_consumed'],
                'total_bonus' => (int)$tokenBalance['total_bonus'],
            ]
        ];

        $this->jsonResponse($data);
    }

    /**
     * GET /membership/limits
     * 获取用户的使用限制状态
     */
    public function getLimits()
    {
        $limits = UserLimit::getUserLimitStatus($this->userId);
        $this->jsonResponse($limits);
    }

    /**
     * GET /membership/packages
     * 获取所有可用的会员套餐
     */
    public function getMembershipPackages()
    {
        $packages = MembershipPackage::getAll(true);
        $data = [];
        foreach ($packages as $package) {
            $actualPrice = MembershipPackage::getActualPrice($this->userId, $package['id']);
            $packageData = [
                'id' => (int)$package['id'],
                'name' => $package['name'],
                'type' => (int)$package['type'],
                'duration_days' => (int)$package['duration_days'],
                'original_price' => (float)$package['original_price'],
                'actual_price' => (float)$actualPrice['price'],
                'discount' => $actualPrice['discount'],
                'saved' => (float)$actualPrice['saved'],
                'features' => json_decode($package['features'], true) ?? [],
                'is_recommended' => (bool)$package['is_recommended'],
                'is_enabled' => (bool)$package['is_enabled'],
            ];
            $data[] = $packageData;
        }
        $this->jsonResponse($data);
    }

    /**
     * POST /membership/purchase
     * 购买会员套餐
     */
    public function purchaseMembership()
    {
        $input = $this->getJsonInput();
        $packageId = $input['package_id'] ?? null;
        $paymentMethod = $input['payment_method'] ?? null;

        if (!$packageId || !$paymentMethod) {
            return $this->jsonResponse(null, false, '参数错误', 400);
        }

        $actualPrice = MembershipPackage::getActualPrice($this->userId, $packageId);
        if (!$actualPrice) {
            return $this->jsonResponse(null, false, '套餐不存在或已下架', 404);
        }

        // In a real app, this would create a record and return a payment gateway URL.
        // For now, we'll simulate a successful purchase.
        $isRenewal = MembershipPackage::getUserMembership($this->userId) !== null;
        
        if ($isRenewal) {
            $success = MembershipPackage::renewMembership($this->userId, $packageId, 'sim_txn_' . uniqid(), $actualPrice['price']);
        } else {
            $success = MembershipPackage::activateMembership($this->userId, $packageId, $paymentMethod, 'sim_txn_' . uniqid(), $actualPrice['price']);
        }

        if ($success) {
            // The record is created inside the model methods, we need to fetch it.
            // This is a simplification. In reality, the model would return the created record ID.
            $this->jsonResponse(['message' => '购买成功'], true, '购买成功');
        } else {
            $this->jsonResponse(null, false, '购买失败，请稍后重试', 500);
        }
    }

    /**
     * GET /membership/recharge-packages
     * 获取所有可用的充值套餐
     */
    public function getRechargePackages()
    {
        $packages = RechargePackage::getAll(true);
        $data = [];
        foreach ($packages as $package) {
            $actualDetails = RechargePackage::getActualPrice($this->userId, $package['id']);
            $packageData = [
                'id' => (int)$package['id'],
                'name' => $package['name'],
                'tokens' => (int)$actualDetails['base_tokens'],
                'bonus_tokens' => (int)$actualDetails['bonus_tokens'],
                'price' => (float)$actualDetails['original_price'],
                'actual_price' => (float)$actualDetails['actual_price'],
                'discount' => $actualDetails['discount_info'],
                'saved' => (float)$actualDetails['saved_amount'],
                'is_hot' => (bool)$package['is_recommended'], // Using is_recommended as is_hot
                'is_enabled' => (bool)$package['is_enabled'],
            ];
            $data[] = $packageData;
        }
        $this->jsonResponse($data);
    }
    
    /**
     * POST /membership/recharge
     * 充值星夜币
     */
    public function recharge()
    {
        $input = $this->getJsonInput();
        $packageId = $input['package_id'] ?? null;
        $paymentMethod = $input['payment_method'] ?? null;

        if (!$packageId || !$paymentMethod) {
            return $this->jsonResponse(null, false, '参数错误', 400);
        }

        $actualDetails = RechargePackage::getActualPrice($this->userId, $packageId);
        if (!$actualDetails) {
            return $this->jsonResponse(null, false, '套餐不存在或已下架', 404);
        }

        $orderNo = RechargeRecord::create($this->userId, $packageId, $actualDetails['actual_price'], $paymentMethod);

        if ($orderNo) {
            $this->jsonResponse([
                'order_no' => $orderNo,
                'amount' => $actualDetails['actual_price'],
                'tokens' => $actualDetails['total_tokens']
            ], true, '订单创建成功');
        } else {
            $this->jsonResponse(null, false, '订单创建失败', 500);
        }
    }

    /**
     * GET /membership/orders
     * 获取用户的订单列表
     */
    public function getOrders()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 15;
        $type = $_GET['type'] ?? 'all'; // 'membership' or 'recharge'

        $data = [];
        if ($type === 'membership' || $type === 'all') {
            $data = array_merge($data, MembershipPurchaseRecord::getByUserId($this->userId, $page, $perPage)['records']);
        }
        if ($type === 'recharge' || $type === 'all') {
            $data = array_merge($data, RechargeRecord::getByUserId($this->userId, $page, $perPage)['records']);
        }

        // This is a simplified merge. A real implementation would need proper pagination across both tables.
        $this->jsonResponse(['records' => $data]);
    }

    /**
     * GET /membership/token-records
     * 获取用户的星夜币消费记录
     */
    public function getTokenRecords()
    {
        $page = $_GET['page'] ?? 1;
        $perPage = $_GET['per_page'] ?? 20;
        $type = $_GET['type'] ?? null;
        
        $records = UserTokenBalance::getConsumptionRecords($this->userId, $page, $perPage, $type);
        $this->jsonResponse($records);
    }

    /**
     * POST /membership/check-feature
     * 检查用户是否有权限使用某功能
     */
    public function checkFeature()
    {
        $input = $this->getJsonInput();
        $featureKey = $input['feature_key'] ?? null;

        if (!$featureKey) {
            return $this->jsonResponse(null, false, '参数错误', 400);
        }

        $result = Feature::checkUserAccess($this->userId, $featureKey);
        $this->jsonResponse($result);
    }

    /**
     * GET /membership/features
     * 获取用户可使用的功能列表
     */
    public function getFeatures()
    {
        $features = Feature::getUserAvailableFeatures($this->userId);
        $this->jsonResponse($features);
    }

    /**
     * POST /membership/payment-callback
     * 处理支付回调
     */
    public function handlePaymentCallback()
    {
        $input = $this->getJsonInput();
        $orderNo = $input['order_no'] ?? null;
        $status = $input['status'] ?? null;
        $transactionId = $input['transaction_id'] ?? null;

        if (!$orderNo || $status !== 'success') {
            return $this->jsonResponse(null, false, '回调参数错误', 400);
        }

        // Determine if it's a recharge or membership order
        if (strpos($orderNo, 'RC') === 0) {
            // Recharge
            $record = RechargeRecord::getByOrderNo($orderNo);
            if (!$record || $record['status'] !== 'pending') {
                return $this->jsonResponse(null, false, '订单不存在或状态错误', 404);
            }

            RechargeRecord::updateStatus($orderNo, 'completed', $transactionId);
            
            // Add tokens to user balance
            $totalTokens = $record['tokens_recharged'] + $record['tokens_bonus'];
            UserTokenBalance::addTokens($record['user_id'], $totalTokens, 'recharge', "充值到账: {$record['package_name']}", $record['id'], 'recharge_record');

        } elseif (strpos($orderNo, 'VIP') === 0) {
            // Membership purchase - This part is simplified as our purchase logic is synchronous for now.
            // In a real async scenario, you'd find the membership_purchase_records and update its status.
        } else {
            return $this->jsonResponse(null, false, '无效的订单号', 400);
        }

        $this->jsonResponse(['status' => 'ok']);
    }

    // --- Helper Methods ---

    private function getJsonInput(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    private function jsonResponse($data, bool $success = true, string $message = '操作成功', int $httpCode = 200)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($httpCode);
        
        $response = ['success' => $success];
        if ($success) {
            $response['data'] = $data;
            $response['message'] = $message;
        } else {
            $response['error'] = $message; // Using message as error code for simplicity
            $response['message'] = $message;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
