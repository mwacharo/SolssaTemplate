import { defineStore } from "pinia";
import axios from "axios";

export const useConditionStore = defineStore("condition", {
    state: () => ({
        conditions: [],
        loading: false,
        error: null,
    }),

    actions: {

        /* ---------------- FETCH ALL ---------------- */
        async fetchConditions() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get("/api/v1/service-conditions");

                // assuming API returns { data: [...] }
                this.conditions = response.data|| [];
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to fetch service conditions";
                console.error("Fetch conditions error:", error);
            } finally {
                this.loading = false;
            }
        },

        /* ---------------- CREATE ---------------- */
        async createCondition(payload) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.post(
                    "/api/v1/service-conditions",
                    payload
                );

                this.conditions.unshift(response.data.data);

                return response.data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to create condition";
                console.error("Create condition error:", error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /* ---------------- UPDATE ---------------- */
        async updateCondition(id, payload) {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.put(
                    `/api/v1/service-conditions/${id}`,
                    payload
                );

                const index = this.conditions.findIndex(
                    (c) => c.id === id
                );

                if (index !== -1) {
                    this.conditions[index] = response.data.data;
                }

                return response.data.data;
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to update condition";
                console.error("Update condition error:", error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        /* ---------------- DELETE ---------------- */
        async deleteCondition(id) {
            this.loading = true;
            this.error = null;

            try {
                await axios.delete(`/api/v1/service-conditions/${id}`);

                this.conditions = this.conditions.filter(
                    (c) => c.id !== id
                );
            } catch (error) {
                this.error =
                    error.response?.data?.message ||
                    "Failed to delete condition";
                console.error("Delete condition error:", error);
                throw error;
            } finally {
                this.loading = false;
            }
        },
    },
});
