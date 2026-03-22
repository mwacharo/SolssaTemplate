// stores/vendorExpenses.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { notify } from '@/utils/toast'

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

export const useVendorExpensesStore = defineStore('vendorExpenses', () => {
  // ── State ─────────────────────────────────────────────────────────────────

  const exporting = ref(false)

  const expenses  = ref([])
  const loading   = ref(false)
  const error     = ref(null)
  const pagination = ref({
    currentPage: 1,
    perPage: 20,
    total: 0,
    lastPage: 1,
  })

  /**
   * Active search/filter params — single source of truth.
   * Mirrors the backend FILTERABLE whitelist exactly.
   */
  const filters = ref({
    search: '',          // LIKE on description
    vendor_id: '',
    expense_type: '',
    expense_type_id: '',
    remittance_id: '',
    status: '',
    incurred_on: '',
    incurred_on_from: '',
    incurred_on_to: '',
    created_at: '',
    created_at_from: '',
    created_at_to: '',
    sort_by: 'created_at',
    sort_dir: 'desc',
    per_page: 20,
  })

  // ── Getters ───────────────────────────────────────────────────────────────
  const getExpenseById = computed(() => (id) =>
    expenses.value.find((e) => e.id === id),
  )

  const totalExpenses = computed(() => pagination.value.total)

  const totalAmount = computed(() =>
    expenses.value.reduce((sum, e) => {
      const amt = parseFloat(e.amount) || 0
      return e.expense_type === 'expense' ? sum - amt : sum + amt
    }, 0),
  )

  const expensesByStatus = computed(() =>
    expenses.value.reduce((acc, e) => {
      acc[e.status] = (acc[e.status] || 0) + 1
      return acc
    }, {}),
  )

  // ── Helpers ───────────────────────────────────────────────────────────────
  /** Strip empty strings so we don't send noisy query params. O(k) where k = filter count */
  const buildParams = (overrides = {}) => {
    const merged = { ...filters.value, ...overrides }
    return Object.fromEntries(
      Object.entries(merged).filter(([, v]) => v !== '' && v !== null && v !== undefined),
    )
  }

  const parsePagination = (meta) => {
    pagination.value = {
      currentPage: meta.current_page ?? 1,
      perPage:     meta.per_page     ?? 20,
      total:       meta.total        ?? 0,
      lastPage:    meta.last_page    ?? 1,
    }
  }

  // ── Actions ───────────────────────────────────────────────────────────────

  /**
   * Fetch expenses from the server.
   * All filtering happens at DB level — O(log n) per indexed column.
   *
   * @param {object} params  Optional one-off param overrides (e.g. page number).
   */
  const fetchExpenses = async (params = {}) => {
    loading.value = true
    error.value   = null

    try {
      const { data } = await api.get('/seller-expenses', { params: buildParams(params) })

      // Handle Laravel paginated response
      if (data.success && data.expenses?.data) {
        expenses.value = data.expenses.data
        parsePagination(data.expenses)
      } else if (Array.isArray(data.data)) {
        expenses.value = data.data
        if (data.meta) parsePagination(data.meta)
      } else {
        expenses.value = Array.isArray(data) ? data : []
      }
    } catch (err) {
      error.value    = err.response?.data?.message ?? err.message
      expenses.value = []
      console.error('fetchExpenses:', err)
    } finally {
      loading.value = false
    }
  }

  /**
   * Update one or more filters and immediately re-fetch from page 1.
   * O(k) merge + O(log n) DB query.
   *
   * @param {object} newFilters  Partial filter object, e.g. { status: 'approved' }
   */
  const applyFilters = async (newFilters = {}) => {
    filters.value = { ...filters.value, ...newFilters }
    await fetchExpenses({ page: 1 })
  }

  /** Reset all filters and reload. */
  const clearFilters = async () => {
    filters.value = {
      search: '',
      vendor_id: '',
      expense_type: '',
      expense_type_id: '',
      remittance_id: '',
      status: '',
      incurred_on: '',
      incurred_on_from: '',
      incurred_on_to: '',
      created_at: '',
      created_at_from: '',
      created_at_to: '',
      sort_by: 'created_at',
      sort_dir: 'desc',
      per_page: 20,
    }
    await fetchExpenses({ page: 1 })
  }

  /** Change page without touching filters. */
  const goToPage = async (page) => {
    await fetchExpenses({ page })
  }

  const fetchExpenseById = async (id) => {
    loading.value = true
    error.value   = null
    try {
      const { data } = await api.get(`/seller-expenses/${id}`)
      return data.data ?? data
    } catch (err) {
      error.value = err.response?.data?.message ?? err.message
      throw err
    } finally {
      loading.value = false
    }
  }

  const createExpense = async (expenseData) => {
    loading.value = true
    error.value   = null
    try {
      const dataToSend = {
        description:            expenseData.description.trim(),
        amount:                 parseFloat(expenseData.amount),
        expense_type:           expenseData.expense_type   || 'expense',
        vendor_id:              parseInt(expenseData.vendor_id),
        country_id:             expenseData.country_id ? parseInt(expenseData.country_id) : null,
        status:                 expenseData.status         || 'not_applied',
        incurred_on:            expenseData.incurred_on    || null,
        expense_type_id:        expenseData.expense_type_id ? parseInt(expenseData.expense_type_id) : null,
        source_country_id:      expenseData.source_country_id      ? parseInt(expenseData.source_country_id)      : null,
        destination_country_id: expenseData.destination_country_id ? parseInt(expenseData.destination_country_id) : null,
      }

      const { data } = await api.post('/seller-expenses', dataToSend)
      const newExpense = data.data ?? data

      // Prepend so the user sees their new record immediately (O(1) unshift)
      expenses.value.unshift(newExpense)
      pagination.value.total += 1

      notify.success('Expense created successfully.')
      return newExpense
    } catch (err) {
      error.value = err.response?.data?.message ?? err.message
      notify.error(`Error creating expense: ${error.value}`)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateExpense = async (id, expenseData) => {
    loading.value = true
    error.value   = null
    try {
      const dataToSend = {
        description:            expenseData.description.trim(),
        amount:                 parseFloat(expenseData.amount),
        expense_type:           expenseData.expense_type   || 'expense',
        vendor_id:              parseInt(expenseData.vendor_id),
        country_id:             expenseData.country_id ? parseInt(expenseData.country_id) : null,
        status:                 expenseData.status         || 'not_applied',
        incurred_on:            expenseData.incurred_on    || null,
        expense_type_id:        expenseData.expense_type_id ?? null,
        source_country_id:      expenseData.source_country_id      ? parseInt(expenseData.source_country_id)      : null,
        destination_country_id: expenseData.destination_country_id ? parseInt(expenseData.destination_country_id) : null,
      }

      const { data } = await api.put(`/seller-expenses/${id}`, dataToSend)
      const updated  = data.data ?? data

      // O(n) scan — acceptable; list is paginated (≤200 rows)
      const idx = expenses.value.findIndex((e) => e.id === id)
      if (idx !== -1) expenses.value[idx] = updated

      notify.success('Expense updated successfully.')
      return updated
    } catch (err) {
      error.value = err.response?.data?.message ?? err.message
      notify.error(`Error updating expense: ${error.value}`)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteExpense = async (id) => {
    loading.value = true
    error.value   = null
    try {
      await api.delete(`/seller-expenses/${id}`)

      const idx = expenses.value.findIndex((e) => e.id === id)
      if (idx !== -1) {
        expenses.value.splice(idx, 1) // O(n)
        pagination.value.total -= 1
      }

      notify.success('Expense deleted successfully.')
    } catch (err) {
      error.value = err.response?.data?.message ?? err.message
      notify.error(`Error deleting expense: ${error.value}`)
      throw err
    } finally {
      loading.value = false
    }
  }

  const resetStore = () => {
    expenses.value   = []
    loading.value    = false
    error.value      = null
    pagination.value = { currentPage: 1, perPage: 20, total: 0, lastPage: 1 }
  }



  const exportExpenses = async () => {
  exporting.value = true
  error.value     = null
 
  try {
    const response = await api.get('/seller-expenses/export', {
      params:       buildParams(),   // same active filters as the table
      responseType: 'blob',          // receive raw bytes
    })
 
    // Derive filename from Content-Disposition if server sends it,
    // otherwise fall back to a timestamped default.
    const disposition = response.headers['content-disposition'] ?? ''
    const match       = disposition.match(/filename[^;=\n]*=["']?([^"'\n;]+)/)
    const filename    = match?.[1] ?? `expenses_${Date.now()}.csv`
 
    // Create a temporary object URL and click it — works in all modern browsers
    const url  = URL.createObjectURL(new Blob([response.data], { type: 'text/csv;charset=utf-8;' }))
    const link = document.createElement('a')
    link.href  = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)   // free memory immediately
 
    notify.success('Export downloaded successfully.')
  } catch (err) {
    error.value = err.response?.data?.message ?? err.message
    notify.error(`Export failed: ${error.value}`)
    throw err
  } finally {
    exporting.value = false
  }
}

  return {
    // State
    expenses,
    loading,
    error,
    filters,
    pagination,

    // Getters
    getExpenseById,
    totalExpenses,
    totalAmount,
    expensesByStatus,

    // Actions
    fetchExpenses,
    fetchExpenseById,
    applyFilters,
    clearFilters,
    goToPage,
    createExpense,
    updateExpense,
    deleteExpense,
    resetStore,
  }
})