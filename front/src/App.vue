<script setup>
import { onMounted } from 'vue'
import AnnouncementBar from './components/AnnouncementBar.vue'
import SiteHeader from './components/SiteHeader.vue'
import AboutSection from './components/AboutSection.vue'
import SiteFooter from './components/SiteFooter.vue'
import CartDrawer from './components/CartDrawer.vue'
import { useLanguage } from './composables/useLanguage'

const { initLanguage } = useLanguage()

onMounted(() => {
  initLanguage()
})
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <AnnouncementBar v-if="false"/>
    <SiteHeader />
    <main class="flex-1">
      <RouterView />
    </main>
    <AboutSection />
    <SiteFooter />
    <CartDrawer />
  </div>
</template>
