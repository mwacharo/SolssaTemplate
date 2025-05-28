// stores/audit.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuditStore = defineStore('audit', () => {
  // State
  const auditLogs = ref([
    {
      id: 1,
      action: 'Role Created',
      description: 'Created new role "Content Moderator"',
      user: { name: 'Admin User', avatar: 'https://randomuser.me/api/portraits/men/5.jpg' },
      timestamp: new Date('2024-01-15T10:30:00'),
      details: { roleId: 6, roleName: 'Content Moderator' }
    },
    {
      id: 2,
      action: 'Permission Updated',
      description: 'Updated permissions for role "Editor"',
      user: { name: 'John Doe', avatar: 'https://randomuser.me/api/portraits/men/1.jpg' },
      timestamp: new Date('2024-01-15T09:15:00'),
      details: { roleId: 4, addedPermissions: ['manage_content'] }
    },
    {
      id: 3,
      action: 'User Suspended',
      description: 'Suspended user "Bob Wilson"',
      user: { name: 'Admin User', avatar: 'https://randomuser.me/api/portraits/men/5.jpg' },
      timestamp: new Date('2024-01-14T15:45:00'),
      details: { userId: 3, reason: 'Policy violation' }
    }
  ])

  const loading = ref(false)
  const error = ref(null)

  // Getters
  const recentChangesCount = computed(() => {
    const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000)
    return auditLogs.value.filter(log => log.timestamp > oneDayAgo).length
  })

  const getAuditColor = computed(() => (action) => {
    const colorMap = {
      'Role Created': 'success',
      'Role Updated': 'info',
      'Role Deleted': 'error',
      'Permission Updated': 'warning',
      'User Assigned': 'primary',
      'User Suspended': 'error'
    }
    return colorMap[action] || 'grey'
  })

  const getAuditIcon = computed(() => (action) => {
    const iconMap = {
      'Role Created': 'mdi-plus-circle',
      'Role Updated': 'mdi-pencil-circle',
      'Role Deleted': 'mdi-delete-circle',
      'Permission Updated': 'mdi-key-change',
      'User Assigned': 'mdi-account-plus',
      'User Suspended': 'mdi-account-cancel'
    }
    return iconMap[action] || 'mdi-information'
  })

  const filterAuditLogs = computed(() => (actionFilter = null, dateRange = null) => {
    let filtered = auditLogs.value

    if (actionFilter) {
      filtered = filtered.filter(log => log.action === actionFilter)
    }

    if (dateRange && dateRange.length === 2) {
      const [startDate, endDate] = dateRange
      filtered = filtered.filter(log => 
        log.timestamp >= startDate && log.timestamp <= endDate
      )
    }

    return filtered.sort((a, b) => b.timestamp - a.timestamp)
  })

  // Actions
  const logAction = async (action, description, details = {}) => {
    try {
      const newLog = {
        id: Math.max(0, ...auditLogs.value.map(l => l.id)) + 1,
        action,
        description,
        user: { 
          name: 'Current User', // Replace with actual current user
          avatar: 'https://randomuser.me/api/portraits/men/1.jpg' 
        },
        timestamp: new Date(),
        details
      }
      
      auditLogs.value.unshift(newLog)
      
      // Optional: Send to API
      // await api.post('/audit-logs', newLog)
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  const fetchAuditLogs = async (filters = {}) => {
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

  return {
    // State
    auditLogs,
    loading,
    error,
    // Getters
    recentChangesCount,
    getAuditColor,
    getAuditIcon,
    filterAuditLogs,
    // Actions
    logAction,
    fetchAuditLogs
  }
})