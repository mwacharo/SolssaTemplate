// stores/callCenterSettings.js
import { defineStore } from 'pinia'
import axios from 'axios'
import { computed } from 'vue'

export const useCallCenterSettings = defineStore('callCenterSettings', {
  state: () => ({
    settings: [],
    currentSetting: null,
    loading: false,
    error: null
  }),

  getters: {
    getSettingById: (state) => (id) => {
      return state.settings.find(setting => setting.id === id)
    },
    
    hasSettings: (state) => state.settings && state.settings.length > 0,
    
    allSettings: (state) => state.settings,
    
    isLoading: (state) => state.loading,
    
    hasError: (state) => !!state.error,
    
    errorMessage: (state) => state.error
  },

  actions: {
    // Fetch all settings
    async fetchSettings() {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/api/v1/call-center-settings')
        this.settings = response.data.data || response.data || []
        return { success: true, data: this.settings }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch settings'
        console.error('Fetch settings error:', error)
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Fetch a single setting by ID
    async fetchSetting(id) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get(`/api/v1/call-center-settings/${id}`)
        this.currentSetting = response.data.data || response.data
        return this.currentSetting
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch setting'
        console.error('Fetch setting error:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Create a new setting
    async createSetting(settingData) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.post('/api/v1/call-center-settings', settingData)
        const newSetting = response.data.data || response.data
        this.settings.push(newSetting)
        return { success: true, data: newSetting }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create setting'
        console.error('Create setting error:', error)
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Update an existing setting
    async updateSetting(id, settingData) {
      this.loading = true
      this.error = null
      try {
        const response = await axios.put(`/api/v1/call-center-settings/${id}`, settingData)
        const updatedSetting = response.data.data || response.data
        
        const index = this.settings.findIndex(setting => setting.id === id)
        if (index !== -1) {
          this.settings[index] = updatedSetting
        }
        
        if (this.currentSetting && this.currentSetting.id === id) {
          this.currentSetting = updatedSetting
        }
        
        return { success: true, data: updatedSetting }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update setting'
        console.error('Update setting error:', error)
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Delete a setting
    async deleteSetting(id) {
      this.loading = true
      this.error = null
      try {
        await axios.delete(`/api/v1/call-center-settings/${id}`)
        this.settings = this.settings.filter(setting => setting.id !== id)
        
        if (this.currentSetting && this.currentSetting.id === id) {
          this.currentSetting = null
        }
        
        return { success: true }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete setting'
        console.error('Delete setting error:', error)
        return { success: false, error: this.error }
      } finally {
        this.loading = false
      }
    },

    // Clear error state
    clearError() {
      this.error = null
    },

    // Clear current setting
    clearCurrentSetting() {
      this.currentSetting = null
    },

    // Reset entire store
    resetStore() {
      this.settings = []
      this.currentSetting = null
      this.loading = false
      this.error = null
    }
  }
})

// Composable function to use the store with utilities
export function useCallCenterSettingsComposable() {
  const store = useCallCenterSettings()

  // Voice options for dropdown
  const voiceOptions = [
        { value: 'woman', label: 'woman' },
 { value: 'man', label: 'man' },
    // { value: 'alloy', label: 'Alloy' },
    // { value: 'echo', label: 'Echo' },
    // { value: 'fable', label: 'Fable' },
    // { value: 'nova', label: 'Nova' },
    // { value: 'onyx', label: 'Onyx' },
    // { value: 'shimmer', label: 'Shimmer' }

  ]

  // Log level options
  const logLevelOptions = [
    { value: 'debug', label: 'Debug' },
    { value: 'info', label: 'Info' },
    { value: 'warning', label: 'Warning' },
    { value: 'error', label: 'Error' }
  ]

  // Default form data
  const getDefaultFormData = () => ({
    country_id: null,
    username: '',
    api_key: '',
    phone: '',
    sandbox: true,
    default_voice: 'alloy',
    timeout: 30,
    recording_enabled: false,
    welcome_message: '',
    no_input_message: '',
    invalid_option_message: '',
    connecting_agent_message: '',
    agents_busy_message: '',
    voicemail_prompt: '',
    callback_url: '',
    event_callback_url: '',
    ringback_tone: '',
    voicemail_callback: '',
    fallback_number: '',
    default_forward_number: '',
    debug_mode: false,
    log_level: 'info'
  })

  // Form validation
  const validateForm = (formData) => {
    const errors = {}
    
    // Required fields
    if (!formData.username?.trim()) {
      errors.username = 'Username is required'
    }
    
    if (!formData.api_key?.trim()) {
      errors.api_key = 'API Key is required'
    }
    
    if (!formData.phone?.trim()) {
      errors.phone = 'Phone number is required'
    } else if (!/^\+\d{10,15}$/.test(formData.phone.trim())) {
      errors.phone = 'Please enter a valid phone number with country code (e.g., +254700000000)'
    }
    
    if (!formData.country_id || formData.country_id < 1) {
      errors.country_id = 'Country ID is required and must be a positive number'
    }
    
    // Timeout validation
    if (formData.timeout && (formData.timeout < 1 || formData.timeout > 300)) {
      errors.timeout = 'Timeout must be between 1 and 300 seconds'
    }
    
    // URL validations
    const urlFields = ['callback_url', 'event_callback_url', 'ringback_tone', 'voicemail_callback']
    urlFields.forEach(field => {
      if (formData[field] && !isValidUrl(formData[field])) {
        errors[field] = 'Please enter a valid URL'
      }
    })
    
    return {
      isValid: Object.keys(errors).length === 0,
      errors
    }
  }

  // URL validation helper
  const isValidUrl = (string) => {
    try {
      new URL(string)
      return true
    } catch (_) {
      return false
    }
  }

  // Format phone number for display
  const formatPhoneNumber = (phone) => {
    if (!phone) return 'N/A'
    
    // Simple formatting - you can enhance this based on your needs
    const cleaned = phone.replace(/\D/g, '')
    
    if (cleaned.length >= 10) {
      const match = cleaned.match(/^(\d{3})(\d{3})(\d{3})(\d+)$/)
      if (match) {
        return `+${match[1]} ${match[2]} ${match[3]} ${match[4]}`
      }
    }
    
    return phone
  }

  // Get environment badge class
  const getEnvironmentBadgeClass = (sandbox) => {
    return sandbox 
      ? 'bg-yellow-100 text-yellow-800' 
      : 'bg-green-100 text-green-800'
  }

  // Get environment text
  const getEnvironmentText = (sandbox) => {
    return sandbox ? 'Sandbox' : 'Production'
  }

  // Format date
  const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleString()
  }

  return {
    // Store
    store,
    
    // State
    isLoading: computed(() => store.isLoading),
    hasError: computed(() => store.hasError),
    errorMessage: computed(() => store.errorMessage),
    settings: computed(() => store.settings),
    hasSettings: computed(() => store.hasSettings),
    currentSetting: computed(() => store.currentSetting),
    
    // Actions
    fetchSettings: store.fetchSettings,
    fetchSetting: store.fetchSetting,
    createSetting: store.createSetting,
    updateSetting: store.updateSetting,
    deleteSetting: store.deleteSetting,
    clearError: store.clearError,
    clearCurrentSetting: store.clearCurrentSetting,
    resetStore: store.resetStore,
    
    // Utilities
    voiceOptions,
    logLevelOptions,
    getDefaultFormData,
    validateForm,
    formatPhoneNumber,
    getEnvironmentBadgeClass,
    getEnvironmentText,
    formatDate
  }
}