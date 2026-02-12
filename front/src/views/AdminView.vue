<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AdminProductsTab from '../components/admin/AdminProductsTab.vue'
import AdminOrdersTab from '../components/admin/AdminOrdersTab.vue'
import AdminCouponsTab from '../components/admin/AdminCouponsTab.vue'
import AdminMigrationsTab from '../components/admin/AdminMigrationsTab.vue'

const route = useRoute()
const router = useRouter()

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

// Tab component refs
const productsTab = ref(null)
const ordersTab = ref(null)
const couponsTab = ref(null)

// Switch tab
const switchTab = (tab) => {
  activeTab.value = tab
}

// Tab counts (exposed from child components)
const getProductsCount = () => productsTab.value?.products?.length || 0
const getOrdersCount = () => ordersTab.value?.orders?.length || 0
const getCouponsCount = () => couponsTab.value?.coupons?.length || 0

onMounted(() => {
  checkAuth()
})
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
            المنتجات ({{ getProductsCount() }})
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
            الطلبات ({{ getOrdersCount() }})
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
            الكوبونات ({{ getCouponsCount() }})
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
      <AdminProductsTab v-if="activeTab === 'products'" ref="productsTab" />
      <AdminOrdersTab v-if="activeTab === 'orders'" ref="ordersTab" />
      <AdminCouponsTab v-if="activeTab === 'coupons'" ref="couponsTab" />
      <AdminMigrationsTab v-if="activeTab === 'migrations'" />
    </main>
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

:deep(.admin-btn-primary) {
  background: linear-gradient(135deg, #5B3A8C 0%, #7C4DBC 100%);
  color: white;
  padding: 0.5rem 1.25rem;
  border-radius: 0.75rem;
  font-weight: 600;
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(91, 58, 140, 0.3);
}
:deep(.admin-btn-primary):hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(91, 58, 140, 0.4);
}
:deep(.admin-btn-primary):active {
  transform: translateY(0);
}

:deep(.admin-action-btn) {
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
