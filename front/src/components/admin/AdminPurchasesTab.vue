<script setup>
import { ref, computed, onMounted } from 'vue'
import { purchasesApi } from '../../api/purchases'

const purchases = ref([])
const loading = ref(false)
const totalAmount = ref(0)

const search = ref('')
const dateFrom = ref('')
const dateTo = ref('')

const showForm = ref(false)
const editingId = ref(null)
const form = ref({
  transaction_number: '',
  date: '',
  supplier_name: '',
  product: '',
  quantity: '',
  total: '',
  notes: '',
})

const currentPage = ref(1)
const perPage = 20

const filteredPurchases = computed(() => {
  let list = purchases.value
  if (search.value) {
    const s = search.value.toLowerCase()
    list = list.filter(p =>
      (p.supplier_name || '').toLowerCase().includes(s) ||
      (p.product || '').toLowerCase().includes(s)
    )
  }
  if (dateFrom.value) list = list.filter(p => p.date >= dateFrom.value)
  if (dateTo.value) list = list.filter(p => p.date <= dateTo.value)
  return list
})

const totalPages = computed(() => Math.ceil(filteredPurchases.value.length / perPage))
const paginated = computed(() => {
  const start = (currentPage.value - 1) * perPage
  return filteredPurchases.value.slice(start, start + perPage)
})

const filteredTotal = computed(() =>
  filteredPurchases.value.reduce((s, p) => s + (p.total || 0), 0)
)

async function load() {
  loading.value = true
  try {
    const res = await purchasesApi.getAll()
    purchases.value = res.data || []
    totalAmount.value = res.total_amount || 0
  } finally {
    loading.value = false
  }
}

function openAdd() {
  editingId.value = null
  form.value = { transaction_number: '', date: '', supplier_name: '', product: '', quantity: '', total: '', notes: '' }
  showForm.value = true
}

function openEdit(p) {
  editingId.value = p.id
  form.value = {
    transaction_number: p.transaction_number || '',
    date: p.date || '',
    supplier_name: p.supplier_name || '',
    product: p.product || '',
    quantity: p.quantity || '',
    total: p.total ?? '',
    notes: p.notes || '',
  }
  showForm.value = true
}

async function save() {
  const payload = {
    ...form.value,
    total: form.value.total !== '' ? parseFloat(form.value.total) : null,
    transaction_number: form.value.transaction_number !== '' ? parseInt(form.value.transaction_number) : null,
  }
  if (editingId.value) {
    await purchasesApi.update(editingId.value, payload)
  } else {
    await purchasesApi.create(payload)
  }
  showForm.value = false
  await load()
}

async function remove(id) {
  if (!confirm('تأكيد الحذف؟')) return
  await purchasesApi.remove(id)
  await load()
}

function formatAmount(val) {
  if (val === null || val === undefined) return '-'
  return Number(val).toLocaleString('ar-EG') + ' ج.م'
}

onMounted(load)
</script>

<template>
  <div dir="rtl">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h2 class="text-xl font-bold text-gray-800">المشتريات</h2>
        <p class="text-sm text-gray-500 mt-0.5">
          إجمالي المصروف: <span class="font-bold text-red-600">{{ formatAmount(filteredTotal) }}</span>
          &nbsp;|&nbsp; عدد العمليات: {{ filteredPurchases.length }}
        </p>
      </div>
      <button @click="openAdd" class="admin-btn-primary">+ إضافة عملية</button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3">
      <input v-model="search" placeholder="بحث باسم المورد أو المنتج" class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-40" />
      <input v-model="dateFrom" type="date" class="border rounded-lg px-3 py-2 text-sm" />
      <input v-model="dateTo" type="date" class="border rounded-lg px-3 py-2 text-sm" />
      <button @click="search=''; dateFrom=''; dateTo=''" class="text-sm text-gray-500 hover:text-gray-700">مسح</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-400">جاري التحميل...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-4 py-3 text-right text-gray-600 font-medium">#</th>
              <th class="px-4 py-3 text-right text-gray-600 font-medium">التاريخ</th>
              <th class="px-4 py-3 text-right text-gray-600 font-medium">المورد</th>
              <th class="px-4 py-3 text-right text-gray-600 font-medium">المنتج</th>
              <th class="px-4 py-3 text-right text-gray-600 font-medium">الكمية</th>
              <th class="px-4 py-3 text-right text-gray-600 font-medium">الإجمالي</th>
              <th class="px-4 py-3 text-right text-gray-600 font-medium">ملاحظات</th>
              <th class="px-4 py-3 text-right text-gray-600 font-medium">إجراءات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="p in paginated" :key="p.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-gray-500">{{ p.transaction_number }}</td>
              <td class="px-4 py-3 whitespace-nowrap">{{ p.date || '-' }}</td>
              <td class="px-4 py-3 font-medium text-gray-800">{{ p.supplier_name || '-' }}</td>
              <td class="px-4 py-3 text-gray-700 max-w-xs">{{ p.product || '-' }}</td>
              <td class="px-4 py-3 text-center">{{ p.quantity || '-' }}</td>
              <td class="px-4 py-3 font-semibold" :class="(p.total || 0) < 0 ? 'text-red-600' : 'text-gray-800'">
                {{ formatAmount(p.total) }}
              </td>
              <td class="px-4 py-3 text-gray-500 max-w-xs text-xs">{{ p.notes || '' }}</td>
              <td class="px-4 py-3">
                <div class="flex gap-2">
                  <button @click="openEdit(p)" class="admin-action-btn text-blue-600 hover:bg-blue-50">تعديل</button>
                  <button @click="remove(p.id)" class="admin-action-btn text-red-500 hover:bg-red-50">حذف</button>
                </div>
              </td>
            </tr>
            <tr v-if="!paginated.length">
              <td colspan="8" class="px-4 py-8 text-center text-gray-400">لا توجد بيانات</td>
            </tr>
          </tbody>
          <!-- Total row -->
          <tfoot class="bg-gray-50 border-t-2 border-gray-300">
            <tr>
              <td colspan="5" class="px-4 py-3 text-right font-bold text-gray-700">الإجمالي</td>
              <td class="px-4 py-3 font-bold text-red-700 text-base">{{ formatAmount(filteredTotal) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 p-4 border-t">
        <button v-for="p in totalPages" :key="p"
          @click="currentPage = p"
          :class="['w-8 h-8 rounded-lg text-sm font-medium', currentPage === p ? 'bg-purple-600 text-white' : 'text-gray-600 hover:bg-gray-100']"
        >{{ p }}</button>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
        <div class="p-6 border-b">
          <h3 class="text-lg font-bold text-gray-800">{{ editingId ? 'تعديل عملية شراء' : 'إضافة عملية شراء' }}</h3>
        </div>
        <div class="p-6 space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-600 mb-1">رقم العملية</label>
              <input v-model="form.transaction_number" type="number" class="w-full border rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm text-gray-600 mb-1">التاريخ</label>
              <input v-model="form.date" type="date" class="w-full border rounded-lg px-3 py-2 text-sm" />
            </div>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">اسم المورد</label>
            <input v-model="form.supplier_name" class="w-full border rounded-lg px-3 py-2 text-sm" />
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">المنتج</label>
            <textarea v-model="form.product" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-gray-600 mb-1">الكمية</label>
              <input v-model="form.quantity" class="w-full border rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm text-gray-600 mb-1">الإجمالي (ج.م)</label>
              <input v-model="form.total" type="number" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm" />
            </div>
          </div>
          <div>
            <label class="block text-sm text-gray-600 mb-1">ملاحظات</label>
            <input v-model="form.notes" class="w-full border rounded-lg px-3 py-2 text-sm" />
          </div>
        </div>
        <div class="p-6 border-t flex gap-3 justify-end">
          <button @click="showForm = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">إلغاء</button>
          <button @click="save" class="admin-btn-primary">حفظ</button>
        </div>
      </div>
    </div>
  </div>
</template>
