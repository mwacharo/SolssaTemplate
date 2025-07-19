<template>
  <v-dialog v-model="isOpen" max-width="500">
    <v-card>
      <v-card-title>
        <span class="text-h6">Assign {{ modeLabel }}</span>
        <v-spacer />
        <v-btn icon @click="closeDialog"><v-icon>mdi-close</v-icon></v-btn>
      </v-card-title>

      <v-card-text>
        <v-select
          v-model="selectedItem"
          :items="items"
          :label="`Select ${modeLabel}`"
          item-title="name"
          item-value="id"
          :loading="loading"
          clearable
        />
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <!-- <v-btn color="primary" @click="confirmAssign">Update</v-btn> -->
         <v-btn :disabled="!selectedItem || props.selectedOrders.length === 0" color="primary" @click="confirmAssign">Update</v-btn>

        <v-btn color="secondary" @click="closeDialog">Cancel</v-btn>
      </v-card-actions>
    </v-card>
    {{ selectedOrders }}
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
// import { useRiderStore } from '@/stores/riderStore';
// import { useAgentStore } from '@/stores/agentStore';
// import { useStatusStore } from '@/stores/statusStore';

import { useWhatsAppStore } from '@/stores/whatsappStore'


const store = useWhatsAppStore()


const props = defineProps({
  modelValue: Boolean,
  mode: {
    type: String, // 'rider' | 'agent' | 'status'
    required: true
  },
  selectedOrders: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['update:modelValue', 'confirmed']);

const isOpen = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
});

const selectedItem = ref(null);
const items = ref([]);
const loading = ref(false);

const modeLabel = computed(() => {
  return props.mode === 'rider' ? 'Rider' :
         props.mode === 'agent' ? 'Agent' : 'Status';
});

const closeDialog = () => {
  isOpen.value = false;
  selectedItem.value = null;
};

// const confirmAssign = () => {
//   emit('confirmed', {
//     mode: props.mode,
//     selected: selectedItem.value,
//     orders: props.selectedOrders
//   });
//   closeDialog();
// };


const confirmAssign = () => {
  const cleanOrders = props.selectedOrders.filter(id => Number.isInteger(id));
  console.log('Assigning', {
    mode: props.mode,
    selected: selectedItem.value,
    orders: cleanOrders
  });
  emit('confirmed', {
    mode: props.mode,
    selected: selectedItem.value,
    orders: cleanOrders
  });
  closeDialog();
};


watch(isOpen, async (val) => {
  if (val) {
    loading.value = true;
    if (props.mode === 'rider') {
    //   const store = useRiderStore();
    const store = useWhatsAppStore();
      await store.loadRiders();
      items.value = store.riders;
    } else if (props.mode === 'agent') {
      const store = useWhatsAppStore();
      await store.loadAgents();
      items.value = store.agents;
    }
    //  else if (props.mode === 'status') {
    //   const store = useStatusStore();
    //   await store.fetchStatuses();
    //   items.value = store.statuses;
    // }
    loading.value = false;
  }
});
</script>
