<script setup>
import { useI18n } from 'vue-i18n'
import ProductCard from './ProductCard.vue'
import { useCart } from '@/composables/useCart'

const { t } = useI18n()
const { addToCart } = useCart()

defineProps({
  title: {
    type: String,
    default: ''
  },
  viewAllLink: {
    type: String,
    default: '/sale'
  }
})

// Sample products data - in a real app, this would come from an API
const products = [
  {
    id: 1,
    image: 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600',
    name: 'Zara pants',
    originalPrice: 585,
    salePrice: 349,
    isOnSale: true,
    link: '/products/zara-pants'
  },
  {
    id: 2,
    image: 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?w=600',
    name: 'Ribbed Basic - Long Sleeve',
    originalPrice: 449,
    salePrice: 249,
    isOnSale: true,
    link: '/products/ribbed-basic'
  },
  {
    id: 3,
    image: 'https://images.unsplash.com/photo-1485968579580-b6d095142e6e?w=600',
    name: 'HIGH NECK ZIPPER LONG SLEEVE',
    originalPrice: 449,
    salePrice: 249,
    isOnSale: true,
    link: '/products/high-neck-zipper'
  },
  {
    id: 4,
    image: 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600',
    name: 'Check Shirt',
    originalPrice: 600,
    salePrice: 299,
    isOnSale: true,
    link: '/products/check-shirt'
  }
]

const handleAddToCart = (productId) => {
  const product = products.find(p => p.id === productId)
  if (product) {
    // Check if product has sizes or colors - redirect to product page if so
    const hasSizes = product.sizes && product.sizes.length > 0
    const hasColors = product.colors && product.colors.length > 0

    if (hasSizes || hasColors) {
      // Redirect to product page for size/color selection
      window.location.href = `/collections/${productId}`
      return
    }

    addToCart({
      id: product.id,
      name: product.name,
      price: product.salePrice || product.originalPrice,
      image: product.image,
      quantity: 1,
      hasSizesAvailable: false,
      hasColorsAvailable: false
    })
  }
}
</script>

<template>
  <section class="py-12 px-4 md:px-8 lg:px-16">
    <!-- Section Title -->
    <h2 class="text-2xl md:text-3xl font-normal text-center text-gray-900 mb-10">
      {{ title || t('collection.whiteFridaySale') }}
    </h2>

    <!-- Products Grid -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
      <ProductCard
        v-for="product in products"
        :key="product.id"
        :id="product.id"
        :image="product.image"
        :name="product.name"
        :original-price="product.originalPrice"
        :sale-price="product.salePrice"
        :is-on-sale="product.isOnSale"
        @add-to-cart="handleAddToCart"
      />
    </div>

    <!-- View All Button -->
    <div class="text-center mt-10">
      <a
        :href="viewAllLink"
        class="inline-block border border-gray-900 text-gray-900 px-8 py-3 text-sm hover:bg-gray-900 hover:text-white transition-colors duration-300"
      >
        {{ t('common.viewAll') }}
      </a>
    </div>
  </section>
</template>
