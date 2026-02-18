<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ordersApi } from '../api/cart'

const { t, locale } = useI18n()

const phone = ref('')
const orders = ref([])
const loading = ref(false)
const searched = ref(false)
const expandedOrder = ref(null)

const statusColors = {
  pending: 'bg-yellow-100 text-yellow-800',
  confirmed: 'bg-blue-100 text-blue-800',
  processing: 'bg-purple-100 text-purple-800',
  shipped: 'bg-indigo-100 text-indigo-800',
  delivered: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800'
}

const searchOrders = async () => {
  if (!phone.value.trim()) return
  loading.value = true
  searched.value = true
  try {
    orders.value = await ordersApi.getByPhone(phone.value.trim())
  } catch (e) {
    orders.value = []
  } finally {
    loading.value = false
  }
}

const toggleDetails = (orderId) => {
  expandedOrder.value = expandedOrder.value === orderId ? null : orderId
}

const formatDate = (isoDate) => {
  return new Date(isoDate).toLocaleDateString(
    locale.value === 'ar' ? 'ar-EG' : 'en-US',
    { year: 'numeric', month: 'short', day: 'numeric' }
  )
}

const getItemTotal = (item) => {
  return (item.price * item.quantity).toFixed(2)
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-3xl mx-auto">

      <!-- Page Title -->
      <h1 class="text-2xl md:text-3xl font-bold text-[#5B3A8C] mb-2 text-center font-[Cairo]">
        {{ t('myOrders.title') }}
      </h1>
      <p class="text-gray-500 text-center mb-8">{{ t('myOrders.enterPhone') }}</p>

      <!-- Search Form -->
      <form @submit.prevent="searchOrders" class="flex gap-3 mb-8 max-w-md mx-auto">
        <input
          v-model="phone"
          type="tel"
          :placeholder="t('myOrders.phonePlaceholder')"
          class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#5B3A8C] focus:border-transparent text-base"
          dir="ltr"
        />
        <button
          type="submit"
          :disabled="loading || !phone.trim()"
          class="px-6 py-3 bg-[#5B3A8C] text-white rounded-lg font-medium hover:bg-[#4A2E73] transition-colors disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
        >
          {{ loading ? t('myOrders.searching') : t('myOrders.search') }}
        </button>
      </form>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block w-8 h-8 border-4 border-[#5B3A8C] border-t-transparent rounded-full animate-spin"></div>
      </div>

      <!-- No Orders Found -->
      <div v-else-if="searched && orders.length === 0" class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
        </div>
        <p class="text-gray-600 text-lg font-medium">{{ t('myOrders.noOrders') }}</p>
        <p class="text-gray-400 mt-1">{{ t('myOrders.tryAgain') }}</p>
      </div>

      <!-- Orders List -->
      <div v-else-if="orders.length > 0" class="space-y-4">
        <div
          v-for="order in orders"
          :key="order.id"
          class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
        >
          <!-- Order Header -->
          <div class="p-4 md:p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
              <div>
                <span class="text-xs text-gray-400">{{ t('myOrders.orderNumber') }}</span>
                <p class="font-semibold text-[#5B3A8C] text-sm" dir="ltr">{{ order.orderNumber }}</p>
              </div>
              <span
                :class="statusColors[order.status]"
                class="px-3 py-1 rounded-full text-xs font-medium"
              >
                {{ t('myOrders.statuses.' + order.status) }}
              </span>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500">
              <span>{{ formatDate(order.createdAt) }}</span>
              <span class="font-semibold text-gray-800">
                {{ order.total }} {{ t('common.egp') }}
              </span>
            </div>

            <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100">
              <span class="text-sm text-gray-500">
                {{ t('myOrders.itemCount', { count: order.items.length }) }}
              </span>
              <button
                @click="toggleDetails(order.id)"
                class="text-sm text-[#5B3A8C] hover:text-[#4A2E73] font-medium transition-colors"
              >
                {{ expandedOrder === order.id ? t('myOrders.hideDetails') : t('myOrders.viewDetails') }}
              </button>
            </div>
          </div>

          <!-- Order Details (Expandable) -->
          <div v-if="expandedOrder === order.id" class="border-t border-gray-100 bg-gray-50 p-4 md:p-5">
            <!-- Items -->
            <div class="space-y-3 mb-4">
              <div
                v-for="item in order.items"
                :key="item.id"
                class="flex gap-3 bg-white rounded-lg p-3"
              >
                <img
                  v-if="item.image"
                  :src="item.image"
                  :alt="item.name"
                  class="w-16 h-16 object-cover rounded-md flex-shrink-0"
                />
                <div class="flex-1 min-w-0">
                  <p class="font-medium text-gray-800 text-sm truncate">{{ item.name }}</p>
                  <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-gray-500 mt-1">
                    <span v-if="item.color">{{ t('myOrders.color') }}: {{ item.color }}</span>
                    <span v-if="item.sizes && item.sizes.length">{{ t('myOrders.size') }}: {{ item.sizes.join(', ') }}</span>
                    <span>{{ t('myOrders.quantity') }}: {{ item.quantity }}</span>
                  </div>
                  <p class="text-sm font-medium text-gray-700 mt-1">{{ getItemTotal(item) }} {{ t('common.egp') }}</p>
                </div>
              </div>
            </div>

            <!-- Price Breakdown -->
            <div class="border-t border-gray-200 pt-3 space-y-2 text-sm">
              <div class="flex justify-between text-gray-500">
                <span>{{ t('myOrders.subtotal') }}</span>
                <span>{{ order.subtotal }} {{ t('common.egp') }}</span>
              </div>
              <div class="flex justify-between text-gray-500">
                <span>{{ t('myOrders.shipping') }}</span>
                <span>{{ order.shipping }} {{ t('common.egp') }}</span>
              </div>
              <div v-if="order.discountAmount > 0" class="flex justify-between text-green-600">
                <span>{{ t('myOrders.discount') }} ({{ order.couponCode }})</span>
                <span>-{{ order.discountAmount }} {{ t('common.egp') }}</span>
              </div>
              <div class="flex justify-between font-bold text-gray-800 pt-2 border-t border-gray-200">
                <span>{{ t('myOrders.total') }}</span>
                <span>{{ order.total }} {{ t('common.egp') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>
