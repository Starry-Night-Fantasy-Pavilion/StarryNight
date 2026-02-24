# 智简魔方财务系统 - 插件开发指南

## 目录
1. [开发环境准备](#开发环境准备)
2. [创建新插件](#创建新插件)
3. [目录结构规范](#目录结构规范)
4. [API文档](#api文档)
5. [测试框架](#测试框架)
6. [代码示例](#代码示例)
7. [最佳实践](#最佳实践)

---

## 开发环境准备

### 系统要求

| 组件 | 最低要求 | 推荐配置 |
|------|---------|----------|
| PHP | 7.2+ | 7.4+ |
| MySQL | 5.6+ | 5.7+ |
| Web服务器 | Apache 2.4+ / Nginx 1.18+ | Nginx 1.20+ |
| 内存 | 512MB | 2GB+ |
| 磁盘空间 | 500MB | 5GB+ |

### 开发工具

```bash
# 1. 克隆项目
git clone https://github.com/your-repo/project.git
cd project

# 2. 安装依赖
composer install

# 3. 配置数据库
cp config/database.example.php config/database.php
# 编辑数据库配置

# 4. 创建插件目录
mkdir -p public/plugins/{oauth,gateways,certification,addons}

# 5. 设置权限
chmod -R 755 public/plugins
chmod -R 777 data runtime
```

### IDE配置

推荐使用以下IDE进行插件开发：

- **PHPStorm**: 最强大的PHP IDE，支持智能提示和调试
- **VS Code**: 轻量级，丰富的插件生态
- **Sublime Text**: 快速启动，适合小型项目

#### VS Code配置示例

```json
{
  "php.validate.executablePath": "C:/php/php.exe",
  "files.associations": {
    "*.tpl": "html"
  },
  "emmet.includeLanguages": {
    "tpl": "html"
  },
  "editor.formatOnSave": true,
  "php.suggest.basic": false,
  "intelephense.files.maxSize": 5000000
}
```

---

## 创建新插件

### 分步开发流程

#### 步骤1: 确定插件类型

根据功能需求选择合适的插件类型：

```php
<?php
// 插件类型决策树
function determinePluginType($requirements)
{
    if ($requirements['authentication']) {
        if ($requirements['third_party_login']) {
            return 'oauth';
        } elseif ($requirements['identity_verification']) {
            return 'certification';
        }
    }
    
    if ($requirements['payment']) {
        return 'gateways';
    }
    
    if ($requirements['system_extension']) {
        return 'addons';
    }
    
    throw new \InvalidArgumentException('无法确定插件类型');
}
```

#### 步骤2: 创建插件目录

```bash
# OAuth登录插件
mkdir -p public/plugins/oauth/my_login

# 支付网关插件
mkdir -p public/plugins/gateways/my_payment

# 实名认证插件
mkdir -p public/plugins/certification/my_verify

# 功能扩展插件
mkdir -p public/plugins/addons/my_feature
```

#### 步骤3: 创建插件文件

```bash
# OAuth插件文件
touch public/plugins/oauth/my_login/my_login.php

# 支付网关插件文件
touch public/plugins/gateways/my_payment/MyPaymentPlugin.php

# 实名认证插件文件
touch public/plugins/certification/my_verify/MyVerifyPlugin.php

# 功能扩展插件文件
touch public/plugins/addons/my_feature/MyFeaturePlugin.php
```

#### 步骤4: 实现插件接口

```php
<?php
// OAuth插件接口实现
namespace oauth\my_login;

class MyLogin
{
    public static function meta()
    {
        return [
            'name' => '我的登录',
            'description' => '自定义第三方登录',
            'author' => '开发者',
            'logo_url' => 'my_login.svg',
            'version' => '1.0.0'
        ];
    }
    
    public static function config()
    {
        return [
            'Client ID' => [
                'type' => 'text',
                'name' => 'client_id',
                'desc' => '应用客户端ID'
            ],
            'Client Secret' => [
                'type' => 'text',
                'name' => 'client_secret',
                'desc' => '应用客户端密钥'
            ]
        ];
    }
    
    public static function url($params)
    {
        $clientId = $params['client_id'];
        $callback = $params['callback'];
        $state = md5(uniqid(mt_rand(), true));
        
        $authUrl = "https://api.example.com/oauth/authorize";
        $authUrl .= "?client_id={$clientId}";
        $authUrl .= "&redirect_uri=" . urlencode($callback);
        $authUrl .= "&response_type=code";
        $authUrl .= "&state={$state}";
        
        return $authUrl;
    }
    
    public static function callback($params)
    {
        $code = $params['code'];
        $clientId = $params['client_id'];
        $clientSecret = $params['client_secret'];
        
        $tokenUrl = "https://api.example.com/oauth/token";
        $tokenData = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret
        ];
        
        $response = $this->httpPost($tokenUrl, $tokenData);
        $tokenInfo = json_decode($response, true);
        
        if (isset($tokenInfo['access_token'])) {
            $userInfo = $this->getUserInfo($tokenInfo['access_token']);
            
            return [
                'openid' => $userInfo['id'],
                'data' => [
                    'username' => $userInfo['name'],
                    'avatar' => $userInfo['avatar'],
                    'email' => $userInfo['email']
                ],
                'callbackBind' => 'all'
            ];
        }
        
        throw new \Exception('获取访问令牌失败');
    }
    
    private function httpPost($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new \Exception('HTTP请求失败: ' . curl_error($ch));
        }
        
        curl_close($ch);
        return $response;
    }
    
    private function getUserInfo($accessToken)
    {
        $url = "https://api.example.com/user/info";
        $url .= "?access_token={$accessToken}";
        
        $response = file_get_contents($url);
        return json_decode($response, true);
    }
}
```

#### 步骤5: 添加插件图标

```bash
# 创建插件图标文件
# 支持格式: SVG, PNG, JPG
# 推荐尺寸: 64x64 像素

# SVG格式示例
cat > public/plugins/oauth/my_login/my_login.svg << 'EOF'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <circle cx="32" cy="32" r="30" fill="#4A90E2"/>
  <text x="32" y="40" font-size="24" text-anchor="middle" fill="white">My</text>
</svg>
EOF

# PNG格式示例
# 使用在线工具或设计软件创建64x64的PNG图标
```

#### 步骤6: 测试插件

```php
<?php
// 测试脚本
require_once __DIR__ . '/vendor/autoload.php';

use app\admin\lib\PluginManager;

$pluginManager = new PluginManager();

// 测试插件加载
try {
    $plugin = $pluginManager->loadPlugin('oauth', 'my_login');
    echo "插件加载成功\n";
    
    // 测试插件信息
    $meta = $plugin->meta();
    print_r($meta);
    
    // 测试插件配置
    $config = $plugin->config();
    print_r($config);
    
    // 测试插件安装
    $result = $pluginManager->activatePlugin('oauth', 'my_login');
    echo "插件安装" . ($result ? "成功" : "失败") . "\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
```

---

## 目录结构规范

### OAuth登录插件结构

```
public/plugins/oauth/
└── my_login/
    ├── my_login.php              # 主插件文件
    ├── my_login.svg              # 插件图标
    ├── config/                  # 配置目录
    │   └── config.php          # 配置文件
    ├── lang/                   # 语言包
    │   ├── zh-cn.php
    │   └── en-us.php
    └── README.md               # 说明文档
```

### 支付网关插件结构

```
public/plugins/gateways/
└── my_payment/
    ├── MyPaymentPlugin.php      # 主插件文件
    ├── MyPayment.png           # 支付图标
    ├── config/                 # 配置目录
    │   └── config.php         # 配置文件
    ├── controller/             # 控制器目录
    │   └── IndexController.php # 回调控制器
    ├── logic/                 # 业务逻辑
    │   └── Payment.php        # 支付逻辑
    ├── vendor/                 # 第三方库
    │   └── payment-sdk/       # 支付SDK
    ├── lang/                  # 语言包
    │   ├── zh-cn.php
    │   └── en-us.php
    └── README.md              # 说明文档
```

### 实名认证插件结构

```
public/plugins/certification/
└── my_verify/
    ├── MyVerifyPlugin.php      # 主插件文件
    ├── config.php             # 配置文件
    ├── config/                # 配置目录
    │   └── config.php       # 配置文件
    ├── logic/                 # 业务逻辑
    │   └── Verify.php        # 认证逻辑
    ├── vendor/                # 第三方库
    │   └── verify-sdk/       # 认证SDK
    ├── lang/                 # 语言包
    │   ├── zh-cn.php
    │   └── en-us.php
    └── README.md            # 说明文档
```

### 功能扩展插件结构

```
public/plugins/addons/
└── my_feature/
    ├── MyFeaturePlugin.php     # 主插件文件
    ├── config.php             # 配置文件
    ├── controller/            # 控制器
    │   ├── AdminController.php
    │   └── ClientController.php
    ├── model/                 # 模型
    │   └── Feature.php
    ├── view/                  # 视图
    │   ├── admin/
    │   │   ├── index.tpl
    │   │   └── edit.tpl
    │   └── client/
    │       ├── index.tpl
    │       └── detail.tpl
    ├── static/                # 静态资源
    │   ├── css/
    │   │   └── style.css
    │   ├── js/
    │   │   └── script.js
    │   └── images/
    ├── lang/                 # 语言包
    │   ├── zh-cn.php
    │   └── en-us.php
    ├── menu.php              # 菜单配置
    ├── menuclientarea.php    # 客户端菜单
    └── README.md           # 说明文档
```

---

## API文档

### 核心API接口

#### 1. 插件管理API

```php
<?php
namespace app\admin\lib;

class PluginAPI
{
    /**
     * 获取所有插件列表
     * 
     * @param string $type 插件类型 (oauth|gateways|certification|addons)
     * @return array 插件列表
     */
    public function getPlugins($type = null)
    {
        $pluginManager = new PluginManager();
        
        if ($type) {
            return $pluginManager->discoverType($type);
        }
        
        return $pluginManager->discoverAll();
    }
    
    /**
     * 获取插件详情
     * 
     * @param string $type 插件类型
     * @param string $name 插件名称
     * @return array 插件详情
     */
    public function getPluginDetail($type, $name)
    {
        $pluginManager = new PluginManager();
        $plugin = $pluginManager->loadPlugin($type, $name);
        
        return [
            'info' => $plugin->info ?? $plugin->meta(),
            'config' => $pluginManager->getPluginConfig($name),
            'status' => $pluginManager->getPluginStatus($name),
            'version' => $pluginManager->getPluginVersion($name)
        ];
    }
    
    /**
     * 安装插件
     * 
     * @param string $type 插件类型
     * @param string $name 插件名称
     * @return bool 安装结果
     */
    public function installPlugin($type, $name)
    {
        $pluginManager = new PluginManager();
        
        try {
            $result = $pluginManager->activatePlugin($type, $name);
            
            if ($result) {
                $this->logAction('install', $type, $name);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logError('install', $type, $name, $e->getMessage());
            return false;
        }
    }
    
    /**
     * 卸载插件
     * 
     * @param string $type 插件类型
     * @param string $name 插件名称
     * @return bool 卸载结果
     */
    public function uninstallPlugin($type, $name)
    {
        $pluginManager = new PluginManager();
        
        try {
            $result = $pluginManager->deactivatePlugin($type, $name);
            
            if ($result) {
                $this->logAction('uninstall', $type, $name);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logError('uninstall', $type, $name, $e->getMessage());
            return false;
        }
    }
    
    /**
     * 更新插件配置
     * 
     * @param string $name 插件名称
     * @param array $config 配置数据
     * @return bool 更新结果
     */
    public function updatePluginConfig($name, $config)
    {
        $configManager = new ConfigManager();
        
        try {
            $result = $configManager->saveConfig($name, $config);
            
            if ($result) {
                $this->logAction('update_config', null, $name);
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logError('update_config', null, $name, $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取插件配置
     * 
     * @param string $name 插件名称
     * @return array 配置数据
     */
    public function getPluginConfig($name)
    {
        $configManager = new ConfigManager();
        return $configManager->loadConfig($name);
    }
    
    private function logAction($action, $type, $name)
    {
        $logData = [
            'action' => $action,
            'type' => $type,
            'name' => $name,
            'user_id' => session('user_id'),
            'ip' => request()->ip(),
            'time' => time()
        ];
        
        \think\Db::name('plugin_log')->insert($logData);
    }
    
    private function logError($action, $type, $name, $message)
    {
        $logData = [
            'action' => $action,
            'type' => $type,
            'name' => $name,
            'error' => $message,
            'user_id' => session('user_id'),
            'ip' => request()->ip(),
            'time' => time()
        ];
        
        \think\Db::name('plugin_error_log')->insert($logData);
    }
}
```

#### 2. 钩子系统API

```php
<?php
namespace app\admin\lib;

class HookAPI
{
    /**
     * 注册钩子
     * 
     * @param string $hook 钩子名称
     * @param callable $callback 回调函数
     * @param int $priority 优先级 (默认10)
     * @return bool 注册结果
     */
    public function registerHook($hook, $callback, $priority = 10)
    {
        return EventSystem::listen($hook, $callback, $priority);
    }
    
    /**
     * 触发钩子
     * 
     * @param string $hook 钩子名称
     * @param array $data 传递数据
     * @return void
     */
    public function triggerHook($hook, $data = [])
    {
        EventSystem::dispatch($hook, $data);
    }
    
    /**
     * 移除钩子
     * 
     * @param string $hook 钩子名称
     * @param callable $callback 回调函数
     * @return bool 移除结果
     */
    public function removeHook($hook, $callback)
    {
        return EventSystem::removeListener($hook, $callback);
    }
    
    /**
     * 获取所有钩子
     * 
     * @return array 钩子列表
     */
    public function getAllHooks()
    {
        return EventSystem::getAllListeners();
    }
}
```

#### 3. 系统钩子列表

| 钩子名称 | 触发时机 | 参数 | 返回值 |
|---------|---------|------|--------|
| plugin_install | 插件安装后 | $pluginName | void |
| plugin_uninstall | 插件卸载后 | $pluginName | void |
| plugin_activate | 插件激活后 | $pluginName | void |
| plugin_deactivate | 插件停用后 | $pluginName | void |
| user_login | 用户登录后 | $userId | void |
| user_logout | 用户登出后 | $userId | void |
| user_register | 用户注册后 | $userId | void |
| order_create | 订单创建后 | $orderId | void |
| order_paid | 订单支付后 | $orderId | void |
| invoice_create | 账单创建后 | $invoiceId | void |
| invoice_paid | 账单支付后 | $invoiceId | void |

#### 4. 钩子使用示例

```php
<?php
// 在插件中注册钩子
class MyPlugin extends Plugin
{
    public function activate()
    {
        // 注册用户登录钩子
        EventSystem::listen('user_login', function($userId) {
            $this->handleUserLogin($userId);
        }, 10);
        
        // 注册订单支付钩子
        EventSystem::listen('order_paid', function($orderId) {
            $this->handleOrderPaid($orderId);
        }, 5);
    }
    
    private function handleUserLogin($userId)
    {
        // 记录用户登录日志
        $logData = [
            'user_id' => $userId,
            'login_time' => time(),
            'ip' => request()->ip()
        ];
        
        \think\Db::name('user_login_log')->insert($logData);
    }
    
    private function handleOrderPaid($orderId)
    {
        // 处理订单支付完成逻辑
        $order = \think\Db::name('order')->find($orderId);
        
        // 发送通知
        $this->sendOrderNotification($order);
        
        // 更新库存
        $this->updateProductStock($order);
    }
}
```

---

## 测试框架

### 单元测试

```php
<?php
// tests/PluginTest.php

use PHPUnit\Framework\TestCase;
use app\admin\lib\PluginManager;

class PluginTest extends TestCase
{
    private $pluginManager;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->pluginManager = new PluginManager();
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
    }
    
    public function testPluginLoad()
    {
        $plugin = $this->pluginManager->loadPlugin('oauth', 'weixin');
        
        $this->assertNotNull($plugin);
        $this->assertInstanceOf('oauth\weixin\Weixin', $plugin);
    }
    
    public function testPluginMeta()
    {
        $plugin = $this->pluginManager->loadPlugin('oauth', 'weixin');
        $meta = $plugin->meta();
        
        $this->assertIsArray($meta);
        $this->assertArrayHasKey('name', $meta);
        $this->assertArrayHasKey('description', $meta);
        $this->assertArrayHasKey('author', $meta);
        $this->assertArrayHasKey('version', $meta);
    }
    
    public function testPluginConfig()
    {
        $plugin = $this->pluginManager->loadPlugin('oauth', 'weixin');
        $config = $plugin->config();
        
        $this->assertIsArray($config);
        $this->assertNotEmpty($config);
    }
    
    public function testPluginInstall()
    {
        $result = $this->pluginManager->activatePlugin('oauth', 'weixin');
        
        $this->assertTrue($result);
    }
    
    public function testPluginUninstall()
    {
        $result = $this->pluginManager->deactivatePlugin('oauth', 'weixin');
        
        $this->assertTrue($result);
    }
}
```

### 集成测试

```php
<?php
// tests/Integration/PluginIntegrationTest.php

use PHPUnit\Framework\TestCase;

class PluginIntegrationTest extends TestCase
{
    public function testOAuthPluginFlow()
    {
        // 1. 加载插件
        $pluginManager = new PluginManager();
        $plugin = $pluginManager->loadPlugin('oauth', 'weixin');
        
        $this->assertNotNull($plugin);
        
        // 2. 安装插件
        $installResult = $pluginManager->activatePlugin('oauth', 'weixin');
        $this->assertTrue($installResult);
        
        // 3. 配置插件
        $config = [
            'appid' => 'test_appid',
            'appSecret' => 'test_secret'
        ];
        $configManager = new ConfigManager();
        $saveResult = $configManager->saveConfig('weixin', $config);
        $this->assertTrue($saveResult);
        
        // 4. 测试授权URL生成
        $params = array_merge($config, [
            'callback' => 'http://test.com/oauth/callback/weixin'
        ]);
        $authUrl = $plugin->url($params);
        
        $this->assertStringContainsString('open.weixin.qq.com', $authUrl);
        $this->assertStringContainsString('test_appid', $authUrl);
        
        // 5. 卸载插件
        $uninstallResult = $pluginManager->deactivatePlugin('oauth', 'weixin');
        $this->assertTrue($uninstallResult);
    }
    
    public function testPaymentPluginFlow()
    {
        // 1. 加载支付插件
        $pluginManager = new PluginManager();
        $plugin = $pluginManager->loadPlugin('gateways', 'ali_pay');
        
        $this->assertNotNull($plugin);
        
        // 2. 安装插件
        $installResult = $pluginManager->activatePlugin('gateways', 'ali_pay');
        $this->assertTrue($installResult);
        
        // 3. 测试支付处理
        $paymentParams = [
            'product_name' => '测试商品',
            'out_trade_no' => 'TEST' . time(),
            'total_fee' => '0.01'
        ];
        
        $result = $plugin->AliPayHandle($paymentParams);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('type', $result);
        $this->assertArrayHasKey('data', $result);
        
        // 4. 卸载插件
        $uninstallResult = $pluginManager->deactivatePlugin('gateways', 'ali_pay');
        $this->assertTrue($uninstallResult);
    }
}
```

### 功能测试

```php
<?php
// tests/Functional/PluginFunctionalTest.php

use PHPUnit\Framework\TestCase;

class PluginFunctionalTest extends TestCase
{
    public function testPluginUI()
    {
        // 测试插件管理界面
        $response = $this->get('/admin/plugin/index');
        
        $this->assertEquals(200, $response->getCode());
        $this->assertStringContainsString('插件管理', $response->getBody());
    }
    
    public function testPluginInstallUI()
    {
        // 测试插件安装界面
        $response = $this->get('/admin/plugin/install/oauth/weixin');
        
        $this->assertEquals(200, $response->getCode());
        $this->assertStringContainsString('安装插件', $response->getBody());
    }
    
    public function testPluginConfigUI()
    {
        // 测试插件配置界面
        $response = $this->get('/admin/plugin/config/weixin');
        
        $this->assertEquals(200, $response->getCode());
        $this->assertStringContainsString('插件配置', $response->getBody());
    }
    
    public function testOAuthLoginFlow()
    {
        // 测试OAuth登录流程
        $response = $this->get('/oauth/url/weixin');
        
        $this->assertEquals(302, $response->getCode());
        $this->assertStringContainsString('open.weixin.qq.com', $response->getHeader('Location'));
    }
    
    public function testPaymentFlow()
    {
        // 测试支付流程
        $response = $this->post('/gateway/ali_pay/pay', [
            'product_name' => '测试商品',
            'out_trade_no' => 'TEST' . time(),
            'total_fee' => '0.01'
        ]);
        
        $this->assertEquals(200, $response->getCode());
        $this->assertStringContainsString('alipay', $response->getBody());
    }
}
```

### 测试运行

```bash
# 运行所有测试
./vendor/bin/phpunit

# 运行特定测试
./vendor/bin/phpunit tests/PluginTest.php

# 运行特定测试方法
./vendor/bin/phpunit --filter testPluginLoad

# 生成测试覆盖率报告
./vendor/bin/phpunit --coverage-html coverage/

# 生成XML格式测试报告
./vendor/bin/phpunit --log-junit test-results.xml
```

---

## 代码示例

### OAuth登录插件完整示例

```php
<?php
namespace oauth\github;

class Github
{
    public static function meta()
    {
        return [
            'name' => 'GitHub登录',
            'description' => '使用GitHub账号登录',
            'author' => '智简魔方',
            'logo_url' => 'github.svg',
            'version' => '1.0.0'
        ];
    }
    
    public static function config()
    {
        return [
            'Client ID' => [
                'type' => 'text',
                'name' => 'client_id',
                'desc' => 'GitHub OAuth应用的Client ID'
            ],
            'Client Secret' => [
                'type' => 'text',
                'name' => 'client_secret',
                'desc' => 'GitHub OAuth应用的Client Secret'
            ],
            'Callback URL' => [
                'type' => 'text',
                'name' => 'callback_url',
                'desc' => 'OAuth回调地址',
                'value' => request()->domain() . '/oauth/callback/github'
            ]
        ];
    }
    
    public static function url($params)
    {
        $clientId = $params['client_id'];
        $callbackUrl = $params['callback_url'];
        $state = md5(uniqid(mt_rand(), true));
        
        $authUrl = "https://github.com/login/oauth/authorize";
        $authUrl .= "?client_id={$clientId}";
        $authUrl .= "&redirect_uri=" . urlencode($callbackUrl);
        $authUrl .= "&scope=user:email";
        $authUrl .= "&state={$state}";
        
        return $authUrl;
    }
    
    public static function callback($params)
    {
        $code = $params['code'];
        $state = $params['state'];
        $clientId = $params['client_id'];
        $clientSecret = $params['client_secret'];
        $callbackUrl = $params['callback_url'];
        
        // 验证state
        $storedState = cache('github_state_' . session_id());
        if ($state !== $storedState) {
            throw new \Exception('State验证失败');
        }
        
        // 获取访问令牌
        $tokenUrl = "https://github.com/login/oauth/access_token";
        $tokenData = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code
        ];
        
        $response = self::httpPost($tokenUrl, $tokenData);
        parse_str($response, $tokenInfo);
        
        if (!isset($tokenInfo['access_token'])) {
            throw new \Exception('获取访问令牌失败');
        }
        
        // 获取用户信息
        $userUrl = "https://api.github.com/user";
        $userUrl .= "?access_token={$tokenInfo['access_token']}";
        
        $userResponse = file_get_contents($userUrl);
        $userInfo = json_decode($userResponse, true);
        
        // 获取用户邮箱
        $emailUrl = "https://api.github.com/user/emails";
        $emailUrl .= "?access_token={$tokenInfo['access_token']}";
        
        $emailResponse = file_get_contents($emailUrl);
        $emailInfo = json_decode($emailResponse, true);
        
        $primaryEmail = '';
        foreach ($emailInfo as $email) {
            if ($email['primary'] && $email['verified']) {
                $primaryEmail = $email['email'];
                break;
            }
        }
        
        return [
            'openid' => $userInfo['id'],
            'data' => [
                'username' => $userInfo['login'],
                'avatar' => $userInfo['avatar_url'],
                'email' => $primaryEmail
            ],
            'callbackBind' => 'all'
        ];
    }
    
    private static function httpPost($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'GitHub-OAuth-Plugin');
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new \Exception('HTTP请求失败: ' . curl_error($ch));
        }
        
        curl_close($ch);
        return $response;
    }
}
```

### 支付网关插件完整示例

```php
<?php
namespace gateways\wechat_pay;

use app\admin\lib\Plugin;

class WechatPayPlugin extends Plugin
{
    public $info = [
        'name' => 'wechat_pay',
        'title' => '微信支付',
        'description' => '微信支付接口',
        'status' => 1,
        'author' => '智简魔方',
        'version' => '1.0.0'
    ];
    
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `wechat_pay_orders` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` varchar(64) NOT NULL COMMENT '订单号',
            `transaction_id` varchar(64) DEFAULT NULL COMMENT '微信交易号',
            `total_fee` int(11) NOT NULL COMMENT '金额(分)',
            `status` tinyint(1) DEFAULT '0' COMMENT '状态 0未支付 1已支付',
            `notify_time` int(11) DEFAULT NULL COMMENT '通知时间',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `order_id` (`order_id`),
            KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='微信支付订单表';";
        
        return \think\Db::execute($sql) !== false;
    }
    
    public function uninstall()
    {
        $sql = "DROP TABLE IF EXISTS `wechat_pay_orders`";
        return \think\Db::execute($sql) !== false;
    }
    
    public function WechatPayHandle($param)
    {
        $config = $this->getConfig();
        
        $orderData = [
            'appid' => $config['appid'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => $this->generateNonce(),
            'body' => $param['product_name'],
            'out_trade_no' => $param['out_trade_no'],
            'total_fee' => $param['total_fee'] * 100, // 转换为分
            'spbill_create_ip' => request()->ip(),
            'notify_url' => $config['notify_url'],
            'trade_type' => 'NATIVE'
        ];
        
        $orderData['sign'] = $this->generateSign($orderData, $config['key']);
        
        $xml = $this->arrayToXml($orderData);
        
        $response = $this->httpPost('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml);
        $result = $this->xmlToArray($response);
        
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            return [
                'type' => 'insert',
                'data' => $result['code_url']
            ];
        }
        
        throw new \Exception('创建订单失败: ' . $result['return_msg']);
    }
    
    private function generateNonce()
    {
        return md5(uniqid(mt_rand(), true));
    }
    
    private function generateSign($data, $key)
    {
        ksort($data);
        $string = '';
        
        foreach ($data as $k => $v) {
            if ($v != '' && $k != 'sign') {
                $string .= "{$k}={$v}&";
            }
        }
        
        $string = $string . 'key=' . $key;
        return strtoupper(md5($string));
    }
    
    private function arrayToXml($data)
    {
        $xml = '<xml>';
        
        foreach ($data as $key => $value) {
            $xml .= "<{$key}><
![CDATA[{$value}]]></{$key}>";
        }
        
        $xml .= '</xml>';
        return $xml;
    }
    
    private function xmlToArray($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
    
    private function httpPost($url, $data)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new \Exception('HTTP请求失败: ' . curl_error($ch));
        }
        
        curl_close($ch);
        return $response;
    }
}
```

### 实名认证插件完整示例

```php
<?php
namespace certification\alipay;

use app\admin\lib\Plugin;

class AlipayPlugin extends Plugin
{
    public $info = [
        'name' => 'alipay',
        'title' => '支付宝认证',
        'description' => '支付宝实名认证',
        'status' => 1,
        'author' => '智简魔方',
        'version' => '1.0.0'
    ];
    
    public function install()
    {
        return true;
    }
    
    public function uninstall()
    {
        return true;
    }
    
    public function personal($certifi)
    {
        $config = $this->getConfig();
        
        $param = [
            'cert_name' => $certifi['name'],
            'cert_no' => $certifi['card']
        ];
        
        $logic = new \certification\alipay\logic\Alipay();
        $result = $logic->verify($param, $config);
        
        $data = [
            'status' => $result['status'],
            'auth_fail' => $result['message'],
            'certify_id' => $result['certify_id'] ?? ''
        ];
        
        updatePersonalCertifiStatus($data);
        
        return "<h3>正在认证,请稍等...</h3>";
    }
    
    public function company($certifi)
    {
        $config = $this->getConfig();
        
        $param = [
            'company_name' => $certifi['company_name'],
            'company_organ_code' => $certifi['company_organ_code']
        ];
        
        $logic = new \certification\alipay\logic\Alipay();
        $result = $logic->verifyCompany($param, $config);
        
        $data = [
            'status' => $result['status'],
            'auth_fail' => $result['message'],
            'certify_id' => $result['certify_id'] ?? ''
        ];
        
        updateCompanyCertifiStatus($data);
        
        return "<h3>正在认证,请稍等...</h3>";
    }
    
    public function collectionInfo()
    {
        return [
            'name' => [
                'title' => '真实姓名',
                'type' => 'text',
                'value' => '',
                'tip' => '请输入真实姓名',
                'required' => true
            ],
            'card' => [
                'title' => '身份证号',
                'type' => 'text',
                'value' => '',
                'tip' => '请输入身份证号',
                'required' => true
            ]
        ];
    }
    
    public function getStatus($certifi)
    {
        $config = $this->getConfig();
        
        $logic = new \certification\alipay\logic\Alipay();
        $result = $logic->queryStatus($certifi['certify_id'], $config);
        
        return [
            'status' => $result['status'],
            'msg' => $result['message']
        ];
    }
}
```

---

## 最佳实践

### 1. 代码规范

#### 命名规范

```php
<?php
// 类名：大驼峰
class MyPlugin extends Plugin {}

// 方法名：小驼峰
public function installPlugin() {}

// 变量名：小驼峰
$pluginManager = new PluginManager();

// 常量名：大写下划线
define('PLUGIN_VERSION', '1.0.0');

// 数据库表名：小写下划线
$tableName = 'plugin_my_plugin_data';
```

#### 注释规范

```php
<?php
/**
 * 插件基类
 * 
 * 所有插件必须继承此类
 * 
 * @package app\admin\lib
 * @author 智简魔方
 * @version 1.0.0
 */
abstract class Plugin
{
    /**
     * 安装插件
     * 
     * 此方法在插件安装时调用，用于创建数据库表、
     * 初始化配置等操作
     * 
     * @return bool 安装成功返回true，失败返回false
     * @throws \Exception 安装过程中发生错误
     */
    abstract public function install();
    
    /**
     * 卸载插件
     * 
     * 此方法在插件卸载时调用，用于清理数据库表、
     * 删除配置文件等操作
     * 
     * @return bool 卸载成功返回true，失败返回false
     * @throws \Exception 卸载过程中发生错误
     */
    abstract public function uninstall();
}
```

### 2. 错误处理

```php
<?php
class MyPlugin extends Plugin
{
    public function install()
    {
        try {
            $this->createTables();
            $this->createDirectories();
            $this->setDefaultConfig();
            
            return true;
        } catch (\PDOException $e) {
            $this->logError('数据库错误: ' . $e->getMessage());
            $this->rollbackInstall();
            return false;
        } catch (\Exception $e) {
            $this->logError('安装错误: ' . $e->getMessage());
            $this->rollbackInstall();
            return false;
        }
    }
    
    private function logError($message)
    {
        $logFile = 'logs/plugin_errors.log';
        $logMessage = sprintf(
            "[%s] %s\n",
            date('Y-m-d H:i:s'),
            $message
        );
        
        error_log($logMessage, 3, $logFile);
    }
    
    private function rollbackInstall()
    {
        try {
            $this->removeTables();
            $this->removeDirectories();
            $this->removeConfig();
        } catch (\Exception $e) {
            $this->logError('回滚失败: ' . $e->getMessage());
        }
    }
}
```

### 3. 性能优化

```php
<?php
class MyPlugin extends Plugin
{
    private static $cache = [];
    private static $cacheTime = 3600; // 1小时
    
    public function getConfig()
    {
        $cacheKey = 'plugin_config_' . $this->info['name'];
        
        if (isset(self::$cache[$cacheKey]) && 
            (time() - self::$cache[$cacheKey]['time']) < self::$cacheTime) {
            return self::$cache[$cacheKey]['data'];
        }
        
        $config = parent::getConfig();
        
        self::$cache[$cacheKey] = [
            'data' => $config,
            'time' => time()
        ];
        
        return $config;
    }
    
    public function clearCache()
    {
        self::$cache = [];
    }
}
```

### 4. 安全考虑

```php
<?php
class MyPlugin extends Plugin
{
    public function processInput($data)
    {
        if (!is_array($data)) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars(
                    strip_tags(trim($value)),
                    ENT_QUOTES,
                    'UTF-8'
                );
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->processInput($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    public function validateInput($data, $rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = $rule['message'] ?? "{$field} 是必填项";
                continue;
            }
            
            if (!empty($value)) {
                if (isset($rule['type'])) {
                    if (!$this->validateType($value, $rule['type'])) {
                        $errors[$field] = $rule['message'] ?? "{$field} 类型不正确";
                    }
                }
                
                if (isset($rule['pattern'])) {
                    if (!preg_match($rule['pattern'], $value)) {
                        $errors[$field] = $rule['message'] ?? "{$field} 格式不正确";
                    }
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    private function validateType($value, $type)
    {
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'int':
                return is_numeric($value) && (int)$value == $value;
            case 'float':
                return is_numeric($value) && (float)$value == $value;
            default:
                return true;
        }
    }
}
```

### 5. 文档编写

```markdown
# 插件名称

## 简介
简要描述插件的功能和用途

## 安装
1. 将插件文件上传到指定目录
2. 在后台插件管理中安装插件
3. 配置插件参数
4. 启用插件

## 配置
| 参数 | 说明 | 必填 |
|------|------|------|
| app_id | 应用ID | 是 |
| app_secret | 应用密钥 | 是 |

## 使用
详细说明插件的使用方法

## 注意事项
列出使用插件时需要注意的事项

## 常见问题
解答常见问题

## 更新日志
### 1.0.0 (2024-01-01)
- 初始版本发布
```

---

## 总结

本开发指南提供了完整的插件开发流程、API文档、测试框架和代码示例，帮助开发者快速掌握插件开发技能。

### 关键要点

1. **遵循规范**: 严格按照插件开发规范进行开发
2. **完善测试**: 编写充分的测试用例
3. **错误处理**: 实现完善的错误处理机制
4. **性能优化**: 注意插件性能影响
5. **安全考虑**: 实现必要的安全措施
6. **文档编写**: 编写清晰的文档

### 学习路径

1. **初级**: 学习插件基础结构和简单接口
2. **中级**: 掌握钩子系统和配置管理
3. **高级**: 理解插件间通信和性能优化
4. **专家**: 能够开发复杂插件和系统扩展

通过本指南的学习和实践，开发者可以快速成为插件开发专家，为系统贡献高质量的插件。
