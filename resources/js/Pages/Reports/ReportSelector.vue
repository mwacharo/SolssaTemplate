<template>
  <div class="bg-white rounded-lg shadow-sm border p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Select Report Type</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
      <div v-for="(category, key) in reportStore.availableReports" :key="key" 
           class="border rounded-lg p-4 hover:border-blue-300 transition-colors">
        <h3 class="font-medium text-gray-900 mb-3">{{ category.name }}</h3>
        <div class="space-y-2">
          <label v-for="subType in category.subTypes" :key="subType.id" 
                 class="flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded">
            <input type="radio" :value="subType.id" 
                   :checked="reportStore.selectedReportType === subType.id"
                   @change="selectReport(subType.id)"
                   class="text-blue-600 focus:ring-blue-500">
            <span class="ml-2 text-sm text-gray-700">{{ subType.name }}</span>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useReportStore } from '@/stores/reportStore'

const reportStore = useReportStore()

const selectReport = (reportId) => {
  reportStore.setReportType(reportId)
}
</script>