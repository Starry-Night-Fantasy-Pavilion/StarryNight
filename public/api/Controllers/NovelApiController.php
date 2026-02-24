<?php

declare(strict_types=1);

namespace api\controllers;

use app\models\NovelChapter;
use app\models\NovelCharacter;
use app\services\NovelAIService;

/**
 * 小说相关API控制器
 */
class NovelApiController extends BaseApiController
{
    /**
     * 获取章节详情
     */
    public function getChapter($chapterId): void
    {
        $result = $this->requireChapterAccess((int)$chapterId);
        $this->success(['chapter' => $result['chapter']]);
    }

    /**
     * 保存章节
     */
    public function saveChapter(): void
    {
        $novelId = $this->postInt('novel_id');
        $this->requireNovelAccess($novelId);

        $chapterId = $this->postInt('chapter_id');
        $title = $this->post('title', '');
        $content = $this->post('content', '');

        if ($chapterId > 0) {
            NovelChapter::update($chapterId, compact('title', 'content'));
        } else {
            $chapters = NovelChapter::findByNovel($novelId);
            $chapterId = NovelChapter::create([
                'novel_id' => $novelId,
                'chapter_number' => count($chapters) + 1,
                'title' => $title,
                'content' => $content,
            ]);
        }

        $this->success(['chapter_id' => $chapterId], '保存成功');
    }

    /**
     * 获取章节版本历史
     */
    public function getChapterVersions(): void
    {
        $chapterId = $this->getInt('chapter_id');
        if ($chapterId <= 0) {
            $this->error('无效的章节ID');
        }

        $this->requireChapterAccess($chapterId);
        $this->success(['versions' => NovelChapter::getVersions($chapterId)]);
    }

    /**
     * AI续写
     */
    public function aiContinue(): void
    {
        $novelId = $this->postInt('novel_id');
        $novel = $this->requireNovelAccess($novelId);

        $this->callAIService('continueWriting', [
            'context' => $this->post('context', ''),
            'characters' => $this->buildCharactersText($novelId),
            'plot_requirements' => $novel['theme'] ?? '',
            'style' => '',
            'word_count' => $this->postInt('word_count', 500),
        ]);
    }

    /**
     * AI改写
     */
    public function aiRewrite(): void
    {
        $this->requireAuth();
        $this->callAIService('rewrite', [
            'content' => $this->post('content', ''),
            'requirements' => $this->post('requirements', ''),
        ]);
    }

    /**
     * AI扩写
     */
    public function aiExpand(): void
    {
        $this->requireAuth();
        $this->callAIService('expand', [
            'content' => $this->post('content', ''),
            'target_words' => $this->postInt('target_words', 1000),
            'direction' => $this->post('direction', ''),
        ]);
    }

    /**
     * AI润色
     */
    public function aiPolish(): void
    {
        $this->requireAuth();
        $this->callAIService('polish', [
            'content' => $this->post('content', ''),
            'style' => $this->post('style', ''),
        ]);
    }

    /**
     * 调用AI服务
     */
    private function callAIService(string $method, array $params): void
    {
        echo json_encode(NovelAIService::$method($params));
    }

    /**
     * 构建角色文本
     */
    private function buildCharactersText(int $novelId): string
    {
        $characters = NovelCharacter::findByNovel($novelId);
        if (empty($characters)) {
            return '';
        }

        $texts = [];
        foreach ($characters as $char) {
            $texts[] = $char['name'] . '：' . ($char['personality'] ?? '');
        }
        return implode("\n", $texts);
    }
}
