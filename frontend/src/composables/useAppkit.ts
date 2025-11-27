import { onMounted, ref } from 'vue'

export function useAppkit() {
  const theme = ref<'light' | 'dark'>('light')

  const loadTheme = () => {
    const savedTheme = localStorage.getItem('app-theme') as 'light' | 'dark' | null
    if (savedTheme) {
      theme.value = savedTheme
    } else {
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
      theme.value = prefersDark ? 'dark' : 'light'
    }
    applyTheme()
  }

  const applyTheme = () => {
    document.body.classList.remove('theme-light', 'theme-dark')
    document.body.classList.add(`theme-${theme.value}`)
    
    // También aplicar al html para que los selectores funcionen
    document.documentElement.classList.remove('theme-light', 'theme-dark')
    document.documentElement.classList.add(`theme-${theme.value}`)
  }

  const toggleTheme = () => {
    theme.value = theme.value === 'light' ? 'dark' : 'light'
    localStorage.setItem('app-theme', theme.value)
    applyTheme()
  }

  const init = () => {
    loadTheme()
  }

  onMounted(() => {
    init()
  })

  return {
    theme,
    toggleTheme,
    init
  }
}
