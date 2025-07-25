// stores/orderStore.js
import { defineStore } from 'pinia'

export const useOrderStore = defineStore('order', {
  state: () => ({

    // orderdetails dialog state

    const openDialog = async (contactId) => {
    console.log('=== openDialog called ===')
    console.log('contactId:', contactId)
    
    selectedContactId.value = contactId
    dialog.value = true
    
    console.log('Dialog set to true:', dialog.value)
    
    // Load conversation but don't let it affect dialog state
    try {
        await loadConversation(contactId)
    } catch (error) {
        console.error('Error loading conversation:', error)
    }
    
    // Ensure dialog is still true after loading
    if (!dialog.value) {
        console.log('Dialog was somehow set to false, correcting...')
        dialog.value = true
    }
    
    console.log('openDialog completed, final dialog state:', dialog.value)
},

    const closeDialog = () => {
        dialog.value = false
        clearForm()
        selectedContactId.value = null
    },
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

    // Set pagination
    setPagination(pagination) {
      this.pagination = { ...this.pagination, ...pagination }
    },

    // Reset store to initial state
    $reset() {
      this.clearAllFilters()
      this.orders = []
      this.filteredOrders = []
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