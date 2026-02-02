<script setup>
import { ref, computed } from 'vue'
import { useCart } from '../composables/useCart'
import { useLanguage } from '../composables/useLanguage'

const { openCart, cartCount } = useCart()
const { locale, currentLanguage, setLanguage, t } = useLanguage()

const navItems = computed(() => [
  { name: t('nav.home'), link: '/', active: true },
  { name: t('nav.children'), link: '/collection?category=children' },
  { name: t('nav.men'), link: '/collection?category=men' },
  { name: t('nav.women'), link: '/collection?category=Woman' }
])

const isCountryDropdownOpen = ref(false)
const isLanguageDropdownOpen = ref(false)
const isMobileMenuOpen = ref(false)

const toggleLanguageDropdown = () => {
  isLanguageDropdownOpen.value = !isLanguageDropdownOpen.value
  isCountryDropdownOpen.value = false
}

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

const changeLanguage = (lang) => {
  setLanguage(lang)
  isLanguageDropdownOpen.value = false
}
</script>

<template>
  <header class="bg-white border-b border-gray-100 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 py-0 flex items-center justify-between gap-8">
      <!-- Mobile Menu Button -->
      <button @click="toggleMobileMenu" class="md:hidden p-1 bg-transparent border-none cursor-pointer">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"></line>
          <line x1="3" y1="12" x2="21" y2="12"></line>
          <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
      </button>

      <!-- Logo -->
      <div>
        <a href="/"><img src="/logo.png" class="w-[100px]" /></a>
      </div>

      <!-- Navigation -->
      <nav
        class="flex-1 hidden md:block"
        :class="{ '!block absolute top-full left-0 right-0 bg-white border-b border-gray-100 p-5 z-50': isMobileMenuOpen }"
      >
        <ul class="flex items-center gap-8 list-none m-0 p-0" :class="{ 'flex-col !items-start !gap-4': isMobileMenuOpen }">
          <li v-for="item in navItems" :key="item.name">
            <a
              :href="item.link"
              class="text-gray-900 no-underline text-sm font-normal transition-colors duration-300 relative"
              :class="{ 'after:content-[\'\'] after:absolute after:-bottom-1 after:left-0 after:w-full after:h-px after:bg-gray-900': item.active }"
            >
              {{ item.name }}
            </a>
          </li>
        </ul>
      </nav>

      <!-- Right Section -->
      <div class="flex items-center gap-4">
        <!-- Language Selector -->
        <div class="relative hidden lg:block">
          <button @click="toggleLanguageDropdown" class="flex items-center gap-1 bg-transparent border-none cursor-pointer text-sm text-gray-900 px-2 py-1 hover:opacity-70">
            {{ currentLanguage }}
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
          </button>
          <div v-if="isLanguageDropdownOpen" class="absolute top-full ltr:right-0 rtl:left-0 bg-white border border-gray-100 rounded shadow-lg min-w-[150px] z-50">
            <a href="#" @click.prevent="changeLanguage('en')" class="block px-4 py-2.5 text-gray-900 no-underline text-sm hover:bg-gray-50" :class="{ 'bg-gray-100': locale === 'en' }">English</a>
            <a href="#" @click.prevent="changeLanguage('ar')" class="block px-4 py-2.5 text-gray-900 no-underline text-sm hover:bg-gray-50" :class="{ 'bg-gray-100': locale === 'ar' }">العربية</a>
          </div>
        </div>

        <!-- Icons -->
        <div class="flex items-center gap-4">
          <!-- Search -->
          <button class="bg-transparent border-none cursor-pointer p-1 text-gray-900 hover:opacity-60 transition-opacity" aria-label="Search">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </button>

          <!-- Account -->
          <button class="bg-transparent border-none cursor-pointer p-1 text-gray-900 hover:opacity-60 transition-opacity" aria-label="Account">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </button>

          <!-- Cart -->
          <button @click="openCart" class="bg-transparent border-none cursor-pointer p-1 text-gray-900 hover:opacity-60 transition-opacity relative" aria-label="Cart">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M6 6h15l-1.5 9h-12z"></path>
              <circle cx="9" cy="20" r="1"></circle>
              <circle cx="18" cy="20" r="1"></circle>
              <path d="M6 6L4 2H1"></path>
            </svg>
            <span
              v-if="cartCount > 0"
              class="absolute -top-1 -right-1 w-5 h-5 bg-gray-900 text-white text-xs rounded-full flex items-center justify-center"
            >
              {{ cartCount }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </header>
</template>
