<script setup>
import { useCart } from '../composables/useCart'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { cartItems, isCartOpen, closeCart, removeFromCart, updateQuantity, cartTotal } = useCart()
</script>

<template>
  <!-- Overlay -->
  <Transition name="fade">
    <div
      v-if="isCartOpen"
      class="fixed inset-0 bg-black/50 z-50"
      @click="closeCart"
    ></div>
  </Transition>

  <!-- Drawer -->
  <Transition name="slide">
    <div
      v-if="isCartOpen"
      class="fixed top-0 ltr:right-0 rtl:left-0 h-full w-full max-w-md bg-white z-50 flex flex-col shadow-xl"
    >
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">{{ t('cart.title') }}</h2>
        <button
          @click="closeCart"
          class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Cart Items -->
      <div class="flex-1 overflow-y-auto p-4">
        <div v-if="cartItems.length === 0" class="text-center py-12">
          <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
          </svg>
          <p class="text-gray-500">{{ t('cart.empty') }}</p>
          <button
            @click="closeCart"
            class="mt-4 px-6 py-2 bg-gray-900 text-white text-sm hover:bg-gray-800 transition-colors"
          >
            {{ t('cart.continueShopping') }}
          </button>
        </div>

        <div v-else class="space-y-4">
          <div
            v-for="item in cartItems"
            :key="item.id"
            class="flex gap-4 pb-4 border-b border-gray-100"
          >
            <!-- Product Image -->
            <div class="w-20 h-24 bg-gray-100 flex-shrink-0">
              <img :src="item.image" :alt="item.name" class="w-full h-full object-cover" />
            </div>

            <!-- Product Details -->
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</h3>
              <p class="text-sm text-gray-500 mt-1">
                {{ item.color }} / {{ item.size }} / {{ item.height }}
              </p>
              <p class="text-sm font-medium text-gray-900 mt-1">{{ t('common.egp') }} {{ item.price.toFixed(2) }}</p>

              <!-- Quantity Controls -->
              <div class="flex items-center gap-3 mt-2">
                <div class="flex items-center border border-gray-200">
                  <button
                    @click="updateQuantity(item.id, item.quantity - 1)"
                    class="px-2 py-1 text-gray-600 hover:bg-gray-50"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                    </svg>
                  </button>
                  <span class="px-3 py-1 text-sm">{{ item.quantity }}</span>
                  <button
                    @click="updateQuantity(item.id, item.quantity + 1)"
                    class="px-2 py-1 text-gray-600 hover:bg-gray-50"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                  </button>
                </div>

                <button
                  @click="removeFromCart(item.id)"
                  class="text-gray-400 hover:text-red-500 transition-colors"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div v-if="cartItems.length > 0" class="border-t border-gray-200 p-4 space-y-4">
        <!-- Subtotal -->
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-600">{{ t('checkout.subtotal') }}</span>
          <span class="font-medium text-gray-900">{{ t('common.egp') }} {{ cartTotal.toFixed(2) }}</span>
        </div>

        <p class="text-xs text-gray-500">{{ t('cart.taxesNote') }}</p>

        <!-- Action Buttons -->
        <div class="space-y-2">
          <a
            href="/cart"
            class="block w-full py-3 text-center border border-gray-900 text-gray-900 text-sm hover:bg-gray-50 transition-colors"
          >
            {{ t('cart.title') }}
          </a>
          <a
            href="/checkout"
            class="block w-full py-3 text-center bg-gray-900 text-white text-sm hover:bg-gray-800 transition-colors"
          >
            {{ t('cart.checkout') }}
          </a>
        </div>
      </div>
    </div>
  </Transition>
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

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}

.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}

[dir="rtl"] .slide-enter-from,
[dir="rtl"] .slide-leave-to {
  transform: translateX(-100%);
}
</style>
