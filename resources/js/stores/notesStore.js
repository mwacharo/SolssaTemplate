// stores/notesStore.js
import { defineStore } from 'pinia'

export const useNotesStore = defineStore('notes', {
  state: () => ({
    notes: [],
    categories: [],
    search: '',
    selectedCategories: [],
    notesStatus: 'Synced',
    stats: {
      total: 0,
      pinned: 0,
      shared: 0,
      archived: 0
    },
    colorOptions: [
      { color: 'white', name: 'Default' },
      { color: 'blue lighten-4', name: 'Blue' },
      { color: 'green lighten-4', name: 'Green' },
      { color: 'yellow lighten-4', name: 'Yellow' },
      { color: 'red lighten-4', name: 'Red' },
      { color: 'purple lighten-4', name: 'Purple' }
    ],
    sortBy: 'updated_at',
    sortDirection: 'desc'
  }),

  getters: {
    filteredNotes: (state) => {
      let filtered = [...state.notes]
      
      // Filter by search
      if (state.search) {
        const searchLower = state.search.toLowerCase()
        filtered = filtered.filter(note => 
          note.title.toLowerCase().includes(searchLower) || 
          note.content.toLowerCase().includes(searchLower)
        )
      }
      
      // Filter by selected categories
      if (state.selectedCategories.length > 0) {
        filtered = filtered.filter(note => 
          state.selectedCategories.includes(note.category_id)
        )
      }
      
      // Sort notes
      filtered.sort((a, b) => {
        // Always show pinned notes first
        if (a.is_pinned && !b.is_pinned) return -1
        if (!a.is_pinned && b.is_pinned) return 1
        
        // Then sort by the selected field
        if (state.sortDirection === 'desc') {
          return b[state.sortBy].localeCompare(a[state.sortBy])
        } else {
          return a[state.sortBy].localeCompare(b[state.sortBy])
        }
      })
      
      return filtered
    }
  },

  actions: {
    // Initialize with mock data
    initializeData() {
      this.notes = [
        { 
          id: 1, 
          title: 'Meeting Notes - Q2 Planning', 
          content: 'Discussed quarterly goals and assigned tasks to team members. Follow up needed with marketing team about new campaign ideas.',
          category_id: 1, 
          is_pinned: true, 
          color: 'yellow lighten-4',
          created_at: '2025-04-15',
          updated_at: '2025-04-15'
        },
        { 
          id: 2, 
          title: 'Shopping List', 
          content: '- Milk\n- Eggs\n- Bread\n- Vegetables\n- Chicken',
          category_id: 2, 
          is_pinned: false, 
          color: 'white',
          created_at: '2025-04-14',
          updated_at: '2025-04-14'
        },
        { 
          id: 3, 
          title: 'Product Feature Ideas', 
          content: '1. Add dark mode\n2. Implement notification system\n3. Create mobile app version\n4. Add collaboration features',
          category_id: 3, 
          is_pinned: true, 
          color: 'blue lighten-4',
          created_at: '2025-04-10',
          updated_at: '2025-04-17'
        },
        { 
          id: 4, 
          title: 'Website Redesign Tasks', 
          content: '- Update color scheme\n- Improve navigation\n- Optimize for mobile\n- Add animation effects\n- Update content',
          category_id: 4, 
          is_pinned: false, 
          color: 'green lighten-4',
          created_at: '2025-04-08',
          updated_at: '2025-04-12'
        },
        { 
          id: 5, 
          title: 'Project Timeline', 
          content: 'Phase 1: Research (April 1-15)\nPhase 2: Design (April 16-30)\nPhase 3: Development (May 1-20)\nPhase 4: Testing (May 21-31)\nPhase 5: Launch (June 1)',
          category_id: 4, 
          is_pinned: true, 
          color: 'purple lighten-4',
          created_at: '2025-04-05',
          updated_at: '2025-04-19'
        }
      ]

      this.categories = [
        { id: 1, name: 'Work', color: '#4CAF50' },
        { id: 2, name: 'Personal', color: '#2196F3' },
        { id: 3, name: 'Ideas', color: '#FF9800' },
        { id: 4, name: 'Projects', color: '#9C27B0' }
      ]

      this.updateStats()
    },

    // Update statistics
    updateStats() {
      this.stats.total = this.notes.length
      this.stats.pinned = this.notes.filter(note => note.is_pinned).length
    },

    // Add new note
    addNote(noteData) {
      const now = new Date().toISOString().slice(0, 10)
      const newNote = {
        id: this.notes.length + 1,
        ...noteData,
        created_at: now,
        updated_at: now
      }
      
      this.notes.unshift(newNote)
      this.updateStats()
    },

    // Update existing note
    updateNote(noteData) {
      const index = this.notes.findIndex(n => n.id === noteData.id)
      if (index !== -1) {
        this.notes[index] = {
          ...noteData,
          updated_at: new Date().toISOString().slice(0, 10)
        }
      }
    },

    // Delete note
    deleteNote(noteId) {
      this.notes = this.notes.filter(note => note.id !== noteId)
      this.updateStats()
    },

    // Toggle pin status
    togglePin(noteId) {
      const index = this.notes.findIndex(note => note.id === noteId)
      if (index !== -1) {
        this.notes[index].is_pinned = !this.notes[index].is_pinned
        this.updateStats()
      }
    },

    // Add new category
    addCategory(categoryData) {
      const newCategory = {
        id: this.categories.length + 1,
        ...categoryData
      }
      this.categories.push(newCategory)
    },

    // Get category by ID
    getCategoryName(categoryId) {
      const category = this.categories.find(c => c.id === categoryId)
      return category ? category.name : 'Uncategorized'
    },

    getCategoryColor(categoryId) {
      const category = this.categories.find(c => c.id === categoryId)
      return category ? category.color : '#E0E0E0'
    },

    // Set search term
    setSearch(searchTerm) {
      this.search = searchTerm
    },

    // Toggle category filter
    toggleCategoryFilter(categoryId) {
      const index = this.selectedCategories.indexOf(categoryId)
      if (index > -1) {
        this.selectedCategories.splice(index, 1)
      } else {
        this.selectedCategories.push(categoryId)
      }
    }
  }
})