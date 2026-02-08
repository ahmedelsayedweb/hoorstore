<script setup>
import { ref } from 'vue'
import { useCart } from '../composables/useCart'
import { useI18n } from 'vue-i18n'
import { productsApi } from '../api/products'

const { t } = useI18n()
const { cartItems, removeFromCart, updateQuantity, updateCartItem, cartTotal } = useCart()

// Edit item modal state
const editingItem = ref(null)
const editForm = ref({
  sizes: [],
  height: '',
  color: '',
  image: '',
  quantity: 1
})

// Toggle size selection in edit form
const toggleEditSize = (size) => {
  const index = editForm.value.sizes.indexOf(size)
  if (index === -1) {
    editForm.value.sizes.push(size)
  } else {
    editForm.value.sizes.splice(index, 1)
  }
}
const loadingProduct = ref(false)
const currentProduct = ref(null)

// Available options from product
const availableSizes = ref([])
const availableHeights = ref([])
const availableColors = ref([])

// Helper to get color name (supports both string and object format)
const getColorName = (color) => {
  return typeof color === 'object' ? color.name : color
}

// Helper to get color image (supports both string and object format)
const getColorImage = (color) => {
  return typeof color === 'object' ? color.image : null
}

// Handle color change in edit form
const onColorChange = (colorName) => {
  editForm.value.color = colorName
  // Find the color object and update the image
  const colorObj = availableColors.value.find(c => getColorName(c) === colorName)
  if (colorObj) {
    const colorImage = getColorImage(colorObj)
    if (colorImage) {
      editForm.value.image = colorImage
    }
  }
}

// Open edit modal and fetch product details
const openEditModal = async (item) => {
  editingItem.value = item
  editForm.value = {
    sizes: Array.isArray(item.sizes) ? [...item.sizes] : (item.sizes ? [item.sizes] : []),
    height: item.height || '',
    color: item.color || '',
    image: item.image || '',
    quantity: item.quantity
  }

  // Fetch product to get available options
  if (item.productId) {
    loadingProduct.value = true
    try {
      const product = await productsApi.getById(item.productId)
      currentProduct.value = product
      availableSizes.value = product.sizes || []
      availableHeights.value = product.heights || []
      availableColors.value = product.colors || []
    } catch (error) {
      console.error('Failed to load product:', error)
      currentProduct.value = null
      availableSizes.value = []
      availableHeights.value = []
      availableColors.value = []
    } finally {
      loadingProduct.value = false
    }
  }
}

// Close edit modal
const closeEditModal = () => {
  editingItem.value = null
}

// Save edited item
const saveEditedItem = async () => {
  if (editingItem.value) {
    await updateCartItem(editingItem.value.id, {
      sizes: editForm.value.sizes,
      height: editForm.value.height,
      color: editForm.value.color,
      image: editForm.value.image,
      quantity: editForm.value.quantity
    })
    closeEditModal()
  }
}
</script>

<template>
  <!-- Edit Item Modal -->
  <div v-if="editingItem" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-bold text-gray-900">{{ t('checkout.editItem') }}</h3>
        <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="flex gap-4 mb-4">
        <img :src="editForm.image || editingItem.image" :alt="editingItem.name" class="w-20 h-24 object-cover rounded" />
        <div>
          <h4 class="font-medium text-gray-900">{{ editingItem.name }}</h4>
          <p class="text-sm text-gray-500">{{ editForm.color }}</p>
          <p class="text-sm font-medium text-primary">{{ editingItem.price }} {{ t('common.egp') }}</p>
        </div>
      </div>

      <!-- Loading state -->
      <div v-if="loadingProduct" class="flex items-center justify-center py-8">
        <svg class="w-8 h-8 animate-spin text-primary" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>

      <div v-else class="space-y-4">
        <!-- Color -->
        <div v-if="availableColors.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('product.color') }}</label>
          <select
            :value="editForm.color"
            @change="onColorChange($event.target.value)"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary"
          >
            <option v-for="color in availableColors" :key="getColorName(color)" :value="getColorName(color)">{{ getColorName(color) }}</option>
          </select>
        </div>

        <!-- Size (Multiple Selection) -->
        <div v-if="availableSizes.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-2">{{ t('product.size') }}</label>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="size in availableSizes"
              :key="size"
              type="button"
              @click="toggleEditSize(size)"
              class="min-w-[40px] px-3 py-2 text-sm font-medium border-2 rounded-lg transition-all duration-200"
              :class="editForm.sizes.includes(size)
                ? 'bg-primary text-white border-primary'
                : 'bg-white text-gray-700 border-gray-200 hover:border-gray-400'"
            >
              {{ size }}
            </button>
          </div>
        </div>

        <!-- Height -->
        <div v-if="availableHeights.length > 0">
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('product.height') }}</label>
          <select
            v-model="editForm.height"
            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary"
          >
            <option v-for="height in availableHeights" :key="height" :value="height">{{ height }}</option>
          </select>
        </div>

        <!-- Quantity -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('product.quantity') }}</label>
          <div class="flex items-center gap-2">
            <button
              @click="editForm.quantity = Math.max(1, editForm.quantity - 1)"
              class="w-10 h-10 border border-gray-300 rounded flex items-center justify-center hover:bg-gray-100"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
              </svg>
            </button>
            <input
              v-model.number="editForm.quantity"
              type="number"
              min="1"
              class="w-20 px-3 py-2 border border-gray-300 rounded text-center focus:outline-none focus:border-primary"
            />
            <button
              @click="editForm.quantity++"
              class="w-10 h-10 border border-gray-300 rounded flex items-center justify-center hover:bg-gray-100"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div v-if="!loadingProduct" class="flex gap-3 mt-6">
        <button
          @click="closeEditModal"
          class="flex-1 py-2 border border-gray-300 text-gray-700 rounded font-medium hover:bg-gray-50 transition-colors"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          @click="saveEditedItem"
          class="flex-1 py-2 bg-primary text-white rounded font-medium hover:bg-primary/90 transition-colors"
        >
          {{ t('common.save') }}
        </button>
      </div>
    </div>
  </div>

  <div class="max-w-6xl mx-auto px-4 md:px-8 py-12">
    <!-- Header -->
    <div class="flex items-center justify-between mb-10">
      <h1 class="text-3xl md:text-4xl font-normal text-gray-900">{{ t('cart.title') }}</h1>
      <a href="/" class="text-sm text-gray-600 underline hover:text-gray-900 transition-colors">
        {{ t('cart.continueShopping') }}
      </a>
    </div>

    <!-- Empty Cart -->
    <div v-if="cartItems.length === 0" class="text-center py-16">
      <svg class="w-20 h-20 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
      </svg>
      <p class="text-gray-500 text-lg mb-6">{{ t('cart.empty') }}</p>
      <a
        href="/"
        class="inline-block px-8 py-3 bg-gray-900 text-white text-sm hover:bg-gray-800 transition-colors"
      >
        {{ t('cart.startShopping') }}
      </a>
    </div>

    <!-- Cart with Items -->
    <div v-else>
      <!-- Table Header -->
      <div class="hidden md:grid md:grid-cols-12 gap-4 pb-4 border-b border-gray-200 text-sm text-gray-500 uppercase">
        <div class="col-span-6">{{ t('cart.product') }}</div>
        <div class="col-span-3 text-center">{{ t('product.quantity') }}</div>
        <div class="col-span-3 ltr:text-right rtl:text-left">{{ t('cart.total') }}</div>
      </div>

      <!-- Cart Items -->
      <div class="divide-y divide-gray-200">
        <div
          v-for="item in cartItems"
          :key="item.id"
          class="py-6 grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-6 items-center"
        >
          <!-- Product Info -->
          <div class="col-span-6 flex gap-4">
            <!-- Image -->
            <div class="w-24 h-32 md:w-28 md:h-36 bg-gray-100 flex-shrink-0">
              <img :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
            </div>
            <!-- Details -->
            <div class="flex flex-col justify-center">
              <h3 class="text-base font-normal text-gray-900">{{ item.name }}</h3>
              <p class="text-sm text-gray-500 mt-1">{{ t('common.egp') }} {{ item.price.toFixed(2) }}</p>
              <div class="mt-2 space-y-0.5 text-sm text-gray-500">
                <p>{{ t('product.color') }}: {{ item.color }}</p>
                <p>{{ t('product.size') }}: {{ Array.isArray(item.sizes) ? item.sizes.join(', ') : item.sizes }}</p>
                <p>{{ t('product.height') }}: {{ item.height }}</p>
              </div>
            </div>
          </div>

          <!-- Quantity -->
          <div class="col-span-3 flex items-center justify-start md:justify-center gap-1">
            <div class="flex items-center border border-gray-300">
              <button
                @click="updateQuantity(item.id, item.quantity - 1)"
                class="px-3 py-2 text-gray-600 hover:bg-gray-50 transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                </svg>
              </button>
              <span class="px-4 py-2 text-gray-900 min-w-[50px] text-center">{{ item.quantity }}</span>
              <button
                @click="updateQuantity(item.id, item.quantity + 1)"
                class="px-3 py-2 text-gray-600 hover:bg-gray-50 transition-colors"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
              </button>
            </div>
            <!-- Edit Button -->
            <button
              @click="openEditModal(item)"
              class="p-2 text-blue-400 hover:text-blue-600 transition-colors"
              aria-label="Edit item"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
              </svg>
            </button>
            <!-- Delete Button -->
            <button
              @click="removeFromCart(item.id)"
              class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
              aria-label="Remove item"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>

          <!-- Total -->
          <div class="col-span-3 ltr:text-right rtl:text-left">
            <span class="text-base text-gray-900">{{ t('common.egp') }} {{ (item.price * item.quantity).toFixed(2) }}</span>
          </div>
        </div>
      </div>

      <!-- Cart Summary -->
      <div class="mt-10 flex flex-col ltr:items-end rtl:items-start">
        <div class="w-full max-w-md space-y-4">
          <!-- Estimated Total -->
          <div class="flex items-center justify-between text-lg">
            <span class="text-gray-600">{{ t('cart.estimatedTotal') }}</span>
            <span class="font-medium text-gray-900">{{ t('common.egp') }} {{ cartTotal.toFixed(2) }}</span>
          </div>

          <!-- <p class="text-sm text-gray-500 ltr:text-right rtl:text-left">
            {{ t('cart.taxesNote') }}
          </p> -->

          <!-- Checkout Button -->
          <a
            href="/checkout"
            class="block w-full py-4 bg-gray-900 text-white text-center text-sm hover:bg-gray-800 transition-colors"
          >
            {{ t('cart.checkout') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
