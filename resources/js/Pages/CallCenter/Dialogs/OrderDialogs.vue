<template>
  <v-dialog v-model="dialog" max-width="1400" persistent scrollable>
    <v-card class="order-details-card">
      <!-- Enhanced Header with AI Insights -->
      <v-card-title class="pa-6 pb-4 gradient-header">
        <div class="d-flex align-center  w-100">
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
                  v-if="order?.ai_risk_score" 
                  :color="getRiskColor(order.ai_risk_score)" 
                  variant="outlined" 
                  size="small"
                >
                  <v-icon start size="small">mdi-brain</v-icon>
                  Risk: {{ order.ai_risk_score }}%
                </v-chip>
                <v-chip 
                  v-if="order?.predicted_delay" 
                  color="warning" 
                  variant="outlined" 
                  size="small"
                >
                  <v-icon start size="small">mdi-clock-alert</v-icon>
                  +{{ order.predicted_delay }}min
                </v-chip>
              </div>
            </div>
          </div>
          <div class="d-flex align-center gap-2">
            <!-- AI Assistant Button -->
            <v-btn
              icon="mdi-robot"
              variant="text"
              size="small"
              color="white"
              @click="openAIAssistant"
              :loading="aiProcessing"
            >
              <v-tooltip activator="parent" location="bottom">AI Assistant</v-tooltip>
            </v-btn>
            <v-btn
              icon="mdi-auto-fix"
              variant="text"
              size="small"
              color="white"
              @click="triggerAutoOptimization"
              :loading="optimizing"
            >
              <v-tooltip activator="parent" location="bottom">Auto-Optimize</v-tooltip>
            </v-btn>
            <v-btn
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

      <!-- AI Insights Banner -->
      <v-banner
        v-if="aiInsights.length > 0"
        color="info"
        icon="mdi-lightbulb"
        class="ai-insights-banner"
      >
        <template #text>
          <div class="d-flex align-center justify-space-between">
            <div>
              <strong>AI Insights:</strong> {{ aiInsights[currentInsightIndex]?.message }}
            </div>
            <div class="d-flex gap-2">
              <v-btn
                v-if="aiInsights[currentInsightIndex]?.action"
                size="small"
                variant="outlined"
                @click="executeAIAction(aiInsights[currentInsightIndex].action)"
              >
                {{ aiInsights[currentInsightIndex].action.label }}
              </v-btn>
              <v-btn
                icon="mdi-close"
                size="small"
                variant="text"
                @click="dismissInsight"
              />
            </div>
          </div>
        </template>
      </v-banner>

      <v-divider />

      <v-card-text class="pa-0">
        <div v-if="!order || isLoading" class="pa-8 text-center">
          <v-progress-circular indeterminate color="primary" size="64" />
          <p class="text-h6 mt-4">Loading order details...</p>
          <p class="text-body-2 text-medium-emphasis">AI is analyzing order data...</p>
        </div>

        <div v-else>
          <!-- Enhanced Tabs with AI Indicators -->
          <v-tabs v-model="activeTab" color="primary" class="border-b bg-surface">
            <v-tab value="overview" prepend-icon="mdi-view-dashboard">
              <span class="font-weight-medium">Overview</span>
              <v-badge
                v-if="automationAlerts.overview > 0"
                :content="automationAlerts.overview"
                color="error"
                inline
                class="ml-2"
              />
            </v-tab>
            <v-tab value="ai-automation" prepend-icon="mdi-robot">
              <span class="font-weight-medium">AI & Automation</span>
              <v-chip size="x-small" variant="flat" color="success" class="ml-2">
                Smart
              </v-chip>
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
            <v-tab value="smart-routing" prepend-icon="mdi-map-marker-path">
              <span class="font-weight-medium">Smart Routing</span>
              <v-badge
                v-if="routeOptimized"
                dot
                color="success"
                inline
                class="ml-2"
              />
            </v-tab>
            <v-tab value="predictive" prepend-icon="mdi-crystal-ball">
              <span class="font-weight-medium">Predictions</span>
            </v-tab>
            <v-tab value="communications" prepend-icon="mdi-message-processing">
              <span class="font-weight-medium">Smart Comms</span>
            </v-tab>
          </v-tabs>

          <v-tabs-window v-model="activeTab">
            <!-- Overview Tab with AI Enhancements -->
            <v-tabs-window-item value="overview" class="pa-6">
              <v-row>
                <!-- AI-Enhanced Order Summary -->
                <v-col cols="12" lg="8">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center">
                        <v-icon icon="mdi-clipboard-text" class="me-2" color="primary" />
                        Order Details
                        <v-chip
                          v-if="order.ai_confidence_score"
                          size="small"
                          variant="outlined"
                          color="success"
                          class="ml-3"
                        >
                          <v-icon start size="small">mdi-check-decagram</v-icon>
                          {{ order.ai_confidence_score }}% Verified
                        </v-chip>
                      </div>
                      <div class="d-flex gap-2">
                        <v-btn
                          color="info"
                          variant="outlined"
                          size="small"
                          @click="runAIValidation"
                          :loading="validating"
                        >
                          <v-icon start>mdi-brain</v-icon>
                          AI Validate
                        </v-btn>
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
                      </div>
                    </v-card-title>
                    <v-card-text>
                      <v-row>
                        <v-col cols="12" md="6">
                          <div class="info-grid">
                            <div class="info-item">
                              <div class="label">Order Number</div>
                              <div class="value d-flex align-center">
                                {{ order.order_no }}
                                <v-btn
                                  v-if="order.duplicate_likelihood > 70"
                                  icon="mdi-content-duplicate"
                                  size="x-small"
                                  variant="text"
                                  color="warning"
                                  class="ml-2"
                                >
                                  <v-tooltip activator="parent">
                                    Possible duplicate detected ({{ order.duplicate_likelihood }}%)
                                  </v-tooltip>
                                </v-btn>
                              </div>
                            </div>
                            <div class="info-item">
                              <div class="label">Platform</div>
                              <v-chip variant="outlined" size="small">{{ order.platform }}</v-chip>
                            </div>
                            <div class="info-item">
                              <div class="label">Auto-Priority (AI)</div>
                              <v-chip :color="getPriorityColor(order.ai_suggested_priority)" variant="flat" size="small">
                                {{ order.ai_suggested_priority || 'Standard' }}
                                <v-icon 
                                  v-if="order.priority !== order.ai_suggested_priority"
                                  end 
                                  size="small"
                                  color="warning"
                                >
                                  mdi-alert
                                </v-icon>
                              </v-chip>
                            </div>
                            <div class="info-item">
                              <div class="label">Estimated Delivery</div>
                              <div class="value d-flex align-center">
                                {{ formatDateTime(order.ai_estimated_delivery) }}
                                <v-chip
                                  size="x-small"
                                  variant="flat"
                                  :color="getAccuracyColor(order.prediction_accuracy)"
                                  class="ml-2"
                                >
                                  {{ order.prediction_accuracy }}% accurate
                                </v-chip>
                              </div>
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
                              <div class="label">AI Risk Assessment</div>
                              <v-progress-linear
                                :model-value="order.ai_risk_score"
                                :color="getRiskProgressColor(order.ai_risk_score)"
                                height="20"
                                rounded
                              >
                                <template #default="{ value }">
                                  <strong class="text-caption">{{ Math.ceil(value) }}% Risk</strong>
                                </template>
                              </v-progress-linear>
                            </div>
                            <div class="info-item">
                              <div class="label">Auto-Assigned Rider</div>
                              <div class="value d-flex align-center">
                                {{ order.auto_assigned_rider || 'Pending Assignment' }}
                                <v-btn
                                  v-if="!order.auto_assigned_rider"
                                  size="x-small"
                                  variant="outlined"
                                  color="primary"
                                  class="ml-2"
                                  @click="autoAssignRider"
                                  :loading="assigning"
                                >
                                  Auto-Assign
                                </v-btn>
                              </div>
                            </div>
                            <div class="info-item">
                              <div class="label">Fraud Score</div>
                              <v-chip 
                                :color="getFraudColor(order.fraud_score)" 
                                variant="flat" 
                                size="small"
                              >
                                <v-icon start size="small">mdi-shield-check</v-icon>
                                {{ order.fraud_score || 0 }}% Risk
                              </v-chip>
                            </div>
                          </div>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- AI-Powered Quick Actions -->
                <v-col cols="12" lg="4">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title>
                      <v-icon icon="mdi-robot" class="me-2" color="primary" />
                      AI-Powered Actions
                    </v-card-title>
                    <v-card-text>
                      <div class="d-flex flex-column gap-3">
                        <v-btn
                          color="primary"
                          variant="flat"
                          block
                          @click="autoOptimizeRoute"
                          :loading="optimizing"
                        >
                          <v-icon start>mdi-map-marker-path</v-icon>
                          Auto-Optimize Route
                        </v-btn>
                        <v-btn
                          color="success"
                          variant="outlined"
                          block
                          @click="predictDeliveryIssues"
                          :loading="predicting"
                        >
                          <v-icon start>mdi-crystal-ball</v-icon>
                          Predict Issues
                        </v-btn>
                        <v-btn
                          color="info"
                          variant="outlined"
                          block
                          @click="generateSmartNotifications"
                        >
                          <v-icon start>mdi-bell-ring</v-icon>
                          Smart Notify
                        </v-btn>
                        <v-btn
                          color="warning"
                          variant="outlined"
                          block
                          @click="runFraudCheck"
                          :loading="checkingFraud"
                        >
                          <v-icon start>mdi-shield-search</v-icon>
                          Fraud Check
                        </v-btn>
                      </div>

                      <v-divider class="my-4" />

                      <!-- AI Metrics -->
                      <div class="text-subtitle-2 mb-3">AI Insights</div>
                      <div class="metrics-grid">
                        <div class="metric-item">
                          <div class="metric-value text-h6 text-success">{{ order.ai_efficiency_score || 85 }}%</div>
                          <div class="metric-label">Efficiency</div>
                        </div>
                        <div class="metric-item">
                          <div class="metric-value text-h6 text-primary">{{ formatCurrency(order.ai_cost_savings || 25) }}</div>
                          <div class="metric-label">AI Savings</div>
                        </div>
                        <div class="metric-item">
                          <div class="metric-value text-h6 text-info">{{ order.ai_confidence_score || 92 }}%</div>
                          <div class="metric-label">Confidence</div>
                        </div>
                        <div class="metric-item">
                          <div class="metric-value text-h6 text-warning">{{ order.carbon_footprint || 2.5 }}kg</div>
                          <div class="metric-label">CO₂ Impact</div>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            Enhanced Order Items Tab
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
            <v-tabs-window-item value="ai-automation" class="pa-6">
              <v-row>
                <!-- Automation Controls -->
                <v-col cols="12" md="6">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-cog-sync" class="me-2" color="primary" />
                      Automation Settings
                    </v-card-title>
                    <v-card-text>
                      <div class="automation-controls">
                        <v-switch
                          v-model="automationSettings.autoRiderAssignment"
                          label="Auto Rider Assignment"
                          color="primary"
                          density="compact"
                          hide-details
                        />
                        <v-switch
                          v-model="automationSettings.smartRouting"
                          label="Smart Route Optimization"
                          color="primary"
                          density="compact"
                          hide-details
                        />
                        <v-switch
                          v-model="automationSettings.predictiveAlerts"
                          label="Predictive Delay Alerts"
                          color="primary"
                          density="compact"
                          hide-details
                        />
                        <v-switch
                          v-model="automationSettings.autoStatusUpdates"
                          label="Auto Status Updates"
                          color="primary"
                          density="compact"
                          hide-details
                        />
                        <v-switch
                          v-model="automationSettings.fraudDetection"
                          label="Real-time Fraud Detection"
                          color="primary"
                          density="compact"
                          hide-details
                        />
                        <v-switch
                          v-model="automationSettings.customerSentimentTracking"
                          label="Customer Sentiment Analysis"
                          color="primary"
                          density="compact"
                          hide-details
                        />
                      </div>

                      <v-divider class="my-4" />

                      <div class="text-subtitle-2 mb-3">AI Models Performance</div>
                      <div class="ai-models">
                        <div class="model-item">
                          <div class="d-flex justify-space-between align-center">
                            <span>Route Optimization</span>
                            <v-chip size="small" color="success">98.5% accuracy</v-chip>
                          </div>
                          <v-progress-linear value="98.5" color="success" height="4" />
                        </div>
                        <div class="model-item">
                          <div class="d-flex justify-space-between align-center">
                            <span>Delivery Prediction</span>
                            <v-chip size="small" color="success">94.2% accuracy</v-chip>
                          </div>
                          <v-progress-linear value="94.2" color="success" height="4" />
                        </div>
                        <div class="model-item">
                          <div class="d-flex justify-space-between align-center">
                            <span>Fraud Detection</span>
                            <v-chip size="small" color="warning">87.8% accuracy</v-chip>
                          </div>
                          <v-progress-linear value="87.8" color="warning" height="4" />
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>

                <!-- AI Analytics -->
                <v-col cols="12" md="6">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-chart-line" class="me-2" color="primary" />
                      AI Analytics Dashboard
                    </v-card-title>
                    <v-card-text>
                      <!-- Real-time Processing Status -->
                      <div class="ai-processing-status mb-4">
                        <div class="d-flex justify-space-between align-center mb-2">
                          <span class="text-subtitle-2">AI Processing Status</span>
                          <v-chip size="small" color="success" variant="flat">
                            <v-icon start size="small">mdi-check-circle</v-icon>
                            Active
                          </v-chip>
                        </div>
                        <v-progress-linear
                          :model-value="aiProcessingLoad"
                          color="primary"
                          height="8"
                          rounded
                        >
                          <template #default="{ value }">
                            <span class="text-caption">{{ Math.ceil(value) }}% Load</span>
                          </template>
                        </v-progress-linear>
                      </div>

                      <!-- Automation Statistics -->
                      <div class="automation-stats">
                        <div class="stat-row">
                          <span>Orders Auto-Processed Today:</span>
                          <strong>{{ todayStats.autoProcessed || 245 }}</strong>
                        </div>
                        <div class="stat-row">
                          <span>AI Cost Savings (24h):</span>
                          <strong class="text-success">{{ formatCurrency(todayStats.costSavings || 1250) }}</strong>
                        </div>
                        <div class="stat-row">
                          <span>Route Optimizations:</span>
                          <strong>{{ todayStats.routeOptimizations || 89 }}</strong>
                        </div>
                        <div class="stat-row">
                          <span>Fraud Incidents Prevented:</span>
                          <strong class="text-error">{{ todayStats.fraudPrevented || 3 }}</strong>
                        </div>
                        <div class="stat-row">
                          <span>Average Delivery Accuracy:</span>
                          <strong>{{ todayStats.deliveryAccuracy || 96.7 }}%</strong>
                        </div>
                      </div>

                      <v-btn
                        color="primary"
                        variant="outlined"
                        block
                        class="mt-4"
                        @click="openAIAnalytics"
                      >
                        <v-icon start>mdi-chart-box</v-icon>
                        Full AI Analytics
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <!-- AI Training & Learning -->
              <v-row class="mt-6">
                <v-col cols="12">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-school" class="me-2" color="primary" />
                      Machine Learning Pipeline
                    </v-card-title>
                    <v-card-text>
                      <v-row>
                        <v-col cols="12" md="4">
                          <div class="learning-module">
                            <v-icon icon="mdi-database" size="32" color="info" class="mb-2" />
                            <h4>Data Collection</h4>
                            <p class="text-caption">Real-time order, delivery, and customer data ingestion</p>
                            <v-progress-linear value="100" color="info" height="4" />
                            <div class="text-caption mt-1">Status: Active</div>
                          </div>
                        </v-col>
                        <v-col cols="12" md="4">
                          <div class="learning-module">
                            <v-icon icon="mdi-brain" size="32" color="warning" class="mb-2" />
                            <h4>Model Training</h4>
                            <p class="text-caption">Continuous learning from delivery patterns and outcomes</p>
                            <v-progress-linear value="87" color="warning" height="4" />
                            <div class="text-caption mt-1">Next Training: 2 hours</div>
                          </div>
                        </v-col>
                        <v-col cols="12" md="4">
                          <div class="learning-module">
                            <v-icon icon="mdi-rocket-launch" size="32" color="success" class="mb-2" />
                            <h4>Model Deployment</h4>
                            <p class="text-caption">Auto-deployment of improved models to production</p>
                            <v-progress-linear value="94" color="success" height="4" />
                            <div class="text-caption mt-1">Last Deploy: 6 hours ago</div>
                          </div>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            <!-- Smart Routing Tab -->
            <v-tabs-window-item value="smart-routing" class="pa-6">
              <v-row>
                <v-col cols="12" md="8">
                  <v-card variant="outlined">
                    <v-card-title class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center">
                        <v-icon icon="mdi-map-marker-path" class="me-2" color="primary" />
                        AI Route Optimization
                      </div>
                      <div class="d-flex gap-2">
                        <v-btn
                          color="success"
                          variant="outlined"
                          size="small"
                          @click="recalculateRoute"
                          :loading="optimizing"
                        >
                          <v-icon start>mdi-refresh</v-icon>
                          Recalculate
                        </v-btn>
                        <v-btn
                          color="primary"
                          variant="flat"
                          size="small"
                          @click="applyOptimizedRoute"
                          :disabled="!routeOptimized"
                        >
                          <v-icon start>mdi-check</v-icon>
                          Apply Route
                        </v-btn>
                      </div>
                    </v-card-title>
                    <v-card-text>
                      <!-- Route Map Placeholder -->
                      <div class="route-map-container mb-4">
                        <div class="route-map-placeholder">
                          <v-icon icon="mdi-map" size="64" color="primary" />
                          <p class="text-h6 mt-2">Interactive Route Map</p>
                          <p class="text-body-2 text-medium-emphasis">
                            Real-time route optimization with traffic, weather, and delivery constraints
                          </p>
                        </div>
                      </div>

                      <!-- Route Details -->
                      <div class="route-details">
                        <v-row>
                          <v-col cols="6" md="3">
                            <div class="route-metric">
                              <div class="metric-value text-h6 text-primary">{{ routeData.distance }}km</div>
                              <div class="metric-label">Total Distance</div>
                              <div class="metric-improvement text-success">
                                <v-icon size="small">mdi-arrow-down</v-icon>
                                -{{ routeData.distanceSaved }}km saved
                              </div>
                            </div>
                          </v-col>
                          <v-col cols="6" md="3">
                            <div class="route-metric">
                              <div class="metric-value text-h6 text-warning">{{ routeData.duration }}min</div>
                              <div class="metric-label">Est. Duration</div>
                              <div class="metric-improvement text-success">
                                <v-icon size="small">mdi-arrow-down</v-icon>
                                -{{ routeData.timeSaved }}min saved
                              </div>
                            </div>
                          </v-col>
                          <v-col cols="6" md="3">
                            <div class="route-metric">
                              <div class="metric-value text-h6 text-info">{{ formatCurrency(routeData.fuelCost) }}</div>
                              <div class="metric-label">Fuel Cost</div>
                              <div class="metric-improvement text-success">
                                <v-icon size="small">mdi-arrow-down</v-icon>
                                {{ formatCurrency(routeData.fuelSaved) }} saved
                              </div>
                            </div>
                          </v-col>
                          <v-col cols="6" md="3">
                            <div class="route-metric">
                              <div class="metric-value text-h6 text-error">{{ routeData.co2 }}kg</div>
                              <div class="metric-label">CO₂ Emissions</div>
                              <div class="metric-improvement text-success">
                                <v-icon size="small">mdi-arrow-down</v-icon>
                                -{{ routeData.co2Saved }}kg reduced
                              </div>
                            </div>
                          </v-col>
                        </v-row>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" md="4">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-cog" class="me-2" color="primary" />
                      Route Optimization Settings
                    </v-card-title>
                    <v-card-text>
                      <div class="optimization-settings">
                        <div class="setting-item">
                          <v-switch
                            v-model="routeSettings.avoidTraffic"
                            label="Avoid Traffic"
                            color="primary"
                            density="compact"
                            hide-details
                          />
                        </div>
                        <div class="setting-item">
                          <v-switch
                            v-model="routeSettings.weatherConsideration"
                            label="Weather Consideration"
                            color="primary"
                            density="compact"
                            hide-details
                          />
                        </div>
                        <div class="setting-item">
                          <v-switch
                            v-model="routeSettings.prioritizeSpeed"
                            label="Prioritize Speed"
                            color="primary"
                            density="compact"
                            hide-details
                          />
                        </div>
                        <div class="setting-item">
                          <v-switch
                            v-model="routeSettings.ecoFriendly"
                            label="Eco-Friendly Route"
                            color="primary"
                            density="compact"
                            hide-details
                          />
                        </div>

                        <v-divider class="my-4" />

                        <div class="text-subtitle-2 mb-3">Optimization Algorithm</div>
                        <v-select
                          v-model="routeSettings.algorithm"
                          :items="['Fastest', 'Shortest', 'Most Economical', 'Balanced', 'AI Hybrid']"
                          label="Algorithm"
                          variant="outlined"
                          density="compact"
                        />

                        <div class="text-subtitle-2 mb-3 mt-4">Vehicle Constraints</div>
                        <v-text-field
                          v-model="routeSettings.vehicleCapacity"
                          label="Vehicle Capacity (kg)"
                          type="number"
                          variant="outlined"
                          density="compact"
                        />
                        <v-text-field
                          v-model="routeSettings.maxDeliveryTime"
                          label="Max Delivery Window (hours)"
                          type="number"
                          variant="outlined"
                          density="compact"
                        />
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            <!-- Predictive Analytics Tab -->
            <v-tabs-window-item value="predictive" class="pa-6">
              <v-row>
                <v-col cols="12" md="6">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-crystal-ball" class="me-2" color="primary" />
                      Delivery Predictions
                    </v-card-title>
                    <v-card-text>
                      <div class="prediction-item">
                        <div class="d-flex justify-space-between align-center mb-2">
                          <span class="text-subtitle-2">On-Time Delivery Probability</span>
                          <v-chip :color="getProbabilityColor(predictions.onTime)" size="small">
                            {{ predictions.onTime }}%
                          </v-chip>
                        </div>
                        <v-progress-linear 
                          :model-value="predictions.onTime" 
                          :color="getProbabilityColor(predictions.onTime)"
                          height="8" 
                        />
                      </div>

                      <div class="prediction-item">
                        <div class="d-flex justify-space-between align-center mb-2">
                          <span class="text-subtitle-2">Customer Satisfaction Score</span>
                          <v-chip color="success" size="small">{{ predictions.satisfaction }}/5</v-chip>
                        </div>
                        <v-progress-linear 
                          :model-value="(predictions.satisfaction / 5) * 100" 
                          color="success"
                          height="8" 
                        />
                      </div>

                      <div class="prediction-item">
                        <div class="d-flex justify-space-between align-center mb-2">
                          <span class="text-subtitle-2">Weather Impact Risk</span>
                          <v-chip :color="getRiskColor(predictions.weatherRisk)" size="small">
                            {{ predictions.weatherRisk }}%
                          </v-chip>
                        </div>
                        <v-progress-linear 
                          :model-value="predictions.weatherRisk" 
                          :color="getRiskColor(predictions.weatherRisk)"
                          height="8" 
                        />
                      </div>

                      <div class="prediction-item">
                        <div class="d-flex justify-space-between align-center mb-2">
                          <span class="text-subtitle-2">Return/Rejection Probability</span>
                          <v-chip :color="getRiskColor(predictions.returnRisk)" size="small">
                            {{ predictions.returnRisk }}%
                          </v-chip>
                        </div>
                        <v-progress-linear 
                          :model-value="predictions.returnRisk" 
                          :color="getRiskColor(predictions.returnRisk)"
                          height="8" 
                        />
                      </div>

                      <v-alert
                        v-if="predictions.alerts.length > 0"
                        type="warning"
                        variant="tonal"
                        class="mt-4"
                      >
                        <div class="text-subtitle-2 mb-1">AI Predictions Alert</div>
                        <ul class="text-body-2">
                          <li v-for="alert in predictions.alerts" :key="alert">{{ alert }}</li>
                        </ul>
                      </v-alert>
                    </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" md="6">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-trending-up" class="me-2" color="primary" />
                      Demand Forecasting
                    </v-card-title>
                    <v-card-text>
                      <div class="forecast-chart-placeholder mb-4">
                        <v-icon icon="mdi-chart-line" size="48" color="primary" />
                        <p class="text-subtitle-1 mt-2">7-Day Demand Forecast</p>
                        <p class="text-caption text-medium-emphasis">
                          AI-powered prediction based on historical patterns, seasonality, and external factors
                        </p>
                      </div>

                      <div class="forecast-insights">
                        <div class="insight-item">
                          <v-icon icon="mdi-trending-up" color="success" size="small" class="me-2" />
                          <span>Peak demand expected tomorrow 2-4 PM</span>
                        </div>
                        <div class="insight-item">
                          <v-icon icon="mdi-weather-rainy" color="warning" size="small" class="me-2" />
                          <span>Weather may impact deliveries on Friday</span>
                        </div>
                        <div class="insight-item">
                          <v-icon icon="mdi-account-group" color="info" size="small" class="me-2" />
                          <span>15% increase in orders expected due to local event</span>
                        </div>
                        <div class="insight-item">
                          <v-icon icon="mdi-truck" color="error" size="small" class="me-2" />
                          <span>Recommend adding 2 extra riders for weekend</span>
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <!-- Recommended Actions -->
              <v-row class="mt-6">
                <v-col cols="12">
                  <v-card variant="outlined">
                    <v-card-title>
                      <v-icon icon="mdi-lightbulb" class="me-2" color="primary" />
                      AI Recommendations
                    </v-card-title>
                    <v-card-text>
                      <v-row>
                        <v-col cols="12" md="4" v-for="recommendation in aiRecommendations" :key="recommendation.id">
                          <v-card variant="tonal" :color="recommendation.priority">
                            <v-card-text>
                              <div class="d-flex align-center mb-2">
                                <v-icon :icon="recommendation.icon" class="me-2" />
                                <span class="text-subtitle-2">{{ recommendation.title }}</span>
                              </div>
                              <p class="text-body-2 mb-3">{{ recommendation.description }}</p>
                              <div class="d-flex justify-space-between align-center">
                                <v-chip size="small" variant="outlined">
                                  Impact: {{ recommendation.impact }}
                                </v-chip>
                                <v-btn
                                  size="small"
                                  variant="outlined"
                                  @click="implementRecommendation(recommendation)"
                                >
                                  Apply
                                </v-btn>
                              </div>
                            </v-card-text>
                          </v-card>
                        </v-col>
                      </v-row>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>

            <!-- Smart Communications Tab -->
            <v-tabs-window-item value="communications" class="pa-6">
              <v-row>
                <v-col cols="12" md="8">
                  <v-card variant="outlined">
                    <v-card-title class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center">
                        <v-icon icon="mdi-message-processing" class="me-2" color="primary" />
                        AI-Powered Communications
                      </div>
                      <v-btn color="primary" variant="outlined" size="small" @click="generateSmartMessage">
                        <v-icon start>mdi-auto-fix</v-icon>
                        Generate Message
                      </v-btn>
                    </v-card-title>
                    <v-card-text>
                      <!-- AI Message Suggestions -->
                      <div class="message-suggestions mb-4">
                        <div class="text-subtitle-2 mb-3">AI Message Suggestions</div>
                        <div class="suggestion-cards">
                          <v-card 
                            v-for="suggestion in messageSuggestions" 
                            :key="suggestion.type"
                            variant="outlined"
                            class="suggestion-card"
                            @click="selectMessageSuggestion(suggestion)"
                          >
                            <v-card-text class="pa-3">
                              <div class="d-flex align-center mb-2">
                                <v-icon :icon="suggestion.icon" size="small" class="me-2" />
                                <span class="text-subtitle-2">{{ suggestion.title }}</span>
                                <v-spacer />
                                <v-chip size="x-small" :color="suggestion.urgency">{{ suggestion.type }}</v-chip>
                              </div>
                              <p class="text-body-2">{{ suggestion.preview }}</p>
                            </v-card-text>
                          </v-card>
                        </div>
                      </div>

                      <!-- Voice-to-Text -->
                      <v-card variant="tonal" color="info" class="mb-4">
                        <v-card-text>
                          <div class="d-flex align-center">
                            <v-btn
                              :icon="isRecording ? 'mdi-stop' : 'mdi-microphone'"
                              :color="isRecording ? 'error' : 'primary'"
                              variant="flat"
                              @click="toggleVoiceRecording"
                              class="me-3"
                            />
                            <div>
                              <div class="text-subtitle-2">Voice-to-Text</div>
                              <div class="text-caption">
                                {{ isRecording ? 'Recording... Tap to stop' : 'Tap to record your message' }}
                              </div>
                            </div>
                          </div>
                          <div v-if="voiceTranscript" class="mt-3 pa-3 bg-surface rounded">
                            <div class="text-caption text-medium-emphasis mb-1">Transcribed:</div>
                            <div class="text-body-2">{{ voiceTranscript }}</div>
                          </div>
                        </v-card-text>
                      </v-card>

                      <!-- Message Composer -->
                      <v-textarea
                        v-model="messageComposer.content"
                        label="Compose Message"
                        variant="outlined"
                        rows="4"
                        placeholder="Type your message or use AI suggestions..."
                      />
                      
                      <div class="d-flex align-center gap-2 mt-3">
                        <v-select
                          v-model="messageComposer.tone"
                          :items="['Professional', 'Friendly', 'Urgent', 'Apologetic', 'Informative']"
                          label="Tone"
                          variant="outlined"
                          density="compact"
                          style="max-width: 150px;"
                        />
                        <v-select
                          v-model="messageComposer.language"
                          :items="['English', 'Spanish', 'French', 'German', 'Chinese']"
                          label="Language"
                          variant="outlined"
                          density="compact"
                          style="max-width: 120px;"
                        />
                        <v-spacer />
                        <v-btn color="info" variant="outlined" @click="improveMessage">
                          <v-icon start>mdi-magic-staff</v-icon>
                          AI Improve
                        </v-btn>
                        <v-btn color="success" variant="flat" @click="sendMessage">
                          <v-icon start>mdi-send</v-icon>
                          Send
                        </v-btn>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" md="4">
                  <v-card variant="outlined" class="h-100">
                    <v-card-title>
                      <v-icon icon="mdi-chart-arc" class="me-2" color="primary" />
                      Communication Analytics
                    </v-card-title>
                    <v-card-text>
                      <!-- Customer Sentiment -->
                      <div class="sentiment-analysis mb-4">
                        <div class="text-subtitle-2 mb-2">Customer Sentiment</div>
                        <div class="sentiment-score">
                          <v-progress-circular
                            :model-value="customerSentiment.score"
                            :color="getSentimentColor(customerSentiment.score)"
                            size="80"
                            width="8"
                          >
                            <span class="text-h6">{{ customerSentiment.score }}%</span>
                          </v-progress-circular>
                          <div class="sentiment-label mt-2">
                            <v-chip :color="getSentimentColor(customerSentiment.score)" size="small">
                              {{ customerSentiment.label }}
                            </v-chip>
                          </div>
                        </div>
                      </div>

                      <!-- Communication Stats -->
                      <div class="communication-stats">
                        <div class="stat-item">
                          <div class="stat-label">Messages Sent Today</div>
                          <div class="stat-value">{{ communicationStats.messagesSent }}</div>
                        </div>
                        <div class="stat-item">
                          <div class="stat-label">Response Rate</div>
                          <div class="stat-value text-success">{{ communicationStats.responseRate }}%</div>
                        </div>
                        <div class="stat-item">
                          <div class="stat-label">Avg Response Time</div>
                          <div class="stat-value">{{ communicationStats.avgResponseTime }}</div>
                        </div>
                        <div class="stat-item">
                          <div class="stat-label">Customer Satisfaction</div>
                          <div class="stat-value text-info">{{ communicationStats.satisfaction }}/5</div>
                        </div>
                      </div>

                      <!-- AI Chatbot Integration -->
                      <v-divider class="my-4" />
                      <div class="chatbot-section">
                        <div class="text-subtitle-2 mb-2">AI Chatbot Status</div>
                        <div class="d-flex align-center justify-space-between">
                          <div>
                            <div class="text-body-2">Auto-Response</div>
                            <div class="text-caption text-medium-emphasis">Handling {{ chatbotStats.activeChats }} chats</div>
                          </div>
                          <v-switch
                            v-model="chatbotEnabled"
                            color="primary"
                            density="compact"
                            hide-details
                          />
                        </div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-tabs-window-item>
          </v-tabs-window>
        </div>
      </v-card-text>

      <!-- Enhanced Actions with AI -->
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
          color="info"
          variant="outlined"
          @click="runFullAIAnalysis"
          :disabled="!order"
          :loading="aiAnalyzing"
        >
          <v-icon icon="mdi-brain" start />
          Full AI Analysis
        </v-btn>
        <v-spacer />
        <v-btn
          variant="outlined"
          @click="exportAIReport"
          :disabled="!order"
          class="me-3"
        >
          <v-icon icon="mdi-file-chart" start />
          AI Report
        </v-btn>
        <v-btn
          color="primary"
          variant="flat"
          @click="autoExecuteOptimizations"
          :disabled="!order"
          :loading="executing"
        >
          <v-icon icon="mdi-auto-fix" start />
          Auto-Execute All
        </v-btn>
      </v-card-actions>
    </v-card>

    <!-- AI Assistant Dialog -->
    <v-dialog v-model="aiAssistantDialog" max-width="600">
      <v-card>
        <v-card-title class="d-flex align-center">
          <v-icon icon="mdi-robot" class="me-2" color="primary" />
          AI Assistant
        </v-card-title>
        <v-card-text>
          <div class="ai-chat-container">
            <div class="ai-messages">
              <div v-for="message in aiMessages" :key="message.id" class="ai-message">
                <div class="message-avatar">
                  <v-avatar size="32" :color="message.sender === 'ai' ? 'primary' : 'secondary'">
                    <v-icon :icon="message.sender === 'ai' ? 'mdi-robot' : 'mdi-account'" />
                  </v-avatar>
                </div>
                <div class="message-content">
                  <div class="message-text">{{ message.text }}</div>
                  <div class="message-time">{{ formatTime(message.timestamp) }}</div>
                </div>
              </div>
            </div>
            <v-text-field
              v-model="aiQuery"
              label="Ask AI about this order..."
              variant="outlined"
              density="compact"
              append-icon="mdi-send"
              @click:append="sendAIQuery"
              @keydown.enter="sendAIQuery"
              :loading="aiProcessing"
            />
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-dialog>
</template>

<script setup>
import { computed, toRefs, ref, watch, onMounted } from 'vue'
import { useOrderStore } from '@/stores/orderStore'

// Pinia store usage
const orderStore = useOrderStore()
const { dialog } = toRefs(orderStore)

// Local state
const activeTab = ref('overview')
const saving = ref(false)
const aiProcessing = ref(false)
const optimizing = ref(false)
const validating = ref(false)
const predicting = ref(false)
const checkingFraud = ref(false)
const assigning = ref(false)
const aiAnalyzing = ref(false)
const executing = ref(false)

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
const productOptions = ref([
  { id: 1, product_name: 'Laptop Computer', sku: 'LT001' },
  { id: 2, product_name: 'Wireless Mouse', sku: 'MS002' },
  { id: 3, product_name: 'Keyboard', sku: 'KB003' }
])




// Item table headers
const itemHeaders = [
  { title: 'Product', key: 'product', sortable: false },
  { title: 'Quantity', key: 'quantity', align: 'center' },
  { title: 'Unit Price', key: 'price', align: 'end' },
  { title: 'Discount', key: 'discount', align: 'end' },
  { title: 'Total', key: 'total_price', align: 'end' },
  { title: 'Actions', key: 'actions', sortable: false, align: 'center' }
]

// AI and Automation state
const aiInsights = ref([
  {
    message: "Route can be optimized to save 15 minutes and $12 in fuel costs",
    action: { label: "Optimize Route", type: "route_optimization" }
  },
  {
    message: "Customer satisfaction score is predicted to be high (4.7/5) based on delivery time",
    action: null
  },
  {
    message: "Weather conditions may cause 10-minute delay. Consider proactive communication",
    action: { label: "Send Alert", type: "weather_alert" }
  }
])

const currentInsightIndex = ref(0)
const automationAlerts = ref({ overview: 2, items: 0, routing: 1 })
const routeOptimized = ref(false)

// Automation settings
const automationSettings = ref({
  autoRiderAssignment: true,
  smartRouting: true,
  predictiveAlerts: true,
  autoStatusUpdates: true,
  fraudDetection: true,
  customerSentimentTracking: true
})

// AI Processing metrics
const aiProcessingLoad = ref(67)
const todayStats = ref({
  autoProcessed: 245,
  costSavings: 1250,
  routeOptimizations: 89,
  fraudPrevented: 3,
  deliveryAccuracy: 96.7
})

// Route data
const routeData = ref({
  distance: 12.4,
  distanceSaved: 2.1,
  duration: 35,
  timeSaved: 8,
  fuelCost: 15.80,
  fuelSaved: 3.20,
  co2: 4.2,
  co2Saved: 0.8
})

const routeSettings = ref({
  avoidTraffic: true,
  weatherConsideration: true,
  prioritizeSpeed: false,
  ecoFriendly: true,
  algorithm: 'AI Hybrid',
  vehicleCapacity: 50,
  maxDeliveryTime: 4
})

// Predictions
const predictions = ref({
  onTime: 87,
  satisfaction: 4.3,
  weatherRisk: 25,
  returnRisk: 8,
  alerts: [
    "Traffic congestion detected on main route",
    "Weather may impact delivery after 3 PM"
  ]
})

// AI Recommendations
const aiRecommendations = ref([
  {
    id: 1,
    title: "Optimize Delivery Window",
    description: "Adjust delivery time by 30 minutes to avoid traffic and improve efficiency",
    impact: "High",
    priority: "success",
    icon: "mdi-clock-fast"
  },
  {
    id: 2,
    title: "Assign Premium Rider",
    description: "Customer has VIP status. Recommend assigning top-rated rider for better experience",
    impact: "Medium",
    priority: "info",
    icon: "mdi-star"
  },
  {
    id: 3,
    title: "Proactive Communication",
    description: "Send update about potential weather delay to manage customer expectations",
    impact: "Medium",
    priority: "warning",
    icon: "mdi-message-alert"
  }
])

// Communications
const messageSuggestions = ref([
  {
    type: "UPDATE",
    title: "Delivery Update",
    preview: "Your order is on the way and will arrive in approximately 25 minutes...",
    icon: "mdi-truck-delivery",
    urgency: "info"
  },
  {
    type: "DELAY",
    title: "Delay Notification",
    preview: "We're experiencing a slight delay due to traffic conditions...",
    icon: "mdi-clock-alert",
    urgency: "warning"
  },
  {
    type: "COMPLETED",
    title: "Delivery Confirmation",
    preview: "Your order has been successfully delivered. Thank you for choosing us!",
    icon: "mdi-check-circle",
    urgency: "success"
  }
])

const messageComposer = ref({
  content: '',
  tone: 'Professional',
  language: 'English'
})

const isRecording = ref(false)
const voiceTranscript = ref('')

const customerSentiment = ref({
  score: 78,
  label: 'Positive'
})

const communicationStats = ref({
  messagesSent: 42,
  responseRate: 89,
  avgResponseTime: '3.2 min',
  satisfaction: 4.5
})

const chatbotEnabled = ref(true)
const chatbotStats = ref({
  activeChats: 15
})

// AI Assistant
const aiAssistantDialog = ref(false)
const aiQuery = ref('')
const aiMessages = ref([
  {
    id: 1,
    sender: 'ai',
    text: 'Hello! I\'m your AI assistant. I can help you optimize this order, predict issues, or answer questions about delivery logistics.',
    timestamp: new Date()
  }
])

// Computed properties
const order = computed(() => orderStore.selectedOrder)
const isLoading = computed(() => orderStore.loading.orders)

const orderTotal = computed(() => {
  if (!order.value?.orderItems) return 0
  return order.value.orderItems.reduce((sum, item) => {
    return sum + (item.total_price || 0)
  }, 0)
})

// Lifecycle
onMounted(() => {
  // Simulate real-time AI processing
  setInterval(() => {
    aiProcessingLoad.value = Math.max(20, Math.min(95, aiProcessingLoad.value + (Math.random() - 0.5) * 10))
  }, 5000)

  // Rotate AI insights
  setInterval(() => {
    if (aiInsights.value.length > 0) {
      currentInsightIndex.value = (currentInsightIndex.value + 1) % aiInsights.value.length
    }
  }, 10000)
})

// Helper functions
function formatDate(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString()
}

function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString()
}

function formatTime(dateStr) {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
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
    'confirmed': 'info',
    'processing': 'primary',
    'completed': 'success',
    'cancelled': 'error'
  }
  return colors[status?.toLowerCase()] || 'default'
}

function getRiskColor(score) {
  if (score < 30) return 'success'
  if (score < 70) return 'warning'
  return 'error'
}

function getRiskProgressColor(score) {
  if (score < 30) return 'success'
  if (score < 70) return 'warning'
  return 'error'
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

function getAccuracyColor(accuracy) {
  if (accuracy >= 90) return 'success'
  if (accuracy >= 70) return 'warning'
  return 'error'
}

function getFraudColor(score) {
  if (score < 20) return 'success'
  if (score < 50) return 'warning'
  return 'error'
}

function getProbabilityColor(prob) {
  if (prob >= 80) return 'success'
  if (prob >= 60) return 'warning'
  return 'error'
}

function getSentimentColor(score) {
  if (score >= 70) return 'success'
  if (score >= 40) return 'warning'
  return 'error'
}

function getPaymentStatusColor(status) {
  const colors = {
    'pending': 'warning',
    'paid': 'success',
    'partial': 'info',
    'failed': 'error',
    'refunded': 'secondary'
  }
  return colors[status?.toLowerCase()] || 'warning'
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
    // Update order items via API
    console.log('Saving items changes:', itemsEdit.value)
    // Here you would call the API to update items
    // await orderStore.updateOrderItems(order.value.id, itemsEdit.value)
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
    total_price: 0,
    product: { product_name: '', sku: '' }
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

// Charges editing functions
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

function callClient() {
  if (order.value?.client?.phone_number) {
    window.open(`tel:${order.value.client.phone_number}`)
  }
}

// AI Functions
async function openAIAssistant() {
  aiAssistantDialog.value = true
}

async function sendAIQuery() {
  if (!aiQuery.value.trim()) return
  
  // Add user message
  aiMessages.value.push({
    id: Date.now(),
    sender: 'user',
    text: aiQuery.value,
    timestamp: new Date()
  })

  const query = aiQuery.value
  aiQuery.value = ''
  aiProcessing.value = true

  // Simulate AI processing
  setTimeout(() => {
    const responses = [
      "Based on the order data, I recommend optimizing the route to save 15% in delivery time.",
      "The customer has a 92% satisfaction rate. This order should proceed smoothly.",
      "Weather conditions are favorable. No delivery delays expected.",
      "I've analyzed similar orders and predict a 97% on-time delivery probability."
    ]
    
    aiMessages.value.push({
      id: Date.now(),
      sender: 'ai',
      text: responses[Math.floor(Math.random() * responses.length)],
      timestamp: new Date()
    })
    
    aiProcessing.value = false
  }, 1500)
}

async function triggerAutoOptimization() {
  optimizing.value = true
  
  // Simulate optimization process
  setTimeout(() => {
    routeOptimized.value = true
    optimizing.value = false
    
    // Add success insight
    aiInsights.value.unshift({
      message: "Route optimization completed! Saved 8 minutes and $3.20 in fuel costs",
      action: null
    })
  }, 2000)
}

async function runAIValidation() {
  validating.value = true
  
  setTimeout(() => {
    validating.value = false
    // Update order with AI validation results
    if (order.value) {
      order.value.ai_confidence_score = 96
      order.value.ai_risk_score = 15
    }
  }, 1500)
}

async function predictDeliveryIssues() {
  predicting.value = true
  
  setTimeout(() => {
    predicting.value = false
    predictions.value.alerts.push("New prediction: Customer may not be available between 2-3 PM")
  }, 1000)
}

async function runFraudCheck() {
  checkingFraud.value = true
  
  setTimeout(() => {
    checkingFraud.value = false
    if (order.value) {
      order.value.fraud_score = Math.floor(Math.random() * 20) // Low fraud score
    }
  }, 1500)
}

async function autoAssignRider() {
  assigning.value = true
  
  setTimeout(() => {
    assigning.value = false
    if (order.value) {
      order.value.auto_assigned_rider = "John Smith (Rating: 4.9)"
    }
  }, 1000)
}

// Automation Functions
async function generateSmartNotifications() {
  console.log('Generating smart notifications based on customer preferences and delivery status')
}

async function autoOptimizeRoute() {
  optimizing.value = true
  
  setTimeout(() => {
    optimizing.value = false
    routeOptimized.value = true
    
    // Update route data with optimized values
    routeData.value.distance = Math.max(8, routeData.value.distance - 2.1)
    routeData.value.duration = Math.max(20, routeData.value.duration - 8)
  }, 2000)
}

async function recalculateRoute() {
  optimizing.value = true
  
  setTimeout(() => {
    optimizing.value = false
    // Simulate new route calculation
    routeData.value = {
      distance: 11.2,
      distanceSaved: 3.3,
      duration: 28,
      timeSaved: 15,
      fuelCost: 14.20,
      fuelSaved: 4.80,
      co2: 3.8,
      co2Saved: 1.2
    }
  }, 1500)
}

function applyOptimizedRoute() {
  console.log('Applying optimized route to delivery system')
  routeOptimized.value = false
}

// Communication Functions
function generateSmartMessage() {
  const templates = [
    "Hi! Your order is being prepared and will be delivered within the estimated time window.",
    "Good news! Your delivery is ahead of schedule and should arrive 10 minutes early.",
    "We're experiencing slight delays due to traffic, but your order is still on track for delivery."
  ]
  
  messageComposer.value.content = templates[Math.floor(Math.random() * templates.length)]
}

function selectMessageSuggestion(suggestion) {
  messageComposer.value.content = suggestion.preview
}

function toggleVoiceRecording() {
  isRecording.value = !isRecording.value
  
  if (isRecording.value) {
    // Simulate voice recording
    setTimeout(() => {
      if (isRecording.value) {
        voiceTranscript.value = "The customer called asking about delivery time"
        isRecording.value = false
      }
    }, 3000)
  }
}

function improveMessage() {
  // Simulate AI message improvement
  const improved = "Thank you for your order! We're pleased to inform you that your delivery is currently being prepared with the utmost care. Based on current conditions, we anticipate arrival within your specified time window. We'll keep you updated throughout the process."
  messageComposer.value.content = improved
}

function sendMessage() {
  console.log('Sending message:', messageComposer.value)
  messageComposer.value.content = ''
}

// Action Functions
function dismissInsight() {
  aiInsights.value.splice(currentInsightIndex.value, 1)
  if (aiInsights.value.length === 0) {
    currentInsightIndex.value = 0
  } else {
    currentInsightIndex.value = currentInsightIndex.value % aiInsights.value.length
  }
}

function executeAIAction(action) {
  console.log('Executing AI action:', action)
  
  switch (action.type) {
    case 'route_optimization':
      autoOptimizeRoute()
      break
    case 'weather_alert':
      generateSmartNotifications()
      break
    default:
      console.log('Unknown action type')
  }
}

function implementRecommendation(recommendation) {
  console.log('Implementing recommendation:', recommendation)
}

async function runFullAIAnalysis() {
  aiAnalyzing.value = true
  
  setTimeout(() => {
    aiAnalyzing.value = false
    console.log('Full AI analysis completed')
  }, 3000)
}

async function autoExecuteOptimizations() {
  executing.value = true
  
  setTimeout(() => {
    executing.value = false
    console.log('All optimizations executed automatically')
  }, 2500)
}

function exportAIReport() {
  console.log('Exporting AI analysis report')
}

function openAIAnalytics() {
  console.log('Opening full AI analytics dashboard')
}

// Standard functions
function closeDialog() {
  orderStore.closeDialog()
  activeTab.value = 'overview'
}

function refreshOrder() {
  if (order.value?.id) {
    orderStore.loadOrder(order.value.id)
  }
}

function deleteOrder() {
  if (!order.value || !confirm('Are you sure you want to delete this order?')) return
  orderStore.deleteOrder(order.value.id)
  closeDialog()
}

// function startEditingOrder() {
//   console.log('Start editing order')
// }

function printOrder() {
  console.log('Print order')
}
</script>

<style scoped>
.order-details-card {
  max-height: 95vh;
}

.gradient-header {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)), rgb(var(--v-theme-secondary)));
}

.ai-insights-banner {
  border-left: 4px solid rgb(var(--v-theme-info));
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

.automation-controls {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.ai-models {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.model-item {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.automation-stats {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid rgb(var(--v-border-color));
}

.learning-module {
  text-align: center;
  padding: 16px;
  border-radius: 8px;
  background-color: rgb(var(--v-theme-surface-variant));
}

.route-map-placeholder {
  text-align: center;
  padding: 40px;
  border: 2px dashed rgb(var(--v-border-color));
  border-radius: 8px;
  background-color: rgb(var(--v-theme-surface-variant));
}

.route-details {
  margin-top: 16px;
}

.route-metric {
  text-align: center;
  padding: 16px;
  border-radius: 8px;
  background-color: rgb(var(--v-theme-surface-variant));
}

.metric-improvement {
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  margin-top: 4px;
}

.optimization-settings {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.prediction-item {
  margin-bottom: 20px;
}

.forecast-chart-placeholder {
  text-align: center;
  padding: 32px;
  border: 2px dashed rgb(var(--v-border-color));
  border-radius: 8px;
  background-color: rgb(var(--v-theme-surface-variant));
}

.forecast-insights {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.insight-item {
  display: flex;
  align-items: center;
  padding: 8px;
  border-radius: 4px;
  background-color: rgb(var(--v-theme-surface-variant));
}

.suggestion-cards {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.suggestion-card {
  cursor: pointer;
  transition: all 0.2s;
}

.suggestion-card:hover {
  background-color: rgb(var(--v-theme-surface-variant));
}

.sentiment-analysis {
  text-align: center;
}

.sentiment-score {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.communication-stats {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid rgb(var(--v-border-color));
}

.ai-chat-container {
  height: 400px;
  display: flex;
  flex-direction: column;
}

.ai-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background-color: rgb(var(--v-theme-surface-variant));
  border-radius: 8px;
  margin-bottom: 16px;
}

.ai-message {
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}

.message-content {
  flex: 1;
}

.message-text {
  background-color: rgb(var(--v-theme-surface));
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 4px;
}

.message-time {
  font-size: 0.75rem;
  color: rgb(var(--v-theme-on-surface-variant));
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

.order-items-table tbody tr:hover {
  background-color: rgb(var(--v-theme-surface-variant));
}
</style>