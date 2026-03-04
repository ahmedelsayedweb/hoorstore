<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { productsApi, uploadApi } from '../../api/products'

// Helper to check if a URL is a video
const videoExtensions = ['mp4', 'mov', 'avi', 'wmv', 'webm']
const isVideo = (url) => {
  if (!url) return false
  const ext = url.split('.').pop()?.toLowerCase().split('?')[0]
  return videoExtensions.includes(ext)
}

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
const imageInputMode = ref('upload')
const imageLink = ref('')

// Description editor
const descriptionEditor = ref(null)

const execCmd = (command) => {
  document.execCommand(command, false, null)
  descriptionEditor.value?.focus()
}

const execCreateLink = () => {
  const url = prompt('أدخل الرابط:')
  if (url) {
    document.execCommand('createLink', false, url)
    descriptionEditor.value?.focus()
  }
}

const onDescriptionInput = () => {
  console.log(form.value);  
  form.value.description = descriptionEditor.value?.innerHTML || ''
}

const syncDescriptionEditor = () => {
  if (descriptionEditor.value) {
    descriptionEditor.value.innerHTML = form.value.description || ''
  }
}

// Album images state
const albumImages = ref([])
const albumPreviews = ref([])
const albumInputMode = ref('upload')
const albumLinkInput = ref('')
const albumLinks = ref([])

// Size input
const newSize = ref('')
const newHeight = ref('')
const newWeight = ref('')

// Color input with image support
const newColorName = ref('')
const newColorImageFile = ref(null)
const newColorImagePreview = ref(null)

// Variant mode
const useVariants = ref(true)
const variantWithColors = ref(true)
const variantColors = ref([])
const variantSizesOnly = ref([])
const newVariantColorName = ref('')
const newVariantColorImageFile = ref(null)
const newVariantColorImagePreview = ref(null)
const newVariantSizeInputs = ref({})
const newVariantSizeOnlyInput = ref({ size: '', quantity: '' })

// Form data
const form = ref({
  code: '',
  name: '',
  price: '',
  salePrice: '',
  category: [],
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
  { value: 'children', label: 'الاطفال' },
  { value: 'men', label: 'رجالي' },
  { value: 'woman', label: 'حريمي' },
]

// Get category label
const getCategoryLabel = (value) => {
  if (Array.isArray(value)) {
    return value.map(v => {
      const cat = categories.find(c => c.value === v)
      return cat ? cat.label : v
    }).join(', ')
  }
  const cat = categories.find(c => c.value === value)
  return cat ? cat.label : value
}

// Pagination
const currentPage = ref(1)
const perPage = ref(10)
const totalPages = ref(1)
const totalProducts = ref(0)

const goToPage = async (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    await fetchProducts()
  }
}

// Fetch products with backend pagination
const fetchProducts = async () => {
  try {
    loading.value = true
    error.value = null
    const res = await productsApi.getPage(currentPage.value, perPage.value)
    products.value = res.data
    totalPages.value = res.last_page
    totalProducts.value = res.total
    // If current page exceeds last page (e.g. after delete), go to last page
    if (currentPage.value > res.last_page && res.last_page > 0) {
      currentPage.value = res.last_page
      await fetchProducts()
    }
  } catch (err) {
    error.value = 'فشل في تحميل المنتجات'
    console.error(err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchProducts()
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

// Add album link
const addAlbumLink = () => {
  const link = albumLinkInput.value.trim()
  if (link) {
    albumLinks.value.push(link)
    albumLinkInput.value = ''
  }
}

// Remove album link
const removeAlbumLink = (index) => {
  albumLinks.value.splice(index, 1)
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

  const exists = form.value.colors.some(c =>
    (typeof c === 'object' ? c.name : c) === colorName
  )
  if (exists) {
    alert('هذا اللون موجود بالفعل')
    return
  }

  let colorImage = ''

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

  form.value.colors.push({
    name: colorName,
    image: colorImage
  })

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

// Handle variant color image file selection
const handleVariantColorImageSelect = (event) => {
  const file = event.target.files[0]
  if (file) {
    newVariantColorImageFile.value = file
    newVariantColorImagePreview.value = URL.createObjectURL(file)
  }
}

// Add variant color
const addVariantColor = async () => {
  if (!newVariantColorName.value.trim()) return
  const colorName = newVariantColorName.value.trim()

  if (variantColors.value.some(v => v.color === colorName)) {
    alert('هذا اللون موجود بالفعل')
    return
  }

  let colorImage = ''
  if (newVariantColorImageFile.value) {
    try {
      const uploadResult = await uploadApi.uploadImage(newVariantColorImageFile.value)
      if (uploadResult.error) throw new Error(uploadResult.error)
      colorImage = uploadResult.url
    } catch (err) {
      console.error('Failed to upload variant color image:', err)
      alert('فشل في رفع صورة اللون')
      return
    }
  }

  variantColors.value.push({
    color: colorName,
    image: colorImage,
    sizes: []
  })

  newVariantColorName.value = ''
  newVariantColorImageFile.value = null
  newVariantColorImagePreview.value = null
}

// Remove variant color
const removeVariantColor = (index) => {
  variantColors.value.splice(index, 1)
  delete newVariantSizeInputs.value[index]
}

// Add size to a variant color
const addVariantSize = (colorIndex) => {
  const input = newVariantSizeInputs.value[colorIndex]
  if (!input?.size?.trim()) return

  const variant = variantColors.value[colorIndex]
  if (variant.sizes.some(s => s.size === input.size.trim())) {
    alert('هذا المقاس موجود بالفعل لهذا اللون')
    return
  }

  variant.sizes.push({
    size: input.size.trim(),
    quantity: parseInt(input.quantity) || 0
  })

  newVariantSizeInputs.value[colorIndex] = { size: '', quantity: '' }
}

// Remove size from a variant color
const removeVariantSize = (colorIndex, sizeIndex) => {
  variantColors.value[colorIndex].sizes.splice(sizeIndex, 1)
}

// Move variant color up/down
const moveVariantColor = (index, direction) => {
  const newIndex = index + direction
  if (newIndex < 0 || newIndex >= variantColors.value.length) return
  const arr = variantColors.value
  ;[arr[index], arr[newIndex]] = [arr[newIndex], arr[index]]
}

// Move variant size up/down within a color
const moveVariantSize = (colorIndex, sizeIndex, direction) => {
  const sizes = variantColors.value[colorIndex].sizes
  const newIndex = sizeIndex + direction
  if (newIndex < 0 || newIndex >= sizes.length) return
  ;[sizes[sizeIndex], sizes[newIndex]] = [sizes[newIndex], sizes[sizeIndex]]
}

// Move size-only variant up/down
const moveVariantSizeOnly = (index, direction) => {
  const newIndex = index + direction
  if (newIndex < 0 || newIndex >= variantSizesOnly.value.length) return
  const arr = variantSizesOnly.value
  ;[arr[index], arr[newIndex]] = [arr[newIndex], arr[index]]
}

// Handle editing variant color image
const handleEditVariantColorImage = async (event, colorIndex) => {
  const file = event.target.files[0]
  if (!file) return
  try {
    const uploadResult = await uploadApi.uploadImage(file)
    if (uploadResult.error) throw new Error(uploadResult.error)
    variantColors.value[colorIndex].image = uploadResult.url
  } catch (err) {
    console.error('Failed to upload color image:', err)
    alert('فشل في رفع صورة اللون')
  }
}

// Add size in sizes-only mode
const addVariantSizeOnly = () => {
  const input = newVariantSizeOnlyInput.value
  if (!input.size?.trim()) return
  if (variantSizesOnly.value.some(s => s.size === input.size.trim())) {
    alert('هذا المقاس موجود بالفعل')
    return
  }
  variantSizesOnly.value.push({
    size: input.size.trim(),
    quantity: parseInt(input.quantity) || 0
  })
  newVariantSizeOnlyInput.value = { size: '', quantity: '' }
}

// Remove size in sizes-only mode
const removeVariantSizeOnly = (index) => {
  variantSizesOnly.value.splice(index, 1)
}

// Reset form
const resetForm = () => {
  form.value = {
    code: '',
    name: '',
    price: '',
    salePrice: '',
    category: [],
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
  imageInputMode.value = 'upload'
  imageLink.value = ''
  albumImages.value = []
  albumPreviews.value = []
  albumInputMode.value = 'upload'
  albumLinkInput.value = ''
  albumLinks.value = []
  newSize.value = ''
  newColorName.value = ''
  newColorImageFile.value = null
  newColorImagePreview.value = null
  newHeight.value = ''
  newWeight.value = ''
  useVariants.value = true
  variantWithColors.value = true
  variantColors.value = []
  variantSizesOnly.value = []
  newVariantColorName.value = ''
  newVariantColorImageFile.value = null
  newVariantColorImagePreview.value = null
  newVariantSizeInputs.value = {}
  newVariantSizeOnlyInput.value = { size: '', quantity: '' }
}

// Open form for new product
const openNewForm = () => {
  resetForm()
  showForm.value = true
  nextTick(syncDescriptionEditor)
}

// Open form for editing
const openEditForm = (product) => {
  editingProduct.value = product

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
    category: Array.isArray(product.category) ? [...product.category] : (product.category ? [product.category] : []),
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

  if (product.variants && Array.isArray(product.variants) && product.variants.length > 0) {
    useVariants.value = true
    const isSizesOnly = product.variants.length === 1 && !product.variants[0].color
    if (isSizesOnly) {
      variantWithColors.value = false
      variantSizesOnly.value = (product.variants[0].sizes || []).map(s => ({ size: s.size, quantity: s.quantity || 0 }))
      variantColors.value = []
    } else {
      variantWithColors.value = true
      variantColors.value = product.variants.map(v => ({
        color: v.color || '',
        image: v.image || '',
        sizes: (v.sizes || []).map(s => ({ size: s.size, quantity: s.quantity || 0 }))
      }))
      variantSizesOnly.value = []
    }
  } else {
    useVariants.value = true
    variantWithColors.value = true
    variantColors.value = []
    variantSizesOnly.value = []
  }
  newVariantColorName.value = ''
  newVariantColorImageFile.value = null
  newVariantColorImagePreview.value = null
  newVariantSizeInputs.value = {}
  newVariantSizeOnlyInput.value = { size: '', quantity: '' }

  showForm.value = true
  nextTick(syncDescriptionEditor)
}

// Close form
const closeForm = () => {
  showForm.value = false
  resetForm()
}

// Save product (create or update)
const saveProduct = async () => {
  if (form.value.category.length === 0) {
    error.value = 'يرجى اختيار قسم واحد على الأقل'
    return
  }
  try {
    saving.value = true
    uploading.value = true
    error.value = null

    let imageUrl = form.value.image

    if (imageInputMode.value === 'link' && imageLink.value) {
      imageUrl = imageLink.value
    }
    else if (imageFile.value) {
      const uploadResult = await uploadApi.uploadImage(imageFile.value)
      if (uploadResult.error) {
        throw new Error(uploadResult.error)
      }
      imageUrl = uploadResult.url
    }

    const uploadedAlbumImages = [...form.value.images]
    for (const file of albumImages.value) {
      const uploadResult = await uploadApi.uploadImage(file)
      if (uploadResult.error) {
        throw new Error(uploadResult.error)
      }
      uploadedAlbumImages.push(uploadResult.url)
    }
    for (const link of albumLinks.value) {
      uploadedAlbumImages.push(link)
    }

    const productData = {
      code: form.value.code,
      name: form.value.name,
      price: parseFloat(form.value.price) || 0,
      salePrice: form.value.salePrice ? parseFloat(form.value.salePrice) : null,
      category: form.value.category,
      image: imageUrl,
      images: uploadedAlbumImages,
      sizes: useVariants.value ? [] : form.value.sizes,
      colors: useVariants.value ? [] : form.value.colors,
      heights: form.value.heights,
      weights: form.value.weights,
      variants: useVariants.value
        ? (variantWithColors.value
          ? variantColors.value.map(v => ({
              color: v.color,
              image: v.image,
              sizes: v.sizes.map(s => ({ size: s.size, quantity: s.quantity }))
            }))
          : [{ color: '', image: '', sizes: variantSizesOnly.value.map(s => ({ size: s.size, quantity: s.quantity })) }]
        )
        : null,
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

defineExpose({ products })
</script>

<template>
  <div>
    <!-- Error Message -->
    <div v-if="error" class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
      {{ error }}
    </div>

    <!-- Actions Bar -->
    <div class="mb-5 flex justify-between items-center">
      <h2 class="text-lg sm:text-xl font-bold text-gray-800">
        المنتجات <span class="text-gray-400 font-normal">({{ totalProducts }})</span>
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

    <!-- Pagination -->
    <div v-if="totalPages > 1" class="flex flex-wrap items-center justify-between gap-3 mt-4 px-2">
      <span class="text-sm text-gray-500">
        عرض {{ (currentPage - 1) * perPage + 1 }}-{{ Math.min(currentPage * perPage, totalProducts) }} من {{ totalProducts }} منتج
      </span>
      <div class="flex items-center gap-1">
        <button
          @click="goToPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          السابق
        </button>
        <template v-for="page in totalPages" :key="page">
          <button
            v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
            @click="goToPage(page)"
            :class="[
              'px-3 py-1.5 text-sm rounded-lg border',
              page === currentPage
                ? 'bg-[#5B3A8C] text-white border-[#5B3A8C]'
                : 'border-gray-300 hover:bg-gray-50'
            ]"
          >
            {{ page }}
          </button>
          <span
            v-else-if="page === currentPage - 2 || page === currentPage + 2"
            class="px-1 text-gray-400"
          >...</span>
        </template>
        <button
          @click="goToPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          التالي
        </button>
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

          <!-- Category (Multi-select) -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              القسم * <span class="text-xs text-gray-400">(يمكن اختيار أكثر من قسم)</span>
            </label>
            <div class="flex flex-wrap gap-3 p-3 border border-gray-300 rounded">
              <label
                v-for="cat in categories"
                :key="cat.value"
                class="flex items-center gap-2 cursor-pointer select-none px-3 py-1.5 rounded-full border transition-colors"
                :class="form.category.includes(cat.value) ? 'bg-purple-100 border-purple-500 text-purple-700' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100'"
              >
                <input
                  type="checkbox"
                  :value="cat.value"
                  v-model="form.category"
                  class="hidden"
                />
                <span class="text-sm font-medium">{{ cat.label }}</span>
              </label>
            </div>
            <p v-if="form.category.length === 0" class="text-xs text-red-500 mt-1">يرجى اختيار قسم واحد على الأقل</p>
          </div>

          <!-- Image Upload -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              صورة/فيديو المنتج الرئيسي {{ editingProduct ? '' : '*' }}
            </label>
            <div class="flex gap-2 mb-2">
              <button
                type="button"
                @click="imageInputMode = 'upload'"
                :class="[
                  'px-3 py-1 rounded text-sm font-medium transition-all',
                  imageInputMode === 'upload'
                    ? 'bg-purple-600 text-white'
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                ]"
              >
                رفع ملف
              </button>
              <button
                type="button"
                @click="imageInputMode = 'link'"
                :class="[
                  'px-3 py-1 rounded text-sm font-medium transition-all',
                  imageInputMode === 'link'
                    ? 'bg-purple-600 text-white'
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                ]"
              >
                لينك مباشر
              </button>
            </div>
            <input
              v-if="imageInputMode === 'upload'"
              type="file"
              accept="image/*,video/mp4,video/webm,video/quicktime,video/avi,video/x-ms-wmv"
              @change="handleFileSelect"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            />
            <input
              v-else
              type="url"
              v-model="imageLink"
              placeholder="الصق لينك الصورة أو الفيديو هنا"
              dir="ltr"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            />
            <div v-if="imagePreview || imageLink" class="mt-3">
              <video
                v-if="isVideo(imageFile?.name || imagePreview || imageLink)"
                :src="imageInputMode === 'link' ? imageLink : imagePreview"
                class="h-32 w-32 object-cover rounded border"
                muted
                playsinline
                controls
              />
              <img
                v-else
                :src="imageInputMode === 'link' ? imageLink : imagePreview"
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
            <div class="flex gap-2 mb-2">
              <button
                type="button"
                @click="albumInputMode = 'upload'"
                :class="[
                  'px-3 py-1 rounded text-sm font-medium transition-all',
                  albumInputMode === 'upload'
                    ? 'bg-purple-600 text-white'
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                ]"
              >
                رفع ملفات
              </button>
              <button
                type="button"
                @click="albumInputMode = 'link'"
                :class="[
                  'px-3 py-1 rounded text-sm font-medium transition-all',
                  albumInputMode === 'link'
                    ? 'bg-purple-600 text-white'
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                ]"
              >
                لينكات مباشرة
              </button>
            </div>
            <input
              v-if="albumInputMode === 'upload'"
              type="file"
              accept="image/*,video/mp4,video/webm,video/quicktime,video/avi,video/x-ms-wmv"
              multiple
              @change="handleAlbumFileSelect"
              class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
            />
            <div v-else class="flex gap-2">
              <input
                type="url"
                v-model="albumLinkInput"
                placeholder="الصق لينك الصورة أو الفيديو هنا"
                dir="ltr"
                class="flex-1 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                @keyup.enter.prevent="addAlbumLink"
              />
              <button
                type="button"
                @click="addAlbumLink"
                class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 text-sm font-medium"
              >
                + إضافة
              </button>
            </div>
            <!-- Album Links Preview -->
            <div v-if="albumLinks.length > 0" class="mt-3">
              <p class="text-sm text-gray-600 mb-2">لينكات مضافة:</p>
              <div class="flex flex-wrap gap-2">
                <div v-for="(link, index) in albumLinks" :key="'link-' + index" class="relative">
                  <video
                    v-if="isVideo(link)"
                    :src="link"
                    class="h-20 w-20 object-cover rounded border"
                    muted
                    playsinline
                  />
                  <img
                    v-else
                    :src="link"
                    alt="لينك ألبوم"
                    class="h-20 w-20 object-cover rounded border"
                  />
                  <button
                    type="button"
                    @click="removeAlbumLink(index)"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
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

          <!-- Sizes & Colors Mode Selection -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">المقاسات والكميات</label>
            <div class="flex gap-4">
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" :value="true" v-model="variantWithColors" class="text-purple-600 focus:ring-purple-500" />
                <span class="text-sm text-gray-700">ألوان + مقاسات + كميات</span>
              </label>
              <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" :value="false" v-model="variantWithColors" class="text-purple-600 focus:ring-purple-500" />
                <span class="text-sm text-gray-700">مقاسات + كميات فقط (بدون ألوان)</span>
              </label>
            </div>

            <!-- WITH COLORS MODE -->
            <div v-if="variantWithColors">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                إدارة المتغيرات (لون + مقاسات + كميات)
              </label>

              <div class="border border-gray-300 rounded p-4 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">اسم اللون</label>
                    <input
                      v-model="newVariantColorName"
                      type="text"
                      class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                      placeholder="مثال: أسود"
                      @keyup.enter.prevent="addVariantColor"
                    />
                  </div>
                  <div>
                    <label class="block text-xs text-gray-500 mb-1">صورة اللون (اختياري)</label>
                    <input
                      type="file"
                      accept="image/*"
                      @change="handleVariantColorImageSelect"
                      class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    />
                  </div>
                  <button
                    type="button"
                    @click="addVariantColor"
                    class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition-colors"
                  >
                    + إضافة لون
                  </button>
                </div>
                <div v-if="newVariantColorImagePreview" class="mt-2">
                  <img :src="newVariantColorImagePreview" alt="معاينة" class="h-16 w-16 object-cover rounded border" />
                </div>
              </div>

              <!-- Variant Colors List -->
              <div v-if="variantColors.length > 0" class="mt-4 space-y-4">
                <div
                  v-for="(variant, cIdx) in variantColors"
                  :key="cIdx"
                  class="border border-gray-200 rounded-lg overflow-hidden"
                >
                  <div class="flex items-center gap-3 p-4 bg-gray-50 border-b">
                    <div class="relative group">
                      <img v-if="variant.image" :src="variant.image" class="w-10 h-10 object-cover rounded border cursor-pointer" @click="$refs['colorImageInput-' + cIdx]?.[0]?.click()" />
                      <div v-else class="w-10 h-10 bg-gray-200 rounded border flex items-center justify-center text-xs text-gray-400 cursor-pointer" @click="$refs['colorImageInput-' + cIdx]?.[0]?.click()">بدون</div>
                      <div class="absolute inset-0 bg-black bg-opacity-40 rounded flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer" @click="$refs['colorImageInput-' + cIdx]?.[0]?.click()">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                      </div>
                      <input type="file" accept="image/*" :ref="'colorImageInput-' + cIdx" class="hidden" @change="handleEditVariantColorImage($event, cIdx)" />
                    </div>
                    <input type="text" v-model="variant.color" class="font-bold text-gray-900 flex-1 bg-transparent border-b border-transparent hover:border-gray-300 focus:border-purple-500 focus:outline-none px-1 py-0.5 transition-colors" />
                    <span class="text-xs text-gray-500 bg-gray-200 px-2 py-1 rounded-full">{{ variant.sizes.length }} مقاس</span>
                    <div class="flex items-center gap-1">
                      <button type="button" @click="moveVariantColor(cIdx, -1)" :disabled="cIdx === 0" class="p-1 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded disabled:opacity-30 disabled:cursor-not-allowed" title="تحريك لأعلى">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                      </button>
                      <button type="button" @click="moveVariantColor(cIdx, 1)" :disabled="cIdx === variantColors.length - 1" class="p-1 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded disabled:opacity-30 disabled:cursor-not-allowed" title="تحريك لأسفل">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                      </button>
                      <button type="button" @click="removeVariantColor(cIdx)" class="p-1 text-red-500 hover:text-red-700 hover:bg-red-50 rounded">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                      </button>
                    </div>
                  </div>
                  <div class="p-4">
                    <table v-if="variant.sizes.length > 0" class="w-full text-sm mb-3">
                      <thead>
                        <tr class="text-gray-500 text-xs">
                          <th class="text-right pb-2">المقاس</th>
                          <th class="text-right pb-2">الكمية</th>
                          <th class="pb-2 w-20"></th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="(s, sIdx) in variant.sizes" :key="sIdx" class="border-t border-gray-100">
                          <td class="py-2">
                            <input type="text" v-model="s.size" class="w-full px-2 py-1 border border-gray-300 rounded text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                          </td>
                          <td class="py-2">
                            <input type="number" v-model.number="s.quantity" min="0" class="w-24 px-2 py-1 border border-gray-300 rounded text-center focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                          </td>
                          <td class="py-2">
                            <div class="flex items-center gap-0.5 justify-center">
                              <button type="button" @click="moveVariantSize(cIdx, sIdx, -1)" :disabled="sIdx === 0" class="p-0.5 text-gray-400 hover:text-purple-600 rounded disabled:opacity-30 disabled:cursor-not-allowed" title="تحريك لأعلى">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                              </button>
                              <button type="button" @click="moveVariantSize(cIdx, sIdx, 1)" :disabled="sIdx === variant.sizes.length - 1" class="p-0.5 text-gray-400 hover:text-purple-600 rounded disabled:opacity-30 disabled:cursor-not-allowed" title="تحريك لأسفل">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                              </button>
                              <button type="button" @click="removeVariantSize(cIdx, sIdx)" class="text-red-400 hover:text-red-600 text-lg mr-1">×</button>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                    <div class="flex gap-2 items-end">
                      <div class="flex-1">
                        <label class="block text-xs text-gray-500 mb-1">المقاس</label>
                        <input
                          :value="newVariantSizeInputs[cIdx]?.size || ''"
                          @input="if (!newVariantSizeInputs[cIdx]) newVariantSizeInputs[cIdx] = { size: '', quantity: '' }; newVariantSizeInputs[cIdx].size = $event.target.value"
                          type="text" placeholder="مثال: M"
                          class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                          @keyup.enter.prevent="addVariantSize(cIdx)"
                        />
                      </div>
                      <div class="w-24">
                        <label class="block text-xs text-gray-500 mb-1">الكمية</label>
                        <input
                          :value="newVariantSizeInputs[cIdx]?.quantity || ''"
                          @input="if (!newVariantSizeInputs[cIdx]) newVariantSizeInputs[cIdx] = { size: '', quantity: '' }; newVariantSizeInputs[cIdx].quantity = $event.target.value"
                          type="number" placeholder="0" min="0"
                          class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm text-center focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                          @keyup.enter.prevent="addVariantSize(cIdx)"
                        />
                      </div>
                      <button type="button" @click="addVariantSize(cIdx)" class="px-3 py-1.5 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition-colors">+ مقاس</button>
                    </div>
                  </div>
                </div>
              </div>
              <p v-else class="mt-3 text-sm text-gray-500 text-center py-4 border border-dashed border-gray-300 rounded-lg">
                أضف ألوان لبدء إدارة المتغيرات
              </p>
            </div>

            <!-- SIZES ONLY MODE (no colors) -->
            <div v-else>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                المقاسات والكميات
              </label>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="p-4">
                  <table v-if="variantSizesOnly.length > 0" class="w-full text-sm mb-3">
                    <thead>
                      <tr class="text-gray-500 text-xs">
                        <th class="text-right pb-2">المقاس</th>
                        <th class="text-right pb-2">الكمية</th>
                        <th class="pb-2 w-20"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(s, sIdx) in variantSizesOnly" :key="sIdx" class="border-t border-gray-100">
                        <td class="py-2">
                          <input type="text" v-model="s.size" class="w-full px-2 py-1 border border-gray-300 rounded text-sm font-medium focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                        </td>
                        <td class="py-2">
                          <input type="number" v-model.number="s.quantity" min="0" class="w-24 px-2 py-1 border border-gray-300 rounded text-center focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                        </td>
                        <td class="py-2">
                          <div class="flex items-center gap-0.5 justify-center">
                            <button type="button" @click="moveVariantSizeOnly(sIdx, -1)" :disabled="sIdx === 0" class="p-0.5 text-gray-400 hover:text-purple-600 rounded disabled:opacity-30 disabled:cursor-not-allowed" title="تحريك لأعلى">
                              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" /></svg>
                            </button>
                            <button type="button" @click="moveVariantSizeOnly(sIdx, 1)" :disabled="sIdx === variantSizesOnly.length - 1" class="p-0.5 text-gray-400 hover:text-purple-600 rounded disabled:opacity-30 disabled:cursor-not-allowed" title="تحريك لأسفل">
                              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <button type="button" @click="removeVariantSizeOnly(sIdx)" class="text-red-400 hover:text-red-600 text-lg mr-1">×</button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <div class="flex gap-2 items-end">
                    <div class="flex-1">
                      <label class="block text-xs text-gray-500 mb-1">المقاس</label>
                      <input
                        v-model="newVariantSizeOnlyInput.size"
                        type="text" placeholder="مثال: M"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        @keyup.enter.prevent="addVariantSizeOnly"
                      />
                    </div>
                    <div class="w-24">
                      <label class="block text-xs text-gray-500 mb-1">الكمية</label>
                      <input
                        v-model="newVariantSizeOnlyInput.quantity"
                        type="number" placeholder="0" min="0"
                        class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm text-center focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                        @keyup.enter.prevent="addVariantSizeOnly"
                      />
                    </div>
                    <button type="button" @click="addVariantSizeOnly" class="px-3 py-1.5 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition-colors">+ مقاس</button>
                  </div>
                </div>
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
            <!-- Toolbar -->
            <div class="flex flex-wrap gap-1 p-2 border border-gray-300 rounded-t bg-gray-50">
              <button type="button" @click="execCmd('bold')" class="px-2 py-1 text-sm font-bold border border-gray-300 rounded hover:bg-gray-200" title="Bold">B</button>
              <button type="button" @click="execCmd('italic')" class="px-2 py-1 text-sm italic border border-gray-300 rounded hover:bg-gray-200" title="Italic">I</button>
              <button type="button" @click="execCmd('underline')" class="px-2 py-1 text-sm underline border border-gray-300 rounded hover:bg-gray-200" title="Underline">U</button>
              <span class="w-px bg-gray-300 mx-1"></span>
              <button type="button" @click="execCmd('insertUnorderedList')" class="px-2 py-1 text-sm border border-gray-300 rounded hover:bg-gray-200" title="Bullet List">&#8226; List</button>
              <button type="button" @click="execCmd('insertOrderedList')" class="px-2 py-1 text-sm border border-gray-300 rounded hover:bg-gray-200" title="Numbered List">1. List</button>
              <span class="w-px bg-gray-300 mx-1"></span>
              <button type="button" @click="execCreateLink" class="px-2 py-1 text-sm border border-gray-300 rounded hover:bg-gray-200" title="Link">&#128279;</button>
              <button type="button" @click="execCmd('removeFormat')" class="px-2 py-1 text-sm border border-gray-300 rounded hover:bg-gray-200" title="Clear Formatting">&#10005;</button>
            </div>
            <!-- Editable Area -->
            <div
              ref="descriptionEditor"
              contenteditable="true"
              @input="onDescriptionInput"
              class="w-full min-h-[100px] px-3 py-2 border border-t-0 border-gray-300 rounded-b focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent prose prose-sm max-w-none"
              dir="rtl"
              :data-placeholder="'أدخل وصف المنتج'"
            ></div>
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
