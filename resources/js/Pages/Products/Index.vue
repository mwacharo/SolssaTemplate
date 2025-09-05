<template>
  <AppLayout>
    <div class="container mx-auto p-6 bg-gray-50 min-h-screen">
      <!-- Header Section -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex justify-between items-center mb-6">
          <h1 class="text-2xl font-bold text-gray-800">Products Management</h1>
          <button @click="openCreateModal"
            class="bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Add a product
          </button>
        </div>

        <!-- Search and Filters -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
          <div class="lg:col-span-1">
            <input v-model="searchQuery" type="text" placeholder="Search for a product (id, name, sku)"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              @input="handleSearch" />
          </div>

          <div>
            <label class="block text-sm font-medium text-red-600 mb-1">DATE</label>
            <input v-model="filters.date" type="date" placeholder="Filter by date"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
          </div>

          <div>
            <label class="block text-sm font-medium text-red-600 mb-1">SELLER</label>
            <select v-model="filters.vendor"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Filter by seller</option>
              <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">
                {{ vendor.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-red-600 mb-1">CATEGORY</label>
            <select v-model="filters.category"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="">Category</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Filter Actions -->
        <div class="flex gap-4">
          <button @click="resetFilters"
            class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Reset
          </button>
          <button @click="applyFilters"
            class="px-6 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z">
              </path>
            </svg>
            Filter
          </button>
        </div>
      </div>

      <!-- Products Table -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200">
          <div class="flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Products</h2>
            <span class="text-sm text-gray-600">
              {{ pagination.from }}-{{ pagination.to }} / {{ pagination.total }}
            </span>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-blue-600 text-white">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">ID/SKU</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">SELLER</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">PRODUCT</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">CATEGORY</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">DATE</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">CURRENT QUANTITY</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">COMMITTED STOCK</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">DEFECTED QUANTITY</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">STOCK THRESHOLD</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">PRICE</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">PRODUCT PAGE</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider">ACTIONS</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="product in products" :key="product.id" class="hover:bg-gray-50">
                <td class="px-4 py-4">
                  <div class="flex flex-col">
                    <span class="bg-red-500 text-white px-2 py-1 rounded text-xs font-medium mb-1 inline-block w-fit">
                      pdt-{{ product.id }}
                    </span>
                    <span class="text-sm text-gray-600">{{ product.sku }}</span>
                  </div>
                </td>

                <td class="px-4 py-4 text-sm text-gray-900">
                  {{ product.vendor?.name || 'N/A' }}
                </td>

                <td class="px-4 py-4">
                  <div class="flex items-center gap-3">
                    <img :src="getProductImage(product)" :alt="product.product_name"
                      class="w-12 h-12 rounded-lg object-cover" />
                    <div>
                      <div class="font-medium text-gray-900">{{ product.product_name }}</div>
                      <div class="text-sm text-gray-500">{{ product.description }}</div>
                    </div>
                  </div>
                </td>

                <td class="px-4 py-4">
                  <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                    {{ getCategoryName(product.category_id) }}
                  </span>
                </td>

                <td class="px-4 py-4 text-sm text-gray-900">
                  {{ formatDate(product.created_at) }}
                </td>

                <td class="px-4 py-4 text-center">
                  <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-sm font-medium">
                    Total {{ getCurrentStock(product) }}
                  </span>
                  <div class="text-xs text-gray-500 mt-1 flex items-center justify-center gap-1">
                    <img src="/api/placeholder/16/16" alt="Flag" class="w-4 h-4" />
                    <span>{{ getCurrentStock(product) }}</span>
                    <span class="bg-red-500 text-white px-1 rounded text-xs">Nairobi</span>
                  </div>
                </td>

                <td class="px-4 py-4 text-center">
                  <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-sm font-medium">
                    Total {{ getCommittedStock(product) }}
                  </span>
                  <div class="text-xs text-gray-500 mt-1 flex items-center justify-center gap-1">
                    <img src="/api/placeholder/16/16" alt="Flag" class="w-4 h-4" />
                    <span>{{ getCommittedStock(product) }}</span>
                    <span class="bg-red-500 text-white px-1 rounded text-xs">Nairobi</span>
                  </div>
                </td>

                <td class="px-4 py-4 text-center">
                  <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-sm font-medium">
                    Total {{ getDefectedStock(product) }}
                  </span>
                  <div class="text-xs text-gray-500 mt-1 flex items-center justify-center gap-1">
                    <img src="/api/placeholder/16/16" alt="Flag" class="w-4 h-4" />
                    <span>{{ getDefectedStock(product) }}</span>
                    <span class="bg-red-500 text-white px-1 rounded text-xs">Nairobi</span>
                  </div>
                </td>

                <td class="px-4 py-4 text-center text-sm font-medium">
                  {{ getStockThreshold(product) }}
                </td>

                <td class="px-4 py-4 text-sm font-medium">
                  {{ formatPrice(getProductPrice(product)) }} <span class="text-xs text-gray-500">KES</span>
                </td>

                <td class="px-4 py-4">
                  <button class="text-blue-600 hover:text-blue-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                  </button>
                </td>

                <td class="px-4 py-4">
                  <div class="flex items-center gap-2">
                    <button @click="editProduct(product)" class="text-blue-600 hover:text-blue-800 p-1" title="Edit">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                      </svg>
                    </button>
                    <button @click="viewProduct(product)" class="text-gray-600 hover:text-gray-800 p-1" title="View">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                        </path>
                      </svg>
                    </button>
                    <button @click="deleteProduct(product)" class="text-red-600 hover:text-red-800 p-1" title="Delete">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                      </svg>
                    </button>
                    <button @click="adjustStock(product, -1)" class="text-red-600 hover:text-red-800 p-1" title="Remove Stock">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5"></path>
                      </svg>
                    </button>
                    <button @click="adjustStock(product, 1)" class="text-green-600 hover:text-green-800 p-1" title="Add Stock">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                      </svg>
                    </button>
                    <button @click="openMoveStockModal(product)" class="text-yellow-600 hover:text-yellow-800 p-1" title="Move Stock">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!products || products.length === 0">
                <td colspan="12" class="px-4 py-8 text-center text-gray-500">
                  <div class="flex flex-col items-center gap-2">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2 2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-4.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 009.586 13H7">
                      </path>
                    </svg>
                    <span>No products found</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
          <div class="flex-1 flex justify-between sm:hidden">
            <button @click="previousPage" :disabled="!pagination.prev_page_url"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
              Previous
            </button>
            <button @click="nextPage" :disabled="!pagination.next_page_url"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing
                <span class="font-medium">{{ pagination.from }}</span>
                to
                <span class="font-medium">{{ pagination.to }}</span>
                of
                <span class="font-medium">{{ pagination.total }}</span>
                results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                <button @click="previousPage" :disabled="!pagination.prev_page_url"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                  <span class="sr-only">Previous</span>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                      clip-rule="evenodd" />
                  </svg>
                </button>

                <button v-for="page in visiblePages" :key="page" @click="goToPage(page)" :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  page === pagination.current_page
                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                ]">
                  {{ page }}
                </button>

                <button @click="nextPage" :disabled="!pagination.next_page_url"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                  <span class="sr-only">Next</span>
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                      d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                      clip-rule="evenodd" />
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <!-- Create/Edit Modal -->
      <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white">
          <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">
                {{ isEditing ? 'Edit Product' : 'Create New Product' }}
              </h3>
              <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>

            <form @submit.prevent="saveProduct" class="space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">SKU *</label>
                  <input v-model="form.sku" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required placeholder="Enter SKU" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                  <input v-model="form.product_name" type="text"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required placeholder="Enter product name" />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                  <select v-model="form.category_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
                    <option value="">Select Category</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Vendor *</label>
                  <select v-model="form.vendor_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required>
                    <option value="">Select Vendor</option>
                    <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">
                      {{ vendor.name }}
                    </option>
                  </select>
                </div>

                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Initial Stock Quantity</label>
                  <input v-model="form.initial_quantity" type="number" min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter initial stock quantity" />
                </div>

                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Stock Threshold</label>
                  <input v-model="form.stock_threshold" type="number" min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter minimum stock threshold" />
                </div>

                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Price (KES)</label>
                  <input v-model="form.price" type="number" min="0" step="0.01"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Enter product price" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea v-model="form.description" rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter product description (optional)"></textarea>
              </div>



              <!-- Media Upload Section -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Product Images</label>
                  <input type="file" accept="image/*" multiple @change="handleImageUpload" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                  <div class="flex flex-wrap gap-2 mt-2">
                    <img v-for="(img, idx) in form.images" :key="idx" :src="img.preview" class="w-16 h-16 object-cover rounded" />
                  </div>
                  <div v-if="formErrors.images" class="text-red-600 text-xs mt-1">{{ formErrors.images[0] }}</div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Product Videos</label>
                  <input type="file" accept="video/*" multiple @change="handleVideoUpload" class="w-full px-3 py-2 border border-gray-300 rounded-md" />
                  <div class="flex flex-wrap gap-2 mt-2">
                    <video v-for="(vid, idx) in form.videos" :key="idx" :src="vid.preview" controls class="w-24 h-16 rounded"></video>
                  </div>
                  <div v-if="formErrors.videos" class="text-red-600 text-xs mt-1">{{ formErrors.videos[0] }}</div>
                </div>
              </div>

              <!-- Price Offers Expansion -->
              <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Price Offers</label>
                <div v-for="(offer, idx) in form.price_offers" :key="idx" class="border rounded p-3 mb-2">
                  <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div>
                      <input v-model="offer.amount" type="number" min="0" step="0.01" placeholder="Amount"
                        class="w-full px-2 py-1 border rounded" />
                      <div v-if="formErrors[`price_offers.${idx}.amount`]" class="text-red-600 text-xs mt-1">
                        {{ formErrors[`price_offers.${idx}.amount`][0] }}
                      </div>
                    </div>
                    <div>
                      <input v-model="offer.currency" type="text" placeholder="Currency (e.g. KES)"
                        class="w-full px-2 py-1 border rounded" />
                      <div v-if="formErrors[`price_offers.${idx}.currency`]" class="text-red-600 text-xs mt-1">
                        {{ formErrors[`price_offers.${idx}.currency`][0] }}
                      </div>
                    </div>
                    <div>
                      <input v-model="offer.label" type="text" placeholder="Offer Label (e.g. Discount, Promo)"
                        class="w-full px-2 py-1 border rounded" />
                    </div>
                  </div>
                  <button type="button" @click="removePriceOffer(idx)" class="text-red-600 text-xs mt-2">Remove Offer</button>
                </div>
                <button type="button" @click="addPriceOffer" class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-xs mt-2">
                  Add Price Offer
                </button>

                <!-- Product Offers Table -->
                <div v-if="form.price_offers && form.price_offers.length" class="mt-4">
                  <table class="min-w-full divide-y divide-gray-200 border rounded">
                  <thead class="bg-gray-100">
                    <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Amount</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Currency</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-700">Label</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(offer, idx) in form.price_offers" :key="idx">
                    <td class="px-4 py-2 text-sm text-gray-900">{{ offer.amount }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ offer.currency }}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">{{ offer.label }}</td>
                    </tr>
                  </tbody>
                  </table>
                </div>
              </div>

          

              <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="closeModal"
                  class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                  Cancel
                </button>
                <button type="submit" :disabled="isLoading" 
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                  {{ isEditing ? 'Update' : 'Create' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Stock Adjustment Modal -->
      <div v-if="showStockModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-md shadow-lg rounded-md bg-white">
          <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
              <h3 class="text-lg font-medium text-gray-900">Adjust Stock</h3>
              <button @click="closeStockModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>

            <form @submit.prevent="saveStockAdjustment" class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
                <p class="text-sm text-gray-900">{{ stockForm.product?.product_name }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Current Stock</label>
                <p class="text-sm text-gray-900">{{ getCurrentStock(stockForm.product) }}</p>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Quantity *</label>
                <input v-model="stockForm.quantity" type="number"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required placeholder="Enter quantity (positive to add, negative to subtract)" />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                <textarea v-model="stockForm.reason" rows="3"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Enter reason for stock adjustment (optional)"></textarea>
              </div>

              <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="closeStockModal"
                  class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                  Cancel
                </button>
                <button type="submit" :disabled="isLoading"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                  Adjust Stock
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import AppLayout from "@/Layouts/AppLayout.vue"
import { useCategoryStore } from '@/stores/categoryStore'
import { useClientStore } from '@/stores/clientStore'
import axios from 'axios'

// Configure axios defaults
axios.defaults.withCredentials = true
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Get API base URL from environment or fallback
const API_BASE_URL = import.meta.env.VITE_API_URL || window.location.origin

// Configure axios instance with base URL and CSRF
const apiClient = axios.create({
  baseURL: `${API_BASE_URL}/api/v1`,
  withCredentials: true,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
})

// Add CSRF token to requests if available
apiClient.interceptors.request.use((config) => {
  const token = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
  if (token) {
    config.headers['X-CSRF-TOKEN'] = token
  }
  return config
})

// Handle response errors globally
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 419) {
      // CSRF token mismatch - reload page to get fresh token
      window.location.reload()
    }
    return Promise.reject(error)
  }
)

// Reactive data
const products = ref([])
const vendors = ref([])
const categories = ref([])
const searchQuery = ref('')

// Loading states
const isLoading = ref(false)
const isModalLoading = ref(false)
const isStockLoading = ref(false)
const isDeletingProduct = ref(null) // Track which product is being deleted

// Ensure categories are loaded from Pinia store
const categoryStore = useCategoryStore()
const clientStore = useClientStore()

// Modal states
const showModal = ref(false)
const showStockModal = ref(false)
const isEditing = ref(false)
const currentProduct = ref(null)

// Filters
const filters = ref({
  date: '',
  vendor: '',
  category: ''
})

// Pagination
const pagination = ref({
  current_page: 1,
  per_page: 15,
  total: 0,
  last_page: 1,
  from: 1,
  to: 15,
  prev_page_url: null,
  next_page_url: null
})

// Form data
const form = ref({
  sku: '',
  product_name: '',
  description: '',
  category_id: '',
  vendor_id: '',
  initial_quantity: 0,
  stock_threshold: 0,
  price: 0
})

// Form validation errors
const formErrors = ref({})

// Stock adjustment form
const stockForm = ref({
  product: null,
  quantity: 0,
  reason: ''
})

// Stock form validation errors
const stockFormErrors = ref({})

// Computed properties
const visiblePages = computed(() => {
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []

  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }

  return pages
})

// Helper methods for data extraction
const getProductImage = (product) => {
  const primaryImage = product.media?.find(m => m.media_type === 'image' && m.is_primary) 
    || product.media?.find(m => m.media_type === 'image')
  return primaryImage?.url || '/api/placeholder/50/50'
}

const getCategoryName = (categoryId) => {
  const category = categories.value.find(c => c.id === categoryId)
  return category?.name || 'Uncategorized'
}

const getCurrentStock = (product) => {
  return product.stocks?.[0]?.current_stock || 0
}

const getCommittedStock = (product) => {
  return product.stocks?.[0]?.committed_stock || 0
}

const getDefectedStock = (product) => {
  return product.stocks?.[0]?.defected_stock || 0
}

const getStockThreshold = (product) => {
  return product.stocks?.[0]?.stock_threshold || 0
}

const getProductPrice = (product) => {
  const price = product.prices?.[0]
  return Number(price?.base_price || price?.discount_price || price?.price || 0)
}

// Debounced search
let searchTimeout = null
const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchProducts(1)
  }, 500)
}

// Watch filters for auto-apply
watch(filters, () => {
  applyFilters()
}, { deep: true })

// Methods
const fetchProducts = async (page = 1) => {
  try {
    isLoading.value = true
    
    // Build query parameters
    const params = {
      page: page,
      per_page: pagination.value.per_page
    }
    
    if (searchQuery.value) {
      params.search = searchQuery.value
    }
    
    if (filters.value.vendor) {
      params.vendor_id = filters.value.vendor
    }
    
    if (filters.value.category) {
      params.category_id = filters.value.category
    }
    
    if (filters.value.date) {
      params.created_date = filters.value.date
    }

    const response = await apiClient.get('/products', { params })
    const data = response.data

    // Handle both paginated and non-paginated responses
    if (data.data && Array.isArray(data.data)) {
      // Paginated response
      products.value = data.data
      pagination.value = {
        current_page: data.current_page || 1,
        per_page: data.per_page || 15,
        total: data.total || data.data.length,
        last_page: data.last_page || 1,
        from: data.from || 1,
        to: data.to || data.data.length,
        prev_page_url: data.prev_page_url,
        next_page_url: data.next_page_url
      }
    } else if (Array.isArray(data)) {
      // Direct array response
      products.value = data
      pagination.value = {
        current_page: 1,
        per_page: data.length,
        total: data.length,
        last_page: 1,
        from: 1,
        to: data.length,
        prev_page_url: null,
        next_page_url: null
      }
    }

  } catch (error) {
    console.error('Error fetching products:', error)
    
    // Handle different types of errors
    if (error.response) {
      const status = error.response.status
      const message = error.response.data?.message || 'Failed to load products'
      
      if (status === 401) {
        alert('Please log in to continue')
        // Redirect to login if needed
        // window.location.href = '/login'
      } else if (status === 403) {
        alert('You do not have permission to view products')
      } else if (status >= 500) {
        alert('Server error. Please try again later.')
      } else {
        alert(message)
      }
    } else if (error.request) {
      alert('Network error. Please check your connection and try again.')
    } else {
      alert('An unexpected error occurred')
    }
  } finally {
    isLoading.value = false
  }
}

const fetchVendors = async () => {
  try {
    if (!clientStore.vendors || clientStore.vendors.length === 0) {
      await clientStore.fetchVendors?.()
    }
    vendors.value = clientStore.vendors || []
  } catch (error) {
    console.error('Error fetching vendors:', error)
    // Try direct API call if store fails
    try {
      const response = await apiClient.get('/vendors')
      vendors.value = response.data.data || response.data || []
    } catch (apiError) {
      console.error('Error fetching vendors from API:', apiError)
    }
  }
}

const fetchCategories = async () => {
  try {
    if (!categoryStore.categories || categoryStore.categories.length === 0) {
      await categoryStore.fetchCategories?.()
    }
    categories.value = categoryStore.categories || []
  } catch (error) {
    console.error('Error fetching categories:', error)
    // Try direct API call if store fails
    try {
      const response = await apiClient.get('/categories')
      categories.value = response.data.data || response.data || []
    } catch (apiError) {
      console.error('Error fetching categories from API:', apiError)
    }
  }
}

const applyFilters = () => {
  fetchProducts(1)
}

const resetFilters = () => {
  filters.value = {
    date: '',
    vendor: '',
    category: ''
  }
  searchQuery.value = ''
  fetchProducts(1)
}

const openCreateModal = () => {
  isEditing.value = false
  formErrors.value = {}
  form.value = {
    sku: '',
    product_name: '',
    description: '',
    category_id: '',
    vendor_id: '',
    initial_quantity: 0,
    stock_threshold: 0,
    price: 0
  }
  showModal.value = true
}

const editProduct = (product) => {
  isEditing.value = true
  currentProduct.value = product
  formErrors.value = {}
  form.value = {
    sku: product.sku,
    product_name: product.product_name,
    description: product.description,
    category_id: product.category_id,
    vendor_id: product.vendor_id || product.vendor?.id,
    initial_quantity: getCurrentStock(product),
    stock_threshold: getStockThreshold(product),
    price: getProductPrice(product)
  }
  showModal.value = true
}

const viewProduct = (product) => {
  // Navigate to product detail page or open view modal
  console.log('Viewing product:', product)
  // You can implement navigation here: router.push(`/products/${product.id}`)
}

const closeModal = () => {
  showModal.value = false
  isEditing.value = false
  currentProduct.value = null
  formErrors.value = {}
  form.value = {
    sku: '',
    product_name: '',
    description: '',
    category_id: '',
    vendor_id: '',
    initial_quantity: 0,
    stock_threshold: 0,
    price: 0
  }
}

const saveProduct = async () => {
  try {
    isModalLoading.value = true
    formErrors.value = {}
    
    const endpoint = isEditing.value 
      ? `/products/${currentProduct.value.id}` 
      : '/products'
    
    const method = isEditing.value ? 'put' : 'post'

    const response = await apiClient[method](endpoint, form.value)

    // Show success message
    const message = isEditing.value ? 'Product updated successfully' : 'Product created successfully'
    alert(message)
    
    closeModal()
    fetchProducts(pagination.value.current_page)

  } catch (error) {
    console.error('Error saving product:', error)
    
    if (error.response?.status === 422) {
      // Validation errors
      formErrors.value = error.response.data.errors || {}
      const firstError = Object.values(formErrors.value)[0]?.[0]
      if (firstError) {
        alert('Validation error: ' + firstError)
      }
    } else {
      const message = error.response?.data?.message || 'Error saving product'
      alert(message)
    }
  } finally {
    isModalLoading.value = false
  }
}

const deleteProduct = async (product) => {
  if (confirm(`Are you sure you want to delete "${product.product_name}"?`)) {
    try {
      isDeletingProduct.value = product.id
      
      await apiClient.delete(`/products/${product.id}`)
      
      alert('Product deleted successfully')
      fetchProducts(pagination.value.current_page)
      
    } catch (error) {
      console.error('Error deleting product:', error)
      const message = error.response?.data?.message || 'Error deleting product'
      alert(message)
    } finally {
      isDeletingProduct.value = null
    }
  }
}





const adjustStock = async () => {
  try {
    isStockLoading.value = true
    stockFormErrors.value = {}

    await apiClient.put(`/products/${stockForm.value.product.id}`, {
      stock: {
        current_quantity: stockForm.value.quantity,
        track: true // set as needed
      },
      reason: stockForm.value.reason
    })

    alert('Stock adjusted successfully')
    closeStockModal()
    fetchProducts(pagination.value.current_page)

  } catch (error) {
    console.error('Error adjusting stock:', error)

    if (error.response?.status === 422) {
      stockFormErrors.value = error.response.data.errors || {}
      const firstError = Object.values(stockFormErrors.value)[0]?.[0]
      if (firstError) {
        alert('Validation error: ' + firstError)
      }
    } else {
      const message = error.response?.data?.message || 'Error adjusting stock'
      alert(message)
    }
  } finally {
    isStockLoading.value = false
  }
}


const openMoveStockModal = (product) => {
  // Implement stock movement functionality
  console.log('Move stock for product:', product)
  alert('Stock movement feature will be implemented here')
}

const closeStockModal = () => {
  showStockModal.value = false
  stockFormErrors.value = {}
  stockForm.value = {
    product: null,
    quantity: 0,
    reason: ''
  }
}

const saveStockAdjustment = async () => {
  try {
    isStockLoading.value = true
    stockFormErrors.value = {}
    
    // await apiClient.post(`/products/${stockForm.value.product.id}/adjust-stock`, {
    await apiClient.put(`/products/${stockForm.value.product.id}`, {

    current_stock: stockForm.value.quantity,
      reason: stockForm.value.reason,
      warehouse_id: 1 // Assuming default warehouse
    })

    alert('Stock adjusted successfully')
    closeStockModal()
    fetchProducts(pagination.value.current_page)
    
  } catch (error) {
    console.error('Error adjusting stock:', error)
    
    if (error.response?.status === 422) {
      // Validation errors
      stockFormErrors.value = error.response.data.errors || {}
      const firstError = Object.values(stockFormErrors.value)[0]?.[0]
      if (firstError) {
        alert('Validation error: ' + firstError)
      }
    } else {
      const message = error.response?.data?.message || 'Error adjusting stock'
      alert(message)
    }
  } finally {
    isStockLoading.value = false
  }
}

const previousPage = () => {
  if (pagination.value.prev_page_url && pagination.value.current_page > 1) {
    fetchProducts(pagination.value.current_page - 1)
  }
}

const nextPage = () => {
  if (pagination.value.next_page_url && pagination.value.current_page < pagination.value.last_page) {
    fetchProducts(pagination.value.current_page + 1)
  }
}

const goToPage = (page) => {
  if (page !== pagination.value.current_page) {
    fetchProducts(page)
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('en-KE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price || 0)
}

// Get CSRF token on component mount
const initializeCsrfToken = async () => {
  try {
    await axios.get(`${API_BASE_URL}/sanctum/csrf-cookie`)
  } catch (error) {
    console.warn('Could not initialize CSRF token:', error)
  }
}

// Lifecycle
onMounted(async () => {
  await initializeCsrfToken()
  
  await Promise.all([
    fetchCategories(),
    fetchVendors(),
    fetchProducts()
  ])
})

// Export for testing or external access if needed
defineExpose({
  fetchProducts,
  resetFilters,
  formErrors,
  stockFormErrors,
  isLoading,
  isModalLoading,
  isStockLoading
})
</script>