import { defineStore } from 'pinia'
import axios from 'axios'

export const useAgentStore = defineStore('agent', {
    state: () => ({
        callHistory: [],
        loading: false,
        error: null,
    }),
    actions: {
        async fetchCallHistory(options = {}) {
            this.loading = true
            this.error = null
            try {
                // Build query params
                const params = {
                    page: options.page || 1,
                    per_page: options.itemsPerPage || 15,
                    sort: options.sortBy?.length ? options.sortBy.map(s => (s.desc ? '-' : '') + s.key).join(',') : undefined,
                    search: options.search || undefined,
                }
                // Remove undefined params
                Object.keys(params).forEach(k => params[k] === undefined && delete params[k])

                const response = await axios.get('/api/v1/agent/call-history', { params })
                this.callHistory = response.data.data || []
                return {
                    total: response.data.total || 0,
                    data: this.callHistory,
                }
            } catch (e) {
                this.error = e
                this.callHistory = []
                throw e
            } finally {
                this.loading = false
            }
        },
        clearCallHistory() {
            this.callHistory = []
        },
    },
})