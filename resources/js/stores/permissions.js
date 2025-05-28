import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const usePermissionsStore = defineStore('permissions', () => {
  // State
  const permissions = ref([
    { id: 1, name: 'create', description: 'Create resources' },
    { id: 2, name: 'read', description: 'Read resources' },
    { id: 3, name: 'update', description: 'Update resources' },
    { id: 4, name: 'delete', description: 'Delete resources' },
    { id: 5, name: 'manage_users', description: 'Manage users' },
    { id: 6, name: 'system_config', description: 'System configuration' },
  ])

  const loading = ref(false)
  const error = ref(null)

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
      // Replace with actual API call
      // const response = await api.get('/permissions')
      // permissions.value = response.data
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const createPermission = async (permissionData) => {
    try {
      loading.value = true
      error.value = null
      
      // API call
      // const response = await api.post('/permissions', permissionData)
      
      // Mock implementation
      const newId = Math.max(0, ...permissions.value.map(p => p.id)) + 1
      const newPermission = { ...permissionData, id: newId }
      permissions.value.push(newPermission)
      
      return newPermission
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const updatePermission = async (id, permissionData) => {
    try {
      loading.value = true
      error.value = null
      
      // API call
      // await api.put(`/permissions/${id}`, permissionData)
      
      // Mock implementation
      const index = permissions.value.findIndex(p => p.id === id)
      if (index !== -1) {
        permissions.value[index] = { ...permissionData, id }
      }
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const deletePermission = async (id) => {
    try {
      loading.value = true
      error.value = null
      
      // API call
      // await api.delete(`/permissions/${id}`)
      
      // Mock implementation
      permissions.value = permissions.value.filter(p => p.id !== id)
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
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
    deletePermission
  }
})