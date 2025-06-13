// stores/vendorStore.js
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useVendorStore = defineStore('vendor', () => {
  // State
  const vendors = ref([])
  const loading = ref(false)
  const error = ref(null)

  // Getters
  const getVendorById = computed(() => {
    return (id) => vendors.value.find(vendor => vendor.id === id)
  })

  const activeVendors = computed(() => {
    return vendors.value.filter(vendor => vendor.status === true)
  })

  const vendorsByStage = computed(() => {
    return (stage) => vendors.value.filter(vendor => vendor.onboarding_stage === stage)
  })

  // Actions
  const fetchVendors = async () => {
    loading.value = true
    error.value = null
    
    try {
      // Replace with your actual API endpoint
      const response = await fetch('/api/vendors', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          // Add authentication headers if needed
          // 'Authorization': `Bearer ${token}`
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      vendors.value = data.data || data
    } catch (err) {
      error.value = err.message
      console.error('Error fetching vendors:', err)
      
      // Mock data for development/testing
      vendors.value = [
        {
          id: 1,
          name: 'John Doe',
          company_name: 'ABC Corporation',
          email: 'john@abc.com',
          billing_email: 'billing@abc.com',
          phone: '+1234567890',
          alt_phone: '+1234567891',
          address: '123 Main St, Suite 100',
          city: 'New York',
          state: 'NY',
          zip_code: '10001',
          country: 'USA',
          region: 'North America',
          warehouse_location: 'Warehouse A',
          preferred_pickup_time: '9:00 AM - 5:00 PM',
          contact_person_name: 'Jane Smith',
          business_type: 'Retailer',
          registration_number: 'REG123456',
          tax_id: 'TAX789012',
          website_url: 'https://abc.com',
          social_media_links: {
            facebook: 'https://facebook.com/abc',
            twitter: 'https://twitter.com/abc',
            linkedin: 'https://linkedin.com/company/abc',
            instagram: 'https://instagram.com/abc'
          },
          bank_account_info: {
            bank_name: 'First National Bank',
            account_number: '1234567890',
            routing_number: '123456789',
            swift_code: 'FNBKUS33'
          },
          delivery_mode: 'both',
          payment_terms: 'Net 30',
          credit_limit: 50000.00,
          integration_id: 'INT001',
          onboarding_stage: 'verified',
          last_active_at: '2024-01-15T10:30:00Z',
          rating: 4.5,
          status: true,
          notes: 'Excellent vendor with reliable delivery.',
          user_id: 1,
          created_at: '2024-01-01T00:00:00Z',
          updated_at: '2024-01-15T10:30:00Z'
        },
        {
          id: 2,
          name: 'Sarah Wilson',
          company_name: 'XYZ Supplies',
          email: 'sarah@xyz.com',
          billing_email: null,
          phone: '+1987654321',
          alt_phone: null,
          address: '456 Oak Avenue',
          city: 'Los Angeles',
          state: 'CA',
          zip_code: '90210',
          country: 'USA',
          region: 'West Coast',
          warehouse_location: 'Warehouse B',
          preferred_pickup_time: '8:00 AM - 4:00 PM',
          contact_person_name: 'Mike Johnson',
          business_type: 'Wholesaler',
          registration_number: 'REG654321',
          tax_id: 'TAX210987',
          website_url: 'https://xyz.com',
          social_media_links: {
            facebook: '',
            twitter: 'https://twitter.com/xyz',
            linkedin: 'https://linkedin.com/company/xyz',
            instagram: ''
          },
          bank_account_info: {
            bank_name: 'Pacific Bank',
            account_number: '0987654321',
            routing_number: '987654321',
            swift_code: 'PACBUS22'
          },
          delivery_mode: 'delivery',
          payment_terms: 'Net 15',
          credit_limit: 25000.00,
          integration_id: 'INT002',
          onboarding_stage: 'active',
          last_active_at: '2024-01-10T14:20:00Z',
          rating: 3.8,
          status: true,
          notes: 'Good vendor, sometimes delays in delivery.',
          user_id: 1,
          created_at: '2024-01-05T00:00:00Z',
          updated_at: '2024-01-10T14:20:00Z'
        }
      ]
    } finally {
      loading.value = false
    }
  }

  const createVendor = async (vendorData) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch('/api/vendors', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          // Add authentication headers if needed
        },
        body: JSON.stringify(vendorData)
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const newVendor = await response.json()
      vendors.value.push(newVendor.data || newVendor)
      
      return newVendor
    } catch (err) {
      error.value = err.message
      console.error('Error creating vendor:', err)
      
      // Mock creation for development
      const mockVendor = {
        ...vendorData,
        id: Date.now(), // Mock ID
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      }
      vendors.value.push(mockVendor)
      return mockVendor
    } finally {
      loading.value = false
    }
  }

  const updateVendor = async (id, vendorData) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`/api/vendors/${id}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          // Add authentication headers if needed
        },
        body: JSON.stringify(vendorData)
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const updatedVendor = await response.json()
      const index = vendors.value.findIndex(vendor => vendor.id === id)
      
      if (index !== -1) {
        vendors.value[index] = updatedVendor.data || updatedVendor
      }
      
      return updatedVendor
    } catch (err) {
      error.value = err.message
      console.error('Error updating vendor:', err)
      
      // Mock update for development
      const index = vendors.value.findIndex(vendor => vendor.id === id)
      if (index !== -1) {
        vendors.value[index] = {
          ...vendorData,
          id,
          updated_at: new Date().toISOString()
        }
      }
      return vendors.value[index]
    } finally {
      loading.value = false
    }
  }

  const deleteVendor = async (id) => {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`/api/vendors/${id}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          // Add authentication headers if needed
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      vendors.value = vendors.value.filter(vendor => vendor.id !== id)
      
      return true
    } catch (err) {
      error.value = err.message
      console.error('Error deleting vendor:', err)
      
      // Mock deletion for development
      vendors.value = vendors.value.filter(vendor => vendor.id !== id)
      return true
    } finally {
      loading.value = false
    }
  }

  const toggleVendorStatus = async (id) => {
    const vendor = vendors.value.find(v => v.id === id)
    if (vendor) {
      const updatedData = { ...vendor, status: !vendor.status }
      return await updateVendor(id, updatedData)
    }
  }

  const updateVendorRating = async (id, rating) => {
    const vendor = vendors.value.find(v => v.id === id)
    if (vendor) {
      const updatedData = { ...vendor, rating }
      return await updateVendor(id, updatedData)
    }
  }

  const searchVendors = (query) => {
    if (!query) return vendors.value
    
    const searchTerm = query.toLowerCase()
    return vendors.value.filter(vendor => 
      vendor.name.toLowerCase().includes(searchTerm) ||
      vendor.company_name?.toLowerCase().includes(searchTerm) ||
      vendor.email.toLowerCase().includes(searchTerm) ||
      vendor.phone.includes(searchTerm) ||
      vendor.city?.toLowerCase().includes(searchTerm)
    )
  }

  const getVendorStats = computed(() => {
    const total = vendors.value.length
    const active = vendors.value.filter(v => v.status).length
    const inactive = total - active
    const pending = vendors.value.filter(v => v.onboarding_stage === 'pending').length
    const verified = vendors.value.filter(v => v.onboarding_stage === 'verified').length
    
    return {
      total,
      active,
      inactive,
      pending,
      verified
    }
  })

  // Reset store
  const $reset = () => {
    vendors.value = []
    loading.value = false
    error.value = null
  }

  return {
    // State
    vendors,
    loading,
    error,
    
    // Getters
    getVendorById,
    activeVendors,
    vendorsByStage,
    getVendorStats,
    
    // Actions
    fetchVendors,
    createVendor,
    updateVendor,
    deleteVendor,
    toggleVendorStatus,
    updateVendorRating,
    searchVendors,
    $reset
  }
})