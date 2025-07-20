// stores/clientStore.js
import { defineStore } from 'pinia'

export const useClientStore = defineStore('client', {
    state: () => ({
        vendors: []
    }),

    getters: {
        getVendorById: (state) => (id) => {
            return state.vendors.find(vendor => vendor.id === id)
        },
        
        getClientById: (state) => (vendorId, clientId) => {
            const vendor = state.vendors.find(v => v.id === vendorId)
            if (vendor) {
                return vendor.clients.find(client => client.id === clientId)
            }
            return null
        },
        
        getTotalClients: (state) => {
            return state.vendors.reduce((total, vendor) => total + vendor.clients.length, 0)
        },
        
        getActiveVendors: (state) => {
            return state.vendors.filter(vendor => vendor.status === 'active')
        }
    },

    actions: {
        initializeData() {
            // Initialize with sample data if no data exists
            if (this.vendors.length === 0) {
                this.vendors = [
                    {
                        id: 1,
                        name: 'Swift Logistics Ltd',
                        email: 'contact@swiftlogistics.com',
                        phone: '+254-700-123-456',
                        address: '123 Industrial Area, Nairobi',
                        status: 'active',
                        clients: [
                            {
                                id: 1,
                                name: 'Nairobi Supermarket Chain',
                                email: 'procurement@nairobisupermarket.com',
                                phone: '+254-711-234-567',
                                address: '456 City Center, Nairobi',
                                status: 'active',
                                totalOrders: 145
                            },
                            {
                                id: 2,
                                name: 'East Africa Electronics',
                                email: 'orders@eaelectronics.com',
                                phone: '+254-722-345-678',
                                address: '789 Tech Hub, Westlands',
                                status: 'active',
                                totalOrders: 89
                            }
                        ]
                    },
                    {
                        id: 2,
                        name: 'Express Courier Services',
                        email: 'info@expresscourier.co.ke',
                        phone: '+254-733-456-789',
                        address: '321 Mombasa Road, Nairobi',
                        status: 'active',
                        clients: [
                            {
                                id: 3,
                                name: 'Mombasa Import Co.',
                                email: 'logistics@mombasaimport.com',
                                phone: '+254-741-567-890',
                                address: '654 Port Area, Mombasa',
                                status: 'active',
                                totalOrders: 203
                            },
                            {
                                id: 4,
                                name: 'Kisumu Distributors',
                                email: 'orders@kisumudist.com',
                                phone: '+254-752-678-901',
                                address: '987 Lakeside, Kisumu',
                                status: 'inactive',
                                totalOrders: 67
                            },
                            {
                                id: 5,
                                name: 'Nakuru Agricultural Supplies',
                                email: 'supply@nakuruagri.com',
                                phone: '+254-763-789-012',
                                address: '147 Farm Road, Nakuru',
                                status: 'active',
                                totalOrders: 134
                            }
                        ]
                    },
                    {
                        id: 3,
                        name: 'Reliable Transport Co.',
                        email: 'service@reliabletransport.com',
                        phone: '+254-774-890-123',
                        address: '258 Thika Road, Nairobi',
                        status: 'active',
                        clients: [
                            {
                                id: 6,
                                name: 'Eldoret Manufacturing',
                                email: 'shipping@eldoretmanuf.com',
                                phone: '+254-785-901-234',
                                address: '369 Industrial Zone, Eldoret',
                                status: 'active',
                                totalOrders: 178
                            }
                        ]
                    },
                    {
                        id: 4,
                        name: 'Kenya Fast Delivery',
                        email: 'hello@kenyafast.com',
                        phone: '+254-796-012-345',
                        address: '741 Ngong Road, Nairobi',
                        status: 'inactive',
                        clients: []
                    }
                ]
            }
        },

        addVendor(vendorData) {
            this.vendors.push({
                ...vendorData,
                id: vendorData.id || Date.now(),
                status: vendorData.status || 'active',
                clients: vendorData.clients || []
            })
        },

        updateVendor(vendorId, updatedData) {
            const index = this.vendors.findIndex(vendor => vendor.id === vendorId)
            if (index !== -1) {
                this.vendors[index] = { ...this.vendors[index], ...updatedData }
            }
        },

        deleteVendor(vendorId) {
            const index = this.vendors.findIndex(vendor => vendor.id === vendorId)
            if (index !== -1) {
                this.vendors.splice(index, 1)
            }
        },

        addClient(vendorId, clientData) {
            const vendor = this.vendors.find(v => v.id === vendorId)
            if (vendor) {
                vendor.clients.push({
                    ...clientData,
                    id: clientData.id || Date.now(),
                    status: clientData.status || 'active',
                    totalOrders: clientData.totalOrders || 0
                })
            }
        },

        updateClient(vendorId, clientId, updatedData) {
            const vendor = this.vendors.find(v => v.id === vendorId)
            if (vendor) {
                const clientIndex = vendor.clients.findIndex(c => c.id === clientId)
                if (clientIndex !== -1) {
                    vendor.clients[clientIndex] = { ...vendor.clients[clientIndex], ...updatedData }
                }
            }
        },

        deleteClient(vendorId, clientId) {
            const vendor = this.vendors.find(v => v.id === vendorId)
            if (vendor) {
                const clientIndex = vendor.clients.findIndex(c => c.id === clientId)
                if (clientIndex !== -1) {
                    vendor.clients.splice(clientIndex, 1)
                }
            }
        }
    }
})