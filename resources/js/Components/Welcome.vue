<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useCounterStore } from '@/stores/counterStore'

const counter = useCounterStore();

</script>


<template>
  <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
    <h1 class="text-2xl font-medium text-gray-900">Pinia Test</h1>

    <p class="mt-4 text-gray-500 leading-relaxed">
      This is a test to verify that Pinia is properly installed and working in your Jetstream application.
    </p>

    <div class="mt-6">
      <p class="text-lg">Current Count: <strong>{{ counter.count }}</strong></p>
      <button
        class="mt-4 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700"
        @click="counter.increment"
      >
        Increment
      </button>
    </div>
  </div>
</template>