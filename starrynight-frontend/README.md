# StarryNight Frontend

## 运行配置

本项目支持通过环境变量配置 API 代理地址。

### 开发模式
```bash
npm install
npm run dev
```

### 构建生产版本
```bash
npm run build
```

### 前端代理配置

前端开发服务器默认代理 `/api` 请求到 `http://localhost:8080`。

如需修改，编辑 `vite.config.ts`：
```typescript
server: {
  proxy: {
    '/api': {
      target: 'http://your-backend-url:8080',
      changeOrigin: true
    }
  }
}
```

## 页面说明

### 用户端
- `/auth/login` - 登录页
- `/auth/register` - 注册页
- `/home` - 首页
- `/author` - 作者中心
- `/novel/:id` - 作品详情

### 运营端
- `/admin/dashboard` - 仪表盘
- `/admin/users` - 用户管理
- `/admin/novels` - 作品管理

## 技术栈
- Vue 3.5
- TypeScript
- Vite 8.0
- Pinia (状态管理)
- Vue Router 4
- Element Plus
- Axios
