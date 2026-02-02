<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { useCart } from '../composables/useCart'
import { productsApi } from '../api/products'

const route = useRoute()
const { addToCart } = useCart()

// Product data from API
const product = ref(null)
const loading = ref(true)
const error = ref(null)

// Fetch product by ID from route params
const fetchProduct = async () => {
  try {
    loading.value = true
    error.value = null
    const id = route.params.id
    const data = await productsApi.getById(id)

    // Normalize product data to match template expectations
    product.value = {
      ...data,
      brand: data.brand || 'HOOR',
      images: data.images || [data.image],
      originalPrice: data.price,
      salePrice: data.salePrice || data.price,
      isOnSale: !!data.salePrice,
      rating: data.rating || 0,
      reviewCount: data.reviewCount || 0,
      colors: data.colors || [],
      sizes: data.sizes || [],
      heights: data.heights || [],
      description: data.description && typeof data.description === 'object'
        ? data.description
        : { title: data.name, details: [], note: '', footer: data.description || '' }
    }
  } catch (err) {
    error.value = 'Failed to load product'
    console.error(err)
  } finally {
    loading.value = false
  }
}

// Selected options
const selectedImage = ref(0)
const selectedColor = ref('white')
const selectedSize = ref('Large')
const selectedHeight = ref('100 cm')
const quantity = ref(1)

// Countdown timer
const countdown = ref({
  days: 0,
  hours: 6,
  minutes: 44,
  seconds: 53
})

let countdownInterval = null

onMounted(() => {
  fetchProduct()

  countdownInterval = setInterval(() => {
    if (countdown.value.seconds > 0) {
      countdown.value.seconds--
    } else if (countdown.value.minutes > 0) {
      countdown.value.minutes--
      countdown.value.seconds = 59
    } else if (countdown.value.hours > 0) {
      countdown.value.hours--
      countdown.value.minutes = 59
      countdown.value.seconds = 59
    } else if (countdown.value.days > 0) {
      countdown.value.days--
      countdown.value.hours = 23
      countdown.value.minutes = 59
      countdown.value.seconds = 59
    }
  }, 1000)
})

onUnmounted(() => {
  if (countdownInterval) clearInterval(countdownInterval)
})

const formatNumber = (num) => String(num).padStart(2, '0')

const decreaseQuantity = () => {
  if (quantity.value > 1) quantity.value--
}

const increaseQuantity = () => {
  quantity.value++
}

const handleAddToCart = () => {
  addToCart({
    id: product.value.id,
    name: product.value.name,
    price: product.value.salePrice,
    image: product.value.images?.[0] || product.value.image,
    color: selectedColor.value,
    size: selectedSize.value,
    height: selectedHeight.value,
    quantity: quantity.value
  })
}
</script>

<template>
  <!-- Loading State -->
  <div v-if="loading" class="max-w-7xl mx-auto px-4 md:px-8 py-8 text-center">
    <p class="text-gray-500">Loading...</p>
  </div>

  <!-- Error State -->
  <div v-else-if="error" class="max-w-7xl mx-auto px-4 md:px-8 py-8 text-center">
    <p class="text-red-500">{{ error }}</p>
  </div>

  <!-- Product Content -->
  <div v-else-if="product" class="max-w-7xl mx-auto px-4 md:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
      <!-- Product Images -->
      <div class="space-y-4">
        <!-- Main Image -->
        <div class="aspect-[3/4] bg-gray-100 overflow-hidden">
          <img
            :src="product.images?.[selectedImage] || product.image"
            :alt="product.name"
            class="w-full h-full object-cover"
          />
        </div>
        <!-- Thumbnail Gallery -->
        <div v-if="product.images?.length > 1" class="grid grid-cols-4 gap-3">
          <button
            v-for="(image, index) in product.images"
            :key="index"
            @click="selectedImage = index"
            class="aspect-square bg-gray-100 overflow-hidden border-2 transition-colors"
            :class="selectedImage === index ? 'border-gray-900' : 'border-transparent'"
          >
            <img :src="image" :alt="`${product.name} ${index + 1}`" class="w-full h-full object-cover" />
          </button>
        </div>
      </div>

      <!-- Product Info -->
      <div class="space-y-6">
        <!-- Brand & Title -->
        <div>
          <p class="text-sm text-gray-500 mb-1">{{ product.brand }}</p>
          <h1 class="text-3xl font-normal text-gray-900">{{ product.name }}</h1>
        </div>

        <!-- Price -->
        <div class="flex items-center gap-3">
          <span v-if="product.isOnSale" class="text-lg text-gray-400 line-through">
            LE {{ Number(product.originalPrice || 0).toFixed(2) }}
          </span>
          <span class="text-lg text-gray-900">LE {{ Number(product.salePrice || product.originalPrice || 0).toFixed(2) }}</span>
          <span v-if="product.isOnSale" class="bg-gray-900 text-white text-xs px-2 py-1">Sale</span>
        </div>

        <p class="text-sm text-gray-500">Taxes included. <a href="#" class="underline">Shipping</a> calculated at checkout.</p>

        <!-- Sale Countdown Banner -->
        <!-- <div v-if="product.isOnSale" class="bg-amber-50 border border-amber-200 p-6 text-center">
          <p class="text-pink-500 font-semibold text-lg mb-1">TIME IS TICKING</p>
          <p class="text-pink-400 text-sm mb-4">White Friday sale ends in</p>
          <div class="flex justify-center gap-2 text-pink-500 text-3xl font-bold mb-4">
            <span>{{ formatNumber(countdown.days) }}</span>
            <span class="text-pink-300">:</span>
            <span>{{ formatNumber(countdown.hours) }}</span>
            <span class="text-pink-300">:</span>
            <span>{{ formatNumber(countdown.minutes) }}</span>
            <span class="text-pink-300">:</span>
            <span>{{ formatNumber(countdown.seconds) }}</span>
          </div>
          <button class="border border-pink-400 text-pink-500 px-4 py-2 text-sm rounded-full">
            SAVE UP TO 50%
          </button>
        </div> -->

        <!-- Rating -->
        <div v-if="product.reviewCount" class="flex items-center gap-2">
          <div class="flex">
            <svg
              v-for="i in 5"
              :key="i"
              class="w-5 h-5"
              :class="i <= product.rating ? 'text-yellow-400' : 'text-gray-200'"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
          </div>
          <span class="text-sm text-gray-600">{{ product.reviewCount }} Reviews</span>
        </div>

        <!-- Color Options -->
        <div v-if="product.colors?.length">
          <p class="text-sm text-gray-700 mb-3">color</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="color in product.colors"
              :key="color.name"
              @click="selectedColor = color.name"
              class="px-4 py-2 text-sm border transition-colors"
              :class="selectedColor === color.name
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'"
            >
              {{ color.name }}
            </button>
          </div>
        </div>
        <!-- Size Options -->
        <div v-if="product.sizes?.length">
          <p class="text-sm text-gray-700 mb-3">Size</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="size in product.sizes"
              :key="size"
              @click="selectedSize = size"
              class="px-4 py-2 text-sm border transition-colors"
              :class="selectedSize === size
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'"
            >
              {{ size }}
            </button>
          </div>
        </div>

        <!-- Height Options -->
        <div v-if="product.heights?.length">
          <p class="text-sm text-gray-700 mb-3">height</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="height in product.heights"
              :key="height"
              @click="selectedHeight = height"
              class="px-4 py-2 text-sm border transition-colors"
              :class="selectedHeight === height
                ? 'bg-gray-900 text-white border-gray-900'
                : 'bg-white text-gray-700 border-gray-300 hover:border-gray-400'"
            >
              {{ height }}
            </button>
          </div>
        </div>

        <!-- Quantity -->
        <div>
          <p class="text-sm text-gray-700 mb-3">Quantity</p>
          <div class="inline-flex items-center border border-gray-300">
            <button
              @click="decreaseQuantity"
              class="px-4 py-3 text-gray-600 hover:bg-gray-50 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
              </svg>
            </button>
            <span class="px-6 py-3 text-gray-900 min-w-[60px] text-center">{{ quantity }}</span>
            <button
              @click="increaseQuantity"
              class="px-4 py-3 text-gray-600 hover:bg-gray-50 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3">
          <button @click="handleAddToCart" class="w-full py-4 border border-gray-900 text-gray-900 text-sm hover:bg-gray-50 transition-colors">
            Add to cart
          </button>
          <button class="w-full py-4 bg-gray-900 text-white text-sm hover:bg-gray-800 transition-colors">
            Buy it now
          </button>
        </div>

        <!-- Product Description -->
        <div v-if="product.description" class="pt-6 border-t border-gray-200">
          <h3 v-if="product.description.title" class="font-medium text-gray-900 mb-4">{{ product.description.title }}</h3>
          <div v-if="product.description.details?.length" class="space-y-3 text-sm text-gray-600">
            <div v-for="detail in product.description.details" :key="detail.label">
              <p>
                <span class="font-medium text-gray-900">{{ detail.label }}:</span>
                {{ detail.text }}
              </p>
              <ul v-if="detail.list" class="ml-4 mt-1 space-y-1">
                <li v-for="item in detail.list" :key="item">– {{ item }}</li>
              </ul>
            </div>
          </div>
          <p v-if="product.description.note" class="mt-4 text-green-600 font-medium text-sm">
            {{ product.description.note }}
          </p>
          <p v-if="product.description.footer" class="mt-4 text-sm text-gray-600">{{ product.description.footer }}</p>
        </div>

        <!-- Share Button -->
        <button class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
          </svg>
          Share
        </button>
      </div>
    </div>

    <!-- Customer Reviews Section -->
    <!-- <section class="mt-16 pt-8 border-t border-gray-200">
      <h2 class="text-2xl font-normal text-gray-900 mb-8">
        <span class="border-b-2 border-gray-900 pb-2">Customer Reviews</span>
      </h2>
      <div class="text-center py-12 text-gray-500">
        <p>No reviews yet. Be the first to review this product!</p>
        <button class="mt-4 px-6 py-2 border border-gray-900 text-gray-900 text-sm hover:bg-gray-900 hover:text-white transition-colors">
          Write a Review
        </button>
      </div>
    </section> -->
  </div>
</template>
