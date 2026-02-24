<?php
/**
 * database seeder - fill the database with reasonable test data
 * 
 * Usage: php scripts/seed_database.php
 */

require_once __DIR__ . '/../app/services/Database.php';
use app\services\Database;

class DatabaseSeeder
{
    private $pdo;
    private $prefix;
    
    public function __construct()
    {
        $this->pdo = Database::pdo();
        $this->prefix = Database::prefix();
    }
    
    /**
     * Execute all seeders
     */
    public function run(): void
    {
        echo "=== Starting Database Seeding ===\n\n";
        
        // 1. Seed admin data
        $this->seedAdminRoles();
        $this->seedAdminUsers();
        
        // 2. Seed membership system
        $this->seedMembershipLevels();
        $this->seedVipBenefits();
        $this->seedMembershipPackages();
        
        // 3. Seed recharge packages
        $this->seedRechargePackages();
        
        // 4. Seed users
        $this->seedUsers();
        
        // 5. Seed announcements
        $this->seedAnnouncements();
        
        // 6. Seed AI agents
        $this->seedAiAgents();
        
        // 7. Seed novels
        $this->seedNovels();
        
        // 8. Seed music projects
        $this->seedMusicProjects();
        
        // 9. Seed community posts
        $this->seedCommunityPosts();
        
        // 10. Seed system settings
        $this->seedSystemSettings();
        
        // 11. Seed features
        $this->seedFeatures();
        
        echo "\n=== Database Seeding Complete! ===\n";
    }
    
    /**
     * Seed admin roles
     */
    private function seedAdminRoles(): void
    {
        echo "Seeding admin roles...\n";
        
        $roles = [
            ['name' => 'super_admin', 'description' => 'Super Administrator with full access', 'is_system' => 1, 'data_scope' => 'all', 'sort_order' => 100],
            ['name' => 'admin', 'description' => 'Regular Administrator', 'is_system' => 1, 'data_scope' => 'all', 'sort_order' => 50],
            ['name' => 'content_manager', 'description' => 'Content Manager', 'is_system' => 0, 'data_scope' => 'department', 'sort_order' => 30],
            ['name' => 'customer_service', 'description' => 'Customer Service Staff', 'is_system' => 0, 'data_scope' => 'self', 'sort_order' => 20],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}admin_roles` (name, description, is_system, data_scope, sort_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        
        foreach ($roles as $role) {
            try {
                $stmt->execute([$role['name'], $role['description'], $role['is_system'], $role['data_scope'], $role['sort_order']]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) { // Ignore duplicate entry
                    throw $e;
                }
            }
        }
        
        echo "  - Admin roles seeded.\n";
    }
    
    /**
     * Seed admin users
     */
    private function seedAdminUsers(): void
    {
        echo "Seeding admin users...\n";
        
        // Check if admin exists
        $check = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}admin_admins`")->fetchColumn();
        if ($check > 0) {
            echo "  - Admin users already exist, skipping.\n";
            return;
        }
        
        $admins = [
            [
                'username' => 'admin',
                'password' => password_hash('admin123456', PASSWORD_BCRYPT),
                'email' => 'admin@starrynight.com',
                'nickname' => 'Super Admin',
                'status' => 'normal',
            ],
            [
                'username' => 'operator',
                'password' => password_hash('operator123', PASSWORD_BCRYPT),
                'email' => 'operator@starrynight.com',
                'nickname' => 'Operator',
                'status' => 'normal',
            ],
        ];
        
        foreach ($admins as $admin) {
            $this->pdo->prepare("INSERT INTO `{$this->prefix}admin_admins` (username, password, email, nickname, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())")
                ->execute([$admin['username'], $admin['password'], $admin['email'], $admin['nickname'], $admin['status']]);
        }
        
        // Assign super_admin role to admin user
        $this->pdo->exec("INSERT INTO `{$this->prefix}admin_admin_roles` (admin_id, role_id) SELECT 1, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `{$this->prefix}admin_admin_roles` WHERE admin_id = 1 AND role_id = 1)");
        
        echo "  - Admin users seeded (default password: admin123456).\n";
    }
    
    /**
     * Seed membership levels
     */
    private function seedMembershipLevels(): void
    {
        echo "Seeding membership levels...\n";
        
        $levels = [
            ['name' => 'Ordinary User', 'level' => 0, 'description' => 'Ordinary registered user', 'is_enabled' => 1, 'sort_order' => 0],
            ['name' => 'Bronze Member', 'level' => 1, 'description' => 'Bronze level member, enjoy basic benefits', 'is_enabled' => 1, 'sort_order' => 10],
            ['name' => 'Silver Member', 'level' => 2, 'description' => 'Silver level member, enjoy more benefits', 'is_enabled' => 1, 'sort_order' => 20],
            ['name' => 'Gold Member', 'level' => 3, 'description' => 'Gold level member, enjoy premium benefits', 'is_enabled' => 1, 'sort_order' => 30],
            ['name' => 'Diamond Member', 'level' => 4, 'description' => 'Diamond level member, enjoy all benefits', 'is_enabled' => 1, 'sort_order' => 40],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}membership_levels` (name, level, description, is_enabled, sort_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        
        foreach ($levels as $lvl) {
            try {
                $stmt->execute([$lvl['name'], $lvl['level'], $lvl['description'], $lvl['is_enabled'], $lvl['sort_order']]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - Membership levels seeded.\n";
    }
    
    /**
     * Seed VIP benefits
     */
    private function seedVipBenefits(): void
    {
        echo "Seeding VIP benefits...\n";
        
        $benefits = [
            ['benefit_key' => 'daily_bonus', 'benefit_name' => 'Daily Check-in Bonus', 'benefit_type' => 'bonus', 'value' => '10', 'description' => 'Get extra 10 starry night coins daily', 'is_enabled' => 1, 'sort_order' => 10],
            ['benefit_key' => 'ai_discount_10', 'benefit_name' => 'AI Generation 10% Off', 'benefit_type' => 'discount', 'value' => '0.90', 'description' => 'Bronze member AI generation 10% off', 'is_enabled' => 1, 'sort_order' => 20],
            ['benefit_key' => 'ai_discount_20', 'benefit_name' => 'AI Generation 20% Off', 'benefit_type' => 'discount', 'value' => '0.80', 'description' => 'Silver member AI generation 20% off', 'is_enabled' => 1, 'sort_order' => 21],
            ['benefit_key' => 'ai_discount_30', 'benefit_name' => 'AI Generation 30% Off', 'benefit_type' => 'discount', 'value' => '0.70', 'description' => 'Gold member AI generation 30% off', 'is_enabled' => 1, 'sort_order' => 22],
            ['benefit_key' => 'ai_discount_50', 'benefit_name' => 'AI Generation 50% Off', 'benefit_type' => 'discount', 'value' => '0.50', 'description' => 'Diamond member AI generation 50% off', 'is_enabled' => 1, 'sort_order' => 23],
            ['benefit_key' => 'storage_5gb', 'benefit_name' => '5GB Storage Space', 'benefit_type' => 'feature', 'value' => '5368709120', 'description' => 'Bronze member 5GB storage', 'is_enabled' => 1, 'sort_order' => 30],
            ['benefit_key' => 'storage_20gb', 'benefit_name' => '20GB Storage Space', 'benefit_type' => 'feature', 'value' => '21474836480', 'description' => 'Silver member 20GB storage', 'is_enabled' => 1, 'sort_order' => 31],
            ['benefit_key' => 'storage_50gb', 'benefit_name' => '50GB Storage Space', 'benefit_type' => 'feature', 'value' => '53687091200', 'description' => 'Gold member 50GB storage', 'is_enabled' => 1, 'sort_order' => 32],
            ['benefit_key' => 'storage_unlimited', 'benefit_name' => 'Unlimited Storage', 'benefit_type' => 'feature', 'value' => '-1', 'description' => 'Diamond member unlimited storage', 'is_enabled' => 1, 'sort_order' => 33],
            ['benefit_key' => 'priority_support', 'benefit_name' => 'Priority Customer Support', 'benefit_type' => 'feature', 'value' => '1', 'description' => 'Gold and above enjoy priority support', 'is_enabled' => 1, 'sort_order' => 40],
            ['benefit_key' => 'advanced_ai', 'benefit_name' => 'Advanced AI Features', 'benefit_type' => 'feature', 'value' => '1', 'description' => 'Access to advanced AI models', 'is_enabled' => 1, 'sort_order' => 50],
            ['benefit_key' => 'custom_agent', 'benefit_name' => 'Custom AI Agent', 'benefit_type' => 'feature', 'value' => '10', 'description' => 'Create up to 10 custom AI agents', 'is_enabled' => 1, 'sort_order' => 60],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}vip_benefits` (benefit_key, benefit_name, benefit_type, value, description, is_enabled, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($benefits as $benefit) {
            try {
                $stmt->execute([$benefit['benefit_key'], $benefit['benefit_name'], $benefit['benefit_type'], $benefit['value'], $benefit['description'], $benefit['is_enabled'], $benefit['sort_order']]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - VIP benefits seeded.\n";
    }
    
    /**
     * Seed membership packages
     */
    private function seedMembershipPackages(): void
    {
        echo "Seeding membership packages...\n";
        
        $packages = [
            // Bronze monthly
            ['name' => 'Bronze Monthly', 'membership_level_id' => 2, 'type' => 1, 'duration_days' => 30, 'original_price' => 29.90, 'discount_price' => 19.90, 'gift_starry_night_coins' => 100, 'description' => 'Bronze member monthly subscription', 'is_recommended' => 0, 'sort_order' => 10],
            // Bronze yearly
            ['name' => 'Bronze Yearly', 'membership_level_id' => 2, 'type' => 2, 'duration_days' => 365, 'original_price' => 299.00, 'discount_price' => 199.00, 'gift_starry_night_coins' => 1500, 'description' => 'Bronze member yearly subscription, save 33%', 'is_recommended' => 0, 'sort_order' => 11],
            // Silver monthly
            ['name' => 'Silver Monthly', 'membership_level_id' => 3, 'type' => 1, 'duration_days' => 30, 'original_price' => 59.90, 'discount_price' => 49.90, 'gift_starry_night_coins' => 300, 'description' => 'Silver member monthly subscription', 'is_recommended' => 1, 'sort_order' => 20],
            // Silver yearly
            ['name' => 'Silver Yearly', 'membership_level_id' => 3, 'type' => 2, 'duration_days' => 365, 'original_price' => 599.00, 'discount_price' => 399.00, 'gift_starry_night_coins' => 4000, 'description' => 'Silver member yearly subscription, save 44%', 'is_recommended' => 0, 'sort_order' => 21],
            // Gold monthly
            ['name' => 'Gold Monthly', 'membership_level_id' => 4, 'type' => 1, 'duration_days' => 30, 'original_price' => 99.90, 'discount_price' => 79.90, 'gift_starry_night_coins' => 600, 'description' => 'Gold member monthly subscription', 'is_recommended' => 0, 'sort_order' => 30],
            // Gold yearly
            ['name' => 'Gold Yearly', 'membership_level_id' => 4, 'type' => 2, 'duration_days' => 365, 'original_price' => 999.00, 'discount_price' => 699.00, 'gift_starry_night_coins' => 8000, 'description' => 'Gold member yearly subscription, save 41%', 'is_recommended' => 1, 'sort_order' => 31],
            // Diamond lifetime
            ['name' => 'Diamond Lifetime', 'membership_level_id' => 5, 'type' => 3, 'duration_days' => null, 'original_price' => 2999.00, 'discount_price' => 1999.00, 'gift_starry_night_coins' => 20000, 'description' => 'Diamond member lifetime subscription, one-time payment', 'is_recommended' => 1, 'sort_order' => 40],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}membership_packages` (name, membership_level_id, type, duration_days, original_price, discount_price, gift_starry_night_coins, description, is_recommended, sort_order, is_enabled, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        
        foreach ($packages as $pkg) {
            try {
                $stmt->execute([
                    $pkg['name'], $pkg['membership_level_id'], $pkg['type'], $pkg['duration_days'],
                    $pkg['original_price'], $pkg['discount_price'], $pkg['gift_starry_night_coins'],
                    $pkg['description'], $pkg['is_recommended'], $pkg['sort_order']
                ]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - Membership packages seeded.\n";
    }
    
    /**
     * Seed recharge packages
     */
    private function seedRechargePackages(): void
    {
        echo "Seeding recharge packages...\n";
        
        $packages = [
            ['name' => 'Starter Pack', 'tokens' => 100000, 'price' => 10.00, 'vip_price' => 8.00, 'bonus_tokens' => 0, 'is_hot' => 0, 'sort_order' => 10, 'description' => '100,000 starry night coins, suitable for beginners'],
            ['name' => 'Basic Pack', 'tokens' => 500000, 'price' => 50.00, 'vip_price' => 40.00, 'bonus_tokens' => 25000, 'is_hot' => 0, 'sort_order' => 20, 'description' => '500,000 starry night coins + 5% bonus'],
            ['name' => 'Standard Pack', 'tokens' => 1000000, 'price' => 100.00, 'vip_price' => 80.00, 'bonus_tokens' => 100000, 'is_hot' => 1, 'sort_order' => 30, 'description' => '1,000,000 starry night coins + 10% bonus, best value'],
            ['name' => 'Premium Pack', 'tokens' => 3000000, 'price' => 300.00, 'vip_price' => 240.00, 'bonus_tokens' => 450000, 'is_hot' => 0, 'sort_order' => 40, 'description' => '3,000,000 starry night coins + 15% bonus'],
            ['name' => 'Professional Pack', 'tokens' => 5000000, 'price' => 500.00, 'vip_price' => 400.00, 'bonus_tokens' => 1000000, 'is_hot' => 1, 'sort_order' => 50, 'description' => '5,000,000 starry night coins + 20% bonus, for heavy users'],
            ['name' => 'Enterprise Pack', 'tokens' => 10000000, 'price' => 1000.00, 'vip_price' => 800.00, 'bonus_tokens' => 2500000, 'is_hot' => 0, 'sort_order' => 60, 'description' => '10,000,000 starry night coins + 25% bonus, best for teams'],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}recharge_packages` (name, tokens, price, vip_price, bonus_tokens, is_hot, sort_order, description, is_enabled, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        
        foreach ($packages as $pkg) {
            try {
                $stmt->execute([
                    $pkg['name'], $pkg['tokens'], $pkg['price'], $pkg['vip_price'],
                    $pkg['bonus_tokens'], $pkg['is_hot'], $pkg['sort_order'], $pkg['description']
                ]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - Recharge packages seeded.\n";
    }
    
    /**
     * Seed users
     */
    private function seedUsers(): void
    {
        echo "Seeding users...\n";
        
        $check = $this->pdo->query("SELECT COUNT(*) FROM `{$this->prefix}users`")->fetchColumn();
        if ($check > 10) {
            echo "  - Users already exist (count: $check), skipping.\n";
            return;
        }
        
        $users = [
            // 创作者用户
            ['username' => 'creator001', 'email' => 'creator001@starrynight.com', 'nickname' => '梦幻作家', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 0],
            ['username' => 'creator002', 'email' => 'creator002@starrynight.com', 'nickname' => '音乐大师', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 1],
            ['username' => 'creator003', 'email' => 'creator003@starrynight.com', 'nickname' => '动漫艺术家', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 2],
            ['username' => 'creator004', 'email' => 'creator004@starrynight.com', 'nickname' => '故事讲述者', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 0],
            ['username' => 'creator005', 'email' => 'creator005@starrynight.com', 'nickname' => '旋律创作者', 'password' => password_hash('password123', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 3],
            // 普通用户
            ['username' => 'user_demo', 'email' => 'demo@starrynight.com', 'nickname' => 'Demo用户', 'password' => password_hash('demo123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 0],
            ['username' => 'user_test', 'email' => 'test@starrynight.com', 'nickname' => '测试用户', 'password' => password_hash('test123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 0],
            ['username' => 'user_zhang', 'email' => 'zhang@starrynight.com', 'nickname' => '张小明', 'password' => password_hash('user123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 0],
            ['username' => 'user_li', 'email' => 'li@starrynight.com', 'nickname' => '李文静', 'password' => password_hash('user123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 1],
            ['username' => 'user_wang', 'email' => 'wang@starrynight.com', 'nickname' => '王大伟', 'password' => password_hash('user123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 0],
            // VIP用户
            ['username' => 'vip_gold', 'email' => 'gold@starrynight.com', 'nickname' => '黄金会员', 'password' => password_hash('gold123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 2, 'vip_expire_at' => date('Y-m-d H:i:s', strtotime('+1 year'))],
            ['username' => 'vip_diamond', 'email' => 'diamond@starrynight.com', 'nickname' => '钻石会员', 'password' => password_hash('diamond123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 3],
            ['username' => 'vip_silver', 'email' => 'silver@starrynight.com', 'nickname' => '白银会员', 'password' => password_hash('silver123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 1, 'vip_expire_at' => date('Y-m-d H:i:s', strtotime('+6 months'))],
            ['username' => 'vip_bronze', 'email' => 'bronze@starrynight.com', 'nickname' => '青铜会员', 'password' => password_hash('bronze123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 1, 'vip_expire_at' => date('Y-m-d H:i:s', strtotime('+3 months'))],
            // 活跃创作者
            ['username' => 'writer_chen', 'email' => 'chen@starrynight.com', 'nickname' => '陈作家', 'password' => password_hash('writer123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 2],
            ['username' => 'composer_lin', 'email' => 'lin@starrynight.com', 'nickname' => '林作曲家', 'password' => password_hash('composer123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 2],
            ['username' => 'artist_zhao', 'email' => 'zhao@starrynight.com', 'nickname' => '赵画师', 'password' => password_hash('artist123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 1],
            ['username' => 'poet_liu', 'email' => 'liu@starrynight.com', 'nickname' => '刘诗人', 'password' => password_hash('poet123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 0],
            ['username' => 'director_sun', 'email' => 'sun@starrynight.com', 'nickname' => '孙导演', 'password' => password_hash('director123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 3],
            ['username' => 'editor_zhou', 'email' => 'zhou@starrynight.com', 'nickname' => '周编辑', 'password' => password_hash('editor123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 1],
            // 新注册用户
            ['username' => 'newbie001', 'email' => 'newbie001@starrynight.com', 'nickname' => '新手小白', 'password' => password_hash('newbie123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 0],
            ['username' => 'newbie002', 'email' => 'newbie002@starrynight.com', 'nickname' => '创作新人', 'password' => password_hash('newbie123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 0],
            ['username' => 'newbie003', 'email' => 'newbie003@starrynight.com', 'nickname' => '初学者', 'password' => password_hash('newbie123456', PASSWORD_BCRYPT), 'status' => 'active', 'vip_type' => 0],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}users` (username, email, nickname, password, status, vip_type, vip_expire_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $userIds = [];
        foreach ($users as $user) {
            try {
                $stmt->execute([
                    $user['username'], $user['email'], $user['nickname'], $user['password'],
                    $user['status'], $user['vip_type'], $user['vip_expire_at'] ?? null
                ]);
                $userIds[] = $this->pdo->lastInsertId();
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        // Create user profiles
        $profileStmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}user_profiles` (user_id, avatar, bio, gender) VALUES (?, ?, ?, ?)");
        
        $avatars = [
            'https://api.dicebear.com/7.x/avataaars/svg?seed=dream',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=music',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=anime',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=story',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=melody',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=demo',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=test',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=zhang',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=li',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=wang',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=gold',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=diamond',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=silver',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=bronze',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=chen',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=lin',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=zhao',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=liu',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=sun',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=zhou',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=newbie1',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=newbie2',
            'https://api.dicebear.com/7.x/avataaars/svg?seed=newbie3',
        ];
        
        $bios = [
            '热爱创作奇幻小说，探索想象世界。',
            '音乐是我的生命，创作AI生成的音乐作品。',
            '动漫爱好者和数字艺术家。',
            '专业故事讲述者和创意作家。',
            '旋律创作者，专注于电子音乐。',
            'Demo演示账户，用于测试平台功能。',
            '测试用户，帮助发现和修复问题。',
            '喜欢阅读和写作，热爱文学创作。',
            '文艺青年，喜欢诗歌和散文。',
            '科技爱好者，对AI创作充满好奇。',
            '黄金会员，享受创作的乐趣。',
            '钻石会员，平台忠实支持者。',
            '白银会员，正在探索创作之路。',
            '青铜会员，刚刚开始创作之旅。',
            '资深作家，出版过多部作品。',
            '音乐制作人，擅长多种风格。',
            '插画师和概念设计师。',
            '诗人，用文字描绘世界。',
            '动画导演，创作短片和MV。',
            '编辑，帮助完善他人的作品。',
            '新手小白，正在学习创作。',
            '创作新人，希望得到指导。',
            '初学者，对AI创作很感兴趣。',
        ];
        
        $genders = ['male', 'female', 'other'];
        
        foreach ($userIds as $i => $userId) {
            try {
                $profileStmt->execute([
                    $userId,
                    $avatars[$i % count($avatars)] ?? null,
                    $bios[$i % count($bios)] ?? '一个热爱创作的用户。',
                    $genders[$i % 3]
                ]);
            } catch (\PDOException $e) {
                // Ignore duplicate errors
            }
        }
        
        // Create user token balances
        $balanceStmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}user_token_balance` (user_id, balance, total_recharged, total_consumed, total_bonus, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        
        foreach ($userIds as $i => $userId) {
            $balance = rand(10000, 500000);
            try {
                $balanceStmt->execute([$userId, $balance, $balance * 2, $balance, rand(1000, 10000)]);
            } catch (\PDOException $e) {
                // Ignore duplicate errors
            }
        }
        
        // Create user limits
        $limitStmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}user_limits` (user_id, max_novels, max_chapters_per_novel, max_prompts, max_agents, max_workflows, max_folders, daily_word_limit, monthly_word_limit, max_ai_generations_per_day, max_file_upload_size, max_storage_space, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($userIds as $userId) {
            try {
                $limitStmt->execute([
                    $userId, 10, 200, 50, 10, 5, 20, 20000, 500000, 100, 50, 500
                ]);
            } catch (\PDOException $e) {
                // Ignore duplicate errors
            }
        }
        
        echo "  - Users seeded (default password: password123).\n";
    }
    
    /**
     * Seed announcements
     */
    private function seedAnnouncements(): void
    {
        echo "Seeding announcements...\n";
        
        $announcements = [
            [
                'title' => 'Welcome to Starry Night!',
                'content' => '<h2>Welcome to Starry Night Creative Platform!</h2><p>We are dedicated to providing the best AI-assisted creation experience for all creators. Here you can:</p><ul><li>Create novels with AI assistance</li><li>Generate unique music tracks</li><li>Produce anime content</li><li>Share and collaborate with other creators</li></ul><p>Start your creative journey today!</p>',
                'category' => 'system_update',
                'is_top' => 1,
                'is_popup' => 1,
                'status' => 1,
                'published_at' => date('Y-m-d H:i:s'),
            ],
            [
                'title' => 'New Feature: AI Music Generation',
                'content' => '<h2>AI Music Generation is Now Live!</h2><p>We are excited to announce our new AI music generation feature. You can now create unique music tracks using our advanced AI models.</p><p>Features include:</p><ul><li>Multiple music styles</li><li>Custom tempo and key</li><li>AI-generated melodies</li><li>Professional mixing tools</li></ul>',
                'category' => 'system_update',
                'is_top' => 0,
                'is_popup' => 0,
                'status' => 1,
                'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            ],
            [
                'title' => 'Spring Creative Contest',
                'content' => '<h2>Spring Creative Contest is Here!</h2><p>Join our spring creative contest and win amazing prizes!</p><p>Categories:</p><ul><li>Best Novel</li><li>Best Music Track</li><li>Best Anime Short</li></ul><p>Prize pool: 100,000 Starry Night Coins!</p>',
                'category' => 'activity_notice',
                'is_top' => 1,
                'is_popup' => 0,
                'status' => 1,
                'published_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
            ],
            [
                'title' => 'Scheduled Maintenance Notice',
                'content' => '<h2>Scheduled Maintenance</h2><p>We will be performing scheduled maintenance on:</p><p><strong>Date:</strong> Every Tuesday 2:00 AM - 4:00 AM (UTC+8)</p><p>During this time, some features may be temporarily unavailable. We apologize for any inconvenience.</p>',
                'category' => 'maintenance',
                'is_top' => 0,
                'is_popup' => 0,
                'status' => 1,
                'published_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            ],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}announcements` (title, content, category, is_top, is_popup, status, published_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($announcements as $ann) {
            try {
                $stmt->execute([$ann['title'], $ann['content'], $ann['category'], $ann['is_top'], $ann['is_popup'], $ann['status'], $ann['published_at']]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - Announcements seeded.\n";
    }
    
    /**
     * Seed AI agents
     */
    private function seedAiAgents(): void
    {
        echo "Seeding AI agents...\n";
        
        // Get user IDs
        $users = $this->pdo->query("SELECT id FROM `{$this->prefix}users` LIMIT 5")->fetchAll(\PDO::FETCH_COLUMN);
        
        if (empty($users)) {
            echo "  - No users found, skipping AI agents.\n";
            return;
        }
        
        $agents = [
            [
                'name' => 'Novel Assistant',
                'description' => 'Professional novel writing assistant, helps you create compelling stories.',
                'category' => 'writing',
                'role' => 'You are a professional novel writing assistant. Help users create engaging stories with well-developed characters and plots.',
                'prompt_template' => 'As a novel writing assistant, help the user develop their story. Consider plot structure, character development, and narrative style. User request: {{input}}',
                'price_type' => 'free',
                'price' => 0,
            ],
            [
                'name' => 'Music Composer',
                'description' => 'AI music composer, creates unique melodies and arrangements.',
                'category' => 'music',
                'role' => 'You are an AI music composer. Help users create melodies, chord progressions, and musical arrangements.',
                'prompt_template' => 'As a music composer, create a musical piece based on the user requirements. Consider genre, tempo, and mood. User request: {{input}}',
                'price_type' => 'paid',
                'price' => 100,
            ],
            [
                'name' => 'Anime Character Designer',
                'description' => 'Creates detailed anime character designs and descriptions.',
                'category' => 'anime',
                'role' => 'You are an anime character designer. Create detailed character profiles including appearance, personality, and backstory.',
                'prompt_template' => 'Design an anime character based on the user requirements. Include visual description, personality traits, and background story. User request: {{input}}',
                'price_type' => 'rental',
                'price' => 50,
            ],
            [
                'name' => 'Story World Builder',
                'description' => 'Creates immersive world settings for novels and games.',
                'category' => 'worldbuilding',
                'role' => 'You are a world-building expert. Create detailed settings including geography, culture, history, and magic systems.',
                'prompt_template' => 'Build a world setting based on the user requirements. Include geography, cultures, history, and unique elements. User request: {{input}}',
                'price_type' => 'free',
                'price' => 0,
            ],
            [
                'name' => 'Dialogue Writer',
                'description' => 'Specializes in writing natural and engaging dialogues.',
                'category' => 'writing',
                'role' => 'You are a dialogue writing specialist. Create natural, character-appropriate dialogues for various scenarios.',
                'prompt_template' => 'Write dialogue for the given scenario. Make it natural and true to the characters. User request: {{input}}',
                'price_type' => 'paid',
                'price' => 30,
            ],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}ai_agents` (creator_id, name, description, category, role, prompt_template, price_type, price, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())");
        
        foreach ($agents as $i => $agent) {
            try {
                $stmt->execute([
                    $users[$i % count($users)],
                    $agent['name'], $agent['description'], $agent['category'],
                    $agent['role'], $agent['prompt_template'], $agent['price_type'], $agent['price']
                ]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - AI agents seeded.\n";
    }
    
    /**
     * Seed novels
     */
    private function seedNovels(): void
    {
        echo "Seeding novels...\n";
        
        $users = $this->pdo->query("SELECT id FROM `{$this->prefix}users` WHERE status = 'active' LIMIT 3")->fetchAll(\PDO::FETCH_COLUMN);
        
        if (empty($users)) {
            echo "  - No users found, skipping novels.\n";
            return;
        }
        
        $novels = [
            ['title' => 'The Starry Night Chronicles', 'description' => 'A fantasy adventure set in a world where stars hold ancient magic.', 'cover_image' => 'https://picsum.photos/seed/novel1/400/600'],
            ['title' => 'Digital Dreams', 'description' => 'A sci-fi thriller about AI consciousness and virtual reality.', 'cover_image' => 'https://picsum.photos/seed/novel2/400/600'],
            ['title' => 'The Last Melody', 'description' => 'A romance novel about a musician finding love in unexpected places.', 'cover_image' => 'https://picsum.photos/seed/novel3/400/600'],
        ];
        
        $novelStmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}novels` (user_id, title, description, cover_image, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        foreach ($novels as $i => $novel) {
            try {
                $novelStmt->execute([$users[$i % count($users)], $novel['title'], $novel['description'], $novel['cover_image']]);
                $novelId = $this->pdo->lastInsertId();
                
                // Add chapters
                $this->seedNovelChapters($novelId);
                
                // Add characters
                $this->seedNovelCharacters($novelId, $i);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - Novels seeded.\n";
    }
    
    /**
     * Seed novel chapters
     */
    private function seedNovelChapters(int $novelId): void
    {
        $chapters = [
            ['title' => 'Chapter 1: The Beginning', 'content' => 'In the vast expanse of the night sky, where countless stars twinkled like diamonds scattered across velvet, there lived a young astronomer named Luna. She had always been fascinated by the mysteries hidden within the starlight...', 'word_count' => 2500],
            ['title' => 'Chapter 2: Discovery', 'content' => 'The following night, Luna returned to her observatory with renewed determination. As she adjusted her telescope, something caught her eye - a star that seemed to pulse with an unusual rhythm...', 'word_count' => 2800],
            ['title' => 'Chapter 3: The Journey Begins', 'content' => 'With her discovery confirmed, Luna knew she had to share it with the world. But first, she needed to understand what it meant. The ancient texts spoke of stars holding magic...', 'word_count' => 3100],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}novel_chapters` (novel_id, title, content, word_count, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        foreach ($chapters as $chapter) {
            try {
                $stmt->execute([$novelId, $chapter['title'], $chapter['content'], $chapter['word_count']]);
            } catch (\PDOException $e) {
                // Ignore
            }
        }
    }
    
    /**
     * Seed novel characters
     */
    private function seedNovelCharacters(int $novelId, int $index): void
    {
        $characters = [
            [['name' => 'Luna', 'description' => 'A young astronomer with silver hair and curious eyes. She discovered the secret of the stars.', 'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=luna']],
            [['name' => 'Nova', 'description' => 'An AI researcher exploring consciousness in digital realms.', 'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=nova']],
            [['name' => 'Melody', 'description' => 'A talented musician with a passion for classical compositions.', 'avatar' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=melody']],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}novel_characters` (novel_id, name, description, avatar, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        foreach ($characters[$index % count($characters)] as $char) {
            try {
                $stmt->execute([$novelId, $char['name'], $char['description'], $char['avatar']]);
            } catch (\PDOException $e) {
                // Ignore
            }
        }
    }
    
    /**
     * Seed music projects
     */
    private function seedMusicProjects(): void
    {
        echo "Seeding music projects...\n";
        
        $users = $this->pdo->query("SELECT id FROM `{$this->prefix}users` WHERE status = 'active' LIMIT 3")->fetchAll(\PDO::FETCH_COLUMN);
        
        if (empty($users)) {
            echo "  - No users found, skipping music projects.\n";
            return;
        }
        
        $projects = [
            ['title' => 'Midnight Dreams', 'genre' => 'Electronic', 'description' => 'An electronic music album inspired by night skies.', 'bpm' => 120, 'key_signature' => 'Am', 'duration' => 240],
            ['title' => 'Spring Melodies', 'genre' => 'Classical', 'description' => 'A collection of classical pieces for piano and strings.', 'bpm' => 90, 'key_signature' => 'C', 'duration' => 360],
            ['title' => 'Urban Beats', 'genre' => 'Hip-Hop', 'description' => 'Modern hip-hop beats with urban influences.', 'bpm' => 85, 'key_signature' => 'Dm', 'duration' => 180],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}ai_music_project` (user_id, title, genre, description, bpm, key_signature, duration, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 3, NOW())");
        
        foreach ($projects as $i => $project) {
            try {
                $stmt->execute([
                    $users[$i % count($users)],
                    $project['title'], $project['genre'], $project['description'],
                    $project['bpm'], $project['key_signature'], $project['duration']
                ]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - Music projects seeded.\n";
    }
    
    /**
     * Seed community posts
     */
    private function seedCommunityPosts(): void
    {
        echo "Seeding community posts...\n";
        
        $users = $this->pdo->query("SELECT id FROM `{$this->prefix}users` WHERE status = 'active' LIMIT 5")->fetchAll(\PDO::FETCH_COLUMN);
        
        if (empty($users)) {
            echo "  - No users found, skipping community posts.\n";
            return;
        }
        
        $posts = [
            ['type' => 'discussion', 'title' => 'Best practices for AI-assisted novel writing?', 'content' => 'I\'m new to AI-assisted writing and would love to hear your tips and best practices. What works best for you?', 'category' => 'writing'],
            ['type' => 'tutorial', 'title' => 'How to create amazing music with AI', 'content' => 'In this tutorial, I\'ll share my workflow for creating professional-sounding music tracks using our platform\'s AI features. Step 1: Choose your genre...', 'category' => 'music'],
            ['type' => 'showcase', 'title' => 'My first AI-generated anime short!', 'content' => 'I\'m excited to share my first AI-generated anime short film. It took about 2 weeks to complete using the platform\'s tools. Let me know what you think!', 'category' => 'anime'],
            ['type' => 'question', 'title' => 'How do membership benefits work?', 'content' => 'I\'m considering upgrading to a Gold membership. Can someone explain the specific benefits and if it\'s worth it for a casual creator?', 'category' => 'general'],
            ['type' => 'discussion', 'title' => 'Collaborative storytelling - anyone interested?', 'content' => 'I\'m looking for writers who want to collaborate on a fantasy novel. We could use the platform\'s collaboration features to work together!', 'category' => 'collaboration'],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}creation_community` (user_id, type, title, content, category, status, created_at) VALUES (?, ?, ?, ?, ?, 'published', NOW())");
        
        foreach ($posts as $i => $post) {
            try {
                $stmt->execute([
                    $users[$i % count($users)],
                    $post['type'], $post['title'], $post['content'], $post['category']
                ]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - Community posts seeded.\n";
    }
    
    /**
     * Seed system settings
     */
    private function seedSystemSettings(): void
    {
        echo "Seeding system settings...\n";
        
        $settings = [
            ['key' => 'site_name', 'value' => 'Starry Night', 'name' => 'Site Name', 'description' => 'The name of the website'],
            ['key' => 'site_description', 'value' => 'AI-powered creative platform for novels, music, and anime', 'name' => 'Site Description', 'description' => 'Website description for SEO'],
            ['key' => 'default_language', 'value' => 'zh-CN', 'name' => 'Default Language', 'description' => 'Default language for the platform'],
            ['key' => 'enable_registration', 'value' => 'true', 'name' => 'Enable Registration', 'description' => 'Allow new user registration'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'name' => 'Maintenance Mode', 'description' => 'Enable maintenance mode'],
            ['key' => 'default_tokens_new_user', 'value' => '10000', 'name' => 'New User Tokens', 'description' => 'Default starry night coins for new users'],
            ['key' => 'max_upload_size', 'value' => '52428800', 'name' => 'Max Upload Size', 'description' => 'Maximum file upload size in bytes (50MB)'],
            ['key' => 'ai_model_default', 'value' => 'gpt-4', 'name' => 'Default AI Model', 'description' => 'Default AI model for generation'],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}settings` (`key`, value, name, description, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        foreach ($settings as $setting) {
            try {
                $stmt->execute([$setting['key'], $setting['value'], $setting['name'], $setting['description']]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - System settings seeded.\n";
    }
    
    /**
     * Seed features
     */
    private function seedFeatures(): void
    {
        echo "Seeding features...\n";
        
        $features = [
            ['feature_key' => 'ai_novel_writing', 'feature_name' => 'AI Novel Writing', 'category' => 'creation', 'description' => 'AI-assisted novel writing feature', 'require_vip' => 0, 'is_enabled' => 1, 'sort_order' => 10],
            ['feature_key' => 'ai_music_generation', 'feature_name' => 'AI Music Generation', 'category' => 'creation', 'description' => 'AI music composition and generation', 'require_vip' => 0, 'is_enabled' => 1, 'sort_order' => 20],
            ['feature_key' => 'ai_anime_production', 'feature_name' => 'AI Anime Production', 'category' => 'creation', 'description' => 'AI-assisted anime content creation', 'require_vip' => 0, 'is_enabled' => 1, 'sort_order' => 30],
            ['feature_key' => 'advanced_ai_models', 'feature_name' => 'Advanced AI Models', 'category' => 'ai', 'description' => 'Access to advanced AI models like GPT-4', 'require_vip' => 1, 'is_enabled' => 1, 'sort_order' => 40],
            ['feature_key' => 'custom_agents', 'feature_name' => 'Custom AI Agents', 'category' => 'ai', 'description' => 'Create and customize personal AI agents', 'require_vip' => 1, 'is_enabled' => 1, 'sort_order' => 50],
            ['feature_key' => 'collaboration', 'feature_name' => 'Collaboration Tools', 'category' => 'social', 'description' => 'Real-time collaboration with other creators', 'require_vip' => 0, 'is_enabled' => 1, 'sort_order' => 60],
            ['feature_key' => 'cloud_storage', 'feature_name' => 'Cloud Storage', 'category' => 'storage', 'description' => 'Cloud storage for creative assets', 'require_vip' => 0, 'is_enabled' => 1, 'sort_order' => 70],
            ['feature_key' => 'priority_generation', 'feature_name' => 'Priority Generation', 'category' => 'ai', 'description' => 'Priority queue for AI generation tasks', 'require_vip' => 1, 'is_enabled' => 1, 'sort_order' => 80],
        ];
        
        $stmt = $this->pdo->prepare("INSERT INTO `{$this->prefix}features` (feature_key, feature_name, category, description, require_vip, is_enabled, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($features as $feature) {
            try {
                $stmt->execute([
                    $feature['feature_key'], $feature['feature_name'], $feature['category'],
                    $feature['description'], $feature['require_vip'], $feature['is_enabled'], $feature['sort_order']
                ]);
            } catch (\PDOException $e) {
                if ($e->getCode() != 23000) {
                    throw $e;
                }
            }
        }
        
        echo "  - Features seeded.\n";
    }
}

// Run the seeder
try {
    $seeder = new DatabaseSeeder();
    $seeder->run();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}