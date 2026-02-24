<?php

namespace app\admin\controller;

use Exception;

class ThemeController extends BaseController
{
    public function index()
    {
        try {
        $title = '主题管理';
        $currentPage = 'themes';

        $themeManager = new \app\services\ThemeManager();
        $themes = $themeManager->listThemes();
        $activeThemeId = $themeManager->getActiveThemeId();
        
        ob_start();
        require __DIR__ . '/../views/themes.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    
        } catch (Exception $e) {
            // 记录错误日志
            error_log('Controller Error in ThemeController::index: ' . $e->getMessage());
            
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
    }

    public function activate()
    {
        $themeId = isset($_POST['theme_id']) ? (string) $_POST['theme_id'] : '';
        $themeManager = new \app\services\ThemeManager();
        $ok = $themeId !== '' && $themeManager->activateTheme('web', $themeId);

        $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
        header('Location: /' . $adminPrefix . '/themes' . ($ok ? '' : '?error=1'));
        exit;
    }

    /**
     * 主题预览
     */
    public function preview()
    {
        $themeId = $_GET['theme_id'] ?? '';
        if (empty($themeId)) {
            $this->error('主题ID不能为空', 400);
            return;
        }

        $themeManager = new \app\services\ThemeManager();
        $themes = $themeManager->listThemes('web');
        
        $theme = null;
        foreach ($themes as $t) {
            if ($t['id'] === $themeId) {
                $theme = $t;
                break;
            }
        }

        if (!$theme) {
            $this->error('主题不存在', 404);
            return;
        }

        $title = '主题预览 - ' . $theme['name'];
        $currentPage = 'themes';
        
        ob_start();
        require __DIR__ . '/../views/themes_preview.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    /**
     * 主题上传
     */
    public function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->error('仅支持POST请求', 405);
            return;
        }

        if (!isset($_FILES['theme_file']) || $_FILES['theme_file']['error'] !== UPLOAD_ERR_OK) {
            $this->json(['success' => false, 'message' => '文件上传失败'], 400);
            return;
        }

        $file = $_FILES['theme_file'];
        $tmpPath = $file['tmp_name'];
        $fileName = $file['name'];

        // 验证文件类型
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            $this->json(['success' => false, 'message' => '仅支持ZIP格式的主题包'], 400);
            return;
        }

        $themeManager = new \app\services\ThemeManager();
        $themesDir = $themeManager->getThemeDir('web');

        // 创建临时解压目录
        $tempDir = sys_get_temp_dir() . '/theme_upload_' . uniqid();
        if (!mkdir($tempDir, 0755, true)) {
            $this->json(['success' => false, 'message' => '无法创建临时目录'], 500);
            return;
        }

        try {
            // 解压ZIP文件
            $zip = new \ZipArchive();
            if ($zip->open($tmpPath) !== true) {
                throw new \Exception('无法打开ZIP文件');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            // 查找theme.json文件
            $themeJsonPath = null;
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getFilename() === 'theme.json') {
                    $themeJsonPath = $file->getPath();
                    break;
                }
            }

            if (!$themeJsonPath) {
                throw new \Exception('主题包中未找到theme.json文件');
            }

            // 读取主题信息
            $themeJson = json_decode(file_get_contents($themeJsonPath . '/theme.json'), true);
            if (!$themeJson || empty($themeJson['id'])) {
                throw new \Exception('theme.json格式无效或缺少主题ID');
            }

            $themeId = $themeJson['id'];
            $targetDir = $themesDir . '/' . $themeId;

            // 如果主题已存在，备份
            if (is_dir($targetDir)) {
                $backupDir = $targetDir . '_backup_' . date('YmdHis');
                if (!rename($targetDir, $backupDir)) {
                    throw new \Exception('无法备份现有主题');
                }
            }

            // 移动主题文件
            if (!rename($themeJsonPath, $targetDir)) {
                throw new \Exception('无法移动主题文件');
            }

            // 清理临时文件
            $this->deleteDirectory($tempDir);

            $this->json([
                'success' => true,
                'message' => '主题上传成功',
                'theme_id' => $themeId
            ]);

        } catch (\Exception $e) {
            // 清理临时文件
            if (is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 主题配置
     */
    public function config()
    {
        $themeId = $_GET['theme_id'] ?? '';
        if (empty($themeId)) {
            $this->error('主题ID不能为空', 400);
            return;
        }

        $themeManager = new \app\services\ThemeManager();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $config = $_POST['config'] ?? [];
            if (is_string($config)) {
                $config = json_decode($config, true) ?? [];
            }

            if ($themeManager->saveThemeConfig($themeId, $config)) {
                $adminPrefix = trim((string) get_env('ADMIN_PATH', 'admin'), '/');
                header('Location: /' . $adminPrefix . '/themes/config?theme_id=' . urlencode($themeId) . '&success=1');
                exit;
            } else {
                $this->error('保存配置失败', 500);
                return;
            }
        }

        $themes = $themeManager->listThemes('web');
        $theme = null;
        foreach ($themes as $t) {
            if ($t['id'] === $themeId) {
                $theme = $t;
                break;
            }
        }

        if (!$theme) {
            $this->error('主题不存在', 404);
            return;
        }

        $currentConfig = $themeManager->getThemeConfig($themeId);
        
        $title = '主题配置 - ' . $theme['name'];
        $currentPage = 'themes';
        
        ob_start();
        require __DIR__ . '/../views/themes_config.php';
        $content = ob_get_clean();

        require __DIR__ . '/../views/layout.php';
    }

    /**
     * 递归删除目录
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        return rmdir($dir);
    }
}

