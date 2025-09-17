<template>
  <div class="bg-white p-6 rounded-2xl shadow-lg">
    <!-- Header Section -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-800">Top Sellers by Deliveries</h3>
        <p class="text-sm text-gray-500 mt-1">Performance ranking for {{ currentMonth }}</p>
      </div>
      <div class="flex items-center space-x-4">
        <div class="flex items-center text-xs text-gray-500">
          <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
          Completed Deliveries
        </div>
        <div class="text-right">
          <div class="text-xs text-gray-500">Growth</div>
          <div class="text-sm font-semibold text-green-600">+12.5%</div>
        </div>
      </div>
    </div>

    <!-- Chart Container -->
    <div class="mb-6">
      <apexchart
        type="bar"
        height="400"
        :options="chartOptions"
        :series="series"
      />
    </div>

    <!-- Seller Performance Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
      <div 
        v-for="(seller, index) in topSellers" 
        :key="seller.name"
        class="p-4 border border-gray-100 rounded-lg hover:shadow-md transition-shadow"
        :class="index === 0 ? 'ring-2 ring-green-200 bg-green-50' : ''"
      >
        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center">
            <div 
              class="w-3 h-3 rounded-full mr-2"
              :style="{ backgroundColor: sellerColors[index] }"
            ></div>
            <span class="font-medium text-gray-800">{{ seller.name }}</span>
            <span v-if="index === 0" class="ml-2 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
              🏆 Top
            </span>
          </div>
          <div class="text-right">
            <div class="font-bold text-gray-800">{{ seller.deliveries }}</div>
            <div class="text-xs text-gray-500">deliveries</div>
          </div>
        </div>
        
        <div class="flex items-center justify-between text-sm">
          <span class="text-gray-500">Success Rate</span>
          <span class="font-medium" :class="getSuccessRateColor(seller.successRate)">
            {{ seller.successRate }}%
          </span>
        </div>
        
        <!-- Progress Bar -->
        <div class="mt-2">
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div 
              class="h-2 rounded-full transition-all duration-500"
              :style="{ 
                width: `${(seller.deliveries / maxDeliveries) * 100}%`,
                backgroundColor: sellerColors[index]
              }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Statistics -->
    <div class="grid grid-cols-4 gap-4 pt-6 border-t border-gray-100">
      <div class="text-center">
        <div class="text-2xl font-bold text-gray-800">{{ totalDeliveries }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Total Deliveries</div>
      </div>
      <div class="text-center">
        <div class="text-2xl font-bold text-green-600">{{ topSellers[0]?.name || 'N/A' }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Top Performer</div>
      </div>
      <div class="text-center">
        <div class="text-2xl font-bold text-blue-600">{{ averageDeliveries }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Avg Deliveries</div>
      </div>
      <div class="text-center">
        <div class="text-2xl font-bold text-purple-600">{{ activeVendors }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Active Vendors</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue"

// Props for external data
const props = defineProps({
  data: {
    type: Array,
    default: () => []
  },
  period: {
    type: String,
    default: 'This Month'
  }
})

// Example static data with enhanced seller information
const defaultSellers = [
  { name: "Vendor A", deliveries: 120, successRate: 98, rating: 4.9, location: "Nairobi" },
  { name: "Vendor B", deliveries: 95, successRate: 95, rating: 4.7, location: "Mombasa" },
  { name: "Vendor C", deliveries: 88, successRate: 92, rating: 4.5, location: "Kisumu" },
  { name: "Vendor D", deliveries: 60, successRate: 90, rating: 4.3, location: "Nakuru" },
  { name: "Vendor E", deliveries: 45, successRate: 88, rating: 4.1, location: "Eldoret" }
]

// Use props data if available, otherwise use default
const sellerData = computed(() => props.data.length > 0 ? props.data : defaultSellers)

// Extract data for chart
const sellers = computed(() => sellerData.value.map(seller => seller.name))
const deliveries = computed(() => sellerData.value.map(seller => seller.deliveries))

// Enhanced seller data with rankings
const topSellers = computed(() => {
  return sellerData.value
    .sort((a, b) => b.deliveries - a.deliveries)
    .slice(0, 5)
})

// Color scheme for different sellers
const sellerColors = [
  "#10b981", // Green for #1
  "#3b82f6", // Blue for #2  
  "#f59e0b", // Yellow for #3
  "#ef4444", // Red for #4
  "#8b5cf6"  // Purple for #5
]

// Computed statistics
const totalDeliveries = computed(() => deliveries.value.reduce((sum, val) => sum + val, 0))
const averageDeliveries = computed(() => Math.round(totalDeliveries.value / deliveries.value.length))
const maxDeliveries = computed(() => Math.max(...deliveries.value))
const activeVendors = computed(() => sellerData.value.length)
const currentMonth = computed(() => props.period)

// Helper function for success rate colors
const getSuccessRateColor = (rate) => {
  if (rate >= 95) return 'text-green-600'
  if (rate >= 90) return 'text-blue-600'
  if (rate >= 85) return 'text-yellow-600'
  return 'text-red-600'
}

const series = ref([
  {
    name: "Deliveries",
    data: deliveries.value
  }
])

const chartOptions = ref({
  chart: {
    type: "bar",
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
  plotOptions: {
    bar: {
      horizontal: true,
      borderRadius: 8,
      borderRadiusApplication: 'end',
      barHeight: "65%",
      distributed: true,
      dataLabels: {
        position: 'center'
      }
    }
  },
  dataLabels: {
    enabled: true,
    textAnchor: 'middle',
    distributed: false,
    offsetX: 0,
    offsetY: 0,
    style: {
      fontSize: '14px',
      fontFamily: 'Inter, sans-serif',
      fontWeight: '600',
      colors: ['#ffffff']
    },
    formatter: function (val) {
      return val + ' deliveries'
    }
  },
  xaxis: {
    categories: sellers.value,
    title: {
      text: "Number of Deliveries",
      style: {
        color: '#6b7280',
        fontSize: '12px',
        fontFamily: 'Inter, sans-serif'
      }
    },
    labels: {
      style: { 
        colors: "#6b7280",
        fontSize: '12px',
        fontFamily: 'Inter, sans-serif'
      },
      formatter: function (val) {
        return val
      }
    },
    axisBorder: {
      show: false
    },
    axisTicks: {
      show: false
    }
  },
  yaxis: {
    labels: {
      style: { 
        colors: "#374151",
        fontSize: '13px',
        fontFamily: 'Inter, sans-serif',
        fontWeight: '500'
      },
      maxWidth: 100
    }
  },
  colors: sellerColors,
  grid: {
    show: true,
    borderColor: '#f3f4f6',
    strokeDashArray: 0,
    position: 'back',
    xaxis: {
      lines: {
        show: true
      }
    },
    yaxis: {
      lines: {
        show: false
      }
    },
    padding: {
      top: 0,
      right: 20,
      bottom: 0,
      left: 10
    }
  },
  tooltip: {
    enabled: true,
    theme: 'light',
    style: {
      fontSize: '12px',
      fontFamily: 'Inter, sans-serif'
    },
    custom: function({ series, seriesIndex, dataPointIndex, w }) {
      const seller = topSellers.value[dataPointIndex]
      return `
        <div class="px-3 py-2">
          <div class="font-semibold">${seller.name}</div>
          <div class="text-sm">
            <div>Deliveries: <span class="font-medium">${seller.deliveries}</span></div>
            <div>Success Rate: <span class="font-medium">${seller.successRate}%</span></div>
            <div>Rating: <span class="font-medium">${seller.rating}★</span></div>
          </div>
        </div>
      `
    }
  },
  legend: {
    show: false
  },
  responsive: [
    {
      breakpoint: 768,
      options: {
        plotOptions: {
          bar: {
            barHeight: '55%'
          }
        },
        dataLabels: {
          style: {
            fontSize: '12px'
          }
        }
      }
    }
  ]
})

// Watch for data changes
import { watch } from 'vue'
watch(() => props.data, (newData) => {
  if (newData.length > 0) {
    series.value = [{
      name: "Deliveries",
      data: newData.map(seller => seller.deliveries)
    }]
    
    chartOptions.value.xaxis.categories = newData.map(seller => seller.name)
  }
}, { deep: true })
</script>

<style scoped>
/* Custom styling */
.apexcharts-canvas {
  font-family: 'Inter', sans-serif;
}

/* Responsive grid adjustments */
@media (max-width: 640px) {
  .grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }
  
  .grid-cols-1.md\:grid-cols-2.lg\:grid-cols-3 {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Hover animations */
.hover\:shadow-md {
  transition: box-shadow 0.2s ease-in-out;
}
</style>