<script setup>
defineProps({
  discount: {
    type: String,
    default: '50%'
  },
  subtitle: {
    type: String,
    default: 'LIMITED TIME SALE'
  },
  buttonText: {
    type: String,
    default: 'Shop all'
  },
  buttonLink: {
    type: String,
    default: '/sale'
  }
})
</script>

<template>
  <section class="relative min-h-[650px] md:min-h-[450px] bg-gold flex items-center justify-center overflow-hidden">
    <!-- Background shadow text -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 translate-x-[15px] translate-y-[15px] text-black/15 whitespace-nowrap z-[1] select-none font-black leading-[0.8] tracking-[-15px] md:tracking-[-8px] text-[clamp(250px,45vw,550px)] font-display">
      50%
    </div>

    <!-- Main 50% text -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-gray-900 whitespace-nowrap z-[2] font-black leading-[0.8] tracking-[-15px] md:tracking-[-8px] text-[clamp(250px,45vw,550px)] font-display">
      50%
    </div>

    <!-- Center content -->
    <div class="relative z-[3] text-center flex flex-col items-center gap-8">
      <div class="text-[clamp(18px,3vw,32px)] md:text-sm font-light text-gray-900 tracking-[12px] md:tracking-[6px] uppercase">
        {{ subtitle }}
      </div>
      <a
        :href="buttonLink"
        class="inline-block py-3.5 px-10 md:py-3 md:px-8 bg-transparent text-gray-900 no-underline text-sm md:text-xs font-normal tracking-wide border border-gray-900 transition-all duration-300 hover:bg-gray-900 hover:text-white"
      >
        {{ buttonText }}
      </a>
    </div>
  </section>
</template>
