const API_URL = import.meta.env.VITE_API_URL

// Get or generate browser ID for cart identification
function getBrowserId() {
  let browserId = localStorage.getItem('hoor_browser_id')
  if (!browserId) {
    browserId = 'browser_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
    localStorage.setItem('hoor_browser_id', browserId)
  }
  return browserId
}

export const cartApi = {
  // Get cart items
  async getAll() {
    const browserId = getBrowserId()
    const res = await fetch(`${API_URL}/cart/${browserId}`)
    return res.json()
  },

  // Add item to cart
  async addItem(item) {
    const browserId = getBrowserId()
    const res = await fetch(`${API_URL}/cart/${browserId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(item)
    })
    return res.json()
  },

  // Update cart item
  async updateItem(id, data) {
    const browserId = getBrowserId()
    const res = await fetch(`${API_URL}/cart/${browserId}/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    return res.json()
  },

  // Remove item from cart
  async removeItem(id) {
    const browserId = getBrowserId()
    const res = await fetch(`${API_URL}/cart/${browserId}/${id}`, {
      method: 'DELETE'
    })
    return res.json()
  },

  // Clear entire cart
  async clearCart() {
    const browserId = getBrowserId()
    const res = await fetch(`${API_URL}/cart/${browserId}`, {
      method: 'DELETE'
    })
    return res.json()
  }
}

// Orders API
export const ordersApi = {
  // Get all orders
  async getAll(status = null) {
    const url = status ? `${API_URL}/orders?status=${status}` : `${API_URL}/orders`
    const res = await fetch(url)
    return res.json()
  },

  // Get single order
  async getById(id) {
    const res = await fetch(`${API_URL}/orders/${id}`)
    return res.json()
  },

  // Get order by number
  async getByNumber(orderNumber) {
    const res = await fetch(`${API_URL}/orders/number/${orderNumber}`)
    return res.json()
  },

  // Create new order
  async create(orderData) {
    const res = await fetch(`${API_URL}/orders`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(orderData)
    })
    const data = await res.json()

    // Handle validation errors (422)
    if (!res.ok) {
      return {
        success: false,
        errors: data,
        status: res.status
      }
    }

    return data
  },

  // Update order status
  async update(id, data) {
    const res = await fetch(`${API_URL}/orders/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    })
    return res.json()
  },

  // Delete order
  async delete(id) {
    const res = await fetch(`${API_URL}/orders/${id}`, {
      method: 'DELETE'
    })
    return res.json()
  }
}

// Shipping API
export const shippingApi = {
  // Get all shipping zones with prices
  async getZones() {
    const res = await fetch(`${API_URL}/shipping/zones`)
    return res.json()
  },

  // Get all governorates with shipping prices
  async getGovernorates() {
    const res = await fetch(`${API_URL}/shipping/governorates`)
    return res.json()
  },

  // Get shipping price for specific governorate
  async getPrice(governorate) {
    const res = await fetch(`${API_URL}/shipping/price`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ governorate })
    })
    return res.json()
  }
}

// Coupons API
export const couponsApi = {
  // Validate coupon code
  async validate(code, subtotal) {
    const res = await fetch(`${API_URL}/coupons/validate`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ code, subtotal })
    })
    return res.json()
  },

  // Get all coupons (admin)
  async getAll() {
    const res = await fetch(`${API_URL}/coupons`)
    return res.json()
  },

  // Create new coupon (admin)
  async create(couponData) {
    const res = await fetch(`${API_URL}/coupons`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(couponData)
    })
    return res.json()
  },

  // Update coupon (admin)
  async update(id, couponData) {
    const res = await fetch(`${API_URL}/coupons/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(couponData)
    })
    return res.json()
  },

  // Delete coupon (admin)
  async delete(id) {
    const res = await fetch(`${API_URL}/coupons/${id}`, {
      method: 'DELETE'
    })
    return res.json()
  }
}
