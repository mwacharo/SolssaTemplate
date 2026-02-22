// stores/orderReport.js
import { defineStore } from "pinia";
import { ref, computed } from "vue";
import axios from "axios";


import { useOrderStore } from "@/stores/orderStore";



const useOrderReportStore = defineStore("orderReport", () => {


    const orderStore = useOrderStore();

    // ─── Report Types ─────────────────────────────────────────────────────────
    const reportTypes = [
        // { id: "delivery", name: "Delivery Report" },
        // { id: "returns", name: "Returns Report" },
        // { id: "dispatch", name: "Dispatch Report" },
        // { id: "out_scan", name: "Out Scan Report" },
        // { id: "undispatched", name: "Undispatched Report" },
        { id: "merchant", name: "Merchant Report" },
        // { id: "product", name: "Product Report" },
        // { id: "product_performance", name: "Product Performance" },
        // { id: "zone", name: "Zone Report" },
    ];

    const reportFilterConfig = {
        delivery:            ["zone", "city", "rider", "deliveryDate", "shippingStatus"],
        returns:             ["zone", "city", "rider", "deliveryDate", "shippingStatus"],
        dispatch:            ["merchant", "zone", "city", "orderDate"],
        out_scan:            ["zone", "city", "rider", "deliveryDate"],
        undispatched:        ["merchant", "product", "zone", "city", "orderDate"],
        merchant:            ["merchant", "product", "zone", "city", "confirmationStatus", "shippingStatus", "orderDate",'statusDate'],
        product:             ["merchant", "product", "category", "zone", "city", "orderDate"],
        product_performance: ["merchant", "product", "category", "orderDate"],
        city:                ["city", "zone", "orderDate", "shippingStatus"],
        zone:                ["zone", "city", "orderDate", "shippingStatus"],
    };

    const columnConfig = {
        delivery: [
            { key: "order_no",        label: "Order No" },
            { key: "product_items",   label: "Product Items" },
            { key: "receiver_name",   label: "Receiver Name" },
            { key: "receiver_address",label: "Receiver Address" },
            { key: "receiver_phone",  label: "Receiver Phone" },
            { key: "receiver_town",   label: "Receiver Town" },
            { key: "cash_on_delivery",label: "Cash on Delivery" },
            { key: "total_qty",       label: "Total Qty" },
            { key: "order_status",    label: "Order Status" },
            { key: "created_at",      label: "Created At" },
            { key: "scheduled_date",  label: "Scheduled Date" },
            { key: "delivery_date",   label: "Delivery Date" },
            { key: "rider",           label: "Rider" },
            // add city
            { key: "city",            label: "City" },
            { key: "zone",            label: "Zone" },
        ],
        merchant: [
            { key: "order_no",              label: "Order No" },
            { key: "product_items",         label: "Product Items" },
            { key: "receiver_name",         label: "Receiver Name" },
            { key: "receiver_address",      label: "Receiver Address" },
            { key: "receiver_phone",        label: "Receiver Phone" },
            { key: "receiver_town",         label: "Receiver Town" },
            { key: "special_instructions",  label: "Special Instructions" },
            { key: "cash_on_delivery",      label: "Cash on Delivery" },
            { key: "total_qty",             label: "Total Qty" },
            { key: "order_status",          label: "Order Status" },
            { key: "created_at",            label: "Created At" },
            { key: "scheduled_date",        label: "Scheduled Date" },
            { key: "delivery_date",         label: "Delivery Date" },
            { key: "rider",                 label: "Rider" },
            // add city 
            { key: "city",                  label: "City" },
            { key: "zone",                  label: "Zone" },
            { key: "agent",                 label: "Agent" },
        ],
        default: [
            { key: "order_no",      label: "Order No" },
            { key: "product_items", label: "Product Items" },
            { key: "receiver_name", label: "Receiver Name" },
            { key: "receiver_phone",label: "Receiver Phone" },
            { key: "order_status",  label: "Order Status" },
            { key: "created_at",    label: "Created At" },
            { key: "city",          label: "City" },
            { key: "zone",          label: "Zone" },
        ],
    };

    // ─── State ────────────────────────────────────────────────────────────────
    const selectedReportType = ref(null);

    const filters = ref({
        merchant:           null,
        product:            null,
        category:           null,
        zone:               null,
        city:               null,
        rider:              null,
        agent:             null,
        confirmationStatus: null,
        shippingStatus:     null,
        orderDate:          null,
        deliveryDate:       null,
    });

    const options = ref({
        merchants: [],
        products:  [],
        categories:[],
        zones:     [],
        cities:    [],
        riders:    [],
        agents:    [],
        confirmationStatuses: [
         
        ],
       
    });

    const results    = ref([]);
    const pagination = ref({ page: 1, perPage: 25, total: 0 });
    const loading    = ref({ generate: false, download: false, options: false });
    const error      = ref(null);

    // ─── Computed ─────────────────────────────────────────────────────────────
    const activeFilterFields = computed(() => {
        if (!selectedReportType.value) return [];
        return reportFilterConfig[selectedReportType.value] ?? [];
    });

    const activeColumns = computed(() => {
        if (!selectedReportType.value) return [];
        return columnConfig[selectedReportType.value] ?? columnConfig.default;
    });

    // ─── Actions ──────────────────────────────────────────────────────────────
    function resetFilters() {
        selectedReportType.value = null;
        Object.keys(filters.value).forEach((k) => (filters.value[k] = null));
        results.value = [];
        error.value   = null;
        pagination.value = { page: 1, perPage: 25, total: 0 };
    }

    function buildParams() {
        const params = {
            report_type: selectedReportType.value,
            page:        pagination.value.page,
            per_page:    pagination.value.perPage,


        merchant: orderStore.orderFilterVendor,
        product: orderStore.orderFilterProduct,
        category: orderStore.orderFilterCategory,
        zone: orderStore.orderFilterZone,
        city: orderStore.orderFilterCity,
        rider: orderStore.orderFilterRider,
        agent: orderStore.orderFilterAgent,

        confirmationStatus: orderStore.orderFilterStatus,
        statusDate: orderStore.statusDateRange,
        deliveryDate: orderStore.deliveryDateRange,
        orderDate: orderStore.createdDateRange,

        };
        activeFilterFields.value.forEach((field) => {
            if (filters.value[field] !== null && filters.value[field] !== "") {
                params[field] = filters.value[field];
            }
        });
        return params;
    }

    async function fetchOptions() {
        loading.value.options = true;
        try {
            const { data } = await axios.get("/api/v1/reports/options");
            options.value.merchants  = data.merchants  ?? [];
            options.value.products   = data.products   ?? [];
            options.value.categories = data.categories ?? [];
            options.value.zones      = data.zones      ?? [];
            options.value.cities     = data.cities     ?? [];
            options.value.riders     = data.riders     ?? [];
            options.value.agents     = data.agents     ?? [];
        } catch (e) {
            console.error("Failed to load report options", e);
        } finally {
            loading.value.options = false;
        }
    }

    async function generateReport() {
        if (!selectedReportType.value) return;
        error.value = null;
        loading.value.generate = true;
        pagination.value.page  = 1;
        try {
            const { data } = await axios.get("/api/v1/reports/generate", {
                params: buildParams(),
            });
            results.value            = data.data  ?? [];
            pagination.value.total   = data.total ?? 0;
        } catch (e) {
            error.value   = e.response?.data?.message ?? "Failed to generate report.";
            results.value = [];
        } finally {
            loading.value.generate = false;
        }
    }

    async function changePage(page) {
        pagination.value.page = page;
        await generateReport();
    }

    async function downloadExcel() {
        if (!selectedReportType.value) return;
        loading.value.download = true;
        try {
            const response = await axios.get("/api/v1/reports/download", {
                params:       { ...buildParams(), format: "xlsx" },
                responseType: "blob",
            });
            const url  = URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement("a");
            link.href  = url;
            link.setAttribute(
                "download",
                `${selectedReportType.value}_report_${Date.now()}.xlsx`,
            );
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        } catch (e) {
            error.value = "Failed to download Excel file.";
        } finally {
            loading.value.download = false;
        }
    }

    return {
        // state
        reportTypes,
        selectedReportType,
        filters,
        options,
        results,
        pagination,
        loading,
        error,
        // computed
        activeFilterFields,
        activeColumns,
        // actions
        fetchOptions,
        generateReport,
        changePage,
        downloadExcel,
        resetFilters,
    };
});

export default useOrderReportStore;