<?php

namespace app\admin\controller;

use app\models\MembershipLevel;
use app\models\CoinPackage;
use app\models\User;
use app\services\Database;

/**
 * 1.6 会员、财务与营销管理
 * 会员等级、充值套餐、充值记录、星夜币消耗、优惠券、活动、推广链接、站内信、通知模板
 */
class MembershipFinanceController extends BaseController
{
    private function render(string $view, array $vars = []): void
    {
        $title = $vars['title'] ?? '会员与营销';
        $currentPage = $vars['currentPage'] ?? 'finance';
        ob_start();
        extract($vars);
        require __DIR__ . '/../views/membership_finance/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    private function tableExists(string $table): bool
    {
        $prefix = Database::prefix();
        $full = $prefix . $table;
        $pdo = Database::pdo();
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($full));
        return $stmt->fetch() !== false;
    }

    // ---------- 会员等级 ----------
    public function index()
    {
        header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/finance/membership-levels');
        exit;
    }

    public function membershipLevels()
    {
        if (!$this->tableExists('membership_levels')) {
            $this->render('message', ['title' => '会员等级', 'message' => '请先执行数据库迁移 011。']);
            return;
        }
        $levels = MembershipLevel::getAll();
        $this->render('membership_levels', [
            'title' => '会员等级配置',
            'currentPage' => 'finance-levels',
            'levels' => $levels,
        ]);
    }

    public function membershipLevelEdit($id)
    {
        $level = $id === 'new' ? null : MembershipLevel::find((int)$id);
        if ($level === null && $id !== 'new') {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/finance/membership-levels');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'price_monthly' => (float)($_POST['price_monthly'] ?? 0),
                'price_yearly' => (float)($_POST['price_yearly'] ?? 0),
                'benefits_json' => $_POST['benefits_json'] ?? null,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'coin_discount_percent' => (float)($_POST['coin_discount_percent'] ?? 100),
                'permissions_json' => $_POST['permissions_json'] ?? null,
                'quota_json' => $_POST['quota_json'] ?? null,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
            ];
            try {
                if ($id === 'new') {
                    $newId = MembershipLevel::create($data);
                    header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/finance/membership-level/' . $newId);
                    exit;
                }
                if (MembershipLevel::update((int)$id, $data)) {
                    header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/finance/membership-levels');
                    exit;
                }
                $error = '保存失败';
            } catch (\Exception $e) {
                $error = '保存失败，请确认已执行数据库迁移 011：' . $e->getMessage();
            }
        }
        $this->render('membership_level_edit', [
            'title' => $level ? '编辑等级' : '新增等级',
            'currentPage' => 'finance-levels',
            'level' => $level,
            'error' => $error ?? null,
        ]);
    }

    // ---------- 充值套餐 ----------
    public function coinPackages()
    {
        if (!$this->tableExists('coin_packages')) {
            $this->render('message', ['title' => '充值套餐', 'message' => '请先执行数据库迁移 011。']);
            return;
        }
        $packages = CoinPackage::getAll();
        $this->render('coin_packages', [
            'title' => '充值套餐管理',
            'currentPage' => 'finance-packages',
            'packages' => $packages,
        ]);
    }

    public function coinPackageEdit($id)
    {
        $pkg = $id === 'new' ? null : CoinPackage::find((int)$id);
        if ($pkg === null && $id !== 'new') {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/finance/coin-packages');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'amount' => (float)($_POST['amount'] ?? 0),
                'coin_amount' => (int)($_POST['coin_amount'] ?? 0),
                'valid_days' => (int)($_POST['valid_days'] ?? 0),
                'sale_status' => $_POST['sale_status'] ?? 'on_sale',
                'is_limited_offer' => isset($_POST['is_limited_offer']) ? 1 : 0,
                'offer_start_at' => !empty($_POST['offer_start_at']) ? $_POST['offer_start_at'] : null,
                'offer_end_at' => !empty($_POST['offer_end_at']) ? $_POST['offer_end_at'] : null,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
            ];
            if ($id === 'new') {
                $newId = CoinPackage::create($data);
                header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/finance/coin-package/' . $newId);
                exit;
            }
            if (CoinPackage::update((int)$id, $data)) {
                header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/finance/coin-packages');
                exit;
            }
            $error = '保存失败';
        }
        $this->render('coin_package_edit', [
            'title' => $pkg ? '编辑套餐' : '新增套餐',
            'currentPage' => 'finance-packages',
            'package' => $pkg,
            'error' => $error ?? null,
        ]);
    }

    public function coinPackageDelete($id)
    {
        if (CoinPackage::delete((int)$id)) {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/finance/coin-packages');
            exit;
        }
        $this->render('message', ['title' => '错误', 'message' => '删除失败']);
    }

    // ---------- 充值记录 ----------
    public function orders()
    {
        $prefix = Database::prefix();
        $pdo = Database::pdo();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $where = ["o.product_type IN ('coins', 'coin_package', 'recharge') OR o.product_type IS NULL OR o.product_type = ''"];
        $params = [];
        if (!empty($_GET['user_id'])) {
            $where[] = 'o.user_id = :user_id';
            $params[':user_id'] = $_GET['user_id'];
        }
        if (isset($_GET['status']) && $_GET['status'] !== '') {
            $where[] = 'o.status = :status';
            $params[':status'] = $_GET['status'];
        }
        $sql = "SELECT o.*, u.username, u.nickname, u.email FROM `{$prefix}orders` o LEFT JOIN `{$prefix}users` u ON o.user_id = u.id WHERE " . implode(' AND ', $where) . " ORDER BY o.id DESC LIMIT " . ($perPage + 1) . " OFFSET " . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $total = count($orders);
        $hasMore = $total > $perPage;
        if ($hasMore) array_pop($orders);
        $this->render('orders', [
            'title' => '充值记录',
            'currentPage' => 'finance-orders',
            'orders' => $orders,
            'page' => $page,
            'hasMore' => $hasMore,
        ]);
    }

    public function orderRefund($id)
    {
        $prefix = Database::prefix();
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT o.*, u.username FROM `{$prefix}orders` o LEFT JOIN `{$prefix}users` u ON o.user_id = u.id WHERE o.id = :id");
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$order || $order['status'] !== 'completed') {
            header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/finance/orders');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $reason = trim($_POST['refund_reason'] ?? '');
            $adminId = $_SESSION['admin_user_id'] ?? null;
            $refundedAt = date('Y-m-d H:i:s');
            try {
                $pdo->beginTransaction();
                $upd = $pdo->prepare("UPDATE `{$prefix}orders` SET status = 'refunded', refund_reason = ?, refund_operator_id = ?, refunded_at = ? WHERE id = ?");
                $upd->execute([$reason, $adminId, $refundedAt, $id]);
                $pdo->commit();
                header('Location: /' . trim((string)get_env('ADMIN_PATH', 'admin'), '/') . '/finance/orders');
                exit;
            } catch (\Exception $e) {
                $pdo->rollBack();
                $error = '退款操作失败';
            }
        }
        $this->render('order_refund', [
            'title' => '充值退款',
            'currentPage' => 'finance-orders',
            'order' => $order,
            'error' => $error ?? null,
        ]);
    }

    // ---------- 星夜币消耗记录 ----------
    public function coinSpendRecords()
    {
        $prefix = Database::prefix();
        $pdo = Database::pdo();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $where = ["t.type = 'spend'"];
        $params = [];
        if (!empty($_GET['user_id'])) {
            $where[] = 't.user_id = :user_id';
            $params[':user_id'] = $_GET['user_id'];
        }
        if (!empty($_GET['related_type'])) {
            $where[] = 't.related_type = :related_type';
            $params[':related_type'] = $_GET['related_type'];
        }
        if (!empty($_GET['date_from'])) {
            $where[] = 't.created_at >= :date_from';
            $params[':date_from'] = $_GET['date_from'] . ' 00:00:00';
        }
        if (!empty($_GET['date_to'])) {
            $where[] = 't.created_at <= :date_to';
            $params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
        }
        $sql = "SELECT t.*, u.username, u.nickname FROM `{$prefix}coin_transactions` t LEFT JOIN `{$prefix}users` u ON t.user_id = u.id WHERE " . implode(' AND ', $where) . " ORDER BY t.id DESC LIMIT " . ($perPage + 1) . " OFFSET " . $offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $hasMore = count($rows) > $perPage;
        if ($hasMore) array_pop($rows);
        $this->render('coin_spend_records', [
            'title' => '星夜币消耗记录',
            'currentPage' => 'finance-coin-spend',
            'records' => $rows,
            'page' => $page,
            'hasMore' => $hasMore,
        ]);
    }

    // ---------- 优惠券、活动、推广、站内信、通知模板：占位列表 ----------
    public function coupons()
    {
        $prefix = Database::prefix();
        if (!$this->tableExists('coupons')) {
            $this->render('message', ['title' => '优惠券', 'message' => '请先执行数据库迁移 011。']);
            return;
        }
        $list = Database::pdo()->query("SELECT * FROM `{$prefix}coupons` ORDER BY id DESC")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $this->render('coupons', ['title' => '优惠券管理', 'currentPage' => 'finance-coupons', 'list' => $list]);
    }

    public function activities()
    {
        if (!$this->tableExists('marketing_activities')) {
            $this->render('message', ['title' => '活动配置', 'message' => '请先执行数据库迁移 011。']);
            return;
        }
        $prefix = Database::prefix();
        $list = Database::pdo()->query("SELECT * FROM `{$prefix}marketing_activities` ORDER BY id DESC")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $this->render('activities', ['title' => '活动配置', 'currentPage' => 'finance-activities', 'list' => $list]);
    }

    public function promotionLinks()
    {
        if (!$this->tableExists('promotion_links')) {
            $this->render('message', ['title' => '推广链接', 'message' => '请先执行数据库迁移 011。']);
            return;
        }
        $prefix = Database::prefix();
        $list = Database::pdo()->query("SELECT * FROM `{$prefix}promotion_links` ORDER BY id DESC")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $this->render('promotion_links', ['title' => '推广链接管理', 'currentPage' => 'finance-promotion', 'list' => $list]);
    }

    public function siteMessages()
    {
        if (!$this->tableExists('site_messages')) {
            $this->render('message', ['title' => '站内信', 'message' => '请先执行数据库迁移 011。']);
            return;
        }
        $prefix = Database::prefix();
        $list = Database::pdo()->query("SELECT * FROM `{$prefix}site_messages` ORDER BY id DESC LIMIT 100")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $this->render('site_messages', ['title' => '站内信管理', 'currentPage' => 'finance-messages', 'list' => $list]);
    }

    public function notificationTemplates()
    {
        if (!$this->tableExists('notification_templates')) {
            $this->render('message', ['title' => '通知模板', 'message' => '请先执行数据库迁移 011。']);
            return;
        }
        $prefix = Database::prefix();
        $pdo = Database::pdo();
        $successMessage = '';
        $adminPrefix = trim((string)get_env('ADMIN_PATH', 'admin'), '/');

        // 基本管理操作：删除模板
        if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
            $id = (int)$_GET['id'];
            if ($id > 0) {
                try {
                    $stmt = $pdo->prepare("SELECT * FROM `{$prefix}notification_templates` WHERE id = :id LIMIT 1");
                    $stmt->execute([':id' => $id]);
                    $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                    if ($row) {
                        // 删除对应的 HTML 文件
                        $publicRoot = realpath(__DIR__ . '/../../../public');
                        if ($publicRoot && !empty($row['content'])) {
                            // 根据渠道选择目录：email -> Email, sms -> sms
                            $subDir = ($row['channel'] ?? 'email') === 'email' ? 'Email' : 'sms';
                            $filePath = $publicRoot . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . $row['content'];
                            if (is_file($filePath)) {
                                @unlink($filePath);
                            }
                        }
                        $del = $pdo->prepare("DELETE FROM `{$prefix}notification_templates` WHERE id = :id");
                        $del->execute([':id' => $id]);
                    }
                } catch (\Throwable $e) {
                    error_log('删除通知模板失败: ' . $e->getMessage());
                }
            }
            header('Location: /' . $adminPrefix . '/finance/notification-templates');
            exit;
        }

        // URL 成功提示（保存模板）
        if (isset($_GET['success']) && (string)$_GET['success'] === '1') {
            $successMessage = '模板已保存';
        }

        // 基本筛选：按渠道、关键词
        $channelFilter = isset($_GET['channel']) ? trim((string)$_GET['channel']) : '';
        $keyword = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $where = [];
        $params = [];

        if (in_array($channelFilter, ['email', 'sms', 'system'], true)) {
            $where[] = 'channel = :channel';
            $params[':channel'] = $channelFilter;
        }
        if ($keyword !== '') {
            $where[] = '(code LIKE :kw OR title LIKE :kw)';
            $params[':kw'] = '%' . $keyword . '%';
        }

        // 处理 POST：测试发送 或 上传模板
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 优先处理测试发送
            if (!empty($_POST['test_template_id'])) {
                $tplId = (int)$_POST['test_template_id'];
                $target = trim((string)($_POST['test_target'] ?? ''));
                if ($tplId <= 0 || $target === '') {
                    $error = '请填写测试接收地址';
                } else {
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM `{$prefix}notification_templates` WHERE id = :id LIMIT 1");
                        $stmt->execute([':id' => $tplId]);
                        $tpl = $stmt->fetch(\PDO::FETCH_ASSOC);
                        if (!$tpl) {
                            $error = '模板不存在';
                        } else {
                            $channel = (string)($tpl['channel'] ?? '');
                            $code = (string)($tpl['code'] ?? '');

                            // 加载模板内容（邮件使用 HTML）
                            $body = '';
                            $publicRoot = realpath(__DIR__ . '/../../../public');
                            if ($publicRoot && !empty($tpl['content'])) {
                                // 根据渠道选择目录：email -> Email, sms -> sms
                                $subDir = ($tpl['channel'] ?? 'email') === 'email' ? 'Email' : 'sms';
                                $filePath = $publicRoot . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . $tpl['content'];
                                if (is_file($filePath)) {
                                    $body = (string)file_get_contents($filePath);
                                }
                            }
                            if ($body === '') {
                                $body = '这是模板 [' . $code . '] 的测试内容。';
                            }
                            $placeholders = [
                                '{{code}}' => 'TEST1234',
                                '{{username}}' => '测试用户',
                                '{{site_name}}' => (string)get_env('APP_NAME', '星夜阁'),
                            ];
                            $body = strtr($body, $placeholders);

                            if ($channel === 'email') {
                                if (!filter_var($target, FILTER_VALIDATE_EMAIL)) {
                                    $error = '请输入正确的测试邮箱地址';
                                } elseif (!function_exists('send_system_mail')) {
                                    $error = '系统未配置邮件发送功能';
                                } else {
                                    $subject = (string)($tpl['title'] ?? ('模板测试：' . $code));
                                    $err = '';
                                    // 后台测试邮件也应快速返回，避免 SMTP 重试/超时造成管理页面卡死
                                    $ok = send_system_mail($target, $subject, $body, $err, [
                                        'timeout' => 10,
                                        'retry_attempts' => 1,
                                        'retry_delay' => 0,
                                    ]);
                                    if ($ok) {
                                        $successMessage = '测试邮件已发送至：' . $target;
                                    } else {
                                        $error = '测试发送失败：' . ($err ?: '未知错误');
                                    }
                                }
                            } elseif ($channel === 'sms') {
                                if (!preg_match('/^1[3-9]\d{9}$/', $target)) {
                                    $error = '请输入正确的测试手机号';
                                } elseif (!function_exists('send_system_sms')) {
                                    $error = '系统未配置短信发送功能';
                                } else {
                                    $smsContent = '【测试】模板 ' . $code . ' 已配置成功，如收到本短信说明通道可用。';
                                    $ok = send_system_sms($target, $smsContent, []);
                                    if ($ok) {
                                        $successMessage = '测试短信已发送至：' . $target;
                                    } else {
                                        $error = '测试短信发送失败，请检查短信配置';
                                    }
                                }
                            } else {
                                $error = '当前渠道暂不支持测试发送';
                            }
                        }
                    } catch (\Throwable $e) {
                        error_log('测试发送通知模板失败: ' . $e->getMessage());
                        $error = '测试发送异常，请稍后重试';
                    }
                }
            } else {
                // 处理上传 HTML 模版
                $channel = trim((string)($_POST['channel'] ?? 'email')); // email | sms
                $code = trim((string)($_POST['code'] ?? ''));
                $name = trim((string)($_POST['name'] ?? ''));
                $subject = trim((string)($_POST['subject'] ?? '')); // 兼容旧字段，实际存入 title

                if ($channel === '' || $name === '') {
                    $error = '请完整填写渠道和名称';
                } else {
                    // 上传目录：根据渠道选择 public/static/errors/html/Email 或 public/static/errors/html/sms
                    $publicRoot = realpath(__DIR__ . '/../../../public');
                    $subDir = $channel === 'email' ? 'Email' : 'sms';
                    $uploadDir = $publicRoot . DIRECTORY_SEPARATOR . 'static' . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . $subDir;
                    if (!is_dir($uploadDir)) {
                        @mkdir($uploadDir, 0755, true);
                    }

                    $fileField = 'template_file';
                    if (!empty($_FILES[$fileField]) && is_array($_FILES[$fileField])) {
                        $f = $_FILES[$fileField];
                        if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                            $nameRaw = (string)($f['name'] ?? '');
                            $tmp = (string)($f['tmp_name'] ?? '');
                            if ($nameRaw !== '' && $tmp !== '' && is_uploaded_file($tmp)) {
                                $ext = strtolower(pathinfo($nameRaw, PATHINFO_EXTENSION));
                                if ($ext === 'html' || $ext === 'htm') {
                                    $size = (int)($f['size'] ?? 0);
                                    if ($size > 0 && $size <= 2 * 1024 * 1024) { // 限制 2MB
                                        // 如果前端未填编码，则根据名称或文件名自动生成
                                        if ($code === '') {
                                            $base = $name !== '' ? $name : pathinfo($nameRaw, PATHINFO_FILENAME);
                                            $base = strtolower($base);
                                            $base = preg_replace('/[^a-z0-9]+/', '_', $base);
                                            $base = trim((string)$base, '_');
                                            if ($base === '') {
                                                $base = 'template_' . date('Ymd_His');
                                            }
                                            $code = $channel . '_' . $base;
                                        }

                                        $safeCode = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $code);
                                        $safeCode = trim((string)$safeCode, '_');
                                        if ($safeCode === '') {
                                            $safeCode = 'template_' . date('Ymd_His');
                                        }

                                        $filename = $channel . '_' . $safeCode . '_' . date('Ymd_His') . '.html';
                                        $dest = $uploadDir . DIRECTORY_SEPARATOR . $filename;
                                        if (@move_uploaded_file($tmp, $dest)) {
                                            // 插入或更新数据库记录，仅保存文件名，路径固定为 /static/errors/html/
                                            $stmt = $pdo->prepare("SELECT id FROM `{$prefix}notification_templates` WHERE channel = :channel AND code = :code LIMIT 1");
                                            $stmt->execute([':channel' => $channel, ':code' => $code]);
                                            $exists = $stmt->fetch(\PDO::FETCH_ASSOC);

                                            if ($exists) {
                                                // 表结构：channel, code, title, content, created_at
                                                $upd = $pdo->prepare("UPDATE `{$prefix}notification_templates` SET title = :title, content = :content WHERE id = :id");
                                                $upd->execute([
                                                    ':id' => (int)$exists['id'],
                                                    ':title' => ($subject !== '' ? $subject : $name),
                                                    ':content' => $filename,
                                                ]);
                                            } else {
                                                $ins = $pdo->prepare("INSERT INTO `{$prefix}notification_templates` (channel, code, title, content, created_at) VALUES (:channel, :code, :title, :content, NOW())");
                                                $ins->execute([
                                                    ':channel' => $channel,
                                                    ':code' => $code,
                                                    ':title' => ($subject !== '' ? $subject : $name),
                                                    ':content' => $filename,
                                                ]);
                                            }

                                            // 保存成功后跳转回 GET，确保列表可见且避免重复提交
                                            header('Location: /' . $adminPrefix . '/finance/notification-templates?success=1');
                                            exit;
                                        } else {
                                            $error = '上传文件保存失败，请检查目录权限';
                                        }
                                    } else {
                                        $error = '模板文件大小不合法（最大 2MB）';
                                    }
                                } else {
                                    $error = '仅支持上传 .html/.htm 文件';
                                }
                            }
                        }
                    } else {
                        $error = '请上传 HTML 模版文件';
                    }
                }
            }
        }

        $sql = "SELECT * FROM `{$prefix}notification_templates`";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY channel, code';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $list = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        $this->render('notification_templates', [
            'title' => '短信/邮件模板',
            'currentPage' => 'finance-templates',
            'list' => $list,
            'error' => $error ?? null,
            'successMessage' => $successMessage,
            'channelFilter' => $channelFilter,
            'keyword' => $keyword,
        ]);
    }
}
