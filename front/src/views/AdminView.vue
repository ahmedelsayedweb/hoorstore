<script setup>
import { ref, onMounted, computed } from 'vue'
import { productsApi, uploadApi } from '../api/products'
import { ordersApi } from '../api/cart'

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
const newColor = ref('')
const newHeight = ref('')
const newWeight = ref('')

// Form data
const form = ref({
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

onMounted(fetchProducts)

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
    albumPreviews.value.push(URL.createObjectURL(file))
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

// Add new colors (comma separated)
const addColors = () => {
  const colors = newColor.value.split(',').map(s => s.trim()).filter(s => s)
  colors.forEach(color => {
    if (!form.value.colors.includes(color)) {
      form.value.colors.push(color)
    }
  })
  newColor.value = ''
}

// Remove color
const removeColor = (index) => {
  form.value.colors.splice(index, 1)
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
  newColor.value = ''
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
  form.value = {
    name: product.name || '',
    price: product.price || '',
    salePrice: product.salePrice || '',
    category: product.category || '',
    image: product.image || '',
    images: product.images ? [...product.images] : [],
    sizes: product.sizes ? [...product.sizes] : [],
    colors: product.colors ? [...product.colors] : [],
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
  newColor.value = ''
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
  <div class="min-h-screen bg-gray-100" dir="rtl">
    <!-- Header -->
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-6">
        <h1 class="text-3xl font-bold text-gray-900">لوحة التحكم</h1>
        <p class="text-gray-600 mt-1">إدارة المتجر</p>
      </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="bg-white border-b">
      <div class="max-w-7xl mx-auto px-4">
        <nav class="flex gap-8">
          <button
            @click="switchTab('products')"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'products'
                ? 'border-purple-600 text-purple-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            المنتجات ({{ products.length }})
          </button>
          <button
            @click="switchTab('orders')"
            :class="[
              'py-4 px-1 border-b-2 font-medium text-sm transition-colors',
              activeTab === 'orders'
                ? 'border-purple-600 text-purple-600'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]"
          >
            الطلبات ({{ orders.length }})
          </button>
        </nav>
      </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8">
      <!-- ==================== PRODUCTS TAB ==================== -->
      <div v-if="activeTab === 'products'">
        <!-- Error Message -->
        <div v-if="error" class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {{ error }}
        </div>

        <!-- Actions Bar -->
        <div class="mb-6 flex justify-between items-center">
          <h2 class="text-xl font-semibold text-gray-800">
            المنتجات ({{ products.length }})
          </h2>
          <button
            @click="openNewForm"
            class="bg-primary text-white px-4 py-2 rounded hover:bg-primary-dark transition-colors"
            style="background-color: #5B3A8C;"
          >
            + إضافة منتج
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <p class="text-gray-500">جاري تحميل المنتجات...</p>
        </div>

      <!-- Products Table -->
      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                المنتج
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                التصنيف
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                السعر
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                الحالة
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                الإجراءات
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="h-12 w-12 flex-shrink-0">
                    <img
                      :src="product.image"
                      :alt="product.name"
                      class="h-12 w-12 object-cover rounded"
                    />
                  </div>
                  <div class="mr-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ product.name }}
                    </div>
                    <div class="text-sm text-gray-500">
                      رقم: {{ product.id }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs bg-gray-100 text-gray-800 rounded">
                  {{ getCategoryLabel(product.category) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">
                  {{ Number(product.price || 0).toFixed(2) }} ج.م
                </div>
                <div v-if="product.salePrice" class="text-sm text-green-600">
                  تخفيض: {{ Number(product.salePrice || 0).toFixed(2) }} ج.م
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-2 py-1 text-xs rounded-full"
                  :class="product.inStock !== false
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800'"
                >
                  {{ product.inStock !== false ? 'متوفر' : 'غير متوفر' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                <button
                  @click="openEditForm(product)"
                  class="text-indigo-600 hover:text-indigo-900 ml-4"
                >
                  تعديل
                </button>
                <button
                  @click="deleteProduct(product)"
                  class="text-red-600 hover:text-red-900"
                >
                  حذف
                </button>
              </td>
            </tr>
            <tr v-if="products.length === 0">
              <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                لا توجد منتجات. اضغط "إضافة منتج" لإنشاء منتج جديد.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      </div>

      <!-- ==================== ORDERS TAB ==================== -->
      <div v-if="activeTab === 'orders'">
        <!-- Error Message -->
        <div v-if="ordersError" class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {{ ordersError }}
        </div>

        <!-- Actions Bar -->
        <div class="mb-6 flex justify-between items-center">
          <h2 class="text-xl font-semibold text-gray-800">
            الطلبات ({{ orders.length }})
          </h2>
          <button
            @click="fetchOrders"
            class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition-colors"
          >
            تحديث
          </button>
        </div>

        <!-- Bulk Actions Bar -->
        <div v-if="selectedOrders.length > 0" class="mb-4 bg-purple-50 border border-purple-200 rounded-lg p-4 flex items-center justify-between">
          <span class="text-purple-800 font-medium">تم تحديد {{ selectedOrders.length }} طلب</span>
          <div class="flex gap-2">
            <select
              @change="updateSelectedOrdersStatus($event.target.value); $event.target.value = ''"
              class="px-3 py-1.5 text-sm border border-purple-300 rounded bg-white text-purple-800"
            >
              <option value="">تغيير الحالة...</option>
              <option v-for="status in orderStatuses" :key="status.value" :value="status.value">
                {{ status.label }}
              </option>
            </select>
            <button
              @click="deleteSelectedOrders"
              class="px-3 py-1.5 text-sm bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
            >
              حذف المحدد
            </button>
            <button
              @click="selectedOrders = []"
              class="px-3 py-1.5 text-sm bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition-colors"
            >
              إلغاء التحديد
            </button>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="ordersLoading" class="text-center py-12">
          <p class="text-gray-500">جاري تحميل الطلبات...</p>
        </div>

        <!-- Orders Table -->
        <div v-else class="bg-white rounded-lg shadow overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-center">
                  <input
                    type="checkbox"
                    :checked="isAllSelected"
                    @change="toggleSelectAll"
                    class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded cursor-pointer"
                  />
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  رقم الطلب
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  العميل
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  المنتجات
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  الإجمالي
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  الحالة
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  التاريخ
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  الإجراءات
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-50" :class="{ 'bg-purple-50': selectedOrders.includes(order.id) }">
                <td class="px-4 py-4 text-center">
                  <input
                    type="checkbox"
                    :checked="selectedOrders.includes(order.id)"
                    @change="toggleOrderSelection(order.id)"
                    class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded cursor-pointer"
                  />
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm font-medium text-gray-900">{{ order.orderNumber }}</span>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ order.delivery?.fullName }}</div>
                  <div class="text-sm text-gray-500">{{ order.delivery?.phone }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ order.items?.length || 0 }} منتج</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm font-medium text-gray-900">{{ Number(order.total || 0).toFixed(2) }} ج.م</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <select
                    :value="order.status"
                    @change="updateOrderStatus(order, $event.target.value)"
                    :class="[
                      'text-xs px-2 py-1 rounded-full border-0 cursor-pointer',
                      getStatusInfo(order.status).color
                    ]"
                  >
                    <option v-for="status in orderStatuses" :key="status.value" :value="status.value">
                      {{ status.label }}
                    </option>
                  </select>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="text-sm text-gray-500">{{ formatDate(order.createdAt) }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-left text-sm font-medium">
                  <button
                    @click="viewOrder(order)"
                    class="text-indigo-600 hover:text-indigo-900 ml-4"
                  >
                    عرض
                  </button>
                  <button
                    @click="deleteOrder(order)"
                    class="text-red-600 hover:text-red-900"
                  >
                    حذف
                  </button>
                </td>
              </tr>
              <tr v-if="orders.length === 0">
                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                  لا توجد طلبات حتى الآن.
                </td>
              </tr>
            </tbody>
          </table>
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
            <h4 class="font-medium text-gray-900 mb-3">المنتجات</h4>
            <div class="border rounded-lg overflow-hidden">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">المنتج</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">التفاصيل</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">الكمية</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">السعر</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="item in selectedOrder.items" :key="item.id">
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-3">
                        <img v-if="item.image" :src="item.image" class="w-12 h-12 object-cover rounded" />
                        <span class="text-sm text-gray-900">{{ item.name }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500">
                      {{ item.color }} / {{ item.size }} / {{ item.height }}
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ item.quantity }}</td>
                    <td class="px-4 py-3 text-sm text-gray-900">{{ Number(item.price * item.quantity).toFixed(2) }} ج.م</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Order Summary -->
          <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex justify-between text-sm mb-2">
              <span class="text-gray-500">المنتجات:</span>
              <span class="text-gray-900">{{ Number(selectedOrder.subtotal || 0).toFixed(2) }} ج.م</span>
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
              التصنيف *
            </label>
            <select
              v-model="form.category"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            >
              <option value="">اختر التصنيف</option>
              <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                {{ cat.label }}
              </option>
            </select>
          </div>

          <!-- Image Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              صورة المنتج الرئيسية {{ editingProduct ? '' : '*' }}
            </label>
            <input
              type="file"
              accept="image/*"
              @change="handleFileSelect"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            />
            <!-- Image Preview -->
            <div v-if="imagePreview" class="mt-3">
              <img
                :src="imagePreview"
                alt="معاينة الصورة"
                class="h-32 w-32 object-cover rounded border"
              />
            </div>
          </div>

          <!-- Album Images -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              ألبوم صور المنتج
            </label>
            <input
              type="file"
              accept="image/*"
              multiple
              @change="handleAlbumFileSelect"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            />
            <!-- Existing Album Images -->
            <div v-if="form.images.length > 0" class="mt-3">
              <p class="text-sm text-gray-600 mb-2">الصور الحالية:</p>
              <div class="flex flex-wrap gap-2">
                <div v-for="(img, index) in form.images" :key="'existing-' + index" class="relative">
                  <img
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
                  <img
                    :src="preview"
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
            <p v-if="uploading" class="mt-2 text-sm text-purple-600">جاري رفع الصور...</p>
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

          <!-- Colors -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              الألوان المتاحة
            </label>
            <input
              v-model="newColor"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
              placeholder="أدخل الألوان مفصولة بفاصلة (مثال: أسود,أبيض,أحمر)"
              @keyup.enter.prevent="addColors"
              @blur="addColors"
            />
            <div v-if="form.colors.length > 0" class="mt-3 flex flex-wrap gap-2">
              <span
                v-for="(color, index) in form.colors"
                :key="index"
                class="inline-flex items-center px-3 py-1 bg-purple-600 text-white rounded-full text-sm"
              >
                {{ color }}
                <button
                  type="button"
                  @click="removeColor(index)"
                  class="mr-2 hover:text-red-300"
                >
                  ×
                </button>
              </span>
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
  </div>
</template>
