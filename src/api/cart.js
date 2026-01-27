const CART_STORAGE_KEY = 'hoor_cart'

// Get cart from localStorage
function getCartFromStorage() {
  const stored = localStorage.getItem(CART_STORAGE_KEY)
  if (stored) {
    return JSON.parse(stored)
  }
  return []
}

// Save cart to localStorage
function saveCartToStorage(cart) {
  localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart))
}

export const cartApi = {
  // Get cart items
  async getAll() {
    return getCartFromStorage()
  },

  // Add item to cart
  async addItem(item) {
    const cart = getCartFromStorage()
    const { productId, name, price, image, color, size, height, quantity = 1 } = item

    // Check if item already in cart (same product + options)
    const existingIndex = cart.findIndex(cartItem =>
      cartItem.productId === productId &&
      cartItem.color === color &&
      cartItem.size === size &&
      cartItem.height === height
    )

    if (existingIndex !== -1) {
      cart[existingIndex].quantity += quantity
    } else {
      cart.push({
        id: cart.length > 0 ? Math.max(...cart.map(i => i.id)) + 1 : 1,
        productId,
        name,
        price,
        image,
        color,
        size,
        height,
        quantity,
        addedAt: new Date().toISOString()
      })
    }

    saveCartToStorage(cart)
    return cart
  },

  // Update cart item
  async updateItem(id, data) {
    const cart = getCartFromStorage()
    const index = cart.findIndex(item => item.id === parseInt(id))

    if (index === -1) {
      throw new Error('Cart item not found')
    }

    cart[index] = {
      ...cart[index],
      ...data,
      id: parseInt(id)
    }
    saveCartToStorage(cart)
    return cart[index]
  },

  // Remove item from cart
  async removeItem(id) {
    const cart = getCartFromStorage()
    const index = cart.findIndex(item => item.id === parseInt(id))

    if (index === -1) {
      throw new Error('Cart item not found')
    }

    const deleted = cart.splice(index, 1)
    saveCartToStorage(cart)
    return { message: 'Item removed from cart', item: deleted[0] }
  },

  // Clear entire cart
  async clearCart() {
    saveCartToStorage([])
    return { message: 'Cart cleared' }
  }
}
