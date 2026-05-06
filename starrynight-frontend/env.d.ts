/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_ADMIN_PATH: string
  /** 可选：浏览器访问后端的站点根（无尾斜杠、不含 /api），分域部署时拼接接口前缀 */
  readonly VITE_API_PUBLIC_ORIGIN?: string
  /**
   * 仅 Vite 开发服务器读取（见 vite.config）：把 /api 代理到的后端根地址，默认 http://127.0.0.1:8080
   * 可在 .env.development.local 中覆盖，例如后端跑在 8081 时写 http://127.0.0.1:8081
   */
  readonly VITE_DEV_API_PROXY?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
  export default component
}
