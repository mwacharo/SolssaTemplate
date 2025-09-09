<script setup>
import { ref, onMounted, reactive } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"
import { notify } from '@/utils/toast'
import axios from 'axios'

// Reactive data
const statuses = ref([])
const loading = ref(false)
const editingStatus = ref(null)

// Form data
const form = reactive({
  name: '',
  description: '',
  color: '',
  country_id: null
})

// API base URL (adjust as needed)
const API_BASE = '/api/v1'

// Fetch all statuses
const fetchStatuses = async () => {
  loading.value = true
  try {
    const { data } = await axios.get(`${API_BASE}/statuses`)
    statuses.value = data
  } catch (error) {
    console.error('Error fetching statuses:', error)
    notify.error('Failed to load statuses')
  } finally {
    loading.value = false
  }
}

// Create new status
const createStatus = async () => {
  loading.value = true
  try {
    const { data: newStatus } = await axios.post(`${API_BASE}/statuses`, form)
    statuses.value.push(newStatus)
    resetForm()
    notify.success('Status created successfully!')
  } catch (error) {
    console.error('Error creating status:', error)
    notify.error('Failed to create status')
  } finally {
    loading.value = false
  }
}

// Update existing status
const updateStatus = async () => {
  loading.value = true
  try {
    const { data: updatedStatus } = await axios.put(`${API_BASE}/statuses/${editingStatus.value.id}`, form)
    const index = statuses.value.findIndex(s => s.id === editingStatus.value.id)
    if (index !== -1) {
      statuses.value[index] = updatedStatus
    }
    resetForm()
    notify.success('Status updated successfully!')
  } catch (error) {
    console.error('Error updating status:', error)
    notify.error('Failed to update status')
  } finally {
    loading.value = false
  }
}

// Delete status
const deleteStatus = async (id) => {
  if (!confirm('Are you sure you want to delete this status?')) return

  loading.value = true
  try {
    await axios.delete(`${API_BASE}/statuses/${id}`)
    statuses.value = statuses.value.filter(s => s.id !== id)
    notify.success('Status deleted successfully!')
  } catch (error) {
    console.error('Error deleting status:', error)
    notify.error('Failed to delete status')
  } finally {
    loading.value = false
  }
}

// Edit status
const editStatus = (status) => {
  editingStatus.value = status
  form.name = status.name
  form.description = status.description
  form.color = status.color
  form.country_id = status.country_id
}

// Cancel edit
const cancelEdit = () => {
  editingStatus.value = null
  resetForm()
}

// Reset form
const resetForm = () => {
  form.name = ''
  form.description = ''
  form.color = ''
  form.country_id = null
  editingStatus.value = null
}

// Submit form
const submitForm = () => {
  if (editingStatus.value) {
    updateStatus()
  } else {
    createStatus()
  }
}

// Initialize component
onMounted(() => {
  fetchStatuses()
})
</script>



<template>
  <AppLayout>
    <div class="container mx-auto px-4 py-8">
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Status Management</h1>
        <p class="text-gray-600">Manage your application statuses</p>
      </div>

      <!-- Add/Edit Form -->
      <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">
          {{ editingStatus ? 'Edit Status' : 'Add New Status' }}
        </h2>
        
        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                Name *
              </label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Enter status name"
                :disabled="loading"
              />
            </div>
            
            <div>
              <label for="color" class="block text-sm font-medium text-gray-700 mb-2">
                Color *
              </label>
              <select
                id="color"
                v-model="form.color"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                :disabled="loading"
              >
                <option value="">Select a color</option>
                <option value="red">Red</option>
                <option value="blue">Blue</option>
                <option value="green">Green</option>
                <option value="yellow">Yellow</option>
                <option value="purple">Purple</option>
                <option value="pink">Pink</option>
                <option value="gray">Gray</option>
                <option value="indigo">Indigo</option>
                <option value="orange">Orange</option>
              </select>
            </div>
          </div>

          <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
              Description
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Enter status description"
              :disabled="loading"
            ></textarea>
          </div>

          <div>
            <label for="country_id" class="block text-sm font-medium text-gray-700 mb-2">
              Country ID *
            </label>
            <input
              id="country_id"
              v-model.number="form.country_id"
              type="number"
              required
              min="1"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="Enter country ID"
              :disabled="loading"
            />
          </div>

          <div class="flex gap-2">
            <button
              type="submit"
              :disabled="loading"
              class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200 flex items-center"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ editingStatus ? 'Update Status' : 'Create Status' }}
            </button>
            
            <button
              v-if="editingStatus"
              type="button"
              @click="cancelEdit"
              :disabled="loading"
              class="bg-gray-500 hover:bg-gray-600 disabled:bg-gray-400 text-white px-6 py-2 rounded-md font-medium transition-colors duration-200"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>

      <!-- Status List -->
      <div class="bg-white rounded-lg shadow-md">
        <div class="p-6 border-b border-gray-200">
          <h2 class="text-xl font-semibold">Status List</h2>
        </div>

        <!-- Loading State -->
        <div v-if="loading && statuses.length === 0" class="p-8 text-center">
          <div class="inline-flex items-center">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading statuses...
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading && statuses.length === 0" class="p-8 text-center text-gray-500">
          <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p>No statuses found. Create your first status above.</p>
        </div>

        <!-- Status Table -->
        <div v-else class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Color</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="status in statuses" :key="status.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ status.id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ status.name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <span class="inline-flex items-center">
                    <span
                      class="w-3 h-3 rounded-full mr-2"
                      :class="`bg-${status.color}-500`"
                    ></span>
                    {{ status.color }}
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">{{ status.description }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ status.country_id }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ new Date(status.created_at).toLocaleDateString() }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                  <button
                    @click="editStatus(status)"
                    :disabled="loading"
                    class="text-blue-600 hover:text-blue-900 disabled:text-blue-300 transition-colors duration-200"
                  >
                    Edit
                  </button>
                  <button
                    @click="deleteStatus(status.id)"
                    :disabled="loading"
                    class="text-red-600 hover:text-red-900 disabled:text-red-300 transition-colors duration-200"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>