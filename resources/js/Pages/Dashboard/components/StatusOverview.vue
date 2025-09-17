<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <!-- Header Section -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <div class="p-2.5 bg-purple-100 rounded-xl">
          <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Order Status Overview</h3>
          <p class="text-sm text-gray-500">Current distribution of order statuses</p>
        </div>
      </div>
      
      <!-- Time Filter -->
      <div class="flex bg-gray-100 rounded-lg p-1">
        <button 
          v-for="period in timePeriods" 
          :key="period"
          @click="selectedPeriod = period"
          :class="[
            'px-3 py-1.5 text-xs font-medium rounded-md transition-all duration-200',
            selectedPeriod === period 
              ? 'bg-white text-purple-600 shadow-sm' 
              : 'text-gray-600 hover:text-gray-900'
          ]"
        >
          {{ period }}
        </button>
      </div>
    </div>

    <!-- Chart Container -->
    <div class="relative">
      <apexchart 
        type="donut" 
        height="300" 
        :options="chartOptions" 
        :series="status" 
        class="mx-auto"
      />
      
      <!-- Chart overlay with additional info -->
      <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm rounded-lg p-3 shadow-sm border border-gray-200">
        <div class="text-center">
          <p class="text-xs text-gray-500 font-medium">Success Rate</p>
          <p class="text-lg font-bold text-green-600">{{ successRate }}%</p>
        </div>
      </div>
    </div>

    <!-- Status Breakdown List -->
    <div class="space-y-3 mt-6">
      <h4 class="text-sm font-medium text-gray-700 mb-4">Status Breakdown</h4>
      
      <div 
        v-for="(item, index) in statusBreakdown" 
        :key="item.label"
        class="flex items-center justify-between p-3 rounded-xl border transition-all duration-200 hover:shadow-sm"
        :class="item.bgClass"
      >
        <div class="flex items-center space-x-3">
          <!-- Status Icon -->
          <div :class="[
            'w-10 h-10 rounded-full flex items-center justify-center transition-all duration-200',
            item.iconBgClass
          ]">
            <component :is="item.icon" :class="['w-5 h-5', item.iconColorClass]" />
          </div>
          
          <div>
            <p :class="['text-sm font-medium', item.textClass]">{{ item.label }}</p>
            <p :class="['text-xs', item.subTextClass]">{{ item.count }} orders</p>
          </div>
        </div>
        
        <div class="flex items-center space-x-3">
          <!-- Percentage -->
          <div class="text-right">
            <p :class="['text-lg font-bold', item.textClass]">{{ item.percentage }}%</p>
            <div :class="['w-16 rounded-full h-1.5', item.progressBg]">
              <div 
                :class="['h-1.5 rounded-full transition-all duration-700', item.progressColor]"
                :style="{ width: item.percentage + '%' }"
              ></div>
            </div>
          </div>
          
          <!-- Trend Indicator -->
          <div :class="['p-1.5 rounded-lg', item.trendBg]">
            <svg 
              v-if="item.trend === 'up'" 
              class="w-3 h-3 text-green-600" 
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M5.293 7.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L6.707 7.707a1 1 0 01-1.414 0z" clip-rule="evenodd" />
            </svg>
            <svg 
              v-else-if="item.trend === 'down'" 
              class="w-3 h-3 text-red-600" 
              fill="currentColor" 
              viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M14.707 12.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 14.586V3a1 1 0 112 0v11.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
            <div v-else class="w-3 h-3 rounded-full bg-gray-400"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 gap-3 mt-6 pt-6 border-t border-gray-100">
      <button class="flex items-center justify-center space-x-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium py-3 px-4 rounded-lg transition-all duration-200 hover:shadow-md">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
        </svg>
        <span>Manage Orders</span>
      </button>
      
      <button class="flex items-center justify-center space-x-2 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium py-3 px-4 rounded-lg transition-all duration-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
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
  status: {
    type: Array,
    required: true,
    default: () => [20, 15, 40, 10, 5] // [pending, shipped, delivered, returned, cancelled]
  }
})

// Reactive data
const selectedPeriod = ref('7D')
const timePeriods = ['24H', '7D', '30D', '90D']

// Status configuration
const statusConfig = [
  {
    label: 'Pending',
    color: '#F59E0B',
    bgClass: 'bg-amber-50 border-amber-200',
    iconBgClass: 'bg-amber-100',
    iconColorClass: 'text-amber-600',
    textClass: 'text-amber-800',
    subTextClass: 'text-amber-600',
    progressBg: 'bg-amber-200',
    progressColor: 'bg-amber-500',
    trendBg: 'bg-amber-100',
    trend: 'up',
    icon: 'ClockIcon'
  },
  {
    label: 'Shipped',
    color: '#3B82F6',
    bgClass: 'bg-blue-50 border-blue-200',
    iconBgClass: 'bg-blue-100',
    iconColorClass: 'text-blue-600',
    textClass: 'text-blue-800',
    subTextClass: 'text-blue-600',
    progressBg: 'bg-blue-200',
    progressColor: 'bg-blue-500',
    trendBg: 'bg-blue-100',
    trend: 'up',
    icon: 'TruckIcon'
  },
  {
    label: 'Delivered',
    color: '#10B981',
    bgClass: 'bg-green-50 border-green-200',
    iconBgClass: 'bg-green-100',
    iconColorClass: 'text-green-600',
    textClass: 'text-green-800',
    subTextClass: 'text-green-600',
    progressBg: 'bg-green-200',
    progressColor: 'bg-green-500',
    trendBg: 'bg-green-100',
    trend: 'up',
    icon: 'CheckIcon'
  },
  {
    label: 'Returned',
    color: '#8B5CF6',
    bgClass: 'bg-purple-50 border-purple-200',
    iconBgClass: 'bg-purple-100',
    iconColorClass: 'text-purple-600',
    textClass: 'text-purple-800',
    subTextClass: 'text-purple-600',
    progressBg: 'bg-purple-200',
    progressColor: 'bg-purple-500',
    trendBg: 'bg-purple-100',
    trend: 'stable',
    icon: 'ArrowLeftIcon'
  },
  {
    label: 'Cancelled',
    color: '#EF4444',
    bgClass: 'bg-red-50 border-red-200',
    iconBgClass: 'bg-red-100',
    iconColorClass: 'text-red-600',
    textClass: 'text-red-800',
    subTextClass: 'text-red-600',
    progressBg: 'bg-red-200',
    progressColor: 'bg-red-500',
    trendBg: 'bg-red-100',
    trend: 'down',
    icon: 'XIcon'
  }
]

// Chart options
const chartOptions = computed(() => ({
  chart: {
    type: 'donut',
    fontFamily: 'Inter, sans-serif',
    toolbar: {
      show: false
    },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800,
      animateGradually: {
        enabled: true,
        delay: 150
      },
      dynamicAnimation: {
        enabled: true,
        speed: 350
      }
    }
  },
  
  labels: statusConfig.map(s => s.label),
  colors: statusConfig.map(s => s.color),
  
  plotOptions: {
    pie: {
      donut: {
        size: '70%',
        labels: {
          show: true,
          name: {
            show: true,
            fontSize: '14px',
            fontWeight: 600,
            color: '#374151',
            offsetY: -10,
            formatter: (val) => val
          },
          value: {
            show: true,
            fontSize: '28px',
            fontWeight: 700,
            color: '#111827',
            offsetY: 10,
            formatter: (val) => val
          },
          total: {
            show: true,
            showAlways: true,
            label: 'Total Orders',
            fontSize: '12px',
            fontWeight: 500,
            color: '#6B7280',
            formatter: () => {
              const total = props.status.reduce((sum, val) => sum + val, 0)
              return total.toLocaleString()
            }
          }
        }
      }
    }
  },
  
  dataLabels: {
    enabled: true,
    formatter: (val, opt) => {
      return Math.round(val) + '%'
    },
    style: {
      fontSize: '11px',
      fontWeight: 600,
      colors: ['#FFFFFF']
    },
    dropShadow: {
      enabled: true,
      blur: 2,
      opacity: 0.8
    }
  },
  
  legend: {
    show: false // We'll use custom legend below
  },
  
  tooltip: {
    enabled: true,
    theme: 'light',
    style: {
      fontSize: '12px',
      fontFamily: 'Inter, sans-serif'
    },
    y: {
      formatter: (val, { seriesIndex }) => {
        const total = props.status.reduce((sum, val) => sum + val, 0)
        const count = props.status[seriesIndex]
        return `${count} orders (${val.toFixed(1)}%)`
      }
    }
  },
  
  stroke: {
    show: true,
    width: 2,
    colors: ['#FFFFFF']
  },
  
  responsive: [
    {
      breakpoint: 480,
      options: {
        chart: {
          height: 250
        },
        plotOptions: {
          pie: {
            donut: {
              size: '65%',
              labels: {
                value: {
                  fontSize: '24px'
                },
                total: {
                  fontSize: '11px'
                }
              }
            }
          }
        }
      }
    }
  ]
}))

// Computed values
const totalOrders = computed(() => {
  return props.status.reduce((sum, val) => sum + val, 0)
})

const successRate = computed(() => {
  if (totalOrders.value === 0) return 0
  const delivered = props.status[2] || 0 // Delivered is index 2
  return Math.round((delivered / totalOrders.value) * 100)
})

const statusBreakdown = computed(() => {
  return statusConfig.map((config, index) => ({
    ...config,
    count: props.status[index] || 0,
    percentage: totalOrders.value > 0 
      ? Math.round((props.status[index] / totalOrders.value) * 100) 
      : 0
  }))
})

// Icon components (simplified SVG icons)
const ClockIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
}

const TruckIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`
}

const CheckIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>`
}

const ArrowLeftIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>`
}

const XIcon = {
  template: `<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>`
}
</script>

<style scoped>
/* Custom hover effects */
.hover\:shadow-md:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Progress bar animations */
.transition-all {
  transition: all 0.7s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Button hover animations */
button:hover {
  transform: translateY(-1px);
}

button:active {
  transform: translateY(0);
}

/* ApexCharts custom styling */
:deep(.apexcharts-tooltip) {
  border-radius: 8px !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
}

:deep(.apexcharts-datalabel-label) {
  fill: #6B7280 !important;
}

:deep(.apexcharts-datalabel-value) {
  fill: #111827 !important;
}
</style>