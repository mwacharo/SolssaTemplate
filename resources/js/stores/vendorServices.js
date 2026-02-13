// stores/vendorServices.js
import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useVendorServicesStore = defineStore('vendorServices', () => {
    // State
    const isModalOpen = ref(false);
    const selectedVendorId = ref(null);
    const vendorServices = ref([]);
    const availableServices = ref([]);
    const overrides = ref({});
    const loading = ref(false);

    // Actions
    const openModal = async (vendorId) => {
        selectedVendorId.value = vendorId;
        isModalOpen.value = true;
        await loadVendorServices(vendorId);
    };

    const closeModal = () => {
        isModalOpen.value = false;
        selectedVendorId.value = null;
        vendorServices.value = [];
        overrides.value = {};
    };

    const loadVendorServices = async (vendorId) => {
        if (!vendorId) return;

        loading.value = true;
        try {
            const res = await axios.get(`/api/v1/vendors/${vendorId}/services`);
            vendorServices.value = res.data;

            // Initialize override map
            vendorServices.value.forEach((vs) => {
                overrides.value[vs.id] = {};
                vs.service.conditions.forEach((cond) => {
                    overrides.value[vs.id][cond.id] = {
                        custom_rate: cond.custom_rate || null,
                        rate_type: cond.custom_rate_type || cond.rate_type,
                    };
                });
            });
        } catch (error) {
            console.error('Failed to load vendor services:', error);
            throw error;
        } finally {
            loading.value = false;
        }
    };

    const loadAvailableServices = async () => {
        try {
            const res = await axios.get('/api/v1/services');
            availableServices.value = res.data;
        } catch (error) {
            console.error('Failed to load services:', error);
            throw error;
        }
    };

    const assignService = async (serviceId) => {
        if (!serviceId || !selectedVendorId.value) return;

        try {
            await axios.post(`/api/v1/vendors/${selectedVendorId.value}/services`, {
                service_id: serviceId,
                vendor_id: selectedVendorId.value,
            });

            await loadVendorServices(selectedVendorId.value);
            return true;
        } catch (error) {
            console.error('Failed to assign service:', error);
            throw error;
        }
    };

    const removeService = async (vendorServiceId) => {
        if (!selectedVendorId.value) return;

        try {
            await axios.delete(
                `/api/v1/vendors/${selectedVendorId.value}/services/${vendorServiceId}`
            );

            await loadVendorServices(selectedVendorId.value);
            return true;
        } catch (error) {
            console.error('Failed to remove service:', error);
            throw error;
        }
    };

    const saveOverride = async (vendorServiceId, conditionId) => {
        const data = overrides.value[vendorServiceId]?.[conditionId];

        if (!data || data.custom_rate === null || data.custom_rate === '') {
            throw new Error('Please enter a rate');
        }

        try {
            await axios.post(
                `/api/v1/vendors/${selectedVendorId.value}/services/${vendorServiceId}/rates`,
                {
                    service_condition_id: conditionId,
                    custom_rate: data.custom_rate,
                    rate_type: data.rate_type,
                }
            );

            return true;
        } catch (error) {
            console.error('Failed to save override:', error);
            throw error;
        }
    };

    const updateOverride = (vendorServiceId, conditionId, field, value) => {
        if (!overrides.value[vendorServiceId]) {
            overrides.value[vendorServiceId] = {};
        }
        if (!overrides.value[vendorServiceId][conditionId]) {
            overrides.value[vendorServiceId][conditionId] = {
                custom_rate: null,
                rate_type: 'fixed',
            };
        }
        overrides.value[vendorServiceId][conditionId][field] = value;
    };

    return {
        // State
        isModalOpen,
        selectedVendorId,
        vendorServices,
        availableServices,
        overrides,
        loading,

        // Actions
        openModal,
        closeModal,
        loadVendorServices,
        loadAvailableServices,
        assignService,
        removeService,
        saveOverride,
        updateOverride,
    };
});