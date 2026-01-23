import { defineStore } from 'pinia';
import axios from 'axios';
import { notify } from '@/utils/toast';

export const useExpenseTypeStore = defineStore('expenseType', {
    state: () => ({
        types: [],
        categories: [],
        loading: false,
        error: null,
    }),

    getters: {
        getTypeById: (state) => (id) => {
            return state.types.find(type => type.id === id);
        },
        
        orderLevelTypes: (state) => {
            return state.types.filter(type => type.is_order_level);
        },
        
        itemLevelTypes: (state) => {
            return state.types.filter(type => !type.is_order_level);
        },
        
        getTypesByCategory: (state) => (categoryId) => {
            return state.types.filter(type => type.expense_category_id === categoryId);
        },
        
        hasTypes: (state) => state.types.length > 0,
    },

    actions: {
        async fetchTypes() {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.get('/api/v1/expense-types');
                this.types = response.data.data || response.data;
            } catch (error) {
                this.error = error.message;
                notify.error('Failed to fetch expense types');
                console.error('Error fetching types:', error);
            } finally {
                this.loading = false;
            }
        },

        async fetchCategories() {
            try {
                const response = await axios.get('/api/v1/expense-categories');
                this.categories = response.data.data || response.data;
            } catch (error) {
                notify.error('Failed to fetch expense categories');
                console.error('Error fetching categories:', error);
            }
        },

        async createType(typeData) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.post('/api/v1/expense-types', typeData);
                this.types.push(response.data.data || response.data);
                notify.success('Expense type created successfully');
                return response.data;
            } catch (error) {
                this.error = error.message;
                notify.error('Failed to create expense type');
                console.error('Error creating type:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateType(id, typeData) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.put(`/api/v1/expense-types/${id}`, typeData);
                const index = this.types.findIndex(type => type.id === id);
                
                if (index !== -1) {
                    this.types[index] = response.data.data || response.data;
                }
                
                notify.success('Expense type updated successfully');
                return response.data;
            } catch (error) {
                this.error = error.message;
                notify.error('Failed to update expense type');
                console.error('Error updating type:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteType(id) {
            this.loading = true;
            this.error = null;
            
            try {
                await axios.delete(`/api/v1/expense-types/${id}`);
                this.types = this.types.filter(type => type.id !== id);
                notify.success('Expense type deleted successfully');
            } catch (error) {
                this.error = error.message;
                notify.error('Failed to delete expense type');
                console.error('Error deleting type:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        resetError() {
            this.error = null;
        },
    },
});