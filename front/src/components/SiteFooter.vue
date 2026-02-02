<script setup>
import { ref } from 'vue'
import { useLanguage } from '../composables/useLanguage'

const { locale, currentLanguage, setLanguage, t } = useLanguage()
const isLanguageDropdownOpen = ref(false)
const toggleLanguageDropdown = () => {
  isLanguageDropdownOpen.value = !isLanguageDropdownOpen.value
  isCountryDropdownOpen.value = false
}
const changeLanguage = (lang) => {
  setLanguage(lang)
  isLanguageDropdownOpen.value = false
}
</script>

<template>
  <footer class="bg-gray-50 border-t border-gray-200">
    <div class="max-w-7xl flex items-center justify-between mx-auto px-5 lg:px-8 py-4">    
      <!-- Copyright & Links -->
      <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
        <span>&copy; {{ new Date().getFullYear() }}, Hoor Store</span>
      </div>
      <div class="relative hidden lg:block">
          <button @click="toggleLanguageDropdown" class="flex items-center gap-1 bg-transparent border-none cursor-pointer text-sm text-gray-900 px-2 py-1 hover:opacity-70">
            {{ currentLanguage }}
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div v-if="isLanguageDropdownOpen" class="absolute bottom-full ltr:right-0 rtl:left-0 bg-white border border-gray-100 rounded shadow-lg min-w-[150px] z-50 mb-1">
            <a href="#" @click.prevent="changeLanguage('en')" class="block px-4 py-2.5 text-gray-900 no-underline text-sm hover:bg-gray-50" :class="{ 'bg-gray-100': locale === 'en' }">English</a>
            <a href="#" @click.prevent="changeLanguage('ar')" class="block px-4 py-2.5 text-gray-900 no-underline text-sm hover:bg-gray-50" :class="{ 'bg-gray-100': locale === 'ar' }">العربية</a>
          </div>
        </div>
    </div>
  </footer>
</template>
