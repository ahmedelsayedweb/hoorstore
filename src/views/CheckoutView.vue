<script setup>
import { ref, computed } from 'vue'
import { useCart } from '../composables/useCart'
import emailjs from '@emailjs/browser'
import { EMAILJS_CONFIG } from '../config/emailjs'

const ORDERS_STORAGE_KEY = 'hoor_orders'

const { cartItems, cartTotal, clearCart } = useCart()

// Form data
const contact = ref('')
const delivery = ref({
  country: 'Egypt',
  firstName: '',
  lastName: '',
  address: '',
  city: '',
  governorate: 'Cairo',
  postalCode: '',
  phone: ''
})
const saveInfo = ref(false)
const shippingMethod = ref('standard')
const paymentMethod = ref('cod')
const billingAddress = ref('same')

// Order state
const isSubmitting = ref(false)
const orderComplete = ref(false)
const completedOrder = ref(null)
const orderScreenshot = ref(null)


// Governorates list
const governorates = [
  'Cairo', 'Giza', 'Alexandria', 'Dakahlia', 'Sharqia', 'Qalyubia',
  'Beheira', 'Gharbia', 'Menoufia', 'Kafr El Sheikh', 'Damietta',
  'Port Said', 'Ismailia', 'Suez', 'Fayoum', 'Beni Suef', 'Minya',
  'Assiut', 'Sohag', 'Qena', 'Luxor', 'Aswan', 'Red Sea',
  'New Valley', 'Matruh', 'North Sinai', 'South Sinai'
]

// Shipping cost
const shippingCost = 60

// Computed values
const subtotal = computed(() => cartTotal.value)
const tax = computed(() => Math.round(subtotal.value * 0.14))
const total = computed(() => subtotal.value + shippingCost)

// Form validation
const isFormValid = computed(() => {
  return contact.value &&
    delivery.value.lastName &&
    delivery.value.address &&
    delivery.value.city &&
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
    ctx.fillText(`Name: ${delivery.value.firstName} ${delivery.value.lastName}`, 30, y)
    y += 20
    ctx.fillText(`Phone: ${delivery.value.phone}`, 30, y)
    y += 20
    ctx.fillText(`Address: ${delivery.value.address}, ${delivery.value.city}`, 30, y)
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
      ctx.fillText(`E£${(item.price * item.quantity).toFixed(2)}`, 570, y + 18)
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
    ctx.fillText(`E£${subtotal.value.toFixed(2)}`, 570, y)
    ctx.textAlign = 'left'
    y += 22

    ctx.fillText('Shipping:', 30, y)
    ctx.textAlign = 'right'
    ctx.fillText(`E£${shippingCost.toFixed(2)}`, 570, y)
    ctx.textAlign = 'left'
    y += 30

    ctx.font = 'bold 18px Arial'
    ctx.fillText('Total:', 30, y)
    ctx.textAlign = 'right'
    ctx.fillText(`E£${total.value.toFixed(2)}`, 570, y)
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

// Submit order
const submitOrder = async () => {
  if (!isFormValid.value) {
    alert('Please fill in all required fields')
    return
  }

  if (cartItems.value.length === 0) {
    alert('Your cart is empty')
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
      shipping: shippingCost,
      total: total.value
    }

    // Send order to backend API (this will also send email)
    const response = await fetch('/api/orders', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(orderData)
    })

    if (!response.ok) {
      throw new Error('Failed to submit order')
    }

    const result = await response.json()
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
    alert('Failed to place order. Please try again.')
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
  <div class="min-h-screen bg-white">
    <!-- Header -->
    <header class="bg-primary py-4">
      <div class="max-w-6xl mx-auto px-4">
        <h1 class="text-2xl font-bold text-white">HOOR STORE</h1>
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

        <h2 class="text-2xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h2>
        <p class="text-gray-600 mb-4">Order #{{ completedOrder?.orderNumber }}</p>

        <p class="text-sm text-gray-500 mb-6">
          We've sent the order details to the store. You will be contacted shortly to confirm your order.
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
            Download Order Screenshot
          </button>

          <button
            @click="continueShopping"
            class="w-full py-3 border border-gray-300 text-gray-700 rounded font-medium hover:bg-gray-50 transition-colors"
          >
            Continue Shopping
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Left Column - Form -->
        <div class="space-y-8">
          <!-- Contact Section -->
          <section>
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-xl font-medium text-gray-900">Contact</h2>
              <a href="#" class="text-sm text-blue-600 hover:underline">Sign in</a>
            </div>
            <input
              v-model="contact"
              type="text"
              placeholder="Email or mobile phone number"
              class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500"
            />
          </section>

          <!-- Delivery Section -->
          <section>
            <h2 class="text-xl font-medium text-gray-900 mb-4">Delivery</h2>
            <div class="space-y-4">
              <!-- Country -->
              <div>
                <label class="block text-xs text-gray-500 mb-1">Country/Region</label>
                <select
                  v-model="delivery.country"
                  class="w-full px-4 py-3 border border-gray-300 rounded bg-white focus:outline-none focus:border-gray-500"
                >
                  <option value="Egypt">Egypt</option>
                </select>
              </div>

              <!-- Name Row -->
              <div class="grid grid-cols-2 gap-4">
                <input
                  v-model="delivery.firstName"
                  type="text"
                  placeholder="First name (optional)"
                  class="px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500"
                />
                <input
                  v-model="delivery.lastName"
                  type="text"
                  placeholder="Last name"
                  class="px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500"
                />
              </div>

              <!-- Address -->
              <input
                v-model="delivery.address"
                type="text"
                placeholder="Address"
                class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500"
              />

              <!-- City Row -->
              <div class="grid grid-cols-3 gap-4">
                <input
                  v-model="delivery.city"
                  type="text"
                  placeholder="City"
                  class="px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500"
                />
                <div>
                  <label class="block text-xs text-gray-500 mb-1">Governorate</label>
                  <select
                    v-model="delivery.governorate"
                    class="w-full px-4 py-3 border border-gray-300 rounded bg-white focus:outline-none focus:border-gray-500"
                  >
                    <option v-for="gov in governorates" :key="gov" :value="gov">{{ gov }}</option>
                  </select>
                </div>
                <input
                  v-model="delivery.postalCode"
                  type="text"
                  placeholder="Postal code (optional)"
                  class="px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500"
                />
              </div>

              <!-- Phone -->
              <div class="relative">
                <input
                  v-model="delivery.phone"
                  type="tel"
                  placeholder="Phone"
                  class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500"
                />
                <button class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </button>
              </div>

              <!-- Save Info Checkbox -->
              <label class="flex items-center gap-2 cursor-pointer">
                <input
                  v-model="saveInfo"
                  type="checkbox"
                  class="w-4 h-4 rounded border-gray-300"
                />
                <span class="text-sm text-gray-700">Save this information for next time</span>
              </label>
            </div>
          </section>

          <!-- Shipping Method Section -->
          <section>
            <h2 class="text-xl font-medium text-gray-900 mb-4">Shipping method</h2>
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
                  <span class="text-sm text-gray-900">Standard delivery From 2 to 4 working days</span>
                </div>
                <span class="text-sm font-medium">E£60.00</span>
              </label>
            </div>
          </section>

          <!-- Payment Section -->
          <section>
            <h2 class="text-xl font-medium text-gray-900 mb-2">Payment</h2>
            <p class="text-sm text-gray-500 mb-4">All transactions are secure and encrypted.</p>
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
                  <span class="text-sm text-gray-900">Cash on Delivery (COD)</span>
                </label>
              </div>
            </div>
          </section>

          <!-- Billing Address Section -->
          <section>
            <h2 class="text-xl font-medium text-gray-900 mb-4">Billing address</h2>
            <div class="border border-gray-300 rounded overflow-hidden">
              <label class="flex items-center gap-3 p-4 bg-blue-50/30 border-b border-gray-300 cursor-pointer">
                <input
                  v-model="billingAddress"
                  type="radio"
                  value="same"
                  class="w-4 h-4"
                />
                <span class="text-sm text-gray-900">Same as shipping address</span>
              </label>
              <label class="flex items-center gap-3 p-4 cursor-pointer">
                <input
                  v-model="billingAddress"
                  type="radio"
                  value="different"
                  class="w-4 h-4"
                />
                <span class="text-sm text-gray-900">Use a different billing address</span>
              </label>
            </div>
          </section>

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
            {{ isSubmitting ? 'Processing...' : 'Complete order' }}
          </button>
        </div>

        <!-- Right Column - Order Summary -->
        <div class="lg:border-l lg:pl-12 lg:border-gray-200">
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
                <span class="text-sm text-gray-900">E£{{ (item.price * item.quantity).toFixed(2) }}</span>
              </div>
            </div>

            <!-- Empty Cart Message -->
            <div v-if="cartItems.length === 0" class="text-center py-8 text-gray-500">
              <p>Your cart is empty</p>
              <a href="/" class="text-blue-600 hover:underline mt-2 inline-block">Continue shopping</a>
            </div>

            <!-- Totals -->
            <div v-if="cartItems.length > 0" class="space-y-3 pt-4 border-t border-gray-200">
              <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal</span>
                <span class="text-gray-900">E£{{ subtotal.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between text-sm">
                <span class="text-gray-600 flex items-center gap-1">
                  Shipping
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </span>
                <span class="text-gray-900">E£{{ shippingCost.toFixed(2) }}</span>
              </div>
              <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                <span class="text-lg font-medium text-gray-900">Total</span>
                <div class="text-right">
                  <span class="text-xs text-gray-500 mr-2">EGP</span>
                  <span class="text-xl font-medium text-gray-900">E£{{ total.toFixed(2) }}</span>
                </div>
              </div>
              <p class="text-xs text-gray-500">Including E£{{ tax.toFixed(2) }} in taxes</p>
            </div>

            <!-- Customer Info Summary (for screenshot) -->
            <div v-if="contact || delivery.phone" class="mt-6 pt-4 border-t border-gray-200 text-sm">
              <h4 class="font-medium text-gray-900 mb-2">Customer Details</h4>
              <p v-if="contact" class="text-gray-600">{{ contact }}</p>
              <p v-if="delivery.firstName || delivery.lastName" class="text-gray-600">
                {{ delivery.firstName }} {{ delivery.lastName }}
              </p>
              <p v-if="delivery.phone" class="text-gray-600">{{ delivery.phone }}</p>
              <p v-if="delivery.address" class="text-gray-600">
                {{ delivery.address }}, {{ delivery.city }}, {{ delivery.governorate }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
