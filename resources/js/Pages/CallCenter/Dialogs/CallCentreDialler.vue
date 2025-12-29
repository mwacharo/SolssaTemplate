<template>
    <v-dialog
        :model-value="dialogOpen"
        @update:model-value="updateDialogOpen"
        max-width="1200px"
        persistent
        scrollable
    >
        <v-card class="call-center-dialog">
            <!-- Header -->
            <v-card-title class="dialog-header pa-4">
                <div class="d-flex align-center w-100">
                    <v-avatar color="primary" size="48" class="mr-3">
                        <v-icon color="white" size="28">mdi-headset</v-icon>
                    </v-avatar>
                    <div class="flex-grow-1">
                        <div class="text-h5 font-weight-bold">
                            CallCenter
                        </div>
                        <div class="text-caption text-grey-lighten-1">
                            Order #{{ order?.order_no || "N/A" }} -
                            {{ customer?.full_name || "Unknown Customer" }}
                        </div>
                    </div>
                    <v-chip
                        :color="getOrderStatusColor(order?.status)"
                        variant="flat"
                        class="mr-2"
                    >
                        {{ order?.latest_status?.status?.name || "Pending" }}
                    </v-chip>
                    <v-btn icon variant="text" @click="closeDialog">
                        <v-icon>mdi-close</v-icon>
                    </v-btn>
                </div>
            </v-card-title>

            <v-divider />

            <!-- Main Content -->
            <v-card-text class="pa-0" style="height: 70vh">
                <v-row no-gutters style="height: 100%">
                    <!-- Left Panel - Customer & Order Info -->
                    <v-col cols="12" md="4" class="left-panel">
                        <div class="pa-4">
                            <!-- Customer Information -->
                            <v-card variant="outlined" class="mb-4">
                                <v-card-title
                                    class="text-subtitle-1 bg-blue-grey-lighten-5 py-2"
                                >
                                    <v-icon size="small" class="mr-2"
                                        >mdi-account</v-icon
                                    >
                                    Customer Information
                                </v-card-title>
                                <v-card-text class="pa-3">
                                    <div class="info-row">
                                        <v-icon size="20" class="mr-2"
                                            >mdi-account-circle</v-icon
                                        >
                                        <span class="font-weight-medium">{{
                                            customer?.full_name || "N/A"
                                        }}</span>
                                    </div>
                                    <div class="info-row">
                                        <v-icon size="20" class="mr-2"
                                            >mdi-phone</v-icon
                                        >
                                        <span>{{
                                            customer?.phone || "N/A"
                                        }}</span>
                                        <v-btn
                                            icon
                                            size="x-small"
                                            variant="text"
                                            @click="
                                                copyToClipboard(customer?.phone)
                                            "
                                            class="ml-1"
                                        >
                                            <v-icon size="14"
                                                >mdi-content-copy</v-icon
                                            >
                                        </v-btn>
                                    </div>
                                    <div
                                        class="info-row"
                                        v-if="customer?.email"
                                    >
                                        <v-icon size="20" class="mr-2"
                                            >mdi-email</v-icon
                                        >
                                        <span class="text-truncate">{{
                                            customer.email
                                        }}</span>
                                    </div>
                                    <div
                                        class="info-row"
                                        v-if="customer?.address"
                                    >
                                        <v-icon size="20" class="mr-2"
                                            >mdi-map-marker</v-icon
                                        >
                                        <span class="text-truncate">{{
                                            customer.address
                                        }}</span>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Order Summary -->
                            <v-card variant="outlined" class="mb-4">
                                <v-card-title
                                    class="text-subtitle-1 bg-blue-grey-lighten-5 py-2"
                                >
                                    <v-icon size="small" class="mr-2"
                                        >mdi-package-variant</v-icon
                                    >
                                    Order Summary
                                </v-card-title>
                                <v-card-text class="pa-3">
                                    <div class="info-row">
                                        <span class="text-grey">Order No:</span>
                                        <span class="font-weight-bold">{{
                                            order?.order_no || "N/A"
                                        }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="text-grey">Total:</span>
                                        <span
                                            class="font-weight-bold text-green"
                                        >
                                            KSH
                                            {{ order?.total_price || "0.00" }}
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="text-grey"
                                            >Delivery Date:</span
                                        >
                                        <span>{{
                                            formatDate(order?.delivery_date) ||
                                            "N/A"
                                        }}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="text-grey">Items:</span>
                                        <span
                                            >{{
                                                order?.order_items?.length || 0
                                            }}
                                            product(s)</span
                                        >
                                    </div>

                                    <v-divider class="my-2" />

                                    <!-- Order Items List -->
                                    <div class="order-items-list">
                                        <div
                                            v-for="item in order?.order_items"
                                            :key="item.id"
                                            class="order-item"
                                        >
                                            <div class="d-flex align-center">
                                                <v-avatar
                                                    size="32"
                                                    color="blue-grey-lighten-4"
                                                    class="mr-2"
                                                >
                                                    <v-icon size="18"
                                                        >mdi-package</v-icon
                                                    >
                                                </v-avatar>
                                                <div class="flex-grow-1">
                                                    <div
                                                        class="text-body-2 font-weight-medium"
                                                    >
                                                        {{
                                                            item.product
                                                                ?.product_name ||
                                                            item.name ||
                                                            "Product"
                                                        }}
                                                    </div>
                                                    <div
                                                        class="text-caption text-grey"
                                                    >
                                                        Qty:
                                                        {{ item.quantity }} ×
                                                        KSH
                                                        {{ item.unit_price }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </v-card-text>
                            </v-card>

                            <!-- Quick Actions -->
                            <v-card variant="outlined">
                                <v-card-title
                                    class="text-subtitle-1 bg-blue-grey-lighten-5 py-2"
                                >
                                    <v-icon size="small" class="mr-2"
                                        >mdi-lightning-bolt</v-icon
                                    >
                                    Quick Actions
                                </v-card-title>
                                <v-card-text class="pa-2">
                                    <v-btn
                                        block
                                        variant="outlined"
                                        color="primary"
                                        size="small"
                                        class="mb-2"
                                        @click="viewOrderHistory"
                                    >
                                        <v-icon size="18" class="mr-2"
                                            >mdi-history</v-icon
                                        >
                                        Order History
                                    </v-btn>
                                    <v-btn
                                        block
                                        variant="outlined"
                                        color="orange"
                                        size="small"
                                        @click="viewCustomerOrders"
                                    >
                                        <v-icon size="18" class="mr-2"
                                            >mdi-cart</v-icon
                                        >
                                        Customer Orders
                                    </v-btn>
                                </v-card-text>
                            </v-card>
                        </div>
                    </v-col>

                    <v-divider vertical />

                    <!-- Right Panel - Operations Tabs -->
                    <v-col cols="12" md="8" class="right-panel">
                        <v-tabs
                            v-model="activeTab"
                            bg-color="blue-grey-lighten-5"
                            height="48"
                        >
                            <v-tab value="call">
                                <v-icon class="mr-2">mdi-phone</v-icon>
                                Call
                            </v-tab>
                            <v-tab value="message">
                                <v-icon class="mr-2">mdi-message</v-icon>
                                Message
                            </v-tab>
                            <v-tab value="status">
                                <v-icon class="mr-2">mdi-update</v-icon>
                                Update Status
                            </v-tab>
                            <v-tab value="notes">
                                <v-icon class="mr-2">mdi-note-text</v-icon>
                                Notes
                            </v-tab>
                        </v-tabs>

                        <v-window v-model="activeTab" class="tab-content">
                            <!-- Call Tab -->
                            <v-window-item value="call" class="pa-4">
                                <v-card variant="outlined" class="mb-4">
                                    <v-card-text>
                                        <div class="text-h6 mb-4">
                                            Make a Call
                                        </div>

                                        <v-text-field
                                            v-model="callForm.phone"
                                            label="Phone Number"
                                            prepend-inner-icon="mdi-phone"
                                            variant="outlined"
                                            density="comfortable"
                                            readonly
                                            class="mb-3"
                                        />

                                        <!-- Call Controls -->
                                        <div
                                            v-if="!callActive"
                                            class="text-center"
                                        >
                                            <v-btn
                                                color="green"
                                                size="x-large"
                                                rounded="pill"
                                                elevation="4"
                                                @click="initiateCall"
                                                :loading="calling"
                                                class="call-button"
                                            >
                                                <v-icon size="32" class="mr-2"
                                                    >mdi-phone</v-icon
                                                >
                                                Call Customer
                                            </v-btn>
                                        </div>

                                        <div
                                            v-else
                                            class="call-controls text-center"
                                        >
                                            <div class="call-status mb-4">
                                                <v-avatar
                                                    color="green"
                                                    size="80"
                                                    class="pulse-animation"
                                                >
                                                    <v-icon
                                                        size="40"
                                                        color="white"
                                                        >mdi-phone-in-talk</v-icon
                                                    >
                                                </v-avatar>
                                                <div class="text-h6 mt-3">
                                                    Call in Progress
                                                </div>
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    {{ callDuration }}
                                                </div>
                                            </div>

                                            <div
                                                class="d-flex justify-center gap-3"
                                            >
                                                <v-btn
                                                    icon
                                                    size="large"
                                                    :color="
                                                        muted ? 'error' : 'grey'
                                                    "
                                                    variant="outlined"
                                                    @click="toggleMute"
                                                >
                                                    <v-icon>{{
                                                        muted
                                                            ? "mdi-microphone-off"
                                                            : "mdi-microphone"
                                                    }}</v-icon>
                                                </v-btn>

                                                <v-btn
                                                    icon
                                                    size="large"
                                                    color="grey"
                                                    variant="outlined"
                                                    @click="holdCall"
                                                >
                                                    <v-icon>mdi-pause</v-icon>
                                                </v-btn>

                                                <v-btn
                                                    icon
                                                    size="large"
                                                    color="red"
                                                    variant="flat"
                                                    @click="endCall"
                                                >
                                                    <v-icon
                                                        >mdi-phone-hangup</v-icon
                                                    >
                                                </v-btn>
                                            </div>
                                        </div>

                                        <!-- Call Options -->
                                        <v-expansion-panels
                                            class="mt-4"
                                            variant="accordion"
                                        >
                                            <v-expansion-panel>
                                                <v-expansion-panel-title>
                                                    <v-icon class="mr-2"
                                                        >mdi-cog</v-icon
                                                    >
                                                    Call Options
                                                </v-expansion-panel-title>
                                                <v-expansion-panel-text>
                                                    <v-checkbox
                                                        v-model="
                                                            callForm.recordCall
                                                        "
                                                        label="Record this call"
                                                        density="compact"
                                                    />
                                                    <v-checkbox
                                                        v-model="
                                                            callForm.autoLog
                                                        "
                                                        label="Auto-log call notes"
                                                        density="compact"
                                                    />
                                                </v-expansion-panel-text>
                                            </v-expansion-panel>
                                        </v-expansion-panels>
                                    </v-card-text>
                                </v-card>

                                <!-- Recent Call History -->
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">
                                        <v-icon class="mr-2"
                                            >mdi-history</v-icon
                                        >
                                        Recent Calls
                                    </v-card-title>
                                    <v-card-text>
                                        <v-list density="compact">
                                            <v-list-item
                                                v-for="call in recentCalls"
                                                :key="call.id"
                                                class="mb-1"
                                            >
                                                <template #prepend>
                                                    <v-icon
                                                        :color="
                                                            call.type ===
                                                            'incoming'
                                                                ? 'blue'
                                                                : 'green'
                                                        "
                                                    >
                                                        {{
                                                            call.type ===
                                                            "incoming"
                                                                ? "mdi-phone-incoming"
                                                                : "mdi-phone-outgoing"
                                                        }}
                                                    </v-icon>
                                                </template>
                                                <v-list-item-title>{{
                                                    call.duration
                                                }}</v-list-item-title>
                                                <v-list-item-subtitle>{{
                                                    call.timestamp
                                                }}</v-list-item-subtitle>
                                                <template #append>
                                                    <v-btn
                                                        icon
                                                        size="x-small"
                                                        variant="text"
                                                    >
                                                        <v-icon
                                                            >mdi-play</v-icon
                                                        >
                                                    </v-btn>
                                                </template>
                                            </v-list-item>
                                        </v-list>
                                    </v-card-text>
                                </v-card>
                            </v-window-item>

                            <!-- Message Tab -->
                            <v-window-item value="message" class="pa-4">
                                <v-card variant="outlined">
                                    <v-card-text>
                                        <div class="text-h6 mb-4">
                                            Send Message
                                        </div>

                                        <!-- Message Type Selector -->
                                        <v-btn-toggle
                                            v-model="messageType"
                                            mandatory
                                            divided
                                            variant="outlined"
                                            class="mb-4"
                                        >
                                            <v-btn value="whatsapp">
                                                <v-icon class="mr-2"
                                                    >mdi-whatsapp</v-icon
                                                >
                                                WhatsApp
                                            </v-btn>
                                            <v-btn value="sms">
                                                <v-icon class="mr-2"
                                                    >mdi-message-text</v-icon
                                                >
                                                SMS
                                            </v-btn>
                                            <v-btn value="email">
                                                <v-icon class="mr-2"
                                                    >mdi-email</v-icon
                                                >
                                                Email
                                            </v-btn>
                                        </v-btn-toggle>

                                        <!-- Template Selector -->
                                        <v-select
                                            v-model="selectedTemplate"
                                            :items="templates"
                                            item-title="name"
                                            item-value="id"
                                            label="Select Template (Optional)"
                                            variant="outlined"
                                            density="comfortable"
                                            clearable
                                            class="mb-3"
                                            @update:model-value="loadTemplate"
                                        >
                                            <template #item="{ props, item }">
                                                <v-list-item v-bind="props">
                                                    <template #prepend>
                                                        <v-icon
                                                            >mdi-file-document-outline</v-icon
                                                        >
                                                    </template>
                                                    <v-list-item-title>{{
                                                        item.raw.name
                                                    }}</v-list-item-title>
                                                    <v-list-item-subtitle>{{
                                                        item.raw.description
                                                    }}</v-list-item-subtitle>
                                                </v-list-item>
                                            </template>
                                        </v-select>

                                        <!-- Email Subject (only for email) -->
                                        <v-text-field
                                            v-if="messageType === 'email'"
                                            v-model="messageForm.subject"
                                            label="Subject"
                                            variant="outlined"
                                            density="comfortable"
                                            class="mb-3"
                                        />

                                        <!-- Message Content -->
                                        <v-textarea
                                            v-model="messageForm.content"
                                            label="Message"
                                            variant="outlined"
                                            rows="8"
                                            counter
                                            :maxlength="
                                                messageType === 'sms'
                                                    ? 160
                                                    : 1000
                                            "
                                            class="mb-3"
                                        />

                                        <!-- Variable Chips -->
                                        <div class="mb-3">
                                            <div
                                                class="text-caption text-grey mb-2"
                                            >
                                                Insert Variables:
                                            </div>
                                            <v-chip-group>
                                                <v-chip
                                                    size="small"
                                                    @click="
                                                        insertVariable(
                                                            '{customer_name}'
                                                        )
                                                    "
                                                >
                                                    Customer Name
                                                </v-chip>
                                                <v-chip
                                                    size="small"
                                                    @click="
                                                        insertVariable(
                                                            '{order_no}'
                                                        )
                                                    "
                                                >
                                                    Order Number
                                                </v-chip>
                                                <v-chip
                                                    size="small"
                                                    @click="
                                                        insertVariable(
                                                            '{delivery_date}'
                                                        )
                                                    "
                                                >
                                                    Delivery Date
                                                </v-chip>
                                                <v-chip
                                                    size="small"
                                                    @click="
                                                        insertVariable(
                                                            '{total_price}'
                                                        )
                                                    "
                                                >
                                                    Total Price
                                                </v-chip>
                                            </v-chip-group>
                                        </div>

                                        <!-- Attachments (for email) -->
                                        <div
                                            v-if="messageType === 'email'"
                                            class="mb-3"
                                        >
                                            <v-btn
                                                variant="outlined"
                                                size="small"
                                                prepend-icon="mdi-paperclip"
                                                @click="$refs.fileInput.click()"
                                            >
                                                Attach File
                                            </v-btn>
                                            <input
                                                ref="fileInput"
                                                type="file"
                                                style="display: none"
                                                @change="handleFileUpload"
                                            />
                                            <div
                                                v-if="
                                                    messageForm.attachments
                                                        .length
                                                "
                                                class="mt-2"
                                            >
                                                <v-chip
                                                    v-for="(
                                                        file, idx
                                                    ) in messageForm.attachments"
                                                    :key="idx"
                                                    closable
                                                    @click:close="
                                                        removeAttachment(idx)
                                                    "
                                                    size="small"
                                                    class="mr-2"
                                                >
                                                    {{ file.name }}
                                                </v-chip>
                                            </div>
                                        </div>

                                        <!-- Preview -->
                                        <v-card
                                            variant="tonal"
                                            color="blue-grey-lighten-5"
                                            class="mb-3"
                                        >
                                            <v-card-text class="text-body-2">
                                                <div
                                                    class="font-weight-bold mb-2"
                                                >
                                                    Preview:
                                                </div>
                                                <div class="preview-content">
                                                    {{ previewMessage }}
                                                </div>
                                            </v-card-text>
                                        </v-card>

                                        <v-btn
                                            block
                                            color="primary"
                                            size="large"
                                            :loading="sending"
                                            @click="sendMessage"
                                        >
                                            <v-icon class="mr-2"
                                                >mdi-send</v-icon
                                            >
                                            Send {{ messageType.toUpperCase() }}
                                        </v-btn>
                                    </v-card-text>
                                </v-card>
                            </v-window-item>

                            <!-- Status Update Tab -->
                            <v-window-item value="status" class="pa-4">
                                <v-card variant="outlined">
                                    <v-card-text>
                                        <div class="text-h6 mb-4">
                                            Update Order Status
                                        </div>

                                        <v-form ref="statusForm">
                                            <!-- Current Status Display -->
                                            <v-alert
                                                type="info"
                                                variant="tonal"
                                                density="compact"
                                                class="mb-4"
                                            >
                                                <div class="text-body-2">
                                                    <strong
                                                        >Current Status:</strong
                                                    >
                                                    {{
                                                        order?.latest_status
                                                            ?.status?.name ||
                                                        "Pending"
                                                    }}
                                                </div>
                                                <div
                                                    class="text-caption"
                                                    v-if="
                                                        order?.latest_status
                                                            ?.created_at
                                                    "
                                                >
                                                    Updated
                                                    {{
                                                        formatDateTime(
                                                            order.latest_status
                                                                .created_at
                                                        )
                                                    }}
                                                </div>
                                            </v-alert>

                                            <!-- New Status -->
                                            <v-select
                                                v-model="statusForm.status_id"
                                                :items="statusOptions"
                                                item-title="name"
                                                item-value="id"
                                                label="New Status"
                                                variant="outlined"
                                                density="comfortable"
                                                :rules="[
                                                    (v) =>
                                                        !!v ||
                                                        'Status is required',
                                                ]"
                                                class="mb-3"
                                            >
                                                <template
                                                    #item="{ props, item }"
                                                >
                                                    <v-list-item v-bind="props">
                                                        <template #prepend>
                                                            <v-icon
                                                                :color="
                                                                    item.raw
                                                                        .color
                                                                "
                                                                >mdi-circle</v-icon
                                                            >
                                                        </template>
                                                    </v-list-item>
                                                </template>
                                            </v-select>

                                            <!-- Status Category -->
                                            <v-select
                                                v-model="
                                                    statusForm.status_category_id
                                                "
                                                :items="
                                                    filteredStatusCategories
                                                "
                                                item-title="name"
                                                item-value="id"
                                                label="Status Category"
                                                variant="outlined"
                                                density="comfortable"
                                                :disabled="
                                                    !statusForm.status_id
                                                "
                                                class="mb-3"
                                            />

                                            <!-- Recall Date (if Follow Up category) -->
                                            <v-text-field
                                                v-if="shouldShowRecallDate"
                                                v-model="statusForm.recall_date"
                                                label="Recall Date & Time"
                                                type="datetime-local"
                                                variant="outlined"
                                                density="comfortable"
                                                :rules="[
                                                    (v) =>
                                                        !!v ||
                                                        'Recall date is required',
                                                ]"
                                                class="mb-3"
                                            />

                                            <!-- Status Notes -->
                                            <v-textarea
                                                v-model="
                                                    statusForm.status_notes
                                                "
                                                label="Status Notes"
                                                variant="outlined"
                                                rows="4"
                                                :rules="[
                                                    (v) =>
                                                        !!v ||
                                                        'Notes are required',
                                                ]"
                                                hint="Add any relevant information about this status change"
                                                class="mb-3"
                                            />

                                            <!-- Notify Customer -->
                                            <v-checkbox
                                                v-model="
                                                    statusForm.notifyCustomer
                                                "
                                                label="Notify customer of status change"
                                                density="compact"
                                                class="mb-3"
                                            />

                                            <v-expand-transition>
                                                <div
                                                    v-if="
                                                        statusForm.notifyCustomer
                                                    "
                                                    class="ml-8 mb-3"
                                                >
                                                    <v-radio-group
                                                        v-model="
                                                            statusForm.notificationMethod
                                                        "
                                                        density="compact"
                                                        inline
                                                    >
                                                        <v-radio
                                                            label="SMS"
                                                            value="sms"
                                                        />
                                                        <v-radio
                                                            label="WhatsApp"
                                                            value="whatsapp"
                                                        />
                                                        <v-radio
                                                            label="Email"
                                                            value="email"
                                                        />
                                                    </v-radio-group>
                                                </div>
                                            </v-expand-transition>

                                            <v-btn
                                                block
                                                color="primary"
                                                size="large"
                                                :loading="updatingStatus"
                                                @click="updateStatus"
                                            >
                                                <v-icon class="mr-2"
                                                    >mdi-update</v-icon
                                                >
                                                Update Status
                                            </v-btn>
                                        </v-form>

                                        <!-- Status History -->
                                        <v-divider class="my-4" />

                                        <div
                                            class="text-subtitle-1 font-weight-medium mb-3"
                                        >
                                            <v-icon class="mr-2"
                                                >mdi-timeline</v-icon
                                            >
                                            Status History
                                        </div>

                                        <v-timeline
                                            density="compact"
                                            side="end"
                                            align="start"
                                        >
                                            <v-timeline-item
                                                v-for="(
                                                    status, idx
                                                ) in statusHistory"
                                                :key="idx"
                                                :dot-color="status.color"
                                                size="small"
                                            >
                                                <div
                                                    class="text-body-2 font-weight-medium"
                                                >
                                                    {{ status.status_name }}
                                                </div>
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    {{ status.notes }}
                                                </div>
                                                <div
                                                    class="text-caption text-grey"
                                                >
                                                    {{
                                                        formatDateTime(
                                                            status.created_at
                                                        )
                                                    }}
                                                    <span v-if="status.user">
                                                        by
                                                        {{ status.user }}</span
                                                    >
                                                </div>
                                            </v-timeline-item>
                                        </v-timeline>
                                    </v-card-text>
                                </v-card>
                            </v-window-item>

                            <!-- Notes Tab -->
                            <v-window-item value="notes" class="pa-4">
                                <v-card variant="outlined" class="mb-4">
                                    <v-card-text>
                                        <div class="text-h6 mb-4">Add Note</div>

                                        <v-select
                                            v-model="noteForm.type"
                                            :items="noteTypes"
                                            label="Note Type"
                                            variant="outlined"
                                            density="comfortable"
                                            class="mb-3"
                                        />

                                        <v-textarea
                                            v-model="noteForm.content"
                                            label="Note Content"
                                            variant="outlined"
                                            rows="6"
                                            counter
                                            maxlength="500"
                                            class="mb-3"
                                        />

                                        <v-checkbox
                                            v-model="noteForm.pinned"
                                            label="Pin this note"
                                            density="compact"
                                            class="mb-3"
                                        />

                                        <v-btn
                                            block
                                            color="primary"
                                            :loading="savingNote"
                                            @click="saveNote"
                                        >
                                            <v-icon class="mr-2"
                                                >mdi-content-save</v-icon
                                            >
                                            Save Note
                                        </v-btn>
                                    </v-card-text>
                                </v-card>

                                <!-- Notes List -->
                                <v-card variant="outlined">
                                    <v-card-title class="text-subtitle-1">
                                        <v-icon class="mr-2"
                                            >mdi-note-multiple</v-icon
                                        >
                                        All Notes
                                    </v-card-title>
                                    <v-card-text>
                                        <div
                                            v-for="note in notes"
                                            :key="note.id"
                                            class="note-item mb-3"
                                        >
                                            <v-card variant="tonal">
                                                <v-card-text>
                                                    <div
                                                        class="d-flex align-center mb-2"
                                                    >
                                                        <v-chip
                                                            size="x-small"
                                                            :color="
                                                                getNoteTypeColor(
                                                                    note.type
                                                                )
                                                            "
                                                            class="mr-2"
                                                        >
                                                            {{ note.type }}
                                                        </v-chip>
                                                        <v-icon
                                                            v-if="note.pinned"
                                                            size="small"
                                                            color="warning"
                                                            class="mr-2"
                                                        >
                                                            mdi-pin
                                                        </v-icon>
                                                        <v-spacer />
                                                        <span
                                                            class="text-caption text-grey"
                                                        >
                                                            {{
                                                                formatDateTime(
                                                                    note.created_at
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                                    <div class="text-body-2">
                                                        {{ note.content }}
                                                    </div>
                                                    <div
                                                        class="text-caption text-grey mt-2"
                                                    >
                                                        By {{ note.user_name }}
                                                    </div>
                                                </v-card-text>
                                            </v-card>
                                        </div>
                                    </v-card-text>
                                </v-card>
                            </v-window-item>
                        </v-window>
                    </v-col>
                </v-row>
            </v-card-text>

            <v-divider />

            <!-- Footer Actions -->
            <v-card-actions class="pa-4">
                <v-btn variant="outlined" @click="closeDialog"> Close </v-btn>
                <v-spacer />
                <v-btn
                    color="success"
                    variant="flat"
                    prepend-icon="mdi-check-circle"
                    @click="completeAndClose"
                >
                    Mark Complete & Close
                </v-btn>
            </v-card-actions>
        </v-card>

        <!-- Success Snackbar -->
        <v-snackbar
            v-model="snackbar.show"
            :color="snackbar.color"
            :timeout="3000"
        >
            {{ snackbar.message }}
            <template #actions>
                <v-btn variant="text" @click="snackbar.show = false">
                    Close
                </v-btn>
            </template>
        </v-snackbar>
    </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { usecallCentreDiallerStore } from "@/stores/callCentreDialler";

// Props
const props = defineProps({
    modelValue: Boolean,
    order: Object,
});

const emit = defineEmits(["update:modelValue", "status-updated", "note-added"]);

// Dialog state
const dialog = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});




// Get store instance
const store = usecallCentreDiallerStore();

// Dialog state - use computed to sync with store
const dialogOpen = computed({
    get: () => props.modelValue,
    set: (val) => emit("update:modelValue", val),
});

// Method to update dialog state
const updateDialogOpen = (value) => {
    emit("update:modelValue", value);
    if (!value) {
        store.closeDialog();
    }
};
// Active tab
const activeTab = ref("call");

// Customer info
const customer = computed(() => {
    return (
        props.order?.customer ||
        props.order?.customer_address ||
        props.order?.shipping_address ||
        {}
    );
});

// Call state
const callActive = ref(false);
const calling = ref(false);
const callDuration = ref("00:00");
const muted = ref(false);
const callForm = ref({
    phone: "",
    recordCall: true,
    autoLog: true,
});

// Message state
const messageType = ref("whatsapp");
const selectedTemplate = ref(null);
const sending = ref(false);
const messageForm = ref({
    subject: "",
    content: "",
    attachments: [],
});

// Status state
const updatingStatus = ref(false);
const statusForm = ref({
    status_id: null,
    status_category_id: null,
    status_notes: "",
    recall_date: "",
    notifyCustomer: true,
    notificationMethod: "sms",
});

// Notes state
const savingNote = ref(false);
const noteForm = ref({
    type: "General",
    content: "",
    pinned: false,
});

// Snackbar
const snackbar = ref({
    show: false,
    message: "",
    color: "success",
});

// Sample data
const templates = ref([
    {
        id: 1,
        name: "Order Confirmation",
        description: "Confirm order details",
        content:
            "Dear {customer_name}, your order #{order_no} has been confirmed.",
    },
    {
        id: 2,
        name: "Delivery Update",
        description: "Update on delivery status",
        content:
            "Hi {customer_name}, your order will be delivered on {delivery_date}.",
    },
    {
        id: 3,
        name: "Payment Reminder",
        description: "Remind about pending payment",
        content:
            "Hello {customer_name}, please complete payment of KSH {total_price} for order #{order_no}.",
    },
]);

const statusOptions = ref([
    { id: 1, name: "Confirmed", color: "green" },
    { id: 2, name: "Processing", color: "blue" },
    { id: 3, name: "Shipped", color: "orange" },
    { id: 4, name: "Delivered", color: "teal" },
    { id: 5, name: "Follow Up", color: "purple" },
]);
const statusCategories = ref([
    { id: 1, name: "Standard", status_id: 1 },
    { id: 2, name: "Expedited", status_id: 2 },
    { id: 3, name: "Follow Up", status_id: 5 },
]);

// Continuing from the script setup...

const noteTypes = ref([
    "General",
    "Call Summary",
    "Customer Request",
    "Issue",
    "Follow Up",
]);

const recentCalls = ref([
    { id: 1, type: "outgoing", duration: "5:23", timestamp: "2 hours ago" },
    { id: 2, type: "incoming", duration: "3:45", timestamp: "Yesterday" },
]);

const statusHistory = ref([
    {
        status_name: "Order Placed",
        notes: "Customer placed order online",
        created_at: "2024-01-15T10:30:00",
        user: "System",
        color: "blue",
    },
    {
        status_name: "Payment Confirmed",
        notes: "Payment received via M-Pesa",
        created_at: "2024-01-15T10:35:00",
        user: "John Doe",
        color: "green",
    },
]);

const notes = ref([
    {
        id: 1,
        type: "Call Summary",
        content:
            "Customer called to confirm delivery address. Updated address in system.",
        pinned: true,
        created_at: "2024-01-15T14:20:00",
        user_name: "Jane Smith",
    },
    {
        id: 2,
        type: "General",
        content: "Customer prefers morning deliveries between 8-10 AM.",
        pinned: false,
        created_at: "2024-01-15T14:25:00",
        user_name: "Jane Smith",
    },
]);

// Computed
const filteredStatusCategories = computed(() => {
    if (!statusForm.value.status_id) return [];
    return statusCategories.value.filter(
        (cat) => cat.status_id === statusForm.value.status_id
    );
});

const shouldShowRecallDate = computed(() => {
    const selectedCategory = statusCategories.value.find(
        (cat) => cat.id === statusForm.value.status_category_id
    );
    return selectedCategory?.name === "Follow Up";
});

const previewMessage = computed(() => {
    let content =
        messageForm.value.content || "Your message will appear here...";

    // Replace variables with actual values
    content = content.replace(
        "{customer_name}",
        customer.value.full_name || "Customer"
    );
    content = content.replace("{order_no}", props.order?.order_no || "N/A");
    content = content.replace(
        "{delivery_date}",
        formatDate(props.order?.delivery_date) || "N/A"
    );
    content = content.replace(
        "{total_price}",
        props.order?.total_price || "0.00"
    );

    return content;
});

// Watch for order changes
watch(
    () => props.order,
    (newOrder) => {
        if (newOrder) {
            callForm.value.phone = customer.value.phone || "";

            // Load status history if available
            if (newOrder.order_statuses && newOrder.order_statuses.length > 0) {
                statusHistory.value = newOrder.order_statuses.map((status) => ({
                    status_name: status.status?.name || "Unknown",
                    notes: status.status_notes || "No notes",
                    created_at: status.created_at,
                    user: status.user?.name || "System",
                    color: getOrderStatusColor(status.status?.name),
                }));
            }
        }
    },
    { immediate: true, deep: true }
);

// Methods
const getOrderStatusColor = (status) => {
    const statusMap = {
        Pending: "orange",
        Confirmed: "green",
        Processing: "blue",
        Shipped: "purple",
        Delivered: "teal",
        Cancelled: "red",
        "Follow Up": "amber",
    };
    return statusMap[status] || "grey";
};

const getNoteTypeColor = (type) => {
    const typeMap = {
        General: "blue-grey",
        "Call Summary": "blue",
        "Customer Request": "purple",
        Issue: "red",
        "Follow Up": "orange",
    };
    return typeMap[type] || "grey";
};

const formatDate = (date) => {
    if (!date) return "";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatDateTime = (datetime) => {
    if (!datetime) return "";
    return new Date(datetime).toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        showSnackbar("Phone number copied to clipboard", "success");
    } catch (err) {
        showSnackbar("Failed to copy", "error");
    }
};

const showSnackbar = (message, color = "success") => {
    snackbar.value = {
        show: true,
        message,
        color,
    };
};

// Call functions
const initiateCall = () => {
    calling.value = true;

    setTimeout(() => {
        calling.value = false;
        callActive.value = true;
        startCallTimer();
        showSnackbar("Call connected", "success");
    }, 2000);
};

const startCallTimer = () => {
    let seconds = 0;
    const timer = setInterval(() => {
        if (!callActive.value) {
            clearInterval(timer);
            return;
        }
        seconds++;
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        callDuration.value = `${String(mins).padStart(2, "0")}:${String(
            secs
        ).padStart(2, "0")}`;
    }, 1000);
};

const toggleMute = () => {
    muted.value = !muted.value;
    showSnackbar(
        muted.value ? "Microphone muted" : "Microphone unmuted",
        "info"
    );
};

const holdCall = () => {
    showSnackbar("Call on hold", "info");
};

const endCall = () => {
    callActive.value = false;
    callDuration.value = "00:00";
    showSnackbar("Call ended", "info");

    // Add to recent calls
    recentCalls.value.unshift({
        id: Date.now(),
        type: "outgoing",
        duration: callDuration.value,
        timestamp: "Just now",
    });
};

// Message functions
const loadTemplate = (templateId) => {
    const template = templates.value.find((t) => t.id === templateId);
    if (template) {
        messageForm.value.content = template.content;
        if (messageType.value === "email") {
            messageForm.value.subject = template.name;
        }
    }
};

const insertVariable = (variable) => {
    const textarea = document.querySelector("textarea");
    if (textarea) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = messageForm.value.content;
        messageForm.value.content =
            text.substring(0, start) + variable + text.substring(end);
    } else {
        messageForm.value.content += variable;
    }
};

const handleFileUpload = (event) => {
    const files = Array.from(event.target.files);
    messageForm.value.attachments.push(...files);
};

const removeAttachment = (index) => {
    messageForm.value.attachments.splice(index, 1);
};

const sendMessage = async () => {
    if (!messageForm.value.content.trim()) {
        showSnackbar("Please enter a message", "error");
        return;
    }

    sending.value = true;

    // Simulate API call
    setTimeout(() => {
        sending.value = false;
        showSnackbar(
            `${messageType.value.toUpperCase()} sent successfully`,
            "success"
        );

        // Reset form
        messageForm.value = {
            subject: "",
            content: "",
            attachments: [],
        };
        selectedTemplate.value = null;
    }, 1500);
};

// Status functions
const updateStatus = async () => {
    if (!statusForm.value.status_id || !statusForm.value.status_notes) {
        showSnackbar("Please fill in all required fields", "error");
        return;
    }

    updatingStatus.value = true;

    // Simulate API call
    setTimeout(() => {
        updatingStatus.value = false;
        showSnackbar("Status updated successfully", "success");

        // Add to status history
        const selectedStatus = statusOptions.value.find(
            (s) => s.id === statusForm.value.status_id
        );
        statusHistory.value.unshift({
            status_name: selectedStatus.name,
            notes: statusForm.value.status_notes,
            created_at: new Date().toISOString(),
            user: "Current User",
            color: selectedStatus.color,
        });

        emit("status-updated", statusForm.value);

        // Reset form
        statusForm.value = {
            status_id: null,
            status_category_id: null,
            status_notes: "",
            recall_date: "",
            notifyCustomer: true,
            notificationMethod: "sms",
        };
    }, 1500);
};

// Notes functions
const saveNote = async () => {
    if (!noteForm.value.content.trim()) {
        showSnackbar("Please enter note content", "error");
        return;
    }

    savingNote.value = true;

    // Simulate API call
    setTimeout(() => {
        savingNote.value = false;

        const newNote = {
            id: Date.now(),
            type: noteForm.value.type,
            content: noteForm.value.content,
            pinned: noteForm.value.pinned,
            created_at: new Date().toISOString(),
            user_name: "Current User",
        };

        notes.value.unshift(newNote);
        showSnackbar("Note saved successfully", "success");
        emit("note-added", newNote);

        // Reset form
        noteForm.value = {
            type: "General",
            content: "",
            pinned: false,
        };
    }, 1000);
};

// Other actions
const viewOrderHistory = () => {
    showSnackbar("Opening order history...", "info");
    // Implement navigation to order history
};

const viewCustomerOrders = () => {
    showSnackbar("Opening customer orders...", "info");
    // Implement navigation to customer orders
};

const completeAndClose = () => {
    showSnackbar("Order marked as complete", "success");
    closeDialog();
};

const closeDialog = () => {
    dialog.value = false;
    // Reset all forms
    activeTab.value = "call";
    callActive.value = false;
    messageForm.value = {
        subject: "",
        content: "",
        attachments: [],
    };
    statusForm.value = {
        status_id: null,
        status_category_id: null,
        status_notes: "",
        recall_date: "",
        notifyCustomer: true,
        notificationMethod: "sms",
    };
    noteForm.value = {
        type: "General",
        content: "",
        pinned: false,
    };
};
</script>

<style scoped>
.call-center-dialog {
    height: 100%;
}

.dialog-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.left-panel {
    background-color: #f5f5f5;
    overflow-y: auto;
}

.right-panel {
    overflow-y: auto;
}

.info-row {
    display: flex;
    align-items: center;
    padding: 8px 0;
    gap: 8px;
}

.order-items-list {
    max-height: 200px;
    overflow-y: auto;
}

.order-item {
    padding: 8px;
    margin-bottom: 8px;
    border-radius: 4px;
    background-color: rgba(0, 0, 0, 0.02);
}

.tab-content {
    height: calc(70vh - 48px);
    overflow-y: auto;
}

.call-button {
    padding: 20px 40px !important;
}

.call-controls {
    padding: 20px;
}

.pulse-animation {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%,
    100% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.05);
        opacity: 0.9;
    }
}

.preview-content {
    white-space: pre-wrap;
    word-wrap: break-word;
    padding: 12px;
    background: white;
    border-radius: 4px;
    min-height: 60px;
}

.note-item {
    transition: all 0.3s ease;
}

.note-item:hover {
    transform: translateY(-2px);
}

/* Scrollbar styling */
.left-panel::-webkit-scrollbar,
.right-panel::-webkit-scrollbar,
.tab-content::-webkit-scrollbar {
    width: 8px;
}

.left-panel::-webkit-scrollbar-track,
.right-panel::-webkit-scrollbar-track,
.tab-content::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.left-panel::-webkit-scrollbar-thumb,
.right-panel::-webkit-scrollbar-thumb,
.tab-content::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.left-panel::-webkit-scrollbar-thumb:hover,
.right-panel::-webkit-scrollbar-thumb:hover,
.tab-content::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
