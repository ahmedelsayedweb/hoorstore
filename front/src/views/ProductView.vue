<script setup>
import { ref, computed, onMounted, onUnmounted, reactive } from 'vue'
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
  const mainImage = product.value?.image
  // Combine main image with gallery, removing duplicates
  const combined = mainImage ? [mainImage, ...media] : [...media]
  // Also include variant images
  if (product.value?.variants?.length) {
    for (const v of product.value.variants) {
      if (v.image && !combined.includes(v.image)) {
        combined.push(v.image)
      }
    }
  }
  allMedia.value = [...new Set(combined)]
}

// Handle thumbnail click - also select variant color if applicable
const handleThumbnailClick = (media, index) => {
  selectedImage.value = index
  selectedColorImage.value = null
  resetMainImageState()
  // If this media belongs to a variant, select that color
  if (product.value?.variants?.length) {
    const variant = product.value.variants.find(v => v.image === media)
    if (variant && variant.color) {
      selectColor({ name: variant.color, image: variant.image || '' })
    }
  }
}

// Get current media URL
const getCurrentMedia = () => {
  return selectedColorImage.value || allMedia.value?.[selectedImage.value] || product.value?.image
}

// Image loading/error state
const mainImageLoading = ref(true)
const mainImageError = ref(false)
const imageStates = reactive({}) // keyed by image URL

const onMainImageLoad = () => {
  mainImageLoading.value = false
}
const onMainImageError = () => {
  mainImageLoading.value = false
  mainImageError.value = true
}
const onThumbLoad = (url) => {
  imageStates[url] = { loaded: true, error: false }
}
const onThumbError = (url) => {
  imageStates[url] = { loaded: true, error: true }
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
    if (product.value.variants?.length) {
      // Variant mode: select first variant color (only if colors exist)
      const firstVariant = product.value.variants[0]
      if (firstVariant.color) {
        selectColor({ name: firstVariant.color, image: firstVariant.image || '' })
      }
    } else if (product.value.colors?.length) {
      selectColor(product.value.colors[0])
    }
    if (!product.value.variants?.length && product.value.sizes?.length) selectedSizes.value = [product.value.sizes[0]]
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

const openImageModal = () => {
  showImageModal.value = true
  document.body.style.overflow = 'hidden'
}

const closeImageModal = () => {
  showImageModal.value = false
  document.body.style.overflow = ''
}

const resetMainImageState = () => {
  mainImageLoading.value = true
  mainImageError.value = false
}

const prevImage = () => {
  if (!allMedia.value.length) return
  selectedColorImage.value = null
  selectedImage.value = (selectedImage.value - 1 + allMedia.value.length) % allMedia.value.length
  resetMainImageState()
}

const nextImage = () => {
  if (!allMedia.value.length) return
  selectedColorImage.value = null
  selectedImage.value = (selectedImage.value + 1) % allMedia.value.length
  resetMainImageState()
}
const selectedSizes = ref([])
const selectedHeight = ref()
const selectedWeight = ref()
const quantity = ref(1)

// Variant support
const selectedVariantSize = ref(null)

const hasVariants = computed(() => {
  return product.value?.variants && Array.isArray(product.value.variants) && product.value.variants.length > 0
})

// Detect if variants have colors or are sizes-only
const variantHasColors = computed(() => {
  if (!hasVariants.value) return false
  return product.value.variants.some(v => v.color && v.color.trim() !== '')
})

const variantSizesForColor = computed(() => {
  if (!hasVariants.value) return []
  // Sizes-only mode: single variant with empty color
  if (!variantHasColors.value) {
    return product.value.variants[0]?.sizes || []
  }
  // Colors mode: filter by selected color
  if (!selectedColor.value) return []
  const colorName = getColorName(selectedColor.value)
  const variant = product.value.variants.find(v => v.color === colorName)
  return variant?.sizes || []
})

const maxQuantity = computed(() => {
  if (!hasVariants.value || !selectedVariantSize.value) return Infinity
  const sizeObj = variantSizesForColor.value.find(s => s.size === selectedVariantSize.value)
  return sizeObj?.quantity || 0
})

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
  // Reset variant size when color changes
  if (hasVariants.value) {
    selectedVariantSize.value = null
    quantity.value = 1
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
  if (hasVariants.value && quantity.value >= maxQuantity.value) return
  quantity.value++
}

const handleAddToCart = () => {
  const cartImage = selectedColorImage.value || product.value.images?.[0] || product.value.image

  if (hasVariants.value) {
    if (!selectedVariantSize.value) {
      alert('يرجى اختيار المقاس')
      return
    }
    addToCart({
      id: product.value.id,
      name: product.value.name,
      price: product.value.salePrice,
      image: cartImage,
      color: variantHasColors.value ? getColorName(selectedColor.value) : '',
      sizes: [selectedVariantSize.value],
      height: selectedHeight.value,
      weight: selectedWeight.value,
      quantity: quantity.value,
      hasSizesAvailable: true,
      hasColorsAvailable: variantHasColors.value
    })
  } else {
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
}

const handleBuyNow = async () => {
  const cartImage = selectedColorImage.value || product.value.images?.[0] || product.value.image

  if (hasVariants.value) {
    if (!selectedVariantSize.value) {
      alert('يرجى اختيار المقاس')
      return
    }
    await addToCart({
      id: product.value.id,
      name: product.value.name,
      price: product.value.salePrice,
      image: cartImage,
      color: variantHasColors.value ? getColorName(selectedColor.value) : '',
      sizes: [selectedVariantSize.value],
      height: selectedHeight.value,
      weight: selectedWeight.value,
      quantity: quantity.value,
      hasSizesAvailable: true,
      hasColorsAvailable: variantHasColors.value
    }, { openDrawer: false })
  } else {
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
  }
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
        <!-- Main Image Preview -->
        <div class="aspect-[3/4] bg-gray-50 overflow-hidden rounded-lg cursor-pointer relative" @click="openImageModal()">
          <!-- Loading Shimmer -->
          <div
            v-if="mainImageLoading && !isVideo(getCurrentMedia())"
            class="absolute inset-0 bg-gray-200 animate-pulse flex items-center justify-center z-10"
          >
            <img src="/logo.png" alt="Loading" class="w-24 opacity-30" />
          </div>

          <video
            v-if="isVideo(getCurrentMedia())"
            :src="getCurrentMedia()"
            class="w-full h-full object-cover"
            controls
            playsinline
          />
          <!-- Error Fallback -->
          <div
            v-else-if="mainImageError"
            class="w-full h-full flex items-center justify-center bg-gray-50"
          >
            <img src="/logo.png" alt="Hoor Store" class="w-32 opacity-60" />
          </div>
          <img
            v-else
            :src="getCurrentMedia()"
            :alt="product.name"
            class="w-full h-full object-cover hover:scale-105 transition-transform duration-500"
            :class="{ 'opacity-0': mainImageLoading }"
            @load="onMainImageLoad"
            @error="onMainImageError"
          />
        </div>
        <!-- Thumbnail Gallery (images + videos + variant images) -->
        <div v-if="allMedia.length > 1" class="grid grid-cols-4 gap-3">
          <button
            v-for="(media, index) in allMedia"
            :key="'media-' + index"
            @click="handleThumbnailClick(media, index)"
            class="aspect-square bg-gray-50 overflow-hidden rounded-lg border-2 transition-all duration-300 hover:opacity-80 relative"
            :class="selectedImage === index && !selectedColorImage ? 'border-primary ring-2 ring-primary/20' : 'border-transparent'"
          >
            <video v-if="isVideo(media)" :src="media" class="w-full h-full object-cover" muted playsinline />
            <div v-else-if="imageStates[media]?.error" class="w-full h-full flex items-center justify-center bg-gray-50">
              <img src="/logo.png" alt="Hoor Store" class="w-8 opacity-60" />
            </div>
            <img v-else :src="media" :alt="`${product.name} ${index + 1}`" class="w-full h-full object-cover" @load="onThumbLoad(media)" @error="onThumbError(media)" />
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

        <!-- Color Options (variant-aware) -->
        <div v-if="hasVariants ? (variantHasColors && product.variants?.length) : product.colors?.length">
          <p class="text-sm font-medium text-gray-900 mb-3">{{ t('product.color') }}</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="color in hasVariants
                ? product.variants.map(v => ({ name: v.color, image: v.image || '' }))
                : product.colors"
              :key="getColorName(color)"
              @click="selectColor(color)"
              class="px-4 py-2.5 text-sm font-medium border-2 rounded-lg transition-all duration-200"
              :class="getColorName(selectedColor) === getColorName(color)
                ? 'bg-primary text-white border-gray-900'
                : 'bg-white text-gray-700 border-gray-200 hover:border-gray-900'"
            >
              {{ getColorName(color) }}
            </button>
          </div>
        </div>

        <!-- Size Options: LEGACY mode (multiple selection) -->
        <div v-if="!hasVariants && product.sizes?.length">
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
          <!-- WhatsApp Size Help Alert -->
          <a
            href="https://wa.me/201224982557"
            target="_blank"
            class="mt-3 flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800 hover:bg-green-100 transition-colors duration-200"
          >
            <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            <span>لو عندك أي استفسار عن المقاسات أو محتاج مساعدة، ابعتلنا على الواتساب</span>
          </a>
        </div>

        <!-- Size Options: VARIANT mode (single selection with stock) -->
        <div v-if="hasVariants && variantSizesForColor.length > 0">
          <p class="text-sm font-medium text-gray-900 mb-3">{{ t('product.size') }}</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="sizeObj in variantSizesForColor"
              :key="sizeObj.size"
              @click="sizeObj.quantity > 0 ? (selectedVariantSize = sizeObj.size, quantity = 1) : null"
              :disabled="sizeObj.quantity === 0"
              class="min-w-[50px] px-4 py-2 text-sm font-medium border-2 rounded-lg transition-all duration-200 text-center"
              :class="[
                sizeObj.quantity === 0
                  ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed line-through'
                  : selectedVariantSize === sizeObj.size
                    ? 'bg-primary text-white border-gray-900'
                    : 'bg-white text-gray-700 border-gray-200 hover:border-gray-900'
              ]"
            >
              <span>{{ sizeObj.size }}</span>
              <span v-if="sizeObj.quantity === 0" class="block text-[10px] text-red-500 mt-0.5 no-underline" style="text-decoration: none;">نفذ</span>
              <span v-else class="block text-[10px] mt-0.5" :class="selectedVariantSize === sizeObj.size ? 'text-white/70' : 'text-gray-400'">{{ sizeObj.quantity }} متاح</span>
            </button>
          </div>
          <!-- WhatsApp Size Help Alert -->
          <a
            href="https://wa.me/201224982557"
            target="_blank"
            class="mt-3 flex items-center gap-2 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800 hover:bg-green-100 transition-colors duration-200"
          >
            <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            <span>لو عندك أي استفسار عن المقاسات أو محتاج مساعدة، ابعتلنا على الواتساب</span>
          </a>
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
          <div v-if="product.description.footer" class="mt-4 text-sm text-gray-600 prose prose-sm max-w-none" v-html="product.description.footer"></div>
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
            <div
              v-else-if="mainImageError"
              class="flex items-center justify-center"
            >
              <img src="/logo.png" alt="Hoor Store" class="w-40 opacity-60" />
            </div>
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
