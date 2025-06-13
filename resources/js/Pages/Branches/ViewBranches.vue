<template>
  <AppLayout title="Countries Management">
    <v-container fluid>
      <!-- Header with Add Button -->
      <v-row class="mb-4">
        <v-col>
          <div class="d-flex justify-space-between align-center">
            <h2 class="text-h4">Countries</h2>
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              @click="openCreateDialog"
            >
              Add Country
            </v-btn>
          </div>
        </v-col>
      </v-row>

      <!-- Search Bar -->
      <v-row class="mb-4">
        <v-col cols="12" md="4">
          <v-text-field
            v-model="searchTerm"
            prepend-inner-icon="mdi-magnify"
            label="Search countries..."
            variant="outlined"
            density="compact"
            clearable
          />
        </v-col>
      </v-row>

      <!-- Data Table -->
      <v-card>
        <v-data-table
          :headers="headers"
          :items="filteredCountries"
          :loading="countriesStore.loading"
          :items-per-page="10"
          class="elevation-1"
        >
          <!-- Status Column -->
          <template #item.is_active="{ item }">
            <v-chip
              :color="item.is_active ? 'success' : 'error'"
              size="small"
            >
              {{ item.is_active ? 'Active' : 'Inactive' }}
            </v-chip>
          </template>

          <!-- Actions Column -->
          <template #item.actions="{ item }">
            <v-btn
              icon="mdi-pencil"
              variant="text"
              size="small"
              color="primary"
              @click="openEditDialog(item)"
            />
            <v-btn
              icon="mdi-delete"
              variant="text"
              size="small"
              color="error"
              @click="openDeleteDialog(item)"
            />
          </template>

          <!-- No Data -->
          <template #no-data>
            <div class="text-center pa-4">
              <v-icon size="64" color="grey">mdi-database-off</v-icon>
              <p class="text-h6 mt-2">No countries found</p>
            </div>
          </template>
        </v-data-table>
      </v-card>

      <!-- Create/Edit Dialog -->
      <v-dialog v-model="dialog" max-width="500px" persistent>
        <v-card>
          <v-card-title class="text-h5">
            {{ isEditing ? 'Edit Country' : 'Add New Country' }}
          </v-card-title>
          
          <v-card-text>
            <v-form ref="form" v-model="formValid">
              <v-text-field
                v-model="formData.name"
                label="Country Name"
                :rules="nameRules"
                variant="outlined"
                required
              />
              
              <v-text-field
                v-model="formData.code"
                label="Country Code (ISO 3166-1 alpha-3)"
                :rules="codeRules"
                variant="outlined"
                maxlength="3"
                counter="3"
                required
              />
              
              <v-text-field
                v-model="formData.phone_code"
                label="Phone Code"
                :rules="phoneCodeRules"
                variant="outlined"
                placeholder="+1"
                required
              />
              
              <v-switch
                v-model="formData.is_active"
                label="Active Status"
                color="primary"
                inset
              />
            </v-form>
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn
              text
              @click="closeDialog"
            >
              Cancel
            </v-btn>
            <v-btn
              color="primary"
              :loading="countriesStore.loading"
              :disabled="!formValid"
              @click="saveCountry"
            >
              {{ isEditing ? 'Update' : 'Create' }}
            </v-btn>
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
            Are you sure you want to delete <strong>{{ countryToDelete?.name }}</strong>?
            This action cannot be undone.
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn
              text
              @click="deleteDialog = false"
            >
              Cancel
            </v-btn>
            <v-btn
              color="error"
              :loading="countriesStore.loading"
              @click="confirmDelete"
            >
              Delete
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Snackbar for notifications -->
      <v-snackbar
        v-model="snackbar.show"
        :color="snackbar.color"
        :timeout="3000"
        top
      >
        {{ snackbar.message }}
        <template #actions>
          <v-btn
            text
            @click="snackbar.show = false"
          >
            Close
          </v-btn>
        </template>
      </v-snackbar>
    </v-container>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useCountriesStore } from '@/stores/countries'
import AppLayout from '@/Layouts/AppLayout.vue'

// Store
const countriesStore = useCountriesStore()

// Reactive data
const searchTerm = ref('')
const dialog = ref(false)
const deleteDialog = ref(false)
const formValid = ref(false)
const form = ref(null)
const isEditing = ref(false)
const countryToDelete = ref(null)

// Form data
const formData = ref({
  id: null,
  name: '',
  code: '',
  phone_code: '',
  is_active: true
})

// Snackbar
const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

// Table headers
const headers = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Code', key: 'code', sortable: true },
  { title: 'Phone Code', key: 'phone_code', sortable: true },
  { title: 'Status', key: 'is_active', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false, width: '120px' }
]

// Validation rules
const nameRules = [
  v => !!v || 'Country name is required',
  v => (v && v.length >= 2) || 'Country name must be at least 2 characters'
]

const codeRules = [
  v => !!v || 'Country code is required',
  v => (v && v.length === 3) || 'Country code must be exactly 3 characters',
  v => /^[A-Z]{3}$/.test(v) || 'Country code must be 3 uppercase letters'
]

const phoneCodeRules = [
  v => !!v || 'Phone code is required',
  v => /^\+\d{1,4}$/.test(v) || 'Phone code must start with + followed by 1-4 digits'
]

// Computed
const filteredCountries = computed(() => {
  if (!searchTerm.value) {
    return countriesStore.countries
  }
  
  const search = searchTerm.value.toLowerCase()
  return countriesStore.countries.filter(country =>
    country.name.toLowerCase().includes(search) ||
    country.code.toLowerCase().includes(search) ||
    country.phone_code.includes(search)
  )
})

// Methods
const openCreateDialog = () => {
  isEditing.value = false
  resetForm()
  dialog.value = true
}

const openEditDialog = (country) => {
  isEditing.value = true
  formData.value = { ...country }
  dialog.value = true
}

const openDeleteDialog = (country) => {
  countryToDelete.value = country
  deleteDialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  resetForm()
}

const resetForm = () => {
  formData.value = {
    id: null,
    name: '',
    code: '',
    phone_code: '',
    is_active: true
  }
  
  nextTick(() => {
    if (form.value) {
      form.value.resetValidation()
    }
  })
}

const saveCountry = async () => {
  if (!formValid.value) return

  try {
    if (isEditing.value) {
      await countriesStore.updateCountry(formData.value.id, formData.value)
      showSnackbar('Country updated successfully', 'success')
    } else {
      await countriesStore.createCountry(formData.value)
      showSnackbar('Country created successfully', 'success')
    }
    
    closeDialog()
  } catch (error) {
    showSnackbar(error.message || 'An error occurred', 'error')
  }
}

const confirmDelete = async () => {
  try {
    await countriesStore.deleteCountry(countryToDelete.value.id)
    showSnackbar('Country deleted successfully', 'success')
    deleteDialog.value = false
    countryToDelete.value = null
  } catch (error) {
    showSnackbar(error.message || 'An error occurred', 'error')
  }
}

const showSnackbar = (message, color = 'success') => {
  snackbar.value = {
    show: true,
    message,
    color
  }
}

// Lifecycle
onMounted(() => {
  countriesStore.fetchCountries()
})
</script>

<style scoped>
.v-data-table {
  background-color: transparent;
}
</style>