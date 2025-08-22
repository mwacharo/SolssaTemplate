// stores/productStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useProductStore = defineStore('product', () => {
  // State
  const searchTerm = ref('')
  const selectedSeller = ref('')
  const selectedCategory = ref('')
  const sortBy = ref('orders_desc')
  const dateFilter = ref('')
  
  const products = ref([])

  // Actions
  const initializeProducts = () => {
    products.value = [
      {
        id: 'pdt-3489',
        seller: 'testmerchant',
        product: 'strong man',
        image: '/api/placeholder/60/60',
        category: 'Health & Fitness',
        totalOrders: 98,
        totalPending: 3,
        pendingPercent: 3.1,
        totalCancelled: 0,
        cancelledPercent: 0.0,
        totalConfirmed: 1,
        confirmedPercent: 1.0,
        inDelivery: 1,
        deliveryPercent: 100.0,
        totalReturned: 0,
        returnedPercent: 0.0,
        totalDelivered: 0,
        deliveredPercent: 0.0,
        deliveryRate: 0,
        unitPrice: 25.99,
        totalRevenue: 2547.02,
        avgOrderValue: 25.99
      },
      {
        id: 'pdt-9757',
        seller: 'testmerchant',
        product: 'test',
        image: '/api/placeholder/60/60',
        category: 'General',
        totalOrders: 96,
        totalPending: 4,
        pendingPercent: 4.2,
        totalCancelled: 2,
        cancelledPercent: 2.1,
        totalConfirmed: 28,
        confirmedPercent: 29.2,
        inDelivery: 0,
        deliveryPercent: 0.0,
        totalReturned: 5,
        returnedPercent: 17.9,
        totalDelivered: 8,
        deliveredPercent: 28.6,
        deliveryRate: 8.33,
        unitPrice: 15.50,
        totalRevenue: 1488.00,
        avgOrderValue: 15.50
      },
      {
        id: 'pdt-7650',
        seller: 'CHEAPIST',
        product: 'EARPHONE',
        image: '/api/placeholder/60/60',
        category: 'Electronics',
        totalOrders: 6,
        totalPending: 6,
        pendingPercent: 100.0,
        totalCancelled: 0,
        cancelledPercent: 0.0,
        totalConfirmed: 0,
        confirmedPercent: 0.0,
        inDelivery: 0,
        deliveryPercent: 0,
        totalReturned: 0,
        returnedPercent: 0,
        totalDelivered: 0,
        deliveredPercent: 0,
        deliveryRate: 0,
        unitPrice: 12.99,
        totalRevenue: 77.94,
        avgOrderValue: 12.99
      }
    ]
  }

  const resetFilters = () => {
    searchTerm.value = ''
    selectedSeller.value = ''
    selectedCategory.value = ''
    sortBy.value = 'orders_desc'
    dateFilter.value = ''
  }

  const updateProduct = (productId, updates) => {
    const productIndex = products.value.findIndex(p => p.id === productId)
    if (productIndex !== -1) {
      products.value[productIndex] = { ...products.value[productIndex], ...updates }
    }
  }

  const addProduct = (product) => {
    products.value.push(product)
  }

  const removeProduct = (productId) => {
    const productIndex = products.value.findIndex(p => p.id === productId)
    if (productIndex !== -1) {
      products.value.splice(productIndex, 1)
    }
  }

  // Getters (computed)
  const getFilteredAndSortedProducts = computed(() => {
    let filtered = products.value.filter(product => {
      const matchesSearch = product.product.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
                          product.seller.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
                          product.id.toLowerCase().includes(searchTerm.value.toLowerCase())
      const matchesSeller = !selectedSeller.value || product.seller === selectedSeller.value
      const matchesCategory = !selectedCategory.value || product.category === selectedCategory.value
      return matchesSearch && matchesSeller && matchesCategory
    })

    // Sort products
    filtered.sort((a, b) => {
      switch(sortBy.value) {
        case 'revenue_desc':
          return b.totalRevenue - a.totalRevenue
        case 'revenue_asc':
          return a.totalRevenue - b.totalRevenue
        case 'orders_desc':
          return b.totalOrders - a.totalOrders
        case 'orders_asc':
          return a.totalOrders - b.totalOrders
        case 'delivery_rate_desc':
          return b.deliveryRate - a.deliveryRate
        case 'delivery_rate_asc':
          return a.deliveryRate - b.deliveryRate
        default:
          return b.totalOrders - a.totalOrders
      }
    })

    return filtered
  })

  const getProductById = computed(() => {
    return (id) => products.value.find(product => product.id === id)
  })

  const getTotalRevenue = computed(() => {
    return products.value.reduce((total, product) => total + product.totalRevenue, 0)
  })

  const getTotalOrders = computed(() => {
    return products.value.reduce((total, product) => total + product.totalOrders, 0)
  })

  const getSellerProducts = computed(() => {
    return (sellerName) => products.value.filter(product => product.seller === sellerName)
  })

  const getCategoryProducts = computed(() => {
    return (categoryName) => products.value.filter(product => product.category === categoryName)
  })

  const getTopPerformingProducts = computed(() => {
    return (limit = 5) => {
      return [...products.value]
        .sort((a, b) => b.totalRevenue - a.totalRevenue)
        .slice(0, limit)
    }
  })

  const getAvgDeliveryRate = computed(() => {
    const total = products.value.reduce((sum, product) => sum + product.deliveryRate, 0)
    return products.value.length > 0 ? total / products.value.length : 0
  })

  const getProductsWithLowStock = computed(() => {
    return products.value.filter(product => product.totalOrders < 10)
  })

  const getRevenueByCategory = computed(() => {
    const categoryRevenue = {}
    products.value.forEach(product => {
      if (!categoryRevenue[product.category]) {
        categoryRevenue[product.category] = 0
      }
      categoryRevenue[product.category] += product.totalRevenue
    })
    return categoryRevenue
  })

  return {
    // State
    searchTerm,
    selectedSeller,
    selectedCategory,
    sortBy,
    dateFilter,
    products,
    
    // Actions
    initializeProducts,
    resetFilters,
    updateProduct,
    addProduct,
    removeProduct,
    
    // Getters
    getFilteredAndSortedProducts,
    getProductById,
    getTotalRevenue,
    getTotalOrders,
    getSellerProducts,
    getCategoryProducts,
    getTopPerformingProducts,
    getAvgDeliveryRate,
    getProductsWithLowStock,
    getRevenueByCategory
  }
})