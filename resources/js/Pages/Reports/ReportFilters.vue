<template>
  <div v-if="reportStore.selectedReportType" class="bg-white rounded-lg shadow-sm border p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Configure Filters</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Date Range -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
        <input type="date" 
               :value="reportStore.filters.dateRange?.start || ''"
               @input="updateFilter('dateRange.start', $event.target.value)"
               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
        <input type="date" 
               :value="reportStore.filters.dateRange?.end || ''"
               @input="updateFilter('dateRange.end', $event.target.value)"
               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      
      <!-- Department -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
        <select :value="reportStore.filters.department || ''" 
                @change="updateFilter('department', $event.target.value)"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Departments</option>
          <option value="sales">Sales</option>
          <option value="support">Support</option>
          <option value="marketing">Marketing</option>
          <option value="operations">Operations</option>
        </select>
      </div>
      
      <!-- Agent -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Agent</label>
        <select :value="reportStore.filters.agent || ''" 
                @change="updateFilter('agent', $event.target.value)"
                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
          <option value="">All Agents</option>
          <option value="john">John Doe</option>
          <option value="jane">Jane Smith</option>
          <option value="mike">Mike Johnson</option>
          <option value="sarah">Sarah Wilson</option>
        </select>
      </div>
    </div>
    
    <!-- Generate Button -->
    <div class="mt-6">
      <button @click="generateReport" 
              :disabled="reportStore.loading || !reportStore.isValidDateRange"
              class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
        <span v-if="reportStore.loading" class="flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Generating Report...
        </span>
        <span v-else>Generate Report</span>
      </button>
      <p v-if="!reportStore.isValidDateRange" class="text-red-600 text-sm mt-2">
        Please select a valid date range
      </p>
    </div>
  </div>
</template>

<script setup>
import { useReportStore } from '@/stores/reportStore'

const reportStore = useReportStore()

const updateFilter = (key, value) => {
  reportStore.updateFilter(key, value)
}

const generateReport = async () => {
  try {
    await reportStore.generateReport()
  } catch (error) {
    alert(error.message)
  }
}
</script>