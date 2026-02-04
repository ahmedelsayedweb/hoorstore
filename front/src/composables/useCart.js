import { ref, computed } from 'vue'
import { cartApi } from '@/api/cart'

const cartItems = ref([])
const isCartOpen = ref(false)

// Load cart from API on startup
async function loadCart() {
  try {
    const items = await cartApi.getAll()
    cartItems.value = items
  } catch (error) {
    console.error('Failed to load cart:', error)
  }
}

// Initialize cart
loadCart()

// Helper to compare arrays
const arraysEqual = (a, b) => {
  if (!Array.isArray(a) || !Array.isArray(b)) return a === b
  if (a.length !== b.length) return false
  return a.every((val, idx) => val === b[idx])
}

export function useCart() {
  const addToCart = async (product) => {
    try {
      // Ensure cartItems is an array
      if (!Array.isArray(cartItems.value)) {
        cartItems.value = []
      }

      const existingItem = cartItems.value.find(
        item =>
          item.productId === product.id &&
          item.color === product.color &&
          arraysEqual(item.sizes, product.sizes) &&
          item.height === product.height
      )

      if (existingItem) {
        existingItem.quantity += product.quantity
        // Update in API
        await cartApi.updateItem(existingItem.id, { quantity: existingItem.quantity })
      } else {
        // Add to API
        const newItem = {
          productId: product.id,
          name: product.name,
          price: product.price,
          image: product.image,
          color: product.color || null,
          sizes: Array.isArray(product.sizes) ? product.sizes : [],
          height: product.height || null,
          quantity: product.quantity || 1,
          hasSizesAvailable: product.hasSizesAvailable || false,
          hasColorsAvailable: product.hasColorsAvailable || false
        }
        const updatedCart = await cartApi.addItem(newItem)
        if (Array.isArray(updatedCart)) {
          cartItems.value = updatedCart
        }
      }

      isCartOpen.value = true
    } catch (error) {
      console.error('Failed to add to cart:', error)
    }
  }

  const removeFromCart = async (cartId) => {
    const index = cartItems.value.findIndex(item => item.id === cartId)
    if (index > -1) {
      await cartApi.removeItem(cartId)
      cartItems.value.splice(index, 1)
    }
  }

  const updateQuantity = async (cartId, quantity) => {
    const item = cartItems.value.find(item => item.id === cartId)
    if (item) {
      if (quantity <= 0) {
        await removeFromCart(cartId)
      } else {
        item.quantity = quantity
        await cartApi.updateItem(cartId, { quantity })
      }
    }
  }

  const updateCartItem = async (cartId, updates) => {
    const item = cartItems.value.find(item => item.id === cartId)
    if (item) {
      Object.assign(item, updates)
      await cartApi.updateItem(cartId, updates)
    }
  }

  const clearCart = async () => {
    await cartApi.clearCart()
    cartItems.value = []
  }

  const openCart = () => {
    isCartOpen.value = true
  }

  const closeCart = () => {
    isCartOpen.value = false
  }

  const cartCount = computed(() => {
    return cartItems.value.reduce((total, item) => total + item.quantity, 0)
  })

  const cartTotal = computed(() => {
    return cartItems.value.reduce((total, item) => total + (item.price * item.quantity), 0)
  })

  return {
    cartItems,
    isCartOpen,
    addToCart,
    removeFromCart,
    updateQuantity,
    updateCartItem,
    clearCart,
    openCart,
    closeCart,
    cartCount,
    cartTotal
  }
}
