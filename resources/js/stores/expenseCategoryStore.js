import { defineStore } from 'pinia';
import axios from 'axios';
import { notify } from '@/utils/toast';

export const useExpenseCategoryStore = defineStore('expenseCategory', {
    state: () => ({
        categories: [],
        loading: false,
        error: null,
    }),

    getters: {
        getCategoryById: (state) => (id) => {
            return state.categories.find(category => category.id === id);
        },
        
        deductibleCategories: (state) => {
            return state.categories.filter(category => category.is_deductible);
        },
        
        hasCategories: (state) => state.categories.length > 0,
    },

    actions: {
        async fetchCategories() {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.get('/api/v1/expense-categories');
                this.categories = response.data.data || response.data;
            } catch (error) {
                this.error = error.message;
                notify.error('Failed to fetch expense categories');
                console.error('Error fetching categories:', error);
            } finally {
                this.loading = false;
            }
        },

        async createCategory(categoryData) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.post('/api/v1/expense-categories', categoryData);
                this.categories.push(response.data.data || response.data);
                notify.success('Category created successfully');
                return response.data;
            } catch (error) {
                this.error = error.message;
                notify.error('Failed to create category');
                console.error('Error creating category:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async updateCategory(id, categoryData) {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await axios.put(`/api/v1/expense-categories/${id}`, categoryData);
                const index = this.categories.findIndex(cat => cat.id === id);
                
                if (index !== -1) {
                    this.categories[index] = response.data.data || response.data;
                }
                
                notify.success('Category updated successfully');
                return response.data;
            } catch (error) {
                this.error = error.message;
                notify.error('Failed to update category');
                console.error('Error updating category:', error);
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async deleteCategory(id) {
            this.loading = true;
            this.error = null;
            
            try {
                await axios.delete(`/api/v1/expense-categories/${id}`);
                this.categories = this.categories.filter(cat => cat.id !== id);
                notify.success('Category deleted successfully');
            } catch (error) {
                this.error = error.message;
                notify.error('Failed to delete category');
                console.error('Error deleting category:', error);
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