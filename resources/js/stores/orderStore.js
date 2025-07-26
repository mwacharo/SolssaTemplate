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
    
    // Data
    orders: [],
    filteredOrders: [],
    
    // Dialog states - ADD selectedOrder to state
    dialog: false,
    selectedOrderId: null,
    selectedOrder: null, // Add this to store the selected order details
    
    // Loading states
    loading: {
      orders: false,
      refresh: false,
      statusOptions: false,
      productOptions: false,
      zoneOptions: false,
      agentOptions: false,
      riderOptions: false,
      vendorOptions: false
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
        // Replace with your API call
        const response = await fetch('/api/order-statuses')
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
        // Replace with your API call
        const response = await fetch('/api/products')
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
        // Replace with your API call
        const response = await fetch('/api/zones')
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
        // Replace with your API call
        const response = await fetch('/api/agents')
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
        // Replace with your API call
        const response = await fetch('/api/riders')
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
        // Replace with your API call
        const response = await fetch('/api/vendors')
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
        const response = await fetch(`/api/orders?${queryParams.toString()}`)
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
          this.loadVendorOptions()
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
        // If response has a "data" property, use it
        const order = result.data ? result.data : result
        return order
      } catch (error) {
        console.error('Error loading order:', error)
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // Dialog actions - FIXED VERSION
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
        // Store the order in selectedOrder state property
        this.selectedOrder = order
        console.log('Order loaded and stored:', order)
      } catch (error) {
        console.error('Error loading order:', error)
        this.selectedOrder = null
      }

      console.log('openDialog completed, final dialog state:', this.dialog)
    },

    closeDialog() {
      this.dialog = false
      this.selectedOrderId = null
      this.selectedOrder = null // Clear the selected order
    },

    // Set pagination
    setPagination(pagination) {
      this.pagination = { ...this.pagination, ...pagination }
    },

    // Update order
    async updateOrder(orderId, orderData) {
      try {
        this.loading.orders = true
        const response = await fetch(`/api/v1/orders/${orderId}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(orderData)
        })
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const result = await response.json()
        const updatedOrder = result.data ? result.data : result
        
        // Update the selected order if it matches
        if (this.selectedOrder && this.selectedOrder.id === orderId) {
          this.selectedOrder = updatedOrder
        }
        
        // Update in orders array if it exists
        const orderIndex = this.orders.findIndex(order => order.id === orderId)
        if (orderIndex !== -1) {
          this.orders[orderIndex] = updatedOrder
        }
        
        return updatedOrder
      } catch (error) {
        console.error('Error updating order:', error)
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // Update client
    async updateClient(clientId, clientData) {
      try {
        this.loading.orders = true
        const response = await fetch(`/api/v1/clients/${clientId}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(clientData)
        })
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        const result = await response.json()
        const updatedClient = result.data ? result.data : result
        
        // Update the client in selected order if it matches
        if (this.selectedOrder && this.selectedOrder.client && this.selectedOrder.client.id === clientId) {
          this.selectedOrder.client = updatedClient
        }
        
        return updatedClient
      } catch (error) {
        console.error('Error updating client:', error)
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // Delete order
    async deleteOrder(orderId) {
      try {
        this.loading.orders = true
        const response = await fetch(`/api/v1/orders/${orderId}`, {
          method: 'DELETE'
        })
        
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        
        // Remove from orders array
        this.orders = this.orders.filter(order => order.id !== orderId)
        this.filteredOrders = this.filteredOrders.filter(order => order.id !== orderId)
        
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
        this.loading.orders = false
      }
    },

    // Reset store to initial state
    $reset() {
      this.clearAllFilters()
      this.orders = []
      this.filteredOrders = []
      this.dialog = false
      this.selectedOrderId = null
      this.selectedOrder = null // Clear selected order
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