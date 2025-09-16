<template>
  <AppLayout>
    <v-container>
      <!-- Header -->
      <v-row class="mb-4 align-center">
        <v-col>
          <h2 class="text-h4">Roles Management</h2>
        </v-col>
        <v-col class="text-right">
          <v-btn 
            color="primary" 
            @click="openCreateDialog"
            prepend-icon="mdi-plus"
          >
            Create Role
          </v-btn>
        </v-col>
      </v-row>

      <!-- Search -->
      <v-card class="mb-4" variant="outlined">
        <v-card-text>
          <v-text-field
            v-model="searchQuery"
            label="Search roles..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            clearable
          ></v-text-field>
        </v-card-text>
      </v-card>

      <!-- Roles Table -->
      <v-data-table
        :headers="headers"
        :items="filteredRoles"
        :loading="loading"
        item-key="id"
        class="elevation-1"
      >
        <template #loading>
          <v-skeleton-loader type="table-row@5"></v-skeleton-loader>
        </template>
        
        <template #item.permissions="{ item }">
          <v-chip-group>
            <v-chip
              v-for="perm in (item.permissions || []).slice(0, 2)"
              :key="perm"
              size="small"
              color="primary"
              variant="tonal"
            >
              {{ formatPermission(perm) }}
            </v-chip>
            <v-chip
              v-if="item.permissions && item.permissions.length > 2"
              size="small"
              variant="outlined"
              @click="openPermissionsDialog(item)"
              style="cursor: pointer"
            >
              +{{ item.permissions.length - 2 }} more
            </v-chip>
          </v-chip-group>
        </template>
        
        <template #item.actions="{ item }">
          <v-btn 
            icon="mdi-shield-edit" 
            size="small" 
            variant="text"
            @click="openPermissionsDialog(item)"
          ></v-btn>
          <v-btn 
            icon="mdi-pencil" 
            size="small" 
            variant="text"
            @click="openEditDialog(item)"
          ></v-btn>
          <v-btn 
            icon="mdi-delete" 
            size="small" 
            variant="text"
            color="error"
            @click="confirmDelete(item)"
          ></v-btn>
        </template>
      </v-data-table>
    </v-container>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="roleDialog" max-width="500px">
      <v-card>
        <v-card-title>
          {{ isEditing ? 'Edit Role' : 'Create Role' }}
        </v-card-title>
        
        <v-card-text>
          <v-text-field
            v-model="roleForm.name"
            label="Role Name"
            :rules="[rules.required]"
            variant="outlined"
          ></v-text-field>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="outlined" @click="closeRoleDialog">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="saving"
            @click="saveRole"
          >
            {{ isEditing ? 'Update' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Permissions Dialog -->
    <v-dialog v-model="permissionsDialog" max-width="600px">
      <v-card>
        <v-card-title>
          Manage Permissions - {{ selectedRole?.name }}
        </v-card-title>
        
        <v-card-text style="max-height: 400px; overflow-y: auto;">
          <v-text-field
            v-model="permissionSearch"
            label="Search permissions..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            density="compact"
            clearable
            class="mb-4"
          ></v-text-field>

          <v-list density="compact">
            <v-list-item
              v-for="permission in filteredPermissions"
              :key="permission.id"
            >
              <template #prepend>
                <v-checkbox
                  :model-value="selectedPermissions.includes(permission.name)"
                  @update:model-value="togglePermission(permission.name, $event)"
                  color="primary"
                ></v-checkbox>
              </template>
              
              <v-list-item-title class="d-flex align-center">
                <v-icon 
                  :icon="permission.icon" 
                  :color="permission.color" 
                  class="mr-3" 
                  size="small"
                ></v-icon>
                {{ formatPermission(permission.name) }}
              </v-list-item-title>
              
              <v-list-item-subtitle v-if="permission.description">
                {{ permission.description }}
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
        
        <v-card-actions>
          <v-btn variant="outlined" @click="selectAll">Select All</v-btn>
          <v-btn variant="outlined" @click="clearAll">Clear All</v-btn>
          <v-spacer></v-spacer>
          <v-btn variant="outlined" @click="closePermissionsDialog">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="saving"
            @click="updatePermissions"
          >
            Update
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card>
        <v-card-title>Confirm Delete</v-card-title>
        <v-card-text>
          Are you sure you want to delete "{{ roleToDelete?.name }}"?
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="outlined" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn
            color="error"
            :loading="deleting"
            @click="deleteRole"
          >
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"
import axios from 'axios'

// Data
const roles = ref([])
const permissions = ref([])
const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)

// Search
const searchQuery = ref('')
const permissionSearch = ref('')

// Dialogs
const roleDialog = ref(false)
const permissionsDialog = ref(false)
const deleteDialog = ref(false)

// Forms
const roleForm = ref({ name: '' })
const editingId = ref(null)
const selectedRole = ref(null)
const selectedPermissions = ref([])
const roleToDelete = ref(null)

// Table headers
const headers = [
  { title: 'Role Name', value: 'name' },
  { title: 'Permissions', value: 'permissions' },
  { title: 'Actions', value: 'actions', sortable: false, width: '150px' }
]

// Validation
const rules = {
  required: value => !!value || 'Required'
}

// Computed
const isEditing = computed(() => editingId.value !== null)

const filteredRoles = computed(() => {
  if (!searchQuery.value) return roles.value
  return roles.value.filter(role =>
    role.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

const filteredPermissions = computed(() => {
  if (!permissionSearch.value) return permissions.value
  return permissions.value.filter(permission =>
    permission.name.toLowerCase().includes(permissionSearch.value.toLowerCase()) ||
    permission.description.toLowerCase().includes(permissionSearch.value.toLowerCase())
  )
})

// API Functions
async function fetchRoles() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/v1/admin/roles')
    roles.value = data
  } catch (error) {
    console.error('Error fetching roles:', error)
  } finally {
    loading.value = false
  }
}

async function fetchPermissions() {
  try {
    const { data } = await axios.get('/api/v1/admin/permissions')
    permissions.value = data
  } catch (error) {
    console.error('Error fetching permissions:', error)
  }
}

async function saveRole() {
  if (!roleForm.value.name) return
  
  saving.value = true
  try {
    if (isEditing.value) {
      const { data } = await axios.put(`/api/v1/admin/roles/${editingId.value}`, {
        name: roleForm.value.name
      })
      const index = roles.value.findIndex(r => r.id === editingId.value)
      if (index !== -1) roles.value[index] = data
    } else {
      const { data } = await axios.post('/api/v1/admin/roles', {
        name: roleForm.value.name,
        permissions: []
      })
      roles.value.push(data)
    }
    closeRoleDialog()
  } catch (error) {
    console.error('Error saving role:', error)
  } finally {
    saving.value = false
  }
}

async function updatePermissions() {
  if (!selectedRole.value) return
  
  saving.value = true
  try {
    const { data } = await axios.put(`/api/v1/admin/roles/${selectedRole.value.id}`, {
      name: selectedRole.value.name,
      permissions: selectedPermissions.value
    })
    const index = roles.value.findIndex(r => r.id === selectedRole.value.id)
    if (index !== -1) roles.value[index] = data
    closePermissionsDialog()
  } catch (error) {
    console.error('Error updating permissions:', error)
  } finally {
    saving.value = false
  }
}

async function deleteRole() {
  if (!roleToDelete.value) return
  
  deleting.value = true
  try {
    await axios.delete(`/api/v1/admin/roles/${roleToDelete.value.id}`)
    roles.value = roles.value.filter(r => r.id !== roleToDelete.value.id)
    deleteDialog.value = false
    roleToDelete.value = null
  } catch (error) {
    console.error('Error deleting role:', error)
  } finally {
    deleting.value = false
  }
}

// Dialog Functions
function openCreateDialog() {
  roleForm.value = { name: '' }
  editingId.value = null
  roleDialog.value = true
}

function openEditDialog(role) {
  roleForm.value = { name: role.name }
  editingId.value = role.id
  roleDialog.value = true
}

function openPermissionsDialog(role) {
  selectedRole.value = role
  selectedPermissions.value = [...(role.permissions || [])]
  permissionsDialog.value = true
}

function confirmDelete(role) {
  roleToDelete.value = role
  deleteDialog.value = true
}

function closeRoleDialog() {
  roleDialog.value = false
  roleForm.value = { name: '' }
  editingId.value = null
}

function closePermissionsDialog() {
  permissionsDialog.value = false
  selectedRole.value = null
  selectedPermissions.value = []
  permissionSearch.value = ''
}

// Permission Functions
function togglePermission(permissionName, selected) {
  if (selected) {
    if (!selectedPermissions.value.includes(permissionName)) {
      selectedPermissions.value.push(permissionName)
    }
  } else {
    const index = selectedPermissions.value.indexOf(permissionName)
    if (index > -1) {
      selectedPermissions.value.splice(index, 1)
    }
  }
}

function selectAll() {
  selectedPermissions.value = permissions.value.map(p => p.name)
}

function clearAll() {
  selectedPermissions.value = []
}

function formatPermission(permission) {
  return permission.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Initialize
onMounted(async () => {
  await Promise.all([fetchRoles(), fetchPermissions()])
})
</script>