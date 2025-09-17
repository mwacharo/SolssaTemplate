<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <!-- My Orders Card -->
    <div class="group p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:border-blue-200">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-blue-100 rounded-xl group-hover:bg-blue-200 transition-colors">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
          <h3 class="text-sm font-medium text-gray-600">My Orders</h3>
        </div>
        <div class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">
          +{{ orderGrowth }}%
        </div>
      </div>
      
      <div class="space-y-1">
        <p class="text-3xl font-bold text-gray-900">{{ formatNumber(stats.totalOrders) }}</p>
        <p class="text-xs text-gray-500">Total orders this month</p>
      </div>
      
      <!-- Progress indicator -->
      <div class="mt-4">
        <div class="flex justify-between text-xs text-gray-500 mb-1">
          <span>This month</span>
          <span>{{ stats.totalOrders }}/{{ monthlyTarget }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-1.5">
          <div 
            class="bg-blue-600 h-1.5 rounded-full transition-all duration-500" 
            :style="{ width: orderProgress + '%' }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Successful Deliveries Card -->
    <div class="group p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:border-green-200">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-green-100 rounded-xl group-hover:bg-green-200 transition-colors">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-sm font-medium text-gray-600">Successful Deliveries</h3>
        </div>
        <div class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">
          {{ deliveryRate }}%
        </div>
      </div>
      
      <div class="space-y-1">
        <p class="text-3xl font-bold text-gray-900">{{ formatNumber(stats.deliveries) }}</p>
        <p class="text-xs text-gray-500">Completed deliveries</p>
      </div>
      
      <!-- Success rate indicator -->
      <div class="mt-4">
        <div class="flex justify-between text-xs text-gray-500 mb-1">
          <span>Success rate</span>
          <span>{{ deliveryRate }}% of {{ stats.totalOrders }}</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-1.5">
          <div 
            class="bg-green-600 h-1.5 rounded-full transition-all duration-500" 
            :style="{ width: deliveryRate + '%' }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Additional Stats Cards -->
    <div class="group p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:border-orange-200">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-orange-100 rounded-xl group-hover:bg-orange-200 transition-colors">
            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-sm font-medium text-gray-600">Pending Orders</h3>
        </div>
        <div class="text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded-full font-medium">
          {{ pendingOrders }}
        </div>
      </div>
      
      <div class="space-y-1">
        <p class="text-3xl font-bold text-gray-900">{{ formatNumber(pendingOrders) }}</p>
        <p class="text-xs text-gray-500">Awaiting processing</p>
      </div>
    </div>

    <!-- Average Delivery Time -->
    <div class="group p-6 bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-300 hover:border-purple-200">
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center space-x-3">
          <div class="p-2 bg-purple-100 rounded-xl group-hover:bg-purple-200 transition-colors">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
          </div>
          <h3 class="text-sm font-medium text-gray-600">Avg. Delivery Time</h3>
        </div>
        <div class="text-xs text-purple-600 bg-purple-50 px-2 py-1 rounded-full font-medium">
          Fast
        </div>
      </div>
      
      <div class="space-y-1">
        <p class="text-3xl font-bold text-gray-900">{{ averageDeliveryTime }}</p>
        <p class="text-xs text-gray-500">Hours per delivery</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// Props
const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      totalOrders: 0,
      deliveries: 0
    })
  }
})

// Computed values
const deliveryRate = computed(() => {
  if (props.stats.totalOrders === 0) return 0
  return Math.round((props.stats.deliveries / props.stats.totalOrders) * 100)
})

const pendingOrders = computed(() => {
  return Math.max(0, props.stats.totalOrders - props.stats.deliveries)
})

const orderGrowth = computed(() => {
  // Mock growth percentage - replace with real data
  return 12
})

const monthlyTarget = computed(() => {
  // Mock monthly target - replace with real data
  return Math.max(props.stats.totalOrders + 20, 150)
})

const orderProgress = computed(() => {
  return Math.min(100, (props.stats.totalOrders / monthlyTarget.value) * 100)
})

const averageDeliveryTime = computed(() => {
  // Mock average delivery time - replace with real data
  return "2.4h"
})

// Helper functions
const formatNumber = (num) => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(1) + 'M'
  } else if (num >= 1000) {
    return (num / 1000).toFixed(1) + 'K'
  }
  return num.toString()
}
</script>

<style scoped>
/* Additional custom animations */
.group:hover .transition-all {
  transform: translateY(-1px);
}

@media (max-width: 640px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style>