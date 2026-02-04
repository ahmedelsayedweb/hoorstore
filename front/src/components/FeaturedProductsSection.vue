<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import ProductCard from './ProductCard.vue'
import { productsApi } from '@/api/products'
import { useCart } from '@/composables/useCart'

const { t } = useI18n()
const { addToCart } = useCart()

defineProps({
  title: {
    type: String,
    default: ''
  }
})

const products = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    products.value = await productsApi.getAll()
  } catch (error) {
    console.error('Failed to fetch featured products:', error)
  } finally {
    loading.value = false
  }
})

const handleAddToCart = (productId) => {
  const product = products.value.find(p => p.id === productId)
  if (product) {
    addToCart({
      id: product.id,
      name: product.name,
      color: product.colors[0].name,
      sizes: [product.sizes[0]],
      price: product.salePrice || product.price,
      image: product.image,
      quantity: 1,
      hasSizesAvailable: false,
      hasColorsAvailable: false
    })
  }
}
</script>

<template>
  <section class="py-12 px-4 md:px-8 lg:px-16">
    <!-- Section Title with Underline -->
    <h2 class="text-2xl md:text-3xl font-normal text-gray-900 mb-10">
      <span class="border-b-2 border-gray-900 pb-2">{{ t('home.featuredProducts') }}</span>
    </h2>
    <!-- Loading State -->
    <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
      <div v-for="n in 8" :key="n" class="animate-pulse">
        <div class="bg-gray-200 aspect-[3/4] rounded-lg mb-3"></div>
        <div class="bg-gray-200 h-4 rounded w-3/4 mb-2"></div>
        <div class="bg-gray-200 h-4 rounded w-1/2"></div>
      </div>
    </div>

    <!-- Products Grid -->
    
    <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
      <ProductCard
        v-for="product in products"
        :key="product.id"
        :id="product.id"
        :image="product.image"
        :name="product.name"
        :original-price="product.price"
        :sale-price="product.salePrice"
        :is-on-sale="product.isOnSale"
        @add-to-cart="handleAddToCart"
      />
    </div>
  </section>
</template>
