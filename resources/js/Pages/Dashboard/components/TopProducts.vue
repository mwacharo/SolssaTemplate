<template>
  <div class="bg-white p-6 rounded-2xl shadow-lg">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-800">Top Selling Products</h3>
        <p class="text-sm text-gray-500 mt-1">Best performing items this month</p>
      </div>
      <div class="flex items-center space-x-2">
        <div class="flex items-center text-xs text-gray-500">
          <div class="w-2 h-2 bg-green-500 rounded-full mr-1"></div>
          Units Sold
        </div>
      </div>
    </div>

    <div class="mb-4">
      <apexchart
        type="bar"
        height="380"
        :options="chartOptions"
        :series="series"
      />
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-3 gap-4 pt-4 border-t border-gray-100">
      <div class="text-center">
        <div class="text-2xl font-bold text-gray-800">{{ totalSales }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Total Sales</div>
      </div>
      <div class="text-center">
        <div class="text-2xl font-bold text-green-600">{{ topProduct.name }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Best Seller</div>
      </div>
      <div class="text-center">
        <div class="text-2xl font-bold text-blue-600">{{ averageSales }}</div>
        <div class="text-xs text-gray-500 uppercase tracking-wide">Avg Sales</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue"

// Props for external data (optional)
const props = defineProps({
  data: {
    type: Array,
    default: () => []
  }
})

// Example static data (replace with API or props later)
const defaultProducts = [
  { name: "CBD Scanner", sales: 83, category: "Electronics" },
  { name: "MK Oil", sales: 70, category: "Health" },
  { name: 'Tablet 10"', sales: 55, category: "Electronics" },
  { name: "Smartwatch", sales: 40, category: "Electronics" },
  { name: "Foot Patch", sales: 20, category: "Health" }
]

// Use props data if available, otherwise use default
const productData = computed(() => props.data.length > 0 ? props.data : defaultProducts)

// Extract products and sales for chart
const products = computed(() => productData.value.map(item => item.name))
const sales = computed(() => productData.value.map(item => item.sales))

// Computed statistics
const totalSales = computed(() => sales.value.reduce((sum, val) => sum + val, 0))
const topProduct = computed(() => {
  const maxSales = Math.max(...sales.value)
  const topIndex = sales.value.indexOf(maxSales)
  return productData.value[topIndex]
})
const averageSales = computed(() => Math.round(totalSales.value / sales.value.length))

const series = ref([
  {
    name: "Units Sold",
    data: sales.value
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
      distributed: true, // Different colors for each bar
      barHeight: '70%',
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
      return val + ' units'
    }
  },
  xaxis: {
    categories: products.value,
    labels: {
      style: { 
        colors: "#6b7280",
        fontSize: '12px',
        fontFamily: 'Inter, sans-serif'
      },
      formatter: function (val) {
        return val + ' units'
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
      maxWidth: 120
    }
  },
  colors: [
    "#3b82f6", // Blue
    "#10b981", // Green  
    "#f59e0b", // Yellow
    "#ef4444", // Red
    "#8b5cf6"  // Purple
  ],
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
    row: {
      colors: undefined,
      opacity: 0.5
    },
    column: {
      colors: undefined,
      opacity: 0.5
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
    y: {
      formatter: function (val) {
        return val + ' units sold'
      }
    },
    marker: {
      show: true
    }
  },
  legend: {
    show: false
  },
  responsive: [
    {
      breakpoint: 480,
      options: {
        plotOptions: {
          bar: {
            barHeight: '60%'
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

// Watch for data changes and update series
import { watch } from 'vue'
watch(() => props.data, (newData) => {
  if (newData.length > 0) {
    series.value = [{
      name: "Units Sold",
      data: newData.map(item => item.sales)
    }]
    
    // Update chart options with new categories
    chartOptions.value.xaxis.categories = newData.map(item => item.name)
  }
}, { deep: true })
</script>

<style scoped>
/* Custom styling for better chart appearance */
.apexcharts-canvas {
  font-family: 'Inter', sans-serif;
}

/* Ensure responsive behavior */
@media (max-width: 640px) {
  .grid-cols-3 {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}
</style>