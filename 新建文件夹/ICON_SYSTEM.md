# 星夜阁 SVG图标系统设计规范

## 版本历史

| 版本 | 日期 | 说明 |
|------|------|------|
| 1.0 | 2026-04-28 | 初始版本 |

---

## 1. 图标设计原则

### 1.1 设计理念
- **简洁清晰**：图标线条粗细统一，形状简洁明了
- **语义明确**：图标含义与功能对应，一目了然
- **风格统一**：整体风格一致，视觉和谐
- **可扩展性**：支持多尺寸、多颜色定制

### 1.2 技术规范
- **格式**：SVG (Scalable Vector Graphics)
- **尺寸基准**：24x24 (标准)、16x16 (小)、32x32 (大)
- **线宽**：2px (标准)
- **圆角**：2px (统一)
- **颜色**：支持 currentColor (跟随文字颜色)
- **可访问性**：提供 aria-label 属性

---

## 2. 图标分类

### 2.1 品牌图标

| 图标名称 | 用途 | 设计说明 |
|----------|------|----------|
| Logo | 网站标志 | 星夜主题，星星/月亮元素 |
| LogoText | 带文字的完整Logo | Logo + "星夜阁" 文字 |

### 2.2 导航图标

| 图标名称 | 用途 | 设计说明 |
|----------|------|----------|
| Home | 首页 | 房屋轮廓 |
| Center | 创作中心 | 笔/文档组合 |
| Material | 素材库 | 书本/文件夹 |
| Tool | 小工具 | 工具箱/扳手 |
| About | 关于 | 信息圆形 |

### 2.3 创作流程图标

| 图标名称 | 用途 | 设计说明 |
|----------|------|----------|
| Outline | 大纲 | 树形结构/列表 |
| Volume | 卷纲 | 书本分层 |
| Chapter | 章节 | 页面堆叠 |
| Detail | 细纲 | 卡片/清单 |
| Content | 正文 | 文档编辑 |
| Expand | 扩写 | 箭头扩展 |

### 2.4 AI相关图标

| 图标名称 | 用途 | 设计说明 |
|----------|------|----------|
| AI | AI助手 | 机器人/芯片 |
| Sparkle | 智能生成 | 星星闪耀 |
| Chat | 对话 | 气泡对话 |
| History | 历史版本 | 时钟回溯 |
| Compare | 差异对比 | 左右对比 |

### 2.5 素材库图标

| 图标名称 | 用途 | 设计说明 |
|----------|------|----------|
| Knowledge | 知识库 | 地球/文献 |
| Template | 提示词模板 | 模板/卡片 |
| Character | 角色库 | 人物剪影 |
| Material | 素材 | 积木/拼图 |

### 2.6 工具图标

| 图标名称 | 用途 | 设计说明 |
|----------|------|----------|
| GoldenFinger | 金手指 | 手指/魔法棒 |
| BookName | 书名生成 | 标签/标题 |
| Synopsis | 简介生成 | 描述/文段 |
| WorldView | 世界观 | 地球/地图 |
| Conflict | 冲突生成 | 闪电/碰撞 |
| CharacterQuick | 人物速成 | 人物+闪电 |

### 2.7 状态图标

| 图标名称 | 用途 | 设计说明 |
|----------|------|----------|
| Success | 成功 | 对勾圆形 |
| Error | 错误 | 叉号圆形 |
| Warning | 警告 | 感叹号三角 |
| Loading | 加载中 | 旋转圆环 |
| Empty | 空状态 | 文档+问号 |

### 2.8 交互图标

| 图标名称 | 用途 | 设计说明 |
|----------|------|----------|
| Edit | 编辑 | 铅笔 |
| Delete | 删除 | 垃圾桶 |
| Add | 添加 | 加号 |
| Close | 关闭 | 叉号 |
| Expand | 展开 | 向下箭头 |
| Collapse | 收起 | 向上箭头 |
| ArrowLeft | 左箭头 |  |
| ArrowRight | 右箭头 |  |
| ArrowUp | 上箭头 |  |
| ArrowDown | 下箭头 |  |
| Save | 保存 | 磁盘 |
| Search | 搜索 | 放大镜 |
| Filter | 筛选 | 漏斗 |
| Sort | 排序 | 上下箭头 |
| More | 更多 | 三个点 |

---

## 3. 图标颜色规范

### 3.1 主题色
```
--color-primary: #6366F1;      // 主色：靛蓝
--color-secondary: #8B5CF6;   // 次要：紫色
--color-accent: #F59E0B;       // 强调：金色
```

### 3.2 语义色
```
--color-success: #10B981;      // 成功：绿色
--color-warning: #F59E0B;      // 警告：橙色
--color-error: #EF4444;        // 错误：红色
--color-info: #3B82F6;         // 信息：蓝色
```

### 3.3 中性色
```
--color-text-primary: #1F2937;  // 主要文字
--color-text-secondary: #6B7280; // 次要文字
--color-text-tertiary: #9CA3AF;  // 弱化文字
--color-border: #E5E7EB;        // 边框
--color-bg: #F9FAFB;            // 背景
```

---

## 4. SVG图标代码

### 4.1 Logo图标

```svg
<svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="16" cy="16" r="14" stroke="currentColor" stroke-width="2"/>
  <path d="M16 6C16 6 12 10 12 14C12 18 16 22 16 22C16 22 20 18 20 14C20 10 16 6 16 6Z" fill="currentColor"/>
  <circle cx="16" cy="16" r="3" fill="currentColor"/>
  <circle cx="22" cy="8" r="2" fill="currentColor" opacity="0.6"/>
  <circle cx="24" cy="14" r="1.5" fill="currentColor" opacity="0.4"/>
</svg>
```

### 4.2 导航图标

**Home (首页)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M3 12L5 10M5 10L12 3L19 10M5 10V20C5 20.5523 5.44772 21 6 21H9M19 10L21 12M19 10V20C19 20.5523 18.5523 21 18 21H15M9 21C9.55228 21 10 20.5523 10 20V16C10 15.4477 10.4477 15 11 15H13C13.5523 15 14 15.4477 14 16V20C14 20.5523 14.4477 21 15 21M9 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

**Center (创作中心)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M12 19L19 12L22 15M22 15L19 18M22 15H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M2 5V19C2 20.1046 2.89543 21 4 21H15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M7 3L12 8L17 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

### 4.3 创作流程图标

**Outline (大纲)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M4 6H20M4 12H20M4 18H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  <circle cx="18" cy="18" r="3" stroke="currentColor" stroke-width="2"/>
  <path d="M18 16V18M18 18H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**Volume (卷纲)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="4" y="4" width="16" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
  <rect x="4" y="10" width="16" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
  <rect x="4" y="16" width="16" height="4" rx="1" stroke="currentColor" stroke-width="2"/>
</svg>
```

**Chapter (章节)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="5" y="3" width="14" height="18" rx="2" stroke="currentColor" stroke-width="2"/>
  <path d="M9 7H15M9 11H15M9 15H13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**Detail (细纲)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
  <rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
  <rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
  <rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="2"/>
</svg>
```

**Content (正文)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M4 6C4 4.89543 4.89543 4 6 4H18C19.1046 4 20 4.89543 20 6V18C20 19.1046 19.1046 20 18 20H6C4.89543 20 4 19.1046 4 18V6Z" stroke="currentColor" stroke-width="2"/>
  <path d="M7 8H17M7 12H17M7 16H13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

### 4.4 AI相关图标

**AI (AI助手)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="4" y="8" width="16" height="12" rx="2" stroke="currentColor" stroke-width="2"/>
  <path d="M8 8V6C8 4.89543 8.89543 4 10 4H14C15.1046 4 16 4.89543 16 6V8" stroke="currentColor" stroke-width="2"/>
  <circle cx="9" cy="13" r="1.5" fill="currentColor"/>
  <circle cx="15" cy="13" r="1.5" fill="currentColor"/>
  <path d="M12 16V18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**Sparkle (智能生成)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M12 2L13.5 8.5L20 10L13.5 11.5L12 18L10.5 11.5L4 10L10.5 8.5L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M19 15L19.5 17L21 17.5L19.5 18L19 20L18.5 18L17 17.5L18.5 17L19 15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

**Chat (对话)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M21 11.5C21 16.1944 16.9706 20 12 20C10.5 20 9.1 19.6 7.9 18.9L3 20L4.1 15.1C3.4 13.9 3 12.5 3 11C3 5.80558 7.02944 2 12 2C16.9706 2 21 6.80558 21 11.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M8 10H16M8 14H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

### 4.5 素材库图标

**Knowledge (知识库)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
  <path d="M12 3C12 3 7 6 7 12C7 18 12 21 12 21" stroke="currentColor" stroke-width="2"/>
  <ellipse cx="12" cy="12" rx="9" ry="4" stroke="currentColor" stroke-width="2"/>
</svg>
```

**Template (提示词模板)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect x="4" y="4" width="16" height="16" rx="2" stroke="currentColor" stroke-width="2"/>
  <path d="M8 8H16M8 12H16M8 16H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**Character (角色库)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
  <path d="M4 20C4 16.6863 7.58172 14 12 14C16.4183 14 20 16.6863 20 20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

### 4.6 工具图标

**GoldenFinger (金手指)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M14 4L18 8M18 8L22 4M18 8L14 12M18 8H12C9.79086 8 8 9.79086 8 12V16C8 18.2091 9.79086 20 12 20H14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M6 14L10 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**WorldView (世界观)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
  <path d="M12 3C12 3 8 7 8 12C8 17 12 21 12 21" stroke="currentColor" stroke-width="2"/>
  <path d="M12 3C12 3 16 7 16 12C16 17 12 21 12 21" stroke="currentColor" stroke-width="2"/>
  <path d="M3 12H21" stroke="currentColor" stroke-width="2"/>
  <ellipse cx="12" cy="12" rx="4" ry="9" stroke="currentColor" stroke-width="2"/>
</svg>
```

**Conflict (冲突)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M13 3L21 12L13 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M11 3L3 12L11 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

### 4.7 状态图标

**Success (成功)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
  <path d="M8 12L11 15L16 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

**Error (错误)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
  <path d="M15 9L9 15M9 9L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**Warning (警告)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M12 3L21 20H3L12 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M12 10V14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  <circle cx="12" cy="17" r="1" fill="currentColor"/>
</svg>
```

**Loading (加载)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" stroke-dasharray="56.5" stroke-dashoffset="14.125" stroke-linecap="round"/>
</svg>
```

### 4.8 交互图标

**Edit (编辑)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M16 3L21 8L8 21H3V16L16 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

**Delete (删除)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  <path d="M10 7V4C10 3.44772 10.4477 3 11 3H13C13.5523 3 14 3.44772 14 4V7" stroke="currentColor" stroke-width="2"/>
  <path d="M19 7V19C19 20.1046 18.1046 21 17 21H7C5.89543 21 5 20.1046 5 19V7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
  <path d="M10 11V17M14 11V17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**Add (添加)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
  <path d="M12 8V16M8 12H16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**Close (关闭)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
  <path d="M15 9L9 15M9 9L15 15" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
</svg>
```

**ArrowDown (下箭头)**
```svg
<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M12 5V19M12 19L6 13M12 19L18 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
```

---

## 5. Vue3图标组件使用示例

### 5.1 全局注册
```typescript
// main.ts
import { createApp } from 'vue'
import { Icon } from '@/components/Icon'
import icons from '@/icons'

const app = createApp(App)
app.component('Icon', Icon)
```

### 5.2 组件使用
```vue
<template>
  <!-- 基础用法 -->
  <Icon name="home" />

  <!-- 带尺寸 -->
  <Icon name="ai" size="32" />

  <!-- 带颜色 -->
  <Icon name="success" color="#10B981" />

  <!-- 带旋转(用于loading) -->
  <Icon name="loading" spin />
</template>
```

### 5.3 在设计文档中使用
```vue
<!-- admin-design.md 页面示例 -->
<template>
  <div class="dashboard">
    <Icon name="home" size="20" />
    <Icon name="outline" size="24" color="primary" />
    <Icon name="ai" size="24" />
  </div>
</template>
```

---

## 6. 设计决策记录

| 决策ID | 决策内容 | 理由 |
|--------|----------|------|
| ICON-001 | 使用SVG而非图标字体 | SVG可缩放、更灵活、支持多颜色 |
| ICON-002 | 图标尺寸基准24px | 平衡清晰度和空间效率 |
| ICON-003 | 使用currentColor | 便于通过CSS控制颜色 |
| ICON-004 | 统一2px线宽 | 视觉一致性好 |
| ICON-005 | 使用 stroke 而非 fill | 线条图标更现代、清晰 |
