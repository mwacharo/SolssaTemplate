// stores/countries.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCountriesStore = defineStore('countries', () => {
  // State
  const countries = ref([])
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
      // Replace with your actual API endpoint
      const response = await fetch('/api/countries')
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      const data = await response.json()
      countries.value = data.data || data
    } catch (err) {
      error.value = err.message
      console.error('Error fetching countries:', err)
      
      // Fallback with sample data for development
      countries.value = [
        {
          id: 1,
          name: 'United States',
          code: 'USA',
          phone_code: '+1',
          is_active: true,
          created_at: '2024-01-01T00:00:00Z',
          updated_at: '2024-01-01T00:00:00Z'
        },
        {
          id: 2,
          name: 'United Kingdom',
          code: 'GBR',
          phone_code: '+44',
          is_active: true,
          created_at: '2024-01-01T00:00:00Z',
          updated_at: '2024-01-01T00:00:00Z'
        },
        {
          id: 3,
          name: 'Canada',
          code: 'CAN',
          phone_code: '+1',
          is_active: true,
          created_at: '2024-01-01T00:00:00Z',
          updated_at: '2024-01-01T00:00:00Z'
        },
        {
          id: 4,
          name: 'Australia',
          code: 'AUS',
          phone_code: '+61',
          is_active: false,
          created_at: '2024-01-01T00:00:00Z',
          updated_at: '2024-01-01T00:00:00Z'
        }
      ]
    } finally {
      loading.value = false
    }
  }

  const createCountry = async (countryData) => {
    loading.value = true
    error.value = null
    
    try {
      // Validate required fields
      if (!countryData.name || !countryData.code || !countryData.phone_code) {
        throw new Error('Name, code, and phone code are required')
      }

      // Check for duplicate code
      if (getCountryByCode.value(countryData.code.toUpperCase())) {
        throw new Error('Country code already exists')
      }

      // Prepare data
      const dataToSend = {
        name: countryData.name.trim(),
        code: countryData.code.toUpperCase().trim(),
        phone_code: countryData.phone_code.trim(),
        is_active: countryData.is_active ?? true
      }

      // Replace with your actual API endpoint
      const response = await fetch('/api/countries', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          // Add CSRF token if needed
          // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(dataToSend)
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
      }

      const newCountry = await response.json()
      
      // Add to local state
      countries.value.unshift(newCountry.data || newCountry)
      
    } catch (err) {
      error.value = err.message
      console.error('Error creating country:', err)
      
      // For development - simulate API response
      const newCountry = {
        id: Date.now(),
        name: countryData.name.trim(),
        code: countryData.code.toUpperCase().trim(),
        phone_code: countryData.phone_code.trim(),
        is_active: countryData.is_active ?? true,
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      }
      
      countries.value.unshift(newCountry)
      
      throw err
    } finally {
      loading.value = false
    }
  }

  const updateCountry = async (id, countryData) => {
    loading.value = true
    error.value = null
    
    try {
      // Validate required fields
      if (!countryData.name || !countryData.code || !countryData.phone_code) {
        throw new Error('Name, code, and phone code are required')
      }

      // Check for duplicate code (excluding current country)
      const existingCountry = getCountryByCode.value(countryData.code.toUpperCase())
      if (existingCountry && existingCountry.id !== id) {
        throw new Error('Country code already exists')
      }

      // Prepare data
      const dataToSend = {
        name: countryData.name.trim(),
        code: countryData.code.toUpperCase().trim(),
        phone_code: countryData.phone_code.trim(),
        is_active: countryData.is_active ?? true
      }

      // Replace with your actual API endpoint
      const response = await fetch(`/api/countries/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          // Add CSRF token if needed
          // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(dataToSend)
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
      }

      const updatedCountry = await response.json()
      
      // Update local state
      const index = countries.value.findIndex(country => country.id === id)
      if (index !== -1) {
        countries.value[index] = updatedCountry.data || updatedCountry
      }
      
    } catch (err) {
      error.value = err.message
      console.error('Error updating country:', err)
      
      // For development - simulate API response
      const index = countries.value.findIndex(country => country.id === id)
      if (index !== -1) {
        countries.value[index] = {
          ...countries.value[index],
          name: countryData.name.trim(),
          code: countryData.code.toUpperCase().trim(),
          phone_code: countryData.phone_code.trim(),
          is_active: countryData.is_active ?? true,
          updated_at: new Date().toISOString()
        }
      }
      
      throw err
    } finally {
      loading.value = false
    }
  }

  const deleteCountry = async (id) => {
    loading.value = true
    error.value = null
    
    try {
      // Replace with your actual API endpoint
      const response = await fetch(`/api/countries/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          // Add CSRF token if needed
          // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
      }

      // Remove from local state
      const index = countries.value.findIndex(country => country.id === id)
      if (index !== -1) {
        countries.value.splice(index, 1)
      }
      
    } catch (err) {
      error.value = err.message
      console.error('Error deleting country:', err)
      
      // For development - simulate deletion
      const index = countries.value.findIndex(country => country.id === id)
      if (index !== -1) {
        countries.value.splice(index, 1)
      }
      
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

  const resetStore = () => {
    countries.value = []
    loading.value = false
    error.value = null
  }

  return {
    // State
    countries,
    loading,
    error,
    
    // Getters
    getCountryById,
    getCountryByCode,
    activeCountries,
    totalCountries,
    
    // Actions
    fetchCountries,
    createCountry,
    updateCountry,
    deleteCountry,
    toggleCountryStatus,
    resetStore
  }
})