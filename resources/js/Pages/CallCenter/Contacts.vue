<template>
  <AppLayout>
    <v-container fluid class="pa-0">
      <v-card class="rounded-lg elevation-2 mb-6">
        <v-card-title class="d-flex justify-space-between align-center py-4 px-6 bg-surface">
          <span class="text-h5 font-weight-bold">Contact Management</span>
            <div class="d-flex align-center">
            <v-btn
              color="primary"
              prepend-icon="mdi-plus"
              @click="dialog = true"
              size="large"
              variant="elevated"
              rounded="pill"
            >
              New Contact
            </v-btn>
            <v-btn
              color="secondary"
              prepend-icon="mdi-upload"
              @click="importContacts"
              size="large"
              variant="elevated"
              rounded="pill"
              class="ms-2"
            >
              Import ContactsX
            </v-btn>
            </div>
        </v-card-title>
        
        <v-card-text class="pa-6">
          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="contactsStore.searchQuery"
                label="Search contacts"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="comfortable"
                hide-details
                clearable
                @keyup.enter="performSearch"
                @click:clear="contactsStore.resetFilters"
                class="rounded-lg"
              ></v-text-field>
            </v-col>
            
            <v-col cols="12" md="4">
              <v-select
                v-model="contactsStore.filterType"
                :items="contactsStore.contactTypes"
                label="Filter by type"
                variant="outlined"
                density="comfortable"
                hide-details
                clearable
                class="rounded-lg"
                @update:model-value="filterByType"
              ></v-select>
            </v-col>
            
            <v-col cols="12" md="4">
              <v-select
                v-model="contactsStore.filterStatus"
                :items="[
                  { title: 'All', value: 'All' },
                  { title: 'Active', value: 1 },
                  { title: 'Inactive', value: 0 }
                ]"
                item-title="title"
                item-value="value"
                label="Filter by status"
                variant="outlined"
                density="comfortable"
                hide-details
                clearable
                class="rounded-lg"
                @update:model-value="filterByStatus"
              ></v-select>
            </v-col>
          </v-row>
        </v-card-text>
        
        <v-data-table
          :headers="headers"
          :loading="contactsStore.loading"
          :items="contactsStore.filteredContacts"
          :sort-by="[{ key: 'name', order: 'asc' }]"
          :items-per-page="10"
          :loading-text="'Loading contacts...'"
          class="rounded-b-lg"
        >
          <template v-slot:loader>
            <v-progress-linear indeterminate color="primary" height="2"></v-progress-linear>
          </template>
          
          <!-- Profile Picture -->
          <template v-slot:item.profile_picture="{ item }">
            <v-avatar size="40" class="me-2">
              <v-img v-if="item.profile_picture" :src="item.profile_picture" :alt="item.name"></v-img>
              <v-icon v-else>mdi-account</v-icon>
            </v-avatar>
          </template>
          
          <!-- Contact Info -->
          <template v-slot:item.contact_info="{ item }">
            <div>
              <div class="font-weight-medium">{{ item.name }}</div>
              <div class="text-caption text-grey">{{ item.email }}</div>
              <div class="text-caption text-grey">{{ item.phone }}</div>
            </div>
          </template>
          
          <!-- Type -->
          <template v-slot:item.type="{ item }">
            <v-chip
              v-if="item.type"
              :color="getTypeColor(item.type)"
              variant="flat"
              size="small"
              class="font-weight-medium"
            >
              {{ item.type }}
            </v-chip>
            <span v-else class="text-grey">-</span>
          </template>
          
          <!-- Company -->
          <template v-slot:item.company="{ item }">
            <div v-if="item.company_name || item.job_title">
              <div class="font-weight-medium">{{ item.company_name || '-' }}</div>
              <div class="text-caption text-grey">{{ item.job_title || '-' }}</div>
            </div>
            <span v-else class="text-grey">-</span>
          </template>
          
          <!-- Status -->
          <template v-slot:item.status="{ item }">
            <v-chip
              :color="item.status ? 'success' : 'error'"
              variant="flat"
              size="small"
              class="font-weight-medium"
            >
              {{ item.status ? 'Active' : 'Inactive' }}
            </v-chip>
          </template>
          
          <!-- Social Media -->
          <template v-slot:item.social="{ item }">
            <div class="d-flex">
              <v-btn
                v-if="item.whatsapp"
                icon
                size="x-small"
                color="success"
                variant="text"
                :href="`https://wa.me/${item.whatsapp}`"
                target="_blank"
              >
                <v-icon>mdi-whatsapp</v-icon>
              </v-btn>
              <v-btn
                v-if="item.linkedin"
                icon
                size="x-small"
                color="blue"
                variant="text"
                :href="item.linkedin"
                target="_blank"
              >
                <v-icon>mdi-linkedin</v-icon>
              </v-btn>
              <v-btn
                v-if="item.facebook"
                icon
                size="x-small"
                color="blue-darken-2"
                variant="text"
                :href="item.facebook"
                target="_blank"
              >
                <v-icon>mdi-facebook</v-icon>
              </v-btn>
              <v-btn
                v-if="item.twitter"
                icon
                size="x-small"
                color="light-blue"
                variant="text"
                :href="item.twitter"
                target="_blank"
              >
                <v-icon>mdi-twitter</v-icon>
              </v-btn>
            </div>
          </template>
          
          <template v-slot:item.actions="{ item }">
            <div class="d-flex align-center">
              <v-tooltip location="top" text="View">
                <template v-slot:activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon
                    size="small"
                    color="info"
                    class="me-1"
                    variant="text"
                    @click="viewItem(item)"
                  >
                    <v-icon>mdi-eye</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>
              
              <v-tooltip location="top" text="Edit">
                <template v-slot:activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon
                    size="small"
                    color="primary"
                    class="me-1"
                    variant="text"
                    @click="editItem(item)"
                  >
                    <v-icon>mdi-pencil</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>
              
              <v-tooltip location="top" text="Delete">
                <template v-slot:activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon
                    size="small"
                    color="error"
                    class="me-1"
                    variant="text"
                    @click="deleteItem(item)"
                  >
                    <v-icon>mdi-delete</v-icon>
                  </v-btn>
                </template>
              </v-tooltip>
            </div>
          </template>
          
          <template v-slot:no-data>
            <div class="d-flex flex-column align-center py-6">
              <v-icon size="64" color="grey-lighten-1" class="mb-4">mdi-account-group-outline</v-icon>
              <p class="text-body-1 text-medium-emphasis mb-4">No contacts found</p>
              <v-btn color="primary" variant="elevated" @click="contactsStore.fetchContacts()">
                Refresh Data
              </v-btn>
            </div>
          </template>
        </v-data-table>
      </v-card>
    </v-container>
    
    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="1200px" persistent scrollable>
      <v-card class="rounded-lg">
        <v-card-title class="d-flex justify-space-between pa-6 bg-surface">
          <span class="text-h5 font-weight-bold">{{ formTitle }}</span>
          <v-btn icon @click="close" variant="text">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        
        <v-divider></v-divider>
        
        <v-card-text class="pa-6" style="height: 600px;">
          <v-form ref="form" v-model="isFormValid">
            <v-tabs v-model="tab" bg-color="transparent" color="primary">
              <v-tab value="basic">Basic Info</v-tab>
              <v-tab value="contact">Contact Details</v-tab>
              <v-tab value="social">Social Media</v-tab>
              <v-tab value="additional">Additional Info</v-tab>
            </v-tabs>
            
            <v-window v-model="tab" class="mt-4">
              <!-- Basic Information -->
              <v-window-item value="basic">
                <v-row>
                  <v-col cols="12" class="text-center mb-4">
                    <v-avatar size="120" class="mx-auto">
                      <v-img v-if="editedItem.profile_picture" :src="editedItem.profile_picture"></v-img>
                      <v-icon v-else size="60">mdi-account</v-icon>
                    </v-avatar>
                    <div class="mt-2">
                      <v-btn size="small" variant="outlined" @click="selectProfilePicture">
                        Change Picture
                      </v-btn>
                    </div>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.name"
                      label="Full Name *"
                      variant="outlined"
                      prepend-inner-icon="mdi-account"
                      :rules="[v => !!v || 'Name is required']"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-select
                      v-model="editedItem.type"
                      :items="['Customer', 'Lead', 'Partner', 'Vendor', 'Employee', 'Other']"
                      label="Contact Type"
                      variant="outlined"
                      prepend-inner-icon="mdi-tag"
                    ></v-select>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.company_name"
                      label="Company Name"
                      variant="outlined"
                      prepend-inner-icon="mdi-office-building"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.job_title"
                      label="Job Title"
                      variant="outlined"
                      prepend-inner-icon="mdi-briefcase"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-switch
                      v-model="editedItem.status"
                      label="Active Status"
                      color="success"
                      true-value="1"
                      false-value="0"
                      hide-details
                    ></v-switch>
                  </v-col>
                </v-row>
              </v-window-item>
              
              <!-- Contact Details -->
              <v-window-item value="contact">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.email"
                      label="Email Address"
                      type="email"
                      variant="outlined"
                      prepend-inner-icon="mdi-email"
                      :rules="[v => !v || /.+@.+\..+/.test(v) || 'Email must be valid']"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.phone"
                      label="Primary Phone"
                      variant="outlined"
                      prepend-inner-icon="mdi-phone"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.alt_phone"
                      label="Alternative Phone"
                      variant="outlined"
                      prepend-inner-icon="mdi-phone-outline"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.whatsapp"
                      label="WhatsApp"
                      variant="outlined"
                      prepend-inner-icon="mdi-whatsapp"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-textarea
                      v-model="editedItem.address"
                      label="Address"
                      variant="outlined"
                      prepend-inner-icon="mdi-map-marker"
                      rows="3"
                    ></v-textarea>
                  </v-col>
                  
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="editedItem.city"
                      label="City"
                      variant="outlined"
                      prepend-inner-icon="mdi-city"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="editedItem.state_name"
                      label="State/Province"
                      variant="outlined"
                      prepend-inner-icon="mdi-map"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="4">
                    <v-text-field
                      v-model="editedItem.zip_code"
                      label="ZIP/Postal Code"
                      variant="outlined"
                      prepend-inner-icon="mdi-mailbox"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-text-field
                      v-model="editedItem.country_name"
                      label="Country"
                      variant="outlined"
                      prepend-inner-icon="mdi-earth"
                    ></v-text-field>
                  </v-col>
                </v-row>
              </v-window-item>
              
              <!-- Social Media -->
              <v-window-item value="social">
                <v-row>
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.linkedin"
                      label="LinkedIn Profile"
                      variant="outlined"
                      prepend-inner-icon="mdi-linkedin"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.twitter"
                      label="Twitter/X Handle"
                      variant="outlined"
                      prepend-inner-icon="mdi-twitter"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.facebook"
                      label="Facebook Profile"
                      variant="outlined"
                      prepend-inner-icon="mdi-facebook"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.instagram"
                      label="Instagram Handle"
                      variant="outlined"
                      prepend-inner-icon="mdi-instagram"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.telegram"
                      label="Telegram Handle"
                      variant="outlined"
                      prepend-inner-icon="mdi-send"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.wechat"
                      label="WeChat ID"
                      variant="outlined"
                      prepend-inner-icon="mdi-wechat"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.snapchat"
                      label="Snapchat Handle"
                      variant="outlined"
                      prepend-inner-icon="mdi-snapchat"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.tiktok"
                      label="TikTok Handle"
                      variant="outlined"
                      prepend-inner-icon="mdi-music-note"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.youtube"
                      label="YouTube Channel"
                      variant="outlined"
                      prepend-inner-icon="mdi-youtube"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.pinterest"
                      label="Pinterest Profile"
                      variant="outlined"
                      prepend-inner-icon="mdi-pinterest"
                    ></v-text-field>
                  </v-col>
                  
                  <v-col cols="12" md="6">
                    <v-text-field
                      v-model="editedItem.reddit"
                      label="Reddit Username"
                      variant="outlined"
                      prepend-inner-icon="mdi-reddit"
                    ></v-text-field>
                  </v-col>
                </v-row>
              </v-window-item>
              
              <!-- Additional Information -->
              <v-window-item value="additional">
                <v-row>
                  <v-col cols="12">
                    <v-combobox
                      v-model="tags"
                      label="Tags"
                      variant="outlined"
                      prepend-inner-icon="mdi-tag-multiple"
                      chips
                      closable-chips
                      multiple
                      small-chips
                      hide-details
                    ></v-combobox>
                    <div class="text-caption text-grey mt-2">
                      Add tags to categorize this contact
                    </div>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-textarea
                      v-model="editedItem.notes"
                      label="Notes"
                      variant="outlined"
                      prepend-inner-icon="mdi-note-text"
                      auto-grow
                      rows="4"
                    ></v-textarea>
                  </v-col>
                  
                  <v-col cols="12">
                    <v-switch
                      v-model="editedItem.consent_to_contact"
                      label="Consent to Contact"
                      color="success"
                      :true-value="1"
                      :false-value="0"
                      hide-details
                    ></v-switch>
                    <div class="text-caption text-grey mt-2">
                      Has this contact given consent to be contacted for marketing purposes?
                    </div>
                  </v-col>
                </v-row>
              </v-window-item>
            </v-window>
          </v-form>
        </v-card-text>
        
        <v-divider></v-divider>
        
        <v-card-actions class="pa-6">
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            variant="text"
            @click="close"
            class="text-capitalize"
          >
            Cancel
          </v-btn>
          <v-btn
            color="primary"
            variant="elevated"
            @click="save"
            :loading="contactsStore.saving"
            :disabled="!isFormValid || contactsStore.saving"
            class="text-capitalize"
          >
            Save
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- View Dialog -->
    <v-dialog v-model="dialogView" max-width="800px">
      <v-card class="rounded-lg">
        <v-card-title class="d-flex justify-space-between pa-6 bg-surface">
          <span class="text-h5 font-weight-bold">Contact Details</span>
          <v-btn icon @click="dialogView = false" variant="text">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-card-title>
        
        <v-divider></v-divider>
        
        <v-card-text class="pa-6">
          <div v-if="viewedItem">
            <div class="text-center mb-6">
              <v-avatar size="100" class="mb-4">
                <v-img v-if="viewedItem.profile_picture" :src="viewedItem.profile_picture"></v-img>
                <v-icon v-else size="50">mdi-account</v-icon>
              </v-avatar>
              <h2 class="text-h4 font-weight-bold">{{ viewedItem.name }}</h2>
              <p class="text-subtitle-1 text-grey">{{ viewedItem.company_name }} {{ viewedItem.job_title ? '• ' + viewedItem.job_title : '' }}</p>
            </div>
            
            <v-row>
              <v-col cols="12" md="6" v-if="viewedItem.email">
                <div class="d-flex align-center mb-2">
                  <v-icon class="me-2">mdi-email</v-icon>
                  <span>{{ viewedItem.email }}</span>
                </div>
              </v-col>
              
              <v-col cols="12" md="6" v-if="viewedItem.phone">
                <div class="d-flex align-center mb-2">
                  <v-icon class="me-2">mdi-phone</v-icon>
                  <span>{{ viewedItem.phone }}</span>
                </div>
              </v-col>
              
              <v-col cols="12" v-if="viewedItem.address">
                <div class="d-flex align-start mb-2">
                  <v-icon class="me-2 mt-1">mdi-map-marker</v-icon>
                  <div>
                    <div>{{ viewedItem.address }}</div>
                    <div class="text-grey">
                      {{ [viewedItem.city, viewedItem.state_name, viewedItem.zip_code, viewedItem.country_name].filter(Boolean).join(', ') }}
                    </div>
                  </div>
                </div>
              </v-col>
            </v-row>
            
            <v-divider class="my-4"></v-divider>
            
            <!-- Social Media Links -->
            <div v-if="hasSocialMedia(viewedItem)" class="mb-4">
              <h3 class="text-h6 mb-3">Social Media</h3>
              <div class="d-flex flex-wrap gap-2">
                <v-btn
                  v-if="viewedItem.whatsapp"
                  :href="`https://wa.me/${viewedItem.whatsapp}`"
                  target="_blank"
                  color="success"
                  variant="outlined"
                  size="small"
                  prepend-icon="mdi-whatsapp"
                >
                  WhatsApp
                </v-btn>
                <v-btn
                  v-if="viewedItem.linkedin"
                  :href="viewedItem.linkedin"
                  target="_blank"
                  color="blue"
                  variant="outlined"
                  size="small"
                  prepend-icon="mdi-linkedin"
                >
                  LinkedIn
                </v-btn>
                <v-btn
                  v-if="viewedItem.twitter"
                  :href="viewedItem.twitter"
                  target="_blank"
                  color="light-blue"
                  variant="outlined"
                  size="small"
                  prepend-icon="mdi-twitter"
                >
                  Twitter
                </v-btn>
                <v-btn
                  v-if="viewedItem.facebook"
                  :href="viewedItem.facebook"
                  target="_blank"
                  color="blue-darken-2"
                  variant="outlined"
                  size="small"
                  prepend-icon="mdi-facebook"
                >
                  Facebook
                </v-btn>
                <v-btn
                  v-if="viewedItem.instagram"
                  :href="viewedItem.instagram"
                  target="_blank"
                  color="pink"
                  variant="outlined"
                  size="small"
                  prepend-icon="mdi-instagram"
                >
                  Instagram
                </v-btn>
              </div>
            </div>
            
            <!-- Notes -->
            <div v-if="viewedItem.notes" class="mb-4">
              <h3 class="text-h6 mb-3">Notes</h3>
              <v-card variant="outlined" class="pa-4">
                <p class="text-body-1">{{ viewedItem.notes }}</p>
              </v-card>
            </div>
            
            <!-- Tags -->
            <div v-if="viewedItem.tags" class="mb-4">
              <h3 class="text-h6 mb-3">Tags</h3>
              <div class="d-flex flex-wrap gap-2">
                <v-chip
                  v-for="tag in parseTagsForDisplay(viewedItem.tags)"
                  :key="tag"
                  size="small"
                  variant="outlined"
                >
                  {{ tag }}
                </v-chip>
              </div>
            </div>
            
            <!-- Contact Status -->
            <div class="d-flex justify-space-between align-center">
              <div>
                <h3 class="text-h6 mb-2">Status</h3>
                <v-chip
                  :color="viewedItem.status ? 'success' : 'error'"
                  variant="flat"
                  size="small"
                >
                  {{ viewedItem.status ? 'Active' : 'Inactive' }}
                </v-chip>
              </div>
              
              <div>
                <h3 class="text-h6 mb-2">Consent to Contact</h3>
                <v-chip
                  :color="viewedItem.consent_to_contact ? 'success' : 'warning'"
                  variant="flat"
                  size="small"
                >
                  {{ viewedItem.consent_to_contact ? 'Yes' : 'No' }}
                </v-chip>
              </div>
            </div>
          </div>
        </v-card-text>
        
        <v-divider></v-divider>
        
        <v-card-actions class="pa-6">
          <v-spacer></v-spacer>
          <v-btn
            color="primary"
            variant="text"
            @click="dialogView = false"
            class="text-capitalize"
          >
            Close
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    
    <!-- Delete Confirmation Dialog -->
    <v-dialog v-model="dialogDelete" max-width="500px" persistent>
      <v-card class="rounded-lg">
        <v-card-title class="text-h5 pa-6">Confirm Deletion</v-card-title>
        <v-card-text class="pa-6">
          Are you sure you want to delete contact <strong>{{ editedItem.name }}</strong>? This action cannot be undone.
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions class="pa-6">
          <v-spacer></v-spacer>
          <v-btn
            color="grey"
            variant="text"
            @click="closeDelete"
            class="text-capitalize"
          >
            Cancel
          </v-btn>
          <v-btn
            color="error"
            variant="elevated"
            @click="deleteItemConfirm"
            :loading="contactsStore.deleting"
            class="text-capitalize"
          >
            Delete
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useContactsStore } from '@/stores/contact'
import AppLayout from "@/Layouts/AppLayout.vue"

// Store
const contactsStore = useContactsStore()

// Reactive data
const dialog = ref(false)
const dialogView = ref(false)
const dialogDelete = ref(false)
const isFormValid = ref(false)
const tab = ref('basic')
const tags = ref([])
const editedIndex = ref(-1)

const editedItem = ref({
  id: null,
  name: '',
  email: '',
  phone: '',
  alt_phone: '',
  address: '',
  city: '',
  zip_code: '',
  country_name: '',
  state_name: '',
  type: '',
  company_name: '',
  job_title: '',
  whatsapp: '',
  linkedin: '',
  telegram: '',
  facebook: '',
  twitter: '',
  instagram: '',
  wechat: '',
  snapchat: '',
  tiktok: '',
  youtube: '',
  pinterest: '',
  reddit: '',
  consent_to_contact: 0,
  tags: '',
  profile_picture: '',
  notes: '',
  status: 1,
  contactable_type: '',
  contactable_id: null
})

const viewedItem = ref(null)

const defaultItem = {
  id: null,
  name: '',
  email: '',
  phone: '',
  alt_phone: '',
  address: '',
  city: '',
  zip_code: '',
  country_name: '',
  state_name: '',
  type: '',
  company_name: '',
  job_title: '',
  whatsapp: '',
  linkedin: '',
  telegram: '',
  facebook: '',
  twitter: '',
  instagram: '',
  wechat: '',
  snapchat: '',
  tiktok: '',
  youtube: '',
  pinterest: '',
  reddit: '',
  consent_to_contact: 0,
  tags: '',
  profile_picture: '',
  notes: '',
  status: 1,
  contactable_type: '',
  contactable_id: null
}

// Table headers
const headers = [
  { title: "", key: "profile_picture", sortable: false, width: "60px" },
  { title: "Contact Info", key: "contact_info", sortable: false },
  { title: "Type", key: "type" },
  { title: "Company", key: "company", sortable: false },
  { title: "Status", key: "status" },
  { title: "Social", key: "social", sortable: false },
  { title: "Actions", key: "actions", sortable: false, align: "end" }
]

// Computed properties
const formTitle = computed(() => {
  return editedIndex.value === -1 ? "Create New Contact" : "Edit Contact"
})

// Methods
const getTypeColor = (type) => {
  const colors = {
    'Customer': 'primary',
    'Lead': 'warning',
    'Partner': 'success',
    'Vendor': 'info',
    'Employee': 'purple',
    'Other': 'grey'
  }
  return colors[type] || 'grey'
}

const hasSocialMedia = (item) => {
  return item.whatsapp || item.linkedin || item.twitter || item.facebook || 
         item.instagram || item.telegram || item.wechat
}

const parseTagsForDisplay = (tagsString) => {
  if (!tagsString) return []
  try {
    return JSON.parse(tagsString)
  } catch (e) {
    return tagsString.split(',').map(tag => tag.trim()).filter(Boolean)
  }
}

const viewItem = (item) => {
  viewedItem.value = { ...item }
  dialogView.value = true
}

const editItem = (item) => {
  editedIndex.value = contactsStore.contacts.indexOf(item)
  editedItem.value = { ...item }
  
  // Parse tags for editing
  if (item.tags) {
    try {
      tags.value = JSON.parse(item.tags)
    } catch (e) {
      tags.value = item.tags.split(',').map(tag => tag.trim()).filter(Boolean)
    }
  } else {
    tags.value = []
  }
  
  dialog.value = true
}

const deleteItem = (item) => {
  editedIndex.value = contactsStore.contacts.indexOf(item)
  editedItem.value = { ...item }
  dialogDelete.value = true
}

const deleteItemConfirm = async () => {
  try {
    await contactsStore.deleteContact(editedItem.value.id)
    closeDelete()
    // Show success message (you can add a toast notification here)
  } catch (error) {
    console.error('Delete failed:', error)
    // Show error message
  }
}

const close = () => {
  dialog.value = false
  resetForm()
}

const closeDelete = () => {
  dialogDelete.value = false
  resetForm()
}

const resetForm = () => {
  setTimeout(() => {
    editedItem.value = { ...defaultItem }
    editedIndex.value = -1
    tags.value = []
    tab.value = 'basic'
  }, 300)
}

const selectProfilePicture = () => {
  // Implement file selection logic here
  console.log('Select profile picture')
}

const save = async () => {
  if (!isFormValid.value) return
  
  try {
    // Convert tags array to JSON string
    const payload = {
      ...editedItem.value,
      tags: JSON.stringify(tags.value),
      consent_given_at: editedItem.value.consent_to_contact ? new Date().toISOString() : null
    }
    
    if (editedIndex.value > -1) {
      await contactsStore.updateContact(editedItem.value.id, payload)
    } else {
      await contactsStore.createContact(payload)
    }
    
    close()
    // Show success message
  } catch (error) {
    console.error('Save failed:', error)
    // Show error message
  }
}

const performSearch = () => {
  contactsStore.searchContacts(contactsStore.searchQuery)
}

const filterByType = () => {
  // The computed property handles this automatically
}

const filterByStatus = () => {
  // The computed property handles this automatically
}

// Watchers
watch(dialog, (val) => {
  if (!val) close()
})

watch(dialogDelete, (val) => {
  if (!val) closeDelete()
})

// Lifecycle
onMounted(() => {
  contactsStore.fetchContacts()
})
</script>

<style scoped>
.v-data-table .v-table__wrapper {
  border-radius: 0 0 8px 8px;
}

.v-data-table {
  box-shadow: none !important;
}

.gap-2 > * {
  margin: 4px;
}

.v-window {
  min-height: 400px;
}

.v-tab {
  text-transform: none !important;
}
</style>