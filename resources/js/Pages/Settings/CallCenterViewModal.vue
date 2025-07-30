<template>
  <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white max-h-screen overflow-y-auto">
      <div class="mt-3">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-semibold text-gray-900">Call Center Setting Details</h3>
          <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div v-if="setting" class="space-y-6">
          <!-- Basic Information -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-500">Username</label>
                <p class="mt-1 text-sm text-gray-900 font-medium">{{ setting.username }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Phone Number</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatPhoneNumber(setting.phone) }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Country ID</label>
                <p class="mt-1 text-sm text-gray-900">{{ setting.country_id }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Environment</label>
                <span :class="getEnvironmentBadgeClass(setting.sandbox)" class="mt-1 px-2 py-1 text-xs font-semibold rounded-full">
                  {{ getEnvironmentText(setting.sandbox) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Voice & Call Settings -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Voice & Call Settings</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-500">Default Voice</label>
                <p class="mt-1 text-sm text-gray-900 capitalize">{{ setting.default_voice }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Timeout</label>
                <p class="mt-1 text-sm text-gray-900">{{ setting.timeout }} seconds</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Log Level</label>
                <p class="mt-1 text-sm text-gray-900 capitalize">{{ setting.log_level }}</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
              <div>
                <label class="block text-sm font-medium text-gray-500">Recording Enabled</label>
                <span :class="setting.recording_enabled ? 'text-green-600' : 'text-red-600'" class="mt-1 text-sm font-medium">
                  {{ setting.recording_enabled ? 'Yes' : 'No' }}
                </span>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Debug Mode</label>
                <span :class="setting.debug_mode ? 'text-yellow-600' : 'text-gray-600'" class="mt-1 text-sm font-medium">
                  {{ setting.debug_mode ? 'Enabled' : 'Disabled' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Phone Numbers -->
          <div v-if="setting.fallback_number || setting.default_forward_number" class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Fallback Numbers</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-if="setting.fallback_number">
                <label class="block text-sm font-medium text-gray-500">Fallback Number</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatPhoneNumber(setting.fallback_number) }}</p>
              </div>
              
              <div v-if="setting.default_forward_number">
                <label class="block text-sm font-medium text-gray-500">Default Forward Number</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatPhoneNumber(setting.default_forward_number) }}</p>
              </div>
            </div>
          </div>

          <!-- Messages -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Voice Messages</h4>
            <div class="space-y-4">
              <div v-if="setting.welcome_message">
                <label class="block text-sm font-medium text-gray-500">Welcome Message</label>
                <p class="mt-1 text-sm text-gray-900 bg-white p-3 rounded border">{{ setting.welcome_message }}</p>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="setting.no_input_message">
                  <label class="block text-sm font-medium text-gray-500">No Input Message</label>
                  <p class="mt-1 text-sm text-gray-900 bg-white p-3 rounded border">{{ setting.no_input_message }}</p>
                </div>
                
                <div v-if="setting.invalid_option_message">
                  <label class="block text-sm font-medium text-gray-500">Invalid Option Message</label>
                  <p class="mt-1 text-sm text-gray-900 bg-white p-3 rounded border">{{ setting.invalid_option_message }}</p>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-if="setting.connecting_agent_message">
                  <label class="block text-sm font-medium text-gray-500">Connecting Agent Message</label>
                  <p class="mt-1 text-sm text-gray-900 bg-white p-3 rounded border">{{ setting.connecting_agent_message }}</p>
                </div>
                
                <div v-if="setting.agents_busy_message">
                  <label class="block text-sm font-medium text-gray-500">Agents Busy Message</label>
                  <p class="mt-1 text-sm text-gray-900 bg-white p-3 rounded border">{{ setting.agents_busy_message }}</p>
                </div>
              </div>
              
              <div v-if="setting.voicemail_prompt">
                <label class="block text-sm font-medium text-gray-500">Voicemail Prompt</label>
                <p class="mt-1 text-sm text-gray-900 bg-white p-3 rounded border">{{ setting.voicemail_prompt }}</p>
              </div>
            </div>
          </div>

          <!-- URLs & Callbacks -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">URLs & Callbacks</h4>
            <div class="space-y-4">
              <div class="grid grid-cols-1 gap-4">
                <div v-if="setting.callback_url">
                  <label class="block text-sm font-medium text-gray-500">Callback URL</label>
                  <a :href="setting.callback_url" target="_blank" class="mt-1 text-sm text-blue-600 hover:text-blue-800 break-all">
                    {{ setting.callback_url }}
                  </a>
                </div>
                
                <div v-if="setting.event_callback_url">
                  <label class="block text-sm font-medium text-gray-500">Event Callback URL</label>
                  <a :href="setting.event_callback_url" target="_blank" class="mt-1 text-sm text-blue-600 hover:text-blue-800 break-all">
                    {{ setting.event_callback_url }}
                  </a>
                </div>
                
                <div v-if="setting.ringback_tone">
                  <label class="block text-sm font-medium text-gray-500">Ringback Tone URL</label>
                  <a :href="setting.ringback_tone" target="_blank" class="mt-1 text-sm text-blue-600 hover:text-blue-800 break-all">
                    {{ setting.ringback_tone }}
                  </a>
                </div>
                
                <div v-if="setting.voicemail_callback">
                  <label class="block text-sm font-medium text-gray-500">Voicemail Callback URL</label>
                  <a :href="setting.voicemail_callback" target="_blank" class="mt-1 text-sm text-blue-600 hover:text-blue-800 break-all">
                    {{ setting.voicemail_callback }}
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- Timestamps -->
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-lg font-medium text-gray-900 mb-4">Timestamps</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-500">Created At</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(setting.created_at) }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Updated At</label>
                <p class="mt-1 text-sm text-gray-900">{{ formatDate(setting.updated_at) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-else class="text-center py-8">
          <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent mx-auto"></div>
          <p class="mt-2 text-gray-500">Loading setting details...</p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end mt-6 pt-6 border-t">
          <button
            @click="$emit('close')"
            class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useCallCenterSettingsComposable } from '@/stores/callCenterSettings'

defineProps({
  show: {
    type: Boolean,
    default: false
  },
  setting: {
    type: Object,
    default: null
  }
})

defineEmits(['close'])

const { formatPhoneNumber, getEnvironmentBadgeClass, getEnvironmentText, formatDate } = useCallCenterSettingsComposable()
</script>

<style scoped>
/* Custom scrollbar for modal */
.max-h-screen::-webkit-scrollbar {
  width: 8px;
}

.max-h-screen::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.max-h-screen::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.max-h-screen::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Smooth transitions */
.bg-gray-50 {
  transition: background-color 0.2s ease;
}

.bg-gray-50:hover {
  background-color: #f9fafb;
}

/* Link styles */
a {
  transition: color 0.2s ease;
}

/* Badge hover effects */
.px-2.py-1 {
  transition: all 0.2s ease;
}
</style>