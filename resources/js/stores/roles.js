// stores/roles.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { usePermissionsStore } from './permissions'

export const useRolesStore = defineStore('roles', () => {
  // State
  const roles = ref([
    {
      id: 1,
      name: 'Super Admin',
      description: 'Full system access with all permissions',
      permissions: ['create', 'read', 'update', 'delete', 'manage_users', 'system_config'],
      users_count: 2,
      active: true,
      color: 'red',
      icon: 'mdi-shield-crown'
    },
    {
      id: 2,
      name: 'Admin',
      description: 'Administrative access with limited system config',
      permissions: ['create', 'read', 'update', 'delete', 'manage_users'],
      users_count: 8,
      active: true,
      color: 'orange',
      icon: 'mdi-shield-account'
    },
    {
      id: 3,
      name: 'Manager',
      description: 'Team management and reporting capabilities',
      permissions: ['create', 'read', 'update', 'reports'],
      users_count: 15,
      active: true,
      color: 'blue',
      icon: 'mdi-account-tie'
    },
    {
      id: 4,
      name: 'Editor',
      description: 'Content creation and editing permissions',
      permissions: ['create', 'read', 'update'],
      users_count: 32,
      active: true,
      color: 'green',
      icon: 'mdi-pencil'
    },
    {
      id: 5,
      name: 'Viewer',
      description: 'Read-only access to system resources',
      permissions: ['read'],
      users_count: 189,
      active: true,
      color: 'grey',
      icon: 'mdi-eye'
    }
  ])

  const loading = ref(false)
  const error = ref(null)

  // Getters
  const activeRoles = computed(() => roles.value.filter(role => role.active))
  
  const rolesCount = computed(() => roles.value.length)
  
  const totalUsers = computed(() => 
    roles.value.reduce((sum, role) => sum + role.users_count, 0)
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

  // Actions
  const fetchRoles = async () => {
    try {
      loading.value = true
      error.value = null
      // API call would go here
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const createRole = async (roleData) => {
    try {
      loading.value = true
      error.value = null
      
      const newId = Math.max(0, ...roles.value.map(r => r.id)) + 1
      const newRole = { 
        ...roleData, 
        id: newId, 
        users_count: 0,
        active: true 
      }
      roles.value.push(newRole)
      
      return newRole
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateRole = async (id, roleData) => {
    try {
      loading.value = true
      error.value = null
      
      const index = roles.value.findIndex(r => r.id === id)
      if (index !== -1) {
        roles.value[index] = { ...roles.value[index], ...roleData }
      }
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteRole = async (id) => {
    try {
      loading.value = true
      error.value = null
      
      // Check if role has users assigned
      const role = getRoleById.value(id)
      if (role && role.users_count > 0) {
        throw new Error('Cannot delete role with assigned users')
      }
      
      roles.value = roles.value.filter(r => r.id !== id)
    } catch (err) {
      error.value = err.message
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
      error.value = err.message
      throw err
    }
  }

  const duplicateRole = async (id) => {
    try {
      const role = getRoleById.value(id)
      if (role) {
        const duplicatedRole = {
          ...role,
          name: `${role.name} (Copy)`,
          users_count: 0
        }
        delete duplicatedRole.id
        return await createRole(duplicatedRole)
      }
    } catch (err) {
      error.value = err.message
      throw err
    }
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
    // Actions
    fetchRoles,
    createRole,
    updateRole,
    deleteRole,
    toggleRoleStatus,
    duplicateRole
  }
})

