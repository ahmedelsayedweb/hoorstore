<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCart } from '../composables/useCart'
import { ordersApi, shippingApi } from '../api/cart'

const ORDERS_STORAGE_KEY = 'hoor_orders'
const CUSTOMER_INFO_STORAGE_KEY = 'hoor_customer_info'

const { t, locale } = useI18n()
const { cartItems, cartTotal, clearCart } = useCart()

// Language direction
const isRTL = computed(() => locale.value === 'ar')

// Currency symbol based on locale
const currencySymbol = computed(() => locale.value === 'ar' ? 'ج.م' : 'LE')

// Toggle language
const toggleLanguage = () => {
  locale.value = locale.value === 'ar' ? 'en' : 'ar'
  localStorage.setItem('locale', locale.value)
}

// Governorates keys for translation
const governorateKeys = [
  'cairo', 'giza', 'alexandria', 'dakahlia', 'sharqia', 'qalyubia',
  'beheira', 'gharbia', 'menoufia', 'kafrElSheikh', 'damietta',
  'portSaid', 'ismailia', 'suez', 'fayoum', 'beniSuef', 'minya',
  'assiut', 'sohag', 'qena', 'luxor', 'aswan', 'redSea',
  'matrouh', 'northSinai', 'southSinai'
]

// Form data
const contact = ref('') // Email - optional
const delivery = ref({
  country: 'egypt',
  fullName: '',
  addressDetails: '',
  governorate: 'cairo',
  phone: '',
  phone2: ''
})
const saveInfo = ref(false)
const shippingMethod = ref('standard')
const paymentMethod = ref('cod')
const billingAddress = ref('same')

// Shipping zones from API
const shippingZones = ref([])
const shippingCost = ref(75)
const deliveryTime = ref('24')
const loadingShipping = ref(false)

// Governorate mapping for shipping calculation
const governorateMap = {
  'cairo': 'القاهرة',
  'giza': 'الجيزة',
  'alexandria': 'الإسكندرية',
  'dakahlia': 'الدقهلية',
  'sharqia': 'الشرقية',
  'qalyubia': 'القليوبية',
  'beheira': 'البحيرة',
  'gharbia': 'الغربية',
  'menoufia': 'المنوفية',
  'kafrElSheikh': 'كفر الشيخ',
  'damietta': 'دمياط',
  'portSaid': 'بورسعيد',
  'ismailia': 'الإسماعيلية',
  'suez': 'السويس',
  'fayoum': 'الفيوم',
  'beniSuef': 'بني سويف',
  'minya': 'المنيا',
  'assiut': 'أسيوط',
  'sohag': 'سوهاج',
  'qena': 'قنا',
  'luxor': 'الأقصر',
  'aswan': 'أسوان',
  'redSea': 'البحر الأحمر',
  'matrouh': 'مرسى مطروح',
  'northSinai': 'شمال سيناء',
  'southSinai': 'جنوب سيناء'
}

// Update shipping cost when governorate changes
const updateShippingCost = (governorate) => {
  const arabicName = governorateMap[governorate] || governorate

  for (const zone of shippingZones.value) {
    if (zone.areas.some(area => area.includes(arabicName) || arabicName.includes(area))) {
      shippingCost.value = zone.price
      deliveryTime.value = zone.deliveryTime
      return
    }
  }

  // Default fallback
  shippingCost.value = 100
  deliveryTime.value = '48-72'
}

// Load shipping zones from API
const loadShippingZones = async () => {
  try {
    loadingShipping.value = true
    const response = await shippingApi.getZones()
    if (response.success) {
      shippingZones.value = response.zones
      // Set initial shipping cost based on default governorate
      updateShippingCost(delivery.value.governorate)
    }
  } catch (error) {
    console.error('Failed to load shipping zones:', error)
  } finally {
    loadingShipping.value = false
  }
}

// Load shipping zones and saved customer info on mount
onMounted(async () => {
  loadSavedCustomerInfo()
  await loadShippingZones()
})

// Watch governorate changes
watch(() => delivery.value.governorate, (newGov) => {
  updateShippingCost(newGov)
})

// Save customer info to localStorage
const saveCustomerInfo = () => {
  if (saveInfo.value) {
    const customerData = {
      contact: contact.value,
      delivery: delivery.value
    }
    localStorage.setItem(CUSTOMER_INFO_STORAGE_KEY, JSON.stringify(customerData))
  }
}

// Load saved customer info from localStorage
const loadSavedCustomerInfo = () => {
  const savedData = localStorage.getItem(CUSTOMER_INFO_STORAGE_KEY)
  if (savedData) {
    try {
      const customerData = JSON.parse(savedData)
      if (customerData.contact) {
        contact.value = customerData.contact
      }
      if (customerData.delivery) {
        delivery.value = { ...delivery.value, ...customerData.delivery }
      }
      saveInfo.value = true
    } catch (error) {
      console.error('Failed to load saved customer info:', error)
    }
  }
}

// Watch saveInfo checkbox - save or clear data
watch(saveInfo, (newValue) => {
  if (newValue) {
    saveCustomerInfo()
  } else {
    localStorage.removeItem(CUSTOMER_INFO_STORAGE_KEY)
  }
})

// Watch form data changes and save if saveInfo is checked
watch([contact, delivery], () => {
  saveCustomerInfo()
}, { deep: true })

// Order state
const isSubmitting = ref(false)
const orderComplete = ref(false)
const completedOrder = ref(null)
const orderScreenshot = ref(null)
const formErrors = ref({})
const errorMessage = ref('')



// Computed values
const subtotal = computed(() => cartTotal.value)
const tax = computed(() => Math.round(subtotal.value * 0.14))
const total = computed(() => subtotal.value + shippingCost.value)

// Form validation
const isFormValid = computed(() => {
  return delivery.value.fullName &&
    delivery.value.phone
})

// Generate order image using canvas (simpler approach)
const takeScreenshot = async () => {
  try {
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')

    canvas.width = 600
    canvas.height = 800

    // White background
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)

    // Header
    ctx.fillStyle = '#c9a66b'
    ctx.fillRect(0, 0, 600, 60)
    ctx.fillStyle = '#ffffff'
    ctx.font = 'bold 24px Arial'
    ctx.textAlign = 'center'
    ctx.fillText('HOOR', 300, 40)

    // Order details
    ctx.fillStyle = '#333333'
    ctx.font = 'bold 18px Arial'
    ctx.textAlign = 'left'
    ctx.fillText(`Order #${completedOrder.value?.orderNumber || ''}`, 30, 100)

    ctx.font = '14px Arial'
    ctx.fillStyle = '#666666'
    let y = 140

    // Customer info
    ctx.fillStyle = '#333333'
    ctx.font = 'bold 14px Arial'
    ctx.fillText('Customer Information:', 30, y)
    y += 25
    ctx.font = '14px Arial'
    ctx.fillStyle = '#666666'
    ctx.fillText(`Contact: ${contact.value}`, 30, y)
    y += 20
    ctx.fillText(`Name: ${delivery.value.fullName}`, 30, y)
    y += 20
    const phoneText = delivery.value.phone2 ? `${delivery.value.phone} - ${delivery.value.phone2}` : delivery.value.phone
    ctx.fillText(`Phone: ${phoneText}`, 30, y)
    y += 20
    ctx.fillText(`Governorate: ${delivery.value.governorate}`, 30, y)
    if (delivery.value.addressDetails) {
      y += 18
      ctx.fillStyle = '#888888'
      ctx.font = '12px Arial'
      ctx.fillText(delivery.value.addressDetails, 30, y)
      ctx.font = '14px Arial'
    }
    y += 40

    // Items
    ctx.fillStyle = '#333333'
    ctx.font = 'bold 14px Arial'
    ctx.fillText('Order Items:', 30, y)
    y += 25

    ctx.font = '14px Arial'
    cartItems.value.forEach(item => {
      ctx.fillStyle = '#333333'
      ctx.fillText(`${item.name}`, 30, y)
      ctx.fillStyle = '#666666'
      ctx.fillText(`${item.color} / ${item.size} / ${item.height}`, 30, y + 18)
      ctx.fillText(`Qty: ${item.quantity}`, 30, y + 36)
      ctx.fillStyle = '#333333'
      ctx.textAlign = 'right'
      ctx.fillText(`${(item.price * item.quantity).toFixed(2)} ${currencySymbol.value}`, 570, y + 18)
      ctx.textAlign = 'left'
      y += 60
    })

    // Totals
    y += 20
    ctx.strokeStyle = '#dddddd'
    ctx.beginPath()
    ctx.moveTo(30, y)
    ctx.lineTo(570, y)
    ctx.stroke()
    y += 25

    ctx.font = '14px Arial'
    ctx.fillText('Subtotal:', 30, y)
    ctx.textAlign = 'right'
    ctx.fillText(`${subtotal.value.toFixed(2)} ${currencySymbol.value}`, 570, y)
    ctx.textAlign = 'left'
    y += 22

    ctx.fillText('Shipping:', 30, y)
    ctx.textAlign = 'right'
    ctx.fillText(`${shippingCost.value.toFixed(2)} ${currencySymbol.value}`, 570, y)
    ctx.textAlign = 'left'
    y += 30

    ctx.font = 'bold 18px Arial'
    ctx.fillText('Total:', 30, y)
    ctx.textAlign = 'right'
    ctx.fillText(`${total.value.toFixed(2)} ${currencySymbol.value}`, 570, y)
    ctx.textAlign = 'left'
    y += 40

    // Payment method
    ctx.fillStyle = '#e8f5e9'
    ctx.fillRect(30, y, 540, 40)
    ctx.fillStyle = '#333333'
    ctx.font = '14px Arial'
    ctx.fillText('Payment: Cash on Delivery (COD)', 45, y + 26)

    return canvas.toDataURL('image/png')
  } catch (error) {
    console.error('Screenshot error:', error)
    return null
  }
}

// Download screenshot
const downloadScreenshot = () => {
  if (!orderScreenshot.value) return

  const link = document.createElement('a')
  link.download = `order-${completedOrder.value?.orderNumber || 'confirmation'}.png`
  link.href = orderScreenshot.value
  link.click()
}

// Error messages translations (computed based on locale)
const getErrorTranslation = (key) => {
  const translations = {
    'contact': t('checkout.email'),
    'The contact must be a valid email address.': locale.value === 'ar' ? 'يرجى إدخال بريد إلكتروني صحيح' : 'Please enter a valid email address',
    'The contact field is required.': locale.value === 'ar' ? 'البريد الإلكتروني مطلوب' : 'Email is required',
    'delivery.fullName': t('checkout.fullName'),
    'delivery.addressDetails': t('checkout.addressDetails'),
    'delivery.governorate': t('checkout.governorate'),
    'delivery.phone': t('checkout.phone'),
    'delivery.phone2': t('checkout.phone2'),
  }
  return translations[key] || key
}

// Submit order
const submitOrder = async () => {
  // Clear previous errors
  formErrors.value = {}
  errorMessage.value = ''

  if (!isFormValid.value) {
    errorMessage.value = t('checkout.fillRequired')
    return
  }

  if (cartItems.value.length === 0) {
    errorMessage.value = t('checkout.cartEmpty')
    return
  }

  isSubmitting.value = true

  try {
    const orderData = {
      contact: contact.value,
      delivery: delivery.value,
      shippingMethod: shippingMethod.value,
      paymentMethod: paymentMethod.value,
      billingAddress: billingAddress.value,
      items: cartItems.value,
      subtotal: subtotal.value,
      shipping: shippingCost.value,
      total: total.value
    }

    // Send order to Laravel backend API
    const result = await ordersApi.create(orderData)

    // Handle validation errors
    if (result.status === 422 || result.errors) {
      formErrors.value = result.errors || {}
      // Build error message
      const errors = []
      for (const [, messages] of Object.entries(result.errors)) {
        messages.forEach(msg => {
          errors.push(getErrorTranslation(msg))
        })
      }
      errorMessage.value = errors.join(locale.value === 'ar' ? '، ' : ', ')
      return
    }

    if (!result.success) {
      throw new Error(result.error || 'Failed to submit order')
    }

    completedOrder.value = result.order

    // Also save to localStorage as backup
    const existingOrders = JSON.parse(localStorage.getItem(ORDERS_STORAGE_KEY) || '[]')
    existingOrders.push(result.order)
    localStorage.setItem(ORDERS_STORAGE_KEY, JSON.stringify(existingOrders))

    // Take screenshot before clearing
    orderScreenshot.value = await takeScreenshot()

    // Show success
    orderComplete.value = true

    // Clear cart
    await clearCart()
  } catch (error) {
    console.error('Order error:', error)
    errorMessage.value = t('checkout.orderFailed')
  } finally {
    isSubmitting.value = false
  }
}

// Go back to shopping
const continueShopping = () => {
  window.location.href = '/'
}
</script>

<template>
  <div class="min-h-screen bg-white" :dir="isRTL ? 'rtl' : 'ltr'">
    <!-- Header -->
    <header class="bg-primary py-4">
      <div class="max-w-6xl mx-auto px-4 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-white">{{ $t('checkout.storeName') }}</h1>
        <!-- Language Switcher -->
        <button
          @click="toggleLanguage"
          class="flex items-center gap-2 px-3 py-1.5 bg-white/20 hover:bg-white/30 rounded text-white text-sm transition-colors"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
          </svg>
          {{ locale === 'ar' ? 'English' : 'عربي' }}
        </button>
      </div>
    </header>

    <!-- Order Success Modal -->
    <div v-if="orderComplete" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-6 text-center">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $t('checkout.orderSuccess') }}</h2>
        <p class="text-gray-600 mb-4">{{ $t('checkout.orderNumber') }} #{{ completedOrder?.orderNumber }}</p>

        <p class="text-sm text-gray-500 mb-6">
          {{ $t('checkout.orderConfirmMessage') }}
        </p>

        <div class="space-y-3">
          <button
            v-if="orderScreenshot"
            @click="downloadScreenshot"
            class="w-full py-3 bg-[#c9a66b] text-white rounded font-medium hover:bg-[#b8955a] transition-colors flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            {{ $t('checkout.downloadOrder') }}
          </button>

          <button
            @click="continueShopping"
            class="w-full py-3 border border-gray-300 text-gray-700 rounded font-medium hover:bg-gray-50 transition-colors"
          >
            {{ $t('cart.continueShopping') }}
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Left Column - Form -->
        <div class="space-y-8">
          <!-- Delivery Section -->
          <section>
            <h2 class="text-xl font-medium text-gray-900 mb-4">{{ $t('checkout.deliveryInfo') }}</h2>
            <div class="space-y-4">
              <!-- Full Name -->
              <div>
                <input
                  v-model="delivery.fullName"
                  type="text"
                  :placeholder="$t('checkout.fullNamePlaceholder')"
                  class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500"
                />
              </div>

              <!-- Phone Numbers -->
              <div class="grid grid-cols-2 gap-4">
                <input
                  v-model="delivery.phone"
                  type="tel"
                  :placeholder="$t('checkout.phonePlaceholder')"
                  class="px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500"
                />
                <input
                  v-model="delivery.phone2"
                  type="tel"
                  :placeholder="$t('checkout.phone2Placeholder')"
                  class="px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500"
                />
              </div>

              <!-- Email (Optional) -->
              <input
                v-model="contact"
                type="email"
                :placeholder="$t('checkout.emailPlaceholder')"
                :class="[
                  'w-full px-4 py-3 border rounded focus:outline-none',
                  formErrors.contact ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-gray-500'
                ]"
                @input="formErrors.contact = null; errorMessage = ''"
              />

              <!-- Country -->
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('checkout.country') }}</label>
                <select
                  v-model="delivery.country"
                  class="w-full px-4 py-3 border border-gray-300 rounded bg-white focus:outline-none focus:border-gray-500"
                >
                  <option value="egypt">{{ $t('checkout.egypt') }}</option>
                </select>
              </div>

              <!-- Governorate -->
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('checkout.governoratePlaceholder') }}</label>
                <select
                  v-model="delivery.governorate"
                  class="w-full px-4 py-3 border border-gray-300 rounded bg-white focus:outline-none focus:border-gray-500"
                >
                  <option v-for="key in governorateKeys" :key="key" :value="key">{{ $t('checkout.governorates.' + key) }}</option>
                </select>
              </div>

              <!-- Address Details -->
              <textarea
                v-model="delivery.addressDetails"
                :placeholder="$t('checkout.addressDetailsPlaceholder')"
                rows="2"
                class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500 resize-none"
              ></textarea>

              <!-- Save Info Checkbox -->
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="saveInfo"
                  type="checkbox"
                  class="w-4 h-4 rounded border-gray-300"
                />
                <span class="text-sm text-gray-700">{{ $t('checkout.saveInfo') }}</span>
              </label>
            </div>
          </section>

          <!-- Shipping Method Section -->
          <section>
            <h2 class="text-xl font-medium text-gray-900 mb-4">{{ $t('checkout.shipping') }}</h2>
            <div class="border border-blue-500 rounded p-4 bg-blue-50/30">
              <label class="flex items-center justify-between cursor-pointer">
                <div class="flex items-center gap-3">
                  <input
                    v-model="shippingMethod"
                    type="radio"
                    value="standard"
                    class="w-4 h-4"
                    checked
                  />
                  <div>
                    <span class="text-sm text-gray-900">{{ $t('checkout.standardShipping') }}</span>
                    <p class="text-xs text-gray-500 mt-1">
                      {{ locale === 'ar' ? 'وقت التوصيل:' : 'Delivery:' }} {{ deliveryTime }} {{ locale === 'ar' ? 'ساعة' : 'hours' }}
                    </p>
                  </div>
                </div>
                <span class="text-sm font-medium">{{ shippingCost }} {{ $t('common.egp') }}</span>
              </label>
            </div>
          </section>

          <!-- Payment Section -->
          <section>
            <h2 class="text-xl font-medium text-gray-900 mb-2">{{ $t('checkout.payment') }}</h2>
            <p class="text-sm text-gray-500 mb-4">{{ $t('checkout.paymentSecure') }}</p>
            <div class="border border-gray-300 rounded overflow-hidden">
              <div class="p-4 bg-gray-100">
                <label class="flex items-center gap-3 cursor-pointer">
                  <input
                    v-model="paymentMethod"
                    type="radio"
                    value="cod"
                    class="w-4 h-4"
                    checked
                  />
                  <span class="text-sm text-gray-900">{{ $t('checkout.cod') }}</span>
                </label>
              </div>
            </div>
          </section>

          <!-- Billing Address Section -->
          <section>
            <h2 class="text-xl font-medium text-gray-900 mb-4">{{ $t('checkout.billing') }}</h2>
            <div class="border border-gray-300 rounded overflow-hidden">
              <label class="flex items-center gap-3 p-4 bg-blue-50/30 border-b border-gray-300 cursor-pointer">
                <input
                  v-model="billingAddress"
                  type="radio"
                  value="same"
                  class="w-4 h-4"
                />
                <span class="text-sm text-gray-900">{{ $t('checkout.sameAsDelivery') }}</span>
              </label>
              <label class="flex items-center gap-3 p-4 cursor-pointer">
                <input
                  v-model="billingAddress"
                  type="radio"
                  value="different"
                  class="w-4 h-4"
                />
                <span class="text-sm text-gray-900">{{ $t('checkout.differentAddress') }}</span>
              </label>
            </div>
          </section>

          <!-- Error Message -->
          <div v-if="errorMessage" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center gap-2 text-red-700">
              <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-sm font-medium">{{ errorMessage }}</span>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            @click="submitOrder"
            :disabled="isSubmitting || cartItems.length === 0"
            class="w-full py-4 bg-gray-900 text-white text-sm font-medium rounded hover:bg-gray-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <svg v-if="isSubmitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isSubmitting ? $t('checkout.processing') : $t('checkout.placeOrder') }}
          </button>
        </div>

        <!-- Right Column - Order Summary -->
        <div :class="[isRTL ? 'lg:border-l lg:pl-12' : 'lg:border-r lg:pr-12', 'lg:border-gray-200']">
          <div class="sticky top-8 bg-white p-4">
            <!-- Cart Items -->
            <div class="space-y-4 mb-6">
              <div
                v-for="item in cartItems"
                :key="item.id"
                class="flex items-start gap-4"
              >
                <div class="relative">
                  <div class="w-16 h-20 bg-gray-100 rounded overflow-hidden">
                    <img
                      :src="item.image"
                      :alt="item.name"
                      class="w-full h-full object-cover"
                    />
                  </div>
                  <span class="absolute -top-2 -right-2 w-5 h-5 bg-gray-500 text-white text-xs rounded-full flex items-center justify-center">
                    {{ item.quantity }}
                  </span>
                </div>
                <div class="flex-1">
                  <h3 class="text-sm font-medium text-gray-900">{{ item.name }}</h3>
                  <p class="text-xs text-gray-500">{{ item.color }} / {{ item.size }} / {{ item.height }}</p>
                </div>
                <span class="text-sm text-gray-900">{{ (item.price * item.quantity).toFixed(2) }} {{ $t('common.egp') }}</span>
              </div>
            </div>

            <!-- Empty Cart Message -->
            <div v-if="cartItems.length === 0" class="text-center py-8 text-gray-500">
              <p>{{ $t('checkout.emptyCart') }}</p>
              <a href="/" class="text-blue-600 hover:underline mt-2 inline-block">{{ $t('cart.continueShopping') }}</a>
            </div>

            <!-- Totals -->
            <div v-if="cartItems.length > 0" class="space-y-3 pt-4 border-t border-gray-200">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">{{ $t('checkout.subtotal') }}</span>
                <span class="text-gray-900">{{ subtotal.toFixed(2) }} {{ $t('common.egp') }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 flex items-center gap-1">
                  {{ $t('checkout.shippingCost') }}
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </span>
                <span class="text-gray-900">{{ Number(shippingCost).toFixed(2) }} {{ $t('common.egp') }}</span>
              </div>
              <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                <span class="text-lg font-medium text-gray-900">{{ $t('checkout.totalAmount') }}</span>
                <div :class="isRTL ? 'text-left' : 'text-right'">
                  <span class="text-xl font-medium text-gray-900">{{ total.toFixed(2) }} {{ $t('common.egp') }}</span>
                </div>
              </div>
              <p v-if="false" class="text-xs text-gray-500">{{ $t('checkout.tax', { amount: tax.toFixed(2) }) }}</p>
            </div>

            <!-- Customer Info Summary (for screenshot) -->
            <div v-if="delivery.fullName || delivery.phone" class="mt-6 pt-4 border-t border-gray-200 text-sm">
              <h4 class="font-medium text-gray-900 mb-2">{{ $t('checkout.customerInfo') }}</h4>
              <p v-if="delivery.fullName" class="text-gray-600">{{ delivery.fullName }}</p>
              <p v-if="delivery.phone" class="text-gray-600">
                {{ delivery.phone }}
                <span v-if="delivery.phone2"> - {{ delivery.phone2 }}</span>
              </p>
              <p v-if="contact" class="text-gray-600">{{ contact }}</p>
              <p class="text-gray-600">{{ $t('checkout.governorates.' + delivery.governorate) }}</p>
              <p v-if="delivery.addressDetails" class="text-gray-500 text-xs">
                {{ delivery.addressDetails }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
