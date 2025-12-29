import { defineStore } from 'pinia'
import axios from 'axios'

export const useStkpushStore = defineStore('stkpush', {
    state: () => ({
        loading: false,
        error: null,
        success: false,
        lastRequest: null,
        lastResponse: null,
    }),

    getters: {
        isLoading: (state) => state.loading,
        hasError: (state) => !!state.error,
        errorMessage: (state) => state.error,
        isSuccess: (state) => state.success,
    },

    actions: {
        /**
         * Initiate STK Push
         * @param {Object} payload
         * @param {String|Number} payload.order_no - Order ID
         * @param {String} payload.phone - Phone number in format 254XXXXXXXXX
         * @param {Number} payload.amount - Amount in KES
         * @param {String} payload.description - Payment description (optional)
         * @returns {Promise<Object>} API response data
         */
        async initiateStkPush(payload) {
            this.loading = true
            this.error = null
            this.success = false
            this.lastRequest = payload

            try {
                const response = await axios.post(
                    '/api/v1/payments/mpesa/stk-push',
                    {
                        order_no: payload.orderId,
                        phone: payload.phone,
                        amount: payload.amount,
                        description: payload.description || 'Courier Payment',
                    }
                )

                this.lastResponse = response.data
                this.success = true

                return response.data
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    error.message ||
                    'Failed to initiate STK Push'

                throw error
            } finally {
                this.loading = false
            }
        },

        /**
         * Reset store state (call when dialog closes)
         */
        reset() {
            this.loading = false
            this.error = null
            this.success = false
            this.lastRequest = null
            this.lastResponse = null
        },
    },
})