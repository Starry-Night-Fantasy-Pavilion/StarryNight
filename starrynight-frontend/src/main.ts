import { createApp } from 'vue'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import ElementPlus from 'element-plus'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import 'element-plus/dist/index.css'
import 'element-plus/theme-chalk/dark/css-vars.css'

import App from './App.vue'
import router from './router'
import './styles/index.scss'
import apiClient from '@/utils/request'
import { applyRuntimeApiPublicOriginFromServer, syncAxiosApiBase } from '@/config/apiBase'
import { fetchPortalPublicConfig, type PortalPublicConfig } from '@/api/portalPublic'
import { usePortalPublicStore } from '@/stores/portalPublic'
import { setCommunityAutoPublishFromPortal } from '@/config/communityPublish'

try {
  localStorage.removeItem('auth')
} catch {
  /* ignore */
}

async function bootstrap() {
  let portalCfg: PortalPublicConfig = {}
  try {
    portalCfg = await fetchPortalPublicConfig()
    applyRuntimeApiPublicOriginFromServer(portalCfg.apiPublicOrigin)
    setCommunityAutoPublishFromPortal(portalCfg.communityAutoPublishPosts)
  } catch {
    /* 离线或接口未就绪时沿用相对 /api */
  }
  syncAxiosApiBase(apiClient)

  const app = createApp(App)

  const pinia = createPinia()
  pinia.use(piniaPluginPersistedstate)

  for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
    app.component(key, component)
  }

  app.use(pinia)
  usePortalPublicStore().hydrate(portalCfg)
  app.use(router)
  app.use(ElementPlus)

  app.mount('#app')
}

void bootstrap()
