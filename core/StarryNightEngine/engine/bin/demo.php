<?php

// 本 demo 以项目根目录的 composer autoload 为准
require __DIR__ . '/../../../../vendor/autoload.php';

use StarryNightEngine\Contracts\EngineRequest;
use StarryNightEngine\Contracts\UserTier;
use StarryNightEngine\EngineFactory;

$engine = EngineFactory::default();

$req = new EngineRequest(
    userQuery: '接着写主角遇到龙的场景，保持紧张但有希望。',
    context: [
        'last_excerpt' => '他踏入龙巢，潮湿的石壁上凝着黑色的水光。',
        'entities' => ['阿瑞斯', '幽暗森林', '银器', '屠龙咒文'],
        'must_include' => ['屠龙咒文', '银器弱点'],
        'must_avoid' => ['火系魔法', '突然出现的盟友'],
        // 作为“记忆库”示例：后续你可替换为数据库/向量库检索结果
        'memory_corpus' => [
            ['id' => 'lore-001', 'content' => '银器能刺穿龙鳞的缝隙，是古老猎龙人留下的弱点学。', 'meta' => ['tag' => 'lore']],
            ['id' => 'spell-013', 'content' => '屠龙咒文需要以血为引，最后一句必须在对视时吐出。', 'meta' => ['tag' => 'spell']],
            ['id' => 'scene-042', 'content' => '幽暗森林的雾会吞没回声，脚步声听起来像来自更深处。', 'meta' => ['tag' => 'scene']],
        ],
    ],
    options: [
        'top_k' => 6,
    ],
);

$tier = new UserTier(UserTier::REGULAR);
$resp = $engine->generate($req, $tier);

echo $resp->content;

// 如需查看 debug：取消注释
// var_dump($resp->debug);

