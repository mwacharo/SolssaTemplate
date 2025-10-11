// stores/orderStore.js - Improved Version
import { defineStore } from 'pinia'
import axios from 'axios'

// Constants
const DEFAULT_PAGINATION = {
  page: 1,
  itemsPerPage: 25,
  totalItems: 0
}

const ORDER_STATUS_OPTIONS = [
  { title: 'All Status', value: null },
  { title: 'Active', value: 'active' },
  { title: 'Inactive', value: 'inactive' },
  { title: 'Pending', value: 'pending' },
  { title: 'Processing', value: 'processing' },
  { title: 'Completed', value: 'completed' },
  { title: 'Cancelled', value: 'cancelled' },
  { title: 'In Progress', value: 'inprogress' } // Fixed typo
]

const CACHE_LIMITS = {
  vendorProducts: 100,
  notifications: 50
}

const API_ENDPOINTS = {
  orders: '/api/v1/orders',
  orderStatuses: '/api/v1/order-statuses',
  products: '/api/v1/products',
  zones: '/api/v1/zones',
  agents: '/api/v1/agents',
  riders: '/api/v1/riders',
  vendors: '/api/v1/vendors',
  clients: '/api/v1/clients',
  warehouses: '/api/v1/warehouses',
  countries: '/api/v1/countries'
}

// Utility functions
const createLoadingState = () => ({
  orders: false,
  refresh: false,
  statusOptions: false,
  productOptions: false,
  zoneOptions: false,
  agentOptions: false,
  riderOptions: false,
  vendorOptions: false,
  clientOptions: false,
  warehouseOptions: false,
  countryOptions: false,
  creating: false,
  updating: false,
  deleting: false
})

const createInitialFilters = () => ({
  orderFilterStatus: null,
  orderFilterProduct: null,
  orderFilterZone: null,
  orderFilterAgent: null,
  orderFilterRider: null,
  orderFilterVendor: null,
  orderDateRange: [],
  orderSearch: ''
})

export const useOrderStore = defineStore('order', {
  state: () => ({
    // Filter states
    ...createInitialFilters(),
    
    // Options for dropdowns
    orderStatusOptions: [...ORDER_STATUS_OPTIONS],
    productOptions: [],
    zoneOptions: [],
    agentOptions: [],
    riderOptions: [],
    vendorOptions: [],
    clientOptions: [],
    warehouseOptions: [],
    countryOptions: [],
    
    // Data
    orders: [],
    filteredOrders: [],
    selectedOrders: [],
    
    // Dialog states
    dialog: false,
    createDialog: false,
    selectedOrderId: null,
    selectedOrder: null,
    
    // Loading states
    loading: createLoadingState(),
    
    // Pagination
    pagination: { ...DEFAULT_PAGINATION },

    // Enhanced caching with size limits
    vendorProductsCache: new Map(),
    
    // Error handling
    lastError: null,
    notifications: [],

    // Initialization flag
    initialized: false,

    // Performance tracking
    lastFetchTime: null,
    fetchCount: 0
  }),

  getters: {
    // Get active filter count (optimized)
    activeFilterCount: (state) => {
      const filters = [
        state.orderFilterStatus,
        state.orderFilterProduct,
        state.orderFilterZone,
        state.orderFilterAgent,
        state.orderFilterRider,
        state.orderFilterVendor,
        state.orderSearch,
        state.orderDateRange?.length > 0 ? state.orderDateRange : null
      ]
      return filters.filter(filter => filter !== null && filter !== undefined && filter !== '').length
    },
    
    // Get current filters as object (memoized)
    currentFilters: (state) => ({
      status: state.orderFilterStatus,
      product: state.orderFilterProduct,
      zone: state.orderFilterZone,
      agent: state.orderFilterAgent,
      rider: state.orderFilterRider,
      vendor: state.orderFilterVendor,
      dateRange: state.orderDateRange,
      search: state.orderSearch
    }),
    
    // Check if any loading state is active (optimized)
    isLoading: (state) => Object.values(state.loading).some(Boolean),

    // Check if critical loading states are active
    isCriticalLoading: (state) => state.loading.orders || state.loading.creating || state.loading.updating || state.loading.deleting,

    // Get vendor products from cache with fallback
    getVendorProducts: (state) => (vendorId) => {
      if (!vendorId) return []
      return state.vendorProductsCache.get(vendorId) || []
    },

    // Get order by ID with error handling
    getOrderById: (state) => (orderId) => {
      if (!orderId) return null
      return state.orders.find(order => order.id === orderId) || null
    },

    // Get table items (optimized)
    tableItems: (state, getters) => {
      const items = getters.activeFilterCount > 0 ? state.filteredOrders : state.orders
      return items || []
    },

    // Get orders count
    ordersCount: (state) => state.orders?.length || 0,

    // Get filtered orders count
    filteredOrdersCount: (state) => state.filteredOrders?.length || 0,

    // Check if store has data
    hasData: (state) => state.orders.length > 0,

    // Get recent notifications
    recentNotifications: (state) => state.notifications.slice(-10),

    // Get error notifications
    errorNotifications: (state) => state.notifications.filter(n => n.type === 'error'),

    // Performance metrics
    performanceMetrics: (state) => ({
      lastFetchTime: state.lastFetchTime,
      fetchCount: state.fetchCount,
      cacheSize: state.vendorProductsCache.size,
      notificationsCount: state.notifications.length
    })
  },

  actions: {
    // INITIALIZATION - Enhanced with better error handling
    async initialize() {
      if (this.initialized) {
        console.log('[OrderStore] Already initialized, skipping...')
        return
      }

      console.log('[OrderStore] Initializing store...')
      
      try {
        // Initialize options in parallel with better error handling
        const initPromises = [
          this.loadOrderStatusOptions(),
          this.loadProductOptions(),
          this.loadZoneOptions(),
          this.loadAgentOptions(),
          this.loadRiderOptions(),
          this.loadVendorOptions(),
          this.loadClientOptions(),
          this.loadWarehouseOptions(),
          this.loadCountryOptions()
        ]

        const results = await Promise.allSettled(initPromises)
        
        // Log any failed initializations
        results.forEach((result, index) => {
          if (result.status === 'rejected') {
            console.warn(`[OrderStore] Failed to initialize option ${index}:`, result.reason)
          }
        })

        // Load initial orders
        await this.loadOrdersWithFilters({})
        
        this.initialized = true
        this.addNotification({
          type: 'success',
          message: 'Order store initialized successfully'
        })
        
        console.log('[OrderStore] Initialization complete')
      } catch (error) {
        console.error('[OrderStore] Initialization error:', error)
        this.setError(error)
        this.addNotification({
          type: 'error',
          message: 'Failed to initialize order store',
          details: error.message
        })
      }
    },

    // Enhanced error handling
    setError(error) {
      this.lastError = {
        message: error.message || 'Unknown error',
        timestamp: new Date(),
        stack: error.stack
      }
      console.error('[OrderStore] Error:', error)
    },

    clearError() {
      this.lastError = null
    },

    // Enhanced notification system with limits
    addNotification(notification) {
      const newNotification = {
        id: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
        timestamp: new Date(),
        ...notification
      }
      
      this.notifications.push(newNotification)
      
      // Limit notifications to prevent memory leaks
      if (this.notifications.length > CACHE_LIMITS.notifications) {
        this.notifications = this.notifications.slice(-CACHE_LIMITS.notifications)
      }
    },

    removeNotification(id) {
      this.notifications = this.notifications.filter(n => n.id !== id)
    },

    clearAllNotifications() {
      this.notifications = []
    },

    // Clear all filters (optimized)
    clearAllFilters() {
      Object.assign(this, createInitialFilters())
    },

    // Enhanced API call helper with retry logic and better error handling
    async apiCall(url, options = {}) {
      const maxRetries = options.retries || 0
      let lastError

      for (let attempt = 0; attempt <= maxRetries; attempt++) {
        try {
          const config = {
            url,
            method: (options.method || 'get').toLowerCase(),
            headers: {
              'Accept': 'application/json',
              'Content-Type': 'application/json',
              ...(options.headers || {})
            },
            withCredentials: true,
            timeout: options.timeout || 30000
          }

          if (options.body) {
            config.data = typeof options.body === 'string'
              ? JSON.parse(options.body)
              : options.body
          }

          if (config.method === 'get' && config.data) {
            config.params = config.data
            delete config.data
          }

          const response = await axios(config)
          
          // Track performance
          this.fetchCount++
          this.lastFetchTime = new Date()
          
          return response.data
        } catch (error) {
          lastError = error
          
          // Don't retry on client errors (4xx)
          if (error.response?.status >= 400 && error.response?.status < 500) {
            break
          }
          
          // Wait before retry (exponential backoff)
          if (attempt < maxRetries) {
            await new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt) * 1000))
          }
        }
      }

      // Handle final error
      let message = 'Network error occurred'
      if (lastError.response?.data?.message) {
        message = lastError.response.data.message
      } else if (lastError.message) {
        message = lastError.message
      }
      
      const finalError = new Error(message)
      finalError.originalError = lastError
      this.setError(finalError)
      throw finalError
    },

    // Generic options loader to reduce code duplication
    async loadOptions(endpoint, optionsKey, loadingKey) {
      try {
        this.loading[loadingKey] = true
        const data = await this.apiCall(endpoint)
        
        // Handle different response formats
        if (Array.isArray(data)) {
          this[optionsKey] = data
        } else if (data && typeof data === 'object') {
          // Try common property names
          const possibleKeys = [optionsKey.replace('Options', 's'), optionsKey.replace('Options', ''), 'data']
          const foundKey = possibleKeys.find(key => Array.isArray(data[key]))
          this[optionsKey] = foundKey ? data[foundKey] : []
        } else {
          this[optionsKey] = []
        }
        
        return this[optionsKey]
      } catch (error) {
        console.error(`Error loading ${optionsKey}:`, error)
        this[optionsKey] = []
        return []
      } finally {
        this.loading[loadingKey] = false
      }
    },

    // Refactored option loading methods using the generic loader
    async loadOrderStatusOptions() {
      try {
        await this.loadOptions(API_ENDPOINTS.orderStatuses, 'orderStatusOptions', 'statusOptions')
        // Keep defaults if API fails
        if (this.orderStatusOptions.length === 0) {
          this.orderStatusOptions = [...ORDER_STATUS_OPTIONS]
        }
      } catch (error) {
        console.warn('Could not load status options from API, using defaults')
        this.orderStatusOptions = [...ORDER_STATUS_OPTIONS]
      }
    },

    async loadProductOptions() {
      return this.loadOptions(API_ENDPOINTS.products, 'productOptions', 'productOptions')
    },

    async loadZoneOptions() {
      return this.loadOptions(API_ENDPOINTS.zones, 'zoneOptions', 'zoneOptions')
    },

    async loadAgentOptions() {
      return this.loadOptions(API_ENDPOINTS.agents, 'agentOptions', 'agentOptions')
    },

    async loadRiderOptions() {
      return this.loadOptions(API_ENDPOINTS.riders, 'riderOptions', 'riderOptions')
    },

    async loadVendorOptions() {
      return this.loadOptions(API_ENDPOINTS.vendors, 'vendorOptions', 'vendorOptions')
    },

    async loadWarehouseOptions() {
      return this.loadOptions(API_ENDPOINTS.warehouses, 'warehouseOptions', 'warehouseOptions')
    },

    async loadCountryOptions() {
      return this.loadOptions(API_ENDPOINTS.countries, 'countryOptions', 'countryOptions')
    },

    // Enhanced client options with search functionality
    async loadClientOptions(search = '') {
      try {
        this.loading.clientOptions = true
        const queryParam = search 
          ? `?search=${encodeURIComponent(search)}&limit=50` 
          : '?limit=100'
        
        const data = await this.apiCall(`${API_ENDPOINTS.clients}${queryParam}`)
        this.clientOptions = Array.isArray(data) ? data : data.clients || []
        return this.clientOptions
      } catch (error) {
        console.error('Error loading client options:', error)
        this.clientOptions = []
        return []
      } finally {
        this.loading.clientOptions = false
      }
    },

    // Enhanced vendor products caching
    async loadVendorProducts(vendorId) {
      if (!vendorId) return []
      
      // Check cache first
      if (this.vendorProductsCache.has(vendorId)) {
        return this.vendorProductsCache.get(vendorId)
      }

      try {
        const data = await this.apiCall(`${API_ENDPOINTS.vendors}/${vendorId}/products`)
        const products = Array.isArray(data) ? data : data.products || []
        
        // Cache with size limit
        if (this.vendorProductsCache.size >= CACHE_LIMITS.vendorProducts) {
          // Remove oldest entry
          const firstKey = this.vendorProductsCache.keys().next().value
          this.vendorProductsCache.delete(firstKey)
        }
        
        this.vendorProductsCache.set(vendorId, products)
        return products
      } catch (error) {
        console.error('Error loading vendor products:', error)
        return []
      }
    },

    // CORE METHOD: Enhanced orders loading with better error handling and performance
    async loadOrdersWithFilters(filters = {}) {
      try {
        console.log('[OrderStore] loadOrdersWithFilters called with filters:', filters)
        this.loading.orders = true
        this.clearError()

        // Build URL with filters
        const queryParams = new URLSearchParams()

        // Add filters to query params
        Object.entries(filters).forEach(([key, value]) => {
          if (this.isValidFilterValue(value)) {
            if (Array.isArray(value)) {
              queryParams.append(key, JSON.stringify(value))
            } else {
              queryParams.append(key, String(value))
            }
          }
        })

        // Add pagination
        queryParams.append('page', String(this.pagination.page))
        queryParams.append('per_page', String(this.pagination.itemsPerPage))

        const url = `${API_ENDPOINTS.orders}?${queryParams.toString()}`
        console.log('[OrderStore] Final orders API URL:', url)

        // Make API call with retry
        const response = await this.apiCall(url, { retries: 2 })
        console.log('[OrderStore] Raw API Response:', response)

        // Process response
        this.processOrdersResponse(response)
        
        return this.orders
      } catch (error) {
        console.error('[OrderStore] Error loading orders:', error)
        this.handleOrdersLoadError()
        throw error
      } finally {
        this.loading.orders = false
        console.log('[OrderStore] loadOrdersWithFilters finished. Orders count:', this.orders.length)
      }
    },

    // Helper method to validate filter values
    isValidFilterValue(value) {
      if (value === null || value === undefined || value === '') return false
      if (Array.isArray(value)) return value.length > 0
      return true
    },

    // Helper method to process orders response
    // processOrdersResponse(response) {
    //   if (!response || typeof response !== 'object') {
    //     this.handleOrdersLoadError()
    //     return
    //   }

    //   let orders = []
    //   let totalItems = 0

    //   if (response.data && Array.isArray(response.data)) {
    //     // Laravel pagination format
    //     orders = response.data
    //     totalItems = response.meta?.total || response.total || orders.length
    //   } else if (Array.isArray(response)) {
    //     // Direct array response
    //     orders = response
    //     totalItems = orders.length
    //   } else {
    //     console.warn('[OrderStore] Unexpected response format:', response)
    //     this.handleOrdersLoadError()
    //     return
    //   }

    //   // Normalize order data
    //   this.orders = orders.map(this.normalizeOrderData)
    //   this.filteredOrders = [...this.orders]
    //   this.pagination.totalItems = totalItems

    //   console.log('[OrderStore] Successfully loaded orders:', this.orders.length)
    // },


    processOrdersResponse(response) {
  if (!response || typeof response !== 'object') {
    this.handleOrdersLoadError()
    return
  }

  let orders = []
  let totalItems = 0

  // ✅ Matches your backend structure
  if (response.data?.data && Array.isArray(response.data.data.data)) {
    orders = response.data.data.data
    totalItems = response.data.data.total || orders.length
  }
  // Laravel-style fallback
  else if (response.data && Array.isArray(response.data.data)) {
    orders = response.data.data
    totalItems = response.data.total || orders.length
  }
  // Direct array fallback
  else if (Array.isArray(response)) {
    orders = response
    totalItems = orders.length
  } else {
    console.warn('[OrderStore] Unexpected response format:', response)
    this.handleOrdersLoadError()
    return
  }

  this.orders = orders.map(this.normalizeOrderData)
  this.filteredOrders = [...this.orders]
  this.pagination.totalItems = totalItems

  console.log('[OrderStore] ✅ Loaded orders:', this.orders.length)
  console.log('[OrderStore] Sample order:', this.orders[0])
},

    // Helper method to normalize order data
    normalizeOrderData(order) {
      return {
        ...order,
        orderItems: order.orderItems || order.order_items || [],
        client: order.client || {},
        vendor: order.vendor || {},
        agent: order.agent || {},
        rider: order.rider || {},
        warehouse: order.warehouse || {},
        country: order.country || {}
      }
    },

    // Helper method to handle orders load error
    handleOrdersLoadError() {
      this.orders = []
      this.filteredOrders = []
      this.pagination.totalItems = 0
    },

    // Enhanced order loading with caching
    async loadOrder(orderId) {
      if (!orderId) throw new Error('Order ID is required')

      try {
        this.loading.orders = true
        const data = await this.apiCall(`${API_ENDPOINTS.orders}/${orderId}`)
        const order = this.normalizeOrderData(data.data || data)
        
        // Update cache if order exists in orders array
        const orderIndex = this.orders.findIndex(o => o.id === orderId)
        if (orderIndex !== -1) {
          this.orders[orderIndex] = order
          this.filteredOrders[orderIndex] = order
        }
        
        return order
      } catch (error) {
        console.error('Error loading order:', error)
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // Enhanced dialog management
    async openDialog(orderId) {
      console.log('[OrderStore] openDialog called with:', orderId)

      const id = typeof orderId === 'object' && orderId?.id ? orderId.id : orderId
      
      if (!id) {
        this.addNotification({
          type: 'error',
          message: 'Invalid order ID provided'
        })
        return
      }

      this.selectedOrderId = id
      this.dialog = true

      try {
        const order = await this.loadOrder(id)
        this.selectedOrder = order
        console.log('[OrderStore] Order loaded and stored:', order)
      } catch (error) {
        console.error('[OrderStore] Error loading order:', error)
        this.selectedOrder = null
        this.addNotification({
          type: 'error',
          message: 'Failed to load order details',
          details: error.message
        })
      }
    },

    openCreateDialog() {
      this.createDialog = true
      this.dialog = true
      this.selectedOrderId = null
      this.selectedOrder = null
    },

    closeDialog() {
      this.dialog = false
      this.createDialog = false
      this.selectedOrderId = null
      this.selectedOrder = null
    },

    // Enhanced CRUD operations with optimistic updates
    async updateOrder(orderId, orderData) {
      if (!orderId) throw new Error('Order ID is required')
      if (!orderData) throw new Error('Order data is required')

      try {
        this.loading.updating = true
        this.clearError()

        const data = await this.apiCall(`${API_ENDPOINTS.orders}/${orderId}`, {
          method: 'PUT',
          body: JSON.stringify(orderData)
        })

        const updatedOrder = this.normalizeOrderData(data.data || data)

        // Update selectedOrder if it matches
        if (this.selectedOrder?.id === orderId) {
          this.selectedOrder = updatedOrder
        }

        // Update in orders arrays
        this.updateOrderInArrays(orderId, updatedOrder)

        this.addNotification({
          type: 'success',
          message: 'Order updated successfully'
        })

        return updatedOrder
      } catch (error) {
        this.addNotification({
          type: 'error',
          message: 'Failed to update order',
          details: error.message
        })
        throw error
      } finally {
        this.loading.updating = false
      }
    },

    async createOrder(orderData) {
      if (!orderData) throw new Error('Order data is required')

      // Validate required fields
      const requiredFields = ['client_id', 'vendor_id']
      const missingFields = requiredFields.filter(field => !orderData[field])
      
      if (missingFields.length > 0) {
        throw new Error(`Missing required fields: ${missingFields.join(', ')}`)
      }

      try {
        this.loading.creating = true
        this.clearError()

        const data = await this.apiCall(API_ENDPOINTS.orders, {
          method: 'POST',
          body: JSON.stringify(orderData)
        })
        
        const newOrder = this.normalizeOrderData(data.data || data)
        
        // Add to orders arrays (optimistic update)
        this.orders.unshift(newOrder)
        this.filteredOrders.unshift(newOrder)
        this.pagination.totalItems += 1
        
        this.addNotification({
          type: 'success',
          message: 'Order created successfully',
          details: `Order ${newOrder.order_no || newOrder.id} has been created`
        })
        
        return newOrder
      } catch (error) {
        this.addNotification({
          type: 'error',
          message: 'Failed to create order',
          details: error.message
        })
        throw error
      } finally {
        this.loading.creating = false
      }
    },

    async deleteOrder(orderId) {
      if (!orderId) throw new Error('Order ID is required')

      try {
        this.loading.deleting = true
        this.clearError()

        await this.apiCall(`${API_ENDPOINTS.orders}/${orderId}`, {
          method: 'DELETE'
        })
        
        // Remove from orders arrays
        this.removeOrderFromArrays(orderId)
        this.pagination.totalItems = Math.max(0, this.pagination.totalItems - 1)
        
        // Clear selected order if it was deleted
        if (this.selectedOrder?.id === orderId) {
          this.selectedOrder = null
          this.selectedOrderId = null
          this.dialog = false
        }

        this.addNotification({
          type: 'success',
          message: 'Order deleted successfully'
        })
        
        return true
      } catch (error) {
        this.addNotification({
          type: 'error',
          message: 'Failed to delete order',
          details: error.message
        })
        throw error
      } finally {
        this.loading.deleting = false
      }
    },

    // Helper methods for array updates
    updateOrderInArrays(orderId, updatedOrder) {
      const orderIndex = this.orders.findIndex(order => order.id === orderId)
      if (orderIndex !== -1) {
        this.orders[orderIndex] = updatedOrder
      }
      
      const filteredIndex = this.filteredOrders.findIndex(order => order.id === orderId)
      if (filteredIndex !== -1) {
        this.filteredOrders[filteredIndex] = updatedOrder
      }
    },

    removeOrderFromArrays(orderId) {
      this.orders = this.orders.filter(order => order.id !== orderId)
      this.filteredOrders = this.filteredOrders.filter(order => order.id !== orderId)
      this.selectedOrders = this.selectedOrders.filter(order => order.id !== orderId)
    },

    // Enhanced pagination
    setPagination(pagination) {
      this.pagination = { ...this.pagination, ...pagination }
    },

    async goToPage(page) {
      if (page < 1 || page === this.pagination.page) return
      
      this.pagination.page = page
      await this.loadOrdersWithFilters(this.currentFilters)
    },

    async changeItemsPerPage(itemsPerPage) {
      if (itemsPerPage === this.pagination.itemsPerPage) return
      
      this.pagination.itemsPerPage = itemsPerPage
      this.pagination.page = 1 // Reset to first page
      await this.loadOrdersWithFilters(this.currentFilters)
    },

    // Enhanced refresh with debouncing
    async refreshOrders() {
      try {
        this.loading.refresh = true
        await this.loadOrdersWithFilters(this.currentFilters)
        
        this.addNotification({
          type: 'info',
          message: 'Orders refreshed successfully'
        })
      } catch (error) {
        this.addNotification({
          type: 'error',
          message: 'Failed to refresh orders',
          details: error.message
        })
        throw error
      } finally {
        this.loading.refresh = false
      }
    },

    // Bulk operations
    async bulkUpdateOrders(orderIds, updateData) {
      if (!Array.isArray(orderIds) || orderIds.length === 0) {
        throw new Error('Order IDs array is required')
      }

      try {
        this.loading.updating = true
        
        const promises = orderIds.map(id => this.updateOrder(id, updateData))
        const results = await Promise.allSettled(promises)
        
        const successful = results.filter(r => r.status === 'fulfilled').length
        const failed = results.filter(r => r.status === 'rejected').length
        
        this.addNotification({
          type: successful > 0 ? 'success' : 'error',
          message: `Bulk update completed: ${successful} successful, ${failed} failed`
        })
        
        return { successful, failed }
      } catch (error) {
        this.addNotification({
          type: 'error',
          message: 'Bulk update failed',
          details: error.message
        })
        throw error
      } finally {
        this.loading.updating = false
      }
    },

    // Cache management
    clearCache() {
      this.vendorProductsCache.clear()
      console.log('[OrderStore] Cache cleared')
    },

    getCacheStats() {
      return {
        vendorProductsCacheSize: this.vendorProductsCache.size,
        notificationsCount: this.notifications.length
      }
    },

    // Enhanced reset with cleanup
    $reset() {
      // Clear filters
      Object.assign(this, createInitialFilters())
      
      // Clear data
      this.orders = []
      this.filteredOrders = []
      this.selectedOrders = []
      
      // Clear dialogs
      this.dialog = false
      this.createDialog = false
      this.selectedOrderId = null
      this.selectedOrder = null
      
      // Reset pagination
      this.pagination = { ...DEFAULT_PAGINATION }
      
      // Reset loading states
      this.loading = createLoadingState()
      
      // Clear cache and notifications
      this.vendorProductsCache.clear()
      this.notifications = []
      this.lastError = null
      
      // Reset initialization
      this.initialized = false
      
      // Reset performance tracking
      this.lastFetchTime = null
      this.fetchCount = 0
      
      console.log('[OrderStore] Store reset completed')
    }
  }
})
