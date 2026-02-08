<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { productsApi, uploadApi } from '../api/products'
import { ordersApi, couponsApi, migrateApi } from '../api/cart'

const route = useRoute()
const router = useRouter()

// Helper to check if a URL is a video
const videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'webm']
const isVideo = (url) => {
  if (!url) return false
  const ext = url.split('.').pop()?.toLowerCase().split('?')[0]
  return videoExtensions.includes(ext)
}

// Authentication check
const isAuthenticated = ref(false)
const ADMIN_USER = 'ahmed'
const ADMIN_PASS = 'Ahmed123@'

const checkAuth = () => {
  const user = route.query.user
  const pass = route.query.pass

  if (user === ADMIN_USER && pass === ADMIN_PASS) {
    isAuthenticated.value = true
  } else {
    router.replace('/')
  }
}

// Active tab
const activeTab = ref('products')

// Products list
const products = ref([])
const loading = ref(true)
const error = ref(null)

// Orders list
const orders = ref([])
const ordersLoading = ref(false)
const ordersError = ref(null)
const selectedOrder = ref(null)
const selectedOrders = ref([])

// Order filters
const orderFilters = ref({
  status: '',
  search: '',
  dateFrom: '',
  dateTo: ''
})

// Filtered orders computed
const filteredOrders = computed(() => {
  let result = orders.value

  // Filter by status
  if (orderFilters.value.status) {
    result = result.filter(o => o.status === orderFilters.value.status)
  }

  // Filter by search (name or phone)
  if (orderFilters.value.search) {
    const search = orderFilters.value.search.toLowerCase()
    result = result.filter(o =>
      o.delivery?.fullName?.toLowerCase().includes(search) ||
      o.delivery?.phone?.includes(search) ||
      o.orderNumber?.toLowerCase().includes(search)
    )
  }

  // Filter by date range
  if (orderFilters.value.dateFrom) {
    const fromDate = new Date(orderFilters.value.dateFrom)
    result = result.filter(o => new Date(o.createdAt) >= fromDate)
  }
  if (orderFilters.value.dateTo) {
    const toDate = new Date(orderFilters.value.dateTo)
    toDate.setHours(23, 59, 59)
    result = result.filter(o => new Date(o.createdAt) <= toDate)
  }

  return result
})

// Reset filters
const resetOrderFilters = () => {
  orderFilters.value = { status: '', search: '', dateFrom: '', dateTo: '' }
}

// Coupons state
const coupons = ref([])
const couponsLoading = ref(false)
const couponsError = ref(null)
const showCouponForm = ref(false)
const editingCoupon = ref(null)
const savingCoupon = ref(false)

// Migrations state
const migrations = ref([])
const migrationsRaw = ref('')
const migrationsLoading = ref(false)
const migrationsError = ref(null)
const migrationRunning = ref(false)
const migrationReport = ref(null)

// Cleanup state
const cleanupRunning = ref(false)
const cleanupReport = ref(null)

// Run cleanup of unused files
const runCleanup = async () => {
  if (!confirm('هل أنت متأكد من حذف جميع الصور والفيديوهات غير المستخدمة؟ هذا الإجراء لا يمكن التراجع عنه.')) return

  try {
    cleanupRunning.value = true
    cleanupReport.value = null
    const data = await uploadApi.cleanupUnused()
    cleanupReport.value = data
  } catch (err) {
    cleanupReport.value = { error: 'فشل في الاتصال بالسيرفر' }
    console.error(err)
  } finally {
    cleanupRunning.value = false
  }
}

// Fetch migration status
const fetchMigrations = async () => {
  try {
    migrationsLoading.value = true
    migrationsError.value = null
    const data = await migrateApi.getStatus()
    if (data.error) {
      migrationsError.value = data.error
    } else {
      migrations.value = data.migrations || []
      migrationsRaw.value = data.raw || ''
    }
  } catch (err) {
    migrationsError.value = 'فشل في تحميل حالة التحديثات'
    console.error(err)
  } finally {
    migrationsLoading.value = false
  }
}

// Run migrations
const runMigrations = async () => {
  if (!confirm('هل أنت متأكد من تشغيل التحديثات على قاعدة البيانات؟')) return

  try {
    migrationRunning.value = true
    migrationReport.value = null
    const data = await migrateApi.run()
    migrationReport.value = data
    // Refresh status after running
    await fetchMigrations()
  } catch (err) {
    migrationReport.value = { success: false, output: 'فشل في الاتصال بالسيرفر' }
    console.error(err)
  } finally {
    migrationRunning.value = false
  }
}

// Coupon form
const couponForm = ref({
  code: '',
  type: 'percentage',
  value: '',
  min_order: '',
  max_discount: '',
  usage_limit: '',
  start_date: '',
  end_date: '',
  is_active: true
})

// Fetch coupons
const fetchCoupons = async () => {
  try {
    couponsLoading.value = true
    couponsError.value = null
    coupons.value = await couponsApi.getAll()
  } catch (err) {
    couponsError.value = 'فشل في تحميل الكوبونات'
    console.error(err)
  } finally {
    couponsLoading.value = false
  }
}

// Open coupon form for new
const openNewCouponForm = () => {
  editingCoupon.value = null
  couponForm.value = {
    code: '',
    type: 'percentage',
    value: '',
    min_order: '',
    max_discount: '',
    usage_limit: '',
    start_date: '',
    end_date: '',
    is_active: true
  }
  showCouponForm.value = true
}

// Open coupon form for edit
const openEditCouponForm = (coupon) => {
  editingCoupon.value = coupon
  couponForm.value = {
    code: coupon.code,
    type: coupon.type,
    value: coupon.value,
    min_order: coupon.min_order || '',
    max_discount: coupon.max_discount || '',
    usage_limit: coupon.usage_limit || '',
    start_date: coupon.start_date ? coupon.start_date.split('T')[0] : '',
    end_date: coupon.end_date ? coupon.end_date.split('T')[0] : '',
    is_active: coupon.is_active
  }
  showCouponForm.value = true
}

// Close coupon form
const closeCouponForm = () => {
  showCouponForm.value = false
  editingCoupon.value = null
}

// Save coupon
const saveCoupon = async () => {
  try {
    savingCoupon.value = true
    const data = {
      code: couponForm.value.code.toUpperCase(),
      type: couponForm.value.type,
      value: parseFloat(couponForm.value.value),
      min_order: couponForm.value.min_order ? parseFloat(couponForm.value.min_order) : null,
      max_discount: couponForm.value.max_discount ? parseFloat(couponForm.value.max_discount) : null,
      usage_limit: couponForm.value.usage_limit ? parseInt(couponForm.value.usage_limit) : null,
      start_date: couponForm.value.start_date || null,
      end_date: couponForm.value.end_date || null,
      is_active: couponForm.value.is_active
    }

    if (editingCoupon.value) {
      await couponsApi.update(editingCoupon.value.id, data)
    } else {
      await couponsApi.create(data)
    }

    await fetchCoupons()
    closeCouponForm()
  } catch (err) {
    console.error('Failed to save coupon:', err)
    alert('فشل في حفظ الكوبون')
  } finally {
    savingCoupon.value = false
  }
}

// Delete coupon
const deleteCoupon = async (coupon) => {
  if (!confirm(`هل أنت متأكد من حذف الكوبون "${coupon.code}"؟`)) {
    return
  }
  try {
    await couponsApi.delete(coupon.id)
    coupons.value = coupons.value.filter(c => c.id !== coupon.id)
  } catch (err) {
    console.error('Failed to delete coupon:', err)
    alert('فشل في حذف الكوبون')
  }
}

// Toggle coupon active status
const toggleCouponStatus = async (coupon) => {
  try {
    await couponsApi.update(coupon.id, { is_active: !coupon.is_active })
    coupon.is_active = !coupon.is_active
  } catch (err) {
    console.error('Failed to toggle coupon status:', err)
  }
}

// Select all checkbox state
const isAllSelected = computed(() => {
  return orders.value.length > 0 && selectedOrders.value.length === orders.value.length
})

// Toggle select all
const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedOrders.value = []
  } else {
    selectedOrders.value = orders.value.map(o => o.id)
  }
}

// Toggle single order selection
const toggleOrderSelection = (orderId) => {
  const index = selectedOrders.value.indexOf(orderId)
  if (index > -1) {
    selectedOrders.value.splice(index, 1)
  } else {
    selectedOrders.value.push(orderId)
  }
}

// Delete selected orders
const deleteSelectedOrders = async () => {
  if (selectedOrders.value.length === 0) return

  if (!confirm(`هل أنت متأكد من حذف ${selectedOrders.value.length} طلب؟`)) {
    return
  }

  try {
    for (const orderId of selectedOrders.value) {
      await ordersApi.delete(orderId)
    }
    orders.value = orders.value.filter(o => !selectedOrders.value.includes(o.id))
    selectedOrders.value = []
  } catch (err) {
    console.error('Failed to delete orders:', err)
    alert('فشل في حذف بعض الطلبات')
  }
}

// Update selected orders status
const updateSelectedOrdersStatus = async (newStatus) => {
  if (selectedOrders.value.length === 0) return

  try {
    for (const orderId of selectedOrders.value) {
      await ordersApi.update(orderId, { status: newStatus })
      const order = orders.value.find(o => o.id === orderId)
      if (order) order.status = newStatus
    }
  } catch (err) {
    console.error('Failed to update orders:', err)
    alert('فشل في تحديث بعض الطلبات')
  }
}

// Order statuses
const orderStatuses = [
  { value: 'pending', label: 'قيد الانتظار', color: 'bg-yellow-100 text-yellow-800' },
  { value: 'confirmed', label: 'مؤكد', color: 'bg-blue-100 text-blue-800' },
  { value: 'processing', label: 'جاري التجهيز', color: 'bg-purple-100 text-purple-800' },
  { value: 'shipped', label: 'تم الشحن', color: 'bg-indigo-100 text-indigo-800' },
  { value: 'delivered', label: 'تم التوصيل', color: 'bg-green-100 text-green-800' },
  { value: 'cancelled', label: 'ملغي', color: 'bg-red-100 text-red-800' }
]

// Get status info
const getStatusInfo = (status) => {
  return orderStatuses.find(s => s.value === status) || { label: status, color: 'bg-gray-100 text-gray-800' }
}

// Fetch orders
const fetchOrders = async () => {
  try {
    ordersLoading.value = true
    ordersError.value = null
    orders.value = await ordersApi.getAll()
  } catch (err) {
    ordersError.value = 'فشل في تحميل الطلبات'
    console.error(err)
  } finally {
    ordersLoading.value = false
  }
}

// Update order status
const updateOrderStatus = async (order, newStatus) => {
  try {
    await ordersApi.update(order.id, { status: newStatus })
    order.status = newStatus
  } catch (err) {
    console.error('Failed to update order:', err)
    alert('فشل في تحديث حالة الطلب')
  }
}

// Delete order
const deleteOrder = async (order) => {
  if (!confirm(`هل أنت متأكد من حذف الطلب #${order.orderNumber}؟`)) {
    return
  }
  try {
    await ordersApi.delete(order.id)
    orders.value = orders.value.filter(o => o.id !== order.id)
  } catch (err) {
    console.error('Failed to delete order:', err)
    alert('فشل في حذف الطلب')
  }
}

// View order details
const viewOrder = (order) => {
  selectedOrder.value = order
}

// Close order details
const closeOrderDetails = () => {
  selectedOrder.value = null
}

// Format date
const formatDate = (dateString) => {
  const date = new Date(dateString)
  return date.toLocaleDateString('ar-EG', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Switch tab
const switchTab = (tab) => {
  activeTab.value = tab
  if (tab === 'orders' && orders.value.length === 0) {
    fetchOrders()
  }
  if (tab === 'coupons' && coupons.value.length === 0) {
    fetchCoupons()
  }
  if (tab === 'migrations') {
    fetchMigrations()
  }
}

// Form state
const showForm = ref(false)
const editingProduct = ref(null)
const saving = ref(false)
const uploading = ref(false)
const imagePreview = ref(null)
const imageFile = ref(null)

// Album images state
const albumImages = ref([])
const albumPreviews = ref([])

// Size input
const newSize = ref('')
const newHeight = ref('')
const newWeight = ref('')

// Color input with image support
const newColorName = ref('')
const newColorImageFile = ref(null)
const newColorImagePreview = ref(null)

// Form data
const form = ref({
  code: '',
  name: '',
  price: '',
  salePrice: '',
  category: '',
  image: '',
  images: [],
  sizes: [],
  colors: [],
  heights: [],
  weights: [],
  description: '',
  inStock: true
})

// Categories with Arabic names
const categories = [
  // { value: 'basic-tops', label: 'أعلي مبيعات' },
  { value: 'children', label: 'الاطفال' },
  { value: 'men', label: 'رجالي' },
  { value: 'Woman', label: 'حريمي' },
]

// Get category label
const getCategoryLabel = (value) => {
  const cat = categories.find(c => c.value === value)
  return cat ? cat.label : value
}

// Fetch all products
const fetchProducts = async () => {
  try {
    loading.value = true
    error.value = null
    products.value = await productsApi.getAll()
  } catch (err) {
    error.value = 'فشل في تحميل المنتجات'
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  checkAuth()
  if (isAuthenticated.value) {
    fetchProducts()
  }
})

// Handle file selection
const handleFileSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    imageFile.value = file
    imagePreview.value = URL.createObjectURL(file)
  }
}

// Handle album file selection
const handleAlbumFileSelect = (event) => {
  const files = Array.from(event.target.files)
  files.forEach(file => {
    albumImages.value.push(file)
    albumPreviews.value.push({ url: URL.createObjectURL(file), name: file.name })
  })
}

// Remove album image
const removeAlbumImage = (index) => {
  albumImages.value.splice(index, 1)
  albumPreviews.value.splice(index, 1)
}

// Remove existing album image
const removeExistingAlbumImage = (index) => {
  form.value.images.splice(index, 1)
}

// Add new sizes (comma separated)
const addSizes = () => {
  const sizes = newSize.value.split(',').map(s => s.trim()).filter(s => s)
  sizes.forEach(size => {
    if (!form.value.sizes.includes(size)) {
      form.value.sizes.push(size)
    }
  })
  newSize.value = ''
}

// Remove size
const removeSize = (index) => {
  form.value.sizes.splice(index, 1)
}

// Handle color image file selection
const handleColorImageSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    newColorImageFile.value = file
    newColorImagePreview.value = URL.createObjectURL(file)
  }
}

// Add new color with image
const addColor = async () => {
  if (!newColorName.value.trim()) return

  const colorName = newColorName.value.trim()

  // Check if color already exists
  const exists = form.value.colors.some(c =>
    (typeof c === 'object' ? c.name : c) === colorName
  )
  if (exists) {
    alert('هذا اللون موجود بالفعل')
    return
  }

  let colorImage = ''

  // Upload image if selected
  if (newColorImageFile.value) {
    try {
      const uploadResult = await uploadApi.uploadImage(newColorImageFile.value)
      if (uploadResult.error) {
        throw new Error(uploadResult.error)
      }
      colorImage = uploadResult.url
    } catch (err) {
      console.error('Failed to upload color image:', err)
      alert('فشل في رفع صورة اللون')
      return
    }
  }

  // Add color object
  form.value.colors.push({
    name: colorName,
    image: colorImage
  })

  // Reset inputs
  newColorName.value = ''
  newColorImageFile.value = null
  newColorImagePreview.value = null
}

// Remove color
const removeColor = (index) => {
  form.value.colors.splice(index, 1)
}

// Get color name helper
const getColorName = (color) => {
  return typeof color === 'object' ? color.name : color
}

// Get color image helper
const getColorImage = (color) => {
  return typeof color === 'object' ? color.image : null
}

// Add new heights (comma separated)
const addHeights = () => {
  const heights = newHeight.value.split(',').map(s => s.trim()).filter(s => s)
  heights.forEach(height => {
    if (!form.value.heights.includes(height)) {
      form.value.heights.push(height)
    }
  })
  newHeight.value = ''
}

// Remove height
const removeHeight = (index) => {
  form.value.heights.splice(index, 1)
}

// Add new weights (comma separated)
const addWeights = () => {
  const weights = newWeight.value.split(',').map(s => s.trim()).filter(s => s)
  weights.forEach(weight => {
    if (!form.value.weights.includes(weight)) {
      form.value.weights.push(weight)
    }
  })
  newWeight.value = ''
}

// Remove weight
const removeWeight = (index) => {
  form.value.weights.splice(index, 1)
}

// Reset form
const resetForm = () => {
  form.value = {
    code: '',
    name: '',
    price: '',
    salePrice: '',
    category: '',
    image: '',
    images: [],
    sizes: [],
    colors: [],
    heights: [],
    weights: [],
    description: '',
    inStock: true
  }
  editingProduct.value = null
  imageFile.value = null
  imagePreview.value = null
  albumImages.value = []
  albumPreviews.value = []
  newSize.value = ''
  newColorName.value = ''
  newColorImageFile.value = null
  newColorImagePreview.value = null
  newHeight.value = ''
  newWeight.value = ''
}

// Open form for new product
const openNewForm = () => {
  resetForm()
  showForm.value = true
}

// Open form for editing
const openEditForm = (product) => {
  editingProduct.value = product

  // Convert old string colors to new object format if needed
  const normalizedColors = (product.colors || []).map(color => {
    if (typeof color === 'string') {
      return { name: color, image: '' }
    }
    return color
  })

  form.value = {
    code: product.code || '',
    name: product.name || '',
    price: product.price || '',
    salePrice: product.salePrice || '',
    category: product.category || '',
    image: product.image || '',
    images: product.images ? [...product.images] : [],
    sizes: product.sizes ? [...product.sizes] : [],
    colors: normalizedColors,
    heights: product.heights ? [...product.heights] : [],
    weights: product.weights ? [...product.weights] : [],
    description: product.description || '',
    inStock: product.inStock !== false
  }
  imageFile.value = null
  imagePreview.value = product.image || null
  albumImages.value = []
  albumPreviews.value = []
  newSize.value = ''
  newColorName.value = ''
  newColorImageFile.value = null
  newColorImagePreview.value = null
  newHeight.value = ''
  newWeight.value = ''
  showForm.value = true
}

// Close form
const closeForm = () => {
  showForm.value = false
  resetForm()
}

// Save product (create or update)
const saveProduct = async () => {
  try {
    saving.value = true
    uploading.value = true
    error.value = null

    let imageUrl = form.value.image

    // Upload image if new file selected
    if (imageFile.value) {
      const uploadResult = await uploadApi.uploadImage(imageFile.value)
      if (uploadResult.error) {
        throw new Error(uploadResult.error)
      }
      imageUrl = uploadResult.url
    }

    // Upload album images
    const uploadedAlbumImages = [...form.value.images]
    for (const file of albumImages.value) {
      const uploadResult = await uploadApi.uploadImage(file)
      if (uploadResult.error) {
        throw new Error(uploadResult.error)
      }
      uploadedAlbumImages.push(uploadResult.url)
    }

    const productData = {
      code: form.value.code,
      name: form.value.name,
      price: parseFloat(form.value.price) || 0,
      salePrice: form.value.salePrice ? parseFloat(form.value.salePrice) : null,
      category: form.value.category,
      image: imageUrl,
      images: uploadedAlbumImages,
      sizes: form.value.sizes,
      colors: form.value.colors,
      heights: form.value.heights,
      weights: form.value.weights,
      description: form.value.description,
      inStock: form.value.inStock
    }

    if (editingProduct.value) {
      await productsApi.update(editingProduct.value.id, productData)
    } else {
      await productsApi.create(productData)
    }

    await fetchProducts()
    closeForm()
  } catch (err) {
    error.value = 'فشل في حفظ المنتج'
    console.error(err)
  } finally {
    saving.value = false
    uploading.value = false
  }
}

// Delete product
const deleteProduct = async (product) => {
  if (!confirm(`هل أنت متأكد من حذف "${product.name}"؟`)) {
    return
  }

  try {
    await productsApi.delete(product.id)
    await fetchProducts()
  } catch (err) {
    error.value = 'فشل في حذف المنتج'
    console.error(err)
  }
}

// Form title
const formTitle = computed(() =>
  editingProduct.value ? 'تعديل المنتج' : 'إضافة منتج جديد'
)
</script>

<template>
  <div v-if="isAuthenticated" class="min-h-screen bg-gray-100" dir="rtl">
    <!-- Header -->
    <header class="admin-header">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-white">لوحة التحكم</h1>
        <p class="text-purple-200 mt-1 text-sm sm:text-base">إدارة المتجر</p>
      </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="bg-white shadow-sm sticky top-0 z-30">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <nav class="flex overflow-x-auto scrollbar-hide -mb-px gap-1 sm:gap-2">
          <button
            @click="switchTab('products')"
            :class="[
              'py-3 px-4 border-b-3 font-medium text-sm whitespace-nowrap transition-all',
              activeTab === 'products'
                ? 'border-purple-600 text-purple-700 bg-purple-50/50'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]"
          >
            المنتجات ({{ products.length }})
          </button>
          <button
            @click="switchTab('orders')"
            :class="[
              'py-3 px-4 border-b-3 font-medium text-sm whitespace-nowrap transition-all',
              activeTab === 'orders'
                ? 'border-purple-600 text-purple-700 bg-purple-50/50'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]"
          >
            الطلبات ({{ orders.length }})
          </button>
          <button
            @click="switchTab('coupons')"
            :class="[
              'py-3 px-4 border-b-3 font-medium text-sm whitespace-nowrap transition-all',
              activeTab === 'coupons'
                ? 'border-purple-600 text-purple-700 bg-purple-50/50'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]"
          >
            الكوبونات ({{ coupons.length }})
          </button>
          <button
            @click="switchTab('migrations')"
            :class="[
              'py-3 px-4 border-b-3 font-medium text-sm whitespace-nowrap transition-all',
              activeTab === 'migrations'
                ? 'border-purple-600 text-purple-700 bg-purple-50/50'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'
            ]"
          >
            تحديثات قاعدة البيانات
          </button>
        </nav>
      </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-8">
      <!-- ==================== PRODUCTS TAB ==================== -->
      <div v-if="activeTab === 'products'">
        <!-- Error Message -->
        <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
          {{ error }}
        </div>

        <!-- Actions Bar -->
        <div class="mb-5 flex justify-between items-center">
          <h2 class="text-lg sm:text-xl font-bold text-gray-800">
            المنتجات <span class="text-gray-400 font-normal">({{ products.length }})</span>
          </h2>
          <button
            @click="openNewForm"
            class="admin-btn-primary text-sm sm:text-base"
          >
            + إضافة منتج
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-16">
          <div class="inline-block w-8 h-8 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mb-3"></div>
          <p class="text-gray-500 text-sm">جاري تحميل المنتجات...</p>
        </div>

        <!-- Products: Desktop Table -->
        <div v-else class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">المنتج</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الكود</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">القسم</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">السعر</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الحالة</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">الإجراءات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <video
                      v-if="isVideo(product.image)"
                      :src="product.image"
                      class="h-12 w-12 object-cover rounded-lg border border-gray-200"
                      muted
                      playsinline
                    />
                    <img
                      v-else
                      :src="product.image"
                      :alt="product.name"
                      class="h-12 w-12 object-cover rounded-lg border border-gray-200"
                    />
                    <div>
                      <div class="text-sm font-semibold text-gray-900">{{ product.name }}</div>
                      <div class="text-xs text-gray-400">#{{ product.id }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm font-mono text-gray-500 bg-gray-50 px-2 py-0.5 rounded">{{ product.code || '-' }}</span>
                </td>
                <td class="px-5 py-4">
                  <span class="px-2.5 py-1 text-xs font-medium bg-purple-50 text-purple-700 rounded-full">
                    {{ getCategoryLabel(product.category) }}
                  </span>
                </td>
                <td class="px-5 py-4">
                  <div class="text-sm font-semibold text-gray-900">{{ Number(product.price || 0).toFixed(2) }} ج.م</div>
                  <div v-if="product.salePrice" class="text-xs text-green-600 font-medium">
                    تخفيض: {{ Number(product.salePrice || 0).toFixed(2) }} ج.م
                  </div>
                </td>
                <td class="px-5 py-4">
                  <span
                    class="px-2.5 py-1 text-xs font-medium rounded-full"
                    :class="product.inStock !== false
                      ? 'bg-green-50 text-green-700'
                      : 'bg-red-50 text-red-700'"
                  >
                    {{ product.inStock !== false ? 'متوفر' : 'غير متوفر' }}
                  </span>
                </td>
                <td class="px-5 py-4 text-left">
                  <div class="flex items-center gap-2">
                    <button @click="openEditForm(product)" class="admin-action-btn text-indigo-600 hover:bg-indigo-50">تعديل</button>
                    <button @click="deleteProduct(product)" class="admin-action-btn text-red-600 hover:bg-red-50">حذف</button>
                  </div>
                </td>
              </tr>
              <tr v-if="products.length === 0">
                <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                  لا توجد منتجات. اضغط "إضافة منتج" لإنشاء منتج جديد.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Products: Mobile Cards -->
        <div v-if="!loading" class="md:hidden space-y-3">
          <div
            v-for="product in products"
            :key="'m-' + product.id"
            class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
          >
            <div class="flex items-center gap-3 p-4">
              <video
                v-if="isVideo(product.image)"
                :src="product.image"
                class="h-16 w-16 object-cover rounded-lg border border-gray-200 flex-shrink-0"
                muted
                playsinline
              />
              <img
                v-else
                :src="product.image"
                :alt="product.name"
                class="h-16 w-16 object-cover rounded-lg border border-gray-200 flex-shrink-0"
              />
              <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                  <h3 class="text-sm font-bold text-gray-900 truncate">{{ product.name }}</h3>
                  <span
                    class="px-2 py-0.5 text-[10px] font-medium rounded-full flex-shrink-0"
                    :class="product.inStock !== false
                      ? 'bg-green-50 text-green-700'
                      : 'bg-red-50 text-red-700'"
                  >
                    {{ product.inStock !== false ? 'متوفر' : 'غير متوفر' }}
                  </span>
                </div>
                <div class="flex items-center gap-2 mt-1">
                  <span v-if="product.code" class="text-xs font-mono text-gray-400">{{ product.code }}</span>
                  <span class="text-xs text-gray-300">|</span>
                  <span class="text-xs text-purple-600 font-medium">{{ getCategoryLabel(product.category) }}</span>
                </div>
                <div class="flex items-center gap-2 mt-1.5">
                  <span class="text-sm font-bold text-gray-900">{{ Number(product.price || 0).toFixed(2) }} ج.م</span>
                  <span v-if="product.salePrice" class="text-xs text-green-600 line-through">{{ Number(product.salePrice || 0).toFixed(2) }} ج.م</span>
                </div>
              </div>
            </div>
            <div class="flex border-t border-gray-100">
              <button
                @click="openEditForm(product)"
                class="flex-1 py-2.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 transition-colors text-center border-l border-gray-100"
              >
                تعديل
              </button>
              <button
                @click="deleteProduct(product)"
                class="flex-1 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors text-center"
              >
                حذف
              </button>
            </div>
          </div>
          <div v-if="products.length === 0" class="text-center py-16 text-gray-400 text-sm">
            لا توجد منتجات. اضغط "إضافة منتج" لإنشاء منتج جديد.
          </div>
        </div>
      </div>

      <!-- ==================== ORDERS TAB ==================== -->
      <div v-if="activeTab === 'orders'">
        <!-- Error Message -->
        <div v-if="ordersError" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
          {{ ordersError }}
        </div>

        <!-- Actions Bar -->
        <div class="mb-5 flex justify-between items-center">
          <h2 class="text-lg sm:text-xl font-bold text-gray-800">
            الطلبات <span class="text-gray-400 font-normal">({{ filteredOrders.length }} من {{ orders.length }})</span>
          </h2>
          <button
            @click="fetchOrders"
            class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
          >
            تحديث
          </button>
        </div>

        <!-- Filters Bar -->
        <div class="mb-5 bg-white rounded-xl border border-gray-200 shadow-sm p-4">
          <div class="grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
            <div class="col-span-2 md:col-span-1">
              <label class="block text-xs font-medium text-gray-500 mb-1">بحث</label>
              <input
                v-model="orderFilters.search"
                type="text"
                placeholder="اسم، هاتف، رقم طلب..."
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">الحالة</label>
              <select
                v-model="orderFilters.status"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              >
                <option value="">الكل</option>
                <option v-for="status in orderStatuses" :key="status.value" :value="status.value">
                  {{ status.label }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">من تاريخ</label>
              <input
                v-model="orderFilters.dateFrom"
                type="date"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">إلى تاريخ</label>
              <input
                v-model="orderFilters.dateTo"
                type="date"
                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              />
            </div>
            <div>
              <button
                @click="resetOrderFilters"
                class="w-full px-4 py-2 text-sm bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-colors"
              >
                إعادة تعيين
              </button>
            </div>
          </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div v-if="selectedOrders.length > 0" class="mb-4 bg-purple-50 border border-purple-200 rounded-xl p-3 sm:p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
          <span class="text-purple-800 font-medium text-sm">تم تحديد {{ selectedOrders.length }} طلب</span>
          <div class="flex flex-wrap gap-2">
            <select
              @change="updateSelectedOrdersStatus($event.target.value); $event.target.value = ''"
              class="px-3 py-1.5 text-sm border border-purple-300 rounded-lg bg-white text-purple-800"
            >
              <option value="">تغيير الحالة...</option>
              <option v-for="status in orderStatuses" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
            <button
              @click="deleteSelectedOrders"
              class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
            >
              حذف المحدد
            </button>
            <button
              @click="selectedOrders = []"
              class="px-3 py-1.5 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"
            >
              إلغاء التحديد
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="ordersLoading" class="text-center py-16">
          <div class="inline-block w-8 h-8 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mb-3"></div>
          <p class="text-gray-500 text-sm">جاري تحميل الطلبات...</p>
        </div>

        <!-- Orders: Desktop Table -->
        <div v-if="!ordersLoading" class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-center">
                  <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded cursor-pointer" />
                </th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">رقم الطلب</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">العميل</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">المنتجات</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الإجمالي</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الحالة</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">التاريخ</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">الإجراءات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-gray-50 transition-colors" :class="{ 'bg-purple-50': selectedOrders.includes(order.id) }">
                <td class="px-4 py-4 text-center">
                  <input type="checkbox" :checked="selectedOrders.includes(order.id)" @change="toggleOrderSelection(order.id)" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded cursor-pointer" />
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm font-semibold text-gray-900">{{ order.orderNumber }}</span>
                </td>
                <td class="px-5 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ order.delivery?.fullName }}</div>
                  <div class="text-xs text-gray-400">{{ order.delivery?.phone }}</div>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm text-gray-600">{{ order.items?.length || 0 }} منتج</span>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm font-semibold text-gray-900">{{ Number(order.total || 0).toFixed(2) }} ج.م</span>
                  <span v-if="order.couponCode" class="block text-xs text-green-600 mt-0.5">{{ order.couponCode }}</span>
                </td>
                <td class="px-5 py-4">
                  <select :value="order.status" @change="updateOrderStatus(order, $event.target.value)" :class="['text-xs px-2.5 py-1 rounded-full border-0 cursor-pointer font-medium', getStatusInfo(order.status).color]">
                    <option v-for="status in orderStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                  </select>
                </td>
                <td class="px-5 py-4">
                  <span class="text-xs text-gray-400">{{ formatDate(order.createdAt) }}</span>
                </td>
                <td class="px-5 py-4 text-left">
                  <div class="flex items-center gap-2">
                    <button @click="viewOrder(order)" class="admin-action-btn text-indigo-600 hover:bg-indigo-50">عرض</button>
                    <button @click="deleteOrder(order)" class="admin-action-btn text-red-600 hover:bg-red-50">حذف</button>
                  </div>
                </td>
              </tr>
              <tr v-if="filteredOrders.length === 0">
                <td colspan="8" class="px-6 py-16 text-center text-gray-400">
                  {{ orders.length === 0 ? 'لا توجد طلبات حتى الآن.' : 'لا توجد نتائج مطابقة للفلتر.' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Orders: Mobile Cards -->
        <div v-if="!ordersLoading" class="md:hidden space-y-3">
          <div
            v-for="order in filteredOrders"
            :key="'m-' + order.id"
            class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
            :class="{ 'ring-2 ring-purple-300': selectedOrders.includes(order.id) }"
          >
            <div class="p-4">
              <!-- Top row: checkbox + order number + status -->
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                  <input type="checkbox" :checked="selectedOrders.includes(order.id)" @change="toggleOrderSelection(order.id)" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded cursor-pointer" />
                  <span class="text-sm font-bold text-gray-900">{{ order.orderNumber }}</span>
                </div>
                <select :value="order.status" @change="updateOrderStatus(order, $event.target.value)" :class="['text-[11px] px-2 py-0.5 rounded-full border-0 cursor-pointer font-medium', getStatusInfo(order.status).color]">
                  <option v-for="status in orderStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                </select>
              </div>
              <!-- Customer info -->
              <div class="flex items-center justify-between text-sm mb-2">
                <div>
                  <span class="font-medium text-gray-900">{{ order.delivery?.fullName }}</span>
                  <span class="text-gray-400 text-xs mr-2">{{ order.delivery?.phone }}</span>
                </div>
              </div>
              <!-- Bottom row: items count + total + date -->
              <div class="flex items-center justify-between text-xs text-gray-500">
                <div class="flex items-center gap-3">
                  <span>{{ order.items?.length || 0 }} منتج</span>
                  <span class="font-bold text-sm text-gray-900">{{ Number(order.total || 0).toFixed(2) }} ج.م</span>
                  <span v-if="order.couponCode" class="text-green-600">{{ order.couponCode }}</span>
                </div>
                <span>{{ formatDate(order.createdAt) }}</span>
              </div>
            </div>
            <div class="flex border-t border-gray-100">
              <button @click="viewOrder(order)" class="flex-1 py-2.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 transition-colors text-center border-l border-gray-100">عرض</button>
              <button @click="deleteOrder(order)" class="flex-1 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors text-center">حذف</button>
            </div>
          </div>
          <div v-if="filteredOrders.length === 0" class="text-center py-16 text-gray-400 text-sm">
            {{ orders.length === 0 ? 'لا توجد طلبات حتى الآن.' : 'لا توجد نتائج مطابقة للفلتر.' }}
          </div>
        </div>
      </div>

      <!-- ==================== COUPONS TAB ==================== -->
      <div v-if="activeTab === 'coupons'">
        <!-- Error Message -->
        <div v-if="couponsError" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
          {{ couponsError }}
        </div>

        <!-- Actions Bar -->
        <div class="mb-5 flex justify-between items-center">
          <h2 class="text-lg sm:text-xl font-bold text-gray-800">
            الكوبونات <span class="text-gray-400 font-normal">({{ coupons.length }})</span>
          </h2>
          <div class="flex gap-2">
            <button
              @click="fetchCoupons"
              class="px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium"
            >
              تحديث
            </button>
            <button
              @click="openNewCouponForm"
              class="admin-btn-primary text-sm"
            >
              + إضافة كوبون
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="couponsLoading" class="text-center py-16">
          <div class="inline-block w-8 h-8 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mb-3"></div>
          <p class="text-gray-500 text-sm">جاري تحميل الكوبونات...</p>
        </div>

        <!-- Coupons: Desktop Table -->
        <div v-if="!couponsLoading" class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الكود</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">النوع</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">القيمة</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الحد الأدنى</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الاستخدام</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الحالة</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">الإجراءات</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="coupon in coupons" :key="coupon.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-4">
                  <span class="text-sm font-mono font-bold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-lg">{{ coupon.code }}</span>
                </td>
                <td class="px-5 py-4">
                  <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', coupon.type === 'percentage' ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700']">
                    {{ coupon.type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت' }}
                  </span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-900">
                  <span class="font-semibold">{{ coupon.type === 'percentage' ? `${coupon.value}%` : `${coupon.value} ج.م` }}</span>
                  <span v-if="coupon.max_discount && coupon.type === 'percentage'" class="text-gray-400 text-xs block">(أقصى: {{ coupon.max_discount }} ج.م)</span>
                </td>
                <td class="px-5 py-4 text-sm text-gray-500">{{ coupon.min_order ? `${coupon.min_order} ج.م` : '-' }}</td>
                <td class="px-5 py-4 text-sm text-gray-500">{{ coupon.used_count || 0 }} / {{ coupon.usage_limit || '∞' }}</td>
                <td class="px-5 py-4">
                  <button @click="toggleCouponStatus(coupon)" :class="['px-2.5 py-1 text-xs font-medium rounded-full cursor-pointer transition-colors', coupon.is_active ? 'bg-green-50 text-green-700 hover:bg-green-100' : 'bg-red-50 text-red-700 hover:bg-red-100']">
                    {{ coupon.is_active ? 'فعال' : 'معطل' }}
                  </button>
                </td>
                <td class="px-5 py-4 text-left">
                  <div class="flex items-center gap-2">
                    <button @click="openEditCouponForm(coupon)" class="admin-action-btn text-indigo-600 hover:bg-indigo-50">تعديل</button>
                    <button @click="deleteCoupon(coupon)" class="admin-action-btn text-red-600 hover:bg-red-50">حذف</button>
                  </div>
                </td>
              </tr>
              <tr v-if="coupons.length === 0">
                <td colspan="7" class="px-6 py-16 text-center text-gray-400">
                  لا توجد كوبونات. اضغط "إضافة كوبون" لإنشاء كوبون جديد.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Coupons: Mobile Cards -->
        <div v-if="!couponsLoading" class="md:hidden space-y-3">
          <div
            v-for="coupon in coupons"
            :key="'m-' + coupon.id"
            class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
          >
            <div class="p-4">
              <!-- Code + Status -->
              <div class="flex items-center justify-between mb-3">
                <span class="text-sm font-mono font-bold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-lg">{{ coupon.code }}</span>
                <button @click="toggleCouponStatus(coupon)" :class="['px-2.5 py-0.5 text-[11px] font-medium rounded-full cursor-pointer', coupon.is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700']">
                  {{ coupon.is_active ? 'فعال' : 'معطل' }}
                </button>
              </div>
              <!-- Details grid -->
              <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <span class="text-xs text-gray-400">النوع</span>
                  <div>
                    <span :class="['px-2 py-0.5 text-[11px] font-medium rounded-full', coupon.type === 'percentage' ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700']">
                      {{ coupon.type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت' }}
                    </span>
                  </div>
                </div>
                <div>
                  <span class="text-xs text-gray-400">القيمة</span>
                  <div class="font-bold text-gray-900">{{ coupon.type === 'percentage' ? `${coupon.value}%` : `${coupon.value} ج.م` }}</div>
                  <div v-if="coupon.max_discount && coupon.type === 'percentage'" class="text-[11px] text-gray-400">(أقصى: {{ coupon.max_discount }} ج.م)</div>
                </div>
                <div>
                  <span class="text-xs text-gray-400">الحد الأدنى</span>
                  <div class="text-gray-700">{{ coupon.min_order ? `${coupon.min_order} ج.م` : '-' }}</div>
                </div>
                <div>
                  <span class="text-xs text-gray-400">الاستخدام</span>
                  <div class="text-gray-700">{{ coupon.used_count || 0 }} / {{ coupon.usage_limit || '∞' }}</div>
                </div>
              </div>
            </div>
            <div class="flex border-t border-gray-100">
              <button @click="openEditCouponForm(coupon)" class="flex-1 py-2.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50 transition-colors text-center border-l border-gray-100">تعديل</button>
              <button @click="deleteCoupon(coupon)" class="flex-1 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 transition-colors text-center">حذف</button>
            </div>
          </div>
          <div v-if="coupons.length === 0" class="text-center py-16 text-gray-400 text-sm">
            لا توجد كوبونات. اضغط "إضافة كوبون" لإنشاء كوبون جديد.
          </div>
        </div>
      </div>

      <!-- ==================== MIGRATIONS TAB ==================== -->
      <div v-if="activeTab === 'migrations'">
        <!-- Error Message -->
        <div v-if="migrationsError" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
          {{ migrationsError }}
        </div>

        <!-- Actions Bar -->
        <div class="mb-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <h2 class="text-lg sm:text-xl font-bold text-gray-800">
            تحديثات قاعدة البيانات
          </h2>
          <div class="flex gap-2">
            <button
              @click="fetchMigrations"
              :disabled="migrationsLoading"
              class="px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium disabled:opacity-50"
            >
              تحديث الحالة
            </button>
            <button
              @click="runMigrations"
              :disabled="migrationRunning"
              class="px-3 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium disabled:opacity-50"
            >
              {{ migrationRunning ? 'جاري التشغيل...' : 'تشغيل التحديثات' }}
            </button>
          </div>
        </div>

        <!-- Migration Report -->
        <div v-if="migrationReport" class="mb-5 rounded-xl shadow-sm border overflow-hidden">
          <div :class="[
            'px-4 py-3 font-medium text-sm',
            migrationReport.success
              ? 'bg-green-50 border-b border-green-200 text-green-800'
              : 'bg-red-50 border-b border-red-200 text-red-800'
          ]">
            {{ migrationReport.success ? 'تم تنفيذ التحديثات بنجاح' : 'فشل في تنفيذ التحديثات' }}
          </div>
          <div class="bg-gray-900 text-gray-100 p-4 font-mono text-xs sm:text-sm whitespace-pre-wrap overflow-x-auto" dir="ltr">{{ migrationReport.output || 'لا يوجد مخرجات' }}</div>
        </div>

        <!-- Loading State -->
        <div v-if="migrationsLoading" class="text-center py-16">
          <div class="inline-block w-8 h-8 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mb-3"></div>
          <p class="text-gray-500 text-sm">جاري تحميل حالة التحديثات...</p>
        </div>

        <!-- Migrations: Desktop Table -->
        <div v-if="!migrationsLoading" class="hidden md:block bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الحالة</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">اسم التحديث</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">الدفعة</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(migration, index) in migrations" :key="index" class="hover:bg-gray-50 transition-colors">
                <td class="px-5 py-4">
                  <span :class="['px-2.5 py-1 text-xs font-medium rounded-full', migration.ran ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700']">
                    {{ migration.ran ? 'تم التنفيذ' : 'في الانتظار' }}
                  </span>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm font-mono text-gray-900">{{ migration.migration }}</span>
                </td>
                <td class="px-5 py-4">
                  <span class="text-sm text-gray-500">{{ migration.batch || '-' }}</span>
                </td>
              </tr>
              <tr v-if="migrations.length === 0">
                <td colspan="3" class="px-6 py-16 text-center text-gray-400">لا توجد تحديثات</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Migrations: Mobile Cards -->
        <div v-if="!migrationsLoading" class="md:hidden space-y-2">
          <div
            v-for="(migration, index) in migrations"
            :key="'m-' + index"
            class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between gap-3"
          >
            <div class="flex-1 min-w-0">
              <div class="text-xs font-mono text-gray-700 truncate">{{ migration.migration }}</div>
              <div v-if="migration.batch" class="text-[11px] text-gray-400 mt-0.5">دفعة: {{ migration.batch }}</div>
            </div>
            <span :class="['px-2.5 py-1 text-[11px] font-medium rounded-full flex-shrink-0', migration.ran ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700']">
              {{ migration.ran ? 'تم التنفيذ' : 'في الانتظار' }}
            </span>
          </div>
          <div v-if="migrations.length === 0" class="text-center py-16 text-gray-400 text-sm">لا توجد تحديثات</div>
        </div>

        <!-- Raw Output -->
        <div v-if="migrationsRaw && migrations.length === 0" class="mt-5">
          <h3 class="text-sm font-bold text-gray-800 mb-2">المخرجات الخام</h3>
          <div class="bg-gray-900 text-gray-100 p-4 rounded-xl font-mono text-xs sm:text-sm whitespace-pre-wrap overflow-x-auto" dir="ltr">{{ migrationsRaw }}</div>
        </div>

        <!-- ==================== CLEANUP SECTION ==================== -->
        <div class="mt-10 pt-8 border-t border-gray-200">
          <div class="mb-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
              <h2 class="text-lg sm:text-xl font-bold text-gray-800">تنظيف الملفات غير المستخدمة</h2>
              <p class="text-sm text-gray-500 mt-1">حذف الصور والفيديوهات المرفوعة التي لا تستخدمها أي منتجات</p>
            </div>
            <button
              @click="runCleanup"
              :disabled="cleanupRunning"
              class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium disabled:opacity-50 flex items-center gap-2"
            >
              <svg v-if="cleanupRunning" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ cleanupRunning ? 'جاري التنظيف...' : 'تنظيف الملفات' }}
            </button>
          </div>

          <!-- Cleanup Report -->
          <div v-if="cleanupReport" class="rounded-xl shadow-sm border overflow-hidden">
            <div v-if="cleanupReport.error" class="px-4 py-3 bg-red-50 border-b border-red-200 text-red-800 font-medium text-sm">
              {{ cleanupReport.error }}
            </div>
            <template v-else>
              <div class="px-4 py-3 bg-green-50 border-b border-green-200 text-green-800 font-medium text-sm">
                {{ cleanupReport.message }}
              </div>
              <div class="p-4 bg-white space-y-3">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                  <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ cleanupReport.total_files_on_disk }}</div>
                    <div class="text-xs text-gray-500 mt-1">إجمالي الملفات</div>
                  </div>
                  <div class="bg-blue-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-blue-700">{{ cleanupReport.used_files }}</div>
                    <div class="text-xs text-blue-600 mt-1">ملفات مستخدمة</div>
                  </div>
                  <div class="bg-red-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-red-700">{{ cleanupReport.deleted_count }}</div>
                    <div class="text-xs text-red-600 mt-1">ملفات محذوفة</div>
                  </div>
                  <div class="bg-green-50 rounded-lg p-3 text-center">
                    <div class="text-2xl font-bold text-green-700">{{ cleanupReport.freed_space }}</div>
                    <div class="text-xs text-green-600 mt-1">مساحة محررة</div>
                  </div>
                </div>
                <!-- Deleted files list -->
                <div v-if="cleanupReport.deleted_files?.length > 0" class="mt-3">
                  <h4 class="text-sm font-medium text-gray-700 mb-2">الملفات المحذوفة:</h4>
                  <div class="bg-gray-900 text-gray-100 p-3 rounded-lg font-mono text-xs max-h-48 overflow-y-auto" dir="ltr">
                    <div v-for="(file, i) in cleanupReport.deleted_files" :key="i">{{ file }}</div>
                  </div>
                </div>
                <div v-if="cleanupReport.deleted_count === 0" class="text-center py-4 text-gray-500 text-sm">
                  لا توجد ملفات غير مستخدمة - كل الملفات مرتبطة بمنتجات
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
    </main>

    <!-- Order Details Modal -->
    <div
      v-if="selectedOrder"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" dir="rtl">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
          <h3 class="text-lg font-semibold text-gray-900">تفاصيل الطلب #{{ selectedOrder.orderNumber }}</h3>
          <button @click="closeOrderDetails" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="p-6 space-y-6">
          <!-- Customer Info -->
          <div class="bg-gray-50 rounded-lg p-4">
            <h4 class="font-medium text-gray-900 mb-3">بيانات العميل</h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-500">الاسم:</span>
                <span class="text-gray-900 mr-2">{{ selectedOrder.delivery?.fullName }}</span>
              </div>
              <div>
                <span class="text-gray-500">الهاتف:</span>
                <span class="text-gray-900 mr-2">{{ selectedOrder.delivery?.phone }}</span>
                <span v-if="selectedOrder.delivery?.phone2" class="text-gray-900"> - {{ selectedOrder.delivery?.phone2 }}</span>
              </div>
              <div>
                <span class="text-gray-500">المحافظة:</span>
                <span class="text-gray-900 mr-2">{{ selectedOrder.delivery?.governorate }}</span>
              </div>
              <div v-if="selectedOrder.contact">
                <span class="text-gray-500">الإيميل:</span>
                <span class="text-gray-900 mr-2">{{ selectedOrder.contact }}</span>
              </div>
              <div v-if="selectedOrder.delivery?.addressDetails" class="col-span-2">
                <span class="text-gray-500">العنوان:</span>
                <span class="text-gray-900 mr-2">{{ selectedOrder.delivery?.addressDetails }}</span>
              </div>
              <div v-if="selectedOrder.notes" class="col-span-2">
                <span class="text-gray-500">ملاحظات:</span>
                <span class="text-gray-900 mr-2">{{ selectedOrder.notes }}</span>
              </div>
            </div>
          </div>

          <!-- Order Items -->
          <div>
            <h4 class="font-medium text-gray-900 mb-3">المنتجات ({{ selectedOrder.items?.length || 0 }})</h4>
            <div class="space-y-3">
              <div v-for="item in selectedOrder.items" :key="item.id" class="border border-gray-200 rounded-lg p-4">
                <div class="flex gap-4">
                  <img v-if="item.image" :src="item.image" class="w-20 h-20 object-cover rounded-lg flex-shrink-0" />
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start mb-2">
                      <h5 class="text-sm font-bold text-gray-900">{{ item.name }}</h5>
                      <span class="text-sm font-bold text-purple-700 whitespace-nowrap mr-2">{{ Number(item.price * item.quantity).toFixed(2) }} ج.م</span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
                      <div v-if="item.code">
                        <span class="text-gray-500">الكود:</span>
                        <span class="text-gray-900 font-mono font-medium mr-1">{{ item.code }}</span>
                      </div>
                      <div v-if="item.color">
                        <span class="text-gray-500">اللون:</span>
                        <span class="text-gray-900 mr-1">{{ item.color }}</span>
                      </div>
                      <div v-if="item.sizes && (Array.isArray(item.sizes) ? item.sizes.length : item.sizes)">
                        <span class="text-gray-500">المقاس:</span>
                        <span class="text-gray-900 mr-1">{{ Array.isArray(item.sizes) ? item.sizes.join(', ') : item.sizes }}</span>
                      </div>
                      <div v-if="item.height">
                        <span class="text-gray-500">الطول:</span>
                        <span class="text-gray-900 mr-1">{{ item.height }}</span>
                      </div>
                      <div>
                        <span class="text-gray-500">الكمية:</span>
                        <span class="text-gray-900 mr-1">{{ item.quantity }}</span>
                      </div>
                      <div>
                        <span class="text-gray-500">سعر القطعة:</span>
                        <span class="text-gray-900 mr-1">{{ Number(item.price).toFixed(2) }} ج.م</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex justify-between text-sm mb-2">
              <span class="text-gray-500">المنتجات:</span>
              <span class="text-gray-900">{{ Number(selectedOrder.subtotal || 0).toFixed(2) }} ج.م</span>
            </div>
            <div v-if="selectedOrder.couponCode" class="flex justify-between text-sm mb-2">
              <span class="text-green-600">الخصم ({{ selectedOrder.couponCode }}):</span>
              <span class="text-green-600">-{{ Number(selectedOrder.discountAmount || 0).toFixed(2) }} ج.م</span>
            </div>
            <div class="flex justify-between text-sm mb-2">
              <span class="text-gray-500">الشحن:</span>
              <span class="text-gray-900">{{ Number(selectedOrder.shipping || 0).toFixed(2) }} ج.م</span>
            </div>
            <div class="flex justify-between text-sm font-bold border-t pt-2">
              <span class="text-gray-900">الإجمالي:</span>
              <span class="text-gray-900">{{ Number(selectedOrder.total || 0).toFixed(2) }} ج.م</span>
            </div>
          </div>

          <!-- Status & Payment -->
          <div class="flex gap-4">
            <div class="flex-1 bg-gray-50 rounded-lg p-4">
              <span class="text-gray-500 text-sm">حالة الطلب:</span>
              <span :class="['mr-2 px-2 py-1 text-xs rounded-full', getStatusInfo(selectedOrder.status).color]">
                {{ getStatusInfo(selectedOrder.status).label }}
              </span>
            </div>
            <div class="flex-1 bg-gray-50 rounded-lg p-4">
              <span class="text-gray-500 text-sm">طريقة الدفع:</span>
              <span class="text-gray-900 mr-2 text-sm">
                {{ selectedOrder.paymentMethod === 'cod' ? 'الدفع عند الاستلام' : selectedOrder.paymentMethod }}
              </span>
            </div>
          </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
          <button
            @click="closeOrderDetails"
            class="w-full px-4 py-2 text-gray-700 border border-gray-300 rounded hover:bg-gray-50 transition-colors"
          >
            إغلاق
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div
      v-if="showForm"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto" dir="rtl">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">{{ formTitle }}</h3>
        </div>

        <form @submit.prevent="saveProduct" class="p-6 space-y-4">
          <!-- Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              كود المنتج
            </label>
            <input
              v-model="form.code"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="أدخل كود المنتج"
            />
          </div>

          <!-- Name -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              اسم المنتج *
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="أدخل اسم المنتج"
            />
          </div>

          <!-- Price -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                السعر (ج.م) *
              </label>
              <input
                v-model="form.price"
                type="number"
                step="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="0.00"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                سعر التخفيض (ج.م)
              </label>
              <input
                v-model="form.salePrice"
                type="number"
                step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                placeholder="0.00"
              />
            </div>
          </div>

          <!-- Category -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              القسم *
            </label>
            <select
              v-model="form.category"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option value="">اختر القسم</option>
              <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                {{ cat.label }}
              </option>
            </select>
          </div>

          <!-- Image Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              صورة/فيديو المنتج الرئيسي {{ editingProduct ? '' : '*' }}
            </label>
            <input
              type="file"
              accept="image/*,video/mp4,video/webm,video/quicktime,video/avi,video/x-ms-wmv"
              @change="handleFileSelect"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            />
            <!-- Preview -->
            <div v-if="imagePreview" class="mt-3">
              <video
                v-if="isVideo(imageFile?.name || imagePreview)"
                :src="imagePreview"
                class="h-32 w-32 object-cover rounded border"
                muted
                playsinline
                controls
              />
              <img
                v-else
                :src="imagePreview"
                alt="معاينة الصورة"
                class="h-32 w-32 object-cover rounded border"
              />
            </div>
          </div>

          <!-- Album Images -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              ألبوم صور وفيديوهات المنتج
            </label>
            <input
              type="file"
              accept="image/*,video/mp4,video/webm,video/quicktime,video/avi,video/x-ms-wmv"
              multiple
              @change="handleAlbumFileSelect"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            />
            <!-- Existing Album Images -->
            <div v-if="form.images.length > 0" class="mt-3">
              <p class="text-sm text-gray-600 mb-2">الصور الحالية:</p>
              <div class="flex flex-wrap gap-2">
                <div v-for="(img, index) in form.images" :key="'existing-' + index" class="relative">
                  <video
                    v-if="isVideo(img)"
                    :src="img"
                    class="h-20 w-20 object-cover rounded border"
                    muted
                    playsinline
                  />
                  <img
                    v-else
                    :src="img"
                    alt="صورة الألبوم"
                    class="h-20 w-20 object-cover rounded border"
                  />
                  <button
                    type="button"
                    @click="removeExistingAlbumImage(index)"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
            <!-- New Album Previews -->
            <div v-if="albumPreviews.length > 0" class="mt-3">
              <p class="text-sm text-gray-600 mb-2">صور جديدة:</p>
              <div class="flex flex-wrap gap-2">
                <div v-for="(preview, index) in albumPreviews" :key="'new-' + index" class="relative">
                  <video
                    v-if="isVideo(preview.name)"
                    :src="preview.url"
                    class="h-20 w-20 object-cover rounded border"
                    muted
                    playsinline
                  />
                  <img
                    v-else
                    :src="preview.url"
                    alt="معاينة"
                    class="h-20 w-20 object-cover rounded border"
                  />
                  <button
                    type="button"
                    @click="removeAlbumImage(index)"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
            <p v-if="uploading" class="mt-2 text-sm text-purple-600">جاري رفع الملفات...</p>
          </div>

          <!-- Sizes -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              المقاسات المتاحة
            </label>
            <input
              v-model="newSize"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="أدخل المقاسات مفصولة بفاصلة (مثال: 30,32,34)"
              @keyup.enter.prevent="addSizes"
              @blur="addSizes"
            />
            <div v-if="form.sizes.length > 0" class="mt-3 flex flex-wrap gap-2">
              <span
                v-for="(size, index) in form.sizes"
                :key="index"
                class="inline-flex items-center px-3 py-1 bg-gray-900 text-white rounded-full text-sm"
              >
                {{ size }}
                <button
                  type="button"
                  @click="removeSize(index)"
                  class="mr-2 hover:text-red-300"
                >
                  ×
                </button>
              </span>
            </div>
          </div>

          <!-- Colors with Images -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              الألوان المتاحة (مع الصور)
            </label>
            <div class="border border-gray-300 rounded p-4 bg-gray-50">
              <!-- Add new color -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div>
                  <label class="block text-xs text-gray-500 mb-1">اسم اللون</label>
                  <input
                    v-model="newColorName"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="مثال: أسود"
                  />
                </div>
                <div>
                  <label class="block text-xs text-gray-500 mb-1">صورة اللون (اختياري)</label>
                  <input
                    type="file"
                    accept="image/*"
                    @change="handleColorImageSelect"
                    class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                  />
                </div>
                <button
                  type="button"
                  @click="addColor"
                  class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors"
                >
                  + إضافة لون
                </button>
              </div>
              <!-- Preview new color image -->
              <div v-if="newColorImagePreview" class="mt-2">
                <img :src="newColorImagePreview" alt="معاينة" class="h-16 w-16 object-cover rounded border" />
              </div>
            </div>

            <!-- Colors List -->
            <div v-if="form.colors.length > 0" class="mt-4 space-y-2">
              <div
                v-for="(color, index) in form.colors"
                :key="index"
                class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg"
              >
                <!-- Color Image -->
                <div v-if="getColorImage(color)" class="w-12 h-12 flex-shrink-0">
                  <img
                    :src="getColorImage(color)"
                    :alt="getColorName(color)"
                    class="w-full h-full object-cover rounded border"
                  />
                </div>
                <div v-else class="w-12 h-12 flex-shrink-0 bg-gray-200 rounded border flex items-center justify-center text-gray-400 text-xs">
                  بدون صورة
                </div>
                <!-- Color Name -->
                <span class="flex-1 font-medium text-gray-900">{{ getColorName(color) }}</span>
                <!-- Remove Button -->
                <button
                  type="button"
                  @click="removeColor(index)"
                  class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Heights -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              الأطوال المتاحة
            </label>
            <input
              v-model="newHeight"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="أدخل الأطوال مفصولة بفاصلة (مثال: 160,165,170)"
              @keyup.enter.prevent="addHeights"
              @blur="addHeights"
            />
            <div v-if="form.heights.length > 0" class="mt-3 flex flex-wrap gap-2">
              <span
                v-for="(height, index) in form.heights"
                :key="index"
                class="inline-flex items-center px-3 py-1 bg-blue-600 text-white rounded-full text-sm"
              >
                {{ height }}
                <button
                  type="button"
                  @click="removeHeight(index)"
                  class="mr-2 hover:text-red-300"
                >
                  ×
                </button>
              </span>
            </div>
          </div>

          <!-- Weights -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              الأوزان المتاحة
            </label>
            <input
              v-model="newWeight"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="أدخل الأوزان مفصولة بفاصلة (مثال: 50-60,60-70,70-80)"
              @keyup.enter.prevent="addWeights"
              @blur="addWeights"
            />
            <div v-if="form.weights.length > 0" class="mt-3 flex flex-wrap gap-2">
              <span
                v-for="(weight, index) in form.weights"
                :key="index"
                class="inline-flex items-center px-3 py-1 bg-green-600 text-white rounded-full text-sm"
              >
                {{ weight }}
                <button
                  type="button"
                  @click="removeWeight(index)"
                  class="mr-2 hover:text-red-300"
                >
                  ×
                </button>
              </span>
            </div>
          </div>

          <!-- Description -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              الوصف
            </label>
            <textarea
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="أدخل وصف المنتج"
            ></textarea>
          </div>

          <!-- In Stock -->
          <div class="flex items-center">
            <input
              v-model="form.inStock"
              type="checkbox"
              id="inStock"
              class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
            />
            <label for="inStock" class="mr-2 text-sm text-gray-700">
              متوفر في المخزون
            </label>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-start gap-3 pt-4 border-t border-gray-200">
            <button
              type="submit"
              :disabled="saving"
              class="px-4 py-2 text-white rounded transition-colors disabled:opacity-50"
              style="background-color: #5B3A8C;"
            >
              {{ saving ? 'جاري الحفظ...' : 'حفظ المنتج' }}
            </button>
            <button
              type="button"
              @click="closeForm"
              class="px-4 py-2 text-gray-700 border border-gray-300 rounded hover:bg-gray-50 transition-colors"
            >
              إلغاء
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Coupon Form Modal -->
    <div
      v-if="showCouponForm"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto" dir="rtl">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ editingCoupon ? 'تعديل الكوبون' : 'إضافة كوبون جديد' }}
          </h3>
        </div>

        <form @submit.prevent="saveCoupon" class="p-6 space-y-4">
          <!-- Code -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">كود الخصم *</label>
            <input
              v-model="couponForm.code"
              type="text"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 uppercase"
              placeholder="مثال: WELCOME10"
            />
          </div>

          <!-- Type & Value -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">نوع الخصم *</label>
              <select
                v-model="couponForm.type"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
              >
                <option value="percentage">نسبة مئوية (%)</option>
                <option value="fixed">مبلغ ثابت (ج.م)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                {{ couponForm.type === 'percentage' ? 'النسبة (%)' : 'المبلغ (ج.م)' }} *
              </label>
              <input
                v-model="couponForm.value"
                type="number"
                step="0.01"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                :placeholder="couponForm.type === 'percentage' ? '10' : '50'"
              />
            </div>
          </div>

          <!-- Min Order & Max Discount -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">الحد الأدنى للطلب (ج.م)</label>
              <input
                v-model="couponForm.min_order"
                type="number"
                step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                placeholder="100"
              />
            </div>
            <div v-if="couponForm.type === 'percentage'">
              <label class="block text-sm font-medium text-gray-700 mb-1">أقصى خصم (ج.م)</label>
              <input
                v-model="couponForm.max_discount"
                type="number"
                step="0.01"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
                placeholder="50"
              />
            </div>
          </div>

          <!-- Usage Limit -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">حد الاستخدام (اتركه فارغاً لعدد غير محدود)</label>
            <input
              v-model="couponForm.usage_limit"
              type="number"
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
              placeholder="100"
            />
          </div>

          <!-- Date Range -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ البدء</label>
              <input
                v-model="couponForm.start_date"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الانتهاء</label>
              <input
                v-model="couponForm.end_date"
                type="date"
                class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500"
              />
            </div>
          </div>

          <!-- Is Active -->
          <div class="flex items-center">
            <input
              v-model="couponForm.is_active"
              type="checkbox"
              id="couponActive"
              class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
            />
            <label for="couponActive" class="mr-2 text-sm text-gray-700">
              الكوبون فعال
            </label>
          </div>

          <!-- Form Actions -->
          <div class="flex justify-start gap-3 pt-4 border-t border-gray-200">
            <button
              type="submit"
              :disabled="savingCoupon"
              class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors disabled:opacity-50"
            >
              {{ savingCoupon ? 'جاري الحفظ...' : 'حفظ الكوبون' }}
            </button>
            <button
              type="button"
              @click="closeCouponForm"
              class="px-4 py-2 text-gray-700 border border-gray-300 rounded hover:bg-gray-50 transition-colors"
            >
              إلغاء
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-header {
  background: linear-gradient(135deg, #5B3A8C 0%, #7C4DBC 50%, #9B6DD7 100%);
  position: relative;
  overflow: hidden;
}
.admin-header::before {
  content: '';
  position: absolute;
  top: -50%;
  left: -20%;
  width: 60%;
  height: 200%;
  background: radial-gradient(ellipse, rgba(255,255,255,0.08) 0%, transparent 70%);
  pointer-events: none;
}

.admin-btn-primary {
  background: linear-gradient(135deg, #5B3A8C 0%, #7C4DBC 100%);
  color: white;
  padding: 0.5rem 1.25rem;
  border-radius: 0.75rem;
  font-weight: 600;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(91, 58, 140, 0.3);
}
.admin-btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(91, 58, 140, 0.4);
}
.admin-btn-primary:active {
  transform: translateY(0);
}

.admin-action-btn {
  padding: 0.25rem 0.75rem;
  border-radius: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 500;
  transition: all 0.15s;
}

.border-b-3 {
  border-bottom-width: 3px;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>
