// stores/orderStore.js - Fixed version with persistent filters
import { defineStore } from 'pinia'
import { ref, computed, reactive } from 'vue'
import axios from 'axios'
import { notify } from '@/utils/toast'

export const useOrderStore = defineStore('orders', () => {
  // State
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
  const deliveryDateRange = ref([null, null])
  const statusDateRange = ref([null, null])
  const createdDateRange = ref([null, null])
  const orderSearch = ref('')

  // NEW: Store the last applied filters
  const lastAppliedFilters = ref({})

  // Options for dropdowns
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
  const statusOptions = ref([])

  // Pagination
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0,
    from: 0,
    to: 0
  })

  // NEW: Items per page options
  const perPageOptions = [15, 30, 50, 100, 200]

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
    if (deliveryDateRange.value && deliveryDateRange.value.length > 0) count++
    if (statusDateRange.value && statusDateRange.value.length > 0) count++
    if (createdDateRange.value && createdDateRange.value.length > 0) count++
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
    deliveryDateRange: deliveryDateRange.value,
    statusDateRange: statusDateRange.value,
    createdDateRange: createdDateRange.value,
    search: orderSearch.value
  }))

  // Helper function to format dates
  const formatDate = (date) => {
    if (!date) return null
    const d = new Date(date)
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    const day = String(d.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  // NEW: Build filter params from current filter state
  const buildFilterParams = () => {
    const filters = {}

    if (orderFilterStatus.value) filters.status = orderFilterStatus.value
    if (orderFilterProduct.value) filters.product_id = orderFilterProduct.value
    if (orderFilterZone.value) filters.zone_id = orderFilterZone.value
    if (orderFilterAgent.value) filters.agent_id = orderFilterAgent.value
    if (orderFilterRider.value) filters.rider_id = orderFilterRider.value
    if (orderFilterVendor.value) filters.vendor_id = orderFilterVendor.value
    if (orderFilterCity.value) filters.city_id = orderFilterCity.value
    if (orderFilterCategory.value) filters.category_id = orderFilterCategory.value
    if (orderSearch.value) filters.search = orderSearch.value

    // Format dates properly
    if (deliveryDateRange.value && deliveryDateRange.value.length === 2) {
      filters.delivery_from = formatDate(deliveryDateRange.value[0])
      filters.delivery_to = formatDate(deliveryDateRange.value[1])
    }
    if (statusDateRange.value && statusDateRange.value.length === 2) {
      filters.status_from = formatDate(statusDateRange.value[0])
      filters.status_to = formatDate(statusDateRange.value[1])
    }
    if (createdDateRange.value && createdDateRange.value.length === 2) {
      filters.created_from = formatDate(createdDateRange.value[0])
      filters.created_to = formatDate(createdDateRange.value[1])
    }

    return filters
  }

  // MODIFIED: Fetch orders with persistent filters
  const fetchOrders = async (params = {}) => {
    loading.value.orders = true
    error.value = null
    try {
      // Build query parameters - merge with last applied filters
      const queryParams = new URLSearchParams()

      // Combine params with lastAppliedFilters (params take precedence)
      const finalParams = { ...lastAppliedFilters.value, ...params }

      // Add pagination
      if (finalParams.page) queryParams.append('page', finalParams.page)
      if (finalParams.per_page) queryParams.append('per_page', finalParams.per_page)

      // Add filters
      if (finalParams.status) queryParams.append('status', finalParams.status)
      if (finalParams.vendor_id) queryParams.append('vendor_id', finalParams.vendor_id)
      if (finalParams.city_id) queryParams.append('city_id', finalParams.city_id)
      if (finalParams.agent_id) queryParams.append('agent_id', finalParams.agent_id)
      if (finalParams.rider_id) queryParams.append('rider_id', finalParams.rider_id)
      if (finalParams.zone_id) queryParams.append('zone_id', finalParams.zone_id)
      if (finalParams.product_id) queryParams.append('product_id', finalParams.product_id)
      if (finalParams.category_id) queryParams.append('category_id', finalParams.category_id)
      if (finalParams.search) queryParams.append('search', finalParams.search)
      
      // Add date range filters
      if (finalParams.delivery_from) queryParams.append('delivery_from', finalParams.delivery_from)
      if (finalParams.delivery_to) queryParams.append('delivery_to', finalParams.delivery_to)
      if (finalParams.status_from) queryParams.append('status_from', finalParams.status_from)
      if (finalParams.status_to) queryParams.append('status_to', finalParams.status_to)
      if (finalParams.created_from) queryParams.append('created_from', finalParams.created_from)
      if (finalParams.created_to) queryParams.append('created_to', finalParams.created_to)

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
      throw err
    } finally {
      loading.value.orders = false
    }
  }

  // MODIFIED: Apply filters and save them
  const applyFilters = async () => {
    const filters = buildFilterParams()
    
    // Save the filters for future pagination
    lastAppliedFilters.value = { ...filters, per_page: pagination.value.per_page }
    
    // Reset to first page when applying filters
    filters.page = 1
    filters.per_page = pagination.value.per_page
    
    await fetchOrders(filters)
  }

  // NEW: Change items per page
  const changePerPage = async (perPage) => {
    pagination.value.per_page = perPage
    const filters = buildFilterParams()
    lastAppliedFilters.value = { ...filters, per_page: perPage }
    await fetchOrders({ ...filters, page: 1, per_page: perPage })
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
    deliveryDateRange.value = []
    statusDateRange.value = []
    createdDateRange.value = []
    orderSearch.value = ''
    lastAppliedFilters.value = {}
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
        responseType: 'blob'
      })
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', 'waybills.pdf')
      document.body.appendChild(link)
      link.click()
      link.remove()
      notify.success('Waybills printed successfully')
    } catch (err) {
      notify.error('Failed to print waybills')
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

      orders.value.unshift(result.data)
      pagination.value.total += 1

      notify.success('Order created successfully')
      return result.data
    } catch (err) {
      notify.error('Failed to create order')
      error.value = err.response?.data?.message || err.message || 'Failed to create order'
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

      const index = orders.value.findIndex(order => order.id === id)
      if (index !== -1) {
        orders.value[index] = result.data
      }

      if (selectedOrderId.value === id) {
        selectedOrder.value = result.data
      }

      return result.data
    } catch (err) {
      notify.error('Failed to update order')
      error.value = err.response?.data?.message || err.message || 'Failed to update order'
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

      const index = orders.value.findIndex(order => order.id === id)
      if (index !== -1) {
        orders.value.splice(index, 1)
        pagination.value.total -= 1
      }
      notify.success('Order deleted successfully')
    } catch (err) {
      notify.error('Failed to delete order')
      error.value = err.response?.data?.message || err.message || 'Failed to delete order'
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
      throw new Error(message)
    }
  }

  const updateOrderCustomer = async (id, customerData) => {
    return updateOrder(id, { customer: customerData })
  }

  const updateOrderItems = async (id, items) => {
    return updateOrder(id, { order_items: items })
  }

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
      throw err
    } finally {
      loading.value.options = false
    }
  }

  const openDialog = async (orderId) => {
    selectedOrderId.value = orderId
    isCreateMode.value = false
    dialog.value = true

    if (orderId) {
      const order = await loadOrder(orderId)
      selectedOrder.value = order
      return order
    }
  }

  const openCreateDialog = () => {
    dialog.value = true
    selectedOrderId.value = null
    selectedOrder.value = null
    isCreateMode.value = true
  }

  const closeDialog = () => {
    dialog.value = false
    selectedOrderId.value = null
    selectedOrder.value = null
  }

  const setSelectedOrders = (orders) => {
    selectedOrders.value = orders
  }

  const openAssignDeliveryDialog = (selectedOrdersParam = []) => {
    console.debug('openAssignDeliveryDialog called with:', selectedOrdersParam)
    selectedOrders.value = selectedOrdersParam
    dialogType.value = 'assignDelivery'
    dialogTitle.value = 'Assign Delivery Person'
    bulkActionDialog.value = true
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
    Object.keys(bulkActionForm).forEach(key => {
      bulkActionForm[key] = ''
    })
  }

  const handleBulkAction = async (actionData) => {
    loading.value.updating = true
    try {
      const { type, data, orders } = actionData
      const targetOrders = Array.isArray(orders) && orders.length > 0 ? orders : selectedOrders.value

      switch (type) {
        case 'assignDelivery':
          if (!data.deliveryPerson) throw new Error("No delivery person selected")
          await assignDeliveryPersonToOrders(targetOrders, data.deliveryPerson)
          break
        case 'assignCallCentre':
          if (!data.callCentreAgent) throw new Error("No call centre agent selected")
          await assignCallCentreAgentToOrders(targetOrders, data.callCentreAgent)
          break
        case 'status':
          if (!data.status) throw new Error("No status selected")
          await updateOrdersStatus(targetOrders, data.status)
          break
        case 'delete':
          await deleteOrders(targetOrders)
          break
      }

      closeBulkActionDialog()
    } catch (error) {
      console.error('Bulk action failed:', error)
    } finally {
      loading.value.updating = false
    }
  }

  const assignDeliveryPersonToOrders = async (orders = [], deliveryPersonId) => {
    if (!orders.length || !deliveryPersonId) {
      throw new Error('Orders and deliveryPersonId are required')
    }
    try {
      await axios.post('/api/v1/orders/assign-rider', {
        order_ids: orders,
        rider_id: deliveryPersonId
      })
      notify.success('Rider assigned successfully')
    } catch (err) {
      notify.error('Failed to assign rider')
      throw err
    }
  }

  const assignCallCentreAgentToOrders = async (orders = [], agentId) => {
    if (!orders.length || !agentId) {
      throw new Error('Orders and agentId are required')
    }
    try {
      await axios.post('/api/v1/orders/assign-agent', {
        order_ids: orders,
        agent_id: agentId
      })
      notify.success('Call centre agent assigned successfully')
    } catch (err) {
      notify.error('Failed to assign call centre agent')
      throw err
    }
  }

  const updateOrdersStatus = async (orders = [], status) => {
    if (!orders.length || !status) {
      throw new Error('Orders and status are required')
    }
    try {
      await axios.post('/api/v1/orders/update-status', {
        order_ids: orders,
        status: status
      })
      notify.success('Order status updated successfully')
    } catch (err) {
      notify.error('Failed to update order status')
      throw err
    }
  }

  const deleteOrders = async (orderIds = []) => {
    if (!orderIds.length) {
      throw new Error('No orders selected for deletion')
    }
    try {
      await axios.post('/api/v1/orders/bulk-delete', {
        order_ids: orderIds
      })
      if (Array.isArray(orders.value)) {
        orderIds.forEach(id => {
          const index = orders.value.findIndex(order => order.id === id)
          if (index !== -1) {
            orders.value.splice(index, 1)
            pagination.value.total -= 1
          }
        })
      }
      notify.success('Orders deleted successfully')
    } catch (err) {
      notify.error('Failed to bulk delete orders')
      throw err
    }
  }

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
    deliveryDateRange,
    statusDateRange,
    createdDateRange,
    orderSearch,
    lastAppliedFilters,

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
    perPageOptions,

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
    changePerPage,
    initialize
  }
})