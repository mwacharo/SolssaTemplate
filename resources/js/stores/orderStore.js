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
    orderStatusOptions: [],
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
    notifications: []
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
    }
  },

  actions: {
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
      if (this.orderStatusOptions.length > 0) return // Already loaded

      try {
        this.loading.statusOptions = true
        const data = await this.apiCall('/api/v1/order-statuses')
        this.orderStatusOptions = Array.isArray(data) ? data : data.statuses || []
      } catch (error) {
        console.error('Error loading status options:', error)
        // Fallback to default statuses
        this.orderStatusOptions = ['Inprogress', 'active', 'inactive', 'confirmed', 'processing', 'completed', 'cancelled']
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

    // Load vendor products with caching - KEY FUNCTIONALITY FOR EDITING
    async loadVendorProducts(vendorId, forceRefresh = false) {
      if (!vendorId) {
        this.productOptions = []
        return []
      }

      // Check cache first
      if (!forceRefresh && this.vendorProductsCache.has(vendorId)) {
        const cachedProducts = this.vendorProductsCache.get(vendorId)
        this.productOptions = cachedProducts
        return cachedProducts
      }

      try {
        this.loading.productOptions = true
        const data = await this.apiCall(`/api/v1/products/vendor/${vendorId}`)
        
        const products = Array.isArray(data) ? data : data.products || []
        const formattedProducts = products.map(product => ({
          id: product.id,
          product_name: product.product_name,
          sku: product.sku,
          price: product.price || 0,
          vendor_id: product.vendor_id,
          image_url: product.image_url,
          description: product.description,
          category: product.category,
          stock_quantity: product.stock_quantity || 0
        }))

        // Cache the results
        this.vendorProductsCache.set(vendorId, formattedProducts)
        this.productOptions = formattedProducts
        
        return formattedProducts
      } catch (error) {
        console.error('Error loading vendor products:', error)
        this.productOptions = []
        return []
      } finally {
        this.loading.productOptions = false
      }
    },

    // Clear vendor products cache
    clearVendorProductsCache(vendorId = null) {
      if (vendorId) {
        this.vendorProductsCache.delete(vendorId)
      } else {
        this.vendorProductsCache.clear()
      }
    },

    // Initialize all options at once
    async initializeOptions() {
      try {
        await Promise.allSettled([
          this.loadOrderStatusOptions(),
          this.loadZoneOptions(),
          this.loadAgentOptions(),
          this.loadRiderOptions(),
          this.loadVendorOptions(),
          this.loadClientOptions(),
          this.loadWarehouseOptions(),
          this.loadCountryOptions()
        ])
      } catch (error) {
        console.error('Error initializing options:', error)
      }
    },

    // Load orders with filters and enhanced error handling
    async loadOrdersWithFilters(filters = {}) {
      try {
        this.loading.orders = true
        this.clearError()
        
        // Build query parameters
        const queryParams = new URLSearchParams()
        
        // Add filters to query params
        Object.entries(filters).forEach(([key, value]) => {
          if (value !== null && value !== undefined && value !== '') {
            if (Array.isArray(value)) {
              if (value.length > 0) {
                queryParams.append(key, JSON.stringify(value))
              }
            } else {
              queryParams.append(key, value)
            }
          }
        })
        
        // Add pagination
        queryParams.append('page', this.pagination.page)
        queryParams.append('limit', this.pagination.itemsPerPage)
        
        // Make API call
        const data = await this.apiCall(`/api/v1/orders?${queryParams.toString()}`)
        
        // Update state
        this.orders = data.orders || data.data || []
        this.filteredOrders = this.orders
        this.pagination.totalItems = data.total || data.totalItems || 0
        
        return this.orders
      } catch (error) {
        console.error('Error loading orders:', error)
        this.orders = []
        this.filteredOrders = []
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // Load specific order details with enhanced data structure
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

    // CREATE - Create new order with validation
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
        if (!orderData.items || orderData.items.length === 0) {
          throw new Error('At least one item is required')
        }

        // Validate items
        orderData.items.forEach((item, index) => {
          if (!item.product_id) {
            throw new Error(`Product is required for item ${index + 1}`)
          }
          if (!item.quantity || item.quantity <= 0) {
            throw new Error(`Valid quantity is required for item ${index + 1}`)
          }
          if (item.price < 0) {
            throw new Error(`Price cannot be negative for item ${index + 1}`)
          }
        })

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

    // Dialog management
    async openDialog(orderId) {
      console.log('=== openDialog called ===')
      console.log('orderId:', orderId)

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
        console.log('Order loaded and stored:', order)
      } catch (error) {
        console.error('Error loading order:', error)
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

    // UPDATE - Update order with optimistic updates
    async updateOrder(orderId, orderData) {
      try {
        this.loading.updating = true
        this.clearError()

        // Store original order for rollback
        const originalOrder = this.selectedOrder ? { ...this.selectedOrder } : null
        const orderIndex = this.orders.findIndex(order => order.id === orderId)
        const originalOrderInList = orderIndex !== -1 ? { ...this.orders[orderIndex] } : null

        // Optimistic update
        if (this.selectedOrder && this.selectedOrder.id === orderId) {
          Object.assign(this.selectedOrder, orderData)
        }
        if (orderIndex !== -1) {
          Object.assign(this.orders[orderIndex], orderData)
          Object.assign(this.filteredOrders[orderIndex], orderData)
        }

        try {
          const data = await this.apiCall(`/api/v1/orders/${orderId}`, {
            method: 'PUT',
            body: JSON.stringify(orderData)
          })

          const updatedOrder = data.data || data

          // Update with server response
          if (this.selectedOrder && this.selectedOrder.id === orderId) {
            this.selectedOrder = updatedOrder
          }
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
          // Rollback optimistic update
          if (originalOrder && this.selectedOrder && this.selectedOrder.id === orderId) {
            this.selectedOrder = originalOrder
          }
          if (originalOrderInList && orderIndex !== -1) {
            this.orders[orderIndex] = originalOrderInList
            this.filteredOrders[orderIndex] = originalOrderInList
          }
          throw error
        }
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

    // UPDATE - Update order items with validation
    async updateOrderItems(orderId, items) {
      try {
        this.loading.updating = true
        this.clearError()

        // Validate items
        const validItems = items.filter(item => {
          if (!item.product_id) return false
          if (!item.quantity || item.quantity <= 0) return false
          if (item.price < 0) return false
          return true
        })

        if (validItems.length !== items.length) {
          const invalidCount = items.length - validItems.length
          throw new Error(`${invalidCount} item(s) have invalid data. Please check all fields.`)
        }

        const data = await this.apiCall(`/api/v1/orders/${orderId}`, {
          method: 'PUT',
          body: JSON.stringify({ items: validItems })
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
          message: 'Order items updated successfully'
        })

        return updatedOrder
      } catch (error) {
        this.addNotification({
          type: 'error',
          message: 'Failed to update order items',
          details: error.message
        })
        throw error
      } finally {
        this.loading.updating = false
      }
    },

    // UPDATE - Update client information
    async updateClient(clientId, clientData) {
      try {
        this.loading.updating = true
        this.clearError()

        const data = await this.apiCall(`/api/v1/clients/${clientId}`, {
          method: 'PUT',
          body: JSON.stringify(clientData)
        })
        
        const updatedClient = data.data || data
        
        // Update the client in selected order
        if (this.selectedOrder && this.selectedOrder.client && this.selectedOrder.client.id === clientId) {
          this.selectedOrder.client = updatedClient
        }
        
        // Update client in orders array
        this.orders.forEach(order => {
          if (order.client && order.client.id === clientId) {
            order.client = updatedClient
          }
        })
        
        this.filteredOrders.forEach(order => {
          if (order.client && order.client.id === clientId) {
            order.client = updatedClient
          }
        })
        
        // Update client options if it exists there
        const clientOptionIndex = this.clientOptions.findIndex(c => c.id === clientId)
        if (clientOptionIndex !== -1) {
          this.clientOptions[clientOptionIndex] = { ...this.clientOptions[clientOptionIndex], ...updatedClient }
        }

        this.addNotification({
          type: 'success',
          message: 'Client updated successfully'
        })
        
        return updatedClient
      } catch (error) {
        this.addNotification({
          type: 'error',
          message: 'Failed to update client',
          details: error.message
        })
        throw error
      } finally {
        this.loading.updating = false
      }
    },

    // DELETE - Delete order with confirmation
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

    // Bulk operations with progress tracking
    async bulkUpdateOrders(orderIds, updateData) {
      try {
        this.loading.updating = true
        this.clearError()

        const data = await this.apiCall('/api/v1/orders/bulk-update', {
          method: 'PUT',
          body: JSON.stringify({
            order_ids: orderIds,
            update_data: updateData
          })
        })
        
        const updatedOrders = data.data || data
        
        // Update orders in arrays
        updatedOrders.forEach(updatedOrder => {
          const orderIndex = this.orders.findIndex(order => order.id === updatedOrder.id)
          if (orderIndex !== -1) {
            this.orders[orderIndex] = updatedOrder
            this.filteredOrders[orderIndex] = updatedOrder
          }
          
          // Update selected order if it matches
          if (this.selectedOrder && this.selectedOrder.id === updatedOrder.id) {
            this.selectedOrder = updatedOrder
          }
        })

        this.addNotification({
          type: 'success',
          message: `${updatedOrders.length} orders updated successfully`
        })
        
        return updatedOrders
      } catch (error) {
        this.addNotification({
          type: 'error',
          message: 'Failed to bulk update orders',
          details: error.message
        })
        throw error
      } finally {
        this.loading.updating = false
      }
    },

    async bulkDeleteOrders(orderIds) {
      try {
        this.loading.deleting = true
        this.clearError()

        await this.apiCall('/api/v1/orders/bulk-delete', {
          method: 'DELETE',
          body: JSON.stringify({ order_ids: orderIds })
        })
        
        // Remove deleted orders from arrays
        this.orders = this.orders.filter(order => !orderIds.includes(order.id))
        this.filteredOrders = this.filteredOrders.filter(order => !orderIds.includes(order.id))
        this.pagination.totalItems = Math.max(0, this.pagination.totalItems - orderIds.length)
        
        // Clear selected order if it was deleted
        if (this.selectedOrder && orderIds.includes(this.selectedOrder.id)) {
          this.selectedOrder = null
          this.selectedOrderId = null
          this.dialog = false
        }

        this.addNotification({
          type: 'success',
          message: `${orderIds.length} orders deleted successfully`
        })
        
        return true
      } catch (error) {
        this.addNotification({
          type: 'error',
          message: 'Failed to bulk delete orders',
          details: error.message
        })
        throw error
      } finally {
        this.loading.deleting = false
      }
    },

    // Search and filter utilities
    async searchOrders(query) {
      this.orderSearch = query
      await this.loadOrdersWithFilters(this.currentFilters)
    },

    async applyFilters(filters) {
      // Update filter state
      Object.keys(filters).forEach(key => {
        if (key.startsWith('order')) {
          this[key] = filters[key]
        }
      })
      
      // Reset pagination when applying new filters
      this.pagination.page = 1
      
      await this.loadOrdersWithFilters(this.currentFilters)
    },

    // Set pagination
    setPagination(pagination) {
      this.pagination = { ...this.pagination, ...pagination }
    },

    // Export orders with progress tracking
    async exportOrders(format = 'csv', filters = {}) {
      try {
        this.clearError()
        
        const queryParams = new URLSearchParams()
        
        // Add filters
        Object.entries(filters).forEach(([key, value]) => {
          if (value !== null && value !== undefined && value !== '') {
            queryParams.append(key, value)
          }
        })
        
        queryParams.append('format', format)
        
        const response = await fetch(`/api/v1/orders/export?${queryParams.toString()}`)
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        // Handle file download
        const blob = await response.blob()
        const url = window.URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.style.display = 'none'
        a.href = url
        a.download = `orders-export-${new Date().toISOString().split('T')[0]}.${format}`
        document.body.appendChild(a)
        a.click()
        window.URL.revokeObjectURL(url)
        document.body.removeChild(a)

        this.addNotification({
          type: 'success',
          message: 'Orders exported successfully'
        })
        
        return true
      } catch (error) {
        this.addNotification({
          type: 'error',
          message: 'Failed to export orders',
          details: error.message
        })
        throw error
      }
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
      this.dialog = false
      this.createDialog = false
      this.selectedOrderId = null
      this.selectedOrder = null
      this.pagination = {
        page: 1,
        itemsPerPage: 25,
        totalItems: 0
      }
      
      // Reset all options
      this.orderStatusOptions = []
      this.productOptions = []
      this.zoneOptions = []
      this.agentOptions = []
      this.riderOptions = []
      this.vendorOptions = []
      this.clientOptions = []
      this.warehouseOptions = []
      this.countryOptions = []
      
      // Clear cache and notifications
      this.vendorProductsCache.clear()
      this.notifications = []
      this.lastError = null
      
      // Reset loading states
      Object.keys(this.loading).forEach(key => {
        this.loading[key] = false
      })
    }
  }
})