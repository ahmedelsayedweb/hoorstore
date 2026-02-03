<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCart } from '../composables/useCart'
import { ordersApi, shippingApi } from '../api/cart'
import { productsApi } from '../api/products'

const ORDERS_STORAGE_KEY = 'hoor_orders'
const CUSTOMER_INFO_STORAGE_KEY = 'hoor_customer_info'

const { t, locale } = useI18n()
const { cartItems, cartTotal, clearCart, updateCartItem, removeFromCart } = useCart()

// Edit item modal state
const editingItem = ref(null)
const editForm = ref({
  size: '',
  height: '',
  weight: '',
  quantity: 1
})
const loadingProduct = ref(false)

// Available sizes, heights and weights from product
const availableSizes = ref([])
const availableHeights = ref([])
const availableWeights = ref([])

// Open edit modal and fetch product details
const openEditModal = async (item) => {
  editingItem.value = item
  editForm.value = {
    size: item.size || '',
    height: item.height || '',
    weight: item.weight || '',
    quantity: item.quantity
  }

  // Fetch product to get available sizes, heights and weights
  if (item.productId) {
    loadingProduct.value = true
    try {
      const product = await productsApi.getById(item.productId)
      availableSizes.value = product.sizes || []
      availableHeights.value = product.heights || []
      availableWeights.value = product.weights || []
    } catch (error) {
      console.error('Failed to load product:', error)
      availableSizes.value = []
      availableHeights.value = []
      availableWeights.value = []
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
      size: editForm.value.size,
      height: editForm.value.height,
      weight: editForm.value.weight,
      quantity: editForm.value.quantity
    })
    closeEditModal()
  }
}

// Delete item from cart
const deleteCartItem = async (itemId) => {
  await removeFromCart(itemId)
}

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
const notes = ref('') // Order notes - optional
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

// Clear field error
const clearFieldError = (field) => {
  if (formErrors.value[field]) {
    delete formErrors.value[field]
  }
}



// Computed values
const subtotal = computed(() => cartTotal.value)
const tax = computed(() => Math.round(subtotal.value * 0.14))
const total = computed(() => subtotal.value + shippingCost.value)

// Form validation
const isFormValid = computed(() => {
  return delivery.value.fullName &&
    delivery.value.phone
})

// Generate order image using canvas with proper Arabic/English support
const takeScreenshot = async () => {
  try {
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    const isArabic = locale.value === 'ar'

    // Calculate dynamic height based on items
    const baseHeight = 520
    const itemHeight = 80
    const notesHeight = notes.value ? 60 : 0
    const totalHeight = baseHeight + (cartItems.value.length * itemHeight) + notesHeight

    canvas.width = 600
    canvas.height = Math.max(700, totalHeight)

    const width = canvas.width
    const padding = 30
    const contentWidth = width - (padding * 2)

    // Helper for RTL text positioning
    const getX = (leftPos, rightPos) => isArabic ? rightPos : leftPos
    const getAlign = (ltrAlign) => {
      if (ltrAlign === 'left') return isArabic ? 'right' : 'left'
      if (ltrAlign === 'right') return isArabic ? 'left' : 'right'
      return ltrAlign
    }

    // White background with subtle border
    ctx.fillStyle = '#ffffff'
    ctx.fillRect(0, 0, canvas.width, canvas.height)

    // Border
    ctx.strokeStyle = '#e5e5e5'
    ctx.lineWidth = 2
    ctx.strokeRect(1, 1, canvas.width - 2, canvas.height - 2)

    // Header with primary color
    ctx.fillStyle = '#5b3a8c'
    ctx.fillRect(0, 0, width, 80)

    // Store logo/name
    ctx.fillStyle = '#ffffff'
    ctx.font = 'bold 28px Arial, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(isArabic ? 'متجر حور' : 'HOOR STORE', width / 2, 45)

    // Order number badge
    ctx.font = '14px Arial, sans-serif'
    ctx.fillText(`${isArabic ? 'رقم الطلب' : 'Order'} #${completedOrder.value?.orderNumber || ''}`, width / 2, 68)

    let y = 110

    // Success checkmark
    ctx.beginPath()
    ctx.arc(width / 2, y, 25, 0, 2 * Math.PI)
    ctx.fillStyle = '#ede9f3'
    ctx.fill()
    ctx.strokeStyle = '#5b3a8c'
    ctx.lineWidth = 3
    ctx.stroke()

    // Checkmark
    ctx.beginPath()
    ctx.moveTo(width / 2 - 10, y)
    ctx.lineTo(width / 2 - 3, y + 8)
    ctx.lineTo(width / 2 + 12, y - 8)
    ctx.strokeStyle = '#5b3a8c'
    ctx.lineWidth = 3
    ctx.stroke()

    y += 50

    // Success message
    ctx.fillStyle = '#5b3a8c'
    ctx.font = 'bold 18px Arial, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(isArabic ? 'تم الطلب بنجاح!' : 'Order Placed Successfully!', width / 2, y)

    y += 40

    // Divider
    ctx.strokeStyle = '#e5e5e5'
    ctx.lineWidth = 1
    ctx.beginPath()
    ctx.moveTo(padding, y)
    ctx.lineTo(width - padding, y)
    ctx.stroke()

    y += 25

    // Customer Information Section
    ctx.fillStyle = '#5b3a8c'
    ctx.font = 'bold 16px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    ctx.fillText(
      isArabic ? 'بيانات العميل' : 'Customer Information',
      getX(padding, width - padding),
      y
    )

    y += 25
    ctx.fillStyle = '#374151'
    ctx.font = '14px Arial, sans-serif'

    // Name
    const nameLabel = isArabic ? 'الاسم:' : 'Name:'
    ctx.font = 'bold 14px Arial, sans-serif'
    ctx.fillText(nameLabel, getX(padding, width - padding), y)
    ctx.font = '14px Arial, sans-serif'
    const nameLabelWidth = ctx.measureText(nameLabel).width + 10
    ctx.fillText(
      delivery.value.fullName,
      getX(padding + nameLabelWidth, width - padding - nameLabelWidth),
      y
    )

    y += 22

    // Phone
    const phoneLabel = isArabic ? 'الهاتف:' : 'Phone:'
    ctx.font = 'bold 14px Arial, sans-serif'
    ctx.fillText(phoneLabel, getX(padding, width - padding), y)
    ctx.font = '14px Arial, sans-serif'
    const phoneLabelWidth = ctx.measureText(phoneLabel).width + 10
    const phoneText = delivery.value.phone2 ? `${delivery.value.phone} - ${delivery.value.phone2}` : delivery.value.phone
    ctx.fillText(
      phoneText,
      getX(padding + phoneLabelWidth, width - padding - phoneLabelWidth),
      y
    )

    y += 22

    // Email (if exists)
    if (contact.value) {
      const emailLabel = isArabic ? 'البريد:' : 'Email:'
      ctx.font = 'bold 14px Arial, sans-serif'
      ctx.fillText(emailLabel, getX(padding, width - padding), y)
      ctx.font = '14px Arial, sans-serif'
      const emailLabelWidth = ctx.measureText(emailLabel).width + 10
      ctx.fillText(
        contact.value,
        getX(padding + emailLabelWidth, width - padding - emailLabelWidth),
        y
      )
      y += 22
    }

    // Governorate
    const govLabel = isArabic ? 'المحافظة:' : 'Governorate:'
    ctx.font = 'bold 14px Arial, sans-serif'
    ctx.fillText(govLabel, getX(padding, width - padding), y)
    ctx.font = '14px Arial, sans-serif'
    const govLabelWidth = ctx.measureText(govLabel).width + 10
    const govName = t('checkout.governorates.' + delivery.value.governorate)
    ctx.fillText(
      govName,
      getX(padding + govLabelWidth, width - padding - govLabelWidth),
      y
    )

    y += 22

    // Address
    if (delivery.value.addressDetails) {
      const addrLabel = isArabic ? 'العنوان:' : 'Address:'
      ctx.font = 'bold 14px Arial, sans-serif'
      ctx.fillText(addrLabel, getX(padding, width - padding), y)
      y += 18
      ctx.font = '13px Arial, sans-serif'
      ctx.fillStyle = '#6b7280'

      // Wrap long address text
      const maxWidth = contentWidth - 20
      const words = delivery.value.addressDetails.split(' ')
      let line = ''
      for (let word of words) {
        const testLine = line + word + ' '
        if (ctx.measureText(testLine).width > maxWidth && line !== '') {
          ctx.fillText(line.trim(), getX(padding + 10, width - padding - 10), y)
          line = word + ' '
          y += 18
        } else {
          line = testLine
        }
      }
      if (line.trim()) {
        ctx.fillText(line.trim(), getX(padding + 10, width - padding - 10), y)
      }
      ctx.fillStyle = '#374151'
    }

    y += 25

    // Notes (if exists)
    if (notes.value) {
      const notesLabel = isArabic ? 'ملاحظات:' : 'Notes:'
      ctx.font = 'bold 14px Arial, sans-serif'
      ctx.fillStyle = '#5b3a8c'
      ctx.fillText(notesLabel, getX(padding, width - padding), y)
      y += 18
      ctx.font = '13px Arial, sans-serif'
      ctx.fillStyle = '#6b7280'
      ctx.fillText(notes.value, getX(padding + 10, width - padding - 10), y)
      y += 25
    }

    // Divider
    ctx.strokeStyle = '#e5e5e5'
    ctx.beginPath()
    ctx.moveTo(padding, y)
    ctx.lineTo(width - padding, y)
    ctx.stroke()

    y += 25

    // Order Items Section
    ctx.fillStyle = '#5b3a8c'
    ctx.font = 'bold 16px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    ctx.fillText(
      isArabic ? 'المنتجات' : 'Order Items',
      getX(padding, width - padding),
      y
    )

    y += 20

    // Items
    cartItems.value.forEach((item, index) => {
      // Item background
      if (index % 2 === 0) {
        ctx.fillStyle = '#fafafa'
        ctx.fillRect(padding, y - 5, contentWidth, 70)
      }

      y += 15

      // Item name
      ctx.fillStyle = '#1f2937'
      ctx.font = 'bold 14px Arial, sans-serif'
      ctx.textAlign = getAlign('left')
      ctx.fillText(item.name, getX(padding + 10, width - padding - 10), y)

      // Price on the opposite side
      ctx.textAlign = getAlign('right')
      ctx.fillStyle = '#5b3a8c'
      ctx.fillText(
        `${(item.price * item.quantity).toFixed(2)} ${currencySymbol.value}`,
        getX(width - padding - 10, padding + 10),
        y
      )

      y += 20

      // Item details (color, size, height, weight)
      ctx.fillStyle = '#6b7280'
      ctx.font = '13px Arial, sans-serif'
      ctx.textAlign = getAlign('left')
      const details = [item.color, item.size, item.height, item.weight].filter(Boolean).join(' | ')
      ctx.fillText(details, getX(padding + 10, width - padding - 10), y)

      y += 18

      // Quantity
      const qtyLabel = isArabic ? 'الكمية:' : 'Qty:'
      ctx.fillText(`${qtyLabel} ${item.quantity}`, getX(padding + 10, width - padding - 10), y)

      y += 25
    })

    y += 10

    // Totals Section with background
    ctx.fillStyle = '#f9fafb'
    ctx.fillRect(padding, y, contentWidth, 100)

    // Border for totals
    ctx.strokeStyle = '#e5e5e5'
    ctx.strokeRect(padding, y, contentWidth, 100)

    y += 25

    // Subtotal
    ctx.fillStyle = '#374151'
    ctx.font = '14px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    ctx.fillText(
      isArabic ? 'المجموع الفرعي' : 'Subtotal',
      getX(padding + 15, width - padding - 15),
      y
    )
    ctx.textAlign = getAlign('right')
    ctx.fillText(
      `${subtotal.value.toFixed(2)} ${currencySymbol.value}`,
      getX(width - padding - 15, padding + 15),
      y
    )

    y += 22

    // Shipping
    ctx.textAlign = getAlign('left')
    ctx.fillText(
      isArabic ? 'الشحن' : 'Shipping',
      getX(padding + 15, width - padding - 15),
      y
    )
    ctx.textAlign = getAlign('right')
    ctx.fillText(
      `${shippingCost.value.toFixed(2)} ${currencySymbol.value}`,
      getX(width - padding - 15, padding + 15),
      y
    )

    y += 28

    // Total
    ctx.fillStyle = '#1f2937'
    ctx.font = 'bold 18px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    ctx.fillText(
      isArabic ? 'الإجمالي' : 'Total',
      getX(padding + 15, width - padding - 15),
      y
    )
    ctx.fillStyle = '#5b3a8c'
    ctx.textAlign = getAlign('right')
    ctx.fillText(
      `${total.value.toFixed(2)} ${currencySymbol.value}`,
      getX(width - padding - 15, padding + 15),
      y
    )

    y += 45

    // Payment Method Badge
    ctx.fillStyle = '#ede9f3'
    const badgeWidth = 250
    const badgeX = (width - badgeWidth) / 2
    ctx.beginPath()
    ctx.roundRect(badgeX, y, badgeWidth, 35, 8)
    ctx.fill()

    ctx.fillStyle = '#5b3a8c'
    ctx.font = '14px Arial, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(
      isArabic ? 'الدفع عند الاستلام' : 'Cash on Delivery',
      width / 2,
      y + 23
    )

    y += 55

    // Footer
    ctx.fillStyle = '#9ca3af'
    ctx.font = '12px Arial, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(
      isArabic ? 'شكراً لتسوقكم من متجر حور' : 'Thank you for shopping at HOOR Store',
      width / 2,
      y
    )

    y += 18
    ctx.fillText(
      new Date().toLocaleDateString(isArabic ? 'ar-EG' : 'en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      }),
      width / 2,
      y
    )

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
      notes: notes.value,
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
        <div class="w-16 h-16 bg-primary/20 rounded-full flex items-center justify-center mx-auto mb-4">
          <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            class="w-full py-3 bg-primary text-white rounded font-medium hover:bg-primary/90 transition-colors flex items-center justify-center gap-2"
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

    <!-- Edit Item Modal -->
    <div v-if="editingItem" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div class="bg-white rounded-lg max-w-md w-full p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-gray-900">{{ $t('checkout.editItem') }}</h3>
          <button @click="closeEditModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="flex gap-4 mb-4">
          <img :src="editingItem.image" :alt="editingItem.name" class="w-20 h-24 object-cover rounded" />
          <div>
            <h4 class="font-medium text-gray-900">{{ editingItem.name }}</h4>
            <p class="text-sm text-gray-500">{{ editingItem.color }}</p>
            <p class="text-sm font-medium text-primary">{{ editingItem.price }} {{ $t('common.egp') }}</p>
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
          <!-- Size -->
          <div v-if="availableSizes.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('product.size') }}</label>
            <select
              v-model="editForm.size"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary"
            >
              <option v-for="size in availableSizes" :key="size" :value="size">{{ size }}</option>
            </select>
          </div>

          <!-- Height -->
          <div v-if="availableHeights.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('product.height') }}</label>
            <select
              v-model="editForm.height"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary"
            >
              <option v-for="height in availableHeights" :key="height" :value="height">{{ height }}</option>
            </select>
          </div>

          <!-- Weight -->
          <div v-if="availableWeights.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('product.weight') }}</label>
            <select
              v-model="editForm.weight"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary"
            >
              <option v-for="weight in availableWeights" :key="weight" :value="weight">{{ weight }}</option>
            </select>
          </div>

          <!-- Quantity -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('product.quantity') }}</label>
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
            {{ $t('common.cancel') }}
          </button>
          <button
            @click="saveEditedItem"
            class="flex-1 py-2 bg-primary text-white rounded font-medium hover:bg-primary/90 transition-colors"
          >
            {{ $t('common.save') }}
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Left Column - Form -->
        <form class="space-y-8" @submit.prevent="submitOrder">
          <!-- Delivery Section -->
          <section>
            <h2 class="text-xl font-medium text-gray-900 mb-4">{{ $t('checkout.deliveryInfo') }}</h2>
            <div class="space-y-4">
              <!-- Full Name -->
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('checkout.fullNamePlaceholder') }}</label>
                <input
                  v-model="delivery.fullName"
                  type="text"
                  required
                  :placeholder="$t('checkout.fullNamePlaceholder')"
                  :class="[
                    'w-full px-4 py-3 border rounded focus:outline-none',
                    formErrors['delivery.fullName'] ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-gray-500'
                  ]"
                  @input="clearFieldError('delivery.fullName')"
                />
                <p v-if="formErrors['delivery.fullName']" class="mt-1 text-xs text-red-500">
                  {{ formErrors['delivery.fullName'][0] }}
                </p>
              </div>

              <!-- Phone Numbers -->
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('checkout.phonePlaceholder') }}</label>
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <input
                      required
                      v-model="delivery.phone"
                      type="tel"
                      :placeholder="$t('checkout.phonePlaceholder')"
                      :class="[
                        'w-full px-4 py-3 border rounded focus:outline-none',
                        formErrors['delivery.phone'] ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-gray-500'
                      ]"
                      @input="clearFieldError('delivery.phone')"
                    />
                    <p v-if="formErrors['delivery.phone']" class="mt-1 text-xs text-red-500">
                      {{ formErrors['delivery.phone'][0] }}
                    </p>
                  </div>
                  <div>
                    <input
                      v-model="delivery.phone2"
                      type="tel"
                      :placeholder="$t('checkout.phone2Placeholder')"
                      :class="[
                        'w-full px-4 py-3 border rounded focus:outline-none',
                        formErrors['delivery.phone2'] ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-gray-500'
                      ]"
                      @input="clearFieldError('delivery.phone2')"
                    />
                    <p v-if="formErrors['delivery.phone2']" class="mt-1 text-xs text-red-500">
                      {{ formErrors['delivery.phone2'][0] }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Email (Optional) -->
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('checkout.emailPlaceholder') }}</label>
                <input
                  v-model="contact"
                  type="email"
                  :placeholder="$t('checkout.emailPlaceholder')"
                  :class="[
                    'w-full px-4 py-3 border rounded focus:outline-none',
                    formErrors.contact ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-gray-500'
                  ]"
                  @input="clearFieldError('contact')"
                />
                <p v-if="formErrors.contact" class="mt-1 text-xs text-red-500">
                  {{ formErrors.contact[0] }}
                </p>
              </div>

              <!-- Country -->
              <div v-show="false">
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
                  :class="[
                    'w-full px-4 py-3 border rounded bg-white focus:outline-none',
                    formErrors['delivery.governorate'] ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-gray-500'
                  ]"
                  @change="clearFieldError('delivery.governorate')"
                >
                  <option v-for="key in governorateKeys" :key="key" :value="key">{{ $t('checkout.governorates.' + key) }}</option>
                </select>
                <p v-if="formErrors['delivery.governorate']" class="mt-1 text-xs text-red-500">
                  {{ formErrors['delivery.governorate'][0] }}
                </p>
              </div>

              <!-- Address Details -->
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('checkout.addressDetailsPlaceholder') }}</label>
                <textarea
                  required
                  v-model="delivery.addressDetails"
                  :placeholder="$t('checkout.addressDetailsPlaceholder')"
                  rows="2"
                  :class="[
                    'w-full px-4 py-3 border rounded focus:outline-none resize-none',
                    formErrors['delivery.addressDetails'] ? 'border-red-500 focus:border-red-500' : 'border-gray-300 focus:border-gray-500'
                  ]"
                  @input="clearFieldError('delivery.addressDetails')"
                ></textarea>
                <p v-if="formErrors['delivery.addressDetails']" class="mt-1 text-xs text-red-500">
                  {{ formErrors['delivery.addressDetails'][0] }}
                </p>
              </div>

              <!-- Order Notes -->
              <div>
                <label class="block text-xs text-gray-500 mb-1">{{ $t('checkout.notesPlaceholder') }}</label>
                <textarea
                  v-model="notes"
                  :placeholder="$t('checkout.notesPlaceholder')"
                  rows="3"
                  class="w-full px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-gray-500 resize-none"
                ></textarea>
              </div>

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
          <section v-if="false">
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
            type="submit"
            :disabled="isSubmitting || cartItems.length === 0"
            class="w-full py-4 bg-primary text-white text-sm font-medium rounded hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <svg v-if="isSubmitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ isSubmitting ? $t('checkout.processing') : $t('checkout.placeOrder') }}
          </button>
        </form>

        <!-- Right Column - Order Summary -->
        <div :class="[isRTL ? 'lg:border-l lg:pl-12' : 'lg:border-r lg:pr-12', 'lg:border-gray-200']">
          <div class="sticky top-8 bg-white p-4">
            <!-- Cart Items -->
            <div class="space-y-4 mb-6">
              <div
                v-for="item in cartItems"
                :key="item.id"
                class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg"
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
                <div class="flex-1 min-w-0">
                  <h3 class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</h3>
                  <p class="text-xs text-gray-500">{{ [item.color, item.size, item.height, item.weight].filter(Boolean).join(' / ') }}</p>
                  <p class="text-sm font-medium text-gray-900 mt-1">{{ (item.price * item.quantity).toFixed(2) }} {{ $t('common.egp') }}</p>
                  <!-- Edit/Delete Buttons -->
                  <div class="flex gap-2 mt-2">
                    <button
                      @click="openEditModal(item)"
                      class="text-xs px-2 py-1 bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition-colors"
                    >
                      {{ $t('common.edit') }}
                    </button>
                    <button
                      @click="deleteCartItem(item.id)"
                      class="text-xs px-2 py-1 bg-red-50 text-red-600 rounded hover:bg-red-100 transition-colors"
                    >
                      {{ $t('common.delete') }}
                    </button>
                  </div>
                </div>
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
            <div v-if="false" class="mt-6 pt-4 border-t border-gray-200 text-sm">
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
