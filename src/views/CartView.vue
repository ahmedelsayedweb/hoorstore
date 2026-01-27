<script setup>
import { useCart } from '../composables/useCart'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const { cartItems, removeFromCart, updateQuantity, cartTotal } = useCart()
</script>

<template>
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
                <p>{{ t('product.size') }}: {{ item.size }}</p>
                <p>{{ t('product.height') }}: {{ item.height }}</p>
              </div>
            </div>
          </div>

          <!-- Quantity -->
          <div class="col-span-3 flex items-center justify-start md:justify-center gap-3">
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

          <p class="text-sm text-gray-500 ltr:text-right rtl:text-left">
            {{ t('cart.taxesNote') }}
          </p>

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
