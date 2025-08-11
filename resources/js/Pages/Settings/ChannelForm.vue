<template>
  <v-form @submit.prevent="handleSave">
    <v-row>
      <!-- Provider Selection -->
      <v-col cols="12" md="6">
        <v-select
          v-model="formData.provider"
          :items="providers"
          label="Provider"
          variant="outlined"
          :rules="[rules.required]"
        />
      </v-col>

      <!-- Status Toggle -->
      <v-col cols="12" md="6">
        <v-switch
          v-model="formData.status"
          :label="formData.status === 'active' ? 'Active' : 'Inactive'"
          true-value="active"
          false-value="inactive"
          color="primary"
        />
      </v-col>

      <!-- Dynamic Fields Based on Channel -->
      <template v-if="channel === 'voice'">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.account_sid"
            label="Account SID"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.auth_token"
            label="Auth Token"
            type="password"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.phone_number"
            label="Phone Number"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.api_key"
            label="API Key"
            variant="outlined"
          />
        </v-col>
      </template>

      <template v-else-if="channel === 'email'">
        <v-col cols="12" md="6">
          <v-select
            v-model="formData.mail_mailer"
            :items="['smtp', 'sendmail', 'mailgun', 'ses', 'postmark']"
            label="Mail Mailer"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.from_name"
            label="From Name"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.from_address"
            label="From Email"
            type="email"
            variant="outlined"
            :rules="[rules.email]"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.smtp_host"
            label="SMTP Host"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.smtp_port"
            label="SMTP Port"
            type="number"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-select
            v-model="formData.encryption"
            :items="['tls', 'ssl']"
            label="Encryption"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.user_name"
            label="Username"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.password"
            label="Password"
            type="password"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.api_key"
            label="API Key (for API providers)"
            variant="outlined"
          />
        </v-col>
      </template>

      <template v-else-if="channel === 'sms'">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.account_sid"
            label="Account SID"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.auth_token"
            label="Auth Token"
            type="password"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.phone_number"
            label="From Number"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.api_key"
            label="API Key"
            variant="outlined"
          />
        </v-col>


            <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.api_url"
            label="API URL"
            variant="outlined"
            :rules="[rules.url]"
          />
        </v-col>




          <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.instance_id"
            label="Instance ID"
            variant="outlined"
          />
        </v-col>
      </template>

      <template v-else-if="channel === 'whatsapp'">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.api_key"
            label="Business API Key"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.phone_number"
            label="Business Phone Number"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.account_id"
            label="Business Account ID"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.webhook"
            label="Webhook URL"
            variant="outlined"
            :rules="[rules.url]"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.instance_id"
            label="Instance ID"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.api_url"
            label="API URL"
            variant="outlined"
            :rules="[rules.url]"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.api_token"
            label="API Token"
            variant="outlined"
          />
        </v-col>
      </template>

      <template v-else-if="channel === 'telegram'">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.access_token"
            label="Bot Token"
            variant="outlined"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.user_name"
            label="Bot Username"
            variant="outlined"
          />
        </v-col>
        <v-col cols="12">
          <v-text-field
            v-model="formData.webhook"
            label="Webhook URL"
            variant="outlined"
            :rules="[rules.url]"
          />
        </v-col>
      </template>

      <template v-else-if="channel === 'twitter'">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.api_key"
            label="API Key"
            variant="outlined"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.api_secret"
            label="API Secret"
            type="password"
            variant="outlined"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.access_token"
            label="Access Token"
            variant="outlined"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.access_token_secret"
            label="Access Token Secret"
            type="password"
            variant="outlined"
            :rules="[rules.required]"
          />
        </v-col>
      </template>

      <template v-else-if="channel === 'facebook'">
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.app_id"
            label="App ID"
            variant="outlined"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.app_secret"
            label="App Secret"
            type="password"
            variant="outlined"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.page_access_token"
            label="Page Access Token"
            variant="outlined"
            :rules="[rules.required]"
          />
        </v-col>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="formData.page_id"
            label="Page ID"
            variant="outlined"
            :rules="[rules.required]"
          />
        </v-col>
      </template>

      <!-- Description (common for all channels) -->
      <v-col cols="12">
        <v-textarea
          v-model="formData.description"
          label="Description (optional)"
          variant="outlined"
          rows="2"
        />
      </v-col>
    </v-row>

    <!-- Actions -->
    <v-card-actions class="px-0">
      <v-spacer />
      <v-btn 
        color="grey" 
        variant="outlined" 
        @click="handleReset"
        :disabled="loading"
      >
        Reset
      </v-btn>
      <v-btn 
        color="primary" 
        type="submit"
        :loading="loading"
      >
        Save Configuration
      </v-btn>
    </v-card-actions>
  </v-form>
</template>

<script>
import { ref, watch, computed } from 'vue'

export default {
  name: 'ChannelForm',
  props: {
    channel: {
      type: String,
      required: true
    },
    credential: {
      type: Object,
      default: () => ({})
    },
    providers: {
      type: Array,
      default: () => []
    }
  },
  emits: ['save', 'reset'],
  
  setup(props, { emit }) {
    const loading = ref(false)
    const formData = ref({})

    // Validation rules
    const rules = {
      required: (value) => !!value || 'This field is required',
      email: (value) => {
        const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        return !value || pattern.test(value) || 'Please enter a valid email address'
      },
      url: (value) => {
        try {
          if (!value) return true
          new URL(value)
          return true
        } catch {
          return 'Please enter a valid URL'
        }
      }
    }

    // Initialize form data when credential changes
    const initializeForm = () => {
      formData.value = {
        channel: props.channel,
        provider: props.credential.provider || (props.providers[0] || ''),
        status: props.credential.status || 'inactive',
        api_key: props.credential.api_key || '',
        api_secret: props.credential.api_secret || '',
        access_token: props.credential.access_token || '',
        access_token_secret: props.credential.access_token_secret || '',
        auth_token: props.credential.auth_token || '',
        user_name: props.credential.user_name || '',
        password: props.credential.password || '',
        account_sid: props.credential.account_sid || '',
        account_id: props.credential.account_id || '',
        app_id: props.credential.app_id || '',
        app_secret: props.credential.app_secret || '',
        page_access_token: props.credential.page_access_token || '',
        page_id: props.credential.page_id || '',
        phone_number: props.credential.phone_number || '',
        webhook: props.credential.webhook || '',
        instance_id: props.credential.instance_id || '',
        api_url: props.credential.api_url || 'https://api.green-api.com',
        api_token: props.credential.api_token || '',
        from_name: props.credential.from_name || '',
        from_address: props.credential.from_address || '',
        smtp_host: props.credential.smtp_host || '',
        smtp_port: props.credential.smtp_port || 587,
        encryption: props.credential.encryption || 'tls',
        mail_mailer: props.credential.mail_mailer || 'smtp',
        description: props.credential.description || '',
        value: props.credential.value || '',
        meta: props.credential.meta || {}
      }
    }

    const handleSave = async () => {
      loading.value = true
      try {
        // Clean up empty values to avoid storing null strings
        const cleanData = Object.entries(formData.value).reduce((acc, [key, value]) => {
          if (value !== '' && value !== null && value !== undefined) {
            acc[key] = value
          }
          return acc
        }, {})

        emit('save', cleanData)
      } finally {
        loading.value = false
      }
    }

    const handleReset = () => {
      initializeForm()
      emit('reset', props.channel)
    }

    // Watch for credential changes
    watch(() => props.credential, initializeForm, { immediate: true, deep: true })

    return {
      formData,
      loading,
      rules,
      handleSave,
      handleReset
    }
  }
}
</script>

<style scoped>
.v-card-actions {
  padding-top: 16px;
}
</style>