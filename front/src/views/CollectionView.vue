<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import ProductCard from '../components/ProductCard.vue'
import AboutSection from '../components/AboutSection.vue'
import SiteFooter from '../components/SiteFooter.vue'
import { useCart } from '@/composables/useCart'
import { productsApi } from '@/api/products'

const { t } = useI18n()
const { addToCart } = useCart()
const route = useRoute()

// Products data
const products = ref([])
const loading = ref(true)

// Category tabs
const categories = computed(() => [
  { value: 'all', label: t('collection.all') },
  { value: 'children', label: t('nav.children') },
  { value: 'men', label: t('nav.men') },
  { value: 'woman', label: t('nav.woman') }
])

// Initialize category from URL query parameter
const validCategories = ['all', 'children', 'men', 'woman']
const getCategoryFromQuery = () => {
  const queryCategory = route.query.category;
  return validCategories.includes(queryCategory) ? queryCategory : 'all'
}

const selectedCategory = ref(getCategoryFromQuery())

// Watch for URL query changes
watch(() => route.query.category, (newCategory) => {
  selectedCategory.value = validCategories.includes(newCategory) ? newCategory : 'all'
})

// Hide tabs when category is specified in URL
const hasCategoryInUrl = computed(() => !!route.query.category)

// Filter states
const isAvailabilityOpen = ref(false)
const isPriceOpen = ref(false)
const isSortOpen = ref(false)

const selectedSort = ref('featured')

const sortOptions = computed(() => [
  { value: 'featured', label: t('collection.featured') },
  { value: 'price-asc', label: t('collection.priceLowToHigh') },
  { value: 'price-desc', label: t('collection.priceHighToLow') },
  { value: 'newest', label: t('collection.newest') }
])

// Filter products by category
const filteredProducts = computed(() => {
  if (selectedCategory.value === 'all') {
    return products.value
  }
  return products.value.filter(p => p.category === selectedCategory.value)
})

// Sort filtered products
const sortedProducts = computed(() => {
  const sorted = [...filteredProducts.value]
  switch (selectedSort.value) {
    case 'price-asc':
      return sorted.sort((a, b) => (a.salePrice || a.price) - (b.salePrice || b.price))
    case 'price-desc':
      return sorted.sort((a, b) => (b.salePrice || b.price) - (a.salePrice || a.price))
    default:
      return sorted
  }
})

const closeAllDropdowns = () => {
  isAvailabilityOpen.value = false
  isPriceOpen.value = false
  isSortOpen.value = false
}

const handleAddToCart = (productId) => {
  const product = products.value.find(p => p.id === productId)
  if (product) {
    // Check if product has sizes or colors - redirect to product page if so
    const hasSizes = product.sizes && product.sizes.length > 0
    const hasColors = product.colors && product.colors.length > 0

    if (hasSizes || hasColors) {
      // Redirect to product page for size/color selection
      window.location.href = `/collections/${productId}`
      return
    }

    addToCart({
      id: product.id,
      name: product.name,
      price: product.salePrice || product.price,
      image: product.image,
      quantity: 1,
      hasSizesAvailable: false,
      hasColorsAvailable: false
    })
  }
}

// Fetch products on mount
onMounted(async () => {
  try {
    products.value = await productsApi.getAll()
  } catch (error) {
    console.error('Failed to fetch products:', error)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div @click="closeAllDropdowns">
    <!-- Category Tabs -->
    <div v-if="!hasCategoryInUrl" class="border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-5 lg:px-8">
        <nav class="flex gap-4 md:gap-8 -mb-px overflow-x-auto scrollbar-hide" aria-label="Tabs">
          <button
            v-for="category in categories"
            :key="category.value"
            @click="selectedCategory = category.value"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors flex-shrink-0',
              selectedCategory === category.value
                ? 'border-primary text-primary'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            {{ category.label }}
          </button>
        </nav>
      </div>
    </div>

    <!-- Filters Bar -->
    <div v-if="false" :class="`max-w-7xl mx-auto px-5 lg:px-8 py-6`">
      <div class="flex items-center justify-between">
        <!-- Left Filters -->
        <div class="flex items-center gap-6">
          <span class="text-sm text-gray-500">{{ t('collection.filter') }}:</span>

          <!-- Availability Filter -->
          <div class="relative" @click.stop>
            <button
              @click="isAvailabilityOpen = !isAvailabilityOpen; isPriceOpen = false; isSortOpen = false"
              class="flex items-center gap-1.5 text-sm text-gray-700 hover:text-gray-900 bg-transparent border-none cursor-pointer"
            >
              {{ t('collection.availability') }}
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              v-if="isAvailabilityOpen"
              class="absolute top-full left-0 mt-2 bg-white border border-gray-200 shadow-lg min-w-[160px] z-20 p-3"
            >
              <label class="flex items-center gap-2 cursor-pointer py-1">
                <input type="checkbox" class="w-4 h-4 accent-primary" />
                <span class="text-sm">{{ t('collection.inStock') }}</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer py-1">
                <input type="checkbox" class="w-4 h-4 accent-primary" />
                <span class="text-sm">{{ t('collection.outOfStock') }}</span>
              </label>
            </div>
          </div>

          <!-- Price Filter -->
          <div class="relative" @click.stop>
            <button
              @click="isPriceOpen = !isPriceOpen; isAvailabilityOpen = false; isSortOpen = false"
              class="flex items-center gap-1.5 text-sm text-gray-700 hover:text-gray-900 bg-transparent border-none cursor-pointer"
            >
              {{ t('collection.price') }}
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              v-if="isPriceOpen"
              class="absolute top-full left-0 mt-2 bg-white border border-gray-200 shadow-lg min-w-[200px] z-20 p-4"
            >
              <div class="flex items-center gap-3">
                <div>
                  <label class="text-xs text-gray-500 mb-1 block">{{ t('collection.minPrice') }}</label>
                  <input
                    type="number"
                    :placeholder="`${t('common.egp')} 0`"
                    class="w-20 px-2 py-1.5 border border-gray-300 text-sm"
                  />
                </div>
                <span class="text-gray-400 mt-4">-</span>
                <div>
                  <label class="text-xs text-gray-500 mb-1 block">{{ t('collection.maxPrice') }}</label>
                  <input
                    type="number"
                    :placeholder="`${t('common.egp')} 1000`"
                    class="w-20 px-2 py-1.5 border border-gray-300 text-sm"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Sort -->
        <div class="flex items-center gap-6">
          <div class="relative" @click.stop>
            <button
              @click="isSortOpen = !isSortOpen; isAvailabilityOpen = false; isPriceOpen = false"
              class="flex items-center gap-1.5 text-sm text-gray-700 hover:text-gray-900 bg-transparent border-none cursor-pointer"
            >
              {{ t('collection.sortBy') }}:
              <span class="text-gray-900">{{ sortOptions.find(o => o.value === selectedSort)?.label }}</span>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              v-if="isSortOpen"
              class="absolute top-full right-0 mt-2 bg-white border border-gray-200 shadow-lg min-w-[180px] z-20"
            >
              <button
                v-for="option in sortOptions"
                :key="option.value"
                @click="selectedSort = option.value; isSortOpen = false"
                class="block w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50"
                :class="selectedSort === option.value ? 'text-primary font-medium' : 'text-gray-700'"
              >
                {{ option.label }}
              </button>
            </div>
          </div>
          <span class="text-sm text-gray-500">{{ filteredProducts.length }} {{ t('collection.products') }}</span>
        </div>
      </div>
    </div>

    <!-- Products Grid -->
    <div :class="`${hasCategoryInUrl ? 'mt-16' : 'mt-8'} max-w-7xl mx-auto px-5 lg:px-8 pb-16`">
      <!-- Loading State -->
      <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 lg:gap-8">
        <div v-for="n in 8" :key="n" class="animate-pulse">
          <div class="bg-gray-200 aspect-[3/4] rounded-lg mb-3"></div>
          <div class="bg-gray-200 h-4 rounded w-3/4 mb-2"></div>
          <div class="bg-gray-200 h-4 rounded w-1/2"></div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="sortedProducts.length === 0" class="text-center py-16">
        <p class="text-gray-500 text-lg">{{ t('collection.noProducts') }}</p>
      </div>

      <!-- Products -->
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 lg:gap-8">
        <ProductCard
          v-for="product in sortedProducts"
          :key="product.id"
          :id="product.id"
          :image="product.image"
          :name="product.name"
          :original-price="product.price"
          :sale-price="product.salePrice"
          :is-on-sale="!!product.salePrice"
          @add-to-cart="handleAddToCart"
        />
      </div>
    </div>
  </div>
</template>
