<script setup>
import { useLanguage } from '../composables/useLanguage'

const { t, isRTL } = useLanguage()
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-pink-50 flex items-center justify-center px-4" :dir="isRTL ? 'rtl' : 'ltr'">
    <div class="max-w-2xl w-full text-center">
      <!-- Animated 404 Number -->
      <div class="relative mb-8">
        <h1 class="text-[180px] md:text-[220px] font-bold text-primary/10 select-none leading-none">
          404
        </h1>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="bg-white/80 backdrop-blur-sm rounded-2xl px-8 py-4 shadow-lg">
            <svg class="w-20 h-20 md:w-24 md:h-24 text-primary mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Message -->
      <h2 class="text-2xl md:text-3xl font-bold text-gray-800 mb-4">
        {{ t('notFound.title') }}
      </h2>
      <p class="text-gray-500 mb-8 max-w-md mx-auto">
        {{ t('notFound.message') }}
      </p>

      <!-- Actions -->
      <div class="flex flex-col sm:flex-row gap-4 justify-center">
        <a
          href="/"
          class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-primary text-white font-semibold rounded-xl hover:bg-primary/90 transition-all duration-300 shadow-lg shadow-primary/25 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          {{ t('notFound.goHome') }}
        </a>
        <button
          @click="$router.go(-1)"
          class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-gray-700 font-semibold rounded-xl border-2 border-gray-200 hover:border-primary hover:text-primary transition-all duration-300"
        >
          <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          {{ t('notFound.goBack') }}
        </button>
      </div>

      <!-- Decorative Elements -->
      <div class="mt-16 flex justify-center gap-8 opacity-30">
        <div class="w-16 h-16 rounded-full bg-primary/20 animate-pulse"></div>
        <div class="w-12 h-12 rounded-full bg-pink-300/30 animate-pulse delay-150"></div>
        <div class="w-8 h-8 rounded-full bg-purple-300/30 animate-pulse delay-300"></div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.delay-150 {
  animation-delay: 150ms;
}
.delay-300 {
  animation-delay: 300ms;
}
</style>
