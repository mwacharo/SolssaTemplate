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
      creating: false,
      updating: false,
      deleting: false
    },
    
    // Pagination
    pagination: {
      page: 1,
      itemsPerPage: 25,
      totalItems: 0
    }
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
    }
  },

  actions: {
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

    // Load order status options
    async loadOrderStatusOptions() {
      try {
        this.loading.statusOptions = true
        const response = await fetch('/api/v1/order-statuses')
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        this.orderStatusOptions = await response.json()
      } catch (error) {
        console.error('Error loading status options:', error)
        throw error
      } finally {
        this.loading.statusOptions = false
      }
    },

    // Load product options
    async loadProductOptions() {
      try {
        this.loading.productOptions = true
        const response = await fetch('/api/v1/products')
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        this.productOptions = await response.json()
      } catch (error) {
        console.error('Error loading product options:', error)
        throw error
      } finally {
        this.loading.productOptions = false
      }
    },

    // Load zone options
    async loadZoneOptions() {
      try {
        this.loading.zoneOptions = true
        const response = await fetch('/api/v1/zones')
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        this.zoneOptions = await response.json()
      } catch (error) {
        console.error('Error loading zone options:', error)
        throw error
      } finally {
        this.loading.zoneOptions = false
      }
    },

    // Load agent options
    async loadAgentOptions() {
      try {
        this.loading.agentOptions = true
        const response = await fetch('/api/v1/agents')
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        this.agentOptions = await response.json()
      } catch (error) {
        console.error('Error loading agent options:', error)
        throw error
      } finally {
        this.loading.agentOptions = false
      }
    },

    // Load rider options
    async loadRiderOptions() {
      try {
        this.loading.riderOptions = true
        const response = await fetch('/api/v1/riders')
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        this.riderOptions = await response.json()
      } catch (error) {
        console.error('Error loading rider options:', error)
        throw error
      } finally {
        this.loading.riderOptions = false
      }
    },

    // Load vendor options
    async loadVendorOptions() {
      try {
        this.loading.vendorOptions = true
        const response = await fetch('/api/v1/vendors')
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        this.vendorOptions = await response.json()
      } catch (error) {
        console.error('Error loading vendor options:', error)
        throw error
      } finally {
        this.loading.vendorOptions = false
      }
    },

    // Load client options
    async loadClientOptions(search = '') {
      try {
        this.loading.clientOptions = true
        const queryParam = search ? `?search=${encodeURIComponent(search)}` : ''
        const response = await fetch(`/api/clients${queryParam}`)
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        const data = await response.json()
        this.clientOptions = data.clients || data
      } catch (error) {
        console.error('Error loading client options:', error)
        throw error
      } finally {
        this.loading.clientOptions = false
      }
    },

    // Load vendor products
    async loadVendorProducts(vendorId) {
      try {
        this.loading.productOptions = true
        const response = await fetch(`/api/v1/products/vendor/${vendorId}`)
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        const products = await response.json()
        this.productOptions = products.map(product => ({
          id: product.id,
          product_name: product.product_name,
          sku: product.sku,
          price: product.price,
          vendor_id: product.vendor_id
        }))
      } catch (error) {
        console.error('Error loading vendor products:', error)
        throw error
      } finally {
        this.loading.productOptions = false
      }
    },

    // Get selected order's vendor ID
    getSelectedOrderVendorId() {
      if (this.selectedOrder && this.selectedOrder.vendor) {
        return this.selectedOrder.vendor.id
      }
      return null
    },

    // Load orders with filters
    async loadOrdersWithFilters(filters = {}) {
      try {
        this.loading.orders = true
        
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
        const response = await fetch(`/api/v1/orders?${queryParams.toString()}`)
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        const data = await response.json()
        
        // Update state
        this.orders = data.orders || []
        this.filteredOrders = data.orders || []
        this.pagination.totalItems = data.total || 0
        
      } catch (error) {
        console.error('Error loading orders:', error)
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // Initialize all options
    async initializeOptions() {
      try {
        await Promise.all([
          this.loadOrderStatusOptions(),
          this.loadProductOptions(),
          this.loadZoneOptions(),
          this.loadAgentOptions(),
          this.loadRiderOptions(),
          this.loadVendorOptions(),
          this.loadClientOptions()
        ])
      } catch (error) {
        console.error('Error initializing options:', error)
        throw error
      }
    },

    // Load specific order details
    async loadOrder(orderId) {
      try {
        this.loading.orders = true
        const response = await fetch(`/api/v1/orders/${orderId}`)
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        const result = await response.json()
        const order = result.data ? result.data : result
        return order
      } catch (error) {
        console.error('Error loading order:', error)
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // CREATE - Create new order
    async createOrder(orderData) {
      try {
        this.loading.creating = true
        const response = await fetch('/api/v1/orders', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(orderData)
        })
        
        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        }
        
        const result = await response.json()
        const newOrder = result.data ? result.data : result
        
        // Add to orders array
        this.orders.unshift(newOrder)
        this.filteredOrders.unshift(newOrder)
        this.pagination.totalItems += 1
        
        return newOrder
      } catch (error) {
        console.error('Error creating order:', error)
        throw error
      } finally {
        this.loading.creating = false
      }
    },

    // READ - Dialog actions
    async openDialog(orderId) {
      console.log('=== openDialog called ===')
      console.log('orderId:', orderId)

      // If orderId is an object, extract its id property
      const id = typeof orderId === 'object' && orderId !== null && 'id' in orderId
        ? orderId.id
        : orderId

      this.selectedOrderId = id
      this.dialog = true

      console.log('Dialog set to true:', this.dialog)

      // Load order details
      try {
        const order = await this.loadOrder(id)
        this.selectedOrder = order
        console.log('Order loaded and stored:', order)
      } catch (error) {
        console.error('Error loading order:', error)
        this.selectedOrder = null
      }

      console.log('openDialog completed, final dialog state:', this.dialog)
    },

    // Open create dialog
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

    // UPDATE - Update order items
    async updateOrderItems(orderId, items) {
      try {
        this.loading.updating = true
        const response = await fetch(`/api/v1/orders/${orderId}/items`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ items })
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        }

        const result = await response.json()
        const updatedOrder = result.data ? result.data : result

        // Update selectedOrder if it matches
        if (this.selectedOrder && this.selectedOrder.id === orderId) {
          this.selectedOrder = updatedOrder
        }

        // Update in orders array if it exists
        const orderIndex = this.orders.findIndex(order => order.id === orderId)
        if (orderIndex !== -1) {
          this.orders[orderIndex] = updatedOrder
          this.filteredOrders[orderIndex] = updatedOrder
        }

        return updatedOrder
      } catch (error) {
        console.error('Error updating order items:', error)
        throw error
      } finally {
        this.loading.updating = false
      }
    },

    // UPDATE - Update order
    async updateOrder(orderId, orderData) {
      try {
      this.loading.updating = true
      const axios = (await import('axios')).default
      const response = await axios.put(`/api/v1/orders/${orderId}`, orderData, {
       
      })

      const result = response.data
      const updatedOrder = result.data ? result.data : result

      // Update the selected order if it matches
      if (this.selectedOrder && this.selectedOrder.id === orderId) {
        this.selectedOrder = updatedOrder
      }

      // Update in orders array if it exists
      const orderIndex = this.orders.findIndex(order => order.id === orderId)
      if (orderIndex !== -1) {
        this.orders[orderIndex] = updatedOrder
        this.filteredOrders[orderIndex] = updatedOrder
      }

      return updatedOrder
      } catch (error) {
      console.error('Error updating order:', error)
      throw error
      } finally {
      this.loading.updating = false
      }
    },

    // UPDATE - Update client
    async updateClient(clientId, clientData) {
      try {
        this.loading.updating = true
        const response = await fetch(`/api/v1/clients/${clientId}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify(clientData)
        })
        
        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        }
        
        const result = await response.json()
        const updatedClient = result.data ? result.data : result
        
        // Update the client in selected order if it matches
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
        
        return updatedClient
      } catch (error) {
        console.error('Error updating client:', error)
        throw error
      } finally {
        this.loading.updating = false
      }
    },

    // DELETE - Delete order
    async deleteOrder(orderId) {
      try {
        this.loading.deleting = true
        const response = await fetch(`/api/v1/orders/${orderId}`, {
          method: 'DELETE',
          headers: {
            'Accept': 'application/json'
          }
        })
        
        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        }
        
        // Remove from orders array
        this.orders = this.orders.filter(order => order.id !== orderId)
        this.filteredOrders = this.filteredOrders.filter(order => order.id !== orderId)
        this.pagination.totalItems -= 1
        
        // Clear selected order if it was deleted
        if (this.selectedOrder && this.selectedOrder.id === orderId) {
          this.selectedOrder = null
          this.selectedOrderId = null
          this.dialog = false
        }
        
        return true
      } catch (error) {
        console.error('Error deleting order:', error)
        throw error
      } finally {
        this.loading.deleting = false
      }
    },

    // Bulk operations
    async bulkUpdateOrders(orderIds, updateData) {
      try {
        this.loading.updating = true
        const response = await fetch('/api/v1/orders/bulk-update', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            order_ids: orderIds,
            update_data: updateData
          })
        })
        
        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        }
        
        const result = await response.json()
        const updatedOrders = result.data || result
        
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
        
        return updatedOrders
      } catch (error) {
        console.error('Error bulk updating orders:', error)
        throw error
      } finally {
        this.loading.updating = false
      }
    },

    async bulkDeleteOrders(orderIds) {
      try {
        this.loading.deleting = true
        const response = await fetch('/api/v1/orders/bulk-delete', {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ order_ids: orderIds })
        })
        
        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
        }
        
        // Remove deleted orders from arrays
        this.orders = this.orders.filter(order => !orderIds.includes(order.id))
        this.filteredOrders = this.filteredOrders.filter(order => !orderIds.includes(order.id))
        this.pagination.totalItems -= orderIds.length
        
        // Clear selected order if it was deleted
        if (this.selectedOrder && orderIds.includes(this.selectedOrder.id)) {
          this.selectedOrder = null
          this.selectedOrderId = null
          this.dialog = false
        }
        
        return true
      } catch (error) {
        console.error('Error bulk deleting orders:', error)
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
      Object.keys(filters).forEach(key => {
        if (key.startsWith('order')) {
          this[key] = filters[key]
        }
      })
      await this.loadOrdersWithFilters(this.currentFilters)
    },

    // Set pagination
    setPagination(pagination) {
      this.pagination = { ...this.pagination, ...pagination }
    },

    // Export orders
    async exportOrders(format = 'csv', filters = {}) {
      try {
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
        
        return true
      } catch (error) {
        console.error('Error exporting orders:', error)
        throw error
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
      Object.keys(this.loading).forEach(key => {
        this.loading[key] = false
      })
    }
  }
})