<template>
  <v-snackbar
    v-model="toaster.state.show"
    :color="toaster.state.color"
    :timeout="toaster.state.timeout"
    location="top right"
    multi-line
  >
    {{ toaster.state.message }}
    <template #actions>
      <v-btn icon @click="toaster.state.show = false">
        <v-icon>mdi-close</v-icon>
      </v-btn>
    </template>
  </v-snackbar>
</template>

<script setup>
import { useToaster } from '@/composables/useToaster';
const toaster = useToaster();
</script>
