<template>
  <AppLayout title="Client Management Dashboard">
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- Header Section -->
      <div class="mb-8">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Client Management</h1>
            <p class="text-gray-600">Manage vendors and their clients</p>
          </div>
          <div class="flex gap-3">
            <button
              @click="exportData"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export Data
            </button>
            <input
              ref="fileInput"
              type="file"
              accept=".json,.csv"
              @change="importData"
              class="hidden"
            />
            <button
              @click="$refs.fileInput.click()"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
              </svg>
              Import Data
            </button>
          </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white p-6 rounded-xl shadow-sm border">
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Vendors</p>
                <p class="text-2xl font-semibold text-gray-900">{{ clientStore.vendors.length }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-6 rounded-xl shadow-sm border">
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-green-100 text-green-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Clients</p>
                <p class="text-2xl font-semibold text-gray-900">{{ totalClients }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-6 rounded-xl shadow-sm border">
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Active Orders</p>
                <p class="text-2xl font-semibold text-gray-900">{{ activeOrders }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-6 rounded-xl shadow-sm border">
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Monthly Revenue</p>
                <p class="text-2xl font-semibold text-gray-900">${{ monthlyRevenue.toLocaleString() }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Vendors and Clients Section -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Vendors Section -->
        <div class="bg-white rounded-xl shadow-sm border">
          <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <h2 class="text-xl font-semibold text-gray-900">Vendors</h2>
              <button
                @click="showAddVendorModal = true"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors"
              >
                Add Vendor
              </button>
            </div>
          </div>
          
          <div class="p-6">
            <div class="space-y-4">
              <div
                v-for="vendor in clientStore.vendors"
                :key="vendor.id"
                class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                @click="selectVendor(vendor)"
                :class="{ 'bg-blue-50 border-blue-200': selectedVendor?.id === vendor.id }"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <h3 class="font-semibold text-gray-900">{{ vendor.name }}</h3>
                    <p class="text-sm text-gray-600">{{ vendor.email }}</p>
                    <p class="text-sm text-gray-500">{{ vendor.phone }}</p>
                    <div class="mt-2">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="vendor.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ vendor.status }}
                      </span>
                      <span class="ml-2 text-xs text-gray-500">
                        {{ vendor.clients.length }} clients
                      </span>
                    </div>
                  </div>
                  <button
                    @click.stop="deleteVendor(vendor.id)"
                    class="text-red-500 hover:text-red-700 p-1"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
              
              <div v-if="clientStore.vendors.length === 0" class="text-center py-8 text-gray-500">
                No vendors found. Add your first vendor to get started.
              </div>
            </div>
          </div>
        </div>

        <!-- Clients Section -->
        <div class="bg-white rounded-xl shadow-sm border">
          <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <h2 class="text-xl font-semibold text-gray-900">
                {{ selectedVendor ? `${selectedVendor.name}'s Clients` : 'Select a Vendor' }}
              </h2>
              <button
                v-if="selectedVendor"
                @click="showAddClientModal = true"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-colors"
              >
                Add Client
              </button>
            </div>
          </div>
          
          <div class="p-6">
            <div v-if="selectedVendor" class="space-y-4">
              <div
                v-for="client in selectedVendor.clients"
                :key="client.id"
                class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <h3 class="font-semibold text-gray-900">{{ client.name }}</h3>
                    <p class="text-sm text-gray-600">{{ client.email }}</p>
                    <p class="text-sm text-gray-500">{{ client.phone }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ client.address }}</p>
                    <div class="mt-2">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="client.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ client.status }}
                      </span>
                      <span class="ml-2 text-xs text-gray-500">
                        {{ client.totalOrders }} orders
                      </span>
                    </div>
                  </div>
                  <button
                    @click="deleteClient(selectedVendor.id, client.id)"
                    class="text-red-500 hover:text-red-700 p-1"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </div>
              
              <div v-if="selectedVendor.clients.length === 0" class="text-center py-8 text-gray-500">
                No clients found for this vendor. Add the first client to get started.
              </div>
            </div>
            
            <div v-else class="text-center py-12 text-gray-500">
              <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
              </svg>
              Select a vendor to view their clients
            </div>
          </div>
        </div>
      </div>

      <!-- Add Vendor Modal -->
      <div v-if="showAddVendorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
          <h3 class="text-lg font-semibold mb-4">Add New Vendor</h3>
          <form @submit.prevent="addVendor">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input v-model="newVendor.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input v-model="newVendor.email" type="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input v-model="newVendor.phone" type="tel" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea v-model="newVendor.address" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
              </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
              <button type="button" @click="showAddVendorModal = false" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Add Vendor</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add Client Modal -->
      <div v-if="showAddClientModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
          <h3 class="text-lg font-semibold mb-4">Add New Client</h3>
          <form @submit.prevent="addClient">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input v-model="newClient.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input v-model="newClient.email" type="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input v-model="newClient.phone" type="tel" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea v-model="newClient.address" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
              </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
              <button type="button" @click="showAddClientModal = false" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Add Client</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useClientStore } from '@/stores/clientStore'
import AppLayout from '@/Layouts/AppLayout.vue'
import { notify } from '@/utils/toast'

const clientStore = useClientStore()

// Reactive data
const selectedVendor = ref(null)
const showAddVendorModal = ref(false)
const showAddClientModal = ref(false)
const fileInput = ref(null)

const newVendor = ref({
  name: '',
  email: '',
  phone: '',
  address: ''
})

const newClient = ref({
  name: '',
  email: '',
  phone: '',
  address: ''
})

// Computed properties
const totalClients = computed(() => {
  return clientStore.vendors.reduce((total, vendor) => total + vendor.clients.length, 0)
})

const activeOrders = computed(() => {
  return clientStore.vendors.reduce((total, vendor) => {
    return total + vendor.clients.reduce((clientTotal, client) => clientTotal + client.totalOrders, 0)
  }, 0)
})

const monthlyRevenue = computed(() => {
  return clientStore.vendors.reduce((total, vendor) => {
    return total + vendor.clients.reduce((clientTotal, client) => clientTotal + (client.totalOrders * 25), 0)
  }, 0)
})

// Methods
const selectVendor = (vendor) => {
  selectedVendor.value = vendor
}

const addVendor = () => {
  clientStore.addVendor({
    ...newVendor.value,
    id: Date.now(),
    status: 'active',
    clients: []
  })
  
  newVendor.value = { name: '', email: '', phone: '', address: '' }
  showAddVendorModal.value = false
  notify('Vendor added successfully', 'success')
}

const addClient = () => {
  if (!selectedVendor.value) {
    notify('Please select a vendor first', 'error')
    return
  }
  
  clientStore.addClient(selectedVendor.value.id, {
    ...newClient.value,
    id: Date.now(),
    status: 'active',
    totalOrders: Math.floor(Math.random() * 50)
  })
  
  newClient.value = { name: '', email: '', phone: '', address: '' }
  showAddClientModal.value = false
  notify('Client added successfully', 'success')
}

const deleteVendor = (vendorId) => {
  if (confirm('Are you sure you want to delete this vendor?')) {
    clientStore.deleteVendor(vendorId)
    if (selectedVendor.value?.id === vendorId) {
      selectedVendor.value = null
    }
    notify('Vendor deleted successfully', 'success')
  }
}

const deleteClient = (vendorId, clientId) => {
  if (confirm('Are you sure you want to delete this client?')) {
    clientStore.deleteClient(vendorId, clientId)
    notify('Client deleted successfully', 'success')
  }
}

const exportData = () => {
  const data = {
    vendors: clientStore.vendors,
    exportDate: new Date().toISOString(),
    totalVendors: clientStore.vendors.length,
    totalClients: totalClients.value
  }
  
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `courier-data-${new Date().toISOString().split('T')[0]}.json`
  link.click()
  URL.revokeObjectURL(url)
  
  notify('Data exported successfully', 'success')
}

const importData = (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  const reader = new FileReader()
  reader.onload = (e) => {
    try {
      const data = JSON.parse(e.target.result)
      if (data.vendors && Array.isArray(data.vendors)) {
        clientStore.importVendors(data.vendors)
        notify('Data imported successfully', 'success')
        selectedVendor.value = null
      } else {
        notify('Invalid file format', 'error')
      }
    } catch (error) {
      notify('Error parsing file', 'error')
    }
  }
  reader.readAsText(file)
  event.target.value = ''
}

// Initialize store on mount
onMounted(() => {
  clientStore.initializeData()
})
</script>