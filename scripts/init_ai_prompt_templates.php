<?php

/**
 * AI提示词模板初始化脚本
 * 用于初始化小说创作工具的提示词模板
 */

require_once __DIR__ . '/../public/index.php';

use app\models\AIPromptTemplate;

// 分类映射
$categories = [
    'chapter_analysis' => '章节分析',
    'book_analysis' => '拆书仿写',
    'opening' => '黄金开篇',
    'title' => '书名生成',
    'description' => '简介生成',
    'cheat' => '金手指生成',
    'name' => '名字生成',
    'cover' => '封面描述',
    'short_story' => '短篇创作',
    'short_drama' => '短剧剧本'
];

// 模板数据
$templates = [
    // 章节分析模板
    [
        'name' => '章节质量评估',
        'category' => '章节分析',
        'description' => '对章节进行专业质量评估，包括情节、角色、文笔等多个维度',
        'template_content' => "你是一位资深文学编辑。请对以下章节进行专业分析：

【章节内容】：{chapter_content}

【角色设定】：{character_settings}

【情节背景】：{plot_background}

请从以下维度分析：

1. 情节评估（1-10分）
   - 节奏：是否合理
   - 冲突：是否突出
   - 转折：是否自然

2. 角色表现（1-10分）
   - 行为合理性
   - 对话质量
   - 情感刻画

3. 文笔质量（1-10分）
   - 语言流畅度
   - 描写生动性
   - 氛围营造

4. 具体建议
   - 需要加强的地方（至少3点）
   - 可以删减的内容
   - 修改方向

请给出评分（总分/各项满分）和详细分析。",
        'variables' => [
            ['name' => 'chapter_content', 'label' => '章节内容', 'type' => 'textarea', 'required' => true],
            ['name' => 'character_settings', 'label' => '角色设定', 'type' => 'textarea', 'required' => false],
            ['name' => 'plot_background', 'label' => '情节背景', 'type' => 'textarea', 'required' => false]
        ],
        'is_system' => 1,
        'is_active' => 1
    ],

    // 拆书分析模板
    [
        'name' => '拆书分析',
        'category' => '拆书仿写',
        'description' => '分析参考文本的写作技巧、风格特点和结构特点',
        'template_content' => "你是一位文学分析专家。请分析以下文本的写作技巧：

【参考文本】：{reference_text}

请分析：

1. 写作技巧
   - 叙事视角
   - 描写手法
   - 修辞技巧

2. 风格特点
   - 语言风格
   - 节奏控制
   - 氛围营造

3. 结构特点
   - 段落安排
   - 信息展示顺序
   - 留白技巧

4. 仿写建议
   - 可以学习的技巧
   - 练习方向
   - 注意事项",
        'variables' => [
            ['name' => 'reference_text', 'label' => '参考文本', 'type' => 'textarea', 'required' => true]
        ],
        'is_system' => 1,
        'is_active' => 1
    ],

    // 仿写生成模板
    [
        'name' => '仿写创作',
        'category' => '拆书仿写',
        'description' => '基于分析结果仿照原文风格进行创作',
        'template_content' => "基于以下分析，请仿照该风格创作：

【原文分析】：{analysis}

【原文参考】：{reference_text}

【仿写主题】：{new_theme}

【仿写要求】：{requirements}

请创作约 {word_count} 字的内容，保持原文的风格和技巧。",
        'variables' => [
            ['name' => 'reference_text', 'label' => '原文参考', 'type' => 'textarea', 'required' => true],
            ['name' => 'analysis', 'label' => '原文分析', 'type' => 'textarea', 'required' => true],
            ['name' => 'new_theme', 'label' => '仿写主题', 'type' => 'text', 'required' => true],
            ['name' => 'requirements', 'label' => '仿写要求', 'type' => 'textarea', 'required' => false],
            ['name' => 'word_count', 'label' => '字数', 'type' => 'number', 'required' => true, 'default' => 500]
        ],
        'is_system' => 1,
        'is_active' => 1
    ],

    // 黄金开篇模板
    [
        'name' => '黄金开篇生成',
        'category' => '黄金开篇',
        'description' => '生成引人入胜的小说开篇，奠定作品基调',
        'template_content' => "你是一位资深小说编辑。请根据以下信息，为小说创作一个引人入胜的开篇：

【小说类型】：{novel_type}
【核心主题】：{core_theme}
【主要人物】：{main_character}
【开篇氛围】：{opening_atmosphere}

请创作一个约 {word_count} 字的开篇，要求：
- 悬念迭起，引人入胜
- 奠定作品基调
- 展现主要人物性格
- 设置情节伏笔",
        'variables' => [
            ['name' => 'novel_type', 'label' => '小说类型', 'type' => 'text', 'required' => true],
            ['name' => 'core_theme', 'label' => '核心主题', 'type' => 'text', 'required' => true],
            ['name' => 'main_character', 'label' => '主要人物', 'type' => 'text', 'required' => true],
            ['name' => 'opening_atmosphere', 'label' => '开篇氛围', 'type' => 'text', 'required' => false],
            ['name' => 'word_count', 'label' => '字数', 'type' => 'number', 'required' => true, 'default' => 500]
        ],
        'is_system' => 1,
        'is_active' => 1
    ],

    // 书名生成模板
    [
        'name' => '爆款书名生成',
        'category' => '书名生成',
        'description' => '生成吸引读者的爆款书名',
        'template_content' => "你是一位爆款书名策划师。请根据以下小说信息，生成{count}个吸引人的书名：

【小说类型】：{novel_type}
【核心主题】：{core_theme}
【关键词】：{keywords}

要求：
- 简洁、有力、有记忆点
- 能够吸引读者眼球
- 体现小说核心卖点
- 避免过于俗套

请以列表形式返回书名，每个书名附上简要说明。",
        'variables' => [
            ['name' => 'novel_type', 'label' => '小说类型', 'type' => 'text', 'required' => true],
            ['name' => 'core_theme', 'label' => '核心主题', 'type' => 'text', 'required' => true],
            ['name' => 'keywords', 'label' => '关键词', 'type' => 'text', 'required' => false],
            ['name' => 'count', 'label' => '生成数量', 'type' => 'number', 'required' => true, 'default' => 5]
        ],
        'is_system' => 1,
        'is_active' => 1
    ],

    // 简介生成模板
    [
        'name' => '小说简介生成',
        'category' => '简介生成',
        'description' => '生成精炼吸睛的小说简介',
        'template_content' => "你是一位资深小说编辑。请为以下小说创作一个精炼吸睛的简介：

【书名】：{title}
【小说类型】：{novel_type}
【核心主题】：{core_theme}
【主要人物】：{main_character}

请创作一个约 {word_count} 字的简介，要求：
- 让读者欲罢不能
- 产生阅读冲动
- 突出小说亮点
- 设置悬念吸引",
        'variables' => [
            ['name' => 'title', 'label' => '书名', 'type' => 'text', 'required' => true],
            ['name' => 'novel_type', 'label' => '小说类型', 'type' => 'text', 'required' => true],
            ['name' => 'core_theme', 'label' => '核心主题', 'type' => 'text', 'required' => true],
            ['name' => 'main_character', 'label' => '主要人物', 'type' => 'text', 'required' => true],
            ['name' => 'word_count', 'label' => '字数', 'type' => 'number', 'required' => true, 'default' => 200]
        ],
        'is_system' => 1,
        'is_active' => 1
    ],

    // 金手指生成模板
    [
        'name' => '金手指设计',
        'category' => '金手指生成',
        'description' => '设计新颖有趣的金手指设定',
        'template_content' => "你是一位创意写作专家。请为以下小说设计一个出其不意的金手指（特殊能力/设定）：

【小说类型】：{novel_type}
【核心主题】：{core_theme}
【主要人物】：{main_character}

请设计一个新颖、有趣且与故事紧密结合的金手指，包括：

1. 金手指的具体设定（能力名称、表现形式）
2. 使用条件和限制（防止过于强大）
3. 如何推动剧情发展
4. 可能产生的戏剧冲突
5. 后续升级方向
6. 与世界观的关系",
        'variables' => [
            ['name' => 'novel_type', 'label' => '小说类型', 'type' => 'text', 'required' => true],
            ['name' => 'core_theme', 'label' => '核心主题', 'type' => 'text', 'required' => true],
            ['name' => 'main_character', 'label' => '主要人物', 'type' => 'text', 'required' => true]
        ],
        'is_system' => 1,
        'is_active' => 1
    ],

    // 名字生成模板
    [
        'name' => '角色/地名/势力名生成',
        'category' => '名字生成',
        'description' => '生成独特而富有寓意的名字',
        'template_content' => "请为以下设定生成{count}个独特而富有寓意的{name_type}：

【题材风格】：{genre}
【人物类型】：{character_type}
【风格要求】：{style}

要求：
- 符合题材氛围
- 有独特性和记忆点
- 富有寓意或象征意义
- 朗朗上口

请以列表形式返回，每个名字附上简要寓意说明。",
        'variables' => [
            ['name' => 'name_type', 'label' => '名字类型', 'type' => 'select', 'required' => true, 'options' => [
                ['value' => 'character', 'label' => '人物名字'],
                ['value' => 'place', 'label' => '地名'],
                ['value' => 'faction', 'label' => '势力/组织名'],
                ['value' => 'skill', 'label' => '技能名'],
                ['value' => 'item', 'label' => '物品名']
            ]],
            ['name' => 'genre', 'label' => '题材风格', 'type' => 'text', 'required' => false],
            ['name' => 'character_type', 'label' => '人物类型', 'type' => 'text', 'required' => false],
            ['name' => 'style', 'label' => '风格要求', 'type' => 'text', 'required' => false],
            ['name' => 'count', 'label' => '生成数量', 'type' => 'number', 'required' => true, 'default' => 10]
        ],
        'is_system' => 1,
        'is_active' => 1
    ],

    // 封面描述生成模板
    [
        'name' => '封面描述生成',
        'category' => '封面描述',
        'description' => '根据小说内容生成封面视觉描述',
        'template_content' => "请根据以下小说信息，生成一个精美的封面描述：

【书名】：{title}
【小说类型】：{novel_type}
【核心主题】：{core_theme}
【关键元素】：{key_elements}

请描述一个吸引人的封面视觉设计，包括：

1. 整体风格和色调（如何体现小说氛围）
2. 主要视觉元素（人物、场景、意象）
3. 氛围和情绪（传达怎样的情感）
4. 文字排版建议（书名、简介的位置和样式）
5. 整体构图思路",
        'variables' => [
            ['name' => 'title', 'label' => '书名', 'type' => 'text', 'required' => true],
            ['name' => 'novel_type', 'label' => '小说类型', 'type' => 'text', 'required' => true],
            ['name' => 'core_theme', 'label' => '核心主题', 'type' => 'text', 'required' => true],
            ['name' => 'key_elements', 'label' => '关键元素', 'type' => 'textarea', 'required' => false]
        ],
        'is_system' => 1,
        'is_active' => 1
    ],

    // 短篇创作模板
    [
        'name' => '短篇小说创作',
        'category' => '短篇创作',
        'description' => '创作精彩的短篇小说',
        'template_content' => "请创作一个精彩的短篇小说：

【题材类型】：{genre}
【核心主题】：{theme}
【主要人物】：{main_character}
【情节梗概】：{plot}

要求：
- 创作约 {word_count} 字
- 情节完整，有起承转合
- 人物立体，有鲜明特点
- 结局有回味，引发思考
- 保持风格统一

请直接开始创作故事。",
        'variables' => [
            ['name' => 'genre', 'label' => '题材类型', 'type' => 'text', 'required' => true],
            ['name' => 'theme', 'label' => '核心主题', 'type' => 'text', 'required' => true],
            ['name' => 'main_character', 'label' => '主要人物', 'type' => 'text', 'required' => true],
            ['name' => 'plot', 'label' => '情节梗概', 'type' => 'textarea', 'required' => true],
            ['name' => 'word_count', 'label' => '目标字数', 'type' => 'number', 'required' => true, 'default' => 2000]
        ],
        'is_system' => 1,
        'is_active' => 1
    ],

    // 短剧剧本模板
    [
        'name' => '短剧剧本创作',
        'category' => '短剧剧本',
        'description' => '创作精彩的短剧剧本',
        'template_content' => "请创作一个精彩的短剧剧本：

【剧名】：{title}
【类型】：{genre}
【主要人物】：{main_character}
【剧情梗概】：{plot}

请创作 {episode_count} 集剧本，每集包含：

1. 场景描述（时间、地点、环境）
2. 人物对白（标注说话人，体现人物性格）
3. 动作提示（表情、肢体语言）
4. 情绪变化（从什么情绪到什么情绪）
5. 每集结尾的悬念设置（吸引观众继续观看）

格式示例：
【场景1】内景 - 客厅 - 夜
（描述）
张三：\"台词...\"
李四：\"台词...\"
",
        'variables' => [
            ['name' => 'title', 'label' => '剧名', 'type' => 'text', 'required' => true],
            ['name' => 'genre', 'label' => '类型', 'type' => 'text', 'required' => true],
            ['name' => 'main_character', 'label' => '主要人物', 'type' => 'text', 'required' => true],
            ['name' => 'plot', 'label' => '剧情梗概', 'type' => 'textarea', 'required' => true],
            ['name' => 'episode_count', 'label' => '集数', 'type' => 'number', 'required' => true, 'default' => 1]
        ],
        'is_system' => 1,
        'is_active' => 1
    ]
];

// 执行初始化
echo "开始初始化AI提示词模板...\n\n";

$successCount = 0;
$failCount = 0;

foreach ($templates as $template) {
    try {
        // 检查是否已存在同名模板
        $existing = null;
        $allTemplates = AIPromptTemplate::getAll(1, 100, $template['category'])['templates'];
        foreach ($allTemplates as $t) {
            if ($t['name'] === $template['name']) {
                $existing = $t;
                break;
            }
        }

        if ($existing) {
            echo "跳过（已存在）：{$template['name']}\n";
            $failCount++;
            continue;
        }

        $id = AIPromptTemplate::create($template);
        if ($id) {
            echo "✓ 创建成功：{$template['name']}\n";
            $successCount++;
        } else {
            echo "✗ 创建失败：{$template['name']}\n";
            $failCount++;
        }
    } catch (\Exception $e) {
        echo "✗ 异常：{$template['name']} - {$e->getMessage()}\n";
        $failCount++;
    }
}

echo "\n==========\n";
echo "初始化完成！\n";
echo "成功：{$successCount} 个\n";
echo "跳过：{$failCount} 个\n";
echo "==========\n";
