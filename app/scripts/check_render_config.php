<?php
/**
 * 渲染引擎配置检查脚本
 * 
 * 使用方法：
 * php app/scripts/check_render_config.php
 */

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "========================================\n";
echo "  渲染引擎配置检查工具\n";
echo "========================================\n\n";

$checks = [];
$warnings = [];
$errors = [];

// 1. 检查PHP版本
echo "1. 检查PHP版本...\n";
$phpVersion = PHP_VERSION;
$minVersion = '8.1.0';
if (version_compare($phpVersion, $minVersion, '>=')) {
    echo "   ✓ PHP版本: $phpVersion (要求: >= $minVersion)\n";
    $checks[] = ['name' => 'PHP版本', 'status' => 'ok', 'value' => $phpVersion];
} else {
    echo "   ✗ PHP版本: $phpVersion (要求: >= $minVersion)\n";
    $errors[] = "PHP版本过低，需要 >= $minVersion";
    $checks[] = ['name' => 'PHP版本', 'status' => 'error', 'value' => $phpVersion];
}

// 2. 检查PHP扩展
echo "\n2. 检查PHP扩展...\n";
$requiredExtensions = [
    'gd' => '必需 - 用于生成单帧图像',
    'curl' => '必需 - 用于API调用',
    'json' => '必需 - 用于数据处理',
    'zip' => '推荐 - 用于文件压缩',
    'fileinfo' => '推荐 - 用于文件类型检测',
];

foreach ($requiredExtensions as $ext => $desc) {
    if (extension_loaded($ext)) {
        echo "   ✓ $ext: 已安装 ($desc)\n";
        $checks[] = ['name' => "PHP扩展: $ext", 'status' => 'ok'];
    } else {
        $isRequired = strpos($desc, '必需') !== false;
        if ($isRequired) {
            echo "   ✗ $ext: 未安装 ($desc)\n";
            $errors[] = "PHP扩展 $ext 未安装";
            $checks[] = ['name' => "PHP扩展: $ext", 'status' => 'error'];
        } else {
            echo "   ⚠ $ext: 未安装 ($desc)\n";
            $warnings[] = "PHP扩展 $ext 未安装（可选）";
            $checks[] = ['name' => "PHP扩展: $ext", 'status' => 'warning'];
        }
    }
}

// 3. 检查GD库功能
echo "\n3. 检查GD库功能...\n";
if (function_exists('imagecreatetruecolor')) {
    echo "   ✓ imagecreatetruecolor() 可用\n";
    $checks[] = ['name' => 'GD库功能', 'status' => 'ok'];
    
    // 测试创建图像
    $testImage = @imagecreatetruecolor(100, 100);
    if ($testImage) {
        echo "   ✓ 可以创建图像资源\n";
        imagedestroy($testImage);
    } else {
        echo "   ✗ 无法创建图像资源\n";
        $errors[] = "GD库无法创建图像资源";
    }
} else {
    echo "   ✗ imagecreatetruecolor() 不可用\n";
    $errors[] = "GD库功能不可用";
    $checks[] = ['name' => 'GD库功能', 'status' => 'error'];
}

// 4. 检查FFmpeg
echo "\n4. 检查FFmpeg...\n";
$ffmpegPath = getenv('FFMPEG_PATH') ?: 'ffmpeg';
$ffmpegOutput = [];
$ffmpegReturnCode = 0;
@exec("$ffmpegPath -version 2>&1", $ffmpegOutput, $ffmpegReturnCode);

if ($ffmpegReturnCode === 0 && !empty($ffmpegOutput)) {
    $versionLine = $ffmpegOutput[0] ?? '';
    echo "   ✓ FFmpeg已安装\n";
    echo "     路径: $ffmpegPath\n";
    echo "     版本: $versionLine\n";
    $checks[] = ['name' => 'FFmpeg', 'status' => 'ok', 'value' => $versionLine];
} else {
    echo "   ✗ FFmpeg未安装或不可用\n";
    echo "     尝试的路径: $ffmpegPath\n";
    $errors[] = "FFmpeg未安装或不在PATH中";
    $checks[] = ['name' => 'FFmpeg', 'status' => 'error'];
}

// 5. 检查Blender（可选）
echo "\n5. 检查Blender（可选）...\n";
$blenderPath = getenv('BLENDER_PATH') ?: 'blender';
$blenderOutput = [];
$blenderReturnCode = 0;
@exec("$blenderPath --version 2>&1", $blenderOutput, $blenderReturnCode);

if ($blenderReturnCode === 0 && !empty($blenderOutput)) {
    $versionLine = $blenderOutput[0] ?? '';
    echo "   ✓ Blender已安装\n";
    echo "     路径: $blenderPath\n";
    echo "     版本: $versionLine\n";
    $checks[] = ['name' => 'Blender', 'status' => 'ok', 'value' => $versionLine];
} else {
    echo "   ⚠ Blender未安装（可选，用于3D渲染）\n";
    echo "     尝试的路径: $blenderPath\n";
    $warnings[] = "Blender未安装（可选）";
    $checks[] = ['name' => 'Blender', 'status' => 'warning'];
}

// 6. 检查目录权限
echo "\n6. 检查目录权限...\n";
$baseDir = dirname(__DIR__, 2);
$renderDir = $baseDir . '/public/uploads/anime_renders';

if (!is_dir($renderDir)) {
    echo "   ⚠ 渲染输出目录不存在，尝试创建...\n";
    if (@mkdir($renderDir, 0755, true)) {
        echo "   ✓ 已创建渲染输出目录\n";
        $checks[] = ['name' => '渲染输出目录', 'status' => 'ok'];
    } else {
        echo "   ✗ 无法创建渲染输出目录: $renderDir\n";
        $errors[] = "无法创建渲染输出目录";
        $checks[] = ['name' => '渲染输出目录', 'status' => 'error'];
    }
} else {
    echo "   ✓ 渲染输出目录存在: $renderDir\n";
    $checks[] = ['name' => '渲染输出目录', 'status' => 'ok'];
}

if (is_dir($renderDir)) {
    if (is_writable($renderDir)) {
        echo "   ✓ 渲染输出目录可写\n";
    } else {
        echo "   ✗ 渲染输出目录不可写\n";
        $errors[] = "渲染输出目录不可写: $renderDir";
        $checks[] = ['name' => '渲染输出目录权限', 'status' => 'error'];
    }
}

// 7. 检查PHP配置
echo "\n7. 检查PHP配置...\n";
$memoryLimit = ini_get('memory_limit');
$maxExecutionTime = ini_get('max_execution_time');
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');

echo "   内存限制: $memoryLimit\n";
echo "   执行时间限制: {$maxExecutionTime}秒\n";
echo "   上传文件大小限制: $uploadMaxFilesize\n";
echo "   POST大小限制: $postMaxSize\n";

// 检查内存限制
$memoryBytes = parseSize($memoryLimit);
$minMemoryBytes = 512 * 1024 * 1024; // 512MB
if ($memoryBytes >= $minMemoryBytes) {
    echo "   ✓ 内存限制足够（推荐 >= 512M）\n";
    $checks[] = ['name' => 'PHP内存限制', 'status' => 'ok', 'value' => $memoryLimit];
} else {
    echo "   ⚠ 内存限制可能不足（推荐 >= 512M）\n";
    $warnings[] = "PHP内存限制较低: $memoryLimit";
    $checks[] = ['name' => 'PHP内存限制', 'status' => 'warning', 'value' => $memoryLimit];
}

// 检查执行时间限制
if ($maxExecutionTime >= 300 || $maxExecutionTime == 0) {
    echo "   ✓ 执行时间限制足够（推荐 >= 300秒）\n";
    $checks[] = ['name' => 'PHP执行时间限制', 'status' => 'ok', 'value' => $maxExecutionTime];
} else {
    echo "   ⚠ 执行时间限制可能不足（推荐 >= 300秒）\n";
    $warnings[] = "PHP执行时间限制较低: {$maxExecutionTime}秒";
    $checks[] = ['name' => 'PHP执行时间限制', 'status' => 'warning', 'value' => $maxExecutionTime];
}

// 8. 检查exec函数
echo "\n8. 检查系统函数...\n";
if (function_exists('exec')) {
    echo "   ✓ exec() 函数可用\n";
    $checks[] = ['name' => 'exec函数', 'status' => 'ok'];
} else {
    echo "   ✗ exec() 函数被禁用\n";
    $errors[] = "exec函数被禁用，无法执行FFmpeg/Blender命令";
    $checks[] = ['name' => 'exec函数', 'status' => 'error'];
}

if (function_exists('shell_exec')) {
    echo "   ✓ shell_exec() 函数可用\n";
} else {
    echo "   ⚠ shell_exec() 函数被禁用（可选）\n";
}

// 9. 检查环境变量
echo "\n9. 检查环境变量...\n";
$envVars = [
    'FFMPEG_PATH' => 'FFmpeg路径（可选）',
    'BLENDER_PATH' => 'Blender路径（可选）',
];

foreach ($envVars as $var => $desc) {
    $value = getenv($var);
    if ($value) {
        echo "   ✓ $var = $value ($desc)\n";
        $checks[] = ['name' => "环境变量: $var", 'status' => 'ok', 'value' => $value];
    } else {
        echo "   - $var 未设置 ($desc)\n";
    }
}

// 10. 总结
echo "\n========================================\n";
echo "  检查总结\n";
echo "========================================\n\n";

$okCount = count(array_filter($checks, fn($c) => $c['status'] === 'ok'));
$warningCount = count(array_filter($checks, fn($c) => $c['status'] === 'warning'));
$errorCount = count(array_filter($checks, fn($c) => $c['status'] === 'error'));

echo "通过: $okCount\n";
echo "警告: $warningCount\n";
echo "错误: $errorCount\n\n";

if (!empty($errors)) {
    echo "错误列表:\n";
    foreach ($errors as $error) {
        echo "  ✗ $error\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "警告列表:\n";
    foreach ($warnings as $warning) {
        echo "  ⚠ $warning\n";
    }
    echo "\n";
}

if (empty($errors)) {
    echo "✓ 所有必需配置检查通过！\n";
    echo "\n提示：如果看到警告，建议根据实际情况进行优化。\n";
    exit(0);
} else {
    echo "✗ 发现配置错误，请修复后重试。\n";
    exit(1);
}

/**
 * 解析大小字符串（如 "512M"）为字节数
 */
function parseSize($size) {
    $size = trim($size);
    $last = strtolower($size[strlen($size) - 1]);
    $size = (int)$size;
    
    switch ($last) {
        case 'g':
            $size *= 1024;
        case 'm':
            $size *= 1024;
        case 'k':
            $size *= 1024;
    }
    
    return $size;
}
