// stores/countries.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

const api = axios.create({
  baseURL: '/api/v1',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
    // Add CSRF token if needed
    // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
  }
})

export const useCountriesStore = defineStore('countries', () => {
  // State
  const countries = ref([])
  const countrySettings = ref({}) // Store settings by country ID
  const loading = ref(false)
  const error = ref(null)

  // Getters
  const getCountryById = computed(() => {
    return (id) => countries.value.find(country => country.id === id)
  })

  const getCountryByCode = computed(() => {
    return (code) => countries.value.find(country => country.code === code)
  })

  const activeCountries = computed(() => {
    return countries.value.filter(country => country.is_active)
  })

  const totalCountries = computed(() => countries.value.length)

  // Actions
  const fetchCountries = async () => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/countries')
      countries.value = data.data || data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error fetching countries:', err)
      countries.value = []
    } finally {
      loading.value = false
    }
  }

  const createCountry = async (countryData) => {
    loading.value = true
    error.value = null
    try {
      if (!countryData.name || !countryData.code || !countryData.phone_code) {
        throw new Error('Name, code, and phone code are required')
      }
      if (getCountryByCode.value(countryData.code.toUpperCase())) {
        throw new Error('Country code already exists')
      }
      const dataToSend = {
        name: countryData.name.trim(),
        code: countryData.code.toUpperCase().trim(),
        phone_code: countryData.phone_code.trim(),
        is_active: countryData.is_active ?? true
      }
      const { data } = await api.post('/countries', dataToSend)
      countries.value.unshift(data.data || data)
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error creating country:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateCountry = async (id, countryData) => {
    loading.value = true
    error.value = null
    try {
      if (!countryData.name || !countryData.code || !countryData.phone_code) {
        throw new Error('Name, code, and phone code are required')
      }
      const existingCountry = getCountryByCode.value(countryData.code.toUpperCase())
      if (existingCountry && existingCountry.id !== id) {
        throw new Error('Country code already exists')
      }
      const dataToSend = {
        name: countryData.name.trim(),
        code: countryData.code.toUpperCase().trim(),
        phone_code: countryData.phone_code.trim(),
        is_active: countryData.is_active ?? true
      }
      const { data } = await api.put(`/countries/${id}`, dataToSend)
      const index = countries.value.findIndex(country => country.id === id)
      if (index !== -1) {
        countries.value[index] = data.data || data
      }
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error updating country:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteCountry = async (id) => {
    loading.value = true
    error.value = null
    try {
      await api.delete(`/countries/${id}`)
      const index = countries.value.findIndex(country => country.id === id)
      if (index !== -1) {
        countries.value.splice(index, 1)
      }
      // Also remove settings if they exist
      if (countrySettings.value[id]) {
        delete countrySettings.value[id]
      }
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error deleting country:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const toggleCountryStatus = async (id) => {
    const country = getCountryById.value(id)
    if (!country) return
    await updateCountry(id, {
      ...country,
      is_active: !country.is_active
    })
  }

  // Settings-related actions
  const getCountrySettings = async (countryId) => {
    try {
      // Try to get from cache first
      if (countrySettings.value[countryId]) {
        return countrySettings.value[countryId]
      }

      // Fetch from API
      const { data } = await api.get(`/countries/${countryId}/settings`)
      let settings = data.data || data

      // If options is a JSON string, parse it
      if (settings.options && typeof settings.options === 'string') {
        try {
          settings.options = JSON.parse(settings.options)
        } catch (e) {
          settings.options = {}
        }
      }

      // Cache the settings
      countrySettings.value[countryId] = settings

      return settings
    } catch (err) {
      console.error('Error fetching country settings:', err)

      // Return default settings if API fails
      // const defaultSettings = {
      //   template_name: `Template for ${getCountryById.value(countryId)?.name || 'Country'}`,
      //   name: 'Company Name',
      //   phone: '+1234567890',
      //   email: 'info@company.com',
      //   address: 'Company Address',
      //   terms: 'Standard terms and conditions',
      //   footer: 'Thank you for your business',
      //   color: '#1976d2',
      //   size: 'A4',
      //   options: {}
      // }

      // Cache default settings
      countrySettings.value[countryId] = defaultSettings

      return defaultSettings
    }
  }

  const saveCountrySettings = async (countryId, settingsData) => {
    loading.value = true
    error.value = null
    
    try {
      const { data } = await api.post(`/countries/${countryId}/settings`, settingsData)
      const savedSettings = data.data || data
      
      // Update cache
      countrySettings.value[countryId] = savedSettings
      
      return savedSettings
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error saving country settings:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateCountrySettings = async (countryId, settingsData) => {
    loading.value = true
    error.value = null
    
    try {
      const { data } = await api.put(`/countries/${countryId}/settings`, settingsData)
      const updatedSettings = data.data || data
      
      // Update cache
      countrySettings.value[countryId] = updatedSettings
      
      return updatedSettings
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error updating country settings:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteCountrySettings = async (countryId) => {
    loading.value = true
    error.value = null
    
    try {
      await api.delete(`/countries/${countryId}/settings`)
      
      // Remove from cache
      if (countrySettings.value[countryId]) {
        delete countrySettings.value[countryId]
      }
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error deleting country settings:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  // Upload logo (if needed)
  const uploadLogo = async (file) => {
    const formData = new FormData()
    formData.append('logo', file)
    
    try {
      const { data } = await api.post('/upload/logo', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      
      return data.url || data.data?.url
    } catch (err) {
      console.error('Error uploading logo:', err)
      throw err
    }
  }

  const resetStore = () => {
    countries.value = []
    countrySettings.value = {}
    loading.value = false
    error.value = null
  }

  return {
    // State
    countries,
    countrySettings,
    loading,
    error,
    
    // Getters
    getCountryById,
    getCountryByCode,
    activeCountries,
    totalCountries,
    
    // Country actions
    fetchCountries,
    createCountry,
    updateCountry,
    deleteCountry,
    toggleCountryStatus,
    
    // Settings actions
    getCountrySettings,
    saveCountrySettings,
    updateCountrySettings,
    deleteCountrySettings,
    uploadLogo,
    
    // Utility
    resetStore
  }
})