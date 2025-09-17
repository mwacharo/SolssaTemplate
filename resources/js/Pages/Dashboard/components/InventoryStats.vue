<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <!-- Header Section -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <div class="p-2.5 bg-indigo-100 rounded-xl">
          <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Inventory Overview</h3>
          <p class="text-sm text-gray-500">Stock distribution and management</p>
        </div>
      </div>
      
      <!-- Quick Stats Badge -->
      <div class="text-right">
        <div class="text-2xl font-bold text-gray-900">{{ formatNumber(inventory.items) }}</div>
        <div class="text-xs text-gray-500">Total Items</div>
      </div>
    </div>

    <!-- Summary Cards Row -->
    <div class="grid grid-cols-2 gap-4 mb-6">
      <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border border-blue-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-blue-700">Product SKUs</p>
            <p class="text-2xl font-bold text-blue-900">{{ formatNumber(inventory.skus) }}</p>
          </div>
          <div class="p-2 bg-blue-200 rounded-lg">
            <svg class="w-4 h-4 text-blue-700" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
            </svg>
          </div>
        </div>
      </div>
      
      <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-4 border border-emerald-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-emerald-700">Stock Value</p>
            <p class="text-2xl font-bold text-emerald-900">${{ stockValue }}</p>
          </div>
          <div class="p-2 bg-emerald-200 rounded-lg">
            <svg class="w-4 h-4 text-emerald-700" fill="currentColor" viewBox="0 0 20 20">
              <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z" />
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Stock Distribution Chart -->
    <div class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <h4 class="text-sm font-medium text-gray-700">Stock Distribution</h4>
        <div class="flex items-center space-x-2">
          <button 
            @click="chartType = 'column'"
            :class="[
              'px-3 py-1.5 text-xs font-medium rounded-lg transition-all',
              chartType === 'column' 
                ? 'bg-indigo-100 text-indigo-700' 
                : 'text-gray-500 hover:bg-gray-100'
            ]"
          >
            Column
          </button>
          <button 
            @click="chartType = 'donut'"
            :class="[
              'px-3 py-1.5 text-xs font-medium rounded-lg transition-all',
              chartType === 'donut' 
                ? 'bg-indigo-100 text-indigo-700' 
                : 'text-gray-500 hover:bg-gray-100'
            ]"
          >
            Donut
          </button>
        </div>
      </div>
      
      <apexchart 
        :type="chartType" 
        height="240" 
        :options="chartOptions" 
        :series="chartSeries" 
      />
    </div>

    <!-- Detailed Stock Breakdown -->
    <div class="space-y-3">
      <h4 class="text-sm font-medium text-gray-700 mb-3">Stock Status Breakdown</h4>
      
      <!-- In Stock -->
      <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-100">
        <div class="flex items-center space-x-3">
          <div class="w-3 h-3 bg-green-500 rounded-full"></div>
          <div>
            <p class="text-sm font-medium text-green-800">In Stock</p>
            <p class="text-xs text-green-600">{{ calculateItems('inStock') }} items</p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-lg font-bold text-green-900">{{ inventory.inStock }}%</p>
          <div class="w-16 bg-green-200 rounded-full h-1.5">
            <div 
              class="bg-green-500 h-1.5 rounded-full transition-all duration-500" 
              :style="{ width: inventory.inStock + '%' }"
            ></div>
          </div>
        </div>
      </div>
      
      <!-- Low Stock -->
      <div class="flex items-center justify-between p-3 bg-amber-50 rounded-lg border border-amber-100">
        <div class="flex items-center space-x-3">
          <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
          <div>
            <p class="text-sm font-medium text-amber-800">Low Stock</p>
            <p class="text-xs text-amber-600">{{ calculateItems('lowStock') }} items</p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-lg font-bold text-amber-900">{{ inventory.lowStock }}%</p>
          <div class="w-16 bg-amber-200 rounded-full h-1.5">
            <div 
              class="bg-amber-500 h-1.5 rounded-full transition-all duration-500" 
              :style="{ width: inventory.lowStock + '%' }"
            ></div>
          </div>
        </div>
      </div>
      
      <!-- Out of Stock -->
      <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg border border-red-100">
        <div class="flex items-center space-x-3">
          <div class="w-3 h-3 bg-red-500 rounded-full"></div>
          <div>
            <p class="text-sm font-medium text-red-800">Out of Stock</p>
            <p class="text-xs text-red-600">{{ calculateItems('outStock') }} items</p>
          </div>
        </div>
        <div class="text-right">
          <p class="text-lg font-bold text-red-900">{{ inventory.outStock }}%</p>
          <div class="w-16 bg-red-200 rounded-full h-1.5">
            <div 
              class="bg-red-500 h-1.5 rounded-full transition-all duration-500" 
              :style="{ width: inventory.outStock + '%' }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-3 mt-6 pt-6 border-t border-gray-100">
      <button class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        <span>Add Inventory</span>
      </button>
      
      <button class="flex-1 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        <span>View Report</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props
const props = defineProps({
  inventory: {
    type: Object,
    required: true,
    default: () => ({
      items: 0,
      skus: 0,
      inStock: 0,
      lowStock: 0,
      outStock: 0
    })
  }
})

// Reactive data
const chartType = ref('column')

// Computed values
const stockValue = computed(() => {
  // Mock calculation - replace with real data
  return (props.inventory.items * 45.50).toLocaleString()
})

const chartSeries = computed(() => {
  if (chartType.value === 'donut') {
    return [props.inventory.inStock, props.inventory.lowStock, props.inventory.outStock]
  } else {
    return [{
      name: 'Stock Percentage',
      data: [props.inventory.inStock, props.inventory.lowStock, props.inventory.outStock]
    }]
  }
})

const chartOptions = computed(() => {
  const baseOptions = {
    chart: {
      fontFamily: 'Inter, sans-serif',
      toolbar: {
        show: false
      },
      animations: {
        enabled: true,
        easing: 'easeinout',
        speed: 800
      }
    },
    colors: ['#10B981', '#F59E0B', '#EF4444'],
    dataLabels: {
      enabled: true,
      style: {
        fontSize: '12px',
        fontWeight: 600
      }
    },
    legend: {
      show: chartType.value === 'donut',
      position: 'bottom',
      fontSize: '12px',
      fontFamily: 'Inter, sans-serif',
      markers: {
        width: 8,
        height: 8,
        strokeWidth: 0,
        radius: 4
      }
    },
    tooltip: {
      theme: 'light',
      style: {
        fontSize: '12px',
        fontFamily: 'Inter, sans-serif'
      }
    }
  }

  if (chartType.value === 'donut') {
    return {
      ...baseOptions,
      labels: ['In Stock', 'Low Stock', 'Out of Stock'],
      plotOptions: {
        pie: {
          donut: {
            size: '65%',
            labels: {
              show: true,
              name: {
                show: true,
                fontSize: '14px',
                fontWeight: 600
              },
              value: {
                show: true,
                fontSize: '24px',
                fontWeight: 700,
                formatter: (val) => val + '%'
              },
              total: {
                show: true,
                label: 'Total Stock',
                fontSize: '12px',
                color: '#6B7280',
                formatter: () => {
                  return props.inventory.items + ' items'
                }
              }
            }
          }
        }
      }
    }
  } else {
    return {
      ...baseOptions,
      plotOptions: {
        bar: {
          borderRadius: 8,
          columnWidth: '60%',
          dataLabels: {
            position: 'top'
          }
        }
      },
      xaxis: {
        categories: ['In Stock', 'Low Stock', 'Out of Stock'],
        axisBorder: {
          show: false
        },
        axisTicks: {
          show: false
        },
        labels: {
          style: {
            colors: '#6B7280',
            fontSize: '12px',
            fontWeight: 500
          }
        }
      },
      yaxis: {
        labels: {
          style: {
            colors: '#6B7280',
            fontSize: '12px'
          },
          formatter: (value) => value + '%'
        }
      },
      grid: {
        borderColor: '#E5E7EB',
        strokeDashArray: 3,
        xaxis: {
          lines: {
            show: false
          }
        },
        yaxis: {
          lines: {
            show: true
          }
        }
      }
    }
  }
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

const calculateItems = (type) => {
  const percentage = props.inventory[type]
  const items = Math.round((percentage / 100) * props.inventory.items)
  return formatNumber(items)
}
</script>

<style scoped>
/* Custom button hover effects */
button:hover {
  transform: translateY(-1px);
}

button:active {
  transform: translateY(0);
}

/* Custom progress bar animations */
.transition-all {
  transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Mobile responsiveness */
@media (max-width: 640px) {
  .grid-cols-2 {
    grid-template-columns: 1fr;
  }
}
</style>