<template>
  <AppLayout>
    <div class="min-h-screen bg-gray-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Product Statistics</h1>
          <p class="text-gray-600 mt-2">Complete overview of product performance and revenue</p>
        </div>

        <!-- Summary Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <StatCard
            title="Total Revenue"
            :value="`$${summaryStats.totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2 })}`"
            :change="12.5"
            icon="DollarSign"
            color="bg-green-500"
          />
          <StatCard
            title="Total Products"
            :value="summaryStats.totalProducts"
            :change="3.2"
            icon="Package"
            color="bg-blue-500"
          />
          <StatCard
            title="Total Orders"
            :value="summaryStats.totalOrders"
            :change="8.7"
            icon="ShoppingCart"
            color="bg-purple-500"
          />
          <StatCard
            title="Delivered Orders"
            :value="summaryStats.totalDelivered"
            :change="-2.1"
            icon="Truck"
            color="bg-orange-500"
          />
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
          <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            <!-- Search -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Search Products
              </label>
              <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search by ID, name, or seller"
                  v-model="searchTerm"
                  class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
            </div>

            <!-- Date Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                DATE
              </label>
              <input
                type="date"
                v-model="dateFilter"
                class="px-4 py-2 w-full border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>

            <!-- Seller Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                SELLER
              </label>
              <div class="relative">
                <select
                  v-model="selectedSeller"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none"
                >
                  <option value="">Filter by seller</option>
                  <option value="testmerchant">testmerchant</option>
                  <option value="CHEAPIST">CHEAPIST</option>
                </select>
                <ChevronDown class="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              </div>
            </div>

            <!-- Category Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                CATEGORY
              </label>
              <div class="relative">
                <select
                  v-model="selectedCategory"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none"
                >
                  <option value="">All categories</option>
                  <option value="Health & Fitness">Health & Fitness</option>
                  <option value="General">General</option>
                  <option value="Electronics">Electronics</option>
                </select>
                <ChevronDown class="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              </div>
            </div>

            <!-- Sort Filter -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                SORT BY
              </label>
              <div class="relative">
                <select
                  v-model="sortBy"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none"
                >
                  <option value="orders_desc">Orders (High to Low)</option>
                  <option value="orders_asc">Orders (Low to High)</option>
                  <option value="revenue_desc">Revenue (High to Low)</option>
                  <option value="revenue_asc">Revenue (Low to High)</option>
                  <option value="delivery_rate_desc">Delivery Rate (High to Low)</option>
                  <option value="delivery_rate_asc">Delivery Rate (Low to High)</option>
                </select>
                <ChevronDown class="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
              <button
                @click="resetFilters"
                class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center gap-2"
              >
                <RotateCcw class="h-4 w-4" />
                Reset
              </button>
              <button class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition-colors flex items-center gap-2">
                <Filter class="h-4 w-4" />
                Filter
              </button>
            </div>
          </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">
              Products Statistics
              <span class="text-sm font-normal text-gray-500 ml-2">
                1 - {{ Math.min(15, filteredProducts.length) }} / {{ filteredProducts.length }}
              </span>
            </h2>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-blue-600 text-white">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Seller</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Product</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Revenue</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Unit Price</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Total Orders</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Pending</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Cancelled</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Confirmed</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">In Delivery</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Returned</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Delivered</th>
                  <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">Delivery Rate</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr 
                  v-for="(product, index) in filteredProducts" 
                  :key="product.id" 
                  :class="index % 2 === 0 ? 'bg-white' : 'bg-gray-50'"
                >
                  <td class="px-4 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                      {{ product.id }}
                    </span>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ product.seller }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="h-12 w-12 rounded-lg bg-gray-200 mr-4">
                        <img
                          class="h-12 w-12 rounded-lg object-cover"
                          :src="product.image"
                          :alt="product.product"
                        />
                      </div>
                      <div>
                        <div class="text-sm font-medium text-gray-900">{{ product.product }}</div>
                        <div class="text-sm text-gray-500">{{ product.category }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-green-600">
                      ${{ product.totalRevenue.toFixed(2) }}
                    </div>
                    <div class="text-xs text-gray-500">
                      AOV: ${{ product.avgOrderValue.toFixed(2) }}
                    </div>
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${{ product.unitPrice.toFixed(2) }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    {{ product.totalOrders }}
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ product.totalPending }} / {{ product.pendingPercent.toFixed(1) }}%
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ product.totalCancelled }} / {{ product.cancelledPercent.toFixed(1) }}%
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ product.totalConfirmed }} / {{ product.confirmedPercent.toFixed(1) }}%
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ product.inDelivery }} / {{ product.deliveryPercent.toFixed(1) }}%
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ product.totalReturned }} / {{ product.returnedPercent.toFixed(1) }}%
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ product.totalDelivered }} / {{ product.deliveredPercent.toFixed(1) }}%
                  </td>
                  <td class="px-4 py-4 whitespace-nowrap">
                    <span :class="[
                      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                      product.deliveryRate >= 50 ? 'bg-green-100 text-green-800' : 
                      product.deliveryRate >= 25 ? 'bg-yellow-100 text-yellow-800' : 
                      'bg-red-100 text-red-800'
                    ]">
                      {{ product.deliveryRate.toFixed(2) }}%
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between bg-white px-6 py-3 border-t border-gray-200">
          <div class="flex items-center">
            <p class="text-sm text-gray-700">
              Showing <span class="font-medium">1</span> to <span class="font-medium">{{ Math.min(15, filteredProducts.length) }}</span> of
              <span class="font-medium">{{ filteredProducts.length }}</span> results
            </p>
          </div>
          <div class="flex items-center space-x-2">
            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
              Previous
            </button>
            <button class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
              1
            </button>
            <button class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useProductStore } from '@/stores/productStore'
import AppLayout from "@/Layouts/AppLayout.vue"
import StatCard from '@/Pages/Stock/StatCard.vue'

import { 
  Search, 
  Filter, 
  RotateCcw, 
  ChevronDown, 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  Package, 
  ShoppingCart, 
  Truck 
} from 'lucide-vue-next'

// Use product store
const productStore = useProductStore()
const { 
  searchTerm, 
  selectedSeller, 
  selectedCategory, 
  sortBy, 
  dateFilter,
  products 
} = storeToRefs(productStore)

// Initialize products if empty
if (products.value.length === 0) {
  productStore.initializeProducts()
}

// Computed properties
const summaryStats = computed(() => {
  return products.value.reduce((acc, product) => {
    acc.totalProducts++
    acc.totalOrders += product.totalOrders
    acc.totalRevenue += product.totalRevenue
    acc.totalPending += product.totalPending
    acc.totalConfirmed += product.totalConfirmed
    acc.totalDelivered += product.totalDelivered
    acc.totalReturned += product.totalReturned
    return acc
  }, {
    totalProducts: 0,
    totalOrders: 0,
    totalRevenue: 0,
    totalPending: 0,
    totalConfirmed: 0,
    totalDelivered: 0,
    totalReturned: 0
  })
})

const filteredProducts = computed(() => {
  return productStore.getFilteredAndSortedProducts
})

// Methods
const resetFilters = () => {
  productStore.resetFilters()
}
</script>

<style scoped>
/* Add any component-specific styles here */
</style>