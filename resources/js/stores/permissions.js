import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const usePermissionsStore = defineStore('permissions', () => {
  // State
  const permissions = ref([])
  const loading = ref(false)
  const error = ref(null)
  const userDirectPermissions = ref([]) // For storing user's direct permissions

  // API base URL - adjust according to your setup
  const API_BASE_URL = '/api/v1/admin'

  // Getters
  const permissionsCount = computed(() => permissions.value.length)

  const getPermissionById = computed(() => (id) =>
    permissions.value.find(p => p && p.id === id)
  )

  const searchPermissions = computed(() => (searchTerm) => {
    if (!searchTerm) return permissions.value
    return permissions.value.filter(p =>
      p && p.name && p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      (p && p.description && p.description.toLowerCase().includes(searchTerm.toLowerCase()))
    )
  })

  // Actions
  const fetchPermissions = async () => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.get(`${API_BASE_URL}/permissions`)
      permissions.value = response.data.data || response.data || []

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error fetching permissions:', err)
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

      // Add to local state only if we have valid data
      if (newPermission && newPermission.id) {
        permissions.value.push(newPermission)
      }

      return newPermission
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error creating permission:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updatePermission = async (id, permissionData) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.put(`${API_BASE_URL}/permissions/${id}`, permissionData)
      const updatedPermission = response.data.data || response.data

      // Update local state only if we have valid data
      if (updatedPermission && updatedPermission.id) {
        const index = permissions.value.findIndex(p => p && p.id === id)
        if (index !== -1) {
          permissions.value[index] = updatedPermission
        }
      }

      return updatedPermission
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error updating permission:', err)
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
      permissions.value = permissions.value.filter(p => p && p.id !== id)

      return true
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error deleting permission:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Fixed assignPermissionToUser method with proper grant/revoke logic
  const assignPermissionToUser = async (userId, permissionId, shouldGrant = true) => {
    try {
      loading.value = true
      error.value = null

      const endpoint = shouldGrant ? 'assign-permission' : 'remove-permission'
      const response = await axios.post(`${API_BASE_URL}/users/${userId}/${endpoint}`, {
        permission_id: permissionId
      })
      // console.log('User permissions API response:', response.data)

      console.log('[DEBUG] User Permissions API Response:', JSON.stringify(response.data, null, 2));


      // Update local userDirectPermissions state
      if (shouldGrant) {


        if (!Array.isArray(userDirectPermissions.value)) {
  userDirectPermissions.value = []
}

        // Add permission if not already present
        const existingPermission = userDirectPermissions.value.find(p => 
          (p && p.id === permissionId) || (p && p.name === permissionId)
        )
        if (!existingPermission) {
          // Find the permission details from the main permissions list
          const permissionDetail = permissions.value.find(p => 
            (p && p.id === permissionId) || (p && p.name === permissionId)
          )
          if (permissionDetail) {
            userDirectPermissions.value.push(permissionDetail)
          }
        }
      } else {
        // Remove permission
        userDirectPermissions.value = userDirectPermissions.value.filter(p => 
          p && (p.id !== permissionId && p.name !== permissionId)
        )
      }

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error(`Error ${shouldGrant ? 'assigning' : 'removing'} permission:`, err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const removePermissionFromUser = async (userId, permissionId) => {
    return assignPermissionToUser(userId, permissionId, false)
  }

  // Fetch user's direct permissions (separate from role permissions)
  const fetchUserDirectPermissions = async (userId) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.get(`${API_BASE_URL}/users/${userId}/permissions`)
      userDirectPermissions.value = response.data.data || response.data || []
      console.log('[DEBUG] userDirectPermissions:', JSON.stringify(userDirectPermissions.value, null, 2))
      return userDirectPermissions.value
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error fetching user direct permissions:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Reset state
  const resetState = () => {
    permissions.value = []
    userDirectPermissions.value = []
    loading.value = false
    error.value = null
  }

  const clearError = () => {
    error.value = null
  }

  return {
    // State
    userDirectPermissions,
    permissions,
    loading,
    error,

    // Getters
    permissionsCount,
    getPermissionById,
    searchPermissions,

    // Actions
    fetchUserDirectPermissions,
    fetchPermissions,
    createPermission,
    updatePermission,
    deletePermission,
    assignPermissionToUser,
    removePermissionFromUser,
    resetState,
    clearError
  }
})