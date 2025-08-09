// stores/orderStore.js
import { defineStore } from 'pinia'

export const useOrderStore = defineStore('order', {
  state: () => ({
    // Filter states
    orderFilterStatus: null,
    orderFilterProduct: null,
    orderFilterZone: null,
    orderFilterAgent: null,
    orderFilterRider: null,
    orderFilterVendor: null,
    orderDateRange: [],
    orderSearch: '',
    
    // Options for dropdowns
    orderStatusOptions: [
      { title: 'All Status', value: null },
      { title: 'Active', value: 'active' },
      { title: 'Inactive', value: 'inactive' },
      { title: 'Pending', value: 'pending' },
      { title: 'Processing', value: 'processing' },
      { title: 'Completed', value: 'completed' },
      { title: 'Cancelled', value: 'cancelled' },
      { title: 'Inprogress', value: 'Inprogress' }
    ],
    productOptions: [],
    zoneOptions: [],
    agentOptions: [],
    riderOptions: [],
    vendorOptions: [],
    clientOptions: [],
    warehouseOptions: [],
    countryOptions: [],
    
    // Data - CRITICAL: These must be properly initialized
    orders: [],
    filteredOrders: [],
    selectedOrders: [], // This was missing in your component
    
    // Dialog states
    dialog: false,
    createDialog: false,
    selectedOrderId: null,
    selectedOrder: null,
    
    // Loading states
    loading: {
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
    },
    
    // Pagination
    pagination: {
      page: 1,
      itemsPerPage: 25,
      totalItems: 0
    },

    // Cache for vendor products to improve performance
    vendorProductsCache: new Map(),
    
    // Error handling
    lastError: null,
    notifications: [],

    // Initialization flag
    initialized: false
  }),

  getters: {
    // Get active filter count
    activeFilterCount: (state) => {
      let count = 0
      if (state.orderFilterStatus) count++
      if (state.orderFilterProduct) count++
      if (state.orderFilterZone) count++
      if (state.orderFilterAgent) count++
      if (state.orderFilterRider) count++
      if (state.orderFilterVendor) count++
      if (state.orderDateRange && state.orderDateRange.length > 0) count++
      if (state.orderSearch) count++
      return count
    },
    
    // Get current filters as object
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
    
    // Check if any loading state is active
    isLoading: (state) => {
      return Object.values(state.loading).some(loading => loading)
    },

    // Get vendor products from cache
    getVendorProducts: (state) => (vendorId) => {
      return state.vendorProductsCache.get(vendorId) || []
    },

    // Get order by ID
    getOrderById: (state) => (orderId) => {
      return state.orders.find(order => order.id === orderId) || null
    },

    // Get table items (this is what the component should use)
    tableItems: (state) => {
      const items = state.activeFilterCount > 0 ? state.filteredOrders : state.orders
      console.log('[OrderStore] tableItems getter called, returning:', items?.length || 0, 'items')
      return items || []
    }
  },

  actions: {
    // INITIALIZATION - This is crucial
    async initialize() {
      if (this.initialized) {
        console.log('[OrderStore] Already initialized, skipping...')
        return
      }

      console.log('[OrderStore] Initializing store...')
      
      try {
        // Initialize options in parallel
        await Promise.allSettled([
          this.loadOrderStatusOptions(),
          this.loadProductOptions(),
          this.loadZoneOptions(),
          this.loadAgentOptions(),
          this.loadRiderOptions(),
          this.loadVendorOptions(),
          this.loadClientOptions(),
          this.loadWarehouseOptions(),
          this.loadCountryOptions()
        ])

        // Load initial orders
        await this.loadOrdersWithFilters({})
        
        this.initialized = true
        console.log('[OrderStore] Initialization complete')
      } catch (error) {
        console.error('[OrderStore] Initialization error:', error)
        this.setError(error)
      }
    },

    // Error handling
    setError(error) {
      this.lastError = error
      console.error('Order Store Error:', error)
    },

    clearError() {
      this.lastError = null
    },

    addNotification(notification) {
      this.notifications.push({
        id: Date.now(),
        timestamp: new Date(),
        ...notification
      })
    },

    removeNotification(id) {
      this.notifications = this.notifications.filter(n => n.id !== id)
    },

    // Clear all filters
    clearAllFilters() {
      this.orderFilterStatus = null
      this.orderFilterProduct = null
      this.orderFilterZone = null
      this.orderFilterAgent = null
      this.orderFilterRider = null
      this.orderFilterVendor = null
      this.orderDateRange = []
      this.orderSearch = ''
    },

    // Generic API call helper with error handling
    async apiCall(url, options = {}) {
      try {
        // Use axios for API calls (assumes axios is imported)
        const config = {
          url,
          method: options.method ? options.method.toLowerCase() : 'get',
          headers: {
            'Accept': 'application/json',
            ...(options.headers || {})
          },
          withCredentials: true
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
        return response.data
      } catch (error) {
        let message = 'Unknown error'
        if (error.response && error.response.data) {
          message = error.response.data.message || JSON.stringify(error.response.data)
        } else if (error.message) {
          message = error.message
        }
        this.setError(new Error(message))
        throw new Error(message)
      }
    },

    // Load order status options
    async loadOrderStatusOptions() {
      try {
        this.loading.statusOptions = true
        // Try to load from API, fallback to defaults
        try {
          const data = await this.apiCall('/api/v1/order-statuses')
          this.orderStatusOptions = Array.isArray(data) ? data : data.statuses || this.orderStatusOptions
        } catch (error) {
          console.warn('Could not load status options from API, using defaults')
        }
      } catch (error) {
        console.error('Error loading status options:', error)
      } finally {
        this.loading.statusOptions = false
      }
    },

    // Load product options (general products)
    async loadProductOptions() {
      try {
        this.loading.productOptions = true
        const data = await this.apiCall('/api/v1/products')
        this.productOptions = Array.isArray(data) ? data : data.products || []
      } catch (error) {
        console.error('Error loading product options:', error)
        this.productOptions = []
      } finally {
        this.loading.productOptions = false
      }
    },

    // Load zone options
    async loadZoneOptions() {
      try {
        this.loading.zoneOptions = true
        const data = await this.apiCall('/api/v1/zones')
        this.zoneOptions = Array.isArray(data) ? data : data.zones || []
      } catch (error) {
        console.error('Error loading zone options:', error)
        this.zoneOptions = []
      } finally {
        this.loading.zoneOptions = false
      }
    },

    // Load agent options
    async loadAgentOptions() {
      try {
        this.loading.agentOptions = true
        const data = await this.apiCall('/api/v1/agents')
        this.agentOptions = Array.isArray(data) ? data : data.agents || []
      } catch (error) {
        console.error('Error loading agent options:', error)
        this.agentOptions = []
      } finally {
        this.loading.agentOptions = false
      }
    },

    // Load rider options
    async loadRiderOptions() {
      try {
        this.loading.riderOptions = true
        const data = await this.apiCall('/api/v1/riders')
        this.riderOptions = Array.isArray(data) ? data : data.riders || []
      } catch (error) {
        console.error('Error loading rider options:', error)
        this.riderOptions = []
      } finally {
        this.loading.riderOptions = false
      }
    },

    // Load vendor options
    async loadVendorOptions() {
      try {
        this.loading.vendorOptions = true
        const data = await this.apiCall('/api/v1/vendors')
        this.vendorOptions = Array.isArray(data) ? data : data.vendors || []
      } catch (error) {
        console.error('Error loading vendor options:', error)
        this.vendorOptions = []
      } finally {
        this.loading.vendorOptions = false
      }
    },

    // Load client options with search functionality
    async loadClientOptions(search = '') {
      try {
        this.loading.clientOptions = true
        const queryParam = search ? `?search=${encodeURIComponent(search)}&limit=50` : '?limit=100'
        const data = await this.apiCall(`/api/v1/clients${queryParam}`)
        this.clientOptions = Array.isArray(data) ? data : data.clients || []
      } catch (error) {
        console.error('Error loading client options:', error)
        this.clientOptions = []
      } finally {
        this.loading.clientOptions = false
      }
    },

    // Load warehouse options
    async loadWarehouseOptions() {
      try {
        this.loading.warehouseOptions = true
        const data = await this.apiCall('/api/v1/warehouses')
        this.warehouseOptions = Array.isArray(data) ? data : data.warehouses || []
      } catch (error) {
        console.error('Error loading warehouse options:', error)
        this.warehouseOptions = []
      } finally {
        this.loading.warehouseOptions = false
      }
    },

    // Load country options
    async loadCountryOptions() {
      try {
        this.loading.countryOptions = true
        const data = await this.apiCall('/api/v1/countries')
        this.countryOptions = Array.isArray(data) ? data : data.countries || []
      } catch (error) {
        console.error('Error loading country options:', error)
        this.countryOptions = []
      } finally {
        this.loading.countryOptions = false
      }
    },

    // CORE METHOD: Load orders with filters - FIXED VERSION
    async loadOrdersWithFilters(filters = {}) {
      try {
        console.log('[OrderStore] loadOrdersWithFilters called with filters:', filters)
        this.loading.orders = true
        this.clearError()

        // Build URL with filters
        let url = '/api/v1/orders'
        const queryParams = new URLSearchParams()

        // Add filters to query params if they have values
        Object.entries(filters).forEach(([key, value]) => {
          if (value !== null && value !== undefined && value !== '') {
            if (Array.isArray(value)) {
              if (value.length > 0) {
                console.log(`[OrderStore] Adding array filter: ${key} =`, value)
                queryParams.append(key, JSON.stringify(value))
              }
            } else {
              console.log(`[OrderStore] Adding filter: ${key} =`, value)
              queryParams.append(key, value)
            }
          }
        })

        // Always add pagination
        queryParams.append('page', this.pagination.page.toString())
        queryParams.append('per_page', this.pagination.itemsPerPage.toString())

        if (queryParams.toString()) {
          url += `?${queryParams.toString()}`
        }

        console.log('[OrderStore] Final orders API URL:', url)

        // Make API call
        const response = await this.apiCall(url)
        console.log('[OrderStore] Raw API Response:', response)

        // CRITICAL: Handle Laravel pagination structure correctly
        if (response && typeof response === 'object') {
          if (response.data && Array.isArray(response.data)) {
            // Laravel pagination format
            this.orders = response.data
            this.filteredOrders = response.data
            
            // Handle Laravel pagination meta
            if (response.meta) {
              this.pagination.totalItems = response.meta.total || 0
            }
            
            console.log('[OrderStore] Successfully loaded orders:', this.orders.length)
            console.log('[OrderStore] Sample order:', this.orders[0])
            
            // Ensure orderItems are properly formatted for your templates
            this.orders.forEach(order => {
              if (!order.orderItems && order.order_items) {
                order.orderItems = order.order_items
              }
              if (!order.orderItems) {
                order.orderItems = []
              }
            })
          } else if (Array.isArray(response)) {
            // Direct array response
            this.orders = response
            this.filteredOrders = response
            this.pagination.totalItems = response.length
            
            console.log('[OrderStore] Loaded direct array orders:', this.orders.length)
          } else {
            console.warn('[OrderStore] Unexpected response format:', response)
            this.orders = []
            this.filteredOrders = []
            this.pagination.totalItems = 0
          }
        } else {
          console.warn('[OrderStore] Invalid response:', response)
          this.orders = []
          this.filteredOrders = []
          this.pagination.totalItems = 0
        }

        return this.orders
      } catch (error) {
        console.error('[OrderStore] Error loading orders:', error)
        this.orders = []
        this.filteredOrders = []
        this.pagination.totalItems = 0
        throw error
      } finally {
        this.loading.orders = false
        console.log('[OrderStore] loadOrdersWithFilters finished. Orders count:', this.orders.length)
      }
    },

    // Load specific order details
    async loadOrder(orderId) {
      try {
        this.loading.orders = true
        const data = await this.apiCall(`/api/v1/orders/${orderId}`)
        const order = data.data || data

        // Ensure proper data structure
        if (order) {
          order.orderItems = order.orderItems || order.order_items || []
          order.client = order.client || {}
          order.vendor = order.vendor || {}
          order.agent = order.agent || {}
          order.rider = order.rider || {}
          order.warehouse = order.warehouse || {}
          order.country = order.country || {}
        }

        return order
      } catch (error) {
        console.error('Error loading order:', error)
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // Dialog management
    async openDialog(orderId) {
      console.log('[OrderStore] openDialog called with:', orderId)

      // Handle object parameter
      const id = typeof orderId === 'object' && orderId !== null && 'id' in orderId
        ? orderId.id
        : orderId

      this.selectedOrderId = id
      this.dialog = true

      // Load order details
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

    // UPDATE - Update order
    async updateOrder(orderId, orderData) {
      try {
        this.loading.updating = true
        this.clearError()

        const data = await this.apiCall(`/api/v1/orders/${orderId}`, {
          method: 'PUT',
          body: JSON.stringify(orderData)
        })

        const updatedOrder = data.data || data

        // Update selectedOrder if it matches
        if (this.selectedOrder && this.selectedOrder.id === orderId) {
          this.selectedOrder = updatedOrder
        }

        // Update in orders array
        const orderIndex = this.orders.findIndex(order => order.id === orderId)
        if (orderIndex !== -1) {
          this.orders[orderIndex] = updatedOrder
          this.filteredOrders[orderIndex] = updatedOrder
        }

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

    // CREATE - Create new order
    async createOrder(orderData) {
      try {
        this.loading.creating = true
        this.clearError()

        // Validate required fields
        if (!orderData.client_id) {
          throw new Error('Client is required')
        }
        if (!orderData.vendor_id) {
          throw new Error('Vendor is required')
        }

        const data = await this.apiCall('/api/v1/orders', {
          method: 'POST',
          body: JSON.stringify(orderData)
        })
        
        const newOrder = data.data || data
        
        // Add to orders array
        this.orders.unshift(newOrder)
        this.filteredOrders.unshift(newOrder)
        this.pagination.totalItems += 1
        
        this.addNotification({
          type: 'success',
          message: 'Order created successfully',
          details: `Order ${newOrder.order_no} has been created`
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

    // DELETE - Delete order
    async deleteOrder(orderId) {
      try {
        this.loading.deleting = true
        this.clearError()

        await this.apiCall(`/api/v1/orders/${orderId}`, {
          method: 'DELETE'
        })
        
        // Remove from orders array
        this.orders = this.orders.filter(order => order.id !== orderId)
        this.filteredOrders = this.filteredOrders.filter(order => order.id !== orderId)
        this.pagination.totalItems = Math.max(0, this.pagination.totalItems - 1)
        
        // Clear selected order if it was deleted
        if (this.selectedOrder && this.selectedOrder.id === orderId) {
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

    // Set pagination
    setPagination(pagination) {
      this.pagination = { ...this.pagination, ...pagination }
    },

    // Refresh data
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
      } finally {
        this.loading.refresh = false
      }
    },

    // Reset store to initial state
    $reset() {
      this.clearAllFilters()
      this.orders = []
      this.filteredOrders = []
      this.selectedOrders = []
      this.dialog = false
      this.createDialog = false
      this.selectedOrderId = null
      this.selectedOrder = null
      this.pagination = {
        page: 1,
        itemsPerPage: 25,
        totalItems: 0
      }
      
      // Reset loading states
      Object.keys(this.loading).forEach(key => {
        this.loading[key] = false
      })

      this.initialized = false
      this.vendorProductsCache.clear()
      this.notifications = []
      this.lastError = null
    }
  }
})