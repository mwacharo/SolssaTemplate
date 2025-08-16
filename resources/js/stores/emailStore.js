// stores/emailStore.js
import { defineStore } from 'pinia'
import axios from 'axios'

// Configure axios base URL
const API_BASE_URL = '/api/v1'

// Create axios instance with base configuration
const apiClient = axios.create({
  baseURL: API_BASE_URL
})

// Add request interceptor for authentication if needed
// apiClient.interceptors.request.use((config) => {
//   // Add auth token if available
//   const token = localStorage.getItem('auth_token')
//   if (token) {
//     config.headers.Authorization = `Bearer ${token}`
//   }
//   return config
// })

// Add response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error('API Error:', error.response?.data || error.message)
    return Promise.reject(error)
  }
)

export const useEmailStore = defineStore('email', {
  state: () => ({
    // Email data
    sentEmails: [],
    drafts: [],
    templates: [],
    
    // Pagination data for templates
    templatesPagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0
    },
    
    // Clients data (if you have clients endpoint)
    clients: [],
    
    // Loading states
    isLoading: false,
    isSending: false,
    isLoadingTemplates: false,
    isLoadingDrafts: false,
    isLoadingSentEmails: false,
    
    // Error states
    error: null,
    templateError: null,
    draftError: null,
    sentEmailError: null
  }),

  getters: {
    // Get drafts count
    draftsCount: (state) => state.drafts.length,
    
    // Get sent emails count
    sentEmailsCount: (state) => state.sentEmails.length,
    
    // Get templates count
    templatesCount: (state) => state.templates.length,
    
    // Get emails by client (if clientId is available)
    getEmailsByClient: (state) => (clientId) => {
      return state.sentEmails.filter(email => email.client_id === clientId)
    },
    
    // Get emails by date range
    getEmailsByDateRange: (state) => (startDate, endDate) => {
      return state.sentEmails.filter(email => {
        const emailDate = new Date(email.sent_at || email.created_at)
        const start = new Date(startDate)
        const end = new Date(endDate)
        return emailDate >= start && emailDate <= end
      })
    },
    
    // Get recent emails (last 7 days)
    recentEmails: (state) => {
      const sevenDaysAgo = new Date()
      sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7)
      
      return state.sentEmails.filter(email => 
        new Date(email.sent_at || email.created_at) >= sevenDaysAgo
      ).sort((a, b) => new Date(b.sent_at || b.created_at) - new Date(a.sent_at || a.created_at))
    },
    
    // Get template by ID
    getTemplateById: (state) => (id) => {
      return state.templates.find(template => template.id === parseInt(id))
    },
    
    // Get draft by ID
    getDraftById: (state) => (id) => {
      return state.drafts.find(draft => draft.id === parseInt(id))
    },
    
    // Get sent email by ID
    getSentEmailById: (state) => (id) => {
      return state.sentEmails.find(email => email.id === parseInt(id))
    },

    // Check if there are more templates to load
    hasMoreTemplates: (state) => {
      return state.templatesPagination.current_page < state.templatesPagination.last_page
    }
  },

  actions: {
    // Error handling helper
    handleError(error, errorStateKey) {
      const errorMessage = error.response?.data?.message || error.message || 'An error occurred'
      if (errorStateKey) {
        this[errorStateKey] = errorMessage
      }
      this.error = errorMessage
      console.error('Store Error:', errorMessage)
      return errorMessage
    },

    // Clear errors
    clearErrors() {
      this.error = null
      this.templateError = null
      this.draftError = null
      this.sentEmailError = null
    },

    // TEMPLATE ACTIONS
    async fetchTemplates(page = 1) {
      this.isLoadingTemplates = true
      this.templateError = null
      
      try {
        const response = await apiClient.get(`/email-templates?page=${page}`)
        
        if (page === 1) {
          this.templates = response.data.data
        } else {
          this.templates.push(...response.data.data)
        }
        
        // Update pagination info
        this.templatesPagination = {
          current_page: response.data.meta.current_page,
          last_page: response.data.meta.last_page,
          per_page: response.data.meta.per_page,
          total: response.data.meta.total
        }
        
        return response.data
      } catch (error) {
        this.handleError(error, 'templateError')
        throw error
      } finally {
        this.isLoadingTemplates = false
      }
    },

    async saveTemplate(templateData) {
      this.isLoading = true
      this.templateError = null
      
      try {
        const response = await apiClient.post('/email-templates', templateData)
        this.templates.unshift(response.data)
        return response.data
      } catch (error) {
        this.handleError(error, 'templateError')
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async updateTemplate(templateId, templateData) {
      this.isLoading = true
      this.templateError = null
      
      try {
        const response = await apiClient.put(`/email-templates/${templateId}`, templateData)
        
        // Update template in local state
        const index = this.templates.findIndex(t => t.id === parseInt(templateId))
        if (index !== -1) {
          this.templates[index] = response.data
        }
        
        return response.data
      } catch (error) {
        this.handleError(error, 'templateError')
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async deleteTemplate(templateId) {
      this.isLoading = true
      this.templateError = null
      
      try {
        await apiClient.delete(`/email-templates/${templateId}`)
        
        // Remove template from local state
        const index = this.templates.findIndex(t => t.id === parseInt(templateId))
        if (index !== -1) {
          this.templates.splice(index, 1)
          this.templatesPagination.total--
        }
        
        return true
      } catch (error) {
        this.handleError(error, 'templateError')
        throw error
      } finally {
        this.isLoading = false
      }
    },

    // DRAFT ACTIONS
    async fetchDrafts() {
      this.isLoadingDrafts = true
      this.draftError = null
      
      try {
        const response = await apiClient.get('/drafts')
        this.drafts = response.data.data || response.data
        return response.data
      } catch (error) {
        this.handleError(error, 'draftError')
        throw error
      } finally {
        this.isLoadingDrafts = false
      }
    },

    async saveDraft(draftData) {
      this.isLoading = true
      this.draftError = null
      
      try {
        const response = await apiClient.post('/drafts', draftData)
        
        // Add or update draft in local state
        const existingIndex = this.drafts.findIndex(d => d.id === response.data.id)
        if (existingIndex !== -1) {
          this.drafts[existingIndex] = response.data
        } else {
          this.drafts.unshift(response.data)
        }
        
        return response.data
      } catch (error) {
        this.handleError(error, 'draftError')
        throw error
      } finally {
        this.isLoading = false
      }
    },

    async deleteDraft(draftId) {
      this.isLoading = true
      this.draftError = null
      
      try {
        await apiClient.delete(`/drafts/${draftId}`)
        
        // Remove draft from local state
        const index = this.drafts.findIndex(d => d.id === parseInt(draftId))
        if (index !== -1) {
          this.drafts.splice(index, 1)
        }
        
        return true
      } catch (error) {
        this.handleError(error, 'draftError')
        throw error
      } finally {
        this.isLoading = false
      }
    },

    // SENT EMAIL ACTIONS
    async fetchSentEmails() {
      this.isLoadingSentEmails = true
      this.sentEmailError = null
      
      try {
        const response = await apiClient.get('/sent')
        this.sentEmails = response.data.data || response.data
        return response.data
      } catch (error) {
        this.handleError(error, 'sentEmailError')
        throw error
      } finally {
        this.isLoadingSentEmails = false
      }
    },

    async sendEmail(emailData) {
      this.isSending = true
      this.error = null
      
      try {
        const response = await apiClient.post('/send', emailData)
        
        // Add sent email to local state
        this.sentEmails.unshift(response.data)
        
        return response.data
      } catch (error) {
        this.handleError(error)
        throw error
      } finally {
        this.isSending = false
      }
    },

    async sendBulkEmails(emailData) {
      this.isSending = true
      this.error = null
      
      try {
        const response = await apiClient.post('/bulk-send', emailData)
        
        // Add sent emails to local state if they're returned
        if (response.data.sent_emails && Array.isArray(response.data.sent_emails)) {
          this.sentEmails.unshift(...response.data.sent_emails)
        }
        
        return response.data
      } catch (error) {
        this.handleError(error)
        throw error
      } finally {
        this.isSending = false
      }
    },

    // INITIALIZATION
    async initializeData() {
      this.isLoading = true
      
      try {
        // Load all data concurrently
        await Promise.allSettled([
          this.fetchTemplates(),
          this.fetchDrafts(),
          this.fetchSentEmails()
        ])
      } catch (error) {
        this.handleError(error)
      } finally {
        this.isLoading = false
      }
    },

    // UTILITY FUNCTIONS
    personalizeMail(content, replacements = {}) {
      let personalizedContent = content
      
      Object.keys(replacements).forEach(key => {
        const regex = new RegExp(`\\{\\{${key}\\}\\}`, 'g')
        personalizedContent = personalizedContent.replace(regex, replacements[key] || '')
      })
      
      // Default replacements
      personalizedContent = personalizedContent
        .replace(/\{\{date\}\}/g, new Date().toLocaleDateString())
        .replace(/\{\{time\}\}/g, new Date().toLocaleTimeString())
      
      return personalizedContent
    },

    // SEARCH AND FILTER
    searchEmails(query, type = 'sent') {
      const emails = type === 'sent' ? this.sentEmails : this.drafts
      if (!query || query.trim() === '') return emails
      
      const lowerQuery = query.toLowerCase()
      
      return emails.filter(email => 
        (email.subject && email.subject.toLowerCase().includes(lowerQuery)) ||
        (email.body && email.body.toLowerCase().includes(lowerQuery)) ||
        (email.to && email.to.toLowerCase().includes(lowerQuery)) ||
        (email.from && email.from.toLowerCase().includes(lowerQuery))
      )
    },

    searchTemplates(query) {
      if (!query || query.trim() === '') return this.templates
      
      const lowerQuery = query.toLowerCase()
      
      return this.templates.filter(template =>
        (template.name && template.name.toLowerCase().includes(lowerQuery)) ||
        (template.subject && template.subject.toLowerCase().includes(lowerQuery)) ||
        (template.body && template.body.toLowerCase().includes(lowerQuery))
      )
    },

    // STATISTICS
    getEmailStats() {
      const today = new Date()
      const thisWeek = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000)
      const thisMonth = new Date(today.getFullYear(), today.getMonth(), 1)

      return {
        total: this.sentEmails.length,
        thisWeek: this.sentEmails.filter(e => 
          new Date(e.sent_at || e.created_at) >= thisWeek
        ).length,
        thisMonth: this.sentEmails.filter(e => 
          new Date(e.sent_at || e.created_at) >= thisMonth
        ).length,
        delivered: this.sentEmails.filter(e => e.status === 'sent').length,
        failed: this.sentEmails.filter(e => e.status === 'failed').length,
        scheduled: this.sentEmails.filter(e => e.status === 'scheduled').length,
        drafts: this.drafts.length,
        templates: this.templates.length
      }
    },

    // EXPORT DATA
    exportEmails(type = 'sent', format = 'json') {
      const data = type === 'sent' ? this.sentEmails : this.drafts
      
      if (format === 'csv') {
        // Convert to CSV format
        const headers = ['Date', 'From', 'To', 'Subject', 'Status']
        const rows = data.map(email => [
          new Date(email.sent_at || email.created_at).toLocaleDateString(),
          email.from || '',
          email.to || '',
          email.subject || '',
          email.status || 'draft'
        ])
        
        return [headers, ...rows]
      }
      
      return data
    },

    exportTemplates(format = 'json') {
      if (format === 'csv') {
        const headers = ['Name', 'Subject', 'Placeholders', 'Created Date']
        const rows = this.templates.map(template => [
          template.name,
          template.subject,
          template.placeholders ? template.placeholders.join(', ') : '',
          new Date(template.created_at).toLocaleDateString()
        ])
        
        return [headers, ...rows]
      }
      
      return this.templates
    },

    // BULK OPERATIONS
    async deleteBulkDrafts(draftIds) {
      this.isLoading = true
      const results = []
      
      try {
        for (const draftId of draftIds) {
          try {
            await this.deleteDraft(draftId)
            results.push({ id: draftId, success: true })
          } catch (error) {
            results.push({ id: draftId, success: false, error: error.message })
          }
        }
        
        return results
      } finally {
        this.isLoading = false
      }
    },

    async deleteBulkTemplates(templateIds) {
      this.isLoading = true
      const results = []
      
      try {
        for (const templateId of templateIds) {
          try {
            await this.deleteTemplate(templateId)
            results.push({ id: templateId, success: true })
          } catch (error) {
            results.push({ id: templateId, success: false, error: error.message })
          }
        }
        
        return results
      } finally {
        this.isLoading = false
      }
    },

    // REFRESH DATA
    async refreshTemplates() {
      this.templatesPagination.current_page = 1
      await this.fetchTemplates(1)
    },

    async refreshDrafts() {
      await this.fetchDrafts()
    },

    async refreshSentEmails() {
      await this.fetchSentEmails()
    },

    async refreshAll() {
      await this.initializeData()
    },

    // CACHE MANAGEMENT
    clearCache() {
      this.templates = []
      this.drafts = []
      this.sentEmails = []
      this.templatesPagination = {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0
      }
      this.clearErrors()
    }
  }
})