<template>
  <AppLayout title="Client Management Dashboard">
    <div class="p-6 bg-gray-50 min-h-screen">
      <!-- Loading Overlay -->
      <div v-if="clientStore.loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
          <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
          <span>Loading...</span>
        </div>
      </div>

      <!-- Error Alert -->
      <div v-if="clientStore.error" class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
        <strong class="font-bold">Error: </strong>
        <span class="block sm:inline">{{ clientStore.error }}</span>
        <button @click="clientStore.error = null" class="absolute top-0 bottom-0 right-0 px-4 py-3">
          <svg class="fill-current h-6 w-6 text-red-500" role="button" viewBox="0 0 20 20">
            <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
          </svg>
        </button>
      </div>

      <!-- Header Section -->
      <div class="mb-8">
        <div class="flex justify-between items-center mb-6">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Client Management</h1>
            <p class="text-gray-600">Manage vendors, clients, and riders</p>
          </div>
          <div class="flex gap-3">
            <button
              @click="refreshData"
              class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
              </svg>
              Refresh
            </button>
            <button
              @click="exportData"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Export Data
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
                <p class="text-2xl font-semibold text-gray-900">{{ clientStore.getTotalClients }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-6 rounded-xl shadow-sm border">
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Active Riders</p>
                <p class="text-2xl font-semibold text-gray-900">{{ clientStore.getActiveRiders.length }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white p-6 rounded-xl shadow-sm border">
            <div class="flex items-center">
              <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Verified Vendors</p>
                <p class="text-2xl font-semibold text-gray-900">{{ verifiedVendorsCount }}</p>
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
            <div class="space-y-4 max-h-96 overflow-y-auto">
              <div
                v-for="vendor in clientStore.vendors"
                :key="vendor.id"
                class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                @click="selectVendor(vendor)"
                :class="{ 'bg-blue-50 border-blue-200': selectedVendor?.id === vendor.id }"
              >
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <h3 class="font-semibold text-gray-900">{{ vendor.name }}</h3>
                    <p class="text-sm text-gray-600">{{ vendor.company_name }}</p>
                    <p class="text-sm text-gray-600">{{ vendor.email }}</p>
                    <p class="text-sm text-gray-500">{{ vendor.phone }}</p>
                    <div class="mt-2 flex items-center space-x-2">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="vendor.status === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ vendor.status === 1 ? 'Active' : 'Inactive' }}
                      </span>
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ vendor.onboarding_stage }}
                      </span>
                      <span class="text-xs text-gray-500">
                        {{ vendor.clients ? vendor.clients.length : 0 }} clients
                      </span>
                    </div>
                    <div class="mt-1 text-xs text-gray-500">
                      {{ vendor.city }}, {{ vendor.state }}, {{ vendor.country }}
                    </div>
                    <div v-if="vendor.rating" class="mt-1 flex items-center">
                      <span class="text-xs text-gray-500">Rating: {{ vendor.rating }}/5</span>
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
            <div v-if="selectedVendor" class="space-y-4 max-h-96 overflow-y-auto">
              <div
                v-for="client in selectedVendor.clients"
                :key="client.id"
                class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
              >
                <div class="flex justify-between items-start">
                  <div class="flex-1">
                    <h3 class="font-semibold text-gray-900">{{ client.name }}</h3>
                    <p class="text-sm text-gray-600">{{ client.email || 'No email' }}</p>
                    <p class="text-sm text-gray-500">{{ client.phone_number }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ client.address || 'No address' }}</p>
                    <div class="mt-2">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                            :class="client.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                        {{ client.status }}
                      </span>
                      <span class="ml-2 text-xs text-gray-500">
                        {{ client.city || 'Unknown city' }}
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
              
              <div v-if="!selectedVendor.clients || selectedVendor.clients.length === 0" class="text-center py-8 text-gray-500">
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

      <!-- Riders Section -->
      <div class="mt-8 bg-white rounded-xl shadow-sm border">
        <div class="p-6 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-900">Riders</h2>
            <button
              @click="showAddRiderModal = true"
              class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm transition-colors"
            >
              Add Rider
            </button>
          </div>
        </div>
        
        <div class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
              v-for="rider in clientStore.riders"
              :key="rider.id"
              class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
            >
              <div class="flex justify-between items-start">
                <div class="flex-1">
                  <h3 class="font-semibold text-gray-900">{{ rider.name }}</h3>
                  <p class="text-sm text-gray-600">{{ rider.email }}</p>
                  <p class="text-sm text-gray-500">{{ rider.phone }}</p>
                  <div class="mt-2 space-y-1">
                    <p class="text-xs text-gray-500">Vehicle: {{ rider.vehicle_number }}</p>
                    <p class="text-xs text-gray-500">License: {{ rider.license_number }}</p>
                    <p class="text-xs text-gray-500">{{ rider.city }}, {{ rider.state }}</p>
                  </div>
                  <div class="mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="rider.status === 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                      {{ rider.status === 1 ? 'Active' : 'Inactive' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            
            <div v-if="clientStore.riders.length === 0" class="col-span-full text-center py-8 text-gray-500">
              No riders found. Add your first rider to get started.
            </div>
          </div>
        </div>
      </div>

      <!-- Add Vendor Modal -->
      <div v-if="showAddVendorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
          <h3 class="text-lg font-semibold mb-4">Add New Vendor</h3>
          <form @submit.prevent="addVendor">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input v-model="newVendor.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                <input v-model="newVendor.company_name" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input v-model="newVendor.email" type="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                <input v-model="newVendor.phone" type="tel" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea v-model="newVendor.address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                  <input v-model="newVendor.city" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                  <input v-model="newVendor.state" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                <input v-model="newVendor.country" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Type</label>
                <select v-model="newVendor.business_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                  <option value="Corporation">Corporation</option>
                  <option value="LLC">LLC</option>
                  <option value="Partnership">Partnership</option>
                  <option value="Sole Proprietorship">Sole Proprietorship</option>
                </select>
              </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
              <button type="button" @click="showAddVendorModal = false" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
              <button type="submit" :disabled="clientStore.loading" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                {{ clientStore.loading ? 'Adding...' : 'Add Vendor' }}
              </button>
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input v-model="newClient.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input v-model="newClient.email" type="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                <input v-model="newClient.phone" type="tel" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea v-model="newClient.address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                <input v-model="newClient.city" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-green-500" />
              </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
              <button type="button" @click="showAddClientModal = false" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
              <button type="submit" :disabled="clientStore.loading" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50">
                {{ clientStore.loading ? 'Adding...' : 'Add Client' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Add Rider Modal -->
      <div v-if="showAddRiderModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
          <h3 class="text-lg font-semibold mb-4">Add New Rider</h3>
          <form @submit.prevent="addRider">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                <input v-model="newRider.name" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input v-model="newRider.email" type="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                <input v-model="newRider.phone" type="tel" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                <textarea v-model="newRider.address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                  <input v-model="newRider.city" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                  <input v-model="newRider.state" type="text" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                </div>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Number *</label>
                <input v-model="newRider.vehicle_number" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">License Number *</label>
                <input v-model="newRider.license_number" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
              </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
              <button type="button" @click="showAddRiderModal = false" class="px-4 py-2 text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</button>
              <button type="submit" :disabled="clientStore.loading" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                {{ clientStore.loading ? 'Adding...' : 'Add Rider' }}
              </button>
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

const clientStore = useClientStore()

// Reactive data
const selectedVendor = ref(null)
const showAddVendorModal = ref(false)
const showAddClientModal = ref(false)
const showAddRiderModal = ref(false)

const newVendor = ref({
  name: '',
  company_name: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  country: '',
  business_type: 'Corporation'
})

const newClient = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  city: ''
})

const newRider = ref({
  name: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  vehicle_number: '',
  license_number: ''
})

// Computed properties
const verifiedVendorsCount = computed(() => {
  return clientStore.getVendorsByOnboardingStage('verified').length
})

// Methods
const selectVendor = (vendor) => {
  selectedVendor.value = vendor
}

const addVendor = async () => {
  try {
    await clientStore.addVendor(newVendor.value)
    newVendor.value = {
      name: '',
      company_name: '',
      email: '',
      phone: '',
      address: '',
      city: '',
      state: '',
      country: '',
      business_type: 'Corporation'
    }
    showAddVendorModal.value = false
    showNotification('Vendor added successfully', 'success')
  } catch (error) {
    showNotification('Failed to add vendor', 'error')
  }
}

const addClient = async () => {
  if (!selectedVendor.value) {
    showNotification('Please select a vendor first', 'error')
    return
  }
  
  try {
    await clientStore.addClient(selectedVendor.value.id, newClient.value)
    newClient.value = {
      name: '',
      email: '',
      phone: '',
      address: '',
      city: ''
    }
    showAddClientModal.value = false
    showNotification('Client added successfully', 'success')
  } catch (error) {
    showNotification('Failed to add client', 'error')
  }
}

const addRider = async () => {
  try {
    await clientStore.addRider(newRider.value)
    newRider.value = {
      name: '',
      email: '',
      phone: '',
      address: '',
      city: '',
      state: '',
      vehicle_number: '',
      license_number: ''
    }
    showAddRiderModal.value = false
    showNotification('Rider added successfully', 'success')
  } catch (error) {
    showNotification('Failed to add rider', 'error')
  }
}

const deleteVendor = async (vendorId) => {
  if (!confirm('Are you sure you want to delete this vendor? This will also delete all associated clients.')) return
  
  try {
    await clientStore.deleteVendor(vendorId)
    if (selectedVendor.value?.id === vendorId) {
      selectedVendor.value = null
    }
    showNotification('Vendor deleted successfully', 'success')
  } catch (error) {
    showNotification('Failed to delete vendor', 'error')
  }
}

const deleteClient = async (vendorId, clientId) => {
  if (!confirm('Are you sure you want to delete this client?')) return
  
  try {
    await clientStore.deleteClient(vendorId, clientId)
    showNotification('Client deleted successfully', 'success')
  } catch (error) {
    showNotification('Failed to delete client', 'error')
  }
}

const refreshData = async () => {
  try {
    await clientStore.initializeData()
    showNotification('Data refreshed successfully', 'success')
  } catch (error) {
    showNotification('Failed to refresh data', 'error')
  }
}

const exportData = () => {
  const data = {
    vendors: clientStore.vendors,
    riders: clientStore.riders,
    exportDate: new Date().toISOString(),
    totalVendors: clientStore.vendors.length,
    totalClients: clientStore.getTotalClients,
    totalRiders: clientStore.riders.length
  }
  
  const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `courier-management-data-${new Date().toISOString().split('T')[0]}.json`
  link.click()
  URL.revokeObjectURL(url)
  
  showNotification('Data exported successfully', 'success')
}

// Simple notification system
const showNotification = (message, type = 'info') => {
  // You can replace this with your preferred notification library
  console.log(`${type.toUpperCase()}: ${message}`)
  
  // Simple toast notification
  const toast = document.createElement('div')
  toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg text-white ${
    type === 'success' ? 'bg-green-500' : 
    type === 'error' ? 'bg-red-500' : 'bg-blue-500'
  }`
  toast.textContent = message
  document.body.appendChild(toast)
  
  setTimeout(() => {
    document.body.removeChild(toast)
  }, 3000)
}

// Initialize data on component mount
onMounted(async () => {
  await clientStore.initializeData()
})
</script>