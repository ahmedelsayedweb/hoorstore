<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCart } from '../composables/useCart'
import { useLanguage } from '../composables/useLanguage'
import { productsApi } from '../api/products'

const route = useRoute()
const router = useRouter()
const { addToCart } = useCart()
const { t } = useLanguage()

// Helper to check if a URL is a video
const videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'webm']
const isVideo = (url) => {
  if (!url) return false
  const ext = url.split('.').pop()?.toLowerCase().split('?')[0]
  return videoExtensions.includes(ext)
}

// All media (images + videos combined)
const allMedia = ref([])

const splitMedia = () => {
  const media = product.value?.images || []
  allMedia.value = media.length ? media : (product.value?.image ? [product.value.image] : [])
}

// Get current media URL
const getCurrentMedia = () => {
  return selectedColorImage.value || allMedia.value?.[selectedImage.value] || product.value?.image
}

// Share notification
const showShareNotification = ref(false)

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
      weights: data.weights || [],
      description: data.description && typeof data.description === 'object'
        ? data.description
        : { title: data.name, details: [], note: '', footer: data.description || '' }
    }

    // Split media into images and videos
    splitMedia()

    // Set default selected values to first option
    if (product.value.colors?.length) {
      selectColor(product.value.colors[0])
    }
    if (product.value.sizes?.length) selectedSizes.value = [product.value.sizes[0]]
    if (product.value.heights?.length) selectedHeight.value = product.value.heights[0]
    if (product.value.weights?.length) selectedWeight.value = product.value.weights[0]
  } catch (err) {
    error.value = 'Failed to load product'
    console.error(err)
  } finally {
    loading.value = false
  }
}

// Selected options
const selectedImage = ref(0)
const selectedColor = ref()
const selectedColorImage = ref(null)

// Image modal
const showImageModal = ref(false)

const openImageModal = (index) => {
  if (index !== undefined) {
    selectedImage.value = index
    selectedColorImage.value = null
  }
  showImageModal.value = true
  document.body.style.overflow = 'hidden'
}

const closeImageModal = () => {
  showImageModal.value = false
  document.body.style.overflow = ''
}

const prevImage = () => {
  if (!allMedia.value.length) return
  selectedColorImage.value = null
  selectedImage.value = (selectedImage.value - 1 + allMedia.value.length) % allMedia.value.length
}

const nextImage = () => {
  if (!allMedia.value.length) return
  selectedColorImage.value = null
  selectedImage.value = (selectedImage.value + 1) % allMedia.value.length
}
const selectedSizes = ref([])
const selectedHeight = ref()
const selectedWeight = ref()
const quantity = ref(1)

// Toggle size selection (allow multiple)
const toggleSize = (size) => {
  const index = selectedSizes.value.indexOf(size)
  if (index === -1) {
    selectedSizes.value.push(size)
  } else {
    selectedSizes.value.splice(index, 1)
  }
}

// Helper to get color name (supports both string and object format)
const getColorName = (color) => {
  return typeof color === 'object' ? color.name : color
}

// Helper to get color image (supports both string and object format)
const getColorImage = (color) => {
  return typeof color === 'object' ? color.image : null
}

// Handle color selection
const selectColor = (color) => {
  selectedColor.value = color
  const colorImage = getColorImage(color)
  if (colorImage) {
    selectedColorImage.value = colorImage
  } else {
    selectedColorImage.value = null
  }
}

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
  // Get the correct image - color image if available, otherwise default
  const cartImage = selectedColorImage.value || product.value.images?.[0] || product.value.image

  addToCart({
    id: product.value.id,
    name: product.value.name,
    price: product.value.salePrice,
    image: cartImage,
    color: getColorName(selectedColor.value),
    sizes: selectedSizes.value,
    height: selectedHeight.value,
    weight: selectedWeight.value,
    quantity: quantity.value,
    hasSizesAvailable: product.value.sizes?.length > 0,
    hasColorsAvailable: product.value.colors?.length > 0
  })
}

const handleBuyNow = async () => {
  // Get the correct image - color image if available, otherwise default
  const cartImage = selectedColorImage.value || product.value.images?.[0] || product.value.image

  await addToCart({
    id: product.value.id,
    name: product.value.name,
    price: product.value.salePrice,
    image: cartImage,
    color: getColorName(selectedColor.value),
    sizes: selectedSizes.value,
    height: selectedHeight.value,
    weight: selectedWeight.value,
    quantity: quantity.value,
    hasSizesAvailable: product.value.sizes?.length > 0,
    hasColorsAvailable: product.value.colors?.length > 0
  }, { openDrawer: false })
  router.push('/checkout')
}

const handleShare = async () => {
  const shareData = {
    title: product.value.name,
    text: `${product.value.name} - ${t('common.egp')} ${product.value.salePrice || product.value.originalPrice}`,
    url: window.location.href
  }

  try {
    if (navigator.share) {
      await navigator.share(shareData)
    } else {
      await navigator.clipboard.writeText(window.location.href)
      showShareNotification.value = true
      setTimeout(() => {
        showShareNotification.value = false
      }, 2000)
    }
  } catch (err) {
    console.log('Share failed:', err)
  }
}
</script>

<template>
  <!-- Loading State -->
  <div v-if="loading" class="max-w-7xl mx-auto px-4 md:px-8 py-16 text-center">
    <p class="text-gray-500">{{ t('common.loading') }}</p>
  </div>

  <!-- Error State -->
  <div v-else-if="error" class="max-w-7xl mx-auto px-4 md:px-8 py-16 text-center">
    <p class="text-red-500">{{ t('product.failedToLoad') }}</p>
  </div>

  <!-- Product Content -->
  <div v-else-if="product" class="max-w-7xl mx-auto px-4 md:px-8 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-16">
      <!-- Product Images -->
      <div class="space-y-4 lg:sticky lg:top-24 order-1 lg:order-2">
        <!-- Main Image/Video Preview -->
        <div class="aspect-[3/4] bg-gray-50 overflow-hidden rounded-lg cursor-pointer relative" @click="openImageModal()">
          <img
            :src="product.image"
            :alt="product.name"
            class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
          />
        </div>
        <!-- Thumbnail Gallery (images + videos) -->
        <div v-if="allMedia.length > 0" class="grid grid-cols-4 gap-3">
          <button
            v-for="(media, index) in allMedia"
            :key="index"
            @click="openImageModal(index)"
            class="aspect-square bg-gray-50 overflow-hidden rounded-lg border-2 transition-all duration-300 hover:opacity-80 relative"
            :class="selectedImage === index && !selectedColorImage ? 'border-primary ring-2 ring-primary/20' : 'border-transparent'"
          >
            <video v-if="isVideo(media)" :src="media" class="w-full h-full object-cover" muted playsinline />
            <img v-else :src="media" :alt="`${product.name} ${index + 1}`" class="w-full h-full object-cover" />
            <!-- Play icon overlay for videos -->
            <div v-if="isVideo(media)" class="absolute inset-0 flex items-center justify-center bg-black/20">
              <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M8 5v14l11-7z" />
              </svg>
            </div>
          </button>
        </div>
      </div>

      <!-- Product Info -->
      <div class="space-y-6 order-2 lg:order-1">
        <!-- Brand & Title -->
        <div>
          <span class="inline-block bg-primary text-white text-xs px-3 py-1 rounded mb-3">{{ product.brand }}</span>
          <h1 class="text-2xl md:text-3xl font-semibold text-gray-900">{{ product.name }}</h1>
        </div>

        <!-- Price -->
        <div class="flex items-center gap-3 flex-wrap">
          <span class="text-2xl font-semibold text-gray-900">{{ t('common.egp') }} {{ Number(product.salePrice || product.originalPrice || 0).toFixed(2) }}</span>
          <span v-if="product.isOnSale" class="text-lg text-gray-400 line-through">
            {{ t('common.egp') }} {{ Number(product.originalPrice || 0).toFixed(2) }}
          </span>
          <span v-if="product.isOnSale" class="bg-primary text-white text-xs px-3 py-1 rounded-full">{{ t('product.sale') }}</span>
        </div>

        <!-- <p class="text-sm text-gray-500">Taxes included. <a href="#" class="underline">Shipping</a> calculated at checkout.</p> -->

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
          <span class="text-sm text-gray-600">{{ product.reviewCount }} {{ t('product.reviews') }}</span>
        </div>

        <!-- Color Options -->
        <div v-if="product.colors?.length">
          <p class="text-sm font-medium text-gray-900 mb-3">{{ t('product.color') }}</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="color in product.colors"
              :key="getColorName(color)"
              @click="selectColor(color)"
              class="px-4 py-2.5 text-sm font-medium border-2 rounded-lg transition-all duration-200"
              :class="getColorName(selectedColor) === getColorName(color)
                ? 'bg-primary text-white border-gray-900'
                : 'bg-white text-gray-700 border-gray-200 hover:border-gray-900'" >
              {{ getColorName(color) }}
            </button>
          </div>
        </div>
        <!-- Size Options (Multiple Selection) -->
        <div v-if="product.sizes?.length">
          <p class="text-sm font-medium text-gray-900 mb-3">{{ t('product.size') }}</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="size in product.sizes"
              :key="size"
              @click="toggleSize(size)"
              class="min-w-[50px] px-4 py-2.5 text-sm font-medium border-2 rounded-lg transition-all duration-200"
              :class="selectedSizes.includes(size)
                ? 'bg-primary text-white border-gray-900'
                : 'bg-white text-gray-700 border-gray-200 hover:border-gray-900'"
            >
              {{ size }}
            </button>
          </div>
        </div>

        <!-- Height Options -->
        <div v-if="product.heights?.length">
          <p class="text-sm font-medium text-gray-900 mb-3">{{ t('product.height') }}</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="height in product.heights"
              :key="height"
              @click="selectedHeight = height"
              class="px-4 py-2.5 text-sm font-medium border-2 rounded-lg transition-all duration-200"
              :class="selectedHeight === height
                ? 'bg-primary text-white border-gray-900'
                : 'bg-white text-gray-700 border-gray-200 hover:border-gray-900'"
            >
              {{ height }}
            </button>
          </div>
        </div>

        <!-- Weight Options -->
        <div v-if="product.weights?.length">
          <p class="text-sm font-medium text-gray-900 mb-3">{{ t('product.weight') }}</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="weight in product.weights"
              :key="weight"
              @click="selectedWeight = weight"
              class="px-4 py-2.5 text-sm font-medium border-2 rounded-lg transition-all duration-200"
              :class="selectedWeight === weight
                ? 'bg-primary text-white border-gray-900'
                : 'bg-white text-gray-700 border-gray-200 hover:border-gray-900'"
            >
              {{ weight }}
            </button>
          </div>
        </div>

        <!-- Quantity -->
        <div>
          <p class="text-sm font-medium text-gray-900 mb-3">{{ t('product.quantity') }}</p>
          <div class="inline-flex items-center border-2 border-gray-200 rounded-lg overflow-hidden">
            <button
              @click="increaseQuantity"
              class="px-4 py-3 text-gray-600 hover:bg-gray-100 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
            <span class="px-6 py-3 text-gray-900 font-medium min-w-[60px] text-center border-x-2 border-gray-200">{{ quantity }}</span>
            <button
              @click="decreaseQuantity"
              class="px-4 py-3 text-gray-600 hover:bg-gray-100 transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="space-y-3 pt-4">
          <button @click="handleAddToCart" class="w-full py-4 border-2 border-gray-900 text-gray-900 font-semibold rounded-lg hover:bg-gray-900 hover:text-white transition-all duration-300">
            {{ t('product.addToCart') }}
          </button>
          <button @click="handleBuyNow" class="w-full py-4 bg-primary text-white font-semibold rounded-lg hover:bg-primary-dark transition-all duration-300">
            {{ t('product.buyNow') }}
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
        <div class="relative">
          <button @click="handleShare" class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
            </svg>
            {{ t('product.share') }}
          </button>
          <!-- Copy notification -->
          <Transition name="fade">
            <div
              v-if="showShareNotification"
              class="absolute bottom-full mb-2 ltr:left-0 rtl:right-0 bg-green-600 text-white text-xs px-3 py-2 rounded-lg shadow-lg whitespace-nowrap"
            >
              {{ t('product.linkCopied') }}
            </div>
          </Transition>
        </div>
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

    <!-- Image Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div
          v-if="showImageModal"
          class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/90"
          @click="closeImageModal"
        >
          <!-- Close Button -->
          <button
            @click="closeImageModal"
            class="absolute top-4 right-4 text-white hover:text-gray-300 transition-colors z-10"
          >
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Prev Button -->
          <button
            v-if="allMedia.length > 1"
            @click.stop="prevImage"
            class="absolute left-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white bg-black/40 hover:bg-black/60 rounded-full p-2 transition-all z-10"
          >
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>

          <!-- Next Button -->
          <button
            v-if="allMedia.length > 1"
            @click.stop="nextImage"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-white/70 hover:text-white bg-black/40 hover:bg-black/60 rounded-full p-2 transition-all z-10"
          >
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>

          <!-- Modal Image/Video -->
          <div class="flex-1 flex items-center justify-center p-4 min-h-0" @click.stop>
            <video
              v-if="isVideo(getCurrentMedia())"
              :src="getCurrentMedia()"
              class="max-w-full max-h-full object-contain"
              controls
              autoplay
              playsinline
            />
            <img
              v-else
              :src="getCurrentMedia()"
              :alt="product.name"
              class="max-w-full max-h-full object-contain"
            />
          </div>

          <!-- Thumbnail Strip -->
          <div v-if="allMedia.length > 1" class="flex gap-2 pb-4 px-4" @click.stop>
            <button
              v-for="(media, index) in allMedia"
              :key="index"
              @click="selectedColorImage = null; selectedImage = index"
              class="w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden border-2 transition-all duration-200 relative"
              :class="selectedImage === index ? 'border-white opacity-100' : 'border-transparent opacity-50 hover:opacity-80'"
            >
              <video v-if="isVideo(media)" :src="media" class="w-full h-full object-cover" muted playsinline />
              <img v-else :src="media" :alt="`${product.name} ${index + 1}`" class="w-full h-full object-cover" />
              <div v-if="isVideo(media)" class="absolute inset-0 flex items-center justify-center bg-black/20">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M8 5v14l11-7z" />
                </svg>
              </div>
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Modal transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
