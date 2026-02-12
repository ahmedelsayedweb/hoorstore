<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const emit = defineEmits(['add-to-cart'])

const imageLoading = ref(true)
const imageError = ref(false)

const onImageLoad = () => {
  imageLoading.value = false
}

const onImageError = () => {
  imageLoading.value = false
  imageError.value = true
}

const videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'webm']
const isVideo = (url) => {
  if (!url) return false
  const ext = url.split('.').pop()?.toLowerCase().split('?')[0]
  return videoExtensions.includes(ext)
}

const props = defineProps({
  id: {
    type: [String, Number],
    required: true
  },
  image: {
    type: String,
    required: true
  },
  name: {
    type: String,
    required: true
  },
  originalPrice: {
    type: Number,
    default: null
  },
  salePrice: {
    type: Number,
    default: 0
  },
  rating: {
    type: Number,
    default: 0
  },
  reviewCount: {
    type: Number,
    default: 0
  },
  isOnSale: {
    type: Boolean,
    default: false
  },
})

const addToCart = (event) => {
  event.preventDefault()
  event.stopPropagation()
  emit('add-to-cart', props.id)
}
</script>

<template>
  <a :href="`/collections/${id}`" class="group block no-underline ">
    <!-- Image Container -->
    <div class="relative aspect-[3/4] overflow-hidden bg-gray-100 mb-3 rounded-lg">
      <!-- Loading Shimmer -->
      <div
        v-if="imageLoading && !isVideo(image)"
        class="absolute inset-0 bg-gray-200 animate-pulse flex items-center justify-center"
      >
        <img src="/logo.png" alt="Loading" class="w-16 opacity-30" />
      </div>

      <video
        v-if="isVideo(image)"
        :src="image"
        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
        muted
        playsinline
        loop
        autoplay
      />
      <!-- Error Fallback: Show Logo -->
      <div
        v-else-if="imageError"
        class="w-full h-full flex items-center justify-center bg-gray-50"
      >
        <img src="/logo.png" alt="Hoor Store" class="w-24 opacity-60" />
      </div>
      <img
        v-else
        :src="image"
        :alt="name"
        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
        :class="{ 'opacity-0': imageLoading }"
        @load="onImageLoad"
        @error="onImageError"
      />
      <!-- Sale Badge -->
      <span
        v-if="isOnSale"
        class="absolute top-2 left-2 bg-primary text-white text-xs px-3 py-1.5"
      >
        {{ t('collection.sale') }}
      </span>

      <!-- Add to Cart Button -->
      <button
        @click="addToCart"
        class="absolute bottom-4 right-4 bg-white rounded-lg text-gray-900 px-3 py-2 md:px-4 md:py-2.5 text-xs md:text-sm font-medium
               flex items-center gap-1.5 md:gap-2 shadow-lg
               opacity-100 md:opacity-0 translate-y-0 md:translate-y-2 md:group-hover:opacity-100 md:group-hover:translate-y-0
               transition-all duration-300 ease-out
               hover:bg-primary hover:text-white
               active:scale-95"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        {{ t('product.addToCart') }}
      </button>
    </div>

    <!-- Product Info -->
    <div class="space-y-1.5">
      <!-- Product Name -->
      <h3 class="text-sm text-gray-900 font-normal">{{ name }}</h3>

      <!-- Rating -->
      <div v-if="reviewCount > 0" class="flex items-center gap-1">
        <div class="flex">
          <svg
            v-for="i in 5"
            :key="i"
            class="w-4 h-4"
            :class="i <= Math.round(rating) ? 'text-yellow-400' : 'text-gray-200'"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>
        <span class="text-sm text-gray-500">({{ reviewCount }})</span>
      </div>

      <!-- Price -->
      <div class="flex items-center gap-2">
        <template v-if="originalPrice && salePrice">
          <span v-if="originalPrice" class="text-sm text-gray-400 line-through">
            LE {{ originalPrice.toFixed(2) }}
          </span>
          <span v-if="salePrice" class="text-sm text-gray-900">
            LE {{ (salePrice ?? 0).toFixed(2) }}
          </span>
        </template>
        <template v-if="originalPrice && !salePrice">
            <span v-if="originalPrice" class="text-sm text-gray-900">
              LE {{ (originalPrice ?? 0).toFixed(2) }}
            </span>
        </template>
      </div>
    </div>
  </a>
</template>
