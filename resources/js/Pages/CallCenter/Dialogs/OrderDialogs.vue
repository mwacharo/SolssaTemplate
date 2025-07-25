<template>
  <v-dialog v-model="dialog" max-width="800" persistent>
    <v-card>
      <v-card-title>
        Order Details - {{ order?.order_no }}
      </v-card-title>
      <v-card-text>
        <v-row>
          <v-col cols="6">
            <strong>Status:</strong> {{ order?.status }}<br>
            <strong>Delivery Status:</strong> {{ order?.delivery_status }}<br>
            <strong>Platform:</strong> {{ order?.platform }}<br>
            <strong>Delivery Date:</strong> {{ formatDate(order?.delivery_date) }}<br>
            <strong>Created At:</strong> {{ order?.created_at }}
          </v-col>
          <v-col cols="6">
            <strong>Client:</strong> {{ order?.client?.name }}<br>
            <strong>Phone:</strong> {{ order?.client?.phone_number }}<br>
            <strong>City:</strong> {{ order?.client?.city }}<br>
            <strong>Vendor:</strong> {{ order?.vendor?.company_name }}<br>
            <strong>Agent:</strong> {{ order?.agent?.name }}<br>
            <strong>Rider:</strong> {{ order?.rider?.name }}
          </v-col>
        </v-row>
        <v-divider class="my-4"></v-divider>
        <h4>Order Items</h4>
        <v-simple-table>
          <thead>
            <tr>
              <th>Product</th>
              <th>Quantity</th>
              <th>Price</th>
              <th>Total Price</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in order?.orderItems || []" :key="item.id">
              <td>{{ item.product?.product_name }}</td>
              <td>{{ item.quantity }}</td>
              <td>{{ item.price }}</td>
              <td>{{ item.total_price ?? '-' }}</td>
            </tr>
          </tbody>
        </v-simple-table>
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn color="primary" @click="dialog = false">Close</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useOrderStore } from '@/stores/orderStore' // Pinia store
import { useOrderStore } from '@/stores/orderStore'

// const dialog = ref(true)



const {
  dialog,

} = toRefs(orderStore)

// Pinia store usage
const orderStore = useOrderStore()
// For demo, just use the first order in the data array
const order = computed(() => orderStore.selectedOrder)

// Helper to format date
function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString()
}
</script>
