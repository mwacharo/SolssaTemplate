<template>
  <div v-if="!reportStore.hasReportData && !reportStore.loading" class="text-center py-12">
    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
    </svg>
    <h3 class="text-lg font-medium text-gray-900 mb-2">No report generated yet</h3>
    <p class="text-gray-500 mb-4">
      {{ reportStore.selectedReportType 
         ? 'Configure your filters and click "Generate Report" to view data.' 
         : 'Select a report type to get started with generating comprehensive analytics.' }}
    </p>
    <div v-if="!reportStore.selectedReportType" class="space-y-2">
      <p class="text-sm text-gray-400">Available report categories:</p>
      <div class="flex flex-wrap justify-center gap-2 mt-3">
        <span v-for="(category, key) in reportStore.availableReports" :key="key"
              class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs">
          {{ category.name }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useReportStore } from '@/stores/reportStore'

const reportStore = useReportStore()
</script>

<style scoped>
/* EmptyState specific styles if needed */
.text-center {
  text-align: center;
}

.py-12 {
  padding-top: 3rem;
  padding-bottom: 3rem;
}

.mx-auto {
  margin-left: auto;
  margin-right: auto;
}

.h-12 {
  height: 3rem;
}

.w-12 {
  width: 3rem;
}

.text-gray-400 {
  color: #9ca3af;
}

.mb-4 {
  margin-bottom: 1rem;
}

.mb-2 {
  margin-bottom: 0.5rem;
}

.text-lg {
  font-size: 1.125rem;
}

.font-medium {
  font-weight: 500;
}

.text-gray-900 {
  color: #111827;
}

.text-gray-500 {
  color: #6b7280;
}

.space-y-2 > * + * {
  margin-top: 0.5rem;
}

.text-sm {
  font-size: 0.875rem;
}

.flex {
  display: flex;
}

.flex-wrap {
  flex-wrap: wrap;
}

.justify-center {
  justify-content: center;
}

.gap-2 {
  gap: 0.5rem;
}

.mt-3 {
  margin-top: 0.75rem;
}

.bg-gray-100 {
  background-color: #f3f4f6;
}

.text-gray-600 {
  color: #4b5563;
}

.px-3 {
  padding-left: 0.75rem;
  padding-right: 0.75rem;
}

.py-1 {
  padding-top: 0.25rem;
  padding-bottom: 0.25rem;
}

.rounded-full {
  border-radius: 9999px;
}

.text-xs {
  font-size: 0.75rem;
}
</style>