import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: HomeView
  },
  {
    path: '/products/:slug',
    name: 'product',
    component: () => import('../views/ProductView.vue')
  },
  {
    path: '/collections/:id',
    name: 'product-detail',
    component: () => import('../views/ProductView.vue')
  },
  {
    path: '/cart',
    name: 'cart',
    component: () => import('../views/CartView.vue')
  },
  {
    path: '/checkout',
    name: 'checkout',
    component: () => import('../views/CheckoutView.vue')
  },
  {
    path: '/basic-tops',
    name: 'basic-tops',
    component: () => import('../views/CollectionView.vue')
  },
  {
    path: '/soiree-wear',
    name: 'soiree-wear',
    component: () => import('../views/CollectionView.vue')
  },
  {
    path: '/pants',
    name: 'pants',
    component: () => import('../views/CollectionView.vue')
  },
  {
    path: '/skirts',
    name: 'skirts',
    component: () => import('../views/CollectionView.vue')
  },
  {
    path: '/isdal',
    name: 'isdal',
    component: () => import('../views/CollectionView.vue')
  },
  {
    path: '/cardigans',
    name: 'cardigans',
    component: () => import('../views/CollectionView.vue')
  },
  {
    path: '/admin',
    name: 'admin',
    component: () => import('../views/AdminView.vue')
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

export default router
