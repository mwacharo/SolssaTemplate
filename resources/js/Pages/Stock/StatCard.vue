<template>
  <div class="bg-white rounded-lg shadow-sm border p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-600">{{ title }}</p>
        <p class="text-3xl font-semibold text-gray-900 mt-2">{{ value }}</p>
        <div 
          v-if="change !== undefined" 
          :class="[
            'flex items-center mt-2 text-sm',
            change >= 0 ? 'text-green-600' : 'text-red-600'
          ]"
        >
          <TrendingUp v-if="change >= 0" class="h-4 w-4 mr-1" />
          <TrendingDown v-else class="h-4 w-4 mr-1" />
          <span>{{ Math.abs(change) }}% vs last month</span>
        </div>
      </div>
      <div :class="['p-3 rounded-lg', color]">
        <component 
          :is="iconComponent" 
          class="h-8 w-8 text-white" 
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { 
  TrendingUp, 
  TrendingDown, 
  DollarSign, 
  Package, 
  ShoppingCart, 
  Truck 
} from 'lucide-vue-next'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  change: {
    type: Number,
    default: undefined
  },
  icon: {
    type: String,
    required: true
  },
  color: {
    type: String,
    default: 'bg-blue-500'
  }
})

const iconComponent = computed(() => {
  const iconMap = {
    'DollarSign': DollarSign,
    'Package': Package,
    'ShoppingCart': ShoppingCart,
    'Truck': Truck
  }
  return iconMap[props.icon] || Package
})
</script>