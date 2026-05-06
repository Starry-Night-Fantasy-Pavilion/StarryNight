# 星夜阁 AI小说写作平台 - 开发规范文档

## 版本历史

| 版本 | 日期 | 说明 |
|------|------|------|
| 1.0 | 2026-04-28 | 初始版本 |

---

## 第一部分：项目概述

### 1.1 项目定位

**星夜阁**是一款面向中文网文作者的AI辅助小说创作平台，核心价值在于：

- 降低创作门槛，提高写作效率
- 通过浏览器本地算力实现低成本AI推理
- P2P网络优化带宽成本
- 完整支持从大纲到正文的系统化创作流程

### 1.2 设计理念

```
┌─────────────────────────────────────────────────────────────────┐
│                         星夜阁设计理念                            │
├─────────────────────────────────────────────────────────────────┤
│  🎯 使命    │  让每个有创意的人都能写出好故事                    │
│  🚀 愿景    │  成为中文网文作者首选的AI创作工具                   │
│  ⚡ 特色    │  本地AI + P2P分发 + 阶段式协同                     │
│  🎨 体验    │  简洁直观 + 深度定制 + 素材联动                   │
└─────────────────────────────────────────────────────────────────┘
```

### 1.3 核心流程

```
                              阶段式AI对话修改
                                   ↕↗↘
┌─────────────────────────────────────────────────────────────────┐
│                                                                 │
│   📖 大纲 ──→ 📑 卷纲 ──→ 📝 章节细纲 ──→ ✍️ 正文扩写            │
│      │          │           │           │                        │
│      └──────────┴───────────┴───────────┘                        │
│                       │                                          │
│                       ▼                                          │
│              💬 素材库 (统一存储/拖拽嵌入)                         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## 第二部分：系统架构

### 2.1 整体架构

```
┌─────────────────────────────────────────────────────────────────────────┐
│                              CDN/OSS (极少量使用)                       │
│                    仅存储: 引导页 + 种子文件 + 核心镜像                  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ╔════════════════════════════════════════════════════════════════════╗   │
│  ║                    P2P Network (WebTorrent)                       ║   │
│  ║                    实现前端资源CDN加速效果                          ║   │
│  ║                                                                 ║   │
│  ║    ┌──────────────────────────────────────────────────────────┐  ║   │
│  ║    │                    User Swarm                            │  ║   │
│  ║    │                                                           │  ║   │
│  ║    │  ┌────────┐   前端资源      ┌────────┐                  │  ║   │
│  ║    │  │ User A │◄──────────────►│ User B │                  │  ║   │
│  ║    │  └────────┘                  └────────┘                  │  ║   │
│  ║    │      │                            │                       │  ║   │
│  ║    │      │     共享前端资源                                    │  ║   │
│  ║    │      │   (JS/CSS/图片/字体/SPA Bundle)                  │  ║   │
│  ║    │      ▼                            ▼                       │  ║   │
│  ║    │  ┌─────────────────────────────────────────────────────┐ │  ║   │
│  ║    │  │           IndexedDB Cache (本地持久化)                │ │  ║   │
│  ║    │  │  • SPA Bundles  • 用户数据                           │ │  ║   │
│  ║    │  └─────────────────────────────────────────────────────┘ │  ║   │
│  ║    └──────────────────────────────────────────────────────────┘  ║   │
│  ╚════════════════════════════════════════════════════════════════════╝   │
│                                                                          │
├─────────────────────────────────────────────────────────────────────────┤
│                         Nginx (反向代理)                                   │
│                  主域名 → 用户端  |  /admin → 运营端                      │
├─────────────────────────────────────────────────────────────────────────┤
│                    Vue3 前端 (单工程, P2P加载)                             │
│         ┌─────────────────────────┬────────────────────────┐             │
│         │        用户端            │          运营端          │             │
│         │   (AI创作中心)          │     (管理系统)           │             │
│         └─────────────────────────┴────────────────────────┘             │
├─────────────────────────────────────────────────────────────────────────┤
│                      Spring Boot API (极轻量)                            │
│     认证 │ 支付 │ 配置管理 │ 元数据 │ 知识库向量索引                       │
├─────────────────────────────────────────────────────────────────────────┤
│                    MySQL │ Redis │ 向量数据库                            │
└─────────────────────────────────────────────────────────────────────────┘
```

### 2.2 访问路由规则

| 访问路径 | 目标 | 说明 |
|---------|------|------|
| `starrynight.com/` | 用户端 | 作者创作中心 |
| `starrynight.com/{admin_path}` | 运营端 | 管理系统（路径可配置，默认`/admin`） |

### 2.3 技术选型

| 层级 | 技术 | 选型依据 |
|------|------|----------|
| 后端框架 | Spring Boot 3.x | 成熟稳定、社区活跃 |
| 安全框架 | Spring Security + JWT | 无状态认证 |
| ORM | MyBatis-Plus | 高效CRUD、分页支持 |
| 缓存 | Redis | 高性能、会话管理 |
| 数据库 | MySQL 8.0 | 成熟稳定 |
| 向量数据库 | Qdrant | 支持多集合、元数据过滤、混合搜索 |
| 前端框架 | Vue3 + Vite | 性能优异 |
| 状态管理 | Pinia | Vue3官方推荐 |
| AI推理 | WebLLM + TF.js | 浏览器本地推理 |
| P2P | WebTorrent | BitTorrent协议 |

---

## 第三部分：功能模块设计

### 3.1 模块总览

```
星夜阁功能模块
├── 一、核心AI创作流程
│   ├── 1.1 大纲生成
│   ├── 1.2 卷纲生成
│   ├── 1.3 章节细纲生成
│   └── 1.4 正文扩写
├── 二、知识库系统
│   ├── 2.1 文档上传与管理
│   ├── 2.2 智能切片与向量化
│   ├── 2.3 RAG召回注入
│   └── 2.4 手动引用
├── 三、提示词库
│   ├── 3.1 官方模板库
│   ├── 3.2 自定义提示词
│   └── 3.3 一键使用
├── 四、角色库
│   ├── 4.1 角色档案管理
│   ├── 4.2 关系网
│   ├── 4.3 一致性检查
│   └── 4.4 AI协作生成
├── 五、风格扩写
│   ├── 5.1 样本上传
│   ├── 5.2 风格分析
│   └── 5.3 扩写/续写
├── 六、阶段式AI对话
│   ├── 6.1 节点级对话
│   ├── 6.2 版本历史
│   └── 6.3 差异对比
├── 七、小工具集
│   ├── 7.1 金手指生成
│   ├── 7.2 书名生成
│   ├── 7.3 简介生成
│   ├── 7.4 世界观生成
│   ├── 7.5 冲突/桥段生成
│   ├── 7.6 人物速成
│   └── 7.7 扩写/仿写
├── 八、素材库
│   ├── 8.1 统一存储
│   ├── 8.2 拖拽嵌入
│   └── 8.3 适合度推荐
├── 九、用户端
│   ├── 9.1 首页/落地页
│   ├── 9.2 作者中心
│   ├── 9.3 作品管理
│   └── 9.4 个人中心
└── 十、运营端
    ├── 10.1 仪表盘
    ├── 10.2 用户管理
    ├── 10.3 作品管理
    ├── 10.4 AI配置中心
    ├── 10.5 订单管理
    ├── 10.6 系统设置
    └── 10.7 数据统计
```

### 3.2 核心AI创作流程

#### 3.2.1 大纲生成

**功能描述**：用户输入一句话创意，系统生成完整小说大纲。

**输入参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| core_idea | string | 是 | 一句话核心创意 |
| genre | string | 是 | 题材类型 |
| word_count_target | int | 否 | 目标字数（默认30万） |
| style | string | 否 | 写作风格 |
| knowledge_ids | array | 否 | 引用的知识库ID |
| character_ids | array | 否 | 引用角色ID |
| outline_template | string | 否 | 大纲模板（黄金三幕/起承转合等） |

**输出结构**：
```json
{
  "outline": {
    "title": "小说标题",
    "genre": "玄幻",
    "estimated_word_count": 300000,
    "three_act_structure": {
      "act1": {
        "name": "建置",
        "summary": "概括",
        "chapters": ["第1-10章概述"],
        "key_events": ["事件A", "事件B"]
      },
      "act2": {
        "name": "对抗",
        "summary": "概括",
        "chapters": ["第11-80章概述"],
        "key_events": []
      },
      "act3": {
        "name": "解决",
        "summary": "概括",
        "chapters": ["第81-100章概述"],
        "key_events": []
      }
    },
    "main_characters": ["角色ID列表"],
    "world_setting": "世界观摘要"
  },
  "version": 1,
  "created_at": "2026-04-28T10:00:00Z"
}
```

**AI提示词模板**：
```
你是一位资深网文编辑，擅长设计引人入胜的故事结构。
请根据以下信息生成一个完整的【{genre}】类型小说大纲：

核心创意：{core_idea}
目标字数：{word_count_target}字
写作风格：{style}
参考知识：{knowledge_context}
相关角色：{character_context}
大纲模板：{outline_template}

要求：
1. 大纲应包含清晰的三幕结构（建置/对抗/解决）
2. 每幕需包含核心冲突、关键事件、人物弧线
3. 主线清晰，支线为辅
4. 结局要有高潮和情感释放
5. 符合网文读者的阅读习惯

请以JSON格式输出，包含：
- title: 小说标题
- three_act_structure: 三幕结构详情
- main_characters: 主要角色列表
- world_setting: 世界观简述
```

#### 3.2.2 卷纲生成

**功能描述**：根据大纲生成各卷的核心冲突、情节走向、关键节点。

**输入参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| outline_id | string | 是 | 大纲ID |
| volume_count | int | 是 | 分卷数量 |
| volume_index | int | 否 | 指定卷索引（空则生成全部） |
| knowledge_ids | array | 否 | 引用知识库 |
| character_ids | array | 否 | 引用角色 |
| golden_finger_id | string | 否 | 金手指ID |

**输出结构**：
```json
{
  "volumes": [
    {
      "volume_no": 1,
      "title": "第一卷：入门",
      "summary": "本卷核心冲突...",
      "theme": "成长与觉醒",
      "chapters_preview": ["第1章：...","第2章：..."],
      "key_events": [
        {
          "event_name": "事件名称",
          "chapter_range": "1-5",
          "description": "事件描述",
          "emotional_beat": "情绪高点"
        }
      ],
      "character_arcs": [
        {
          "character_id": "角色ID",
          "arc_description": "本卷角色弧线"
        }
      ],
      "conflicts": ["冲突点列表"],
      "foreshadowing": ["伏笔列表"]
    }
  ]
}
```

#### 3.2.3 章节细纲生成

**功能描述**：根据卷纲生成每章节的核心事件、人物出场、情感变化。

**输入参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| volume_id | string | 是 | 卷ID |
| chapter_count | int | 是 | 章节数量 |
| chapter_index | int | 否 | 指定章节（空则全部） |
| draft_style | string | 否 | 卡片模式/详细模式 |
| knowledge_ids | array | 否 | 引用知识库 |
| reference_novel_id | string | 否 | 参考作品ID（用于风格） |

**输出结构**：
```json
{
  "chapters": [
    {
      "chapter_no": 1,
      "title": "第1章：觉醒",
      "core_event": "本章核心事件",
      "scene_setting": {
        "location": "场景地点",
        "time": "时间段",
        "atmosphere": "氛围"
      },
      "characters_present": ["角色ID列表"],
      "plot_points": [
        {
          "order": 1,
          "type": "开场/发展/转折/高潮",
          "description": "情节点描述",
          "dialogue_snippet": "关键对白",
          "emotional_change": "情感变化"
        }
      ],
      "pacing": "节奏描述",
      "word_count_target": 3000,
      "foreshadowing": ["本章埋下的伏笔"],
      "connection_to_previous": "与上一章的衔接"
    }
  ]
}
```

#### 3.2.4 正文扩写

**功能描述**：根据章节细纲和风格样本，生成完整章节正文。

**输入参数**：
| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| chapter_id | string | 是 | 章节ID |
| reference_sample | string | 否 | 风格参考文本（可为空） |
| writing_style | string | 否 | 写作风格描述 |
| expand_ratio | float | 否 | 扩写比例（默认3-5倍） |
| first_person | boolean | 否 | 第一人称 |

**输出结构**：
```json
{
  "chapter_id": "章节ID",
  "title": "第1章：觉醒",
  "content": "正文内容...",
  "word_count": 3500,
  "style_fingerprint": {
    "avg_sentence_length": 25,
    "dialogue_ratio": 0.35,
    "description_density": "中等",
    "emotional_tone": "热血"
  },
  "generated_at": "2026-04-28T10:00:00Z"
}
```

### 3.3 知识库系统

#### 3.3.1 知识库分类

```typescript
type KnowledgeType = 
  | 'canon'        // 官方正史（人物/反派/世界观/时间线）
  | 'reference'    // 参考资料
  | 'material'     // 素材
  | 'custom';      // 自定义

interface KnowledgeLibrary {
  id: string;
  user_id: number;
  name: string;
  type: KnowledgeType;
  source_type: 'UPLOAD' | 'IMPORT' | 'GENERATED';
  file_type: string;
  file_size: number;
  file_url: string;
  capacity_used: number;
  tags: string[];
  description: string;
  is_public: boolean;
  share_code?: string;
  document_count: number;
  chunk_count: number;
  status: 'PROCESSING' | 'READY' | 'ERROR';
  error_message?: string;
  created_at: Date;
  updated_at: Date;
}
```

#### 3.3.2 容量管理

**容量规则**：
- 用户总容量 = 基础容量 × 会员等级系数（可配置）
- 默认容量：普通用户 1GB，VIP 10GB
- 运营端可单独调整每个用户的容量上限
- 不限制知识库创建数量，仅限制总容量

**运营端配置**（sys_config表）：
| 配置键 | 默认值 | 说明 |
|--------|--------|------|
| `knowledge_capacity_free` | 1073741824 (1GB) | 普通用户容量 |
| `knowledge_capacity_vip` | 10737418240 (10GB) | VIP用户容量 |
| `knowledge_capacity_svip` | 10737418240 (10GB) | SVIP用户容量（默认同VIP） |
| `max_upload_file_size` | 52428800 (50MB) | 单文件大小限制 |

#### 3.3.3 文档上传

**支持格式**：
| 格式 | 处理方式 |
|------|----------|
| txt | 直接解析 |
| markdown | 解析为结构化文本 |
| pdf | PDF.js解析 + OCR |
| epub | epub.js解析 |
| 图片 | OCR提取文字 |

**上传限制**：
- 单文件大小：50MB（可配置）
- 知识库数量：不限制
- 总容量：按用户等级上限

#### 3.3.4 文档切片策略

```typescript
interface DocumentChunk {
  id: string;
  library_id: string;
  content: string;
  content_hash: string;
  library_type: KnowledgeType;
  canon_metadata?: {
    entity_type: 'character' | 'antagonist' | 'worldview' | 'timeline' | 'rule';
    entity_name: string;
    entity_id?: string;
    importance: 'core' | 'important' | 'minor';
    tags: string[];
  };
  metadata: {
    source: string;
    page_start?: number;
    page_end?: number;
    section_title?: string;
  };
  vector_id: string;
  token_count: number;
  created_at: Date;
}

// 切片规则
const CHUNK_STRATEGY = {
  max_tokens: 500,
  overlap_tokens: 50,
  split_by: 'paragraph',
};
```

#### 3.3.5 RAG召回注入

**召回流程**：
```
用户请求 → 当前节点上下文 → 向量检索 → Top-K相关片段 → 注入提示词 → AI生成
```

**召回参数**：
```typescript
const RAG_CONFIG = {
  top_k: 5,
  similarity_threshold: 0.7,
  max_total_tokens: 2000,
  rerank: true,
};
```

#### 3.3.6 官方正史解析器

**定位**：基于知识库的官方设定检索解析组件

**使用限制**：
- ❌ 不参与：大纲生成阶段
- ✅ 只参与：卷纲 → 章节细纲 → 正文扩写

**接口定义**：

```typescript
// 正史检索请求
interface CanonSearchRequest {
  query: string;
  entity_types?: EntityType[];
  importance?: 'core' | 'important' | 'minor';
  novel_id?: string;
  top_k?: number;
}

type EntityType = 'character' | 'antagonist' | 'worldview' | 'timeline' | 'rule';

// 正史检索响应
interface CanonSearchResponse {
  results: CanonEntity[];
  total_count: number;
  processing_time_ms: number;
}

interface CanonEntity {
  chunk_id: string;
  library_id: string;
  library_name: string;
  entity_type: EntityType;
  entity_name: string;
  importance: 'core' | 'important' | 'minor';
  content: string;
  summary: string;
  related_entities?: {
    type: EntityType;
    name: string;
    chunk_id: string;
  }[];
  confidence: number;
  source_reference: string;
}
```

**使用流程**：
```
用户选择「引用官方正史」
        │
        ▼
┌─────────────────────────────────────┐
│     正史解析器 - 知识库检索           │
│  - 检索canon类型的知识库             │
│  - 向量相似度匹配                    │
│  - 实体类型过滤                      │
│  - 按重要程度排序                    │
└─────────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────────┐
│         检索结果展示界面              │
│  - 分类型展示（人物/反派/世界观等）   │
│  - 显示匹配度/摘要/来源              │
│  - 用户勾选需要引用的设定            │
└─────────────────────────────────────┘
        │
        ▼
      用户审核（只读，不写入）
        │
        ▼
┌─────────────────────────────────────┐
│      确认注入细纲/正文创作            │
└─────────────────────────────────────┘
```

### 3.4 存储系统

#### 3.4.1 存储模式类型

| 模式 | 说明 | 适用场景 |
|------|------|----------|
| `local` | 本地文件系统 | 单机部署、个人使用 |
| `oss` | 阿里云OSS | 国内生产环境 |
| `s3` | Amazon S3 / 兼容S3服务 | 海外AWS、自建MinIO |
| `hybrid` | 混合存储模式 | 需要灵活配置的企业环境 |

#### 3.4.2 数据分类与存储适配

```
┌─────────────────────────────────────────────────────────────────┐
│                        数据分类矩阵                              │
├───────────────────┬─────────────┬─────────────┬─────────────────┤
│ 数据类型           │ 本地存储     │ OSS/S3      │ 说明            │
├───────────────────┼─────────────┼─────────────┼─────────────────┤
│ 小说正文          │ ✅ 适合      │ ✅ 适合     │ 大文件          │
│ 章节草稿          │ ✅ 适合      │ ✅ 适合     │ 中等文件        │
│ 用户头像          │ ✅ 适合      │ ✅ 适合     │ 小文件          │
│ 知识库文档        │ ✅ 适合      │ ✅ 适合     │ 大文件          │
│ AI模型文件        │ ⚠️ 可用      │ ✅ 最佳     │ 超大文件(GB级) │
│ 种子文件          │ ❌ 不适合    │ ✅ 适合     │ 需要CDN分发     │
│ 封面图片          │ ✅ 适合      │ ✅ 适合     │ 小文件          │
│ 临时文件          │ ✅ 适合      │ ❌ 不推荐   │ 用完即删        │
│ 导出文件          │ ✅ 适合      │ ✅ 适合     │ 中等文件        │
└───────────────────┴─────────────┴─────────────┴─────────────────┘
```

#### 3.4.3 存储配置结构

```typescript
type StorageMode = 'local' | 'oss' | 's3';
type FileType = 
  | 'novel_content'   // 小说正文
  | 'chapter_draft'   // 章节草稿
  | 'avatar'          // 用户头像
  | 'cover'          // 小说封面
  | 'knowledge_doc'   // 知识库文档
  | 'ai_model'        // AI模型文件
  | 'torrent'         // 种子文件
  | 'temp'           // 临时文件
  | 'export'         // 导出文件
  | 'chat_attachment'; // 对话附件

interface StorageConfig {
  mode: StorageMode;
  
  local?: {
    basePath: string;
    maxLocalStorageGB: number;
  };
  
  oss?: {
    endpoint: string;
    accessKeyId: string;
    accessKeySecret: string;
    bucket: string;
    region: string;
    cdnDomain?: string;
  };
  
  s3?: {
    endpoint: string;
    accessKeyId: string;
    accessKeySecret: string;
    bucket: string;
    region: string;
    signatureVersion?: 'v4' | 'v2';
    cdnDomain?: string;
  };
  
  policies: {
    [key in FileType]: {
      storage: StorageMode | 'follow_global';
      pathTemplate: string;
    };
  };
}
```

#### 3.4.4 混合存储策略

**全局模式 + 文件类型覆盖**：

```typescript
// 全局配置示例 (hybrid模式)
const storageConfig: StorageConfig = {
  mode: 'hybrid',
  
  // 本地存储配置
  local: {
    basePath: '/data/starrynight/local',
    maxLocalStorageGB: 100
  },
  
  // OSS配置
  oss: {
    endpoint: 'https://oss-cn-hangzhou.aliyuncs.com',
    accessKeyId: 'xxx',
    accessKeySecret: 'xxx',
    bucket: 'starrynight',
    cdnDomain: 'https://cdn.starrynight.com'
  },
  
  // S3配置
  s3: {
    endpoint: 'https://s3.amazonaws.com',
    accessKeyId: 'xxx',
    accessKeySecret: 'xxx',
    bucket: 'starrynight',
    region: 'us-east-1'
  },
  
  // 各文件类型存储策略
  policies: {
    novel_content: { storage: 'oss', pathTemplate: '{userId}/novel/{novelId}/content/' },
    chapter_draft: { storage: 'local', pathTemplate: '{userId}/novel/{novelId}/draft/' },
    avatar: { storage: 'oss', pathTemplate: 'avatar/{userId}/' },
    cover: { storage: 'oss', pathTemplate: 'cover/{novelId}/' },
    knowledge_doc: { storage: 'oss', pathTemplate: '{userId}/knowledge/{libraryId}/' },
    ai_model: { storage: 's3', pathTemplate: 'ai_models/{modelName}/{version}/' },
    torrent: { storage: 'oss', pathTemplate: 'torrents/{category}/' },
    temp: { storage: 'local', pathTemplate: 'temp/{userId}/{sessionId}/' },
    export: { storage: 'follow_global', pathTemplate: 'export/{userId}/{novelId}/' },
    chat_attachment: { storage: 'oss', pathTemplate: 'chat/{userId}/{messageId}/' }
  }
};
```

#### 3.4.5 存储服务接口

```typescript
interface StorageService {
  read(path: string): Promise<Buffer>;
  write(path: string, content: Buffer): Promise<void>;
  delete(path: string): Promise<void>;
  exists(path: string): Promise<boolean>;
  getMetadata(path: string): Promise<FileMetadata>;
  list(prefix: string): Promise<FileMetadata[]>;
  getSignedUrl(path: string, expires: number): Promise<string>;
  copy(sourcePath: string, targetPath: string): Promise<void>;
}

interface FileMetadata {
  path: string;
  size: number;
  contentType: string;
  lastModified: Date;
  etag?: string;
  storageMode?: StorageMode;
}
```

#### 3.4.6 数据迁移方案

```typescript
interface MigrationTask {
  id: string;
  taskNo: string;
  sourceMode: StorageMode;
  targetMode: StorageMode;
  
  scope: {
    fileTypes: FileType[];
    userIds?: string[];
    dateRange?: { start: Date; end: Date };
  };
  
  progress: {
    totalFiles: number;
    migratedFiles: number;
    failedFiles: number;
    totalBytes: number;
    migratedBytes: number;
    currentFile?: string;
    startTime: Date;
    estimatedEndTime?: Date;
  };
  
  config: {
    parallelWorkers: number;
    retryCount: number;
    verifyAfterCopy: boolean;
    deleteAfterVerify: boolean;
  };
  
  status: 'pending' | 'running' | 'paused' | 'completed' | 'failed' | 'cancelled';
  errorMessage?: string;
}

// 迁移流程
async function executeMigration(task: MigrationTask) {
  const sourceStorage = getStorageAdapter(task.sourceMode);
  const targetStorage = getStorageAdapter(task.targetMode);
  
  const files = await scanFiles(task.scope);
  
  for (const file of files) {
    try {
      const content = await sourceStorage.read(file.path);
      const targetPath = transformPath(file.path, task.targetMode);
      await targetStorage.write(targetPath, content);
      
      if (task.config.verifyAfterCopy) {
        const sourceHash = await hash(content);
        const targetHash = await hash(await targetStorage.read(targetPath));
        if (sourceHash !== targetHash) throw new Error('Hash mismatch');
      }
      
      if (task.config.deleteAfterVerify) {
        await sourceStorage.delete(file.path);
      }
      
      task.progress.migratedFiles++;
      task.progress.migratedBytes += file.size;
    } catch (error) {
      task.progress.failedFiles++;
    }
  }
}
```

#### 3.4.7 存储相关数据库表

```sql
-- 存储配置表
CREATE TABLE storage_config (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    config_key      VARCHAR(100) NOT NULL UNIQUE,
    config_value    TEXT COMMENT '配置值(JSON)',
    mode            ENUM('local', 'oss', 's3', 'hybrid') DEFAULT 'local',
    is_active       TINYINT DEFAULT 1,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT='存储配置表';

-- 存储策略表
CREATE TABLE storage_policy (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    file_type       VARCHAR(50) NOT NULL,
    storage_mode    ENUM('local', 'oss', 's3', 'follow_global') NOT NULL,
    path_template   VARCHAR(500),
    max_size        BIGINT,
    enabled         TINYINT DEFAULT 1,
    sort_order      INT DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_file_type (file_type)
) COMMENT='存储策略表';

-- 迁移任务表
CREATE TABLE storage_migration (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    task_no         VARCHAR(50) NOT NULL UNIQUE,
    source_mode     ENUM('local', 'oss', 's3') NOT NULL,
    target_mode     ENUM('local', 'oss', 's3') NOT NULL,
    file_types      VARCHAR(200) COMMENT 'JSON数组',
    status          ENUM('pending', 'running', 'paused', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    total_files     INT DEFAULT 0,
    migrated_files  INT DEFAULT 0,
    failed_files    INT DEFAULT 0,
    total_bytes     BIGINT DEFAULT 0,
    migrated_bytes  BIGINT DEFAULT 0,
    error_log       TEXT,
    started_at      DATETIME,
    completed_at    DATETIME,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) COMMENT='存储迁移任务表';

-- 预置存储策略
INSERT INTO storage_policy (file_type, storage_mode, path_template, sort_order) VALUES
('novel_content', 'follow_global', '{userId}/novel/{novelId}/content/', 1),
('chapter_draft', 'follow_global', '{userId}/novel/{novelId}/draft/', 2),
('avatar', 'follow_global', 'avatar/{userId}/', 3),
('cover', 'follow_global', 'cover/{novelId}/', 4),
('knowledge_doc', 'follow_global', '{userId}/knowledge/{libraryId}/', 5),
('ai_model', 'follow_global', 'ai_models/', 6),
('torrent', 'follow_global', 'torrents/', 7),
('temp', 'local', 'temp/', 8),
('export', 'follow_global', 'export/{userId}/', 9);
```

#### 3.4.8 运营端存储接口

| 接口 | 方法 | 说明 |
|------|------|------|
| `/api/admin/storage/config` | GET | 获取存储配置 |
| `/api/admin/storage/config` | PUT | 更新存储配置 |
| `/api/admin/storage/switch-mode` | POST | 切换存储模式 |
| `/api/admin/storage/migrate` | POST | 开始迁移 |
| `/api/admin/storage/migration/progress` | GET | 获取迁移进度 |
| `/api/admin/storage/migration/cancel` | POST | 取消迁移 |
| `/api/admin/storage/migration/verify` | POST | 验证迁移 |
| `/api/admin/storage/policies` | GET | 获取存储策略 |
| `/api/admin/storage/policies` | PUT | 更新存储策略 |

### 3.5 提示词库

#### 3.4.1 内置模板分类

| 类别 | 模板名称 | 适用场景 |
|------|----------|----------|
| 爆款开头 | 黄金三秒开头 | 新书发布 |
| 爆款开头 | 反转悬念开头 | 悬疑类 |
| 爆款开头 | 情绪爆发开头 | 虐文/甜文 |
| 大纲结构 | 黄金三幕 | 通用 |
| 大纲结构 | 起承转合 | 传统文学 |
| 大纲结构 | 升级打脸 | 爽文 |
| 人物设定 | 反派塑造 | 反派设计 |
| 人物设定 | 女主设定 | 女频 |
| 冲突制造 | 师徒对立 | 仙侠 |
| 冲突制造 | 家族恩怨 | 玄幻 |
| 描写润色 | 电影感画面 | 场景描写 |
| 描写润色 | 情绪渲染 | 心理描写 |

#### 3.4.2 提示词模板结构

```json
{
  "template_id": "outline_golden_three_act",
  "name": "黄金三幕大纲模板",
  "category": "大纲结构",
  "description": "经典的英雄之旅三幕结构，适合大多数网文类型",
  "variables": [
    {
      "name": "genre",
      "type": "string",
      "required": true,
      "description": "小说题材"
    },
    {
      "name": "core_conflict",
      "type": "string",
      "required": true,
      "description": "核心冲突"
    }
  ],
  "prompt_template": "请生成一个{gene}类型的黄金三幕结构大纲...",
  "output_format": "json",
  "version": 1
}
```

### 3.5 角色库

#### 3.5.1 角色档案字段

```json
{
  "character_id": "char_001",
  "basic_info": {
    "name": "姓名",
    "alias": ["别名1", "别名2"],
    "age": "年龄",
    "gender": "性别",
    "appearance": "外貌描述",
    "identity": "身份定位"
  },
  "personality": {
    "mbti": "INTJ",
    "core_desire": "核心欲望",
    "fear": "最大恐惧",
    "verbal_tics": ["口头禅1", "口头禅2"],
    "traits": ["性格特点列表"]
  },
  "abilities": {
    "level": "境界/等级",
    "skills": ["技能列表"],
    "equipment": ["装备列表"],
    "golden_finger": "金手指关联"
  },
  "background": {
    "origin": "出身背景",
    "key_events": ["关键事件编年史"],
    "secrets": ["隐藏秘密"],
    "motivation": "行为动机"
  },
  "growth_arc": {
    "start_state": "初始状态",
    "turning_points": ["转变节点"],
    "end_state": "最终状态"
  },
  "relationships": [
    {
      "target_id": "角色ID",
      "type": "师徒/敌对/暗恋/血缘...",
      "affinity": 75,
      "description": "关系描述"
    }
  ],
  "created_at": "创建时间",
  "updated_at": "更新时间"
}
```

### 3.6 小工具集

#### 3.6.1 功能列表

| 工具 | 功能描述 | 产出格式 |
|------|----------|----------|
| 金手指生成 | 生成系统/老爷爷/重生等外挂设定 | JSON卡片 |
| 书名生成 | 生成10+个爆款风格书名 | 列表+推荐指数 |
| 简介生成 | 生成100-300字网文风格简介 | 文本+标签 |
| 世界观生成 | 生成地理/势力/力量体系等设定 | 结构化文本 |
| 冲突桥段 | 生成特定情境下的矛盾或转折 | 场景片段 |
| 人物速成 | 一句话生成完整角色档案 | JSON角色卡 |
| 扩写仿写 | 学习样本风格后扩写当前段落 | 正文 |

#### 3.6.2 小工具与主流程联动

**联动机制**：
```
素材库(统一存储)
    │
    ├── 金手指卡片 ──┬─→ 大纲(核心驱动) / 角色库(系统角色) / 细纲(激活场景)
    ├── 世界观卡片 ──┼─→ 大纲(前置设定) / 细纲(地点引用)
    ├── 冲突桥段 ────┼─→ 卷纲(核心事件) / 正文(矛盾改写)
    ├── 角色草稿 ────┼─→ 角色库(快速导入) / 大纲(角色绑定)
    └── 风格指纹 ────┴─→ 正文(扩写仿写)
    
主流程各节点工具栏: ⚡金手指 │ 🌍世界观 │ 💥冲突 │ 📖扩写
```

### 3.7 素材库

#### 3.7.1 素材分类

```typescript
type MaterialType =
  | 'golden_finger'    // 金手指
  | 'worldview'        // 世界观
  | 'character_draft'  // 角色草稿
  | 'conflict_idea'    // 冲突桥段
  | 'style_fingerprint' // 风格指纹
  | 'chapter_summary'  // 章节梗概
  | 'custom';          // 自定义
```

#### 3.7.2 素材卡片结构

```json
{
  "material_id": "mat_001",
  "project_id": "项目ID",
  "type": "golden_finger",
  "title": "抽奖系统",
  "content": {
    "system_name": "逆天抽奖系统",
    "rules": ["每日抽奖一次", "保底机制"],
    "limitations": ["每日限10次"],
    "side_effects": ["概率异常"]
  },
  "source": "tool_generated",  // tool_generated | user_created | imported
  "source_tool": "golden_finger_generator",
  "applicable_scene": ["大纲", "细纲", "正文"],
  "tags": ["系统", "都市", "成长"],
  "usage_count": 3,
  "last_used_at": "2026-04-28T10:00:00Z",
  "created_at": "2026-04-28T10:00:00Z"
}
```

### 3.8 阶段式AI对话

#### 3.8.1 对话上下文构建

```typescript
interface AIConversationContext {
  current_node: {
    type: 'outline' | 'volume' | 'chapter_draft' | 'chapter_content',
    id: string,
    content: any,
  };
  project_context: {
    outline_summary: string,
    relevant_characters: Character[],
    relevant_worldview: Worldview[],
    knowledge_chunks: KnowledgeChunk[],
    previous_conversations: ConversationHistory[],
  };
  user_query: string;
}
```

#### 3.8.2 版本历史

```typescript
interface VersionRecord {
  version_id: string;
  node_type: string;
  node_id: string;
  content_before: any;
  content_after: any;
  diff_summary: string;
  conversation_id: string;
  created_at: Date;
  is_applied: boolean;
}
```

---

## 第四部分：数据库设计

### 4.1 ER图

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   sys_user  │────<│    novel    │────<│    volume   │
│     用户     │     │    小说     │     │     卷      │
└─────────────┘     └─────────────┘     └─────────────┘
                          │                   │
                          │                   │
                          ▼                   ▼
                   ┌─────────────┐     ┌─────────────┐
                   │  novel_     │     │  chapter    │
                   │  outline    │     │    章节     │
                   │   大纲      │     └─────────────┘
                   └─────────────┘            │
                                             ▼
                                    ┌─────────────┐
                                    │chapter_draft│
                                    │  章节细纲   │
                                    └─────────────┘

┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│    sys_role │────<│sys_user_role│    │   sys_user │
│     角色     │     └─────────────┘     │     用户     │
└─────────────┘                          └─────────────┘
                                             │
                                             │
┌─────────────┐     ┌─────────────┐          │
│    order    │────<│   sys_user │          │
│     订单     │     └─────────────┘          │
└─────────────┘                               │
                                             ┌─────────────┐
┌─────────────┐     ┌─────────────┐     ┌────│knowledge_lib│
│ ai_template │────<│  sys_user   │◄─── │    │   知识库     │
│  AI模板     │     └─────────────┘     │    └─────────────┘
└─────────────┘                          │          │
                                         │    ┌─────────────┐
┌─────────────┐                    ┌─────┴───>│document_chunk│
│ ai_model    │                    │         │  文档切片     │
│  AI模型     │                    │         └─────────────┘
└─────────────┘                    │
                                   ┌─────────────┐
                              ┌────│    sys_config    │
                              │    │     系统配置     │
                              │    └─────────────────┘
                              │
                         ┌─────────────┐
                         │admin_path_config│
                         │  Admin路径配置  │
                         └─────────────┘
```

### 4.2 表结构

#### 4.2.1 用户相关表

```sql
-- 用户表
CREATE TABLE sys_user (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT '用户ID',
    username        VARCHAR(50) NOT NULL UNIQUE COMMENT '用户名',
    password        VARCHAR(255) NOT NULL COMMENT '密码(BCrypt加密)',
    nickname        VARCHAR(100) COMMENT '昵称',
    email           VARCHAR(100) COMMENT '邮箱',
    phone          VARCHAR(20) COMMENT '手机号',
    avatar          VARCHAR(500) COMMENT '头像URL',
    role            ENUM('USER', 'ADMIN') DEFAULT 'USER' COMMENT '角色',
    points          INT DEFAULT 0 COMMENT '积分余额',
    membership_type ENUM('FREE', 'VIP', 'SVIP') DEFAULT 'FREE' COMMENT '会员类型',
    membership_expire DATETIME COMMENT '会员过期时间',
    is_seeder       TINYINT DEFAULT 0 COMMENT '是否P2P种子(1=是)',
    status          TINYINT DEFAULT 1 COMMENT '状态(0=禁用,1=正常)',
    last_login_at   DATETIME COMMENT '最后登录时间',
    last_login_ip   VARCHAR(50) COMMENT '最后登录IP',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    deleted_at      DATETIME COMMENT '删除时间',
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
) COMMENT='用户表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 角色表
CREATE TABLE sys_role (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT '角色ID',
    name            VARCHAR(50) NOT NULL UNIQUE COMMENT '角色名称',
    code            VARCHAR(50) NOT NULL UNIQUE COMMENT '角色代码',
    description     VARCHAR(200) COMMENT '角色描述',
    permissions     TEXT COMMENT '权限JSON',
    status          TINYINT DEFAULT 1 COMMENT '状态',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间'
) COMMENT='角色表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户角色关联表
CREATE TABLE sys_user_role (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id         BIGINT NOT NULL COMMENT '用户ID',
    role_id         BIGINT NOT NULL COMMENT '角色ID',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_role (user_id, role_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id)
) COMMENT='用户角色关联表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4.2.2 创作相关表

```sql
-- 小说表
CREATE TABLE novel (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT '小说ID',
    user_id         BIGINT NOT NULL COMMENT '作者ID',
    title           VARCHAR(200) NOT NULL COMMENT '小说标题',
    subtitle        VARCHAR(200) COMMENT '副标题',
    genre           VARCHAR(50) COMMENT '题材分类',
    tags            VARCHAR(500) COMMENT '标签(JSON数组)',
    cover_image     VARCHAR(500) COMMENT '封面图片URL',
    synopsis        TEXT COMMENT '简介',
    synopsis_short  VARCHAR(300) COMMENT '短简介(用于推荐)',
    outline         JSON COMMENT '大纲(JSON)',
    outline_template VARCHAR(50) COMMENT '大纲模板类型',
    target_word_count INT DEFAULT 300000 COMMENT '目标字数',
    actual_word_count INT DEFAULT 0 COMMENT '实际字数',
    status          ENUM('DRAFT', 'WRITING', 'COMPLETED', 'PUBLISHED') DEFAULT 'DRAFT' COMMENT '状态',
    is_deleted      TINYINT DEFAULT 0 COMMENT '是否删除',
    version         INT DEFAULT 1 COMMENT '版本号',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_genre (genre),
    INDEX idx_created_at (created_at),
    FULLTEXT idx_title (title)
) COMMENT='小说表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 卷表
CREATE TABLE volume (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT '卷ID',
    novel_id        BIGINT NOT NULL COMMENT '小说ID',
    volume_no       INT NOT NULL COMMENT '卷序号',
    title           VARCHAR(200) COMMENT '卷标题',
    subtitle        VARCHAR(200) COMMENT '卷副标题',
    summary         JSON COMMENT '卷纲(JSON)',
    sort_order      INT DEFAULT 0 COMMENT '排序',
    word_count      INT DEFAULT 0 COMMENT '字数',
    status          ENUM('PLANNING', 'WRITING', 'COMPLETED') DEFAULT 'PLANNING' COMMENT '状态',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_novel_id (novel_id),
    INDEX idx_volume_no (volume_no)
) COMMENT='卷表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 章节表
CREATE TABLE chapter (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT '章节ID',
    volume_id       BIGINT NOT NULL COMMENT '卷ID',
    chapter_no      INT NOT NULL COMMENT '章节序号',
    title           VARCHAR(200) COMMENT '章节标题',
    content         LONGTEXT COMMENT '正文内容',
    draft           JSON COMMENT '章节细纲',
    summary         VARCHAR(500) COMMENT '章节梗概',
    word_count      INT DEFAULT 0 COMMENT '字数',
    status          ENUM('DRAFT', 'WRITING', 'COMPLETED', 'PUBLISHED') DEFAULT 'DRAFT' COMMENT '状态',
    is_deleted      TINYINT DEFAULT 0 COMMENT '是否删除',
    version         INT DEFAULT 1 COMMENT '版本号',
    ai_model_used   VARCHAR(100) COMMENT '使用的AI模型',
    style_fingerprint JSON COMMENT '风格指纹',
    published_at    DATETIME COMMENT '发布时间',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_volume_id (volume_id),
    INDEX idx_chapter_no (chapter_no),
    INDEX idx_status (status),
    FULLTEXT idx_content (content)
) COMMENT='章节表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 章节版本历史表
CREATE TABLE chapter_version (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    chapter_id      BIGINT NOT NULL COMMENT '章节ID',
    version_no      INT NOT NULL COMMENT '版本号',
    title           VARCHAR(200) COMMENT '章节标题',
    content         LONGTEXT COMMENT '正文内容',
    draft           JSON COMMENT '章节细纲',
    change_summary  VARCHAR(500) COMMENT '变更摘要',
    created_by      BIGINT COMMENT '创建人',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_chapter_id (chapter_id),
    INDEX idx_version_no (chapter_id, version_no)
) COMMENT='章节版本历史表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 小说大纲版本表
CREATE TABLE outline_version (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    novel_id        BIGINT NOT NULL COMMENT '小说ID',
    version_no      INT NOT NULL COMMENT '版本号',
    outline         JSON NOT NULL COMMENT '大纲内容',
    change_summary  VARCHAR(500) COMMENT '变更摘要',
    conversation_id VARCHAR(100) COMMENT '关联对话ID',
    created_by      BIGINT COMMENT '创建人',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_novel_id (novel_id)
) COMMENT='大纲版本历史表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4.2.3 角色库相关表

```sql
-- 角色表
CREATE TABLE `character` (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT COMMENT '角色ID',
    novel_id        BIGINT NOT NULL COMMENT '小说ID',
    name            VARCHAR(100) NOT NULL COMMENT '角色名',
    alias           VARCHAR(200) COMMENT '别名(JSON数组)',
    age             VARCHAR(50) COMMENT '年龄',
    gender          ENUM('MALE', 'FEMALE', 'OTHER') COMMENT '性别',
    appearance      TEXT COMMENT '外貌描述',
    identity        VARCHAR(200) COMMENT '身份定位',
    personality     JSON COMMENT '性格特征',
    abilities       JSON COMMENT '能力设定',
    background      JSON COMMENT '背景故事',
    growth_arc      JSON COMMENT '成长曲线',
    mbti            VARCHAR(10) COMMENT 'MBTI',
    core_desire     VARCHAR(200) COMMENT '核心欲望',
    fear            VARCHAR(200) COMMENT '最大恐惧',
    verbal_tics      VARCHAR(500) COMMENT '口头禅(JSON数组)',
    is_main         TINYINT DEFAULT 0 COMMENT '是否主角(1=是)',
    is_deleted      TINYINT DEFAULT 0 COMMENT '是否删除',
    avatar_url      VARCHAR(500) COMMENT '角色头像',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_novel_id (novel_id),
    INDEX idx_name (name),
    FULLTEXT idx_background (background)
) COMMENT='角色表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 角色关系表
CREATE TABLE character_relationship (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    novel_id        BIGINT NOT NULL COMMENT '小说ID',
    source_char_id  BIGINT NOT NULL COMMENT '源角色ID',
    target_char_id  BIGINT NOT NULL COMMENT '目标角色ID',
    relationship_type VARCHAR(50) COMMENT '关系类型(师徒/敌对/暗恋等)',
    affinity        INT DEFAULT 50 COMMENT '亲密度(0-100)',
    description     VARCHAR(500) COMMENT '关系描述',
    is_deleted      TINYINT DEFAULT 0 COMMENT '是否删除',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_novel_id (novel_id),
    INDEX idx_source_char (source_char_id),
    INDEX idx_target_char (target_char_id)
) COMMENT='角色关系表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4.2.4 知识库相关表

```sql
-- 知识库表
CREATE TABLE knowledge_library (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id         BIGINT NOT NULL COMMENT '用户ID',
    name            VARCHAR(200) NOT NULL COMMENT '文档名称',
    source_type     ENUM('UPLOAD', 'IMPORT', 'GENERATED') DEFAULT 'UPLOAD' COMMENT '来源类型',
    file_type       VARCHAR(20) COMMENT '文件类型',
    file_size       BIGINT COMMENT '文件大小(字节)',
    file_url        VARCHAR(500) COMMENT '文件存储路径',
    source_url      VARCHAR(500) COMMENT '来源URL',
    tags            VARCHAR(500) COMMENT '标签(JSON数组)',
    summary         TEXT COMMENT '文档摘要',
    reference_mode  ENUM('GLOBAL', 'SCENE') DEFAULT 'SCENE' COMMENT '参考模式',
    is_public       TINYINT DEFAULT 0 COMMENT '是否公开(1=公开)',
    status          ENUM('PROCESSING', 'READY', 'ERROR') DEFAULT 'PROCESSING' COMMENT '处理状态',
    chunk_count     INT DEFAULT 0 COMMENT '切片数量',
    error_message   VARCHAR(500) COMMENT '错误信息',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_reference_mode (reference_mode)
) COMMENT='知识库表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 文档切片表
CREATE TABLE document_chunk (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    library_id      BIGINT NOT NULL COMMENT '知识库ID',
    content         TEXT NOT NULL COMMENT '切片内容',
    content_hash    VARCHAR(64) COMMENT '内容哈希(SHA256)',
    metadata        JSON COMMENT '元数据(页码/章节等)',
    vector_id       VARCHAR(100) COMMENT '向量数据库ID',
    token_count     INT COMMENT 'Token数量',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_library_id (library_id),
    INDEX idx_content_hash (content_hash),
    FULLTEXT idx_content (content)
) COMMENT='文档切片表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 知识库引用表(记录项目中引用了哪些知识)
CREATE TABLE knowledge_reference (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    library_id      BIGINT NOT NULL COMMENT '知识库ID',
    novel_id        BIGINT COMMENT '小说ID(可空,全局知识无关联)',
    used_in_node    VARCHAR(50) COMMENT '使用的节点类型(outline/volume/chapter)',
    used_in_node_id BIGINT COMMENT '使用的节点ID',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_library_id (library_id),
    INDEX idx_novel_id (novel_id),
    INDEX idx_node (used_in_node, used_in_node_id)
) COMMENT='知识库引用表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4.2.5 提示词库相关表

```sql
-- AI模板表
CREATE TABLE ai_template (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL COMMENT '模板名称',
    code            VARCHAR(100) NOT NULL UNIQUE COMMENT '模板代码',
    category        VARCHAR(50) NOT NULL COMMENT '模板分类',
    description     VARCHAR(500) COMMENT '模板描述',
    prompt_template TEXT NOT NULL COMMENT '提示词模板',
    variables       JSON COMMENT '变量定义',
    output_format   VARCHAR(50) DEFAULT 'json' COMMENT '输出格式',
    model_name      VARCHAR(100) COMMENT '推荐模型',
    parameters      JSON COMMENT '生成参数(温度/max_tokens等)',
    is_system       TINYINT DEFAULT 0 COMMENT '是否系统模板(1=系统)',
    is_public       TINYINT DEFAULT 0 COMMENT '是否公开',
    user_id         BIGINT COMMENT '创建用户(系统模板为空)',
    usage_count     INT DEFAULT 0 COMMENT '使用次数',
    rating          DECIMAL(3,2) DEFAULT 0 COMMENT '平均评分',
    status          TINYINT DEFAULT 1 COMMENT '状态(0=禁用,1=启用)',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_category (category),
    INDEX idx_is_system (is_system),
    INDEX idx_user_id (user_id)
) COMMENT='AI模板表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4.2.6 素材库相关表

```sql
-- 素材表
CREATE TABLE material (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id         BIGINT NOT NULL COMMENT '用户ID',
    novel_id        BIGINT COMMENT '关联小说ID(可空)',
    type            VARCHAR(50) NOT NULL COMMENT '素材类型',
    title           VARCHAR(200) NOT NULL COMMENT '素材标题',
    content         JSON NOT NULL COMMENT '素材内容',
    source          ENUM('TOOL_GENERATED', 'USER_CREATED', 'IMPORTED') DEFAULT 'USER_CREATED' COMMENT '来源',
    source_tool     VARCHAR(100) COMMENT '来源工具',
    applicable_scene VARCHAR(200) COMMENT '适用场景(JSON数组)',
    tags            VARCHAR(500) COMMENT '标签(JSON数组)',
    usage_count     INT DEFAULT 0 COMMENT '使用次数',
    last_used_at    DATETIME COMMENT '最后使用时间',
    is_deleted      TINYINT DEFAULT 0 COMMENT '是否删除',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_user_id (user_id),
    INDEX idx_novel_id (novel_id),
    INDEX idx_type (type),
    INDEX idx_source (source)
) COMMENT='素材表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4.2.7 订单相关表

```sql
-- 订单表
CREATE TABLE `order` (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    order_no        VARCHAR(50) NOT NULL UNIQUE COMMENT '订单号',
    user_id         BIGINT NOT NULL COMMENT '用户ID',
    type            ENUM('POINTS_RECHARGE', 'MEMBERSHIP', 'TEMPLATE') NOT NULL COMMENT '订单类型',
    product_id      BIGINT COMMENT '商品ID',
    product_name    VARCHAR(200) COMMENT '商品名称',
    amount          DECIMAL(10,2) NOT NULL COMMENT '订单金额',
    points          INT COMMENT '充值积分',
    payment_method  VARCHAR(50) COMMENT '支付方式',
    payment_status  ENUM('PENDING', 'PAID', 'REFUNDED', 'FAILED') DEFAULT 'PENDING' COMMENT '支付状态',
    paid_at         DATETIME COMMENT '支付时间',
    expire_at       DATETIME COMMENT '过期时间',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_user_id (user_id),
    INDEX idx_order_no (order_no),
    INDEX idx_type (type),
    INDEX idx_payment_status (payment_status)
) COMMENT='订单表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 积分变动记录表
CREATE TABLE points_log (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id         BIGINT NOT NULL COMMENT '用户ID',
    type            ENUM('RECHARGE', 'CONSUME', 'REFUND', 'BONUS') NOT NULL COMMENT '变动类型',
    points          INT NOT NULL COMMENT '变动积分(正负)',
    balance         INT NOT NULL COMMENT '变动后余额',
    source          VARCHAR(50) COMMENT '来源(AI生成/导出等)',
    source_id       BIGINT COMMENT '关联ID',
    description     VARCHAR(200) COMMENT '描述',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_user_id (user_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) COMMENT='积分变动记录表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4.2.8 系统配置相关表

```sql
-- 系统配置表
CREATE TABLE sys_config (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    config_key      VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
    config_value    TEXT COMMENT '配置值',
    type            VARCHAR(50) DEFAULT 'string' COMMENT '类型(string/int/json)',
    description     VARCHAR(500) COMMENT '配置描述',
    group_name      VARCHAR(50) COMMENT '配置分组',
    is_encrypted    TINYINT DEFAULT 0 COMMENT '是否加密',
    is_public       TINYINT DEFAULT 0 COMMENT '是否公开(前端可读)',
    sort_order      INT DEFAULT 0 COMMENT '排序',
    status          TINYINT DEFAULT 1 COMMENT '状态(0=禁用,1=启用)',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_config_key (config_key),
    INDEX idx_group_name (group_name)
) COMMENT='系统配置表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 预置配置数据
INSERT INTO sys_config (config_key, config_value, type, description, group_name, is_public) VALUES
('admin_path', 'admin', 'string', '运营端访问路径', 'system', 0),
('torrent_seeder_url', 'https://cdn.starrynight.com/torrents/', 'string', 'P2P种子服务器地址', 'p2p', 1),
('default_ai_model', 'qwen2-0.5b-instruct', 'string', '默认AI模型', 'ai', 1),
('max_upload_file_size', '52428800', 'int', '最大上传文件大小(字节)', 'system', 1),
('user_knowledge_limit', '1073741824', 'int', '普通用户知识库容量限制(字节)', 'system', 1),
('vip_knowledge_limit', '10737418240', 'int', 'VIP用户知识库容量限制(字节)', 'system', 1),
('storage_mode', 'local', 'string', '全局存储模式(local/oss/s3/hybrid)', 'storage', 1);

-- AI模型表
CREATE TABLE ai_model (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    model_name      VARCHAR(100) NOT NULL COMMENT '模型名称',
    model_type      VARCHAR(50) NOT NULL COMMENT '模型类型(outline/chapter/nlp)',
    display_name    VARCHAR(100) COMMENT '显示名称',
    description     VARCHAR(500) COMMENT '模型描述',
    torrent_info    TEXT COMMENT '种子信息/磁力链接',
    file_size       BIGINT COMMENT '文件大小(字节)',
    checksum        VARCHAR(64) COMMENT 'SHA256校验',
    version         VARCHAR(20) COMMENT '模型版本',
    min_ram         INT COMMENT '最低内存要求(MB)',
    parameters      JSON COMMENT '模型参数配置',
    status          ENUM('PENDING', 'READY', 'DEPRECATED') DEFAULT 'PENDING' COMMENT '状态',
    is_default      TINYINT DEFAULT 0 COMMENT '是否默认模型',
    download_count  INT DEFAULT 0 COMMENT '下载次数',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_model_type (model_type),
    INDEX idx_status (status)
) COMMENT='AI模型表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 操作日志表
CREATE TABLE operation_log (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id         BIGINT COMMENT '操作用户ID',
    username        VARCHAR(50) COMMENT '操作用户名',
    module          VARCHAR(50) COMMENT '操作模块',
    action          VARCHAR(100) COMMENT '操作动作',
    target_type     VARCHAR(50) COMMENT '操作对象类型',
    target_id       BIGINT COMMENT '操作对象ID',
    target_name     VARCHAR(200) COMMENT '操作对象名称',
    request_method  VARCHAR(10) COMMENT '请求方法',
    request_url     VARCHAR(500) COMMENT '请求URL',
    request_params  TEXT COMMENT '请求参数',
    response_code   VARCHAR(10) COMMENT '响应码',
    ip_address      VARCHAR(50) COMMENT 'IP地址',
    user_agent      VARCHAR(500) COMMENT 'User-Agent',
    error_message   TEXT COMMENT '错误信息',
    execution_time  INT COMMENT '执行时间(毫秒)',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '操作时间',
    INDEX idx_user_id (user_id),
    INDEX idx_module (module),
    INDEX idx_created_at (created_at)
) COMMENT='操作日志表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 4.2.9 AI对话相关表

```sql
-- AI对话会话表
CREATE TABLE ai_conversation (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    conversation_no VARCHAR(100) NOT NULL UNIQUE COMMENT '会话编号',
    user_id         BIGINT NOT NULL COMMENT '用户ID',
    novel_id        BIGINT COMMENT '关联小说ID',
    node_type       VARCHAR(50) COMMENT '节点类型(outline/volume/chapter)',
    node_id         BIGINT COMMENT '节点ID',
    title           VARCHAR(200) COMMENT '会话标题',
    status          ENUM('ACTIVE', 'ARCHIVED') DEFAULT 'ACTIVE' COMMENT '状态',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_user_id (user_id),
    INDEX idx_novel_id (novel_id),
    INDEX idx_node (node_type, node_id)
) COMMENT='AI对话会话表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- AI对话消息表
CREATE TABLE ai_message (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    conversation_id BIGINT NOT NULL COMMENT '会话ID',
    role            ENUM('USER', 'ASSISTANT', 'SYSTEM') NOT NULL COMMENT '角色',
    content         TEXT NOT NULL COMMENT '消息内容',
    model_name      VARCHAR(100) COMMENT '使用的模型',
    tokens_used     INT COMMENT '消耗Token数',
    references      JSON COMMENT '引用的知识/素材',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_created_at (created_at)
) COMMENT='AI对话消息表' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 第五部分：存储架构设计

### 5.1 存储模式类型

| 模式 | 说明 | 适用场景 |
|------|------|----------|
| `local` | 本地文件系统 | 单机部署、个人使用 |
| `oss` | 阿里云OSS | 国内生产环境 |
| `s3` | Amazon S3 / 兼容S3服务 | 海外AWS、自建MinIO |
| `hybrid` | 混合存储模式 | 同时使用多种存储 |

### 5.2 数据分类与存储适配

```
┌─────────────────────────────────────────────────────────────────┐
│                        数据分类矩阵                              │
├───────────────────┬─────────────┬─────────────┬─────────────────┤
│ 数据类型           │ 本地存储     │ OSS/S3      │ 说明            │
├───────────────────┼─────────────┼─────────────┼─────────────────┤
│ 小说正文          │ ✅ 适合      │ ✅ 适合     │ 大文件          │
│ 章节草稿          │ ✅ 适合      │ ✅ 适合     │ 中等文件        │
│ 用户头像          │ ✅ 适合      │ ✅ 适合     │ 小文件          │
│ 知识库文档        │ ✅ 适合      │ ✅ 适合     │ 大文件          │
│ AI模型文件        │ ⚠️ 可用      │ ✅ 最佳     │ 超大文件(GB级) │
│ 种子文件          │ ❌ 不适合    │ ✅ 适合     │ 需要CDN分发     │
│ 封面图片          │ ✅ 适合      │ ✅ 适合     │ 小文件          │
│ 临时文件          │ ✅ 适合      │ ❌ 不推荐   │ 用完即删        │
│ 导出文件          │ ✅ 适合      │ ✅ 适合     │ 中等文件        │
│ 对话附件          │ ✅ 适合      │ ✅ 适合     │ 中等文件        │
└───────────────────┴─────────────┴─────────────┴─────────────────┘
```

### 5.3 存储配置结构

```typescript
type StorageMode = 'local' | 'oss' | 's3';
type FileType = 
  | 'novel_content'   // 小说正文
  | 'chapter_draft'   // 章节草稿
  | 'avatar'          // 用户头像
  | 'cover'          // 小说封面
  | 'knowledge_doc'   // 知识库文档
  | 'ai_model'        // AI模型文件
  | 'torrent'         // 种子文件
  | 'temp'           // 临时文件
  | 'export'         // 导出文件
  | 'chat_attachment'; // 对话附件

interface StorageConfig {
  mode: StorageMode;
  
  local?: {
    basePath: string;
    maxLocalStorageGB: number;
  };
  
  oss?: {
    endpoint: string;
    accessKeyId: string;
    accessKeySecret: string;
    bucket: string;
    region: string;
    cdnDomain?: string;
  };
  
  s3?: {
    endpoint: string;
    accessKeyId: string;
    accessKeySecret: string;
    bucket: string;
    region: string;
    signatureVersion?: 'v4' | 'v2';
    cdnDomain?: string;
  };
  
  policies: {
    [key in FileType]: {
      storage: StorageMode | 'follow_global';
      pathTemplate: string;
    };
  };
}
```

### 5.4 混合存储策略

**混合存储模式**：支持不同类型文件使用不同存储，同时保留全局默认配置。

```
┌─────────────────────────────────────────────────────────────────┐
│                    混合存储架构                                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  用户请求 → 存储策略路由 → 适配对应存储服务                        │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │              全局默认存储: oss                           │    │
│  │                                                         │    │
│  │   文件类型策略覆盖:                                      │    │
│  │   ├── temp      → local (临时文件本地优先)               │    │
│  │   ├── torrent   → oss (需要CDN分发)                     │    │
│  │   ├── ai_model  → oss (大文件云端存储)                  │    │
│  │   └── avatar    → follow_global (跟随全局)               │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  读取流程:                                                       │
│  1. 根据文件类型查找存储策略                                      │
│  2. 获取文件元数据(含实际存储位置)                                │
│  3. 调用对应存储服务读取                                         │
│                                                                  │
│  写入流程:                                                       │
│  1. 根据文件类型查找存储策略                                      │
│  2. 确定目标存储服务                                            │
│  3. 上传到对应存储                                               │
│  4. 保存文件元数据(含存储位置标识)                               │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 5.5 存储路径模板

```typescript
const PATH_TEMPLATES = {
  novel_content: '{userId}/novel/{novelId}/content/{timestamp}.json',
  chapter_draft: '{userId}/novel/{novelId}/draft/ch_{chapterNo}.json',
  avatar: 'avatar/{userId}/{timestamp}.{ext}',
  cover: 'cover/{novelId}/{timestamp}.{ext}',
  knowledge_doc: '{userId}/knowledge/{libraryId}/{docId}/{filename}',
  ai_model: 'ai_models/{modelName}/{version}/{filename}',
  torrent: 'torrents/{category}/{filename}',
  temp: 'temp/{userId}/{sessionId}/{filename}',
  export: 'export/{userId}/{novelId}/{filename}',
  chat_attachment: 'attachment/{userId}/{conversationId}/{filename}',
};
```

### 5.6 存储服务接口

```typescript
interface StorageService {
  mode: StorageMode;
  
  read(path: string): Promise<Buffer>;
  write(path: string, content: Buffer): Promise<void>;
  delete(path: string): Promise<void>;
  exists(path: string): Promise<boolean>;
  getMetadata(path: string): Promise<FileMetadata>;
  list(prefix: string): Promise<FileMetadata[]>;
  getSignedUrl(path: string, expires: number): Promise<string>;
  copy(sourcePath: string, targetPath: string): Promise<void>;
}

interface FileMetadata {
  path: string;
  storageMode: StorageMode;
  size: number;
  contentType: string;
  lastModified: Date;
  etag?: string;
  actualStorage?: 'local' | 'oss' | 's3';  // 混合存储时标识实际存储位置
}
```

### 5.7 数据迁移方案

```typescript
interface MigrationTask {
  id: string;
  taskNo: string;
  sourceMode: StorageMode;
  targetMode: StorageMode;
  
  scope: {
    fileTypes: FileType[];
    userIds?: string[];
    dateRange?: { start: Date; end: Date; };
  };
  
  progress: {
    totalFiles: number;
    migratedFiles: number;
    failedFiles: number;
    totalBytes: number;
    migratedBytes: number;
    currentFile?: string;
    startTime: Date;
    estimatedEndTime?: Date;
  };
  
  config: {
    parallelWorkers: number;
    retryCount: number;
    verifyAfterCopy: boolean;
    deleteAfterVerify: boolean;
  };
  
  status: 'pending' | 'running' | 'paused' | 'completed' | 'failed' | 'cancelled';
  errorMessage?: string;
}

interface MigrationProgress {
  taskId: string;
  status: MigrationTask['status'];
  progress: MigrationTask['progress'];
  currentFile?: string;
  errorMessage?: string;
}
```

**迁移流程**：
```
配置验证 → 创建任务 → 后台执行 → 进度追踪 → 切换生效 → 清理源文件
```

### 5.8 存储相关数据库表

```sql
-- 存储配置表
CREATE TABLE storage_config (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    config_key      VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
    config_value    TEXT COMMENT '配置值(JSON)',
    mode            ENUM('local', 'oss', 's3') DEFAULT 'local' COMMENT '当前模式',
    is_active       TINYINT DEFAULT 1 COMMENT '是否激活',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT='存储配置表';

-- 存储策略表
CREATE TABLE storage_policy (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    file_type       VARCHAR(50) NOT NULL COMMENT '文件类型',
    storage_mode    ENUM('local', 'oss', 's3', 'follow_global') NOT NULL COMMENT '存储模式',
    path_template   VARCHAR(500) COMMENT '路径模板',
    max_size        BIGINT COMMENT '单文件大小限制',
    enabled         TINYINT DEFAULT 1 COMMENT '是否启用',
    sort_order      INT DEFAULT 0 COMMENT '排序',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_file_type (file_type)
) COMMENT='存储策略表';

-- 文件元数据表
CREATE TABLE file_metadata (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    file_key        VARCHAR(255) NOT NULL COMMENT '文件标识',
    file_type       VARCHAR(50) NOT NULL COMMENT '文件类型',
    storage_mode    ENUM('local', 'oss', 's3') NOT NULL COMMENT '实际存储位置',
    file_path       VARCHAR(500) NOT NULL COMMENT '存储路径',
    file_name       VARCHAR(255) COMMENT '原始文件名',
    file_size       BIGINT DEFAULT 0 COMMENT '文件大小',
    content_type    VARCHAR(100) COMMENT 'MIME类型',
    user_id         BIGINT COMMENT '所属用户',
    related_id      BIGINT COMMENT '关联ID(如小说ID/知识库ID)',
    etag            VARCHAR(64) COMMENT '云存储ETag',
    status          TINYINT DEFAULT 1 COMMENT '状态(0=删除,1=正常)',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_file_key (file_key),
    INDEX idx_file_type (file_type),
    INDEX idx_user_id (user_id),
    INDEX idx_storage_mode (storage_mode)
) COMMENT='文件元数据表';

-- 迁移任务表
CREATE TABLE storage_migration (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    task_no         VARCHAR(50) NOT NULL UNIQUE COMMENT '任务编号',
    source_mode     ENUM('local', 'oss', 's3') NOT NULL COMMENT '源存储',
    target_mode     ENUM('local', 'oss', 's3') NOT NULL COMMENT '目标存储',
    file_types      VARCHAR(200) COMMENT '迁移文件类型(JSON数组)',
    status          ENUM('pending', 'running', 'paused', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    total_files     INT DEFAULT 0 COMMENT '总文件数',
    migrated_files  INT DEFAULT 0 COMMENT '已迁移数',
    failed_files    INT DEFAULT 0 COMMENT '失败数',
    total_bytes     BIGINT DEFAULT 0 COMMENT '总字节数',
    migrated_bytes  BIGINT DEFAULT 0 COMMENT '已迁移字节',
    error_log       TEXT COMMENT '错误日志',
    started_at      DATETIME COMMENT '开始时间',
    completed_at    DATETIME COMMENT '完成时间',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) COMMENT='存储迁移任务表';

-- 预置存储策略
INSERT INTO storage_policy (file_type, storage_mode, path_template, sort_order) VALUES
('novel_content', 'follow_global', '{userId}/novel/{novelId}/content/', 1),
('chapter_draft', 'follow_global', '{userId}/novel/{novelId}/draft/', 2),
('avatar', 'follow_global', 'avatar/{userId}/', 3),
('cover', 'follow_global', 'cover/{novelId}/', 4),
('knowledge_doc', 'follow_global', '{userId}/knowledge/{libraryId}/', 5),
('ai_model', 'follow_global', 'ai_models/', 6),
('torrent', 'oss', 'torrents/', 7),
('temp', 'local', 'temp/', 8),
('export', 'follow_global', 'export/{userId}/', 9),
('chat_attachment', 'follow_global', 'attachment/{userId}/', 10);
```

### 5.9 运营端存储API

| 接口路径 | 方法 | 说明 |
|---------|------|------|
| `/api/admin/storage/config` | GET | 获取存储配置 |
| `/api/admin/storage/config` | PUT | 更新存储配置 |
| `/api/admin/storage/switch-mode` | POST | 切换存储模式 |
| `/api/admin/storage/policy` | GET | 获取存储策略列表 |
| `/api/admin/storage/policy` | PUT | 更新存储策略 |
| `/api/admin/storage/migrate` | POST | 创建迁移任务 |
| `/api/admin/storage/migration/progress` | GET | 获取迁移进度 |
| `/api/admin/storage/migration/cancel` | POST | 取消迁移 |
| `/api/admin/storage/migration/verify` | POST | 验证迁移 |
| `/api/admin/storage/files` | GET | 文件列表(可筛选存储类型) |

---

## 第六部分：消息队列架构设计

### 6.1 RabbitMQ支持

#### 6.1.1 队列配置结构

```typescript
interface RabbitMQConfig {
  enabled: boolean;
  host: string;
  port: number;
  username: string;
  password: string;
  virtualHost: string;
  heartbeat: number;          // 心跳间隔(秒)
  connectionTimeout: number;   // 连接超时(毫秒)
  
  // 集群配置
  cluster?: {
    nodes: {
      host: string;
      port: number;
      weight?: number;
    }[];
    automaticFailover: boolean;
  };
  
  // 队列策略
  policies: QueuePolicy[];
  
  // 消费者配置
  consumer: {
    prefetchCount: number;     // 预取数量
    autoAck: boolean;
    retryAttempts: number;     // 重试次数
    retryDelay: number;        // 重试延迟(毫秒)
  };
}

interface QueuePolicy {
  name: string;
  pattern: string;             // 队列匹配模式
  priority?: number;
  maxLength?: number;          // 最大队列长度
  messageTtl?: number;         // 消息TTL(毫秒)
  deadLetterExchange?: string; // 死信交换机
  deadLetterRoutingKey?: string;
}
```

#### 6.1.2 队列定义

```typescript
// 系统队列定义
const SYSTEM_QUEUES = {
  // AI任务队列
  ai_outline_generate: {
    name: 'ai.outline.generate',
    exchange: 'ai.direct',
    routingKey: 'outline.generate',
    description: '大纲生成任务',
    maxConsumers: 10,
    priority: 'high'
  },
  
  ai_chapter_expand: {
    name: 'ai.chapter.expand',
    exchange: 'ai.direct',
    routingKey: 'chapter.expand',
    description: '正文扩写任务',
    maxConsumers: 5,
    priority: 'normal'
  },
  
  ai_style_analyze: {
    name: 'ai.style.analyze',
    exchange: 'ai.direct',
    routingKey: 'style.analyze',
    description: '风格分析任务',
    maxConsumers: 3,
    priority: 'low'
  },
  
  // 文档处理队列
  doc_upload_process: {
    name: 'doc.upload.process',
    exchange: 'doc.direct',
    routingKey: 'upload.process',
    description: '文档上传处理',
    maxConsumers: 5,
    priority: 'normal'
  },
  
  doc_chunk_vectorize: {
    name: 'doc.chunk.vectorize',
    exchange: 'doc.direct',
    routingKey: 'chunk.vectorize',
    description: '文档切片向量化',
    maxConsumers: 3,
    priority: 'low'
  },
  
  // 系统任务队列
  sys_notification: {
    name: 'sys.notification',
    exchange: 'sys.direct',
    routingKey: 'notification.#',
    description: '系统通知',
    maxConsumers: 2,
    priority: 'high'
  },
  
  sys_email_send: {
    name: 'sys.email.send',
    exchange: 'sys.direct',
    routingKey: 'email.send',
    description: '邮件发送',
    maxConsumers: 5,
    priority: 'normal'
  },
  
  // 数据导出队列
  export_task: {
    name: 'export.task',
    exchange: 'export.direct',
    routingKey: 'task.create',
    description: '数据导出任务',
    maxConsumers: 3,
    priority: 'normal'
  },
  
  // 存储任务队列
  storage_migration: {
    name: 'storage.migration',
    exchange: 'storage.direct',
    routingKey: 'migration.task',
    description: '存储迁移任务',
    maxConsumers: 2,
    priority: 'low'
  },
};

// 交换机定义
const EXCHANGES = {
  ai: { name: 'ai.direct', type: 'direct' },
  doc: { name: 'doc.direct', type: 'direct' },
  sys: { name: 'sys.direct', type: 'topic' },
  export: { name: 'export.direct', type: 'direct' },
  storage: { name: 'storage.direct', type: 'direct' },
  dlx: { name: 'dlx.fanout', type: 'fanout' },  // 死信交换机
};
```

#### 6.1.3 消息格式

```typescript
interface QueueMessage<T = any> {
  messageId: string;           // 消息唯一ID
  timestamp: number;           // 时间戳
  headers?: Record<string, any>;
  body: T;
  properties: {
    contentType: string;
    contentEncoding?: string;
    priority?: number;
    expiration?: string;       // 过期时间
    replyTo?: string;         // 回复队列
    correlationId?: string;   // 关联ID(用于RPC)
  };
}

interface AITaskMessage extends QueueMessage {
  body: {
    taskType: 'outline' | 'chapter_expand' | 'style_analyze' | 'consistency_check';
    userId: number;
    novelId?: number;
    params: Record<string, any>;
    callbackUrl?: string;      // 回调地址
  };
}

interface DocProcessMessage extends QueueMessage {
  body: {
    processType: 'chunk' | 'vectorize' | 'parse';
    libraryId: number;
    documentId: number;
    filePath: string;
    options?: Record<string, any>;
  };
}
```

#### 6.1.4 消费者服务接口

```typescript
interface MessageConsumer {
  queue: string;
  handler: (message: QueueMessage) => Promise<void>;
  options?: {
    prefetch?: number;
    retry?: boolean;
    deadLetter?: boolean;
  };
}

interface PublisherService {
  publish(exchange: string, routingKey: string, message: QueueMessage): Promise<void>;
  publishRPC(exchange: string, routingKey: string, message: QueueMessage, timeout: number): Promise<any>;
  batchPublish(messages: Array<{exchange: string; routingKey: string; message: QueueMessage}>): Promise<void>;
}

interface QueueManagementService {
  createQueue(name: string, options?: QueueOptions): Promise<void>;
  deleteQueue(name: string): Promise<void>;
  purgeQueue(name: string): Promise<number>;  // 返回清除的消息数
  getQueueInfo(name: string): Promise<QueueInfo>;
  getQueueStats(): Promise<QueueStats>;
}

interface QueueInfo {
  name: string;
  messages: number;
  consumers: number;
  memory: number;
  state: 'running' | 'idle' | 'syncing';
}

interface QueueStats {
  totalMessages: number;
  totalConsumers: number;
  messageRates: {
    publish: number;
    deliver: number;
    ack: number;
  };
}
```

### 6.2 运营端RabbitMQ配置界面

#### 6.2.1 配置页面

```
┌─────────────────────────────────────────────────────────────────┐
│  RabbitMQ 配置                                                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  连接配置                                                │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                          │   │
│  │  启用RabbitMQ:  [☑ 启用]                                │   │
│  │                                                          │   │
│  │  主机地址:      [localhost________________________]    │   │
│  │  端口:         [5672_____]  心跳: [60____]              │   │
│  │  用户名:       [guest_____________________________]    │   │
│  │  密码:         [********____________________________]    │   │
│  │  虚拟主机:     [/vhost___________________________]    │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  集群配置 (可选)                                         │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                          │   │
│  │  ☑ 启用集群                                             │   │
│  │                                                          │   │
│  │  节点列表:                                               │   │
│  │  ┌─────────────────────────────────────────────────┐   │   │
│  │  │ 主机                │ 端口 │ 权重 │ 操作          │   │   │
│  │  ├─────────────────────────────────────────────────┤   │   │
│  │  │ node-01:5672        │ 5672 │ 1    │ [编辑][删除] │   │   │
│  │  │ node-02:5672        │ 5672 │ 1    │ [编辑][删除] │   │   │
│  │  └─────────────────────────────────────────────────┘   │   │
│  │  [+ 添加节点]                                           │   │
│  │                                                          │   │
│  │  自动故障转移: [☑ 启用]                                 │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  消费者配置                                             │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                          │   │
│  │  预取数量:     [10_____]                                │   │
│  │  自动确认:     [☑ 启用]                                 │   │
│  │  重试次数:     [3_____]   重试延迟: [1000___] ms        │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│                              [取消]  [测试连接]  [保存配置]     │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

#### 6.2.2 队列监控页面

```
┌─────────────────────────────────────────────────────────────────┐
│  RabbitMQ 监控                                                  │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  [概览] [队列] [交换机] [消费者] [健康检查]                      │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  队列概览                                                │   │
│  │  ─────────────────────────────────────────────────────  │   │
│  │                                                          │   │
│  │  总队列数: 8      总消息数: 1,234      总消费者: 15     │   │
│  │                                                          │   │
│  │  消息率:  [████████░░] 100msg/s                          │   │
│  │  消费率:  [██████░░░░] 80msg/s                           │   │
│  │                                                          │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
│  队列列表:                                                       │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │ 队列名称          │ 消息数 │ 消费者 │ 消息率 │ 操作     │   │
│  ├─────────────────────────────────────────────────────────┤   │
│  │ 🟢 ai.outline     │ 12     │ 3/10   │ 5/s    │ [详情]  │   │
│  │ 🟢 ai.chapter     │ 45     │ 2/5    │ 10/s   │ [详情]  │   │
│  │ 🟢 doc.process    │ 8      │ 2/5    │ 3/s    │ [详情]  │   │
│  │ 🟢 sys.notify     │ 2      │ 1/2    │ 1/s    │ [详情]  │   │
│  │ 🟢 export.task    │ 0      │ 1/3    │ 0/s    │ [详情]  │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

#### 6.2.3 队列详情页面

```
┌─────────────────────────────────────────────────────────────────┐
│  队列详情 - ai.outline.generate                                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  基本信息:                                                       │
│  ─────────────────────────────────────────────────────────────  │
│  交换机: ai.direct           路由键: outline.generate            │
│  消息数: 12                 消费者: 3/10                        │
│  状态: 🟢 运行中            创建时间: 2026-04-01                 │
│                                                                  │
│  性能指标:                                                       │
│  ─────────────────────────────────────────────────────────────  │
│  发布率: 5msg/s      传递率: 15msg/s     确认率: 99.8%         │
│  平均延迟: 120ms      错误率: 0.2%                               │
│                                                                  │
│  队列参数:                                                       │
│  ─────────────────────────────────────────────────────────────  │
│  最大长度: 10000        消息TTL: 无                             │
│  死信交换机: dlx.fanout    死信路由键: 无                       │
│                                                                  │
│  [查看消息] [清空队列] [暂停消费] [导出配置]                     │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 6.3 RabbitMQ相关数据库表

```sql
-- RabbitMQ配置表
CREATE TABLE rabbitmq_config (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    config_key      VARCHAR(100) NOT NULL UNIQUE COMMENT '配置键',
    config_value    TEXT COMMENT '配置值(JSON)',
    is_active       TINYINT DEFAULT 1 COMMENT '是否激活',
    description     VARCHAR(500) COMMENT '配置描述',
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) COMMENT='RabbitMQ配置表';

-- 队列统计表
CREATE TABLE queue_statistics (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    queue_name      VARCHAR(100) NOT NULL COMMENT '队列名称',
    message_count   INT DEFAULT 0 COMMENT '当前消息数',
    consumer_count  INT DEFAULT 0 COMMENT '消费者数',
    publish_rate    DECIMAL(10,2) DEFAULT 0 COMMENT '发布率(条/秒)',
    deliver_rate    DECIMAL(10,2) DEFAULT 0 COMMENT '传递率(条/秒)',
    error_rate      DECIMAL(5,2) DEFAULT 0 COMMENT '错误率(%)',
    avg_latency     INT DEFAULT 0 COMMENT '平均延迟(ms)',
    record_time     DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '记录时间',
    INDEX idx_queue_name (queue_name),
    INDEX idx_record_time (record_time)
) COMMENT='队列统计表';

-- 预置RabbitMQ配置
INSERT INTO rabbitmq_config (config_key, config_value, description) VALUES
('rabbitmq_enabled', 'true', '是否启用RabbitMQ'),
('rabbitmq_host', 'localhost', 'RabbitMQ主机'),
('rabbitmq_port', '5672', 'RabbitMQ端口'),
('rabbitmq_username', 'guest', 'RabbitMQ用户名'),
('rabbitmq_password', '', 'RabbitMQ密码(加密存储)'),
('rabbitmq_virtual_host', '/', '虚拟主机'),
('rabbitmq_heartbeat', '60', '心跳间隔(秒)'),
('rabbitmq_connection_timeout', '10000', '连接超时(毫秒)'),
('rabbitmq_consumer_pref count', '10', '预取数量'),
('rabbitmq_retry_attempts', '3', '重试次数'),
('rabbitmq_retry_delay', '1000', '重试延迟(毫秒)');
```

### 6.4 运营端RabbitMQ API

| 接口路径 | 方法 | 说明 |
|---------|------|------|
| `/api/admin/rabbitmq/config` | GET | 获取RabbitMQ配置 |
| `/api/admin/rabbitmq/config` | PUT | 更新RabbitMQ配置 |
| `/api/admin/rabbitmq/config/test` | POST | 测试连接 |
| `/api/admin/rabbitmq/queues` | GET | 获取队列列表 |
| `/api/admin/rabbitmq/queues/{name}` | GET | 获取队列详情 |
| `/api/admin/rabbitmq/queues/{name}/purge` | POST | 清空队列 |
| `/api/admin/rabbitmq/queues/{name}/pause` | POST | 暂停消费 |
| `/api/admin/rabbitmq/queues/{name}/resume` | POST | 恢复消费 |
| `/api/admin/rabbitmq/exchanges` | GET | 获取交换机列表 |
| `/api/admin/rabbitmq/stats` | GET | 获取队列统计 |
| `/api/admin/rabbitmq/stats/history` | GET | 获取历史统计 |
| `/api/admin/rabbitmq/health` | GET | 健康检查 |

---

## 第七部分：API接口规范

### 7.1 用户端API

#### 5.1.1 认证模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/auth/register` | POST | 用户注册 | 否 |
| `/api/auth/login` | POST | 用户登录 | 否 |
| `/api/auth/logout` | POST | 退出登录 | 是 |
| `/api/auth/refresh-token` | POST | 刷新Token | 是 |
| `/api/auth/send-code` | POST | 发送验证码 | 否 |
| `/api/auth/reset-password` | POST | 重置密码 | 否 |

#### 5.1.2 用户模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/user/profile` | GET | 获取用户信息 | 是 |
| `/api/user/profile` | PUT | 更新用户信息 | 是 |
| `/api/user/avatar` | POST | 上传头像 | 是 |
| `/api/user/points` | GET | 获取积分信息 | 是 |
| `/api/user/points/log` | GET | 积分变动记录 | 是 |

#### 6.1.3 作品模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/novels` | GET | 作品列表 | 是 |
| `/api/novels` | POST | 创建作品 | 是 |
| `/api/novels/{id}` | GET | 作品详情 | 是 |
| `/api/novels/{id}` | PUT | 更新作品 | 是 |
| `/api/novels/{id}` | DELETE | 删除作品 | 是 |
| `/api/novels/{id}/outline` | GET | 获取大纲 | 是 |
| `/api/novels/{id}/outline` | PUT | 更新大纲 | 是 |
| `/api/novels/{id}/outline/version` | GET | 大纲版本列表 | 是 |
| `/api/novels/{id}/outline/version/{vid}` | GET | 大纲版本详情 | 是 |
| `/api/novels/{id}/outline/rollback/{vid}` | POST | 大纲版本回滚 | 是 |
| `/api/novels/{id}/volumes` | GET | 卷列表 | 是 |
| `/api/novels/{id}/volumes` | POST | 创建卷 | 是 |
| `/api/novels/{novelId}/volumes/{id}` | PUT | 更新卷 | 是 |
| `/api/novels/{novelId}/volumes/{id}` | DELETE | 删除卷 | 是 |
| `/api/novels/{novelId}/chapters` | GET | 章节列表 | 是 |
| `/api/novels/{novelId}/chapters` | POST | 创建章节 | 是 |
| `/api/novels/{novelId}/chapters/{id}` | GET | 章节详情 | 是 |
| `/api/novels/{novelId}/chapters/{id}` | PUT | 更新章节 | 是 |
| `/api/novels/{novelId}/chapters/{id}` | DELETE | 删除章节 | 是 |
| `/api/novels/{novelId}/chapters/{id}/version` | GET | 章节版本列表 | 是 |
| `/api/novels/{novelId}/chapters/{id}/rollback/{vid}` | POST | 章节回滚 | 是 |
| `/api/novels/{id}/export` | GET | 导出作品 | 是 |

#### 5.1.4 AI创作模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/ai/generate-outline` | POST | 生成大纲 | 是 |
| `/api/ai/generate-volumes` | POST | 生成分卷 | 是 |
| `/api/ai/generate-chapter-draft` | POST | 生成章节细纲 | 是 |
| `/api/ai/expand-content` | POST | 扩写正文 | 是 |
| `/api/ai/continue-writing` | POST | 智能续写 | 是 |
| `/api/ai/plot-suggestion` | POST | 情节建议 | 是 |
| `/api/ai/check-consistency` | POST | 一致性检查 | 是 |
| `/api/models` | GET | 获取可用模型 | 是 |
| `/api/models/{id}/torrent` | GET | 获取模型种子 | 是 |
| `/api/models/{id}/download-status` | GET | 获取下载状态 | 是 |

#### 5.1.5 知识库模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/knowledge` | GET | 知识库列表 | 是 |
| `/api/knowledge` | POST | 上传文档 | 是 |
| `/api/knowledge/{id}` | GET | 文档详情 | 是 |
| `/api/knowledge/{id}` | DELETE | 删除文档 | 是 |
| `/api/knowledge/{id}/chunks` | GET | 文档切片列表 | 是 |
| `/api/knowledge/{id}/search` | POST | 知识检索 | 是 |

#### 5.1.6 提示词库模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/templates` | GET | 模板列表 | 是 |
| `/api/templates/{id}` | GET | 模板详情 | 是 |
| `/api/templates` | POST | 创建模板 | 是 |
| `/api/templates/{id}` | PUT | 更新模板 | 是 |
| `/api/templates/{id}` | DELETE | 删除模板 | 是 |

#### 5.1.7 角色库模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/novels/{novelId}/characters` | GET | 角色列表 | 是 |
| `/api/novels/{novelId}/characters` | POST | 创建角色 | 是 |
| `/api/novels/{novelId}/characters/{id}` | GET | 角色详情 | 是 |
| `/api/novels/{novelId}/characters/{id}` | PUT | 更新角色 | 是 |
| `/api/novels/{novelId}/characters/{id}` | DELETE | 删除角色 | 是 |
| `/api/novels/{novelId}/characters/relationships` | GET | 关系列表 | 是 |
| `/api/novels/{novelId}/characters/relationships` | POST | 创建关系 | 是 |
| `/api/novels/{novelId}/characters/generate` | POST | AI生成角色 | 是 |

#### 6.1.8 素材库模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/materials` | GET | 素材列表 | 是 |
| `/api/materials` | POST | 创建素材 | 是 |
| `/api/materials/{id}` | GET | 素材详情 | 是 |
| `/api/materials/{id}` | PUT | 更新素材 | 是 |
| `/api/materials/{id}` | DELETE | 删除素材 | 是 |
| `/api/materials/apply/{id}` | POST | 应用素材到项目 | 是 |
| `/api/materials/recommend` | POST | 获取适合度推荐 | 是 |

#### 5.1.9 AI对话模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/conversations` | GET | 对话列表 | 是 |
| `/api/conversations` | POST | 创建对话 | 是 |
| `/api/conversations/{id}` | GET | 对话详情 | 是 |
| `/api/conversations/{id}` | DELETE | 删除对话 | 是 |
| `/api/conversations/{id}/messages` | GET | 消息历史 | 是 |
| `/api/conversations/{id}/messages` | POST | 发送消息 | 是 |

#### 5.1.10 小工具模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/tools/golden-finger` | POST | 金手指生成 | 是 |
| `/api/tools/book-title` | POST | 书名生成 | 是 |
| `/api/tools/synopsis` | POST | 简介生成 | 是 |
| `/api/tools/worldview` | POST | 世界观生成 | 是 |
| `/api/tools/conflict` | POST | 冲突生成 | 是 |
| `/api/tools/character-quick` | POST | 人物速成 | 是 |
| `/api/tools/expand-style` | POST | 风格扩写 | 是 |

#### 5.1.11 订单模块

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/orders` | GET | 订单列表 | 是 |
| `/api/orders/{id}` | GET | 订单详情 | 是 |
| `/api/orders/create` | POST | 创建订单 | 是 |
| `/api/orders/{id}/pay` | POST | 支付订单 | 是 |
| `/api/products` | GET | 商品列表 | 是 |
| `/api/products/{id}` | GET | 商品详情 | 是 |

### 5.2 运营端API

#### 5.2.1 仪表盘

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/admin/stats/overview` | GET | 数据概览 | ADMIN |
| `/api/admin/stats/user-growth` | GET | 用户增长趋势 | ADMIN |
| `/api/admin/stats/novel-trend` | GET | 作品趋势 | ADMIN |
| `/api/admin/stats/order-stats` | GET | 订单统计 | ADMIN |

#### 6.2.2 用户管理

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/admin/users` | GET | 用户列表 | ADMIN |
| `/api/admin/users/{id}` | GET | 用户详情 | ADMIN |
| `/api/admin/users/{id}` | PUT | 更新用户 | ADMIN |
| `/api/admin/users/{id}/status` | PUT | 修改用户状态 | ADMIN |
| `/api/admin/users/{id}/points` | PUT | 调整积分 | ADMIN |
| `/api/admin/users/{id}/membership` | PUT | 修改会员 | ADMIN |

#### 5.2.3 作品管理

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/admin/novels` | GET | 作品列表 | ADMIN |
| `/api/admin/novels/{id}` | GET | 作品详情 | ADMIN |
| `/api/admin/novels/{id}/audit` | POST | 作品审核 | ADMIN |
| `/api/admin/novels/{id}/recommend` | POST | 推荐作品 | ADMIN |
| `/api/admin/novels/categories` | GET | 分类管理 | ADMIN |
| `/api/admin/novels/categories` | POST | 创建分类 | ADMIN |
| `/api/admin/novels/categories/{id}` | PUT | 更新分类 | ADMIN |
| `/api/admin/novels/categories/{id}` | DELETE | 删除分类 | ADMIN |

#### 5.2.4 AI配置

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/admin/ai/templates` | GET | AI模板列表 | ADMIN |
| `/api/admin/ai/templates` | POST | 创建模板 | ADMIN |
| `/api/admin/ai/templates/{id}` | PUT | 更新模板 | ADMIN |
| `/api/admin/ai/templates/{id}` | DELETE | 删除模板 | ADMIN |
| `/api/admin/ai/models` | GET | 模型列表 | ADMIN |
| `/api/admin/ai/models` | POST | 添加模型 | ADMIN |
| `/api/admin/ai/models/{id}` | PUT | 更新模型 | ADMIN |
| `/api/admin/ai/models/{id}` | DELETE | 删除模型 | ADMIN |
| `/api/admin/ai/keywords` | GET | 敏感词列表 | ADMIN |
| `/api/admin/ai/keywords` | POST | 添加敏感词 | ADMIN |
| `/api/admin/ai/keywords/{id}` | DELETE | 删除敏感词 | ADMIN |

#### 5.2.5 系统设置

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/admin/config/admin-path` | GET | 获取Admin路径 | ADMIN |
| `/api/admin/config/admin-path` | PUT | 修改Admin路径 | ADMIN |
| `/api/admin/config` | GET | 配置列表 | ADMIN |
| `/api/admin/config` | PUT | 更新配置 | ADMIN |
| `/api/admin/config/{key}` | PUT | 更新单个配置 | ADMIN |
| `/api/admin/announcements` | GET | 公告列表 | ADMIN |
| `/api/admin/announcements` | POST | 创建公告 | ADMIN |
| `/api/admin/announcements/{id}` | PUT | 更新公告 | ADMIN |
| `/api/admin/announcements/{id}` | DELETE | 删除公告 | ADMIN |
| `/api/admin/roles` | GET | 角色列表 | ADMIN |
| `/api/admin/roles` | POST | 创建角色 | ADMIN |
| `/api/admin/roles/{id}` | PUT | 更新角色 | ADMIN |
| `/api/admin/logs` | GET | 操作日志 | ADMIN |

#### 6.2.6 订单管理

| 接口路径 | 方法 | 说明 | 认证 |
|---------|------|------|------|
| `/api/admin/orders` | GET | 订单列表 | ADMIN |
| `/api/admin/orders/{id}` | GET | 订单详情 | ADMIN |
| `/api/admin/orders/{id}/refund` | POST | 退款 | ADMIN |
| `/api/admin/products` | GET | 商品管理 | ADMIN |
| `/api/admin/products` | POST | 创建商品 | ADMIN |
| `/api/admin/products/{id}` | PUT | 更新商品 | ADMIN |
| `/api/admin/products/{id}` | DELETE | 删除商品 | ADMIN |

### 5.3 通用响应格式

```typescript
// 成功响应
interface ApiResponse<T> {
  code: 200 | 201;           // HTTP状态码
  message: string;            // 成功信息
  data: T;                    // 响应数据
  timestamp: number;          // 时间戳
  requestId: string;          // 请求ID
}

// 分页响应
interface PagedResponse<T> {
  code: 200;
  message: string;
  data: {
    list: T[];
    pagination: {
      page: number;
      pageSize: number;
      total: number;
      totalPages: number;
    };
  };
  timestamp: number;
  requestId: string;
}

// 错误响应
interface ErrorResponse {
  code: 400 | 401 | 403 | 404 | 500;
  message: string;
  error: string;            // 错误详情
  details?: Record<string, string[]>;  // 字段错误
  timestamp: number;
  requestId: string;
}
```

### 6.4 请求/响应示例

#### 生成大纲

**请求**
```http
POST /api/ai/generate-outline
Authorization: Bearer <token>
Content-Type: application/json

{
  "core_idea": "一个现代青年穿越到修仙世界，发现自己竟然带着一个能够抽奖的系统",
  "genre": "玄幻",
  "word_count_target": 500000,
  "style": "热血、爽文",
  "outline_template": "golden_three_act",
  "knowledge_ids": ["kb_001", "kb_002"],
  "character_ids": ["char_001"]
}
```

**响应**
```json
{
  "code": 200,
  "message": "大纲生成成功",
  "data": {
    "outline_id": "outline_001",
    "title": "抽签修仙录",
    "outline": {
      "title": "抽签修仙录",
      "genre": "玄幻",
      "estimated_word_count": 500000,
      "three_act_structure": { ... },
      "main_characters": ["char_001"],
      "world_setting": "..."
    },
    "version": 1,
    "created_at": "2026-04-28T10:00:00Z",
    "tokens_used": 1500,
    "processing_time_ms": 3200
  },
  "timestamp": 1745824800000,
  "requestId": "req_abc123"
}
```

---

## 第八部分：前端架构设计

### 8.1 项目结构

```
starrynight/
├── public/
│   └── index.html
├── src/
│   ├── main.ts
│   ├── App.vue
│   ├── api/                    # API接口定义
│   │   ├── index.ts
│   │   ├── auth.ts
│   │   ├── novel.ts
│   │   ├── ai.ts
│   │   ├── knowledge.ts
│   │   ├── template.ts
│   │   ├── character.ts
│   │   ├── material.ts
│   │   ├── tool.ts
│   │   ├── order.ts
│   │   └── admin/
│   ├── assets/                 # 静态资源
│   │   ├── images/
│   │   └── styles/
│   ├── components/             # 公共组件
│   │   ├── common/
│   │   │   ├── Button.vue
│   │   │   ├── Input.vue
│   │   │   ├── Modal.vue
│   │   │   └── ...
│   │   ├── editor/
│   │   │   ├── ChapterEditor.vue
│   │   │   ├── OutlineEditor.vue
│   │   │   └── ...
│   │   └── ai/
│   │       ├── AIChatPanel.vue
│   │       ├── AIGenerator.vue
│   │       └── ...
│   ├── composables/            # 组合式函数
│   │   ├── useAI.ts
│   │   ├── useNovel.ts
│   │   ├── useP2P.ts
│   │   └── ...
│   ├── core/                   # 核心模块
│   │   ├── ai/
│   │   │   ├── WebLLMEngine.ts
│   │   │   ├── TensorFlowEngine.ts
│   │   │   ├── ModelManager.ts
│   │   │   └── index.ts
│   │   ├── p2p/
│   │   │   ├── WebTorrentClient.ts
│   │   │   ├── TorrentManager.ts
│   │   │   └── index.ts
│   │   ├── storage/
│   │   │   ├── IndexedDB.ts
│   │   │   ├── CacheManager.ts
│   │   │   └── index.ts
│   │   └── utils/
│   │       ├── webgpu.ts
│   │       └── ...
│   ├── layouts/                # 布局组件
│   │   ├── DefaultLayout.vue
│   │   ├── UserLayout.vue
│   │   └── AdminLayout.vue
│   ├── router/                 # 路由配置
│   │   ├── index.ts
│   │   ├── userRoutes.ts
│   │   └── adminRoutes.ts
│   ├── store/                 # 状态管理
│   │   ├── index.ts
│   │   ├── user.ts
│   │   ├── novel.ts
│   │   ├── ai.ts
│   │   ├── knowledge.ts
│   │   ├── material.ts
│   │   └── admin/
│   ├── types/                 # TypeScript类型
│   │   ├── api.d.ts
│   │   ├── novel.d.ts
│   │   ├── ai.d.ts
│   │   └── ...
│   ├── utils/                  # 工具函数
│   │   ├── request.ts
│   │   ├── storage.ts
│   │   └── ...
│   ├── views/                  # 页面视图
│   │   ├── user/              # 用户端视图
│   │   │   ├── Home.vue
│   │   │   ├── Login.vue
│   │   │   ├── Register.vue
│   │   │   ├── AuthorCenter.vue
│   │   │   ├── NovelDetail.vue
│   │   │   ├── NovelEditor.vue
│   │   │   ├── OutlineEditor.vue
│   │   │   ├── VolumeEditor.vue
│   │   │   ├── ChapterEditor.vue
│   │   │   ├── AIChat.vue
│   │   │   ├── KnowledgeLibrary.vue
│   │   │   ├── TemplateLibrary.vue
│   │   │   ├── CharacterLibrary.vue
│   │   │   ├── MaterialLibrary.vue
│   │   │   ├── ToolBox.vue
│   │   │   ├── Profile.vue
│   │   │   └── OrderCenter.vue
│   │   └── admin/             # 运营端视图
│   │       ├── Dashboard.vue
│   │       ├── UserManage.vue
│   │       ├── NovelManage.vue
│   │       ├── AIConfig.vue
│   │       ├── OrderManage.vue
│   │       ├── SystemConfig.vue
│   │       ├── Announcement.vue
│   │       └── RoleManage.vue
│   └── worker/                 # Web Workers
│       ├── ai.worker.ts
│       └── p2p.worker.ts
├── package.json
├── vite.config.ts
├── tsconfig.json
└── Dockerfile
```

### 6.2 路由配置

```typescript
// router/index.ts
import { createRouter, createWebHistory } from 'vue-router';
import { useUserStore } from '@/store/user';

const routes = [
  // 用户端路由
  {
    path: '/',
    component: () => import('@/layouts/UserLayout.vue'),
    children: [
      { path: '', name: 'Home', component: () => import('@/views/user/Home.vue') },
      { path: 'login', name: 'Login', component: () => import('@/views/user/Login.vue') },
      { path: 'register', name: 'Register', component: () => import('@/views/user/Register.vue') },
      { path: 'author', name: 'AuthorCenter', component: () => import('@/views/user/AuthorCenter.vue'), meta: { requiresAuth: true } },
      { path: 'novel/:id', name: 'NovelDetail', component: () => import('@/views/user/NovelDetail.vue'), meta: { requiresAuth: true } },
      { path: 'novel/:id/edit', name: 'NovelEditor', component: () => import('@/views/user/NovelEditor.vue'), meta: { requiresAuth: true } },
      { path: 'knowledge', name: 'KnowledgeLibrary', component: () => import('@/views/user/KnowledgeLibrary.vue'), meta: { requiresAuth: true } },
      { path: 'characters/:novelId', name: 'CharacterLibrary', component: () => import('@/views/user/CharacterLibrary.vue'), meta: { requiresAuth: true } },
      { path: 'materials', name: 'MaterialLibrary', component: () => import('@/views/user/MaterialLibrary.vue'), meta: { requiresAuth: true } },
      { path: 'tools', name: 'ToolBox', component: () => import('@/views/user/ToolBox.vue'), meta: { requiresAuth: true } },
      { path: 'profile', name: 'Profile', component: () => import('@/views/user/Profile.vue'), meta: { requiresAuth: true } },
      { path: 'orders', name: 'OrderCenter', component: () => import('@/views/user/OrderCenter.vue'), meta: { requiresAuth: true } },
    ]
  },
  // 运营端路由 (动态路径，从后端获取)
  {
    path: `/${import.meta.env.VITE_ADMIN_PATH || 'admin'}`,
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      { path: '', name: 'Dashboard', component: () => import('@/views/admin/Dashboard.vue') },
      { path: 'users', name: 'UserManage', component: () => import('@/views/admin/UserManage.vue') },
      { path: 'novels', name: 'NovelManage', component: () => import('@/views/admin/NovelManage.vue') },
      { path: 'ai-config', name: 'AICConfig', component: () => import('@/views/admin/AICConfig.vue') },
      { path: 'orders', name: 'OrderManage', component: () => import('@/views/admin/OrderManage.vue') },
      { path: 'system', name: 'SystemConfig', component: () => import('@/views/admin/SystemConfig.vue') },
      { path: 'announcements', name: 'Announcement', component: () => import('@/views/admin/Announcement.vue') },
      { path: 'roles', name: 'RoleManage', component: () => import('@/views/admin/RoleManage.vue') },
    ]
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach(async (to, from, next) => {
  const userStore = useUserStore();
  
  if (to.meta.requiresAuth && !userStore.isLoggedIn) {
    return next({ name: 'Login', query: { redirect: to.fullPath } });
  }
  
  if (to.meta.requiresAdmin && !userStore.isAdmin) {
    return next({ name: 'Home' });
  }
  
  next();
});

export default router;
```

### 6.3 状态管理

```typescript
// store/user.ts
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useUserStore = defineStore('user', () => {
  const token = ref(localStorage.getItem('token') || '');
  const userInfo = ref<UserInfo | null>(null);
  
  const isLoggedIn = computed(() => !!token.value);
  const isAdmin = computed(() => userInfo.value?.role === 'ADMIN');
  const points = computed(() => userInfo.value?.points || 0);
  const membershipType = computed(() => userInfo.value?.membership_type || 'FREE');
  
  async function login(username: string, password: string) {
    const res = await api.auth.login({ username, password });
    token.value = res.data.token;
    userInfo.value = res.data.user;
    localStorage.setItem('token', res.data.token);
    return res;
  }
  
  async function logout() {
    await api.auth.logout();
    token.value = '';
    userInfo.value = null;
    localStorage.removeItem('token');
  }
  
  async function fetchUserInfo() {
    if (!token.value) return;
    const res = await api.user.profile();
    userInfo.value = res.data;
  }
  
  return {
    token, userInfo, isLoggedIn, isAdmin, points, membershipType,
    login, logout, fetchUserInfo
  };
});
```

### 6.4 核心模块

#### 6.4.1 WebLLM AI引擎

```typescript
// core/ai/WebLLMEngine.ts
import { WebLLM } from '@mlc-ai/web-llm';

export class WebLLMEngine {
  private engine: WebLLM | null = null;
  private modelName: string = '';
  
  async initialize(modelPath: string, options?: InitOptions) {
    this.engine = new WebLLM();
    await this.engine.init(modelPath, (progress) => {
      console.log(`Loading: ${progress.progress}%`);
    });
    this.modelName = modelPath;
  }
  
  async generate(prompt: string, params?: GenerationParams): Promise<string> {
    if (!this.engine) {
      throw new Error('Engine not initialized');
    }
    return await this.engine.generate(prompt, params);
  }
  
  async chat(messages: ChatMessage[]): Promise<string> {
    if (!this.engine) {
      throw new Error('Engine not initialized');
    }
    const prompt = this.buildPrompt(messages);
    return await this.generate(prompt);
  }
  
  private buildPrompt(messages: ChatMessage[]): string {
    return messages.map(m => `${m.role}: ${m.content}`).join('\n');
  }
  
  destroy() {
    this.engine = null;
  }
}
```

#### 6.4.2 P2P资源加载器

```typescript
// core/p2p/WebTorrentClient.ts
import WebTorrent from 'webtorrent';

export class P2PResourceLoader {
  private client: WebTorrent.Instance;
  private cache: Map<string, ArrayBuffer>;
  
  constructor() {
    this.client = new WebTorrent();
    this.cache = new Map();
  }
  
  async downloadResource(
    torrentUrl: string,
    onProgress?: (progress: number) => void
  ): Promise<ArrayBuffer> {
    // 检查缓存
    const cached = this.cache.get(torrentUrl);
    if (cached) return cached;
    
    return new Promise((resolve, reject) => {
      this.client.download(torrentUrl, (torrent) => {
        torrent.on('download', (bytes) => {
          onProgress?.(torrent.progress);
        });
        
        torrent.files[0].arrayBuffer((err, buffer) => {
          if (err) reject(err);
          else {
            this.cache.set(torrentUrl, buffer);
            resolve(buffer);
          }
        });
      });
    });
  }
  
  seedFile(buffer: ArrayBuffer, torrentName: string) {
    return this.client.seed(buffer, { name: torrentName });
  }
  
  destroy() {
    this.client.destroy();
  }
}
```

#### 6.4.3 本地存储管理

```typescript
// core/storage/IndexedDB.ts
import { openDB, DBSchema, IDBPDatabase } from 'idb';

interface StarryNightDB extends DBSchema {
  models: {
    key: string;
    value: {
      id: string;
      name: string;
      type: string;
      data: ArrayBuffer;
      size: number;
      downloadedAt: Date;
    };
  };
  materials: {
    key: string;
    value: {
      id: string;
      type: string;
      data: any;
      cachedAt: Date;
    };
  };
  drafts: {
    key: string;
    value: {
      id: string;
      content: string;
      updatedAt: Date;
    };
  };
}

export class LocalStorageManager {
  private db: IDBPDatabase<StarryNightDB>;
  
  async init() {
    this.db = await openDB<StarryNightDB>('starrynight', 1, {
      upgrade(db) {
        db.createObjectStore('models', { keyPath: 'id' });
        db.createObjectStore('materials', { keyPath: 'id' });
        db.createObjectStore('drafts', { keyPath: 'id' });
      },
    });
  }
  
  async saveModel(id: string, name: string, type: string, data: ArrayBuffer) {
    await this.db.put('models', {
      id, name, type, data,
      size: data.byteLength,
      downloadedAt: new Date()
    });
  }
  
  async getModel(id: string) {
    return this.db.get('models', id);
  }
  
  async saveDraft(id: string, content: string) {
    await this.db.put('drafts', { id, content, updatedAt: new Date() });
  }
  
  async getDraft(id: string) {
    return this.db.get('drafts', id);
  }
}
```

---

## 第九部分：AI模块设计

### 9.1 提示词模板

#### 8.1.1 大纲生成模板

```json
{
  "template_id": "outline_generation",
  "name": "小说大纲生成",
  "category": "outline",
  "prompt_template": "你是一位资深的网络小说编辑，擅长设计引人入胜的故事结构。\n\n请根据以下信息生成一个完整的{genre}类型小说大纲：\n\n## 核心信息\n- 核心创意：{core_idea}\n- 目标字数：{word_count}字\n- 写作风格：{style}\n- 大纲模板：{template_type}\n\n## 参考知识\n{knowledge_context}\n\n## 相关角色\n{character_context}\n\n## 要求\n1. 大纲应包含清晰的三幕结构（建置/对抗/解决）\n2. 每幕需包含核心冲突、关键事件、人物弧线\n3. 主线清晰，支线为辅\n4. 结局要有高潮和情感释放\n5. 符合网文读者的阅读习惯\n6. 必须包含至少3个主要角色的设定\n\n请以JSON格式输出，包含：\n- title: 小说标题\n- subtitle: 副标题（如有）\n- three_act_structure: 三幕结构详情\n- main_characters: 主要角色列表（含角色ID和简要设定）\n- world_setting: 世界观简述\n- potential_hooks: 3-5个爆点设计",
  "variables": [
    { "name": "genre", "type": "string", "required": true },
    { "name": "core_idea", "type": "string", "required": true },
    { "name": "word_count", "type": "number", "required": false, "default": 300000 },
    { "name": "style", "type": "string", "required": false, "default": "热血、爽文" },
    { "name": "template_type", "type": "enum", "required": false, "default": "golden_three_act", "options": ["golden_three_act", "hero_journey", "five_act_structure"] },
    { "name": "knowledge_context", "type": "string", "required": false },
    { "name": "character_context", "type": "string", "required": false }
  ],
  "output_format": "json",
  "model": "qwen2-1.5b-instruct",
  "parameters": {
    "temperature": 0.8,
    "max_tokens": 4096,
    "top_p": 0.9
  }
}
```

#### 9.1.2 章节细纲生成模板

```json
{
  "template_id": "chapter_draft_generation",
  "name": "章节细纲生成",
  "category": "chapter_draft",
  "prompt_template": "你是一位专业的网文大纲设计师，擅长设计引人入胜的章节结构。\n\n请为以下卷纲生成第{chapter_no}章的详细细纲：\n\n## 卷纲上下文\n{volume_summary}\n\n## 当前章节信息\n- 章节序号：{chapter_no}\n- 章节主题：{chapter_theme}\n- 目标字数：{target_word_count}字\n\n## 章节模板类型\n{chapter_template}\n\n## 角色信息\n{character_context}\n\n## 世界观设定\n{worldview_context}\n\n## 已有细纲（参考）\n{existing_drafts}\n\n请生成包含以下要素的章节细纲：\n\n1. **基础信息**：章节标题建议、核心事件\n2. **场景设定**：时间、地点、氛围\n3. **出场人物**：主要人物及其在本章的目标\n4. **情节点**：至少5个情节点（含开端、发展、转折、高潮、结尾）\n5. **情感曲线**：本章的情感变化\n6. **关键对白**：1-3句关键对白\n7. **伏笔埋设**：本章埋下的伏笔（如有）\n8. **与前后章节的衔接**：如何承接上文、引发下文\n\n请以JSON格式输出。",
  "variables": [
    { "name": "chapter_no", "type": "number", "required": true },
    { "name": "volume_summary", "type": "string", "required": true },
    { "name": "chapter_theme", "type": "string", "required": false },
    { "name": "target_word_count", "type": "number", "required": false, "default": 3000 },
    { "name": "chapter_template", "type": "enum", "required": false, "options": ["standard", "cliffhanger", "emotional", "action"] },
    { "name": "character_context", "type": "string", "required": false },
    { "name": "worldview_context", "type": "string", "required": false },
    { "name": "existing_drafts", "type": "string", "required": false }
  ],
  "output_format": "json",
  "model": "qwen2-0.5b-instruct",
  "parameters": {
    "temperature": 0.7,
    "max_tokens": 2048
  }
}
```

### 7.2 RAG检索配置

```typescript
const RAG_CONFIG = {
  // 召回配置
  retrieval: {
    top_k: 5,
    similarity_threshold: 0.7,
    max_results: 10,
  },
  
  // 注入配置
  injection: {
    max_total_tokens: 2000,
    chunk_prefix: '【参考知识】',
    chunk_suffix: '【参考知识结束】',
  },
  
  // 知识库匹配规则
  matching: {
    exact_genre_match: true,
    character_match: true,
    worldview_match: true,
  },
  
  // 向量模型
  embedding: {
    model: 'bge-large-zh',
    dimension: 1024,
    batch_size: 32,
  },
};
```

### 7.3 风格指纹分析

```typescript
interface StyleFingerprint {
  // 句式特征
  avg_sentence_length: number;      // 平均句长
  sentence_length_variance: number;  // 句长方差
  paragraph_length_avg: number;      // 平均段落长度
  
  // 用词特征
  vocabulary_richness: number;       // 词汇丰富度
  common_words: string[];            // 高频词
  unique_words_ratio: number;        // 独特词汇比例
  
  // 修辞特征
  metaphor_density: number;          // 隐喻密度
  dialogue_ratio: number;            // 对话占比
  exclamation_ratio: number;         // 感叹句比例
  
  // 情感特征
  emotional_tone: 'positive' | 'negative' | 'neutral';
  emotional_variance: number;        // 情感波动
  
  // 结构特征
  pacing_type: 'fast' | 'medium' | 'slow';
  action_scene_ratio: number;        // 动作场景占比
}

// 风格分析提示词
const STYLE_ANALYSIS_PROMPT = `请分析以下文本的写作风格特征，以JSON格式输出：

{
  "avg_sentence_length": 平均句长（单词数）,
  "sentence_length_variance": 句长方差,
  "paragraph_length_avg": 平均段落长度,
  "vocabulary_richness": 词汇丰富度（0-1）,
  "common_words": ["高频词列表"],
  "unique_words_ratio": 独特词汇比例（0-1）,
  "metaphor_density": 隐喻密度（0-1）,
  "dialogue_ratio": 对话占比（0-1）,
  "exclamation_ratio": 感叹句比例（0-1）,
  "emotional_tone": "positive/negative/neutral",
  "emotional_variance": 情感波动（0-1）,
  "pacing_type": "fast/medium/slow",
  "action_scene_ratio": 动作场景占比（0-1）
}

请只输出JSON，不要有其他内容。`;
```

---

## 第九部分：部署方案

### 9.1 Docker Compose 部署

```yaml
version: '3.8'

services:
  # API服务
  api:
    build:
      context: ./backend
      dockerfile: Dockerfile
    container_name: starrynight-api
    ports:
      - "8080:8080"
    environment:
      - SPRING_PROFILES_ACTIVE=prod
      - MYSQL_HOST=mysql
      - REDIS_HOST=redis
      - MILVUS_HOST=milvus
    depends_on:
      - mysql
      - redis
    networks:
      - starrynight

  # MySQL数据库
  mysql:
    image: mysql:8.0
    container_name: starrynight-mysql
    environment:
      - MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
      - MYSQL_DATABASE=starrynight
      - MYSQL_USER=${MYSQL_USER}
      - MYSQL_PASSWORD=${MYSQL_PASSWORD}
    volumes:
      - mysql_data:/var/lib/mysql
      - ./mysql/init:/docker-entrypoint-initdb.d
    ports:
      - "3306:3306"
    networks:
      - starrynight

  # Redis缓存
  redis:
    image: redis:7-alpine
    container_name: starrynight-redis
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data
    networks:
      - starrynight

  # Milvus向量数据库
  milvus:
    image: milvusdb/milvus:v2.3.3
    container_name: starrynight-milvus
    environment:
      - ETCD_ENDPOINTS=etcd:2379
      - MINIO_ADDRESS=minio:9000
    ports:
      - "19530:19530"
      - "9091:9091"
    depends_on:
      - etcd
      - minio
    networks:
      - starrynight

  # Milvus依赖服务
  etcd:
    image: quay.io/coreos/etcd:v3.5.5
    container_name: starrynight-etcd
    environment:
      - ETCD_AUTO_COMPACTION_MODE=revision
      - ETCD_AUTO_COMPACTION_RETENTION=1000
      - ETCD_QUOTA_BACKEND_BYTES=4294967296
      - ETCD_SNAPSHOT_COUNT=50000
    volumes:
      - etcd_data:/etcd
    command: etcd -advertise-client-urls=http://127.0.0.1:2379 -listen-client-urls http://0.0.0.0:2379 --data-dir /etcd
    networks:
      - starrynight

  minio:
    image: minio/minio:RELEASE.2023-09-04T19-57-37Z
    container_name: starrynight-minio
    environment:
      - MINIO_ROOT_PASSWORD=${MINIO_ROOT_PASSWORD}
      - MINIO_ROOT_USER=${MINIO_ROOT_USER}
    ports:
      - "9000:9000"
      - "9001:9001"
    volumes:
      - minio_data:/minio_data
    command: minio server /minio_data --console-address ":9001"
    networks:
      - starrynight

  # Nginx反向代理
  nginx:
    image: nginx:alpine
    container_name: starrynight-nginx
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf
      - ./nginx/conf.d:/etc/nginx/conf.d
      - ./frontend/dist:/usr/share/nginx/html
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - api
    networks:
      - starrynight

volumes:
  mysql_data:
  redis_data:
  etcd_data:
  minio_data:

networks:
  starrynight:
    driver: bridge
```

### 9.2 Nginx配置

```nginx
# nginx/nginx.conf
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for"';
    
    access_log /var/log/nginx/access.log main;
    
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    gzip on;
    gzip_types text/plain text/css application/json application/javascript;
    
    upstream api_backend {
        server api:8080;
    }
    
    include /etc/nginx/conf.d/*.conf;
}

# nginx/conf.d/user.conf (用户端)
server {
    listen 80;
    server_name starrynight.com;
    
    root /usr/share/nginx/html;
    index index.html;
    
    # 用户端SPA
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    # API代理
    location /api/ {
        proxy_pass http://api_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
    
    # WebSocket支持(AI对话)
    location /ws/ {
        proxy_pass http://api_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}

# nginx/conf.d/admin.conf (运营端)
# 路径由后端配置，动态生成
server {
    listen 80;
    server_name admin.starrynight.com;
    
    root /usr/share/nginx/html;
    index index.html;
    
    # 运营端SPA
    location / {
        try_files $uri $uri/ /admin.html;
    }
    
    # 运营端API
    location /api/admin/ {
        proxy_pass http://api_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

### 9.3 后端配置

```yaml
# application-prod.yml
spring:
  application:
    name: starrynight-api
  
  datasource:
    url: jdbc:mysql://mysql:3306/starrynight?useUnicode=true&characterEncoding=utf8&serverTimezone=Asia/Shanghai
    username: ${MYSQL_USER}
    password: ${MYSQL_PASSWORD}
    driver-class-name: com.mysql.cj.jdbc.Driver
    hikari:
      maximum-pool-size: 20
      minimum-idle: 5
      idle-timeout: 300000
      connection-timeout: 20000
  
  redis:
    host: redis
    port: 6379
    database: 0
    timeout: 3000ms
    lettuce:
      pool:
        max-active: 20
        max-idle: 10
        min-idle: 5
  
  servlet:
    multipart:
      max-file-size: 50MB
      max-request-size: 100MB

jwt:
  secret: ${JWT_SECRET}
  expiration: 86400000  # 24小时
  refresh-expiration: 604800000  # 7天

ai:
  model:
    default: qwen2-0.5b-instruct
    outline-model: qwen2-1.5b-instruct
    chapter-model: qwen2-1.5b-instruct
  
  vector:
    milvus:
      host: milvus
      port: 19530
      collection: starrynight_knowledge

p2p:
  torrent:
    seeder-url: https://cdn.starrynight.com/torrents/
    tracker: wss://tracker.starrynight.com

logging:
  level:
    root: INFO
    com.starrynight: DEBUG
  file:
    name: /var/log/starrynight/application.log
```

---

## 第十部分：开发规范

### 10.1 Git工作流

```
main (生产环境)
  │
  ├── develop (开发分支)
  │     │
  │     ├── feature/xxx (功能分支)
  │     ├── fix/xxx (修复分支)
  │     └── refactor/xxx (重构分支)
```

### 10.2 分支命名规范

```
feature/AI-001-outline-generation
fix/AI-002-chat-bug
refactor/UI-003-editor-optimization
```

### 10.3 提交信息规范

```
<type>(<scope>): <subject>

# type: feat | fix | docs | style | refactor | test | chore
# scope: ai | novel | user | admin | ui | api
# subject: 简短描述

示例:
feat(ai): 添加大纲生成功能
fix(novel): 修复章节保存问题
docs(api): 更新API文档
```

### 10.4 代码风格

**Java后端**：
- 遵循Google Java Style Guide
- 使用Lombok减少样板代码
- 使用构造器模式创建DTO
- 所有public方法需有Javadoc注释

**TypeScript前端**：
- 遵循ESLint + Prettier配置
- 使用泛型避免any
- 组件使用Composition API
- 所有接口和类型定义在types目录

### 10.5 安全规范

1. **密码**：BCrypt加密存储
2. **JWT**：短期token + 刷新机制
3. **敏感操作**：需二次验证
4. **SQL注入**：使用MyBatis-Plus参数绑定
5. **XSS**：全局过滤器 + HTML转义
6. **CSRF**：Token校验机制
7. **限流**：Redis + Bucket4j

---

## 附录

### A. 术语表

| 术语 | 说明 |
|------|------|
| RAG | Retrieval-Augmented Generation，检索增强生成 |
| 向量数据库 | 存储向量嵌入的数据库，用于语义检索 |
| P2P | Peer-to-Peer，对等网络 |
| WebLLM | 浏览器端LLM推理引擎 |
| SPA | Single Page Application，单页应用 |

### B. 参考资料

- Spring Boot Documentation
- Vue 3 Documentation
- MyBatis-Plus Documentation
- WebLLM Documentation
- WebTorrent Documentation

### C. 联系方式

- 项目负责人：[待定]
- 技术支持：[待定]
