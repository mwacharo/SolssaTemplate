// stores/users.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useRolesStore } from './roles'

export const useUsersStore = defineStore('users', () => {
  // State
  const users = ref([])
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 15,
    total: 0
  })

  // API base URL - adjust according to your setup
  const API_BASE_URL = '/api/v1/admin'

  // Getters
  const activeUsers = computed(() =>
    users.value.filter(user => user.status === 'active' || user.status === 'Active')
  )

  const activeUsersCount = computed(() => activeUsers.value.length)

  const getUserById = computed(() => (id) =>
    users.value.find(user => user.id == id)
  )

  const getUsersByRole = computed(() => (roleName) =>
    users.value.filter(user =>
      user.roles?.some(role => role.name === roleName) || user.role === roleName
    )
  )

  const searchUsers = computed(() => (searchTerm, roleFilter = null) => {
    let filtered = users.value

    if (roleFilter) {
      filtered = filtered.filter(user =>
        user.roles?.some(role => role.name === roleFilter) || user.role === roleFilter
      )
    }

    if (searchTerm) {
      filtered = filtered.filter(user =>
        user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        user.email.toLowerCase().includes(searchTerm.toLowerCase())
      )
    }

    return filtered
  })

  // Actions
  const fetchUsers = async (page = 1, params = {}) => {
    try {
      loading.value = true
      error.value = null

      const queryParams = {
        page,
        ...params
      }

      const response = await axios.get(`${API_BASE_URL}/users`, { params: queryParams })
      const responseData = response.data

      // Handle Laravel pagination structure
      if (responseData.data) {
        users.value = responseData.data
        pagination.value = {
          current_page: responseData.current_page,
          last_page: responseData.last_page,
          per_page: responseData.per_page,
          total: responseData.total
        }
      } else {
        users.value = responseData
      }

      return responseData
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const createUser = async (userData) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.post(`${API_BASE_URL}/users`, userData)
      const newUser = response.data.data || response.data

      // Add to local state
      users.value.push(newUser)

      return newUser
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateUser = async (userId, userData) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.put(`${API_BASE_URL}/users/${userId}`, userData)
      const updatedUser = response.data.data || response.data

      // Update local state
      const index = users.value.findIndex(user => user.id == userId)
      if (index !== -1) {
        users.value[index] = updatedUser
      }

      return updatedUser
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteUser = async (userId) => {
    try {
      loading.value = true
      error.value = null

      await axios.delete(`${API_BASE_URL}/users/${userId}`)

      // Remove from local state
      users.value = users.value.filter(user => user.id != userId)

      return true
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const showUser = async (userId) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.get(`${API_BASE_URL}/users/${userId}`)
      const userData = response.data.data || response.data

      return userData
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  // Role management methods
  const assignRoleToUser = async (userId, roleName) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.post(`${API_BASE_URL}/users/${userId}/assign-role`, {
        role: roleName
      })

      // Update local user data
      const user = getUserById.value(userId)
      if (user && response.data.data) {
        Object.assign(user, response.data.data)
      }

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
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

      // Update local user data
      const user = getUserById.value(userId)
      if (user && response.data.data) {
        Object.assign(user, response.data.data)
      }

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  // Permission management methods
  const assignPermissionToUser = async (userId, permissionId) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.post(`${API_BASE_URL}/users/${userId}/assign-permission`, {
        permission_id: permissionId
      })

      // Update local user data
      const user = getUserById.value(userId)
      if (user && response.data.data) {
        Object.assign(user, response.data.data)
      }

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

      // Update local user data
      const user = getUserById.value(userId)
      if (user && response.data.data) {
        Object.assign(user, response.data.data)
      }

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  // Legacy method for backward compatibility
  const updateUserRole = async (userId, role) => {
    return await assignRoleToUser(userId, role)
  }

  const toggleUserStatus = async (userId) => {
    try {
      loading.value = true
      error.value = null

      const user = getUserById.value(userId)
      if (!user) throw new Error('User not found')

      const newStatus = user.status === 'active' ? 'inactive' : 'active'

      const response = await axios.post(`${API_BASE_URL}/${userId}/toggle-status`)

      // Update local state
      const updatedUser = response.data.data || response.data
      const index = users.value.findIndex(u => u.id == userId)
      if (index !== -1) {
        users.value[index] = updatedUser
      }

      return updatedUser
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }
  const forcePasswordChange = async (userId) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.post(`${API_BASE_URL}/${userId}/force-password`)

      // Update local user data
      const user = getUserById.value(userId)
      if (user && response.data.data) {
        Object.assign(user, response.data.data)
      }

      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      throw err
    } finally {
      loading.value = false
    }
  }

 const sendPasswordResetLink = async (email) => {
    try {
      loading.value = true
      error.value = null

      const response = await axios.post(`${API_BASE_URL}/reset-link`, { email })

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
    users.value = []
    loading.value = false
    error.value = null
    pagination.value = {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    }
  }

  return {
    // State
    users,
    loading,
    error,
    pagination,
    // Getters
    activeUsers,
    activeUsersCount,
    getUserById,
    getUsersByRole,
    searchUsers,
    // Actions
    fetchUsers,
    createUser,
    updateUser,
    deleteUser,
    showUser,
    assignRoleToUser,
    removeRoleFromUser,
    assignPermissionToUser,
    removePermissionFromUser,
    updateUserRole, // Legacy method
    toggleUserStatus,
    resetState,
    forcePasswordChange,
    sendPasswordResetLink
    
  }
})