<template>
  <AppLayout>
    <v-container>
      <!-- Header with Create Button -->
      <v-row class="mb-4 align-center">
        <v-col>
          <h2 class="text-h4">Warehouse Management</h2>
        </v-col>
        <v-col cols="auto">
          <v-btn
            color="primary"
            prepend-icon="mdi-plus"
            @click="openCreateDialog"
            :loading="loading"
          >
            Create Warehouse
          </v-btn>
        </v-col>
      </v-row>

      <!-- Search and Filters -->
      <v-card class="mb-4" variant="outlined">
        <v-card-title class="text-subtitle-1 pb-2">
          <v-icon left>mdi-filter</v-icon>
          Search & Filter
        </v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filters.search"
                label="Search warehouses..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="compact"
                clearable
                hint="Search by warehouse name or location"
                persistent-hint
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="4">
              <v-autocomplete
                v-model="filters.country"
                label="Country"
                :items="countryOptions"
                item-title="name"
                item-value="id"
                variant="outlined"
                density="compact"
                clearable
              ></v-autocomplete>
            </v-col>
            <v-col cols="12" md="4">
              <v-autocomplete
                v-model="filters.city"
                label="City"
                :items="cityOptions"
                item-title="name"
                item-value="id"
                variant="outlined"
                density="compact"
                clearable
                :disabled="!filters.country"
              ></v-autocomplete>
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Warehouses Data Table -->
      <v-card>
        <v-card-title class="text-subtitle-1">
          <v-icon left>mdi-warehouse</v-icon>
          All Warehouses ({{ filteredWarehouses.length }})
        </v-card-title>
        <v-divider></v-divider>
        
        <v-data-table
          :headers="headers"
          :items="filteredWarehouses"
          :loading="loading"
          item-value="id"
          class="elevation-0"
          :items-per-page="25"
        >
          <!-- Warehouse Name Column -->
          <template #item.name="{ item }">
            <div class="d-flex align-center">
              <v-icon 
                icon="mdi-warehouse" 
                color="primary"
                class="mr-2"
                size="small"
              ></v-icon>
              <div>
                <div class="font-weight-medium">{{ item.name || 'Unnamed Warehouse' }}</div>
                <div class="text-caption text-medium-emphasis">ID: {{ item.id }}</div>
              </div>
            </div>
          </template>

          <!-- Location Column -->
          <template #item.location="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.location || 'No location specified' }}</div>
              <div class="text-caption text-medium-emphasis">
                {{ item.country?.name || 'Unknown Country' }}
              </div>
            </div>
          </template>

          <!-- Contact Info Column -->
          <template #item.contact="{ item }">
            <div v-if="item.contact_person || item.phone">
              <div class="font-weight-medium">{{ item.contact_person || 'No contact person' }}</div>
              <div class="text-caption text-medium-emphasis">{{ item.phone || 'No phone' }}</div>
            </div>
            <span v-else class="text-caption text-medium-emphasis">No contact info</span>
          </template>

          <!-- Country Column -->
          <template #item.country="{ item }">
            <v-chip
              color="info"
              size="small"
              variant="outlined"
            >
              {{ item.country?.name || 'Unknown' }}
            </v-chip>
          </template>

          <!-- Actions Column -->
          <template #item.actions="{ item }">
            <div class="d-flex gap-1">
              <v-btn
                icon="mdi-eye"
                size="small"
                variant="text"
                color="info"
                @click="openViewDialog(item)"
              ></v-btn>
              <v-btn
                icon="mdi-pencil"
                size="small"
                variant="text"
                color="primary"
                @click="openEditDialog(item)"
                :loading="updatingWarehouse === item.id"
              ></v-btn>
              <v-btn
                icon="mdi-delete"
                size="small"
                variant="text"
                color="error"
                @click="openDeleteDialog(item)"
                :loading="deletingWarehouse === item.id"
              ></v-btn>
            </div>
          </template>

          <!-- Loading State -->
          <template #loading>
            <v-skeleton-loader type="table-row@10"></v-skeleton-loader>
          </template>

          <!-- No Data State -->
          <template #no-data>
            <div class="text-center py-8">
              <v-icon size="64" color="grey-lighten-1">mdi-warehouse-off</v-icon>
              <div class="text-h6 mt-4">No warehouses found</div>
              <div class="text-caption">Create your first warehouse to get started</div>
            </div>
          </template>
        </v-data-table>
      </v-card>

      <!-- Create/Edit Warehouse Dialog -->
      <v-dialog v-model="warehouseDialog" max-width="800px" persistent>
        <v-card>
          <v-card-title>
            <span class="text-h5">
              {{ isEditing ? 'Edit Warehouse' : 'Create New Warehouse' }}
            </span>
          </v-card-title>

          <v-form ref="warehouseForm" v-model="warehouseFormValid" @submit.prevent="saveWarehouse">
            <v-card-text>
              <v-container>
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="warehouseFormData.name"
                      label="Warehouse Name *"
                      :rules="[rules.required]"
                      variant="outlined"
                      density="comfortable"
                      placeholder="e.g., Main Warehouse, Storage Unit A"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="warehouseFormData.location"
                      label="Location"
                      variant="outlined"
                      density="comfortable"
                      placeholder="Street address or area"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-autocomplete
                      v-model="warehouseFormData.country_id"
                      label="Country *"
                      :items="countryOptions"
                      item-title="name"
                      item-value="id"
                      :rules="[rules.required]"
                      variant="outlined"
                      density="comfortable"
                      @update:model-value="onCountryChange"
                    ></v-autocomplete>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-autocomplete
                      v-model="warehouseFormData.city_id"
                      label="City"
                      :items="cityOptions"
                      item-title="name"
                      item-value="id"
                      variant="outlined"
                      density="comfortable"
                      :disabled="!warehouseFormData.country_id"
                    ></v-autocomplete>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="warehouseFormData.contact_person"
                      label="Contact Person"
                      variant="outlined"
                      density="comfortable"
                      placeholder="Manager or responsible person"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="warehouseFormData.phone"
                      label="Phone Number"
                      variant="outlined"
                      density="comfortable"
                      placeholder="+1234567890"
                    ></v-text-field>
                  </v-col>

                  <v-col cols="12" md="6">
                    <v-select
                      v-model="warehouseFormData.zone_id"
                      label="Zone"
                      :items="zoneOptions"
                      item-title="name"
                      item-value="id"
                      variant="outlined"
                      density="comfortable"
                      clearable
                    ></v-select>
                  </v-col>
                </v-row>
              </v-container>
            </v-card-text>

            <v-card-actions>
              <v-spacer></v-spacer>
              <v-btn
                variant="text"
                @click="closeWarehouseDialog"
                :disabled="formLoading"
              >
                Cancel
              </v-btn>
              <v-btn
                color="primary"
                variant="flat"
                type="submit"
                :loading="formLoading"
                :disabled="!warehouseFormValid"
              >
                {{ isEditing ? 'Update' : 'Create' }}
              </v-btn>
            </v-card-actions>
          </v-form>
        </v-card>
      </v-dialog>

      <!-- View Warehouse Dialog -->
      <v-dialog v-model="viewDialog" max-width="600px">
        <v-card v-if="viewingWarehouse">
          <v-card-title>
            <v-icon left>mdi-warehouse</v-icon>
            Warehouse Details
          </v-card-title>
          <v-card-text>
            <v-container>
              <v-row>
                <v-col cols="12">
                  <div class="text-h6 mb-2">{{ viewingWarehouse.name || 'Unnamed Warehouse' }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-subtitle-2 mb-1">Location</div>
                  <div>{{ viewingWarehouse.location || 'Not specified' }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-subtitle-2 mb-1">Country</div>
                  <div>{{ viewingWarehouse.country?.name || 'Unknown' }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-subtitle-2 mb-1">Contact Person</div>
                  <div>{{ viewingWarehouse.contact_person || 'Not specified' }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-subtitle-2 mb-1">Phone</div>
                  <div>{{ viewingWarehouse.phone || 'Not specified' }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-subtitle-2 mb-1">Created</div>
                  <div>{{ formatDate(viewingWarehouse.created_at) }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-subtitle-2 mb-1">Updated</div>
                  <div>{{ formatDate(viewingWarehouse.updated_at) }}</div>
                </v-col>
              </v-row>
            </v-container>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn variant="text" @click="viewDialog = false">Close</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Delete Confirmation Dialog -->
      <v-dialog v-model="deleteDialog" max-width="400px">
        <v-card>
          <v-card-title class="text-h5">
            Confirm Delete
          </v-card-title>
          <v-card-text>
            Are you sure you want to delete the warehouse 
            <strong>"{{ warehouseToDelete?.name || 'Unnamed Warehouse' }}"</strong>?
            <br><br>
            <v-alert color="warning" variant="outlined" class="mt-3">
              This action cannot be undone and may affect related inventory and operations.
            </v-alert>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn
              variant="text"
              @click="closeDeleteDialog"
              :disabled="deletingWarehouse"
            >
              Cancel
            </v-btn>
            <v-btn
              color="error"
              variant="flat"
              @click="deleteWarehouse"
              :loading="deletingWarehouse"
            >
              Delete
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"
import { notify } from '@/utils/toast'
import axios from 'axios'

axios.defaults.withCredentials = true
axios.defaults.headers.common['Accept'] = 'application/json'

// Data
const warehouses = ref([])
const countries = ref([])
const cities = ref([])
const zones = ref([])
const loading = ref(false)
const formLoading = ref(false)
const updatingWarehouse = ref(null)
const deletingWarehouse = ref(null)

// Dialogs
const warehouseDialog = ref(false)
const viewDialog = ref(false)
const deleteDialog = ref(false)

// Form data
const warehouseFormData = ref({
  name: '',
  location: '',
  country_id: null,
  city_id: null,
  zone_id: null,
  contact_person: '',
  phone: ''
})
const warehouseFormValid = ref(false)
const editingWarehouseId = ref(null)
const warehouseToDelete = ref(null)
const viewingWarehouse = ref(null)

// Filters
const filters = ref({
  search: '',
  country: '',
  city: ''
})

// Form reference
const warehouseForm = ref(null)

// Table headers
const headers = [
  { title: 'Warehouse', value: 'name', sortable: true },
  { title: 'Location', value: 'location', sortable: true },
  { title: 'Contact', value: 'contact', sortable: false },
  { title: 'Country', value: 'country', sortable: true },
  { title: 'Actions', value: 'actions', sortable: false, width: '150px' }
]

// Form validation rules
const rules = {
  required: value => !!value || 'This field is required'
}

// Options
const countryOptions = computed(() => countries.value)
const cityOptions = computed(() => cities.value)
const zoneOptions = computed(() => zones.value)

// Computed
const isEditing = computed(() => editingWarehouseId.value !== null)

const filteredWarehouses = computed(() => {
  let filtered = warehouses.value

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(warehouse =>
      (warehouse.name && warehouse.name.toLowerCase().includes(search)) ||
      (warehouse.location && warehouse.location.toLowerCase().includes(search)) ||
      (warehouse.contact_person && warehouse.contact_person.toLowerCase().includes(search))
    )
  }

  if (filters.value.country) {
    filtered = filtered.filter(warehouse => warehouse.country_id === filters.value.country)
  }

  if (filters.value.city) {
    filtered = filtered.filter(warehouse => warehouse.city_id === filters.value.city)
  }

  return filtered
})

// Methods
async function fetchWarehouses() {
  loading.value = true
  try {
    const { data } = await axios.get('/api/v1/warehouses')
    warehouses.value = data
  } catch (error) {
    notify.error('Failed to load warehouses')
    console.error('Error fetching warehouses:', error)
  } finally {
    loading.value = false
  }
}

async function fetchCountries() {
  try {
    const { data } = await axios.get('/api/v1/countries')
    countries.value = data
  } catch (error) {
    notify.error('Failed to load countries')
    console.error('Error fetching countries:', error)
  }
}

async function fetchCities(countryId = null) {
  try {
    const url = countryId ? `/api/v1/cities?country_id=${countryId}` : '/api/v1/admin/cities'
    const { data } = await axios.get(url)
    cities.value = data
  } catch (error) {
    notify.error('Failed to load cities')
    console.error('Error fetching cities:', error)
  }
}

async function fetchZones() {
  try {
    const { data } = await axios.get('/api/v1/zones')
    zones.value = data
  } catch (error) {
    console.error('Error fetching zones:', error)
    // Zones might be optional, so don't show error notification
  }
}

async function createWarehouse() {
  formLoading.value = true
  try {
    const { data } = await axios.post('/api/v1/warehouses', warehouseFormData.value)
    warehouses.value.push(data)
    notify.success('Warehouse created successfully')
    closeWarehouseDialog()
  } catch (error) {
    notify.error('Failed to create warehouse')
    console.error('Error creating warehouse:', error)
  } finally {
    formLoading.value = false
  }
}

async function updateWarehouse() {
  formLoading.value = true
  updatingWarehouse.value = editingWarehouseId.value
  try {
    const { data } = await axios.put(
      `/api/v1/warehouses/${editingWarehouseId.value}`,
      warehouseFormData.value
    )
    const index = warehouses.value.findIndex(w => w.id === editingWarehouseId.value)
    if (index !== -1) {
      warehouses.value[index] = data
    }
    notify.success('Warehouse updated successfully')
    closeWarehouseDialog()
  } catch (error) {
    notify.error('Failed to update warehouse')
    console.error('Error updating warehouse:', error)
  } finally {
    formLoading.value = false
    updatingWarehouse.value = null
  }
}

async function deleteWarehouse() {
  deletingWarehouse.value = warehouseToDelete.value.id
  try {
    await axios.delete(`/api/v1/admin/warehouses/${warehouseToDelete.value.id}`)
    warehouses.value = warehouses.value.filter(w => w.id !== warehouseToDelete.value.id)
    notify.success('Warehouse deleted successfully')
    closeDeleteDialog()
  } catch (error) {
    notify.error('Failed to delete warehouse')
    console.error('Error deleting warehouse:', error)
  } finally {
    deletingWarehouse.value = null
  }
}

function saveWarehouse() {
  if (!warehouseForm.value.validate()) return
  if (isEditing.value) {
    updateWarehouse()
  } else {
    createWarehouse()
  }
}

function openCreateDialog() {
  resetForm()
  editingWarehouseId.value = null
  warehouseDialog.value = true
}

function openEditDialog(warehouse) {
  resetForm()
  warehouseFormData.value = {
    name: warehouse.name || '',
    location: warehouse.location || '',
    country_id: warehouse.country_id,
    city_id: warehouse.city_id,
    zone_id: warehouse.zone_id,
    contact_person: warehouse.contact_person || '',
    phone: warehouse.phone || ''
  }
  editingWarehouseId.value = warehouse.id
  warehouseDialog.value = true
}

function openViewDialog(warehouse) {
  viewingWarehouse.value = warehouse
  viewDialog.value = true
}

function openDeleteDialog(warehouse) {
  warehouseToDelete.value = warehouse
  deleteDialog.value = true
}

function closeWarehouseDialog() {
  warehouseDialog.value = false
  resetForm()
  editingWarehouseId.value = null
}

function closeDeleteDialog() {
  deleteDialog.value = false
  warehouseToDelete.value = null
}

function resetForm() {
  warehouseFormData.value = {
    name: '',
    location: '',
    country_id: null,
    city_id: null,
    zone_id: null,
    contact_person: '',
    phone: ''
  }
  if (warehouseForm.value) {
    warehouseForm.value.resetValidation()
  }
}

function onCountryChange() {
  warehouseFormData.value.city_id = null
  if (warehouseFormData.value.country_id) {
    fetchCities(warehouseFormData.value.country_id)
  }
}

function formatDate(dateString) {
  if (!dateString) return 'Not available'
  return new Date(dateString).toLocaleDateString()
}

// Lifecycle
onMounted(() => {
  fetchWarehouses()
  fetchCountries()
  fetchCities()
  fetchZones()
})
</script>

<style scoped>
.v-data-table {
  background-color: transparent;
}
</style>