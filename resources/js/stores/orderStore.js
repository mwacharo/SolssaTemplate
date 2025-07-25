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
    
    // Dialog states (if needed for order details)
    dialog: false,
    selectedOrderId: null,
    
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
        const response = await fetch(`/api/orders/${orderId}`)
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`)
        }
        const order = await response.json()
        return order
      } catch (error) {
        console.error('Error loading order:', error)
        throw error
      } finally {
        this.loading.orders = false
      }
    },

    // Dialog actions
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

      // Load order details but don't let it affect dialog state
      try {
      await this.loadOrder(id)
      } catch (error) {
      console.error('Error loading order:', error)
      }

      // Ensure dialog is still true after loading
      if (!this.dialog) {
      console.log('Dialog was somehow set to false, correcting...')
      this.dialog = true
      }

      console.log('openDialog completed, final dialog state:', this.dialog)
    },

    closeDialog() {
      this.dialog = false
      this.selectedOrderId = null
    },

    // Set pagination
    setPagination(pagination) {
      this.pagination = { ...this.pagination, ...pagination }
    },

    // Reset store to initial state
    $reset() {
      this.clearAllFilters()
      this.orders = []
      this.filteredOrders = []
      this.dialog = false
      this.selectedOrderId = null
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