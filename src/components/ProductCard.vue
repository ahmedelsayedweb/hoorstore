<script setup>
defineProps({
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
</script>

<template>
  <a :href="`/collections/${id}`" class="group block no-underline">
    <!-- Image Container -->
    <div class="relative aspect-[3/4] overflow-hidden bg-gray-100 mb-3">
      <img
        :src="image"
        :alt="name"
        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
      />
      <!-- Sale Badge -->
      <span
        v-if="isOnSale"
        class="absolute bottom-4 left-4 bg-gray-900 text-white text-xs px-3 py-1.5"
      >
        Sale
      </span>
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
        <span v-if="originalPrice" class="text-sm text-gray-400 line-through">
          LE {{ originalPrice.toFixed(2) }}
        </span>
        <span class="text-sm text-gray-900">
          LE {{ (salePrice ?? 0).toFixed(2) }}
        </span>
      </div>
    </div>
  </a>
</template>
