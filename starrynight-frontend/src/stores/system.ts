import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import type { SystemConfigItem } from '@/types/api'
import { listSystemConfigs, getSystemConfig, updateSystemConfig } from '@/api/systemConfig'

export type Theme = 'light' | 'dark' | 'auto'
export type FontSize = 'small' | 'medium' | 'large'
export type EditorFontSize = 14 | 16 | 18

export interface SystemSettings {
  theme: Theme
  fontSize: FontSize
  editorFontSize: EditorFontSize
  editorLineHeight: number
  editorWordWrap: boolean
  autoSaveInterval: number
  showLineNumbers: boolean
  enableSpellCheck: boolean
  language: string
}

export const useSystemStore = defineStore('system', () => {
  const config = ref<SystemConfigItem[]>([])
  const loading = ref(false)
  const settings = ref<SystemSettings>({
    theme: 'auto',
    fontSize: 'medium',
    editorFontSize: 16,
    editorLineHeight: 1.8,
    editorWordWrap: true,
    autoSaveInterval: 30000,
    showLineNumbers: true,
    enableSpellCheck: true,
    language: 'zh-CN'
  })
  const isDarkMode = ref(false)
  const sidebarCollapsed = ref(false)
  const notificationCount = ref(0)

  const themeClass = computed(() => {
    if (settings.value.theme === 'auto') {
      return isDarkMode.value ? 'dark' : 'light'
    }
    return settings.value.theme
  })

  const effectiveFontSize = computed(() => {
    const sizeMap: Record<FontSize, string> = {
      small: '12px',
      medium: '14px',
      large: '16px'
    }
    return sizeMap[settings.value.fontSize]
  })

  function applyTheme() {
    const html = document.documentElement
    if (settings.value.theme === 'auto') {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      html.classList.toggle('dark', prefersDark)
      isDarkMode.value = prefersDark
    } else {
      html.classList.toggle('dark', settings.value.theme === 'dark')
      isDarkMode.value = settings.value.theme === 'dark'
    }
  }

  function setTheme(theme: Theme) {
    settings.value.theme = theme
    applyTheme()
    saveSettings()
  }

  function toggleSidebar() {
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  function setSidebarCollapsed(collapsed: boolean) {
    sidebarCollapsed.value = collapsed
  }

  function setFontSize(fontSize: FontSize) {
    settings.value.fontSize = fontSize
    saveSettings()
  }

  function setEditorFontSize(fontSize: EditorFontSize) {
    settings.value.editorFontSize = fontSize
    saveSettings()
  }

  function setEditorLineHeight(lineHeight: number) {
    settings.value.editorLineHeight = lineHeight
    saveSettings()
  }

  function setEditorWordWrap(wordWrap: boolean) {
    settings.value.editorWordWrap = wordWrap
    saveSettings()
  }

  function setAutoSaveInterval(interval: number) {
    settings.value.autoSaveInterval = interval
    saveSettings()
  }

  function setShowLineNumbers(show: boolean) {
    settings.value.showLineNumbers = show
    saveSettings()
  }

  function setEnableSpellCheck(enable: boolean) {
    settings.value.enableSpellCheck = enable
    saveSettings()
  }

  function setNotificationCount(count: number) {
    notificationCount.value = count
  }

  async function fetchSystemConfig() {
    loading.value = true
    try {
      const res = await listSystemConfigs()
      if (res.data) {
        config.value = res.data
        applyConfigToSettings(res.data)
      }
    } finally {
      loading.value = false
    }
  }

  function applyConfigToSettings(configList: SystemConfigItem[]) {
    configList.forEach(item => {
      switch (item.configKey) {
        case 'theme':
          if (item.configValue && ['light', 'dark', 'auto'].includes(item.configValue)) {
            settings.value.theme = item.configValue as Theme
          }
          break
        case 'fontSize':
          if (item.configValue && ['small', 'medium', 'large'].includes(item.configValue)) {
            settings.value.fontSize = item.configValue as FontSize
          }
          break
        case 'language':
          if (item.configValue) {
            settings.value.language = item.configValue
          }
          break
      }
    })
  }

  function saveSettings() {
    try {
      localStorage.setItem('systemSettings', JSON.stringify(settings.value))
    } catch (e) {
      console.warn('Failed to save settings to localStorage:', e)
    }
  }

  function loadSettings() {
    try {
      const saved = localStorage.getItem('systemSettings')
      if (saved) {
        const parsed = JSON.parse(saved)
        settings.value = { ...settings.value, ...parsed }
      }
    } catch (e) {
      console.warn('Failed to load settings from localStorage:', e)
    }
    applyTheme()
  }

  function resetSettings() {
    settings.value = {
      theme: 'auto',
      fontSize: 'medium',
      editorFontSize: 16,
      editorLineHeight: 1.8,
      editorWordWrap: true,
      autoSaveInterval: 30000,
      showLineNumbers: true,
      enableSpellCheck: true,
      language: 'zh-CN'
    }
    saveSettings()
    applyTheme()
  }

  watch(() => settings.value.theme, () => {
    applyTheme()
  })

  return {
    config,
    loading,
    settings,
    isDarkMode,
    sidebarCollapsed,
    notificationCount,
    themeClass,
    effectiveFontSize,
    applyTheme,
    setTheme,
    toggleSidebar,
    setSidebarCollapsed,
    setFontSize,
    setEditorFontSize,
    setEditorLineHeight,
    setEditorWordWrap,
    setAutoSaveInterval,
    setShowLineNumbers,
    setEnableSpellCheck,
    setNotificationCount,
    fetchSystemConfig,
    loadSettings,
    saveSettings,
    resetSettings
  }
})
