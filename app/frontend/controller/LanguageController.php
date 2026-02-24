<?php

namespace app\frontend\controller;

/**
 * 语言切换控制器
 */
class LanguageController
{
    /**
     * 切换语言
     * GET /language/switch?lang=zh-cn|en
     */
    public function switchLanguage()
    {
        $lang = $_GET['lang'] ?? 'zh-cn';
        
        // 验证语言代码
        $supportedLanguages = ['zh-cn', 'en'];
        if (!in_array($lang, $supportedLanguages)) {
            $lang = 'zh-cn';
        }
        
        // 设置session
        session_start();
        $_SESSION['language'] = $lang;
        
        // 设置cookie（30天）
        setcookie('language', $lang, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        
        // 返回JSON响应（用于AJAX请求）
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'language' => $lang,
                'message' => '语言切换成功'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // 普通请求：重定向回来源页面或首页
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $referer);
        exit;
    }
    
    /**
     * 获取当前语言
     * GET /language/current
     */
    public function getCurrentLanguage()
    {
        session_start();
        
        // 优先从session获取，其次cookie，最后默认zh-cn
        $currentLang = $_SESSION['language'] 
            ?? $_COOKIE['language'] 
            ?? 'zh-cn';
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'language' => $currentLang
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
