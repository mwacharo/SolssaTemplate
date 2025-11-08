<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-teal-600 mb-2">Documentation API</h1>
        <p class="text-gray-600">Integration guides for OMS services</p>
      </div>

      <!-- Webhook Order Creation Section -->
      <!-- <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-l-4 border-teal-500">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
          <span class="w-2 h-2 bg-gray-800 rounded-full mr-3"></span>
          Webhook order creation:
        </h2>
        <div class="bg-gray-50 rounded-md p-4 border border-gray-200">
          <code class="text-sm text-gray-700 break-all">
https://1091cc56ee9a.ngrok-free.app/api/v1/shopify/orders/webhook?key=H8zaX8YLrTPhoF4yf93HkWS44Bf73AUs&secret=CggwYh8gWC9be3fjTGpyeI6lJCYpAq66a4gS1ayntnB4ygMZ9xTw9G5kfYesh2CD          </code>
        </div>
        <div class="mt-3 flex items-start">
          <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
          </svg>
          <p class="text-sm text-gray-600">Replace {KEY_CODE}, {SECRET_CODE}, and {SOURCE_NAME} with your actual credentials</p>
        </div>
      </div> -->

      <!-- Integration Google Sheet Section -->
      <!-- <div class="bg-white rounded-lg shadow-md p-6 mb-6 border-l-4 border-teal-500">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
          <span class="w-2 h-2 bg-gray-800 rounded-full mr-3"></span>
          Integration Google Sheet
        </h2>
        <button 
          @click="generateIntegrationCode"
          class="bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium py-2 px-6 rounded-md transition-colors duration-200 border border-gray-300"
        >
          Generate integration code
        </button>
        <div v-if="showIntegrationCode" class="mt-4 bg-green-50 border border-green-200 rounded-md p-4">
          <p class="text-green-800 text-sm">Integration code generated successfully! Check your email for instructions.</p>
        </div>
      </div> -->

      <!-- Integration SHOPIFY with webhook Section -->
      <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-teal-500">
        <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
          <span class="w-2 h-2 bg-gray-800 rounded-full mr-3"></span>
          Integration SHOPIFY with webhook
        </h2>

        <!-- Procedure -->
        <div class="mb-6">
          <h3 class="font-semibold text-gray-800 mb-3">Procedure:</h3>
          <ol class="space-y-3">
            <li class="flex items-start">
              <span class="flex-shrink-0 w-6 h-6 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center text-sm font-medium mr-3">1</span>
              <span class="text-gray-700">Navigate to <span class="font-semibold">Settings > Notification</span> (in your shopify account).</span>
            </li>
            <li class="flex items-start">
              <span class="flex-shrink-0 w-6 h-6 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center text-sm font-medium mr-3">2</span>
              <span class="text-gray-700">Click <span class="font-semibold">Create webhook</span>.</span>
            </li>
            <li class="flex items-start">
              <span class="flex-shrink-0 w-6 h-6 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center text-sm font-medium mr-3">3</span>
              <span class="text-gray-700">Specify the Event: <span class="font-semibold">(Choose Order Creation)</span>.</span>
            </li>
            <li class="flex items-start">
              <span class="flex-shrink-0 w-6 h-6 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center text-sm font-medium mr-3">4</span>
              <div class="flex-1">
                <span class="text-gray-700 block mb-2">Provide a URL webhook.</span>
                <span class="text-sm text-gray-600">Copy and paste this link</span>
              </div>
            </li>
          </ol>
        </div>

        <!-- API Endpoints -->
        <div class="space-y-4 mb-6">
          <!-- Kenya API -->
          <div class="border-2 border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-semibold text-gray-800">KENYA API:</h4>
              <button 
                @click="copyToClipboard(kenyaApi)"
                class="text-teal-600 hover:text-teal-700 transition-colors"
                title="Copy to clipboard"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
              </button>
            </div>
            <div class="bg-gray-50 rounded p-3 border border-gray-200">
              <code class="text-xs text-gray-700 break-all">{{ kenyaApi }}</code>
            </div>
          </div>

          <!-- Uganda API -->
          <div class="border-2 border-gray-200 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-semibold text-gray-800">UGANDA API:</h4>
              <button 
                @click="copyToClipboard(ugandaApi)"
                class="text-teal-600 hover:text-teal-700 transition-colors"
                title="Copy to clipboard"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
              </button>
            </div>
            <div class="bg-gray-50 rounded p-3 border border-gray-200">
              <code class="text-xs text-gray-700 break-all">{{ ugandaApi }}</code>
            </div>
          </div>
        </div>

        <!-- Remaining Steps -->
        <ol class="space-y-3 mb-6" start="5">
          <li class="flex items-start">
            <span class="flex-shrink-0 w-6 h-6 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center text-sm font-medium mr-3">5</span>
            <span class="text-gray-700">Specify Webhook API version: <span class="font-semibold">(Choose the lastets one)</span>.</span>
          </li>
          <li class="flex items-start">
            <span class="flex-shrink-0 w-6 h-6 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center text-sm font-medium mr-3">6</span>
            <span class="text-gray-700">Click <span class="font-semibold">Save</span>.</span>
          </li>
        </ol>

        <!-- Important Note -->
        <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
          <div class="flex items-start">
            <svg class="w-6 h-6 text-green-600 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
              <h4 class="font-semibold text-green-800 mb-1">Note:</h4>
              <p class="text-green-700 text-sm">
                Make sure to synchronize the products SKUs between Shopify and Boxleo. First, Copy the value of the product SKU that exists in the Boxleo platform. Then, paste it into the <span class="font-semibold">Product SKU field</span> in Shopify. Finally, don't forget to save the changes.
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Country Code Note -->
      <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
        <p class="text-sm text-gray-700">
          <span class="font-semibold">Note:</span> Country will be the country code (for example kenya: KE, Uganda: UG ...)
        </p>
      </div>

      <!-- Toast Notification -->
      <Transition name="fade">
        <div 
          v-if="showToast" 
          class="fixed bottom-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2"
        >
          <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
          <span>Copied to clipboard!</span>
        </div>
      </Transition>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import AppLayout from "@/Layouts/AppLayout.vue";

// API URLs
const kenyaApi = ref('https://boxleo-backend-nml82.ondigitalocean.app/api/orders/apicreate?key=AVhLOGcnPj7d59Z3emnfiU5BsBYNCPBA&secret=4we4sWyLmtuY9ete8hbAtlacTTMDYEtlzmKGv4jEGvWsgszxZaOrdV7Mi8J3JEriN&source=SHOPIFY&country=KE');
const ugandaApi = ref('https://boxleo-backend-nml82.ondigitalocean.app/api/orders/apicreate?key=AVhLOGcnPj7d59Z3emnfiU5BsBYNCPBA&secret=4we4sWyLmtuY9ete8hbAtlacTTMDYEtlzmKGv4jEGvWsgszxZaOrdV7Mi8J3JEriN&source=SHOPIFY&country=UG');

// State
const showToast = ref(false);
const showIntegrationCode = ref(false);

// Methods
const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    showToast.value = true;
    setTimeout(() => {
      showToast.value = false;
    }, 2000);
  } catch (err) {
    console.error('Failed to copy:', err);
  }
};

const generateIntegrationCode = () => {
  showIntegrationCode.value = true;
  setTimeout(() => {
    showIntegrationCode.value = false;
  }, 3000);
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>