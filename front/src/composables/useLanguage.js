import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'

export function useLanguage() {
  const { locale, t } = useI18n()

  const isRTL = computed(() => locale.value === 'ar')
  const currentLanguage = computed(() => locale.value === 'ar' ? 'العربية' : 'English')

  const setLanguage = (lang) => {
    locale.value = lang
    localStorage.setItem('locale', lang)
    updateDirection(lang)
  }

  const toggleLanguage = () => {
    const newLang = locale.value === 'ar' ? 'en' : 'ar'
    setLanguage(newLang)
  }

  const updateDirection = (lang) => {
    document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr'
    document.documentElement.lang = lang
  }

  // Initialize direction on load
  const initLanguage = () => {
    const savedLocale = localStorage.getItem('locale') || 'ar'
    locale.value = savedLocale
    updateDirection(savedLocale)
  }

  // Watch for locale changes
  watch(locale, (newLocale) => {
    updateDirection(newLocale)
  })

  return {
    locale,
    isRTL,
    currentLanguage,
    setLanguage,
    toggleLanguage,
    initLanguage,
    t
  }
}
