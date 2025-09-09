\<template>
  <AppLayout>
    <v-container>
      <!-- Header with Create Button -->
      <v-row class="mb-4 align-center">
        <v-col>
          <h2 class="text-h4">Permissions Management</h2>
        </v-col>
        <v-col cols="auto">
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="openCreateDialog"
            :loading="loading"
          >
            Create Permission
          </v-btn>
        </v-col>
      </v-row>

      <!-- Search and Filters -->
      <v-card class="mb-4" variant="outlined">
        <v-card-title class="text-subtitle-1 pb-2">
          <v-icon left>mdi-filter</v-icon>
          Search & Filter
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="filters.search"
                label="Search permissions..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="compact"
                clearable
                hint="Search by permission name"
                persistent-hint
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.status"
                label="Status"
                :items="statusOptions"
                variant="outlined"
                density="compact"
                clearable
              ></v-select>
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.guard"
                label="Guard"
                :items="guardOptions"
                variant="outlined"
                density="compact"
                clearable
              ></v-select>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Permissions Data Table -->
      <v-card>
        <v-card-title class="text-subtitle-1">
          <v-icon left>mdi-key</v-icon>
          All Permissions ({{ filteredPermissions.length }})
        </v-card-title>
        <v-divider></v-divider>
        
        <v-data-table
          :headers="headers"
          :items="filteredPermissions"
          :loading="loading"
          item-value="id"
          class="elevation-0"
          :items-per-page="25"
        >
          <!-- Permission Name Column -->
          <template #item.name="{ item }">
            <div class="d-flex align-center">
              <v-icon 
                :icon="item.icon || 'mdi-key'" 
                :color="item.color || 'primary'"
                class="mr-2"
                size="small"
              ></v-icon>
              <div>
                <div class="font-weight-medium">{{ formatPermission(item.name) }}</div>
                <div class="text-caption text-medium-emphasis">{{ item.name }}</div>
              </div>
            </div>
          </template>

          <!-- Description Column -->
          <template #item.description="{ item }">
            <span class="text-caption">
              {{ item.description || 'No description available' }}
            </span>
          </template>

          <!-- Status Column -->
          <template #item.active="{ item }">
            <v-chip
              :color="item.active ? 'success' : 'error'"
              size="small"
              variant="flat"
            >
              {{ item.active ? 'Active' : 'Inactive' }}
            </v-chip>
          </template>

          <!-- Guard Column -->
          <template #item.guard_name="{ item }">
            <v-chip
              color="info"
              size="small"
              variant="outlined"
            >
              {{ item.guard_name }}
            </v-chip>
          </template>

          <!-- Actions Column -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                color="primary"
                @click="openEditDialog(item)"
                :loading="updatingPermission === item.id"
              ></v-btn>
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                @click="openDeleteDialog(item)"
                :loading="deletingPermission === item.id"
              ></v-btn>
            </div>
          </template>

          <!-- Loading State -->
          <template #loading>
            <v-skeleton-loader type="table-row@10"></v-skeleton-loader>
          </template>

          <!-- No Data State -->
          <template #no-data>
            <div class="text-center py-8">
              <v-icon size="64" color="grey-lighten-1">mdi-key-off</v-icon>
              <div class="text-h6 mt-4">No permissions found</div>
              <div class="text-caption">Create your first permission to get started</div>
            </div>
          </template>
        </v-data-table>
      </v-card>

      <!-- Create/Edit Permission Dialog -->
      <v-dialog v-model="permissionDialog" max-width="600px" persistent>
        <v-card>
          <v-card-title>
            <span class="text-h5">
              {{ isEditing ? 'Edit Permission' : 'Create New Permission' }}
            </span>
          </v-card-title>

          <v-form ref="permissionForm" v-model="permissionFormValid" @submit.prevent="savePermission">
            <v-card-text>
              <v-container>
                <v-row>
                  <v-col cols="12">
                    <v-text-field
                      v-model="permissionFormData.name"
                      label="Permission Name *"
                      :rules="[rules.required, rules.minLength]"
                      variant="outlined"
                      density="comfortable"
                      placeholder="e.g., manage_users, create_posts"
                      hint="Use snake_case format for permission names"
                      persistent-hint
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12">
                    <v-textarea
                      v-model="permissionFormData.description"
                      label="Description"
                      variant="outlined"
                      density="comfortable"
                      rows="3"
                      placeholder="Describe what this permission allows users to do"
                    ></v-textarea>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-select
                      v-model="permissionFormData.guard_name"
                      label="Guard Name *"
                      :items="guardOptions"
                      :rules="[rules.required]"
                      variant="outlined"
                      density="comfortable"
                    ></v-select>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-select
                      v-model="permissionFormData.icon"
                      label="Icon"
                      :items="iconOptions"
                      variant="outlined"
                      density="comfortable"
                    >
                      <template #selection="{ item }">
                        <v-icon :icon="item.value" class="mr-2"></v-icon>
                        {{ item.title }}
                      </template>
                      <template #item="{ props, item }">
                        <v-list-item v-bind="props">
                          <template #prepend>
                            <v-icon :icon="item.value"></v-icon>
                          </template>
                        </v-list-item>
                      </template>
                    </v-select>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-select
                      v-model="permissionFormData.color"
                      label="Color"
                      :items="colorOptions"
                      variant="outlined"
                      density="comfortable"
                    >
                      <template #selection="{ item }">
                        <v-chip :color="item.value" size="small" class="mr-2"></v-chip>
                        {{ item.title }}
                      </template>
                    </v-select>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-switch
                      v-model="permissionFormData.active"
                      label="Active"
                      color="success"
                      inset
                    ></v-switch>
                  </v-col>
                </v-row>
              </v-container>
            </v-card-text>

            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn
                variant="text"
                @click="closePermissionDialog"
                :disabled="formLoading"
              >
                Cancel
              </v-btn>
              <v-btn
                color="primary"
                variant="flat"
                type="submit"
                :loading="formLoading"
                :disabled="!permissionFormValid"
              >
                {{ isEditing ? 'Update' : 'Create' }}
              </v-btn>
            </v-card-actions>
          </v-form>
        </v-card>
      </v-dialog>

      <!-- Delete Confirmation Dialog -->
      <v-dialog v-model="deleteDialog" max-width="400px">
        <v-card>
          <v-card-title class="text-h5">
            Confirm Delete
          </v-card-title>
          <v-card-text>
            Are you sure you want to delete the permission 
            <strong>"{{ permissionToDelete?.name }}"</strong>?
            <br><br>
            <v-alert color="warning" variant="outlined" class="mt-3">
              This action cannot be undone and may affect users with this permission.
            </v-alert>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn
              variant="text"
              @click="closeDeleteDialog"
              :disabled="deletingPermission"
            >
              Cancel
            </v-btn>
            <v-btn
              color="error"
              variant="flat"
              @click="deletePermission"
              :loading="deletingPermission"
            >
              Delete
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"
import { notify } from '@/utils/toast'
import axios from 'axios'

axios.defaults.withCredentials = true
axios.defaults.headers.common['Accept'] = 'application/json'

// Data
const permissions = ref([])
const loading = ref(false)
const formLoading = ref(false)
const updatingPermission = ref(null)
const deletingPermission = ref(null)

// Dialogs
const permissionDialog = ref(false)
const deleteDialog = ref(false)

// Form data
const permissionFormData = ref({
  name: '',
  description: '',
  guard_name: 'sanctum',
  icon: 'mdi-key',
  color: 'primary',
  active: true
})
const permissionFormValid = ref(false)
const editingPermissionId = ref(null)
const permissionToDelete = ref(null)

// Filters
const filters = ref({
  search: '',
  status: '',
  guard: ''
})

// Form reference
const permissionForm = ref(null)

// Table headers
const headers = [
  { title: 'Permission', value: 'name', sortable: true },
  { title: 'Description', value: 'description', sortable: false },
  { title: 'Status', value: 'active', sortable: true },
  { title: 'Guard', value: 'guard_name', sortable: true },
  { title: 'Actions', value: 'actions', sortable: false, width: '120px' }
]

// Form validation rules
const rules = {
  required: value => !!value || 'This field is required',
  minLength: value => (value && value.length >= 2) || 'Minimum 2 characters required'
}

// Options
const statusOptions = [
  { title: 'Active', value: true },
  { title: 'Inactive', value: false }
]

const guardOptions = [
  { title: 'Sanctum', value: 'sanctum' },
  { title: 'Web', value: 'web' },
  { title: 'API', value: 'api' }
]

const iconOptions = [
  { title: 'Key', value: 'mdi-key' },
  { title: 'Shield', value: 'mdi-shield' },
  { title: 'Account', value: 'mdi-account' },
  { title: 'Cog', value: 'mdi-cog' },
  { title: 'Database', value: 'mdi-database' },
  { title: 'Eye', value: 'mdi-eye' },
  { title: 'Pencil', value: 'mdi-pencil' },
  { title: 'Delete', value: 'mdi-delete' },
  { title: 'Plus', value: 'mdi-plus' },
  { title: 'Chart', value: 'mdi-chart-line' }
]

const colorOptions = [
  { title: 'Primary', value: 'primary' },
  { title: 'Success', value: 'success' },
  { title: 'Info', value: 'info' },
  { title: 'Warning', value: 'warning' },
  { title: 'Error', value: 'error' },
  { title: 'Green', value: 'green' },
  { title: 'Blue', value: 'blue' },
  { title: 'Purple', value: 'purple' },
  { title: 'Orange', value: 'orange' }
]

// Computed
const isEditing = computed(() => editingPermissionId.value !== null)

const filteredPermissions = computed(() => {
  let filtered = permissions.value

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(permission =>
      (permission.name && permission.name.toLowerCase().includes(search)) ||
      (permission.description && permission.description.toLowerCase().includes(search))
    )
  }

  if (filters.value.status !== '') {
    filtered = filtered.filter(permission => permission.active === filters.value.status)
  }

  if (filters.value.guard) {
    filtered = filtered.filter(permission => permission.guard_name === filters.value.guard)
  }

  return filtered
})

// Methods
async function fetchPermissions() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/v1/admin/permissions')
    permissions.value = data
  } catch (error) {
    notify.error('Failed to load permissions')
    console.error('Error fetching permissions:', error)
  } finally {
    loading.value = false
  }
}

async function createPermission() {
  formLoading.value = true
  try {
    const { data } = await axios.post('/api/v1/admin/permissions', permissionFormData.value)
    permissions.value.push(data)
    notify.success('Permission created successfully')
    closePermissionDialog()
  } catch (error) {
    notify.error('Failed to create permission')
    console.error('Error creating permission:', error)
  } finally {
    formLoading.value = false
  }
}

async function updatePermission() {
  formLoading.value = true
  updatingPermission.value = editingPermissionId.value
  try {
    const { data } = await axios.put(
      `/api/v1/admin/permissions/${editingPermissionId.value}`,
      permissionFormData.value
    )
    const index = permissions.value.findIndex(p => p.id === editingPermissionId.value)
    if (index !== -1) {
      permissions.value[index] = data
    }
    notify.success('Permission updated successfully')
    closePermissionDialog()
  } catch (error) {
    notify.error('Failed to update permission')
    console.error('Error updating permission:', error)
  } finally {
    formLoading.value = false
    updatingPermission.value = null
  }
}

async function deletePermission() {
  deletingPermission.value = permissionToDelete.value.id
  try {
    await axios.delete(`/api/v1/admin/permissions/${permissionToDelete.value.id}`)
    permissions.value = permissions.value.filter(p => p.id !== permissionToDelete.value.id)
    notify.success('Permission deleted successfully')
    closeDeleteDialog()
  } catch (error) {
    notify.error('Failed to delete permission')
    console.error('Error deleting permission:', error)
  } finally {
    deletingPermission.value = null
  }
}

function savePermission() {
  if (!permissionForm.value.validate()) return
  if (isEditing.value) {
    updatePermission()
  } else {
    createPermission()
  }
}

function openCreateDialog() {
  resetForm()
  editingPermissionId.value = null
  permissionDialog.value = true
}

function openEditDialog(permission) {
  resetForm()
  permissionFormData.value = {
    name: permission.name,
    description: permission.description || '',
    guard_name: permission.guard_name,
    icon: permission.icon || 'mdi-key',
    color: permission.color || 'primary',
    active: permission.active
  }
  editingPermissionId.value = permission.id
  permissionDialog.value = true
}

function openDeleteDialog(permission) {
  permissionToDelete.value = permission
  deleteDialog.value = true
}

function closePermissionDialog() {
  permissionDialog.value = false
  resetForm()
  editingPermissionId.value = null
}

function closeDeleteDialog() {
  deleteDialog.value = false
  permissionToDelete.value = null
}

function resetForm() {
  permissionFormData.value = {
    name: '',
    description: '',
    guard_name: 'sanctum',
    icon: 'mdi-key',
    color: 'primary',
    active: true
  }
  if (permissionForm.value) {
    permissionForm.value.resetValidation()
  }
}

function formatPermission(permission) {
  return permission.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Lifecycle
onMounted(() => {
  fetchPermissions()
})
</script>

<style scoped>
.v-data-table {
  background-color: transparent;
}
</style>