# StarryNight 项目后续开发计划

本计划基于2026年4月30日生成的开发进度对比报告，旨在解决当前的关键缺失项，并分阶段完成剩余功能。

---

### **第一阶段：基础集成与稳定性增强 (预计 1-2 周)**

**目标：补齐核心依赖，为后续开发奠定稳定基础。**

- **[ ] 任务 1.1：实现文件上传服务**
    - **描述：** 当前知识库无法上传文档。需集成对象存储服务（如 MinIO 或 阿里云 OSS）以支持文件上传。
    - **关键步骤：**
        1. 在 `starrynight-backend` 中添加 MinIO/OSS 客户端依赖。
        2. 创建 `FileService` 用于处理文件上传、下载和删除逻辑。
        3. 修改 `KnowledgeLibraryService`，在处理文档上传时调用 `FileService` 将文件持久化。
        4. 在运营后台的“系统设置”中添加存储服务相关配置。

- **[ ] 任务 1.2：集成 Redis 缓存**
    - **描述：** 系统缺少缓存层，高频读取操作会给数据库带来压力。
    - **关键步骤：**
        1. 在 `pom.xml` 中添加 `spring-boot-starter-data-redis` 依赖。
        2. 在 `application.yml` 中配置 Redis 连接信息。
        3. 使用 `@Cacheable` 注解为高频读取操作添加缓存，例如：
            - `AuthService` 中的用户会话信息。
            - `SystemConfigService` 中的系统配置。
            - `NovelService` 中频繁访问的小说基本信息。

- **[ ] 任务 1.3：建立单元测试框架**
    - **描述：** 项目缺乏单元测试，代码质量和重构安全性无法保证。
    - **关键步骤：**
        1. 确保 `pom.xml` 中包含 `spring-boot-starter-test` (JUnit 5, Mockito)。
        2. 为一个核心服务（如 `AuthService` 或 `NovelService`）编写第一批单元测试，作为后续测试的范例。
        3. 设定 CI 流程，在代码提交时自动运行单元测试。

---

### **第二阶段：生产级 AI 引擎与异步处理 (预计 2-3 周)**

**目标：将 AI 引擎从原型升级为生产可用状态，并优化性能。**

- **[ ] 任务 2.1：集成生产级向量数据库**
    - **描述：** 当前的 `InMemoryVectorStore` 无法用于生产环境。
    - **关键步骤：**
        1. 选择并部署一个向量数据库（如 Milvus 或 Qdrant）。
        2. 在 `starrynight-engine` 模块中创建新的 `VectorStore` 实现（例如 `QdrantVectorStore`）。
        3. 在 `EngineConfig` 中使用 Spring Profile，实现在开发环境使用内存存储，在生产环境切换到向量数据库。

- **[ ] 任务 2.2：集成 RabbitMQ 消息队列**
    - **描述：** 部分耗时操作（如文档切片）会阻塞主线程，影响用户体验。
    - **关键步骤：**
        1. 在 `pom.xml` 中添加 `spring-boot-starter-amqp` 依赖。
        2. 定义消息队列，例如 `topic.knowledge.chunking`。
        3. 将 `KnowledgeLibraryService` 中的文档切片逻辑改造为异步处理：
            - 上传成功后，发送一条包含文档ID的消息到队列。
            - 创建一个消费者服务监听该队列，并执行实际的切片和向量化工作。

---

### **第三阶段：补全后台管理功能 (预计 1 周)**

**目标：完成运营后台所有模块的后端逻辑。**

- **[ ] 任务 3.1：实现缺失的运营后台 API**
    - **描述：** 公告、AI配置、运营账号、操作日志等模块仅有前端页面，后端逻辑缺失。
    - **关键步骤：**
        1. 创建 `AdminAnnouncementService` 并实现 CRUD 接口。
        2. 创建 `AdminAIConfigService` 用于管理 AI 模型参数。
        3. 创建 `OpsAccountService` 用于管理运营人员账号。
        4. （可选）集成 AOP 实现全局操作日志记录。

---

### **第四阶段：高级 AI 功能研发 (预计 2-3 周)**

**目标：根据核心设计文档，实现项目独特的 AI 功能。**

- **[ ] 任务 4.1：实现引擎高级功能**
    - **描述：** `core-logic.md` 中定义的节奏分析、伏笔管理等高级功能尚未实现。
    - **关键步骤：**
        1. 在 `starrynight-engine` 中创建 `RhythmAnalysisEngine`，根据文本分析生成情绪曲线和节奏数据。
        2. 创建 `ForeshadowingEngine`，用于识别和追踪文中的伏笔。
        3. 在 `ChapterWorkshopService` 中集成这些新引擎，为用户提供更深度的写作分析。
