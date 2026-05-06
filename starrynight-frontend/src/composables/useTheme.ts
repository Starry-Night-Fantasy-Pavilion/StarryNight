import { ref, watch } from 'vue'

type Theme = 'light' | 'dark'

const THEME_KEY = 'starrynight-admin-theme'

const currentTheme = ref<Theme>((localStorage.getItem(THEME_KEY) as Theme) || 'light')

function applyTheme(theme: Theme) {
  document.documentElement.setAttribute('data-theme', theme)
}

function toggleTheme() {
  currentTheme.value = currentTheme.value === 'dark' ? 'light' : 'dark'
}

watch(currentTheme, (theme) => {
  localStorage.setItem(THEME_KEY, theme)
  applyTheme(theme)
}, { immediate: true })

export function useTheme() {
  return {
    theme: currentTheme,
    toggleTheme,
    isDark: () => currentTheme.value === 'dark'
  }
}
