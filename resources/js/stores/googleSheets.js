// stores/googleSheets.js
import { defineStore } from 'pinia'

import { isAxiosError } from 'axios'

export const useGoogleSheetsStore = defineStore('googleSheets', {
  state: () => ({
    sheets: [

    ],
    loading: false,
    error: null
  }),

  getters: {
    activeSheets: (state) => state.sheets.filter(sheet => sheet.status === 'active'),
    inactiveSheets: (state) => state.sheets.filter(sheet => sheet.status === 'inactive'),
    defaultSheets: (state) => state.sheets.filter(sheet => sheet.isDefault),
    getSheetById: (state) => (id) => state.sheets.find(sheet => sheet.id === id),
    getSheetsByMerchant: (state) => (merchant) =>
      state.sheets.filter(sheet => sheet.merchant.toLowerCase().includes(merchant.toLowerCase()))
  },

  actions: {
    async addSheet(sheetData) {
      try {
        this.loading = true
        const newSheet = {
          id: Date.now(),
          merchant: sheetData.vendor,
          sheetName: sheetData.sheetName || 'Sheet1',
          status: 'active',
          isDefault: false,
          lastUpdate: new Date().toISOString(),
          created: new Date().toISOString(),
          spreadsheetId: sheetData.spreadsheetId,
          lastUpdateOrderNo: '',
          operatingUnit: '',
          autoSync: true,
          timeInterval: 30,
          orderNumberPrefix: '',
          syncMode: 'all'
        }

        this.sheets.push(newSheet)

        // Here you would typically make an API call
        await axios.post('/api/v1/google-sheets', newSheet)

        this.error = null
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteSheet(id) {
      try {
        this.loading = true
        const index = this.sheets.findIndex(sheet => sheet.id === id)
        if (index > -1) {
          this.sheets.splice(index, 1)

          // Here you would typically make an API call
          await isAxiosError.delete('/api/v1/google-sheets/' + id)
        }
        this.error = null
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async toggleStatus(id) {
      try {
        this.loading = true
        const sheet = this.sheets.find(sheet => sheet.id === id)
        if (sheet) {
          sheet.status = sheet.status === 'active' ? 'inactive' : 'active'
          sheet.lastUpdate = new Date().toISOString()

          // Here you would typically make an API call
          // await api.updateSheet(id, { status: sheet.status })
        }
        this.error = null
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async toggleDefault(id) {
      try {
        this.loading = true
        const sheet = this.sheets.find(sheet => sheet.id === id)
        if (sheet) {
          sheet.isDefault = !sheet.isDefault
          sheet.lastUpdate = new Date().toISOString()

          // Here you would typically make an API call
          // await api.updateSheet(id, { isDefault: sheet.isDefault })
        }
        this.error = null
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateSheet(id, updates) {
      try {
        this.loading = true
      
            await axios.put(`/api/v1/google-sheets/${id}`, updates)
        // }
        this.error = null
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async readSheet(id) {
      try {
        this.loading = true
        console.log('Reading sheet:', id)

        // Here you would implement the actual read logic
        const response = await axios.post(`/api/v1/google-sheets/${id}/read-sheet`)
        return response.data

        this.error = null
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async writeSheet(id) {
      try {
        this.loading = true
        console.log('Writing to sheet:', id)

        // Here you would implement the actual write logic
        // Example: send data to backend to write to Google Sheet
        await api.post(`/api/v1/google-sheets/${id}/write-sheet`, data)

        this.error = null
      } catch (error) {
        this.error = error.message
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchSheets() {
      try {
        this.loading = true

        // Here you would fetch sheets from API
        const sheets = await axios.get('/api/v1/google-sheets')
        this.sheets = sheets.data

        this.error = null
      } catch (error) {
        this.error = error.message
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
