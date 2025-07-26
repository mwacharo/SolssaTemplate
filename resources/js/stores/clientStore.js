// stores/clientStore.js
import { defineStore } from 'pinia'
import axios from 'axios'

export const useClientStore = defineStore('client', {
    state: () => ({
        vendors: [],
        riders: [],
        loading: false,
        error: null
    }),

    getters: {
        getVendorById: (state) => (id) => {
            return state.vendors.find(vendor => vendor.id === id)
        },
        
        getClientById: (state) => (vendorId, clientId) => {
            const vendor = state.vendors.find(v => v.id === vendorId)
            if (vendor && vendor.clients) {
                return vendor.clients.find(client => client.id === clientId)
            }
            return null
        },
        
        getTotalClients: (state) => {
            return state.vendors.reduce((total, vendor) => {
                return total + (vendor.clients ? vendor.clients.length : 0)
            }, 0)
        },
        
        getActiveVendors: (state) => {
            return state.vendors.filter(vendor => vendor.status === 1)
        },
        
        getActiveRiders: (state) => {
            return state.riders.filter(rider => rider.status === 1)
        },
        
        getVendorsByOnboardingStage: (state) => (stage) => {
            return state.vendors.filter(vendor => vendor.onboarding_stage === stage)
        }
    },

    actions: {
        async fetchVendors() {
            this.loading = true
            this.error = null
            try {
                const response = await axios.get('/api/v1/vendors')
                this.vendors = response.data.data || []
            } catch (error) {
                this.error = error.response?.data?.message || error.message
                console.error('Error fetching vendors:', error)
            } finally {
                this.loading = false
            }
        },

        async fetchRiders() {
            this.loading = true
            this.error = null
            try {
                const response = await axios.get('/api/v1/riders')
                this.riders = response.data.data || []
            } catch (error) {
                this.error = error.response?.data?.message || error.message
                console.error('Error fetching riders:', error)
            } finally {
                this.loading = false
            }
        },

        async addVendor(vendorData) {
            this.loading = true
            this.error = null
            try {
                const response = await axios.post('/api/v1/vendors', {
                    name: vendorData.name,
                    company_name: vendorData.company_name || vendorData.name,
                    email: vendorData.email,
                    phone: vendorData.phone,
                    address: vendorData.address,
                    city: vendorData.city,
                    state: vendorData.state,
                    country: vendorData.country,
                    business_type: vendorData.business_type || 'Corporation',
                    delivery_mode: vendorData.delivery_mode || 'both',
                    status: 1
                })
                this.vendors.push({ ...response.data, clients: [] })
            } catch (error) {
                this.error = error.response?.data?.message || error.message
                console.error('Error adding vendor:', error)
                throw error
            } finally {
                this.loading = false
            }
        },

        async updateVendor(vendorId, updatedData) {
            this.loading = true
            this.error = null
            try {
                await axios.put(`/api/v1/vendors/${vendorId}`, updatedData)
                await this.fetchVendors()
            } catch (error) {
                this.error = error.response?.data?.message || error.message
                console.error('Error updating vendor:', error)
                throw error
            } finally {
                this.loading = false
            }
        },

        async deleteVendor(vendorId) {
            this.loading = true
            this.error = null
            try {
                await axios.delete(`/api/v1/vendors/${vendorId}`)
                this.vendors = this.vendors.filter(v => v.id !== vendorId)
            } catch (error) {
                this.error = error.response?.data?.message || error.message
                console.error('Error deleting vendor:', error)
                throw error
            } finally {
                this.loading = false
            }
        },

        async addClient(vendorId, clientData) {
            this.loading = true
            this.error = null
            try {
                const response = await axios.post('http://127.0.0.1:8000/api/v1/clients', {
                    name: clientData.name,
                    email: clientData.email || null,
                    phone_number: clientData.phone,
                    address: clientData.address || null,
                    city: clientData.city,
                    vendor_id: vendorId,
                    status: 'active'
                })
                const vendor = this.vendors.find(v => v.id === vendorId)
                if (vendor) {
                    if (!vendor.clients) vendor.clients = []
                    vendor.clients.push(response.data)
                }
            } catch (error) {
                this.error = error.response?.data?.message || error.message
                console.error('Error adding client:', error)
                throw error
            } finally {
                this.loading = false
            }
        },

        async updateClient(clientId, updatedData) {
            this.loading = true
            this.error = null
            try {
                await axios.put(`http://127.0.0.1:8000/api/v1/clients/${clientId}`, {
                    name: updatedData.name,
                    email: updatedData.email,
                    phone_number: updatedData.phone,
                    address: updatedData.address,
                    city: updatedData.city,
                    status: updatedData.status
                })
                await this.fetchVendors()
            } catch (error) {
                this.error = error.response?.data?.message || error.message
                console.error('Error updating client:', error)
                throw error
            } finally {
                this.loading = false
            }
        },

        async deleteClient(vendorId, clientId) {
            this.loading = true
            this.error = null
            try {
                await axios.delete(`http://127.0.0.1:8000/api/v1/clients/${clientId}`)
                const vendor = this.vendors.find(v => v.id === vendorId)
                if (vendor && vendor.clients) {
                    vendor.clients = vendor.clients.filter(c => c.id !== clientId)
                }
            } catch (error) {
                this.error = error.response?.data?.message || error.message
                console.error('Error deleting client:', error)
                throw error
            } finally {
                this.loading = false
            }
        },

        async addRider(riderData) {
            this.loading = true
            this.error = null
            try {
                const response = await axios.post('/api/v1/riders', {
                    name: riderData.name,
                    email: riderData.email,
                    phone: riderData.phone,
                    address: riderData.address,
                    city: riderData.city,
                    state: riderData.state,
                    vehicle_number: riderData.vehicle_number,
                    license_number: riderData.license_number,
                    status: 1
                })
                this.riders.push(response.data)
            } catch (error) {
                this.error = error.response?.data?.message || error.message
                console.error('Error adding rider:', error)
                throw error
            } finally {
                this.loading = false
            }
        },

        async initializeData() {
            await Promise.all([
                this.fetchVendors(),
                this.fetchRiders()
            ])
        },

        transformVendorForAPI(vendorData) {
            return {
                name: vendorData.name,
                company_name: vendorData.company_name || vendorData.name,
                email: vendorData.email,
                billing_email: vendorData.billing_email || vendorData.email,
                phone: vendorData.phone,
                address: vendorData.address,
                city: vendorData.city,
                state: vendorData.state,
                country: vendorData.country,
                business_type: vendorData.business_type || 'Corporation',
                delivery_mode: vendorData.delivery_mode || 'both',
                status: vendorData.status || 1
            }
        },

        transformClientForAPI(clientData, vendorId) {
            return {
                name: clientData.name,
                email: clientData.email,
                phone_number: clientData.phone,
                address: clientData.address,
                city: clientData.city,
                vendor_id: vendorId,
                status: clientData.status || 'active'
            }
        }
    }
})
