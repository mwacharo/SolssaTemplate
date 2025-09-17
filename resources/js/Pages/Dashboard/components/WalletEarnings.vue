<template>
  <div class="p-6 bg-white rounded-2xl shadow-lg">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide">Wallet</h3>
        <p class="mt-1 text-3xl font-bold text-gray-900">KES {{ formatNumber(wallet.balance) }}</p>
        <p class="text-sm text-gray-500 mt-1">
          Target: KES {{ formatNumber(wallet.target || 100000) }}
        </p>
      </div>
      <div class="text-right">
        <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
             :class="progressColor">
          {{ Math.round(wallet.progress) }}%
        </div>
      </div>
    </div>
    
    <div class="relative">
      <apexchart 
        type="radialBar" 
        height="220" 
        :options="chartOptions" 
        :series="[wallet.progress]" 
      />
      
      <!-- Center content overlay -->
      <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="text-center">
          <div class="text-2xl font-bold text-gray-900">
            {{ Math.round(wallet.progress) }}%
          </div>
          <div class="text-xs text-gray-500 mt-1">
            Complete
          </div>
        </div>
      </div>
    </div>
    
    <div class="mt-4 flex items-center justify-between text-sm">
      <span class="text-gray-500">Remaining</span>
      <span class="font-medium text-gray-900">
        KES {{ formatNumber(Math.max(0, (wallet.target || 100000) - wallet.balance)) }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  wallet: {
    type: Object,
    required: true,
    default: () => ({
      balance: 0,
      progress: 0,
      target: 100000
    })
  }
})

// Format numbers with commas
const formatNumber = (num) => {
  return new Intl.NumberFormat().format(num)
}

// Dynamic progress color based on completion
const progressColor = computed(() => {
  const progress = props.wallet.progress
  if (progress >= 90) return 'bg-green-100 text-green-800'
  if (progress >= 70) return 'bg-blue-100 text-blue-800'
  if (progress >= 50) return 'bg-yellow-100 text-yellow-800'
  return 'bg-gray-100 text-gray-800'
})

// Dynamic chart color based on progress
const chartColor = computed(() => {
  const progress = props.wallet.progress
  if (progress >= 90) return '#10b981' // green-500
  if (progress >= 70) return '#3b82f6' // blue-500
  if (progress >= 50) return '#f59e0b' // yellow-500
  return '#6b7280' // gray-500
})

const chartOptions = computed(() => ({
  chart: {
    type: 'radialBar',
    sparkline: {
      enabled: true
    }
  },
  plotOptions: {
    radialBar: {
      startAngle: -90,
      endAngle: 90,
      hollow: {
        size: '65%',
        background: 'transparent'
      },
      track: {
        background: '#f3f4f6',
        strokeWidth: '100%',
        margin: 5,
      },
      dataLabels: {
        name: {
          show: false
        },
        value: {
          show: false
        }
      }
    }
  },
  colors: [chartColor.value],
  stroke: {
    lineCap: 'round'
  },
  grid: {
    padding: {
      top: -10,
      bottom: -25
    }
  },
  fill: {
    type: 'gradient',
    gradient: {
      shade: 'light',
      shadeIntensity: 0.4,
      inverseColors: false,
      opacityFrom: 1,
      opacityTo: 1,
      stops: [0, 50, 53, 91]
    }
  }
}))
</script>

<style scoped>
/* Ensure the chart container is properly positioned */
.apexcharts-canvas {
  margin: 0 auto;
}
</style>