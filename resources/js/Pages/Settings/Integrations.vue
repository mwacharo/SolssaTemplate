<template>
  <AppLayout>
    <!-- Owner Selection Card -->
    <VCard class="mb-4">
      <v-card-title>Select Owner</v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="12" md="6">
            <v-autocomplete 
              v-model="selectedOwnerType" 
              :items="ownerTypes" 
              label="Owner Type"
              variant="outlined" 
              @update:model-value="loadOwners"
            />
          </v-col>
          <v-col cols="12" md="6">
            <v-autocomplete 
              v-model="selectedOwnerId" 
              :items="owners" 
              item-title="name" 
              item-value="id"
              label="Owner" 
              variant="outlined" 
              :disabled="!selectedOwnerType"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </VCard>

    <!-- Channel Configuration Tabs -->
    <VCard v-if="selectedOwnerId">
      <v-tabs v-model="activeTab" bg-color="primary">
        <v-tab v-for="channel in channels" :key="channel.value" :value="channel.value">
          <v-icon start>{{ channel.icon }}</v-icon>
          {{ channel.title }}
        </v-tab>
      </v-tabs>

      <v-card-text>
        <v-window v-model="activeTab">
          <v-window-item v-for="channel in channels" :key="channel.value" :value="channel.value">
            <ChannelForm 
              :channel="channel.value"
              :credential="getCredentialByChannel(channel.value)"
              :providers="getProvidersByChannel(channel.value)"
              @save="saveCredential"
              @reset="resetCredential"
            />
          </v-window-item>
        </v-window>
      </v-card-text>
    </VCard>

    <!-- Credentials List -->
    <VCard v-if="selectedOwnerId" class="mt-4">
      <v-card-title>
        Existing Credentials
        <v-spacer />
        <v-text-field
          v-model="searchQuery"
          label="Search"
          prepend-inner-icon="mdi-magnify"
          clearable
          variant="outlined"
          density="compact"
          style="max-width: 300px;"
        />
      </v-card-title>
      
      <v-card-text>
        <v-data-table
          :headers="tableHeaders"
          :items="filteredCredentials"
          :loading="loading"
          class="elevation-1"
        >
          <template #item.channel="{ item }">
            <v-chip :color="getChannelColor(item.channel)">
              <v-icon start>{{ getChannelIcon(item.channel) }}</v-icon>
              {{ item.channel }}
            </v-chip>
          </template>
          
          <template #item.status="{ item }">
            <v-chip :color="item.status === 'active' ? 'success' : 'error'">
              {{ item.status === 'active' ? 'Active' : 'Inactive' }}
            </v-chip>
          </template>
          
          <template #item.actions="{ item }">
            <v-btn icon variant="text" size="small" color="primary" @click="editCredential(item)">
              <v-icon>mdi-pencil</v-icon>
            </v-btn>
            <v-btn icon variant="text" size="small" color="error" @click="deleteCredential(item)">
              <v-icon>mdi-delete</v-icon>
            </v-btn>
          </template>
        </v-data-table>
      </v-card-text>
    </VCard>
  </AppLayout>
</template>

<script>
import { ref, computed, watch, onMounted } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"
import ChannelForm from "./ChannelForm.vue"
import { useChannelCredentials } from '@/stores/useChannelCredentials'
import { notify } from '@/utils/toast'

export default {
  name: 'ChannelCredentials',
  components: {
    AppLayout,
    ChannelForm,
  },
  
  setup() {
    // Reactive data
    const selectedOwnerType = ref(null)
    const selectedOwnerId = ref(null)
    const activeTab = ref('voice')
    const searchQuery = ref('')
    const loading = ref(false)
    
    // Use composable for business logic
    const {
      ownerTypes,
      owners,
      credentials,
      loadOwners,
      loadCredentials,
      saveCredential: saveCredentialApi,
      deleteCredential: deleteCredentialApi
    } = useChannelCredentials()

    // Static data
    const channels = [
      { value: 'voice', icon: 'mdi-headset', title: 'Voice/Call Center' },
      { value: 'whatsapp', icon: 'mdi-whatsapp', title: 'WhatsApp' },
      { value: 'email', icon: 'mdi-email-outline', title: 'Email' },
      { value: 'sms', icon: 'mdi-message-text-outline', title: 'SMS' },
      { value: 'telegram', icon: 'mdi-telegram', title: 'Telegram' },
      { value: 'twitter', icon: 'mdi-twitter', title: 'Twitter' },
      { value: 'facebook', icon: 'mdi-facebook', title: 'Facebook' },
    ]

    const providers = {
      voice: ['Twilio', 'Vonage', 'Advanta', 'Africa\'s Talking', 'Amazon Connect', 'Plivo'],
      email: ['SendGrid', 'Mailgun', 'SMTP', 'Amazon SES', 'Postmark', 'Zoho'],
      sms: ['Twilio', 'Vonage', 'Advanta', 'MessageBird', 'Plivo'],
      whatsapp: ['Meta', 'Twilio'],
      telegram: ['Telegram'],
      twitter: ['Twitter'],
      facebook: ['Facebook']
    }

    const tableHeaders = [
      { title: 'Channel', key: 'channel', sortable: true },
      { title: 'Provider', key: 'provider', sortable: true },
      { title: 'Status', key: 'status', sortable: true },
      { title: 'Updated', key: 'updated_at', sortable: true },
      { title: 'Actions', key: 'actions', sortable: false }
    ]

    // Computed properties
    const filteredCredentials = computed(() => {
      if (!searchQuery.value) return credentials.value
      
      const query = searchQuery.value.toLowerCase()
      return credentials.value.filter(credential => 
        credential.channel.toLowerCase().includes(query) ||
        (credential.provider && credential.provider.toLowerCase().includes(query)) ||
        credential.status.toLowerCase().includes(query)
      )
    })

    // Methods
    const getCredentialByChannel = (channel) => {
      return credentials.value.find(c => c.channel === channel) || createEmptyCredential(channel)
    }

    const createEmptyCredential = (channel) => ({
      channel,
      provider: providers[channel]?.[0] || '',
      status: 'inactive',
      // Initialize all possible fields to avoid undefined errors
      api_key: '',
      api_secret: '',
      access_token: '',
      auth_token: '',
      user_name: '',
      password: '',
      account_sid: '',
      phone_number: '',
      webhook: '',
      from_name: '',
      from_address: '',
      smtp_host: '',
      smtp_port: 587,
      encryption: 'tls',
      mail_mailer: 'smtp'
    })

    const getProvidersByChannel = (channel) => providers[channel] || []

    const getChannelColor = (channel) => {
      const colors = {
        voice: 'indigo',
        whatsapp: 'green',
        email: 'blue',
        sms: 'purple',
        telegram: 'cyan',
        twitter: 'light-blue',
        facebook: 'blue-darken-2'
      }
      return colors[channel] || 'grey'
    }

    const getChannelIcon = (channel) => {
      const icons = {
        voice: 'mdi-headset',
        whatsapp: 'mdi-whatsapp',
        email: 'mdi-email-outline',
        sms: 'mdi-message-text-outline',
        telegram: 'mdi-telegram',
        twitter: 'mdi-twitter',
        facebook: 'mdi-facebook'
      }
      return icons[channel] || 'mdi-help-circle'
    }

    const saveCredential = async (credentialData) => {
      if (!selectedOwnerType.value || !selectedOwnerId.value) {
        notify.error('Please select an owner first')
        return
      }

      loading.value = true
      try {
        await saveCredentialApi({
          ...credentialData,
          credentialable_type: selectedOwnerType.value,
          credentialable_id: selectedOwnerId.value
        })
        notify.success('Credential saved successfully')
        await loadCredentials(selectedOwnerType.value, selectedOwnerId.value)
      } catch (error) {
        notify.error('Failed to save credential')
        console.error(error)
      } finally {
        loading.value = false
      }
    }

    const editCredential = (credential) => {
      activeTab.value = credential.channel
    }

    const deleteCredential = async (credential) => {
      if (!confirm(`Delete ${credential.channel} credential?`)) return

      loading.value = true
      try {
        await deleteCredentialApi(credential.id)
        notify.success('Credential deleted successfully')
        await loadCredentials(selectedOwnerType.value, selectedOwnerId.value)
      } catch (error) {
        notify.error('Failed to delete credential')
        console.error(error)
      } finally {
        loading.value = false
      }
    }

    const resetCredential = (channel) => {
      // Reset logic - could emit to child component or handle differently
      console.log(`Resetting ${channel} credential`)
    }

    // Watchers
    watch([selectedOwnerType, selectedOwnerId], async ([newOwnerType, newOwnerId]) => {
      if (newOwnerType && newOwnerId) {
        loading.value = true
        try {
          await loadCredentials(newOwnerType, newOwnerId)
        } catch (error) {
          notify.error('Failed to load credentials')
        } finally {
          loading.value = false
        }
      }
    })

    // Lifecycle
    onMounted(() => {
      // Load initial data if needed
    })

    return {
      // Reactive data
      selectedOwnerType,
      selectedOwnerId,
      activeTab,
      searchQuery,
      loading,
      
      // From composable
      ownerTypes,
      owners,
      loadOwners,
      
      // Static data
      channels,
      tableHeaders,
      
      // Computed
      filteredCredentials,
      
      // Methods
      getCredentialByChannel,
      getProvidersByChannel,
      getChannelColor,
      getChannelIcon,
      saveCredential,
      editCredential,
      deleteCredential,
      resetCredential
    }
  }
}
</script>

<style scoped>
.v-card {
  margin-bottom: 1rem;
}
</style>