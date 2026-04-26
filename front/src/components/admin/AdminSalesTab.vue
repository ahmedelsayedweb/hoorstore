<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { salesApi } from '../../api/purchases'

const sales = ref([])
const loading = ref(false)
const meta = ref({ count: 0, total_amount: 0, total_debtor: 0, total_creditor: 0, current_page: 1, last_page: 1, per_page: 20 })

const search = ref('')
const dateFrom = ref('')
const dateTo = ref('')
const debtorFilter = ref('')   // '' | 'has' | 'none'
const creditorFilter = ref('') // '' | 'has' | 'none'
const currentPage = ref(1)
const perPage = 20

const showForm = ref(false)
const editingId = ref(null)
const form = ref({
  transaction_number: '', date: '', customer_name: '', product: '',
  quantity: '', unit_price: '', total: '', debtor: '', creditor: '', notes: '',
})

async function load() {
  loading.value = true
  try {
    const params = { page: currentPage.value, per_page: perPage }
    if (search.value)        params.search = search.value
    if (dateFrom.value)      params.date_from = dateFrom.value
    if (dateTo.value)        params.date_to = dateTo.value
    if (debtorFilter.value)  params.debtor = debtorFilter.value
    if (creditorFilter.value) params.creditor = creditorFilter.value

    const res = await salesApi.getAll(params)
    sales.value = res.data || []
    meta.value = {
      count:          res.count          ?? 0,
      total_amount:   res.total_amount   ?? 0,
      total_debtor:   res.total_debtor   ?? 0,
      total_creditor: res.total_creditor ?? 0,
      current_page:   res.current_page   ?? 1,
      last_page:      res.last_page      ?? 1,
      per_page:       res.per_page       ?? perPage,
    }
  } finally {
    loading.value = false
  }
}

// Reset page and reload when filters change
function applyFilter() { currentPage.value = 1; load() }
watch(search,        applyFilter)
watch(dateFrom,      applyFilter)
watch(dateTo,        applyFilter)
watch(debtorFilter,  applyFilter)
watch(creditorFilter,applyFilter)
watch(currentPage,   load)

function clearFilters() {
  search.value = ''; dateFrom.value = ''; dateTo.value = ''
  debtorFilter.value = ''; creditorFilter.value = ''
  currentPage.value = 1
}

function openAdd() {
  editingId.value = null
  form.value = { transaction_number: '', date: '', customer_name: '', product: '', quantity: '', unit_price: '', total: '', debtor: '', creditor: '', notes: '' }
  showForm.value = true
}
function openEdit(s) {
  editingId.value = s.id
  form.value = {
    transaction_number: s.transaction_number || '',
    date: s.date || '',
    customer_name: s.customer_name || '',
    product: s.product || '',
    quantity: s.quantity || '',
    unit_price: s.unit_price || '',
    total: s.total ?? '',
    debtor: s.debtor ?? '',
    creditor: s.creditor ?? '',
    notes: s.notes || '',
  }
  showForm.value = true
}

async function save() {
  const payload = {
    ...form.value,
    total:    form.value.total    !== '' ? parseFloat(form.value.total)    : null,
    debtor:   form.value.debtor   !== '' ? parseFloat(form.value.debtor)   : null,
    creditor: form.value.creditor !== '' ? parseFloat(form.value.creditor) : null,
    transaction_number: form.value.transaction_number !== '' ? parseInt(form.value.transaction_number) : null,
  }
  if (editingId.value) await salesApi.update(editingId.value, payload)
  else                  await salesApi.create(payload)
  showForm.value = false
  await load()
}

async function remove(id) {
  if (!confirm('تأكيد الحذف؟')) return
  await salesApi.remove(id)
  await load()
}

const visiblePages = computed(() => {
  const total = meta.value.last_page
  const cur = meta.value.current_page
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)
  const pages = new Set([1, total, cur, cur - 1, cur + 1, cur - 2, cur + 2])
  return [...pages].filter(p => p >= 1 && p <= total).sort((a, b) => a - b)
})

function fmt(val) {
  if (!val && val !== 0) return '-'
  return Number(val).toLocaleString('ar-EG')
}

onMounted(load)
</script>

<template>
  <div dir="rtl">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
      <div>
        <h2 class="text-xl font-bold text-gray-800">المبيعات</h2>
        <p class="text-sm text-gray-500 mt-0.5">
          إجمالي: <span class="font-bold" :class="meta.total_amount >= 0 ? 'text-green-600' : 'text-red-600'">{{ fmt(meta.total_amount) }} ج.م</span>
          &nbsp;|&nbsp; مدين: <span class="font-bold text-orange-600">{{ fmt(meta.total_debtor) }} ج.م</span>
          &nbsp;|&nbsp; دائن: <span class="font-bold text-blue-600">{{ fmt(meta.total_creditor) }} ج.م</span>
          &nbsp;|&nbsp; {{ meta.count }} عملية
        </p>
      </div>
      <button @click="openAdd" class="admin-btn-primary">+ إضافة عملية</button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-center">
      <input v-model="search" placeholder="بحث باسم العميل أو المنتج" class="border rounded-lg px-3 py-2 text-sm flex-1 min-w-40" />
      <input v-model="dateFrom" type="date" class="border rounded-lg px-3 py-2 text-sm" />
      <input v-model="dateTo" type="date" class="border rounded-lg px-3 py-2 text-sm" />

      <!-- Debtor filter -->
      <div class="flex items-center gap-1">
        <span class="text-xs text-orange-700 font-medium whitespace-nowrap">المدين:</span>
        <button
          v-for="opt in [{v:'', label:'الكل'}, {v:'has', label:'عنده'}, {v:'none', label:'ما عندوش'}]"
          :key="opt.v"
          @click="debtorFilter = opt.v"
          :class="['px-2 py-1 rounded-lg text-xs font-medium transition-all',
            debtorFilter === opt.v ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-orange-100']"
        >{{ opt.label }}</button>
      </div>

      <!-- Creditor filter -->
      <div class="flex items-center gap-1">
        <span class="text-xs text-blue-700 font-medium whitespace-nowrap">الدائن:</span>
        <button
          v-for="opt in [{v:'', label:'الكل'}, {v:'has', label:'عنده'}, {v:'none', label:'ما عندوش'}]"
          :key="opt.v"
          @click="creditorFilter = opt.v"
          :class="['px-2 py-1 rounded-lg text-xs font-medium transition-all',
            creditorFilter === opt.v ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-blue-100']"
        >{{ opt.label }}</button>
      </div>

      <button @click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap">مسح الكل</button>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-gray-400">جاري التحميل...</div>
      <div v-else class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b">
            <tr>
              <th class="px-3 py-3 text-right text-gray-600 font-medium">#</th>
              <th class="px-3 py-3 text-right text-gray-600 font-medium">التاريخ</th>
              <th class="px-3 py-3 text-right text-gray-600 font-medium">العميل</th>
              <th class="px-3 py-3 text-right text-gray-600 font-medium">المنتج</th>
              <th class="px-3 py-3 text-right text-gray-600 font-medium">الكمية</th>
              <th class="px-3 py-3 text-right text-gray-600 font-medium">سعر الوحدة</th>
              <th class="px-3 py-3 text-right text-gray-600 font-medium">الإجمالي</th>
              <th class="px-3 py-3 text-right text-orange-700 font-medium">المدين</th>
              <th class="px-3 py-3 text-right text-blue-700 font-medium">الدائن</th>
              <th class="px-3 py-3 text-right text-gray-600 font-medium">ملاحظات</th>
              <th class="px-3 py-3 text-right text-gray-600 font-medium">إجراءات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="s in sales" :key="s.id" class="hover:bg-gray-50">
              <td class="px-3 py-2 text-gray-500">{{ s.transaction_number }}</td>
              <td class="px-3 py-2 whitespace-nowrap">{{ s.date || '-' }}</td>
              <td class="px-3 py-2 font-medium text-gray-800">{{ s.customer_name || '-' }}</td>
              <td class="px-3 py-2 text-gray-700 max-w-52 truncate" :title="s.product">{{ s.product || '-' }}</td>
              <td class="px-3 py-2 text-center">{{ s.quantity || '-' }}</td>
              <td class="px-3 py-2 text-gray-500 text-xs max-w-24 truncate" :title="s.unit_price">{{ s.unit_price || '-' }}</td>
              <td class="px-3 py-2 font-semibold whitespace-nowrap" :class="(s.total || 0) < 0 ? 'text-red-600' : 'text-green-700'">
                {{ s.total ? fmt(s.total) : '-' }}
              </td>
              <td class="px-3 py-2 font-semibold text-orange-600 whitespace-nowrap">{{ s.debtor ? fmt(s.debtor) : '-' }}</td>
              <td class="px-3 py-2 font-semibold text-blue-600 whitespace-nowrap">{{ s.creditor ? fmt(s.creditor) : '-' }}</td>
              <td class="px-3 py-2 text-gray-500 text-xs max-w-28">{{ s.notes || '' }}</td>
              <td class="px-3 py-2">
                <div class="flex gap-1">
                  <button @click="openEdit(s)" class="admin-action-btn text-blue-600 hover:bg-blue-50">تعديل</button>
                  <button @click="remove(s.id)" class="admin-action-btn text-red-500 hover:bg-red-50">حذف</button>
                </div>
              </td>
            </tr>
            <tr v-if="!sales.length">
              <td colspan="11" class="px-4 py-8 text-center text-gray-400">لا توجد بيانات</td>
            </tr>
          </tbody>
          <!-- Totals footer (full filtered set, not just current page) -->
          <tfoot class="bg-gray-50 border-t-2 border-gray-300 font-bold">
            <tr>
              <td colspan="6" class="px-3 py-3 text-right text-gray-700">إجمالي النتائج ({{ meta.count }})</td>
              <td class="px-3 py-3" :class="meta.total_amount >= 0 ? 'text-green-700' : 'text-red-700'">{{ fmt(meta.total_amount) }}</td>
              <td class="px-3 py-3 text-orange-700">{{ fmt(meta.total_debtor) }}</td>
              <td class="px-3 py-3 text-blue-700">{{ fmt(meta.total_creditor) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-between gap-2 p-4 border-t">
        <span class="text-sm text-gray-500">
          صفحة {{ meta.current_page }} من {{ meta.last_page }}
        </span>
        <div class="flex gap-1 flex-wrap">
          <button
            @click="currentPage = 1" :disabled="currentPage === 1"
            class="px-2 py-1 rounded text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-30"
          >«</button>
          <button
            @click="currentPage--" :disabled="currentPage === 1"
            class="px-2 py-1 rounded text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-30"
          >‹</button>
          <button
            v-for="p in visiblePages" :key="p"
            @click="currentPage = p"
            :class="['w-8 h-8 rounded text-sm font-medium',
              currentPage === p ? 'bg-purple-600 text-white' : 'text-gray-600 hover:bg-gray-100']"
          >{{ p }}</button>
          <button
            @click="currentPage++" :disabled="currentPage === meta.last_page"
            class="px-2 py-1 rounded text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-30"
          >›</button>
          <button
            @click="currentPage = meta.last_page" :disabled="currentPage === meta.last_page"
            class="px-2 py-1 rounded text-sm text-gray-600 hover:bg-gray-100 disabled:opacity-30"
          >»</button>
        </div>
      </div>
    </div>

    <!-- Modal Form -->
    <div v-if="showForm" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-screen overflow-y-auto">
        <div class="p-6 border-b sticky top-0 bg-white">
          <h3 class="text-lg font-bold text-gray-800">{{ editingId ? 'تعديل عملية بيع' : 'إضافة عملية بيع' }}</h3>
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
            <label class="block text-sm text-gray-600 mb-1">اسم العميل</label>
            <input v-model="form.customer_name" class="w-full border rounded-lg px-3 py-2 text-sm" />
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
              <label class="block text-sm text-gray-600 mb-1">سعر الوحدة</label>
              <input v-model="form.unit_price" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="مثال: 130+130" />
            </div>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-sm text-gray-600 mb-1">الإجمالي</label>
              <input v-model="form.total" type="number" step="0.01" class="w-full border rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-orange-700 mb-1">المدين</label>
              <input v-model="form.debtor" type="number" step="0.01" class="w-full border border-orange-200 rounded-lg px-3 py-2 text-sm" />
            </div>
            <div>
              <label class="block text-sm font-medium text-blue-700 mb-1">الدائن</label>
              <input v-model="form.creditor" type="number" step="0.01" class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm" />
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

