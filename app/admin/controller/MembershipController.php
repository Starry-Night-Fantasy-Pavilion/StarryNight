<?php

namespace app\admin\controller;

use app\models\User;
use app\models\Feature;
use app\models\UserLimit;
use app\models\UserTokenBalance;
use app\models\RechargePackage;
use app\models\RechargeRecord;
use app\models\MembershipPackage;
use app\models\MembershipPurchaseRecord;
use app\models\VipBenefit;
use app\admin\controller\BaseController;
use Exception;

class MembershipController extends BaseController
{
    /**
     * 会员管理首页
     */
    public function index()
    {
        try {
        // 获取会员统计
        $totalUsers = $this->getTotalUsers();
        $vipUsers = $this->getVipUsers();
        $totalRevenue = $this->getTotalRevenue();
        $monthlyRevenue = $this->getMonthlyRevenue();
        
        // 获取最近的会员购买记录
        $recentPurchases = MembershipPurchaseRecord::getAll(1, 10);
        
        // 获取最近的充值记录
        $recentRecharges = RechargeRecord::getAll(1, 10);
        
        $this->view('membership/index', [
            'totalUsers' => $totalUsers,
            'vipUsers' => $vipUsers,
            'totalRevenue' => $totalRevenue,
            'monthlyRevenue' => $monthlyRevenue,
            'recentPurchases' => $recentPurchases,
            'recentRecharges' => $recentRecharges
        ]);
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in MembershipController::index: ' . $e->getMessage());
            
            // 返回友好的错误信息
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return json_encode([
                    'success' => false,
                    'message' => '操作失败，请稍后重试',
                    'error' => DEBUG ? $e->getMessage() : '系统内部错误'
                ]);
            } else {
                // 对于普通请求，显示错误页面
                http_response_code(500);
                echo '<h1>系统错误</h1><p>抱歉，系统遇到了一些问题，请稍后重试。</p>';
                if (DEBUG) {
                    echo '<p>错误详情: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
                exit;
            }
        }

    /**
     * 会员列表
     */
    public function users()
    {
        $page = intval($_GET['page'] ?? 1);
        $perPage = intval($_GET['per_page'] ?? 20);
        $search = $_GET['search'] ?? '';
        $vipType = $_GET['vip_type'] ?? '';
        $sortBy = $_GET['sort_by'] ?? 'id';
        $sortOrder = $_GET['sort_order'] ?? 'desc';
        
        // 构建查询条件
        $conditions = [];
        if ($vipType !== '') {
            $conditions['vip_type'] = $vipType;
        }
        
        $users = User::getAll($page, $perPage, $search ?: null, $sortBy, $sortOrder);
        
        $this->view('membership/users', [
            'users' => $users,
            'search' => $search,
            'vipType' => $vipType,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder
        ]);
    }

    /**
     * 会员套餐管理
     */
    public function packages()
    {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $packages = MembershipPackage::getAll();
                $this->view('membership/packages', ['packages' => $packages]);
                break;
                
            case 'add':
                $this->view('membership/package-form');
                break;
                
            case 'edit':
                $id = intval($_GET['id'] ?? 0);
                $package = MembershipPackage::getById($id);
                if ($package) {
                    $package['features_array'] = $package['features'] ? json_decode($package['features'], true) : [];
                }
                $this->view('membership/package-form', ['package' => $package]);
                break;
                
            case 'save':
                $this->savePackage();
                break;
                
            case 'delete':
                $id = intval($_GET['id'] ?? 0);
                if ($id) {
                    MembershipPackage::delete($id);
                    $this->json(['success' => true, 'message' => '删除成功']);
                } else {
                    $this->json(['success' => false, 'message' => '参数错误']);
                }
                break;
                
            case 'toggle_status':
                $id = intval($_GET['id'] ?? 0);
                if ($id) {
                    MembershipPackage::toggleStatus($id);
                    $this->json(['success' => true, 'message' => '状态更新成功']);
                } else {
                    $this->json(['success' => false, 'message' => '参数错误']);
                }
                break;
                
            case 'toggle_recommended':
                $id = intval($_GET['id'] ?? 0);
                if ($id) {
                    MembershipPackage::toggleRecommended($id);
                    $this->json(['success' => true, 'message' => '推荐状态更新成功']);
                } else {
                    $this->json(['success' => false, 'message' => '参数错误']);
                }
                break;
        }
    }

    /**
     * 充值套餐管理
     */
    public function rechargePackages()
    {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $packages = RechargePackage::getAll();
                $this->view('membership/recharge-packages', ['packages' => $packages]);
                break;
                
            case 'add':
                $this->view('membership/recharge-package-form');
                break;
                
            case 'edit':
                $id = intval($_GET['id'] ?? 0);
                $package = RechargePackage::getById($id);
                $this->view('membership/recharge-package-form', ['package' => $package]);
                break;
                
            case 'save':
                $this->saveRechargePackage();
                break;
                
            case 'delete':
                $id = intval($_GET['id'] ?? 0);
                if ($id) {
                    RechargePackage::delete($id);
                    $this->json(['success' => true, 'message' => '删除成功']);
                } else {
                    $this->json(['success' => false, 'message' => '参数错误']);
                }
                break;
                
            case 'toggle_status':
                $id = intval($_GET['id'] ?? 0);
                if ($id) {
                    RechargePackage::toggleStatus($id);
                    $this->json(['success' => true, 'message' => '状态更新成功']);
                } else {
                    $this->json(['success' => false, 'message' => '参数错误']);
                }
                break;
                
            case 'toggle_hot':
                $id = intval($_GET['id'] ?? 0);
                if ($id) {
                    RechargePackage::toggleHot($id);
                    $this->json(['success' => true, 'message' => '热门状态更新成功']);
                } else {
                    $this->json(['success' => false, 'message' => '参数错误']);
                }
                break;
        }
    }

    /**
     * 功能权限管理
     */
    public function features()
    {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $features = Feature::getAll();
                $categories = Feature::getCategories();
                $this->view('membership/features', [
                    'features' => $features,
                    'categories' => $categories
                ]);
                break;
                
            case 'add':
                $categories = Feature::getCategories();
                $this->view('membership/feature-form', ['categories' => $categories]);
                break;
                
            case 'edit':
                $id = intval($_GET['id'] ?? 0);
                $feature = Feature::getById($id);
                $categories = Feature::getCategories();
                $this->view('membership/feature-form', [
                    'feature' => $feature,
                    'categories' => $categories
                ]);
                break;
                
            case 'save':
                $this->saveFeature();
                break;
                
            case 'delete':
                $id = intval($_GET['id'] ?? 0);
                if ($id) {
                    Feature::delete($id);
                    $this->json(['success' => true, 'message' => '删除成功']);
                } else {
                    $this->json(['success' => false, 'message' => '参数错误']);
                }
                break;
        }
    }

    /**
     * 会员权益管理
     */
    public function benefits()
    {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                $benefits = VipBenefit::getAll();
                $types = VipBenefit::getTypes();
                $this->view('membership/benefits', [
                    'benefits' => $benefits,
                    'types' => $types
                ]);
                break;
                
            case 'add':
                $types = VipBenefit::getTypes();
                $this->view('membership/benefit-form', ['types' => $types]);
                break;
                
            case 'edit':
                $id = intval($_GET['id'] ?? 0);
                $benefit = VipBenefit::getById($id);
                $types = VipBenefit::getTypes();
                $this->view('membership/benefit-form', [
                    'benefit' => $benefit,
                    'types' => $types
                ]);
                break;
                
            case 'save':
                $this->saveBenefit();
                break;
                
            case 'delete':
                $id = intval($_GET['id'] ?? 0);
                if ($id) {
                    VipBenefit::delete($id);
                    $this->json(['success' => true, 'message' => '删除成功']);
                } else {
                    $this->json(['success' => false, 'message' => '参数错误']);
                }
                break;
                
            case 'toggle_status':
                $id = intval($_GET['id'] ?? 0);
                if ($id) {
                    VipBenefit::toggleStatus($id);
                    $this->json(['success' => true, 'message' => '状态更新成功']);
                } else {
                    $this->json(['success' => false, 'message' => '参数错误']);
                }
                break;
        }
    }

    /**
     * 订单管理
     */
    public function orders()
    {
        $type = $_GET['type'] ?? 'membership'; // membership, recharge
        $page = intval($_GET['page'] ?? 1);
        $perPage = intval($_GET['per_page'] ?? 20);
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        
        if ($type === 'membership') {
            $orders = MembershipPurchaseRecord::getAll($page, $perPage, $status ?: null, $search ?: null);
            $this->view('membership/membership-orders', [
                'orders' => $orders,
                'type' => $type,
                'status' => $status,
                'search' => $search
            ]);
        } else {
            $orders = RechargeRecord::getAll($page, $perPage, $status ?: null, $search ?: null);
            $this->view('membership/recharge-orders', [
                'orders' => $orders,
                'type' => $type,
                'status' => $status,
                'search' => $search
            ]);
        }
    }

    /**
     * 统计报表
     */
    public function statistics()
    {
        $period = $_GET['period'] ?? 'month'; // day, week, month, year
        
        $membershipStats = MembershipPurchaseRecord::getStatistics($period);
        $rechargeStats = RechargeRecord::getStatistics($period);
        $membershipTypeDistribution = MembershipPurchaseRecord::getTypeDistribution();
        
        $this->view('membership/statistics', [
            'period' => $period,
            'membershipStats' => $membershipStats,
            'rechargeStats' => $rechargeStats,
            'membershipTypeDistribution' => $membershipTypeDistribution
        ]);
    }

    /**
     * 保存会员套餐
     */
    private function savePackage()
    {
        $data = [
            'id' => intval($_POST['id'] ?? 0),
            'name' => $_POST['name'] ?? '',
            'type' => intval($_POST['type'] ?? 1),
            'duration_days' => intval($_POST['duration_days'] ?? 0),
            'original_price' => floatval($_POST['original_price'] ?? 0),
            'discount_price' => floatval($_POST['discount_price'] ?? 0) ?: null,
            'discount_rate' => floatval($_POST['discount_rate'] ?? 0) ?: null,
            'description' => $_POST['description'] ?? '',
            'is_recommended' => intval($_POST['is_recommended'] ?? 0),
            'is_enabled' => intval($_POST['is_enabled'] ?? 1),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'icon' => $_POST['icon'] ?? '',
            'badge' => $_POST['badge'] ?? ''
        ];
        
        // 处理功能特性
        if (isset($_POST['features']) && is_array($_POST['features'])) {
            $data['features'] = $_POST['features'];
        }
        
        if (MembershipPackage::save($data)) {
            $this->json(['success' => true, 'message' => '保存成功']);
        } else {
            $this->json(['success' => false, 'message' => '保存失败']);
        }
    }

    /**
     * 保存充值套餐
     */
    private function saveRechargePackage()
    {
        $data = [
            'id' => intval($_POST['id'] ?? 0),
            'name' => $_POST['name'] ?? '',
            'tokens' => intval($_POST['tokens'] ?? 0),
            'price' => floatval($_POST['price'] ?? 0),
            'vip_price' => floatval($_POST['vip_price'] ?? 0) ?: null,
            'discount_rate' => floatval($_POST['discount_rate'] ?? 0) ?: null,
            'bonus_tokens' => intval($_POST['bonus_tokens'] ?? 0),
            'is_hot' => intval($_POST['is_hot'] ?? 0),
            'sort_order' => intval($_POST['sort_order'] ?? 0),
            'is_enabled' => intval($_POST['is_enabled'] ?? 1),
            'description' => $_POST['description'] ?? '',
            'icon' => $_POST['icon'] ?? '',
            'badge' => $_POST['badge'] ?? ''
        ];
        
        if (RechargePackage::save($data)) {
            $this->json(['success' => true, 'message' => '保存成功']);
        } else {
            $this->json(['success' => false, 'message' => '保存失败']);
        }
    }

    /**
     * 保存功能权限
     */
    private function saveFeature()
    {
        $data = [
            'id' => intval($_POST['id'] ?? 0),
            'feature_key' => $_POST['feature_key'] ?? '',
            'feature_name' => $_POST['feature_name'] ?? '',
            'category' => $_POST['category'] ?? '',
            'description' => $_POST['description'] ?? '',
            'require_vip' => intval($_POST['require_vip'] ?? 0),
            'is_enabled' => intval($_POST['is_enabled'] ?? 1),
            'sort_order' => intval($_POST['sort_order'] ?? 0)
        ];
        
        if (Feature::save($data)) {
            $this->json(['success' => true, 'message' => '保存成功']);
        } else {
            $this->json(['success' => false, 'message' => '保存失败']);
        }
    }

    /**
     * 保存会员权益
     */
    private function saveBenefit()
    {
        $data = [
            'id' => intval($_POST['id'] ?? 0),
            'benefit_key' => $_POST['benefit_key'] ?? '',
            'benefit_name' => $_POST['benefit_name'] ?? '',
            'benefit_type' => $_POST['benefit_type'] ?? '',
            'value' => $_POST['value'] ?? '',
            'description' => $_POST['description'] ?? '',
            'is_enabled' => intval($_POST['is_enabled'] ?? 1),
            'sort_order' => intval($_POST['sort_order'] ?? 0)
        ];
        
        if (VipBenefit::save($data)) {
            $this->json(['success' => true, 'message' => '保存成功']);
        } else {
            $this->json(['success' => false, 'message' => '保存失败']);
        }
    }

    /**
     * 获取总用户数
     */
    private function getTotalUsers(): int
    {
        $pdo = \app\services\Database::pdo();
        $prefix = \app\services\Database::prefix();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}users`");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * 获取VIP用户数
     */
    private function getVipUsers(): int
    {
        $pdo = \app\services\Database::pdo();
        $prefix = \app\services\Database::prefix();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$prefix}users` WHERE vip_type > 0 AND (vip_expire_at IS NULL OR vip_expire_at > NOW())");
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    /**
     * 获取总收入
     */
    private function getTotalRevenue(): float
    {
        $pdo = \app\services\Database::pdo();
        $prefix = \app\services\Database::prefix();
        
        $stmt = $pdo->prepare("
            SELECT SUM(actual_price) FROM (
                SELECT actual_price FROM `{$prefix}membership_purchase_records` WHERE payment_status = 'paid'
                UNION ALL
                SELECT actual_price FROM `{$prefix}recharge_records` WHERE payment_status = 'paid'
            ) AS combined
        ");
        $stmt->execute();
        return floatval($stmt->fetchColumn() ?: 0);
    }

    /**
     * 获取本月收入
     */
    private function getMonthlyRevenue(): float
    {
        $pdo = \app\services\Database::pdo();
        $prefix = \app\services\Database::prefix();
        
        $stmt = $pdo->prepare("
            SELECT SUM(actual_price) FROM (
                SELECT actual_price FROM `{$prefix}membership_purchase_records` WHERE payment_status = 'paid' AND YEAR(payment_time) = YEAR(CURDATE()) AND MONTH(payment_time) = MONTH(CURDATE())
                UNION ALL
                SELECT actual_price FROM `{$prefix}recharge_records` WHERE payment_status = 'paid' AND YEAR(payment_time) = YEAR(CURDATE()) AND MONTH(payment_time) = MONTH(CURDATE())
            ) AS combined
        ");
        $stmt->execute();
        return floatval($stmt->fetchColumn() ?: 0);
    }
}