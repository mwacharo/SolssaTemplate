<template>
  <AppLayout>
    <v-container>
      <!-- Header with Create Button -->
      <v-row class="mb-4 align-center">
        <v-col>
          <h2 class="text-h4">Roles Management</h2>
        </v-col>
        <v-col class="text-right">
          <v-btn 
            color="primary" 
            @click="openCreateDialog"
            :loading="loading"
            prepend-icon="mdi-plus"
          >
            Create Role
          </v-btn>
        </v-col>
      </v-row>


      <!-- add filter section -->
      <v-card class="mb-4" variant="outlined">
        <v-card-title class="text-subtitle-1 pb-2">
          <v-icon left>mdi-filter</v-icon>
          Filters
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filters.search"
                label="Search roles..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="compact"
                clearable
                @input="debouncedSearch"
                hint="Search by name"
                persistent-hint
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.status"
                :items="statusFilterOptions"
                label="Filter by Status"
                variant="outlined"
                density="compact"
                clearable
                @update:model-value="applyFilters"
              ></v-select>
            </v-col>
            <v-col cols="12" md="2" class="d-flex align-center">
              <v-btn
                variant="outlined"
                @click="clearFilters"
                :disabled="!hasActiveFilters"
              >
                Clear Filters
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>


      <!-- addd search tab -->

      <v-row class="mb-4">
        <v-col>
          <v-tabs
            v-model="searchTab"
            background-color="transparent"
            grow
            slider-color="primary"
          >
            <v-tab value="all">All Roles</v-tab>
            <v-tab value="with_permissions">With Permissions</v-tab>
            <v-tab value="without_permissions">Without Permissions</v-tab>
          </v-tabs>
        </v-col>
      </v-row>

      <!-- Roles Data Table -->
      <v-data-table
        :headers="headers"
        :items="roles"
        :loading="loading"
        item-key="id"
        class="elevation-1"
      >
        <template #loading>
          <v-skeleton-loader type="table-row@5"></v-skeleton-loader>
        </template>
        
        <template v-slot:item.permissions="{ item }">
          <div class="d-flex align-center">
            <v-chip-group>
              <v-chip
                v-for="perm in (item.permissions || []).slice(0, 3)"
                :key="perm"
                size="small"
                color="primary"
                variant="tonal"
              >
                {{ formatPermission(perm) }}
              </v-chip>
              <v-chip
                v-if="item.permissions && item.permissions.length > 3"
                size="small"
                variant="outlined"
                @click="openPermissionsDialog(item)"
                style="cursor: pointer"
              >
                +{{ item.permissions.length - 3 }} more
              </v-chip>
            </v-chip-group>
          </div>
        </template>
        
        <template v-slot:item.actions="{ item }">
          <v-btn 
            icon="mdi-shield-edit" 
            size="small" 
            variant="text"
            @click="openPermissionsDialog(item)"
            :loading="updatingRole === item.id"
          >
          </v-btn>
          <v-btn 
            icon="mdi-pencil" 
            size="small" 
            variant="text"
            @click="openEditDialog(item)"
            :loading="updatingRole === item.id"
          >
          </v-btn>
          <v-btn 
            icon="mdi-delete" 
            size="small" 
            variant="text"
            color="error"
            @click="confirmDelete(item)"
            :loading="deletingRole === item.id"
          >
          </v-btn>
        </template>
      </v-data-table>
    </v-container>

    <!-- Create/Edit Role Dialog -->
    <v-dialog v-model="roleDialog" max-width="500px" persistent>
      <v-card>
        <v-card-title class="d-flex align-center">
          <span class="text-h6">{{ isEditing ? 'Edit Role' : 'Create Role' }}</span>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" @click="closeRoleDialog"></v-btn>
        </v-card-title>
        
        <v-divider></v-divider>
        
        <v-card-text class="pb-0">
          <v-form ref="roleForm" v-model="roleFormValid">
            <v-text-field
              v-model="roleFormData.name"
              label="Role Name"
              :rules="[rules.required, rules.minLength]"
              :loading="formLoading"
              variant="outlined"
              class="mb-4"
            ></v-text-field>
          </v-form>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="outlined" @click="closeRoleDialog">Cancel</v-btn>
          <v-btn
            color="primary"
            :disabled="!roleFormValid"
            :loading="formLoading"
            @click="saveRole"
          >
            {{ isEditing ? 'Update' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Permissions Management Dialog -->
    <v-dialog v-model="permissionsDialog" max-width="600px" persistent>
      <v-card>
        <v-card-title class="d-flex align-center">
          <span class="text-h6">Manage Permissions - {{ selectedRole?.name }}</span>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" @click="closePermissionsDialog"></v-btn>
        </v-card-title>
        
        <v-divider></v-divider>
        
        <v-card-text>
          <div class="mb-4">
            <v-text-field
              v-model="permissionSearch"
              label="Search permissions..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              clearable
            ></v-text-field>
          </div>

          <div class="permission-groups" style="max-height: 400px; overflow-y: auto;">
            <v-expansion-panels v-model="expandedPanels" multiple>
              <v-expansion-panel
                v-for="group in filteredPermissionGroups"
                :key="group.name"
                :value="group.name"
              >
                <v-expansion-panel-title>
                  <div class="d-flex align-center w-100">
                    <v-icon :icon="group.icon" class="mr-2"></v-icon>
                    <span>{{ group.name }}</span>
                    <v-spacer></v-spacer>
                    <v-chip size="x-small" variant="tonal">
                      {{ getGroupSelectedCount(group) }}/{{ group.permissions.length }}
                    </v-chip>
                  </div>
                </v-expansion-panel-title>
                
                <v-expansion-panel-text>
                  <div class="mb-2">
                    <v-btn
                      size="small"
                      variant="outlined"
                      @click="selectAllInGroup(group)"
                      class="mr-2"
                    >
                      Select All
                    </v-btn>
                    <v-btn
                      size="small"
                      variant="outlined"
                      @click="deselectAllInGroup(group)"
                    >
                      Deselect All
                    </v-btn>
                  </div>
                  
                  <v-list>
                    <v-list-item
                      v-for="permission in group.permissions"
                      :key="permission.key"
                      class="px-0"
                    >
                      <template #prepend>
                        <v-checkbox
                          :model-value="selectedPermissions.includes(permission.key)"
                          @update:model-value="togglePermission(permission.key, $event)"
                          color="primary"
                        ></v-checkbox>
                      </template>
                      
                      <v-list-item-title>{{ permission.name }}</v-list-item-title>
                      <v-list-item-subtitle>{{ permission.description }}</v-list-item-subtitle>
                    </v-list-item>
                  </v-list>
                </v-expansion-panel-text>
              </v-expansion-panel>
            </v-expansion-panels>
          </div>
        </v-card-text>
        
        <v-divider></v-divider>
        
        <v-card-actions>
          <div class="text-caption text-medium-emphasis">
            {{ selectedPermissions.length }} permissions selected
          </div>
          <v-spacer></v-spacer>
          <v-btn variant="outlined" @click="closePermissionsDialog">Cancel</v-btn>
          <v-btn
            color="primary"
            :loading="permissionLoading"
            @click="updateRolePermissions"
          >
            Update Permissions
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h6">Confirm Delete</v-card-title>
        <v-card-text>
          Are you sure you want to delete the role "{{ roleToDelete?.name }}"? This action cannot be undone.
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="outlined" @click="deleteDialog = false">Cancel</v-btn>
          <v-btn
            color="error"
            :loading="deletingRole === roleToDelete?.id"
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
import { ref, computed, onMounted, nextTick } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"
import { notify } from '@/utils/toast'
import axios from 'axios'

// Set axios defaults for Laravel Sanctum
axios.defaults.withCredentials = true
axios.defaults.headers.common['Accept'] = 'application/json'

// Reactive data
const roles = ref([])
const loading = ref(false)
const formLoading = ref(false)
const permissionLoading = ref(false)
const updatingRole = ref(null)
const deletingRole = ref(null)

// Dialog states
const roleDialog = ref(false)
const permissionsDialog = ref(false)
const deleteDialog = ref(false)

// Form data
const roleFormData = ref({ name: '' })
const roleFormValid = ref(false)
const editingRoleId = ref(null)
const selectedRole = ref(null)
const selectedPermissions = ref([])
const roleToDelete = ref(null)

// UI state
const permissionSearch = ref('')
const expandedPanels = ref(['User Management', 'Content Management'])

// Form refs
const roleForm = ref(null)

const searchTab = ref('all')
const filters = ref({
  search: '',
  status: ''
})

// Table headers
const headers = [
  { title: 'Role Name', value: 'name', sortable: true },
  { title: 'Permissions', value: 'permissions', sortable: false },
  { title: 'Actions', value: 'actions', sortable: false, width: '120px' }
]

// Validation rules
const rules = {
  required: value => !!value || 'This field is required',
  minLength: value => (value && value.length >= 2) || 'Minimum 2 characters required'
}

// Available permissions grouped by category
const permissionGroups = [
  {
    name: 'Basic Operations',
    icon: 'mdi-cog',
    permissions: [
      { key: 'create', name: 'Create', description: 'Create new records' },
      { key: 'read', name: 'Read', description: 'View and read records' },
      { key: 'update', name: 'Update', description: 'Modify existing records' },
      { key: 'delete', name: 'Delete', description: 'Remove records' }
    ]
  },
  {
    name: 'User Management',
    icon: 'mdi-account-multiple',
    permissions: [
      { key: 'manage_users', name: 'Manage Users', description: 'Full user management capabilities' }
    ]
  },
  {
    name: 'System Administration',
    icon: 'mdi-shield-crown',
    permissions: [
      { key: 'system_config', name: 'System Configuration', description: 'Configure system settings' }
    ]
  },
  {
    name: 'Reports & Analytics',
    icon: 'mdi-chart-line',
    permissions: [
      { key: 'reports', name: 'Reports', description: 'Access and generate reports' }
    ]
  }
]

// Computed properties
const isEditing = computed(() => editingRoleId.value !== null)

const filteredPermissionGroups = computed(() => {
  if (!permissionSearch.value) return permissionGroups
  
  return permissionGroups.map(group => ({
    ...group,
    permissions: group.permissions.filter(permission =>
      permission.name.toLowerCase().includes(permissionSearch.value.toLowerCase()) ||
      permission.description.toLowerCase().includes(permissionSearch.value.toLowerCase()) ||
      permission.key.toLowerCase().includes(permissionSearch.value.toLowerCase())
    )
  })).filter(group => group.permissions.length > 0)
})

// API Functions
async function fetchRoles() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/v1/admin/roles')
    roles.value = data
  } catch (error) {
    console.error('Error fetching roles:', error)
    notify.error('Failed to load roles', 'error')
  } finally {
    loading.value = false
  }
}

async function createRole() {
  formLoading.value = true
  try {
    const { data: newRole } = await axios.post('/api/v1/admin/roles', {
      name: roleFormData.value.name,
      permissions: []
    })
    roles.value.push(newRole)
    notify.success('Role created successfully', 'success')
    closeRoleDialog()
  } catch (error) {
    console.error('Error creating role:', error)
    notify.error('Failed to create role', 'error')
  } finally {
    formLoading.value = false
  }
}

async function updateRole() {
  formLoading.value = true
  updatingRole.value = editingRoleId.value
  try {
    const { data: updatedRole } = await axios.put(`/api/v1/admin/roles/${editingRoleId.value}`, {
      name: roleFormData.value.name
    })
    const index = roles.value.findIndex(r => r.id === editingRoleId.value)
    if (index !== -1) {
      roles.value[index] = updatedRole
    }
    notify.success('Role updated successfully', 'success')
    closeRoleDialog()
    await fetchRoles()
  } catch (error) {
    console.error('Error updating role:', error)
    notify('Failed to update role', 'error')
  } finally {
    formLoading.value = false
    updatingRole.value = null
  }
}

async function updateRolePermissions() {
  if (!selectedRole.value) return
  permissionLoading.value = true
  updatingRole.value = selectedRole.value.id
  try {
    const { data: updatedRole } = await axios.put(`/api/v1/admin/roles/${selectedRole.value.id}`, {
      name: selectedRole.value.name,
      permissions: selectedPermissions.value
    })
    const index = roles.value.findIndex(r => r.id === selectedRole.value.id)
    if (index !== -1) {
      roles.value[index] = updatedRole
    }
    notify.success('Permissions updated successfully', 'success')
    closePermissionsDialog()
  } catch (error) {
    console.error('Error updating permissions:', error)
    notify.error('Failed to update permissions', 'error')
  } finally {
    permissionLoading.value = false
    updatingRole.value = null
  }
}

async function deleteRole() {
  if (!roleToDelete.value) return
  deletingRole.value = roleToDelete.value.id
  try {
    await axios.delete(`/api/v1/admin/roles/${roleToDelete.value.id}`)
    roles.value = roles.value.filter(r => r.id !== roleToDelete.value.id)
    notify.success('Role deleted successfully', 'success')
    deleteDialog.value = false
    roleToDelete.value = null
  } catch (error) {
    console.error('Error deleting role:', error)
    notify.error('Failed to delete role', 'error')
  } finally {
    deletingRole.value = null
  }
}

// Dialog methods
function openCreateDialog() {
  roleFormData.value = { name: '' }
  editingRoleId.value = null
  roleDialog.value = true
  nextTick(() => {
    if (roleForm.value) roleForm.value.resetValidation()
  })
}

function openEditDialog(role) {
  roleFormData.value = { name: role.name }
  editingRoleId.value = role.id
  roleDialog.value = true
  nextTick(() => {
    if (roleForm.value) roleForm.value.resetValidation()
  })
}

function openPermissionsDialog(role) {
  selectedRole.value = role
  selectedPermissions.value = [...(role.permissions || [])]
  permissionsDialog.value = true
}

function closeRoleDialog() {
  roleDialog.value = false
  roleFormData.value = { name: '' }
  editingRoleId.value = null
}

function closePermissionsDialog() {
  permissionsDialog.value = false
  selectedRole.value = null
  selectedPermissions.value = []
  permissionSearch.value = ''
}

function confirmDelete(role) {
  roleToDelete.value = role
  deleteDialog.value = true
}

// Permission management methods
function togglePermission(permissionKey, selected) {
  if (selected) {
    if (!selectedPermissions.value.includes(permissionKey)) {
      selectedPermissions.value.push(permissionKey)
    }
  } else {
    const index = selectedPermissions.value.indexOf(permissionKey)
    if (index > -1) {
      selectedPermissions.value.splice(index, 1)
    }
  }
}

function selectAllInGroup(group) {
  group.permissions.forEach(permission => {
    if (!selectedPermissions.value.includes(permission.key)) {
      selectedPermissions.value.push(permission.key)
    }
  })
}

function deselectAllInGroup(group) {
  group.permissions.forEach(permission => {
    const index = selectedPermissions.value.indexOf(permission.key)
    if (index > -1) {
      selectedPermissions.value.splice(index, 1)
    }
  })
}

function getGroupSelectedCount(group) {
  return group.permissions.filter(permission =>
    selectedPermissions.value.includes(permission.key)
  ).length
}

// Utility methods
function saveRole() {
  if (!roleForm.value.validate()) return
  if (isEditing.value) {
    updateRole()
  } else {
    createRole()
  }
}

function formatPermission(permission) {
  return permission.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Lifecycle
onMounted(() => {
  fetchRoles()
})
</script>

<style scoped>
.permission-groups {
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 4px;
}
</style>