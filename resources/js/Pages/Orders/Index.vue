<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-50">
            <div class="p-6">
                <!-- Search Bar -->
                <div class="mb-6">
                    <div class="relative">
                        <input
                            v-model="orderStore.orderSearch"
                            type="text"
                            placeholder="Search orders by (id, customer name, phone, address)"
                            class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            @input="debouncedSearch"
                        />
                        <button
                            class="absolute right-2 top-2 p-1 text-gray-500 hover:text-gray-700"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                ></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-4"
                    >
                        <!-- Seller Filter -->
                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >SELLER</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterVendor"
                                :items="orderStore.vendorOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Search sellers..."
                                class="w-full"
                                autocomplete
                            />
                        </div>

                        <!-- Product Filter -->
                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >PRODUCT</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterProduct"
                                :items="orderStore.productOptions"
                                item-title="product_name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Search products..."
                                class="w-full"
                                autocomplete
                            />
                        </div>

                        <!-- Category Filter -->
                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >CATEGORY</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterCategory"
                                :items="orderStore.categoryOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Search categories..."
                                class="w-full"
                                autocomplete
                            />
                        </div>

                        <!-- City Filter -->
                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >CITY</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterCity"
                                :items="orderStore.cityOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Search cities..."
                                class="w-full"
                                autocomplete
                            />
                        </div>

                        <!-- zone Filter-->

                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >ZONE</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterZone"
                                :items="orderStore.zoneOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Search zones..."
                                class="w-full"
                                autocomplete
                            />
                        </div>

                        <!-- Call Agent Filter -->
                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >CALL AGENT</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterAgent"
                                :items="orderStore.agentOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Search agents..."
                                class="w-full"
                                autocomplete
                            />
                        </div>

                        <!-- Delivery Person Filter -->

                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >DELIVERY PERSON</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterRider"
                                :items="orderStore.riderOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Search delivery persons..."
                                class="w-full"
                                autocomplete
                            />
                        </div>

                        <!-- Confirmation Status -->

                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >CONFIRMATION STATUS</label
                            >
                            <v-autocomplete
                                v-model="orderStore.orderFilterStatus"
                                :items="orderStore.statusOptions"
                                item-title="name"
                                item-value="id"
                                clearable
                                dense
                                outlined
                                placeholder="Search statuses..."
                                class="w-full"
                            />
                        </div>
                        <!-- delivery date  -->

                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >DELIVERY DATE</label
                            >

                            <DateRangePicker
                                v-model="orderStore.deliveryDateRange"
                            />
                        </div>

                        <!-- Status Date  -->

                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >STATUS DATE
                            </label>
                            <DateRangePicker
                                v-model="orderStore.statusDateRange"
                            />
                        </div>

                        <!-- Created From -->
                        <div>
                            <label
                                class="block text-sm font-medium text-red-600 mb-1"
                                >CREATED RANGE</label
                            >

                            <DateRangePicker
                                v-model="orderStore.createdDateRange"
                            />
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex justify-center gap-4">
                        <button
                            @click="applyFilters"
                            :disabled="orderStore.loading.orders"
                            class="px-8 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors flex items-center gap-2 disabled:opacity-50"
                        >
                            <span
                                v-if="orderStore.loading.orders"
                                class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
                            ></span>
                            Filter
                            <svg
                                v-if="!orderStore.loading.orders"
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.121A1 1 0 013 6.414V4z"
                                ></path>
                            </svg>
                        </button>
                        <button
                            @click="resetFilters"
                            class="px-8 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors flex items-center gap-2"
                        >
                            Reset
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                ></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Active Filters Display -->
                    <div
                        v-if="orderStore.activeFilterCount > 0"
                        class="mt-4 flex items-center gap-2 text-sm text-gray-600"
                    >
                        <span
                            >Active filters:
                            {{ orderStore.activeFilterCount }}</span
                        >
                        <button
                            @click="resetFilters"
                            class="text-red-600 hover:text-red-800 underline"
                        >
                            Clear all
                        </button>
                    </div>
                </div>

                <!-- Orders Header -->
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center gap-2">
                        <h1 class="text-2xl font-semibold">Orders</h1>
                        <span class="text-sm text-gray-500">
                            {{ orderStore.pagination.from }} -
                            {{ orderStore.pagination.to }}/{{
                                orderStore.pagination.total
                            }}
                        </span>
                    </div>

                    <div class="flex gap-2 flex-wrap">
                        <button
                            @click="associateAgents"
                            :disabled="
                                selectedOrders.length === 0 ||
                                orderStore.loading.orders
                            "
                            class="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors flex items-center gap-2 text-sm"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                            Associate agents
                        </button>

                        <button
                            @click="newOrder"
                            class="px-3 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center gap-2 text-sm"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                ></path>
                            </svg>
                            New Order
                        </button>

                        <!-- bulk print -->

                        <button
                            @click="bulkPrint"
                            :disabled="
                                selectedOrders.length === 0 ||
                                orderStore.loading.orders
                            "
                            class="px-3 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-900 transition-colors flex items-center gap-2 text-sm disabled:opacity-50"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2h-2M7 17H5a2 2 0 01-2-2v-4a2 2 0 012-2h2m10 9v1a3 3 0 01-3 3H10a3 3 0 01-3-3v-1m10 0H7m10 0a5 5 0 00-10 0m10 0v-3a5 5 0 00-10 0v3"
                                ></path>
                            </svg>
                            Print ({{ selectedOrders.length }})
                        </button>

                        <button
                            @click="exportOrders"
                            :disabled="orderStore.loading.orders"
                            class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2 text-sm disabled:opacity-50"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                                ></path>
                            </svg>
                            Export Orders
                        </button>

                        <button
                            @click="bulkUpdateStatus"
                            :disabled="
                                selectedOrders.length === 0 ||
                                orderStore.loading.orders
                            "
                            class="px-3 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors flex items-center gap-2 text-sm disabled:opacity-50"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                ></path>
                            </svg>
                            Change Status ({{ selectedOrders.length }})
                        </button>

                        <!-- assign delivery person -->
                        <button
                            @click="assignDeliveryPerson"
                            :disabled="
                                selectedOrders.length === 0 ||
                                orderStore.loading.orders
                            "
                            class="px-3 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors flex items-center gap-2 text-sm disabled:opacity-50"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 10h1l1 2h13l1-2h1m-1 0V6a2 2 0 00-2-2H5a2 2 0 00-2 2v4m16 0v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6m16 0h-3.586a1 1 0 00-.707.293l-1.414 1.414a1 1 0 01-.707.293H8a1 1 0 01-1-1v-3a1 1 0 011-1h8a1 1 0 011 1v3z"
                                ></path>
                            </svg>
                            Assign Delivery ({{ selectedOrders.length }})
                        </button>

                        <button
                            @click="bulkDelete"
                            :disabled="
                                selectedOrders.length === 0 ||
                                orderStore.loading.orders
                            "
                            class="px-3 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center gap-2 text-sm disabled:opacity-50"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                ></path>
                            </svg>
                            Bulk Delete ({{ selectedOrders.length }})
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div
                    v-if="
                        orderStore.loading.orders &&
                        orderStore.orders.length === 0
                    "
                    class="bg-white rounded-lg shadow-sm p-8 text-center"
                >
                    <div
                        class="animate-spin roundecreatedDateRanged-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"
                    ></div>
                    <p class="text-gray-600">Loading orders...</p>
                </div>

                <!-- Error State -->
                <div
                    v-else-if="
                        orderStore.error && orderStore.orders.length === 0
                    "
                    class="bg-white rounded-lg shadow-sm p-8 text-center"
                >
                    <div class="text-red-600 mb-4">
                        <svg
                            class="w-8 h-8 mx-auto mb-2"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.732 16.5c-.77.833.192 2.5 1.732 2.5z"
                            ></path>
                        </svg>
                    </div>
                    <p class="text-gray-600 mb-4">{{ orderStore.error }}</p>
                    <button
                        @click="refreshOrders"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Retry
                    </button>
                </div>

                <!-- Orders Table -->
                <div
                    v-else
                    class="bg-white rounded-lg shadow-sm overflow-hidden"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-blue-600 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">
                                        <input
                                            type="checkbox"
                                            v-model="selectAll"
                                            @change="toggleSelectAll"
                                            class="rounded"
                                            aria-label="Select all orders"
                                        />
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        ORDER MERCHANT ID
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        ORDER NO
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        SELLER
                                    </th>
                                    <!-- <th class="px-4 py-3 text-left font-medium">SOURCE</th> -->
                                    <th class="px-4 py-3 text-left font-medium">
                                        Delivery Date
                                    </th>

                                    <th class="px-4 py-3 text-left font-medium">
                                        CUSTOMER
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        DETAILS
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        ADDRESS
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        DATE
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        TOTAL PRICE
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        DELIVERY PERSON
                                    </th>

                                    <th class="px-4 py-3 text-left font-medium">
                                        CUSTOMER CARE
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        STATUS
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium">
                                        ACTIONS
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="orderStore.orders.length === 0">
                                    <td
                                        colspan="12"
                                        class="px-4 py-8 text-center text-gray-500"
                                    >
                                        No orders found
                                    </td>
                                </tr>
                                <tr
                                    v-for="order in orderStore.orders"
                                    :key="order.id"
                                    :class="[
                                        'border-b hover:bg-gray-50',
                                        order.status === 1
                                            ? 'bg-green-50'
                                            : order.status === 0
                                              ? 'bg-yellow-50'
                                              : order.status === 2
                                                ? 'bg-red-50'
                                                : 'bg-white',
                                    ]"
                                >
                                    <td class="px-4 py-3">
                                        <input
                                            type="checkbox"
                                            v-model="selectedOrders"
                                            :value="order.id"
                                            class="rounded"
                                        />
                                    </td>
                                    <td class="px-4 py-3 font-mono text-sm">
                                        {{ order.reference || "-" }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-sm">
                                        {{ order.order_no || "-" }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        {{ getVendorName(order.vendor_id) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="px-2 py-1 bg-orange-100 text-orange-800 rounded text-xs"
                                        >
                                            <!-- {{ order.source || 'Unknown' }} -->
                                            {{
                                                order.delivery_date || "Unknown"
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-sm">
                                            <div>
                                                {{
                                                    order.shipping_address
                                                        ?.full_name ||
                                                    order.customer?.full_name
                                                }}
                                            </div>
                                            <div class="text-gray-500">
                                                {{
                                                    order.shipping_address
                                                        ?.phone ||
                                                    order.customer?.phone
                                                }}
                                            </div>
                                        </div>
                                    </td>
                                    <!-- <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-800 rounded text-xs"
                                        >
                                            <span
                                                class="w-2 h-2 bg-red-500 rounded-full"
                                            ></span>
                                            {{ order.order_items?.length || 0 }}
                                            product{{
                                                order.order_items?.length !== 1
                                                    ? "s"
                                                    : ""
                                            }}
                                        </span>
                                    </td> -->

                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-800 rounded text-xs"
                                        >
                                            <span
                                                class="w-2 h-2 bg-red-500 rounded-full"
                                            ></span>

                                            {{ order.order_items?.length || 0 }}
                                            product{{
                                                (order.order_items?.length ||
                                                    0) !== 1
                                                    ? "s"
                                                    : ""
                                            }}
                                        </span>

                                        <div class="mt-1">
                                            <div
                                                v-for="item in order.order_items"
                                                :key="item.id"
                                                class="text-[11px] text-gray-700"
                                            >
                                                {{
                                                    item.product
                                                        ?.product_name ||
                                                    item.name ||
                                                    "Product"
                                                }}
                                                (x{{ item.quantity }})
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <div>
                                            <!-- {{
                                                order.shipping_address?.city ||
                                                "-" ||
                                                order.customer?.city_id ||
                                                order.customer?.zone_id
                                            }} -->

                                            {{
                                                order.shipping_address?.city
                                                    ?.name ||
                                                order.customer?.city?.name ||
                                                order.customer?.zone?.name ||
                                                "-"
                                            }}
                                            {{
                                                order.shipping_address
                                                    ?.address ||
                                                order.customer?.address ||
                                                "-"
                                            }}
                                        </div>
                                        <div
                                            class="text-gray-500 truncate max-w-32"
                                        >
                                            {{
                                                order.shipping_address
                                                    ?.address ||
                                                order.customer?.address
                                            }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        <div>
                                            {{ formatDate(order.created_at) }}
                                        </div>
                                    </td>

                                    <!-- total_price -->

                                    <td class="px-4 py-3 font-mono text-sm">
                                        KSH{{ order.total_price || "0.00" }}
                                    </td>

                                    <!-- delivery man  -->

                                    <td class="px-4 py-3">
                                        <div
                                            v-if="getDeliveryAgent(order)"
                                            class="text-xs text-green-600 font-semibold text-center"
                                        >
                                            <img
                                                :src="
                                                    getDeliveryAgent(order)
                                                        .profile_photo_url ||
                                                    '/default-avatar.png'
                                                "
                                                :alt="
                                                    getDeliveryAgent(order).name
                                                "
                                                class="w-6 h-6 rounded-full mx-auto mb-1"
                                            />
                                            <div>
                                                {{
                                                    getDeliveryAgent(order).name
                                                }}
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="text-xs text-gray-500 italic text-center"
                                        >
                                            Not assigned
                                        </div>
                                    </td>

                                    <!-- CUSTOMER CARE  -->

                                    <td class="px-4 py-3">
                                        <div
                                            v-if="getCallAgent(order)"
                                            class="text-xs text-blue-600 font-semibold text-center"
                                        >
                                            <img
                                                :src="
                                                    getCallAgent(order)
                                                        .profile_photo_url ||
                                                    '/default-avatar.png'
                                                "
                                                :alt="getCallAgent(order).name"
                                                class="w-6 h-6 rounded-full mx-auto mb-1"
                                            />
                                            <div>
                                                {{ getCallAgent(order).name }}
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="text-xs text-gray-500 italic text-center"
                                        >
                                            Not assigned
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-1">
                                            <span
                                                class="px-2 py-1 text-xs rounded"
                                                :style="
                                                    order.latest_status?.status
                                                        ?.color
                                                        ? {
                                                              backgroundColor:
                                                                  order
                                                                      .latest_status
                                                                      .status
                                                                      .color,
                                                              color: 'white',
                                                          }
                                                        : {}
                                                "
                                            >
                                                {{
                                                    order.latest_status?.status
                                                        ?.name ||
                                                    orderStatusLabel(
                                                        order.status,
                                                    )
                                                }}
                                            </span>

                                            <div
                                                v-if="
                                                    order.latest_status
                                                        ?.status_notes
                                                "
                                            >
                                                <strong class="text-xs"
                                                    >Note:</strong
                                                >
                                                <span
                                                    class="text-xs text-gray-700"
                                                >
                                                    {{
                                                        order.latest_status
                                                            ?.status_notes
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                v-if="
                                                    order.latest_status
                                                        ?.created_at
                                                "
                                                class="text-xs text-gray-600 bg-green-100 px-1 py-0.5 rounded"
                                            >
                                                Status at
                                                {{
                                                    formatDateTime(
                                                        order.latest_status
                                                            .created_at,
                                                    )
                                                }}
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="flex gap-1 flex-wrap">
                                            <button
                                                @click="editOrder(order.id)"
                                                class="p-1 text-orange-600 hover:bg-orange-100 rounded"
                                                title="Edit Order"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                    ></path>
                                                </svg>
                                            </button>
                                            <button
                                                @click="viewOrder(order)"
                                                class="p-1 text-blue-600 hover:bg-blue-100 rounded"
                                                title="View Order"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                    ></path>
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                    ></path>
                                                </svg>
                                            </button>

                                            <!-- recordings -->
                                            <!-- Microphone with Soundwaves Icon -->
                                            <button
                                                @click="viewRecordings(order)"
                                                class="p-1 text-purple-600 hover:bg-purple-100 rounded"
                                                title="View Call Recordings"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <!-- Mic capsule -->
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M12 2a3 3 0 00-3 3v6a3 3 0 006 0V5a3 3 0 00-3-3z"
                                                    />
                                                    <!-- Mic base -->
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M19 10v2a7 7 0 01-14 0v-2m7 9v3m-4 0h8"
                                                    />
                                                    <!-- Sound waves -->
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M5 9a9 9 0 000 6M19 9a9 9 0 010 6"
                                                    />
                                                </svg>
                                            </button>

                                            <button
                                                @click="
                                                    handleOpenCallDialog(
                                                        order.shipping_address
                                                            ?.phone ||
                                                            order.customer
                                                                ?.phone,
                                                        order,
                                                    )
                                                "
                                                class="p-1 text-indigo-600 hover:bg-indigo-100 rounded disabled:opacity-50"
                                                title="Call Customer"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M3 5a2 2 0 012-2h1.5a1 1 0 01.95.68l.7 2.1a1 1 0 01-.27 1L6.6 8.9a12 12 0 005 5l1.1-.58a1 1 0 011 .27l2.1.7A1 1 0 0119 16.5V18a2 2 0 01-2 2C9.163 20 4 14.837 4 6a2 2 0 00-.999-.999z"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- add whatsapp button-->
                                            <button
                                                @click="
                                                    sendWhatsAppMessage(order)
                                                "
                                                class="p-1 text-green-600 hover:bg-green-100 rounded"
                                                title="Send WhatsApp Message"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5"
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        d="M20.52 3.48A11.36 11.36 0 0012.03.5C6.18.5 1.4 5.28 1.4 11.13c0 1.96.51 3.87 1.48 5.54L.5 23l6.43-2.06a11.6 11.6 0 005.1 1.17h.01c5.85 0 10.63-4.78 10.63-10.63 0-2.83-1.1-5.49-3.05-7.46zM12.03 20.9h-.01a9.42 9.42 0 01-4.8-1.3l-.34-.2-3.8 1.22 1.28-3.7-.22-.36a9.15 9.15 0 01-1.4-4.74c0-5.04 4.1-9.14 9.14-9.14 2.44 0 4.73.95 6.45 2.67a9.07 9.07 0 012.67 6.47c0 5.04-4.1 9.14-9.14 9.14zm5-6.41c-.27-.14-1.6-.79-1.85-.88-.25-.09-.43-.14-.61.14-.18.27-.7.88-.86 1.06-.16.18-.32.2-.59.07-.27-.14-1.13-.42-2.15-1.32-.8-.71-1.34-1.58-1.5-1.85-.16-.27-.02-.42.12-.56l.1-.1c.09-.09.27-.23.41-.35.14-.12.18-.2.27-.33.09-.14.05-.27.02-.38-.05-.11-.61-1.47-.84-2.01-.22-.53-.45-.46-.61-.47-.16-.01-.34-.01-.52-.01a1 1 0 00-.73.31c-.25.25-.97.95-.97 2.31 0 1.36.99 2.68 1.13 2.87.14.18 1.94 2.97 4.74 4.05.66.23 1.18.37 1.58.48.66.17 1.26.15 1.73.09.53-.07 1.6-.65 1.83-1.28.23-.63.23-1.17.16-1.28-.07-.11-.25-.18-.52-.32z"
                                                    />
                                                </svg>
                                            </button>

                                            <button
                                                @click="sendSms(order)"
                                                class="p-1 text-blue-600 hover:bg-blue-100 rounded"
                                                title="Send SMS"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                                    />
                                                </svg>
                                            </button>

                                            <button
                                                @click="stkPush(order)"
                                                class="p-1 text-green-600 hover:bg-green-100 rounded"
                                                title="Initiate M-Pesa STK Push"
                                            >
                                                <!-- Mobile Payment Icon -->
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <rect
                                                        x="7"
                                                        y="2"
                                                        width="10"
                                                        height="20"
                                                        rx="2"
                                                    />
                                                    <path d="M11 18h2" />
                                                </svg>
                                            </button>

                                            <!-- add expense -->

                                            <button
                                                @click="addExpense(order)"
                                                class="p-1 text-blue-600 hover:bg-blue-100 rounded"
                                                title="Add Expense"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                                    />
                                                </svg>
                                            </button>

                                            <!-- 
                                            <button
                                                @click="stkPush(order)"
                                                class="p-1 text-yellow-600 hover:bg-yellow-100 rounded"
                                                title="Initiate STK Push (M-Pesa)"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                            </button> -->

                                            <!-- <button @click="duplicateOrder(order)" class="p-1 text-green-600 hover:bg-green-100 rounded"
                        title="Duplicate Order">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                          </path>
                        </svg>
                      </button> -->
                                            <button
                                                @click="deleteOrder(order)"
                                                class="p-1 text-red-600 hover:bg-red-100 rounded"
                                                title="Delete Order"
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    ></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <!-- <div
                        class="flex justify-between items-center px-6 py-4 bg-gray-50"
                    >
                        <div class="text-sm text-gray-700">
                            Showing {{ orderStore.pagination.from }} to
                            {{ orderStore.pagination.to }} of
                            {{ orderStore.pagination.total }} results
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="previousPage"
                                :disabled="
                                    orderStore.pagination.current_page === 1 ||
                                    orderStore.loading.orders
                                "
                                class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                            >
                                Previous
                            </button>

                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                @click="goToPage(page)"
                                :disabled="orderStore.loading.orders"
                                :class="[
                                    'px-3 py-1 border rounded-md text-sm',
                                    page === orderStore.pagination.current_page
                                        ? 'bg-blue-600 text-white border-blue-600'
                                        : 'border-gray-300 hover:bg-gray-100 disabled:opacity-50',
                                ]"
                            >
                                {{ page }}
                            </button>

                            <button
                                @click="nextPage"
                                :disabled="
                                    orderStore.pagination.current_page ===
                                        orderStore.pagination.last_page ||
                                    orderStore.loading.orders
                                "
                                class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div> -->

                    <div
                        class="flex justify-between items-center px-6 py-4 bg-gray-50"
                    >
                        <div class="flex items-center gap-4">
                            <div class="text-sm text-gray-700">
                                Showing {{ orderStore.pagination.from }} to
                                {{ orderStore.pagination.to }} of
                                {{ orderStore.pagination.total }} results
                            </div>

                            <!-- Items per page selector -->
                            <div class="flex items-center gap-2">
                                <label class="text-sm text-gray-600"
                                    >Show:</label
                                >
                                <select
                                    :value="orderStore.pagination.per_page"
                                    @change="
                                        changeItemsPerPage($event.target.value)
                                    "
                                    :disabled="orderStore.loading.orders"
                                    class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50"
                                >
                                    <option
                                        v-for="option in orderStore.perPageOptions"
                                        :key="option"
                                        :value="option"
                                    >
                                        {{ option }}
                                    </option>
                                </select>
                                <span class="text-sm text-gray-600"
                                    >per page</span
                                >
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                @click="previousPage"
                                :disabled="
                                    orderStore.pagination.current_page === 1 ||
                                    orderStore.loading.orders
                                "
                                class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                            >
                                Previous
                            </button>

                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                @click="goToPage(page)"
                                :disabled="orderStore.loading.orders"
                                :class="[
                                    'px-3 py-1 border rounded-md text-sm',
                                    page === orderStore.pagination.current_page
                                        ? 'bg-blue-600 text-white border-blue-600'
                                        : 'border-gray-300 hover:bg-gray-100 disabled:opacity-50',
                                ]"
                            >
                                {{ page }}
                            </button>

                            <button
                                @click="nextPage"
                                :disabled="
                                    orderStore.pagination.current_page ===
                                        orderStore.pagination.last_page ||
                                    orderStore.loading.orders
                                "
                                class="px-3 py-1 border border-gray-300 rounded-md hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="fixed bottom-4 right-4 z-50 space-y-2">
                    <div
                        v-for="notification in orderStore.notifications"
                        :key="notification.id"
                        :class="[
                            'px-6 py-4 rounded-lg shadow-lg text-white max-w-sm',
                            notification.type === 'success'
                                ? 'bg-green-600'
                                : 'bg-red-600',
                        ]"
                    >
                        <div class="flex justify-between items-center">
                            <p class="text-sm">{{ notification.message }}</p>
                            <button
                                @click="
                                    orderStore.removeNotification(
                                        notification.id,
                                    )
                                "
                                class="ml-4 text-white hover:text-gray-200"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 f6M6 6l12 12"
                                    ></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <OrderExpenses ref="orderExpensesDialog" />

        <OrderCallHistory ref="viewRecordingsDialog" />

        <Stkpush
            ref="stkpushDialog"
            @stkPushInitiated="handleStkPushInitiated"
        />

        <CallCentreDialler
            v-model="callCentreDiallerStore.dialogOpen"
            :order="callCentreDiallerStore.activeOrder"
        />
        <WhatsAppConversation />
        <!-- <CallDialogs /> -->

        <!-- <CallDialogs
      v-model:is-open="isNewCallDialog"
      :phone="newCallPhone"
      @close="isNewCallDialog = false"
    /> -->

        <!-- Call Dialog Component -->
        <CallDialogs
            v-model="callDialogType"
            :call-data="{ phone: newCallPhone }"
            :phone-number="selectedPhone"
            @call-made="onCallMade"
            @call-transferred="onCallTransferred"
            @agent-called="onAgentCalled"
        />

        <OrderForm
            :is-create="isCreateMode"
            @order-saved="onOrderSaved"
            @dialog-closed="onDialogClosed"
        />
        <!-- @open-call-dialog="handleOpenCallDialog" -->
        <!-- Reusable Dialog -->
        <!-- <BulkAction :show="orderStore.bulkActionDialog" :type="orderStore.dialogType" :title="orderStore.dialogTitle"
      :selectedOrders="orderStore.selectedOrders" :deliveryMen="deliveryMen" :callCentreAgents="callCentreAgents"
      @close="orderStore.closeBulkActionDialog" @confirm="handleConfirm" /> -->

        <BulkAction />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, reactive } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import WhatsAppConversation from "../CallCenter/WhatsAppConversation.vue";
import { useOrderStore } from "@/stores/orderStore";
import { useCallCenterStore } from "@/stores/callCenter";

import { useConversationStore } from "@/stores/useConversationStore";
import { usecallCentreDiallerStore } from "@/stores/callCentreDialler";
import { useStkpushStore } from "@/stores/stkpushStore";

import OrderForm from "./OrderForm.vue";
import OrderCallHistory from "./OrderCallHistory.vue";

import OrderExpenses from "./OrderExpenses.vue";

import BulkAction from "./BulkAction.vue";
import CallDialogs from "@/Pages/CallCenter/Dialogs/CallDialogs.vue";
// callcentre  op
import CallCentreDialler from "@/Pages/CallCenter/Dialogs/CallCentreDialler.vue";
import Stkpush from "./Stkpush.vue";
import DateRangePicker from "@/Components/DualDatePicker.vue";
// orderjourney

const props = defineProps({ order: Object });

// Initialize store
const orderStore = useOrderStore();
const conversationStore = useConversationStore();

const callCentreDiallerStore = usecallCentreDiallerStore();

const stkpushStore = useStkpushStore();
const stkpushDialog = ref(null); // Add template ref
const viewRecordingsDialog = ref(null);
const orderExpensesDialog = ref(null);

const createMode = ref(false);

const isNewCallDialog = ref(false);
const newCallPhone = ref("");
const isCreateMode = ref(false);
const callDialogType = ref("");

const selectedPhoneNumber = ref("");

// Handle opening call dialog from order form
// const handleOpenCallDialog = (phoneNumber) => {
//     newCallPhone.value = phoneNumber || "";
//     selectedPhone.value = phoneNumber;

//     callDialogType.value = "newCall"; // This opens the "Make New Call" dialog
// };

const viewRecordings = (order) => {
    console.log("Viewing recordings for order:", order);
    // Implement logic to open recordings dialog or page
    viewRecordingsDialog.value?.open(order.call_logs);
};

const addExpense = (order) => {
    console.log("Adding expense for order:", order);
    // Implement logic to open expense dialog or page
    orderExpensesDialog.value?.open(order);
};

const onRecordingsViewed = () => {
    console.log("Recordings dialog closed");
};

const handleOpenCallDialog = (phoneNumber, order) => {
    console.log("Opening call dialog for phone number:", phoneNumber);
    console.log("Order:", order);

    newCallPhone.value = phoneNumber || "";
    selectedPhone.value = phoneNumber;

    // Pass both phone number and order to the store
    callCentreDiallerStore.openDialog(phoneNumber, order);
};

// Handle call dialog events
const onCallMade = (callData) => {
    console.log("Call made:", callData);
    // You can add logic here to update the order status or add notes
    // For example, mark that customer was contacted
    callDialogType.value = null; // Close dialog
};

const onCallTransferred = (transferData) => {
    console.log("Call transferred:", transferData);
    callDialogType.value = null; // Close dialog
};

const onAgentCalled = (agentData) => {
    console.log("Agent called:", agentData);
    callDialogType.value = null; // Close dialog
};

// Dialog state
const dialog = ref({
    show: false,
    type: "",
    title: "",
});

// Component state
const selectedOrders = ref([]);
const selectAll = ref(false);

const selectedPhone = ref("");

const openNewCallDialog = (phone) => {
    selectedPhone.value = phone;
    activeDialog.value = "newCall";
};

// Dialog state
const dialogType = ref("");
const dialogTitle = ref("");

const bulkActionDialog = computed({
    get: () => orderStore.bulkActionDialog,
    set: (val) => {
        if (!val) orderStore.closeBulkActionDialog();
        else orderStore.bulkActionDialog = val;
    },
});

function confirm() {
    orderStore.handleBulkAction({
        type: type.value,
        data: { ...form },
        orders: selectedOrders.value,
    });
}

const type = computed(() => orderStore.dialogType);
const title = computed(() => orderStore.dialogTitle);
// const selectedOrders = computed(() => orderStore.selectedOrders)
const loading = computed(() => orderStore.loading.orders);

const form = orderStore.bulkActionForm;
const errors = {};

// Computed properties
const visiblePages = computed(() => {
    const current = orderStore.pagination.current_page;
    const last = orderStore.pagination.last_page;
    const pages = [];

    // Show max 5 pages
    const start = Math.max(1, current - 2);
    const end = Math.min(last, start + 4);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

// Methods
const editOrder = (id) => {
    orderStore.openDialog(id);
};

const newOrder = async () => {
    await orderStore.fetchDropdownOptions();
    orderStore.openCreateDialog();
};
//

const bulkPrint = () => {
    if (selectedOrders.value.length === 0) return;
    orderStore.printOrders(selectedOrders.value);
};
const assignDeliveryPerson = () => {
    console.debug("Assign Delivery Person clicked", {
        selectedOrders: selectedOrders.value,
    });
    if (selectedOrders.value.length === 0) {
        console.warn("No orders selected for delivery assignment");
        return;
    }
    orderStore.openAssignDeliveryDialog(selectedOrders.value);
    console.info(
        "Opened assign delivery dialog for orders:",
        selectedOrders.value,
    );
};

// emitted events
const onOrderSaved = () => {
    // refresh orders list or show toast
    refreshOrders();
};

const onDialogClosed = () => {
    // optional cleanup
};

const debouncedSearch = async () => {
    await orderStore.applyFilters();
};

const resetFilters = async () => {
    orderStore.clearAllFilters();
    await orderStore.applyFilters();
};

const applyFilters = async () => {
    await orderStore.applyFilters();
};

const refreshOrders = async () => {
    await orderStore.fetchOrders({ page: orderStore.pagination.current_page });
};

const toggleSelectAll = () => {
    if (selectAll.value) {
        selectedOrders.value = orderStore.orders.map((order) => order.id);
    } else {
        selectedOrders.value = [];
    }
};

const formatDate = (dateStr) => {
    const options = { year: "numeric", month: "short", day: "numeric" };
    return new Date(dateStr).toLocaleDateString(undefined, options);
};

const formatTime = (dateStr) => {
    const options = { hour: "2-digit", minute: "2-digit" };
    return new Date(dateStr).toLocaleTimeString(undefined, options);
};

const formatDateTime = (dateStr) => {
    const options = {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    };
    return new Date(dateStr).toLocaleString(undefined, options);
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
    }).format(amount);
};

const orderStatusLabel = (status) => {
    switch (status) {
        case 1:
            return "Confirmed";
        case 0:
            return "Pending";
        case 2:
            return "Cancelled";
        default:
            return "Unknown";
    }
};

const getVendorName = (vendorId) => {
    const vendor = orderStore.vendorOptions.find((v) => v.id === vendorId);
    return vendor ? vendor.name : "Unknown";
};

const getCallAgent = (order) => getAgentByRole(order, "CallAgent");
const getDeliveryAgent = (order) => getAgentByRole(order, "Delivery Agent");

const getAgentByRole = (order, role) => {
    if (!order.assignments || order.assignments.length === 0) return null;
    const assignment = order.assignments.find((a) => a.role === role);
    return assignment ? assignment.user : null;
};

const viewOrder = (order) => {
    // Open the view dialog for the selected order
    orderStore.openViewDialog(order.id);
};

const duplicateOrder = (order) => {
    orderStore.duplicateOrder(order);
};

const deleteOrder = (order) => {
    orderStore.deleteOrder(order.id);
};

const exportOrders = () => {
    orderStore.exportOrders();
};

const bulkUpdateStatus = () => {
    orderStore.openBulkStatusDialog(selectedOrders.value);
    refreshOrders();
};

const bulkDelete = () => {
    orderStore.openDeleteDialog(selectedOrders.value);
};
const associateAgents = () => {
    if (selectedOrders.value.length === 0) return;
    orderStore.openAssignCallCentreDialog(selectedOrders.value);
};

// const previousPage = async () => {
//     if (orderStore.pagination.current_page > 1) {
//         await orderStore.fetchOrders({
//             page: orderStore.pagination.current_page - 1,
//         });
//         selectAll.value = false;
//         selectedOrders.value = [];
//     }
// };

// const nextPage = async () => {
//     if (orderStore.pagination.current_page < orderStore.pagination.last_page) {
//         await orderStore.fetchOrders({
//             page: orderStore.pagination.current_page + 1,
//         });
//         selectAll.value = false;
//         selectedOrders.value = [];
//     }
// };

// const goToPage = async (page) => {
//     if (page !== orderStore.pagination.current_page) {
//         await orderStore.fetchOrders({ page });
//         selectAll.value = false;
//         selectedOrders.value = [];
//     }
// };

const previousPage = async () => {
    if (orderStore.pagination.current_page > 1) {
        const filters = orderStore.buildFilterParams
            ? orderStore.buildFilterParams()
            : {};
        await orderStore.fetchOrders({
            ...filters,
            page: orderStore.pagination.current_page - 1,
            per_page: orderStore.pagination.per_page,
        });
        selectAll.value = false;
        selectedOrders.value = [];
    }
};

const nextPage = async () => {
    if (orderStore.pagination.current_page < orderStore.pagination.last_page) {
        const filters = orderStore.buildFilterParams
            ? orderStore.buildFilterParams()
            : {};
        await orderStore.fetchOrders({
            ...filters,
            page: orderStore.pagination.current_page + 1,
            per_page: orderStore.pagination.per_page,
        });
        selectAll.value = false;
        selectedOrders.value = [];
    }
};

const goToPage = async (page) => {
    if (page !== orderStore.pagination.current_page) {
        const filters = orderStore.buildFilterParams
            ? orderStore.buildFilterParams()
            : {};
        await orderStore.fetchOrders({
            ...filters,
            page,
            per_page: orderStore.pagination.per_page,
        });
        selectAll.value = false;
        selectedOrders.value = [];
    }
};

const changeItemsPerPage = async (perPage) => {
    await orderStore.changePerPage(perPage);
    selectAll.value = false;
    selectedOrders.value = [];
};

// conversation

// const sendWhatsAppMessage = (order) => {
//     const phone = order.customer?.phone;

//     if (!phone) {
//         console.error("❌ No phone number found in order:", order);
//         return;
//     }

//     console.log("Opening WhatsApp dialog for:", phone);
//     conversationStore.openDialog(phone);
// };

const sendWhatsAppMessage = (order) => {
    const customer = order.customer;

    if (!customer?.phone) {
        console.error("❌ No phone number found in order:", order);
        return;
    }

    const contact = {
        id: customer.id,
        name: customer.full_name || customer.name || "Customer",
        phone: customer.phone,
        whatsapp: customer.whatsapp || customer.phone,
        orderId: order.id,
        orderOid: order.order_no,
        orderData: order, // important if template needs details
    };

    // NOW pass full contact
    conversationStore.openDialog(contact);

    console.log("Opening WhatsApp dialog for contact:", contact);
};

const stkPush = (order) => {
    //    opendialog  for stkpush
    stkpushDialog.value?.openDialog(order);
};

// Watch for selection changes
watch(selectedOrders, (newSelection) => {
    const shouldSelectAll =
        newSelection.length === orderStore.orders.length &&
        orderStore.orders.length > 0;
    if (selectAll.value !== shouldSelectAll) {
        selectAll.value = shouldSelectAll;
    }
});

// Lifecycle hooks
onMounted(async () => {
    await orderStore.initialize();
    await orderStore.fetchOrders({ page: 1 });
});
</script>
