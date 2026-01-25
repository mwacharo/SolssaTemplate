import { defineStore } from 'pinia';
import axios from 'axios';

export const useServicesStore = defineStore('services', {
    state: () => ({
        services: [],
        pagination: null,
        loading: false,
        error: null,
    }),

    actions: {
        async fetchServices(page = 1, perPage = 15) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.get('/api/v1/services', {
                    params: { page, per_page: perPage }
                });
                
                // Extract the array from the paginated response
                // Laravel pagination wraps data in: { data: { data: [...], current_page, etc } }
                this.services = response.data.data.data || [];
                this.pagination = {
                    current_page: response.data.data.current_page,
                    last_page: response.data.data.last_page,
                    per_page: response.data.data.per_page,
                    total: response.data.data.total,
                    from: response.data.data.from,
                    to: response.data.data.to,
                };
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to fetch services';
                console.error('Fetch services error:', err);
            } finally {
                this.loading = false;
            }
        },

        async createService(serviceData) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.post('/api/v1/services', serviceData);
                
                // Add the new service to the beginning of the array
                this.services.unshift(response.data.data);
                
                // Update total count if pagination exists
                if (this.pagination) {
                    this.pagination.total += 1;
                }
                
                return response.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to create service';
                console.error('Error creating service:', err);
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async updateService(id, serviceData) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.put(`/api/v1/services/${id}`, serviceData);
                
                // Update the service in the array
                const index = this.services.findIndex(s => s.id === id);
                if (index !== -1) {
                    this.services[index] = response.data.data;
                }
                
                return response.data;
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to update service';
                console.error('Error updating service:', err);
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async deleteService(id) {
            this.loading = true;
            this.error = null;
            
            try {
                await axios.delete(`/api/v1/services/${id}`);
                
                // Remove the service from the array
                this.services = this.services.filter(s => s.id !== id);
                
                // Update total count if pagination exists
                if (this.pagination) {
                    this.pagination.total -= 1;
                }
            } catch (err) {
                this.error = err.response?.data?.message || 'Failed to delete service';
                console.error('Error deleting service:', err);
                throw err;
            } finally {
                this.loading = false;
            }
        },

        async toggleServiceStatus(id) {
            const service = this.services.find(s => s.id === id);
            if (!service) return;

            // Optimistically update UI
            const originalStatus = service.is_active;
            service.is_active = !service.is_active;

            try {
                await this.updateService(id, {
                    service_name: service.service_name,
                    description: service.description,
                    is_active: service.is_active,
                });
            } catch (err) {
                // Revert on error
                service.is_active = originalStatus;
                console.error('Error toggling service status:', err);
                throw err;
            }
        },

        clearError() {
            this.error = null;
        },
    },
});