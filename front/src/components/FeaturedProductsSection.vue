<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
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
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = 8
const loadingMore = ref(false)
const sentinel = ref(null)
let observer = null

const hasMore = computed(() => currentPage.value < lastPage.value)

const loadMore = async () => {
  if (loadingMore.value || !hasMore.value) return
  loadingMore.value = true
  try {
    const res = await productsApi.getPage(currentPage.value + 1, perPage)
    products.value.push(...res.data)
    currentPage.value = res.current_page
    lastPage.value = res.last_page
  } catch (e) {
    console.error('Failed to load more products:', e)
  } finally {
    loadingMore.value = false
  }
}

const initObserver = () => {
  if (!observer) {
    observer = new IntersectionObserver((entries) => {
      if (entries[0].isIntersecting) {
        loadMore()
      }
    }, { rootMargin: '200px' })
  }
}

// Watch sentinel ref - observe when it appears, disconnect when it disappears
watch(sentinel, (el, oldEl) => {
  if (oldEl) observer?.unobserve(oldEl)
  if (el) {
    initObserver()
    observer.observe(el)
  }
})

onMounted(async () => {
  try {
    const res = await productsApi.getPage(1, perPage)
    products.value = res.data
    currentPage.value = res.current_page
    lastPage.value = res.last_page
  } catch (error) {
    console.error('Failed to fetch featured products:', error)
  } finally {
    loading.value = false
    await nextTick()
    if (sentinel.value) {
      initObserver()
      observer.observe(sentinel.value)
    }
  }
})

onUnmounted(() => {
  observer?.disconnect()
})

const handleAddToCart = (productId) => {
  const product = products.value.find(p => p.id === productId)
  if (product) {
    addToCart({
      id: product.id,
      name: product.name,
      color: product.colors[0]?.name,
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
    <div v-else>
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
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

      <!-- Loading more spinner -->
      <div v-if="loadingMore" class="flex justify-center py-8">
        <div class="w-8 h-8 border-4 border-[#5B3A8C] border-t-transparent rounded-full animate-spin"></div>
      </div>

      <!-- Scroll sentinel -->
      <div ref="sentinel" v-if="hasMore" class="h-1"></div>
    </div>
  </section>
</template>
