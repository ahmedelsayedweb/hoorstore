const API_URL = import.meta.env.VITE_API_URL

export const productsApi = {
  // Get all products (no pagination - for admin)
  async getAll() {
    const res = await fetch(`${API_URL}/products`)
    return res.json()
  },

  // Get products with pagination
  async getPage(page = 1, perPage = 8, category = '',type = '') {
    console.log(category);
    const params = new URLSearchParams({ page, per_page: perPage })
    if (category && category !== 'all') params.append('category', category)
    if (type && type !== '') params.append('type', type)
    const res = await fetch(`${API_URL}/products?${params}`)
    return res.json()
  },

  // Get featured products
  async getFeatured() {
    const res = await fetch(`${API_URL}/products/featured`)
    return res.json()
  },

  // Get single product
  async getById(id) {
    const res = await fetch(`${API_URL}/products/${id}`)
    return res.json()
  },

  // Add new product
  async create(product) {
    const res = await fetch(`${API_URL}/products`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(product)
    })
    return res.json()
  },

  // Update product
  async update(id, product) {
    const res = await fetch(`${API_URL}/products/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(product)
    })
    return res.json()
  },

  // Delete product
  async delete(id) {
    const res = await fetch(`${API_URL}/products/${id}`, {
      method: 'DELETE'
    })
    return res.json()
  }
}

export const uploadApi = {
  // Upload image/video
  async uploadImage(file) {
    const formData = new FormData()
    formData.append('image', file)

    const res = await fetch(`${API_URL}/upload/image`, {
      method: 'POST',
      body: formData
    })
    return res.json()
  },

  // Cleanup unused files
  async cleanupUnused() {
    const res = await fetch(`${API_URL}/upload/cleanup`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    })
    return res.json()
  }
}
