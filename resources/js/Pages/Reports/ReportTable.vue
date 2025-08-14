<template>
  <div v-if="reportStore.hasReportData" class="bg-white rounded-lg shadow-sm border">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
      <div>
        <h2 class="text-lg font-semibold text-gray-900">{{ reportStore.selectedReportName }}</h2>
        <p class="text-sm text-gray-600 mt-1">{{ reportStore.reportData.length }} records found</p>
      </div>
      <div class="flex space-x-3">
        <button @click="exportToExcel" 
                class="bg-green-600 text-white px-4 py-2 rounded-md text-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors">
          📊 Export Excel
        </button>
        <button @click="exportToPDF" 
                class="bg-red-600 text-white px-4 py-2 rounded-md text-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors">
          📄 Export PDF
        </button>
      </div>
    </div>
    
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th v-for="(value, key) in reportStore.reportData[0]" :key="key" 
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              {{ formatLabel(key) }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(row, index) in reportStore.reportData" :key="index" 
              class="hover:bg-gray-50 transition-colors">
            <td v-for="(value, key) in row" :key="key" 
                class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              <span v-if="key.toLowerCase().includes('rate') || key.toLowerCase().includes('percent')" 
                    :class="getValueColor(value)">
                {{ value }}
              </span>
              <span v-else>{{ value }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { useReportStore } from '@/stores/reportStore'

const reportStore = useReportStore()

const formatLabel = (key) => {
  return key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())
}

const getValueColor = (value) => {
  const numValue = parseFloat(value)
  if (numValue > 90) return 'text-green-600 font-medium'
  if (numValue > 70) return 'text-yellow-600'
  return 'text-red-600'
}

const exportToExcel = async () => {
  try {
    await reportStore.exportToExcel()
  } catch (error) {
    alert('Export failed: ' + error.message)
  }
}

const exportToPDF = async () => {
  try {
    await reportStore.exportToPDF()
  } catch (error) {
    alert('PDF export failed: ' + error.message)
  }
}
</script>