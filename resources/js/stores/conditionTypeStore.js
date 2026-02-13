import { defineStore } from "pinia";
import axios from "axios";

export const useConditionTypeStore = defineStore("conditionType", {
    state: () => ({
        conditionTypes: [],
        loading: false,
        error: null,
    }),

    actions: {
        async fetchAll() {
            this.loading = true;
            try {
                const res = await axios.get("/api/v1/condition-types");
                this.conditionTypes = res.data.data;
            } catch (e) {
                this.error = e.response?.data || e.message;
            } finally {
                this.loading = false;
            }
        },

        async create(data) {
            const res = await axios.post("/api/v1/condition-types", data);
            this.conditionTypes.push(res.data);
        },

        async update(id, data) {
            const res = await axios.put(`/api/v1/condition-types/${id}`, data);
            const index = this.conditionTypes.findIndex(i => i.id === id);
            if (index !== -1) {
                this.conditionTypes[index] = res.data;
            }
        },

        async delete(id) {
            await axios.delete(`/api/v1/condition-types/${id}`);
            this.conditionTypes = this.conditionTypes.filter(i => i.id !== id);
        },

        async toggleActive(item) {
            await this.update(item.id, {
                ...item,
                is_active: !item.is_active
            });
        }
    }
});
