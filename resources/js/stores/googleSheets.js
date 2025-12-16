// stores/googleSheets.js
import { defineStore } from 'pinia'
import axios from 'axios'
import { notify } from '@/utils/toast'

export const useGoogleSheetsStore = defineStore('googleSheets', {
  state: () => ({
    sheets: {
      data: []
    },
    loading: false,
    error: null
  }),

  getters: {
    activeSheets: (state) => state.sheets.data?.filter(sheet => sheet.active === 1) || [],
    inactiveSheets: (state) => state.sheets.data?.filter(sheet => sheet.active === 0) || [],
    currentSheets: (state) => state.sheets.data?.filter(sheet => sheet.is_current === 1) || [],
    getSheetById: (state) => (id) => state.sheets.data?.find(sheet => sheet.id === id),
    getSheetsByMerchant: (state) => (merchant) =>
      state.sheets.data?.filter(sheet => 
        sheet.vendor?.name?.toLowerCase().includes(merchant.toLowerCase())
      ) || []
  },

  actions: {
    async fetchSheets() {
      try {
        this.loading = true
        const response = await axios.get('/api/v1/google-sheets')
        this.sheets = response.data
        this.error = null
      } catch (error) {
        console.error('Failed to fetch sheets:', error)
        this.error = error.response?.data?.message || error.message
        notify('Failed to fetch sheets', 'error')
        throw error
      } finally {
        this.loading = false
      }
    },

    async addSheet(sheetData) {
      try {
        this.loading = true
        
        const payload = {
          vendor_id: sheetData.vendor,
          sheet_name: sheetData.sheetName || 'Sheet1',
          post_spreadsheet_id: sheetData.spreadsheetId,
          active: 1,
          auto_sync: 1,
          sync_all: 0,
          sync_interval: 15
        }

        const response = await axios.post('/api/v1/google-sheets', payload)
        
        // Add the new sheet to local state
        if (!this.sheets.data) {
          this.sheets.data = []
        }
        this.sheets.data.push(response.data.data || response.data)
        
        notify.success('Sheet added successfully')
        this.error = null
      } catch (error) {
        console.error('Failed to add sheet:', error)
        this.error = error.response?.data?.message || error.message
        notify.error('Failed to add sheet')
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteSheet(id) {
      try {
        this.loading = true
        await axios.delete(`/api/v1/google-sheets/${id}`)
        
        // Remove from local state
        if (this.sheets.data) {
          const index = this.sheets.data.findIndex(sheet => sheet.id === id)
          if (index > -1) {
            this.sheets.data.splice(index, 1)
          }
        }
        
        notify('Sheet deleted successfully', 'success')
        this.error = null
      } catch (error) {
        console.error('Failed to delete sheet:', error)
        this.error = error.response?.data?.message || error.message
        notify('Failed to delete sheet', 'error')
        throw error
      } finally {
        this.loading = false
      }
    },

    async toggleStatus(id) {
      try {
        this.loading = true
        const sheet = this.sheets.data?.find(sheet => sheet.id === id)
        if (!sheet) return
        
        const newStatus = sheet.active === 1 ? 0 : 1
        const response = await axios.put(`/api/v1/google-sheets/${id}`, {
          active: newStatus
        })
        
        // Update local state
        sheet.active = newStatus
        sheet.updated_at = new Date().toISOString()
        
        notify(`Sheet ${newStatus === 1 ? 'activated' : 'deactivated'} successfully`, 'success')
        this.error = null
      } catch (error) {
        console.error('Failed to toggle status:', error)
        this.error = error.response?.data?.message || error.message
        notify('Failed to update sheet status', 'error')
        throw error
      } finally {
        this.loading = false
      }
    },

    async toggleCurrent(id) {
      try {
        this.loading = true
        const sheet = this.sheets.data?.find(sheet => sheet.id === id)
        if (!sheet) return
        
        const newCurrent = sheet.is_current === 1 ? 0 : 1
        await axios.put(`/api/v1/google-sheets/${id}`, {
          is_current: newCurrent
        })
        
        // If setting as current, unset all other current sheets
        if (newCurrent === 1 && this.sheets.data) {
          this.sheets.data.forEach(s => {
            if (s.id !== id) {
              s.is_current = 0
            }
          })
        }
        
        // Update local state
        sheet.is_current = newCurrent
        sheet.updated_at = new Date().toISOString()
        
        notify.success(`Sheet ${newCurrent === 1 ? 'set as current' : 'removed from current'}`)
        this.error = null
      } catch (error) {
        console.error('Failed to toggle current:', error)
        this.error = error.response?.data?.message || error.message
        notify.error('Failed to update current setting')
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateSheet(id, updates) {
      try {
        this.loading = true
        await axios.put(`/api/v1/google-sheets/${id}`, updates)
        
        // Update local state
        const sheet = this.sheets.data?.find(sheet => sheet.id === id)
        if (sheet) {
          Object.assign(sheet, updates)
          sheet.updated_at = new Date().toISOString()
        }
        
        notify.success('Sheet updated successfully')
        this.error = null
      } catch (error) {
        console.error('Failed to update sheet:', error)
        this.error = error.response?.data?.message || error.message
        notify.error('Failed to update sheet')
        throw error
      } finally {
        this.loading = false
      }
    },

    async readSheet(id) {
      try {
        this.loading = true
        console.log('Reading sheet:', id)
        
        const response = await axios.post(`/api/v1/google-sheets/${id}/read-sheet`)
        
        notify.success('Sheet read successfully')
        this.error = null
        return response.data
      } catch (error) {
        console.error('Failed to read sheet:', error)
        // this.error = error.response?.data?.message || error.message
        notify.error('Failed to read sheet')
        throw error
      } finally {
        this.loading = false
      }
    },

    async writeSheet(id, data = {}) {
      try {
        this.loading = true
        console.log('Writing to sheet:', id)
        
        // Using the sync-products endpoint as it's available in your routes
        // const response = await axios.post(`/api/v1/google-sheets/${id}/sync-products`, data)

        const response = await axios.post(`/api/v1/google-sheets/${id}/update-sheet`, data)

        
        notify.success('Data written to sheet successfully')
        this.error = null
        return response.data
      } catch (error) {
        console.error('Failed to write to sheet:', error)
        this.error = error.response?.data?.message || error.message
        notify.error('Failed to write to sheet')
        throw error
      } finally {
        this.loading = false
      }
    },

    clearError() {
      this.error = null
    }
  }
})