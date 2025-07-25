// composables/useChannelCredentials.js
import { ref, reactive } from 'vue'
import axios from 'axios'

export function useChannelCredentials() {
  // Reactive state
  const ownerTypes = ref([])
  const owners = ref([])
  const credentials = ref([])
  const loading = ref(false)
  const error = ref(null)

  // API base URL - adjust according to your setup
  const API_BASE = '/api/v1'

  /**
   * Load available owner types
   */
  const loadOwnerTypes = async () => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.get(`${API_BASE}/credentialable-types`)
      ownerTypes.value = response.data.types || []
      
      return ownerTypes.value
    } catch (err) {
      error.value = 'Failed to load owner types'
      console.error('Error loading owner types:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Load owners by type
   */
  const loadOwners = async (ownerType) => {
    if (!ownerType) {
      owners.value = []
      return []
    }

    try {
      loading.value = true
      error.value = null
      
      const response = await axios.get(`${API_BASE}/credentialables`, {
        params: { type: ownerType }
      })
      
      owners.value = response.data.owners || []
      return owners.value
    } catch (err) {
      error.value = 'Failed to load owners'
      console.error('Error loading owners:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Load credentials for a specific owner
   */
  const loadCredentials = async (ownerType, ownerId) => {
    if (!ownerType || !ownerId) {
      credentials.value = []
      return []
    }

    try {
      loading.value = true
      error.value = null
      
      const response = await axios.get(`${API_BASE}/channel-credentials`, {
        params: {
          credentialable_type: ownerType,
          credentialable_id: ownerId
        }
      })
      
      credentials.value = response.data.credentials || []
      return credentials.value
    } catch (err) {
      error.value = 'Failed to load credentials'
      console.error('Error loading credentials:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Save or update a credential
   */
  const saveCredential = async (credentialData) => {
    try {
      loading.value = true
      error.value = null

      // Determine if this is an update or create
      const existingCredential = credentials.value.find(
        c => c.channel === credentialData.channel && 
             c.credentialable_type === credentialData.credentialable_type &&
             c.credentialable_id === credentialData.credentialable_id
      )

      let response
      if (existingCredential) {
        // Update existing credential
        response = await axios.put(
          `${API_BASE}/channel-credentials/${existingCredential.id}`,
          credentialData
        )
      } else {
        // Create new credential
        response = await axios.post(`${API_BASE}/channel-credentials`, credentialData)
      }

      return response.data
    } catch (err) {
      error.value = 'Failed to save credential'
      console.error('Error saving credential:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Delete a credential
   */
  const deleteCredential = async (credentialId) => {
    try {
      loading.value = true
      error.value = null
      
      await axios.delete(`${API_BASE}/channel-credentials/${credentialId}`)
      
      // Remove from local state
      credentials.value = credentials.value.filter(c => c.id !== credentialId)
      
      return true
    } catch (err) {
      error.value = 'Failed to delete credential'
      console.error('Error deleting credential:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Test a credential configuration
   */
  const testCredential = async (credentialId) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.post(`${API_BASE}/channel-credentials/${credentialId}/test`)
      return response.data
    } catch (err) {
      error.value = 'Failed to test credential'
      console.error('Error testing credential:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Bulk update credentials status
   */
  const bulkUpdateStatus = async (credentialIds, status) => {
    try {
      loading.value = true
      error.value = null
      
      const response = await axios.patch(`${API_BASE}/channel-credentials/bulk-status`, {
        credential_ids: credentialIds,
        status
      })
      
      // Update local state
      credentials.value = credentials.value.map(credential => {
        if (credentialIds.includes(credential.id)) {
          return { ...credential, status }
        }
        return credential
      })
      
      return response.data
    } catch (err) {
      error.value = 'Failed to update credentials status'
      console.error('Error updating credentials status:', err)
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Get credentials by channel
   */
  const getCredentialsByChannel = (channel) => {
    return credentials.value.filter(c => c.channel === channel)
  }

  /**
   * Get active credentials only
   */
  const getActiveCredentials = () => {
    return credentials.value.filter(c => c.status === 'active')
  }

  /**
   * Validate credential data before saving
   */
  const validateCredential = (credentialData) => {
    const errors = []
    
    if (!credentialData.channel) {
      errors.push('Channel is required')
    }
    
    if (!credentialData.provider) {
      errors.push('Provider is required')
    }

    // Channel-specific validation
    switch (credentialData.channel) {
      case 'email':
        if (!credentialData.from_address) {
          errors.push('From address is required for email')
        }
        if (credentialData.mail_mailer === 'smtp' && !credentialData.smtp_host) {
          errors.push('SMTP host is required for SMTP mailer')
        }
        break
        
      case 'voice':
      case 'sms':
        if (credentialData.provider === 'Twilio' && !credentialData.account_sid) {
          errors.push('Account SID is required for Twilio')
        }
        break
        
      case 'whatsapp':
        if (!credentialData.api_key && !credentialData.access_token) {
          errors.push('API Key or Access Token is required for WhatsApp')
        }
        break
        
      case 'telegram':
        if (!credentialData.access_token) {
          errors.push('Bot token is required for Telegram')
        }
        break
        
      case 'twitter':
        if (!credentialData.api_key || !credentialData.api_secret) {
          errors.push('API Key and Secret are required for Twitter')
        }
        break
        
      case 'facebook':
        if (!credentialData.app_id || !credentialData.app_secret) {
          errors.push('App ID and Secret are required for Facebook')
        }
        break
    }
    
    return {
      isValid: errors.length === 0,
      errors
    }
  }

  // Initialize owner types on composable creation
  loadOwnerTypes()

  return {
    // State
    ownerTypes,
    owners,
    credentials,
    loading,
    error,
    
    // Methods
    loadOwnerTypes,
    loadOwners,
    loadCredentials,
    saveCredential,
    deleteCredential,
    testCredential,
    bulkUpdateStatus,
    
    // Utility methods
    getCredentialsByChannel,
    getActiveCredentials,
    validateCredential
  }
}