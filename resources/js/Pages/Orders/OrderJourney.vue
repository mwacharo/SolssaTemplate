<template>
  <Dialog v-model="open">
    <DialogContent class="max-w-3xl">
      <DialogHeader>
        <DialogTitle>Order Journey - #{{ orderNo }}</DialogTitle>
        <DialogDescription>
          Track all updates and actions on this order.
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-6 max-h-[70vh] overflow-y-auto">
        <div v-for="(event, index) in events" :key="index" class="border-l-2 pl-4 relative">
          <span
            class="absolute -left-3 top-1 w-5 h-5 rounded-full flex items-center justify-center"
            :class="event.type === 'order_created' ? 'bg-green-500 text-white' : 'bg-yellow-500 text-black'"
          >
            {{ event.type === 'order_created' ? '🆕' : '✏️' }}
          </span>

          <h3 class="font-semibold">
            {{ formatType(event.type) }} ({{ formatDate(event.created_at) }})
          </h3>

          <!-- For order_created just show key details -->
          <div v-if="event.type === 'order_created'" class="text-sm mt-2 space-y-1">
            <p>Order No: {{ event.data.order_no }}</p>
            <p>Customer: {{ event.data.customer_id }}</p>
            <p>Status: {{ event.data.status_id }}</p>
            <p>
              Items:
              <span v-for="(item, i) in event.data.order_items" :key="i">
                {{ item.sku }} (qty {{ item.quantity }})
              </span>
            </p>
          </div>

          <!-- For updates show diffs -->
          <div v-if="event.type === 'order_updated'" class="text-sm mt-2 space-y-1">
            <p v-for="(change, key) in event.diff" :key="key">
              <span class="font-medium">{{ key }}:</span>
              <span v-if="change.old" class="line-through text-red-500">
                {{ change.old }}
              </span>
              <span v-if="change.old"> → </span>
              <span class="text-green-600">{{ change.new ?? 'added' }}</span>
            </p>
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="open = false">Close</Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup>

</script>
