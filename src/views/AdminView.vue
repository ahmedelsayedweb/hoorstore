<script setup>
import { ref, onMounted, computed } from 'vue'
import { productsApi, uploadApi } from '../api/products'

// Products list
const products = ref([])
const loading = ref(true)
const error = ref(null)

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

// Form data
const form = ref({
  name: '',
  price: '',
  salePrice: '',
  category: '',
  image: '',
  images: [],
  sizes: [],
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
    description: '',
    inStock: true
  }
  editingProduct.value = null
  imageFile.value = null
  imagePreview.value = null
  albumImages.value = []
  albumPreviews.value = []
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
    description: product.description || '',
    inStock: product.inStock !== false
  }
  imageFile.value = null
  imagePreview.value = product.image || null
  albumImages.value = []
  albumPreviews.value = []
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
        <p class="text-gray-600 mt-1">إدارة المنتجات</p>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
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
                      :src="product.image?.startsWith('/uploads') ? `http://localhost:3001${product.image}` : product.image"
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
                  {{ product.price?.toFixed(2) }} ج.م
                </div>
                <div v-if="product.salePrice" class="text-sm text-green-600">
                  تخفيض: {{ product.salePrice?.toFixed(2) }} ج.م
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
    </main>

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
                :src="imagePreview.startsWith('blob:') ? imagePreview : `http://localhost:3001${imagePreview}`"
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
                    :src="img.startsWith('/uploads') ? `http://localhost:3001${img}` : img"
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
