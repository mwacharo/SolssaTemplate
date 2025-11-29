<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-50 p-6">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-800">
                                Seller Expenses
                            </h1>
                            <p class="text-gray-600 mt-1">
                                {{ filteredExpenses.length }} total expenses •
                                {{ formatCurrency(totalAmount) }} total amount
                            </p>
                        </div>
                        <button
                            @click="openModal('create')"
                            class="flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                        >
                            <Plus :size="20" />
                            Add Expense
                        </button>
                    </div>

                    <!-- Search and Filters -->
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative">
                            <Search
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                                :size="20"
                            />
                            <input
                                type="text"
                                placeholder="Search by description, seller, or email..."
                                class="w-full pl-10 pr-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                v-model="searchQuery"
                                @input="currentPage = 1"
                            />
                        </div>
                        <div class="flex items-center gap-2">
                            <Filter :size="20" class="text-gray-400" />
                            <select
                                class="border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                v-model="filterStatus"
                                @change="currentPage = 1"
                            >
                                <option value="all">All Status</option>
                                <option value="not_applied">Not Applied</option>
                                <option value="applied">Applied</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <select
                                class="border rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                v-model="filterType"
                                @change="currentPage = 1"
                            >
                                <option value="all">All Types</option>
                                <option value="expense">Expense</option>
                                <option value="income">Income</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Expenses Table -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-blue-600 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-left">ID</th>
                                    <th class="px-4 py-3 text-left">
                                        Description
                                    </th>
                                    <th class="px-4 py-3 text-left">Amount</th>
                                    <th class="px-4 py-3 text-left">Type</th>
                                    <th class="px-4 py-3 text-left">Seller</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">
                                        Created Date
                                    </th>
                                    <th class="px-4 py-3 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="paginatedExpenses.length === 0">
                                    <td
                                        colspan="8"
                                        class="px-4 py-8 text-center text-gray-500"
                                    >
                                        No expenses found
                                    </td>
                                </tr>
                                <tr
                                    v-for="(
                                        expense, index
                                    ) in paginatedExpenses"
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
                                                    expense.seller.first_name[0]
                                                }}
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium">
                                                    {{
                                                        expense.seller.username
                                                    }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{ expense.seller.email }}
                                                </p>
                                            </div>
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
                                                        expense.status
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
                                        {{ formatDate(expense.created_at) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex gap-2">
                                            <button
                                                @click="
                                                    openModal('view', expense)
                                                "
                                                class="p-2 hover:bg-gray-100 rounded-lg transition"
                                                title="View Details"
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

                    <!-- Pagination -->
                    <div
                        v-if="totalPages > 1"
                        class="border-t p-4 flex justify-between items-center"
                    >
                        <div class="text-sm text-gray-600">
                            Showing
                            {{ (currentPage - 1) * itemsPerPage + 1 }} to
                            {{
                                Math.min(
                                    currentPage * itemsPerPage,
                                    filteredExpenses.length
                                )
                            }}
                            of {{ filteredExpenses.length }} expenses
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="
                                    currentPage = Math.max(1, currentPage - 1)
                                "
                                :disabled="currentPage === 1"
                                class="px-4 py-2 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                Previous
                            </button>
                            <button
                                v-for="page in totalPages"
                                :key="page"
                                @click="currentPage = page"
                                :class="[
                                    'px-4 py-2 rounded-lg transition',
                                    currentPage === page
                                        ? 'bg-blue-600 text-white'
                                        : 'border hover:bg-gray-50',
                                ]"
                            >
                                {{ page }}
                            </button>
                            <button
                                @click="
                                    currentPage = Math.min(
                                        totalPages,
                                        currentPage + 1
                                    )
                                "
                                :disabled="currentPage === totalPages"
                                class="px-4 py-2 border rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                            >
                                Next
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div
                v-if="showModal"
                class="fixed inset-0 bg-grey bg-opacity-50 flex items-center justify-center z-50 p-4"
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
                                    <div class="mt-1">
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs text-white',
                                                getStatusConfig(
                                                    selectedExpense.status
                                                ).bg,
                                            ]"
                                        >
                                            <component
                                                :is="
                                                    getStatusConfig(
                                                        selectedExpense.status
                                                    ).icon
                                                "
                                                :size="12"
                                            />
                                            {{
                                                getStatusConfig(
                                                    selectedExpense.status
                                                ).text
                                            }}
                                        </span>
                                    </div>
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
                                                selectedExpense.amount
                                            )
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Type</p>
                                    <div class="mt-1">
                                        <span
                                            :class="[
                                                'px-2 py-1 rounded text-xs font-medium',
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
                                </div>
                                <div class="col-span-2">
                                    <p class="text-sm text-gray-600 mb-2">
                                        Seller Information
                                    </p>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <p class="font-medium">
                                            {{
                                                selectedExpense.seller
                                                    .first_name
                                            }}
                                            {{
                                                selectedExpense.seller.last_name
                                            }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            @{{
                                                selectedExpense.seller.username
                                            }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            {{ selectedExpense.seller.email }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            {{ selectedExpense.seller.phone }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Created Date
                                    </p>
                                    <p class="font-semibold">
                                        {{
                                            formatDate(
                                                selectedExpense.created_at
                                            )
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Last Updated
                                    </p>
                                    <p class="font-semibold">
                                        {{
                                            formatDate(
                                                selectedExpense.updated_at
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Create/Edit Form -->
                        <form
                            v-else
                            @submit.prevent="
                                modalMode === 'create'
                                    ? handleCreate()
                                    : handleUpdate()
                            "
                            class="space-y-4"
                        >
                            <div>
                                <label class="block text-sm font-medium mb-2">
                                    Description
                                    <span class="text-red-500">*</span>
                                </label>
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
                                    >
                                        Amount (KES)
                                        <span class="text-red-500">*</span>
                                    </label>
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
                                    >
                                        Type <span class="text-red-500">*</span>
                                    </label>
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
                                    >
                                        Seller ID
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        :class="[
                                            'w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none',
                                            errors.seller_id
                                                ? 'border-red-500'
                                                : '',
                                        ]"
                                        v-model="formData.seller_id"
                                        @input="clearError('seller_id')"
                                        placeholder="Enter seller ID"
                                    />
                                    <p
                                        v-if="errors.seller_id"
                                        class="text-red-500 text-sm mt-1"
                                    >
                                        {{ errors.seller_id }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium mb-2"
                                    >
                                        Country ID
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        :class="[
                                            'w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none',
                                            errors.country_id
                                                ? 'border-red-500'
                                                : '',
                                        ]"
                                        v-model="formData.country_id"
                                        @input="clearError('country_id')"
                                        placeholder="Enter country ID"
                                    />
                                    <p
                                        v-if="errors.country_id"
                                        class="text-red-500 text-sm mt-1"
                                    >
                                        {{ errors.country_id }}
                                    </p>
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

                                <div>
                                    <label
                                        class="block text-sm font-medium mb-2"
                                        >Invoice ID (Optional)</label
                                    >
                                    <input
                                        type="number"
                                        class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                        v-model="formData.invoice_id"
                                        placeholder="Enter invoice ID"
                                    />
                                </div>
                            </div>

                            <div class="flex gap-3 justify-end pt-4 border-t">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="px-4 py-2 border rounded-lg hover:bg-gray-50 transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                                >
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
import { ref, computed } from "vue";

import AppLayout from "@/Layouts/AppLayout.vue";

import {
    DollarSign,
    Plus,
    Search,
    Filter,
    Edit,
    Trash2,
    Eye,
    X,
    Calendar,
    User,
    FileText,
    CheckCircle,
    XCircle,
    AlertCircle,
} from "lucide-vue-next";

// State
const expenses = ref([
    {
        id: 1,
        description: "Transportation cost",
        amount: 1000,
        expense_type: "expense",
        invoice_id: null,
        seller_id: 16626,
        country_id: 15,
        status: "not_applied",
        created_at: "2025-09-11T09:58:00.000000Z",
        updated_at: "2025-09-22T05:09:37.000000Z",
        seller: {
            id: 16626,
            first_name: "FOD",
            last_name: "VENTURES",
            username: "FOD_VENTURES",
            email: "yemight2033@gmail.com",
            phone: "2348089519733",
        },
    },
]);

const searchQuery = ref("");
const filterStatus = ref("all");
const filterType = ref("all");
const currentPage = ref(1);
const showModal = ref(false);
const modalMode = ref("create");
const selectedExpense = ref(null);
const itemsPerPage = 10;

const formData = ref({
    description: "",
    amount: "",
    expense_type: "expense",
    seller_id: "",
    country_id: "",
    status: "not_applied",
    invoice_id: "",
});

const errors = ref({});

// Computed
const filteredExpenses = computed(() => {
    let filtered = expenses.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
            (exp) =>
                exp.description?.toLowerCase().includes(query) ||
                exp.seller?.username?.toLowerCase().includes(query) ||
                exp.seller?.email?.toLowerCase().includes(query)
        );
    }

    if (filterStatus.value !== "all") {
        filtered = filtered.filter((exp) => exp.status === filterStatus.value);
    }

    if (filterType.value !== "all") {
        filtered = filtered.filter(
            (exp) => exp.expense_type === filterType.value
        );
    }

    return filtered;
});

const totalPages = computed(() =>
    Math.ceil(filteredExpenses.value.length / itemsPerPage)
);

const paginatedExpenses = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = currentPage.value * itemsPerPage;
    return filteredExpenses.value.slice(start, end);
});

const totalAmount = computed(() =>
    filteredExpenses.value.reduce((sum, exp) => sum + parseFloat(exp.amount), 0)
);

// Methods
const validateForm = () => {
    const newErrors = {};

    if (!formData.value.description?.trim()) {
        newErrors.description = "Description is required";
    }

    if (!formData.value.amount || parseFloat(formData.value.amount) <= 0) {
        newErrors.amount = "Valid amount is required";
    }

    if (!formData.value.seller_id) {
        newErrors.seller_id = "Seller is required";
    }

    if (!formData.value.country_id) {
        newErrors.country_id = "Country is required";
    }

    errors.value = newErrors;
    return Object.keys(newErrors).length === 0;
};

const handleCreate = () => {
    if (!validateForm()) return;

    const newExpense = {
        id: Math.max(...expenses.value.map((e) => e.id), 0) + 1,
        ...formData.value,
        amount: parseFloat(formData.value.amount),
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString(),
        seller: {
            id: parseInt(formData.value.seller_id),
            username: "NEW_USER",
            first_name: "New",
            last_name: "User",
            email: "user@example.com",
            phone: "000000000",
        },
    };

    expenses.value = [newExpense, ...expenses.value];
    closeModal();
};

const handleUpdate = () => {
    if (!validateForm()) return;

    expenses.value = expenses.value.map((exp) =>
        exp.id === selectedExpense.value.id
            ? {
                  ...exp,
                  ...formData.value,
                  amount: parseFloat(formData.value.amount),
                  updated_at: new Date().toISOString(),
              }
            : exp
    );
    closeModal();
};

const handleDelete = (id) => {
    if (window.confirm("Are you sure you want to delete this expense?")) {
        expenses.value = expenses.value.filter((exp) => exp.id !== id);
    }
};

const openModal = (mode, expense = null) => {
    modalMode.value = mode;
    selectedExpense.value = expense;

    if (mode === "create") {
        formData.value = {
            description: "",
            amount: "",
            expense_type: "expense",
            seller_id: "",
            country_id: "",
            status: "not_applied",
            invoice_id: "",
        };
    } else if (expense) {
        formData.value = {
            description: expense.description,
            amount: expense.amount.toString(),
            expense_type: expense.expense_type,
            seller_id: expense.seller_id.toString(),
            country_id: expense.country_id.toString(),
            status: expense.status,
            invoice_id: expense.invoice_id?.toString() || "",
        };
    }

    errors.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedExpense.value = null;
    errors.value = {};
};

const clearError = (field) => {
    if (errors.value[field]) {
        delete errors.value[field];
    }
};

const getStatusConfig = (status) => {
    const configs = {
        not_applied: {
            bg: "bg-gray-500",
            icon: AlertCircle,
            text: "Not Applied",
        },
        applied: { bg: "bg-blue-500", icon: CheckCircle, text: "Applied" },
        approved: { bg: "bg-green-500", icon: CheckCircle, text: "Approved" },
        rejected: { bg: "bg-red-500", icon: XCircle, text: "Rejected" },
    };
    return configs[status] || configs.not_applied;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "KES",
    }).format(amount);
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
