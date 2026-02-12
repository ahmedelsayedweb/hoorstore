<script setup>
import { ref, onMounted } from 'vue'
import { migrateApi } from '../../api/cart'
import { uploadApi } from '../../api/products'

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
    await fetchMigrations()
  } catch (err) {
    migrationReport.value = { success: false, output: 'فشل في الاتصال بالسيرفر' }
    console.error(err)
  } finally {
    migrationRunning.value = false
  }
}

onMounted(() => {
  fetchMigrations()
})
</script>

<template>
  <div>
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
</template>
