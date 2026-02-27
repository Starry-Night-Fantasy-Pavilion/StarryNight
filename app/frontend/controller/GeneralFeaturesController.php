<?php

namespace app\frontend\controller;

/**
 * 通用功能控制器
 */
class GeneralFeaturesController extends BaseUserController
{
    protected $currentPage = 'general_features';

    /**
     * 通用功能首页
     */
    public function index()
    {
        try {
            $userId = $this->checkAuth();
            $user = $this->getCurrentUser();

            $this->render('general_features/index', [
                'title' => '通用功能',
                'user_id' => $userId,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            error_log('通用功能页面错误: ' . $e->getMessage());
            \app\services\ErrorHandler::handleServerError($e);
        }
    }
}
