
<template>
  <v-dialog 
    :model-value="modelValue" 
    @update:model-value="$emit('update:modelValue', $event)" 
    max-width="900px"
    persistent
    scrollable
  >
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between bg-primary text-white pa-4">
        <div class="d-flex align-center">
          <v-avatar size="40" class="mr-3">
            <v-img 
              :src="user?.avatar || '/default-avatar.png'" 
              :alt="user?.name"
            >
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
            All Permissions
          </v-tab>
          <v-tab value="history">
            <v-icon start>mdi-history</v-icon>
            Change History
          </v-tab>
        </v-tabs>

        <v-divider></v-divider>

        <!-- Loading Overlay -->
        <v-overlay 
          :model-value="isLoading" 
          contained 
          class="d-flex align-center justify-center"
        >
          <v-progress-circular indeterminate size="64" color="primary"></v-progress-circular>
        </v-overlay>

        <!-- Error Alert -->
        <v-alert 
          v-if="errorMessage" 
          type="error" 
          variant="tonal" 
          closable 
          class="ma-4"
          @click:close="clearError"
        >
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
                      <v-select 
                        v-model="selectedRoleId" 
                        :items="availableRoles" 
                        item-title="name" 
                        item-value="id"
                        label="Primary Role" 
                        variant="outlined" 
                        :prepend-icon="getCurrentRoleIcon()"
                        @update:model-value="onRoleChange" 
                        :loading="isLoading"
                        :disabled="!canEditRole"
                      >
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
                          <v-list-item 
                            v-for="permission in rolePermissions" 
                            :key="permission.id || permission.name" 
                            class="px-0"
                          >
                            <template v-slot:prepend>
                              <v-checkbox 
                                :model-value="true" 
                                :disabled="true"
                                density="compact" 
                                hide-details 
                                color="primary" 
                                class="mr-2"
                              ></v-checkbox>
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
              <div class="mb-4 d-flex align-center justify-space-between flex-wrap gap-4">
                <div>
                  <h3 class="text-h6 font-weight-medium mb-2">All Available Permissions</h3>
                  <p class="text-body-2 text-medium-emphasis">
                    Complete overview of all system permissions and user's access status
                  </p>
                </div>
                
                <!-- Filter Options -->
                <div class="d-flex align-center gap-2">
                  <v-btn-toggle v-model="permissionFilter" mandatory variant="outlined" density="compact">
                    <v-btn value="all" size="small">All</v-btn>
                    <v-btn value="granted" size="small" color="success">Granted</v-btn>
                    <v-btn value="denied" size="small" color="error">Denied</v-btn>
                  </v-btn-toggle>
                  
                  <v-text-field 
                    v-model="permissionSearch" 
                    placeholder="Search permissions..."
                    prepend-inner-icon="mdi-magnify" 
                    variant="outlined" 
                    density="compact" 
                    hide-details 
                    clearable
                    style="max-width: 250px;"
                  ></v-text-field>
                </div>
              </div>

              <!-- Permission Summary Cards -->
              <v-row class="mb-4">
                <v-col cols="12" sm="4">
                  <v-card elevation="1" color="success" variant="tonal">
                    <v-card-text class="text-center">
                      <v-icon size="32" color="success">mdi-check-circle</v-icon>
                      <div class="text-h4 font-weight-bold mt-2">{{ grantedPermissionsCount }}</div>
                      <div class="text-body-2">Granted Permissions</div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" sm="4">
                  <v-card elevation="1" color="error" variant="tonal">
                    <v-card-text class="text-center">
                      <v-icon size="32" color="error">mdi-close-circle</v-icon>
                      <div class="text-h4 font-weight-bold mt-2">{{ deniedPermissionsCount }}</div>
                      <div class="text-body-2">Denied Permissions</div>
                    </v-card-text>
                  </v-card>
                </v-col>
                <v-col cols="12" sm="4">
                  <v-card elevation="1" color="primary" variant="tonal">
                    <v-card-text class="text-center">
                      <v-progress-circular 
                        :model-value="permissionCoverage" 
                        :color="getPermissionCoverageColor()"
                        size="32" 
                        width="4"
                      >
                        <span class="text-caption font-weight-bold">
                          {{ Math.round(permissionCoverage) }}%
                        </span>
                      </v-progress-circular>
                      <div class="text-body-2 mt-2">Coverage</div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>

              <!-- Permissions List -->
              <v-row>
                <v-col cols="12" sm="6" md="4" v-for="category in filteredPermissionCategories" :key="category.name">
                  <v-card elevation="1" class="h-100">
                    <v-card-title class="d-flex align-center justify-space-between">
                      <div class="d-flex align-center">
                        <v-icon :color="category.color || 'primary'" class="mr-2">{{ category.icon || 'mdi-key' }}</v-icon>
                        <span class="text-subtitle-1">{{ category.name }}</span>
                      </div>
                      <v-chip size="x-small" color="primary" variant="outlined">
                        {{ getCategoryGrantedCount(category) }}/{{ category.permissions.length }}
                      </v-chip>
                    </v-card-title>
                    
                    <v-card-text>
                      <v-list density="compact" max-height="300" class="overflow-y-auto">
                        <v-list-item 
                          v-for="permission in getFilteredCategoryPermissions(category)"
                          :key="permission.id || permission.name" 
                          class="px-0"
                          :class="{ 'permission-item--granted': hasUserPermission(permission) }"
                        >
                          <template v-slot:prepend>
                            <v-icon 
                              :color="hasUserPermission(permission) ? 'success' : 'error'" 
                              size="16"
                            >
                              {{ hasUserPermission(permission) ? 'mdi-check-circle' : 'mdi-close-circle' }}
                            </v-icon>
                          </template>
                          
                          <v-list-item-title 
                            :class="{ 'text-medium-emphasis': !hasUserPermission(permission) }"
                          >
                            {{ formatPermissionName(permission.name || permission) }}
                          </v-list-item-title>
                          
                          <v-list-item-subtitle 
                            v-if="permission.description" 
                            class="text-caption"
                          >
                            {{ permission.description }}
                          </v-list-item-subtitle>
                          
                          <template v-slot:append>
                            <v-tooltip 
                              text="Permission granted through role assignment"
                              v-if="hasUserPermission(permission)"
                            >
                              <template v-slot:activator="{ props: tooltipProps }">
                                <v-icon v-bind="tooltipProps" size="12" color="info">mdi-information</v-icon>
                              </template>
                            </v-tooltip>
                          </template>
                        </v-list-item>
                      </v-list>

                      <!-- Empty state for filtered results -->
                      <div 
                        v-if="getFilteredCategoryPermissions(category).length === 0"
                        class="text-center py-4 text-medium-emphasis"
                      >
                        <v-icon size="24">mdi-filter-off</v-icon>
                        <div class="text-caption mt-1">No permissions match filter</div>
                      </div>
                    </v-card-text>
                  </v-card>
                </v-col>
              </v-row>
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
                  :dot-color="getHistoryColor(change.action || change.type)" 
                  size="small"
                >
                  <template v-slot:icon>
                    <v-icon size="12">{{ getHistoryIcon(change.action || change.type) }}</v-icon>
                  </template>

                  <v-card elevation="1" class="mb-3">
                    <v-card-text class="py-3">
                      <div class="d-flex align-center justify-space-between mb-2">
                        <div class="d-flex align-center">
                          <v-chip 
                            :color="getHistoryColor(change.action || change.type)" 
                            size="x-small" 
                            variant="tonal" 
                            class="mr-2"
                          >
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
                            <v-img 
                              :src="change.user?.avatar || change.changedBy?.avatar || '/default-avatar.png'" 
                              :alt="change.user?.name || change.changedBy?.name"
                            >
                              <template v-slot:placeholder>
                                <v-icon size="12">mdi-account</v-icon>
                              </template>
                            </v-img>
                          </v-avatar>
                          <span class="text-caption">
                            {{ change.user?.name || change.changedBy?.name || 'System' }}
                          </span>
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
        <v-btn variant="outlined" @click="closeDialog" :disabled="isSaving">
          Cancel
        </v-btn>
        <v-btn 
          color="primary" 
          @click="saveChanges" 
          :loading="isSaving" 
          :disabled="!hasChanges"
        >
          Save Changes
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import { usePermissionsStore } from '@/stores/permissions'
import { useRolesStore } from '@/stores/roles'
import { useAuditStore } from '@/stores/audit'

// Props and emits
const props = defineProps({
  modelValue: Boolean,
  user: {
    type: Object,
    default: () => ({})
  }
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
const permissionFilter = ref('all')
const permissionSearch = ref('')
const isSaving = ref(false)

// Computed properties
const isLoading = computed(() => {
  return permissionsStore.loading || rolesStore.loading || auditStore.loading
})

const errorMessage = computed(() => {
  return permissionsStore.error || rolesStore.error || auditStore.error
})

const availableRoles = computed(() => {
  return rolesStore.roles || []
})

const currentRoleDetails = computed(() => {
  if (!selectedRoleId.value) return null
  return availableRoles.value.find(role => role.id === selectedRoleId.value)
})

const rolePermissions = computed(() => {
  return currentRoleDetails.value?.permissions || []
})

const userPermissions = computed(() => {
  if (!currentRoleDetails.value) return []
  return currentRoleDetails.value.permissions || []
})

const allPermissions = computed(() => {
  return permissionsStore.permissions || []
})

const hasChanges = computed(() => {
  const originalRoleId = props.user?.role_id || props.user?.role?.id
  return selectedRoleId.value !== originalRoleId
})

const grantedPermissionsCount = computed(() => {
  return userPermissions.value.length
})

const totalPermissionsCount = computed(() => {
  return allPermissions.value.length
})

const deniedPermissionsCount = computed(() => {
  return Math.max(0, totalPermissionsCount.value - grantedPermissionsCount.value)
})

const permissionCoverage = computed(() => {
  if (totalPermissionsCount.value === 0) return 0
  return (grantedPermissionsCount.value / totalPermissionsCount.value) * 100
})

const permissionCategories = computed(() => {
  const categories = new Map()
  
  allPermissions.value.forEach(permission => {
    const categoryName = permission.category || 'General'
    if (!categories.has(categoryName)) {
      categories.set(categoryName, {
        name: categoryName,
        permissions: [],
        color: getCategoryColor(categoryName),
        icon: getCategoryIcon(categoryName)
      })
    }
    categories.get(categoryName).permissions.push(permission)
  })
  
  return Array.from(categories.values())
})

const filteredPermissionCategories = computed(() => {
  return permissionCategories.value.filter(category => {
    const filteredPermissions = getFilteredCategoryPermissions(category)
    return filteredPermissions.length > 0
  })
})

const userHistory = computed(() => {
  return auditStore.auditLogs.filter(log => 
    log.auditable_id === props.user?.id || 
    log.subject_id === props.user?.id
  ) || []
})

const canEditRole = computed(() => {
  // Add your permission logic here
  return true
})

// Methods
const closeDialog = () => {
  if (!isSaving.value) {
    emit('update:modelValue', false)
    resetForm()
  }
}

const resetForm = () => {
  activeTab.value = 'roles'
  selectedRoleId.value = props.user?.role_id || props.user?.role?.id || null
  roleChangeWarning.value = ''
  permissionFilter.value = 'all'
  permissionSearch.value = ''
  clearError()
}

const clearError = () => {
  permissionsStore.error = null
  rolesStore.error = null
  auditStore.error = null
}

const saveChanges = async () => {
  if (!props.user?.id || !selectedRoleId.value) {
    emit('error', 'User ID and role selection are required')
    return
  }

  try {
    isSaving.value = true
    
    // Update user role (you'll need to implement this method in your store)
    const response = await fetch(`/api/users/${props.user.id}/role`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        role_id: selectedRoleId.value
      })
    })

    if (!response.ok) {
      throw new Error(`Failed to update user role: ${response.statusText}`)
    }

    const updatedUser = await response.json()
    emit('save', updatedUser)
    closeDialog()
  } catch (error) {
    console.error('Error saving user permissions:', error)
    emit('error', error.message)
  } finally {
    isSaving.value = false
  }
}

const onRoleChange = (newRoleId) => {
  const currentRole = availableRoles.value.find(role => 
    role.id === (props.user?.role_id || props.user?.role?.id)
  )
  const targetRole = availableRoles.value.find(role => role.id === newRoleId)

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

const hasUserPermission = (permission) => {
  const permissionName = permission.name || permission
  return userPermissions.value.some(p => 
    (p.name || p) === permissionName
  )
}

const getCategoryGrantedCount = (category) => {
  return category.permissions.filter(permission =>
    hasUserPermission(permission)
  ).length
}

const getFilteredCategoryPermissions = (category) => {
  let permissions = [...category.permissions]

  // Apply search filter
  if (permissionSearch.value) {
    const searchTerm = permissionSearch.value.toLowerCase()
    permissions = permissions.filter(permission =>
      (permission.name || permission).toLowerCase().includes(searchTerm) ||
      (permission.description && permission.description.toLowerCase().includes(searchTerm))
    )
  }

  // Apply status filter
  if (permissionFilter.value === 'granted') {
    permissions = permissions.filter(permission => hasUserPermission(permission))
  } else if (permissionFilter.value === 'denied') {
    permissions = permissions.filter(permission => !hasUserPermission(permission))
  }

  return permissions
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

const getCategoryColor = (categoryName) => {
  const colorMap = {
    'User Management': 'blue',
    'Content': 'green',
    'System': 'orange',
    'Reports': 'purple',
    'Settings': 'teal'
  }
  return colorMap[categoryName] || 'primary'
}

const getCategoryIcon = (categoryName) => {
  const iconMap = {
    'User Management': 'mdi-account-group',
    'Content': 'mdi-file-document',
    'System': 'mdi-cog',
    'Reports': 'mdi-chart-bar',
    'Settings': 'mdi-settings'
  }
  return iconMap[categoryName] || 'mdi-key'
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

const formatDateTime = (date) => {
  if (!date) return 'Unknown'
  return new Date(date).toLocaleString()
}

const formatPermissionName = (permission) => {
  if (!permission) return 'Unknown Permission'
  return permission.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const viewChangeDetails = (change) => {
  console.log('View change details:', change)
  // Implement detailed view functionality
}

// Lifecycle hooks
onMounted(async () => {
  try {
    await Promise.all([
      rolesStore.fetchRoles(),
      permissionsStore.fetchPermissions()
    ])
  } catch (error) {
    console.error('Error loading initial data:', error)
    emit('error', 'Failed to load roles and permissions')
  }
})

// Watchers
watch(() => props.user, (newUser) => {
  if (newUser) {
    selectedRoleId.value = newUser.role_id || newUser.role?.id || null
  }
}, { immediate: true })

watch(() => props.modelValue, async (newValue) => {
  if (newValue) {
    resetForm()
    
    if (props.user?.id) {
      try {
        // Load user-specific audit logs
        await auditStore.fetchAuditLogs({
          user_id: props.user.id
        })
      } catch (error) {
        console.error('Error loading user audit logs:', error)
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

.permission-item--granted {
  background-color: rgba(76, 175, 80, 0.05);
  border-left: 3px solid #4caf50;
}

.gap-2 {
  gap: 8px;
}


.permission-item {
  transition: all 0.2s ease;
  border-radius: 8px;
  margin: 2px 0;
}

.permission-item--clickable {
  cursor: pointer;
}

.permission-item--clickable:hover {
  background-color: rgba(0, 0, 0, 0.04);
}

.permission-item--granted {
  background-color: rgba(76, 175, 80, 0.05);
  border-left: 3px solid #4caf50;
}

.permission-item--role {
  border-left-color: #2196f3;
}

.permission-item--direct {
  border-left-color: #ff9800;
}

.permission-item--granted.permission-item--role.permission-item--direct {
  background: linear-gradient(90deg, rgba(33, 150, 243, 0.1) 0%, rgba(255, 152, 0, 0.1) 100%);
  border-left: 3px solid;
  border-image: linear-gradient(45deg, #2196f3, #ff9800) 1;
}

.gap-1 {
  gap: 4px;
}

.v-chip {
  font-size: 10px !important;
  height: 18px !important;
}
</style>