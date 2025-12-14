import { defineStore } from 'pinia';
import axios from 'axios';
// import notify from '@/plugins/notify';

export const useTemplatesStore = defineStore('templates', {
  state: () => ({
    templates: [],
    originalTemplates: [],
    users: [],
    branches: [],
    loading: false,
    saving: false,
    deleting: false,
    searchQuery: '',
    filterChannel: null,
    filterModule: null,
    editedItem: {
      id: null,
      name: '',
      channel: '',
      module: '',
      content: '',
      placeholders: '',
      owner_type: 'system',
      owner_id: null,
    },
    previewItem: {
      id: null,
      name: '',
      channel: '',
      module: '',
      content: '',
      placeholders: '',
    },
    placeholderValues: {},
  }),

  getters: {
    filteredTemplates: (state) => {
      let result = [...state.templates];
      
      if (state.filterChannel && state.filterChannel !== 'All') {
        result = result.filter(template => 
          template.channel === state.filterChannel
        );
      }
      
      if (state.filterModule && state.filterModule !== 'All') {
        result = result.filter(template => 
          template.module === state.filterModule
        );
      }
      
      return result;
    },

    processedPreviewContent: (state) => {
      let content = state.previewItem.content;
      
      if (state.previewItem.placeholders) {
        const placeholders = extractPlaceholdersHelper(state.previewItem.placeholders);
        
        placeholders.forEach(placeholder => {
          const value = state.placeholderValues[placeholder] || `[${placeholder}]`;
          content = content.replace(new RegExp(`{${placeholder}}`, 'g'), value);
        });
      }
      
      return content;
    },

    defaultItem: () => ({
      id: null,
      name: '',
      channel: '',
      module: '',
      content: '',
      placeholders: '',
      owner_type: 'system',
      owner_id: null,
    }),
  },

  actions: {
    // Fetch all templates
    async fetchTemplates() {
      this.loading = true;
      try {
        const response = await axios.get('/api/v1/templates');
        this.templates = response.data.data;
        this.originalTemplates = [...this.templates];
        return { success: true };
      } catch (error) {
        console.error('API Error:', error);
        return { success: false, error };
      } finally {
        this.loading = false;
      }
    },

    // Search templates
    async searchTemplates(query) {
      this.searchQuery = query;
      
      if (query.trim() === '') {
        this.templates = [...this.originalTemplates];
        return { success: true };
      }

      this.loading = true;
      try {
        const response = await axios.get('/api/v1/templates', { 
          params: { search: query } 
        });
        this.templates = response.data;
        return { success: true };
      } catch (error) {
        console.error('Search error:', error);
        return { success: false, error };
      } finally {
        this.loading = false;
      }
    },

    // Create new template
    async createTemplate(templateData) {
      this.saving = true;
      try {
        const payload = {
          ...templateData,
          placeholders: typeof templateData.placeholders === 'string' 
            ? templateData.placeholders 
            : JSON.stringify(templateData.placeholders)
        };
        
        await axios.post('/api/v1/templates', payload);
        await this.fetchTemplates();
        return { success: true };
      } catch (error) {
        console.error('Create error:', error);
        return { success: false, error };
      } finally {
        this.saving = false;
      }
    },

    // Update existing template
    async updateTemplate(id, templateData) {
      this.saving = true;
      try {
        const payload = {
          ...templateData,
          placeholders: typeof templateData.placeholders === 'string' 
            ? templateData.placeholders 
            : JSON.stringify(templateData.placeholders)
        };
        
        await axios.put(`/api/v1/templates/${id}`, payload);
        await this.fetchTemplates();
        return { success: true };
      } catch (error) {
        console.error('Update error:', error);
        return { success: false, error };
      } finally {
        this.saving = false;
      }
    },

    // Delete template
    async deleteTemplate(id) {
      this.deleting = true;
      try {
        await axios.delete(`/api/v1/templates/${id}`);
        this.templates = this.templates.filter(t => t.id !== id);
        this.originalTemplates = this.originalTemplates.filter(t => t.id !== id);
        return { success: true };
      } catch (error) {
        console.error('Delete error:', error);
        return { success: false, error };
      } finally {
        this.deleting = false;
      }
    },

    // Fetch users for owner selection
    async fetchUsers() {
      try {
        const response = await axios.get('/api/v1/admin/users');
        this.users = response.data;
        return { success: true };
      } catch (error) {
        console.error('API Error:', error);
        return { success: false, error };
      }
    },

    // Fetch branches for owner selection
    async fetchBranches() {
      try {
        const response = await axios.get('/api/v1/branches');
        this.branches = response.data;
        return { success: true };
      } catch (error) {
        console.error('API Error:', error);
        return { success: false, error };
      }
    },

    // Set edited item
    setEditedItem(item) {
      this.editedItem = { ...item };
    },

    // Reset edited item
    resetEditedItem() {
      this.editedItem = { ...this.defaultItem };
    },

    // Set preview item
    setPreviewItem(item) {
      this.previewItem = { ...item };
      this.placeholderValues = {};
      
      if (this.previewItem.placeholders) {
        const placeholders = extractPlaceholdersHelper(this.previewItem.placeholders);
        placeholders.forEach(placeholder => {
          this.placeholderValues[placeholder] = '';
        });
      }
    },

    // Update placeholder value
    updatePlaceholderValue(placeholder, value) {
      this.placeholderValues[placeholder] = value;
    },

    // Set filters
    setChannelFilter(channel) {
      this.filterChannel = channel;
    },

    setModuleFilter(module) {
      this.filterModule = module;
    },

    // Clone template
    cloneTemplate(item) {
      const clonedItem = { ...item };
      delete clonedItem.id;
      clonedItem.name = `${clonedItem.name} (Copy)`;
      return clonedItem;
    },

    // Utility: Extract placeholders
    extractPlaceholders(placeholders) {
      return extractPlaceholdersHelper(placeholders);
    },

    // Utility: Get channel color
    getChannelColor(channel) {
      const colors = {
        email: 'primary',
        sms: 'success',
        push: 'warning',
        'in-app': 'info',
      };
      return colors[channel?.toLowerCase()] || 'grey';
    },
  },
});

// Helper function outside store
function extractPlaceholdersHelper(placeholders) {
  if (typeof placeholders === 'string') {
    try {
      return JSON.parse(placeholders);
    } catch (e) {
      return [];
    }
  } else if (Array.isArray(placeholders)) {
    return placeholders;
  }
  return [];
}