<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-100 p-6">
            <!-- ===== FILTER PANEL ===== -->
            <div class="bg-white rounded-xl shadow-sm mb-6 p-5">
                <!-- Search Bar -->
                <div class="relative mb-5">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search orders by (id , customer name, phone, address)"
                        class="w-full border border-gray-300 rounded-lg pl-4 pr-12 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    />
                    <div class="absolute right-0 top-0 h-full flex">
                        <button class="px-3 text-gray-500 hover:text-gray-700">
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
                                />
                            </svg>
                        </button>
                        <button
                            class="px-3 bg-gray-900 rounded-r-lg text-white hover:bg-gray-800"
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
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Filters Row 1 -->
                <div
                    class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-4"
                >
                    <!-- <div>
                        <label class="block text-xs font-bold text-red-600 mb-1"
                            >CITY FROM</label
                        >
                        <select
                            v-model="filters.cityFrom"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                        >
                            <option value="">Select city from</option>
                            <option
                                v-for="c in cityOptions"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div> -->
                    <!-- <div>
                        <label class="block text-xs font-bold text-red-600 mb-1"
                            >ZONE FROM</label
                        >
                        <select
                            v-model="filters.zoneFrom"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                        >
                            <option value="">Select zone from</option>
                            <option
                                v-for="z in zoneOptions"
                                :key="z.id"
                                :value="z.id"
                            >
                                {{ z.name }}
                            </option>
                        </select>
                    </div> -->
                    <div>
                        <label class="block text-xs font-bold text-red-600 mb-1"
                            >CITY TO</label
                        >
                        <select
                            v-model="filters.cityTo"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                        >
                            <option value="">Select city to</option>
                            <option
                                v-for="c in cityOptions"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-red-600 mb-1"
                            >ZONE TO</label
                        >
                        <select
                            v-model="filters.zoneTo"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                        >
                            <option value="">Select zone to</option>
                            <option
                                v-for="z in zoneOptions"
                                :key="z.id"
                                :value="z.id"
                            >
                                {{ z.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-red-600 mb-1"
                            >DELIVERY MAN</label
                        >
                        <select
                            v-model="filters.deliveryMan"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                        >
                            <option value="">Select delivery man</option>
                            <option
                                v-for="r in riderOptions"
                                :key="r.id"
                                :value="r.id"
                            >
                                {{ r.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-red-600 mb-1"
                            >COURRIER</label
                        >
                        <select
                            v-model="filters.courrier"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                        >
                            <option value="">Select courrier</option>
                            <option
                                v-for="c in courrierOptions"
                                :key="c.id"
                                :value="c.id"
                            >
                                {{ c.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Filters Row 2 -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold text-red-600 mb-1"
                            >SELLER</label
                        >
                        <select
                            v-model="filters.seller"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                        >
                            <option value="">All sellers</option>
                            <option
                                v-for="s in sellerOptions"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-red-600 mb-1"
                            >SHIPPING STATUS</label
                        >
                        <select
                            v-model="filters.shippingStatus"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                        >
                            <option value="">Shipping status</option>
                            <option value="awaiting">Awaiting Dispatch</option>
                            <option value="dispatched">Dispatched</option>
                            <option value="in_transit">In Transit</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-red-600 mb-1"
                            >DISPATCHED ON
                            <span class="text-red-500">*</span></label
                        >
                        <div class="relative">
                            <input
                                v-model="filters.dispatchedOn"
                                type="date"
                                placeholder="Filter by dispatched on date (required)"
                                class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                            />
                            <svg
                                class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-red-600 mb-1"
                            >SHIPPED ON
                            <span class="text-red-500">*</span></label
                        >
                        <div class="relative">
                            <input
                                v-model="filters.shippedOn"
                                type="date"
                                placeholder="Filter by shipped on date (required)"
                                class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                            />
                            <svg
                                class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Date Range Warning -->
                <div
                    v-if="showDateWarning"
                    class="flex items-center gap-3 bg-yellow-50 border border-yellow-300 rounded-lg px-4 py-3 mb-4 text-sm text-yellow-800"
                >
                    <svg
                        class="w-5 h-5 text-yellow-500 flex-shrink-0"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <span
                        ><strong>Date Range Required:</strong> You must select
                        at least one date range (Dispatched on or Shipped on) to
                        view orders.</span
                    >
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-3">
                    <button
                        @click="applyFilters"
                        class="flex items-center gap-2 px-6 py-2.5 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Filter
                    </button>
                    <button
                        @click="resetFilters"
                        class="flex items-center gap-2 px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-lg transition-colors"
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
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                        Reset
                    </button>
                </div>
            </div>

            <!-- ===== TABLE SECTION ===== -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <!-- Table Header -->
                <div
                    class="flex items-center justify-between flex-wrap gap-3 px-5 py-4 border-b border-gray-200"
                >
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-gray-900">
                            Dispatch List &amp; Ship Orders
                        </h2>
                        <div
                            class="flex items-center gap-1 border border-gray-300 rounded px-3 py-1 text-sm text-gray-600 cursor-pointer hover:bg-gray-50"
                        >
                            <span
                                >{{ pagination.from }}-{{ pagination.to }} /
                                {{ pagination.total }}</span
                            >
                            <svg
                                class="w-3 h-3 ml-1 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors"
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
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Download Dispatch List PDF
                        </button>
                        <button
                            class="flex items-center gap-2 px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white text-sm font-medium rounded-lg transition-colors"
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
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            Download Dispatch List Excel
                        </button>
                        <button
                            class="flex items-center gap-2 px-4 py-2 bg-blue-400 hover:bg-blue-500 text-white text-sm font-medium rounded-lg transition-colors"
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
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Send to in transit ({{ selectedOrders.length }})
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left w-10">
                                    <input
                                        type="checkbox"
                                        @change="toggleSelectAll"
                                        :checked="allSelected"
                                        class="rounded border-gray-300"
                                    />
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    ORDER NO
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    CUSTOMER
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    ADDRESS
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    SELLER
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    DETAILS
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    DISPATCHED ON
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    CITY TO
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    TRACKING NUMBER
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    CITY FROM
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    DELIVERY MAN
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    TOTAL PRICE
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold whitespace-nowrap"
                                >
                                    STATUS
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="orders.length === 0">
                                <td
                                    colspan="13"
                                    class="px-4 py-16 text-center text-gray-400 text-sm"
                                >
                                    No items
                                </td>
                            </tr>
                            <tr
                                v-for="order in orders"
                                :key="order.id"
                                class="border-t border-gray-100 hover:bg-blue-50 transition-colors"
                                :class="{
                                    'bg-blue-50': selectedOrders.includes(
                                        order.id,
                                    ),
                                }"
                            >
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        :value="order.id"
                                        v-model="selectedOrders"
                                        class="rounded border-gray-300"
                                    />
                                </td>
                                <td
                                    class="px-4 py-3 text-sm font-mono text-blue-600 font-medium"
                                >
                                    {{ order.orderNo }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-800">
                                    {{ order.customer }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate"
                                >
                                    {{ order.address }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ order.seller }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ order.details }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    {{ order.dispatchedOn }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ order.cityTo }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm font-mono text-gray-700"
                                >
                                    {{ order.trackingNumber }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ order.cityFrom }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ order.deliveryMan }}
                                </td>
                                <td
                                    class="px-4 py-3 text-sm font-semibold text-gray-800"
                                >
                                    KSH {{ order.totalPrice }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="px-2 py-1 rounded text-xs font-medium whitespace-nowrap"
                                        :class="statusClass(order.status)"
                                        >{{ order.status }}</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    class="flex justify-center items-center gap-1 py-4 bg-gray-50 border-t border-gray-200"
                >
                    <button
                        v-for="page in totalPages"
                        :key="page"
                        @click="currentPage = page"
                        class="px-3 py-1 rounded text-sm font-medium border transition-colors"
                        :class="
                            currentPage === page
                                ? 'border-red-400 text-red-600 bg-white'
                                : 'border-gray-300 text-gray-600 hover:bg-gray-100'
                        "
                    >
                        {{ page }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ===== SCAN & DISPATCH DIALOG ===== -->
        <Teleport to="body">
            <Transition name="modal">
                <div
                    v-if="dialogOpen"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4"
                    @click.self="closeDialog"
                >
                    <div
                        class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                        @click="closeDialog"
                    ></div>

                    <div
                        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden"
                    >
                        <!-- Header -->
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2
                                class="text-xl font-bold text-gray-900 text-center"
                            >
                                Dispatch orders /
                                <span class="text-blue-600"
                                    >{{ scannedOrders.length }} selected</span
                                >
                            </h2>
                        </div>

                        <!-- Body -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-5">
                            <!-- City / Zone -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-red-600 mb-1"
                                        >CITY FROM
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <select
                                        v-model="form.cityFrom"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                    >
                                        <option value="">
                                            Select city from
                                        </option>
                                        <option
                                            v-for="c in cityOptions"
                                            :key="c.id"
                                            :value="c.id"
                                        >
                                            {{ c.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-red-600 mb-1"
                                        >ZONE FROM
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <select
                                        v-model="form.zoneFrom"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                    >
                                        <option value="">
                                            Select zone from
                                        </option>
                                        <option
                                            v-for="z in zoneOptions"
                                            :key="z.id"
                                            :value="z.id"
                                        >
                                            {{ z.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-red-600 mb-1"
                                        >CITY TO
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <select
                                        v-model="form.cityTo"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                    >
                                        <option value="">Select city to</option>
                                        <option
                                            v-for="c in cityOptions"
                                            :key="c.id"
                                            :value="c.id"
                                        >
                                            {{ c.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-semibold text-red-600 mb-1"
                                        >ZONE TO
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <select
                                        v-model="form.zoneTo"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                    >
                                        <option value="">Select zone to</option>
                                        <option
                                            v-for="z in zoneOptions"
                                            :key="z.id"
                                            :value="z.id"
                                        >
                                            {{ z.name }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Delivery Man -->
                            <div>
                                <label
                                    class="block text-sm font-semibold text-red-600 mb-1"
                                    >DELIVERY MAN
                                    <span class="text-red-500">*</span></label
                                >
                                <select
                                    v-model="form.deliveryMan"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                >
                                    <option value="">
                                        Select a delivery man
                                    </option>
                                    <option
                                        v-for="r in riderOptions"
                                        :key="r.id"
                                        :value="r.id"
                                    >
                                        {{ r.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Courrier -->
                            <div>
                                <label
                                    class="block text-sm font-semibold text-gray-700 mb-1"
                                    >COURRIER</label
                                >
                                <select
                                    v-model="form.courrier"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                >
                                    <option value="">
                                        Select a courrier (optional)
                                    </option>
                                    <option
                                        v-for="c in courrierOptions"
                                        :key="c.id"
                                        :value="c.id"
                                    >
                                        {{ c.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Scan Mode -->
                            <div class="flex gap-2">
                                <button
                                    @click="scanMode = 'one'"
                                    :class="[
                                        'px-4 py-1.5 rounded-full text-sm font-medium border transition-colors',
                                        scanMode === 'one'
                                            ? 'bg-red-100 border-red-400 text-red-700'
                                            : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    Scan One by One
                                </button>
                                <button
                                    @click="scanMode = 'bulk'"
                                    :class="[
                                        'px-4 py-1.5 rounded-full text-sm font-medium border transition-colors',
                                        scanMode === 'bulk'
                                            ? 'bg-blue-100 border-blue-400 text-blue-700'
                                            : 'bg-white border-gray-300 text-gray-600 hover:bg-gray-50',
                                    ]"
                                >
                                    Bulk Input (comma or line separated)
                                </button>
                            </div>

                            <!-- Scan Input -->
                            <div>
                                <label
                                    class="block text-sm font-semibold text-red-600 mb-1"
                                    >SEARCH ORDERS (AWAITING DISPATCH, BY
                                    TRACKING NUMBER)</label
                                >
                                <div
                                    v-if="scanMode === 'one'"
                                    class="flex gap-2"
                                >
                                    <input
                                        v-model="scanInput"
                                        @keyup.enter="scanOrder"
                                        type="text"
                                        placeholder="Scan or type tracking number and press Enter..."
                                        class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"
                                        autofocus
                                    />
                                    <button
                                        @click="scanOrder"
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors"
                                    >
                                        Add
                                    </button>
                                </div>
                                <div v-else>
                                    <textarea
                                        v-model="bulkInput"
                                        placeholder="Enter tracking numbers separated by commas or new lines..."
                                        rows="4"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none resize-none"
                                    ></textarea>
                                    <button
                                        @click="processBulkInput"
                                        class="mt-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg transition-colors"
                                    >
                                        Process
                                    </button>
                                </div>
                            </div>

                            <!-- Scanned Orders Table -->
                            <div
                                class="border border-gray-200 rounded-lg overflow-hidden"
                            >
                                <table class="w-full">
                                    <thead class="bg-blue-600 text-white">
                                        <tr>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                ORDER ID
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                SELLER
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                CUSTOMER NAME
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                SHIPPING DATE
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                TRACKING NUMBER
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                TOTAL PRICE
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                SHIPPING STATUS
                                            </th>
                                            <th
                                                class="px-3 py-2 text-left text-xs font-semibold"
                                            >
                                                ACTIONS
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="scannedOrders.length === 0">
                                            <td
                                                colspan="8"
                                                class="px-4 py-8 text-center text-gray-400 text-sm"
                                            >
                                                No items
                                            </td>
                                        </tr>
                                        <tr
                                            v-for="(
                                                order, index
                                            ) in scannedOrders"
                                            :key="order.id"
                                            class="border-t border-gray-100 hover:bg-gray-50"
                                        >
                                            <td
                                                class="px-3 py-2 text-sm font-mono"
                                            >
                                                {{ order.id }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                {{ order.seller }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                {{ order.customerName }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                {{ order.shippingDate }}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-sm font-mono"
                                            >
                                                {{ order.trackingNumber }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                KSH {{ order.totalPrice }}
                                            </td>
                                            <td class="px-3 py-2 text-sm">
                                                <span
                                                    class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs"
                                                    >{{
                                                        order.shippingStatus
                                                    }}</span
                                                >
                                            </td>
                                            <td class="px-3 py-2">
                                                <button
                                                    @click="removeOrder(index)"
                                                    class="p-1 text-red-500 hover:bg-red-50 rounded"
                                                    title="Remove"
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
                                                            d="M6 18L18 6M6 6l12 12"
                                                        />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Error -->
                            <div
                                v-if="scanError"
                                class="text-red-600 text-sm bg-red-50 border border-red-200 rounded-lg px-4 py-2"
                            >
                                {{ scanError }}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div
                            class="px-6 py-4 border-t border-gray-200 flex justify-end gap-3"
                        >
                            <button
                                @click="closeDialog"
                                class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                @click="dispatchOrders"
                                :disabled="
                                    scannedOrders.length === 0 ||
                                    !form.cityFrom ||
                                    !form.cityTo ||
                                    !form.deliveryMan ||
                                    dispatching
                                "
                                class="px-6 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2"
                            >
                                <span
                                    v-if="dispatching"
                                    class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"
                                ></span>
                                Dispatch Orders
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import axios from "axios";

// ─── Filter State ─────────────────────────────────────────────────────────────
const searchQuery = ref("");
const showDateWarning = ref(true);

const filters = ref({
    cityFrom: "",
    zoneFrom: "",
    cityTo: "",
    zoneTo: "",
    deliveryMan: "",
    courrier: "",
    seller: "",
    shippingStatus: "",
    dispatchedOn: "",
    shippedOn: "",
});

// ─── Table State ──────────────────────────────────────────────────────────────
const orders = ref([]);
const selectedOrders = ref([]);
const currentPage = ref(1);
const pagination = ref({ from: 1, to: 1000, total: 0 });

const totalPages = computed(() =>
    Math.max(1, Math.ceil(pagination.value.total / 25)),
);

const allSelected = computed(
    () =>
        orders.value.length > 0 &&
        orders.value.every((o) => selectedOrders.value.includes(o.id)),
);

const toggleSelectAll = () => {
    if (allSelected.value) {
        selectedOrders.value = [];
    } else {
        selectedOrders.value = orders.value.map((o) => o.id);
    }
};

const statusClass = (status) => {
    const map = {
        Dispatched: "bg-blue-100 text-blue-800",
        "Awaiting Dispatch": "bg-yellow-100 text-yellow-800",
        "In Transit": "bg-purple-100 text-purple-800",
        Delivered: "bg-green-100 text-green-800",
        Returned: "bg-red-100 text-red-800",
    };
    return map[status] || "bg-gray-100 text-gray-700";
};

// ─── Dropdown Options ─────────────────────────────────────────────────────────
const cityOptions = ref([]);
const zoneOptions = ref([]);
const riderOptions = ref([]);
const courrierOptions = ref([]);
const sellerOptions = ref([]);

// ─── Dialog State ─────────────────────────────────────────────────────────────
const dialogOpen = ref(false);
const scanMode = ref("one");
const scanInput = ref("");
const bulkInput = ref("");
const scanError = ref("");
const dispatching = ref(false);
const scannedOrders = ref([]);

const form = ref({
    cityFrom: "",
    zoneFrom: "",
    cityTo: "",
    zoneTo: "",
    deliveryMan: "",
    courrier: "",
});

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(async () => {
    try {
        const [citiesRes, zonesRes, ridersRes, sellersRes] = await Promise.all([
            axios.get("/api/v1/cities").catch(() => ({ data: { data: [] } })),
            axios.get("/api/v1/zones").catch(() => ({ data: { data: [] } })),
            axios.get("/api/v1/riders").catch(() => ({ data: { data: [] } })),
            axios.get("/api/v1/sellers").catch(() => ({ data: { data: [] } })),
        ]);
        const normalize = (res) => {
            const d = res?.data;
            if (Array.isArray(d)) return d;
            if (Array.isArray(d?.data)) return d.data;
            return [];
        };
        cityOptions.value = normalize(citiesRes);
        zoneOptions.value = normalize(zonesRes);
        riderOptions.value = normalize(ridersRes);
        sellerOptions.value = normalize(sellersRes);
    } catch (e) {
        console.error("Failed to load options", e);
    }
});

// ─── Filter Actions ───────────────────────────────────────────────────────────
const applyFilters = async () => {
    if (!filters.value.dispatchedOn && !filters.value.shippedOn) {
        showDateWarning.value = true;
        return;
    }
    showDateWarning.value = false;
    try {
        const res = await axios.get("/api/v1/orders/dispatched", {
            params: filters.value,
        });
        const d = res.data?.data;
        orders.value = Array.isArray(d) ? d : [];
        pagination.value = res.data?.pagination || {
            from: 1,
            to: orders.value.length,
            total: orders.value.length,
        };
    } catch (e) {
        console.error("Failed to load orders", e);
    }
};

const resetFilters = () => {
    filters.value = {
        cityFrom: "",
        zoneFrom: "",
        cityTo: "",
        zoneTo: "",
        deliveryMan: "",
        courrier: "",
        seller: "",
        shippingStatus: "",
        dispatchedOn: "",
        shippedOn: "",
    };
    searchQuery.value = "";
    orders.value = [];
    showDateWarning.value = true;
};

// ─── Dialog ───────────────────────────────────────────────────────────────────
const openScanDispatchDialog = () => {
    scannedOrders.value = [];
    scanInput.value = "";
    bulkInput.value = "";
    scanError.value = "";
    form.value = {
        cityFrom: "",
        zoneFrom: "",
        cityTo: "",
        zoneTo: "",
        deliveryMan: "",
        courrier: "",
    };
    dialogOpen.value = true;
};

const closeDialog = () => {
    dialogOpen.value = false;
};

// ─── Scanning ─────────────────────────────────────────────────────────────────
const scanOrder = async () => {
    const tracking = scanInput.value.trim();
    if (!tracking) return;
    scanError.value = "";
    if (scannedOrders.value.find((o) => o.trackingNumber === tracking)) {
        scanError.value = `Order "${tracking}" is already added.`;
        return;
    }
    try {
        const res = await axios.get(`/api/v1/orders/by-tracking/${tracking}`);
        const order = res.data?.data;
        if (!order) {
            scanError.value = `No order found for: ${tracking}`;
            return;
        }
        scannedOrders.value.push({
            id: order.id,
            seller: order.vendor?.name || "Unknown",
            customerName:
                order.shipping_address?.full_name ||
                order.customer?.full_name ||
                "-",
            shippingDate: order.delivery_date || "-",
            trackingNumber: tracking,
            totalPrice: order.total_price || "0.00",
            shippingStatus:
                order.latest_status?.status?.name || "Awaiting Dispatch",
        });
        scanInput.value = "";
    } catch (err) {
        scanError.value =
            err.response?.data?.message || `Order not found for: ${tracking}`;
    }
};

const processBulkInput = async () => {
    const lines = bulkInput.value
        .split(/[\n,]+/)
        .map((s) => s.trim())
        .filter(Boolean);
    scanError.value = "";
    for (const tracking of lines) {
        if (!scannedOrders.value.find((o) => o.trackingNumber === tracking)) {
            scanInput.value = tracking;
            await scanOrder();
        }
    }
    bulkInput.value = "";
    scanInput.value = "";
};

const removeOrder = (index) => {
    scannedOrders.value.splice(index, 1);
};

// ─── Dispatch ─────────────────────────────────────────────────────────────────
const dispatchOrders = async () => {
    if (!scannedOrders.value.length) return;
    if (!form.value.cityFrom || !form.value.cityTo || !form.value.deliveryMan) {
        scanError.value =
            "Please fill in all required fields (City From, City To, Delivery Man).";
        return;
    }
    dispatching.value = true;
    scanError.value = "";
    try {
        await axios.post("/api/v1/orders/dispatch", {
            order_ids: scannedOrders.value.map((o) => o.id),
            city_from: form.value.cityFrom,
            zone_from: form.value.zoneFrom,
            city_to: form.value.cityTo,
            zone_to: form.value.zoneTo,
            rider_id: form.value.deliveryMan,
            courrier_id: form.value.courrier || null,
        });
        closeDialog();
        await applyFilters();
    } catch (err) {
        scanError.value =
            err.response?.data?.message ||
            "Failed to dispatch orders. Please try again.";
    } finally {
        dispatching.value = false;
    }
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-active .relative,
.modal-leave-active .relative {
    transition: transform 0.2s ease;
}
.modal-enter-from .relative,
.modal-leave-to .relative {
    transform: scale(0.96) translateY(8px);
}
</style>
