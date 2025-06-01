<template>
  <v-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="800px"
    persistent>
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
        <v-btn icon="mdi-close" variant="text" color="white" @click="closeDialog"></v-btn>
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

        <!-- Loading Overlay -->
        <v-overlay :model-value="permissionsStore.loading" contained class="d-flex align-center justify-center">
          <v-progress-circular indeterminate size="64" color="primary"></v-progress-circular>
        </v-overlay>

        <!-- Error Alert -->
        <v-alert v-if="permissionsStore.error" type="error" variant="tonal" closable class="ma-4"
          @click:close="permissionsStore.clearError">
          {{ permissionsStore.error }}
        </v-alert>

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
                      <v-select v-model="selectedRole" :items="permissionsStore.availableRoles" item-title="name"
                        item-value="name" label="Primary Role" variant="outlined" :prepend-icon="getCurrentRoleIcon()"
                        @update:model-value="onRoleChange" :loading="permissionsStore.loading">
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

                      <v-alert v-if="roleChangeWarning" type="warning" variant="tonal" class="mt-3">
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
                          Included Permissions ({{ currentRoleDetails.permissions?.length || 0 }})
                        </div>
                        <v-chip-group>
                          <v-chip v-for="permission in currentRoleDetails.permissions" :key="permission" size="small"
                            color="primary" variant="tonal">
                            {{ permissionsStore.formatPermission(permission) }}
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
                <v-col cols="12" sm="6" md="4" v-for="category in permissionsStore.permissionCategories"
                  :key="category.name">
                  <v-card elevation="1" class="h-100">
                    <v-card-title class="d-flex align-center">
                      <v-icon :color="category.color" class="mr-2">{{ category.icon }}</v-icon>
                      <span class="text-subtitle-1">{{ category.name }}</span>
                    </v-card-title>
                    <v-card-text>
                      <v-list density="compact">
                        <v-list-item v-for="permission in category.permissions" :key="permission.key" class="px-0">
                          <template v-slot:prepend>
                            <v-icon :color="hasUserPermission(permission.key) ? 'success' : 'error'" size="16">
                              {{ hasUserPermission(permission.key) ? 'mdi-check-circle' : 'mdi-close-circle' }}
                            </v-icon>
                          </template>
                          <v-list-item-title :class="{ 'text-medium-emphasis': !hasUserPermission(permission.key) }">
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
                      <v-progress-circular :model-value="permissionCoverage" :color="getPermissionCoverageColor()"
                        size="60" width="6">
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
                    <v-btn variant="outlined" prepend-icon="mdi-download" @click="exportUserPermissions"
                      :loading="permissionsStore.loading">
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
                <v-timeline-item v-for="change in permissionsStore.userHistory" :key="change.id"
                  :dot-color="getHistoryColor(change.type)" size="small">
                  <template v-slot:icon>
                    <v-icon size="12">{{ getHistoryIcon(change.type) }}</v-icon>
                  </template>

                  <v-card elevation="1" class="mb-3">
                    <v-card-text class="py-3">
                      <div class="d-flex align-center justify-space-between mb-2">
                        <div class="d-flex align-center">
                          <v-chip :color="getHistoryColor(change.type)" size="x-small" variant="tonal" class="mr-2">
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
                        <v-btn variant="text" size="x-small" @click="viewChangeDetails(change)">
                          Details
                        </v-btn>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-timeline-item>
              </v-timeline>

              <div v-if="permissionsStore.userHistory.length === 0" class="text-center py-8">
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
        <v-btn variant="outlined" @click="closeDialog">
          Cancel
        </v-btn>
        <v-btn color="primary" @click="saveChanges" :loading="permissionsStore.saving" :disabled="!hasChanges">
          Save Changes
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { usePermissionsStore } from '@/stores/permissions'
import { useRolesStore } from '@/stores/roles'
import { useUsersStore } from '@/stores/users'
import { useAuditStore } from '@/stores'

// Props and emits
const props = defineProps({
  modelValue: Boolean,
  user: Object
})

const emit = defineEmits(['update:modelValue', 'save'])

// Store
const permissionsStore = usePermissionsStore()
const rolesStore = useRolesStore()
const usersStore = useUsersStore() 
const auditsStore = useAuditStore()

// Reactive data
const activeTab = ref('roles')
const selectedRole = ref('')
const roleChangeWarning = ref('')

// Computed properties
const currentRoleDetails = computed(() => {
  return rolesStore.getRoleName(selectedRole.value)
})

const userPermissions = computed(() => {
  return permissionsStore.getUserPermissions(props.user)
})

const hasChanges = computed(() => {
  return selectedRole.value !== props.user?.role
})

const grantedPermissionsCount = computed(() => {
  return userPermissions.value.length
})

const totalPermissionsCount = computed(() => {
  return permissionsStore.permissionCategories.reduce((total, category) =>
    total + category.permissions.length, 0
  )
})

const permissionCoverage = computed(() => {
  return permissionsStore.getPermissionCoverage(props.user)
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
  permissionsStore.clearError()
}

const saveChanges = async () => {
  try {
    if (!props.user?.id) {
      throw new Error('User ID is required')
    }

    // Find the role object by name to get the ID
    const targetRole = rolesStore.RoleNames(selectedRole.value)
    if (!targetRole) {
      throw new Error('Selected role not found')
    }

    // Update user role via store
    const updatedUser = await permissionsStore.updateUserRole(props.user.id, targetRole.id)

    // Emit save event with updated user data
    emit('save', updatedUser)

    closeDialog()
  } catch (error) {
    console.error('Error saving user permissions:', error)
    // Error is already handled by the store and displayed in the UI
  }
}

const onRoleChange = (newRole) => {
  const currentRole = rolesStore.roleNames(props.user?.role)
  const targetRole = rolesStore.roleNames(newRole)

  if (currentRole && targetRole) {
    const currentPermCount = currentRole.permissions?.length || 0
    const targetPermCount = targetRole.permissions?.length || 0

    if (targetPermCount < currentPermCount) {
      roleChangeWarning.value = `This role change will reduce permissions from ${currentPermCount} to ${targetPermCount}. Some access may be revoked.`
    } else if (targetPermCount > currentPermCount) {
      roleChangeWarning.value = `This role change will grant additional permissions (${targetPermCount - currentPermCount} new permissions).`
    } else {
      roleChangeWarning.value = ''
    }
  }
}

const hasUserPermission = (permissionKey) => {
  return permissionsStore.hasPermission(props.user, permissionKey)
}

const getCurrentRoleIcon = () => {
  return currentRoleDetails.value?.icon || 'mdi-account'
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

const exportUserPermissions = async () => {
  if (props.user?.id) {
    try {
      await permissionsStore.exportUserPermissions(props.user.id)
    } catch (error) {
      console.error('Error exporting user permissions:', error)
    }
  }
}

const viewChangeDetails = (change) => {
  // You can implement a detailed view modal here
  console.log('View change details:', change)
}

// Lifecycle and watchers
onMounted(async () => {
  try {
    // Load initial data when component mounts
    await Promise.all([
      usersStore.fetchUsers(props.user?.id),
      rolesStore.fetchRoles(),
      permissionsStore.fetchPermissions()
    ])
  } catch (error) {
    console.error('Error loading initial data:', error)
  }
})

// Watch for user changes
watch(() => props.user, (newUser) => {
  if (newUser) {
    selectedRole.value = newUser.role || ''
  }
}, { immediate: true })

watch(() => props.modelValue, async (newValue) => {
  if (newValue) {
    resetForm()

    // Load user-specific data when dialog opens
    if (props.user?.id) {
      try {
        await Promise.all([
          usersStore.showUser(props.user.id),
          auditsStore.fetchAuditLogs(props.user.id)
        ])
      } catch (error) {
        console.error('Error loading user data:', error)
      }
    }
  }
})
</script>

<style scoped>
/* Add any custom styles here */
.v-overlay {
  background-color: rgba(255, 255, 255, 0.8);
}

.v-progress-circular {
  font-size: 12px;
}

.v-timeline-item {
  padding-bottom: 16px;
}

.v-chip-group {
  max-height: 120px;
  overflow-y: auto;
}
</style>