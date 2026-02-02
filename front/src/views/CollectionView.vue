<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import ProductCard from '../components/ProductCard.vue'
import AboutSection from '../components/AboutSection.vue'
import SiteFooter from '../components/SiteFooter.vue'

const route = useRoute()

// Filter states
const isAvailabilityOpen = ref(false)
const isPriceOpen = ref(false)
const isSortOpen = ref(false)

const selectedAvailability = ref('all')
const selectedSort = ref('featured')

// Sample products data
const products = ref([
  {
    id: 1,
    name: 'Basic shirt sleeve',
    image: 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600&h=800&fit=crop',
    originalPrice: 600,
    salePrice: 449,
    rating: 5,
    reviewCount: 715,
    isOnSale: true
  },
  {
    id: 2,
    name: 'Ribbed Basic - Long Sleeve',
    image: 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600&h=800&fit=crop',
    originalPrice: 449,
    salePrice: 249,
    rating: 0,
    reviewCount: 0,
    isOnSale: true
  },
  {
    id: 3,
    name: 'Half sleeve basic',
    image: 'https://images.unsplash.com/photo-1485462537746-965f33f7f6a7?w=600&h=800&fit=crop',
    originalPrice: 700,
    salePrice: 449,
    rating: 5,
    reviewCount: 45,
    isOnSale: true
  },
  {
    id: 4,
    name: 'HIGH NECK ZIPPER LONG SLEEVE',
    image: 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=600&h=800&fit=crop',
    originalPrice: 449,
    salePrice: 249,
    rating: 0,
    reviewCount: 0,
    isOnSale: true
  }
])

const sortOptions = [
  { value: 'featured', label: 'Featured' },
  { value: 'price-asc', label: 'Price: Low to High' },
  { value: 'price-desc', label: 'Price: High to Low' },
  { value: 'newest', label: 'Newest' }
]

const sortedProducts = computed(() => {
  const sorted = [...products.value]
  switch (selectedSort.value) {
    case 'price-asc':
      return sorted.sort((a, b) => a.salePrice - b.salePrice)
    case 'price-desc':
      return sorted.sort((a, b) => b.salePrice - a.salePrice)
    default:
      return sorted
  }
})

const closeAllDropdowns = () => {
  isAvailabilityOpen.value = false
  isPriceOpen.value = false
  isSortOpen.value = false
}
</script>

<template>
  <div @click="closeAllDropdowns">
    <!-- Filters Bar -->
    <div class="max-w-7xl mx-auto px-5 lg:px-8 py-6">
      <div class="flex items-center justify-between">
        <!-- Left Filters -->
        <div class="flex items-center gap-6">
          <span class="text-sm text-gray-500">Filter:</span>

          <!-- Availability Filter -->
          <div class="relative" @click.stop>
            <button
              @click="isAvailabilityOpen = !isAvailabilityOpen; isPriceOpen = false; isSortOpen = false"
              class="flex items-center gap-1.5 text-sm text-gray-700 hover:text-gray-900 bg-transparent border-none cursor-pointer"
            >
              Availability
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
                <span class="text-sm">In stock</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer py-1">
                <input type="checkbox" class="w-4 h-4 accent-primary" />
                <span class="text-sm">Out of stock</span>
              </label>
            </div>
          </div>

          <!-- Price Filter -->
          <div class="relative" @click.stop>
            <button
              @click="isPriceOpen = !isPriceOpen; isAvailabilityOpen = false; isSortOpen = false"
              class="flex items-center gap-1.5 text-sm text-gray-700 hover:text-gray-900 bg-transparent border-none cursor-pointer"
            >
              Price
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
                  <label class="text-xs text-gray-500 mb-1 block">Min</label>
                  <input
                    type="number"
                    placeholder="LE 0"
                    class="w-20 px-2 py-1.5 border border-gray-300 text-sm"
                  />
                </div>
                <span class="text-gray-400 mt-4">-</span>
                <div>
                  <label class="text-xs text-gray-500 mb-1 block">Max</label>
                  <input
                    type="number"
                    placeholder="LE 1000"
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
              Sort by:
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

          <span class="text-sm text-gray-500">{{ products.length }} products</span>
        </div>
      </div>
    </div>

    <!-- Products Grid -->
    <div class="max-w-7xl mx-auto px-5 lg:px-8 pb-16">
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5 lg:gap-8">
        <ProductCard
          v-for="product in sortedProducts"
          :key="product.id"
          :id="product.id"
          :image="product.image"
          :name="product.name"
          :originalPrice="product.originalPrice"
          :salePrice="product.salePrice"
          :rating="product.rating"
          :reviewCount="product.reviewCount"
          :isOnSale="product.isOnSale"
        />
      </div>
    </div>

    <!-- About Section -->
    <AboutSection />

    <!-- Footer -->
    <SiteFooter />
  </div>
</template>
