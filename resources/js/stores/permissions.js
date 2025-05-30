import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const usePermissionsStore = defineStore('permissions', () => {
  // State
  const permissions = ref([])
  const loading = ref(false)
  const error = ref(null)

  // API base URL - adjust according to your setup
  const API_BASE_URL = '/api/v1/admin'

  // Getters
  const permissionsCount = computed(() => permissions.value.length)
     
  const getPermissionById = computed(() => (id) =>
    permissions.value.find(p => p.id === id)
  )

  const searchPermissions = computed(() => (searchTerm) => {
    if (!searchTerm) return permissions.value
    return permissions.value.filter(p =>
      p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      (p.description && p.description.toLowerCase().includes(searchTerm.toLowerCase()))
    )
  })

  // Actions
  const fetchPermissions = async () => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.get(`${API_BASE_URL}/permissions`)
      permissions.value = response.data.data || response.data
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const createPermission = async (permissionData) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.post(`${API_BASE_URL}/permissions`, permissionData)
      const newPermission = response.data.data || response.data
      
      // Add to local state
      permissions.value.push(newPermission)
      
      return newPermission
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  // Note: Update is excluded in your routes, but including for completeness
  const updatePermission = async (id, permissionData) => {
    try {
      loading.value = true
      error.value = null
      
      // Since update is excluded in your routes, this would need a custom route
      // or you might want to remove this method entirely
      const response = await axios.put(`${API_BASE_URL}/permissions/${id}`, permissionData)
      const updatedPermission = response.data.data || response.data
      
      // Update local state
      const index = permissions.value.findIndex(p => p.id === id)
      if (index !== -1) {
        permissions.value[index] = updatedPermission
      }
      
      return updatedPermission
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const deletePermission = async (id) => {
    try {
      loading.value = true
      error.value = null
      
      await axios.delete(`${API_BASE_URL}/permissions/${id}`)
      
      // Remove from local state
      permissions.value = permissions.value.filter(p => p.id !== id)
      
      return true
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  // Additional methods for user-permission management
  const assignPermissionToUser = async (userId, permissionId) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.post(`${API_BASE_URL}/users/${userId}/assign-permission`, {
        permission_id: permissionId
      })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const removePermissionFromUser = async (userId, permissionId) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.post(`${API_BASE_URL}/users/${userId}/remove-permission`, {
        permission_id: permissionId
      })
      
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  // Reset state
  const resetState = () => {
    permissions.value = []
    loading.value = false
    error.value = null
  }

  return {
    // State
    permissions,
    loading,
    error,
    // Getters
    permissionsCount,
    getPermissionById,
    searchPermissions,
    // Actions
    fetchPermissions,
    createPermission,
    updatePermission,
    deletePermission,
    assignPermissionToUser,
    removePermissionFromUser,
    resetState
  }
})