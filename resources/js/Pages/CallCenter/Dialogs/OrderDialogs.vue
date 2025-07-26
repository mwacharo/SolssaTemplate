<template>
  <v-dialog v-model="dialog" max-width="1400" persistent scrollable>
    <v-card class="order-details-card">
      <!-- Enhanced Header with Action Buttons -->
      <v-card-title class="pa-6 pb-4 gradient-header">
        <div class="d-flex align-center justify-space-between w-100">
          <div class="d-flex align-center">
            <v-avatar size="48" color="primary" class="me-4">
              <v-icon icon="mdi-package-variant" size="24" />
            </v-avatar>
            <div>
              <h2 class="text-h5 mb-1 text-white">{{ order?.order_no || 'Loading...' }}</h2>
              <div class="d-flex align-center gap-2">
                <v-chip 
                  v-if="order" 
                  :color="getStatusColor(order.status)" 
                  variant="flat" 
                  size="small"
                  class="font-weight-medium"
                >
                  {{ order.status }}
                </v-chip>
                <v-chip 
                  v-if="order?.delivery_status" 
                  :color="getDeliveryStatusColor(order.delivery_status)" 
                  variant="outlined" 
                  size="small"
                >
                  {{ order.delivery_status }}
                </v-chip>
                <v-chip 
                  v-if="order?.priority" 
                  :color="getPriorityColor(order.priority)" 
                  variant="flat" 
                  size="small"
                >
                  {{ order.priority }}
                </v-chip>
              </div>
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <v-btn
              icon="mdi-refresh"
              variant="text"
              size="small"
              color="white"
              @click="refreshOrder"
              :loading="isLoading"
            />
            <v-btn
              icon="mdi-printer"
              variant="text"
              size="small"
              color="white"
              @click="printOrder"
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
        <div v-if="!order || isLoading" class="pa-8 text-center">
          <v-progress-circular indeterminate color="primary" size="64" />
          <p class="text-h6 mt-4">Loading order details...</p>
          <p class="text-body-2 text-medium-emphasis">Please wait while we fetch the information</p>
        </div>

        <div v-else>
          <!-- Enhanced Tabs with Icons -->
          <v-tabs v-model="activeTab" color="primary" class="border-b bg-surface">
            <v-tab value="overview" prepend-icon="mdi-view-dashboard">
              <span class="font-weight-medium">Overview</span>
            </v-tab>
            <v-tab value="items" prepend-icon="mdi-cart">
              <span class="font-weight-medium">Items</span>
              <v-chip size="x-small" variant="flat" color="primary" class="ml-2">
                {{ (order.orderItems || []).length }}
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
            <v-tab value="tracking" prepend-icon="mdi-map-marker-path">
              <span class="font-weight-medium">Tracking</span>
            </v-tab>
            <v-tab value="communications" prepend-icon="mdi-message">
              <span class="font-weight-medium">Communications</span>
            </v-tab>
          </v-tabs>

          <v-tabs-window v-model="activeTab">
            <!-- Overview Tab -->
            <v-tabs-window-item value="overview" class="pa-6">
              <v-row>
                <!-- Order Summary Card -->
                <v-col cols="12" lg="8">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center">
                        <v-icon icon="mdi-clipboard-text" class="me-2" color="primary" />
                        Order Details
                      </div>
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
                    </v-card-title>
                    <v-card-text>
                      <v-row>
                        <v-col cols="12" md="6">
                          <div class="info-grid">
                            <div class="info-item">
                              <div class="label">Order Number</div>
                              <div class="value">{{ order.order_no }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Platform</div>
                              <v-chip variant="outlined" size="small">{{ order.platform }}</v-chip>
                            </div>
                            <div class="info-item">
                              <div class="label">Priority</div>
                              <v-select
                                v-if="editingOrder"
                                v-model="orderEdit.priority"
                                :items="priorityOptions"
                                variant="outlined"
                                density="compact"
                              />
                              <v-chip v-else :color="getPriorityColor(order.priority)" variant="flat" size="small">
                                {{ order.priority || 'Standard' }}
                              </v-chip>
                            </div>
                            <div class="info-item">
                              <div class="label">Service Type</div>
                              <v-select
                                v-if="editingOrder"
                                v-model="orderEdit.service_type"
                                :items="serviceTypeOptions"
                                variant="outlined"
                                density="compact"
                              />
                              <div v-else class="value">{{ order.service_type || 'Standard Delivery' }}</div>
                            </div>
                          </div>
                        </v-col>
                        <v-col cols="12" md="6">
                          <div class="info-grid">
                            <div class="info-item">
                              <div class="label">Created</div>
                              <div class="value">{{ formatDateTime(order.created_at) }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Delivery Date</div>
                              <v-text-field
                                v-if="editingOrder"
                                v-model="orderEdit.delivery_date"
                                type="date"
                                variant="outlined"
                                density="compact"
                              />
                              <div v-else class="value">{{ formatDate(order.delivery_date) }}</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Weight</div>
                              <v-text-field
                                v-if="editingOrder"
                                v-model="orderEdit.weight"
                                suffix="kg"
                                variant="outlined"
                                density="compact"
                              />
                              <div v-else class="value">{{ order.weight || '0.00' }} kg</div>
                            </div>
                            <div class="info-item">
                              <div class="label">Distance</div>
                              <div class="value">{{ order.distance || '0.00' }} km</div>
                            </div>
                          </div>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- Quick Actions & Metrics -->
                <v-col cols="12" lg="4">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title>
                      <v-icon icon="mdi-speedometer" class="me-2" color="primary" />
                      Quick Actions
                    </v-card-title>
                    <v-card-text>
                      <div class="d-flex flex-column gap-3">
                        <v-btn
                          color="primary"
                          variant="flat"
                          block
                          @click="assignRider"
                        >
                          <v-icon start>mdi-motorbike</v-icon>
                          Assign Rider
                        </v-btn>
                        <v-btn
                          color="success"
                          variant="outlined"
                          block
                          @click="updateStatus"
                        >
                          <v-icon start>mdi-check-circle</v-icon>
                          Update Status
                        </v-btn>
                        <v-btn
                          color="info"
                          variant="outlined"
                          block
                          @click="sendNotification"
                        >
                          <v-icon start>mdi-bell-ring</v-icon>
                          Notify Client
                        </v-btn>
                        <v-btn
                          color="warning"
                          variant="outlined"
                          block
                          @click="escalateOrder"
                        >
                          <v-icon start>mdi-alert</v-icon>
                          Escalate
                        </v-btn>
                      </div>

                      <v-divider class="my-4" />

                      <!-- Order Metrics -->
                      <div class="text-subtitle-2 mb-3">Order Metrics</div>
                      <div class="metrics-grid">
                        <div class="metric-item">
                          <div class="metric-value text-h6 text-success">{{ formatCurrency(orderTotal) }}</div>
                          <div class="metric-label">Total Value</div>
                        </div>
                        <div class="metric-item">
                          <div class="metric-value text-h6 text-primary">{{ (order.orderItems || []).length }}</div>
                          <div class="metric-label">Items</div>
                        </div>
                        <div class="metric-item">
                          <div class="metric-value text-h6 text-info">{{ order.weight || '0' }}kg</div>
                          <div class="metric-label">Weight</div>
                        </div>
                        <div class="metric-item">
                          <div class="metric-value text-h6 text-warning">{{ getTimeElapsed(order.created_at) }}</div>
                          <div class="metric-label">Age</div>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            <!-- Enhanced Order Items Tab -->
            <v-tabs-window-item value="items" class="pa-6">
              <v-card variant="outlined">
                <v-card-title class="d-flex align-center justify-space-between">
                  <div class="d-flex align-center">
                    <v-icon icon="mdi-cart" class="me-2" color="primary" />
                    Order Items
                    <v-chip variant="outlined" size="small" class="ml-3">
                      {{ (order.orderItems || []).length }} items
                    </v-chip>
                  </div>
                  <div class="d-flex gap-2">
                    <v-btn
                      v-if="!editingItems"
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
                      :disabled="!editingItems"
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
                    :items="editingItems ? itemsEdit : (order.orderItems || [])"
                    class="order-items-table"
                    :loading="saving"
                    loading-text="Updating items..."
                  >
                    <template #item.product="{ item }">
                      <div v-if="editingItems" class="d-flex align-center gap-2">
                        <v-select
                          v-model="item.product_id"
                          :items="productOptions"
                          item-title="product_name"
                          item-value="id"
                          variant="outlined"
                          density="compact"
                          style="min-width: 200px;"
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
                        v-if="editingItems"
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
                        v-if="editingItems"
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
                        v-if="editingItems"
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
                      <div v-if="editingItems" class="d-flex gap-1">
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
                        v-if="!editingCharges"
                        color="primary"
                        variant="outlined"
                        size="small"
                        @click="startEditingCharges"
                      >
                        <v-icon start>mdi-pencil</v-icon>
                        Edit Charges
                      </v-btn>
                      <div v-else class="d-flex gap-2">
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
                          <div class="charge-value">{{ formatCurrency(order.subtotal || orderTotal) }}</div>
                        </div>
                        
                        <div class="charge-item">
                          <div class="charge-label">Delivery Fee</div>
                          <div class="charge-value">
                            <v-text-field
                              v-if="editingCharges"
                              v-model.number="chargesEdit.delivery_fee"
                              type="number"
                              step="0.01"
                              variant="outlined"
                              density="compact"
                              prefix="$"
                              style="width: 120px;"
                            />
                            <span v-else>{{ formatCurrency(order.delivery_fee || 0) }}</span>
                          </div>
                        </div>

                        <div class="charge-item">
                          <div class="charge-label">Service Fee</div>
                          <div class="charge-value">
                            <v-text-field
                              v-if="editingCharges"
                              v-model.number="chargesEdit.service_fee"
                              type="number"
                              step="0.01"
                              variant="outlined"
                              density="compact"
                              prefix="$"
                              style="width: 120px;"
                            />
                            <span v-else>{{ formatCurrency(order.service_fee || 0) }}</span>
                          </div>
                        </div>

                        <div class="charge-item">
                          <div class="charge-label">Tax</div>
                          <div class="charge-value">
                            <v-text-field
                              v-if="editingCharges"
                              v-model.number="chargesEdit.tax_amount"
                              type="number"
                              step="0.01"
                              variant="outlined"
                              density="compact"
                              prefix="$"
                              style="width: 120px;"
                            />
                            <span v-else>{{ formatCurrency(order.tax_amount || 0) }}</span>
                          </div>
                        </div>

                        <div class="charge-item">
                          <div class="charge-label">Discount</div>
                          <div class="charge-value text-success">
                            <v-text-field
                              v-if="editingCharges"
                              v-model.number="chargesEdit.discount_amount"
                              type="number"
                              step="0.01"
                              variant="outlined"
                              density="compact"
                              prefix="-$"
                              style="width: 120px;"
                            />
                            <span v-else>-{{ formatCurrency(order.discount_amount || 0) }}</span>
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
                          <v-chip variant="outlined" size="small">
                            {{ order.payment_method || 'Cash on Delivery' }}
                          </v-chip>
                        </div>
                        <div class="info-item">
                          <div class="label">Payment Status</div>
                          <v-chip :color="getPaymentStatusColor(order.payment_status)" variant="flat" size="small">
                            {{ order.payment_status || 'Pending' }}
                          </v-chip>
                        </div>
                        <div class="info-item">
                          <div class="label">Amount Paid</div>
                          <div class="value text-success">{{ formatCurrency(order.amount_paid || 0) }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Outstanding</div>
                          <div class="value text-error">
                            {{ formatCurrency(calculateTotalWithCharges() - (order.amount_paid || 0)) }}
                          </div>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            <!-- Enhanced Client Tab -->
            <v-tabs-window-item value="client" class="pa-6">
              <v-card variant="outlined">
                <v-card-title class="d-flex align-center justify-space-between">
                  <div class="d-flex align-center">
                    <v-avatar size="48" color="primary" class="me-3">
                      <v-icon icon="mdi-account" size="24" />
                    </v-avatar>
                    <div>
                      <div class="text-h6">{{ order.client?.name || 'Unknown Client' }}</div>
                      <div class="text-caption text-medium-emphasis">Client ID: {{ order.client?.id || '-' }}</div>
                    </div>
                  </div>
                  <div class="d-flex gap-2">
                    <v-btn
                      color="primary"
                      variant="outlined"
                      size="small"
                      @click="callClient"
                    >
                      <v-icon start>mdi-phone</v-icon>
                      Call
                    </v-btn>
                    <v-btn
                      v-if="!editingClient"
                      color="primary"
                      variant="outlined"
                      size="small"
                      @click="startEditingClient"
                    >
                      <v-icon start>mdi-pencil</v-icon>
                      Edit
                    </v-btn>
                    <div v-else class="d-flex gap-2">
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
                <v-card-text>
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="info-grid">
                        <div class="info-item">
                          <div class="label">Full Name</div>
                          <v-text-field
                            v-if="editingClient"
                            v-model="clientEdit.name"
                            variant="outlined"
                            density="compact"
                          />
                          <div v-else class="value">{{ order.client?.name || '-' }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Phone Number</div>
                          <v-text-field
                            v-if="editingClient"
                            v-model="clientEdit.phone_number"
                            variant="outlined"
                            density="compact"
                          />
                          <div v-else class="value">
                            <a :href="`tel:${order.client?.phone_number}`" class="text-decoration-none">
                              {{ order.client?.phone_number || '-' }}
                            </a>
                          </div>
                        </div>
                        <div class="info-item">
                          <div class="label">Email</div>
                          <v-text-field
                            v-if="editingClient"
                            v-model="clientEdit.email"
                            variant="outlined"
                            density="compact"
                          />
                          <div v-else class="value">
                            <a :href="`mailto:${order.client?.email}`" class="text-decoration-none">
                              {{ order.client?.email || '-' }}
                            </a>
                          </div>
                        </div>
                        <div class="info-item">
                          <div class="label">Customer Since</div>
                          <div class="value">{{ formatDate(order.client?.created_at) }}</div>
                        </div>
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="info-grid">
                        <div class="info-item">
                          <div class="label">Delivery Address</div>
                          <v-textarea
                            v-if="editingClient"
                            v-model="clientEdit.address"
                            variant="outlined"
                            density="compact"
                            rows="3"
                          />
                          <div v-else class="value">{{ order.client?.address || '-' }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">City</div>
                          <v-text-field
                            v-if="editingClient"
                            v-model="clientEdit.city"
                            variant="outlined"
                            density="compact"
                          />
                          <div v-else class="value">{{ order.client?.city || '-' }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Client Status</div>
                          <v-chip 
                            :color="order.client?.status === 'active' ? 'success' : 'error'" 
                            variant="flat" 
                            size="small"
                          >
                            {{ order.client?.status || '-' }}
                          </v-chip>
                        </div>
                        <div class="info-item">
                          <div class="label">Total Orders</div>
                          <div class="value">{{ order.client?.total_orders || '0' }}</div>
                        </div>
                      </div>
                    </v-col>
                  </v-row>
                </v-card-text>
              </v-card>
            </v-tabs-window-item>

            <!-- Fulfillment Tab (Vendor, Agent, Rider) -->
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
                          {{ order.vendor?.company_name || 'No vendor assigned' }}
                        </div>
                      </div>
                    </v-card-title>
                    <v-card-text v-if="order.vendor">
                      <div class="info-grid">
                        <div class="info-item">
                          <div class="label">Company</div>
                          <div class="value">{{ order.vendor.company_name }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Contact Person</div>
                          <div class="value">{{ order.vendor.name }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Phone</div>
                          <div class="value">
                            <a :href="`tel:${order.vendor.phone}`" class="text-decoration-none">
                              {{ order.vendor.phone }}
                            </a>
                          </div>
                        </div>
                        <div class="info-item">
                          <div class="label">Email</div>
                          <div class="value">
                            <a :href="`mailto:${order.vendor.email}`" class="text-decoration-none">
                              {{ order.vendor.email }}
                            </a>
                          </div>
                        </div>
                      </div>
                    </v-card-text>
                    <v-card-text v-else class="text-center py-8">
                      <v-icon icon="mdi-store-off" size="48" class="text-medium-emphasis mb-3" />
                      <p class="text-body-2 text-medium-emphasis">No vendor assigned</p>
                      <v-btn color="primary" variant="outlined" size="small" class="mt-2">
                        <v-icon start>mdi-plus</v-icon>
                        Assign Vendor
                      </v-btn>
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
                          {{ order.agent?.name || 'No agent assigned' }}
                        </div>
                      </div>
                    </v-card-title>
                    <v-card-text v-if="order.agent">
                      <div class="info-grid">
                        <div class="info-item">
                          <div class="label">Name</div>
                          <div class="value">{{ order.agent.name }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Employee ID</div>
                          <div class="value">{{ order.agent.employee_id }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Phone</div>
                          <div class="value">
                            <a :href="`tel:${order.agent.phone}`" class="text-decoration-none">
                              {{ order.agent.phone }}
                            </a>
                          </div>
                        </div>
                        <div class="info-item">
                          <div class="label">Department</div>
                          <div class="value">{{ order.agent.department }}</div>
                        </div>
                      </div>
                    </v-card-text>
                    <v-card-text v-else class="text-center py-8">
                      <v-icon icon="mdi-account-off" size="48" class="text-medium-emphasis mb-3" />
                      <p class="text-body-2 text-medium-emphasis">No agent assigned</p>
                      <v-btn color="primary" variant="outlined" size="small" class="mt-2">
                        <v-icon start>mdi-plus</v-icon>
                        Assign Agent
                      </v-btn>
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
                          {{ order.rider?.name || 'No rider assigned' }}
                        </div>
                      </div>
                    </v-card-title>
                    <v-card-text v-if="order.rider">
                      <div class="info-grid">
                        <div class="info-item">
                          <div class="label">Name</div>
                          <div class="value">{{ order.rider.name }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Phone</div>
                          <div class="value">
                            <a :href="`tel:${order.rider.phone}`" class="text-decoration-none">
                              {{ order.rider.phone }}
                            </a>
                          </div>
                        </div>
                        <div class="info-item">
                          <div class="label">Vehicle</div>
                          <div class="value">{{ order.rider.vehicle_type }} - {{ order.rider.vehicle_number }}</div>
                        </div>
                        <div class="info-item">
                          <div class="label">Status</div>
                          <v-chip :color="getRiderStatusColor(order.rider.status)" variant="flat" size="small">
                            {{ order.rider.status }}
                          </v-chip>
                        </div>
                      </div>
                    </v-card-text>
                    <v-card-text v-else class="text-center py-8">
                      <v-icon icon="mdi-motorbike-off" size="48" class="text-medium-emphasis mb-3" />
                      <p class="text-body-2 text-medium-emphasis">No rider assigned</p>
                      <v-btn color="primary" variant="outlined" size="small" class="mt-2">
                        <v-icon start>mdi-plus</v-icon>
                        Assign Rider
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            <!-- Tracking Tab -->
            <v-tabs-window-item value="tracking" class="pa-6">
              <v-row>
                <v-col cols="12" md="8">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-timeline" class="me-2" color="primary" />
                      Order Timeline
                    </v-card-title>
                    <v-card-text>
                      <v-timeline density="compact">
                        <v-timeline-item
                          dot-color="success"
                          size="small"
                          icon="mdi-check-circle"
                        >
                          <div class="d-flex justify-space-between align-center">
                            <div>
                              <div class="text-subtitle-2">Order Created</div>
                              <div class="text-caption text-medium-emphasis">Order was successfully created and confirmed</div>
                            </div>
                            <div class="text-caption">{{ formatDateTime(order.created_at) }}</div>
                          </div>
                        </v-timeline-item>
                        
                        <v-timeline-item
                          v-if="order.confirmed_at"
                          dot-color="info"
                          size="small"
                          icon="mdi-clipboard-check"
                        >
                          <div class="d-flex justify-space-between align-center">
                            <div>
                              <div class="text-subtitle-2">Order Confirmed</div>
                              <div class="text-caption text-medium-emphasis">Vendor confirmed the order</div>
                            </div>
                            <div class="text-caption">{{ formatDateTime(order.confirmed_at) }}</div>
                          </div>
                        </v-timeline-item>

                        <v-timeline-item
                          v-if="order.picked_up_at"
                          dot-color="primary"
                          size="small"
                          icon="mdi-package-up"
                        >
                          <div class="d-flex justify-space-between align-center">
                            <div>
                              <div class="text-subtitle-2">Package Picked Up</div>
                              <div class="text-caption text-medium-emphasis">Rider collected the package</div>
                            </div>
                            <div class="text-caption">{{ formatDateTime(order.picked_up_at) }}</div>
                          </div>
                        </v-timeline-item>

                        <v-timeline-item
                          v-if="order.delivered_at"
                          dot-color="success"
                          size="small"
                          icon="mdi-package-check"
                        >
                          <div class="d-flex justify-space-between align-center">
                            <div>
                              <div class="text-subtitle-2">Delivered</div>
                              <div class="text-caption text-medium-emphasis">Package successfully delivered to client</div>
                            </div>
                            <div class="text-caption">{{ formatDateTime(order.delivered_at) }}</div>
                          </div>
                        </v-timeline-item>
                      </v-timeline>
                    </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" md="4">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-map-marker" class="me-2" color="primary" />
                      Location Tracking
                    </v-card-title>
                    <v-card-text>
                      <div v-if="order.rider && order.tracking_enabled" class="text-center">
                        <v-btn
                          color="primary"
                          variant="flat"
                          block
                          @click="openMap"
                        >
                          <v-icon start>mdi-map</v-icon>
                          View Live Location
                        </v-btn>
                        <div class="mt-4">
                          <div class="text-caption text-medium-emphasis">Last Updated</div>
                          <div class="text-body-2">{{ formatDateTime(order.last_location_update) }}</div>
                        </div>
                      </div>
                      <div v-else class="text-center py-4">
                        <v-icon icon="mdi-map-marker-off" size="48" class="text-medium-emphasis mb-3" />
                        <p class="text-body-2 text-medium-emphasis">Location tracking not available</p>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            <!-- Communications Tab -->
            <v-tabs-window-item value="communications" class="pa-6">
              <v-row>
                <v-col cols="12" md="8">
                  <v-card variant="outlined">
                    <v-card-title class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center">
                        <v-icon icon="mdi-phone" class="me-2" color="primary" />
                        Call History
                      </div>
                      <v-btn color="primary" variant="outlined" size="small">
                        <v-icon start>mdi-plus</v-icon>
                        Log Call
                      </v-btn>
                    </v-card-title>
                    <v-card-text>
                      <div v-if="!order.call_history || order.call_history.length === 0" class="text-center py-8">
                        <v-icon icon="mdi-phone-off" size="64" class="text-medium-emphasis mb-4" />
                        <p class="text-body-1 text-medium-emphasis">No call history available</p>
                        <p class="text-caption text-medium-emphasis">Start by making your first call to the client</p>
                      </div>
                      <!-- Call history items would go here -->
                    </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" md="4">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-message" class="me-2" color="primary" />
                      Quick Actions
                    </v-card-title>
                    <v-card-text>
                      <div class="d-flex flex-column gap-3">
                        <v-btn
                          color="success"
                          variant="flat"
                          block
                          @click="sendSMS"
                        >
                          <v-icon start>mdi-message-text</v-icon>
                          Send SMS
                        </v-btn>
                        <v-btn
                          color="info"
                          variant="outlined"
                          block
                          @click="sendEmail"
                        >
                          <v-icon start>mdi-email</v-icon>
                          Send Email
                        </v-btn>
                        <v-btn
                          color="warning"
                          variant="outlined"
                          block
                          @click="scheduleCallback"
                        >
                          <v-icon start>mdi-calendar-clock</v-icon>
                          Schedule Callback
                        </v-btn>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>
          </v-tabs-window>
        </div>
      </v-card-text>

      <!-- Enhanced Actions -->
      <v-divider />
      <v-card-actions class="pa-6">
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
          color="warning"
          variant="outlined"
          @click="duplicateOrder"
          :disabled="!order"
        >
          <v-icon icon="mdi-content-duplicate" start />
          Duplicate
        </v-btn>
        <v-spacer />
        <v-btn
          variant="outlined"
          @click="exportOrder"
          :disabled="!order"
          class="me-3"
        >
          <v-icon icon="mdi-download" start />
          Export
        </v-btn>
        <v-btn
          variant="outlined"
          @click="closeDialog"
          class="me-3"
        >
          Close
        </v-btn>
        <v-btn
          color="primary"
          variant="flat"
          @click="printOrder"
          :disabled="!order"
        >
          <v-icon icon="mdi-printer" start />
          Print Order
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { computed, toRefs, ref, watch } from 'vue'
import { useOrderStore } from '@/stores/orderStore'

// Pinia store usage
const orderStore = useOrderStore()
const { dialog } = toRefs(orderStore)

// Local state
const activeTab = ref('overview')
const saving = ref(false)

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

// Options
const priorityOptions = ref(['Low', 'Standard', 'High', 'Urgent', 'Critical'])
const serviceTypeOptions = ref(['Standard Delivery', 'Express Delivery', 'Same Day', 'Next Day', 'Scheduled'])
const productOptions = ref([]) // Would be loaded from API

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

const orderTotal = computed(() => {
  if (!order.value?.orderItems) return 0
  return order.value.orderItems.reduce((sum, item) => {
    return sum + (item.total_price || 0)
  }, 0)
})

// Watch for order changes
watch(order, (newOrder) => {
  if (newOrder) {
    resetEditingStates()
  }
})

// Helper functions
function resetEditingStates() {
  editingOrder.value = false
  editingClient.value = false
  editingItems.value = false
  editingCharges.value = false
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function formatCurrency(amount) {
  if (amount == null) return '$0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

function getStatusColor(status) {
  const colors = {
    'pending': 'warning',
    'confirmed': 'info',
    'processing': 'primary',
    'completed': 'success',
    'cancelled': 'error',
    'refunded': 'secondary'
  }
  return colors[status?.toLowerCase()] || 'default'
}

function getDeliveryStatusColor(status) {
  const colors = {
    'pending': 'warning',
    'assigned': 'info',
    'picked_up': 'primary',
    'in_transit': 'primary',
    'delivered': 'success',
    'failed': 'error',
    'returned': 'secondary'
  }
  return colors[status?.toLowerCase()] || 'default'
}

function getPriorityColor(priority) {
  const colors = {
    'low': 'success',
    'standard': 'info',
    'high': 'warning',
    'urgent': 'error',
    'critical': 'error'
  }
  return colors[priority?.toLowerCase()] || 'info'
}

function getPaymentStatusColor(status) {
  const colors = {
    'pending': 'warning',
    'paid': 'success',
    'failed': 'error',
    'refunded': 'info'
  }
  return colors[status?.toLowerCase()] || 'warning'
}

function getRiderStatusColor(status) {
  const colors = {
    'available': 'success',
    'busy': 'warning',
    'offline': 'error'
  }
  return colors[status?.toLowerCase()] || 'default'
}

function getTimeElapsed(dateStr) {
  if (!dateStr) return '-'
  const now = new Date()
  const created = new Date(dateStr)
  const diffMs = now - created
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60))
  const diffDays = Math.floor(diffHours / 24)
  
  if (diffDays > 0) return `${diffDays}d`
  if (diffHours > 0) return `${diffHours}h`
  return '<1h'
}

// Order editing
function startEditingOrder() {
  editingOrder.value = true
  orderEdit.value = {
    priority: order.value.priority,
    service_type: order.value.service_type,
    delivery_date: order.value.delivery_date,
    weight: order.value.weight
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

// Client editing
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

// Items editing
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
    // Update order items via API
    console.log('Saving items changes:', itemsEdit.value)
    editingItems.value = false
    itemsEdit.value = []
  } catch (error) {
    console.error('Failed to save items changes:', error)
  } finally {
    saving.value = false
  }
}

function addNewItem() {
  itemsEdit.value.push({
    id: `temp-${Date.now()}`,
    product_id: null,
    quantity: 1,
    price: 0,
    discount: 0,
    total_price: 0
  })
}

function duplicateItem(item) {
  const newItem = { ...item, id: `temp-${Date.now()}` }
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
  if (editingItems.value) {
    return itemsEdit.value.reduce((sum, item) => sum + (item.total_price || 0), 0)
  }
  return orderTotal.value
}

// Charges editing
function startEditingCharges() {
  editingCharges.value = true
  chargesEdit.value = {
    delivery_fee: order.value.delivery_fee || 0,
    service_fee: order.value.service_fee || 0,
    tax_amount: order.value.tax_amount || 0,
    discount_amount: order.value.discount_amount || 0
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
  if (editingCharges.value) {
    const delivery = Number(chargesEdit.value.delivery_fee) || 0
    const service = Number(chargesEdit.value.service_fee) || 0
    const tax = Number(chargesEdit.value.tax_amount) || 0
    const discount = Number(chargesEdit.value.discount_amount) || 0
    return subtotal + delivery + service + tax - discount
  }
  
  const delivery = Number(order.value?.delivery_fee) || 0
  const service = Number(order.value?.service_fee) || 0
  const tax = Number(order.value?.tax_amount) || 0
  const discount = Number(order.value?.discount_amount) || 0
  return subtotal + delivery + service + tax - discount
}

// Action functions
function closeDialog() {
  orderStore.closeDialog()
  activeTab.value = 'overview'
  resetEditingStates()
}

async function refreshOrder() {
  if (order.value?.id) {
    await orderStore.loadOrder(order.value.id)
  }
}

async function deleteOrder() {
  if (!order.value || !confirm('Are you sure you want to delete this order?')) return
  
  try {
    await orderStore.deleteOrder(order.value.id)
    closeDialog()
  } catch (error) {
    console.error('Failed to delete order:', error)
  }
}

function printOrder() {
  console.log('Print order:', order.value?.id)
  // Implement print functionality
}

function duplicateOrder() {
  console.log('Duplicate order:', order.value?.id)
  // Implement duplicate functionality
}

function exportOrder() {
  console.log('Export order:', order.value?.id)
  // Implement export functionality
}

// Quick action functions
function assignRider() {
  console.log('Assign rider')
  // Implement rider assignment
}

function updateStatus() {
  console.log('Update status')
  // Implement status update
}

function sendNotification() {
  console.log('Send notification')
  // Implement notification
}

function escalateOrder() {
  console.log('Escalate order')
  // Implement escalation
}

function callClient() {
  if (order.value?.client?.phone_number) {
    window.open(`tel:${order.value.client.phone_number}`)
  }
}

function sendSMS() {
  console.log('Send SMS')
  // Implement SMS functionality
}

function sendEmail() {
  console.log('Send email')
  // Implement email functionality
}

function scheduleCallback() {
  console.log('Schedule callback')
  // Implement callback scheduling
}

function openMap() {
  console.log('Open map')
  // Implement map functionality
}
</script>

<style scoped>
.order-details-card {
  max-height: 95vh;
}

.gradient-header {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), rgb(var(--v-theme-secondary)));
}

.info-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-item .label {
  font-size: 0.75rem;
  color: rgb(var(--v-theme-on-surface-variant));
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.info-item .value {
  font-size: 0.875rem;
  font-weight: 500;
  color: rgb(var(--v-theme-on-surface));
}

.metrics-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.metric-item {
  text-align: center;
  padding: 12px;
  border-radius: 8px;
  background-color: rgb(var(--v-theme-surface-variant));
}

.metric-value {
  font-weight: 700;
  margin-bottom: 4px;
}

.metric-label {
  font-size: 0.75rem;
  color: rgb(var(--v-theme-on-surface-variant));
  text-transform: uppercase;
  font-weight: 500;
}

.charges-breakdown {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.charge-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
}

.charge-item.total-charge {
  border-top: 2px solid rgb(var(--v-border-color));
  padding-top: 16px;
  margin-top: 8px;
}

.charge-label {
  font-weight: 500;
}

.charge-value {
  font-weight: 600;
}

.payment-info {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.order-items-table {
  border-collapse: separate;
  border-spacing: 0;
}

.border-b {
  border-bottom: 1px solid rgb(var(--v-border-color));
}

.gap-2 {
  gap: 8px;
}

.gap-3 {
  gap: 12px;
}
</style>