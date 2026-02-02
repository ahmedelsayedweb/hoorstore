<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  discount: {
    type: String,
    default: '50'
  },
  subtitle: {
    type: String,
    default: ''
  },
  buttonText: {
    type: String,
    default: ''
  },
  buttonLink: {
    type: String,
    default: '/collection'
  },
  backgroundImage: {
    type: String,
    default: '/bgCover.png'
  }
})
</script>

<template>
  <section
    class="relative min-h-[600px] md:min-h-[500px] flex bg-top bg-fixed items-end justify-center overflow-hidden bg-cover bg-center bg-no-repeat"
    :style="{ backgroundImage: `url(${backgroundImage})`, }"
  >
    <!-- Overlay for better text readability -->
    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>

    <!-- Main content -->
    <div class="relative z-10 text-center pb-16 md:pb-12">
      <!-- 50% OFF text -->
      <div dir="rtl" class="flex items-baseline justify-center gap-2 mb-2">
        <span class="text-primary font-black text-[clamp(60px,12vw,100px)] leading-none drop-shadow-lg" style="text-shadow: 4px 4px #fff;">
          {{ t('hero.off') }}
        </span>
        <span class="text-primary font-black text-[clamp(120px,25vw,200px)] leading-none drop-shadow-lg" style=" text-shadow: 4px 4px #fff;">
          {{ discount }}
        </span>
      </div>

      <!-- LIMITED TIME SALE subtitle -->
      <p class="text-white text-[clamp(14px,2.5vw,20px)] tracking-[0.3em] md:tracking-[0.2em] uppercase mb-8 drop-shadow-md font-light">
        {{ t('hero.limitedTimeSale') }}
      </p>

      <!-- SHOP NOW button -->
      <a
        :href="buttonLink"
        class="inline-block py-4 px-12 md:py-3 md:px-10 bg-primary text-white no-underline text-sm md:text-xs font-semibold tracking-wider uppercase transition-all duration-300 hover:bg-primary-dark hover:shadow-lg"
      >
        {{ t('hero.shopAll') }}
      </a>
    </div>
  </section>
</template>
