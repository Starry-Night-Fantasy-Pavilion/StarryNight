# Cursor Skills 使用说明

## 概述

本目录包含13个已安装的Cursor Skills，这些技能可以帮助AI助手更好地理解项目上下文并提供更准确的代码建议。

## 已安装的Skills

1. **backend-patterns** - 后端架构模式、API设计、数据库优化
2. **code-reviewer** - 代码审查和质量保证
3. **coding-standards** - 通用编码标准和最佳实践
4. **frontend-patterns** - 前端开发模式（React、Next.js）
5. **frontend-ui-ux** - 前端UI/UX设计模式
6. **full-stack** - 全栈开发协调技能
7. **fullstack-developer** - 现代Web开发专业知识
8. **interaction-design** - 交互设计模式和微交互
9. **liquid-glass-design** - 液体玻璃设计效果
10. **multimodal-gen** - 多模态生成能力
11. **security-review** - 安全审查和最佳实践
12. **sql-optimization** - SQL查询优化
13. **sqlmap-database-penetration-testing** - 数据库渗透测试

## 如何在Cursor中使用

### 自动加载

Cursor会自动扫描`.trae/skills/`目录下的所有SKILL.md文件。无需额外配置，这些skills会在你与AI助手对话时自动可用。

### 技能格式

每个技能都遵循标准格式：
- 位于独立目录中（如`backend-patterns/`）
- 包含`SKILL.md`文件
- 文件开头有frontmatter，包含`name`和`description`

### 使用示例

当你与Cursor AI对话时，可以：

1. **直接提及技能名称**：
   - "使用security-review技能检查这段代码"
   - "应用full-stack技能来设计这个API端点"

2. **触发关键词**：
   - 提到"React"或"Next.js"时，会自动应用`frontend-patterns`和`fullstack-developer`
   - 提到"SQL优化"时，会应用`sql-optimization`
   - 提到"安全"或"认证"时，会应用`security-review`

3. **查看技能列表**：
   - 查看`index.json`文件了解所有可用技能

## 验证安装

要验证skills是否正确安装，可以：

1. 检查`.trae/skills/`目录是否存在
2. 确认每个技能目录都有`SKILL.md`文件
3. 查看`index.json`文件确认所有技能已注册

## 技能索引

详细的技能列表和描述请查看`index.json`文件。

## 注意事项

- Skills是自动加载的，无需手动启用
- 如果修改了skill文件，Cursor会在下次对话时自动识别
- 每个skill的frontmatter中的`description`字段用于帮助AI决定何时使用该技能
