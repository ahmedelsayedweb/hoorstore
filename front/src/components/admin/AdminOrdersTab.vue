<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { ordersApi } from '../../api/cart'

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

  if (orderFilters.value.status) {
    result = result.filter(o => o.status === orderFilters.value.status)
  }

  if (orderFilters.value.search) {
    const search = orderFilters.value.search.toLowerCase()
    result = result.filter(o =>
      o.delivery?.fullName?.toLowerCase().includes(search) ||
      o.delivery?.phone?.includes(search) ||
      o.orderNumber?.toLowerCase().includes(search)
    )
  }

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

// Pagination
const ordersCurrentPage = ref(1)
const ordersPerPage = ref(10)

const ordersTotalPages = computed(() => Math.ceil(filteredOrders.value.length / ordersPerPage.value))

const paginatedOrders = computed(() => {
  const start = (ordersCurrentPage.value - 1) * ordersPerPage.value
  return filteredOrders.value.slice(start, start + ordersPerPage.value)
})

const goToOrderPage = (page) => {
  if (page >= 1 && page <= ordersTotalPages.value) {
    ordersCurrentPage.value = page
  }
}

// Reset page when filters change
watch(orderFilters, () => { ordersCurrentPage.value = 1 }, { deep: true })

// Reset filters
const resetOrderFilters = () => {
  orderFilters.value = { status: '', search: '', dateFrom: '', dateTo: '' }
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

onMounted(() => {
  fetchOrders()
})

defineExpose({ orders })
</script>

<template>
  <div>
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
          <tr v-for="order in paginatedOrders" :key="order.id" class="hover:bg-gray-50 transition-colors" :class="{ 'bg-purple-50': selectedOrders.includes(order.id) }">
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
          <tr v-if="paginatedOrders.length === 0">
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
        v-for="order in paginatedOrders"
        :key="'m-' + order.id"
        class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden"
        :class="{ 'ring-2 ring-purple-300': selectedOrders.includes(order.id) }"
      >
        <div class="p-4">
          <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
              <input type="checkbox" :checked="selectedOrders.includes(order.id)" @change="toggleOrderSelection(order.id)" class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded cursor-pointer" />
              <span class="text-sm font-bold text-gray-900">{{ order.orderNumber }}</span>
            </div>
            <select :value="order.status" @change="updateOrderStatus(order, $event.target.value)" :class="['text-[11px] px-2 py-0.5 rounded-full border-0 cursor-pointer font-medium', getStatusInfo(order.status).color]">
              <option v-for="status in orderStatuses" :key="status.value" :value="status.value">{{ status.label }}</option>
            </select>
          </div>
          <div class="flex items-center justify-between text-sm mb-2">
            <div>
              <span class="font-medium text-gray-900">{{ order.delivery?.fullName }}</span>
              <span class="text-gray-400 text-xs mr-2">{{ order.delivery?.phone }}</span>
            </div>
          </div>
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
      <div v-if="paginatedOrders.length === 0" class="text-center py-16 text-gray-400 text-sm">
        {{ orders.length === 0 ? 'لا توجد طلبات حتى الآن.' : 'لا توجد نتائج مطابقة للفلتر.' }}
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="ordersTotalPages > 1" class="flex flex-wrap items-center justify-between gap-3 mt-4 px-2">
      <span class="text-sm text-gray-500">
        عرض {{ (ordersCurrentPage - 1) * ordersPerPage + 1 }}-{{ Math.min(ordersCurrentPage * ordersPerPage, filteredOrders.length) }} من {{ filteredOrders.length }} طلب
      </span>
      <div class="flex items-center gap-1">
        <button
          @click="goToOrderPage(ordersCurrentPage - 1)"
          :disabled="ordersCurrentPage === 1"
          class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          السابق
        </button>
        <template v-for="page in ordersTotalPages" :key="page">
          <button
            v-if="page === 1 || page === ordersTotalPages || (page >= ordersCurrentPage - 1 && page <= ordersCurrentPage + 1)"
            @click="goToOrderPage(page)"
            :class="[
              'px-3 py-1.5 text-sm rounded-lg border',
              page === ordersCurrentPage
                ? 'bg-[#5B3A8C] text-white border-[#5B3A8C]'
                : 'border-gray-300 hover:bg-gray-50'
            ]"
          >
            {{ page }}
          </button>
          <span
            v-else-if="page === ordersCurrentPage - 2 || page === ordersCurrentPage + 2"
            class="px-1 text-gray-400"
          >...</span>
        </template>
        <button
          @click="goToOrderPage(ordersCurrentPage + 1)"
          :disabled="ordersCurrentPage === ordersTotalPages"
          class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          التالي
        </button>
      </div>
    </div>

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
  </div>
</template>
