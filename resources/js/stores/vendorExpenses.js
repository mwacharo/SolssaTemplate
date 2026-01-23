// stores/vendorExpenses.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { notify } from "@/utils/toast";


const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  }
})

export const useVendorExpensesStore = defineStore('vendorExpenses', () => {
  // State
  const expenses = ref([])
  const loading = ref(false)
  const error = ref(null)
  const pagination = ref({
    currentPage: 1,
    perPage: 10,
    total: 0,
    lastPage: 1
  })

  // Getters
  const getExpenseById = computed(() => {
    return (id) => expenses.value.find(expense => expense.id === id)
  })

  const getExpensesBySeller = computed(() => {
    return (sellerId) => expenses.value.filter(expense => expense.vendor_id === sellerId)
  })

  const getExpensesByStatus = computed(() => {
    return (status) => expenses.value.filter(expense => expense.status === status)
  })

  const getExpensesByType = computed(() => {
    return (type) => expenses.value.filter(expense => expense.expense_type === type)
  })

  const getExpensesByCountry = computed(() => {
    return (countryId) => expenses.value.filter(expense => expense.country_id === countryId)
  })

  const totalExpenses = computed(() => expenses.value.length)

  const totalAmount = computed(() => {
    return expenses.value.reduce((sum, expense) => {
      const amount = parseFloat(expense.amount) || 0
      return expense.expense_type === 'expense' ? sum - amount : sum + amount
    }, 0)
  })

  const expensesByStatus = computed(() => {
    return expenses.value.reduce((acc, expense) => {
      acc[expense.status] = (acc[expense.status] || 0) + 1
      return acc
    }, {})
  })


  const fetchExpenses = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/seller-expenses', { params })
      
      console.log('Raw API Response:', data)
      
      // Handle paginated response from Laravel
      if (data.success && data.expenses && data.expenses.data && Array.isArray(data.expenses.data)) {
        expenses.value = data.expenses.data
        
        // Update pagination from Laravel pagination structure
        pagination.value = {
          currentPage: data.expenses.current_page || 1,
          perPage: data.expenses.per_page || 20,
          total: data.expenses.total || data.expenses.data.length,
          lastPage: data.expenses.last_page || 1
        }
        
        console.log('Parsed expenses:', expenses.value)
        console.log('Pagination:', pagination.value)
      } else if (data.data && Array.isArray(data.data)) {
        expenses.value = data.data
        
        // Update pagination if provided
        if (data.meta) {
          pagination.value = {
            currentPage: data.meta.current_page,
            perPage: data.meta.per_page,
            total: data.meta.total,
            lastPage: data.meta.last_page
          }
        }
      } else {
        expenses.value = Array.isArray(data) ? data : []
        console.warn('Unexpected API response structure')
      }
      
      console.log('Final expenses count:', expenses.value.length)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error fetching expenses:', err)
      expenses.value = []
    } finally {
      loading.value = false
    }
  }

  const fetchExpenseById = async (id) => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get(`/seller-expenses/${id}`)
      return data.data || data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error fetching expense:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const createExpense = async (expenseData) => {
    loading.value = true
    error.value = null
    try {
      // Validate required fields
      if (!expenseData.description?.trim()) {
        throw new Error('Description is required')
      }
      if (!expenseData.amount || parseFloat(expenseData.amount) <= 0) {
        throw new Error('Valid amount is required')
      }
      if (!expenseData.vendor_id) {
        throw new Error('Seller is required')
      }
      // if (!expenseData.country_id) {
      //   throw new Error('Country is required')
      // }

      const dataToSend = {
        description: expenseData.description.trim(),
        amount: parseFloat(expenseData.amount),
        expense_type: expenseData.expense_type || 'expense',
        vendor_id: parseInt(expenseData.vendor_id),
        country_id: parseInt(expenseData.country_id),
        status: expenseData.status || 'not_applied',
        incurred_on: expenseData.incurred_on || null,

        // incurred_on: expenseData.incurred_on ? parseInt(expenseData.incurred_on) : null,
        source_country_id: expenseData.source_country_id ? parseInt(expenseData.source_country_id) : null,
        destination_country_id: expenseData.destination_country_id ? parseInt(expenseData.destination_country_id) : null,
        expense_type_id: expenseData.expense_type_id ? parseInt(expenseData.expense_type_id) : null
      }

      const { data } = await api.post('/seller-expenses', dataToSend)
      const newExpense = data.data || data
      
      // Add to local state
      expenses.value.unshift(newExpense)

      notify.success('Expense created successfully.')
      
      return newExpense
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error creating expense:', err)
      throw err
      notify.error(`Error creating expense: ${error.value}`)  
    } finally {
      loading.value = false
    }
  }

  const updateExpense = async (id, expenseData) => {
    loading.value = true
    error.value = null
    try {
      // Validate required fields
      if (!expenseData.description?.trim()) {
        throw new Error('Description is required')
      }
      if (!expenseData.amount || parseFloat(expenseData.amount) <= 0) {
        throw new Error('Valid amount is required')
      }
      if (!expenseData.vendor_id) {
        throw new Error('Seller is required')
      }
      if (!expenseData.country_id) {
        throw new Error('Country is required')
      }

      const dataToSend = {
        description: expenseData.description.trim(),
        amount: parseFloat(expenseData.amount),
        expense_type: expenseData.expense_type || 'expense',
        vendor_id: parseInt(expenseData.vendor_id),
        country_id: parseInt(expenseData.country_id),
        status: expenseData.status || 'not_applied',
        // incurred_on: expenseData.incurred_on || null,
          incurred_on: expenseData.incurred_on || null,
          expense_type_id: expenseData.expense_type_id,  // ⚠️ Make sure this is included!


        source_country_id: expenseData.source_country_id ? parseInt(expenseData.source_country_id) : null,
        destination_country_id: expenseData.destination_country_id ? parseInt(expenseData.destination_country_id) : null
      }

      const { data } = await api.put(`/seller-expenses/${id}`, dataToSend)
      const updatedExpense = data.data || data
      
      // Update local state
      const index = expenses.value.findIndex(expense => expense.id === id)
      if (index !== -1) {
        expenses.value[index] = updatedExpense
      }
      notify.success('Expense updated successfully.')
      
      return updatedExpense
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error updating expense:', err)
      throw err
      notify.error(`Error updating expense: ${error.value}`)
    } finally {
      loading.value = false
    }
  }

  const deleteExpense = async (id) => {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/seller-expenses/${id}`)
      
      // Remove from local state
      const index = expenses.value.findIndex(expense => expense.id === id)
      if (index !== -1) {
        expenses.value.splice(index, 1)
      }
      notify.success('Expense deleted successfully.')
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error deleting expense:', err)
      throw err
      notify.error(`Error deleting expense: ${error.value}`)
    } finally {
      loading.value = false
    }
  }

  const updateExpenseStatus = async (id, status) => {
    loading.value = true
    error.value = null
    try {
      const expense = getExpenseById.value(id)
      if (!expense) {
        throw new Error('Expense not found')
      }

      const { data } = await api.patch(`/seller-expenses/${id}/status`, { status })
      const updatedExpense = data.data || data
      
      // Update local state
      const index = expenses.value.findIndex(exp => exp.id === id)
      if (index !== -1) {
        expenses.value[index] = updatedExpense
      }
      
      return updatedExpense
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error updating expense status:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const bulkUpdateStatus = async (ids, status) => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.post('/seller-expenses/bulk-status', { ids, status })
      
      // Update local state for all affected expenses
      ids.forEach(id => {
        const index = expenses.value.findIndex(exp => exp.id === id)
        if (index !== -1) {
          expenses.value[index].status = status
          expenses.value[index].updated_at = new Date().toISOString()
        }
      })
      
      return data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error bulk updating expense status:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchExpensesByDateRange = async (startDate, endDate) => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/seller-expenses/date-range', {
        params: { start_date: startDate, end_date: endDate }
      })
      return data.data || data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error fetching expenses by date range:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const getExpenseStatistics = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/seller-expenses/statistics', { params })
      return data.data || data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error fetching expense statistics:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const exportExpenses = async (format = 'csv', filters = {}) => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/seller-expenses/export', {
        params: { format, ...filters },
        responseType: 'blob'
      })
      
      // Create download link
      const url = window.URL.createObjectURL(new Blob([data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `expenses_${Date.now()}.${format}`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error exporting expenses:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const searchExpenses = async (query, filters = {}) => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/seller-expenses/search', {
        params: { q: query, ...filters }
      })
      
      expenses.value = data.data || data
      
      return data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error searching expenses:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const resetStore = () => {
    expenses.value = []
    loading.value = false
    error.value = null
    pagination.value = {
      currentPage: 1,
      perPage: 10,
      total: 0,
      lastPage: 1
    }
  }

  return {
    // State
    expenses,
    loading,
    error,
    pagination,
    
    // Getters
    getExpenseById,
    getExpensesBySeller,
    getExpensesByStatus,
    getExpensesByType,
    getExpensesByCountry,
    totalExpenses,
    totalAmount,
    expensesByStatus,
    
    // Actions
    fetchExpenses,
    fetchExpenseById,
    createExpense,
    updateExpense,
    deleteExpense,
    updateExpenseStatus,
    bulkUpdateStatus,
    fetchExpensesByDateRange,
    getExpenseStatistics,
    exportExpenses,
    searchExpenses,
    
    // Utility
    resetStore
  }
})