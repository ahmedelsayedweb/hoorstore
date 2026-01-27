<script setup>
import { ref } from 'vue'

const selectedCountry = ref('Egypt | EGP ج.م')
const selectedLanguage = ref('English')
const isCountryOpen = ref(false)
const isLanguageOpen = ref(false)

const countries = [
  'Egypt | EGP ج.م',
  'UAE | AED',
  'KSA | SAR'
]

const languages = ['English', 'العربية']

const footerLinks = [
  { name: 'Refund policy', url: '/refund-policy' },
  { name: 'Privacy policy', url: '/privacy-policy' },
  { name: 'Terms of service', url: '/terms' },
  { name: 'Shipping policy', url: '/shipping-policy' },
  { name: 'Contact information', url: '/contact' }
]
</script>

<template>
  <footer class="bg-gray-50 border-t border-gray-200">
    <div class="max-w-7xl mx-auto px-5 lg:px-8 py-10">
      <!-- Selectors -->
      <div class="flex flex-wrap gap-6 mb-8">
        <!-- Country/Region -->
        <div>
          <label class="block text-sm text-gray-600 mb-2">Country/region</label>
          <div class="relative">
            <button
              @click="isCountryOpen = !isCountryOpen"
              class="flex items-center justify-between gap-8 px-4 py-2.5 bg-white border border-gray-300 text-sm text-gray-900 min-w-[180px]"
            >
              {{ selectedCountry }}
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              v-if="isCountryOpen"
              class="absolute bottom-full left-0 mb-1 bg-white border border-gray-300 shadow-lg min-w-[180px] z-10"
            >
              <button
                v-for="country in countries"
                :key="country"
                @click="selectedCountry = country; isCountryOpen = false"
                class="block w-full text-left px-4 py-2.5 text-sm text-gray-900 hover:bg-gray-50"
              >
                {{ country }}
              </button>
            </div>
          </div>
        </div>

        <!-- Language -->
        <div>
          <label class="block text-sm text-gray-600 mb-2">Language</label>
          <div class="relative">
            <button
              @click="isLanguageOpen = !isLanguageOpen"
              class="flex items-center justify-between gap-8 px-4 py-2.5 bg-white border border-gray-300 text-sm text-gray-900 min-w-[140px]"
            >
              {{ selectedLanguage }}
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              v-if="isLanguageOpen"
              class="absolute bottom-full left-0 mb-1 bg-white border border-gray-300 shadow-lg min-w-[140px] z-10"
            >
              <button
                v-for="lang in languages"
                :key="lang"
                @click="selectedLanguage = lang; isLanguageOpen = false"
                class="block w-full text-left px-4 py-2.5 text-sm text-gray-900 hover:bg-gray-50"
              >
                {{ lang }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Copyright & Links -->
      <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
        <span>&copy; {{ new Date().getFullYear() }}, Hoor Store</span>
        <template v-for="(link, index) in footerLinks" :key="link.name">
          <span class="text-gray-300">·</span>
          <a :href="link.url" class="hover:text-gray-900 transition-colors">{{ link.name }}</a>
        </template>
      </div>
    </div>
  </footer>
</template>
