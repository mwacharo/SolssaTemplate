// stores/categoryStore.js
import { defineStore } from 'pinia'
import axios from 'axios'

export const useCategoryStore = defineStore('category', {
  state: () => ({
    categories: [],
    loading: false,
    error: null
  }),

  getters: {
    getCategoryById: (state) => (id) => {
      return state.categories.find(category => category.id === id)
    },
    
    categoriesCount: (state) => state.categories.length,
    
    sortedCategories: (state) => {
      return [...state.categories].sort((a, b) => a.name.localeCompare(b.name))
    }
  },

  actions: {
    async fetchCategories() {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get('/api/v1/categories')
        this.categories = response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch categories'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createCategory(categoryData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.post('/api/v1/categories', categoryData)
        this.categories.push(response.data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create category'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchCategory(id) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get(`/api/v1/categories/${id}`)
        
        // Update the category in the array if it exists, otherwise add it
        const index = this.categories.findIndex(cat => cat.id === id)
        if (index !== -1) {
          this.categories[index] = response.data
        } else {
          this.categories.push(response.data)
        }
        
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch category'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateCategory(id, categoryData) {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.put(`/api/v1/categories/${id}`, categoryData)

        // Update the category in the array
        const index = this.categories.findIndex(cat => cat.id === id)
        if (index !== -1) {
          this.categories[index] = response.data
        }
        
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update category'
        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteCategory(id) {
      this.loading = true
      this.error = null
      
      try {
        await axios.delete(`/api/v1/categories/${id}`)
        
        // Remove the category from the array
        this.categories = this.categories.filter(cat => cat.id !== id)
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete category'
        throw error
      } finally {
        this.loading = false
      }
    },

    // Utility actions
    clearError() {
      this.error = null
    },

    resetStore() {
      this.categories = []
      this.loading = false
      this.error = null
    },

    // Add a category to the store without API call (useful for real-time updates)
    addCategory(category) {
      this.categories.push(category)
    },

    // Remove a category from the store without API call
    removeCategory(id) {
      this.categories = this.categories.filter(cat => cat.id !== id)
    }
  }
})