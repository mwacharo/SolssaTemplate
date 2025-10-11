import { defineStore } from 'pinia'
import axios from 'axios'

export const useAgentStore = defineStore('agent', {
    state: () => ({
        callHistory: [],
        loading: false,
        error: null,
    }),
    actions: {





        // async fetchCallHistory(options = {}) {
        //     this.loading = true
        //     this.error = null
        //     try {
        //         // Build query params
        //         const params = {
        //             page: options.page || 1,
        //             per_page: options.itemsPerPage || 15,
        //             sort: options.sortBy?.length ? options.sortBy.map(s => (s.desc ? '-' : '') + s.key).join(',') : undefined,
        //             search: options.search || undefined,
        //         }
        //         // Remove undefined params
        //         Object.keys(params).forEach(k => params[k] === undefined && delete params[k])

        //         const response = await axios.get('/api/v1/call-history', { params })
        //         // Adjust for nested data structure in backend response
        //         // const callHistoryData = response.data?.data?.data?.data || []
        //         // const total = response.data?.data?.data?.total || 0


        //         // ✅ Corrected parsing
        //         // const callHistoryData = response.data?.data?.data || [];
        //         // const total = response.data?.data?.total || 0;


        //         const callHistoryData = response.data?.data?.data || []
        //         const total = response.data?.data?.total || 0

        //         this.callHistory = callHistoryData
        //         return {
        //             total,
        //             data: callHistoryData,
        //         }
        //     } catch (e) {
        //         this.error = e
        //         this.callHistory = []
        //         throw e
        //     } finally {
        //         this.loading = false
        //     }
        // },
        // clearCallHistory() {
        //     this.callHistory = []
        // },



        // Pinia Store or Composable
async fetchCallHistory(options = {}) {
    this.loading = true
    this.error = null
    try {
        // Build query params - handle sorting correctly
        let sort = undefined
        if (options.sortBy && options.sortBy.length > 0) {
            sort = options.sortBy
                .map(s => {
                    // sortBy format: { key: 'columnName', order: 'asc'|'desc' }
                    // or { key: 'columnName', desc?: boolean }
                    const prefix = s.desc || s.order === 'desc' ? '-' : ''
                    return prefix + s.key
                })
                .join(',')
        }

        const params = {
            page: options.page || 1,
            per_page: options.itemsPerPage || 15,
            sort_by: sort || 'created_at',
            sort_desc: sort ? undefined : true, // Only send sort_desc if no sort specified
            search: options.search || undefined,
        }

        // Remove undefined params
        Object.keys(params).forEach(k => params[k] === undefined && delete params[k])

        console.log('Fetching with params:', params) // Debug

        const response = await axios.get('/api/v1/call-history', { params })

        // ✅ Extract nested data structure correctly
        const paginatedData = response.data.data
        
        this.callHistory = paginatedData.data || []
        this.totalItems = paginatedData.total || 0
        this.currentPage = paginatedData.current_page || 1

        return {
            total: paginatedData.total,
            data: paginatedData.data,
            currentPage: paginatedData.current_page,
            perPage: paginatedData.per_page,
            lastPage: paginatedData.last_page,
        }
    } catch (e) {
        console.error('Fetch call history error:', e)
        this.error = e.message
        this.callHistory = []
        this.totalItems = 0
        throw e
    } finally {
        this.loading = false
    }
},

clearCallHistory() {
    this.callHistory = []
    this.totalItems = 0
    this.currentPage = 1
},

    },
})