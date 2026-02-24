# AI音乐创作系统API文档

## 概述

AI音乐创作系统提供了完整的音乐创作、编辑、混音和导出功能。本文档描述了所有可用的API接口。

## 基础信息

- **基础URL**: `/api/ai-music`
- **认证方式**: Session/JWT
- **数据格式**: JSON
- **字符编码**: UTF-8

## 通用响应格式

### 成功响应
```json
{
    "success": true,
    "data": {},
    "message": "操作成功"
}
```

### 错误响应
```json
{
    "success": false,
    "error": "错误信息",
    "code": "ERROR_CODE"
}
```

## 1. 音乐项目管理API

### 1.1 创建音乐项目
- **URL**: `POST /api/ai-music/project`
- **描述**: 创建新的音乐项目
- **参数**:
  ```json
  {
    "title": "项目标题",
    "genre": "音乐风格",
    "description": "项目描述",
    "bpm": 120,
    "key_signature": "C major"
  }
  ```
- **响应**:
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "title": "项目标题",
      "genre": "pop",
      "description": "项目描述",
      "status": 1,
      "bpm": 120,
      "key_signature": "C major",
      "created_at": "2026-02-08T15:00:00Z"
    }
  }
  ```

### 1.2 获取用户音乐项目列表
- **URL**: `GET /api/ai-music/projects`
- **描述**: 获取当前用户的音乐项目列表
- **参数**:
  - `page`: 页码 (默认: 1)
  - `limit`: 每页数量 (默认: 20)
  - `status`: 项目状态筛选
- **响应**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "title": "项目标题",
        "genre": "pop",
        "status": 1,
        "track_count": 5,
        "export_count": 2,
        "created_at": "2026-02-08T15:00:00Z"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 50,
      "pages": 3
    }
  }
  ```

### 1.3 获取音乐项目详情
- **URL**: `GET /api/ai-music/project/{id}`
- **描述**: 获取指定音乐项目的详细信息
- **响应**:
  ```json
  {
    "success": true,
    "data": {
      "project": {
        "id": 1,
        "title": "项目标题",
        "genre": "pop",
        "description": "项目描述",
        "status": 1,
        "bpm": 120,
        "key_signature": "C major"
      },
      "stats": {
        "track_count": 5,
        "lyrics_count": 1,
        "vocal_count": 2,
        "export_count": 3
      },
      "lyrics": [],
      "tracks": [],
      "mixes": [],
      "exports": []
    }
  }
  ```

### 1.4 更新音乐项目
- **URL**: `PUT /api/ai-music/project/{id}`
- **描述**: 更新音乐项目信息
- **参数**:
  ```json
  {
    "title": "新标题",
    "description": "新描述",
    "status": 2
  }
  ```

### 1.5 删除音乐项目
- **URL**: `DELETE /api/ai-music/project/{id}`
- **描述**: 删除指定的音乐项目

### 1.6 复制音乐项目
- **URL**: `POST /api/ai-music/project/{id}/duplicate`
- **描述**: 复制现有的音乐项目
- **响应**:
  ```json
  {
    "success": true,
    "data": {
      "id": 2,
      "title": "项目标题 (副本)"
    }
  }
  ```

## 2. 歌词创作API

### 2.1 创建歌词
- **URL**: `POST /api/ai-music/lyrics`
- **描述**: 创建新的歌词
- **参数**:
  ```json
  {
    "project_id": 1,
    "content": "歌词内容",
    "emotion_analysis": {},
    "structure": {},
    "rhyme_scheme": {},
    "syllable_count": 100
  }
  ```

### 2.2 AI生成歌词
- **URL**: `POST /api/ai-music/lyrics/generate`
- **描述**: 使用AI生成歌词
- **参数**:
  ```json
  {
    "project_id": 1,
    "theme": "爱情",
    "emotion": "happy",
    "style": "pop",
    "word_count": 200
  }
  ```

### 2.3 分析歌词情感
- **URL**: `POST /api/ai-music/lyrics/analyze`
- **描述**: 分析歌词的情感倾向
- **参数**:
  ```json
  {
    "content": "歌词内容"
  }
  ```
- **响应**:
  ```json
  {
    "success": true,
    "data": {
      "emotions": {
        "happy": 0.3,
        "sad": 0.1,
        "angry": 0.05
      },
      "primary_emotion": "happy",
      "valence": 0.2,
      "arousal": 0.1
    }
  }
  ```

## 3. 旋律创作API

### 3.1 创建旋律
- **URL**: `POST /api/ai-music/melody`
- **描述**: 创建新的旋律
- **参数**:
  ```json
  {
    "project_id": 1,
    "midi_data": "MIDI数据",
    "tempo": 120,
    "key_signature": "C major",
    "time_signature": "4/4"
  }
  ```

### 3.2 AI生成旋律
- **URL**: `POST /api/ai-music/melody/generate`
- **描述**: 使用AI生成旋律
- **参数**:
  ```json
  {
    "project_id": 1,
    "style": "pop",
    "emotion": "happy",
    "duration": 180
  }
  ```

### 3.3 哼唱识别
- **URL**: `POST /api/ai-music/melody/humming`
- **描述**: 识别哼唱并转换为旋律
- **参数**:
  ```json
  {
    "project_id": 1,
    "audio_file": "音频文件"
  }
  ```

## 4. 编曲与音轨API

### 4.1 创建音轨
- **URL**: `POST /api/ai-music/track`
- **描述**: 创建新的音轨
- **参数**:
  ```json
  {
    "project_id": 1,
    "name": "音轨名称",
    "type": "melody",
    "instrument": "piano",
    "audio_url": "音频文件URL",
    "midi_data": "MIDI数据"
  }
  ```

### 4.2 更新音轨
- **URL**: `PUT /api/ai-music/track/{id}`
- **描述**: 更新音轨信息
- **参数**:
  ```json
  {
    "name": "新名称",
    "volume": 0.5,
    "pan": 0,
    "mute": false,
    "solo": false,
    "effects": []
  }
  ```

### 4.3 删除音轨
- **URL**: `DELETE /api/ai-music/track/{id}`
- **描述**: 删除指定音轨

### 4.4 AI音轨分离
- **URL**: `POST /api/ai-music/track/separate`
- **描述**: 使用AI分离音轨
- **参数**:
  ```json
  {
    "project_id": 1,
    "source_audio_url": "源音频文件URL"
  }
  ```

### 4.5 AI自动编曲
- **URL**: `POST /api/ai-music/arrangement/generate`
- **描述**: 使用AI自动编曲
- **参数**:
  ```json
  {
    "project_id": 1,
    "style": "pop",
    "instrument_config": {
      "lead": "piano",
      "rhythm": "drums",
      "bass": "bass"
    }
  }
  ```

## 5. 人声处理API

### 5.1 录制人声
- **URL**: `POST /api/ai-music/vocal/record`
- **描述**: 录制人声音频
- **参数**:
  ```json
  {
    "project_id": 1,
    "track_id": 1,
    "audio_data": "音频数据"
  }
  ```

### 5.2 AI歌声合成
- **URL**: `POST /api/ai-music/vocal/synthesize`
- **描述**: 使用AI合成人声
- **参数**:
  ```json
  {
    "project_id": 1,
    "lyrics_id": 1,
    "voice_model": "female_singer",
    "emotion": "happy"
  }
  ```

### 5.3 AI自动修音
- **URL**: `POST /api/ai-music/vocal/tune`
- **描述**: 使用AI自动修正音准
- **参数**:
  ```json
  {
    "vocal_id": 1,
    "correction_strength": 0.8,
    "target_key": "C major"
  }
  ```

### 5.4 AI降噪
- **URL**: `POST /api/ai-music/vocal/denoise`
- **描述**: 使用AI去除噪音
- **参数**:
  ```json
  {
    "vocal_id": 1,
    "noise_reduction_level": 0.7
  }
  ```

## 6. 混音与母带API

### 6.1 AI自动混音
- **URL**: `POST /api/ai-music/mix/auto`
- **描述**: 使用AI自动混音
- **参数**:
  ```json
  {
    "project_id": 1,
    "style": "balanced",
    "target_loudness": -14
  }
  ```

### 6.2 手动混音
- **URL**: `POST /api/ai-music/mix/manual`
- **描述**: 保存手动混音设置
- **参数**:
  ```json
  {
    "project_id": 1,
    "mix_settings": {
      "tracks": {},
      "bus": {}
    }
  }
  ```

### 6.3 AI自动母带
- **URL**: `POST /api/ai-music/master/auto`
- **描述**: 使用AI自动母带处理
- **参数**:
  ```json
  {
    "project_id": 1,
    "style": "balanced",
    "target_loudness": -14
  }
  ```

### 6.4 手动母带
- **URL**: `POST /api/ai-music/master/manual`
- **描述**: 保存手动母带设置
- **参数**:
  ```json
  {
    "project_id": 1,
    "master_settings": {
      "eq": {},
      "compression": {},
      "limiter": {}
    }
  }
  ```

## 7. 导出与分享API

### 7.1 导出音频
- **URL**: `POST /api/ai-music/export/{project_id}`
- **描述**: 导出音频文件
- **参数**:
  ```json
  {
    "format": "mp3",
    "quality": "320kbps",
    "normalize": true,
    "fade_in": 0,
    "fade_out": 0
  }
  ```
- **响应**:
  ```json
  {
    "success": true,
    "data": {
      "export_id": 1,
      "file_url": "/exports/audio/project_1.mp3",
      "file_size": 5242880
    }
  }
  ```

### 7.2 获取支持的导出格式
- **URL**: `GET /api/ai-music/export/formats`
- **描述**: 获取支持的导出格式列表
- **响应**:
  ```json
  {
    "success": true,
    "data": {
      "mp3": {
        "name": "MP3",
        "description": "通用音频格式，文件较小",
        "qualities": ["128kbps", "192kbps", "256kbps", "320kbps"]
      },
      "wav": {
        "name": "WAV",
        "description": "无损音频格式，文件较大",
        "qualities": ["16bit", "24bit", "32bit"]
      }
    }
  }
  ```

### 7.3 分享项目
- **URL**: `POST /api/ai-music/share/{project_id}`
- **描述**: 创建项目分享链接
- **参数**:
  ```json
  {
    "platform": "social_media",
    "expires_at": "2026-03-08T15:00:00Z"
  }
  ```

## 8. 搜索与推荐API

### 8.1 搜索项目
- **URL**: `GET /api/ai-music/search`
- **描述**: 搜索公开的音乐项目
- **参数**:
  - `keyword`: 搜索关键词
  - `page`: 页码
  - `limit`: 每页数量

### 8.2 获取热门项目
- **URL**: `GET /api/ai-music/projects/popular`
- **描述**: 获取热门音乐项目
- **参数**:
  - `limit`: 数量限制
  - `genre`: 音乐风格筛选

### 8.3 获取最新项目
- **URL**: `GET /api/ai-music/projects/latest`
- **描述**: 获取最新音乐项目
- **参数**:
  - `limit`: 数量限制
  - `genre`: 音乐风格筛选

### 8.4 获取推荐模板
- **URL**: `GET /api/ai-music/templates/recommended`
- **描述**: 获取推荐的音乐模板
- **响应**:
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "name": "流行歌曲模板",
        "description": "适用于流行音乐创作的完整模板",
        "style": "pop",
        "genre": "pop",
        "is_premium": false,
        "preview_url": "/templates/preview/1.mp3"
      }
    ]
  }
  ```

## 9. 用户统计API

### 9.1 获取用户统计
- **URL**: `GET /api/ai-music/user/stats`
- **描述**: 获取当前用户的创作统计
- **响应**:
  ```json
  {
    "success": true,
    "data": {
      "total_projects": 10,
      "draft_count": 2,
      "in_progress_count": 3,
      "completed_count": 4,
      "published_count": 1,
      "total_views": 1500,
      "total_likes": 89
    }
  }
  ```

## 10. 协作API

### 10.1 添加协作者
- **URL**: `POST /api/ai-music/collaboration/{project_id}/add`
- **描述**: 邀请用户协作项目
- **参数**:
  ```json
  {
    "user_id": 123,
    "role": "collaborator",
    "permissions": {
      "can_edit": true,
      "can_export": false
    }
  }
  ```

### 10.2 管理协作者权限
- **URL**: `PUT /api/ai-music/collaboration/{project_id}/permissions`
- **描述**: 更新协作者权限
- **参数**:
  ```json
  {
    "user_id": 123,
    "permissions": {
      "can_edit": false,
      "can_export": true
    }
  }
  ```

### 10.3 获取协作历史
- **URL**: `GET /api/ai-music/collaboration/{project_id}/history`
- **描述**: 获取项目协作历史记录

## 错误代码

| 错误代码 | 描述 |
|---------|------|
| INVALID_PARAMS | 参数无效 |
| UNAUTHORIZED | 未授权访问 |
| FORBIDDEN | 权限不足 |
| NOT_FOUND | 资源不存在 |
| SERVER_ERROR | 服务器内部错误 |
| AI_SERVICE_ERROR | AI服务错误 |
| FILE_TOO_LARGE | 文件过大 |
| UNSUPPORTED_FORMAT | 不支持的格式 |

## 使用示例

### JavaScript示例

```javascript
// 创建项目
async function createProject() {
    const response = await fetch('/api/ai-music/project', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            title: '我的新歌曲',
            genre: 'pop',
            description: '一首关于爱情的流行歌曲'
        })
    });
    
    const data = await response.json();
    if (data.success) {
        console.log('项目创建成功:', data.data);
    }
}

// AI生成歌词
async function generateLyrics(projectId) {
    const response = await fetch('/api/ai-music/lyrics/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            project_id: projectId,
            theme: '爱情',
            emotion: 'happy',
            style: 'pop'
        })
    });
    
    const data = await response.json();
    if (data.success) {
        console.log('歌词生成成功:', data.data);
    }
}
```

### PHP示例

```php
// 使用Guzzle HTTP客户端
$client = new GuzzleHttp\Client();

// 创建项目
$response = $client->post('/api/ai-music/project', [
    'json' => [
        'title' => '我的新歌曲',
        'genre' => 'pop',
        'description' => '一首关于爱情的流行歌曲'
    ]
]);

$data = json_decode($response->getBody(), true);
if ($data['success']) {
    echo "项目创建成功: " . $data['data']['id'];
}
```

## 注意事项

1. 所有API都需要用户登录认证
2. 文件上传有大小限制（最大100MB）
3. AI生成功能可能需要较长时间处理
4. 导出的音频文件会在24小时后自动删除
5. 协作功能需要项目所有者授权
6. 免费用户有使用次数限制

## 更新日志

### v1.0.0 (2026-02-08)
- 初始版本发布
- 完整的音乐创作功能
- AI辅助创作功能
- 多格式导出支持
- 协作功能