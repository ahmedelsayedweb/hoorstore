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

export function useCart() {
  const addToCart = async (product) => {
    const existingItem = cartItems.value.find(
      item =>
        item.productId === product.id &&
        item.color === product.color &&
        item.size === product.size &&
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
        color: product.color,
        size: product.size,
        height: product.height,
        quantity: product.quantity
      }
      const updatedCart = await cartApi.addItem(newItem)
      cartItems.value = updatedCart
    }

    isCartOpen.value = true
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
    clearCart,
    openCart,
    closeCart,
    cartCount,
    cartTotal
  }
}
