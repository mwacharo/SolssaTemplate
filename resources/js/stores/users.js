// stores/users.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useRolesStore } from './roles'

export const useUsersStore = defineStore('users', () => {
  // State
  const users = ref([
    {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
      avatar: 'https://randomuser.me/api/portraits/men/1.jpg',
      role: 'Admin',
      status: 'Active',
      lastLogin: new Date('2024-01-15')
    },
    {
      id: 2,
      name: 'Jane Smith',
      email: 'jane@example.com',
      avatar: 'https://randomuser.me/api/portraits/women/2.jpg',
      role: 'Manager',
      status: 'Active',
      lastLogin: new Date('2024-01-14')
    },
    {
      id: 3,
      name: 'Bob Wilson',
      email: 'bob@example.com',
      avatar: 'https://randomuser.me/api/portraits/men/3.jpg',
      role: 'Editor',
      status: 'Suspended',
      lastLogin: new Date('2024-01-10')
    }
  ])

  const loading = ref(false)
  const error = ref(null)

  // Getters
  const activeUsers = computed(() => 
    users.value.filter(user => user.status === 'Active')
  )

  const activeUsersCount = computed(() => activeUsers.value.length)

  const getUserById = computed(() => (id) => 
    users.value.find(user => user.id === id)
  )

  const getUsersByRole = computed(() => (roleName) =>
    users.value.filter(user => user.role === roleName)
  )

  const searchUsers = computed(() => (searchTerm, roleFilter = null) => {
    let filtered = users.value

    if (roleFilter) {
      filtered = filtered.filter(user => user.role === roleFilter)
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
  const fetchUsers = async () => {
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

  const updateUserRole = async (userId, newRole) => {
    try {
      loading.value = true
      error.value = null
      
      const user = getUserById.value(userId)
      if (user) {
        const oldRole = user.role
        user.role = newRole
        
        // Update role user counts
        const rolesStore = useRolesStore()
        const oldRoleObj = rolesStore.roles.find(r => r.name === oldRole)
        const newRoleObj = rolesStore.roles.find(r => r.name === newRole)
        
        if (oldRoleObj) oldRoleObj.users_count--
        if (newRoleObj) newRoleObj.users_count++
      }
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const toggleUserStatus = async (userId) => {
    try {
      loading.value = true
      error.value = null
      
      const user = getUserById.value(userId)
      if (user) {
        user.status = user.status === 'Active' ? 'Suspended' : 'Active'
      }
    } catch (err) {
      error.value = err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    // State
    users,
    loading,
    error,
    // Getters
    activeUsers,
    activeUsersCount,
    getUserById,
    getUsersByRole,
    searchUsers,
    // Actions
    fetchUsers,
    updateUserRole,
    toggleUserStatus
  }
})
