# StarryNight Backend

## 运行配置

本项目支持从外部配置文件读取配置。

### 方式一：使用项目根目录配置文件
```bash
java -jar starrynight-backend.jar --spring.config.location=../application.yml
```

### 方式二：使用 Maven 启动
```bash
mvn spring-boot:run -Dspring-boot.run.arguments="--spring.config.location=../application.yml"
```

### 方式三：设置环境变量
```bash
export SPRING_CONFIG_LOCATION=../application.yml
mvn spring-boot:run
```

### 方式四：IDEA 配置
在 Run Configuration 中添加：
- Program arguments: `--spring.config.location=../application.yml`
- Working directory: `$MODULE_DIR$`

## 数据库配置

数据库脚本位于：
- `src/main/resources/db/schema.sql` - 数据库创建脚本
- `src/main/resources/db/table.sql` - 数据表创建脚本

请先执行这些脚本创建数据库和表结构。

## 开发说明

### 目录结构
```
backend/
├── src/main/java/com/starrynight/
│   ├── common/          # 公共组件
│   ├── auth/            # 认证模块
│   ├── user/            # 用户模块
│   ├── novel/           # 作品模块
│   └── ...              # 其他业务模块
├── src/main/resources/
│   ├── application.yml  # 应用配置（开发用）
│   └── db/              # 数据库脚本
└── pom.xml
```

### 模块说明
- **common**: 公共模块，包含异常处理、工具类、通用配置
- **auth**: 认证模块，用户注册、登录、JWT 令牌管理
- **user**: 用户模块，用户信息、会员管理
- **novel**: 作品模块，作品、卷、章节、大纲管理
