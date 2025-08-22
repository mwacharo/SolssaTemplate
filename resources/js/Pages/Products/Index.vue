<template>
  <AppLayout>
    <div class="container mx-auto p-6 bg-gray-50 min-h-screen">
      <!-- Header Section -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
          <h1 class="text-2xl font-bold text-gray-800">Products Management</h1>
          <button
            @click="openCreateModal"
            class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add a product
          </button>
        </div>

        <!-- Search and Filters -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
          <div class="lg:col-span-1">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search for a product (id, name, code)"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @input="handleSearch"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-red-600 mb-1">DATE</label>
            <input
              v-model="filters.date"
              type="date"
              placeholder="Filter by date"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-red-600 mb-1">SELLER</label>
            <select
              v-model="filters.vendor"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Filter by seller</option>
              <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">
                {{ vendor.name }}
              </option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-red-600 mb-1">CATEGORY</label>
            <select
              v-model="filters.category"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Category</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Filter Actions -->
        <div class="flex gap-4">
          <button
            @click="resetFilters"
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Reset
          </button>
          <button
            @click="applyFilters"
            class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition-colors flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
            </svg>
            Filter
          </button>
        </div>
      </div>

      <!-- Products Table -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Products</h2>
            <span class="text-sm text-gray-600">
              {{ pagination.from }}-{{ pagination.to }} / {{ pagination.total }}
            </span>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-blue-600 text-white">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">ID/SKU</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">SELLER</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">PRODUCT</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">CATEGORY</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">DATE</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">CURRENT QUANTITY</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">COMMITTED STOCK</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">DEFECTED QUANTITY</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">STOCK THRESHOLD</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">PRICE</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">PRODUCT PAGE</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">ACTIONS</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="bg-red-500 text-white px-2 py-1 rounded text-xs font-medium mb-1 inline-block w-fit">
                      pdt-{{ product.id }}
                    </span>
                    <span class="text-sm text-gray-600">{{ product.sku }}</span>
                  </div>
                </td>
                
                <td class="px-4 py-4 text-sm text-gray-900">
                  {{ product.vendor?.name || 'N/A' }}
                </td>
                
                <td class="px-4 py-4">
                  <div class="flex items-center gap-3">
                    <img
                      :src="product.image || '/api/placeholder/50/50'"
                      :alt="product.product_name"
                      class="w-12 h-12 rounded-lg object-cover"
                    />
                    <div>
                      <div class="font-medium text-gray-900">{{ product.product_name }}</div>
                      <div class="text-sm text-gray-500">{{ product.description }}</div>
                    </div>
                  </div>
                </td>
                
                <td class="px-4 py-4">
                  <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                    {{ product.category?.name || 'Uncategorized' }}
                  </span>
                </td>
                
                <td class="px-4 py-4 text-sm text-gray-900">
                  {{ formatDate(product.created_at) }}
                </td>
                
                <td class="px-4 py-4 text-center">
                  <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-sm font-medium">
                    Total{{ product.statistics?.current_quantity || 0 }}
                  </span>
                  <div class="text-xs text-gray-500 mt-1 flex items-center justify-center gap-1">
                    <img src="/api/placeholder/16/16" alt="Flag" class="w-4 h-4" />
                    <span>{{ product.statistics?.current_quantity || 0 }}</span>
                    <span class="bg-red-500 text-white px-1 rounded text-xs">Nairobi</span>
                  </div>
                </td>
                
                <td class="px-4 py-4 text-center">
                  <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-sm font-medium">
                    Total{{ product.statistics?.committed_stock || 0 }}
                  </span>
                  <div class="text-xs text-gray-500 mt-1 flex items-center justify-center gap-1">
                    <img src="/api/placeholder/16/16" alt="Flag" class="w-4 h-4" />
                    <span>{{ product.statistics?.committed_stock || 0 }}</span>
                    <span class="bg-red-500 text-white px-1 rounded text-xs">Nairobi</span>
                  </div>
                </td>
                
                <td class="px-4 py-4 text-center">
                  <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-sm font-medium">
                    Total{{ product.statistics?.defected_quantity || 0 }}
                  </span>
                  <div class="text-xs text-gray-500 mt-1 flex items-center justify-center gap-1">
                    <img src="/api/placeholder/16/16" alt="Flag" class="w-4 h-4" />
                    <span>{{ product.statistics?.defected_quantity || 0 }}</span>
                    <span class="bg-red-500 text-white px-1 rounded text-xs">Nairobi</span>
                  </div>
                </td>
                
                <td class="px-4 py-4 text-center text-sm font-medium">
                  {{ product.statistics?.stock_threshold || 0 }}
                </td>
                
                <td class="px-4 py-4 text-sm font-medium">
                  {{ formatPrice(product.prices?.[0]?.price || 0) }} <span class="text-xs text-gray-500">KES</span>
                </td>
                
                <td class="px-4 py-4">
                  <button class="text-blue-600 hover:text-blue-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                  </button>
                </td>
                
                <td class="px-4 py-4">
                  <div class="flex items-center gap-2">
                    <button
                      @click="editProduct(product)"
                      class="text-blue-600 hover:text-blue-800 p-1"
                      title="Edit"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                      </svg>
                    </button>
                    <button
                      @click="viewProduct(product)"
                      class="text-gray-600 hover:text-gray-800 p-1"
                      title="View"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                      </svg>
                    </button>
                    <button
                      @click="deleteProduct(product)"
                      class="text-red-600 hover:text-red-800 p-1"
                      title="Delete"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              
              <tr v-if="products.length === 0">
                <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                  <div class="flex flex-col items-center gap-2">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-4.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7"></path>
                    </svg>
                    <span>No products found</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="previousPage"
              :disabled="!pagination.prev_page_url"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              @click="nextPage"
              :disabled="!pagination.next_page_url"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium">{{ pagination.from }}</span>
                to
                <span class="font-medium">{{ pagination.to }}</span>
                of
                <span class="font-medium">{{ pagination.total }}</span>
                results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button
                  @click="previousPage"
                  :disabled="!pagination.prev_page_url"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span class="sr-only">Previous</span>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
                
                <button
                  v-for="page in visiblePages"
                  :key="page"
                  @click="goToPage(page)"
                  :class="[
                    'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                    page === pagination.current_page
                      ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                  ]"
                >
                  {{ page }}
                </button>
                
                <button
                  @click="nextPage"
                  :disabled="!pagination.next_page_url"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span class="sr-only">Next</span>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
          <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">
                {{ isEditing ? 'Edit Product' : 'Create New Product' }}
              </h3>
              <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            
            <form @submit.prevent="saveProduct" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">SKU *</label>
                  <input
                    v-model="form.sku"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                    placeholder="Enter SKU"
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                  <input
                    v-model="form.product_name"
                    type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                    placeholder="Enter product name"
                  />
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                  <select
                    v-model="form.category_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="">Select Category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Vendor *</label>
                  <select
                    v-model="form.vendor_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                  >
                    <option value="">Select Vendor</option>
                    <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">
                      {{ vendor.name }}
                    </option>
                  </select>
                </div>
                
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Initial Stock Quantity</label>
                  <input
                    v-model="form.initial_quantity"
                    type="number"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter initial stock quantity"
                  />
                </div>
                
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Stock Threshold</label>
                  <input
                    v-model="form.stock_threshold"
                    type="number"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter minimum stock threshold"
                  />
                </div>
                
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Price (KES)</label>
                  <input
                    v-model="form.price"
                    type="number"
                    min="0"
                    step="0.01"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter product price"
                  />
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea
                  v-model="form.description"
                  rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter product description (optional)"
                ></textarea>
              </div>
              
              <div class="flex justify-end space-x-3 mt-6">
                <button
                  type="button"
                  @click="closeModal"
                  class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                >
                  {{ isEditing ? 'Update' : 'Create' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"

// Reactive data
const products = ref([])
const vendors = ref([])
const categories = ref([])
const searchQuery = ref('')
const showModal = ref(false)
const isEditing = ref(false)
const currentProduct = ref(null)

// Filters
const filters = ref({
  date: '',
  vendor: '',
  category: ''
})

// Pagination
const pagination = ref({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
  from: 1,
  to: 15,
  prev_page_url: null,
  next_page_url: null
})

// Form data
const form = ref({
  sku: '',
  product_name: '',
  description: '',
  category_id: '',
  vendor_id: '',
  initial_quantity: 0,
  stock_threshold: 0,
  price: 0
})

// Computed properties
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }
  
  return pages
})

// Methods
const fetchProducts = async (page = 1) => {
  try {
    // Simulate API call - replace with actual API endpoint
    const response = await fetch(`/api/products?page=${page}&search=${searchQuery.value}&vendor=${filters.value.vendor}&category=${filters.value.category}&date=${filters.value.date}`)
    const data = await response.json()
    
    products.value = data.data
    pagination.value = {
      current_page: data.current_page,
      per_page: data.per_page,
      total: data.total,
      last_page: data.last_page,
      from: data.from,
      to: data.to,
      prev_page_url: data.prev_page_url,
      next_page_url: data.next_page_url
    }
  } catch (error) {
    console.error('Error fetching products:', error)
    // Mock data for demo
    mockData()
  }
}

const mockData = () => {
  products.value = [
    {
      id: 8512,
      sku: 'pdt-8512-Nai',
      product_name: 'OBD Scanner',
      description: 'Advanced diagnostic tool',
      image: '/api/placeholder/50/50',
      created_at: '2025-08-19',
      vendor: { name: 'testmerchant' },
      category: { name: 'Car Products' },
      statistics: {
        current_quantity: 100,
        committed_stock: 0,
        defected_quantity: 0,
        stock_threshold: 100
      },
      prices: [{ price: 0 }]
    },
    {
      id: 6670,
      sku: 'pdt-6670-Nai',
      product_name: 'Mk-oil',
      description: 'Premium engine oil',
      image: '/api/placeholder/50/50',
      created_at: '2025-08-19',
      vendor: { name: 'testmerchant' },
      category: { name: 'Cosmetic' },
      statistics: {
        current_quantity: 7,
        committed_stock: 0,
        defected_quantity: 0,
        stock_threshold: 10
      },
      prices: [{ price: 0 }]
    },
    {
      id: 7220,
      sku: 'pdt-7220-Nai',
      product_name: 'Strong man',
      description: 'Health supplement',
      image: '/api/placeholder/50/50',
      created_at: '2025-08-18',
      vendor: { name: 'testmerchant' },
      category: { name: 'Cosmetic' },
      statistics: {
        current_quantity: 100,
        committed_stock: 0,
        defected_quantity: 20,
        stock_threshold: 10
      },
      prices: [{ price: 3500 }]
    }
  ]
  
  vendors.value = [
    { id: 1, name: 'testmerchant' },
    { id: 2, name: 'vendor2' },
    { id: 3, name: 'vendor3' }
  ]
  
  categories.value = [
    { id: 1, name: 'Car Products' },
    { id: 2, name: 'Cosmetic' },
    { id: 3, name: 'Electronics' }
  ]
  
  pagination.value = {
    current_page: 1,
    per_page: 15,
    total: 34456,
    last_page: 2297,
    from: 1,
    to: 15,
    prev_page_url: null,
    next_page_url: '/api/products?page=2'
  }
}

const handleSearch = () => {
  // Debounced search - implement debouncing in real application
  fetchProducts(1)
}

const applyFilters = () => {
  fetchProducts(1)
}

const resetFilters = () => {
  filters.value = {
    date: '',
    vendor: '',
    category: ''
  }
  searchQuery.value = ''
  fetchProducts(1)
}

const openCreateModal = () => {
  isEditing.value = false
  form.value = {
    sku: '',
    product_name: '',
    description: '',
    category_id: '',
    vendor_id: '',
    initial_quantity: 0,
    stock_threshold: 0,
    price: 0
  }
  showModal.value = true
}

const editProduct = (product) => {
  isEditing.value = true
  currentProduct.value = product
  form.value = {
    sku: product.sku,
    product_name: product.product_name,
    description: product.description,
    category_id: product.category_id || product.category?.id,
    vendor_id: product.vendor_id || product.vendor?.id,
    initial_quantity: product.statistics?.current_quantity || 0,
    stock_threshold: product.statistics?.stock_threshold || 0,
    price: product.prices?.[0]?.price || 0
  }
  showModal.value = true
}

const viewProduct = (product) => {
  // Navigate to product detail page or open view modal
  console.log('Viewing product:', product)
  // You can implement navigation here: router.push(`/products/${product.id}`)
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  currentProduct.value = null
  form.value = {
    sku: '',
    product_name: '',
    description: '',
    category_id: '',
    vendor_id: '',
    initial_quantity: 0,
    stock_threshold: 0,
    price: 0
  }
}

const saveProduct = async () => {
  try {
    const url = isEditing.value 
      ? `/api/products/${currentProduct.value.id}` 
      : '/api/products'
    
    const method = isEditing.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    })
    
    if (response.ok) {
      closeModal()
      fetchProducts(pagination.value.current_page)
      // Show success message
      console.log(isEditing.value ? 'Product updated successfully' : 'Product created successfully')
    } else {
      console.error('Error saving product')
    }
  } catch (error) {
    console.error('Error saving product:', error)
  }
}

const deleteProduct = async (product) => {
  if (confirm(`Are you sure you want to delete "${product.product_name}"?`)) {
    try {
      const response = await fetch(`/api/products/${product.id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json'
        }
      })
      
      if (response.ok) {
        fetchProducts(pagination.value.current_page)
        console.log('Product deleted successfully')
      } else {
        console.error('Error deleting product')
      }
    } catch (error) {
      console.error('Error deleting product:', error)
    }
  }
}

const previousPage = () => {
  if (pagination.value.prev_page_url) {
    fetchProducts(pagination.value.current_page - 1)
  }
}

const nextPage = () => {
  if (pagination.value.next_page_url) {
    fetchProducts(pagination.value.current_page + 1)
  }
}

const goToPage = (page) => {
  if (page !== pagination.value.current_page) {
    fetchProducts(page)
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-GB')
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-KE').format(price || 0)
}

// Lifecycle
onMounted(() => {
  fetchProducts()
})

</script>