<template>
  <div v-if="reportStore.summary" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div v-for="(value, key) in reportStore.summary" :key="key" 
         class="bg-white rounded-lg shadow-sm border p-6 hover:shadow-md transition-shadow">
      <h3 class="text-sm font-medium text-gray-600 uppercase tracking-wide">
        {{ formatLabel(key) }}
      </h3>
      <p class="text-2xl font-bold text-gray-900 mt-2">{{ value }}</p>
      <div class="mt-2">
        <span class="text-xs text-gray-500">{{ reportStore.selectedReportName }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useReportStore } from '@/stores/reportStore'

const reportStore = useReportStore()

const formatLabel = (key) => {
  return key.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())
}
</script>