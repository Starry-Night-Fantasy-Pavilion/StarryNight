<?php

declare(strict_types=1);

namespace api\controllers;

use app\models\NoticeBar;

/**
 * 通知栏API控制器
 */
class NoticeApiController extends BaseApiController
{
    /**
     * 获取通知栏数据
     */
    public function index(): void
    {
        $lang = $this->get('lang', 'zh-CN');
        $notices = NoticeBar::getAll($lang, 'enabled');
        $currentTime = date('Y-m-d H:i:s');
        $validNotices = [];

        foreach ($notices as $notice) {
            $startTime = $notice['display_from'] ?? null;
            $endTime = $notice['display_to'] ?? null;
            $timeValid = true;

            if ($startTime && $startTime > $currentTime) {
                $timeValid = false;
            }
            if ($endTime && $endTime < $currentTime) {
                $timeValid = false;
            }

            if ($timeValid) {
                $validNotices[] = [
                    'id' => $notice['id'],
                    'content' => $notice['content'],
                    'link' => $notice['link'] ?? null,
                    'priority' => $notice['priority']
                ];
            }
        }

        usort($validNotices, function($a, $b) {
            if ($a['priority'] == $b['priority']) {
                return 0;
            }
            return ($a['priority'] > $b['priority']) ? -1 : 1;
        });

        $this->success($validNotices, '获取通知栏成功');
    }
}
