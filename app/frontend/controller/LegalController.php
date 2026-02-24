<?php

namespace app\frontend\controller;

use app\models\Setting;

class LegalController
{
    private function outputTxtFromSetting(string $settingKey, string $downloadName): void
    {
        $filename = '';
        try {
            $filename = (string) Setting::get($settingKey, '');
        } catch (\Exception $e) {
            $filename = '';
        }

        $filename = trim($filename);
        if ($filename === '') {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "未配置协议文件，请联系管理员在后台【系统配置-基础设置-协议设置】上传 .txt。\n";
            return;
        }

        // controller 位于 app/frontend/controller，public 在项目根目录下
        $publicRoot = realpath(__DIR__ . '/../../../public');
        if (!$publicRoot) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo "服务器路径错误。\n";
            return;
        }

        // 仅允许读取 public/static/errors/txt 下的 .txt
        $legalRoot = realpath($publicRoot . '/static/errors/txt');
        if (!$legalRoot) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "协议存储目录不存在。\n";
            return;
        }

        // 防止路径穿越：只使用文件名部分
        $safeName = basename(str_replace('\\', '/', $filename));
        $lower = strtolower($safeName);
        if (substr($lower, -4) !== '.txt') {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo "协议文件格式不正确（必须为 .txt）。\n";
            return;
        }

        $abs = realpath($legalRoot . DIRECTORY_SEPARATOR . $safeName);
        if (!$abs || strpos($abs, $legalRoot) !== 0 || !is_file($abs)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo "协议文件不存在。\n";
            return;
        }

        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . rawurlencode($downloadName) . '"');
        readfile($abs);
    }

    public function userAgreement(): void
    {
        $this->outputTxtFromSetting('user_agreement_txt_path', 'user_agreement.txt');
    }

    public function privacyPolicy(): void
    {
        $this->outputTxtFromSetting('privacy_policy_txt_path', 'privacy_policy.txt');
    }
}

