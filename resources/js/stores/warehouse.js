// stores/warehouse.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { notify } from '@/utils/toast'

export const useWarehouseStore = defineStore('warehouse', () => {
  // State
  const warehouses = ref([])
  const loading = ref(false)
  const currentWarehouse = ref(null)

  // Getters
  const getWarehouseById = computed(() => {
    return (id) => warehouses.value.find(w => w.id === id)
  })

  const warehousesCount = computed(() => warehouses.value.length)

  // Actions
  async function fetchWarehouses() {
    loading.value = true
    try {
      const { data } = await axios.get('/api/v1/warehouses')
      warehouses.value = data
      return data
    } catch (error) {
      notify.error('Failed to load warehouses')
      console.error('Error fetching warehouses:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function fetchWarehouse(id) {
    loading.value = true
    try {
      const { data } = await axios.get(`/api/v1/warehouses/${id}`)
      currentWarehouse.value = data
      return data
    } catch (error) {
      notify.error('Failed to load warehouse')
      console.error('Error fetching warehouse:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function createWarehouse(warehouseData) {
    loading.value = true
    try {
      const { data } = await axios.post('/api/v1/warehouses', warehouseData)
      warehouses.value.push(data)
      notify.success('Warehouse created successfully')
      return data
    } catch (error) {
      notify.error('Failed to create warehouse')
      console.error('Error creating warehouse:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function updateWarehouse(id, warehouseData) {
    loading.value = true
    try {
      const { data } = await axios.put(`/api/v1/warehouses/${id}`, warehouseData)
      const index = warehouses.value.findIndex(w => w.id === id)
      if (index !== -1) {
        warehouses.value[index] = data
      }
      if (currentWarehouse.value?.id === id) {
        currentWarehouse.value = data
      }
      notify.success('Warehouse updated successfully')
      return data
    } catch (error) {
      notify.error('Failed to update warehouse')
      console.error('Error updating warehouse:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  async function deleteWarehouse(id) {
    loading.value = true
    try {
      await axios.delete(`/api/v1/warehouses/${id}`)
      warehouses.value = warehouses.value.filter(w => w.id !== id)
      if (currentWarehouse.value?.id === id) {
        currentWarehouse.value = null
      }
      notify.success('Warehouse deleted successfully')
    } catch (error) {
      notify.error('Failed to delete warehouse')
      console.error('Error deleting warehouse:', error)
      throw error
    } finally {
      loading.value = false
    }
  }

  function setCurrentWarehouse(warehouse) {
    currentWarehouse.value = warehouse
  }

  function clearCurrentWarehouse() {
    currentWarehouse.value = null
  }

  function $reset() {
    warehouses.value = []
    loading.value = false
    currentWarehouse.value = null
  }

  return {
    // State
    warehouses,
    loading,
    currentWarehouse,
    
    // Getters
    getWarehouseById,
    warehousesCount,
    
    // Actions
    fetchWarehouses,
    fetchWarehouse,
    createWarehouse,
    updateWarehouse,
    deleteWarehouse,
    setCurrentWarehouse,
    clearCurrentWarehouse,
    $reset
  }
})