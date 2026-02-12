<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCart } from '../composables/useCart'
import { ordersApi, shippingApi, couponsApi } from '../api/cart'
import { productsApi } from '../api/products'

const ORDERS_STORAGE_KEY = 'hoor_orders'
const CUSTOMER_INFO_STORAGE_KEY = 'hoor_customer_info'

const videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'webm']
const isVideo = (url) => {
  if (!url) return false
  const ext = url.split('.').pop()?.toLowerCase().split('?')[0]
  return videoExtensions.includes(ext)
}

const { t, locale } = useI18n()
const { cartItems, cartTotal, clearCart, updateCartItem, removeFromCart } = useCart()

// Edit item modal state
const editingItem = ref(null)
const editForm = ref({
  sizes: [],
  height: '',
  weight: '',
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

// Variant support
const productHasVariants = ref(false)
const currentVariants = ref([])
const variantSizesForColor = ref([])

// Available sizes, heights, weights and colors from product
const availableSizes = ref([])
const availableHeights = ref([])
const availableWeights = ref([])
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
  // Update available sizes for variant products
  if (productHasVariants.value) {
    const variant = currentVariants.value.find(v => v.color === colorName)
    variantSizesForColor.value = variant?.sizes || []
    editForm.value.sizes = []
  }
}

// Open edit modal and fetch product details
const openEditModal = async (item) => {
  editingItem.value = item
  editForm.value = {
    sizes: Array.isArray(item.sizes) ? [...item.sizes] : (item.sizes ? [item.sizes] : []),
    height: item.height || '',
    weight: item.weight || '',
    color: item.color || '',
    image: item.image || '',
    quantity: item.quantity
  }

  // Fetch product to get available sizes, heights, weights and colors
  if (item.productId) {
    loadingProduct.value = true
    try {
      const product = await productsApi.getById(item.productId)
      availableHeights.value = product.heights || []
      availableWeights.value = product.weights || []

      if (product.variants && Array.isArray(product.variants) && product.variants.length > 0) {
        productHasVariants.value = true
        currentVariants.value = product.variants
        const hasColors = product.variants.some(v => v.color && v.color.trim() !== '')
        if (hasColors) {
          availableColors.value = product.variants.map(v => ({ name: v.color, image: v.image || '' }))
          availableSizes.value = []
          const variant = product.variants.find(v => v.color === editForm.value.color)
          variantSizesForColor.value = variant?.sizes || []
        } else {
          availableColors.value = []
          availableSizes.value = []
          variantSizesForColor.value = product.variants[0]?.sizes || []
        }
      } else {
        productHasVariants.value = false
        currentVariants.value = []
        variantSizesForColor.value = []
        availableSizes.value = product.sizes || []
        availableColors.value = product.colors || []
      }
    } catch (error) {
      console.error('Failed to load product:', error)
      productHasVariants.value = false
      currentVariants.value = []
      variantSizesForColor.value = []
      availableSizes.value = []
      availableHeights.value = []
      availableWeights.value = []
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
      weight: editForm.value.weight,
      color: editForm.value.color,
      image: editForm.value.image,
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

// Coupon state
const couponCode = ref('')
const appliedCoupon = ref(null)
const couponLoading = ref(false)
const couponError = ref('')
const couponSuccess = ref('')

// Apply coupon
const applyCoupon = async () => {
  if (!couponCode.value.trim()) {
    couponError.value = locale.value === 'ar' ? 'أدخل كود الخصم' : 'Enter coupon code'
    return
  }

  couponLoading.value = true
  couponError.value = ''
  couponSuccess.value = ''

  try {
    const result = await couponsApi.validate(couponCode.value, subtotal.value)

    if (result.success) {
      appliedCoupon.value = result.coupon
      couponSuccess.value = result.message
      couponError.value = ''
    } else {
      couponError.value = result.message
      appliedCoupon.value = null
    }
  } catch (err) {
    couponError.value = locale.value === 'ar' ? 'حدث خطأ' : 'An error occurred'
    appliedCoupon.value = null
  } finally {
    couponLoading.value = false
  }
}

// Remove coupon
const removeCoupon = () => {
  appliedCoupon.value = null
  couponCode.value = ''
  couponSuccess.value = ''
  couponError.value = ''
}

// Discount amount
const discountAmount = computed(() => {
  return appliedCoupon.value ? appliedCoupon.value.discount : 0
})

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
const total = computed(() => subtotal.value + shippingCost.value - discountAmount.value)

// Form validation
const isFormValid = computed(() => {
  return delivery.value.fullName &&
    delivery.value.phone
})

// Generate order image using canvas with proper Arabic/English support (multi-page)
const takeScreenshot = async () => {
  try {
    const isArabic = locale.value === 'ar'
    const width = 600
    const padding = 30
    const contentWidth = width - (padding * 2)
    const maxPageHeight = 850 // Max height per page
    const itemHeight = 80
    const itemsPerPage = 4 // Items per page after first page

    // Helper for RTL text positioning
    const getX = (leftPos, rightPos) => isArabic ? rightPos : leftPos
    const getAlign = (ltrAlign) => {
      if (ltrAlign === 'left') return isArabic ? 'right' : 'left'
      if (ltrAlign === 'right') return isArabic ? 'left' : 'right'
      return ltrAlign
    }

    // Calculate number of pages needed
    const headerHeight = 350 // Space for header, customer info on first page
    const footerHeight = 250 // Space for totals and footer
    const itemsOnFirstPage = Math.floor((maxPageHeight - headerHeight - footerHeight) / itemHeight)
    const remainingItems = Math.max(0, cartItems.value.length - itemsOnFirstPage)
    const additionalPages = Math.ceil(remainingItems / itemsPerPage)
    const totalPages = 1 + additionalPages

    // If single page is enough, use simpler calculation
    if (totalPages === 1) {
      const baseHeight = 650
      // Calculate notes height based on text length (word wrapping)
      const notesHeight = notes.value ? Math.max(60, Math.ceil(notes.value.length / 50) * 18 + 40) : 0
      // Calculate address height based on text length
      const addressHeight = delivery.value.addressDetails ? Math.max(40, Math.ceil(delivery.value.addressDetails.length / 50) * 18 + 20) : 0
      const discountHeight = appliedCoupon.value ? 25 : 0
      const totalHeight = baseHeight + (cartItems.value.length * itemHeight) + notesHeight + addressHeight + discountHeight

      const canvas = document.createElement('canvas')
      const ctx = canvas.getContext('2d')
      canvas.width = width
      canvas.height = Math.max(800, totalHeight)

      // Draw single page content (use the multi-page function with single page params)
      const getXSingle = (leftPos, rightPos) => isArabic ? rightPos : leftPos
      const getAlignSingle = (ltrAlign) => {
        if (ltrAlign === 'left') return isArabic ? 'right' : 'left'
        if (ltrAlign === 'right') return isArabic ? 'left' : 'right'
        return ltrAlign
      }
      drawSinglePage(ctx, canvas.width, canvas.height, padding, contentWidth, cartItems.value, isArabic, getXSingle, getAlignSingle)
      return canvas.toDataURL('image/png')
    }

    // Multi-page: create combined canvas
    const pageGap = 20
    const totalCanvasHeight = (maxPageHeight * totalPages) + (pageGap * (totalPages - 1))
    const canvas = document.createElement('canvas')
    const ctx = canvas.getContext('2d')
    canvas.width = width
    canvas.height = totalCanvasHeight

    // White background
    ctx.fillStyle = '#f5f5f5'
    ctx.fillRect(0, 0, canvas.width, canvas.height)

    // Draw each page
    let itemIndex = 0
    for (let page = 0; page < totalPages; page++) {
      const pageY = page * (maxPageHeight + pageGap)
      const isFirstPage = page === 0
      const isLastPage = page === totalPages - 1

      // Page background
      ctx.fillStyle = '#ffffff'
      ctx.fillRect(0, pageY, width, maxPageHeight)

      // Page border
      ctx.strokeStyle = '#e5e5e5'
      ctx.lineWidth = 2
      ctx.strokeRect(1, pageY + 1, width - 2, maxPageHeight - 2)

      // Calculate items for this page
      const itemsThisPage = isFirstPage ? itemsOnFirstPage : itemsPerPage
      const pageItems = cartItems.value.slice(itemIndex, itemIndex + itemsThisPage)
      itemIndex += pageItems.length

      // Draw page content
      drawPageContentMulti(ctx, width, padding, contentWidth, pageY, page, totalPages, pageItems, isFirstPage, isLastPage, isArabic, getX, getAlign)
    }

    return canvas.toDataURL('image/png')
  } catch (error) {
    console.error('Screenshot error:', error)
    return null
  }
}

// Draw single page content
const drawSinglePage = (ctx, width, height, padding, contentWidth, items, isArabic, getX, getAlign) => {
  // White background
  ctx.fillStyle = '#ffffff'
  ctx.fillRect(0, 0, width, height)

  // Border
  ctx.strokeStyle = '#e5e5e5'
  ctx.lineWidth = 2
  ctx.strokeRect(1, 1, width - 2, height - 2)

  // Header
  ctx.fillStyle = '#5b3a8c'
  ctx.fillRect(0, 0, width, 80)

  ctx.fillStyle = '#ffffff'
  ctx.font = 'bold 28px Arial, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText(isArabic ? 'متجر حور' : 'HOOR STORE', width / 2, 45)

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

  ctx.beginPath()
  ctx.moveTo(width / 2 - 10, y)
  ctx.lineTo(width / 2 - 3, y + 8)
  ctx.lineTo(width / 2 + 12, y - 8)
  ctx.strokeStyle = '#5b3a8c'
  ctx.lineWidth = 3
  ctx.stroke()

  y += 50

  ctx.fillStyle = '#5b3a8c'
  ctx.font = 'bold 18px Arial, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText(isArabic ? 'تم الطلب بنجاح!' : 'Order Placed Successfully!', width / 2, y)

  y += 40

  ctx.strokeStyle = '#e5e5e5'
  ctx.lineWidth = 1
  ctx.beginPath()
  ctx.moveTo(padding, y)
  ctx.lineTo(width - padding, y)
  ctx.stroke()

  y += 25

  // Customer Info
  ctx.fillStyle = '#5b3a8c'
  ctx.font = 'bold 16px Arial, sans-serif'
  ctx.textAlign = getAlign('left')
  ctx.fillText(isArabic ? 'بيانات العميل' : 'Customer Information', getX(padding, width - padding), y)

  y += 25
  ctx.fillStyle = '#374151'
  ctx.font = '14px Arial, sans-serif'

  const nameLabel = isArabic ? 'الاسم:' : 'Name:'
  ctx.font = 'bold 14px Arial, sans-serif'
  ctx.fillText(nameLabel, getX(padding, width - padding), y)
  ctx.font = '14px Arial, sans-serif'
  const nameLabelWidth = ctx.measureText(nameLabel).width + 10
  ctx.fillText(delivery.value.fullName, getX(padding + nameLabelWidth, width - padding - nameLabelWidth), y)

  y += 22

  const phoneLabel = isArabic ? 'الهاتف:' : 'Phone:'
  ctx.font = 'bold 14px Arial, sans-serif'
  ctx.fillText(phoneLabel, getX(padding, width - padding), y)
  ctx.font = '14px Arial, sans-serif'
  const phoneLabelWidth = ctx.measureText(phoneLabel).width + 10
  const phoneText = delivery.value.phone2 ? `${delivery.value.phone} - ${delivery.value.phone2}` : delivery.value.phone
  ctx.fillText(phoneText, getX(padding + phoneLabelWidth, width - padding - phoneLabelWidth), y)

  y += 22

  if (contact.value) {
    const emailLabel = isArabic ? 'البريد:' : 'Email:'
    ctx.font = 'bold 14px Arial, sans-serif'
    ctx.fillText(emailLabel, getX(padding, width - padding), y)
    ctx.font = '14px Arial, sans-serif'
    const emailLabelWidth = ctx.measureText(emailLabel).width + 10
    ctx.fillText(contact.value, getX(padding + emailLabelWidth, width - padding - emailLabelWidth), y)
    y += 22
  }

  const govLabel = isArabic ? 'المحافظة:' : 'Governorate:'
  ctx.font = 'bold 14px Arial, sans-serif'
  ctx.fillText(govLabel, getX(padding, width - padding), y)
  ctx.font = '14px Arial, sans-serif'
  const govLabelWidth = ctx.measureText(govLabel).width + 10
  ctx.fillText(t('checkout.governorates.' + delivery.value.governorate), getX(padding + govLabelWidth, width - padding - govLabelWidth), y)

  y += 22

  if (delivery.value.addressDetails) {
    const addrLabel = isArabic ? 'العنوان:' : 'Address:'
    ctx.font = 'bold 14px Arial, sans-serif'
    ctx.fillText(addrLabel, getX(padding, width - padding), y)
    y += 18
    ctx.font = '13px Arial, sans-serif'
    ctx.fillStyle = '#6b7280'
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

  if (notes.value) {
    const notesLabel = isArabic ? 'ملاحظات:' : 'Notes:'
    ctx.font = 'bold 14px Arial, sans-serif'
    ctx.fillStyle = '#5b3a8c'
    ctx.fillText(notesLabel, getX(padding, width - padding), y)
    y += 18
    ctx.font = '13px Arial, sans-serif'
    ctx.fillStyle = '#6b7280'
    // Word wrap for notes
    const maxNotesWidth = contentWidth - 20
    const notesWords = notes.value.split(' ')
    let notesLine = ''
    for (let word of notesWords) {
      const testLine = notesLine + word + ' '
      if (ctx.measureText(testLine).width > maxNotesWidth && notesLine !== '') {
        ctx.fillText(notesLine.trim(), getX(padding + 10, width - padding - 10), y)
        notesLine = word + ' '
        y += 18
      } else {
        notesLine = testLine
      }
    }
    if (notesLine.trim()) {
      ctx.fillText(notesLine.trim(), getX(padding + 10, width - padding - 10), y)
    }
    y += 25
  }

  ctx.strokeStyle = '#e5e5e5'
  ctx.beginPath()
  ctx.moveTo(padding, y)
  ctx.lineTo(width - padding, y)
  ctx.stroke()

  y += 25

  // Products
  ctx.fillStyle = '#5b3a8c'
  ctx.font = 'bold 16px Arial, sans-serif'
  ctx.textAlign = getAlign('left')
  ctx.fillText(isArabic ? 'المنتجات' : 'Order Items', getX(padding, width - padding), y)

  y += 20

  items.forEach((item, index) => {
    if (index % 2 === 0) {
      ctx.fillStyle = '#fafafa'
      ctx.fillRect(padding, y - 5, contentWidth, 70)
    }

    y += 15

    ctx.fillStyle = '#1f2937'
    ctx.font = 'bold 14px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    ctx.fillText(item.name, getX(padding + 10, width - padding - 10), y)

    ctx.textAlign = getAlign('right')
    ctx.fillStyle = '#5b3a8c'
    ctx.fillText(`${(item.price * item.quantity).toFixed(2)} ${currencySymbol.value}`, getX(width - padding - 10, padding + 10), y)

    y += 20

    ctx.fillStyle = '#6b7280'
    ctx.font = '13px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    const sizesStr = Array.isArray(item.sizes) ? item.sizes.join(', ') : item.sizes
    const details = [item.color, sizesStr, item.height, item.weight].filter(Boolean).join(' | ')
    ctx.fillText(details, getX(padding + 10, width - padding - 10), y)

    y += 18

    const qtyLabel = isArabic ? 'الكمية:' : 'Qty:'
    ctx.fillText(`${qtyLabel} ${item.quantity}`, getX(padding + 10, width - padding - 10), y)

    y += 25
  })

  y += 10

  // Totals
  const totalsHeight = appliedCoupon.value ? 125 : 100
  ctx.fillStyle = '#f9fafb'
  ctx.fillRect(padding, y, contentWidth, totalsHeight)
  ctx.strokeStyle = '#e5e5e5'
  ctx.strokeRect(padding, y, contentWidth, totalsHeight)

  y += 25

  ctx.fillStyle = '#374151'
  ctx.font = '14px Arial, sans-serif'
  ctx.textAlign = getAlign('left')
  ctx.fillText(isArabic ? 'المجموع الفرعي' : 'Subtotal', getX(padding + 15, width - padding - 15), y)
  ctx.textAlign = getAlign('right')
  ctx.fillText(`${subtotal.value.toFixed(2)} ${currencySymbol.value}`, getX(width - padding - 15, padding + 15), y)

  y += 22

  ctx.textAlign = getAlign('left')
  ctx.fillText(isArabic ? 'الشحن' : 'Shipping', getX(padding + 15, width - padding - 15), y)
  ctx.textAlign = getAlign('right')
  ctx.fillText(`${shippingCost.value.toFixed(2)} ${currencySymbol.value}`, getX(width - padding - 15, padding + 15), y)

  if (appliedCoupon.value && discountAmount.value > 0) {
    y += 22
    ctx.fillStyle = '#16a34a'
    ctx.textAlign = getAlign('left')
    ctx.fillText(isArabic ? `خصم (${appliedCoupon.value.code})` : `Discount (${appliedCoupon.value.code})`, getX(padding + 15, width - padding - 15), y)
    ctx.textAlign = getAlign('right')
    ctx.fillText(`-${discountAmount.value.toFixed(2)} ${currencySymbol.value}`, getX(width - padding - 15, padding + 15), y)
  }

  y += 28

  ctx.fillStyle = '#1f2937'
  ctx.font = 'bold 18px Arial, sans-serif'
  ctx.textAlign = getAlign('left')
  ctx.fillText(isArabic ? 'الإجمالي' : 'Total', getX(padding + 15, width - padding - 15), y)
  ctx.fillStyle = '#5b3a8c'
  ctx.textAlign = getAlign('right')
  ctx.fillText(`${total.value.toFixed(2)} ${currencySymbol.value}`, getX(width - padding - 15, padding + 15), y)

  y += 45

  // Payment badge
  ctx.fillStyle = '#ede9f3'
  const badgeWidth = 250
  const badgeX = (width - badgeWidth) / 2
  ctx.beginPath()
  ctx.roundRect(badgeX, y, badgeWidth, 35, 8)
  ctx.fill()

  ctx.fillStyle = '#5b3a8c'
  ctx.font = '14px Arial, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText(isArabic ? 'الدفع عند الاستلام' : 'Cash on Delivery', width / 2, y + 23)

  y += 55

  ctx.fillStyle = '#9ca3af'
  ctx.font = '12px Arial, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText(isArabic ? 'شكراً لتسوقكم من متجر حور' : 'Thank you for shopping at HOOR Store', width / 2, y)

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
}

// Draw content for multi-page layout
const drawPageContentMulti = (ctx, width, padding, contentWidth, pageY, pageNum, totalPages, pageItems, isFirstPage, isLastPage, isArabic, getX, getAlign) => {
  let y = pageY + 20

  // Header on every page
  ctx.fillStyle = '#5b3a8c'
  ctx.fillRect(0, pageY, width, 60)

  ctx.fillStyle = '#ffffff'
  ctx.font = 'bold 22px Arial, sans-serif'
  ctx.textAlign = 'center'
  ctx.fillText(isArabic ? 'متجر حور' : 'HOOR STORE', width / 2, pageY + 35)

  ctx.font = '12px Arial, sans-serif'
  ctx.fillText(`${isArabic ? 'رقم الطلب' : 'Order'} #${completedOrder.value?.orderNumber || ''} - ${isArabic ? 'صفحة' : 'Page'} ${pageNum + 1}/${totalPages}`, width / 2, pageY + 52)

  y = pageY + 80

  if (isFirstPage) {
    // Success checkmark
    ctx.beginPath()
    ctx.arc(width / 2, y, 20, 0, 2 * Math.PI)
    ctx.fillStyle = '#ede9f3'
    ctx.fill()
    ctx.strokeStyle = '#5b3a8c'
    ctx.lineWidth = 2
    ctx.stroke()

    ctx.beginPath()
    ctx.moveTo(width / 2 - 8, y)
    ctx.lineTo(width / 2 - 2, y + 6)
    ctx.lineTo(width / 2 + 10, y - 6)
    ctx.strokeStyle = '#5b3a8c'
    ctx.lineWidth = 2
    ctx.stroke()

    y += 35

    ctx.fillStyle = '#5b3a8c'
    ctx.font = 'bold 16px Arial, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(isArabic ? 'تم الطلب بنجاح!' : 'Order Placed Successfully!', width / 2, y)

    y += 25

    // Divider
    ctx.strokeStyle = '#e5e5e5'
    ctx.lineWidth = 1
    ctx.beginPath()
    ctx.moveTo(padding, y)
    ctx.lineTo(width - padding, y)
    ctx.stroke()

    y += 20

    // Customer Information
    ctx.fillStyle = '#5b3a8c'
    ctx.font = 'bold 14px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    ctx.fillText(isArabic ? 'بيانات العميل' : 'Customer Information', getX(padding, width - padding), y)

    y += 20
    ctx.fillStyle = '#374151'
    ctx.font = '13px Arial, sans-serif'

    // Name
    const nameLabel = isArabic ? 'الاسم:' : 'Name:'
    ctx.font = 'bold 13px Arial, sans-serif'
    ctx.fillText(nameLabel, getX(padding, width - padding), y)
    ctx.font = '13px Arial, sans-serif'
    const nameLabelWidth = ctx.measureText(nameLabel).width + 8
    ctx.fillText(delivery.value.fullName, getX(padding + nameLabelWidth, width - padding - nameLabelWidth), y)

    y += 18

    // Phone
    const phoneLabel = isArabic ? 'الهاتف:' : 'Phone:'
    ctx.font = 'bold 13px Arial, sans-serif'
    ctx.fillText(phoneLabel, getX(padding, width - padding), y)
    ctx.font = '13px Arial, sans-serif'
    const phoneLabelWidth = ctx.measureText(phoneLabel).width + 8
    const phoneText = delivery.value.phone2 ? `${delivery.value.phone} - ${delivery.value.phone2}` : delivery.value.phone
    ctx.fillText(phoneText, getX(padding + phoneLabelWidth, width - padding - phoneLabelWidth), y)

    y += 18

    // Governorate
    const govLabel = isArabic ? 'المحافظة:' : 'Governorate:'
    ctx.font = 'bold 13px Arial, sans-serif'
    ctx.fillText(govLabel, getX(padding, width - padding), y)
    ctx.font = '13px Arial, sans-serif'
    const govLabelWidth = ctx.measureText(govLabel).width + 8
    ctx.fillText(t('checkout.governorates.' + delivery.value.governorate), getX(padding + govLabelWidth, width - padding - govLabelWidth), y)

    y += 18

    // Address
    if (delivery.value.addressDetails) {
      const addrLabel = isArabic ? 'العنوان:' : 'Address:'
      ctx.font = 'bold 13px Arial, sans-serif'
      ctx.fillText(addrLabel, getX(padding, width - padding), y)
      y += 15
      ctx.font = '12px Arial, sans-serif'
      ctx.fillStyle = '#6b7280'
      const maxWidth = contentWidth - 20
      const words = delivery.value.addressDetails.split(' ')
      let line = ''
      for (let word of words) {
        const testLine = line + word + ' '
        if (ctx.measureText(testLine).width > maxWidth && line !== '') {
          ctx.fillText(line.trim(), getX(padding + 10, width - padding - 10), y)
          line = word + ' '
          y += 15
        } else {
          line = testLine
        }
      }
      if (line.trim()) {
        ctx.fillText(line.trim(), getX(padding + 10, width - padding - 10), y)
      }
      ctx.fillStyle = '#374151'
    }

    y += 20

    // Notes
    if (notes.value) {
      const notesLabel = isArabic ? 'ملاحظات:' : 'Notes:'
      ctx.font = 'bold 13px Arial, sans-serif'
      ctx.fillStyle = '#5b3a8c'
      ctx.fillText(notesLabel, getX(padding, width - padding), y)
      y += 15
      ctx.font = '12px Arial, sans-serif'
      ctx.fillStyle = '#6b7280'
      // Word wrap for notes
      const maxNotesWidth = contentWidth - 20
      const notesWords = notes.value.split(' ')
      let notesLine = ''
      for (let word of notesWords) {
        const testLine = notesLine + word + ' '
        if (ctx.measureText(testLine).width > maxNotesWidth && notesLine !== '') {
          ctx.fillText(notesLine.trim(), getX(padding + 10, width - padding - 10), y)
          notesLine = word + ' '
          y += 15
        } else {
          notesLine = testLine
        }
      }
      if (notesLine.trim()) {
        ctx.fillText(notesLine.trim(), getX(padding + 10, width - padding - 10), y)
      }
      y += 20
    }

    // Divider
    ctx.strokeStyle = '#e5e5e5'
    ctx.beginPath()
    ctx.moveTo(padding, y)
    ctx.lineTo(width - padding, y)
    ctx.stroke()

    y += 15
  }

  // Products section header
  ctx.fillStyle = '#5b3a8c'
  ctx.font = 'bold 14px Arial, sans-serif'
  ctx.textAlign = getAlign('left')
  ctx.fillText(
    isArabic ? 'المنتجات' : 'Order Items',
    getX(padding, width - padding),
    y
  )

  y += 15

  // Items
  pageItems.forEach((item, index) => {
    if (index % 2 === 0) {
      ctx.fillStyle = '#fafafa'
      ctx.fillRect(padding, y - 3, contentWidth, 65)
    }

    y += 12

    ctx.fillStyle = '#1f2937'
    ctx.font = 'bold 13px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    ctx.fillText(item.name, getX(padding + 10, width - padding - 10), y)

    ctx.textAlign = getAlign('right')
    ctx.fillStyle = '#5b3a8c'
    ctx.fillText(
      `${(item.price * item.quantity).toFixed(2)} ${currencySymbol.value}`,
      getX(width - padding - 10, padding + 10),
      y
    )

    y += 16

    ctx.fillStyle = '#6b7280'
    ctx.font = '12px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    const sizesStr = Array.isArray(item.sizes) ? item.sizes.join(', ') : item.sizes
    const details = [item.color, sizesStr, item.height, item.weight].filter(Boolean).join(' | ')
    ctx.fillText(details, getX(padding + 10, width - padding - 10), y)

    y += 14

    const qtyLabel = isArabic ? 'الكمية:' : 'Qty:'
    ctx.fillText(`${qtyLabel} ${item.quantity}`, getX(padding + 10, width - padding - 10), y)

    y += 25
  })

  // Totals on last page
  if (isLastPage) {
    y += 10

    const totalsHeight = appliedCoupon.value ? 110 : 90
    ctx.fillStyle = '#f9fafb'
    ctx.fillRect(padding, y, contentWidth, totalsHeight)
    ctx.strokeStyle = '#e5e5e5'
    ctx.strokeRect(padding, y, contentWidth, totalsHeight)

    y += 22

    ctx.fillStyle = '#374151'
    ctx.font = '13px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    ctx.fillText(isArabic ? 'المجموع الفرعي' : 'Subtotal', getX(padding + 15, width - padding - 15), y)
    ctx.textAlign = getAlign('right')
    ctx.fillText(`${subtotal.value.toFixed(2)} ${currencySymbol.value}`, getX(width - padding - 15, padding + 15), y)

    y += 18

    ctx.textAlign = getAlign('left')
    ctx.fillText(isArabic ? 'الشحن' : 'Shipping', getX(padding + 15, width - padding - 15), y)
    ctx.textAlign = getAlign('right')
    ctx.fillText(`${shippingCost.value.toFixed(2)} ${currencySymbol.value}`, getX(width - padding - 15, padding + 15), y)

    if (appliedCoupon.value && discountAmount.value > 0) {
      y += 18
      ctx.fillStyle = '#16a34a'
      ctx.textAlign = getAlign('left')
      ctx.fillText(isArabic ? `خصم (${appliedCoupon.value.code})` : `Discount (${appliedCoupon.value.code})`, getX(padding + 15, width - padding - 15), y)
      ctx.textAlign = getAlign('right')
      ctx.fillText(`-${discountAmount.value.toFixed(2)} ${currencySymbol.value}`, getX(width - padding - 15, padding + 15), y)
    }

    y += 22

    ctx.fillStyle = '#1f2937'
    ctx.font = 'bold 16px Arial, sans-serif'
    ctx.textAlign = getAlign('left')
    ctx.fillText(isArabic ? 'الإجمالي' : 'Total', getX(padding + 15, width - padding - 15), y)
    ctx.fillStyle = '#5b3a8c'
    ctx.textAlign = getAlign('right')
    ctx.fillText(`${total.value.toFixed(2)} ${currencySymbol.value}`, getX(width - padding - 15, padding + 15), y)

    y += 35

    // Payment badge
    ctx.fillStyle = '#ede9f3'
    const badgeWidth = 200
    const badgeX = (width - badgeWidth) / 2
    ctx.beginPath()
    ctx.roundRect(badgeX, y, badgeWidth, 30, 6)
    ctx.fill()

    ctx.fillStyle = '#5b3a8c'
    ctx.font = '13px Arial, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(isArabic ? 'الدفع عند الاستلام' : 'Cash on Delivery', width / 2, y + 20)

    y += 45

    // Footer
    ctx.fillStyle = '#9ca3af'
    ctx.font = '11px Arial, sans-serif'
    ctx.textAlign = 'center'
    ctx.fillText(isArabic ? 'شكراً لتسوقكم من متجر حور' : 'Thank you for shopping at HOOR Store', width / 2, y)

    y += 15
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

  // Validate quantity matches sizes count (if multiple sizes selected, quantity must be >= sizes count)
  const invalidItems = cartItems.value.filter(item => {
    const sizesCount = Array.isArray(item.sizes) ? item.sizes.length : (item.sizes ? 1 : 0)
    // If multiple sizes selected, quantity must be at least equal to number of sizes
    return sizesCount > 1 && item.quantity < sizesCount
  })

  if (invalidItems.length > 0) {
    const item = invalidItems[0]
    const sizesCount = Array.isArray(item.sizes) ? item.sizes.length : 1

    errorMessage.value = locale.value === 'ar'
      ? `"${item.name}": اخترت ${sizesCount} مقاسات لكن الكمية ${item.quantity} فقط. الكمية لازم تكون ${sizesCount} على الأقل`
      : `"${item.name}": You selected ${sizesCount} sizes but quantity is only ${item.quantity}. Quantity must be at least ${sizesCount}`
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
      couponCode: appliedCoupon.value?.code || null,
      discountAmount: discountAmount.value,
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
          <video v-if="isVideo(editForm.image || editingItem.image)" :src="editForm.image || editingItem.image" class="w-20 h-24 object-cover rounded" muted playsinline />
          <img v-else :src="editForm.image || editingItem.image" :alt="editingItem.name" class="w-20 h-24 object-cover rounded" @error="(e) => { e.target.src = '/logo.png'; e.target.className = 'w-20 h-24 object-contain p-2 opacity-60 rounded' }" />
          <div>
            <h4 class="font-medium text-gray-900">{{ editingItem.name }}</h4>
            <p class="text-sm text-gray-500">{{ editForm.color }}</p>
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
          <!-- Color -->
          <div v-if="availableColors.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('product.color') }}</label>
            <select
              :value="editForm.color"
              @change="onColorChange($event.target.value)"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-primary"
            >
              <option v-for="color in availableColors" :key="getColorName(color)" :value="getColorName(color)">{{ getColorName(color) }}</option>
            </select>
          </div>

          <!-- Size: LEGACY (Multiple Selection) -->
          <div v-if="!productHasVariants && availableSizes.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('product.size') }}</label>
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

          <!-- Size: VARIANT (Single Selection with Stock) -->
          <div v-if="productHasVariants && variantSizesForColor.length > 0">
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('product.size') }}</label>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="sizeObj in variantSizesForColor"
                :key="sizeObj.size"
                type="button"
                @click="sizeObj.quantity > 0 ? (editForm.sizes = [sizeObj.size]) : null"
                :disabled="sizeObj.quantity === 0"
                class="min-w-[40px] px-3 py-2 text-sm font-medium border-2 rounded-lg transition-all duration-200 text-center"
                :class="[
                  sizeObj.quantity === 0
                    ? 'bg-gray-100 text-gray-400 border-gray-200 cursor-not-allowed line-through'
                    : editForm.sizes.includes(sizeObj.size)
                      ? 'bg-primary text-white border-primary'
                      : 'bg-white text-gray-700 border-gray-200 hover:border-gray-400'
                ]"
              >
                <span>{{ sizeObj.size }}</span>
                <span v-if="sizeObj.quantity === 0" class="block text-[10px] text-red-500" style="text-decoration: none;">نفذ</span>
                <span v-else class="block text-[10px]" :class="editForm.sizes.includes(sizeObj.size) ? 'text-white/70' : 'text-gray-400'">{{ sizeObj.quantity }}</span>
              </button>
            </div>
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

          <!-- Coupon Section -->
          <section>
            <h2 class="text-xl font-medium text-gray-900 mb-4">{{ $t('checkout.coupon') }}</h2>
            <div class="space-y-3">
              <!-- Applied Coupon -->
              <div v-if="appliedCoupon" class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <div>
                    <p class="font-medium text-green-800">{{ appliedCoupon.code }}</p>
                    <p class="text-sm text-green-600">
                      {{ appliedCoupon.type === 'percentage' ? `${appliedCoupon.value}%` : `${appliedCoupon.value} ${$t('common.egp')}` }}
                      {{ $t('checkout.discount') }}
                    </p>
                  </div>
                </div>
                <button
                  @click="removeCoupon"
                  class="text-red-500 hover:text-red-700 p-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Coupon Input -->
              <div v-else class="flex gap-2">
                <input
                  v-model="couponCode"
                  type="text"
                  :placeholder="$t('checkout.couponPlaceholder')"
                  class="flex-1 px-4 py-3 border border-gray-300 rounded focus:outline-none focus:border-primary uppercase"
                  @keyup.enter="applyCoupon"
                />
                <button
                  @click="applyCoupon"
                  :disabled="couponLoading"
                  class="px-6 py-3 bg-primary text-white rounded font-medium hover:bg-primary/90 transition-colors disabled:opacity-50"
                >
                  <span v-if="couponLoading" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                  </span>
                  <span v-else>{{ $t('checkout.apply') }}</span>
                </button>
              </div>

              <!-- Coupon Error -->
              <p v-if="couponError" class="text-sm text-red-500 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ couponError }}
              </p>

              <!-- Coupon Success -->
              <p v-if="couponSuccess && appliedCoupon" class="text-sm text-green-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ couponSuccess }}
              </p>
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
                    <video v-if="isVideo(item.image)" :src="item.image" class="w-full h-full object-cover" muted playsinline />
                    <img v-else :src="item.image" :alt="item.name" class="w-full h-full object-cover" @error="(e) => { e.target.src = '/logo.png'; e.target.className = 'w-full h-full object-contain p-2 opacity-60' }" />
                  </div>
                  <span class="absolute -top-2 -right-2 w-5 h-5 bg-primary text-white text-xs rounded-full flex items-center justify-center">
                    {{ item.quantity }}
                  </span>
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</h3>
                  <p class="text-xs text-gray-500">{{ [item.color, Array.isArray(item.sizes) ? item.sizes.join(', ') : item.sizes, item.height, item.weight].filter(Boolean).join(' / ') }}</p>
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
              <!-- Discount Row -->
              <div v-if="appliedCoupon" class="flex justify-between text-sm">
                <span class="text-green-600 flex items-center gap-1">
                  {{ $t('checkout.discount') }} ({{ appliedCoupon.code }})
                </span>
                <span class="text-green-600">-{{ discountAmount.toFixed(2) }} {{ $t('common.egp') }}</span>
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
