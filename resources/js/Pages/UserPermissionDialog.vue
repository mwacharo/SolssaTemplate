<template>
  <v-dialog 
    :model-value="modelValue" 
    @update:model-value="$emit('update:modelValue', $event)"
    max-width="800px"
    persistent
  >
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between bg-primary text-white pa-4">
        <div class="d-flex align-center">
          <v-avatar size="40" class="mr-3">
            <v-img :src="user?.avatar" :alt="user?.name"></v-img>
          </v-avatar>
          <div>
            <div class="text-h6 font-weight-bold">{{ user?.name }}</div>
            <div class="text-body-2 opacity-80">{{ user?.email }}</div>
          </div>
        </div>
        <v-btn 
          icon="mdi-close" 
          variant="text" 
          color="white"
          @click="closeDialog"
        ></v-btn>
      </v-card-title>

      <v-card-text class="pa-0">
        <v-tabs v-model="activeTab" bg-color="transparent" class="px-4 pt-2">
          <v-tab value="roles">
            <v-icon start>mdi-account-group</v-icon>
            Current Roles
          </v-tab>
          <v-tab value="permissions">
            <v-icon start>mdi-key</v-icon>
            Effective Permissions
          </v-tab>
          <v-tab value="history">
            <v-icon start>mdi-history</v-icon>
            Change History
          </v-tab>
        </v-tabs>

        <v-divider></v-divider>

        <div class="pa-6">
          <v-window v-model="activeTab">
            <!-- Current Roles Tab -->
            <v-window-item value="roles">
              <div class="mb-4">
                <h3 class="text-h6 font-weight-medium mb-2">Assigned Roles</h3>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  Manage the roles assigned to this user
                </p>
              </div>

              <v-row>
                <v-col cols="12" md="6">
                  <v-card elevation="1" class="h-100">
                    <v-card-title class="text-subtitle-1 font-weight-medium">
                      Current Role Assignment
                    </v-card-title>
                    <v-card-text>
                      <v-select
                        v-model="selectedRole"
                        :items="availableRoles"
                        item-title="name"
                        item-value="name"
                        label="Primary Role"
                        variant="outlined"
                        :prepend-icon="getCurrentRoleIcon()"
                        @update:model-value="onRoleChange"
                      >
                        <template v-slot:item="{ props, item }">
                          <v-list-item v-bind="props">
                            <template v-slot:prepend>
                              <v-avatar :color="item.raw.color" size="32">
                                <v-icon color="white" size="16">{{ item.raw.icon }}</v-icon>
                              </v-avatar>
                            </template>
                            <v-list-item-title>{{ item.raw.name }}</v-list-item-title>
                            <v-list-item-subtitle>{{ item.raw.description }}</v-list-item-subtitle>
                          </v-list-item>
                        </template>
                      </v-select>

                      <v-alert
                        v-if="roleChangeWarning"
                        type="warning"
                        variant="tonal"
                        class="mt-3"
                      >
                        <v-alert-title>Role Change Warning</v-alert-title>
                        {{ roleChangeWarning }}
                      </v-alert>
                    </v-card-text>
                  </v-card>
                </v-col>

                <v-col cols="12" md="6">
                  <v-card elevation="1" class="h-100">
                    <v-card-title class="text-subtitle-1 font-weight-medium">
                      Role Information
                    </v-card-title>
                    <v-card-text v-if="currentRoleDetails">
                      <div class="d-flex align-center mb-3">
                        <v-avatar :color="currentRoleDetails.color" size="40" class="mr-3">
                          <v-icon color="white">{{ currentRoleDetails.icon }}</v-icon>
                        </v-avatar>
                        <div>
                          <div class="font-weight-medium">{{ currentRoleDetails.name }}</div>
                          <div class="text-caption text-medium-emphasis">
                            {{ currentRoleDetails.users_count }} users with this role
                          </div>
                        </div>
                      </div>
                      
                      <p class="text-body-2 mb-3">{{ currentRoleDetails.description }}</p>
                      
                      <div>
                        <div class="text-subtitle-2 font-weight-medium mb-2">
                          Included Permissions ({{ currentRoleDetails.permissions.length }})
                        </div>
                        <v-chip-group>
                          <v-chip
                            v-for="permission in currentRoleDetails.permissions"
                            :key="permission"
                            size="small"
                            color="primary"
                            variant="tonal"
                          >
                            {{ formatPermission(permission) }}
                          </v-chip>
                        </v-chip-group>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-window-item>

            <!-- Effective Permissions Tab -->
            <v-window-item value="permissions">
              <div class="mb-4">
                <h3 class="text-h6 font-weight-medium mb-2">Effective Permissions</h3>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  All permissions currently granted to this user through their assigned role
                </p>
              </div>

              <v-row>
                <v-col 
                  cols="12" 
                  sm="6" 
                  md="4" 
                  v-for="category in permissionCategories" 
                  :key="category.name"
                >
                  <v-card elevation="1" class="h-100">
                    <v-card-title class="d-flex align-center">
                      <v-icon :color="category.color" class="mr-2">{{ category.icon }}</v-icon>
                      <span class="text-subtitle-1">{{ category.name }}</span>
                    </v-card-title>
                    <v-card-text>
                      <v-list density="compact">
                        <v-list-item
                          v-for="permission in category.permissions"
                          :key="permission.key"
                          class="px-0"
                        >
                          <template v-slot:prepend>
                            <v-icon 
                              :color="hasPermission(permission.key) ? 'success' : 'error'"
                              size="16"
                            >
                              {{ hasPermission(permission.key) ? 'mdi-check-circle' : 'mdi-close-circle' }}
                            </v-icon>
                          </template>
                          <v-list-item-title 
                            :class="{ 'text-medium-emphasis': !hasPermission(permission.key) }"
                          >
                            {{ permission.name }}
                          </v-list-item-title>
                        </v-list-item>
                      </v-list>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <v-card elevation="1" class="mt-4">
                <v-card-title>Permission Summary</v-card-title>
                <v-card-text>
                  <div class="d-flex align-center justify-space-between">
                    <div class="d-flex align-center">
                      <v-progress-circular
                        :model-value="permissionCoverage"
                        :color="getPermissionCoverageColor()"
                        size="60"
                        width="6"
                      >
                        <span class="text-caption font-weight-bold">
                          {{ Math.round(permissionCoverage) }}%
                        </span>
                      </v-progress-circular>
                      <div class="ml-4">
                        <div class="text-subtitle-1 font-weight-medium">
                          {{ grantedPermissionsCount }} of {{ totalPermissionsCount }} permissions granted
                        </div>
                        <div class="text-body-2 text-medium-emphasis">
                          Based on current role assignment
                        </div>
                      </div>
                    </div>
                    <v-btn
                      variant="outlined"
                      prepend-icon="mdi-download"
                      @click="exportUserPermissions"
                    >
                      Export Report
                    </v-btn>
                  </div>
                </v-card-text>
              </v-card>
            </v-window-item>

            <!-- Change History Tab -->
            <v-window-item value="history">
              <div class="mb-4">
                <h3 class="text-h6 font-weight-medium mb-2">Permission Change History</h3>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  Track all role and permission changes for this user
                </p>
              </div>

              <v-timeline side="end" density="compact">
                <v-timeline-item
                  v-for="change in userHistory"
                  :key="change.id"
                  :dot-color="getHistoryColor(change.type)"
                  size="small"
                >
                  <template v-slot:icon>
                    <v-icon size="12">{{ getHistoryIcon(change.type) }}</v-icon>
                  </template>
                  
                  <v-card elevation="1" class="mb-3">
                    <v-card-text class="py-3">
                      <div class="d-flex align-center justify-space-between mb-2">
                        <div class="d-flex align-center">
                          <v-chip
                            :color="getHistoryColor(change.type)"
                            size="x-small"
                            variant="tonal"
                            class="mr-2"
                          >
                            {{ change.type }}
                          </v-chip>
                          <span class="text-body-2 font-weight-medium">{{ change.description }}</span>
                        </div>
                        <span class="text-caption text-medium-emphasis">
                          {{ formatDateTime(change.timestamp) }}
                        </span>
                      </div>
                      
                      <div class="d-flex align-center justify-space-between">
                        <div class="d-flex align-center">
                          <span class="text-caption text-medium-emphasis">Changed by:</span>
                          <v-avatar size="20" class="mx-2">
                            <v-img :src="change.changedBy.avatar" :alt="change.changedBy.name"></v-img>
                          </v-avatar>
                          <span class="text-caption">{{ change.changedBy.name }}</span>
                        </div>
                        <v-btn
                          variant="text"
                          size="x-small"
                          @click="viewChangeDetails(change)"
                        >
                          Details
                        </v-btn>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-timeline-item>
              </v-timeline>

              <div v-if="userHistory.length === 0" class="text-center py-8">
                <v-icon size="48" color="medium-emphasis">mdi-history</v-icon>
                <div class="text-body-1 text-medium-emphasis mt-2">
                  No permission changes recorded for this user
                </div>
              </div>
            </v-window-item>
          </v-window>
        </div>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn
          variant="outlined"
          @click="closeDialog"
        >
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          @click="saveChanges"
          :loading="saving"
          :disabled="!hasChanges"
        >
          Save Changes
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

// Props and emits
const props = defineProps({
  modelValue: Boolean,
  user: Object
})

const emit = defineEmits(['update:modelValue', 'save'])

// Reactive data
const activeTab = ref('roles')
const selectedRole = ref('')
const saving = ref(false)
const roleChangeWarning = ref('')

// Mock data - replace with actual API calls
const availableRoles = ref([
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

const permissionCategories = ref([
  {
    name: 'Content Management',
    icon: 'mdi-file-document',
    color: 'blue',
    permissions: [
      { key: 'create', name: 'Create Content' },
      { key: 'read', name: 'View Content' },
      { key: 'update', name: 'Edit Content' },
      { key: 'delete', name: 'Delete Content' }
    ]
  },
  {
    name: 'User Management',
    icon: 'mdi-account-group',
    color: 'green',
    permissions: [
      { key: 'manage_users', name: 'Manage Users' },
      { key: 'assign_roles', name: 'Assign Roles' },
      { key: 'view_profiles', name: 'View User Profiles' }
    ]
  },
  {
    name: 'System Administration',
    icon: 'mdi-cog',
    color: 'orange',
    permissions: [
      { key: 'system_config', name: 'System Configuration' },
      { key: 'backup_restore', name: 'Backup & Restore' },
      { key: 'audit_logs', name: 'View Audit Logs' }
    ]
  },
  {
    name: 'Reports & Analytics',
    icon: 'mdi-chart-line',
    color: 'purple',
    permissions: [
      { key: 'reports', name: 'Generate Reports' },
      { key: 'analytics', name: 'View Analytics' },
      { key: 'export_data', name: 'Export Data' }
    ]
  }
])

const userHistory = ref([
  {
    id: 1,
    type: 'Role Changed',
    description: 'Role changed from Editor to Manager',
    timestamp: new Date('2024-01-15T10:30:00'),
    changedBy: {
      name: 'Admin User',
      avatar: 'https://randomuser.me/api/portraits/men/5.jpg'
    }
  },
  {
    id: 2,
    type: 'Permission Granted',
    description: 'Granted additional reporting permissions',
    timestamp: new Date('2024-01-10T14:20:00'),
    changedBy: {
      name: 'John Doe',
      avatar: 'https://randomuser.me/api/portraits/men/1.jpg'
    }
  },
  {
    id: 3,
    type: 'User Created',
    description: 'User account created with Editor role',
    timestamp: new Date('2024-01-01T09:00:00'),
    changedBy: {
      name: 'System',
      avatar: 'https://randomuser.me/api/portraits/men/0.jpg'
    }
  }
])

// Computed properties
const currentRoleDetails = computed(() => {
  return availableRoles.value.find(role => role.name === selectedRole.value)
})

const userPermissions = computed(() => {
  return currentRoleDetails.value?.permissions || []
})

const hasChanges = computed(() => {
  return selectedRole.value !== props.user?.role
})

const grantedPermissionsCount = computed(() => {
  return userPermissions.value.length
})

const totalPermissionsCount = computed(() => {
  return permissionCategories.value.reduce((total, category) => 
    total + category.permissions.length, 0
  )
})

const permissionCoverage = computed(() => {
  if (totalPermissionsCount.value === 0) return 0
  return (grantedPermissionsCount.value / totalPermissionsCount.value) * 100
})

// Methods
const closeDialog = () => {
  emit('update:modelValue', false)
  resetForm()
}

const resetForm = () => {
  activeTab.value = 'roles'
  selectedRole.value = props.user?.role || ''
  roleChangeWarning.value = ''
}

const saveChanges = async () => {
  saving.value = true
  try {
    // Implement save logic here
    const userData = {
      ...props.user,
      role: selectedRole.value
    }
    
    emit('save', userData)
    
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    closeDialog()
  } catch (error) {
    console.error('Error saving user permissions:', error)
  } finally {
    saving.value = false
  }
}

const onRoleChange = (newRole) => {
  const currentRole = availableRoles.value.find(r => r.name === props.user?.role)
  const targetRole = availableRoles.value.find(r => r.name === newRole)
  
  if (currentRole && targetRole) {
    const currentPermCount = currentRole.permissions.length
    const targetPermCount = targetRole.permissions.length
    
    if (targetPermCount < currentPermCount) {
      roleChangeWarning.value = `This role change will reduce permissions from ${currentPermCount} to ${targetPermCount}. Some access may be revoked.`
    } else if (targetPermCount > currentPermCount) {
      roleChangeWarning.value = `This role change will grant additional permissions (${targetPermCount - currentPermCount} new permissions).`
    } else {
      roleChangeWarning.value = ''
    }
  }
}

const hasPermission = (permissionKey) => {
  return userPermissions.value.includes(permissionKey)
}

const getCurrentRoleIcon = () => {
  return currentRoleDetails.value?.icon || 'mdi-account'
}

const formatPermission = (permission) => {
  return permission.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const getPermissionCoverageColor = () => {
  const coverage = permissionCoverage.value
  if (coverage >= 80) return 'success'
  if (coverage >= 50) return 'warning'
  return 'error'
}

const getHistoryColor = (type) => {
  const colorMap = {
    'Role Changed': 'info',
    'Permission Granted': 'success',
    'Permission Revoked': 'warning',
    'User Created': 'primary',
    'User Suspended': 'error'
  }
  return colorMap[type] || 'grey'
}

const getHistoryIcon = (type) => {
  const iconMap = {
    'Role Changed': 'mdi-account-switch',
    'Permission Granted': 'mdi-key-plus',
    'Permission Revoked': 'mdi-key-minus',
    'User Created': 'mdi-account-plus',
    'User Suspended': 'mdi-account-cancel'
  }
  return iconMap[type] || 'mdi-information'
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString()
}

const exportUserPermissions = () => {
  // Implement export functionality
  console.log('Export user permissions for:', props.user?.name)
}

const viewChangeDetails = (change) => {
  // Show detailed change information
  console.log('View change details:', change)
}

// Watch for user changes
watch(() => props.user, (newUser) => {
  if (newUser) {
    selectedRole.value = newUser.role || ''
  }
}, { immediate: true })

watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    resetForm()
  }
})
</script>

<style scoped>
/* Add any custom styles here */
</style>