/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_ADMIN_PATH: string
  /** 可选：浏览器访问后端的站点根（无尾斜杠、不含 /api），分域部署时拼接接口前缀 */
  readonly VITE_API_PUBLIC_ORIGIN?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}

declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>
  export default component
}
