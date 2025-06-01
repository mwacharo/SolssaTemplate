// stores/roles.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { usePermissionsStore } from './permissions'
import axios from 'axios'

export const useRolesStore = defineStore('roles', () => {
  // State
  const roles = ref([])
  const loading = ref(false)
  const error = ref(null)

  // API base URL - adjust this to match your Laravel API
  const API_BASE_URL = '/api/v1/admin'

  // Getters
  const activeRoles = computed(() => roles.value.filter(role => role.active))
  
  const rolesCount = computed(() => roles.value.length)
  
  const totalUsers = computed(() => 
    roles.value.reduce((sum, role) => sum + (role.users_count || 0), 0)
  )

  const getRoleById = computed(() => (id) => 
    roles.value.find(role => role.id === id)
  )

  const searchRoles = computed(() => (searchTerm) => {
    if (!searchTerm) return roles.value
    return roles.value.filter(role => 
      role.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      role.description.toLowerCase().includes(searchTerm.toLowerCase())
    )
  })

  const roleNames = computed(() => roles.value.map(role => role.name))

  const getRoleName = (roleId) => {
  return roles.value.find(role => role.id === roleId)?.name
}

  // Helper function to handle API errors
  const handleApiError = (err) => {
    const message = err.response?.data?.message || err.message || 'An error occurred'
    error.value = message
    console.error('API Error:', err)
    return message
  }

  // Actions
  const fetchRoles = async () => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.get(`${API_BASE_URL}/roles`)
      roles.value = response.data.data || response.data
      
      return roles.value
    } catch (err) {
      handleApiError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const createRole = async (roleData) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.post(`${API_BASE_URL}/roles`, roleData)
      const newRole = response.data.data || response.data
      
      roles.value.push(newRole)
      
      return newRole
    } catch (err) {
      handleApiError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateRole = async (id, roleData) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.put(`${API_BASE_URL}/roles/${id}`, roleData)
      const updatedRole = response.data.data || response.data
      
      const index = roles.value.findIndex(r => r.id === id)
      if (index !== -1) {
        roles.value[index] = updatedRole
      }
      
      return updatedRole
    } catch (err) {
      handleApiError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteRole = async (id) => {
    try {
      loading.value = true
      error.value = null
      
      await axios.delete(`${API_BASE_URL}/roles/${id}`)
      
      roles.value = roles.value.filter(r => r.id !== id)
      
      return true
    } catch (err) {
      const message = handleApiError(err)
      // Check if error is about users assigned to role
      if (err.response?.status === 422 || message.includes('users')) {
        throw new Error('Cannot delete role with assigned users')
      }
      throw err
    } finally {
      loading.value = false
    }
  }

  const toggleRoleStatus = async (id) => {
    try {
      const role = getRoleById.value(id)
      if (role) {
        await updateRole(id, { active: !role.active })
      }
    } catch (err) {
      handleApiError(err)
      throw err
    }
  }

  const duplicateRole = async (id) => {
    try {
      const role = getRoleById.value(id)
      if (role) {
        const duplicatedRole = {
          name: `${role.name} (Copy)`,
          description: role.description,
          permissions: [...(role.permissions || [])],
          color: role.color,
          icon: role.icon,
          active: true
        }
        return await createRole(duplicatedRole)
      }
    } catch (err) {
      handleApiError(err)
      throw err
    }
  }

  // Additional role-related API calls
  const assignRoleToUser = async (userId, roleId) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.post(`${API_BASE_URL}/users/${userId}/assign-role`, {
        role_id: roleId
      })
      
      // Update local user count if role exists
      const role = getRoleById.value(roleId)
      if (role) {
        role.users_count = (role.users_count || 0) + 1
      }
      
      return response.data
    } catch (err) {
      handleApiError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const removeRoleFromUser = async (userId, roleId) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.post(`${API_BASE_URL}/users/${userId}/remove-role`, {
        role_id: roleId
      })
      
      // Update local user count if role exists
      const role = getRoleById.value(roleId)
      if (role && role.users_count > 0) {
        role.users_count = role.users_count - 1
      }
      
      return response.data
    } catch (err) {
      handleApiError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Batch operations
  const fetchRolesWithUsers = async () => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.get(`${API_BASE_URL}/roles?with_users=true`)
      roles.value = response.data.data || response.data
      
      return roles.value
    } catch (err) {
      handleApiError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const bulkUpdateRoles = async (updates) => {
    try {
      loading.value = true
      error.value = null
      
      const promises = updates.map(({ id, data }) => 
        axios.put(`${API_BASE_URL}/roles/${id}`, data)
      )
      
      const responses = await Promise.all(promises)
      
      // Update local state
      responses.forEach((response, index) => {
        const updatedRole = response.data.data || response.data
        const roleIndex = roles.value.findIndex(r => r.id === updates[index].id)
        if (roleIndex !== -1) {
          roles.value[roleIndex] = updatedRole
        }
      })
      
      return responses.map(r => r.data)
    } catch (err) {
      handleApiError(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Initialize store
  const init = async () => {
    try {
      await fetchRoles()
    } catch (err) {
      console.warn('Failed to initialize roles store:', err)
    }
  }

  // Clear store
  const clearStore = () => {
    roles.value = []
    error.value = null
    loading.value = false
  }

  return {
    // State
    roles,
    loading,
    error,
    
    // Getters
    activeRoles,
    rolesCount,
    totalUsers,
    getRoleById,
    searchRoles,
    roleNames,
    getRoleName,
    
    // Actions
    fetchRoles,
    createRole,
    updateRole,
    deleteRole,
    toggleRoleStatus,
    duplicateRole,
    assignRoleToUser,
    removeRoleFromUser,
    fetchRolesWithUsers,
    bulkUpdateRoles,
    init,
    clearStore
  }
})