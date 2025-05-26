<template>
  <v-dialog v-model="dialog" max-width="800px" persistent>
    <v-card>
      <v-card-title class="d-flex align-center justify-space-between">
        <div class="d-flex align-center">
          <v-icon class="mr-3" color="primary">mdi-account-group</v-icon>
          <span class="text-h5">{{ isEditing ? 'Edit Role' : 'Create New Role' }}</span>
        </div>
        <v-btn icon variant="text" @click="closeDialog">
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-6">
        <v-form ref="form" v-model="valid">
          <v-row>
            <!-- Basic Information -->
            <v-col cols="12" md="6">
              <v-card variant="outlined" class="h-100">
                <v-card-title class="text-h6 pb-2">Basic Information</v-card-title>
                <v-card-text>
                  <v-text-field
                    v-model="formData.name"
                    label="Role Name"
                    placeholder="Enter role name"
                    variant="outlined"
                    :rules="[rules.required]"
                    class="mb-4"
                  ></v-text-field>

                  <v-textarea
                    v-model="formData.description"
                    label="Description"
                    placeholder="Enter role description"
                    variant="outlined"
                    rows="3"
                    :rules="[rules.required]"
                    class="mb-4"
                  ></v-textarea>

                  <v-select
                    v-model="formData.color"
                    :items="colorOptions"
                    label="Role Color"
                    variant="outlined"
                    class="mb-4"
                  >
                    <template v-slot:selection="{ item }">
                      <div class="d-flex align-center">
                        <v-avatar size="20" :color="item.value" class="mr-2"></v-avatar>
                        {{ item.title }}
                      </div>
                    </template>
                    <template v-slot:item="{ props, item }">
                      <v-list-item v-bind="props">
                        <template v-slot:prepend>
                          <v-icon>{{ item.value }}</v-icon>
                        </template>
                      </v-list-item>
                    </template>
                  </v-select>

                  <v-switch
                    v-model="formData.active"
                    label="Active Role"
                    color="success"
                    hide-details
                  ></v-switch>
                </v-card-text>
              </v-card>
            </v-col>

            <!-- Permissions -->
            <v-col cols="12" md="6">
              <v-card variant="outlined" class="h-100">
                <v-card-title class="text-h6 pb-2 d-flex align-center justify-space-between">
                  <span>Permissions</span>
                  <v-chip size="small" color="primary" variant="tonal">
                    {{ selectedPermissions.length }} selected
                  </v-chip>
                </v-card-title>
                <v-card-text>
                  <v-text-field
                    v-model="permissionSearch"
                    prepend-inner-icon="mdi-magnify"
                    placeholder="Search permissions..."
                    variant="outlined"
                    density="compact"
                    hide-details
                    class="mb-4"
                  ></v-text-field>

                  <div class="permission-groups">
                    <v-expansion-panels v-model="expandedGroups" multiple>
                      <v-expansion-panel
                        v-for="group in filteredPermissionGroups"
                        :key="group.name"
                        :value="group.name"
                      >
                        <v-expansion-panel-title>
                          <div class="d-flex align-center justify-space-between w-100">
                            <div class="d-flex align-center">
                              <v-icon class="mr-2">{{ group.icon }}</v-icon>
                              <span class="font-weight-medium">{{ group.name }}</span>
                            </div>
                            <div class="d-flex align-center">
                              <v-chip size="x-small" color="grey" variant="tonal" class="mr-2">
                                {{ getGroupSelectedCount(group) }}/{{ group.permissions.length }}
                              </v-chip>
                              <v-checkbox
                                :model-value="isGroupFullySelected(group)"
                                :indeterminate="isGroupPartiallySelected(group)"
                                @update:model-value="toggleGroup(group, $event)"
                                density="compact"
                                hide-details
                                @click.stop
                              ></v-checkbox>
                            </div>
                          </div>
                        </v-expansion-panel-title>
                        <v-expansion-panel-text>
                          <v-list density="compact">
                            <v-list-item
                              v-for="permission in group.permissions"
                              :key="permission.key"
                              class="px-0"
                            >
                              <template v-slot:prepend>
                                <v-checkbox
                                  :model-value="selectedPermissions.includes(permission.key)"
                                  @update:model-value="togglePermission(permission.key, $event)"
                                  density="compact"
                                  hide-details
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
              </v-card>
            </v-col>
          </v-row>

          <!-- Permission Summary -->
          <v-row class="mt-4">
            <v-col cols="12">
              <v-card variant="outlined">
                <v-card-title class="text-h6 pb-2">Permission Summary</v-card-title>
                <v-card-text>
                  <div v-if="selectedPermissions.length > 0">
                    <v-chip-group>
                      <v-chip
                        v-for="permission in selectedPermissionDetails"
                        :key="permission.key"
                        size="small"
                        color="primary"
                        variant="tonal"
                        closable
                        @click:close="togglePermission(permission.key, false)"
                      >
                        {{ permission.name }}
                      </v-chip>
                    </v-chip-group>
                  </div>
                  <div v-else class="text-body-2 text-medium-emphasis">
                    No permissions selected. Please select at least one permission for this role.
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-6">
        <v-spacer></v-spacer>
        <v-btn variant="outlined" @click="closeDialog">
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          :disabled="!valid || selectedPermissions.length === 0"
          :loading="loading"
          @click="saveRole"
        >
          {{ isEditing ? 'Update Role' : 'Create Role' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>



<script setup>
import { ref, computed, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: Boolean,
  role: Object
})

const emit = defineEmits(['update:modelValue', 'save'])

// Reactive data
const dialog = ref(false)
const valid = ref(false)
const loading = ref(false)
const form = ref(null)
const permissionSearch = ref('')
const expandedGroups = ref(['User Management', 'Content Management'])

const formData = ref({
  name: '',
  description: '',
  color: 'blue',
  icon: 'mdi-account-group',
  active: true
})

const selectedPermissions = ref([])

// Validation rules
const rules = {
  required: value => !!value || 'This field is required'
}

// Options
const colorOptions = ref([
  { title: 'Blue', value: 'blue' },
  { title: 'Green', value: 'green' },
  { title: 'Orange', value: 'orange' },
  { title: 'Red', value: 'red' },
  { title: 'Purple', value: 'purple' },
  { title: 'Teal', value: 'teal' },
  { title: 'Grey', value: 'grey' }
])

const iconOptions = ref([
  { title: 'Account Group', value: 'mdi-account-group' },
  { title: 'Shield Account', value: 'mdi-shield-account' },
  { title: 'Account Tie', value: 'mdi-account-tie' },
  { title: 'Crown', value: 'mdi-crown' },
  { title: 'Star', value: 'mdi-star' },
  { title: 'Key', value: 'mdi-key' },
  { title: 'Pencil', value: 'mdi-pencil' },
  { title: 'Eye', value: 'mdi-eye' }
])

const permissionGroups = ref([
  {
    name: 'User Management',
    icon: 'mdi-account-multiple',
    permissions: [
      { key: 'users.create', name: 'Create Users', description: 'Create new user accounts' },
      { key: 'users.read', name: 'View Users', description: 'View user information' },
      { key: 'users.update', name: 'Edit Users', description: 'Modify user information' },
      { key: 'users.delete', name: 'Delete Users', description: 'Remove user accounts' },
      { key: 'users.suspend', name: 'Suspend Users', description: 'Suspend/activate user accounts' }
    ]
  },
  {
    name: 'Role Management',
    icon: 'mdi-account-group',
    permissions: [
      { key: 'roles.create', name: 'Create Roles', description: 'Create new roles' },
      { key: 'roles.read', name: 'View Roles', description: 'View role information' },
      { key: 'roles.update', name: 'Edit Roles', description: 'Modify role permissions' },
      { key: 'roles.delete', name: 'Delete Roles', description: 'Remove roles' },
      { key: 'roles.assign', name: 'Assign Roles', description: 'Assign roles to users' }
    ]
  },
  {
    name: 'Content Management',
    icon: 'mdi-file-document',
    permissions: [
      { key: 'content.create', name: 'Create Content', description: 'Create new content' },
      { key: 'content.read', name: 'View Content', description: 'View content' },
      { key: 'content.update', name: 'Edit Content', description: 'Modify existing content' },
      { key: 'content.delete', name: 'Delete Content', description: 'Remove content' },
      { key: 'content.publish', name: 'Publish Content', description: 'Publish/unpublish content' }
    ]
  },
  {
    name: 'System Administration',
    icon: 'mdi-cog',
    permissions: [
      { key: 'system.config', name: 'System Configuration', description: 'Modify system settings' },
      { key: 'system.logs', name: 'View Logs', description: 'Access system logs' },
      { key: 'system.backup', name: 'Backup Management', description: 'Create and restore backups' },
      { key: 'system.maintenance', name: 'Maintenance Mode', description: 'Enable/disable maintenance mode' }
    ]
  },
  {
    name: 'Reports & Analytics',
    icon: 'mdi-chart-line',
    permissions: [
      { key: 'reports.view', name: 'View Reports', description: 'Access reports and analytics' },
      { key: 'reports.create', name: 'Create Reports', description: 'Generate custom reports' },
      { key: 'reports.export', name: 'Export Reports', description: 'Export reports to various formats' },
      { key: 'analytics.access', name: 'Analytics Access', description: 'Access detailed analytics' }
    ]
  }
])

// Computed properties
const isEditing = computed(() => !!props.role?.id)

const filteredPermissionGroups = computed(() => {
  if (!permissionSearch.value) return permissionGroups.value
  
  return permissionGroups.value.map(group => ({
    ...group,
    permissions: group.permissions.filter(permission =>
      permission.name.toLowerCase().includes(permissionSearch.value.toLowerCase()) ||
      permission.description.toLowerCase().includes(permissionSearch.value.toLowerCase())
    )
  })).filter(group => group.permissions.length > 0)
})

const selectedPermissionDetails = computed(() => {
  const allPermissions = permissionGroups.value.flatMap(group => group.permissions)
  return selectedPermissions.value.map(key =>
    allPermissions.find(p => p.key === key)
  ).filter(Boolean)
})

// Watchers
watch(() => props.modelValue, (newVal) => {
  dialog.value = newVal
  if (newVal && props.role) {
    resetForm()
  }
})

watch(dialog, (newVal) => {
  emit('update:modelValue', newVal)
  if (!newVal) {
    resetForm()
  }
})

// Methods
const resetForm = () => {
  if (props.role) {
    formData.value = { ...props.role }
    selectedPermissions.value = [...(props.role.permissions || [])]
  } else {
    formData.value = {
      name: '',
      description: '',
      color: 'blue',
      icon: 'mdi-account-group',
      active: true
    }
    selectedPermissions.value = []
  }
  
  nextTick(() => {
    if (form.value) {
      form.value.resetValidation()
    }
  })
}

const closeDialog = () => {
  dialog.value = false
}

const saveRole = async () => {
  if (!form.value.validate()) return
  
  loading.value = true
  
  try {
    const roleData = {
      ...formData.value,
      permissions: selectedPermissions.value
    }
    
    emit('save', roleData)
  } finally {
    loading.value = false
  }
}

const togglePermission = (permissionKey, selected) => {
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

const toggleGroup = (group, selected) => {
  group.permissions.forEach(permission => {
    togglePermission(permission.key, selected)
  })
}

const isGroupFullySelected = (group) => {
  return group.permissions.every(permission =>
    selectedPermissions.value.includes(permission.key)
  )
}

const isGroupPartiallySelected = (group) => {
  const selectedCount = getGroupSelectedCount(group)
  return selectedCount > 0 && selectedCount < group.permissions.length
}

const getGroupSelectedCount = (group) => {
  return group.permissions.filter(permission =>
    selectedPermissions.value.includes(permission.key)
  ).length
}
</script>