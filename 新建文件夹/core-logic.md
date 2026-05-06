# 星夜阁 - 核心逻辑设计文档

## 版本历史

| 版本 | 日期 | 说明 |
|------|------|------|
| 1.0 | 2026-04-28 | 初始版本 |

---

# 星夜阁 - 核心逻辑设计文档

## 版本历史

| 版本 | 日期 | 说明 |
|------|------|------|
| 1.1 | 2026-04-28 | 新增星夜引擎架构设计 |
| 1.0 | 2026-04-28 | 初始版本 |

---

## 核心架构：星夜引擎 (StarryNight Engine)

### 设计理念：用向量持久记忆替代注意力窗口

大语言模型最大的局限是上下文窗口有限，且注意力在长文本中稀释。星夜引擎的解法：

```
┌─────────────────────────────────────────────────────────────────┐
│                    星夜引擎核心原理                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   所有创作资产 ──▶ 向量化存储 ──▶ 智能召回 ──▶ 约束上下文     │
│                                                                  │
│   ├── 世界观设定  ──▶ 世界观向量库                              │
│   ├── 角色档案    ──▶ 角色向量库 + 状态快照                    │
│   ├── 地点/物品  ──▶ 实体向量库                                │
│   ├── 情节点      ──▶ 情节向量库                                │
│   ├── 章节正文    ──▶ 叙事向量库                                │
│   └── 伏笔        ──▶ 伏笔向量库                                │
│                                                                  │
│   每次生成 ──▶ 从向量库召回相关约束 ──▶ 构建C-Prompt ──▶ 生成 │
│                                                                  │
│   每次产出 ──▶ 解析/总结/向量化 ──▶ 入库成为永久记忆         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 星夜引擎 vs 传统对话式AI

| 特性 | 传统对话式AI | 星夜引擎 |
|------|--------------|----------|
| 记忆方式 | 上下文窗口 | 向量持久存储 |
| 约束来源 | 手动提供 | 自动召回 |
| 长篇一致性 | 容易跑偏 | 严格锚定 |
| 创作方式 | 自由聊天 | 专业步骤工作流 |
| 跑偏防御 | 无 | 多层防御机制 |
| 伏笔管理 | 易遗忘 | 主动追踪 |

---

## 第二部分：星夜引擎整体架构

### 2.1 引擎架构图

```
┌─────────────────────────────────────────────────────────────────┐
│                       星夜引擎 (StarryNight Engine)              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                   专业步骤工作流                          │    │
│  │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐     │    │
│  │  │世界观   │ │角色    │ │故事骨架  │ │章节生成 │     │    │
│  │  │锚定     │ │孵化器   │ │          │ │车间     │     │    │
│  │  └─────────┘ └─────────┘ └─────────┘ └─────────┘     │    │
│  │  ┌─────────┐ ┌─────────┐                               │    │
│  │  │记忆    │ │整体    │                               │    │
│  │  │巩固    │ │收束    │                               │    │
│  │  └─────────┘ └─────────┘                               │    │
│  └─────────────────────────────────────────────────────────┘    │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                   MemCore 记忆管理器                     │    │
│  │              (向量条目版本控制/快照管理/过期标记)         │    │
│  └─────────────────────────────────────────────────────────┘    │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              向量数据库 (Milvus/Qdrant)                   │    │
│  │  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐     │    │
│  │  │ 设定库  │ │ 实体库  │ │ 叙事库  │ │风格库   │     │    │
│  │  │ 世界观  │ │角色/地点│ │章节/情节│ │指纹     │     │    │
│  │  │ 规则    │ │ 组织    │ │ 伏笔    │ │         │     │    │
│  │  └─────────┘ └─────────┘ └─────────┘ └─────────┘     │    │
│  └─────────────────────────────────────────────────────────┘    │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                   混合检索引擎                           │    │
│  │            (稠密向量+稀疏关键词+元数据过滤)              │    │
│  └─────────────────────────────────────────────────────────┘    │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                 C-Prompt 构建器                         │    │
│  │           (上下文编织 + 约束注入 + 优先级排序)           │    │
│  └─────────────────────────────────────────────────────────┘    │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                   一致性自检引擎                         │    │
│  │    (性格偏移检测/世界规则冲突/时间线缝合)               │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 向量存储结构

```typescript
// 向量条目基础结构
interface VectorEntry {
  id: string;                      // 唯一ID
  chunk: string;                   // 文本块(有明确语义边界)

  // 向量表示
  denseVector: number[];          // 稠密向量(嵌入模型生成)
  sparseVector?: SparseVector;     // 稀疏向量(关键词BM25)

  // 元数据
  metadata: {
    type: EntryType;              // 条目类型
    subType?: string;             // 子类型

    // 关联ID
    entityIds: {
      novelId?: string;           // 所属小说
      characterId?: string;       // 关联角色
      locationId?: string;        // 关联地点
      itemId?: string;            // 关联物品
      chapterId?: string;         // 关联章节
      outlineNodeId?: string;     // 关联情节点
    };

    // 时间戳
    narrativeTimestamp?: string;   // 叙事时间点(故事内时间)
    createdAt: Date;              // 创作时间
    updatedAt: Date;              // 更新时间

    // 状态标签
    status: 'active' | 'superseded' | 'historical_snapshot' | 'archived';

    // 重要性
    importanceWeight: number;     // 0-1, 用于召回排序

    // 特殊标记
    tags: string[];               // 自定义标签
    foreshadowingId?: string;     // 如果是伏笔相关
  };
}

type EntryType =
  | 'world_setting'     // 世界观设定
  | 'rule'             // 规则定义
  | 'character'        // 角色
  | 'character_snapshot' // 角色状态快照
  | 'location'         // 地点
  | 'organization'     // 组织
  | 'item'            // 物品
  | 'plot_point'       // 情节点
  | 'chapter_summary'  // 章节摘要
  | 'chapter_segment'  // 章节正文片段
  | 'foreshadowing'   // 伏笔
  | 'style_fingerprint' // 风格指纹
  | 'event';          // 事件
```

### 2.3 多库分区设计

```typescript
// 向量数据库分区
const VECTOR_COLLECTIONS = {
  // 设定库：写入后极少修改，作为绝对约束
  settings: {
    name: 'settings',
    description: '世界观设定、规则定义',
    entryTypes: ['world_setting', 'rule'],
    retention: 'permanent',        // 永久保留
    updateFrequency: 'rare',       // 极少更新
   召回Weight: 1.0,              // 最高召回权重
  },

  // 实体库：高频更新，保留状态历史版本
  entities: {
    name: 'entities',
    description: '角色、地点、组织、物品',
    entryTypes: ['character', 'character_snapshot', 'location', 'organization', 'item'],
    retention: 'with_history',     // 带历史版本
    updateFrequency: 'high',        // 高频更新
    snapshotRetention: 10,          // 保留最近10个快照
  },

  // 叙事库：章节摘要、情节点、伏笔
  narrative: {
    name: 'narrative',
    description: '情节、章节、伏笔',
    entryTypes: ['plot_point', 'chapter_summary', 'chapter_segment', 'event'],
    retention: 'permanent',
    updateFrequency: 'per_chapter', // 每章更新
  },

  // 风格指纹库
  style: {
    name: 'style',
    description: '作者风格指纹',
    entryTypes: ['style_fingerprint'],
    retention: 'permanent',
    updateFrequency: 'low',
  },
};

// 角色状态快照机制
interface CharacterSnapshot {
  id: string;
  characterId: string;
  snapshotNo: number;              // 快照编号

  // 快照内容
  content: {
    mentalState: string;          // 精神状态
    physicalState: string;         // 身体状态
    relationships: {              // 关系状态变化
      [characterId: string]: {
        type: string;
        affinity: number;
        note?: string;
      };
    };
    beliefs: string[];            // 信仰/价值观
    goals: string[];              // 当前目标
    arcPosition: string;          // 在角色弧光中的位置
  };

  // 来源
  sourceChapterId?: string;       // 来源章节
  sourceOutlineNodeId?: string;   // 来源大纲节点

  // 向量
  stateVector: number[];         // 状态向量
  personalityVector: number[];    // 性格向量
  languageFingerprint: number[];  // 语言指纹向量

  // 时间戳
  narrativeTimestamp?: string;     // 叙事时间点
  createdAt: Date;
}
```

---

## 第三部分：专业步骤工作流

### 3.1 工作流总览

```
┌─────────────────────────────────────────────────────────────────┐
│                    星夜引擎 专业步骤工作流                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 步骤一：世界观锚定                                        │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │ • 定义宇宙规则、地理历史、势力分布、魔法/科技体系         │   │
│  │ • 结构化表单填写 或 文档上传解析                         │   │
│  │ • 设定条目分块向量化，建立层级索引                       │   │
│  │ • 产出：世界观知识图谱文本片段(全部可召回)              │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 步骤二：角色孵化器                                       │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │ • 创建角色档案(外貌/性格/成长弧光/语言风格/禁忌)        │   │
│  │ • 引导式提问完善角色细节                                │   │
│  │ • 生成多维度向量(性格向量/语言指纹向量)                  │   │
│  │ • 角色状态快照机制：每个关键时刻记录独立向量             │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 步骤三：故事骨架                                         │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │ • 构建整体剧情结构(幕/序列/章节概要)                    │   │
│  │ • 拖拽卡片定义情节线                                    │   │
│  │ • 情节点关联角色/地点/设定                             │   │
│  │ • 自动检测多线交叉点的时间线一致性                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 步骤四：章节生成车间                                     │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │ • 4.1 写作意图声明 (输入本章核心事件)                    │   │
│  │ • 4.2 上下文编织 (智能召回相关约束)                      │   │
│  │ • 4.3 受控生成 (保守/均衡/创意滑杆)                     │   │
│  │ • 4.4 一致性自检 (性格偏移/规则冲突/时间线)              │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 步骤五：长程记忆巩固                                     │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │ • 章节摘要与事件抽取，向量化入库                         │   │
│  │ • 伏笔捕捉：识别呼应点，记录待回收伏笔                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 步骤六：整体收束                                         │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │ • 全文一致性回顾：人物性格演变曲线聚类                   │   │
│  │ • 世界线检视面板：横向时间轴展示事件/状态变迁           │   │
│  │ • 全局设定回溯：一键列出支撑段落的设定向量              │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 章节生成车间详解

#### 4.1 写作意图声明

```typescript
interface WritingIntent {
  // 核心事件描述
  coreEvent: string;              // 一句话描述本章发生什么

  // 当前状态
  currentState: {
    sceneLocation: string;        // 场景地点
    narrativeTime?: string;       // 叙事时间
    atmosphere: string;           // 氛围
    weather?: string;             // 天气
  };

  // 出场角色
  presentCharacters: {
    characterId: string;
    role: 'protagonist' | 'supporting' | 'antagonist';
    currentGoal?: string;         // 本章目标
  }[];

  // 情感基调
  emotionalTone: 'tension' | 'warmth' | 'sadness' | 'excitement' | 'mystery';

  // 与大纲的关联
  relatedOutlineNodes: string[];   // 关联的大纲节点ID

  // 用户偏好
  generationMode: 'conservative' | 'balanced' | 'creative';
}
```

#### 4.2 上下文编织 (Context Weaving)

```typescript
class ContextWeaver {
  private vectorStore: VectorStore;
  private hybridRetriever: HybridRetriever;

  async weave(intent: WritingIntent): Promise<CPromptContext> {
    const contextParts: ContextPart[] = [];

    // 1. 召回相关角色向量
    const characterContexts = await this.recallCharacterContexts(intent);
    contextParts.push(...characterContexts);

    // 2. 召回相关设定与地点向量
    const settingContexts = await this.recallSettingContexts(intent);
    contextParts.push(...settingContexts);

    // 3. 召回最近章节摘要向量
    const recentChapterContexts = await this.recallRecentChapters(intent, limit: 5);
    contextParts.push(...recentChapterContexts);

    // 4. 召回大纲节点及伏笔
    const outlineContexts = await this.recallOutlineContexts(intent);
    contextParts.push(...outlineContexts);

    // 5. 按优先级排序和截断
    const sortedContext = this.prioritizeAndTruncate(contextParts);

    // 6. 构建C-Prompt
    return this.buildCPrompt(sortedContext, intent);
  }

  private async recallCharacterContexts(
    intent: WritingIntent
  ): Promise<CharacterContext[]> {
    const contexts: CharacterContext[] = [];

    for (const char of intent.presentCharacters) {
      // 获取角色基线档案
      const baseline = await this.vectorStore.search({
        collection: 'entities',
        filter: { type: 'character', entityIds.characterId: char.characterId },
        limit: 1,
      });

      // 获取角色最近状态快照(按时间戳降序取最近3条)
      const snapshots = await this.vectorStore.search({
        collection: 'entities',
        filter: {
          type: 'character_snapshot',
          'metadata.entityIds.characterId': char.characterId,
        },
        limit: 3,
        sort: { narrativeTimestamp: -1 },
      });

      // 获取角色近期行为摘要
      const recentBehaviors = await this.vectorStore.search({
        collection: 'narrative',
        filter: {
          type: 'event',
          'metadata.entityIds.characterId': char.characterId,
        },
        limit: 5,
        // 召回最近3-5章的相关事件
      });

      contexts.push({
        characterId: char.characterId,
        baseline: baseline[0],
        recentSnapshots: snapshots,
        recentBehaviors: recentBehaviors,
        currentGoal: char.currentGoal,
      });
    }

    return contexts;
  }

  private async recallSettingContexts(
    intent: WritingIntent
  ): Promise<SettingContext[]> {
    // 检索与场景相关的设定
    return await this.hybridRetriever.search({
      query: intent.currentState.sceneLocation,
      collection: 'settings',
      filters: [
        { type: 'world_setting' },
        { type: 'location' },
      ],
      limit: 10,
    });
  }

  private async recallRecentChapters(
    intent: WritingIntent,
    limit: number
  ): Promise<ChapterContext[]> {
    // 获取最近N章的摘要
    return await this.vectorStore.search({
      collection: 'narrative',
      filter: { type: 'chapter_summary' },
      limit: limit,
      sort: { 'metadata.chapterNo': -1 },
    });
  }

  private async recallOutlineContexts(
    intent: WritingIntent
  ): Promise<OutlineContext[]> {
    const contexts: OutlineContext[] = [];

    // 获取关联的大纲节点
    for (const nodeId of intent.relatedOutlineNodes) {
      const node = await this.vectorStore.get(nodeId);
      contexts.push({
        node,
        relatedForeshadowings: await this.getUnresolvedForeshadowings(nodeId),
      });
    }

    // 检索相关的未回收伏笔
    const foreshadowings = await this.vectorStore.search({
      collection: 'narrative',
      filter: {
        type: 'foreshadowing',
        'metadata.status': 'unresolved',
        // 与当前情节语义距离 < 0.5
      },
      limit: 5,
    });

    return contexts;
  }

  private prioritizeAndTruncate(contextParts: ContextPart[]): ContextPart[] {
    // 按类型优先级排序
    const priorityOrder = [
      'character_baseline',    // 角色基线(最高)
      'character_snapshot',    // 角色快照
      'world_rule',           // 世界规则
      'location_detail',      // 地点细节
      'outline_node',         // 大纲节点
      'recent_chapter',       // 近期章节
      'foreshadowing',        // 伏笔
      'recent_event',         // 近期事件
    ];

    // 排序
    contextParts.sort((a, b) =>
      priorityOrder.indexOf(a.type) - priorityOrder.indexOf(b.type)
    );

    // 截断到模型可用长度
    const maxTokens = this.config.maxContextTokens;
    return this.truncateToTokenLimit(contextParts, maxTokens);
  }
}
```

#### 4.3 受控生成

```typescript
interface CPromptContext {
  systemPrompt: string;
  constraints: Constraint[];
  narrativeContext: NarrativeContext;
  generationConfig: {
    temperature: number;
    maxTokens: number;
    bindingStrength: number;  // 约束绑定强度 0-1
  };
}

class ControlledGenerator {
  generate(
    cPrompt: CPromptContext,
    mode: 'conservative' | 'balanced' | 'creative'
  ): GenerationResult {
    // 根据模式调整生成参数
    const config = this.adjustConfig(cPrompt.generationConfig, mode);

    // 构建完整提示词
    const fullPrompt = this.buildFullPrompt(cPrompt, config);

    // 执行生成
    return this.executeGeneration(fullPrompt, config);
  }

  private adjustConfig(
    baseConfig: GenerationConfig,
    mode: string
  ): GenerationConfig {
    const modes = {
      conservative: {
        bindingStrength: 0.9,   // 强绑定设定
        temperature: 0.3,
        repetitionPenalty: 1.2,
      },
      balanced: {
        bindingStrength: 0.7,
        temperature: 0.6,
        repetitionPenalty: 1.1,
      },
      creative: {
        bindingStrength: 0.5,   // 允许更多创意
        temperature: 0.9,
        repetitionPenalty: 1.0,
      },
    };

    return { ...baseConfig, ...modes[mode] };
  }
}
```

#### 4.4 一致性自检

```typescript
class ConsistencyChecker {
  async check(
    generated: string,
    context: CPromptContext
  ): Promise<ConsistencyReport> {
    const issues: ConsistencyIssue[] = [];

    // 1. 性格偏移检测
    const personalityIssues = await this.checkPersonalityDrift(
      generated,
      context.characters
    );
    issues.push(...personalityIssues);

    // 2. 世界规则冲突检测
    const ruleConflicts = await this.checkRuleConflicts(
      generated,
      context.worldRules
    );
    issues.push(...ruleConflicts);

    // 3. 时间线缝合检查
    const timelineIssues = await this.checkTimelineContinuity(
      generated,
      context.narrativeContext
    );
    issues.push(...timelineIssues);

    return {
      hasIssues: issues.length > 0,
      issuesBySeverity: this.groupBySeverity(issues),
      issuesByType: this.groupByType(issues),
      report: this.formatReport(issues),
      suggestions: issues.map(i => this.generateFixSuggestion(i)),
    };
  }

  private async checkPersonalityDrift(
    content: string,
    characters: CharacterContext[]
  ): Promise<PersonalityIssue[]> {
    const issues: PersonalityIssue[] = [];

    for (const char of characters) {
      // 提取本章中该角色的行为描述
      const behaviors = this.extractCharacterBehaviors(content, char.characterId);

      // 计算行为向量与角色基线的相似度
      const behaviorVector = await this.embeddingModel.embed(behaviors);
      const similarity = this.cosineSimilarity(
        behaviorVector,
        char.baseline.personalityVector
      );

      // 如果相似度低于阈值，标记为偏移
      const threshold = 0.7;
      if (similarity < threshold) {
        issues.push({
          type: 'personality_drift',
          characterId: char.characterId,
          severity: similarity < 0.5 ? 'high' : 'medium',
          description: `角色"${char.name}"的行为表现与设定偏差${(1-similarity)*100.toFixed(0)}%`,
         偏离证据: behaviors,
          baseline约束: char.baseline,
        });
      }
    }

    return issues;
  }

  private async checkRuleConflicts(
    content: string,
    rules: WorldRule[]
  ): Promise<RuleConflict[]> {
    const conflicts: RuleConflict[] = [];

    for (const rule of rules) {
      // 提取本章中与该规则相关的事件
      const relevantEvents = this.extractRuleRelatedEvents(content, rule);

      // 使用逻辑蕴含判断是否冲突
      for (const event of relevantEvents) {
        const isViolation = await this.checkRuleViolation(event, rule);
        if (isViolation) {
          conflicts.push({
            type: 'rule_violation',
            rule,
            violatingEvent: event,
            severity: 'high',
            description: `违反规则: ${rule.description}`,
          });
        }
      }
    }

    return conflicts;
  }
}
```

---

## 第四部分：混合检索策略

### 4.1 检索架构

```typescript
class HybridRetriever {
  private denseRetriever: DenseRetriever;
  private sparseRetriever: SparseRetriever;
  private metadataFilter: MetadataFilter;

  async search(params: SearchParams): Promise<SearchResult[]> {
    const { query, filters, limit, hybridWeights } = params;

    // 1. 稠密向量检索
    const denseResults = await this.denseRetriever.search(
      await this.embeddingModel.embed(query),
      filters
    );

    // 2. 稀疏向量检索(BM25)
    const sparseResults = await this.sparseRetriever.search(
      this.tokenizer.extractTerms(query),
      filters
    );

    // 3. 元数据过滤
    const filteredDense = this.metadataFilter.apply(denseResults, filters);
    const filteredSparse = this.metadataFilter.apply(sparseResults, filters);

    // 4. 混合评分融合
    const fused = this.hybridFusion(
      filteredDense,
      filteredSparse,
      hybridWeights || { dense: 0.7, sparse: 0.3 }
    );

    // 5. RRF重排序
    const reranked = this.rrfRerank(fused, limit);

    return reranked;
  }

  // RRF (Reciprocal Rank Fusion) 重排序
  private rrfRerank(results: SearchResult[], limit: number): SearchResult[] {
    const rrfScores = new Map<string, number>();

    for (const result of results) {
      const id = result.entry.id;
      const rank = results.indexOf(result) + 1;
      const rrfScore = 1 / (60 + rank);

      rrfScores.set(id, (rrfScores.get(id) || 0) + rrfScore);
    }

    return Array.from(rrfScores.entries())
      .sort((a, b) => b[1] - a[1])
      .slice(0, limit)
      .map(([id]) => results.find(r => r.entry.id === id)!);
  }
}
```

### 4.2 专项检索查询

```typescript
class ContextWeaver {
  // 针对章节生成的专项检索

  async recallForChapter(intent: WritingIntent): Promise<RetrievalResult> {
    const results = await Promise.all([
      // 检索与章节大纲语义相似度>0.8的情节点
      this.hybridRetriever.search({
        query: intent.coreEvent,
        filter: { type: 'plot_point' },
        minSimilarity: 0.8,
      }),

      // 检索当前出场角色的所有状态快照,按时间戳降序取最近3条
      ...intent.presentCharacters.map(char =>
        this.vectorStore.search({
          collection: 'entities',
          filter: {
            type: 'character_snapshot',
            'metadata.entityIds.characterId': char.characterId,
          },
          limit: 3,
          sort: { 'metadata.narrativeTimestamp': -1 },
        })
      ),

      // 检索与写作意图中动作短语相关的设定规则与地点细节
      this.hybridRetriever.search({
        query: this.extractActionPhrases(intent.coreEvent).join(' '),
        filter: { type: ['world_setting', 'rule', 'location'] },
      }),

      // 检索所有标记为'未回收'且与当前情节向量余弦距离<0.5的伏笔
      this.vectorStore.search({
        collection: 'narrative',
        filter: {
          type: 'foreshadowing',
          'metadata.status': 'unresolved',
        },
        maxDistance: 0.5,
      }),
    ]);

    return this.mergeAndWeight(results);
  }
}
```

---

## 第五部分：跑偏防御机制

### 5.1 防御层次

```
┌─────────────────────────────────────────────────────────────────┐
│                    星夜引擎跑偏防御机制                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 空间约束层                                               │   │
│  │ ─────────────────────────────────────────────────────  │   │
│  │                                                         │   │
│  │ 生成前: 通过向量检索建立严格的"设定边界"                 │   │
│  │   • 召回场景规则/物理限制                               │   │
│  │   • C-Prompt中明确注入约束条款                         │   │
│  │   • 模型输出被限制在召回的设定范围内                   │   │
│  │                                                         │   │
│  │ 防御效果: 防止出现违反世界观的场景/行为                 │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 时间约束层                                               │   │
│  │ ─────────────────────────────────────────────────────  │   │
│  │                                                         │   │
│  │ • 带时间戳的世界线向量确保故事进展与时序一致           │   │
│  │ • 章节事件必须继承前序状态                              │   │
│  │ • 时间线因果链闭环检测                                 │   │
│  │                                                         │   │
│  │ 防御效果: 防止时间线混乱/状态突变                       │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 性格约束层                                               │   │
│  │ ─────────────────────────────────────────────────────  │   │
│  │                                                         │   │
│  │ • 性格基线向量持续对比                                   │   │
│  │ • 任何对话/行为漂移立即预警                             │   │
│  │ • 状态快照记录角色成长弧光                              │   │
│  │                                                         │   │
│  │ 防御效果: 防止角色性格跳变                              │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 伏笔约束层                                               │   │
│  │ ─────────────────────────────────────────────────────  │   │
│  │                                                         │   │
│  │ • 伏笔作为语义钉子在后续创作中被主动唤起               │   │
│  │ • 适时提醒回收时机                                      │   │
│  │ • 自动检测冲突的伏笔回收                               │   │
│  │                                                         │   │
│  │ 防御效果: 防止伏笔遗忘或矛盾                           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 人工确认闭环                                             │   │
│  │ ─────────────────────────────────────────────────────  │   │
│  │                                                         │   │
│  │ • 自检报告不是替代作者,而是提供精确的"航向偏移指示"    │   │
│  │ • 作者决策修正方向                                      │   │
│  │ • 人机协同的专业创作模式                               │   │
│  │                                                         │   │
│  │ 防御效果: 最终一致性由作者确认                         │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 第六部分：典型使用场景

### 6.1 场景示例

```
┌─────────────────────────────────────────────────────────────────┐
│                  星夜引擎使用场景示例                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  背景: 作者已完成三部曲第一本,正在创作第二本第17章              │
│                                                                  │
│  输入: 章节意图 "主角团发现古籍中隐藏的第三个封印位置"          │
│                                                                  │
│  引擎召回:                                                      │
│  ─────────────────────────────────────────────────────────────   │
│                                                                  │
│  1. 世界观库: 第一部中关于"封印体系"的完整规则                │
│     → 规则1: 封印共5层,每层需不同条件                        │
│     → 规则2: 第三封印需活体献祭                                │
│                                                                  │
│  2. 实体库: 古籍详细描述(来自第一部第32章,曾标记为伏笔)      │
│     → 物品: 《上古封印秘典》,记载封印解除方法                  │
│                                                                  │
│  3. 角色库: 主角团三位成员当前精神状态快照                    │
│     → 露娜: 坚定、信任队友、愿意牺牲                          │
│     → 雷恩: 谨慎、怀疑一切                                    │
│     → 卡尔: 冲动、重视同伴                                    │
│                                                                  │
│  4. 伏笔库: 相关未回收伏笔                                    │
│     → 伏笔: 第一部第15章提到"封印与献祭的秘密"              │
│                                                                  │
│  引擎警告:                                                      │
│  ─────────────────────────────────────────────────────────────   │
│                                                                  │
│  ⚠️ 冲突检测:                                                   │
│  设定中"第三个封印需活体献祭"与当前角色露娜的                 │
│  "不可牺牲同伴"信念冲突,请处理此矛盾.                         │
│                                                                  │
│  💡 建议:                                                       │
│  • 方案A: 露娜发现新的献祭方式(自我献祭)                     │
│  • 方案B: 雷恩提出替代方案(远古器物代替)                     │
│  • 方案C: 揭示献祭规则另有隐情(只需象征性牺牲)               │
│                                                                  │
│  作者决策后,引擎生成符合设定的章节内容                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 第七部分：MemCore 记忆管理器

### 7.1 核心功能

```typescript
class MemCoreManager {
  // 向量条目的版本控制
  async createVersion(entry: VectorEntry): Promise<VersionResult>;

  // 快照管理
  async createSnapshot(
    entityType: string,
    entityId: string,
    snapshotData: SnapshotData
  ): Promise<Snapshot>;

  // 过期标记
  async markAsExpired(entryId: string): Promise<void>;

  // 快照查询
  async getHistoricalSnapshots(
    entityId: string,
    options?: { limit?: number; before?: Date }
  ): Promise<Snapshot[]>;

  // 向量版本重建(当规则更新时)
  async rebuildVectorVersions(
    entryId: string,
    newEmbedding: number[]
  ): Promise<void>;
}
```

---

## 第八部分：技术选型

| 组件 | 技术选型 | 说明 |
|------|----------|------|
| 向量数据库 | Qdrant | 支持多集合、元数据过滤、混合搜索 |
| 嵌入模型 | text-embedding-3-large / BGE-M3 | 保证语义表示精度 |
| 生成模型 | GPT-4o / Claude 3.5 Sonnet | 长上下文模型,API调用 |
| 记忆管理 | 自研 MemCore | 向量条目版本控制、快照管理 |
| 前端框架 | Vue3 + Canvas | 大纲拖拽、世界线时间轴仪表盘 |

---

## 第一部分：核心流程总览

### 1.1 AI创作主流程

```
┌─────────────────────────────────────────────────────────────────┐
│                    星夜阁 AI创作核心流程                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐   │
│   │ 用户输入 │───▶│ 大纲生成 │───▶│ 卷纲生成 │───▶│细纲生成 │   │
│   │ 核心创意 │    │         │    │         │    │         │   │
│   └─────────┘    └─────────┘    └─────────┘    └─────────┘   │
│                          │              │              │        │
│                          │              │              │        │
│                          ▼              ▼              ▼        │
│                    ┌─────────────────────────────────────┐     │
│                    │         💬 阶段式AI对话             │     │
│                    │   (每个节点可单独对话、修改、迭代)      │     │
│                    └─────────────────────────────────────┘     │
│                                        │                      │
│                                        ▼                      │
│                                  ┌─────────┐                 │
│                                  │ 正文扩写 │                 │
│                                  │(可选)   │                 │
│                                  └─────────┘                 │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 1.2 核心数据流

```
┌─────────────────────────────────────────────────────────────────┐
│                      核心数据流                                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   输入层                处理层                  输出层            │
│   ─────                ──────                  ──────            │
│                                                                  │
│   核心创意 ─────┐                                                │
│   题材类型 ─────┼────▶ AI引擎 ────▶ 大纲JSON                   │
│   风格要求 ─────┤        │                                     │
│   参考素材 ─────┘        │                                     │
│                         ▼                                     │
│                    ┌───────────┐                               │
│                    │ RAG召回   │ ◀── 知识库向量检索             │
│                    │ 上下文注入 │                              │
│                    └───────────┘                               │
│                         │                                      │
│                         ▼                                      │
│                    ┌───────────┐                               │
│                    │ 大纲结构  │                               │
│                    │ (JSON)    │                               │
│                    └───────────┘                               │
│                         │                                      │
│         ┌───────────────┼───────────────┐                      │
│         ▼               ▼               ▼                      │
│   ┌───────────┐  ┌───────────┐  ┌───────────┐                 │
│   │ 卷纲生成  │  │ 卷纲生成  │  │ 卷纲生成  │                 │
│   │ 第一卷    │  │ 第二卷    │  │ 第三卷    │                 │
│   └───────────┘  └───────────┘  └───────────┘                 │
│         │               │               │                      │
│         ▼               ▼               ▼                      │
│   ┌───────────────────────────────────────────────────┐        │
│   │              章节细纲生成                           │        │
│   │  (每个卷下的章节生成细纲卡片)                       │        │
│   └───────────────────────────────────────────────────┘        │
│                         │                                      │
│                         ▼                                      │
│   ┌───────────────────────────────────────────────────┐        │
│   │              正文扩写/智能续写                      │        │
│   │  (根据细纲生成完整正文)                            │        │
│   └───────────────────────────────────────────────────┘        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 第二部分：AI生成引擎

### 2.1 引擎架构

```
┌─────────────────────────────────────────────────────────────────┐
│                      AI生成引擎架构                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    PromptBuilder                        │    │
│  │              提示词构建器                               │    │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────┐         │    │
│  │  │ 模板加载  │ │ 变量替换  │ │ 上下文注入 │         │    │
│  │  └────────────┘ └────────────┘ └────────────┘         │    │
│  └─────────────────────────────────────────────────────────┘    │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                    RAGRetrieval                         │    │
│  │                  知识检索增强                           │    │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────┐         │    │
│  │  │ 向量检索  │ │ 重排序    │ │ 知识注入  │         │    │
│  │  └────────────┘ └────────────┘ └────────────┘         │    │
│  └─────────────────────────────────────────────────────────┘    │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                   LocalAIEngine                          │    │
│  │                本地AI推理引擎                           │    │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────┐         │    │
│  │  │ WebLLM    │ │ TensorFlow │ │  ONNX      │         │    │
│  │  │ (LLM)     │ │ (NLP)     │ │ (推理)     │         │    │
│  │  └────────────┘ └────────────┘ └────────────┘         │    │
│  └─────────────────────────────────────────────────────────┘    │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                  OutputProcessor                        │    │
│  │                  输出处理器                            │    │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────┐         │    │
│  │  │ JSON解析  │ │ 敏感词过滤│ │ 后处理    │         │    │
│  │  └────────────┘ └────────────┘ └────────────┘         │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 提示词构建器

```typescript
interface PromptBuilder {
  // 加载提示词模板
  loadTemplate(templateId: string): PromptTemplate;

  // 构建完整提示词
  build(params: BuildParams): string;
}

interface BuildParams {
  templateId: string;           // 模板ID
  variables: Record<string, any>;  // 变量值
  context: {
    knowledgeChunks?: Chunk[];  // 召回的知识片段
    characterInfo?: Character[]; // 相关角色信息
    worldSetting?: WorldSetting; // 世界观设定
    previousOutline?: Outline;   // 已有大纲(用于续写)
    userPreferences?: UserPrefs; // 用户偏好
  };
  generationConfig?: {
    temperature?: number;
    maxTokens?: number;
    topP?: number;
  };
}
```

### 2.3 提示词模板结构

```json
{
  "template_id": "outline_generation",
  "name": "小说大纲生成",
  "category": "outline",
  "version": "1.0",
  "prompt_template": "你是一位资深的网络小说编辑，擅长设计引人入胜的故事结构。\n\n请根据以下信息生成一个完整的【{genre}】类型小说大纲：\n\n## 基本信息\n- 核心创意：{core_idea}\n- 目标字数：{word_count}字\n- 写作风格：{style}\n- 大纲模板：{template_type}\n\n## 参考知识\n{knowledge_context}\n\n## 角色设定\n{character_context}\n\n## 世界观\n{world_setting}\n\n## 要求\n1. 大纲应包含清晰的三幕结构（建置/对抗/解决）\n2. 每幕需包含核心冲突、关键事件、人物弧线\n3. 主线清晰，支线为辅\n4. 结局要有高潮和情感释放\n5. 符合网文读者的阅读习惯\n\n请以JSON格式输出。",
  "variables": [
    {
      "name": "genre",
      "type": "string",
      "required": true,
      "description": "小说题材"
    },
    {
      "name": "core_idea",
      "type": "string",
      "required": true,
      "description": "一句话核心创意"
    },
    {
      "name": "word_count",
      "type": "number",
      "default": 300000
    },
    {
      "name": "style",
      "type": "string",
      "default": "热血、爽文"
    },
    {
      "name": "template_type",
      "type": "enum",
      "options": ["golden_three_act", "hero_journey", "five_act_structure"],
      "default": "golden_three_act"
    }
  ],
  "output_format": "json",
  "output_schema": {
    "title": "string",
    "subtitle": "string?",
    "three_act_structure": {
      "act1": {
        "name": "string",
        "summary": "string",
        "chapters_summary": "string",
        "key_events": ["string"]
      },
      "act2": {...},
      "act3": {...}
    },
    "main_characters": [{
      "id": "string",
      "name": "string",
      "role": "string",
      "brief": "string"
    }],
    "world_setting": "string",
    "potential_hooks": ["string"]
  },
  "model": "qwen2-1.5b-instruct",
  "parameters": {
    "temperature": 0.8,
    "max_tokens": 4096,
    "top_p": 0.9
  }
}
```

---

## 第三部分：大纲生成逻辑

### 3.1 大纲生成流程

```
┌─────────────────────────────────────────────────────────────────┐
│                    大纲生成流程                                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────┐                                                │
│  │ 1.输入验证   │ ──▶ 必填字段校验、格式校验                      │
│  └──────┬──────┘                                                │
│         │                                                        │
│         ▼                                                        │
│  ┌─────────────┐                                                │
│  │ 2.素材召回  │ ──▶ RAG检索相关知识、角色                       │
│  └──────┬──────┘                                                │
│         │                                                        │
│         ▼                                                        │
│  ┌─────────────┐                                                │
│  │ 3.模板组装  │ ──▶ 加载模板、替换变量、注入上下文              │
│  └──────┬──────┘                                                │
│         │                                                        │
│         ▼                                                        │
│  ┌─────────────┐                                                │
│  │ 4.AI生成   │ ──▶ 本地WebLLM推理                            │
│  └──────┬──────┘                                                │
│         │                                                        │
│         ▼                                                        │
│  ┌─────────────┐                                                │
│  │ 5.输出解析  │ ──▶ JSON解析、格式校验、敏感词过滤             │
│  └──────┬──────┘                                                │
│         │                                                        │
│         ▼                                                        │
│  ┌─────────────┐                                                │
│  │ 6.存储      │ ──▶ 保存到数据库、创建版本记录                 │
│  └─────────────┘                                                │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 大纲生成算法

```typescript
class OutlineGenerator {
  private rag: RAGRetrieval;
  private ai: LocalAIEngine;
  private templateStore: TemplateStore;

  async generate(params: OutlineGenerateParams): Promise<OutlineResult> {
    // 1. 输入验证
    this.validateInput(params);

    // 2. 素材召回
    const context = await this.buildContext(params);

    // 3. 提示词构建
    const template = this.templateStore.get('outline_generation');
    const prompt = this.buildPrompt(template, params, context);

    // 4. AI生成
    const rawOutput = await this.ai.generate(prompt, {
      temperature: template.parameters.temperature,
      maxTokens: template.parameters.maxTokens,
    });

    // 5. 输出解析
    const outline = this.parseOutline(rawOutput);

    // 6. 敏感词过滤
    const filteredOutline = this.filterSensitiveContent(outline);

    // 7. 存储
    const saved = await this.saveOutline(params.novelId, filteredOutline);

    return {
      outline: saved,
      tokensUsed: rawOutput.usage.totalTokens,
      generationTime: rawOutput.generationTime,
    };
  }

  private async buildContext(params: OutlineGenerateParams): Promise<GenerationContext> {
    const context: GenerationContext = {
      knowledgeChunks: [],
      characters: [],
      worldSetting: null,
    };

    // 召回相关知识
    if (params.knowledgeIds?.length) {
      context.knowledgeChunks = await this.rag.retrieveByIds(params.knowledgeIds);
    }

    // 召回相关角色
    if (params.characterIds?.length) {
      context.characters = await this.getCharacters(params.characterIds);
    }

    // 召回相似作品参考
    if (params.genre) {
      const similarOutlines = await this.rag.retrieveSimilar('outline', params.genre, 3);
      context.similarOutlines = similarOutlines;
    }

    return context;
  }

  private parseOutline(rawOutput: string): ParsedOutline {
    try {
      const json = JSON.parse(rawOutput);
      return this.validateAndNormalize(json);
    } catch (e) {
      // 尝试修复不完整的JSON
      const fixed = this.fixIncompleteJSON(rawOutput);
      return JSON.parse(fixed);
    }
  }
}
```

### 3.3 大纲输出结构

```typescript
interface Outline {
  id: string;
  novelId: string;
  title: string;
  subtitle?: string;
  genre: string;
  style: string;
  targetWordCount: number;
  templateType: 'golden_three_act' | 'hero_journey' | 'five_act_structure';

  threeActStructure: {
    act1: {
      name: string;
      summary: string;
      chaptersSummary: string;
      keyEvents: string[];
      conflict: string;
      characterArc: string;
    };
    act2: {
      name: string;
      summary: string;
      chaptersSummary: string;
      keyEvents: string[];
      midpoint: string;
      darkestMoment: string;
    };
    act3: {
      name: string;
      summary: string;
      chaptersSummary: string;
      keyEvents: string[];
      climax: string;
      resolution: string;
    };
  };

  mainCharacters: {
    id: string;
    name: string;
    role: 'protagonist' | 'antagonist' | 'supporting';
    brief: string;
  }[];

  worldSetting: {
    name: string;
    mainRegions: string[];
    factions: string[];
    powerSystem: string;
  };

  potentialHooks: string[];

  version: number;
  createdAt: Date;
  updatedAt: Date;
}
```

---

## 第四部分：卷纲生成逻辑

### 4.1 卷纲生成流程

```
┌─────────────────────────────────────────────────────────────────┐
│                    卷纲生成流程                                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  输入: 大纲ID + 分卷数量                                         │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  1. 获取大纲上下文                                        │   │
│  │     • 读取完整大纲                                        │   │
│  │     • 分析三幕结构                                        │   │
│  │     • 提取各幕核心冲突                                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  2. 分卷策略计算                                          │   │
│  │     • 根据目标字数计算每卷容量                            │   │
│  │     • 根据情节分布规划分卷点                              │   │
│  │     • 识别自然分卷节点(如高潮后)                         │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  3. 各卷生成 (可并行)                                    │   │
│  │     ┌─────────┐  ┌─────────┐  ┌─────────┐              │   │
│  │     │ 生成卷1 │  │ 生成卷2 │  │ 生成卷3 │              │   │
│  │     └─────────┘  └─────────┘  └─────────┘              │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  4. 卷间衔接优化                                         │   │
│  │     • 确保卷间情节连贯                                   │   │
│  │     • 伏笔传递检查                                       │   │
│  │     • 角色弧线跨卷延续                                   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  输出: Volume[]                                                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 卷纲生成算法

```typescript
class VolumeGenerator {
  async generate(params: VolumeGenerateParams): Promise<Volume[]> {
    // 1. 获取大纲
    const outline = await this.outlineRepo.getById(params.outlineId);

    // 2. 分卷策略
    const splitPlan = this.calculateSplitPlan(outline, params.volumeCount);

    // 3. 并行生成各卷
    const volumes = await Promise.all(
      splitPlan.map((plan, index) =>
        this.generateSingleVolume(outline, plan, index + 1)
      )
    );

    // 4. 卷间衔接优化
    this.optimizeVolumeConnection(volumes);

    // 5. 存储
    const savedVolumes = await this.saveVolumes(params.novelId, volumes);

    return savedVolumes;
  }

  private calculateSplitPlan(outline: Outline, volumeCount: number): VolumeSplitPlan[] {
    const plans: VolumeSplitPlan[] = [];
    const totalWordCount = outline.targetWordCount;
    const wordsPerVolume = Math.floor(totalWordCount / volumeCount);

    // 三幕分配策略
    const actDistribution = [
      { act: 'act1', ratio: 0.15 },  // 第一幕占15%
      { act: 'act2', ratio: 0.60 },  // 第二幕占60%
      { act: 'act3', ratio: 0.25 },   // 第三幕占25%
    ];

    let currentPosition = 0;
    for (let i = 0; i < volumeCount; i++) {
      const volumeIndex = i + 1;
      const isFirstVolume = i === 0;
      const isLastVolume = i === volumeCount - 1;

      // 确定本卷所属的幕
      let containedActs = [];
      let currentRatio = 0;
      for (const act of actDistribution) {
        currentRatio += act.ratio;
        if ((i + 1) / volumeCount <= currentRatio) {
          containedActs.push(act.act);
        }
      }

      plans.push({
        volumeIndex,
        volumeTitle: this.suggestVolumeTitle(containedActs, i),
        containedActs,
        wordCountTarget: wordsPerVolume,
        startPoint: this.getActStartPoint(outline, containedActs[0]),
        endPoint: isLastVolume
          ? outline.threeActStructure.act3.climax
          : this.findNaturalBreakPoint(outline, wordsPerVolume),
      });
    }

    return plans;
  }

  private async generateSingleVolume(
    outline: Outline,
    plan: VolumeSplitPlan,
    volumeIndex: number
  ): Promise<Volume> {
    const prompt = this.buildVolumePrompt(outline, plan);

    const rawOutput = await this.ai.generate(prompt, {
      temperature: 0.75,
      maxTokens: 2048,
    });

    return this.parseVolume(rawOutput, plan, volumeIndex);
  }
}
```

### 4.3 卷纲输出结构

```typescript
interface Volume {
  id: string;
  novelId: string;
  volumeNo: number;
  title: string;
  subtitle?: string;

  // 核心内容
  theme: string;                    // 本卷主题
  coreConflict: string;              // 本卷核心冲突
  summary: string;                  // 本卷概述

  // 章节预览
  chaptersPreview: {
    chapterNo: number;
    title: string;
    brief: string;
  }[];

  // 关键事件
  keyEvents: {
    eventName: string;
    chapterRange: string;          // 如 "1-5"
    description: string;
    emotionalBeat: string;          // 情感高点描述
    isClimax: boolean;
  }[];

  // 角色弧线
  characterArcs: {
    characterId: string;
    arcDescription: string;
  }[];

  // 冲突设计
  conflicts: {
    type: 'main' | 'sub' | 'romantic';
    description: string;
    relatedChapters: string;
  }[];

  // 伏笔
  foreshadowing: {
    setup: string;                 // 伏笔内容
    payoffChapter?: number;         // 回收章节
  }[];

  // 关联
  previousVolumeId?: string;
  nextVolumeId?: string;

  wordCount: number;
  status: 'planning' | 'writing' | 'completed';

  createdAt: Date;
  updatedAt: Date;
}
```

---

## 第五部分：章节细纲生成逻辑

### 5.1 细纲生成流程

```
┌─────────────────────────────────────────────────────────────────┐
│                    章节细纲生成流程                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  输入: 卷ID + 章节数量                                           │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  1. 获取卷上下文                                         │   │
│  │     • 卷纲内容                                          │   │
│  │     • 本卷角色列表                                      │   │
│  │     • 本卷关键事件                                      │   │
│  │     • 上卷遗留伏笔                                      │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  2. 章节节奏规划                                        │   │
│  │     • 高潮章节分布(每3-5章一个高点)                     │   │
│  │     • 铺垫与释放节奏                                     │   │
│  │     • 章节类型分配(战斗/情感/日常/转折)                 │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  3. 单章细纲生成 (可并行/可串行)                         │   │
│  │     • 输入: 章节序号、卷上下文、节奏规划                 │   │
│  │     • 输出: 章节细纲卡片                                 │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  4. 章节间衔接检查                                      │   │
│  │     • 上章结尾与本章开头衔接                            │   │
│  │     • 伏笔埋设与回收                                    │   │
│  │     • 角色状态连续性                                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  输出: ChapterDraft[]                                                   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 5.2 细纲卡片生成

```typescript
class ChapterDraftGenerator {
  async generate(params: ChapterDraftParams): Promise<ChapterDraft> {
    // 1. 获取上下文
    const context = await this.buildContext(params);

    // 2. 构建提示词
    const prompt = this.buildPrompt(context, params);

    // 3. 生成
    const rawOutput = await this.ai.generate(prompt, {
      temperature: 0.7,
      maxTokens: 1536,
    });

    // 4. 解析
    const draft = this.parseDraft(rawOutput);

    // 5. 增强
    draft.emotionalCurve = this.calculateEmotionalCurve(draft);
    draft.foreshadowing = this.extractForeshadowing(draft);

    return draft;
  }

  private buildPrompt(context: ChapterContext, params: ChapterDraftParams): string {
    return `
你是一位专业的网文大纲设计师，正在为第${params.chapterNo}章生成细纲。

## 卷纲上下文
主题: ${context.volume.theme}
核心冲突: ${context.volume.coreConflict}
本卷关键事件: ${context.volume.keyEvents.map(e => e.eventName).join(', ')}

## 章节规划
- 章节序号: ${params.chapterNo}
- 目标字数: ${params.targetWordCount}字
- 章节类型: ${params.chapterType || 'standard'}
- 节奏位置: ${params.pacingPosition} (0-1, 0=开头, 1=高潮)

## 前章衔接
上章结尾: ${context.previousChapter?.ending || '无'}
上章结束时角色状态: ${context.previousChapter?.characterStatus || '无'}

## 本卷角色
${context.characters.map(c => `- ${c.name}: ${c.currentStatus}`).join('\n')}

## 生成要求
请生成包含以下要素的章节细纲：
1. 基础信息：章节标题、核心事件
2. 场景设定：时间、地点、氛围
3. 出场人物：主要人物及其本章目标
4. 情节点：至少5个情节点（含开端、发展、转折、高潮、结尾）
5. 情感曲线：本章的情感变化
6. 关键对白：1-3句关键对白
7. 伏笔埋设：本章埋下的伏笔（如有）
8. 与前后章节的衔接

请以JSON格式输出。
`;
  }
}
```

### 5.3 章节细纲输出结构

```typescript
interface ChapterDraft {
  id: string;
  chapterId: string;

  // 基础信息
  chapterNo: number;
  title: string;
  coreEvent: string;

  // 场景设定
  sceneSetting: {
    location: string;
    time: string;
    atmosphere: string;
    lighting?: string;
  };

  // 出场人物
  charactersPresent: {
    characterId: string;
    name: string;
    chapterGoal: string;        // 本章目标
    status: string;             // 本章状态
  }[];

  // 情节点
  plotPoints: {
    order: number;
    type: 'opening' | 'development' | 'turning_point' | 'climax' | 'ending';
    description: string;
    pov?: string;               // 视角
    dialogueSnippet?: string;   // 关键对白
    emotionalChange: string;    // 情感变化
  }[];

  // 情感曲线
  emotionalCurve: {
    start: number;               // 0-10
    peak: number;
    end: number;
    keyPoints: { position: number; emotion: string }[];
  };

  // 关键对白
  keyDialogues: {
    speaker: string;
    content: string;
    purpose: string;
  }[];

  // 伏笔
  foreshadowing: {
    setup: string;
    type: 'plot' | 'character' | 'world';
  }[];

  // 衔接
  connectionToPrevious: string;
  connectionToNext: string;

  // 元数据
  targetWordCount: number;
  pacingType: 'slow' | 'medium' | 'fast';
  isClimaxChapter: boolean;

  // 状态
  status: 'draft' | 'approved' | 'expanded';
  version: number;
}
```

---

## 第六部分：正文扩写逻辑

### 6.1 扩写流程

```
┌─────────────────────────────────────────────────────────────────┐
│                    正文扩写流程                                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  输入: 章节细纲ID + (可选)风格样本                               │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  1. 加载上下文                                          │   │
│  │     • 章节细纲                                          │   │
│  │     • 角色设定(对话风格、性格)                          │   │
│  │     • 之前正文(用于衔接)                                │   │
│  │     • 风格样本(如有)                                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  2. 风格分析 (如有样本)                                  │   │
│  │     • 句长分布                                          │   │
│  │     • 对话占比                                          │   │
│  │     • 描写密度                                          │   │
│  │     • 情感倾向                                          │   │
│  │     → 生成风格指纹                                      │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  3. 分段生成                                            │   │
│  │     • 开头段                                            │   │
│  │     • 发展段 (可多个)                                   │   │
│  │     • 高潮段                                            │   │
│  │     • 结尾段                                            │   │
│  │                                                        │   │
│  │     每段生成后进行流畅度检查，再生成下一段              │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  4. 衔接优化                                            │   │
│  │     • 开头与上章结尾衔接                                │   │
│  │     • 各段之间过渡                                      │   │
│  │     • 结尾留钩子                                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  5. 后处理                                             │   │
│  │     • 敏感词过滤                                        │   │
│  │     • 错别字检查(可选)                                  │   │
│  │     • 标点符号规范化                                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  输出: ChapterContent                                                   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 6.2 风格扩写算法

```typescript
class ContentExpander {
  async expand(params: ExpandParams): Promise<ExpandResult> {
    // 1. 加载上下文
    const context = await this.loadContext(params);

    // 2. 风格分析
    let styleFingerprint = null;
    if (params.styleSample) {
      styleFingerprint = await this.analyzeStyle(params.styleSample);
    }

    // 3. 构建生成计划
    const generationPlan = this.createGenerationPlan(context.draft, params.expandRatio);

    // 4. 分段生成
    const paragraphs: string[] = [];
    for (const segment of generationPlan.segments) {
      const paragraph = await this.generateParagraph(context, segment, styleFingerprint);
      paragraphs.push(paragraph);

      // 流畅度检查
      if (!this.checkFluency(paragraphs)) {
        // 重新生成或调整
        await this.regenerate(paragraphs, segment);
      }
    }

    // 5. 衔接优化
    const content = this.optimizeConnections(paragraphs, context);

    // 6. 后处理
    const processed = this.postProcess(content);

    return {
      content: processed,
      wordCount: this.countWords(processed),
      styleFingerprint,
      generationTime: Date.now() - params.startTime,
    };
  }

  private async analyzeStyle(sample: string): Promise<StyleFingerprint> {
    const stats = {
      sentenceLengths: this.extractSentenceLengths(sample),
      dialogueRatio: this.calculateDialogueRatio(sample),
      descriptionDensity: this.calculateDescriptionDensity(sample),
      emotionalTone: await this.analyzeEmotionalTone(sample),
    };

    // 生成风格指纹
    return {
      avgSentenceLength: this.average(stats.sentenceLengths),
      sentenceLengthVariance: this.variance(stats.sentenceLengths),
      dialogueRatio: stats.dialogueRatio,
      descriptionDensity: stats.descriptionDensity,
      emotionalTone: stats.emotionalTone,
      commonPhrases: this.extractCommonPhrases(sample),
      pacingType: this.inferPacingType(stats),
    };
  }

  private async generateParagraph(
    context: ExpansionContext,
    segment: GenerationSegment,
    style?: StyleFingerprint
  ): Promise<string> {
    const prompt = this.buildExpandPrompt(context, segment, style);

    return await this.ai.generate(prompt, {
      temperature: style ? 0.6 : 0.8,  // 有风格样本时降低随机性
      maxTokens: 1024,
    });
  }
}
```

---

## 第七部分：阶段式AI对话逻辑

### 7.1 对话上下文构建

```
┌─────────────────────────────────────────────────────────────────┐
│                    对话上下文构建                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  当用户点击某个节点的"💬AI对话"按钮时:                            │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  构建上下文                                             │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                         │   │
│  │  1. 当前节点内容                                        │   │
│  │     • 节点类型 (大纲/卷纲/细纲/正文)                    │   │
│  │     • 节点ID                                            │   │
│  │     • 节点内容                                          │   │
│  │                                                        │   │
│  │  2. 上游节点摘要                                        │   │
│  │     • 大纲摘要                                          │   │
│  │     • 已有卷纲(相关卷)                                  │   │
│  │     • 已有细纲(相邻章节)                                │   │
│  │                                                        │   │
│  │  3. 用户选中的知识库                                    │   │
│  │     • 用户手动@引用的知识片段                           │   │
│  │     • 自动检索相关的知识片段(Top-K)                     │   │
│  │                                                        │   │
│  │  4. 角色库相关                                          │   │
│  │     • 当前节点涉及的角色                                │   │
│  │     • 角色设定摘要                                      │   │
│  │                                                        │   │
│  │  5. 对话历史(如有)                                      │   │
│  │     • 同一节点的之前对话                                │   │
│  │     • 之前的修改记录                                    │   │
│  │                                                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  系统提示词组装                                          │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                         │   │
│  │  "你是一个专业的网文创作助手，正在帮助用户编辑[节点类型]。│   │
│  │   当前节点信息: [节点内容摘要]                          │   │
│  │   相关上下文: [上游节点摘要]                            │   │
│  │   参考知识: [用户选中的知识片段]                        │   │
│  │   涉及角色: [角色设定]                                  │   │
│  │   ...                                                    │   │
│  │   用户的问题是: [用户输入]"                              │   │
│  │                                                         │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  对话生成与修改                                          │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                         │   │
│  │  用户输入 → AI理解 → 生成回复 → 修改建议 → 用户确认      │   │
│  │                                                         │   │
│  │  修改建议 → 差异对比 → 接受/拒绝/继续调                  │   │
│  │                                                         │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 7.2 对话服务实现

```typescript
class AIConversationService {
  private contextBuilder: ConversationContextBuilder;
  private ai: LocalAIEngine;
  private versionManager: VersionManager;

  async sendMessage(params: SendMessageParams): Promise<ConversationResponse> {
    // 1. 构建上下文
    const context = await this.contextBuilder.build(params);

    // 2. 构建提示词
    const systemPrompt = this.buildSystemPrompt(context);
    const fullPrompt = `${systemPrompt}\n\n用户: ${params.message}\n\n助手: `;

    // 3. 生成回复
    const reply = await this.ai.chat([
      { role: 'system', content: systemPrompt },
      ...context.history.map(m => ({ role: m.role, content: m.content })),
      { role: 'user', content: params.message },
    ]);

    // 4. 解析回复类型
    const replyType = this.parseReplyType(reply);

    if (replyType === 'modification') {
      // 5. 生成差异对比
      const diff = this.generateDiff(context.nodeContent, reply.suggestedContent);

      return {
        type: 'modification',
        content: reply.content,
        diff,
        suggestedContent: reply.suggestedContent,
      };
    }

    return {
      type: 'general',
      content: reply.content,
    };
  }

  async applyModification(params: ApplyModificationParams): Promise<void> {
    // 1. 创建版本记录
    const version = await this.versionManager.createVersion({
      nodeType: params.nodeType,
      nodeId: params.nodeId,
      contentBefore: params.originalContent,
      contentAfter: params.newContent,
      conversationId: params.conversationId,
    });

    // 2. 更新节点内容
    await this.updateNode(params.nodeType, params.nodeId, params.newContent);

    // 3. 触发下游更新检查(可选)
    if (params.syncToDescendants) {
      await this.checkDescendants(params.nodeType, params.nodeId);
    }
  }
}
```

---

## 第八部分：知识库RAG检索逻辑

### 8.1 RAG流程

```
┌─────────────────────────────────────────────────────────────────┐
│                    RAG检索增强流程                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  文档处理流程 (离线)                                     │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                         │   │
│  │  文档上传 → 格式检测 → 内容提取 → 切片 → 向量化 → 存储  │   │
│  │                                                         │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  查询流程 (在线)                                         │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                         │   │
│  │  用户查询 ──▶ 向量化 ──▶ 向量检索 ──▶ 重排序 ──▶ 注入   │   │
│  │                                                         │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 8.2 RAG服务实现

```typescript
class RAGRetrieval {
  private vectorStore: VectorStore;
  private embeddingModel: EmbeddingModel;

  // 文档切片
  async chunkDocument(document: Document): Promise<Chunk[]> {
    const chunks: Chunk[] = [];

    // 1. 内容提取
    const text = await this.extractText(document);

    // 2. 语义切片
    const semanticChunks = this.semanticSplit(text, {
      maxTokens: 500,
      overlapTokens: 50,
      splitBy: 'paragraph',
    });

    // 3. 向量化
    for (const chunkText of semanticChunks) {
      const embedding = await this.embeddingModel.embed(chunkText);

      chunks.push({
        id: this.generateId(),
        documentId: document.id,
        content: chunkText,
        metadata: {
          source: document.name,
          position: chunks.length,
        },
        vector: embedding,
        tokenCount: this.countTokens(chunkText),
      });
    }

    // 4. 存储
    await this.vectorStore.insert(chunks);

    return chunks;
  }

  // 检索
  async retrieve(query: string, options: RetrieveOptions = {}): Promise<RetrievedChunk[]> {
    const {
      topK = 5,
      similarityThreshold = 0.7,
      filters = {},
    } = options;

    // 1. 查询向量化
    const queryVector = await this.embeddingModel.embed(query);

    // 2. 向量检索
    const results = await this.vectorStore.search(queryVector, {
      topK: topK * 2,  // 多检索一些用于重排序
      filters,
    });

    // 3. 重排序 (可选)
    let finalResults = results;
    if (this.rerankEnabled) {
      finalResults = await this.rerank(query, results);
    }

    // 4. 过滤和截断
    return finalResults
      .filter(r => r.similarity >= similarityThreshold)
      .slice(0, topK)
      .map(r => ({
        chunk: r.chunk,
        score: r.similarity,
        reason: this.generateRetrievalReason(query, r.chunk),
      }));
  }

  // 上下文注入
  injectContext(query: string, retrievedChunks: RetrievedChunk[]): string {
    const contextParts = retrievedChunks.map((rc, index) =>
      `【参考知识${index + 1}】\n${rc.chunk.content}\n`
    );

    return `
请参考以下知识片段来回答用户的问题：

${contextParts.join('\n')}

---
用户问题: ${query}
`;
  }
}
```

---

## 第九部分：小工具生成逻辑

### 9.1 工具列表与生成策略

| 工具 | 生成策略 | 输出格式 |
|------|----------|----------|
| 金手指生成 | 模板填充 + 规则组合 | JSON卡片 |
| 书名生成 | 关键词扩展 + 模板组合 | 列表+评分 |
| 简介生成 | 模板填充 + 爆点注入 | 文本+标签 |
| 世界观生成 | 模板填充 + 细节扩展 | 结构化文本 |
| 冲突生成 | 关系分析 + 模板应用 | 场景片段 |
| 人物速成 | 一句话扩展 + 模板填充 | JSON角色卡 |
| 扩写仿写 | 风格学习 + 分段生成 | 正文 |

### 9.2 金手指生成器

```typescript
class GoldenFingerGenerator {
  private templates: GoldenFingerTemplate[];

  async generate(params: GoldenFingerParams): Promise<GoldenFinger> {
    // 1. 选择模板
    const template = this.selectTemplate(params.type);

    // 2. 构建生成提示词
    const prompt = this.buildPrompt(template, params);

    // 3. 生成
    const rawOutput = await this.ai.generate(prompt, {
      temperature: 0.85,
      maxTokens: 2048,
    });

    // 4. 解析
    let goldenFinger = this.parse(rawOutput);

    // 5. 规则完善
    goldenFinger = this.completeRules(goldenFinger, template);

    // 6. 副作用设计
    goldenFinger.sideEffects = this.designSideEffects(goldenFinger);

    return goldenFinger;
  }

  private selectTemplate(type: string): GoldenFingerTemplate {
    const templates = {
      system: {
        name: '系统类型模板',
        rules: ['每日任务', '等级体系', '积分/金币', '商城', '成就'],
        limitations: ['每日次数限制', '等级门槛', '概率机制'],
        sideEffects: ['系统绑定', '无法交易', '暴露风险'],
      },
      old爷爷: {
        name: '老爷爷类型模板',
        rules: ['传承功法', '指导修炼', '鉴定物品', '，偶尔实体化'],
        limitations: ['灵魂力消耗', '需要材料恢复', '存在时间限制'],
        sideEffects: ['知道太多秘密', '引来觊觎', '性格古怪'],
      },
      // ... 其他类型
    };

    return templates[type];
  }
}
```

### 9.3 书名生成器

```typescript
class BookTitleGenerator {
  async generate(params: TitleParams): Promise<BookTitleResult> {
    // 1. 分析核心元素
    const elements = this.extractElements(params.coreElements);

    // 2. 多风格生成
    const titles = await Promise.all([
      this.generateByStyle(elements, '爽文'),
      this.generateByStyle(elements, '虐文'),
      this.generateByStyle(elements, '悬疑'),
      this.generateByStyle(elements, '轻松'),
    ]);

    // 3. 评分排序
    const scored = titles.map(t => ({
      ...t,
      score: this.scoreTitle(t.title, params.genre),
    }));

    return {
      titles: scored.sort((a, b) => b.score - a.score),
      analysis: this.analyzeTitlePatterns(scored),
    };
  }

  private scoreTitle(title: string, genre: string): number {
    let score = 50;

    // 长度评分
    if (title.length >= 4 && title.length <= 8) score += 20;
    else if (title.length >= 2 && title.length <= 12) score += 10;

    // 关键词匹配
    const genreKeywords = this.getGenreKeywords(genre);
    for (const kw of genreKeywords) {
      if (title.includes(kw)) score += 10;
    }

    // 吸引力评分
    if (this.hasNumbers(title)) score += 5;  // 如"第XX章"
    if (this.hasQuestion(title)) score += 5;  // 悬念感
    if (this.hasStrongWords(title)) score += 10;  // 强情绪词

    return Math.min(100, score);
  }
}
```

---

## 第十部分：角色一致性检查逻辑

### 10.1 检查流程

```
┌─────────────────────────────────────────────────────────────────┐
│                    角色一致性检查流程                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  输入: 正文内容 + 角色库                                  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  1. 角色识别                                             │   │
│  │     • 识别人物名称出现                                   │   │
│  │     • 提取相关描写                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  2. 行为分析                                             │   │
│  │     • 分析角色行为描述                                   │   │
│  │     • 提取性格特征表现                                   │   │
│  │     • 识别情绪变化                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  3. 一致性比对                                          │   │
│  │     • 性格设定 vs 行为表现                             │   │
│  │     • 能力设定 vs 行为结果                             │   │
│  │     • 背景设定 vs 当前场景                             │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  4. 问题报告                                             │   │
│  │     • 问题位置标记                                      │   │
│  │     • 问题类型分类                                      │   │
│  │     • 修改建议                                           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 10.2 一致性检查实现

```typescript
class CharacterConsistencyChecker {
  async check(params: ConsistencyCheckParams): Promise<ConsistencyReport> {
    // 1. 提取角色出现
    const characterMentions = this.extractCharacterMentions(
      params.content,
      params.characters
    );

    // 2. 分析行为
    const behaviorAnalysis = await this.analyzeBehaviors(
      params.content,
      characterMentions
    );

    // 3. 一致性比对
    const issues: ConsistencyIssue[] = [];

    for (const [charId, behaviors] of behaviorAnalysis.entries()) {
      const character = params.characters.find(c => c.id === charId);
      if (!character) continue;

      // 性格一致性
      const personalityIssues = this.checkPersonalityConsistency(
        character,
        behaviors
      );
      issues.push(...personalityIssues);

      // 能力一致性
      const abilityIssues = this.checkAbilityConsistency(
        character,
        behaviors
      );
      issues.push(...abilityIssues);

      // 背景一致性
      const backgroundIssues = this.checkBackgroundConsistency(
        character,
        behaviors
      );
      issues.push(...backgroundIssues);
    }

    // 4. 生成报告
    return {
      totalIssues: issues.length,
      issuesBySeverity: this.groupBySeverity(issues),
      issuesByType: this.groupByType(issues),
      suggestions: issues.map(i => this.generateSuggestion(i)),
    };
  }

  private checkPersonalityConsistency(
    character: Character,
    behaviors: Behavior[]
  ): ConsistencyIssue[] {
    const issues: ConsistencyIssue[] = [];

    for (const behavior of behaviors) {
      // 检查性格特征
      const personality = character.personality;

      // 如果角色设定是内向，但行为表现为极度外向
      if (personality.traits.includes('内向') && behavior.extraversionScore > 0.8) {
        issues.push({
          characterId: character.id,
          type: 'personality',
          severity: 'medium',
          location: behavior.location,
          description: `角色"${character.name}"设定为内向，但行为表现过于外向`,
          suggestion: `建议将行为调整为更符合内向性格的表现，如内心独白、观察而非主动表达等`,
        });
      }

      // 如果角色设定是理性，但行为表现为冲动
      if (personality.traits.includes('理性') && behavior.emotionalLevel > 0.8) {
        issues.push({
          characterId: character.id,
          type: 'personality',
          severity: 'high',
          location: behavior.location,
          description: `角色"${character.name}"设定为理性，但行为过于冲动`,
          suggestion: `建议在冲动行为前增加内心权衡过程，或调整行为结果以符合理性人设`,
        });
      }
    }

    return issues;
  }
}
```

---

## 第十一部分：数据模型关系

### 11.1 核心实体关系

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Novel     │────<│   Volume    │────<│   Chapter   │
│   小说      │     │   卷        │     │   章节      │
└─────────────┘     └─────────────┘     └─────────────┘
      │                                       │
      │                                       │
      ▼                                       ▼
┌─────────────┐                        ┌─────────────┐
│   Outline   │                        │ChapterDraft │
│   大纲      │                        │  章节细纲    │
└─────────────┘                        └─────────────┘
      │                                       │
      │                                       │
      ▼                                       ▼
┌─────────────┐                        ┌─────────────┐
│  Character  │                        │ChapterVersion│
│   角色      │                        │  版本历史    │
└─────────────┘                        └─────────────┘

┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│KnowledgeLib │────<│  Document   │────<│   Chunk     │
│   知识库    │     │   文档      │     │   切片       │
└─────────────┘     └─────────────┘     └─────────────┘

┌─────────────┐     ┌─────────────┐
│   Material  │     │ AIConversation│
│   素材      │     │   AI对话     │
└─────────────┘     └─────────────┘
```

### 11.2 版本控制

```
每次AI修改节点内容时:
1. 保存旧版本到 *_version 表
2. 更新节点内容
3. 记录修改来源(conversation_id)
4. 支持版本回滚
```

---

## 第十三部分：叙事节奏分析引擎

### 13.1 节奏分析流程

```
┌─────────────────────────────────────────────────────────────────┐
│                    叙事节奏分析流程                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  输入: 细纲/正文内容                                     │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  1. 内容分段                                            │   │
│  │     • 按章节/场景分割                                    │   │
│  │     • 按情节线分类(主线/支线/感情线)                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  2. 情绪分析                                            │   │
│  │     • 期待值、紧张感、温馨度、悲伤度                    │   │
│  │     • AI评估 + 用户手动标记                             │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  3. 冲突密度计算                                        │   │
│  │     • 冲突事件识别                                        │   │
│  │     • 冲突强度评级                                      │   │
│  │     • 每千字密度统计                                    │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  4. 追读预测                                            │   │
│  │     • 基于历史数据训练模型                               │   │
│  │     • 预测流失率                                          │   │
│  │     • 生成优化建议                                       │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  输出: RhythmAnalysisResult                                                   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 13.2 节奏分析引擎实现

```typescript
class RhythmAnalyzer {
  private emotionModel: EmotionClassifier;
  private conflictDetector: ConflictDetector;

  async analyze(params: RhythmAnalyzeParams): Promise<RhythmResult> {
    // 1. 内容分段
    const segments = this.segmentContent(params.content);

    // 2. 情绪分析
    const emotionalCurves = await this.analyzeEmotions(segments);

    // 3. 冲突检测
    const conflictDensities = this.calculateConflictDensity(segments);

    // 4. 追读预测
    const predictions = await this.predictRetention(segments);

    // 5. 生成建议
    const suggestions = this.generateSuggestions(
      emotionalCurves,
      conflictDensities,
      predictions
    );

    return {
      chapters: segments.map((s, i) => ({
        chapterNo: s.chapterNo,
        emotions: emotionalCurves[i],
        conflictDensity: conflictDensities[i],
        retentionPrediction: predictions[i],
      })),
      suggestions,
      overallScore: this.calculateOverallScore(predictions),
    };
  }

  private async analyzeEmotions(
    segments: ContentSegment[]
  ): Promise<EmotionCurve[]> {
    const results: EmotionCurve[] = [];

    for (const segment of segments) {
      // 调用本地AI模型进行情绪分类
      const emotions = await this.emotionModel.classify(segment.content, {
        categories: ['anticipation', 'tension', 'warmth', 'sadness'],
        granularity: 'sentence',
      });

      results.push({
        chapterNo: segment.chapterNo,
        anticipation: emotions.anticipation,
        tension: emotions.tension,
        warmth: emotions.warmth,
        sadness: emotions.sadness,
        curve: this.interpolateCurve(emotions),
      });
    }

    return results;
  }

  private calculateConflictDensity(segments: ContentSegment[]): ConflictDensity[] {
    return segments.map(segment => {
      const conflicts = this.conflictDetector.detect(segment.content);

      return {
        chapterNo: segment.chapterNo,
        conflictCount: conflicts.length,
        intensitySum: conflicts.reduce((sum, c) => sum + c.intensity, 0),
        densityPerThousandWords: (conflicts.length / segment.wordCount) * 1000,
        conflicts: conflicts.map(c => ({
          type: c.type,
          intensity: c.intensity,
          position: c.position,
        })),
      };
    });
  }

  private async predictRetention(
    segments: ContentSegment[]
  ): Promise<RetentionPrediction[]> {
    const predictions: RetentionPrediction[] = [];

    for (const segment of segments) {
      // 特征提取
      const features = this.extractFeatures(segment);

      // 使用本地小模型预测
      const score = await this.retentionModel.predict(features);

      predictions.push({
        chapterNo: segment.chapterNo,
        retentionScore: score,
        predictedChurnRate: 1 - score,
        rating: this.scoreToStars(score),
        suggestions: this.getSuggestionsForScore(score),
      });
    }

    return predictions;
  }
}
```

### 13.3 情绪曲线数据结构

```typescript
interface EmotionCurve {
  chapterNo: number;
  anticipation: number;  // 0-1 期待值
  tension: number;      // 0-1 紧张感
  warmth: number;       // 0-1 温馨度
  sadness: number;     // 0-1 悲伤度
  curve: number[];      // 实时曲线数据点
}

interface ConflictDensity {
  chapterNo: number;
  conflictCount: number;
  intensitySum: number;
  densityPerThousandWords: number;
  conflicts: {
    type: 'physical' | 'verbal' | 'psychological' | 'situational';
    intensity: number;  // 1-5
    position: number;   // 章节内位置 0-1
  }[];
}

interface RhythmSuggestion {
  type: 'emotion' | 'conflict' | 'retention';
  priority: 'high' | 'medium' | 'low';
  targetChapterNo: number;
  description: string;
  action: 'add_conflict' | 'add_reveal' | 'adjust_pacing' | 'add_hook';
  estimatedImpact: string;
}
```

---

## 第十四部分：伏笔管理系统

### 14.1 伏笔管理流程

```
┌─────────────────────────────────────────────────────────────────┐
│                      伏笔管理流程                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  伏笔识别                                                │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                          │   │
│  │  规则识别                    AI识别                     │   │
│  │  ────────────────           ────────────────           │   │
│  │  • 神秘物品出现              • 语义异常检测               │   │
│  │  • 暗示性对话                • 悬念语句识别              │   │
│  │  • 未解之谜                  • 矛盾陈述检测               │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  伏笔跟踪                                                │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                          │   │
│  │  ┌─────────────────────────────────────────────────┐   │   │
│  │  │ 伏笔状态机                                        │   │   │
│  │  │                                                 │   │   │
│  │  │  埋设 ──▶ 待回收 ──▶ 已回收                     │   │   │
│  │  │    │           │                               │   │   │
│  │  │    │           │                               │   │   │
│  │  │    └───────────┴──────▶  过期/废弃              │   │   │
│  │  └─────────────────────────────────────────────────┘   │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  智能回收                                                │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                          │   │
│  │  • 适时提醒用户回收                                      │   │
│  │  • AI生成回收场景建议                                    │   │
│  │  • 自动检查一致性                                        │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 14.2 伏笔识别引擎

```typescript
class ForeshadowingDetector {
  private rulePatterns: RegExp[];
  private aiAnalyzer: SemanticAnalyzer;

  async detect(content: string, chapterNo: number): Promise<Foreshadowing[]> {
    const foreshadowings: Foreshadowing[] = [];

    // 1. 规则匹配
    const ruleMatches = this.ruleBasedDetection(content, chapterNo);
    foreshadowings.push(...ruleMatches);

    // 2. AI语义分析
    const aiMatches = await this.aiBasedDetection(content, chapterNo);
    foreshadowings.push(...aiMatches);

    // 3. 去重和合并
    return this.deduplicate(foreshadowings);
  }

  private ruleBasedDetection(
    content: string,
    chapterNo: number
  ): Foreshadowing[] {
    const results: Foreshadowing[] = [];

    // 神秘物品出现
    const itemPatterns = [
      /(戒指|玉佩|古书|卷轴|令牌|宝石)等神秘物品/g,
      /(散发|闪烁|隐隐|似乎)着.*光芒/g,
    ];

    // 暗示性对话
    const dialoguePatterns = [
      /"你(不|永远|迟早).*"/g,
      /".*的秘密.*"/g,
      /"总有一天.*"/g,
    ];

    // 未解之谜
    const mysteryPatterns = [
      /然而.*并未.*(说明|解释|提及)/g,
      /这个.*(谜|秘密|疑问).*(一直|始终|至今)/g,
    ];

    for (const pattern of [...itemPatterns, ...dialoguePatterns, ...mysteryPatterns]) {
      let match;
      while ((match = pattern.exec(content)) !== null) {
        results.push({
          id: this.generateId(),
          chapterNo,
          setupContent: match[0],
          type: this.classifyForeshadowing(match[0]),
          confidence: 0.9,
          status: 'pending',
          detectedAt: new Date(),
        });
      }
    }

    return results;
  }

  private async aiBasedDetection(
    content: string,
    chapterNo: number
  ): Promise<Foreshadowing[]> {
    const prompt = `
分析以下文本，识别可能的伏笔设置:

${content}

伏笔特征:
1. 神秘物品或力量的首次出现
2. 暗示性的对话或陈述
3. 人物的特殊反应或隐藏信息
4. 时间或空间的异常安排
5. 与后续情节矛盾的早期暗示

请以JSON格式输出识别的伏笔列表。
`;

    const response = await this.ai.generate(prompt);
    const parsed = JSON.parse(response);

    return parsed.foreshadowings.map((f: any) => ({
      ...f,
      chapterNo,
      confidence: 0.7,  // AI识别置信度稍低
      status: 'pending',
      detectedAt: new Date(),
    }));
  }
}
```

### 14.3 伏笔回收检测

```typescript
class ForeshadowingPayoffChecker {
  async checkPayoff(
    foreshadowing: Foreshadowing,
    subsequentContent: string[]
  ): Promise<PayoffCheckResult> {
    // 1. 关键词匹配
    const keywordMatch = this.keywordMatching(foreshadowing, subsequentContent);

    // 2. 语义相似度检测
    const semanticMatch = await this.semanticMatching(
      foreshadowing,
      subsequentContent
    );

    // 3. 综合判断
    const isPaidOff = keywordMatch.score > 0.6 || semanticMatch.score > 0.7;

    return {
      isPaidOff,
      matchType: keywordMatch.found ? 'keyword' : semanticMatch.found ? 'semantic' : null,
      matchedChapter: keywordMatch.chapterNo || semanticMatch.chapterNo,
      confidence: Math.max(keywordMatch.score, semanticMatch.score),
      suggestions: isPaidOff ? null : this.generatePayoffSuggestions(foreshadowing),
    };
  }

  async generatePayoffSuggestions(foreshadowing: Foreshadowing): Promise<string[]> {
    const prompt = `
伏笔 "${foreshadowing.setupContent}" (埋设于第${foreshadowing.chapterNo}章)
至今尚未回收，请给出3-5个回收建议:

要求:
1. 回收场景要符合故事逻辑
2. 要有一定的戏剧性
3. 可以是直接揭露或反转式揭露

以JSON数组格式输出建议列表。
`;

    const response = await this.ai.generate(prompt);
    return JSON.parse(response).suggestions;
  }
}
```

### 14.4 伏笔数据结构

```typescript
interface Foreshadowing {
  id: string;
  chapterNo: number;           // 埋设章节
  setupContent: string;      // 伏笔内容
  setupLocation: number;      // 章节内位置 0-1
  type: ForeshadowingType;
  status: ForeshadowingStatus;

  // 预期回收
  expectedChapterNo?: number;  // 用户设置的预期回收章节
  autoDetectedExpected?: number;  // AI自动推断的回收章节

  // 元数据
  confidence: number;        // 检测置信度 0-1
  detectedAt: Date;
  confirmedAt?: Date;          // 用户确认时间
  userEdited: boolean;        // 是否经过用户编辑

  // 回收信息
  payoffInfo?: {
    paidOffAt?: Date;
    paidOffChapterNo?: number;
    payoffMethod?: string;
  };
}

type ForeshadowingType =
  | 'item'        // 物品伏笔
  | 'identity'    // 身份伏笔
  | 'relationship' // 关系伏笔
  | 'ability'     // 能力伏笔
  | 'plot'        // 情节伏笔
  | 'world'       // 世界观伏笔;

type ForeshadowingStatus =
  | 'pending'     // 待确认
  | 'confirmed'   // 已确认(用户确认是伏笔)
  | 'paid_off'    // 已回收
  | 'expired'     // 过期未回收
  | 'cancelled';  // 用户取消标记
```

---

## 第十五部分：版本分支系统

### 15.1 分支创作流程

```
┌─────────────────────────────────────────────────────────────────┐
│                      分支创作流程                                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  创建分支 ────────────────────────────────────────────────────  │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  用户选择"创建分支"                                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  选择基础版本和分支策略                                    │   │
│  │                                                          │   │
│  │  • 复制完整大纲，独立修改                                 │   │
│  │  • 仅复制结构，内容独立生成                               │   │
│  │  • 从特定节点分叉                                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  创建分支副本                                            │   │
│  │                                                          │   │
│  │  主分支 ──●────────────────●──▶ (继续主线路)            │   │
│  │              │                                             │   │
│  │              └──🌿 虐文分支 ──●──▶ (独立修改)           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  分支合并 ────────────────────────────────────────────────────  │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  选择要合并的分支和目标位置                               │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  冲突检测与解决                                          │   │
│  │                                                          │   │
│  │  • 识别冲突点                                            │   │
│  │  • 用户选择保留版本                                      │   │
│  │  • AI辅助决策                                           │   │
│  └─────────────────────────────────────────────────────────┘   │
│                            │                                   │
│                            ▼                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  合并完成                                                │   │
│  │                                                          │   │
│  │  主分支 ──●──●──●──●──●──▶ (合并后)                   │   │
│  │              │                                             │   │
│  │              └──🌿 ──●──▶ (合并)                        │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 15.2 分支管理实现

```typescript
class BranchManager {
  private storage: BranchStorage;
  private diffEngine: DiffEngine;

  async createBranch(params: CreateBranchParams): Promise<Branch> {
    // 1. 获取基础版本
    const baseVersion = await this.getVersion(params.baseVersionId);

    // 2. 创建分支副本
    const branch: Branch = {
      id: this.generateId(),
      name: params.name,
      description: params.description,
      baseVersionId: params.baseVersionId,
      createdAt: new Date(),
      status: 'active',
      rootCommitId: baseVersion.commitId,
    };

    // 3. 复制数据到新分支
    await this.copyToBranch(branch, baseVersion);

    // 4. 保存分支信息
    await this.storage.saveBranch(branch);

    return branch;
  }

  async merge(params: MergeParams): Promise<MergeResult> {
    const sourceBranch = await this.storage.getBranch(params.sourceBranchId);
    const targetBranch = await this.storage.getBranch(params.targetBranchId);

    // 1. 找出共同祖先
    const commonAncestor = this.findCommonAncestor(sourceBranch, targetBranch);

    // 2. 计算差异
    const sourceChanges = await this.diffEngine.computeDiff(
      commonAncestor,
      sourceBranch.headCommitId
    );
    const targetChanges = await this.diffEngine.computeDiff(
      commonAncestor,
      targetBranch.headCommitId
    );

    // 3. 检测冲突
    const conflicts = this.detectConflicts(sourceChanges, targetChanges);

    if (conflicts.length > 0) {
      // 返回冲突供用户解决
      return {
        hasConflicts: true,
        conflicts,
        requiresManualResolution: true,
      };
    }

    // 4. 自动合并
    const mergedContent = this.autoMerge(sourceChanges, targetChanges);

    // 5. 创建合并提交
    const mergeCommit = await this.createMergeCommit({
      parent1: targetBranch.headCommitId,
      parent2: sourceBranch.headCommitId,
      content: mergedContent,
      message: `Merge branch '${sourceBranch.name}' into ${targetBranch.name}`,
    });

    return {
      hasConflicts: false,
      mergeCommit,
      requiresManualResolution: false,
    };
  }

  private detectConflicts(
    sourceChanges: Change[],
    targetChanges: Change[]
  ): Conflict[] {
    const conflicts: Conflict[] = [];

    // 按文件/节点分组
    const sourceByNode = this.groupByNode(sourceChanges);
    const targetByNode = this.groupByNode(targetChanges);

    for (const nodeId of new Set([
      ...sourceByNode.keys(),
      ...targetByNode.keys(),
    ])) {
      const sourceChange = sourceByNode.get(nodeId);
      const targetChange = targetByNode.get(nodeId);

      if (sourceChange && targetChange) {
        if (sourceChange.hash !== targetChange.hash) {
          conflicts.push({
            nodeId,
            nodeType: sourceChange.nodeType,
            sourceValue: sourceChange.content,
            targetValue: targetChange.content,
          });
        }
      }
    }

    return conflicts;
  }
}
```

### 15.3 版本数据结构

```typescript
interface Branch {
  id: string;
  name: string;
  description?: string;
  baseVersionId: string;      // 分支基础版本
  rootCommitId: string;      // 分支起始提交
  headCommitId: string;      // 当前最新提交
  parentBranchId?: string;   // 父分支(如果有)
  status: 'active' | 'merged' | 'archived';
  createdAt: Date;
  mergedAt?: Date;
}

interface Commit {
  id: string;
  branchId: string;
  parentIds: string[];        // 可能多个父提交(合并时)
  nodeType: NodeType;         // 影响的节点类型
  nodeId: string;            // 影响的节点ID
  changeType: 'create' | 'update' | 'delete';
  contentBefore?: any;
  contentAfter: any;
  message: string;
  author: 'user' | 'ai';
  aiConversationId?: string;  // 如果是AI修改,关联对话
  createdAt: Date;
}

interface VersionSnapshot {
  id: string;
  nodeType: NodeType;
  nodeId: string;
  content: any;
  commitId: string;
  createdAt: Date;
}

interface Diff {
  nodeId: string;
  nodeType: NodeType;
  changeType: 'added' | 'modified' | 'deleted';
  contentBefore?: any;
  contentAfter: any;
  hashBefore: string;
  hashAfter: string;
}

interface Conflict {
  nodeId: string;
  nodeType: NodeType;
  sourceValue: any;
  targetValue: any;
  resolution?: 'use_source' | 'use_target' | 'manual';
  resolvedValue?: any;
}
```

---

## 第十六部分：多租户数据隔离体系

### 16.1 隔离架构概述

```
三层隔离架构:
┌─────────────────────────────────────────────────────────────────┐
│                        用户层 (User)                              │
│  • 用户账户体系                                                  │
│  • 认证与授权                                                   │
│  • 积分/配额管理                                                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      作品层 (Novel/Project)                      │
│  • 作品拥有权隔离                                               │
│  • 作品内数据隔离 (角色、素材、大纲等)                          │
│  • 协作共享机制                                                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      数据层 (Data Layer)                         │
│  • 关系型数据隔离 (MySQL)                                       │
│  • 向量数据隔离 (Qdrant Collection)                             │
│  • 文件存储隔离 (OSS/本地)                                      │
└─────────────────────────────────────────────────────────────────┘
```

### 16.2 用户数据结构

```typescript
interface User {
  id: string;
  username: string;
  passwordHash: string;
  email?: string;
  phone?: string;
  role: 'USER' | 'ADMIN';

  // 隔离字段
  tenantId: string;           // 租户ID

  // 配额管理
  creditBalance: number;       // 积分余额
  monthlyQuota: number;        // 月度AI使用配额
  usedQuota: number;          // 已使用配额

  // 会员信息
  vipLevel: 'FREE' | 'VIP' | 'SVIP';
  vipExpireTime?: Date;

  // 状态
  status: 'ACTIVE' | 'BANNED' | 'DELETED';

  createdAt: Date;
  updatedAt: Date;
}
```

### 16.3 作品数据结构

```typescript
interface Novel {
  id: string;
  title: string;
  description?: string;

  // 核心隔离字段
  ownerId: string;            // 拥有者 userId
  tenantId: string;           // 租户ID

  // 作品设置
  genre: NovelGenre;
  status: 'DRAFT' | 'WRITING' | 'COMPLETED' | 'ARCHIVED';

  // 协作管理
  isCollaborative: boolean;
  collaboratorIds: string[];   // 协作用户ID列表

  // 统计
  wordCount: number;
  chapterCount: number;

  createdAt: Date;
  updatedAt: Date;
}

// 协作者结构
interface NovelCollaborator {
  id: string;
  novelId: string;
  userId: string;
  role: 'EDITOR' | 'READER';
  grantedAt: Date;
  grantedBy: string;          // 授权人
}
```

### 16.4 MySQL 数据隔离 (行级隔离)

```
隔离实现: 所有数据表增加 tenant_id 字段

查询拦截器 (MyBatis Plus TenantInterceptor):
1. 从 SecurityContext 获取当前用户 tenantId
2. SQL自动追加: AND tenant_id = #{tenantId}
3. INSERT时自动填充 tenant_id
4. UPDATE/DELETE 仅影响当前租户数据

数据隔离示意图:

Tenant A (用户A)              Tenant B (用户B)
─────────────────────────────────────────────────────

|novels               |      |novels               |
|id | title | owner  |      |id | title | owner  |
|1  | 作品A | A     |      |5  | 作品X | B     |
|2  | 作品B | A     |      |6  | 作品Y | B     |

用户A查询: WHERE tenant_id = A  → 返回 作品A, 作品B
用户B查询: WHERE tenant_id = B  → 返回 作品X, 作品Y
用户A尝试访问: WHERE id = 5 AND tenant_id = A  → 无结果
```

### 16.5 向量数据库隔离策略

```
Collection池设计 - 按用户/作品分配独立命名空间

预设Collection:
┌─────────────────────────────────────────────────────────────────┐
│  Collection Name    │ 隔离类型    │  说明                        │
├─────────────────────────────────────────────────────────────────┤
│  user_knowledge    │ 用户级      │ 每个用户独立命名空间           │
│  character_lib      │ 作品级      │ 每个作品独立命名空间           │
│  plot_outline      │ 作品级      │ 每个作品独立命名空间           │
│  world_setting     │ 用户级      │ 每个用户独立命名空间           │
│  narrative         │ 作品级      │ 每个作品独立命名空间           │
│  style_fingerprint │ 用户级      │ 每个用户独立命名空间           │
└─────────────────────────────────────────────────────────────────┘

命名空间格式:
{collection}_{tenantId}_{entityId}

示例:
user_knowledge_10001_          → 用户10001的知识库
character_lib_10001_567         → 作品567的角色库
plot_outline_10001_567          → 作品567的情节库
narrative_10001_567_chapter_10  → 作品567第10章的叙事向量
```

### 16.6 向量 Entry 隔离字段

```typescript
interface VectorEntry {
  id: string;
  chunk: string;
  denseVector: number[];

  // 隔离字段
  tenantId: string;           // 租户ID (必填)
  userId?: string;           // 用户ID
  novelId?: string;          // 作品ID
  chapterId?: string;        // 章节ID

  // 元数据
  type: EntryType;
  status: 'active' | 'archived';
  importanceWeight?: number;

  // 时间戳
  narrativeTimestamp?: Date;
  createdAt: Date;
}
```

### 16.7 隔离检索流程

```
向量检索隔离:

用户发起检索请求
       │
       ▼
┌─────────────────┐
│ 解析用户身份     │
│ 获取 tenantId   │
│ 获取 userId     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 构建隔离查询     │
│ filter:         │
│ tenantId = ?   │
│ AND userId = ? │
│ AND novelId = ?│ (可选)
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ 执行Qdrant查询  │
│ 返回结果自动    │
│ 过滤完成        │
└────────┬────────┘
         │
         ▼
    返回用户结果
```

### 16.8 AI配额管理

```typescript
interface UserQuota {
  id: string;
  userId: string;
  tenantId: string;

  // 配额设置
  monthlyQuota: number;       // 月度总配额
  usedQuota: number;        // 已使用配额
  creditBalance: number;     // 积分余额

  // 限制
  dailyGenerationLimit: number;
  maxConcurrentRequests: number;

  // 作品级限制
  maxNovelsPerUser: number;
  maxChaptersPerNovel: number;

  resetAt: Date;
  updatedAt: Date;
}
```

### 16.9 配额检查流程

```
AI请求配额检查:

用户发起AI生成请求
       │
       ▼
┌─────────────────────┐
│ 1. 检查月度配额     │
│    usedQuota < monthlyQuota │
└────────┬────────────┘
         │
    ┌────┴────┐
   失败        成功
    │           │
    ▼           ▼
返回配额不足   ┌─────────────────────┐
              │ 2. 检查积分余额     │
              │    creditBalance > 0 │
              └────────┬────────────┘
                       │
                  ┌────┴────┐
                 失败        成功
                  │           │
                  ▼           ▼
              返回积分不足   ┌─────────────────────┐
                           │ 3. 检查并发限制     │
                           │    当前并发 < max   │
                           └────────┬────────────┘
                                    │
                               ┌────┴────┐
                              失败        成功
                               │           │
                               ▼           ▼
                         返回繁忙    执行AI生成
                                        │
                                        ▼
                                 扣减配额/积分
```

### 16.10 运营端数据访问

```
管理员权限等级:

超级管理员 (super_admin)
├── 可访问所有租户数据
├── 可管理所有用户
└── 可配置系统级参数

运营管理员 (admin)
├── 不可访问用户私有数据
├── 仅可访问聚合统计数据
└── 可执行平台级运营操作

运营端数据脱敏 (UserListDTO):
- maskedUsername: 张三 → zhang***
- maskedPhone: 138****1234
- maskedEmail: z***@.com
- 仅显示统计数量 (novelCount, wordCount)
```

### 16.11 API 权限注解

```typescript
// 方法级权限注解
@RequireOwner(resourceType: 'Novel', resourceIdParam: 'novelId')
async updateNovel(novelId: string, dto: NovelUpdateDTO);

@RequireTenant()
async createNovel(dto: NovelCreateDTO);

@RequireQuota(quotaType: QuotaType.AI_GENERATION)
async generate(request: GenerationRequest);

// 协作权限验证
@RequireCollaborator(resourceType: 'Novel', level: CollaboratorLevel.EDITOR)
async updateChapter(novelId: string, dto: ChapterUpdateDTO);
```

### 16.12 隔离效果总结

| 隔离维度 | 隔离方式 | 实现位置 |
|----------|----------|----------|
| 用户数据 | tenant_id + user_id | MySQL 行级隔离 |
| 作品数据 | owner_id + collaborator_ids | MySQL + 业务层 |
| 向量数据 | collection命名空间 + filter | Qdrant 命名空间 |
| AI配额 | 租户配额 + 用户配额 | Redis + MySQL |
| 文件存储 | OSS bucket前缀 / 本地目录隔离 | 存储层 |
| 运营数据 | 聚合统计 + 数据脱敏 | API层 |

---

## 第十二部分：性能与优化

### 12.1 生成性能指标

| 操作 | 目标耗时 | 优化策略 |
|------|----------|----------|
| 大纲生成 | < 10s | 本地GPU加速 |
| 卷纲生成 | < 5s/卷 | 并行生成 |
| 章节细纲 | < 3s/章 | 模板缓存 |
| 正文扩写 | < 30s/千字 | 分段生成 |
| 风格分析 | < 2s | 小模型+缓存 |
| RAG检索 | < 500ms | 向量索引优化 |

### 12.2 缓存策略

```
缓存层级:
1. L1: 提示词模板 (内存, TTL=永久)
2. L2: 风格指纹 (IndexedDB, TTL=7天)
3. L3: 常用知识向量 (内存, LRU淘汰)

缓存键:
- 用户ID + 小说ID + 节点类型 + 节点版本
```

---

## 第十七部分：特摄同人创作增强模块

### 17.1 设计目标

特摄同人创作的独特性在于：在已有、盘根错节的官方设定基础上进行二次创作。星夜引擎需要理解：

- 崇皇时王与Decade激情态的差异
- 火花棱镜与黑暗火花棱镜的转化条件
- 数十年的历史、多部作品、平行宇宙、形态变换、道具联动

### 17.2 世界观锚定·特摄增强

#### 17.2.1 官方设定导入

```
官方设定解析器:
┌─────────────────────────────────────────────────────────────────┐
│  官方设定导入流程                                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. 导入结构化文档 (JSON/Markdown/表格)                        │
│     • 特摄维基JSON导出                                          │
│     • 自制定设定表                                              │
│                                                                  │
│  2. 自动解析作品系谱                                            │
│     • 《假面骑士空我》                                          │
│       → 登场形态: 全能、青龙、天马、泰坦、究极                  │
│       → 变身道具: 亚古鲁腰带                                    │
│       → 必杀技: 全能踢、泰坦剑                                  │
│       → 关联作品: Decade联动回、时王空我篇                       │
│                                                                  │
│  3. 设定条目元数据                                              │
│     • 作品来源标签                                               │
│     • 正史状态: 正典/剧场版/番外/小说/舞台剧                    │
│     • 谱系向量建立                                              │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

#### 17.2.2 多重宇宙标签

```typescript
// 世界线配置
interface Worldline {
  id: string;                    // 世界线ID
  name: string;                  // 显示名称
  source: string;                // 所属作品

  // 跨世界线规则
  crossWorldRules: {
    canImportCharacters: boolean;
    canImportItems: boolean;
    conflictDetection: boolean;  // 是否检测规则冲突
  };

  // 融合规则 (IF线特有)
  fusionRules?: {
    allowedWorldlines: string[];  // 允许融合的世界线
    conflictResolution: 'first' | 'merge' | 'reject';
  };

  status: 'active' | 'archived' | 'if_branch';
}

// 设定条目的世界线属性
interface SettingEntry {
  // ... 基础字段
  applicableWorldlines: string[];  // 适用世界线，空=所有
  isCrossWorldValid: boolean;     // 是否跨世界有效
  conflictWorldlines: string[];   // 冲突的世界线
}
```

#### 17.2.3 特摄规则模板库

```typescript
// 预置特摄世界观构建块
const TokusatsuTemplates = {
  // 能量来源
  energySources: [
    { id: 'photon_blood', name: '光子血液', color: '#FFD700' },
    { id: 'magic_energy', name: '魔力', color: '#9B59B6' },
    { id: 'rider_power', name: '骑士槽', color: '#E74C3C' },
    { id: 'light_force', name: '光之力', color: '#3498DB' },
  ],

  // 变身限制
  transformationLimits: [
    { type: 'time_limit', name: '时间限制', unit: '秒/分钟' },
    { type: 'use_count', name: '使用次数', unit: '次' },
    { type: 'side_effect', name: '副作用', effects: ['昏迷', '能量耗尽', '形态退化'] },
  ],

  // 战斗规则
  battleModes: [
    { id: 'giant', name: '巨大化战斗' },
    { id: 'human_scale', name: '等身战' },
    { id: 'both', name: '双重战斗规则' },
  ],

  // 唯心爆发条件
  mentalBurstTriggers: [
    { condition: '不再迷茫', intensity: 'high' },
    { condition: '同伴羁绊', intensity: 'medium' },
    { condition: '愤怒突破', intensity: 'high' },
    { condition: '牺牲觉悟', intensity: 'extreme' },
  ],
};
```

### 17.3 角色孵化器·特摄增强

#### 17.3.1 形态演化树

```typescript
// 角色+形态+道具 复合体
interface TokusatsuCharacter {
  id: string;
  baseCharacter: Character;           // 基础角色信息

  // 形态演化树
  formTree: FormTree;

  // 持有道具
  ownedDevices: Device[];

  // 语言指纹
  languageFingerprint: {
    transformationAnnounce: string[];  // 变身音效/口癖
    catchphrases: string[];           // 口头禅
    finisherAnnounce: string[];       // 必杀技报幕
  };
}

// 形态树结构
interface FormTree {
  rootFormId: string;                // 初始形态
  forms: Map<string, Form>;
}

interface Form {
  id: string;
  name: string;                      // 形态名称
  parentFormId?: string;             // 上一级形态
  childFormIds: string[];            // 可进化形态

  // 进化条件
  evolutionConditions: {
    emotionalTrigger?: string;        // 情绪触发
    deviceRequired?: string;          // 所需道具
    externalCharge?: boolean;         // 外部充能
    battleCondition?: string;        // 战斗条件
  };

  // 退化条件
  degenerationConditions: {
    energyDepletion: boolean;
    transformationTimeout: boolean;
    forcedByEnemy: boolean;
  };

  // 形态能力向量
  abilityVector: {
    power: number;                   // 力量
    speed: number;                   // 速度
    specialAbilities: string[];       // 特殊能力
    weaknesses: string[];             // 弱点
  };

  // 关联敌人克制
  enemyWeaknesses: Map<string, number>;  // 对特定敌人的克制系数
}

// 变身道具
interface Device {
  id: string;
  name: string;
  type: 'belt' | 'buckel' | 'eyecon' | ' bottle' | 'core_idol' | string;

  // 状态
  status: 'owned' | 'destroyed' | 'evolved' | 'lost';

  // 可进化为
  evolvedInto?: string;
  evolutionCondition?: string;
}
```

#### 17.3.2 变身合规性检查

```typescript
// 变身合规性验证
class TransformationValidator {
  validate(
    characterId: string,
    targetFormId: string,
    context: SceneContext
  ): ValidationResult {
    const character = this.getCharacter(characterId);
    const targetForm = character.formTree.forms.get(targetFormId);

    // 1. 检查道具持有
    if (targetForm.evolutionConditions.deviceRequired) {
      const hasDevice = character.ownedDevices.some(
        d => d.id === targetForm.evolutionConditions.deviceRequired
      );
      if (!hasDevice) {
        return {
          valid: false,
          error: `缺少必要道具: ${targetForm.evolutionConditions.deviceRequired}`,
          type: 'missing_device',
        };
      }
    }

    // 2. 检查能量状态
    if (context.currentEnergy < targetForm.minEnergyRequired) {
      return {
        valid: false,
        error: `能量不足: 需要${targetForm.minEnergyRequired}，当前${context.currentEnergy}`,
        type: 'insufficient_energy',
      };
    }

    // 3. 检查情绪条件
    if (targetForm.evolutionConditions.emotionalTrigger) {
      const emotionMatch = this.checkEmotionalCondition(
        context.currentEmotion,
        targetForm.evolutionConditions.emotionalTrigger
      );
      if (!emotionMatch) {
        return {
          valid: false,
          error: `情绪条件未满足: 需要"${targetForm.evolutionConditions.emotionalTrigger}"`,
          type: 'emotional_mismatch',
          suggestion: '可标记为"唯心突破"事件',
        };
      }
    }

    return { valid: true };
  }
}
```

### 17.4 故事骨架·特摄增强

#### 17.4.1 单元剧双轨大纲

```
三栏联动视图:
┌─────────────────────────────────────────────────────────────────┐
│  话数    │  怪人/事件      │  主线推进                        │
├─────────────────────────────────────────────────────────────────┤
│  第1话   │  亚茲德袭来      │  庄吾获得逢魔zio表                │
│  第2话   │  异类空我       │  沃兹预言显示                    │
│  第3话   │  异类Faiz       │  月津登场                         │
│  ...     │  ...            │  ...                              │
└─────────────────────────────────────────────────────────────────┘

单元回卡片结构:
interface EpisodeCard {
  episodeNo: number;

  // 单元剧信息
  monsterEvent: {
    mainMonster: string;           // 主要怪人
    minions?: string[];             // 杂兵
    episodeThreat: 'low' | 'medium' | 'high';
  };

  // 受害者/触发事件
  victimEvent: {
    type: 'civilian' | 'ally' | 'self';
    description: string;
  };

  // 获得
  gains: {
    newForm?: string;              // 新形态
    newDevice?: string;            // 新道具
    plotAdvance?: string;           // 剧情推进点
  };

  // 主线关联
  mainPlotConnection?: {
    foreshadowingId: string;
    advanceAmount: number;         // 推进程度 0-1
  };

  // 战斗地点
  battleLocation: string;
}
```

#### 17.4.2 敌役模板库

```typescript
// 敌役/怪人模板
interface VillainTemplate {
  id: string;
  name: string;
  category: 'monster' | 'rider' | 'kaijin' | 'ultraman' | 'boss';

  // 组织归属
  organization?: {
    id: string;
    name: string;                  // 古朗基/俄尔以诺/异虫/修卡等
  };

  // 能力
  abilities: {
    combatPower: number;
    specialAttacks: string[];
    weaknesses: string[];          // 弱点
    bodyParts?: string[];          // 身体部位 (可破坏)
  };

  // 状态
  statusHistory: {
    status: 'alive' | 'dead' | 'revived' | 'cloned';
    deathChapter?: number;          // 死亡话数
    revivalCondition?: string;     // 复活条件
  }[];

  // 与主角克制关系
  rivalries: Map<string, {
    type: 'weak_to' | 'strong_to' | 'equal';
    specificForm?: string;
  }>;
}
```

#### 17.4.3 主线伏笔提示器

```typescript
// 主线伏笔提示
class MainPlotAdvisor {
  analyze(episodeCards: EpisodeCard[]): Advisory[] {
    const advisories: Advisory[] = [];

    // 检测连续单元回
    const recentEpisodes = episodeCards.slice(-5);
    const mainPlotAdvances = recentEpisodes.filter(
      e => e.mainPlotConnection && e.mainPlotConnection.advanceAmount > 0
    );

    if (mainPlotAdvances.length < 2) {
      advisories.push({
        type: 'warning',
        priority: 'high',
        message: `已连续${5 - mainPlotAdvances.length}话为纯单元回，建议本话结尾引入主线设定`,
        suggestion: '可在本话结尾添加预示性对话或神秘事件',
      });
    }

    // 检测伏笔过期
    const pendingForeshadowings = this.getPendingForeshadowings();
    for (const fs of pendingForeshadowings) {
      const chaptersSinceSetup = episodeCards.length - fs.chapterNo;
      if (chaptersSinceSetup > 10) {
        advisories.push({
          type: 'warning',
          priority: 'medium',
          message: `伏笔"${fs.description}"已埋设${chaptersSinceSetup}话，尚未回收`,
          suggestion: '建议尽快安排回收或确认是否废弃',
        });
      }
    }

    return advisories;
  }
}
```

### 17.5 章节生成·特摄增强

#### 17.5.1 变身感知召回

```typescript
// 上下文编织的特摄增强
class TokusatsuContextWeaver extends ContextWeaver {
  async weave(intent: WritingIntent): Promise<CPromptContext> {
    const baseContext = await super.weave(intent);

    // 检测变身/形态切换意图
    if (this.isTransformationAction(intent)) {
      const transformationContext = await this.buildTransformationContext(intent);
      baseContext.priorities.unshift(...transformationContext);
    }

    // 检测战斗意图
    if (this.isBattleAction(intent)) {
      const battleContext = await this.buildBattleContext(intent);
      baseContext.constraints.push(...battleContext);
    }

    // 检测必杀技意图
    if (this.isFinisherAction(intent)) {
      const finisherContext = await this.buildFinisherContext(intent);
      baseContext.constraints.push(...finisherContext);
    }

    return baseContext;
  }

  private async buildTransformationContext(
    intent: WritingIntent
  ): Promise<ContextPart[]> {
    const parts: ContextPart[] = [];
    const characterId = intent.characterId;
    const targetFormId = intent.targetForm;

    // 检索当前角色可用形态列表
    const availableForms = await this.vectorStore.search(
      `form_available_${characterId}`,
      { topK: 10 }
    );
    parts.push({
      type: 'form_list',
      content: availableForms,
      priority: 'high',
    });

    // 检索形态切换限制
    const formLimits = await this.vectorStore.search(
      `form_limits_${targetFormId}`,
      { topK: 5 }
    );
    parts.push({
      type: 'form_limits',
      content: formLimits,
      priority: 'high',
    });

    // 检索当前敌人弱点
    if (intent.currentEnemy) {
      const enemyWeaknesses = await this.vectorStore.search(
        `enemy_weakness_${intent.currentEnemy}`,
        { topK: 3 }
      );
      parts.push({
        type: 'enemy_weakness',
        content: enemyWeaknesses,
        priority: 'medium',
      });
    }

    return parts;
  }
}
```

#### 17.5.2 特摄一致性自检

```typescript
// 特摄规则一致性检查
class TokusatsuConsistencyChecker extends ConsistencyChecker {
  async check(chapterContent: GeneratedChapter): Promise<ConsistencyReport> {
    const issues: ConsistencyIssue[] = [];

    // 1. 形态切换合规性检查
    const transformationIssues = await this.checkTransformationCompliance(
      chapterContent
    );
    issues.push(...transformationIssues);

    // 2. 唯心爆发合理性检查
    const mentalBurstIssues = this.checkMentalBurstReasonableness(
      chapterContent
    );
    issues.push(...mentalBurstIssues);

    // 3. 战斗逻辑检查
    const battleLogicIssues = await this.checkBattleLogic(chapterContent);
    issues.push(...battleLogicIssues);

    // 4. 音效/台词缺失检测
    const missingAnnounceIssues = this.checkMissingAnnouncements(
      chapterContent
    );
    issues.push(...missingAnnounceIssues);

    // 5. 敌役状态检查
    const villainStatusIssues = await this.checkVillainStatus(chapterContent);
    issues.push(...villainStatusIssues);

    return {
      totalIssues: issues.length,
      issuesBySeverity: this.groupBySeverity(issues),
      issuesByType: this.groupByType(issues),
      suggestions: issues.map(i => this.generateSuggestion(i)),
    };
  }

  private async checkTransformationCompliance(
    chapter: GeneratedChapter
  ): Promise<ConsistencyIssue[]> {
    const issues: ConsistencyIssue[] = [];
    const transformations = this.extractTransformations(chapter.content);

    for (const trans of transformations) {
      const validation = this.validator.validate(
        trans.characterId,
        trans.targetFormId,
        trans.context
      );

      if (!validation.valid) {
        if (validation.type === 'missing_device') {
          issues.push({
            type: 'transformation',
            severity: 'high',
            location: trans.location,
            description: validation.error,
            suggestion: `确认道具"${validation.requiredDevice}"已正确获得，或标记为"临时借用"`,
            autoFixAvailable: false,
          });
        } else if (validation.type === 'emotional_mismatch') {
          // 唯心突破处理
          issues.push({
            type: 'mental_burst_potential',
            severity: 'warning',
            location: trans.location,
            description: `形态切换缺少情绪条件: ${validation.error}`,
            suggestion: validation.suggestion,
            autoFixAvailable: true,
            autoFixAction: 'mark_as_mental_burst',
          });
        }
      }
    }

    return issues;
  }
}
```

### 17.6 长程记忆巩固·特摄增强

#### 17.6.1 跨作品伏笔链接

```typescript
// 跨世界线伏笔管理
class CrossWorldForeshadowingManager {
  // 记录跨世界伏笔
  async recordCrossWorldForeshadowing(
    foreshadowing: Foreshadowing,
    sourceWorldline: string
  ): Promise<void> {
    await this.vectorStore.insert({
      ...foreshadowing,
      metadata: {
        ...foreshadowing.metadata,
        worldlineId: sourceWorldline,
        isCrossWorld: true,
      },
    });
  }

  // 跨世界线伏笔检索
  async retrieveCrossWorldForeshadowings(
    currentWorldline: string,
    context: SceneContext
  ): Promise<Foreshadowing[]> {
    // 宽松阈值 + 加权策略
    const results = await this.vectorStore.search(context.semanticVector, {
      filter: {
        worldlineId: { $ne: currentWorldline },
        status: 'pending',
      },
      similarityThreshold: 0.65,  // 放宽阈值
      topK: 5,
    });

    // 高相关性结果优先
    return results
      .map(r => ({
        ...r,
        weight: r.similarity * 1.2,  // 跨世界加权
      }))
      .sort((a, b) => b.weight - a.weight);
  }
}
```

#### 17.6.2 道具与形态遗产追踪

```typescript
// 章节完成后的遗产更新
class HeritageTracker {
  async updateHeritage(chapter: GeneratedChapter): Promise<HeritageUpdate> {
    const updates: HeritageUpdate = {
      deviceChanges: [],
      formUnlocks: [],
      villainStatusChanges: [],
    };

    // 1. 道具变化
    for (const event of chapter.itemEvents) {
      if (event.type === 'obtained') {
        updates.deviceChanges.push({
          deviceId: event.deviceId,
          change: 'obtained',
          chapterNo: chapter.chapterNo,
        });
      } else if (event.type === 'destroyed') {
        updates.deviceChanges.push({
          deviceId: event.deviceId,
          change: 'destroyed',
          chapterNo: chapter.chapterNo,
          isMajorEvent: true,  // 标记为重大事件
        });
      }
    }

    // 2. 形态解锁
    for (const form of chapter.newForms) {
      updates.formUnlocks.push({
        characterId: form.characterId,
        formId: form.formId,
        unlockCondition: form.condition,
        chapterNo: chapter.chapterNo,
      });
    }

    // 3. 敌役状态
    for (const status of chapter.villainStatusChanges) {
      updates.villainStatusChanges.push(status);
      if (status.newStatus === 'dead') {
        // 更新组织势力
        await this.updateOrganizationStrength(status);
      }
    }

    return updates;
  }
}
```

### 17.7 整体收束·特摄增强

#### 17.7.1 形态进化时间轴

```typescript
// 形态进化时间轴
interface FormEvolutionTimeline {
  characterId: string;
  characterName: string;
  evolutionPath: {
    formId: string;
    formName: string;
    unlockChapterNo: number;
    unlockCondition: string;
    isJumpUnlocked: boolean;  // 是否跳跃觉醒 (需解释)
    issues: string[];          // 潜在问题
  }[];
  unresolvedJumps: number;    // 未解释的跳跃数
}
```

#### 17.7.2 多元宇宙穿越日志

```typescript
// 世界线穿越记录
interface WorldlineTraversalLog {
  storyId: string;

  traversals: {
    fromWorldline: string;
    toWorldline: string;
    chapterNo: number;
    reason: string;
    charactersBrought: string[];     // 带去的角色
    itemsBrought: string[];          // 带去的道具
    itemsLost: string[];             // 丢失的道具
    plotConsequences: string[];
  }[];

  // 一致性检查
  inconsistencies: {
    type: 'item_appearance' | 'character_state' | 'rule_conflict';
    description: string;
    location: string;
  }[];
}
```

#### 17.7.3 致敬回一致性验证

```typescript
// 全骑士/全奥特曼集结段检验
class AnniversaryConsistencyValidator {
  async validate(
    assemblyScene: AssemblyScene
  ): Promise<ValidationResult> {
    const issues: ConsistencyIssue[] = [];

    for (const entity of assemblyScene.entities) {
      // 检查该角色是否已退场
      const finalStatus = await this.getCharacterFinalStatus(entity.id);

      if (finalStatus.status === 'deceased' && !entity.hasExplanation) {
        issues.push({
          type: 'character_state',
          severity: 'high',
          description: `${entity.name}已退场，不能以原状态直接登场`,
          suggestion: '需要给出合理解释: 时光倒流/克隆/平行世界版本等',
        });
      }

      // 检查形态是否匹配当前时间线
      if (entity.formId !== finalStatus.lastForm) {
        issues.push({
          type: 'form_mismatch',
          severity: 'medium',
          description: `${entity.name}的形态与正史不符`,
          suggestion: `当前应为${finalStatus.lastForm}而非${entity.formId}`,
        });
      }
    }

    return {
      valid: issues.length === 0,
      issues,
    };
  }
}
```

### 17.8 特摄增强典型场景

```
场景: 作者小草正在写《假面骑士ZiO外传：逢魔之日》第14话

1. 写作意图输入:
   "庄吾在AR空我世界获取究极形态力量，对抗异类空我"

2. 上下文编织检索:
   • 空我世界设定: "究极形态会带来黑暗，与'不想战斗'的心矛盾"
   • 庄吾当前状态: "尚未坚定王之觉悟"
   • 检索结果警告:
     "庄吾此刻未与任何古朗基进行仪式性战斗，
      直接获取究极之力将破坏AR空我世界规则"

3. 引擎建议:
   • 建议插入"与自己的黑暗面战斗"剧情
   • 或标记为"时空扭曲特例"

4. 小草选择增加内心戏后:
   • 引擎自动标记该战斗为"时空扭曲特例"
   • 更新庄吾角色状态: "短暂触碰究极之暗，留下未愈伤痕"

5. 后续章节中:
   • 每当庄吾情绪波动，引擎提示"未愈伤痕可能导致黑暗人格浮现"
   • 伏笔贯穿始终
```

---

### 17.9 数据结构扩展汇总

| 原有结构 | 特摄扩展 | 新增字段 |
|----------|----------|----------|
| Character | TokusatsuCharacter | formTree, ownedDevices, languageFingerprint |
| Form | Form | evolutionConditions, abilityVector, enemyWeaknesses |
| Device | Device | status, evolvedInto |
| Novel | TokusatsuNovel | worldlineId, crossWorldSettings |
| Foreshadowing | CrossWorldForeshadowing | worldlineId, isCrossWorld |
| ConsistencyReport | TokusatsuConsistencyReport | transformationIssues, battleLogicIssues |

---

## 第十八部分：同人创作增强模块

### 18.1 模块架构概述

同人创作辅助引擎针对已有IP的二次创作场景，提供以下核心增强功能：

```
┌─────────────────────────────────────────────────────────────────┐
│              同人创作辅助引擎 - 增强模块                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │ 人物关系管理     │  │ 角色状态监控     │  │ 宇宙设定校验     │ │
│  │ 系统             │  │ 模块             │  │ 器               │ │
│  │                 │  │                 │  │                 │ │
│  │ • 互动历史      │  │ • 生命状态      │  │ • 相遇规则引擎  │ │
│  │ • 情感亲密度    │  │ • 健康状况      │  │ • 时空背景校验  │ │
│  │ • 关系指标      │  │ • 情绪波动      │  │ • 能力体系兼容  │ │
│  └────────┬────────┘  └────────┬────────┘  └────────┬────────┘ │
│           │                    │                    │           │
│           └────────────────────┼────────────────────┘           │
│                                ▼                                │
│                    ┌─────────────────┐                        │
│                    │ 对话情境锚定     │                        │
│                    │ 系统             │                        │
│                    │                 │                        │
│                    │ • 关系+状态建议 │                        │
│                    │ • 情境限制条件  │                        │
│                    │ • 台词风格锚定  │                        │
│                    └─────────────────┘                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 18.2 人物关系管理系统

#### 18.2.1 数据结构

```typescript
// 角色关系
interface CharacterRelationship {
  id: string;

  // 关系双方
  characterA: string;
  characterB: string;

  // 关系类型
  relationshipType: RelationshipType;

  // 关系指标
  metrics: RelationshipMetrics;

  // 互动历史
  interactions: Interaction[];

  // 关键事件
  keyEvents: KeyRelationshipEvent[];

  createdAt: Date;
  updatedAt: Date;
}

enum RelationshipType {
  // 正面关系
  FRIEND = 'friend',           // 朋友
  ALLY = 'ally',               // 同盟/战友
  LOVER = 'lover',             // 恋人
  FAMILY = 'family',           // 家人
  MENTOR = 'mentor',          // 师徒
  RIVAL = 'rival',            // 竞争对手
  GRUDGE = 'grudge',          // 仇怨

  // 中立关系
  ACQUAINTANCE = 'acquaintance',  // 认识
  STRANGER = 'stranger',          // 陌生人
  ENEMY = 'enemy',               // 敌人
}

interface RelationshipMetrics {
  intimacy: number;              // 亲密度 (0-100)
  trust: number;                 // 信任度 (0-100)
  interactionFrequency: number;  // 互动频率 (次/章节)
  lastInteractionChapter: number; // 最后互动章节
  totalInteractions: number;      // 累计互动次数
}

interface Interaction {
  id: string;
  chapterNo: number;
  chapterTitle: string;
  interactionType: 'first_meeting' | 'battle' | 'conversation' | 'cooperation' | 'conflict' | 'emotional_support';
  description: string;
  intimacyChange: number;
  trustChange: number;
  emotionTags: string[];
}

interface KeyRelationshipEvent {
  id: string;
  chapterNo: number;
  eventType: 'first_meet' | 'oath' | 'betrayal' | 'reconciliation' | 'death' | 'rescue';
  description: string;
  impactOnRelationship: 'positive' | 'negative' | 'neutral';
  affectedMetrics: {
    intimacyChange?: number;
    trustChange?: number;
  };
}
```

#### 18.2.2 关系追踪流程

```
角色互动追踪流程:

    新章节生成
         │
         ▼
┌─────────────────────┐
│ 解析章节内容        │
│ 识别角色互动事件    │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│ 更新关系指标        │
│ • 亲密度变化        │
│ • 信任度变化        │
│ • 互动频率更新      │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│ 记录互动历史        │
│ • 创建Interaction   │
│ • 关联章节          │
└────────┬────────────┘
         │
         ▼
    关系数据持久化
```

### 18.3 角色状态监控模块

#### 18.3.1 数据结构

```typescript
// 角色状态
interface CharacterStatus {
  characterId: string;
  chapterNo: number;

  // 生命状态
  lifeStatus: 'alive' | 'dead' | 'unknown';
  deathChapter?: number;
  deathCause?: string;

  // 健康状况
  health: HealthStatus;

  // 情绪状态
  emotional: EmotionalStatus;

  // 能力状态
  ability: AbilityStatus;

  // 位置状态
  location: LocationStatus;

  // 状态变化历史
  statusHistory: StatusChange[];

  updatedAt: Date;
}

interface HealthStatus {
  value: number;               // 健康值 (0-100)
  injuries: Injury[];
  fatigue: number;             // 疲劳度 (0-100)
  needsRecovery: boolean;
}

interface Injury {
  id: string;
  type: 'physical' | 'mental' | 'spiritual';
  severity: 'minor' | 'major' | 'critical';
  description: string;
  healingChapter?: number;
  isPersistent: boolean;
}

interface EmotionalStatus {
  value: number;               // 情绪值 (0-100, 50为平静)
  emotion: EmotionalType;
  volatility: number;          // 情绪波动幅度 (0-100)
  mentalState: 'stable' | 'unstable' | 'breaking';
  psychologicalEvents: PsychologicalEvent[];
}

enum EmotionalType {
  JOY = 'joy', CALM = 'calm', ANXIETY = 'anxiety', ANGER = 'anger',
  GRIEF = 'grief', FEAR = 'fear', DETERMINATION = 'determination', CONFUSION = 'confusion',
}

interface AbilityStatus {
  performance: number;         // 当前能力发挥 (0-100%)
  energy: number;              // 能量/气力 (0-100)
  limitations: AbilityLimitation[];
  specialState?: 'mental_burst' | 'awakening' | 'berserk' | 'sealed';
}

interface AbilityLimitation {
  type: 'cooldown' | 'resource_depleted' | 'injury_penalty' | 'sealed';
  description: string;
  recoverable: boolean;
  recoveryCondition?: string;
}
```

#### 18.3.2 状态监控流程

```
状态监控流程:

    每章节完成后
         │
         ▼
┌─────────────────────┐
│ 解析本章事件        │
│ 识别状态变化        │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│ 更新角色状态        │
│ • 检查死亡事件      │
│ • 计算健康变化      │
│ • 分析情绪波动      │
│ • 记录能力变化      │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│ 冲突检测            │
│ • 死亡角色出现?     │
│ • 能力超出当前限制? │
│ • 情绪变化合理性?   │
└────────┬────────────┘
         │
         ▼
    状态预警与持久化
```

### 18.4 宇宙设定一致性校验器

#### 18.4.1 数据结构

```typescript
// 宇宙设定规则
interface UniverseRule {
  id: string;
  universeId: string;
  ruleType: RuleType;
  content: string;

  scope: {
    characters?: string[];
    abilities?: string[];
    locations?: string[];
    timeline?: string;
  };

  conflictRules?: string[];
  priority: number;
}

enum RuleType {
  ENCOUNTER_RULE = 'encounter_rule',           // 相遇规则
  ABILITY_COMPATIBILITY = 'ability_compatibility', // 能力兼容性
  TIMELINE_RULE = 'timeline_rule',             // 时间线规则
  WORLD_RULE = 'world_rule',                  // 世界观规则
  CROSSOVER_RULE = 'crossover_rule',          // 跨作品规则
}

// 相遇规则引擎
interface EncounterContext {
  chapterNo: number;
  timeline: string;
  location: string;
  currentWorldline: string;
}

interface EncounterResult {
  allowed: boolean;
  restrictions: EncounterRestriction[];
  warnings: string[];
  conflictRules?: string[];
}

interface EncounterRestriction {
  type: 'timeline_conflict' | 'ability_conflict' | 'status_conflict' | 'worldline_conflict';
  description: string;
  severity: 'error' | 'warning' | 'info';
  suggestion?: string;
}

// 跨作品兼容矩阵
interface CrossoverCompatibility {
  universeA: string;
  universeB: string;
  compatibilityLevel: 'full' | 'partial' | 'none';
  fusionRules?: {
    allowed: boolean;
    conditions: string[];
    conflicts: string[];
  };
  abilityMapping?: AbilityMapping[];
}
```

#### 18.4.2 校验流程

```
宇宙设定校验流程:

    角色互动/相遇事件
         │
         ▼
┌─────────────────────┐
│ 获取双方宇宙设定    │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│ 时空背景校验        │
│ • 时间线是否重叠    │
│ • 地点是否可达    │
│ • 世界线是否兼容  │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│ 能力体系校验        │
│ • 能力是否冲突    │
│ • 威力是否平衡    │
│ • 规则是否矛盾    │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│ 相遇规则校验        │
│ • 是否满足相遇条件│
│ • 是否有相遇限制│
└────────┬────────────┘
         │
         ▼
    返回校验结果
```

### 18.5 对话情境锚定系统

#### 18.5.1 数据结构

```typescript
// 对话情境上下文
interface DialogueContext {
  characterId: string;
  relationship: CharacterRelationship;
  status: CharacterStatus;
  universeRules: UniverseRule[];
  situationType: SituationType;
  emotionalAtmosphere: EmotionalAtmosphere;
}

enum SituationType {
  FIRST_MEETING = 'first_meeting',
  REUNION = 'reunion',
  BATTLE = 'battle',
  COOPERATION = 'cooperation',
  EMOTIONAL_SUPPORT = 'emotional_support',
  CONFLICT = 'conflict',
  CONVERSATION = 'conversation',
  FAREWELL = 'farewell',
}

interface EmotionalAtmosphere {
  overall: 'friendly' | 'neutral' | 'hostile' | 'tense' | 'warm';
  intensity: number;
  dominantEmotions: string[];
}

// 对话风格配置
interface DialogueStyleConfig {
  languageFingerprint: {
    vocabularyLevel: 'formal' | 'casual' | 'slang';
    speechPatterns: string[];
    catchphrases: string[];
    transformationAnnounce?: string;
    finisherAnnounce?: string;
  };

  relationshipInfluence: {
    toneAdjustments: Map<RelationshipType, ToneAdjustment>;
  };

  situationConstraints: {
    situationLimits: Map<SituationType, string[]>;
  };
}

interface ToneAdjustment {
  formality: number;
  warmth: number;
  hostility: number;
  respect: number;
}

// 对话建议
interface DialogueSuggestion {
  suggestedLines: string[];
  availableExpressions: Expression[];
  prohibitedExpressions: string[];
  toneGuide: {
    formality: number;
    warmth: number;
    shouldInclude: string[];
    avoid: string[];
  };
}
```

#### 18.5.2 对话生成流程

```
对话情境锚定流程:

    请求生成对话
         │
         ▼
┌─────────────────────┐
│ 构建对话上下文      │
│ • 角色关系检索      │
│ • 角色状态检索      │
│ • 宇宙规则检索      │
│ • 情境类型判定      │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│ 获取对话风格配置    │
│ • 语言指纹召回      │
│ • 关系影响调整      │
│ • 情境限制应用      │
└────────┬────────────┘
         │
         ▼
┌─────────────────────┐
│ 生成对话建议        │
│ • 符合风格的台词    │
│ • 关系适配的称呼    │
│ • 情境适当的语气    │
│ • 避免的表达式      │
└────────┬────────────┘
         │
         ▼
    输出对话约束
    (注入C-Prompt)
```

### 18.6 同人增强在C-Prompt中的集成

```typescript
// 同人增强上下文构建
class FanFictionContextBuilder {
  async buildContext(request: WritingRequest): Promise<CPromptContext> {
    const context: CPromptContext = {
      baseConstraints: [],
      relationshipConstraints: [],
      statusConstraints: [],
      universeConstraints: [],
      dialogueConstraints: [],
    };

    // 1. 获取相关角色关系
    const relationships = await this.relationshipService.getRelationships(
      request.characterIds
    );
    context.relationshipConstraints = this.buildRelationshipConstraints(relationships);

    // 2. 获取角色当前状态
    const statuses = await this.statusService.getCurrentStatuses(
      request.characterIds
    );
    context.statusConstraints = this.buildStatusConstraints(statuses);

    // 3. 获取宇宙设定约束
    const universeRules = await this.universeService.getApplicableRules(
      request.characters,
      request.location,
      request.timeline
    );
    context.universeConstraints = this.buildUniverseConstraints(universeRules);

    // 4. 对话情境锚定
    if (request.includeDialogue) {
      const dialogueContext = await this.dialogueService.buildContext(
        request.characterId,
        request.situationType
      );
      context.dialogueConstraints = this.buildDialogueConstraints(dialogueContext);
    }

    return context;
  }

  // 构建关系约束
  private buildRelationshipConstraints(
    relationships: CharacterRelationship[]
  ): Constraint[] {
    return relationships.map(rel => ({
      type: 'relationship',
      content: `${rel.characterA}与${rel.characterB}的关系: ${rel.relationshipType}`,
      metrics: rel.metrics,
      priority: 'high',
    }));
  }

  // 构建状态约束
  private buildStatusConstraints(statuses: CharacterStatus[]): Constraint[] {
    return statuses.map(status => ({
      type: 'status',
      content: `${status.characterId}当前状态: ${status.lifeStatus}, 情绪${status.emotional.emotion}`,
      restrictions: this.extractRestrictions(status),
      priority: 'high',
    }));
  }

  // 构建宇宙规则约束
  private buildUniverseConstraints(rules: UniverseRule[]): Constraint[] {
    return rules.map(rule => ({
      type: 'universe_rule',
      content: rule.content,
      severity: rule.priority > 5 ? 'high' : 'medium',
    }));
  }
}
```

### 18.7 数据持久化设计

```typescript
// 关系数据持久化
interface RelationshipRepository {
  save(relationship: CharacterRelationship): Promise<void>;
  findById(id: string): Promise<CharacterRelationship>;
  findByCharacters(charA: string, charB: string): Promise<CharacterRelationship>;
  findAllByNovel(novelId: string): Promise<CharacterRelationship[]>;
  updateMetrics(id: string, metrics: Partial<RelationshipMetrics>): Promise<void>;
  addInteraction(id: string, interaction: Interaction): Promise<void>;
}

// 状态数据持久化
interface CharacterStatusRepository {
  save(status: CharacterStatus): Promise<void>;
  findByCharacterAndChapter(characterId: string, chapterNo: number): Promise<CharacterStatus>;
  findLatest(characterId: string): Promise<CharacterStatus>;
  findByNovel(novelId: string, chapterNo?: number): Promise<CharacterStatus[]>;
  getStatusHistory(characterId: string): Promise<StatusChange[]>;
}

// 校验规则持久化
interface UniverseRuleRepository {
  save(rule: UniverseRule): Promise<void>;
  findByUniverse(universeId: string): Promise<UniverseRule[]>;
  findCompatibleRules(charA: string, charB: string): Promise<UniverseRule[]>;
  checkCompatibility(universeA: string, universeB: string): Promise<CrossoverCompatibility>;
}
```

### 18.8 同人增强效果总结

| 模块 | 功能 | 创作辅助价值 |
|------|------|--------------|
| 人物关系管理 | 互动历史追踪、亲密度/信任度量化 | 确保关系发展符合逻辑，避免突兀转变 |
| 角色状态监控 | 生命/健康/情绪/能力实时维护 | 防止角色状态矛盾（如死亡后继续行动） |
| 宇宙设定校验 | 跨作品相遇规则、能力兼容性检查 | 维护原作设定的严肃性，避免设定冲突 |
| 对话情境锚定 | 风格建议、关系适配、情境限制 | 确保对话符合人物性格和当前关系 |

---

## 第十九部分：剧情对齐与蝴蝶效应系统

### 19.1 核心问题与解决思路

同人创作面临两大核心难题：

```
┌─────────────────────────────────────────────────────────────────┐
│  同人创作的两大难题                                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  【剧情对齐问题】                                                │
│  如何确保同人作家的创作与原作剧情进度、人物发展保持一致？         │
│                                                                  │
│  【蝴蝶效应问题】                                                │
│  穿越者进入原作时间线后，如何追踪其行为对后续剧情的影响？       │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 19.2 剧情对齐系统

#### 19.2.1 剧情对齐策略

```
┌─────────────────────────────────────────────────────────────────┐
│  剧情对齐策略                                                    │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  策略一：原作进度锚定                                           │
│  ────────────────────────────────────────────────────────────    │
│  • 基于原作时间线建立关键剧情节点                                │
│  • 同人创作必须在关键节点处与原作保持一致                      │
│  • 允许在非关键节点进行自由创作                                │
│                                                                  │
│  策略二：同人小说分析 (用户上传)                                 │
│  ────────────────────────────────────────────────────────────    │
│  • 用户上传同类同人作品作为参考                                  │
│  • 引擎分析同人作品的剧情节奏、人物塑造模式                      │
│  • 提取"安全创作区间"供用户选择                                 │
│                                                                  │
│  策略三：书库选取                                               │
│  ────────────────────────────────────────────────────────────    │
│  • 从平台书库中选择已有的同人作品作为对齐参照                   │
│  • 利用已验证的剧情模板进行创作                                 │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

#### 19.2.2 原作剧情锚点结构

```typescript
// 原作剧情锚点
interface CanonPlotAnchor {
  id: string;
  chapterRange: string;           // 如 "第1-5话"
  title: string;                 // 如 "初登场与第一战"

  // 对齐类型
  alignmentType: AlignmentType;

  // 关键内容 (必须与原作一致)
  criticalContent: {
    characterStates: CharacterState[];
    plotEvents: PlotEvent[];
    relationshipChanges: RelationshipChange[];
  };

  // 允许的同人创作范围
  allowedDeviation: {
    isAllowed: boolean;
    maxDeviationLevel: 'minor' | 'moderate' | 'major' | 'any';
  };

  // 蝴蝶效应标记
  butterflyEffect: {
    isSensitive: boolean;
    impactRadius: number;
    warningMessage?: string;
  };
}

enum AlignmentType {
  CRITICAL = 'critical',     // 关键节点：必须严格对齐
  FLEXIBLE = 'flexible',    // 灵活节点：主线必须对齐，细节可自由发挥
  FREE = 'free',            // 自由区间：可完全自由创作
}
```

#### 19.2.3 同人小说分析器

```typescript
// 同人小说分析器
class FanFictionAnalyzer {

  async analyzeFanFiction(
    document: FanFictionDocument
  ): Promise<FanFictionAnalysis> {

    // 1. 结构化解析
    const structured = await this.parseStructure(document);

    // 2. 关键事件提取
    const keyEvents = await this.extractKeyEvents(structured);

    // 3. 人物发展轨迹提取
    const characterArcs = await this.extractCharacterArcs(structured);

    // 4. 剧情节奏分析
    const rhythm = await this.analyzeRhythm(structured);

    // 5. 生成安全创作区间
    const safeZones = await this.identifySafeZones(keyEvents, characterArcs, rhythm);

    return { safeZones, characterArcs, recommendedPatterns: this.extractPatterns(keyEvents, rhythm) };
  }
}

// 安全区间
interface SafeZone {
  startPosition: number;
  endPosition: number;
  safeLevel: 'high' | 'medium' | 'low';
  recommendedActivities: string[];
  potentialRisks: string[];
}
```

#### 19.2.4 书库选取系统

```typescript
// 书库对齐服务
class BookLibraryAlignmentService {

  async selectAlignmentReference(
    request: AlignmentRequest
  ): Promise<AlignmentReference> {

    // 1. 匹配同类作品
    const similarWorks = await this.findSimilarWorks(request);

    // 2. 评估每个参照物的质量
    const scoredReferences = await this.scoreReferences(similarWorks);

    // 3. 选择最佳参照
    return this.selectBestReference(scoredReferences);
  }

  private async findSimilarWorks(request: AlignmentRequest): Promise<LibraryWork[]> {
    return await this.vectorStore.search({
      query: `${request.originalWork} ${request.fandom}`,
      filter: {
        type: 'fanfiction',
        fandom: request.fandom,
        status: 'completed',
        minRating: 4.0,
      },
      topK: 20,
    });
  }
}
```

### 19.3 穿越者蝴蝶效应系统

#### 19.3.1 蝴蝶效应追踪模型

```
┌─────────────────────────────────────────────────────────────────┐
│  蝴蝶效应追踪模型                                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  【时间线分叉理论】                                              │
│  穿越者进入原作时间线 → 产生新时间线分支                         │
│                                                                  │
│  【效应传播模型】                                                │
│  穿越者行为 → 直接影响事件 → 间接影响事件 → 扩散                  │
│                                                                  │
│  【一致性维护】                                                  │
│  检测穿越者行为是否导致与原作关键节点冲突                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

#### 19.3.2 时间线分叉结构

```typescript
// 时间线分支
interface TimelineBranch {
  id: string;
  name: string;
  parentBranchId?: string;

  // 分叉点
  forkPoint: {
    chapterNo: number;
    originalEvent: string;
    travelerAction: string;
    divergenceDescription: string;
  };

  // 分支状态
  status: 'active' | 'merged' | 'collapsed';

  // 影响范围
  impactScope: {
    affectedChapters: number[];
    affectedCharacters: string[];
    affectedRelationships: string[];
  };

  // 与原作的偏离度
  divergenceLevel: number;      // 0-100, 0=完全一致, 100=完全偏离
}

// 蝴蝶效应事件
interface ButterflyEffectEvent {
  id: string;
  branchId: string;

  event: {
    chapterNo: number;
    description: string;
    type: 'direct' | 'indirect' | 'cascading';
  };

  trigger: {
    type: 'traveler_action' | 'divine_intervention' | 'original_plot';
    sourceAction: string;
  };

  impact: {
    characters: CharacterImpact[];
    relationships: RelationshipImpact[];
    plotDeviation: number;
  };

  traceability: {
    isTraceable: boolean;
    causeChain: string[];
    originalCauseId?: string;
  };
}
```

#### 19.3.3 蝴蝶效应追踪器

```typescript
class ButterflyEffectTracker {

  async recordTravelerAction(
    action: TravelerAction
  ): Promise<EffectPropagation[]> {
    const effects: EffectPropagation[] = [];

    // 1. 直接影响分析
    const directEffects = await this.analyzeDirectEffects(action);
    effects.push(...directEffects);

    // 2. 间接影响分析 (级联效应)
    for (const direct of directEffects) {
      const indirectEffects = await this.analyzeIndirectEffects(direct);
      effects.push(...indirectEffects);
    }

    // 3. 更新时间线状态
    await this.updateTimelineState(action, effects);

    // 4. 检测冲突
    const conflicts = await this.detectConflicts(effects);

    return effects;
  }

  private async analyzeDirectEffects(action: TravelerAction): Promise<EffectPropagation[]> {
    const affectedEntities = await this.findAffectedEntities(action);
    return affectedEntities.map(entity => ({
      id: generateId(),
      triggerAction: action.id,
      affectedEntity: entity,
      effectType: 'direct',
      chapterNo: action.chapterNo,
      deviationLevel: this.calculateDeviation(entity, action),
    }));
  }

  private async analyzeIndirectEffects(directEffect: EffectPropagation): Promise<EffectPropagation[]> {
    const cascadeEffects: EffectPropagation[] = [];
    const secondOrderAffected = await this.findSecondOrderAffected(directEffect.affectedEntity);

    for (const entity of secondOrderAffected) {
      const contradiction = await this.checkContradiction(entity, directEffect);
      if (contradiction) {
        cascadeEffects.push({
          id: generateId(),
          triggerAction: directEffect.triggerAction,
          affectedEntity: entity,
          effectType: 'cascading',
          chapterNo: this.estimateChapter(entity),
          deviationLevel: directEffect.deviationLevel * CASCADE_FACTOR,
          hasContradiction: true,
          contradictionDetails: contradiction,
        });
      }
    }
    return cascadeEffects;
  }
}
```

#### 19.3.4 蝴蝶效应冲突检测

```typescript
class ButterflyConflictDetector {

  async detectConflicts(
    effects: EffectPropagation[],
    branch: TimelineBranch
  ): Promise<Conflict[]> {
    const conflicts: Conflict[] = [];

    // 1. 与原作关键节点的冲突
    const criticalConflicts = await this.checkCriticalNodes(effects, branch);
    conflicts.push(...criticalConflicts);

    // 2. 角色状态矛盾
    const stateConflicts = await this.checkCharacterStates(effects, branch);
    conflicts.push(...stateConflicts);

    // 3. 时间线悖论
    const paradoxes = await this.checkTimelineParadoxes(effects);
    conflicts.push(...paradoxes);

    return conflicts;
  }

  private async checkTimelineParadoxes(effects: EffectPropagation[]): Promise<Paradox[]> {
    const paradoxes: Paradox[] = [];

    for (let i = 0; i < effects.length; i++) {
      for (let j = i + 1; j < effects.length; j++) {
        const paradox = this.evaluateCausality(effects[i], effects[j]);
        if (paradox) {
          paradoxes.push({
            type: 'grandfather' | 'closed_loop' | 'self-caused',
            effectA: effects[i],
            effectB: effects[j],
            description: paradox.description,
            severity: paradox.severity,
          });
        }
      }
    }
    return paradoxes;
  }
}
```

### 19.4 对齐与蝴蝶效应协同

```typescript
class AlignmentButterflyController {

  async processConstraints(request: WritingRequest): Promise<ConstraintSet> {
    const constraints: ConstraintSet = {
      alignmentConstraints: [],
      butterflyConstraints: [],
      conflictWarnings: [],
    };

    // 1. 获取剧情对齐约束
    if (request.useAlignment) {
      const alignment = await this.getAlignmentConstraints(request);
      constraints.alignmentConstraints = alignment.constraints;
    }

    // 2. 获取蝴蝶效应约束
    if (request.isTimeTraveler) {
      const butterfly = await this.getButterflyConstraints(request);
      constraints.butterflyConstraints = butterfly.constraints;
      constraints.conflictWarnings = butterfly.warnings;
    }

    // 3. 检测约束冲突
    const conflicts = this.detectConstraintConflicts(constraints);

    return conflicts.length > 0
      ? { ...constraints, conflicts, requiresUserDecision: true }
      : constraints;
  }

  async buildCPrompt(constraints: ConstraintSet): Promise<string> {
    let prompt = '';

    if (constraints.alignmentConstraints.length > 0) {
      prompt += '\n【剧情对齐约束】\n';
      for (const c of constraints.alignmentConstraints) {
        prompt += `- ${c.description}\n`;
      }
    }

    if (constraints.butterflyConstraints.length > 0) {
      prompt += '\n【蝴蝶效应约束】\n';
      for (const c of constraints.butterflyConstraints) {
        prompt += `- ${c.description}\n`;
      }
    }

    if (constraints.conflictWarnings.length > 0) {
      prompt += '\n【⚠️ 冲突警告】\n';
      for (const w of constraints.conflictWarnings) {
        prompt += `- ${w}\n`;
      }
    }

    return prompt;
  }
}
```

### 19.5 剧情对齐与蝴蝶效应效果总结

| 功能 | 说明 |
|------|------|
| 剧情对齐 | 通过原作锚点、同人分析、书库选取三种策略确保同人作品与原作一致 |
| 蝴蝶效应追踪 | 记录穿越者行为，分析直接/间接影响链，检测时间线冲突 |
| 冲突预警 | 在关键节点前预警可能的剧情偏离，提前提供修复建议 |
| 协同约束 | 将对齐约束与蝴蝶效应约束统一管理，解决双重约束冲突 |
