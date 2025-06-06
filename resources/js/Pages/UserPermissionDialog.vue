<template>
  <v-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="900px"
    persistent scrollable>
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between bg-primary text-white pa-4">
        <div class="d-flex align-center">
          <v-avatar size="40" class="mr-3">
            <v-img :src="user?.avatar || '/default-avatar.png'" :alt="user?.name || 'User'">
              <template v-slot:placeholder>
                <v-icon>mdi-account</v-icon>
              </template>
            </v-img>
          </v-avatar>
          <div>
            <div class="text-h6 font-weight-bold">{{ user?.name || 'Unknown User' }}</div>
            <div class="text-body-2 opacity-80">{{ user?.email || 'No email' }}</div>
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
            All Permissions
          </v-tab>
          <v-tab value="history">
            <v-icon start>mdi-history</v-icon>
            Change History
          </v-tab>
        </v-tabs>

        <v-divider></v-divider>

        <!-- Loading Overlay -->
        <v-overlay :model-value="isLoading" contained class="d-flex align-center justify-center">
          <v-progress-circular indeterminate size="64" color="primary"></v-progress-circular>
        </v-overlay>

        <!-- Error Alert -->
        <v-alert v-if="errorMessage" type="error" variant="tonal" closable class="ma-4" @click:close="clearError">
          {{ errorMessage }}
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
                      <v-select v-model="selectedRoleId" :items="availableRoles" item-title="name" item-value="id"
                        label="Primary Role" variant="outlined" :prepend-icon="getCurrentRoleIcon()"
                        @update:model-value="onRoleChange" :loading="isLoading" :disabled="!canEditRole">
                        <template v-slot:item="{ props, item }">
                          <v-list-item v-bind="props">
                            <template v-slot:prepend>
                              <v-avatar :color="item.raw.color || 'primary'" size="32">
                                <v-icon color="white" size="16">{{ item.raw.icon || 'mdi-account' }}</v-icon>
                              </v-avatar>
                            </template>
                            <v-list-item-title>{{ item.raw.name }}</v-list-item-title>
                            <v-list-item-subtitle>{{ item.raw.description || 'No description' }}</v-list-item-subtitle>
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
                        <v-avatar :color="currentRoleDetails.color || 'primary'" size="40" class="mr-3">
                          <v-icon color="white">{{ currentRoleDetails.icon || 'mdi-account' }}</v-icon>
                        </v-avatar>
                        <div>
                          <div class="font-weight-medium">{{ currentRoleDetails.name }}</div>
                          <div class="text-caption text-medium-emphasis">
                            {{ currentRoleDetails.users_count || 0 }} users with this role
                          </div>
                        </div>
                      </div>

                      <p class="text-body-2 mb-3">{{ currentRoleDetails.description || 'No description available' }}</p>

                      <div>
                        <div class="text-subtitle-2 font-weight-medium mb-2">
                          Included Permissions ({{ rolePermissions.length }})
                        </div>

                        <v-list density="compact" max-height="200" class="overflow-y-auto">
                          <v-list-item v-for="permission in rolePermissions" :key="permission.id || permission.name"
                            class="px-0">
                            <template v-slot:prepend>
                              <v-checkbox :model-value="true" :disabled="true" density="compact" hide-details
                                color="primary" class="mr-2"></v-checkbox>
                            </template>
                            <v-list-item-title>
                              {{ formatPermissionName(permission.name || permission) }}
                            </v-list-item-title>
                          </v-list-item>
                        </v-list>

                        <div v-if="rolePermissions.length === 0" class="text-center py-4 text-medium-emphasis">
                          <v-icon>mdi-key-off</v-icon>
                          <div class="text-caption">No permissions assigned</div>
                        </div>
                      </div>
                    </v-card-text>

                    <v-card-text v-else class="text-center py-8 text-medium-emphasis">
                      <v-icon size="48">mdi-account-question</v-icon>
                      <div class="text-body-2 mt-2">Select a role to view details</div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
            </v-window-item>

            <!-- All Permissions Tab -->
            <v-window-item value="permissions">
              <div class="mb-4">
                <h3 class="text-h6 font-weight-medium mb-2">User Permissions</h3>
                <p class="text-body-2 text-medium-emphasis">
                  Manage user permissions by checking or unchecking the boxes below
                </p>
              </div>

              <!-- Search Filter -->
              <div class="mb-4">
                <v-text-field v-model="permissionSearch" placeholder="Search permissions..."
                  prepend-inner-icon="mdi-magnify" variant="outlined" density="compact" hide-details clearable
                  style="max-width: 400px;">
                </v-text-field>
              </div>

              <!-- Permissions Table -->
              <v-data-table :headers="headers" :items="filteredPermissions" :search="permissionSearch"
                class="elevation-1" item-key="id" :loading="isLoading" loading-text="Loading permissions...">
                <!-- Permission Name Column -->
                <template v-slot:item.name="{ item }">
                  <div class="d-flex align-center">
                    <v-icon :color="item.color || 'primary'" size="small" class="mr-2">
                      {{ item.icon || 'mdi-key' }}
                    </v-icon>
                    <span class="font-weight-medium">{{ formatPermissionName(item.name) }}</span>
                  </div>
                </template>

                <!-- Description Column -->
                <template v-slot:item.description="{ item }">
                  <span class="text-body-2">
                    {{ item.description || 'No description available' }}
                  </span>
                </template>

                <!-- Permission Checkbox Column -->
                <template v-slot:item.hasPermission="{ item }">
                  <v-checkbox :model-value="hasUserPermission(item)"
                    @update:model-value="togglePermission(item, $event)"
                    :disabled="isLoading || isPermissionFromRole(item)" color="success" hide-details>
                    <template v-slot:label>
                      <div class="d-flex align-center gap-1">
                        <span v-if="isPermissionFromRole(item) && hasUserPermission(item)">
                          <v-chip size="x-small" color="info" variant="outlined">
                            From Role
                          </v-chip>
                        </span>
                        <span v-else-if="hasUserPermission(item)">
                          <v-chip size="x-small" color="success" variant="outlined">
                            Direct
                          </v-chip>
                        </span>
                      </div>
                    </template>
                  </v-checkbox>
                </template>

                <!-- Empty state -->
                <template v-slot:no-data>
                  <div class="text-center py-8">
                    <v-icon size="48" color="grey-lighten-2">mdi-database-off</v-icon>
                    <div class="text-h6 mt-2 text-medium-emphasis">No Permissions Found</div>
                    <div class="text-body-2 text-medium-emphasis">
                      No permissions match your search criteria
                    </div>
                  </div>
                </template>
              </v-data-table>
            </v-window-item>

            <!-- Change History Tab -->
            <v-window-item value="history">
              <div class="mb-4">
                <h3 class="text-h6 font-weight-medium mb-2">Permission Change History</h3>
                <p class="text-body-2 text-medium-emphasis mb-4">
                  Track all role and permission changes for this user
                </p>
              </div>

              <v-timeline side="end" density="compact" v-if="userHistory.length > 0">
                <v-timeline-item v-for="change in userHistory" :key="change.id"
                  :dot-color="getHistoryColor(change.action || change.type)" size="small">
                  <template v-slot:icon>
                    <v-icon size="12">{{ getHistoryIcon(change.action || change.type) }}</v-icon>
                  </template>

                  <v-card elevation="1" class="mb-3">
                    <v-card-text class="py-3">
                      <div class="d-flex align-center justify-space-between mb-2">
                        <div class="d-flex align-center">
                          <v-chip :color="getHistoryColor(change.action || change.type)" size="x-small" variant="tonal"
                            class="mr-2">
                            {{ change.action || change.type }}
                          </v-chip>
                          <span class="text-body-2 font-weight-medium">
                            {{ change.description || `${change.action} performed` }}
                          </span>
                        </div>
                        <span class="text-caption text-medium-emphasis">
                          {{ formatDateTime(change.created_at || change.timestamp) }}
                        </span>
                      </div>

                      <div class="d-flex align-center justify-space-between">
                        <div class="d-flex align-center">
                          <span class="text-caption text-medium-emphasis">Changed by:</span>
                          <v-avatar size="20" class="mx-2">
                            <v-img :src="change.user?.avatar || change.changedBy?.avatar || '/default-avatar.png'"
                              :alt="change.user?.name || change.changedBy?.name || 'System'">
                              <template v-slot:placeholder>
                                <v-icon size="12">mdi-account</v-icon>
                              </template>
                            </v-img>
                          </v-avatar>
                          <span class="text-caption">
                            {{ change.user?.name || change.changedBy?.name || 'System' }}
                          </span>
                        </div>
                        <v-btn variant="text" size="x-small" @click="viewChangeDetails(change)">
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
        <v-btn variant="outlined" @click="closeDialog" :disabled="isSaving">
          Cancel
        </v-btn>
        <v-btn color="primary" @click="saveChanges" :loading="isSaving" :disabled="!hasChanges">
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
import { useAuditStore } from '@/stores/audit'

// Props and emits
const props = defineProps({
  modelValue: Boolean,
  user: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:modelValue', 'save', 'error'])

// Stores
const permissionsStore = usePermissionsStore()
const rolesStore = useRolesStore()
const auditStore = useAuditStore()

// Reactive data
const activeTab = ref('roles')
const selectedRoleId = ref(null)
const roleChangeWarning = ref('')
const permissionSearch = ref('')
const isSaving = ref(false)
const hasPermissionChanges = ref(false)

// Table headers configuration
const headers = ref([
  {
    title: 'Permission Name',
    key: 'name',
    align: 'start',
    sortable: true,
    width: '35%'
  },
  {
    title: 'Description',
    key: 'description',
    align: 'start',
    sortable: false,
    width: '45%'
  },
  {
    title: 'Has Permission',
    key: 'hasPermission',
    align: 'center',
    sortable: false,
    width: '20%'
  }
])

// Utility Functions
const getPermissionId = (permission) => {
  if (!permission) return null
  return permission?.id || permission?.name || permission
}

const getHistoryColor = (action) => {
  const colorMap = {
    'Role Changed': 'info',
    'role_changed': 'info',
    'Permission Granted': 'success',
    'permission_granted': 'success',
    'Permission Revoked': 'warning',
    'permission_revoked': 'warning',
    'User Created': 'primary',
    'user_created': 'primary',
    'User Updated': 'info',
    'user_updated': 'info',
    'User Suspended': 'error',
    'user_suspended': 'error'
  }
  return colorMap[action] || 'grey'
}

const getHistoryIcon = (action) => {
  const iconMap = {
    'Role Changed': 'mdi-account-switch',
    'role_changed': 'mdi-account-switch',
    'Permission Granted': 'mdi-key-plus',
    'permission_granted': 'mdi-key-plus',
    'Permission Revoked': 'mdi-key-minus',
    'permission_revoked': 'mdi-key-minus',
    'User Created': 'mdi-account-plus',
    'user_created': 'mdi-account-plus',
    'User Updated': 'mdi-account-edit',
    'user_updated': 'mdi-account-edit',
    'User Suspended': 'mdi-account-cancel',
    'user_suspended': 'mdi-account-cancel'
  }
  return iconMap[action] || 'mdi-information'
}

const handleError = (error, message) => {
  console.error(message, error)
  emit('error', message)
}

// Computed properties
const isLoading = computed(() => 
  permissionsStore.loading || rolesStore.loading || auditStore.loading
)

const errorMessage = computed(() => 
  permissionsStore.error || rolesStore.error || auditStore.error
)

const availableRoles = computed(() => {
  return Array.isArray(rolesStore.roles) ? rolesStore.roles : []
})

const userRoleId = computed(() => 
  props.user?.role_id || props.user?.role?.id || null
)

const currentRoleDetails = computed(() => {
  if (!selectedRoleId.value || !availableRoles.value.length) return null
  return availableRoles.value.find(role => role.id === selectedRoleId.value) || null
})

const rolePermissions = computed(() => {
  if (!currentRoleDetails.value?.permissions) return []
  return Array.isArray(currentRoleDetails.value.permissions) ? currentRoleDetails.value.permissions : []
})

const allPermissions = computed(() => {
  return Array.isArray(permissionsStore.permissions) ? permissionsStore.permissions : []
})

const allUserPermissions = computed(() => {
  const rolePerms = rolePermissions.value || []
  const directPerms = Array.isArray(permissionsStore.userDirectPermissions) ? 
    permissionsStore.userDirectPermissions : []
  
  const combined = [...rolePerms, ...directPerms]
  
  // Remove duplicates based on permission ID or name
  return combined.filter((perm, index, arr) => {
    if (!perm) return false
    const permId = getPermissionId(perm)
    if (!permId) return false
    return arr.findIndex(p => p && getPermissionId(p) === permId) === index
  })
})

const filteredPermissions = computed(() => {
  if (!permissionSearch.value) {
    return allPermissions.value
  }
  
  const search = permissionSearch.value.toLowerCase()
  return allPermissions.value.filter(permission => {
    if (!permission) return false
    const name = permission.name || ''
    const description = permission.description || ''
    return name.toLowerCase().includes(search) || description.toLowerCase().includes(search)
  })
})

const userHistory = computed(() => {
  if (!Array.isArray(auditStore.auditLogs)) return []
  return auditStore.auditLogs.filter(log => 
    log && (log.auditable_id === props.user?.id || log.subject_id === props.user?.id)
  ) || []
})

const canEditRole = computed(() => true)

const hasChanges = computed(() => 
  selectedRoleId.value !== userRoleId.value || hasPermissionChanges.value
)

// Methods
const closeDialog = () => {
  if (!isSaving.value) {
    emit('update:modelValue', false)
    resetForm()
  }
}

const resetForm = () => {
  activeTab.value = 'roles'
  selectedRoleId.value = userRoleId.value
  roleChangeWarning.value = ''
  permissionSearch.value = ''
  hasPermissionChanges.value = false
  clearError()
}

const clearError = () => {
  if (permissionsStore.clearError) permissionsStore.clearError()
  if (rolesStore.clearError) rolesStore.clearError()
  if (auditStore.clearError) auditStore.clearError()
}

const saveChanges = async () => {
  if (!props.user?.id || !selectedRoleId.value) {
    emit('error', 'User ID and role selection are required')
    return
  }

  try {
    isSaving.value = true

    const response = await axios.post(`/api/v1/admin/users/${props.user.id}/assign-role`, {
      role_id: selectedRoleId.value
    })

    emit('save', response.data)
    closeDialog()
  } catch (error) {
    handleError(error, 'Error saving user permissions')
  } finally {
    isSaving.value = false
  }
}

const onRoleChange = (newRoleId) => {
  const currentRole = availableRoles.value.find(role => role.id === userRoleId.value)
  const targetRole = availableRoles.value.find(role => role.id === newRoleId)
  
  if (currentRole && targetRole) {
    const currentPermCount = currentRole.permissions?.length || 0
    const targetPermCount = targetRole.permissions?.length || 0
    
    if (currentPermCount !== targetPermCount) {
      roleChangeWarning.value = `This role change will ${targetPermCount < currentPermCount ? 'reduce' : 'grant additional'} permissions from ${currentPermCount} to ${targetPermCount}.${targetPermCount < currentPermCount ? ' Some access may be revoked.' : ''}`
    } else {
      roleChangeWarning.value = ''
    }
  }
}

const hasUserPermission = (permission) => {
  if (!permission) return false
  const permissionId = getPermissionId(permission)
  if (!permissionId) return false
  
  return allUserPermissions.value.some(p => p && getPermissionId(p) === permissionId)
}

const isPermissionFromRole = (permission) => {
  if (!permission) return false
  const permissionId = getPermissionId(permission)
  if (!permissionId) return false
  
  return rolePermissions.value.some(p => p && getPermissionId(p) === permissionId)
}

const isDirectPermission = (permission) => {
  if (!permission) return false
  const permissionId = getPermissionId(permission)
  if (!permissionId) return false
  
  const directPerms = Array.isArray(permissionsStore.userDirectPermissions) ? 
    permissionsStore.userDirectPermissions : []
  
  return directPerms.some(p => p && getPermissionId(p) === permissionId)
}

const getCurrentRoleIcon = () => currentRoleDetails.value?.icon || 'mdi-account'

const formatDateTime = (date) => {
  if (!date) return 'Unknown'
  try {
    return new Date(date).toLocaleString()
  } catch {
    return 'Invalid Date'
  }
}

const formatPermissionName = (permission) => {
  if (!permission) return 'Unknown Permission'
  const name = typeof permission === 'string' ? permission : (permission.name || 'Unknown Permission')
  return name.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const viewChangeDetails = (change) => {
  console.log('View change details:', change)
}

const togglePermission = async (permission, event) => {
  if (!props.user?.id || !permission) {
    emit('error', 'User ID and permission are required')
    return
  }
  
  if (isPermissionFromRole(permission)) {
    emit('error', 'Cannot modify role-based permissions. Change user role instead.')
    return
  }
  
  const permissionId = getPermissionId(permission)
  if (!permissionId) {
    emit('error', 'Invalid permission data')
    return
  }
  
  const isCurrentlyGranted = isDirectPermission(permission)
  
  try {
    await permissionsStore.assignPermissionToUser(props.user.id, permissionId, !isCurrentlyGranted)
    
    // Log the change if audit store is available
    if (auditStore.logPermissionChange) {
      try {
        await auditStore.logPermissionChange({
          user_id: props.user.id,
          permission_id: permissionId,
          action: isCurrentlyGranted ? 'permission_revoked' : 'permission_granted',
          description: `Direct permission ${isCurrentlyGranted ? 'revoked from' : 'granted to'} user`
        })
      } catch (auditError) {
        console.warn('Failed to log permission change:', auditError)
      }
    }
    
    hasPermissionChanges.value = true
    emit('save', { type: 'permission_change', user: props.user, permission })
  } catch (error) {
    handleError(error, `Failed to ${isCurrentlyGranted ? 'revoke' : 'grant'} permission`)
  }
}

// Lifecycle hooks
onMounted(async () => {
  try {
    const promises = []
    if (rolesStore.fetchRoles) promises.push(rolesStore.fetchRoles())
    if (permissionsStore.fetchPermissions) promises.push(permissionsStore.fetchPermissions())
    
    await Promise.all(promises)
  } catch (error) {
    handleError(error, 'Failed to load roles and permissions')
  }
})

watch(() => props.user, async (newUser) => {
  if (newUser?.id) {
    selectedRoleId.value = userRoleId.value
    try {
      if (permissionsStore.fetchUserDirectPermissions) {
        await permissionsStore.fetchUserDirectPermissions(newUser.id)
      }
    } catch (error) {
      console.error('Error loading user direct permissions:', error)
    }
  } else {
    selectedRoleId.value = null
    permissionsStore.userDirectPermissions = []
  }
}, { immediate: true })

watch(() => props.modelValue, async (newValue) => {
  if (newValue && props.user?.id) {
    resetForm()
    
    const promises = []
    if (auditStore.fetchAuditLogs) {
      promises.push(auditStore.fetchAuditLogs({ user_id: props.user.id }))
    }
    if (permissionsStore.fetchUserDirectPermissions) {
      promises.push(permissionsStore.fetchUserDirectPermissions(props.user.id))
    }
    
    const results = await Promise.allSettled(promises)
    results.forEach((result, index) => {
      if (result.status === 'rejected') {
        console.error(`Error loading ${index === 0 ? 'audit logs' : 'direct permissions'}:`, result.reason)
      }
    })
  }
})
</script>

<style scoped>
.v-overlay {
  background-color: rgba(255, 255, 255, 0.8);
}

.v-progress-circular {
  font-size: 12px;
}

.v-timeline-item {
  padding-bottom: 16px;
}
</style>