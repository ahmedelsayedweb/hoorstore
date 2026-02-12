<script setup>
import { ref, onMounted } from 'vue'
import { couponsApi } from '../../api/cart'

// Coupons state
const coupons = ref([])
const couponsLoading = ref(false)
const couponsError = ref(null)
const showCouponForm = ref(false)
const editingCoupon = ref(null)
const savingCoupon = ref(false)

// Coupon form
const couponForm = ref({
  code: '',
  type: 'percentage',
  value: '',
  min_order: '',
  max_discount: '',
  usage_limit: '',
  one_per_device: false,
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
    one_per_device: false,
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
    one_per_device: coupon.one_per_device || false,
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
      one_per_device: couponForm.value.one_per_device,
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

onMounted(() => {
  fetchCoupons()
})

defineExpose({ coupons })
</script>

<template>
  <div>
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
            <td class="px-5 py-4 text-sm text-gray-500">
              {{ coupon.used_count || 0 }} / {{ coupon.usage_limit || '∞' }}
              <span v-if="coupon.one_per_device" class="block text-[11px] text-orange-600 mt-0.5">استخدام واحد/جهاز</span>
            </td>
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
          <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-mono font-bold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-lg">{{ coupon.code }}</span>
            <button @click="toggleCouponStatus(coupon)" :class="['px-2.5 py-0.5 text-[11px] font-medium rounded-full cursor-pointer', coupon.is_active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700']">
              {{ coupon.is_active ? 'فعال' : 'معطل' }}
            </button>
          </div>
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
              <span v-if="coupon.one_per_device" class="text-[11px] text-orange-600">استخدام واحد/جهاز</span>
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

          <!-- One Per Device -->
          <div class="flex items-center">
            <input
              v-model="couponForm.one_per_device"
              type="checkbox"
              id="couponOnePerDevice"
              class="h-4 w-4 text-purple-600 focus:ring-purple-500 border-gray-300 rounded"
            />
            <label for="couponOnePerDevice" class="mr-2 text-sm text-gray-700">
              استخدام واحد لكل جهاز
            </label>
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
