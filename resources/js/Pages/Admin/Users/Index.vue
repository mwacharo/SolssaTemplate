<template>
  <AppLayout>
    <v-container fluid>
      <!-- Header with Create Button -->
      <v-row class="mb-4 align-center">
        <v-col>
          <h2 class="text-h4 text-primary">User Management</h2>
        </v-col>
        <v-col class="text-right">
          <v-btn 
            color="primary" 
            @click="openCreateDialog"
            :loading="loading"
            prepend-icon="mdi-account-plus"
            variant="elevated"
            size="large"
          >
            Create User
          </v-btn>
        </v-col>
      </v-row>

      <!-- Filters Section -->
      <v-card class="mb-4" variant="outlined" elevation="2">
        <v-card-title class="text-subtitle-1 pb-2 bg-grey-lighten-4">
          <v-icon class="mr-2 text-primary">mdi-filter</v-icon>
          Filters
        </v-card-title>
        <v-card-text class="pt-4">
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filters.search"
                label="Search users..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="comfortable"
                clearable
                @input="debouncedSearch"
                hint="Search by name, email"
                persistent-hint
                color="primary"
                bg-color="white"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.role"
                :items="roleFilterOptions"
                label="Filter by Role"
                variant="outlined"
                density="comfortable"
                clearable
                @update:model-value="applyFilters"
                color="primary"
                bg-color="white"
              ></v-select>
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.status"
                :items="statusFilterOptions"
                label="Filter by Status"
                variant="outlined"
                density="comfortable"
                clearable
                @update:model-value="applyFilters"
                color="primary"
                bg-color="white"
              ></v-select>
            </v-col>
            <v-col cols="12" md="2" class="d-flex align-center">
              <v-btn
                variant="outlined"
                @click="clearFilters"
                :disabled="!hasActiveFilters"
                color="secondary"
                size="large"
              >
                Clear Filters
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Users Data Table -->
      <v-card elevation="2">
        <v-data-table
          :headers="headers"
          :items="users"
          :loading="loading"
          item-key="id"
          :items-per-page="itemsPerPage"
          :page="currentPage"
          :server-items-length="totalUsers"
          @update:options="updateTableOptions"
          fixed-header
          height="600"
          class="elevation-1"
        >
          <template #loading>
            <v-skeleton-loader type="table-row@5"></v-skeleton-loader>
          </template>
          
          <template #item.status="{ item }">
            <v-chip
              :color="getStatusColor(item.status)"
              size="small"
              variant="flat"
              class="font-weight-bold"
            >
              {{ item.status }}
            </v-chip>
          </template>
          
          <template #item.roles="{ item }">
            <v-chip-group>
              <v-chip
                v-for="role in item.roles"
                :key="role"
                size="small"
                color="primary"
                variant="flat"
                class="ma-1"
              >
                {{ role }}
              </v-chip>
            </v-chip-group>
          </template>
          
          <template #item.permissions="{ item }">
            <div class="d-flex align-center">
              <v-chip-group>
                <v-chip
                  v-for="perm in item.permissions.slice(0, 2)"
                  :key="perm"
                  size="small"
                  color="secondary"
                  variant="flat"
                  class="ma-1"
                >
                  {{ formatPermission(perm) }}
                </v-chip>
                <v-chip
                  v-if="item.permissions.length > 2"
                  size="small"
                  variant="outlined"
                  color="info"
                  @click="openPermissionsDialog(item)"
                  class="ma-1 cursor-pointer"
                  style="cursor: pointer !important;"
                >
                  +{{ item.permissions.length - 2 }} more
                </v-chip>
              </v-chip-group>
            </div>
          </template>

          <template v-slot:item.country="{ item }">
            <span class="text-body-2">{{ item.country || 'N/A' }}</span>
          </template>
          
          <template v-slot:item.actions="{ item }">
            <div class="d-flex gap-1">
              <v-tooltip text="Manage Roles" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn 
                    v-bind="props"
                    icon="mdi-account-cog" 
                    size="small" 
                    variant="text"
                    color="primary"
                    @click="openRolesDialog(item)"
                    :loading="updatingUser === item.id"
                    class="ma-1"
                  ></v-btn>
                </template>
              </v-tooltip>
              
              <v-tooltip text="Manage Permissions" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn 
                    v-bind="props"
                    icon="mdi-shield-account" 
                    size="small" 
                    variant="text"
                    color="info"
                    @click="openPermissionsDialog(item)"
                    :loading="updatingUser === item.id"
                    class="ma-1"
                  ></v-btn>
                </template>
              </v-tooltip>
              
              <v-tooltip v-if="hasSellerRole(item)" text="Manage Services" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn 
                    v-bind="props"
                    icon="mdi-store" 
                    size="small" 
                    variant="text"
                    color="success"
                    @click="openServicesDialog(item)"
                    :loading="updatingUser === item.id"
                    class="ma-1"
                  ></v-btn>
                </template>
              </v-tooltip>
              
              <v-tooltip text="Edit User" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn 
                    v-bind="props"
                    icon="mdi-pencil" 
                    size="small" 
                    variant="text"
                    color="warning"
                    @click="openEditDialog(item)"
                    :loading="updatingUser === item.id"
                    class="ma-1"
                  ></v-btn>
                </template>
              </v-tooltip>
              
              <v-tooltip text="Delete User" location="top">
                <template v-slot:activator="{ props }">
                  <v-btn 
                    v-bind="props"
                    icon="mdi-delete" 
                    size="small" 
                    variant="text"
                    color="error"
                    @click="confirmDelete(item)"
                    :loading="deletingUser === item.id"
                    class="ma-1"
                  ></v-btn>
                </template>
              </v-tooltip>
            </div>
          </template>

          <template #bottom>
            <div class="text-center pt-4 pb-2">
              <v-pagination
                v-model="currentPage"
                :length="Math.ceil(totalUsers / itemsPerPage)"
                @update:model-value="fetchUsers"
                color="primary"
                variant="elevated"
              ></v-pagination>
            </div>
          </template>
        </v-data-table>
      </v-card>
    </v-container>

    <!-- Create/Edit User Dialog -->
    <v-dialog v-model="userDialog" max-width="500px" persistent>
      <v-card>
      <v-card-title class="d-flex align-center bg-primary text-white">
        <span class="text-h6">{{ isEditing ? 'Edit User' : 'Create User' }}</span>
        <v-spacer></v-spacer>
        <v-btn 
        icon="mdi-close" 
        variant="text" 
        color="white"
        @click="closeUserDialog"
        ></v-btn>
      </v-card-title>
      
      <v-divider></v-divider>
      
      <v-card-text class="pb-0 pt-4">
        <v-form ref="userForm" v-model="userFormValid">
        <v-text-field
          v-model="userFormData.name"
          label="Full Name"
          :rules="[rules.required, rules.minLength]"
          :loading="formLoading"
          variant="outlined"
          class="mb-4"
          color="primary"
          bg-color="white"
        ></v-text-field>
        
        <v-text-field
          v-model="userFormData.email"
          label="Email Address"
          type="email"
          :rules="[rules.required, rules.email]"
          :loading="formLoading"
          variant="outlined"
          class="mb-4"
          color="primary"
          bg-color="white"
        ></v-text-field>

        <v-text-field
          v-model="userFormData.client_name"
          label="Client Name"
          :rules="[rules.required]"
          :loading="formLoading"
          variant="outlined"
          class="mb-4"
          color="primary"
          bg-color="white"
        ></v-text-field>

        <v-text-field
          v-model="userFormData.username"
          label="Username"
          :rules="[rules.required]"
          :loading="formLoading"
          variant="outlined"
          class="mb-4"
          color="primary"
          bg-color="white"    
        ></v-text-field>

        <!-- <v-text-field
          v-model="userFormData.password"
          label="Password"
          type="password"
          :rules="isEditing ? [] : [rules.required, rules.minPassword]"
          :loading="formLoading"
          variant="outlined"
          class="mb-4"
          :hint="isEditing ? 'Leave blank to keep current password' : ''"
          persistent-hint
          color="primary"
          bg-color="white"
        ></v-text-field> -->

        <v-autocomplete
          v-model="userFormData.country_id"
          :items="countries"
          item-title="name"
          item-value="id"
          label="Country"
          :loading="formLoading"
          variant="outlined"
          class="mb-4"
          color="primary"
          bg-color="white"
          clearable
        ></v-autocomplete>

        <v-autocomplete
          v-model="userFormData.role_id"
          :items="availableRoles"
          label="Roles"
          :loading="formLoading"
          variant="outlined"
          class="mb-4"
          color="primary"
          bg-color="white"
          item-title="name"
          item-value="id"
          chips
          clearable
        ></v-autocomplete>

        <v-select
          v-model="userFormData.status"
          :items="statusOptions"
          label="Status"
          :rules="[rules.required]"
          variant="outlined"
          class="mb-4"
          color="primary"
          bg-color="white"
        ></v-select>
        </v-form>
      </v-card-text>

      <!-- boolean active -->
      <v-card-text class="pt-0 pb-4 px-4">
        <v-checkbox
          v-model="userFormData.is_active"
          label="Active"
          color="primary"
          :disabled="formLoading"
        ></v-checkbox>    
      </v-card-text>
      
      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn 
        variant="outlined" 
        @click="closeUserDialog"
        color="secondary"
        size="large"
        >
        Cancel
        </v-btn>
        <v-btn
        color="primary"
        :disabled="!userFormValid"
        :loading="formLoading"
        @click="saveUser"
        variant="elevated"
        size="large"
        >
        {{ isEditing ? 'Update' : 'Create' }}
        </v-btn>
      </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Roles Management Dialog -->
    <v-dialog v-model="rolesDialog" max-width="600px" persistent>
      <v-card>
        <v-card-title class="d-flex align-center bg-primary text-white">
          <span class="text-h6">Manage Roles - {{ selectedUser?.name }}</span>
          <v-spacer></v-spacer>
          <v-btn 
            icon="mdi-close" 
            variant="text" 
            color="white"
            @click="closeRolesDialog"
          ></v-btn>
        </v-card-title>
        
        <v-divider></v-divider>
        
        <v-card-text class="pt-4">
          <div class="mb-4">
            <v-text-field
              v-model="roleSearch"
              label="Search roles..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="comfortable"
              clearable
              color="primary"
              bg-color="white"
            ></v-text-field>
          </div>

          <v-list bg-color="grey-lighten-5">
            <v-list-item
              v-for="role in filteredAvailableRoles"
              :key="role.id"
              class="px-4 py-2 mb-2"
              rounded
            >
              <template #prepend>
                <v-checkbox
                  :model-value="selectedUserRoles.includes(role.name)"
                  @update:model-value="toggleRole(role.name, $event)"
                  color="primary"
                  :loading="roleActionLoading === role.name"
                  hide-details
                ></v-checkbox>
              </template>
              
              <v-list-item-title class="font-weight-medium">{{ role.name }}</v-list-item-title>
              <v-list-item-subtitle class="text-body-2">
                {{ role.permissions?.length || 0 }} permissions
              </v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
        
        <v-divider></v-divider>
        
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn 
            variant="elevated" 
            @click="closeRolesDialog"
            color="primary"
            size="large"
          >
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Permissions Management Dialog -->
    <v-dialog v-model="permissionsDialog" max-width="600px" persistent>
      <v-card>
        <v-card-title class="d-flex align-center bg-primary text-white">
          <span class="text-h6">Manage Permissions - {{ selectedUser?.name }}</span>
          <v-spacer></v-spacer>
          <v-btn 
            icon="mdi-close" 
            variant="text" 
            color="white"
            @click="closePermissionsDialog"
          ></v-btn>
        </v-card-title>
        
        <v-divider></v-divider>
        
        <v-card-text class="pt-4">
          <v-alert type="info" variant="tonal" class="mb-4">
            <div class="text-body-2">
              <strong>Role Permissions:</strong> {{ roleBasedPermissions.join(', ') || 'None' }}
            </div>
          </v-alert>

          <div class="mb-4">
            <v-text-field
              v-model="permissionSearch"
              label="Search permissions..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="comfortable"
              clearable
              color="primary"
              bg-color="white"
            ></v-text-field>
          </div>

          <v-list bg-color="grey-lighten-5">
            <v-list-item
              v-for="permission in filteredAvailablePermissions"
              :key="permission.key"
              class="px-4 py-2 mb-2"
              rounded
            >
              <template #prepend>
                <v-checkbox
                  :model-value="selectedUserPermissions.includes(permission.key)"
                  @update:model-value="togglePermission(permission.key, $event)"
                  color="primary"
                  :loading="permissionActionLoading === permission.key"
                  :disabled="roleBasedPermissions.includes(permission.key)"
                  hide-details
                ></v-checkbox>
              </template>
              
              <v-list-item-title class="font-weight-medium">
                {{ permission.name }}
                <v-chip 
                  v-if="roleBasedPermissions.includes(permission.key)"
                  size="x-small"
                  color="success"
                  variant="flat"
                  class="ml-2"
                >
                  From Role
                </v-chip>
              </v-list-item-title>
              <v-list-item-subtitle class="text-body-2">{{ permission.description }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-card-text>
        
        <v-divider></v-divider>
        
        <v-card-actions class="pa-4">
          <div class="text-caption text-medium-emphasis">
            {{ selectedUserPermissions.length }} direct permissions assigned
          </div>
          <v-spacer></v-spacer>
          <v-btn 
            variant="elevated" 
            @click="closePermissionsDialog"
            color="primary"
            size="large"
          >
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Services Management Dialog -->
    <v-dialog v-model="servicesDialog" max-width="700px" persistent>
      <v-card>
        <v-card-title class="d-flex align-center bg-primary text-white">
          <span class="text-h6">Manage Services - {{ selectedUser?.name }}</span>
          <v-spacer></v-spacer>
          <v-btn 
            icon="mdi-close" 
            variant="text" 
            color="white"
            @click="closeServicesDialog"
          ></v-btn>
        </v-card-title>
        
        <v-divider></v-divider>
        
        <v-card-text class="pt-4">
          <v-alert type="warning" variant="tonal" class="mb-4">
            Services assigned to sellers determine their billing structure and available features.
          </v-alert>

          <div class="mb-4">
            <v-btn
              color="primary"
              variant="elevated"
              @click="openServiceForm"
              :loading="serviceLoading"
              prepend-icon="mdi-plus"
              size="large"
            >
              Assign New Service
            </v-btn>
          </div>

          <!-- Assigned Services -->
          <v-card v-if="userServices.length > 0" variant="outlined" class="mb-4" elevation="1">
            <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Assigned Services</v-card-title>
            <v-list>
              <v-list-item
                v-for="service in userServices"
                :key="service.id"
                class="border-b"
              >
                <v-list-item-title class="font-weight-medium">{{ service.name }}</v-list-item-title>
                <v-list-item-subtitle class="text-body-2">
                  ${{ service.price }}/{{ service.billing_cycle }} - {{ service.status }}
                </v-list-item-subtitle>
                
                <template #append>
                  <v-btn
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    color="error"
                    @click="removeService(service)"
                    :loading="serviceActionLoading === service.id"
                  ></v-btn>
                </template>
              </v-list-item>
            </v-list>
          </v-card>

          <!-- Service Assignment Form -->
          <v-expand-transition>
            <v-card v-if="showServiceForm" variant="outlined" elevation="2">
              <v-card-title class="text-subtitle-1 bg-grey-lighten-4">Assign Service</v-card-title>
              <v-card-text class="pt-4">
                <v-form ref="serviceForm" v-model="serviceFormValid">
                  <v-select
                    v-model="serviceFormData.service_id"
                    :items="availableServices"
                    item-title="name"
                    item-value="id"
                    label="Select Service"
                    :rules="[rules.required]"
                    variant="outlined"
                    class="mb-4"
                    color="primary"
                    bg-color="white"
                  >
                    <template #item="{ props, item }">
                      <v-list-item v-bind="props">
                        <v-list-item-title class="font-weight-medium">{{ item.raw.name }}</v-list-item-title>
                        <v-list-item-subtitle class="text-body-2">
                          ${{ item.raw.price }}/{{ item.raw.billing_cycle }}
                        </v-list-item-subtitle>
                      </v-list-item>
                    </template>
                  </v-select>
                  
                  <v-select
                    v-model="serviceFormData.status"
                    :items="serviceStatusOptions"
                    label="Service Status"
                    :rules="[rules.required]"
                    variant="outlined"
                    class="mb-4"
                    color="primary"
                    bg-color="white"
                  ></v-select>
                  
                  <v-textarea
                    v-model="serviceFormData.notes"
                    label="Notes (Optional)"
                    variant="outlined"
                    rows="2"
                    color="primary"
                    bg-color="white"
                  ></v-textarea>
                </v-form>
              </v-card-text>
              <v-card-actions class="pa-4">
                <v-spacer></v-spacer>
                <v-btn 
                  variant="outlined" 
                  @click="cancelServiceForm"
                  color="secondary"
                  size="large"
                >
                  Cancel
                </v-btn>
                <v-btn
                  color="primary"
                  :disabled="!serviceFormValid"
                  :loading="serviceLoading"
                  @click="assignService"
                  variant="elevated"
                  size="large"
                >
                  Assign Service
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-expand-transition>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card>
        <v-card-title class="text-h6 bg-error text-white">Confirm Delete</v-card-title>
        <v-card-text class="pt-4">
          <p class="text-body-1">
            Are you sure you want to delete user <strong>"{{ userToDelete?.name }}"</strong>? 
            This action cannot be undone.
          </p>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn 
            variant="outlined" 
            @click="deleteDialog = false"
            color="secondary"
            size="large"
          >
            Cancel
          </v-btn>
          <v-btn
            color="error"
            :loading="deletingUser === userToDelete?.id"
            @click="deleteUser"
            variant="elevated"
            size="large"
          >
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"
import { notify } from '@/utils/toast'
import axios from 'axios'
import { debounce } from 'lodash'
import { useCountriesStore } from '@/stores/countries'

// Configure Axios for Laravel Sanctum
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'

// Reactive data
const users = ref([])
const availableRoles = ref([])
const availablePermissions = ref([])
const availableServices = ref([])
const userServices = ref([])
const countriesStore = useCountriesStore()
const countries = ref([])
const countriesLoading = ref(false)
const error = ref(null)

// Pagination and filtering
const currentPage = ref(1)
const itemsPerPage = ref(10)
const totalUsers = ref(0)
const filters = ref({
  search: '',
  role: null,
  status: null
})

// Loading states
const loading = ref(false)
const formLoading = ref(false)
const updatingUser = ref(null)
const deletingUser = ref(null)
const roleActionLoading = ref(null)
const permissionActionLoading = ref(null)
const serviceLoading = ref(false)
const serviceActionLoading = ref(null)

// Dialog states
const userDialog = ref(false)
const rolesDialog = ref(false)
const permissionsDialog = ref(false)
const servicesDialog = ref(false)
const deleteDialog = ref(false)
const showServiceForm = ref(false)

// Form data
const userFormData = ref({
  name: '',
  email: '',
  password: '',
  country_id: '',
  status: '',
  is_active: true,
  client_name: '',
  username: '',
  role_id: '',
})
const serviceFormData = ref({
  service_id: null,
  status: 'active',
  notes: ''
})

// Form validation
const userFormValid = ref(false)
const serviceFormValid = ref(false)

// Selected data
const selectedUser = ref(null)
const selectedUserRoles = ref([])
const selectedUserPermissions = ref([])
const editingUserId = ref(null)
const userToDelete = ref(null)

// Search filters
const roleSearch = ref('')
const permissionSearch = ref('')

// Form refs
const userForm = ref(null)
const serviceForm = ref(null)

// Table headers
const headers = [
  { title: 'Name', value: 'name', sortable: true },
  { title: 'Email', value: 'email', sortable: true },
  { title: 'Country', value: 'country', sortable: true },
  { title: 'Status', value: 'status', sortable: true },
  { title: 'Roles', value: 'roles', sortable: false },
  { title: 'Permissions', value: 'permissions', sortable: false },
  { title: 'Created', value: 'created_at', sortable: true },
  { title: 'Actions', value: 'actions', sortable: false, width: '200px' }
]

// Options
const statusOptions = [
  { title: 'Active', value: 'Active' },
  { title: 'Inactive', value: 'Inactive' },
  { title: 'Ready', value: 'ready' },
  { title: 'Pending', value: 'pending' }
]

const serviceStatusOptions = [
  { title: 'Active', value: 'active' },
  { title: 'Suspended', value: 'suspended' },
  { title: 'Cancelled', value: 'cancelled' }
]

// Filter options
const statusFilterOptions = computed(() => [
  { title: 'All Statuses', value: null },
  ...statusOptions
])

const roleFilterOptions = computed(() => [
  { title: 'All Roles', value: null },
  ...availableRoles.value.map(role => ({ title: role.name, value: role.name }))
])

// Validation rules
const rules = {
  required: value => !!value || 'This field is required',
  minLength: value => (value && value.length >= 2) || 'Minimum 2 characters required',
  minPassword: value => (value && value.length >= 6) || 'Password must be at least 6 characters',
  email: value => {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return pattern.test(value) || 'Invalid email format'
  }
}

// Mock permission definitions for UI
const permissionDefinitions = [
  { key: 'create', name: 'Create', description: 'Create new records' },
  { key: 'read', name: 'Read', description: 'View and read records' },
  { key: 'update', name: 'Update', description: 'Modify existing records' },
  { key: 'delete', name: 'Delete', description: 'Remove records' },
  { key: 'manage_users', name: 'Manage Users', description: 'Full user management capabilities' },
  { key: 'system_config', name: 'System Configuration', description: 'Configure system settings' },
  { key: 'reports', name: 'Reports', description: 'Access and generate reports' }
]

// Computed properties
const isEditing = computed(() => editingUserId.value !== null)

const hasActiveFilters = computed(() => {
  return filters.value.search || filters.value.role || filters.value.status
})

const filteredAvailableRoles = computed(() => {
  if (!roleSearch.value) return availableRoles.value
  return availableRoles.value.filter(role =>
    role.name.toLowerCase().includes(roleSearch.value.toLowerCase())
  )
})

const filteredAvailablePermissions = computed(() => {
  if (!permissionSearch.value) return permissionDefinitions
  return permissionDefinitions.filter(permission =>
    permission.name.toLowerCase().includes(permissionSearch.value.toLowerCase()) ||
    permission.description.toLowerCase().includes(permissionSearch.value.toLowerCase())
  )
})

const roleBasedPermissions = computed(() => {
  if (!selectedUser.value || !availableRoles.value) return []
  
  const userRoleNames = selectedUserRoles.value
  const rolePermissions = []
  
  userRoleNames.forEach(roleName => {
    const role = availableRoles.value.find(r => r.name === roleName)
    if (role && role.permissions) {
      rolePermissions.push(...role.permissions)
    }
  })
  
  return [...new Set(rolePermissions)] // Remove duplicates
})

// Debounced search function
const debouncedSearch = debounce(() => {
  applyFilters()
}, 500)

// Utility functions
function getStatusColor(status) {
  const colors = {
    Active: 'success',
    Inactive: 'error',
    ready: 'success',
    pending: 'warning'
  }
  return colors[status] || 'grey'
}

function formatPermission(permission) {
  return permission.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

function hasSellerRole(user) {
  return user.roles.includes('Seller') || user.roles.includes('seller')
}

// Filter functions
function applyFilters() {
  currentPage.value = 1
  fetchUsers()
}

function clearFilters() {
  filters.value = {
    search: '',
    role: null,
    status: null
  }
  applyFilters()
}

function updateTableOptions(options) {
  if (options.page) currentPage.value = options.page
  if (options.itemsPerPage) itemsPerPage.value = options.itemsPerPage
  fetchUsers()
}

// API Functions with Axios and Laravel Sanctum
async function ensureCSRFToken() {
  try {
    await axios.get('/sanctum/csrf-cookie')
  } catch (error) {
    console.error('Failed to get CSRF token:', error)
  }
}


const loadCountries = async () => {
  try {
    countriesLoading.value = true
    await countriesStore.fetchCountries()
    countries.value = countriesStore.countries
      .filter(country => country.status === 1)
      .map(country => ({
        id: country.id,
        name: country.name,
        code: country.code,
        phone_code: country.phone_code
      }))
  } catch (error) {
    console.error('Failed to load countries:', error)
    countries.value = []
  } finally {
    countriesLoading.value = false
  } 
}
// fetch countries 

  const fetchCountries = async () => {
    loading.value = true
    error.value = null
    try {
      const { data } = await api.get('/api/v1/countries')
      countries.value = data.data || data
    } catch (err) {
      error.value = err.response?.data?.message || err.message
      console.error('Error fetching countries:', err)
      countries.value = []
    } finally {
      loading.value = false
    }
  }



async function fetchUsers() {
  loading.value = true
  try {
    await ensureCSRFToken()
    
    const params = {
      page: currentPage.value,
      per_page: itemsPerPage.value
    }
    
    // Add filters to params
    if (filters.value.search) params.search = filters.value.search
    if (filters.value.role) params.role = filters.value.role
    if (filters.value.status) params.status = filters.value.status
    
    const response = await axios.get('/api/v1/admin/users', { params })
    
    if (response.data.data) {
      users.value = response.data.data
      totalUsers.value = response.data.meta?.total || response.data.total || users.value.length
    } else {
      users.value = response.data
      totalUsers.value = response.data.length
    }
  } catch (error) {
    console.error('Error fetching users:', error)
    handleApiError(error, 'Failed to load users')
  } finally {
    loading.value = false
  }
}

async function fetchRoles() {
  try {
    const response = await axios.get('/api/v1/admin/roles')
    availableRoles.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching roles:', error)
    handleApiError(error, 'Failed to load roles')
  }
}

async function fetchUserPermissions(userId) {
  try {
    const response = await axios.get(`/api/v1/admin/users/${userId}/permissions`)
    return response.data.permissions || []
  } catch (error) {
    console.error('Error fetching user permissions:', error)
    handleApiError(error, 'Failed to load user permissions')
    return []
  }
}

async function assignRole(userId, roleName) {
  roleActionLoading.value = roleName
  updatingUser.value = userId
  
  try {
    await axios.post(`/api/v1/admin/users/${userId}/assign-role`, { role: roleName })
    
    // Update local user data
    const userIndex = users.value.findIndex(u => u.id === userId)
    if (userIndex !== -1) {
      if (!users.value[userIndex].roles.includes(roleName)) {
        users.value[userIndex].roles.push(roleName)
      }
    }
    
    notify.success(`Role "${roleName}" assigned successfully`, 'success')
  } catch (error) {
    console.error('Error assigning role:', error)
    handleApiError(error, `Failed to assign role "${roleName}"`)
  } finally {
    roleActionLoading.value = null
    updatingUser.value = null
  }
}

async function removeRole(userId, roleName) {
  roleActionLoading.value = roleName
  updatingUser.value = userId
  
  try {
    await axios.post(`/api/v1/admin/users/${userId}/remove-role`, { role: roleName })
    
    // Update local user data
    const userIndex = users.value.findIndex(u => u.id === userId)
    if (userIndex !== -1) {
      users.value[userIndex].roles = users.value[userIndex].roles.filter(r => r !== roleName)
    }
    
    notify.success(`Role "${roleName}" removed successfully`, 'success')
  } catch (error) {
    console.error('Error removing role:', error)
    handleApiError(error, `Failed to remove role "${roleName}"`)
  } finally {
    roleActionLoading.value = null
    updatingUser.value = null
  }
}

async function assignPermission(userId, permission) {
  permissionActionLoading.value = permission
  updatingUser.value = userId
  
  try {
    await axios.post(`/api/v1/admin/users/${userId}/assign-permission`, { permission })
    
    // Update local user data
    const userIndex = users.value.findIndex(u => u.id === userId)
    if (userIndex !== -1) {
      if (!users.value[userIndex].permissions.includes(permission)) {
        users.value[userIndex].permissions.push(permission)
      }
    }
    
    notify(`Permission "${permission}" assigned successfully`, 'success')
  } catch (error) {
    console.error('Error assigning permission:', error)
    handleApiError(error, `Failed to assign permission "${permission}"`)
  } finally {
    permissionActionLoading.value = null
    updatingUser.value = null
  }
}

async function removePermission(userId, permission) {
  permissionActionLoading.value = permission
  updatingUser.value = userId
  
  try {
    await axios.post(`/api/v1/admin/users/${userId}/remove-permission`, { permission })
    
    // Update local user data
    const userIndex = users.value.findIndex(u => u.id === userId)
    if (userIndex !== -1) {
      users.value[userIndex].permissions = users.value[userIndex].permissions.filter(p => p !== permission)
    }
    
    notify.success(`Permission "${permission}" removed successfully`, 'success')
  } catch (error) {
    console.error('Error removing permission:', error)
    handleApiError(error, `Failed to remove permission "${permission}"`)
  } finally {
    permissionActionLoading.value = null
    updatingUser.value = null
  }
}

async function createUser() {
  formLoading.value = true
  try {
    const response = await axios.post('/api/v1/admin/users', userFormData.value)
    
    const newUser = response.data.data || response.data
    await fetchUsers()
    notify.success('User created successfully', 'success')
    closeUserDialog()
  } catch (error) {
    console.error('Error creating user:', error)
    handleApiError(error, 'Failed to create user')
  } finally {
    formLoading.value = false
  }
}

async function updateUser() {
  formLoading.value = true
  updatingUser.value = editingUserId.value
  
  try {
    const updateData = { ...userFormData.value }
    if (!updateData.password) {
      delete updateData.password // Don't send empty password
    }
    
    const response = await axios.put(`/api/v1/admin/users/${editingUserId.value}`, updateData)
    
    const updatedUser = response.data.data || response.data
    const index = users.value.findIndex(u => u.id === editingUserId.value)
    if (index !== -1) {
      users.value[index] = updatedUser
    }
    
    notify.success('User updated successfully', 'success')
    closeUserDialog()
  } catch (error) {
    console.error('Error updating user:', error)
    handleApiError(error, 'Failed to update user')
  } finally {
    formLoading.value = false
    updatingUser.value = null
  }
}   

async function deleteUser() {
  if (!userToDelete.value) return
  
  deletingUser.value = userToDelete.value.id
  try {
    await axios.delete(`/api/v1/admin/users/${userToDelete.value.id}`)
    
    users.value = users.value.filter(u => u.id !== userToDelete.value.id)
    await fetchUsers()
    notify('User deleted successfully', 'success')
    deleteDialog.value = false
    userToDelete.value = null
  } catch (error) {
    console.error('Error deleting user:', error)
    handleApiError(error, 'Failed to delete user')
  } finally {
    deletingUser.value = null
  }
}

async function fetchAvailableServices() {
  try {
    const response = await axios.get('/api/v1/admin/services')
    availableServices.value = response.data.data || response.data
  } catch (error) {
    console.error('Error fetching services:', error)
    handleApiError(error, 'Failed to load services')
  }
}

async function fetchUserServices(userId) {
  serviceLoading.value = true
  try {
    const response = await axios.get(`/api/v1/admin/users/${userId}/services`)
    userServices.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Error fetching user services:', error)
    handleApiError(error, 'Failed to load user services')
    userServices.value = []
  } finally {
    serviceLoading.value = false
  }
}       

async function assignService() {
  if (!selectedUser.value) return
  serviceLoading.value = true
  
  try {
    const response = await axios.post(`/api/v1/admin/users/${selectedUser.value.id}/assign-service`, serviceFormData.value)
    
    const newService = response.data.data || response.data
    userServices.value.push(newService)
    notify('Service assigned successfully', 'success')
    cancelServiceForm()
  } catch (error) {
    console.error('Error assigning service:', error)
    handleApiError(error, 'Failed to assign service')
  } finally {
    serviceLoading.value = false
  }
}

async function removeService(service) {
  if (!selectedUser.value || !service) return
  serviceActionLoading.value = service.id   
  try {
    await axios.post(`/api/v1/admin/users/${selectedUser.value.id}/remove-service`, { 
      service_id: service.id 
    })
    
    userServices.value = userServices.value.filter(s => s.id !== service.id)
    notify('Service removed successfully', 'success')
  } catch (error) {
    console.error('Error removing service:', error)
    handleApiError(error, 'Failed to remove service')
  } finally {
    serviceActionLoading.value = null
  }
}

// Error handling function
function handleApiError(error, defaultMessage) {
  let message = defaultMessage
  
  if (error.response) {
    // Server responded with error status
    const { status, data } = error.response
    
    if (status === 422 && data.errors) {
      // Validation errors
      const errors = Object.values(data.errors).flat()
      message = errors.join(', ')
    } else if (data.message) {
      message = data.message
    } else if (status === 401) {
      message = 'Authentication required. Please log in again.'
      // Optionally redirect to login
      // window.location.href = '/login'
    } else if (status === 403) {
      message = 'You do not have permission to perform this action.'
    } else if (status >= 500) {
      message = 'Server error. Please try again later.'
    }
  } else if (error.request) {
    // Network error
    message = 'Network error. Please check your connection and try again.'
  }
  
  notify.success(message, 'error')
}

// Dialog functions
function openCreateDialog() {
  editingUserId.value = null
  userFormData.value = {
    name: '',
    email: '',
    password: '',
    country: '',
    status: 'Active'
  }
  userDialog.value = true
}

function openEditDialog(user) {
  editingUserId.value = user.id
  userFormData.value = {
    name: user.name,
    email: user.email,
    password: '',
    country: user.country || '',
    status: user.status
  }
  userDialog.value = true
}

function closeUserDialog() {
  userDialog.value = false
  editingUserId.value = null
  if (userForm.value && typeof userForm.value.resetValidation === 'function') {
    userForm.value.resetValidation()
  }
}

async function saveUser() {
  if (!userFormValid.value) return
  
  if (isEditing.value) {
    await updateUser()
  } else {
    await createUser()
  }
}

async function openRolesDialog(user) {
  selectedUser.value = user
  selectedUserRoles.value = [...user.roles]
  rolesDialog.value = true
  
  if (availableRoles.value.length === 0) {
    await fetchRoles()
  }
}

function closeRolesDialog() {
  rolesDialog.value = false
  selectedUser.value = null
  selectedUserRoles.value = []
  roleSearch.value = ''
}

async function toggleRole(roleName, isAssigned) {
  if (!selectedUser.value) return
  
  if (isAssigned) {
    selectedUserRoles.value.push(roleName)
    await assignRole(selectedUser.value.id, roleName)
  } else {
    selectedUserRoles.value = selectedUserRoles.value.filter(r => r !== roleName)
    await removeRole(selectedUser.value.id, roleName)
  }
}

async function openPermissionsDialog(user) {
  selectedUser.value = user
  selectedUserPermissions.value = await fetchUserPermissions(user.id)
  selectedUserRoles.value = [...user.roles]
  permissionsDialog.value = true
  
  if (availableRoles.value.length === 0) {
    await fetchRoles()
  }
}

function closePermissionsDialog() {
  permissionsDialog.value = false
  selectedUser.value = null
  selectedUserPermissions.value = []
  selectedUserRoles.value = []
  permissionSearch.value = ''
}

async function togglePermission(permission, isAssigned) {
  if (!selectedUser.value) return
  
  if (isAssigned) {
    selectedUserPermissions.value.push(permission)
    await assignPermission(selectedUser.value.id, permission)
  } else {
    selectedUserPermissions.value = selectedUserPermissions.value.filter(p => p !== permission)
    await removePermission(selectedUser.value.id, permission)
  }
}

async function openServicesDialog(user) {
  selectedUser.value = user
  servicesDialog.value = true
  
  await Promise.all([
    fetchUserServices(user.id),
    availableServices.value.length === 0 ? fetchAvailableServices() : Promise.resolve()
  ])
}

function closeServicesDialog() {
  servicesDialog.value = false
  selectedUser.value = null
  userServices.value = []
  showServiceForm.value = false
}

function openServiceForm() {
  showServiceForm.value = true
  serviceFormData.value = {
    service_id: null,
    status: 'active',
    notes: ''
  }
}

function cancelServiceForm() {
  showServiceForm.value = false
  if (serviceForm.value && typeof serviceForm.value.resetValidation === 'function') {
    serviceForm.value.resetValidation()
  }
}

function confirmDelete(user) {
  userToDelete.value = user
  deleteDialog.value = true
}

// Initialize component
onMounted(async () => {
  await Promise.all([
    fetchUsers(),
    fetchRoles(),
    loadCountries(),
  ])
})
</script>

<style scoped>
/* Custom styles to ensure visibility */
.v-btn {
  color: inherit !important;
}

.v-btn--variant-text {
  opacity: 1 !important;
}

.v-btn--variant-outlined {
  border: 1px solid currentColor !important;
}

.v-chip {
  opacity: 1 !important;
}

.v-list-item {
  background-color: white !important;
  margin-bottom: 8px;
  border-radius: 8px;
}

.v-text-field,
.v-select {
  background-color: white !important;
}

.cursor-pointer {
  cursor: pointer !important;
}

.gap-1 {
  gap: 4px;
}

.border-b {
  border-bottom: 1px solid rgba(0,0,0,0.12);
}

/* Ensure action buttons are visible */
.v-data-table .v-btn {
  min-width: 32px !important;
  height: 32px !important;
}

/* Fix tooltip positioning */
.v-tooltip .v-overlay__content {
  z-index: 9999 !important;
}

/* Improve contrast for better visibility */
.v-card-title {
  color: white !important;
}

.text-primary {
  color: rgb(var(--v-theme-primary)) !important;
}

/* Ensure form fields are visible */
.v-field {
  background-color: white !important;
}

.v-field__outline {
  opacity: 1 !important;
}
</style>