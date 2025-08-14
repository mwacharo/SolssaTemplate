<template>
  <AppLayout>
    <Head title="Reports Dashboard" />
    
    <!-- Report Selection -->
    <ReportSelector />

    <!-- Report Filters -->
    <ReportFilters />

    <!-- Loading State -->
    <div v-if="reportStore.loading" class="flex items-center justify-center py-12">
      <div class="text-center">
        <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-gray-600">Generating your report...</p>
        <p class="text-sm text-gray-500 mt-1">This may take a few moments</p>
      </div>
    </div>

    <!-- Report Results -->
    <div v-if="reportStore.hasReportData" class="space-y-8">
      <!-- Summary Cards -->
      <ReportSummary />

      <!-- Data Table -->
      <ReportTable />
    </div>

    <!-- Empty State -->
    <EmptyState />
  </AppLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { useReportStore } from '@/stores/reportStore'
import AppLayout from "@/Layouts/AppLayout.vue";


import ReportSelector from '@/Pages/Reports/ReportSelector.vue'
import ReportFilters from '@/Pages/Reports/ReportFilters.vue'
import ReportSummary from '@/Pages/Reports/ReportSummary.vue'
import ReportTable from '@/Pages/Reports/ReportTable.vue'
import EmptyState from '@/Pages/Reports/EmptyState.vue'

const reportStore = useReportStore()
</script>