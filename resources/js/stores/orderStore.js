// stores/orderStore.js - Fixed version
import { defineStore } from 'pinia'
import { ref, computed, reactive } from 'vue'
import axios from 'axios'

export const useOrderStore = defineStore('orders', () => {
  // State

  // Removed unused orderEdit to fix unused variable error


  const dialogType = ref('')
  const dialogTitle = ref('')
  const bulkActionForm = reactive({
    deliveryPerson: '',
    callCentreAgent: '',
    status: '',
    notes: '',
    zone: '',
    city: ''
  })
  const orders = ref([])
  const loading = ref({
    orders: false,
    options: false,
    creating: false,
    updating: false,
    deleting: false
  })
  const error = ref(null)
  const notifications = ref([])
  const dialog = ref(false)
  const bulkActionDialog = ref(false)
  const selectedOrderId = ref(null)
  const selectedOrder = ref(null)
  const initialized = ref(false)
  const isCreateMode = ref(false)

  // Filter states
  const orderFilterStatus = ref(null)
  const orderFilterProduct = ref(null)
  const orderFilterZone = ref(null)
  const orderFilterAgent = ref(null)
  const orderFilterRider = ref(null)
  const orderFilterVendor = ref(null)
  const orderFilterCity = ref(null)
  const orderFilterCategory = ref(null)
  const orderDateRange = ref([])
  const orderSearch = ref('')

  // Options for dropdowns (populated from API)
  const orderStatusOptions = ref([])
  const productOptions = ref([])
  const categoryOptions = ref([])
  const cityOptions = ref([])
  const zoneOptions = ref([])
  const agentOptions = ref([])
  const riderOptions = ref([])
  const vendorOptions = ref([])
  const clientOptions = ref([])
  const warehouseOptions = ref([])
  const countryOptions = ref([])
  const deliveryStatusOptions = ref([])
  const userOptions = ref([])
  const statusOptions = ref([

  ])

  // Pagination
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0
  })

  // Getters
  const getOrderById = computed(() => id => orders.value.find(order => order.id === id))
  const confirmedOrders = computed(() => orders.value.filter(order => order.status === 1))
  const pendingOrders = computed(() => orders.value.filter(order => order.status === 0))
  const cancelledOrders = computed(() => orders.value.filter(order => order.status === 2))
  const totalRevenue = computed(() => orders.value.reduce((sum, order) => sum + parseFloat(order.total_price || 0), 0))

  const activeFilterCount = computed(() => {
    let count = 0
    if (orderFilterStatus.value) count++
    if (orderFilterProduct.value) count++
    if (orderFilterZone.value) count++
    if (orderFilterAgent.value) count++
    if (orderFilterRider.value) count++
    if (orderFilterVendor.value) count++
    if (orderFilterCity.value) count++
    if (orderFilterCategory.value) count++
    if (orderDateRange.value && orderDateRange.value.length > 0) count++
    if (orderSearch.value) count++
    return count
  })

  const currentFilters = computed(() => ({
    status: orderFilterStatus.value,
    product: orderFilterProduct.value,
    zone: orderFilterZone.value,
    agent: orderFilterAgent.value,
    rider: orderFilterRider.value,
    vendor: orderFilterVendor.value,
    city: orderFilterCity.value,
    category: orderFilterCategory.value,
    dateRange: orderDateRange.value,
    search: orderSearch.value
  }))

  // Actions
  const fetchOrders = async (params = {}) => {
    loading.value.orders = true
    error.value = null
    try {
      // Build query parameters
      const queryParams = new URLSearchParams()

      // Add pagination
      if (params.page) queryParams.append('page', params.page)
      if (params.per_page) queryParams.append('per_page', params.per_page)

      // Add filters
      if (params.status) queryParams.append('status', params.status)
      if (params.vendor_id) queryParams.append('vendor_id', params.vendor_id)
      if (params.city) queryParams.append('city', params.city)
      if (params.agent_id) queryParams.append('agent_id', params.agent_id)
      if (params.rider_id) queryParams.append('rider_id', params.rider_id)
      if (params.zone_id) queryParams.append('zone_id', params.zone_id)
      if (params.product_id) queryParams.append('product_id', params.product_id)
      if (params.category_id) queryParams.append('category_id', params.category_id)
      if (params.search) queryParams.append('search', params.search)
      if (params.created_from) queryParams.append('created_from', params.created_from)
      if (params.created_to) queryParams.append('created_to', params.created_to)

      const response = await axios.get(`/api/v1/orders?${queryParams.toString()}`, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      const result = response.data
      if (!result.success) {
        throw new Error(result.message || 'Failed to fetch orders')
      }

      // Reset create mode when fetching orders
      isCreateMode.value = false

      const data = result.data
      orders.value = data.data || []

      // Update pagination
      pagination.value = {
        current_page: data.current_page || 1,
        last_page: data.last_page || 1,
        per_page: data.per_page || 15,
        total: data.total || 0,
        from: data.from || 0,
        to: data.to || 0
      }

      return data
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed to fetch orders'
      // addNotification({ type: 'error', message: error.value })
      throw err
    } finally {
      loading.value.orders = false
    }
  }


  const selectedOrders = ref([])






  const printOrders = async (orderIds) => {
    if (!orderIds || orderIds.length === 0) return
    try {
      const response = await axios.post('/api/v1/bulk-print-waybills', {
        order_ids: orderIds
      }, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        responseType: 'blob' // If you expect a PDF or file
      })
      // Handle file download (example for PDF)
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', 'waybills.pdf')
      document.body.appendChild(link)
      link.click()
      link.remove()
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed to print waybill'
      throw err
    }
  }

  const loadOrder = async (id) => {
    loading.value.orders = true
    error.value = null
    try {
      const order = await getOrderDetails(id)
      selectedOrder.value = order
      selectedOrderId.value = id
      return order
    } catch (err) {
      error.value = err.message || 'Failed to load order'
      throw err
    } finally {
      loading.value.orders = false
    }
  }

  const createOrder = async (orderData) => {
    loading.value.creating = true
    error.value = null
    try {
      const response = await axios.post('/api/v1/orders', orderData, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      const result = response.data
      if (!result.success) {
        throw new Error(result.message || 'Failed to create order')
      }

      // Add new order to the beginning of the list
      orders.value.unshift(result.data)
      pagination.value.total += 1

      // addNotification({ type: 'success', message: 'Order created successfully' })
      return result.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed to create order'
      // addNotification({ type: 'error', message: error.value })
      throw err
    } finally {
      loading.value.creating = false
    }
  }

  const updateOrder = async (id, orderData) => {
    loading.value.updating = true
    error.value = null
    try {
      const response = await axios.put(`/api/v1/orders/${id}`, orderData, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      const result = response.data
      if (!result.success) {
        throw new Error(result.message || 'Failed to update order')
      }

      // Update order in local state
      const index = orders.value.findIndex(order => order.id === id)
      if (index !== -1) {
        orders.value[index] = result.data
      }

      // Update selected order if it's the same
      if (selectedOrderId.value === id) {
        selectedOrder.value = result.data
      }

      // addNotification({ type: 'success', message: 'Order updated successfully' })
      return result.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed to update order'
      // addNotification({ type: 'error', message: error.value })
      throw err
    } finally {
      loading.value.updating = false
    }
  }

  const deleteOrder = async (id) => {
    loading.value.deleting = true
    error.value = null
    try {
      const response = await axios.delete(`/api/v1/orders/${id}`, {
        headers: {
          'Accept': 'application/json'
        }
      })

      const result = response.data
      if (!result.success) {
        throw new Error(result.message || 'Failed to delete order')
      }

      // Remove order from local state
      const index = orders.value.findIndex(order => order.id === id)
      if (index !== -1) {
        orders.value.splice(index, 1)
        pagination.value.total -= 1
      }

      // addNotification({ type: 'success', message: 'Order deleted successfully' })
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Failed to delete order'
      // addNotification({ type: 'error', message: error.value })
      throw err
    } finally {
      loading.value.deleting = false
    }
  }

  const getOrderDetails = async (id) => {
    try {
      const response = await axios.get(`/api/v1/orders/${id}`, {
        headers: {
          'Accept': 'application/json'
        }
      })

      const result = response.data
      if (!result.success) {
        throw new Error(result.message || 'Failed to fetch order details')
      }

      return result.data
    } catch (err) {
      const message = err.response?.data?.message || err.message || 'Failed to fetch order details'
      // addNotification({ type: 'error', message })
      throw new Error(message)
    }
  }

  const updateOrderCustomer = async (id, customerData) => {
    return updateOrder(id, { customer: customerData })
  }

  const updateOrderItems = async (id, items) => {
    return updateOrder(id, { order_items: items })
  }

  // Fetch dropdown options with proper loading management
  const fetchDropdownOptions = async (vendorId = null) => {
    if (loading.value.options) {
      console.log('Options already loading, skipping...')
      return
    }

    loading.value.options = true
    try {
      console.log('Fetching dropdown options...')

      const requests = [
        axios.get('/api/v1/statuses').catch(() => ({ data: { data: [] } })),
        axios.get('/api/v1/categories').catch(() => ({ data: { data: [] } })),
        axios.get('/api/v1/cities').catch(() => ({ data: { data: [] } })),
        axios.get('/api/v1/zones').catch(() => ({ data: { data: [] } })),
        axios.get('/api/v1/agents').catch(() => ({ data: { data: [] } })),
        axios.get('/api/v1/riders').catch(() => ({ data: { data: [] } })),
        axios.get('/api/v1/vendors').catch(() => ({ data: { data: [] } })),
        axios.get('/api/v1/countries').catch(() => ({ data: { data: [] } })),
        axios.get('/api/v1/warehouses').catch(() => ({ data: { data: [] } })),
        axios.get('/api/v1/clients').catch(() => ({ data: { data: [] } })),
      ]

      // Add products request if vendorId is provided
      if (vendorId) {
        requests.push(
          axios.get(`/api/v1/vendors/${vendorId}/products`).catch(() => ({ data: { success: false, data: [] } }))
        )
      } else {
        requests.push(
          axios.get('/api/v1/products').catch(() => ({ data: { success: false, data: [] } }))
        )
      }


      const normalize = (res) => {
        if (!res || !res.data) return []
        if (Array.isArray(res.data)) return res.data
        if (Array.isArray(res.data.data)) return res.data.data
        return []
      }


      const [
        statusesRes,
        categoriesRes,
        citiesRes,
        zonesRes,
        agentsRes,
        ridersRes,
        vendorsRes,
        countriesRes,
        warehousesRes,
        clientsRes,
        productsRes
      ] = await Promise.all(requests)

      // Set options with fallback to empty arrays
      statusOptions.value = normalize(statusesRes)
      categoryOptions.value = normalize(categoriesRes)
      cityOptions.value = normalize(citiesRes)
      zoneOptions.value = normalize(zonesRes)
      agentOptions.value = normalize(agentsRes)
      riderOptions.value = normalize(ridersRes)
      vendorOptions.value = normalize(vendorsRes)
      countryOptions.value = normalize(countriesRes)
      warehouseOptions.value = normalize(warehousesRes)
      clientOptions.value = normalize(clientsRes)
      productOptions.value = normalize(productsRes)

      console.log('Dropdown options loaded successfully')
    } catch (err) {
      console.error('Failed to fetch dropdown options:', err)
      // addNotification({ type: 'error', message: 'Failed to load dropdown options' })
      throw err
    } finally {
      loading.value.options = false
    }
  }

  // Notification helpers
  // const addNotification = (notification) => {
  //   notifications.value.push({
  //     id: Date.now(),
  //     timestamp: new Date(),
  //     ...notification
  //   })
  // }

  // const removeNotification = (id) => {
  //   notifications.value = notifications.value.filter(n => n.id !== id)
  // }

  // Dialog helpers
  const openDialog = async (orderId) => {
    selectedOrderId.value = orderId
    isCreateMode.value = false

    dialog.value = true

    if (orderId) {
      const order = await loadOrder(orderId)

      selectedOrder.value = order  // Make sure this line exists
      return order
      // Pass the loaded order to the form
      // For example, if you have a form state or method, set it here:
      // form.value = { ...order }
      // Replace 'form' with your actual form state variable
    }
  }

  const openCreateDialog = () => {
    dialog.value = true
    selectedOrderId.value = null
    selectedOrder.value = null
    isCreateMode.value = true
    // If you have a resetForm function, call it here. Otherwise, remove the line below.
    // resetForm()
  }

  // Add this function to fix the error
  // const openAssignDeliveryDialog = (selectedOrdersParam = []) => {
  //   bulkActionDialog.value = true
  //   selectedOrders.value = selectedOrdersParam
  //   // You can add more logic if needed
  // }

  const closeDialog = () => {
    dialog.value = false
    selectedOrderId.value = null
    selectedOrder.value = null
  }

  // Filter helpers
  const clearAllFilters = () => {
    orderFilterStatus.value = null
    orderFilterProduct.value = null
    orderFilterZone.value = null
    orderFilterAgent.value = null
    orderFilterRider.value = null
    orderFilterVendor.value = null
    orderFilterCity.value = null
    orderFilterCategory.value = null
    orderDateRange.value = []
    orderSearch.value = ''
  }

  const applyFilters = async () => {
    const filters = {}

    if (orderFilterStatus.value) filters.status = orderFilterStatus.value
    if (orderFilterProduct.value) filters.product_id = orderFilterProduct.value
    if (orderFilterZone.value) filters.zone_id = orderFilterZone.value
    if (orderFilterAgent.value) filters.agent_id = orderFilterAgent.value
    if (orderFilterRider.value) filters.rider_id = orderFilterRider.value
    if (orderFilterVendor.value) filters.vendor_id = orderFilterVendor.value
    if (orderFilterCity.value) filters.city = orderFilterCity.value
    if (orderFilterCategory.value) filters.category_id = orderFilterCategory.value
    if (orderSearch.value) filters.search = orderSearch.value

    if (orderDateRange.value && orderDateRange.value.length === 2) {
      filters.created_from = orderDateRange.value[0]
      filters.created_to = orderDateRange.value[1]
    }

    // Reset to first page when applying filters
    filters.page = 1
    await fetchOrders(filters)
  }

  // Initialization
  const initialize = async () => {
    if (initialized.value) return

    try {
      await Promise.all([
        fetchDropdownOptions(),
        fetchOrders({ page: 1 })
      ])
      initialized.value = true
    } catch (err) {
      console.error('Failed to initialize order store:', err)
    }
  }


  // Actions
  const setSelectedOrders = (ordersList) => {
    selectedOrders.value = ordersList
  }

  const openAssignDeliveryDialog = (selectedOrdersParam = []) => {
    console.debug('openAssignDeliveryDialog called with:', selectedOrdersParam)
    selectedOrders.value = selectedOrdersParam
    dialogType.value = 'assignDelivery'
    dialogTitle.value = 'Assign Delivery Person'
    bulkActionDialog.value = true
    console.debug('Bulk action dialog opened. State:', {
      selectedOrders: selectedOrders.value,
      dialogType: dialogType.value,
      dialogTitle: dialogTitle.value,
      bulkActionDialog: bulkActionDialog.value
    })
  }

  const openAssignCallCentreDialog = (selectedOrdersParam = []) => {
    selectedOrders.value = selectedOrdersParam
    dialogType.value = 'assignCallCentre'
    dialogTitle.value = 'Assign Call Centre Agent'
    bulkActionDialog.value = true
  }

  const openBulkStatusDialog = (selectedOrdersParam = []) => {
    selectedOrders.value = selectedOrdersParam
    dialogType.value = 'status'
    dialogTitle.value = 'Change Status'
    bulkActionDialog.value = true
  }

  const openDeleteDialog = (selectedOrdersParam = []) => {
    selectedOrders.value = selectedOrdersParam
    dialogType.value = 'delete'
    dialogTitle.value = 'Delete Orders'
    bulkActionDialog.value = true
  }

  const closeBulkActionDialog = () => {
    bulkActionDialog.value = false
    dialogType.value = ''
    dialogTitle.value = ''
    selectedOrders.value = []
    // Reset form
    Object.keys(bulkActionForm).forEach(key => {
      bulkActionForm[key] = ''
    })
  }

  const handleBulkAction = async (actionData) => {
    loading.value.updating = true
    try {
      const { type, data, orders: ordersList } = actionData

      switch (type) {
        case 'assignDelivery':
          await assignDeliveryPersonToOrders(ordersList, data.deliveryPerson)
          break
        case 'assignCallCentre':
          await assignCallCentreAgentToOrders(ordersList, data.callCentreAgent)
          break
        case 'status':
          await updateOrdersStatus(ordersList, data)
          break
        case 'delete':
          await deleteOrders(ordersList)
          break
      }

      closeBulkActionDialog()
    } catch (error) {
      console.error('Bulk action failed:', error)
      // Handle error (show notification, etc.)
    } finally {
      loading.value.updating = false
    }
  }

  // API calls (you'll need to implement these based on your backend)
  const assignDeliveryPersonToOrders = async (ordersList, deliveryPersonId) => {
    // Make API call to assign delivery person
    console.log('Assigning delivery person:', deliveryPersonId, 'to orders:', ordersList)
    // Example API call:
    // await $fetch('/api/orders/assign-delivery', {
    //   method: 'POST',
    //   body: { orderIds: ordersList.map(o => o.id), deliveryPersonId }
    // })
  }

  const assignCallCentreAgentToOrders = async (ordersList, agentId) => {
    // Make API call to assign call centre agent
    console.log('Assigning call centre agent:', agentId, 'to orders:', ordersList)
  }

  const updateOrdersStatus = async (ordersList, data) => {
    // Make API call to update status
    console.log('Updating status for orders:', ordersList, 'with data:', data)
  }

  const deleteOrders = async (ordersList) => {
    // Make API call to delete orders
    console.log('Deleting orders:', ordersList)
  }

  // Return all state, getters, and actions
  return {
    // State
    orders,
    loading,
    error,
    notifications,
    dialog,
    bulkActionDialog,
    selectedOrderId,
    selectedOrder,
    initialized,
    isCreateMode,

    // Filters
    orderFilterStatus,
    orderFilterProduct,
    orderFilterZone,
    orderFilterAgent,
    orderFilterRider,
    orderFilterVendor,
    orderFilterCity,
    orderFilterCategory,
    orderDateRange,
    orderSearch,

    // Options
    orderStatusOptions,
    productOptions,
    categoryOptions,
    cityOptions,
    zoneOptions,
    agentOptions,
    riderOptions,
    vendorOptions,
    clientOptions,
    warehouseOptions,
    countryOptions,
    deliveryStatusOptions,
    userOptions,
    statusOptions,

    // Pagination
    pagination,

    // Getters
    getOrderById,
    confirmedOrders,
    pendingOrders,
    cancelledOrders,
    totalRevenue,
    activeFilterCount,
    currentFilters,

    // Actions
    printOrders,
    fetchOrders,
    loadOrder,
    createOrder,
    updateOrder,
    updateOrderCustomer,
    updateOrderItems,
    deleteOrder,
    getOrderDetails,
    fetchDropdownOptions,
    // addNotification,
    // removeNotification,
    openDialog,
    openCreateDialog,
    openAssignDeliveryDialog,
    openAssignCallCentreDialog,
    openBulkStatusDialog,
    dialogType,
    dialogTitle,
    bulkActionForm,
    openDeleteDialog,
    closeBulkActionDialog,
    handleBulkAction,
    closeDialog,
    clearAllFilters,
    applyFilters,
    initialize
  }
})