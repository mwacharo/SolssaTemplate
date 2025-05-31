<template>
  <AppLayout title="Permissions Management">
    <template #header>
      <div class="d-flex align-center justify-space-between">
        <div>
          <h1 class="text-h4 font-weight-bold text-primary mb-2">
            Permissions Management
          </h1>
          <p class="text-body-1 text-medium-emphasis mb-0">
            Manage user roles, permissions, and access controls across your organization
          </p>
        </div>
        <div class="d-flex gap-2">
          <v-btn color="primary" variant="outlined" prepend-icon="mdi-download" @click="exportPermissions">
            Export
          </v-btn>

          <!-- add permissions button to open dialog to create permissions -->
          <v-btn color="primary" prepend-icon="mdi-plus" @click="createRole">
            Create Role
          </v-btn>
        </div>
      </div>
    </template>

    <v-container fluid class="pa-0">
      <!-- Stats Cards -->
      <v-row class="mb-6">
        <v-col cols="12" sm="6" md="3" v-for="stat in stats" :key="stat.title">
          <v-card elevation="2" class="h-100">
            <v-card-text class="d-flex align-center">
              <div class="flex-grow-1">
                <div class="text-h4 font-weight-bold text-primary mb-1">
                  {{ stat.value }}
                </div>
                <div class="text-body-2 text-medium-emphasis">
                  {{ stat.title }}
                </div>
              </div>
              <v-avatar size="48" :color="stat.color" variant="tonal">
                <v-icon size="24" :color="stat.color">{{ stat.icon }}</v-icon>
              </v-avatar>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <!-- Main Content Tabs -->
      <v-card elevation="2">
        <v-tabs v-model="activeTab" bg-color="transparent" class="px-4">
          <!-- add permissions tab -->
          <v-tab value="permissions">
            <v-icon start>mdi-key</v-icon>
            Permissions
          </v-tab>
          <v-tab value="roles">
            <v-icon start>mdi-account-group</v-icon>
            Roles & Permissions
          </v-tab>
          <v-tab value="users">
            <v-icon start>mdi-account-multiple</v-icon>
            User Assignments
          </v-tab>
          <v-tab value="audit">
            <v-icon start>mdi-history</v-icon>
            Audit Log
          </v-tab>
        </v-tabs>

        <v-divider></v-divider>

        <v-card-text class="pa-6">
          <v-window v-model="activeTab">
            <!-- Roles & Permissions Tab -->
            <v-window-item value="roles">
              <div class="d-flex align-center justify-space-between mb-6">
                <div>
                  <h2 class="text-h5 font-weight-medium mb-1">Roles Management</h2>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Configure roles and their associated permissions
                  </p>
                </div>
                <v-text-field v-model="roleSearch" prepend-inner-icon="mdi-magnify" placeholder="Search roles..."
                  variant="outlined" density="compact" single-line hide-details class="search-field"
                  style="max-width: 300px;"></v-text-field>
              </div>

              <v-row>
                <v-col cols="12" md="6" lg="4" v-for="role in filteredRoles" :key="role.id">
                  <v-card elevation="1" :class="{ 'border-primary': role.active }" class="h-100">
                    <v-card-title class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center">
                        <v-avatar :color="role.color" size="32" class="mr-3">
                          <v-icon color="white" size="16">{{ role.icon }}</v-icon>
                        </v-avatar>
                        <div>
                          <div class="font-weight-medium">{{ role.name }}</div>
                          <div class="text-caption text-medium-emphasis">
                            {{ role.users_count }} {{ role.users_count === 1 ? 'user' : 'users' }}
                          </div>
                        </div>
                      </div>
                      <v-menu>
                        <template v-slot:activator="{ props }">
                          <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props"></v-btn>
                        </template>
                        <v-list>
                          <v-list-item @click="editRole(role)">
                            <template v-slot:prepend>
                              <v-icon>mdi-pencil</v-icon>
                            </template>
                            <v-list-item-title>Edit Role</v-list-item-title>
                          </v-list-item>
                          <v-list-item @click="duplicateRole(role)">
                            <template v-slot:prepend>
                              <v-icon>mdi-content-copy</v-icon>
                            </template>
                            <v-list-item-title>Duplicate</v-list-item-title>
                          </v-list-item>
                          <v-divider></v-divider>
                          <v-list-item @click="deleteRole(role)" class="text-error">
                            <template v-slot:prepend>
                              <v-icon color="error">mdi-delete</v-icon>
                            </template>
                            <v-list-item-title>Delete Role</v-list-item-title>
                          </v-list-item>
                        </v-list>
                      </v-menu>
                    </v-card-title>

                    <v-card-text>
                      <p class="text-body-2 text-medium-emphasis mb-4">
                        {{ role.description }}
                      </p>

                      <div class="mb-4">
                        <div class="text-subtitle-2 font-weight-medium mb-2">Permissions</div>
                        <v-chip-group>
                          <v-chip v-for="permission in role.permissions.slice(0, 3)" :key="permission" size="small"
                            color="primary" variant="tonal">
                            {{ permission }}
                          </v-chip>
                          <v-chip v-if="role.permissions.length > 3" size="small" variant="outlined">
                            +{{ role.permissions.length - 3 }} more
                          </v-chip>
                        </v-chip-group>
                      </div>

                      <div class="d-flex align-center justify-space-between">
                        <v-switch v-model="role.active" :label="role.active ? 'Active' : 'Inactive'" color="success"
                          density="compact" hide-details @change="toggleRoleStatus(role)"></v-switch>
                        <v-btn variant="outlined" size="small" @click="viewRoleDetails(role)">
                          View Details
                        </v-btn>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-window-item>

            <!-- permissions CRUD tab  -->
            <v-window-item value="permissions">
              <div class="d-flex align-center justify-space-between mb-6">
                <div>
                  <h2 class="text-h5 font-weight-medium mb-1">Permissions Management</h2>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Create, edit, and delete permissions for your organization
                  </p>
                </div>
                <v-text-field v-model="permissionSearch" prepend-inner-icon="mdi-magnify"
                  placeholder="Search permissions..." variant="outlined" density="compact" single-line hide-details
                  class="search-field" style="max-width: 300px;"></v-text-field>
              </div>

              <v-row>
                <v-col cols="12">

                  <v-btn color="primary" prepend-icon="mdi-plus" class="mb-4" @click="createPermission">
                    Create Permission
                  </v-btn>
                  <v-data-table :headers="permissionHeaders" :items="filteredPermissions" :search="permissionSearch"
                    class="elevation-1" :items-per-page="10">
                    <template v-slot:top>

                    </template>

                    <template v-slot:item.actions="{ item }">
                      <v-menu>
                        <template v-slot:activator="{ props }">
                          <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props"></v-btn>
                        </template>
                        <v-list>
                          <v-list-item @click="editPermission(item)">
                            <template v-slot:prepend>
                              <v-icon>mdi-pencil</v-icon>
                            </template>
                            <v-list-item-title>Edit</v-list-item-title>
                          </v-list-item>
                          <v-divider></v-divider>
                          <v-list-item @click="deletePermission(item)" class="text-error">
                            <template v-slot:prepend>
                              <v-icon color="error">mdi-delete</v-icon>
                            </template>
                            <v-list-item-title>Delete</v-list-item-title>
                          </v-list-item>
                        </v-list>
                      </v-menu>
                    </template>
                  </v-data-table>
                </v-col>
              </v-row>

              <!-- Permission Dialog -->
              <v-dialog v-model="permissionDialog" max-width="500">
                <v-card>
                  <v-card-title>
                    <span class="text-h6">{{ editingPermission ? 'Edit Permission' : 'Create Permission' }}</span>
                  </v-card-title>
                  <v-card-text>
                    <v-text-field v-model="permissionForm.name" label="Permission Name" required
                      autofocus></v-text-field>
                    <v-text-field v-model="permissionForm.description" label="Description"></v-text-field>
                  </v-card-text>
                  <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn text @click="permissionDialog = false">Cancel</v-btn>
                    <v-btn color="primary" @click="savePermission">
                      {{ editingPermission ? 'Save' : 'Create' }}
                    </v-btn>
                  </v-card-actions>
                </v-card>
              </v-dialog>
            </v-window-item>

            <!-- User Assignments Tab -->
            <v-window-item value="users">
              <div class="d-flex align-center justify-space-between mb-6">
                <div>
                  <h2 class="text-h5 font-weight-medium mb-1">User Role Assignments</h2>
                  <p class="text-booptdy-2 text-medium-emphasis mb-0">
                    Manage user role assignments and permissions
                  </p>
                </div>
                <div class="d-flex gap-2">
                  <v-select v-model="selectedRoleFilter" :items="roleFilterOptions" placeholder="Filter by role"
                    variant="outlined" density="compact" clearable style="min-width: 200px;"></v-select>
                  <v-text-field v-model="userSearch" prepend-inner-icon="mdi-magnify" placeholder="Search users..."
                    variant="outlined" density="compact" single-line hide-details
                    style="max-width: 300px;"></v-text-field>
                </div>
              </div>

              <v-data-table :headers="userHeaders" :items="filteredUsers" :search="userSearch" class="elevation-1"
                :items-per-page="10">
                <template v-slot:item.user="{ item }">
                  <div class="d-flex align-center py-2">
                    <v-avatar size="40" class="mr-3">
                      <v-img :src="item.avatar" :alt="item.name"></v-img>
                    </v-avatar>
                    <div>
                      <div class="font-weight-medium">{{ item.name }}</div>
                      <div class="text-caption text-medium-emphasis">{{ item.email }}</div>
                    </div>
                  </div>
                </template>

                <template v-slot:item.role="{ item }">
                  <v-select :model-value="item.role" :items="roles.map(r => ({ title: r.name, value: r.name }))"
                    variant="outlined" density="compact" @update:model-value="updateUserRole(item, $event)"></v-select>
                </template>

                <template v-slot:item.status="{ item }">
                  <v-chip :color="item.status === 'Active' ? 'success' : 'error'" size="small" variant="tonal">
                    {{ item.status }}
                  </v-chip>
                </template>

                <template v-slot:item.lastLogin="{ item }">
                  <div class="text-body-2">
                    {{ formatDate(item.lastLogin) }}
                  </div>
                </template>

                <template v-slot:item.actions="{ item }">
                  <v-menu>
                    <template v-slot:activator="{ props }">
                      <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props"></v-btn>
                    </template>
                    <v-list>
                      <v-list-item @click="viewUserPermissions(item)">
                        <template v-slot:prepend>
                          <v-icon>mdi-eye</v-icon>
                        </template>
                        <v-list-item-title>View Permissions</v-list-item-title>
                      </v-list-item>
                      <v-list-item @click="editUserRoles(item)">
                        <template v-slot:prepend>
                          <v-icon>mdi-pencil</v-icon>
                        </template>
                        <v-list-item-title>Edit Roles</v-list-item-title>
                      </v-list-item>
                      <v-divider></v-divider>
                      <v-list-item @click="suspendUser(item)" :class="{ 'text-error': item.status === 'Active' }">
                        <template v-slot:prepend>
                          <v-icon :color="item.status === 'Active' ? 'error' : 'success'">
                            {{ item.status === 'Active' ? 'mdi-account-cancel' : 'mdi-account-check' }}
                          </v-icon>
                        </template>
                        <v-list-item-title>
                          {{ item.status === 'Active' ? 'Suspend User' : 'Activate User' }}
                        </v-list-item-title>
                      </v-list-item>
                    </v-list>
                  </v-menu>
                </template>
              </v-data-table>
            </v-window-item>

            <!-- Audit Log Tab -->
            <v-window-item value="audit">
              <div class="d-flex align-center justify-space-between mb-6">
                <div>
                  <h2 class="text-h5 font-weight-medium mb-1">Audit Log</h2>
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Track all permission and role changes
                  </p>
                </div>
                <div class="d-flex gap-2">
                  <v-select v-model="selectedAction" :items="auditFilterOptions" placeholder="Filter by action"
                    variant="outlined" density="compact" clearable style="min-width: 200px;"
                    @update:model-value="updateActionFilter"></v-select>
                  <v-menu v-model="dateMenu">
                    <template v-slot:activator="{ props }">
                      <v-text-field v-model="dateRangeText" prepend-inner-icon="mdi-calendar"
                        placeholder="Select date range" variant="outlined" density="compact" readonly v-bind="props"
                        style="min-width: 200px;"></v-text-field>
                    </template>
                    <v-date-picker v-model="selectedDates" range @update:model-value="updateDateRange"></v-date-picker>
                  </v-menu>
                </div>
              </div>

              <!-- Loading State -->
              <div v-if="auditStore.loading" class="text-center py-8">
                <v-progress-circular indeterminate color="primary"></v-progress-circular>
                <p class="mt-4 text-body-2">Loading audit logs...</p>
              </div>

              <!-- Error State -->
              <v-alert v-if="auditStore.error" type="error" class="mb-4" closable
                @click:close="auditStore.error = null">
                {{ auditStore.error }}
              </v-alert>

              <!-- Recent Changes Counter -->
              <v-chip v-if="auditStore.recentChangesCount > 0" color="info" variant="tonal" class="mb-4">
                {{ auditStore.recentChangesCount }} changes in the last 24 hours
              </v-chip>

              <!-- Audit Timeline -->
              <v-timeline v-if="!auditStore.loading && auditStore.filteredAuditLogs.length > 0" side="end"
                class="audit-timeline">
                <v-timeline-item v-for="log in auditStore.filteredAuditLogs" :key="log.id"
                  :dot-color="auditStore.getAuditColor(log.action)" size="small">
                  <template v-slot:icon>
                    <v-icon size="16">{{ auditStore.getAuditIcon(log.action) }}</v-icon>
                  </template>

                  <v-card elevation="1" class="mb-4">
                    <v-card-text>
                      <div class="d-flex align-center justify-space-between mb-2">
                        <div class="d-flex align-center">
                          <v-chip :color="auditStore.getAuditColor(log.action)" size="small" variant="tonal"
                            class="mr-2">
                            {{ log.action }}
                          </v-chip>
                          <span class="font-weight-medium">{{ log.description }}</span>
                        </div>
                        <div class="text-caption text-medium-emphasis">
                          {{ formatDateTime(log.timestamp) }}
                        </div>
                      </div>

                      <div class="d-flex align-center">
                        <v-avatar size="24" class="mr-2">
                          <v-img :src="log.user.avatar" :alt="log.user.name"></v-img>
                        </v-avatar>
                        <span class="text-body-2">{{ log.user.name }}</span>
                        <v-spacer></v-spacer>
                        <v-btn variant="text" size="small" @click="viewAuditDetails(log)">
                          View Details
                        </v-btn>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-timeline-item>
              </v-timeline>

              <!-- Empty State -->
              <div v-else-if="!auditStore.loading && auditStore.filteredAuditLogs.length === 0"
                class="text-center py-8">
                <v-icon size="64" color="grey-lighten-1">mdi-file-document-outline</v-icon>
                <h3 class="text-h6 mt-4 mb-2">No audit logs found</h3>
                <!-- <p class="text-body-2 text-medium-emphasis">
                  {{ auditStore.auditLogs.length === 0 ? 'No audit activity recorded yet.' : 'Try adjusting your
                  filters.'
                  }}
                </p> -->
                <v-btn v-if="auditStore.filters.action || auditStore.filters.startDate || auditStore.filters.endDate"
                  variant="outlined" @click="clearAllFilters" class="mt-4">
                  Clear Filters
                </v-btn>
              </div>

              <!-- Audit Details Dialog -->
              <v-dialog v-model="detailsDialog" max-width="600">
                <v-card v-if="selectedLog">
                  <v-card-title class="d-flex align-center">
                    <v-icon :color="auditStore.getAuditColor(selectedLog.action)" class="mr-2">
                      {{ auditStore.getAuditIcon(selectedLog.action) }}
                    </v-icon>
                    Audit Log Details
                  </v-card-title>

                  <v-card-text>
                    <v-row>
                      <v-col cols="12" sm="6">
                        <div class="text-caption text-medium-emphasis">Action</div>
                        <v-chip :color="auditStore.getAuditColor(selectedLog.action)" size="small" variant="tonal">
                          {{ selectedLog.action }}
                        </v-chip>
                      </v-col>
                      <v-col cols="12" sm="6">
                        <div class="text-caption text-medium-emphasis">Timestamp</div>
                        <div class="text-body-2">{{ formatDateTime(selectedLog.timestamp) }}</div>
                      </v-col>
                      <v-col cols="12">
                        <div class="text-caption text-medium-emphasis">Description</div>
                        <div class="text-body-2">{{ selectedLog.description }}</div>
                      </v-col>
                      <v-col cols="12">
                        <div class="text-caption text-medium-emphasis">User</div>
                        <div class="d-flex align-center">
                          <v-avatar size="32" class="mr-2">
                            <v-img :src="selectedLog.user.avatar" :alt="selectedLog.user.name"></v-img>
                          </v-avatar>
                          <div>{{ selectedLog.user.name }}</div>
                        </div>
                      </v-col>
                    </v-row>

                    <!-- Details Section -->
                    <v-divider class="my-4"></v-divider>
                    <div class="text-caption text-medium-emphasis mb-2">Change Details</div>

                    <div v-if="selectedLog.details?.attributes">
                      <div class="text-body-2 font-weight-medium mb-2">New Values:</div>
                      <v-chip v-for="(value, key) in selectedLog.details.attributes" :key="key" size="small"
                        variant="outlined" class="mr-1 mb-1">
                        {{ key }}: {{ value }}
                      </v-chip>
                    </div>

                    <div v-if="selectedLog.details?.old" class="mt-3">
                      <div class="text-body-2 font-weight-medium mb-2">Previous Values:</div>
                      <v-chip v-for="(value, key) in selectedLog.details.old" :key="key" size="small" variant="outlined"
                        color="orange" class="mr-1 mb-1">
                        {{ key }}: {{ value }}
                      </v-chip>
                    </div>
                  </v-card-text>

                  <v-card-actions>
                    <v-spacer></v-spacer>
                    <v-btn text @click="detailsDialog = false">Close</v-btn>
                  </v-card-actions>
                </v-card>
              </v-dialog>
            </v-window-item>
          </v-window>
        </v-card-text>
      </v-card>
    </v-container>

    <!-- Dialogs -->
    <RoleDialog v-model="roleDialog" :role="selectedRole" @save="saveRole" />

    <UserPermissionDialog v-model="userPermissionDialog" :user="selectedUser" />
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { usePermissionsStore } from '@/stores/permissions'
import { useRolesStore } from '@/stores/roles'
import { useUsersStore } from '@/stores/users'
import { useAuditStore } from '@/stores/audit'
import AppLayout from '@/Layouts/AppLayout.vue'
import RoleDialog from '@/Pages/RoleDialog.vue'
import UserPermissionDialog from '@/Pages/UserPermissionDialog.vue'

// import { toast } from 'vue3-toastify'

import { notify } from '@/utils/toast'


// Initialize Pinia stores
const permissionsStore = usePermissionsStore()
const rolesStore = useRolesStore()
const usersStore = useUsersStore()
const auditStore = useAuditStore()

// Reactive data for UI
const activeTab = ref('roles')
const roleSearch = ref('')
const permissionSearch = ref('')
const userSearch = ref('')
const selectedRoleFilter = ref(null)
const auditFilter = ref(null)
const dateMenu = ref(false)
const dateRange = ref('')
const selectedDates = ref([])
const roleDialog = ref(false)
const permissionDialog = ref(false)
const userPermissionDialog = ref(false)
const selectedRole = ref(null)
const selectedUser = ref(null)
const editingPermission = ref(false)
const permissionForm = ref({
  id: null,
  name: '',
  description: ''
})
// const roles 

const roles = computed(() => rolesStore.roles)

// Computed properties using store getters
const stats = computed(() => [
  { title: 'Total Roles', value: rolesStore.rolesCount, icon: 'mdi-account-group', color: 'primary' },
  { title: 'Active Users', value: usersStore.activeUsersCount, icon: 'mdi-account-check', color: 'success' },
  { title: 'Permissions', value: permissionsStore.permissionsCount, icon: 'mdi-key', color: 'warning' },
  { title: 'Recent Changes', value: '8', icon: 'mdi-history', color: 'info' } // Audit logs not implemented in stores yet
])

const filteredRoles = computed(() => rolesStore.searchRoles(roleSearch.value))

const filteredPermissions = computed(() => permissionsStore.searchPermissions(permissionSearch.value))

const filteredUsers = computed(() => usersStore.searchUsers(userSearch.value, selectedRoleFilter.value))

const roleFilterOptions = computed(() => rolesStore.roleNames)


// Computed properties
const auditFilterOptions = computed(() => {
  return auditStore.uniqueActions.map(action => ({
    title: action,
    value: action
  }))
})


const updateActionFilter = (action) => {
  auditStore.setFilters({ action })
}


const permissionHeaders = ref([
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Description', key: 'description', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end', width: '120px' }
])

const userHeaders = ref([
  { title: 'User', key: 'user', sortable: true, width: '25%' },
  { title: 'Role', key: 'role', sortable: true, width: '20%' },
  { title: 'Status', key: 'status', sortable: true, width: '15%' },
  { title: 'Last Login', key: 'lastLogin', sortable: true, width: '20%' },
  { title: 'Actions', key: 'actions', sortable: false, width: '20%' }
])

// Methods using store actions
const createRole = () => {
  selectedRole.value = null
  roleDialog.value = true
}

const editRole = (role) => {
  selectedRole.value = { ...role }
  roleDialog.value = true
}

const duplicateRole = async (role) => {
  try {
    await rolesStore.duplicateRole(role.id)
  } catch (err) {
    console.error('Failed to duplicate role:', err)
  }
}

const deleteRole = async (role) => {
  if (confirm(`Delete role "${role.name}"?`)) {
    try {
      await rolesStore.deleteRole(role.id)
    } catch (err) {
      console.error('Failed to delete role:', err)
    }
  }
}

const toggleRoleStatus = async (role) => {
  try {
    await rolesStore.toggleRoleStatus(role.id)
  } catch (err) {
    console.error('Failed to toggle role status:', err)
  }
}

const viewRoleDetails = (role) => {
  console.log('View role details:', role)
}

const saveRole = async (roleData) => {
  try {
    if (selectedRole.value && selectedRole.value.id) {
      await rolesStore.updateRole(selectedRole.value.id, roleData)
    } else {
      await rolesStore.createRole(roleData)
    }
    roleDialog.value = false
  } catch (err) {
    console.error('Failed to save role:', err)
  }
}

const createPermission = () => {
  editingPermission.value = false
  permissionForm.value = { id: null, name: '', description: '' }
  permissionDialog.value = true
}

const editPermission = (item) => {
  editingPermission.value = true
  permissionForm.value = { ...item }
  permissionDialog.value = true
}

const savePermission = async () => {
  if (!permissionForm.value.name) return
  try {
    if (editingPermission.value) {
      await permissionsStore.updatePermission(permissionForm.value.id, permissionForm.value)
            notify.updated('Permission')

    } else {
      await permissionsStore.createPermission(permissionForm.value)
            notify.created('Permission')


    }
    permissionDialog.value = false
  } catch (err) {
        notify.failed('save permission')

    console.error('Failed to save permission:', err)

  }
}

const deletePermission = async (item) => {
  if (confirm(`Delete permission "${item.name}"?`)) {
    try {
      await permissionsStore.deletePermission(item.id)
    } catch (err) {
      console.error('Failed to delete permission:', err)
    }
  }
}

const updateUserRole = async (user, newRole) => {
  try {
    const role = rolesStore.getRoleById(rolesStore.roles.find(r => r.name === newRole)?.id)
    if (role) {
      await usersStore.assignRoleToUser(user.id, role.id)
    }
  } catch (err) {
    console.error('Failed to update user role:', err)
  }
}

const viewUserPermissions = (user) => {
  selectedUser.value = user
  userPermissionDialog.value = true
}

const editUserRoles = (user) => {
  console.log('Edit user roles:', user)
}

const suspendUser = async (user) => {
  try {
    await usersStore.toggleUserStatus(user.id)
  } catch (err) {
    console.error('Failed to toggle user status:', err)
  }
}

const exportPermissions = () => {
  console.log('Export permissions')
}

const updateDateRange = (dates) => {
  if (dates && dates.length === 2) {
    auditStore.setFilters({
      startDate: dates[0],
      endDate: dates[1]
    })
  } else {
    auditStore.setFilters({
      startDate: null,
      endDate: null
    })
  }
  dateMenu.value = false
}

// Utility functions
const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

const formatDateTime = (timestamp) => {
  return new Date(timestamp).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}


const clearAllFilters = () => {
  selectedAction.value = null
  selectedDates.value = []
  auditStore.clearFilters()
}

const viewAuditDetails = (log) => {
  selectedLog.value = log
  detailsDialog.value = true
}


const getAuditColor = (action) => {
  const colorMap = {
    'Role Created': 'success',
    'Role Updated': 'info',
    'Role Deleted': 'error',
    'Permission Updated': 'warning',
    'User Assigned': 'primary',
    'User Suspended': 'error'
  }
  return colorMap[action] || 'grey'
}

const getAuditIcon = (action) => {
  const iconMap = {
    'Role Created': 'mdi-plus-circle',
    'Role Updated': 'mdi-pencil-circle',
    'Role Deleted': 'mdi-delete-circle',
    'Permission Updated': 'mdi-key-change',
    'User Assigned': 'mdi-account-plus',
    'User Suspended': 'mdi-account-cancel'
  }
  return iconMap[action] || 'mdi-information'
}

// Fetch initial data
onMounted(async () => {
  try {
    await Promise.all([
      permissionsStore.fetchPermissions(),
      rolesStore.fetchRoles(),
      usersStore.fetchUsers(),
      auditStore.fetchAuditLogs(),
    ])
  } catch (err) {
    console.error('Failed to fetch initial data:', err)
  }
})

// Watch for filter changes to refetch data
watch(
  () => auditStore.filters,
  async () => {
    try {
      await auditStore.fetchAuditLogs()
    } catch (error) {
      console.error('Failed to refetch audit logs:', error)
    }
  },
  { deep: true }
)
</script>