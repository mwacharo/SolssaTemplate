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

    <v-main class="bg-grey-lighten-4">
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
                    <span class="font-mono text-primary">sheets@courier-55c5e.iam.gserviceaccount.com</span>, 
                    then add Sheet.
                  </p>
                </v-card-text>
              </v-col>
              <v-col cols="auto">
                <v-btn 
                  color="primary"
                  @click="showAddSheetModal = true"
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
            :items="filteredSheets"
            :search="searchQuery"
            item-key="id"
            class="elevation-0"
          >
            <template v-slot:item.merchant="{ item }">
              <span class="font-weight-medium">{{ item.merchant }}</span>
            </template>

            <template v-slot:item.sheetName="{ item }">
              <span class="text-primary cursor-pointer">{{ item.sheetName }}</span>
            </template>

            <template v-slot:item.status="{ item }">
              <v-chip
                :color="item.status === 'active' ? 'success' : 'error'"
                size="small"
                variant="tonal"
              >
                <v-icon 
                  :icon="item.status === 'active' ? 'mdi-circle' : 'mdi-circle'"
                  size="x-small"
                  class="mr-1"
                />
                {{ item.status }}
              </v-chip>
            </template>

            <template v-slot:item.isDefault="{ item }">
              <v-switch
                :model-value="item.isDefault"
                @update:model-value="toggleDefault(item.id)"
                color="success"
                density="compact"
                hide-details
              />
            </template>

            <template v-slot:item.lastUpdate="{ item }">
              <span class="text-body-2">{{ formatDate(item.lastUpdate) }}</span>
            </template>

            <template v-slot:item.created="{ item }">
              <span class="text-body-2">{{ formatDate(item.created) }}</span>
            </template>

            <template v-slot:item.actions="{ item }">
              <div class="d-flex ga-1">
                <v-btn
                  size="small"
                  color="primary"
                  variant="flat"
                  @click="readSheet(item.id)"
                >
                  Read
                </v-btn>
                <v-btn
                  size="small"
                  color="success"
                  variant="flat"
                  @click="writeSheet(item.id)"
                >
                  Write
                </v-btn>
                <v-btn
                  size="small"
                  :color="item.status === 'active' ? 'error' : 'success'"
                  variant="flat"
                  @click="toggleStatus(item.id)"
                >
                  {{ item.status === 'active' ? 'Deactivate' : 'Activate' }}
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
                >
                  Delete
                </v-btn>
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-container>
    </v-main>

    <!-- Add Sheet Modal -->
    <v-dialog v-model="showAddSheetModal" max-width="500">
      <v-card>
        <v-card-title>
          <span class="text-h5">Google Sheets</span>
        </v-card-title>
        
        <v-card-text>
          <v-form ref="addSheetForm">
            <v-text-field
              v-model="newSheet.vendor"
              label="Vendor"
              placeholder="type at least 3 characters"
              variant="outlined"
              :rules="[v => !!v || 'Vendor is required']"
              class="mb-3"
            />
            
            <v-text-field
              v-model="newSheet.sheetName"
              label="Sheet name"
              placeholder="Sheet1"
              variant="outlined"
              class="mb-3"
            />
            
            <v-text-field
              v-model="newSheet.spreadsheetId"
              label="Spreadsheet id"
              placeholder="123 mainst"
              variant="outlined"
              :rules="[v => !!v || 'Spreadsheet ID is required']"
            />
          </v-form>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer/>
          <v-btn
            text
            @click="showAddSheetModal = false"
          >
            Close
          </v-btn>
          <v-btn
            color="primary"
            @click="addSheet"
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
              v-model="currentSettings.sheetName"
              label="Sheet name"
              variant="outlined"
              class="mb-3"
            />
            
            <v-text-field
              v-model="currentSettings.spreadsheetId"
              label="Spreadsheet id"
              variant="outlined"
              class="mb-3"
            />
            
            <v-text-field
              v-model="currentSettings.lastUpdateOrderNo"
              label="Last update Order No."
              variant="outlined"
              class="mb-3"
            />
            
            <v-text-field
              v-model="currentSettings.lastTime"
              label="Last time"
              type="datetime-local"
              variant="outlined"
              class="mb-3"
            />
            
            <v-text-field
              v-model="currentSettings.operatingUnit"
              label="Operating unit"
              placeholder="Operating unit"
              variant="outlined"
              class="mb-3"
            />
            
            <v-checkbox
              v-model="currentSettings.autoSync"
              label="Auto sync"
              class="mb-3"
            />
            
            <v-text-field
              v-model.number="currentSettings.timeInterval"
              label="Time interval"
              type="number"
              variant="outlined"
              class="mb-3"
            />
            
            <v-text-field
              v-model="currentSettings.orderNumberPrefix"
              label="Order number prefix"
              placeholder="eg SHP_..."
              variant="outlined"
              class="mb-3"
            />
            
            <v-radio-group v-model="currentSettings.syncMode" class="mb-3">
              <v-radio
                value="all"
                label="Sync all"
              />
              <v-radio
                value="scheduled"
                label="Sync scheduled only"
              />
            </v-radio-group>
          </v-form>
        </v-card-text>
        
        <v-card-actions>
          <v-spacer/>
          <v-btn
            color="primary"
            @click="updateSettings"
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
import { defineStore } from 'pinia'
import { useGoogleSheetsStore } from '@/stores/googleSheets'
import AppLayout from "@/Layouts/AppLayout.vue";


// Use the store
const store = useGoogleSheetsStore()

// Component state
const activeTab = ref('current')
const searchQuery = ref('')
const showAddSheetModal = ref(false)
const showSettingsModal = ref(false)

const newSheet = ref({
  vendor: '',
  sheetName: '',
  spreadsheetId: ''
})

const currentSettings = ref({})

// Table headers
const headers = [
  { title: 'Merchant', key: 'merchant', sortable: true },
  { title: 'Sheet Name', key: 'sheetName', sortable: true },
  { title: 'Status', key: 'active', sortable: true },
  { title: 'Default', key: 'isDefault', sortable: false },
  { title: 'Last Update', key: 'lastUpdate', sortable: true },
  { title: 'Created', key: 'created', sortable: true },
  { title: 'Actions', key: 'actions', sortable: false }
]
const filteredSheets = computed(() => {
  // const sheets = Array.isArray(store.sheets) ? store.sheets : []
    const sheets = store.sheets?.data || []

  console.log('Raw sheets data:', JSON.stringify(sheets, null, 2))
  
  return sheets.map(sheet => ({
    id: sheet.id,
        merchant: sheet.vendor?.name || `Vendor ${sheet.vendor_id}`,

    sheetName: sheet.sheet_name,
    status: sheet.active === 1 ? 'Active' : 'Inactive',
    lastUpdate: new Date(sheet.updated_at).toLocaleDateString(),
    created: new Date(sheet.created_at).toLocaleDateString()
  }))
})


// Methods
const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString()
}

const addSheet = async () => {
  if (newSheet.value.vendor && newSheet.value.spreadsheetId) {
    await store.addSheet(newSheet.value)
    newSheet.value = { vendor: '', sheetName: '', spreadsheetId: '' }
    showAddSheetModal.value = false
  }
}

const deleteSheet = async (id) => {
  if (confirm('Are you sure you want to delete this sheet?')) {
    await store.deleteSheet(id)
  }
}

const toggleStatus = async (id) => {
  await store.toggleStatus(id)
}

const toggleDefault = async (id) => {
  await store.toggleDefault(id)
}

const readSheet = async (id) => {
  await store.readSheet(id)
}

const writeSheet = async (id) => {
  await store.writeSheet(id)
}

const openSettings = (sheet) => {
  currentSettings.value = { ...sheet }
  showSettingsModal.value = true
}

const updateSettings = async () => {
  await store.updateSheet(currentSettings.value.id, currentSettings.value)
  showSettingsModal.value = false
}

onMounted(async() => {
  // Initialize any required data
    await store.fetchSheets() // This line is missing!
    console.debug('Sheets loaded:', JSON.stringify(store.sheets, null, 2))

})
</script>

<style scoped>
.cursor-pointer {
  cursor: pointer;
}
</style>