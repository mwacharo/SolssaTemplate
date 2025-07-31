<template>
  <v-dialog v-model="dialog" max-width="1400" persistent scrollable>
    <v-card class="order-details-card">
      <!-- Header -->
      <v-card-title class="pa-6 pb-4 gradient-header">
        <div class="d-flex align-center w-100">
          <div class="d-flex align-center">
            <v-avatar size="48" color="primary" class="me-4">
              <v-icon :icon="isCreateMode ? 'mdi-plus' : 'mdi-package-variant'" size="24" />
            </v-avatar>
            <div>
              <h2 class="text-h5 mb-1 text-white">
                {{ isCreateMode ? 'Create New Order' : (order?.order_no || 'Loading...') }}
              </h2>
              <div class="d-flex align-center gap-2">
                <v-chip 
                  v-if="order && !isCreateMode" 
                  :color="getStatusColor(order.status)" 
                  variant="flat" 
                  size="small"
                  class="font-weight-medium"
                >
                  {{ order.status }}
                </v-chip>
                <v-chip v-if="isCreateMode" color="info" variant="flat" size="small">
                  Draft
                </v-chip>
              </div>
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <v-btn
              v-if="!isCreateMode"
              icon="mdi-refresh"
              variant="text"
              size="small"
              color="white"
              @click="refreshOrder"
              :loading="isLoading"
            />
            <v-btn
              icon="mdi-close"
              variant="text"
              size="small"
              color="white"
              @click="closeDialog"
            />
          </div>
        </div>
      </v-card-title>

      <v-divider />

      <v-card-text class="pa-0">
        <div v-if="(!order && !isCreateMode) || isLoading" class="pa-8 text-center">
          <v-progress-circular indeterminate color="primary" size="64" />
          <p class="text-h6 mt-4">{{ isCreateMode ? 'Preparing new order...' : 'Loading order details...' }}</p>
        </div>

        <div v-else>
          <!-- Tabs -->
          <v-tabs v-model="activeTab" color="primary" class="border-b bg-surface">
            <v-tab value="overview" prepend-icon="mdi-view-dashboard">
              <span class="font-weight-medium">Overview</span>
            </v-tab>
            <v-tab value="items" prepend-icon="mdi-cart">
              <span class="font-weight-medium">Items</span>
              <v-chip size="x-small" variant="flat" color="primary" class="ml-2">
                {{ (currentOrder.orderItems || []).length }}
              </v-chip>
            </v-tab>
            <v-tab value="charges" prepend-icon="mdi-currency-usd">
              <span class="font-weight-medium">Charges</span>
            </v-tab>
            <v-tab value="client" prepend-icon="mdi-account">
              <span class="font-weight-medium">Client</span>
            </v-tab>
            <v-tab value="fulfillment" prepend-icon="mdi-truck-delivery">
              <span class="font-weight-medium">Fulfillment</span>
            </v-tab>
          </v-tabs>

          <v-tabs-window v-model="activeTab">
            <!-- Overview Tab -->
            <v-tabs-window-item value="overview" class="pa-6">
              <v-row>
                <!-- Order Summary -->
                <v-col cols="12" lg="8">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center">
                        <v-icon icon="mdi-clipboard-text" class="me-2" color="primary" />
                        Order Details
                      </div>
                      <div class="d-flex gap-2" v-if="!isCreateMode">
                        <v-btn
                          v-if="!editingOrder"
                          color="primary"
                          variant="outlined"
                          size="small"
                          @click="startEditingOrder"
                        >
                          <v-icon start>mdi-pencil</v-icon>
                          Edit
                        </v-btn>
                        <div v-else class="d-flex gap-2">
                          <v-btn
                            color="success"
                            variant="flat"
                            size="small"
                            @click="saveOrderChanges"
                            :loading="saving"
                          >
                            <v-icon start>mdi-check</v-icon>
                            Save
                          </v-btn>
                          <v-btn
                            variant="outlined"
                            size="small"
                            @click="cancelOrderEdit"
                          >
                            Cancel
                          </v-btn>
                        </div>
                      </div>
                    </v-card-title>
                    <v-card-text>
                      <v-row>
                        <v-col cols="12" md="6">
                          <div class="info-grid">
                            <div class="info-item">
                              <div class="label">Order Number</div>
                              <v-text-field
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.order_no"
                                variant="outlined"
                                density="compact"
                                placeholder="AUTO-GENERATED"
                                :disabled="!isCreateMode"
                              />
                              <div v-else class="value">{{ currentOrder.order_no }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Reference</div>
                              <v-text-field
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.reference"
                                variant="outlined"
                                density="compact"
                                placeholder="Optional reference"
                              />
                              <div v-else class="value">{{ currentOrder.reference || '-' }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Platform</div>
                              <v-select
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.platform"
                                :items="platformOptions"
                                variant="outlined"
                                density="compact"
                              />
                              <v-chip v-else variant="outlined" size="small">{{ currentOrder.platform }}</v-chip>
                            </div>
                            <div class="info-item">
                              <div class="label">Status</div>
                              <v-select
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.status"
                                :items="statusOptions"
                                variant="outlined"
                                density="compact"
                              />
                              <v-chip v-else :color="getStatusColor(currentOrder.status)" variant="flat" size="small">
                                {{ currentOrder.status || 'Pending' }}
                              </v-chip>
                            </div>
                          </div>
                        </v-col>
                        <v-col cols="12" md="6">
                          <div class="info-grid">
                            <div class="info-item">
                              <div class="label">Client</div>
                              <v-autocomplete
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.client_id"
                                :items="clientOptions"
                                item-title="name"
                                item-value="id"
                                variant="outlined"
                                density="compact"
                                clearable
                                :loading="loadingClients"
                                @update:search="searchClients"
                              />
                              <div v-else class="value">{{ currentOrder.client?.name || '-' }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Vendor</div>
                              <v-autocomplete
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.vendor_id"
                                :items="vendorOptions"
                                item-title="company_name"
                                item-value="id"
                                variant="outlined"
                                density="compact"
                                clearable
                                :loading="loadingVendors"
                                @update:model-value="onVendorChange"
                              />
                              <div v-else class="value">{{ currentOrder.vendor?.company_name || '-' }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Delivery Date</div>
                              <v-text-field
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.delivery_date"
                                type="datetime-local"
                                variant="outlined"
                                density="compact"
                              />
                              <div v-else class="value">{{ formatDateTime(currentOrder.delivery_date) }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Weight (kg)</div>
                              <v-text-field
                                v-if="isCreateMode || editingOrder"
                                v-model="orderEdit.weight"
                                type="number"
                                step="0.1"
                                variant="outlined"
                                density="compact"
                              />
                              <div v-else class="value">{{ currentOrder.weight || 0 }} kg</div>
                            </div>
                          </div>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Quick Actions -->
                <v-col cols="12" lg="4">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title>
                      <v-icon icon="mdi-flash" class="me-2" color="primary" />
                      {{ isCreateMode ? 'Create Actions' : 'Quick Actions' }}
                    </v-card-title>
                    <v-card-text>
                      <div class="d-flex flex-column gap-3">
                        <v-btn
                          v-if="isCreateMode"
                          color="success"
                          variant="flat"
                          block
                          @click="createOrder"
                          :loading="saving"
                        >
                          <v-icon start>mdi-plus</v-icon>
                          Create Order
                        </v-btn>
                        <template v-else>
                          <v-btn
                            color="primary"
                            variant="flat"
                            block
                            @click="callClient"
                            :disabled="!currentOrder.client?.phone_number"
                          >
                            <v-icon start>mdi-phone</v-icon>
                            Call Client
                          </v-btn>
                          <v-btn
                            color="success"
                            variant="outlined"
                            block
                            @click="updateStatus('confirmed')"
                          >
                            <v-icon start>mdi-check-circle</v-icon>
                            Confirm Order
                          </v-btn>
                          <v-btn
                            color="info"
                            variant="outlined"
                            block
                            @click="printOrder"
                          >
                            <v-icon start>mdi-printer</v-icon>
                            Print Order
                          </v-btn>
                          <v-btn
                            color="warning"
                            variant="outlined"
                            block
                            @click="updateStatus('cancelled')"
                          >
                            <v-icon start>mdi-cancel</v-icon>
                            Cancel Order
                          </v-btn>
                        </template>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            <!-- Order Items Tab -->
            <v-tabs-window-item value="items" class="pa-6">
              <v-card variant="outlined">
                <v-card-title class="d-flex align-center justify-space-between">
                  <div class="d-flex align-center">
                    <v-icon icon="mdi-cart" class="me-2" color="primary" />
                    Order Items
                    <v-chip variant="outlined" size="small" class="ml-3">
                      {{ (currentOrder.orderItems || []).length }} items
                    </v-chip>
                  </div>
                  <div class="d-flex gap-2">
                    <v-btn
                      v-if="!editingItems && !isCreateMode"
                      color="primary"
                      variant="outlined"
                      size="small"
                      @click="startEditingItems"
                    >
                      <v-icon start>mdi-pencil</v-icon>
                      Edit Items
                    </v-btn>
                    <v-btn
                      color="success"
                      variant="outlined"
                      size="small"
                      @click="addNewItem"
                      :disabled="!editingItems && !isCreateMode"
                    >
                      <v-icon start>mdi-plus</v-icon>
                      Add Item
                    </v-btn>
                    <div v-if="editingItems" class="d-flex gap-2">
                      <v-btn
                        color="success"
                        variant="flat"
                        size="small"
                        @click="saveItemsChanges"
                        :loading="saving"
                      >
                        <v-icon start>mdi-check</v-icon>
                        Save All
                      </v-btn>
                      <v-btn
                        variant="outlined"
                        size="small"
                        @click="cancelItemsEdit"
                      >
                        Cancel
                      </v-btn>
                    </div>
                  </div>
                </v-card-title>
                <v-card-text class="pa-0">
                  <v-data-table
                    :headers="itemHeaders"
                    :items="editingItems || isCreateMode ? itemsEdit : (currentOrder.orderItems || [])"
                    class="order-items-table"
                    :loading="saving"
                    loading-text="Updating items..."
                  >
                    <template #item.product="{ item }">
                      <div v-if="editingItems || isCreateMode" class="d-flex align-center gap-2">
                        <v-autocomplete
                          v-model="item.product_id"
                          :items="productOptions"
                          item-title="product_name"
                          item-value="id"
                          variant="outlined"
                          density="compact"
                          style="min-width: 200px;"
                          :loading="loadingProducts"
                          @update:model-value="onProductChange(item)"
                        />
                      </div>
                      <div v-else class="d-flex align-center">
                        <v-avatar size="40" class="me-3" rounded>
                          <v-img 
                            :src="item.product?.image_url || '/api/placeholder/40/40'" 
                            :alt="item.product?.product_name"
                          />
                        </v-avatar>
                        <div>
                          <div class="text-subtitle-2">{{ item.product?.product_name || '-' }}</div>
                          <div class="text-caption text-medium-emphasis">{{ item.product?.sku || '-' }}</div>
                        </div>
                      </div>
                    </template>

                    <template #item.quantity="{ item }">
                      <v-text-field
                        v-if="editingItems || isCreateMode"
                        v-model.number="item.quantity"
                        type="number"
                        variant="outlined"
                        density="compact"
                        style="width: 80px;"
                        @input="updateItemTotal(item)"
                      />
                      <v-chip v-else variant="outlined" size="small">
                        {{ item.quantity || 0 }}
                      </v-chip>
                    </template>

                    <template #item.price="{ item }">
                      <v-text-field
                        v-if="editingItems || isCreateMode"
                        v-model.number="item.price"
                        type="number"
                        step="0.01"
                        variant="outlined"
                        density="compact"
                        prefix="$"
                        style="width: 100px;"
                        @input="updateItemTotal(item)"
                      />
                      <div v-else class="text-body-2">{{ formatCurrency(item.price) }}</div>
                    </template>

                    <template #item.discount="{ item }">
                      <v-text-field
                        v-if="editingItems || isCreateMode"
                        v-model.number="item.discount"
                        type="number"
                        step="0.01"
                        variant="outlined"
                        density="compact"
                        prefix="$"
                        style="width: 100px;"
                        @input="updateItemTotal(item)"
                      />
                      <div v-else class="text-body-2">{{ formatCurrency(item.discount || 0) }}</div>
                    </template>

                    <template #item.total_price="{ item }">
                      <div class="text-subtitle-2 font-weight-medium">
                        {{ formatCurrency(item.total_price) }}
                      </div>
                    </template>

                    <template #item.actions="{ item }">
                      <div v-if="editingItems || isCreateMode" class="d-flex gap-1">
                        <v-btn
                          icon="mdi-content-duplicate"
                          variant="text"
                          size="small"
                          @click="duplicateItem(item)"
                        />
                        <v-btn
                          icon="mdi-delete"
                          variant="text"
                          size="small"
                          color="error"
                          @click="removeItem(item)"
                        />
                      </div>
                    </template>

                    <template #bottom>
                      <div class="d-flex justify-end pa-4 bg-surface-variant">
                        <div class="text-h6 font-weight-bold">
                          Total: {{ formatCurrency(calculateItemsTotal()) }}
                        </div>
                      </div>
                    </template>
                  </v-data-table>
                </v-card-text>
              </v-card>
            </v-tabs-window-item>

            <!-- Charges Tab -->
            <v-tabs-window-item value="charges" class="pa-6">
              <v-row>
                <v-col cols="12" md="8">
                  <v-card variant="outlined">
                    <v-card-title class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center">
                        <v-icon icon="mdi-calculator" class="me-2" color="primary" />
                        Pricing Breakdown
                      </div>
                      <v-btn
                        v-if="!editingCharges && !isCreateMode"
                        color="primary"
                        variant="outlined"
                        size="small"
                        @click="startEditingCharges"
                      >
                        <v-icon start>mdi-pencil</v-icon>
                        Edit Charges
                      </v-btn>
                      <div v-else-if="editingCharges" class="d-flex gap-2">
                        <v-btn
                          color="success"
                          variant="flat"
                          size="small"
                          @click="saveChargesChanges"
                          :loading="saving"
                        >
                          <v-icon start>mdi-check</v-icon>
                          Save
                        </v-btn>
                        <v-btn
                          variant="outlined"
                          size="small"
                          @click="cancelChargesEdit"
                        >
                          Cancel
                        </v-btn>
                      </div>
                    </v-card-title>
                    <v-card-text>
                      <div class="charges-breakdown">
                        <div class="charge-item">
                          <div class="charge-label">Subtotal (Items)</div>
                          <div class="charge-value">{{ formatCurrency(calculateItemsTotal()) }}</div>
                        </div>
                        
                        <div class="charge-item">
                          <div class="charge-label">Shipping Charges</div>
                          <div class="charge-value">
                            <v-text-field
                              v-if="editingCharges || isCreateMode"
                              v-model.number="chargesEdit.shipping_charges"
                              type="number"
                              step="0.01"
                              variant="outlined"
                              density="compact"
                              prefix="$"
                              style="width: 120px;"
                            />
                            <span v-else>{{ formatCurrency(currentOrder.shipping_charges || 0) }}</span>
                          </div>
                        </div>

                        <div class="charge-item">
                          <div class="charge-label">Other Charges</div>
                          <div class="charge-value">
                            <v-text-field
                              v-if="editingCharges || isCreateMode"
                              v-model.number="chargesEdit.charges"
                              type="number"
                              step="0.01"
                              variant="outlined"
                              density="compact"
                              prefix="$"
                              style="width: 120px;"
                            />
                            <span v-else>{{ formatCurrency(currentOrder.charges || 0) }}</span>
                          </div>
                        </div>

                        <div class="charge-item">
                          <div class="charge-label">Discount</div>
                          <div class="charge-value text-success">
                            <v-text-field
                              v-if="editingCharges || isCreateMode"
                              v-model.number="chargesEdit.discount"
                              type="number"
                              step="0.01"
                              variant="outlined"
                              density="compact"
                              prefix="-$"
                              style="width: 120px;"
                            />
                            <span v-else>-{{ formatCurrency(currentOrder.discount || 0) }}</span>
                          </div>
                        </div>

                        <v-divider class="my-3" />

                        <div class="charge-item total-charge">
                          <div class="charge-label text-h6">Total Amount</div>
                          <div class="charge-value text-h6 text-primary">
                            {{ formatCurrency(calculateTotalWithCharges()) }}
                          </div>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" md="4">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-credit-card" class="me-2" color="primary" />
                      Payment Information
                    </v-card-title>
                    <v-card-text>
                      <div class="payment-info">
                        <div class="info-item">
                          <div class="label">Payment Method</div>
                          <v-select
                            v-if="editingCharges || isCreateMode"
                            v-model="chargesEdit.payment_method"
                            :items="paymentMethods"
                            variant="outlined"
                            density="compact"
                          />
                          <v-chip v-else variant="outlined" size="small">
                            {{ currentOrder.payment_method || 'Cash on Delivery' }}
                          </v-chip>
                        </div>
                        <div class="info-item">
                          <div class="label">Amount Paid</div>
                          <v-text-field
                            v-if="editingCharges || isCreateMode"
                            v-model.number="chargesEdit.amount_paid"
                            type="number"
                            step="0.01"
                            variant="outlined"
                            density="compact"
                            prefix="$"
                          />
                          <div v-else class="value text-success">{{ formatCurrency(currentOrder.amount_paid || 0) }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Outstanding</div>
                          <div class="value text-error">
                            {{ formatCurrency(calculateTotalWithCharges() - (currentOrder.amount_paid || 0)) }}
                          </div>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            <!-- Client Tab -->
            <v-tabs-window-item value="client" class="pa-6">
              <v-card variant="outlined">
                <v-card-title class="d-flex align-center justify-space-between">
                  <div class="d-flex align-center">
                    <v-avatar size="48" color="primary" class="me-3">
                      <v-icon icon="mdi-account" size="24" />
                    </v-avatar>
                    <div>
                      <div class="text-h6">{{ currentOrder.client?.name || 'Select Client' }}</div>
                      <div class="text-caption text-medium-emphasis">
                        Client ID: {{ currentOrder.client?.id || '-' }}
                      </div>
                    </div>
                  </div>
                  <div class="d-flex gap-2">
                    <v-btn
                      v-if="currentOrder.client?.phone_number"
                      color="primary"
                      variant="outlined"
                      size="small"
                      @click="callClient"
                    >
                      <v-icon start>mdi-phone</v-icon>
                      Call
                    </v-btn>
                    <v-btn
                      v-if="!editingClient && !isCreateMode"
                      color="primary"
                      variant="outlined"
                      size="small"
                      @click="startEditingClient"
                    >
                      <v-icon start>mdi-pencil</v-icon>
                      Edit
                    </v-btn>
                    <div v-else-if="editingClient" class="d-flex gap-2">
                      <v-btn
                        color="success"
                        variant="flat"
                        size="small"
                        @click="saveClientChanges"
                        :loading="saving"
                      >
                        <v-icon start>mdi-check</v-icon>
                        Save
                      </v-btn>
                      <v-btn
                        variant="outlined"
                        size="small"
                        @click="cancelClientEdit"
                      >
                        Cancel
                      </v-btn>
                    </div>
                  </div>
                </v-card-title>
                
                <v-card-text v-if="currentOrder.client || isCreateMode">
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="info-grid">
                        <div class="info-item">
                          <div class="label">Full Name</div>
                          <v-text-field
                            v-if="editingClient || isCreateMode"
                            v-model="clientEdit.name"
                            variant="outlined"
                            density="compact"
                          />
                          <div v-else class="value">{{ currentOrder.client?.name || '-' }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Phone Number</div>
                          <v-text-field
                            v-if="editingClient || isCreateMode"
                            v-model="clientEdit.phone_number"
                            variant="outlined"
                            density="compact"
                          />
                          <div v-else class="value">
                            <a :href="`tel:${currentOrder.client?.phone_number}`" class="text-decoration-none">
                              {{ currentOrder.client?.phone_number || '-' }}
                            </a>
                          </div>
                        </div>
                        <div class="info-item">
                          <div class="label">Email</div>
                          <v-text-field
                            v-if="editingClient || isCreateMode"
                            v-model="clientEdit.email"
                            variant="outlined"
                            density="compact"
                          />
                          <div v-else class="value">
                            <a :href="`mailto:${currentOrder.client?.email}`" class="text-decoration-none">
                              {{ currentOrder.client?.email || '-' }}
                            </a>
                          </div>
                        </div>
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="info-grid">
                        <div class="info-item">
                          <div class="label">Delivery Address</div>
                          <v-textarea
                            v-if="editingClient || isCreateMode"
                            v-model="clientEdit.address"
                            variant="outlined"
                            density="compact"
                            rows="3"
                          />
                          <div v-else class="value">{{ currentOrder.client?.address || '-' }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">City</div>
                          <v-text-field
                            v-if="editingClient || isCreateMode"
                            v-model="clientEdit.city"
                            variant="outlined"
                            density="compact"
                          />
                          <div v-else class="value">{{ currentOrder.client?.city || '-' }}</div>
                        </div>
                      </div>
                    </v-col>
                  </v-row>
                </v-card-text>
                
                <v-card-text v-else class="text-center py-8">
                  <v-icon icon="mdi-account-off" size="48" class="text-medium-emphasis mb-3" />
                  <p class="text-body-2 text-medium-emphasis">No client selected</p>
                  <v-btn color="primary" variant="outlined" size="small" class="mt-2" @click="activeTab = 'overview'">
                    <v-icon start>mdi-plus</v-icon>
                    Select Client
                  </v-btn>
                </v-card-text>
              </v-card>
            </v-tabs-window-item>

            <!-- Fulfillment Tab -->
            <v-tabs-window-item value="fulfillment" class="pa-6">
              <v-row>
                <!-- Vendor Information -->
                <v-col cols="12" lg="4">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title class="d-flex align-center">
                      <v-avatar size="40" color="secondary" class="me-3">
                        <v-icon icon="mdi-store" />
                      </v-avatar>
                      <div>
                        <div class="text-subtitle-1">Vendor</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ currentOrder.vendor?.company_name || 'No vendor assigned' }}
                        </div>
                      </div>
                    </v-card-title>
                    <v-card-text v-if="currentOrder.vendor">
                      <div class="info-grid">
                        <div class="info-item">
                          <div class="label">Company</div>
                          <div class="value">{{ currentOrder.vendor.company_name }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Contact Person</div>
                          <div class="value">{{ currentOrder.vendor.name }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Phone</div>
                          <div class="value">
                            <a :href="`tel:${currentOrder.vendor.phone}`" class="text-decoration-none">
                              {{ currentOrder.vendor.phone }}
                            </a>
                          </div>
                        </div>
                        <div class="info-item">
                          <div class="label">Email</div>
                          <div class="value">
                            <a :href="`mailto:${currentOrder.vendor.email}`" class="text-decoration-none">
                              {{ currentOrder.vendor.email }}
                            </a>
                          </div>
                        </div>
                      </div>
                    </v-card-text>
                    <v-card-text v-else class="text-center py-8">
                      <v-icon icon="mdi-store-off" size="48" class="text-medium-emphasis mb-3" />
                      <p class="text-body-2 text-medium-emphasis">No vendor assigned</p>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Agent Information -->
                <v-col cols="12" lg="4">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title class="d-flex align-center">
                      <v-avatar size="40" color="info" class="me-3">
                        <v-icon icon="mdi-account-tie" />
                      </v-avatar>
                      <div>
                        <div class="text-subtitle-1">Agent</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ currentOrder.agent?.name || 'No agent assigned' }}
                        </div>
                      </div>
                    </v-card-title>
                    <v-card-text v-if="currentOrder.agent">
                      <div class="info-grid">
                        <div class="info-item">
                          <div class="label">Name</div>
                          <div class="value">{{ currentOrder.agent.name }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Employee ID</div>
                          <div class="value">{{ currentOrder.agent.employee_id }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Phone</div>
                          <div class="value">
                            <a :href="`tel:${currentOrder.agent.phone}`" class="text-decoration-none">
                              {{ currentOrder.agent.phone }}
                            </a>
                          </div>
                        </div>
                        <div class="info-item">
                          <div class="label">Department</div>
                          <div class="value">{{ currentOrder.agent.department }}</div>
                        </div>
                      </div>
                    </v-card-text>
                    <v-card-text v-else class="text-center py-8">
                      <v-icon icon="mdi-account-off" size="48" class="text-medium-emphasis mb-3" />
                      <p class="text-body-2 text-medium-emphasis">No agent assigned</p>
                      <v-select
                        v-if="isCreateMode || editingOrder"
                        v-model="orderEdit.agent_id"
                        :items="agentOptions"
                        item-title="name"
                        item-value="id"
                        variant="outlined"
                        density="compact"
                        label="Select Agent"
                        class="mt-2"
                      />
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Rider Information -->
                <v-col cols="12" lg="4">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title class="d-flex align-center">
                      <v-avatar size="40" color="warning" class="me-3">
                        <v-icon icon="mdi-motorbike" />
                      </v-avatar>
                      <div>
                        <div class="text-subtitle-1">Rider</div>
                        <div class="text-caption text-medium-emphasis">
                          {{ currentOrder.rider?.name || 'No rider assigned' }}
                        </div>
                      </div>
                    </v-card-title>
                    <v-card-text v-if="currentOrder.rider">
                      <div class="info-grid">
                        <div class="info-item">
                          <div class="label">Name</div>
                          <div class="value">{{ currentOrder.rider.name }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Phone</div>
                          <div class="value">
                            <a :href="`tel:${currentOrder.rider.phone}`" class="text-decoration-none">
                              {{ currentOrder.rider.phone }}
                            </a>
                          </div>
                        </div>
                        <div class="info-item">
                          <div class="label">Vehicle</div>
                          <div class="value">{{ currentOrder.rider.vehicle_type }} - {{ currentOrder.rider.vehicle_number }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Status</div>
                          <v-chip :color="getRiderStatusColor(currentOrder.rider.status)" variant="flat" size="small">
                            {{ currentOrder.rider.status }}
                          </v-chip>
                        </div>
                      </div>
                    </v-card-text>
                    <v-card-text v-else class="text-center py-8">
                      <v-icon icon="mdi-motorbike-off" size="48" class="text-medium-emphasis mb-3" />
                      <p class="text-body-2 text-medium-emphasis">No rider assigned</p>
                      <v-select
                        v-if="isCreateMode || editingOrder"
                        v-model="orderEdit.rider_id"
                        :items="riderOptions"
                        item-title="name"
                        item-value="id"
                        variant="outlined"
                        density="compact"
                        label="Select Rider"
                        class="mt-2"
                      />
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>
          </v-tabs-window>
        </div>
      </v-card-text>

      <!-- Actions -->
      <v-divider />
      <v-card-actions class="pa-6">
        <div v-if="isCreateMode" class="d-flex w-100 gap-3">
          <v-btn
            variant="outlined"
            @click="resetForm"
          >
            <v-icon icon="mdi-refresh" start />
            Reset Form
          </v-btn>
          <v-spacer />
          <v-btn
            variant="outlined"
            @click="closeDialog"
          >
            Cancel
          </v-btn>
          <v-btn
            color="success"
            variant="flat"
            @click="createOrder"
            :loading="saving"
            :disabled="!canCreateOrder"
          >
            <v-icon icon="mdi-plus" start />
            Create Order
          </v-btn>
        </div>
        
        <div v-else class="d-flex w-100 gap-3">
          <v-btn
            color="error"
            variant="outlined"
            @click="deleteOrder"
            :disabled="!order"
          >
            <v-icon icon="mdi-delete" start />
            Delete Order
          </v-btn>
          <v-btn
            color="info"
            variant="outlined"
            @click="duplicateOrder"
            :disabled="!order"
          >
            <v-icon icon="mdi-content-duplicate" start />
            Duplicate Order
          </v-btn>
          <v-spacer />
          <v-btn
            variant="outlined"
            @click="printOrder"
            :disabled="!order"
            class="me-3"
          >
            <v-icon icon="mdi-printer" start />
            Print
          </v-btn>
          <v-btn
            color="primary"
            variant="flat"
            @click="saveAllChanges"
            :disabled="!order || !hasChanges"
            :loading="saving"
          >
            <v-icon icon="mdi-content-save" start />
            Save All Changes
          </v-btn>
        </div>
      </v-card-actions>
    </v-card>

    <!-- Confirmation Dialogs -->
    <v-dialog v-model="deleteConfirmDialog" max-width="400">
      <v-card>
        <v-card-title class="text-h6">
          <v-icon icon="mdi-alert" color="error" class="me-2" />
          Confirm Delete
        </v-card-title>
        <v-card-text>
          Are you sure you want to delete this order? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteConfirmDialog = false">Cancel</v-btn>
          <v-btn color="error" variant="flat" @click="confirmDelete" :loading="saving">
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-dialog>
</template>

<script setup>
import { computed, toRefs, ref, watch, nextTick } from 'vue'
import { useOrderStore } from '@/stores/orderStore'

// Props
const props = defineProps({
  createMode: {
    type: Boolean,
    default: false
  }
})

// Pinia store usage
const orderStore = useOrderStore()
const { dialog } = toRefs(orderStore)

// Local state
const activeTab = ref('overview')
const saving = ref(false)
const deleteConfirmDialog = ref(false)

// Loading states
const loadingClients = ref(false)
const loadingVendors = ref(false)
const loadingProducts = ref(false)

// Editing states
const editingOrder = ref(false)
const editingClient = ref(false)
const editingItems = ref(false)
const editingCharges = ref(false)

// Edit data
const orderEdit = ref({})
const clientEdit = ref({})
const itemsEdit = ref([])
const chargesEdit = ref({})

// Create mode state
const isCreateMode = ref(props.createMode)

// Options
const clientOptions = ref([])
const vendorOptions = ref([])
const productOptions = ref([])
const agentOptions = ref([])
const riderOptions = ref([])

const platformOptions = ['Website', 'Mobile App', 'Phone', 'WhatsApp', 'Facebook', 'Instagram']
const statusOptions = ['Inprogress', 'active', 'inactive']
const paymentMethods = ['Cash on Delivery', 'Card', 'Bank Transfer', 'Mobile Money', 'PayPal']

// Item table headers
const itemHeaders = [
  { title: 'Product', key: 'product', sortable: false },
  { title: 'Quantity', key: 'quantity', align: 'center' },
  { title: 'Unit Price', key: 'price', align: 'end' },
  { title: 'Discount', key: 'discount', align: 'end' },
  { title: 'Total', key: 'total_price', align: 'end' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'center' }
]

// Computed properties
const order = computed(() => orderStore.selectedOrder)
const isLoading = computed(() => orderStore.loading.orders)

const currentOrder = computed(() => {
  if (isCreateMode.value) {
    return {
      ...orderEdit.value,
      orderItems: itemsEdit.value,
      client: clientOptions.value.find(c => c.id === orderEdit.value.client_id),
      vendor: vendorOptions.value.find(v => v.id === orderEdit.value.vendor_id),
      agent: agentOptions.value.find(a => a.id === orderEdit.value.agent_id),
      rider: riderOptions.value.find(r => r.id === orderEdit.value.rider_id)
    }
  }
  return order.value || {}
})

const hasChanges = computed(() => {
  return editingOrder.value || editingClient.value || editingItems.value || editingCharges.value || isCreateMode.value
})

const canCreateOrder = computed(() => {
  return isCreateMode.value && 
         orderEdit.value.client_id && 
         itemsEdit.value.length > 0 && 
         itemsEdit.value.every(item => item.product_id && item.quantity > 0)
})

// Initialize create mode
watch(() => props.createMode, (newVal) => {
  isCreateMode.value = newVal
  if (newVal) {
    initializeCreateMode()
  }
}, { immediate: true })

// Initialize create mode data
function initializeCreateMode() {
  orderEdit.value = {
    order_no: '',
    reference: '',
    platform: 'Website',
    status: 'Inprogress',
    client_id: null,
    vendor_id: null,
    agent_id: null,
    rider_id: null,
    delivery_date: null,
    weight: 0,
    customer_notes: ''
  }
  
  itemsEdit.value = []
  
  chargesEdit.value = {
    shipping_charges: 0,
    charges: 0,
    discount: 0,
    payment_method: 'Cash on Delivery',
    amount_paid: 0
  }
  
  clientEdit.value = {
    name: '',
    phone_number: '',
    email: '',
    address: '',
    city: ''
  }
  
  // Load options
  loadAllOptions()
}

// Load all dropdown options
async function loadAllOptions() {
  try {
    await Promise.all([
      loadClients(),
      loadVendors(),
      loadAgents(),
      loadRiders()
    ])
  } catch (error) {
    console.error('Error loading options:', error)
  }
}

// Load clients
async function loadClients(search = '') {
  loadingClients.value = true
  try {
    const response = await fetch(`/api/clients?search=${search}&limit=50`)
    if (response.ok) {
      const data = await response.json()
      clientOptions.value = data.clients || data
    }
  } catch (error) {
    console.error('Error loading clients:', error)
  } finally {
    loadingClients.value = false
  }
}

// Load vendors
async function loadVendors() {
  loadingVendors.value = true
  try {
    const response = await fetch('/api/v1/vendors')
    if (response.ok) {
      const data = await response.json()
      vendorOptions.value = data.vendors || data
    }
  } catch (error) {
    console.error('Error loading vendors:', error)
  } finally {
    loadingVendors.value = false
  }
}

// Load agents
async function loadAgents() {
  try {
    const response = await fetch('/api/v1/agents')
    if (response.ok) {
      const data = await response.json()
      agentOptions.value = data.agents || data
    }
  } catch (error) {
    console.error('Error loading agents:', error)
  }
}

// Load riders
async function loadRiders() {
  try {
    const response = await fetch('/api/v1/riders')
    if (response.ok) {
      const data = await response.json()
      riderOptions.value = data.riders || data
    }
  } catch (error) {
    console.error('Error loading riders:', error)
  }
}

// Load products by vendor
async function loadProductsByVendor(vendorId) {
  if (!vendorId) {
    productOptions.value = []
    return
  }
  
  loadingProducts.value = true
  try {
    const response = await fetch(`/api/products/vendor/${vendorId}`)
    if (response.ok) {
      const data = await response.json()
      productOptions.value = data.products || data
    }
  } catch (error) {
    console.error('Error loading products:', error)
  } finally {
    loadingProducts.value = false
  }
}

// Search clients
function searchClients(search) {
  if (search && search.length > 2) {
    loadClients(search)
  }
}

// Vendor change handler
function onVendorChange(vendorId) {
  loadProductsByVendor(vendorId)
  // Clear existing items when vendor changes
  if (isCreateMode.value || editingItems.value) {
    itemsEdit.value = []
  }
}

// Product change handler
function onProductChange(item) {
  const product = productOptions.value.find(p => p.id === item.product_id)
  if (product) {
    item.price = product.price || 0
    item.product = product
    updateItemTotal(item)
  }
}

// Helper functions
function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString()
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString()
}

function formatCurrency(amount) {
  if (amount == null) return '$0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

// Color helper functions
function getStatusColor(status) {
  const colors = {
    'pending': 'warning',
    'inprogress': 'info',
    'active': 'success',
    'confirmed': 'success',
    'processing': 'primary',
    'completed': 'success',
    'cancelled': 'error',
    'inactive': 'error'
  }
  if (typeof status === 'string') {
    return colors[status.toLowerCase()] || 'default'
  }
  return 'default'
}

function getRiderStatusColor(status) {
  const colors = {
    'available': 'success',
    'busy': 'warning',
    'offline': 'error',
    'on_delivery': 'primary'
  }
  return colors[status?.toLowerCase()] || 'default'
}

// Order editing functions
function startEditingOrder() {
  editingOrder.value = true
  orderEdit.value = {
    reference: order.value.reference,
    platform: order.value.platform,
    status: order.value.status,
    client_id: order.value.client_id,
    vendor_id: order.value.vendor_id,
    agent_id: order.value.agent_id,
    rider_id: order.value.rider_id,
    delivery_date: order.value.delivery_date,
    weight: order.value.weight,
    customer_notes: order.value.customer_notes
  }
}

function cancelOrderEdit() {
  editingOrder.value = false
  orderEdit.value = {}
}

async function saveOrderChanges() {
  saving.value = true
  try {
    await orderStore.updateOrder(order.value.id, orderEdit.value)
    editingOrder.value = false
    orderEdit.value = {}
  } catch (error) {
    console.error('Failed to save order changes:', error)
  } finally {
    saving.value = false
  }
}

// Client editing functions
function startEditingClient() {
  editingClient.value = true
  clientEdit.value = {
    name: order.value.client?.name || '',
    phone_number: order.value.client?.phone_number || '',
    email: order.value.client?.email || '',
    address: order.value.client?.address || '',
    city: order.value.client?.city || ''
  }
}

function cancelClientEdit() {
  editingClient.value = false
  clientEdit.value = {}
}

async function saveClientChanges() {
  saving.value = true
  try {
    await orderStore.updateClient(order.value.client.id, clientEdit.value)
    editingClient.value = false
    clientEdit.value = {}
  } catch (error) {
    console.error('Failed to save client changes:', error)
  } finally {
    saving.value = false
  }
}

// Items editing functions
function startEditingItems() {
  editingItems.value = true
  itemsEdit.value = JSON.parse(JSON.stringify(order.value.orderItems || []))
}

function cancelItemsEdit() {
  editingItems.value = false
  itemsEdit.value = []
}

async function saveItemsChanges() {
  saving.value = true
  try {
    await orderStore.updateOrderItems(order.value.id, itemsEdit.value)
    editingItems.value = false
    itemsEdit.value = []
  } catch (error) {
    console.error('Failed to save items changes:', error)
  } finally {
    saving.value = false
  }
}

function addNewItem() {
  const newItem = {
    id: `temp-${Date.now()}`,
    product_id: null,
    quantity: 1,
    price: 0,
    discount: 0,
    total_price: 0,
    product: { product_name: '', sku: '' }
  }
  itemsEdit.value.push(newItem)
}

function duplicateItem(item) {
  const newItem = { ...item, id: `temp-${Date.now()}-${Math.random()}` }
  const index = itemsEdit.value.findIndex(i => i.id === item.id)
  itemsEdit.value.splice(index + 1, 0, newItem)
}

function removeItem(item) {
  const index = itemsEdit.value.findIndex(i => i.id === item.id)
  if (index > -1) {
    itemsEdit.value.splice(index, 1)
  }
}

function updateItemTotal(item) {
  const quantity = Number(item.quantity) || 0
  const price = Number(item.price) || 0
  const discount = Number(item.discount) || 0
  item.total_price = (quantity * price) - discount
}

function calculateItemsTotal() {
  const items = (editingItems.value || isCreateMode.value) ? itemsEdit.value : (currentOrder.value.orderItems || [])
  return items.reduce((sum, item) => sum + (item.total_price || 0), 0)
}

// Charges editing functions
function startEditingCharges() {
  editingCharges.value = true
  chargesEdit.value = {
    shipping_charges: order.value.shipping_charges || 0,
    charges: order.value.charges || 0,
    discount: order.value.discount || 0,
    payment_method: order.value.payment_method || 'Cash on Delivery',
    amount_paid: order.value.amount_paid || 0
  }
}

function cancelChargesEdit() {
  editingCharges.value = false
  chargesEdit.value = {}
}

async function saveChargesChanges() {
  saving.value = true
  try {
    await orderStore.updateOrder(order.value.id, chargesEdit.value)
    editingCharges.value = false
    chargesEdit.value = {}
  } catch (error) {
    console.error('Failed to save charges changes:', error)
  } finally {
    saving.value = false
  }
}

function calculateTotalWithCharges() {
  const subtotal = calculateItemsTotal()
  const charges = (editingCharges.value || isCreateMode.value) ? chargesEdit.value : currentOrder.value
  
  const shipping = Number(charges.shipping_charges) || 0
  const otherCharges = Number(charges.charges) || 0
  const discount = Number(charges.discount) || 0
  
  return subtotal + shipping + otherCharges - discount
}

// Create order function
async function createOrder() {
  if (!canCreateOrder.value) return
  
  saving.value = true
  try {
    const orderData = {
      ...orderEdit.value,
      ...chargesEdit.value,
      items: itemsEdit.value.map(item => ({
        product_id: item.product_id,
        quantity: item.quantity,
        price: item.price,
        discount: item.discount || 0
      })),
      sub_total: calculateItemsTotal(),
      total_price: calculateTotalWithCharges()
    }
    
    const response = await fetch('/api/v1/orders', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(orderData)
    })
    
    if (!response.ok) {
      throw new Error('Failed to create order')
    }
    
    const result = await response.json()
    
    // Switch to view mode with the new order
    isCreateMode.value = false
    orderStore.selectedOrder = result.data
    orderStore.selectedOrderId = result.data.id
    
    // Reload orders list
    orderStore.loadOrdersWithFilters(orderStore.currentFilters)
    
  } catch (error) {
    console.error('Failed to create order:', error)
  } finally {
    saving.value = false
  }
}

// Action functions
function callClient() {
  const phone = currentOrder.value?.client?.phone_number
  if (phone) {
    window.open(`tel:${phone}`)
  }
}

async function updateStatus(status) {
  if (!order.value) return
  
  saving.value = true
  try {
    await orderStore.updateOrder(order.value.id, { status })
  } catch (error) {
    console.error('Failed to update status:', error)
  } finally {
    saving.value = false
  }
}

function printOrder() {
  window.print()
}

function duplicateOrder() {
  if (!order.value) return
  
  // Switch to create mode with duplicated data
  isCreateMode.value = true
  
  orderEdit.value = {
    ...order.value,
    order_no: '',
    reference: `${order.value.reference || order.value.order_no}-COPY`,
    status: 'Inprogress'
  }
  
  itemsEdit.value = order.value.orderItems?.map(item => ({
    ...item,
    id: `temp-${Date.now()}-${Math.random()}`
  })) || []
  
  chargesEdit.value = {
    shipping_charges: order.value.shipping_charges || 0,
    charges: order.value.charges || 0,
    discount: order.value.discount || 0,
    payment_method: order.value.payment_method || 'Cash on Delivery',
    amount_paid: 0
  }
  
  activeTab.value = 'overview'
}

async function saveAllChanges() {
  if (!hasChanges.value) return
  
  saving.value = true
  try {
    const promises = []
    
    if (editingOrder.value) {
      promises.push(saveOrderChanges())
    }
    if (editingClient.value) {
      promises.push(saveClientChanges())
    }
    if (editingItems.value) {
      promises.push(saveItemsChanges())
    }
    if (editingCharges.value) {
      promises.push(saveChargesChanges())
    }
    
    await Promise.all(promises)
    
  } catch (error) {
    console.error('Failed to save all changes:', error)
  } finally {
    saving.value = false
  }
}

// Standard functions
function closeDialog() {
  if (hasChanges.value && !isCreateMode.value) {
    if (confirm('You have unsaved changes. Are you sure you want to close?')) {
      resetEditing()
    } else {
      return
    }
  }
  
  orderStore.closeDialog()
  resetDialog()
}

function resetEditing() {
  editingOrder.value = false
  editingClient.value = false
  editingItems.value = false
  editingCharges.value = false
  orderEdit.value = {}
  clientEdit.value = {}
  itemsEdit.value = []
  chargesEdit.value = {}
}

function resetDialog() {
  activeTab.value = 'overview'
  isCreateMode.value = false
  resetEditing()
}

function resetForm() {
  if (isCreateMode.value) {
    initializeCreateMode()
    activeTab.value = 'overview'
  }
}

function refreshOrder() {
  if (order.value?.id) {
    orderStore.loadOrder(order.value.id)
  }
}

function deleteOrder() {
  deleteConfirmDialog.value = true
}

async function confirmDelete() {
  if (!order.value) return
  
  saving.value = true
  try {
    await orderStore.deleteOrder(order.value.id)
    deleteConfirmDialog.value = false
    closeDialog()
  } catch (error) {
    console.error('Failed to delete order:', error)
  } finally {
    saving.value = false
  }
}

// Watch for dialog changes
watch(dialog, (newVal) => {
  if (newVal && !isCreateMode.value) {
    // Load options when dialog opens in edit mode
    loadAllOptions()
    
    // Load vendor products if vendor is set
    if (order.value?.vendor_id) {
      loadProductsByVendor(order.value.vendor_id)
    }
  } else if (!newVal) {
    // Reset dialog when closed
    resetDialog()
  }
})

// Watch for create mode changes
watch(() => isCreateMode.value, (newVal) => {
  if (newVal) {
    editingItems.value = true
    editingCharges.value = true
    editingOrder.value = true
  } else {
    resetEditing()
  }
})
</script>