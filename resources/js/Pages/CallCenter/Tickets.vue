<template>
  <AppLayout title="Ticket Management Dashboard">
    <div class="p-6 space-y-6">
      <!-- Dashboard Stats -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Total Tickets</p>
              <p class="text-2xl font-bold text-gray-900">{{ ticketStats.total }}</p>
            </div>
            <div class="p-3 rounded-full bg-blue-100">
              <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Open Tickets</p>
              <p class="text-2xl font-bold text-gray-900">{{ ticketStats.open }}</p>
            </div>
            <div class="p-3 rounded-full bg-yellow-100">
              <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">In Progress</p>
              <p class="text-2xl font-bold text-gray-900">{{ ticketStats.inProgress }}</p>
            </div>
            <div class="p-3 rounded-full bg-orange-100">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-gray-600">Resolved</p>
              <p class="text-2xl font-bold text-gray-900">{{ ticketStats.resolved }}</p>
            </div>
            <div class="p-3 rounded-full bg-green-100">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="bg-white rounded-lg shadow p-6">
        <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
          <div class="flex flex-1 gap-4">
            <div class="flex-1">
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search tickets by name, number, or phone..."
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
            </div>
            
            <select 
              v-model="currentFilter"
              class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="all">All Status</option>
              <option value="open">Open</option>
              <option value="in-progress">In Progress</option>
              <option value="resolved">Resolved</option>
              <option value="closed">Closed</option>
            </select>
          </div>
          
          <button 
            @click="showCreateModal = true"
            class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors"
          >
            New Ticket
          </button>
        </div>
      </div>

      <!-- Tickets Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">Recent Tickets</h2>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Ticket
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Client Info
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Subject
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Priority
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Assigned To
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="ticket in filteredTickets" :key="ticket.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ ticket.ticketNumber }}</div>
                    <div class="text-sm text-gray-500">{{ formatDate(ticket.createdAt) }}</div>
                  </div>
                </td>
                
                <td class="px-6 py-4 whitespace-nowrap">
                  <div>
                    <div class="text-sm font-medium text-gray-900">{{ ticket.clientName }}</div>
                    <div class="text-sm text-gray-500">{{ ticket.clientPhone }}</div>
                    <div class="text-sm text-gray-500">Call: {{ ticket.callDuration }}</div>
                  </div>
                </td>
                
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">{{ ticket.subject }}</div>
                  <div class="text-sm text-gray-500 truncate max-w-xs">{{ ticket.description }}</div>
                </td>
                
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getPriorityClass(ticket.priority)" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ ticket.priority.toUpperCase() }}
                  </span>
                </td>
                
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusClass(ticket.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                    {{ formatStatus(ticket.status) }}
                  </span>
                </td>
                
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ ticket.assignedTo }}
                </td>
                
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                  <button 
                    @click="viewTicket(ticket)"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    View
                  </button>
                  <button 
                    @click="editTicket(ticket)"
                    class="text-green-600 hover:text-green-900"
                  >
                    Edit
                  </button>
                </td>
              </tr>
              
              <tr v-if="filteredTickets.length === 0">
                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                  No tickets found matching your criteria
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Create/Edit Ticket Modal -->
      <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ showCreateModal ? 'Create New Ticket' : 'Edit Ticket' }}
            </h3>
          </div>
          
          <form @submit.prevent="submitForm" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Client Name *</label>
                <input
                  v-model="formData.clientName"
                  type="text"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                <input
                  v-model="formData.clientPhone"
                  type="tel"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input
                  v-model="formData.clientEmail"
                  type="email"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Call Duration</label>
                <input
                  v-model="formData.callDuration"
                  type="text"
                  placeholder="e.g., 5:30"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
              <input
                v-model="formData.subject"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description *</label>
              <textarea
                v-model="formData.description"
                rows="4"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              ></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select
                  v-model="formData.priority"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="low">Low</option>
                  <option value="medium">Medium</option>
                  <option value="high">High</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select
                  v-model="formData.category"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="technical">Technical</option>
                  <option value="billing">Billing</option>
                  <option value="support">Support</option>
                  <option value="general">General</option>
                </select>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Assigned To</label>
                <input
                  v-model="formData.assignedTo"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
              </div>
            </div>
            
            <div class="flex justify-end space-x-3 pt-4">
              <button 
                type="button"
                @click="cancelForm"
                class="px-4 py-2 text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors"
              >
                Cancel
              </button>
              <button 
                type="submit"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
              >
                {{ showCreateModal ? 'Create Ticket' : 'Update Ticket' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useTicketStore } from '@/stores/ticketStore'
import { defineStore, storeToRefs } from 'pinia'

import AppLayout from '@/Layouts/AppLayout.vue'
import { notify } from '@/utils/toast'

const ticketStore = useTicketStore()

// Reactive data
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingTicketId = ref(null)

const formData = ref({
  clientName: '',
  clientPhone: '',
  clientEmail: '',
  subject: '',
  description: '',
  priority: 'medium',
  category: 'general',
  assignedTo: '',
  callDuration: ''
})

// Computed properties
const { filteredTickets, ticketStats, currentFilter, searchQuery } = storeToRefs(ticketStore)

// Methods
const formatDate = (date) => {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(date))
}

const formatStatus = (status) => {
  return status.split('-').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const getPriorityClass = (priority) => {
  const classes = {
    high: 'bg-red-100 text-red-800',
    medium: 'bg-yellow-100 text-yellow-800',
    low: 'bg-green-100 text-green-800'
  }
  return classes[priority] || 'bg-gray-100 text-gray-800'
}

const getStatusClass = (status) => {
  const classes = {
    open: 'bg-yellow-100 text-yellow-800',
    'in-progress': 'bg-orange-100 text-orange-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const viewTicket = (ticket) => {
  // Navigate to ticket detail view
  console.log('Viewing ticket:', ticket.id)
  notify(`Viewing ticket: ${ticket.ticketNumber}`, 'info')
}

const editTicket = (ticket) => {
  editingTicketId.value = ticket.id
  formData.value = { ...ticket }
  showEditModal.value = true
}

const submitForm = () => {
  try {
    if (showCreateModal.value) {
      ticketStore.addTicket(formData.value)
      notify('Ticket created successfully!', 'success')
    } else if (showEditModal.value) {
      ticketStore.updateTicket(editingTicketId.value, formData.value)
      notify('Ticket updated successfully!', 'success')
    }
    
    cancelForm()
  } catch (error) {
    notify('Error processing ticket', 'error')
    console.error(error)
  }
}

const cancelForm = () => {
  showCreateModal.value = false
  showEditModal.value = false
  editingTicketId.value = null
  resetForm()
}

const resetForm = () => {
  formData.value = {
    clientName: '',
    clientPhone: '',
    clientEmail: '',
    subject: '',
    description: '',
    priority: 'medium',
    category: 'general',
    assignedTo: '',
    callDuration: ''
  }
}

// Watch for search and filter changes
watch(searchQuery, (newVal) => {
  ticketStore.setSearch(newVal)
})

watch(currentFilter, (newVal) => {
  ticketStore.setFilter(newVal)
})

onMounted(() => {
  // Any initialization logic
  console.log('Dashboard mounted')
})
</script>