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
          <v-btn
            color="primary"
            variant="outlined"
            prepend-icon="mdi-download"
            @click="exportPermissions"
          >
            Export
          </v-btn>

          <!-- add permissions button to open dialog to create permissions -->
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="createRole"
          >
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
                <v-text-field
                  v-model="roleSearch"
                  prepend-inner-icon="mdi-magnify"
                  placeholder="Search roles..."
                  variant="outlined"
                  density="compact"
                  single-line
                  hide-details
                  class="search-field"
                  style="max-width: 300px;"
                ></v-text-field>
              </div>

              <v-row>
                <v-col 
                  cols="12" 
                  md="6" 
                  lg="4" 
                  v-for="role in filteredRoles" 
                  :key="role.id"
                >
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
                          <v-chip 
                            v-for="permission in role.permissions.slice(0, 3)" 
                            :key="permission"
                            size="small"
                            color="primary"
                            variant="tonal"
                          >
                            {{ permission }}
                          </v-chip>
                          <v-chip 
                            v-if="role.permissions.length > 3"
                            size="small"
                            variant="outlined"
                          >
                            +{{ role.permissions.length - 3 }} more
                          </v-chip>
                        </v-chip-group>
                      </div>

                      <div class="d-flex align-center justify-space-between">
                        <v-switch
                          v-model="role.active"
                          :label="role.active ? 'Active' : 'Inactive'"
                          color="success"
                          density="compact"
                          hide-details
                          @change="toggleRoleStatus(role)"
                        ></v-switch>
                        <v-btn
                          variant="outlined"
                          size="small"
                          @click="viewRoleDetails(role)"
                        >
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
              <v-text-field
                v-model="permissionSearch"
                prepend-inner-icon="mdi-magnify"
                placeholder="Search permissions..."
                variant="outlined"
                density="compact"
                single-line
                hide-details
                class="search-field"
                style="max-width: 300px;"
              ></v-text-field>
              </div>

              <v-row>
              <v-col cols="12">

                  <v-btn
                  color="primary"
                  prepend-icon="mdi-plus"
                  class="mb-4"
                  @click="createPermission"
                  >
                  Create Permission
                  </v-btn>
                <v-data-table
                :headers="permissionHeaders"
                :items="filteredPermissions"
                :search="permissionSearch"
                class="elevation-1"
                :items-per-page="10"
                >
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
                <v-text-field
                  v-model="permissionForm.name"
                  label="Permission Name"
                  required
                  autofocus
                ></v-text-field>
                <v-text-field
                  v-model="permissionForm.description"
                  label="Description"
                ></v-text-field>
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
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    Manage user role assignments and permissions
                  </p>
                </div>
                <div class="d-flex gap-2">
                  <v-select
                    v-model="selectedRoleFilter"
                    :items="roleFilterOptions"
                    placeholder="Filter by role"
                    variant="outlined"
                    density="compact"
                    clearable
                    style="min-width: 200px;"
                  ></v-select>
                  <v-text-field
                    v-model="userSearch"
                    prepend-inner-icon="mdi-magnify"
                    placeholder="Search users..."
                    variant="outlined"
                    density="compact"
                    single-line
                    hide-details
                    style="max-width: 300px;"
                  ></v-text-field>
                </div>
              </div>

              <v-data-table
                :headers="userHeaders"
                :items="filteredUsers"
                :search="userSearch"
                class="elevation-1"
                :items-per-page="10"
              >
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
                  <v-select
                    :model-value="item.role"
                    :items="roles.map(r => ({ title: r.name, value: r.name }))"
                    variant="outlined"
                    density="compact"
                    @update:model-value="updateUserRole(item, $event)"
                  ></v-select>
                </template>

                <template v-slot:item.status="{ item }">
                  <v-chip
                    :color="item.status === 'Active' ? 'success' : 'error'"
                    size="small"
                    variant="tonal"
                  >
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
                  <v-select
                    v-model="auditFilter"
                    :items="auditFilterOptions"
                    placeholder="Filter by action"
                    variant="outlined"
                    density="compact"
                    clearable
                    style="min-width: 200px;"
                  ></v-select>
                  <v-menu v-model="dateMenu">
                    <template v-slot:activator="{ props }">
                      <v-text-field
                        v-model="dateRange"
                        prepend-inner-icon="mdi-calendar"
                        placeholder="Select date range"
                        variant="outlined"
                        density="compact"
                        readonly
                        v-bind="props"
                        style="min-width: 200px;"
                      ></v-text-field>
                    </template>
                    <v-date-picker
                      v-model="selectedDates"
                      range
                      @update:model-value="updateDateRange"
                    ></v-date-picker>
                  </v-menu>
                </div>
              </div>

              <v-timeline side="end" class="audit-timeline">
                <v-timeline-item
                  v-for="log in filteredAuditLogs"
                  :key="log.id"
                  :dot-color="getAuditColor(log.action)"
                  size="small"
                >
                  <template v-slot:icon>
                    <v-icon size="16">{{ getAuditIcon(log.action) }}</v-icon>
                  </template>
                  
                  <v-card elevation="1" class="mb-4">
                    <v-card-text>
                      <div class="d-flex align-center justify-space-between mb-2">
                        <div class="d-flex align-center">
                          <v-chip
                            :color="getAuditColor(log.action)"
                            size="small"
                            variant="tonal"
                            class="mr-2"
                          >
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
                        <v-btn
                          variant="text"
                          size="small"
                          @click="viewAuditDetails(log)"
                        >
                          View Details
                        </v-btn>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-timeline-item>
              </v-timeline>
            </v-window-item>
          </v-window>
        </v-card-text>
      </v-card>
    </v-container>

    <!-- Dialogs -->
    <RoleDialog
      v-model="roleDialog"
      :role="selectedRole"
      @save="saveRole"
    />

    <UserPermissionDialog
      v-model="userPermissionDialog"
      :user="selectedUser"
    />
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import RoleDialog from '@/Pages/RoleDialog.vue'
import UserPermissionDialog from '@/Pages/UserPermissionDialog.vue'

// Reactive data
const activeTab = ref('roles')
const roleSearch = ref('')
const userSearch = ref('')
const selectedRoleFilter = ref(null)
const auditFilter = ref(null)
const dateMenu = ref(false)
const dateRange = ref('')
const selectedDates = ref([])
const roleDialog = ref(false)
const userPermissionDialog = ref(false)
const selectedRole = ref(null)
const selectedUser = ref(null)


// Add these to <script setup>:

// Permissions Data
const permissions = ref([
  { id: 1, name: 'create', description: 'Create resources' },
  { id: 2, name: 'read', description: 'Read resources' },
  { id: 3, name: 'update', description: 'Update resources' },
  { id: 4, name: 'delete', description: 'Delete resources' },
  { id: 5, name: 'manage_users', description: 'Manage users' },
  { id: 6, name: 'system_config', description: 'System configuration' },
  // Add more as needed
])

const permissionSearch = ref('')
const permissionDialog = ref(false)
const editingPermission = ref(false)
const permissionForm = ref({
  id: null,
  name: '',
  description: ''
})

const permissionHeaders = ref([
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Description', key: 'description', sortable: false },
  { title: 'Actions', key: 'actions', sortable: false, align: 'end', width: '120px' }
])

const filteredPermissions = computed(() => {
  if (!permissionSearch.value) return permissions.value
  return permissions.value.filter(p =>
    p.name.toLowerCase().includes(permissionSearch.value.toLowerCase()) ||
    (p.description && p.description.toLowerCase().includes(permissionSearch.value.toLowerCase()))
  )
})

// Permission CRUD Methods
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

const savePermission = () => {
  if (!permissionForm.value.name) return
  if (editingPermission.value) {
    // Update existing
    const idx = permissions.value.findIndex(p => p.id === permissionForm.value.id)
    if (idx !== -1) {
      permissions.value[idx] = { ...permissionForm.value }
    }
  } else {
    // Create new
    const newId = Math.max(0, ...permissions.value.map(p => p.id)) + 1
    permissions.value.push({ ...permissionForm.value, id: newId })
  }
  permissionDialog.value = false
}

const deletePermission = (item) => {
  // Simple confirmation, replace with dialog if needed
  if (confirm(`Delete permission "${item.name}"?`)) {
    permissions.value = permissions.value.filter(p => p.id !== item.id)
  }
}


// Mock data - replace with actual API calls
const stats = ref([
  { title: 'Total Roles', value: '12', icon: 'mdi-account-group', color: 'primary' },
  { title: 'Active Users', value: '248', icon: 'mdi-account-check', color: 'success' },
  { title: 'Permissions', value: '45', icon: 'mdi-key', color: 'warning' },
  { title: 'Recent Changes', value: '8', icon: 'mdi-history', color: 'info' }
])

const roles = ref([
  {
    id: 1,
    name: 'Super Admin',
    description: 'Full system access with all permissions',
    permissions: ['create', 'read', 'update', 'delete', 'manage_users', 'system_config'],
    users_count: 2,
    active: true,
    color: 'red',
    icon: 'mdi-shield-crown'
  },
  {
    id: 2,
    name: 'Admin',
    description: 'Administrative access with limited system config',
    permissions: ['create', 'read', 'update', 'delete', 'manage_users'],
    users_count: 8,
    active: true,
    color: 'orange',
    icon: 'mdi-shield-account'
  },
  {
    id: 3,
    name: 'Manager',
    description: 'Team management and reporting capabilities',
    permissions: ['create', 'read', 'update', 'reports'],
    users_count: 15,
    active: true,
    color: 'blue',
    icon: 'mdi-account-tie'
  },
  {
    id: 4,
    name: 'Editor',
    description: 'Content creation and editing permissions',
    permissions: ['create', 'read', 'update'],
    users_count: 32,
    active: true,
    color: 'green',
    icon: 'mdi-pencil'
  },
  {
    id: 5,
    name: 'Viewer',
    description: 'Read-only access to system resources',
    permissions: ['read'],
    users_count: 189,
    active: true,
    color: 'grey',
    icon: 'mdi-eye'
  }
])

const users = ref([
  {
    id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    avatar: 'https://randomuser.me/api/portraits/men/1.jpg',
    role: 'Admin',
    status: 'Active',
    lastLogin: new Date('2024-01-15')
  },
  {
    id: 2,
    name: 'Jane Smith',
    email: 'jane@example.com',
    avatar: 'https://randomuser.me/api/portraits/women/2.jpg',
    role: 'Manager',
    status: 'Active',
    lastLogin: new Date('2024-01-14')
  },
  // Add more mock users...
])

const auditLogs = ref([
  {
    id: 1,
    action: 'Role Created',
    description: 'Created new role "Content Moderator"',
    user: { name: 'Admin User', avatar: 'https://randomuser.me/api/portraits/men/5.jpg' },
    timestamp: new Date('2024-01-15T10:30:00')
  },
  {
    id: 2,
    action: 'Permission Updated',
    description: 'Updated permissions for role "Editor"',
    user: { name: 'John Doe', avatar: 'https://randomuser.me/api/portraits/men/1.jpg' },
    timestamp: new Date('2024-01-15T09:15:00')
  },
  // Add more audit logs...
])

// Computed properties
const filteredRoles = computed(() => {
  if (!roleSearch.value) return roles.value
  return roles.value.filter(role => 
    role.name.toLowerCase().includes(roleSearch.value.toLowerCase()) ||
    role.description.toLowerCase().includes(roleSearch.value.toLowerCase())
  )
})

const filteredUsers = computed(() => {
  let filtered = users.value
  
  if (selectedRoleFilter.value) {
    filtered = filtered.filter(user => user.role === selectedRoleFilter.value)
  }
  
  if (userSearch.value) {
    filtered = filtered.filter(user =>
      user.name.toLowerCase().includes(userSearch.value.toLowerCase()) ||
      user.email.toLowerCase().includes(userSearch.value.toLowerCase())
    )
  }
  
  return filtered
})

const filteredAuditLogs = computed(() => {
  let filtered = auditLogs.value
  
  if (auditFilter.value) {
    filtered = filtered.filter(log => log.action === auditFilter.value)
  }
  
  // Add date filtering logic here if needed
  
  return filtered
})

const roleFilterOptions = computed(() => [
  ...new Set(users.value.map(user => user.role))
])

const auditFilterOptions = ref([
  'Role Created', 'Role Updated', 'Role Deleted',
  'Permission Updated', 'User Assigned', 'User Suspended'
])

const userHeaders = ref([
  { title: 'User', key: 'user', sortable: true, width: '25%' },
  { title: 'Role', key: 'role', sortable: true, width: '20%' },
  { title: 'Status', key: 'status', sortable: true, width: '15%' },
  { title: 'Last Login', key: 'lastLogin', sortable: true, width: '20%' },
  { title: 'Actions', key: 'actions', sortable: false, width: '20%' }
])

// Methods
const createRole = () => {
  selectedRole.value = null
  roleDialog.value = true
}

const editRole = (role) => {
  selectedRole.value = { ...role }
  roleDialog.value = true
}

const duplicateRole = (role) => {
  selectedRole.value = { ...role, id: null, name: `${role.name} (Copy)` }
  roleDialog.value = true
}

const deleteRole = (role) => {
  // Implement delete confirmation and API call
  console.log('Delete role:', role)
}

const toggleRoleStatus = (role) => {
  // Implement API call to toggle role status
  console.log('Toggle role status:', role)
}

const viewRoleDetails = (role) => {
  // Navigate to detailed role view
  console.log('View role details:', role)
}

const saveRole = (roleData) => {
  // Implement save role API call
  console.log('Save role:', roleData)
  roleDialog.value = false
}

const updateUserRole = (user, newRole) => {
  // Implement API call to update user role
  user.role = newRole
  console.log('Update user role:', user, newRole)
}

const viewUserPermissions = (user) => {
  selectedUser.value = user
  userPermissionDialog.value = true
}

const editUserRoles = (user) => {
  // Navigate to user role editing
  console.log('Edit user roles:', user)
}

const suspendUser = (user) => {
  // Implement user suspension/activation
  user.status = user.status === 'Active' ? 'Suspended' : 'Active'
  console.log('Toggle user status:', user)
}

const exportPermissions = () => {
  // Implement export functionality
  console.log('Export permissions')
}

const viewAuditDetails = (log) => {
  // Show detailed audit information
  console.log('View audit details:', log)
}

const updateDateRange = (dates) => {
  if (dates && dates.length === 2) {
    dateRange.value = `${formatDate(dates[0])} - ${formatDate(dates[1])}`
  }
  dateMenu.value = false
}

// Utility functions
const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString()
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

onMounted(() => {
  // Initialize data or make API calls
})
</script>

<style scoped>
.search-field :deep(.v-field__input) {
  padding-top: 0;
  padding-bottom: 0;
}

.audit-timeline :deep(.v-timeline-item__body) {
  padding-bottom: 0;
}

.border-primary {
  border: 2px solid rgb(var(--v-theme-primary)) !important;
}
</style>