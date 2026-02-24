<?php

namespace app\frontend\controller;

use app\models\User;
use app\models\Feature;
use app\models\UserLimit;
use app\models\UserTokenBalance;
use app\models\RechargePackage;
use app\models\RechargeRecord;
use app\models\MembershipPackage;
use app\models\MembershipPurchaseRecord;
use app\models\VipBenefit;
use app\services\Database;
use app\services\ThemeManager;

class MembershipController
{
    /**
     * 检查用户是否登录
     */
    private function isLoggedIn(): bool
    {
        return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * 检查用户登录状态并返回用户ID，未登录则跳转
     */
    private function checkAuth()
    {
        if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
            $redirectUrl = $_SERVER['REQUEST_URI'] ?? '/membership';
            header('Location: /login?redirect=' . urlencode($redirectUrl));
            exit;
        }
        return $_SESSION['user_id'];
    }

    /**
     * 检查是否为POST请求
     */
    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * 渲染视图
     */
    private function view(string $template, array $data = [])
    {
        $themeManager = new ThemeManager();
        $theme = $themeManager->getCurrentTheme();
        
        // 准备视图变量
        $viewVars = array_merge($data, [
            'user' => $this->isLoggedIn() ? User::find($_SESSION['user_id']) : null,
            'isLoggedIn' => $this->isLoggedIn(),
            'theme' => $theme
        ]);
        
        // 将变量提取到当前作用域
        extract($viewVars);
        
        ob_start();
        require __DIR__ . "/../views/membership/{$template}.php";
        $content = ob_get_clean();
        
        // 如果是AJAX请求，直接返回内容
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo $content;
            return;
        }
        
        // 否则加载布局
        $themeManager = new ThemeManager();
        $activeThemeId = $themeManager->getActiveThemeId('web') ?? \app\config\FrontendConfig::THEME_DEFAULT;
        $themeDir = $themeManager->getThemeDir('web', $activeThemeId);
        $layoutPath = $themeDir . '/templates/layout.php';
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
    }

    /**
     * 返回JSON响应
     */
    private function json(array $data)
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * 重定向
     */
    private function redirect(string $url)
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * 会员中心页面
     */
    public function index()
    {
        try {
            if (!$this->isLoggedIn()) {
                $this->redirect('/login');
                return;
            }

            $userId = $_SESSION['user_id'];
            
            // 获取用户信息
            $user = User::find($userId);
            
            // 获取会员信息
            $membership = User::getMembershipInfo($userId);
            
            // 获取星夜币余额
            $tokenBalance = User::getTokenBalance($userId);
            
            // 获取用户限制状态
            $limitStatus = User::getLimitStatus($userId);
            
            // 获取会员权益
            $vipBenefits = User::getVipBenefits($userId);
            
            // 获取推荐套餐
            $recommendedPackages = MembershipPackage::getRecommended();
            
            // 获取推荐充值套餐
            $recommendedRechargePackages = RechargePackage::getRecommendedPackages(3);
            
            $this->view('index', [
                'user' => $user,
                'membership' => $membership,
                'tokenBalance' => $tokenBalance,
                'limitStatus' => $limitStatus,
                'vipBenefits' => $vipBenefits,
                'recommendedPackages' => $recommendedPackages,
                'recommendedRechargePackages' => $recommendedRechargePackages
            ]);
        } catch (\Throwable $e) {
            // 记录错误日志
            error_log('Controller Error in MembershipController::index: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => (defined('DEBUG') && DEBUG) ? $e->getMessage() : '系统内部错误'
                ]);
            }

            // 对于普通请求，显示错误页面
            http_response_code(500);
            echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
            if (defined('DEBUG') && DEBUG) {
                echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            exit;
        }
    }

    /**
     * 会员套餐页面
     */
    public function packages()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // 获取所有会员套餐
        $packages = MembershipPackage::getAll(true);
        
        // 获取用户当前会员信息
        $currentMembership = User::getMembershipInfo($userId);
        
        // 为每个套餐计算实际价格
        foreach ($packages as &$package) {
            $priceInfo = MembershipPackage::getActualPrice($userId, $package['id']);
            $package['actual_price'] = $priceInfo['price'];
            $package['original_price'] = $priceInfo['original_price'];
            $package['discount'] = $priceInfo['discount'];
            $package['saved'] = $priceInfo['saved'];
            
            // 解析功能特性
            $package['features_array'] = $package['features'] ? json_decode($package['features'], true) : [];
        }
        
        $this->view('packages', [
            'packages' => $packages,
            'currentMembership' => $currentMembership
        ]);
    }

    /**
     * 充值中心页面
     */
    public function recharge()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        
        // 获取所有充值套餐
        $packages = RechargePackage::getAll(true);
        
        // 获取用户星夜币余额
        $tokenBalance = User::getTokenBalance($userId);
        
        // 获取热门套餐
        $hotPackages = RechargePackage::getHotPackages();
        
        // 为每个套餐计算实际价格和星夜币数量
        foreach ($packages as &$package) {
            $priceInfo = RechargePackage::getActualPrice($userId, $package['id']);
            $package['actual_price'] = $priceInfo['price'];
            $package['discount'] = $priceInfo['discount'];
            $package['saved'] = $priceInfo['saved'];
            
            $tokenInfo = RechargePackage::getActualTokens($userId, $package['id']);
            $package['token_info'] = $tokenInfo;
        }
        
        $this->view('recharge', [
            'packages' => $packages,
            'tokenBalance' => $tokenBalance,
            'hotPackages' => $hotPackages
        ]);
    }

    /**
     * 购买会员
     */
    public function purchaseMembership()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $packageId = intval($_POST['package_id'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? '';

        if (!$packageId || !$paymentMethod) {
            $this->json(['success' => false, 'message' => '参数错误']);
            return;
        }

        // 获取套餐信息
        $package = MembershipPackage::getById($packageId);
        if (!$package || !$package['is_enabled']) {
            $this->json(['success' => false, 'message' => '套餐不存在或已下架']);
            return;
        }

        // 获取实际价格
        $priceInfo = MembershipPackage::getActualPrice($userId, $packageId);
        
        // 生成订单号
        $orderNo = MembershipPurchaseRecord::generateOrderNo();
        
        // 计算会员时间
        $startTime = date('Y-m-d H:i:s');
        $endTime = null;
        if ($package['type'] != 3) { // 非终身会员
            $endTime = date('Y-m-d H:i:s', strtotime("+{$package['duration_days']} days"));
        }

        // 创建购买记录
        $recordId = MembershipPurchaseRecord::create([
            'user_id' => $userId,
            'membership_type' => $package['type'],
            'membership_name' => $package['name'],
            'original_price' => $package['original_price'],
            'actual_price' => $priceInfo['price'],
            'discount_amount' => $priceInfo['saved'],
            'duration_days' => $package['duration_days'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'order_no' => $orderNo,
            'auto_renew' => intval($_POST['auto_renew'] ?? 0),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        if (!$recordId) {
            $this->json(['success' => false, 'message' => '创建订单失败']);
            return;
        }

        // 这里应该调用支付接口，暂时返回成功
        $this->json([
            'success' => true,
            'message' => '订单创建成功',
            'order_no' => $orderNo,
            'record_id' => $recordId,
            'amount' => $priceInfo['price']
        ]);
    }

    /**
     * 充值星夜币
     */
    public function rechargeTokens()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => '请求方法错误']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $packageId = intval($_POST['package_id'] ?? 0);
        $paymentMethod = $_POST['payment_method'] ?? '';

        if (!$packageId || !$paymentMethod) {
            $this->json(['success' => false, 'message' => '参数错误']);
            return;
        }

        // 获取套餐信息
        $package = RechargePackage::getById($packageId);
        if (!$package || !$package['is_enabled']) {
            $this->json(['success' => false, 'message' => '套餐不存在或已下架']);
            return;
        }

        // 获取实际价格和星夜币数量
        $priceInfo = RechargePackage::getActualPrice($userId, $packageId);
        $tokenInfo = RechargePackage::getActualTokens($userId, $packageId);
        
        // 生成订单号
        $orderNo = RechargeRecord::generateOrderNo();
        
        // 创建充值记录
        $recordId = RechargeRecord::create([
            'user_id' => $userId,
            'package_id' => $packageId,
            'order_no' => $orderNo,
            'tokens' => $package['tokens'],
            'bonus_tokens' => $package['bonus_tokens'],
            'total_tokens' => $tokenInfo['total'],
            'original_price' => $package['price'],
            'actual_price' => $priceInfo['price'],
            'discount_amount' => $priceInfo['saved'],
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        if (!$recordId) {
            $this->json(['success' => false, 'message' => '创建订单失败']);
            return;
        }

        // 这里应该调用支付接口，暂时返回成功
        $this->json([
            'success' => true,
            'message' => '订单创建成功',
            'order_no' => $orderNo,
            'record_id' => $recordId,
            'amount' => $priceInfo['price'],
            'tokens' => $tokenInfo['total']
        ]);
    }

    /**
     * 支付回调
     */
    public function paymentCallback()
    {
        // 这里处理支付回调逻辑
        // 根据不同的支付方式处理回调
        $orderNo = $_GET['order_no'] ?? '';
        $status = $_GET['status'] ?? '';
        $transactionId = $_GET['transaction_id'] ?? '';

        if (!$orderNo || !$status) {
            $this->json(['success' => false, 'message' => '参数错误']);
            return;
        }

        // 判断是会员购买还是充值
        if (strpos($orderNo, 'VIP') === 0) {
            // 会员购买
            $record = MembershipPurchaseRecord::getByOrderNo($orderNo);
            if ($record && $status === 'success') {
                // 更新支付状态
                MembershipPurchaseRecord::updatePaymentStatus($record['id'], 'paid', $transactionId);
                
                // 激活会员
                MembershipPackage::activateMembership($record['user_id'], $record['membership_type'], 'purchase');
            }
        } elseif (strpos($orderNo, 'RC') === 0) {
            // 充值
            $record = RechargeRecord::getByOrderNo($orderNo);
            if ($record && $status === 'success') {
                // 更新支付状态
                RechargeRecord::updatePaymentStatus($record['id'], 'paid', $transactionId);
                
                // 增加用户星夜币
                UserTokenBalance::addTokens(
                    $record['user_id'],
                    $record['total_tokens'],
                    'recharge',
                    "充值订单：{$orderNo}",
                    $record['id'],
                    'recharge_record'
                );
            }
        }

        $this->json(['success' => true, 'message' => '处理成功']);
    }

    /**
     * 我的订单页面
     */
    public function orders()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        $page = intval($_GET['page'] ?? 1);
        $type = $_GET['type'] ?? 'all'; // all, membership, recharge
        $status = $_GET['status'] ?? '';

        $membershipOrders = [];
        $rechargeOrders = [];

        if ($type === 'all' || $type === 'membership') {
            $membershipOrders = MembershipPurchaseRecord::getByUserId($userId, $page, 10, $status ?: null);
        }

        if ($type === 'all' || $type === 'recharge') {
            $rechargeOrders = RechargeRecord::getByUserId($userId, $page, 10, $status ?: null);
        }

        $this->view('orders', [
            'membershipOrders' => $membershipOrders,
            'rechargeOrders' => $rechargeOrders,
            'type' => $type,
            'status' => $status
        ]);
    }

    /**
     * 星夜币消费记录
     */
    public function tokenRecords()
    {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = $_SESSION['user_id'];
        $page = intval($_GET['page'] ?? 1);
        $type = $_GET['type'] ?? '';

        $records = UserTokenBalance::getConsumptionRecords($userId, $page, 20, $type ?: null);
        
        $this->view('token-records', [
            'records' => $records,
            'type' => $type
        ]);
    }

    /**
     * 检查功能权限
     */
    public function checkFeatureAccess()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $featureKey = $_POST['feature_key'] ?? '';

        if (!$featureKey) {
            $this->json(['success' => false, 'message' => '参数错误']);
            return;
        }

        $result = User::checkFeatureAccess($userId, $featureKey);
        $this->json($result);
    }

    /**
     * 检查用户限制
     */
    public function checkUserLimit()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $limitType = $_POST['limit_type'] ?? '';
        $currentValue = intval($_POST['current_value'] ?? 0);

        if (!$limitType) {
            $this->json(['success' => false, 'message' => '参数错误']);
            return;
        }

        $result = User::checkLimit($userId, $limitType, $currentValue);
        $this->json($result);
    }

    /**
     * 获取用户余额信息
     */
    public function getBalance()
    {
        if (!$this->isLoggedIn()) {
            $this->json(['success' => false, 'message' => '请先登录']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $balance = User::getTokenBalance($userId);
        
        $this->json([
            'success' => true,
            'balance' => $balance
        ]);
    }
}