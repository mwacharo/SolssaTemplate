<template>
  <AppLayout>
    <v-container fluid class="pa-0">
      <!-- Header -->
      <v-app-bar 
        color="white" 
        elevation="1"
        height="64"
      >
        <v-container class="d-flex align-center">
          <v-app-bar-title class="text-h5 font-weight-medium">
            Google Sheets Manager
          </v-app-bar-title>
        </v-container>
      </v-app-bar>

      <v-container class="py-6">
        <!-- Share Section -->
        <v-card class="mb-6" elevation="1">
          <v-card-text class="pa-6">
            <v-card-title class="text-h6 pa-0 mb-4">Share</v-card-title>
            <v-row align="center">
              <v-col>
                <v-card-text class="pa-0">
                  <p class="text-body-2 text-medium-emphasis mb-0">
                    To connect, share your googlesheet with 
                    <span class="font-mono text-primary">google-sheets-sync@arcane-indexer-459514-h9.iam.gserviceaccount.com</span>, 
                    then add Sheet.
                  </p>
                </v-card-text>
              </v-col>
              <v-col cols="auto">
                <v-btn 
                  color="primary"
                  @click="showAddSheetModal = true"
                  :loading="store.loading"
                >
                  Add Sheet
                </v-btn>
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <!-- Connected Stores -->
        <v-card elevation="1">
          <v-card-text class="pa-6 pb-0">
            <v-card-title class="text-h6 pa-0 mb-4">Connected Stores</v-card-title>
            
            <!-- Search and Tabs -->
            <v-row class="mb-4" align="center" justify="space-between">
              <v-col>
                <v-tabs v-model="activeTab" color="primary">
                  <v-tab value="current">Current</v-tab>
                  <v-tab value="all">All</v-tab>
                </v-tabs>
              </v-col>
              <v-col cols="auto">
                <v-text-field
                  v-model="searchQuery"
                  placeholder="Search..."
                  prepend-inner-icon="mdi-magnify"
                  variant="outlined"
                  density="compact"
                  hide-details
                  style="min-width: 250px;"
                />
              </v-col>
            </v-row>
          </v-card-text>

          <!-- Table -->
          <v-data-table
            :headers="headers"
            :items="displayedSheets"
            :search="searchQuery"
            item-key="id"
            class="elevation-0"
            :loading="store.loading"
          >
            <template v-slot:item.merchant="{ item }">
              <span class="font-weight-medium">{{ item.merchant }}</span>
            </template>

            <template v-slot:item.sheetName="{ item }">
              <span class="text-primary cursor-pointer">{{ item.sheetName }}</span>
            </template>

            <template v-slot:item.status="{ item }">
              <v-chip
                :color="item.status === 'Active' ? 'success' : 'error'"
                size="small"
                variant="tonal"
              >
                <v-icon 
                  :icon="item.status === 'Active' ? 'mdi-circle' : 'mdi-circle'"
                  size="x-small"
                  class="mr-1"
                />
                {{ item.status }}
              </v-chip>
            </template>

            <template v-slot:item.spreadsheetId="{ item }">
              <span class="text-body-2 font-mono">{{ item.spreadsheetId }}</span>
            </template>

            <template v-slot:item.isCurrent="{ item }">
              <v-switch
                :model-value="item.isCurrent"
                @update:model-value="toggleCurrent(item.id)"
                color="success"
                density="compact"
                hide-details
                :loading="store.loading"
              />
            </template>

            <template v-slot:item.autoSync="{ item }">
              <v-chip
                :color="item.autoSync ? 'success' : 'warning'"
                size="small"
                variant="tonal"
              >
                {{ item.autoSync ? 'Enabled' : 'Disabled' }}
              </v-chip>
            </template>

            <template v-slot:item.lastOrderSynced="{ item }">
              <span class="text-body-2">{{ formatDate(item.lastOrderSynced) }}</span>
            </template>

            <template v-slot:item.actions="{ item }">
              <div class="d-flex ga-1">
                <v-btn
                  size="small"
                  color="primary"
                  variant="flat"
                  @click="readSheet(item.id)"
                  :loading="store.loading"
                >
                  Read
                </v-btn>
                <v-btn
                  size="small"
                  color="success"
                  variant="flat"
                  @click="writeSheet(item.id)"
                  :loading="store.loading"
                >
                  Write
                </v-btn>
                <v-btn
                  size="small"
                  :color="item.status === 'Active' ? 'error' : 'success'"
                  variant="flat"
                  @click="toggleStatus(item.id)"
                  :loading="store.loading"
                >
                  {{ item.status === 'Active' ? 'Deactivate' : 'Activate' }}
                </v-btn>
                <v-btn
                  size="small"
                  color="grey-darken-1"
                  variant="flat"
                  @click="openSettings(item)"
                >
                  Settings
                </v-btn>
                <v-btn
                  size="small"
                  color="error"
                  variant="flat"
                  @click="deleteSheet(item.id)"
                  :loading="store.loading"
                >
                  Delete
                </v-btn>
              </div>
            </template>

            <template v-slot:no-data>
              <div class="text-center py-4">
                <p class="text-body-1">No sheets found</p>
                <v-btn color="primary" @click="showAddSheetModal = true">
                  Add your first sheet
                </v-btn>
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-container>

      <!-- Add Sheet Modal -->
      <v-dialog v-model="showAddSheetModal" max-width="500">
        <v-card>
          <v-card-title>
            <span class="text-h5">Add Google Sheet</span>
          </v-card-title>
          
          <v-card-text>
            <v-form ref="addSheetForm" v-model="formValid">
              <v-autocomplete
                v-model="newSheet.vendor"
                :items="vendorOptions"
                label="Vendor"
                placeholder="Select a vendor"
                variant="outlined"
                :rules="[v => !!v || 'Vendor is required']"
                class="mb-3"
                :loading="vendorLoading"
                item-title="name"
                item-value="id"
                clearable
                hide-no-data
                hide-details="auto"
              />
              
              <v-text-field
                v-model="newSheet.sheetName"
                label="Sheet name"
                placeholder="Sheet1"
                variant="outlined"
                class="mb-3"
                hide-details="auto"
              />
              
              <v-text-field
                v-model="newSheet.spreadsheetId"
                label="Spreadsheet ID"
                placeholder="1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms"
                variant="outlined"
                :rules="[v => !!v || 'Spreadsheet ID is required']"
                hide-details="auto"
              />
            </v-form>
          </v-card-text>
          
          <v-card-actions>
            <v-spacer/>
            <v-btn
              text
              @click="closeAddSheetModal"
            >
              Close
            </v-btn>
            <v-btn
              color="primary"
              @click="addSheet"
              :loading="store.loading"
              :disabled="!formValid"
            >
              Save
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Settings Modal -->
      <v-dialog v-model="showSettingsModal" max-width="600">
        <v-card>
          <v-card-title class="d-flex justify-space-between align-center">
            <span class="text-h5">Settings</span>
            <v-btn
              icon
              variant="text"
              @click="showSettingsModal = false"
            >
              <v-icon>mdi-close</v-icon>
            </v-btn>
          </v-card-title>
          
          <v-card-text>
            <v-form ref="settingsForm">
              <v-text-field
                v-model="currentSettings.sheet_name"
                label="Sheet name"
                variant="outlined"
                class="mb-3"
              />
              
              <v-text-field
                v-model="currentSettings.post_spreadsheet_id"
                label="Spreadsheet ID"
                variant="outlined"
                class="mb-3"
              />
              
              <v-text-field
                v-model="currentSettings.order_prefix"
                label="Order prefix"
                placeholder="eg ORD-2010"
                variant="outlined"
                class="mb-3"
              />
              
              <v-text-field
                v-model="currentSettings.lastUpdatedOrderNumber"
                label="Last updated Order Number"
                variant="outlined"
                class="mb-3"
              />
              
              <v-text-field
                v-model.number="currentSettings.sync_interval"
                label="Sync interval (minutes)"
                type="number"
                variant="outlined"
                class="mb-3"
              />
              
              <v-checkbox
                v-model="currentSettings.auto_sync"
                label="Auto sync"
                class="mb-3"
              />
              
              <v-checkbox
                v-model="currentSettings.sync_all"
                label="Sync all orders"
                class="mb-3"
              />
              
              <v-checkbox
                v-model="currentSettings.is_current"
                label="Set as current sheet"
                class="mb-3"
              />
            </v-form>
          </v-card-text>
          
          <v-card-actions>
            <v-spacer/>
            <v-btn
              color="primary"
              @click="updateSettings"
              :loading="store.loading"
            >
              Update
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useGoogleSheetsStore } from '@/stores/googleSheets'
import AppLayout from "@/Layouts/AppLayout.vue"
import { useClientStore } from '@/stores/clientStore'
import { notify } from '@/utils/toast'

// Use the stores
const store = useGoogleSheetsStore()
const clientStore = useClientStore()

// Component state
const activeTab = ref('current')
const searchQuery = ref('')
const showAddSheetModal = ref(false)
const showSettingsModal = ref(false)
const formValid = ref(false)

const vendorOptions = ref([])
const vendorLoading = ref(false)

const newSheet = ref({
  vendor: '',
  sheetName: '',
  spreadsheetId: ''
})

const currentSettings = ref({})

// Load vendors
const loadVendors = async () => {
  try {
    vendorLoading.value = true
    await clientStore.fetchVendors()
    vendorOptions.value = clientStore.vendors.map(vendor => ({
      id: vendor.id,
      name: vendor.name
    }))
  } catch (error) {
    console.error('Failed to load vendors:', error)
  } finally {
    vendorLoading.value = false
  }
}

// Table headers
const headers = [
  { title: 'Merchant', key: 'merchant', sortable: true },
  { title: 'Sheet Name', key: 'sheetName', sortable: true },
  { title: 'Spreadsheet ID', key: 'spreadsheetId', sortable: false },
  { title: 'Status', key: 'status', sortable: true },
  { title: 'Current', key: 'isCurrent', sortable: false },
  { title: 'Auto Sync', key: 'autoSync', sortable: false },
  { title: 'Last Order Synced', key: 'lastOrderSynced', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false }
]

// Computed properties
const filteredSheets = computed(() => {
  const sheets = store.sheets?.data || []
  
  return sheets.map(sheet => ({
    id: sheet.id,
    merchant: sheet.vendor?.name || `Vendor ${sheet.vendor_id}`,
    sheetName: sheet.sheet_name,
    spreadsheetId: sheet.post_spreadsheet_id,
    status: sheet.active === 1 ? 'Active' : 'Inactive',
    isCurrent: sheet.is_current === 1,
    autoSync: sheet.auto_sync === 1,
    syncAll: sheet.sync_all === 1,
    syncInterval: sheet.sync_interval,
    lastOrderSynced: sheet.last_order_synced,
    lastOrderUpload: sheet.last_order_upload,
    lastProductSynced: sheet.last_product_synced,
    orderPrefix: sheet.order_prefix,
    lastUpdatedOrderNumber: sheet.lastUpdatedOrderNumber,
    created: sheet.created_at,
    updated: sheet.updated_at
  }))
})

const displayedSheets = computed(() => {
  if (activeTab.value === 'current') {
    return filteredSheets.value.filter(sheet => sheet.status === 'Active')
  }
  return filteredSheets.value
})

// Methods
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

const addSheet = async () => {
  if (!formValid.value) return
  
  try {
    await store.addSheet(newSheet.value)
    closeAddSheetModal()
  } catch (error) {
    console.error('Failed to add sheet:', error)
  }
}

const closeAddSheetModal = () => {
  newSheet.value = { vendor: '', sheetName: '', spreadsheetId: '' }
  showAddSheetModal.value = false
}

const deleteSheet = async (id) => {
  if (confirm('Are you sure you want to delete this sheet?')) {
    try {
      await store.deleteSheet(id)
    } catch (error) {
      console.error('Failed to delete sheet:', error)
    }
  }
}

const toggleStatus = async (id) => {
  try {
    await store.toggleStatus(id)
  } catch (error) {
    console.error('Failed to toggle status:', error)
  }
}

const toggleCurrent = async (id) => {
  try {
    await store.toggleCurrent(id)
  } catch (error) {
    console.error('Failed to toggle current:', error)
  }
}

const readSheet = async (id) => {
  try {
    await store.readSheet(id)
  } catch (error) {
    console.error('Failed to read sheet:', error)
  }
}

const writeSheet = async (id) => {
  try {
    await store.writeSheet(id)
  } catch (error) {
    console.error('Failed to write sheet:', error)
  }
}

const openSettings = (sheet) => {
  const originalSheet = store.sheets.data.find(s => s.id === sheet.id)
  currentSettings.value = { ...originalSheet }
  showSettingsModal.value = true
}

const updateSettings = async () => {
  try {
    await store.updateSheet(currentSettings.value.id, currentSettings.value)
    showSettingsModal.value = false
  } catch (error) {
    console.error('Failed to update settings:', error)
  }
}

// Initialize component
onMounted(async () => {
  try {
    await Promise.all([
      store.fetchSheets(),
      loadVendors()
    ])
  } catch (error) {
    console.error('Failed to initialize component:', error)
  }
})
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>