<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
      <div class="mb-4 sm:mb-0">
        <h3 class="text-lg font-semibold text-gray-900 mb-1">Order Analytics</h3>
        <p class="text-sm text-gray-500">Confirmed vs Delivered orders over time</p>
      </div>
      
      <!-- Time Period Selector -->
      <div class="flex items-center space-x-2">
        <div class="flex bg-gray-100 rounded-lg p-1">
          <button 
            v-for="period in timePeriods" 
            :key="period.value"
            @click="selectedPeriod = period.value"
            :class="[
              'px-3 py-1.5 text-sm font-medium rounded-md transition-all duration-200',
              selectedPeriod === period.value 
                ? 'bg-white text-blue-600 shadow-sm' 
                : 'text-gray-600 hover:text-gray-900'
            ]"
          >
            {{ period.label }}
          </button>
        </div>
      </div>
    </div>

    <!-- Chart Container -->
    <div class="relative">
      <!-- Loading state -->
      <div v-if="isLoading" class="flex items-center justify-center h-80">
        <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-600 border-t-transparent"></div>
      </div>
      
      <!-- Chart -->
      <apexchart 
        v-else
        type="area" 
        height="320" 
        :options="chartOptions" 
        :series="processedChartData" 
        class="apex-chart"
      />
    </div>

    <!-- Stats Summary -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100">
      <div class="text-center">
        <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full mx-auto mb-2">
          <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ totalConfirmed }}</p>
        <p class="text-xs text-gray-500">Total Confirmed</p>
      </div>
      
      <div class="text-center">
        <div class="flex items-center justify-center w-8 h-8 bg-green-100 rounded-full mx-auto mb-2">
          <div class="w-3 h-3 bg-green-600 rounded-full"></div>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ totalDelivered }}</p>
        <p class="text-xs text-gray-500">Total Delivered</p>
      </div>
      
      <div class="text-center">
        <div class="flex items-center justify-center w-8 h-8 bg-orange-100 rounded-full mx-auto mb-2">
          <svg class="w-4 h-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
          </svg>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ completionRate }}%</p>
        <p class="text-xs text-gray-500">Completion Rate</p>
      </div>
      
      <div class="text-center">
        <div class="flex items-center justify-center w-8 h-8 bg-purple-100 rounded-full mx-auto mb-2">
          <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
          </svg>
        </div>
        <p class="text-2xl font-bold text-gray-900">{{ avgGrowth }}%</p>
        <p class="text-xs text-gray-500">Avg Growth</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

// Props
const props = defineProps({
  chartData: {
    type: Array,
    required: true,
    default: () => []
  }
})

// Reactive data
const selectedPeriod = ref('6M')
const isLoading = ref(false)

// Time period options
const timePeriods = [
  { label: '1M', value: '1M' },
  { label: '3M', value: '3M' },
  { label: '6M', value: '6M' },
  { label: '1Y', value: '1Y' }
]

// Chart configuration
const chartOptions = computed(() => ({
  chart: {
    id: 'orders-analytics',
    type: 'area',
    height: 320,
    fontFamily: 'Inter, sans-serif',
    toolbar: {
      show: true,
      tools: {
        download: true,
        selection: false,
        zoom: true,
        zoomin: true,
        zoomout: true,
        pan: false,
        reset: true
      }
    },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800
    }
  },
  
  colors: ['#3B82F6', '#10B981'],
  
  dataLabels: {
    enabled: false
  },
  
  stroke: {
    curve: 'smooth',
    width: 3,
    lineCap: 'round'
  },
  
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.1,
      stops: [0, 100]
    }
  },
  
  grid: {
    borderColor: '#E5E7EB',
    strokeDashArray: 3,
    xaxis: {
      lines: {
        show: true
      }
    },
    yaxis: {
      lines: {
        show: true
      }
    },
    padding: {
      top: 20,
      right: 20,
      bottom: 20,
      left: 20
    }
  },
  
  xaxis: {
    categories: getCategories(selectedPeriod.value),
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
        fontSize: '12px',
        fontWeight: 500
      },
      formatter: (value) => {
        return value >= 1000 ? (value / 1000).toFixed(1) + 'K' : value
      }
    }
  },
  
  tooltip: {
    theme: 'light',
    x: {
      show: true
    },
    y: {
      formatter: (value, { seriesIndex, dataPointIndex, w }) => {
        const seriesName = w.config.series[seriesIndex].name
        return `${seriesName}: ${value.toLocaleString()} orders`
      }
    },
    marker: {
      show: true
    },
    style: {
      fontSize: '12px',
      fontFamily: 'Inter, sans-serif'
    }
  },
  
  legend: {
    show: true,
    position: 'top',
    horizontalAlign: 'right',
    fontSize: '12px',
    fontFamily: 'Inter, sans-serif',
    fontWeight: 500,
    labels: {
      colors: '#374151'
    },
    markers: {
      width: 8,
      height: 8,
      strokeWidth: 0,
      radius: 4
    }
  },
  
  responsive: [
    {
      breakpoint: 768,
      options: {
        chart: {
          height: 280
        },
        legend: {
          position: 'bottom',
          horizontalAlign: 'center'
        }
      }
    }
  ]
}))

// Process chart data based on selected period
const processedChartData = computed(() => {
  if (!props.chartData || props.chartData.length === 0) {
    return [
      { name: 'Confirmed', data: [] },
      { name: 'Delivered', data: [] }
    ]
  }
  
  return props.chartData.map(series => ({
    ...series,
    data: adjustDataForPeriod(series.data, selectedPeriod.value)
  }))
})

// Helper function to get categories based on period
function getCategories(period) {
  const categories = {
    '1M': ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
    '3M': ['Jan', 'Feb', 'Mar'],
    '6M': ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
    '1Y': ['Q1', 'Q2', 'Q3', 'Q4', 'Q1', 'Q2', 'Q3', 'Q4', 'Q1', 'Q2', 'Q3', 'Q4']
  }
  return categories[period] || categories['6M']
}

// Helper function to adjust data based on selected period
function adjustDataForPeriod(data, period) {
  if (!data) return []
  
  const adjustments = {
    '1M': data.slice(-4), // Last 4 data points
    '3M': data.slice(-3), // Last 3 data points  
    '6M': data, // All data
    '1Y': [...data, ...data] // Duplicate for year view (mock)
  }
  
  return adjustments[period] || data
}

// Computed statistics
const totalConfirmed = computed(() => {
  const confirmedSeries = processedChartData.value.find(s => s.name === 'Confirmed')
  return confirmedSeries ? confirmedSeries.data.reduce((sum, val) => sum + val, 0) : 0
})

const totalDelivered = computed(() => {
  const deliveredSeries = processedChartData.value.find(s => s.name === 'Delivered')
  return deliveredSeries ? deliveredSeries.data.reduce((sum, val) => sum + val, 0) : 0
})

const completionRate = computed(() => {
  if (totalConfirmed.value === 0) return 0
  return Math.round((totalDelivered.value / totalConfirmed.value) * 100)
})

const avgGrowth = computed(() => {
  const confirmedData = processedChartData.value.find(s => s.name === 'Confirmed')?.data || []
  if (confirmedData.length < 2) return 0
  
  let totalGrowth = 0
  let periods = 0
  
  for (let i = 1; i < confirmedData.length; i++) {
    if (confirmedData[i-1] > 0) {
      const growth = ((confirmedData[i] - confirmedData[i-1]) / confirmedData[i-1]) * 100
      totalGrowth += growth
      periods++
    }
  }
  
  return periods > 0 ? Math.round(totalGrowth / periods) : 0
})

// Watch for period changes and simulate loading
watch(selectedPeriod, async () => {
  isLoading.value = true
  // Simulate API call delay
  await new Promise(resolve => setTimeout(resolve, 500))
  isLoading.value = false
})

// Mount lifecycle
onMounted(() => {
  // Any initialization logic
})
</script>

<style scoped>
.apex-chart {
  font-family: 'Inter', sans-serif;
}

/* Custom scrollbar for mobile */
@media (max-width: 640px) {
  .overflow-x-auto::-webkit-scrollbar {
    height: 4px;
  }
  
  .overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 2px;
  }
  
  .overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
  }
}

/* Loading animation */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}
</style>