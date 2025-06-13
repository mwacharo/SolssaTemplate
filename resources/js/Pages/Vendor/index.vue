<script setup>
import { ref, onMounted, computed } from 'vue'
import { useVendorStore } from '@/stores/vendorStore'
import AppLayout from '@/Layouts/AppLayout.vue'

const vendorStore = useVendorStore()

// Reactive data
const dialog = ref(false)
const deleteDialog = ref(false)
const isEditing = ref(false)
const selectedVendor = ref(null)
const search = ref('')
const loading = ref(false)

// Form data with all vendor fields
const formData = ref({
  id: null,
  name: '',
  company_name: '',
  email: '',
  billing_email: '',
  phone: '',
  alt_phone: '',
  address: '',
  city: '',
  state: '',
  zip_code: '',
  country: '',
  region: '',
  warehouse_location: '',
  preferred_pickup_time: '',
  contact_person_name: '',
  business_type: '',
  registration_number: '',
  tax_id: '',
  website_url: '',
  social_media_links: {
    facebook: '',
    twitter: '',
    linkedin: '',
    instagram: ''
  },
  bank_account_info: {
    bank_name: '',
    account_number: '',
    routing_number: '',
    swift_code: ''
  },
  delivery_mode: 'both',
  payment_terms: '',
  credit_limit: 0.00,
  integration_id: '',
  onboarding_stage: 'pending',
  last_active_at: null,
  rating: null,
  status: true,
  notes: '',
  user_id: 1 // This should come from auth
})

// Form validation rules
const rules = {
  name: [v => !!v || 'Name is required'],
  email: [
    v => !!v || 'Email is required',
    v => /.+@.+\..+/.test(v) || 'Email must be valid'
  ],
  phone: [v => !!v || 'Phone is required'],
  address: [v => !!v || 'Address is required']
}

// Table headers
const headers = [
  { title: 'Name', key: 'name', sortable: true },
  { title: 'Company', key: 'company_name', sortable: true },
  { title: 'Email', key: 'email', sortable: true },
  { title: 'Phone', key: 'phone', sortable: true },
  { title: 'City', key: 'city', sortable: true },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Stage', key: 'onboarding_stage', sortable: true },
  { title: 'Rating', key: 'rating', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false }
]

// Computed
const filteredVendors = computed(() => {
  if (!search.value) return vendorStore.vendors
  return vendorStore.vendors.filter(vendor =>
    vendor.name.toLowerCase().includes(search.value.toLowerCase()) ||
    vendor.email.toLowerCase().includes(search.value.toLowerCase()) ||
    vendor.company_name?.toLowerCase().includes(search.value.toLowerCase())
  )
})

// Options for select fields
const deliveryModeOptions = [
  { title: 'Pickup', value: 'pickup' },
  { title: 'Delivery', value: 'delivery' },
  { title: 'Both', value: 'both' }
]

const onboardingStageOptions = [
  { title: 'Pending', value: 'pending' },
  { title: 'Active', value: 'active' },
  { title: 'Verified', value: 'verified' }
]

const businessTypeOptions = [
  'Retailer',
  'Wholesaler',
  'Distributor',
  'Manufacturer',
  'Service Provider',
  'Other'
]

// Methods
const openDialog = (vendor = null) => {
  if (vendor) {
    isEditing.value = true
    formData.value = { 
      ...vendor,
      social_media_links: vendor.social_media_links || {
        facebook: '',
        twitter: '',
        linkedin: '',
        instagram: ''
      },
      bank_account_info: vendor.bank_account_info || {
        bank_name: '',
        account_number: '',
        routing_number: '',
        swift_code: ''
      }
    }
  } else {
    isEditing.value = false
    resetForm()
  }
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  resetForm()
}

const resetForm = () => {
  formData.value = {
    id: null,
    name: '',
    company_name: '',
    email: '',
    billing_email: '',
    phone: '',
    alt_phone: '',
    address: '',
    city: '',
    state: '',
    zip_code: '',
    country: '',
    region: '',
    warehouse_location: '',
    preferred_pickup_time: '',
    contact_person_name: '',
    business_type: '',
    registration_number: '',
    tax_id: '',
    website_url: '',
    social_media_links: {
      facebook: '',
      twitter: '',
      linkedin: '',
      instagram: ''
    },
    bank_account_info: {
      bank_name: '',
      account_number: '',
      routing_number: '',
      swift_code: ''
    },
    delivery_mode: 'both',
    payment_terms: '',
    credit_limit: 0.00,
    integration_id: '',
    onboarding_stage: 'pending',
    last_active_at: null,
    rating: null,
    status: true,
    notes: '',
    user_id: 1
  }
}

const saveVendor = async () => {
  loading.value = true
  try {
    if (isEditing.value) {
      await vendorStore.updateVendor(formData.value.id, formData.value)
    } else {
      await vendorStore.createVendor(formData.value)
    }
    closeDialog()
  } catch (error) {
    console.error('Error saving vendor:', error)
  } finally {
    loading.value = false
  }
}

const confirmDelete = (vendor) => {
  selectedVendor.value = vendor
  deleteDialog.value = true
}

const deleteVendor = async () => {
  loading.value = true
  try {
    await vendorStore.deleteVendor(selectedVendor.value.id)
    deleteDialog.value = false
    selectedVendor.value = null
  } catch (error) {
    console.error('Error deleting vendor:', error)
  } finally {
    loading.value = false
  }
}

const getStatusColor = (status) => {
  return status ? 'success' : 'error'
}

const getStageColor = (stage) => {
  const colors = {
    pending: 'warning',
    active: 'primary',
    verified: 'success'
  }
  return colors[stage] || 'default'
}

// Lifecycle
onMounted(() => {
  vendorStore.fetchVendors()
})
</script>

<template>
  <AppLayout title="Vendors Management">
    <v-container fluid>
      <!-- Header -->
      <v-row>
        <v-col cols="12">
          <div class="d-flex justify-space-between align-center mb-6">
            <h1 class="text-h4">Vendors</h1>
            <v-btn color="primary" @click="openDialog()" prepend-icon="mdi-plus">
              Add Vendor
            </v-btn>
          </div>
        </v-col>
      </v-row>

      <!-- Search -->
      <v-row>
        <v-col cols="12" md="6">
          <v-text-field
            v-model="search"
            label="Search vendors..."
            prepend-inner-icon="mdi-magnify"
            variant="outlined"
            clearable
            hide-details
          />
        </v-col>
      </v-row>

      <!-- Data Table -->
      <v-row>
        <v-col cols="12">
          <v-card>
            <v-data-table
              :headers="headers"
              :items="filteredVendors"
              :loading="vendorStore.loading"
              item-key="id"
              class="elevation-1"
            >
              <template #item.status="{ item }">
                <v-chip
                  :color="getStatusColor(item.status)"
                  size="small"
                  variant="flat"
                >
                  {{ item.status ? 'Active' : 'Inactive' }}
                </v-chip>
              </template>

              <template #item.onboarding_stage="{ item }">
                <v-chip
                  :color="getStageColor(item.onboarding_stage)"
                  size="small"
                  variant="flat"
                >
                  {{ item.onboarding_stage }}
                </v-chip>
              </template>

              <template #item.rating="{ item }">
                <v-rating
                  v-if="item.rating"
                  :model-value="item.rating"
                  readonly
                  size="small"
                  color="amber"
                />
                <span v-else class="text-grey">No rating</span>
              </template>

              <template #item.actions="{ item }">
                <v-btn
                  icon="mdi-pencil"
                  size="small"
                  variant="text"
                  @click="openDialog(item)"
                />
                <v-btn
                  icon="mdi-delete"
                  size="small"
                  variant="text"
                  color="error"
                  @click="confirmDelete(item)"
                />
              </template>
            </v-data-table>
          </v-card>
        </v-col>
      </v-row>

      <!-- Add/Edit Dialog -->
      <v-dialog v-model="dialog" max-width="1200px" persistent>
        <v-card>
          <v-card-title>
            <span class="text-h5">{{ isEditing ? 'Edit' : 'Add' }} Vendor</span>
          </v-card-title>

          <v-card-text>
            <v-form ref="form">
              <v-tabs v-model="tab" color="primary">
                <v-tab value="basic">Basic Info</v-tab>
                <v-tab value="contact">Contact Details</v-tab>
                <v-tab value="business">Business Info</v-tab>
                <v-tab value="financial">Financial</v-tab>
                <v-tab value="settings">Settings</v-tab>
              </v-tabs>

              <v-tabs-window v-model="tab">
                <!-- Basic Info Tab -->
                <v-tabs-window-item value="basic">
                  <v-container>
                    <v-row>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.name"
                          label="Name *"
                          :rules="rules.name"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.company_name"
                          label="Company Name"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.email"
                          label="Email *"
                          :rules="rules.email"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.billing_email"
                          label="Billing Email"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.phone"
                          label="Phone *"
                          :rules="rules.phone"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.alt_phone"
                          label="Alternative Phone"
                          variant="outlined"
                        />
                      </v-col>
                    </v-row>
                  </v-container>
                </v-tabs-window-item>

                <!-- Contact Details Tab -->
                <v-tabs-window-item value="contact">
                  <v-container>
                    <v-row>
                      <v-col cols="12">
                        <v-textarea
                          v-model="formData.address"
                          label="Address *"
                          :rules="rules.address"
                          variant="outlined"
                          rows="3"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.city"
                          label="City"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.state"
                          label="State"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.zip_code"
                          label="ZIP Code"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.country"
                          label="Country"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.region"
                          label="Region"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.warehouse_location"
                          label="Warehouse Location"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.preferred_pickup_time"
                          label="Preferred Pickup Time"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.contact_person_name"
                          label="Contact Person Name"
                          variant="outlined"
                        />
                      </v-col>
                    </v-row>
                  </v-container>
                </v-tabs-window-item>

                <!-- Business Info Tab -->
                <v-tabs-window-item value="business">
                  <v-container>
                    <v-row>
                      <v-col cols="12" md="6">
                        <v-select
                          v-model="formData.business_type"
                          :items="businessTypeOptions"
                          label="Business Type"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.registration_number"
                          label="Registration Number"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.tax_id"
                          label="Tax ID"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.website_url"
                          label="Website URL"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.social_media_links.facebook"
                          label="Facebook"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.social_media_links.twitter"
                          label="Twitter"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.social_media_links.linkedin"
                          label="LinkedIn"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.social_media_links.instagram"
                          label="Instagram"
                          variant="outlined"
                        />
                      </v-col>
                    </v-row>
                  </v-container>
                </v-tabs-window-item>

                <!-- Financial Tab -->
                <v-tabs-window-item value="financial">
                  <v-container>
                    <v-row>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.payment_terms"
                          label="Payment Terms"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model.number="formData.credit_limit"
                          label="Credit Limit"
                          type="number"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.bank_account_info.bank_name"
                          label="Bank Name"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.bank_account_info.account_number"
                          label="Account Number"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.bank_account_info.routing_number"
                          label="Routing Number"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.bank_account_info.swift_code"
                          label="SWIFT Code"
                          variant="outlined"
                        />
                      </v-col>
                    </v-row>
                  </v-container>
                </v-tabs-window-item>

                <!-- Settings Tab -->
                <v-tabs-window-item value="settings">
                  <v-container>
                    <v-row>
                      <v-col cols="12" md="6">
                        <v-select
                          v-model="formData.delivery_mode"
                          :items="deliveryModeOptions"
                          label="Delivery Mode"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-select
                          v-model="formData.onboarding_stage"
                          :items="onboardingStageOptions"
                          label="Onboarding Stage"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-text-field
                          v-model="formData.integration_id"
                          label="Integration ID"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-rating
                          v-model="formData.rating"
                          label="Rating"
                          color="amber"
                          clearable
                        />
                      </v-col>
                      <v-col cols="12">
                        <v-switch
                          v-model="formData.status"
                          label="Active Status"
                          color="primary"
                        />
                      </v-col>
                      <v-col cols="12">
                        <v-textarea
                          v-model="formData.notes"
                          label="Notes"
                          variant="outlined"
                          rows="3"
                        />
                      </v-col>
                    </v-row>
                  </v-container>
                </v-tabs-window-item>
              </v-tabs-window>
            </v-form>
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn @click="closeDialog">Cancel</v-btn>
            <v-btn
              color="primary"
              :loading="loading"
              @click="saveVendor"
            >
              {{ isEditing ? 'Update' : 'Save' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Delete Confirmation Dialog -->
      <v-dialog v-model="deleteDialog" max-width="500px">
        <v-card>
          <v-card-title>Confirm Delete</v-card-title>
          <v-card-text>
            Are you sure you want to delete vendor "{{ selectedVendor?.name }}"?
            This action cannot be undone.
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn @click="deleteDialog = false">Cancel</v-btn>
            <v-btn
              color="error"
              :loading="loading"
              @click="deleteVendor"
            >
              Delete
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </AppLayout>
</template>

<style scoped>
.v-card {
  margin-bottom: 16px;
}
</style>