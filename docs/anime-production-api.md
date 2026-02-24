# AI动漫制作系统 API 文档

## 概述

星夜阁AI动漫制作系统提供完整的动漫制作解决方案，从企划到发布的全流程支持。本文档详细描述了各个API接口的使用方法、参数和返回格式。

## 基础信息

- **Base URL**: `/api/anime-production`
- **Content-Type**: `application/json`
- **认证方式**: 需要在请求头中包含用户认证信息

## 通用响应格式

所有API响应都遵循统一的JSON格式：

```json
{
    "success": true,
    "data": {
        // 具体数据内容
    },
    "message": "操作成功",
    "pagination": {
        "current_page": 1,
        "total_pages": 10,
        "total_items": 100,
        "items_per_page": 10
    }
}
```

错误响应格式：
```json
{
    "success": false,
    "error": "错误描述",
    "message": "操作失败"
}
```

## API 接口列表

### 1. 仪表板数据

#### 获取仪表板数据

**接口**: `GET /api/anime-production/dashboard`

**描述**: 获取用户的工作台概览数据，包括项目统计、最近活动、进行中的任务等。

**响应示例**:
```json
{
    "success": true,
    "data": {
        "project_stats": {
            "total_projects": 5,
            "completed_projects": 2,
            "in_progress_projects": 2,
            "planning_projects": 1
        },
        "recent_projects": [
            {
                "id": 1,
                "title": "我的动漫项目",
                "status": "in_production",
                "updated_at": "2024-01-15 10:30:00"
            }
        ],
        "recent_generations": [
            {
                "id": 1,
                "generation_type": "character",
                "result": {
                    "title": "主角角色设计",
                    "description": "勇敢的少年主角"
                },
                "created_at": "2024-01-15 09:15:00"
            }
        ],
        "ongoing_tasks": [
            {
                "type": "script_generation",
                "title": "第3集脚本生成",
                "progress": 75,
                "project_id": 1,
                "project_title": "我的动漫项目"
            }
        ]
    }
}
```

### 2. 项目管理

#### 创建新项目

**接口**: `POST /api/anime-production/create-project`

**请求参数**:
```json
{
    "title": "项目标题",
    "type": "long|short",
    "genre": "fantasy|romance|scifi|daily|comedy|action|horror",
    "description": "项目描述",
    "use_ai_assistance": true
}
```

**响应示例**:
```json
{
    "success": true,
    "data": {
        "project_id": 123
    },
    "message": "项目创建成功"
}
```

#### 获取项目列表

**接口**: `GET /api/anime-production/projects`

**查询参数**:
- `page`: 页码 (默认: 1)
- `limit`: 每页数量 (默认: 20)
- `status`: 项目状态过滤 (可选)
- `genre`: 题材过滤 (可选)
- `search`: 搜索关键词 (可选)

**响应示例**:
```json
{
    "success": true,
    "data": {
        "projects": [
            {
                "id": 1,
                "title": "我的动漫项目",
                "type": "long",
                "genre": "fantasy",
                "status": "in_production",
                "target_episodes": 12,
                "completed_episodes": 3,
                "created_at": "2024-01-15 10:30:00",
                "updated_at": "2024-01-15 14:20:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 3,
            "total_items": 25,
            "items_per_page": 20
        }
    }
}
```

#### 获取项目详情

**接口**: `GET /api/anime-production/project/{id}`

**响应示例**:
```json
{
    "success": true,
    "data": {
        "project_info": {
            "id": 1,
            "title": "我的动漫项目",
            "type": "long",
            "genre": "fantasy",
            "status": "in_production"
        },
        "characters": [
            {
                "id": 1,
                "name": "主角",
                "role_type": "protagonist",
                "personality": "勇敢正直",
                "created_at": "2024-01-15 10:30:00"
            }
        ],
        "scripts": [
            {
                "id": 1,
                "episode_number": 1,
                "title": "第一集：冒险的开始",
                "status": "completed",
                "word_count": 5000
            }
        ]
    }
}
```

### 3. AI生成功能

#### AI生成角色

**接口**: `POST /api/anime-production/generate-character`

**请求参数**:
```json
{
    "project_id": 123,
    "character_name": "角色名称",
    "role_type": "protagonist|supporting|antagonist",
    "personality": "性格特点",
    "art_style": "美术风格要求",
    "ai_model": "gpt-4|claude-3"
}
```

**响应示例**:
```json
{
    "success": true,
    "data": {
        "character_id": 456,
        "character_data": {
            "name": "主角",
            "appearance": "黑发蓝眼，穿着现代服装",
            "personality": "勇敢正直，富有正义感",
            "background": "来自普通家庭，从小立志成为英雄"
        }
    }
}
```

#### AI生成脚本

**接口**: `POST /api/anime-production/generate-script`

**请求参数**:
```json
{
    "project_id": 123,
    "episode_number": 1,
    "episode_theme": "冒险的开始",
    "character_status": "主角获得新能力",
    "previous_summary": "前情提要",
    "ai_model": "gpt-4"
}
```

**响应示例**:
```json
{
    "success": true,
    "data": {
        "script_id": 789,
        "script_data": {
            "title": "第一集：冒险的开始",
            "content": "完整的脚本内容...",
            "duration": 20,
            "word_count": 5000
        }
    }
}
```

#### AI生成短剧

**接口**: `POST /api/anime-production/generate-short-drama`

**请求参数**:
```json
{
    "project_id": 123,
    "title": "短剧标题",
    "drama_style": "comedy|tragedy|scifi|romance|daily|fantasy|horror|thriller",
    "duration_minutes": 5,
    "core_plot": "核心剧情描述",
    "character_settings": [
        {
            "name": "小明",
            "age": 25,
            "personality": "乐观开朗"
        }
    ],
    "ai_model": "sora2|pxz_ai|seko_ai"
}
```

**响应示例**:
```json
{
    "success": true,
    "data": {
        "drama_id": 234,
        "drama_data": {
            "title": "搞笑日常",
            "video_url": "https://example.com/video.mp4",
            "duration_minutes": 5,
            "quality_score": 8.5
        }
    }
}
```

### 4. 内容审核与发布

#### 内容审核

**接口**: `POST /api/anime-production/content-review/{video_id}`

**响应示例**:
```json
{
    "success": true,
    "review_result": {
        "content_rating": "G",
        "copyright_check": {
            "status": "passed",
            "issues": [],
            "recommendations": []
        },
        "sensitive_content": {
            "status": "passed",
            "issues": [],
            "warning": null
        },
        "quality_assessment": {
            "score": 8.5,
            "quality_level": "high",
            "issues": []
        },
        "overall_score": 8.8,
        "passed": true,
        "recommendations": []
    }
}
}
```

#### 发布视频

**接口**: `POST /api/anime-production/publish-video`

**请求参数**:
```json
{
    "video_composition_id": 123,
    "platform": "bilibili|youtube|douyin|weibo",
    "title": "发布标题",
    "description": "视频描述",
    "tags": "标签1,标签2",
    "visibility": "public|unlisted|private",
    "thumbnail_url": "缩略图URL",
    "category": "animation",
    "language": "zh-CN",
    "allow_comments": true,
    "allow_download": false
}
```

**响应示例**:
```json
{
    "success": true,
    "publication_id": 567,
    "platform_video_id": "platform_123456",
    "message": "视频发布成功"
}
```

#### 同步平台数据

**接口**: `POST /api/anime-production/sync-platform-data/{publication_id}`

**响应示例**:
```json
{
    "success": true,
    "sync_data": {
        "view_count": 1500,
        "like_count": 89,
        "comment_count": 23,
        "share_count": 12,
        "revenue": 15.50,
        "platform_data": {
            "last_sync": "2024-01-15 16:30:00",
            "engagement_rate": 5.8
        }
    },
    "updated_fields": ["view_count", "like_count", "comment_count", "share_count", "revenue"]
}
```

### 5. 历史记录

#### 获取生成历史

**接口**: `GET /api/anime-production/generation-history`

**查询参数**:
- `page`: 页码 (默认: 1)
- `limit`: 每页数量 (默认: 20)
- `generation_type`: 生成类型过滤 (可选)
- `project_id`: 项目ID过滤 (可选)

**响应示例**:
```json
{
    "success": true,
    "data": {
        "generations": [
            {
                "id": 1,
                "generation_type": "character",
                "result": {
                    "title": "角色设计结果"
                },
                "ai_model": "gpt-4",
                "cost": 0.0500,
                "quality_score": 8.5,
                "created_at": "2024-01-15 10:30:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 5,
            "total_items": 50,
            "items_per_page": 20
        }
    }
}
```

## 错误代码

| 错误代码 | HTTP状态码 | 描述 |
|---------|-----------|--------|
| 400 | Bad Request | 请求参数错误或格式不正确 |
| 401 | Unauthorized | 未授权访问 |
| 403 | Forbidden | 权限不足 |
| 404 | Not Found | 资源不存在 |
| 500 | Internal Server Error | 服务器内部错误 |

## 使用示例

### JavaScript 示例

```javascript
// 创建项目
const createProject = async (projectData) => {
    try {
        const response = await fetch('/api/anime-production/create-project', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(projectData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('项目创建成功:', result.data.project_id);
            return result.data;
        } else {
            console.error('创建失败:', result.error);
            throw new Error(result.error);
        }
    } catch (error) {
        console.error('网络错误:', error);
        throw error;
    }
};

// 获取项目列表
const getProjects = async (filters = {}) => {
    const params = new URLSearchParams(filters);
    const response = await fetch(`/api/anime-production/projects?${params}`);
    
    if (!response.ok) {
        throw new Error('获取项目列表失败');
    }
    
    return await response.json();
};

// AI生成角色
const generateCharacter = async (projectId, characterData) => {
    const response = await fetch('/api/anime-production/generate-character', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            project_id: projectId,
            ...characterData
        })
    });
    
    if (!response.ok) {
        throw new Error('角色生成失败');
    }
    
    return await response.json();
};

// 发布视频
const publishVideo = async (videoId, publishData) => {
    const response = await fetch('/api/anime-production/publish-video', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            video_composition_id: videoId,
            ...publishData
        })
    });
    
    if (!response.ok) {
        throw new Error('视频发布失败');
    }
    
    return await response.json();
};
```

## 注意事项

1. 所有需要认证的接口都需要在请求头中包含有效的认证信息
2. 文件上传接口使用 `multipart/form-data` 格式
3. 所有时间戳都使用 ISO 8601 格式
4. 分页从1开始计数
5. 成本和金额单位为元，精度为4位小数
6. 质量评分范围为 0-10，保留1位小数

## 版本更新记录

### v1.0.0 (2024-01-15)
- 初始版本发布
- 包含基础的项目管理、角色设计、脚本创作功能
- 支持AI辅助生成

### v1.1.0 (2024-02-01)
- 新增场景设计、分镜制作、动画生成功能
- 完善AI提示词模板
- 优化前端界面

### v1.2.0 (2024-02-08)
- 新增音频制作、视频合成功能
- 集成智能审核与发布功能
- 完善AI短剧制作流程
- 添加完整的前端界面和用户体验

### v1.3.0 (2024-02-08)
- 完善所有功能模块
- 添加完整的API文档和测试用例
- 优化性能和用户体验