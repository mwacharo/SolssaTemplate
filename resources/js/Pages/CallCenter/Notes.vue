<template>
  <AppLayout title="Notes">
    <v-container>
      <v-row>
        <v-col cols="12" md="3">
          <v-card>
            <v-card-text class="text-center">
              <v-avatar size="80" color="amber" class="mb-3">
                <v-icon size="48" color="white">mdi-note-text</v-icon>
              </v-avatar>
              <h2 class="text-h6">Notes Manager</h2>
              <v-chip
                :color="notesStore.notesStatus === 'Synced' ? 'success' : 'warning'"
                class="mt-2"
              >
                {{ notesStore.notesStatus }}
              </v-chip>
              
              <v-divider class="my-4"></v-divider>
              
              <v-row>
                <v-col cols="6" class="py-1">
                  <div class="text-subtitle-2">Total</div>
                  <div class="text-h6">{{ notesStore.stats.total }}</div>
                </v-col>
                <v-col cols="6" class="py-1">
                  <div class="text-subtitle-2">Pinned</div>
                  <div class="text-h6">{{ notesStore.stats.pinned }}</div>
                </v-col>
                <v-col cols="6" class="py-1">
                  <div class="text-subtitle-2">Shared</div>
                  <div class="text-h6">{{ notesStore.stats.shared }}</div>
                </v-col>
                <v-col cols="6" class="py-1">
                  <div class="text-subtitle-2">Archived</div>
                  <div class="text-h6">{{ notesStore.stats.archived }}</div>
                </v-col>
              </v-row>
              
              <v-divider class="my-4"></v-divider>
              
              <v-btn color="primary" block @click="openNewNoteDialog">
                New Note
              </v-btn>
              <v-btn color="secondary" block class="mt-2" @click="showCategoryDialog = true">
                Add Category
              </v-btn>
              <v-btn color="info" block class="mt-2" @click="showExportDialog = true">
                Export Notes
              </v-btn>
              
              <v-divider class="my-4"></v-divider>
              
              <div class="text-subtitle-1 font-weight-bold text-left mb-2">Categories</div>
              <v-checkbox
                v-for="category in notesStore.categories"
                :key="category.id"
                :model-value="notesStore.selectedCategories.includes(category.id)"
                @update:model-value="notesStore.toggleCategoryFilter(category.id)"
                :label="category.name"
                density="compact"
                hide-details
                class="mt-1"
              >
                <template v-slot:prepend>
                  <v-avatar size="16" :style="{ backgroundColor: category.color }"></v-avatar>
                </template>
              </v-checkbox>
            </v-card-text>
          </v-card>
        </v-col>
        
        <v-col cols="12" md="9">
          <v-card>
            <v-card-title class="d-flex justify-space-between align-center">
              <div>My Notes</div>
              <v-text-field
                :model-value="notesStore.search"
                @update:model-value="notesStore.setSearch"
                append-icon="mdi-magnify"
                label="Search notes"
                single-line
                hide-details
                density="compact"
                class="max-w-xs"
              ></v-text-field>
            </v-card-title>
            
            <v-card-text>
              <v-row>
                <v-col cols="12" sm="6" md="4" v-for="note in notesStore.filteredNotes" :key="note.id">
                  <v-card :color="note.color" class="note-card h-100">
                    <v-card-text>
                      <div class="d-flex justify-space-between align-center mb-2">
                        <h3 class="text-subtitle-1 font-weight-bold text-truncate" style="max-width: 80%;">
                          {{ note.title }}
                        </h3>
                        <v-btn 
                          icon 
                          size="x-small" 
                          @click="togglePin(note.id)" 
                          :color="note.is_pinned ? 'amber' : ''"
                        >
                          <v-icon>{{ note.is_pinned ? 'mdi-pin' : 'mdi-pin-outline' }}</v-icon>
                        </v-btn>
                      </div>
                      
                      <div class="note-content mb-3" style="max-height: 120px; overflow: hidden;">
                        {{ note.content }}
                      </div>
                      
                      <div class="d-flex justify-space-between align-center">
                        <v-chip 
                          size="x-small" 
                          :style="{ backgroundColor: notesStore.getCategoryColor(note.category_id) }"
                        >
                          {{ notesStore.getCategoryName(note.category_id) }}
                        </v-chip>
                        <div class="text-caption">{{ note.updated_at }}</div>
                      </div>
                    </v-card-text>
                    
                    <v-divider></v-divider>
                    
                    <v-card-actions>
                      <v-spacer></v-spacer>
                      <v-btn icon size="small" color="info" @click="editNote(note)">
                        <v-icon>mdi-pencil</v-icon>
                      </v-btn>
                      <v-btn icon size="small" color="primary">
                        <v-icon>mdi-share-variant</v-icon>
                      </v-btn>
                      <v-btn icon size="small" color="error" @click="deleteNote(note.id)">
                        <v-icon>mdi-delete</v-icon>
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-col>
              </v-row>
              
              <div v-if="notesStore.filteredNotes.length === 0" class="text-center py-8">
                <v-icon size="64" color="grey">mdi-note-off-outline</v-icon>
                <div class="text-h6 mt-2">No notes found</div>
                <div class="text-body-2">Create a new note or adjust your search filters</div>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
    
    <!-- New/Edit Note Dialog -->
    <v-dialog v-model="showNewNoteDialog" max-width="600px">
      <v-card>
        <v-card-title>{{ isEditing ? 'Edit Note' : 'Create Note' }}</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="currentNote.title"
            label="Title"
            required
          ></v-text-field>
          
          <v-textarea
            v-model="currentNote.content"
            label="Content"
            rows="10"
            class="mt-4"
          ></v-textarea>
          
          <v-select
            v-model="currentNote.category_id"
            :items="notesStore.categories"
            item-title="name"
            item-value="id"
            label="Category"
            class="mt-4"
          >
            <template v-slot:selection="{ item }">
              <v-avatar size="16" class="mr-2" :style="{ backgroundColor: item.raw.color }"></v-avatar>
              {{ item.raw.name }}
            </template>
            
            <template v-slot:item="{ item, props }">
              <v-list-item v-bind="props">
                <template v-slot:prepend>
                  <v-avatar size="16" :style="{ backgroundColor: item.raw.color }"></v-avatar>
                </template>
                <v-list-item-title>{{ item.raw.name }}</v-list-item-title>
              </v-list-item>
            </template>
          </v-select>
          
          <v-radio-group 
            v-model="currentNote.color" 
            label="Note Color" 
            class="mt-4"
            inline
          >
            <v-radio
              v-for="option in notesStore.colorOptions"
              :key="option.color"
              :value="option.color"
              :label="option.name"
            >
              <template v-slot:prepend>
                <v-avatar size="16" :class="option.color"></v-avatar>
              </template>
            </v-radio>
          </v-radio-group>
          
          <v-switch
            v-model="currentNote.is_pinned"
            label="Pin Note"
            color="amber"
            class="mt-2"
          ></v-switch>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" @click="showNewNoteDialog = false">Cancel</v-btn>
          <v-btn 
            color="primary" 
            @click="saveNote"
            :disabled="!currentNote.title"
          >
            {{ isEditing ? 'Update' : 'Save' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Add Category Dialog -->
    <v-dialog v-model="showCategoryDialog" max-width="500px">
      <v-card>
        <v-card-title>Add Category</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="newCategory.name"
            label="Category Name"
            required
          ></v-text-field>
          
          <v-color-picker
            v-model="newCategory.color"
            flat
            hide-inputs
            class="mt-4"
          ></v-color-picker>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" @click="showCategoryDialog = false">Cancel</v-btn>
          <v-btn 
            color="primary" 
            @click="saveCategory"
            :disabled="!newCategory.name"
          >
            Add Category
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Export Notes Dialog -->
    <v-dialog v-model="showExportDialog" max-width="500px">
      <v-card>
        <v-card-title>Export Notes</v-card-title>
        <v-card-text>
          <v-radio-group v-model="exportFormat" class="mt-2">
            <v-radio label="Plain Text (.txt)" value="txt"></v-radio>
            <v-radio label="Markdown (.md)" value="md"></v-radio>
            <v-radio label="PDF Document (.pdf)" value="pdf"></v-radio>
            <v-radio label="HTML Document (.html)" value="html"></v-radio>
          </v-radio-group>
          
          <v-checkbox label="Include categories" class="mt-4"></v-checkbox>
          <v-checkbox label="Include creation date" class="mt-0"></v-checkbox>
          <v-checkbox label="Include pinned status" class="mt-0"></v-checkbox>
          
          <v-alert type="info" class="mt-4">
            Only selected notes will be exported. If no notes are selected, all notes will be exported.
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="error" @click="showExportDialog = false">Cancel</v-btn>
          <v-btn color="primary">Export</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useNotesStore } from '@/stores/notesStore'
import { notify } from '@/utils/toast'

// Pinia store
const notesStore = useNotesStore()

// Local reactive state for dialogs and forms
const showNewNoteDialog = ref(false)
const showCategoryDialog = ref(false)
const showExportDialog = ref(false)
const isEditing = ref(false)
const exportFormat = ref('txt')

const currentNote = ref({
  id: null,
  title: '',
  content: '',
  category_id: null,
  is_pinned: false,
  color: 'white'
})

const newCategory = ref({
  name: '',
  color: '#4CAF50'
})

// Methods
const openNewNoteDialog = () => {
  currentNote.value = {
    id: null,
    title: '',
    content: '',
    category_id: null,
    is_pinned: false,
    color: 'white'
  }
  isEditing.value = false
  showNewNoteDialog.value = true
}

const editNote = (note) => {
  currentNote.value = { ...note }
  isEditing.value = true
  showNewNoteDialog.value = true
}

const saveNote = () => {
  try {
    if (isEditing.value) {
      notesStore.updateNote(currentNote.value)
      notify('Note updated successfully!', 'success')
    } else {
      notesStore.addNote(currentNote.value)
      notify('Note created successfully!', 'success')
    }
    
    // Reset form
    currentNote.value = {
      id: null,
      title: '',
      content: '',
      category_id: null,
      is_pinned: false,
      color: 'white'
    }
    isEditing.value = false
    showNewNoteDialog.value = false
  } catch (error) {
    notify('Failed to save note', 'error')
  }
}

const deleteNote = (noteId) => {
  if (confirm('Are you sure you want to delete this note?')) {
    try {
      notesStore.deleteNote(noteId)
      notify('Note deleted successfully!', 'success')
    } catch (error) {
      notify('Failed to delete note', 'error')
    }
  }
}

const togglePin = (noteId) => {
  try {
    notesStore.togglePin(noteId)
    notify('Note pin status updated!', 'info')
  } catch (error) {
    notify('Failed to update pin status', 'error')
  }
}

const saveCategory = () => {
  try {
    notesStore.addCategory(newCategory.value)
    
    // Reset form
    newCategory.value = {
      name: '',
      color: '#4CAF50'
    }
    
    showCategoryDialog.value = false
    notify('Category added successfully!', 'success')
  } catch (error) {
    notify('Failed to add category', 'error')
  }
}

// Initialize data on mount
onMounted(() => {
  notesStore.initializeData()
})
</script>

<style scoped>
.note-card {
  transition: transform 0.2s, box-shadow 0.2s;
}

.note-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 6px 12px rgba(0,0,0,0.15) !important;
}

.note-content {
  white-space: pre-line;
}
</style>