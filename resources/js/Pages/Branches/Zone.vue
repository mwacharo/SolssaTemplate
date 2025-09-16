<template>
  <AppLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <!-- Header -->
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center">
              <div>
                <h2 class="text-2xl font-semibold text-gray-900">Zones Management</h2>
                <p class="mt-1 text-sm text-gray-600">
                  Manage zones and their information
                </p>
              </div>
              <button
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:shadow-outline-indigo active:bg-indigo-600 transition ease-in-out duration-150"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Zone
              </button>
            </div>
          </div>

          <!-- Search and Filters -->
          <div class="p-6 bg-gray-50 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row gap-4">
              <div class="flex-1">
                <input
                  v-model="searchTerm"
                  type="text"
                  placeholder="Search zones..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                />
              </div>
              <div class="flex gap-2">
                <select
                  v-model="selectedCountry"
                  class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="">All Countries</option>
                  <option v-for="country in countries" :key="country.id" :value="country.id">
                    {{ country.name }}
                  </option>
                </select>

                <vue-autocomplete
                  v-model="selectedCity"
                  :items="filteredCities"
                  placeholder="Search city..."
                  class="w-64 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  :get-label="city => city.name"
                  :get-value="city => city.id"
                  :clearable="true"
                  :required="true"
                />



                <select
                  v-model="inboundFilter"
                  class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="">All Zones</option>
                  <option value="true">Inbound Only</option>
                  <option value="false">Not Inbound</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Zones Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Zone Name
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Country
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    City
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Population
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Coordinates
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="zone in filteredZones" :key="zone.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ zone.name }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ zone.country?.name || 'Unknown' }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ zone.city?.name || 'N/A' }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ zone.population ? zone.population.toLocaleString() : 'N/A' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ zone.latitude && zone.longitude ? `${zone.latitude}, ${zone.longitude}` : 'N/A' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="zone.inbound 
                        ? 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800'
                        : 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800'
                      "
                    >
                      {{ zone.inbound ? 'Inbound' : 'Standard' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                      <button
                        @click="viewZone(zone)"
                        class="text-indigo-600 hover:text-indigo-900"
                        title="View"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                      </button>
                      <button
                        @click="editZone(zone)"
                        class="text-yellow-600 hover:text-yellow-900"
                        title="Edit"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </button>
                      <button
                        @click="deleteZone(zone)"
                        class="text-red-600 hover:text-red-900"
                        title="Delete"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="filteredZones.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No zones found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new zone.</p>
          </div>

          <!-- Pagination -->
          <div v-if="filteredZones.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700">
                Showing {{ filteredZones.length }} of {{ zones.length }} zones
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 lg:w-1/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">
              {{ modalMode === 'create' ? 'Add New Zone' : modalMode === 'edit' ? 'Edit Zone' : 'Zone Details' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveZone" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Zone Name *</label>
              <input
                v-model="formData.name"
                type="text"
                :readonly="modalMode === 'view'"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Enter zone name"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Country *</label>
              <select
                v-model="formData.country_id"
                :disabled="modalMode === 'view'"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
              >
                <option value="">Select a country</option>
                <option v-for="country in countries" :key="country.id" :value="country.id">
                  {{ country.name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">City</label>
              <v-autocomplete
                v-model="formData.city_id"
                :items="cities"
                item-title="name"
                item-value="id"
           
                :disabled="modalMode === 'view'"
                placeholder="Select or search city..."
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                :clearable="true"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Latitude</label>
                <input
                  v-model="formData.latitude"
                  type="number"
                  step="0.0000001"
                  :readonly="modalMode === 'view'"
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="e.g., -1.2921"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Longitude</label>
                <input
                  v-model="formData.longitude"
                  type="number"
                  step="0.0000001"
                  :readonly="modalMode === 'view'"
                  class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="e.g., 36.8219"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Population</label>
              <input
                v-model="formData.population"
                type="number"
                :readonly="modalMode === 'view'"
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Enter zone population"
              />
            </div>

            <div>
              <label class="flex items-center">
                <input
                  v-model="formData.inbound"
                  type="checkbox"
                  :disabled="modalMode === 'view'"
                  class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                />
                <span class="ml-2 text-sm text-gray-700">Inbound Zone</span>
              </label>
            </div>

            <div v-if="modalMode !== 'view'" class="flex justify-end space-x-2 pt-4">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
              >
                {{ loading ? 'Saving...' : (modalMode === 'create' ? 'Create Zone' : 'Update Zone') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import { notify } from '@/utils/toast'

// Reactive data
const zones = ref([]);
const countries = ref([]);
const cities = ref([]);
const showModal = ref(false);
const modalMode = ref('create'); // 'create', 'edit', 'view'
const loading = ref(false);
const searchTerm = ref('');
const selectedCountry = ref('');
const selectedCity = ref('');
const inboundFilter = ref('');

// Form data
const formData = ref({
  name: '',
  country_id: '',
  city_id: '',
  latitude: null,
  longitude: null,
  population: null,
  inbound: false
});

const currentZoneId = ref(null);

// Computed properties
const filteredZones = computed(() => {
  let filtered = zones.value;

  if (searchTerm.value) {
    filtered = filtered.filter(zone =>
      zone.name.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
  }

  if (selectedCountry.value) {
    filtered = filtered.filter(zone => zone.country_id == selectedCountry.value);
  }

  if (selectedCity.value) {
    filtered = filtered.filter(zone => zone.city_id == selectedCity.value);
  }

  if (inboundFilter.value !== '') {
    filtered = filtered.filter(zone => zone.inbound === (inboundFilter.value === 'true'));
  }

  return filtered;
});

const filteredCitiesForForm = computed(() => {
  if (!formData.value.country_id) return cities.value;
  return cities.value.filter(city => city.country_id == formData.value.country_id);
});

// Watch for country change in form to reset city selection
watch(() => formData.value.country_id, () => {
  formData.value.city_id = '';
});

// Methods
const fetchZones = async () => {
  try {
    const response = await axios.get('/api/v1/zones');
    zones.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching zones:', error);
    notify.error('Error loading zones. Please try again.');
  }
};

const fetchCountries = async () => {
  try {
    const response = await axios.get('/api/v1/countries');
    countries.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching countries:', error);
    notify.error('Error loading countries. Please try again.');
  }
};

const fetchCities = async () => {
  try {
    const response = await axios.get('/api/v1/cities');
    cities.value = response.data.data || response.data;
  } catch (error) {
    console.error('Error fetching cities:', error);
    notify.error('Error loading cities. Please try again.');
  }
};

const openCreateModal = () => {
  modalMode.value = 'create';
  resetForm();
  showModal.value = true;
};

const editZone = (zone) => {
  modalMode.value = 'edit';
  currentZoneId.value = zone.id;
  formData.value = {
    name: zone.name,
    country_id: zone.country_id,
    city_id: zone.city_id || '',
    latitude: zone.latitude,
    longitude: zone.longitude,
    population: zone.population,
    inbound: zone.inbound
  };
  showModal.value = true;
};

const viewZone = (zone) => {
  modalMode.value = 'view';
  currentZoneId.value = zone.id;
  formData.value = {
    name: zone.name,
    country_id: zone.country_id,
    city_id: zone.city_id || '',
    latitude: zone.latitude,
    longitude: zone.longitude,
    population: zone.population,
    inbound: zone.inbound
  };
  showModal.value = true;
};

const saveZone = async () => {
  loading.value = true;
  
  try {
    const data = {
      name: formData.value.name,
      country_id: formData.value.country_id,
      city_id: formData.value.city_id || null,
      latitude: formData.value.latitude || null,
      longitude: formData.value.longitude || null,
      population: formData.value.population || null,
      inbound: formData.value.inbound
    };

    if (modalMode.value === 'create') {
      await axios.post('/api/v1/zones', data);
    } else {
      await axios.put(`/api/v1/zones/${currentZoneId.value}`, data);
    }
    
    await fetchZones();
    closeModal();
    notify.success(`Zone ${modalMode.value === 'create' ? 'created' : 'updated'} successfully!`);
  } catch (error) {
    console.error('Error saving zone:', error);
    notify.error('Error saving zone. Please try again.');
  } finally {
    loading.value = false;
  }
};

const deleteZone = async (zone) => {
  if (!confirm(`Are you sure you want to delete "${zone.name}"?`)) {
    return;
  }

  try {
    await axios.delete(`/api/v1/zones/${zone.id}`);
    await fetchZones();
    notify.success('Zone deleted successfully!');
  } catch (error) {
    console.error('Error deleting zone:', error);
    notify.error('Error deleting zone. Please try again.');
  }
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
  currentZoneId.value = null;
};

const resetForm = () => {
  formData.value = {
    name: '',
    country_id: '',
    city_id: '',
    latitude: null,
    longitude: null,
    population: null,
    inbound: false
  };
};

// Lifecycle
onMounted(() => {
  fetchZones();
  fetchCountries();
  fetchCities();
});
</script>