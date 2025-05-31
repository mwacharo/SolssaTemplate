// stores/audit.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuditStore = defineStore('audit', () => {
  // State
  const auditLogs = ref([])
  const loading = ref(false)
  const error = ref(null)
  const filters = ref({
    action: null,
    startDate: null,
    endDate: null
  })

  // Getters
  const recentChangesCount = computed(() => {
    const oneDayAgo = new Date(Date.now() - 24 * 60 * 60 * 1000)
    return auditLogs.value.filter(log => new Date(log.timestamp) > oneDayAgo).length
  })

  const getAuditColor = computed(() => (action) => {
    const colorMap = {
      'Created Role': 'success',
      'Updated Role': 'info',
      'Deleted Role': 'error',
      'Created User': 'success',
      'Updated User': 'info',
      'Deleted User': 'error',
      'Created Permission': 'success',
      'Updated Permission': 'warning',
      'Deleted Permission': 'error',
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
      'Created Role': 'mdi-plus-circle',
      'Updated Role': 'mdi-pencil-circle',
      'Deleted Role': 'mdi-delete-circle',
      'Created User': 'mdi-account-plus',
      'Updated User': 'mdi-account-edit',
      'Deleted User': 'mdi-account-remove',
      'Created Permission': 'mdi-key-plus',
      'Updated Permission': 'mdi-key-change',
      'Deleted Permission': 'mdi-key-remove',
      'Role Created': 'mdi-plus-circle',
      'Role Updated': 'mdi-pencil-circle',
      'Role Deleted': 'mdi-delete-circle',
      'Permission Updated': 'mdi-key-change',
      'User Assigned': 'mdi-account-plus',
      'User Suspended': 'mdi-account-cancel'
    }
    return iconMap[action] || 'mdi-information'
  })

  const filteredAuditLogs = computed(() => {
    let filtered = [...auditLogs.value]

    if (filters.value.action) {
      filtered = filtered.filter(log => log.action === filters.value.action)
    }

    if (filters.value.startDate && filters.value.endDate) {
      const startDate = new Date(filters.value.startDate)
      const endDate = new Date(filters.value.endDate)
      filtered = filtered.filter(log => {
        const logDate = new Date(log.timestamp)
        return logDate >= startDate && logDate <= endDate
      })
    }

    return filtered.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp))
  })

  const uniqueActions = computed(() => {
    const actions = [...new Set(auditLogs.value.map(log => log.action))]
    return actions.sort()
  })

  // Actions
  const fetchAuditLogs = async (customFilters = {}) => {
    try {
      loading.value = true
      error.value = null

      const params = new URLSearchParams()
      
      // Apply filters
      const appliedFilters = { ...filters.value, ...customFilters }
      
      if (appliedFilters.action) {
        params.append('action', appliedFilters.action)
      }
      
      if (appliedFilters.startDate) {
        params.append('start_date', appliedFilters.startDate)
      }
      
      if (appliedFilters.endDate) {
        params.append('end_date', appliedFilters.endDate)
      }

      const queryString = params.toString()
      const url = `/api/v1/admin/audit-logs${queryString ? `?${queryString}` : ''}`
      
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          // Add authorization header if needed
          // 'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) {
        throw new Error(`Failed to fetch audit logs: ${response.statusText}`)
      }

      const data = await response.json()
      auditLogs.value = data

    } catch (err) {
      error.value = err.message
      console.error('Error fetching audit logs:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const setFilters = (newFilters) => {
    filters.value = { ...filters.value, ...newFilters }
  }

  const clearFilters = () => {
    filters.value = {
      action: null,
      startDate: null,
      endDate: null
    }
  }

  const refreshLogs = async () => {
    await fetchAuditLogs()
  }

  // Legacy method for backwards compatibility
  const logAction = async (action, description, details = {}) => {
    try {
      // This would typically trigger a backend action that gets logged automatically
      // For now, we'll just refresh the logs to get the latest data
      console.warn('logAction is deprecated. Backend actions should be logged automatically via Spatie Activity Log')
      await refreshLogs()
    } catch (err) {
      error.value = err.message
      throw err
    }
  }

  // Utility methods
  const getLogsByDateRange = (startDate, endDate) => {
    return auditLogs.value.filter(log => {
      const logDate = new Date(log.timestamp)
      return logDate >= new Date(startDate) && logDate <= new Date(endDate)
    })
  }

  const getLogsByAction = (action) => {
    return auditLogs.value.filter(log => log.action === action)
  }

  const getLogsByUser = (userName) => {
    return auditLogs.value.filter(log => log.user.name === userName)
  }

  return {
    // State
    auditLogs,
    loading,
    error,
    filters,
    
    // Getters
    recentChangesCount,
    getAuditColor,
    getAuditIcon,
    filteredAuditLogs,
    uniqueActions,
    
    // Actions
    fetchAuditLogs,
    setFilters,
    clearFilters,
    refreshLogs,
    logAction, // Deprecated but kept for backwards compatibility
    
    // Utility methods
    getLogsByDateRange,
    getLogsByAction,
    getLogsByUser
  }
})