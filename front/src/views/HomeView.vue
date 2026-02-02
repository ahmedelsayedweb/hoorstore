<script setup>
import HeroSection from '../components/HeroSection.vue'
import SaleProductsSection from '../components/SaleProductsSection.vue'
import FeaturedProductsSection from '../components/FeaturedProductsSection.vue'
</script>

<template>
  <HeroSection
    discount="50%"
    subtitle="LIMITED TIME SALE"
    buttonText="Shop all"
    buttonLink="/collections"
  />
  <SaleProductsSection
    v-if="false"
    title="White Friday Sale Up to 50%"
    viewAllLink="/collections"
  />
  <FeaturedProductsSection title="Featured products" />
</template>
