import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { PortalPublicConfig } from '@/api/portalPublic'

export const usePortalPublicStore = defineStore('portalPublic', () => {
  const config = ref<PortalPublicConfig>({})

  function hydrate(data: PortalPublicConfig) {
    config.value = { ...data }
  }

  const siteName = computed(() => (config.value.siteName || '').trim() || '星夜阁')
  const siteLogoUrl = computed(() => (config.value.siteLogoUrl || '').trim())
  const platformCoinName = computed(() => (config.value.platformCoinName || '').trim() || '星夜币')

  return { config, hydrate, siteName, siteLogoUrl, platformCoinName }
})
