// stores/contacts.js
import { defineStore } from 'pinia'
import axios from 'axios'

export const useContactsStore = defineStore('contacts', {
  state: () => ({
    contacts: [],
    loading: false,
    saving: false,
    deleting: false,
    searchQuery: '',
    filterType: null,
    filterStatus: null,
    totalContacts: 0,
    currentPage: 1,
    lastPage: 1,
    perPage: 10
  }),

  getters: {
    filteredContacts: (state) => {
      let contactsArray = Array.isArray(state.contacts) ? state.contacts : []
      let result = [...contactsArray]
      
      if (state.filterType && state.filterType !== 'All') {
        result = result.filter(contact => contact.type === state.filterType)
      }
      
      if (state.filterStatus !== null && state.filterStatus !== 'All') {
        result = result.filter(contact => contact.status === Number(state.filterStatus))
      }
      
      return result
    },

    contactTypes: (state) => {
      const contactsArray = Array.isArray(state.contacts) ? state.contacts : []
      const types = [...new Set(contactsArray.map(contact => contact.type).filter(Boolean))]
      return ['All', ...types]
    }
  },

  actions: {
    async fetchContacts(params = {}) {
      this.loading = true
      try {
      const response = await axios.get('/api/v1/contacts', { params })
      const data = response.data.data || {}
      this.contacts = Array.isArray(data.data) ? data.data : []
      this.totalContacts = data.total || this.contacts.length
      this.currentPage = data.current_page || 1
      this.lastPage = data.last_page || 1
      this.perPage = data.per_page || 10
      } catch (error) {
      console.error('Failed to fetch contacts:', error)
      throw error
      } finally {
      this.loading = false
      }
    },

    async createContact(contactData) {
      this.saving = true
      try {
        const response = await axios.post('/api/v1/contacts', contactData)
        this.contacts.unshift(response.data.data || response.data)
        return response.data
      } catch (error) {
        console.error('Failed to create contact:', error)
        throw error
      } finally {
        this.saving = false
      }
    },

    async updateContact(id, contactData) {
      this.saving = true
      try {
        const response = await axios.put(`/api/v1/contacts/${id}`, contactData)
        const index = this.contacts.findIndex(contact => contact.id === id)
        if (index !== -1) {
          this.contacts[index] = response.data.data || response.data
        }
        return response.data
      } catch (error) {
        console.error('Failed to update contact:', error)
        throw error
      } finally {
        this.saving = false
      }
    },

    async deleteContact(id) {
      this.deleting = true
      try {
        await axios.delete(`/api/v1/contacts/${id}`)
        this.contacts = this.contacts.filter(contact => contact.id !== id)
      } catch (error) {
        console.error('Failed to delete contact:', error)
        throw error
      } finally {
        this.deleting = false
      }
    },

    async searchContacts(query) {
      this.searchQuery = query
      await this.fetchContacts({ search: query })
    },

    setFilter(type, value) {
      if (type === 'type') {
        this.filterType = value
      } else if (type === 'status') {
        this.filterStatus = value
      }
    },

    resetFilters() {
      this.searchQuery = ''
      this.filterType = null
      this.filterStatus = null
      this.fetchContacts()
    }
  }
})