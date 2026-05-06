/** 运营控制台前端路由前缀（与 router、axios 选 Token 逻辑保持一致） */
export const ADMIN_CONSOLE_BASE_PATH = (import.meta.env.VITE_ADMIN_PATH || '/admin').replace(/\/$/, '')

/** 运营登录页（与 /auth 用户登录隔离，挂在控制台同前缀下） */
export const ADMIN_OPS_LOGIN_PATH = `${ADMIN_CONSOLE_BASE_PATH}/login`
