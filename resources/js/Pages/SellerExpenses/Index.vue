<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-50 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">
                                Vendor Expenses
                            </h1>
                            <p class="text-gray-600 mt-1">
                                {{ expenseStore.pagination.total }} total
                                expenses •
                                {{ formatCurrency(expenseStore.totalAmount) }}
                                net amount
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <!-- Export current filters -->
                            <button
                                @click="handleExport"
                                :disabled="
                                    expenseStore.exporting ||
                                    expenseStore.loading
                                "
                                class="flex items-center gap-2 border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                title="Export visible results to CSV"
                            >
                                <p
                                    v-if="activeFilterCount > 0"
                                    class="text-xs text-gray-400 mt-1"
                                >
                                    Exporting
                                    {{ expenseStore.pagination.total }} filtered
                                    rows
                                </p>
                                <Loader
                                    v-if="expenseStore.exporting"
                                    class="animate-spin"
                                    :size="18"
                                />
                                <Download v-else :size="18" />
                                {{
                                    expenseStore.exporting
                                        ? "Exporting…"
                                        : "Export CSV"
                                }}
                            </button>

                            <!-- Add Expense (unchanged) -->
                            <button
                                @click="openModal('create')"
                                class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                            >
                                <Plus :size="20" />
                                Add Expense
                            </button>
                        </div>

                        <!--                         
                        <button
                            @click="openModal('create')"
                            class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                        >
                            <Plus :size="20" />
                            Add Expense
                        </button> -->
                    </div>

                    <!-- ── Search & Filters ── -->
                    <div class="space-y-3">
                        <!-- Row 1: text search + status + type -->
                        <div class="flex flex-col md:flex-row gap-3">
                            <div class="flex-1 relative">
                                <Search
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
                                    :size="18"
                                />
                                <input
                                    type="text"
                                    placeholder="Search description…"
                                    class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                    v-model="localSearch"
                                    @input="debouncedSearch"
                                />
                            </div>

                            <select
                                class="border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                v-model="localFilters.status"
                                @change="applyAndReset"
                            >
                                <option value="">All Status</option>
                                <option value="not_applied">Not Applied</option>
                                <option value="applied">Applied</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>

                            <select
                                class="border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                v-model="localFilters.expense_type"
                                @change="applyAndReset"
                            >
                                <option value="">All Types</option>
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                            </select>
                        </div>
                        <p
                            v-if="activeFilterCount > 0"
                            class="text-xs text-gray-400 mt-1"
                        >
                            Exporting
                            {{ expenseStore.pagination.total }} filtered rows
                        </p>
                        <!-- Row 2: advanced filters (collapsible) -->
                        <div>
                            <button
                                type="button"
                                class="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800"
                                @click="showAdvanced = !showAdvanced"
                            >
                                <SlidersHorizontal :size="15" />
                                {{ showAdvanced ? "Hide" : "Show" }} advanced
                                filters
                                <ChevronDown
                                    :size="15"
                                    :class="showAdvanced ? 'rotate-180' : ''"
                                    class="transition-transform"
                                />
                            </button>

                            <div
                                v-if="showAdvanced"
                                class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3"
                            >
                                <!-- Vendor -->
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 mb-1"
                                        >Vendor</label
                                    >
                                    <v-select
                                        v-model="localFilters.vendor_id"
                                        :items="vendorOptions"
                                        item-title="name"
                                        item-value="id"
                                        clearable
                                        density="comfortable"
                                        variant="outlined"
                                        placeholder="All vendors"
                                        @update:modelValue="applyAndReset"
                                    />
                                </div>

                                <!-- Expense Type ID -->
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 mb-1"
                                        >Expense Category</label
                                    >
                                    <v-autocomplete
                                        v-model="localFilters.expense_type_id"
                                        :items="expenseTypeItems"
                                        item-title="display_name"
                                        item-value="id"
                                        clearable
                                        density="comfortable"
                                        variant="outlined"
                                        placeholder="All categories"
                                        @update:modelValue="applyAndReset"
                                    />
                                </div>

                                <!-- Remittance ID -->
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 mb-1"
                                        >Remittance ID</label
                                    >
                                    <input
                                        type="number"
                                        min="0"
                                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        v-model="localFilters.remittance_id"
                                        placeholder="e.g. 42"
                                        @change="applyAndReset"
                                    />
                                </div>

                                <!-- Incurred On (range) -->
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 mb-1"
                                        >Incurred From</label
                                    >
                                    <input
                                        type="date"
                                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        v-model="localFilters.incurred_on_from"
                                        @change="applyAndReset"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 mb-1"
                                        >Incurred To</label
                                    >
                                    <input
                                        type="date"
                                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        v-model="localFilters.incurred_on_to"
                                        @change="applyAndReset"
                                    />
                                </div>

                                <!-- Created At (range) -->
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 mb-1"
                                        >Created From</label
                                    >
                                    <input
                                        type="date"
                                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        v-model="localFilters.created_at_from"
                                        @change="applyAndReset"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 mb-1"
                                        >Created To</label
                                    >
                                    <input
                                        type="date"
                                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        v-model="localFilters.created_at_to"
                                        @change="applyAndReset"
                                    />
                                </div>

                                <!-- Sort -->
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 mb-1"
                                        >Sort By</label
                                    >
                                    <select
                                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        v-model="localFilters.sort_by"
                                        @change="applyAndReset"
                                    >
                                        <option value="created_at">
                                            Created Date
                                        </option>
                                        <option value="incurred_on">
                                            Incurred On
                                        </option>
                                        <option value="amount">Amount</option>
                                        <option value="status">Status</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-medium text-gray-600 mb-1"
                                        >Direction</label
                                    >
                                    <select
                                        class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        v-model="localFilters.sort_dir"
                                        @change="applyAndReset"
                                    >
                                        <option value="desc">
                                            Newest first
                                        </option>
                                        <option value="asc">
                                            Oldest first
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Active filter chips + clear all -->
                        <div
                            v-if="activeFilterCount > 0"
                            class="flex flex-wrap gap-2 items-center"
                        >
                            <span class="text-xs text-gray-500"
                                >Active filters:</span
                            >
                            <span
                                v-for="chip in filterChips"
                                :key="chip.key"
                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded-full"
                            >
                                {{ chip.label }}
                                <button
                                    @click="removeFilter(chip.key)"
                                    class="hover:text-blue-900"
                                >
                                    <X :size="11" />
                                </button>
                            </span>
                            <button
                                class="text-xs text-red-500 hover:text-red-700 underline ml-1"
                                @click="clearAll"
                            >
                                Clear all
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── Table ── -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <!-- Loading -->
                    <div
                        v-if="expenseStore.loading"
                        class="flex items-center justify-center py-12"
                    >
                        <Loader
                            class="animate-spin text-blue-600 mr-2"
                            :size="24"
                        />
                        <span class="text-gray-600">Loading expenses…</span>
                    </div>

                    <!-- Error -->
                    <div v-else-if="expenseStore.error" class="p-6 text-center">
                        <div class="text-red-600 mb-2">
                            <AlertCircle class="inline-block mr-2" :size="20" />
                            Error loading expenses
                        </div>
                        <p class="text-gray-600 mb-4">
                            {{ expenseStore.error }}
                        </p>
                        <button
                            @click="expenseStore.fetchExpenses()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                        >
                            Retry
                        </button>
                    </div>

                    <!-- Table Content -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-blue-600 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">ID</th>
                                    <th class="px-4 py-3 text-left">Expense</th>
                                    <th class="px-4 py-3 text-left">
                                        Description
                                    </th>
                                    <th class="px-4 py-3 text-left">Amount</th>
                                    <th class="px-4 py-3 text-left">Type</th>
                                    <th class="px-4 py-3 text-left">Vendor</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">
                                        Incurred On
                                    </th>
                                    <th class="px-4 py-3 text-left">
                                        Created Date
                                    </th>
                                    <th class="px-4 py-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="expenseStore.expenses.length === 0">
                                    <td
                                        colspan="10"
                                        class="px-4 py-8 text-center text-gray-500"
                                    >
                                        No expenses found
                                    </td>
                                </tr>
                                <tr
                                    v-for="(
                                        expense, index
                                    ) in expenseStore.expenses"
                                    :key="expense.id"
                                    :class="
                                        index % 2 === 0
                                            ? 'bg-amber-50'
                                            : 'bg-white'
                                    "
                                >
                                    <td class="px-4 py-3 font-medium">
                                        #{{ expense.id }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        {{
                                            expense.expense_type
                                                ?.display_name || "N/A"
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-start gap-2">
                                            <FileText
                                                :size="16"
                                                class="text-gray-400 mt-1 flex-shrink-0"
                                            />
                                            <span class="line-clamp-2">{{
                                                expense.description
                                            }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'font-semibold',
                                                expense.expense_type ===
                                                'expense'
                                                    ? 'text-red-600'
                                                    : 'text-green-600',
                                            ]"
                                        >
                                            {{
                                                expense.expense_type ===
                                                "expense"
                                                    ? "-"
                                                    : "+"
                                            }}{{
                                                formatCurrency(expense.amount)
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'px-2 py-1 rounded text-xs font-medium',
                                                expense.expense_type ===
                                                'expense'
                                                    ? 'bg-red-100 text-red-800'
                                                    : 'bg-green-100 text-green-800',
                                            ]"
                                        >
                                            {{
                                                expense.expense_type ===
                                                "expense"
                                                    ? "Expense"
                                                    : "Income"
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm"
                                            >
                                                {{
                                                    expense.vendor
                                                        ?.first_name?.[0] || "U"
                                                }}
                                            </div>
                                            <p class="text-sm font-medium">
                                                {{
                                                    expense.vendor?.username ||
                                                    "N/A"
                                                }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs text-white',
                                                getStatusConfig(expense.status)
                                                    .bg,
                                            ]"
                                        >
                                            <component
                                                :is="
                                                    getStatusConfig(
                                                        expense.status,
                                                    ).icon
                                                "
                                                :size="12"
                                            />
                                            {{
                                                getStatusConfig(expense.status)
                                                    .text
                                            }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ formatDate(expense.incurred_on) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ formatDate(expense.created_at) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <button
                                                @click="
                                                    openModal('view', expense)
                                                "
                                                class="p-2 hover:bg-gray-100 rounded-lg transition"
                                                title="View"
                                            >
                                                <Eye
                                                    :size="18"
                                                    class="text-gray-600"
                                                />
                                            </button>
                                            <button
                                                @click="
                                                    openModal('edit', expense)
                                                "
                                                class="p-2 hover:bg-gray-100 rounded-lg transition"
                                                title="Edit"
                                            >
                                                <Edit
                                                    :size="18"
                                                    class="text-blue-600"
                                                />
                                            </button>
                                            <button
                                                @click="
                                                    handleDelete(expense.id)
                                                "
                                                class="p-2 hover:bg-gray-100 rounded-lg transition"
                                                title="Delete"
                                            >
                                                <Trash2
                                                    :size="18"
                                                    class="text-red-600"
                                                />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination — server-driven -->
                    <div
                        v-if="expenseStore.pagination.lastPage > 1"
                        class="border-t p-4 flex justify-between items-center"
                    >
                        <div class="text-sm text-gray-600">
                            Showing
                            {{
                                (expenseStore.pagination.currentPage - 1) *
                                    expenseStore.pagination.perPage +
                                1
                            }}
                            –
                            {{
                                Math.min(
                                    expenseStore.pagination.currentPage *
                                        expenseStore.pagination.perPage,
                                    expenseStore.pagination.total,
                                )
                            }}
                            of {{ expenseStore.pagination.total }} expenses
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="
                                    expenseStore.goToPage(
                                        expenseStore.pagination.currentPage - 1,
                                    )
                                "
                                :disabled="
                                    expenseStore.pagination.currentPage === 1
                                "
                                class="px-4 py-2 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                Previous
                            </button>
                            <button
                                v-for="page in expenseStore.pagination.lastPage"
                                :key="page"
                                @click="expenseStore.goToPage(page)"
                                :class="[
                                    'px-4 py-2 rounded-lg transition',
                                    expenseStore.pagination.currentPage === page
                                        ? 'bg-blue-600 text-white'
                                        : 'border hover:bg-gray-50',
                                ]"
                            >
                                {{ page }}
                            </button>
                            <button
                                @click="
                                    expenseStore.goToPage(
                                        expenseStore.pagination.currentPage + 1,
                                    )
                                "
                                :disabled="
                                    expenseStore.pagination.currentPage ===
                                    expenseStore.pagination.lastPage
                                "
                                class="px-4 py-2 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Modal (unchanged logic, kept compact) ── -->
            <div
                v-if="showModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            >
                <div
                    class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto"
                >
                    <div
                        class="sticky top-0 bg-white border-b p-6 flex justify-between items-center"
                    >
                        <h2 class="text-2xl font-bold">
                            {{
                                modalMode === "create"
                                    ? "Add New Expense"
                                    : modalMode === "edit"
                                      ? "Edit Expense"
                                      : "Expense Details"
                            }}
                        </h2>
                        <button
                            @click="closeModal"
                            class="text-gray-500 hover:text-gray-700 transition"
                        >
                            <X :size="24" />
                        </button>
                    </div>

                    <div class="p-6">
                        <!-- View Mode -->
                        <div v-if="modalMode === 'view'" class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Expense ID
                                    </p>
                                    <p class="font-semibold">
                                        #{{ selectedExpense.id }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Status</p>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs text-white mt-1',
                                            getStatusConfig(
                                                selectedExpense.status,
                                            ).bg,
                                        ]"
                                    >
                                        <component
                                            :is="
                                                getStatusConfig(
                                                    selectedExpense.status,
                                                ).icon
                                            "
                                            :size="12"
                                        />
                                        {{
                                            getStatusConfig(
                                                selectedExpense.status,
                                            ).text
                                        }}
                                    </span>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-sm text-gray-600">
                                        Description
                                    </p>
                                    <p class="font-semibold">
                                        {{ selectedExpense.description }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Amount</p>
                                    <p class="font-semibold text-lg">
                                        {{
                                            formatCurrency(
                                                selectedExpense.amount,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Type</p>
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded text-xs font-medium mt-1 inline-block',
                                            selectedExpense.expense_type ===
                                            'expense'
                                                ? 'bg-red-100 text-red-800'
                                                : 'bg-green-100 text-green-800',
                                        ]"
                                    >
                                        {{
                                            selectedExpense.expense_type ===
                                            "expense"
                                                ? "Expense"
                                                : "Income"
                                        }}
                                    </span>
                                </div>
                                <div class="col-span-2">
                                    <p class="text-sm text-gray-600 mb-2">
                                        Vendor
                                    </p>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="font-medium">
                                            {{
                                                selectedExpense.vendor
                                                    ?.username || "N/A"
                                            }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Created</p>
                                    <p class="font-semibold">
                                        {{
                                            formatDate(
                                                selectedExpense.created_at,
                                            )
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Updated</p>
                                    <p class="font-semibold">
                                        {{
                                            formatDate(
                                                selectedExpense.updated_at,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Create / Edit Form -->
                        <form
                            v-else
                            @submit.prevent="
                                modalMode === 'create'
                                    ? handleCreate()
                                    : handleUpdate()
                            "
                            class="space-y-4"
                        >
                            <div
                                v-if="errors.submit"
                                class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm"
                            >
                                {{ errors.submit }}
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-1"
                                    >Expense Category</label
                                >
                                <v-autocomplete
                                    v-model="formData.expense_type_id"
                                    :items="expenseTypeItems"
                                    item-title="display_name"
                                    item-value="id"
                                    clearable
                                    dense
                                    outlined
                                    placeholder="Search expenses…"
                                    class="w-full"
                                />
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2"
                                    >Description
                                    <span class="text-red-500">*</span></label
                                >
                                <textarea
                                    :class="[
                                        'w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none',
                                        errors.description
                                            ? 'border-red-500'
                                            : '',
                                    ]"
                                    rows="3"
                                    v-model="formData.description"
                                    @input="clearError('description')"
                                    placeholder="Enter expense description"
                                />
                                <p
                                    v-if="errors.description"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.description }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium mb-2"
                                        >Amount (KES)
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :class="[
                                            'w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none',
                                            errors.amount
                                                ? 'border-red-500'
                                                : '',
                                        ]"
                                        v-model="formData.amount"
                                        @input="clearError('amount')"
                                        placeholder="0.00"
                                    />
                                    <p
                                        v-if="errors.amount"
                                        class="text-red-500 text-sm mt-1"
                                    >
                                        {{ errors.amount }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium mb-2"
                                        >Type
                                        <span class="text-red-500"
                                            >*</span
                                        ></label
                                    >
                                    <select
                                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        v-model="formData.expense_type"
                                    >
                                        <option value="expense">Expense</option>
                                        <option value="income">Income</option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium mb-2"
                                        >Vendor</label
                                    >
                                    <v-select
                                        v-model="formData.vendor_id"
                                        :items="vendorOptions"
                                        item-title="name"
                                        item-value="id"
                                        label="Vendor"
                                        prepend-inner-icon="mdi-domain"
                                        variant="outlined"
                                        density="comfortable"
                                    />
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium mb-2"
                                        >Status</label
                                    >
                                    <select
                                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        v-model="formData.status"
                                    >
                                        <option value="not_applied">
                                            Not Applied
                                        </option>
                                        <option value="applied">Applied</option>
                                        <option value="approved">
                                            Approved
                                        </option>
                                        <option value="rejected">
                                            Rejected
                                        </option>
                                    </select>
                                </div>

                                <div class="col-span-2">
                                    <label
                                        class="block text-sm font-medium mb-2"
                                        >Incurred On (Optional)</label
                                    >
                                    <v-text-field
                                        v-model="formData.incurred_on"
                                        label="Incurred On"
                                        type="date"
                                        prepend-inner-icon="mdi-calendar-clock"
                                        variant="outlined"
                                        density="comfortable"
                                    />
                                </div>
                            </div>

                            <div class="flex gap-3 justify-end pt-4 border-t">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    :disabled="expenseStore.loading"
                                    class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition disabled:opacity-50"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="expenseStore.loading"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 flex items-center gap-2"
                                >
                                    <Loader
                                        v-if="expenseStore.loading"
                                        class="animate-spin"
                                        :size="16"
                                    />
                                    {{
                                        modalMode === "create"
                                            ? "Create Expense"
                                            : "Update Expense"
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useVendorExpensesStore } from "@/stores/vendorExpenses";
import { useOrderStore } from "@/stores/orderStore";
import { useExpenseTypeStore } from "@/stores/expenseTypeStore.js";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Plus,
    Search,
    Edit,
    Trash2,
    Eye,
    X,
    FileText,
    CheckCircle,
    XCircle,
    AlertCircle,
    Loader,
    SlidersHorizontal,
    ChevronDown,
} from "lucide-vue-next";

// ── Stores ────────────────────────────────────────────────────────────────────
const expenseStore = useVendorExpensesStore();
const orderStore = useOrderStore();
const expenseTypesStore = useExpenseTypeStore();

const vendorOptions = computed(() => orderStore.vendorOptions);
const expenseTypeItems = computed(() => {
    const raw = expenseTypesStore.types;
    return raw?.data ?? raw ?? [];
});

// ── Modal state ───────────────────────────────────────────────────────────────
const showModal = ref(false);
const modalMode = ref("create");
const selectedExpense = ref(null);

const formData = ref({
    description: "",
    amount: "",
    expense_type: "expense",
    expense_type_id: "",
    vendor_id: "",
    country_id: "",
    status: "not_applied",
    incurred_on: "",
});
const errors = ref({});

// ── Filter state (local mirror of store.filters) ──────────────────────────────
const localSearch = ref("");
const showAdvanced = ref(false);

const localFilters = ref({
    status: "",
    expense_type: "",
    vendor_id: "",
    expense_type_id: "",
    remittance_id: "",
    incurred_on_from: "",
    incurred_on_to: "",
    created_at_from: "",
    created_at_to: "",
    sort_by: "created_at",
    sort_dir: "desc",
});

// ── Debounce helper (no external dep needed) ──────────────────────────────────
let searchTimer = null;
const debouncedSearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        expenseStore.applyFilters({ search: localSearch.value });
    }, 350);
};

/** Push all local filter state to the store and re-fetch. */
const applyAndReset = () => {
    expenseStore.applyFilters({ ...localFilters.value });
};

const clearAll = async () => {
    localSearch.value = "";
    localFilters.value = {
        status: "",
        expense_type: "",
        vendor_id: "",
        expense_type_id: "",
        remittance_id: "",
        incurred_on_from: "",
        incurred_on_to: "",
        created_at_from: "",
        created_at_to: "",
        sort_by: "created_at",
        sort_dir: "desc",
    };
    await expenseStore.clearFilters();
};

const removeFilter = (key) => {
    if (key === "search") {
        localSearch.value = "";
    } else {
        localFilters.value[key] = "";
    }
    applyAndReset();
};

// ── Active filter chips ───────────────────────────────────────────────────────
const CHIP_LABELS = {
    search: "Description",
    status: "Status",
    expense_type: "Type",
    vendor_id: "Vendor",
    expense_type_id: "Category",
    remittance_id: "Remittance",
    incurred_on_from: "Incurred ≥",
    incurred_on_to: "Incurred ≤",
    created_at_from: "Created ≥",
    created_at_to: "Created ≤",
};

const filterChips = computed(() => {
    const chips = [];
    if (localSearch.value)
        chips.push({
            key: "search",
            label: `Description: "${localSearch.value}"`,
        });
    for (const [k, v] of Object.entries(localFilters.value)) {
        if (v && k !== "sort_by" && k !== "sort_dir") {
            chips.push({ key: k, label: `${CHIP_LABELS[k] ?? k}: ${v}` });
        }
    }
    return chips;
});

const activeFilterCount = computed(() => filterChips.value.length);

// ── Lifecycle ─────────────────────────────────────────────────────────────────
onMounted(async () => {
    await expenseStore.fetchExpenses();
    await orderStore.fetchDropdownOptions();
    await expenseTypesStore.fetchExpenseTypes();
});

// ── CRUD ──────────────────────────────────────────────────────────────────────
const validateForm = () => {
    const e = {};
    if (!formData.value.expense_type_id)
        e.expense_type_id = "Expense selection is required";
    if (!formData.value.description?.trim())
        e.description = "Description is required";
    if (!formData.value.amount || parseFloat(formData.value.amount) <= 0)
        e.amount = "Valid amount is required";
    if (!formData.value.vendor_id) e.vendor_id = "Vendor is required";
    errors.value = e;
    return Object.keys(e).length === 0;
};

const handleCreate = async () => {
    if (!validateForm()) return;
    try {
        await expenseStore.createExpense(formData.value);
        closeModal();
    } catch (err) {
        errors.value = { submit: err.message || "Failed to create expense" };
    }
};

const handleUpdate = async () => {
    if (!validateForm()) return;
    try {
        await expenseStore.updateExpense(
            selectedExpense.value.id,
            formData.value,
        );
        closeModal();
    } catch (err) {
        errors.value = { submit: err.message || "Failed to update expense" };
    }
};

const handleDelete = async (id) => {
    if (!window.confirm("Are you sure you want to delete this expense?"))
        return;
    try {
        await expenseStore.deleteExpense(id);
    } catch (err) {
        alert(err.message || "Failed to delete expense");
    }
};

const handleExport = async () => {
    try {
        await expenseStore.exportExpenses();
    } catch {
        // error already shown via notify in the store
    }
};

const openModal = (mode, expense = null) => {
    modalMode.value = mode;
    selectedExpense.value = expense;
    errors.value = {};

    formData.value =
        mode === "create"
            ? {
                  description: "",
                  amount: "",
                  expense_type: "expense",
                  expense_type_id: "",
                  vendor_id: "",
                  country_id: "",
                  status: "not_applied",
                  incurred_on: "",
              }
            : {
                  description: expense.description,
                  amount: expense.amount.toString(),
                  expense_type: expense.expense_type,
                  vendor_id: expense.vendor_id?.toString() ?? "",
                  country_id: expense.country_id?.toString() ?? "",
                  status: expense.status,
                  incurred_on: expense.incurred_on ?? "",
                  expense_type_id: expense.expense_type_id ?? "",
              };

    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedExpense.value = null;
    errors.value = {};
};
const clearError = (field) => {
    delete errors.value[field];
};

const getStatusConfig = (status) =>
    ({
        not_applied: {
            bg: "bg-gray-500",
            icon: AlertCircle,
            text: "Not Applied",
        },
        applied: { bg: "bg-blue-500", icon: CheckCircle, text: "Applied" },
        approved: { bg: "bg-green-500", icon: CheckCircle, text: "Approved" },
        rejected: { bg: "bg-red-500", icon: XCircle, text: "Rejected" },
    })[status] ?? { bg: "bg-gray-500", icon: AlertCircle, text: "Not Applied" };

const formatDate = (d) =>
    d
        ? new Date(d).toLocaleDateString("en-US", {
              year: "numeric",
              month: "short",
              day: "numeric",
          })
        : "—";

const formatCurrency = (amount) =>
    new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "KES",
    }).format(amount);
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
