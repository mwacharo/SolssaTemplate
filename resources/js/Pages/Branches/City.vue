<template>
  <AppLayout>
    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <!-- Header -->
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center">
              <div>
                <h2 class="text-2xl font-semibold text-gray-900">Cities Management</h2>
                <p class="mt-1 text-sm text-gray-600">
                  Manage cities and their information
                </p>
              </div>
              <button
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:border-indigo-700 focus:shadow-outline-indigo active:bg-indigo-600 transition ease-in-out duration-150"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add City
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
                  placeholder="Search cities..."
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
                <select
                  v-model="inboundFilter"
                  class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                >
                  <option value="">All Cities</option>
                  <option value="true">Inbound Only</option>
                  <option value="false">Not Inbound</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Cities Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    City
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Country
                  </th>
                  <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Population
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Coordinates
                  </th> -->
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Status
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="city in filteredCities" :key="city.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ city.name }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">{{ city.country?.name || 'Unknown' }}</div>
                  </td>
                  <!-- <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ city.population ? city.population.toLocaleString() : 'N/A' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ city.latitude && city.longitude ? `${city.latitude}, ${city.longitude}` : 'N/A' }}
                    </div>
                  </td> -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      :class="city.inbound 
                        ? 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800'
                        : 'inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800'
                      "
                    >
                      {{ city.inbound ? 'Inbound' : 'Standard' }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="flex space-x-2">
                      <button
                        @click="viewCity(city)"
                        class="text-indigo-600 hover:text-indigo-900"
                        title="View"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                      </button>
                      <button
                        @click="editCity(city)"
                        class="text-yellow-600 hover:text-yellow-900"
                        title="Edit"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </button>
                      <button
                        @click="deleteCity(city)"
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
          <div v-if="filteredCities.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No cities found</h3>
            <p class="mt-1 text-sm text-gray-500">Get started by creating a new city.</p>
          </div>

          <!-- Pagination -->
          <div v-if="filteredCities.length > 0" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            <div class="flex items-center justify-between">
              <div class="text-sm text-gray-700">
                Showing {{ filteredCities.length }} of {{ cities.length }} cities
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
              {{ modalMode === 'create' ? 'Add New City' : modalMode === 'edit' ? 'Edit City' : 'City Details' }}
            </h3>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <form @submit.prevent="saveCity" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">City Name</label>
              <input
                v-model="formData.name"
                type="text"
                :readonly="modalMode === 'view'"
                required
                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
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
                <span class="ml-2 text-sm text-gray-700">Inbound City</span>
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
                {{ loading ? 'Saving...' : (modalMode === 'create' ? 'Create City' : 'Update City') }}
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
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { notify } from '@/utils/toast'

// Reactive data
const cities = ref([]);
// const countries = ref([]);
const showModal = ref(false);
const modalMode = ref('create'); // 'create', 'edit', 'view'
const loading = ref(false);
const searchTerm = ref('');
const selectedCountry = ref('');
const inboundFilter = ref('');

// Form data
const formData = ref({
  name: '',
  // country_id: '',
  latitude: null,
  longitude: null,
  population: null,
  inbound: false
});

const currentCityId = ref(null);

// Computed properties
const filteredCities = computed(() => {
  let filtered = cities.value;

  if (searchTerm.value) {
    filtered = filtered.filter(city =>
      city.name.toLowerCase().includes(searchTerm.value.toLowerCase())
    );
  }

  if (inboundFilter.value !== '') {
    filtered = filtered.filter(city => city.inbound === (inboundFilter.value === 'true'));
  }

  return filtered;
});

// Methods
const fetchCities = async () => {
  try {
    const response = await axios.get('/api/v1/cities');
    cities.value = response.data.data;
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

const editCity = (city) => {
  modalMode.value = 'edit';
  currentCityId.value = city.id;
  formData.value = {
    name: city.name,
    latitude: city.latitude,
    longitude: city.longitude,
    population: city.population,
    inbound: city.inbound
  };
  showModal.value = true;
};

const viewCity = (city) => {
  modalMode.value = 'view';
  currentCityId.value = city.id;
  formData.value = {
    name: city.name,
    latitude: city.latitude,
    longitude: city.longitude,
    population: city.population,
    inbound: city.inbound
  };
  showModal.value = true;
};

const saveCity = async () => {
  loading.value = true;
  
  try {
    const data = {
      ...formData.value,
      latitude: formData.value.latitude || null,
      longitude: formData.value.longitude || null,
      population: formData.value.population || null,
    };

    if (modalMode.value === 'create') {
      await axios.post('/api/v1/cities', data);
    } else {
      await axios.put(`/api/v1/cities/${currentCityId.value}`, data);
    }
    
    await fetchCities();
    closeModal();
    notify.success(`City ${modalMode.value === 'create' ? 'created' : 'updated'} successfully!`);
  } catch (error) {
    console.error('Error saving city:', error);
    notify.error('Error saving city. Please try again.');
  } finally {
    loading.value = false;
  }
};

const deleteCity = async (city) => {
  if (!confirm(`Are you sure you want to delete "${city.name}"?`)) {
    return;
  }

  try {
    await axios.delete(`/api/v1/cities/${city.id}`);
    await fetchCities();
    notify.success('City deleted successfully!');
  } catch (error) {
    console.error('Error deleting city:', error);
    notify.error('Error deleting city. Please try again.');
  }
};

const closeModal = () => {
  showModal.value = false;
  resetForm();
  currentCityId.value = null;
};

const resetForm = () => {
  formData.value = {
    name: '',
    // country_id: '',
    latitude: null,
    longitude: null,
    population: null,
    inbound: false
  };
};

// Lifecycle
onMounted(() => {
  fetchCities();
  // fetchCountries();
});
</script>
