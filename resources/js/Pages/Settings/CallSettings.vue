<template>
  <AppLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Call Center Settings
        </h2>
        <button
          @click="openCreateModal"
          class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
        >
          Add New Setting
        </button>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Success Message -->
        <div v-if="successMessage" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          {{ successMessage }}
          <button @click="successMessage = ''" class="float-right">×</button>
        </div>

        <!-- Error Alert -->
        <div v-if="hasError" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {{ errorMessage }}
          <button @click="clearError()" class="float-right">×</button>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="bg-white shadow-sm sm:rounded-lg p-6">
          <div class="flex justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent"></div>
          </div>
        </div>

        <!-- Settings Table -->
        <div v-else class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div v-if="!hasSettings" class="text-center py-8">
              <p class="text-gray-500 text-lg mb-4">No call center settings found.</p>
              <button
                @click="openCreateModal"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
              >
                Create Your First Setting
              </button>
            </div>

            <div v-else class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Environment</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Voice</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="setting in settings" :key="setting.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                      {{ setting.username }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500">
                      {{ formatPhoneNumber(setting.phone) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getEnvironmentBadgeClass(setting.sandbox)" 
                            class="px-2 py-1 text-xs font-semibold rounded-full">
                        {{ getEnvironmentText(setting.sandbox) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 capitalize">
                      {{ setting.default_voice }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 text-sm">
                      {{ formatDate(setting.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                      <button @click="viewSetting(setting)" class="text-blue-600 hover:text-blue-900">
                        View
                      </button>
                      <button @click="editSetting(setting)" class="text-indigo-600 hover:text-indigo-900">
                        Edit
                      </button>
                      <button @click="confirmDelete(setting)" class="text-red-600 hover:text-red-900">
                        Delete
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <CallCenterModal
      v-if="showModal"
      :show="showModal"
      :mode="modalMode"
      :setting="currentSetting"
      @close="closeModal"
      @submit="handleSubmit"
    />

    <!-- View Modal -->
    <CallCenterViewModal
      v-if="showViewModal"
      :show="showViewModal"
      :setting="viewingSetting"
      @close="showViewModal = false"
    />

    <!-- Delete Confirmation Modal -->
    <DeleteConfirmModal
      v-if="showDeleteModal"
      :show="showDeleteModal"
      :title="`Delete ${deletingSetting?.username} Setting`"
      :message="'This action cannot be undone. All associated data will be permanently removed.'"
      :loading="isLoading"
      @confirm="handleDelete"
      @cancel="showDeleteModal = false"
    />
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useCallCenterSettingsComposable } from '@/stores/callCenterSettings'
import CallCenterModal from '@/Pages/Settings/CallCenterModal.vue'
import CallCenterViewModal from '@/Pages/Settings/CallCenterViewModal.vue'
import DeleteConfirmModal from '@/Components/DeleteConfirmModal.vue'

// Use the composable
const {
  store,
  isLoading,
  hasError,
  errorMessage,
  settings,
  hasSettings,
  createSetting,
  updateSetting,
  deleteSetting,
  fetchSettings,
  clearError,
  formatPhoneNumber,
  getEnvironmentBadgeClass,
  getEnvironmentText,
  formatDate
} = useCallCenterSettingsComposable()

// Local state
const successMessage = ref('')

// Modal states
const showModal = ref(false)
const showViewModal = ref(false)
const showDeleteModal = ref(false)
const modalMode = ref('create') // 'create' or 'edit'

// Current items
const currentSetting = ref(null)
const viewingSetting = ref(null)
const deletingSetting = ref(null)

// Load settings on mount
onMounted(() => {
  loadSettings()
})

// Load settings with error handling
const loadSettings = async () => {
  try {
    await fetchSettings()
  } catch (error) {
    console.error('Failed to load settings:', error)
  }
}

// Show success message
const showSuccess = (message) => {
  successMessage.value = message
  setTimeout(() => {
    successMessage.value = ''
  }, 5000)
}

// Modal handlers
const openCreateModal = () => {
  modalMode.value = 'create'
  currentSetting.value = null
  showModal.value = true
}

const viewSetting = (setting) => {
  viewingSetting.value = setting
  showViewModal.value = true
}

const editSetting = (setting) => {
  modalMode.value = 'edit'
  currentSetting.value = setting
  showModal.value = true
}

const confirmDelete = (setting) => {
  deletingSetting.value = setting
  showDeleteModal.value = true
}

const closeModal = () => {
  showModal.value = false
  currentSetting.value = null
}

// CRUD handlers
const handleSubmit = async (formData) => {
  let result
  
  try {
    if (modalMode.value === 'create') {
      result = await createSetting(formData)
      if (result.success) {
        showSuccess('Setting created successfully!')
        closeModal()
      }
    } else {
      result = await updateSetting(currentSetting.value.id, formData)
      if (result.success) {
        showSuccess('Setting updated successfully!')
        closeModal()
      }
    }
  } catch (error) {
    console.error('Submit error:', error)
    // Error is handled by the store and will show in the error alert
  }
}

const handleDelete = async () => {
  try {
    const result = await deleteSetting(deletingSetting.value.id)
    
    if (result.success) {
      showSuccess('Setting deleted successfully!')
      showDeleteModal.value = false
      deletingSetting.value = null
    }
  } catch (error) {
    console.error('Delete error:', error)
    // Error is handled by the store and will show in the error alert
  }
}

// Refresh data
const refreshData = () => {
  loadSettings()
}

// Export for potential use in parent components
defineExpose({
  refreshData,
  openCreateModal
})
</script>

<style scoped>
/* Add any component-specific styles here */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>