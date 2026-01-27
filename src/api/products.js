const API_URL = 'http://localhost:3001/api'

export const productsApi = {
  // Get all products
  async getAll() {
    const res = await fetch(`${API_URL}/products`)
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
  // Upload image
  async uploadImage(file) {
    const formData = new FormData()
    formData.append('image', file)

    const res = await fetch(`${API_URL}/upload`, {
      method: 'POST',
      body: formData
    })
    return res.json()
  }
}
