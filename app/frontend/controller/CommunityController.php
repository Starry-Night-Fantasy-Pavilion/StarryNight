<?php

namespace app\frontend\controller;

/**
 * 社区控制器
 */
class CommunityController extends BaseUserController
{
    protected $currentPage = 'community';

    /**
     * 社区首页
     */
    public function index()
    {
        try {
            $userId = $this->checkAuth();
            $user = $this->getCurrentUser();

            $this->render('community/index', [
                'title' => '社区',
                'user_id' => $userId,
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            error_log('社区页面错误: ' . $e->getMessage());
            \app\services\ErrorHandler::handleServerError($e);
        }
    }
}
