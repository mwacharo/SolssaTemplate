<template>
  <div v-if="show" class="fixed inset-0 bg-black/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
    <div class="relative w-full max-w-6xl bg-white/95 backdrop-blur-md shadow-2xl rounded-2xl border border-white/20 max-h-[90vh] overflow-hidden">
      <!-- Header with Progress -->
      <div class="sticky top-0 bg-white/90 backdrop-blur-md border-b border-gray-200/50 px-6 py-4 z-10">
        <div class="flex justify-between items-center mb-4">
          <div>
            <h2 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
              {{ mode === 'create' ? 'Create New Setting' : 'Edit Setting' }}
            </h2>
            <p class="text-gray-600 text-sm mt-1">Configure your call center settings</p>
          </div>
          <button 
            @click="$emit('close')" 
            class="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200 group"
          >
            <svg class="h-6 w-6 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Step Progress Indicator -->
        <div class="flex items-center space-x-4 overflow-x-auto pb-2">
          <div 
            v-for="(step, index) in steps" 
            :key="index"
            class="flex items-center whitespace-nowrap"
          >
            <div class="flex items-center">
              <div 
                :class="[
                  'flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium transition-all duration-300',
                  currentStep === index 
                    ? 'bg-indigo-600 text-white shadow-lg' 
                    : currentStep > index 
                      ? 'bg-green-500 text-white' 
                      : 'bg-gray-200 text-gray-600'
                ]"
              >
                <svg v-if="currentStep > index" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                </svg>
                <span v-else>{{ index + 1 }}</span>
              </div>
              <span 
                :class="[
                  'ml-2 text-sm font-medium transition-colors duration-300',
                  currentStep === index ? 'text-indigo-600' : 'text-gray-600'
                ]"
              >
                {{ step.title }}
              </span>
            </div>
            <div 
              v-if="index < steps.length - 1" 
              :class="[
                'h-0.5 w-12 ml-4 transition-colors duration-300',
                currentStep > index ? 'bg-green-500' : 'bg-gray-200'
              ]"
            ></div>
          </div>
        </div>
      </div>

      <!-- Form Content -->
      <div class="px-6 py-4 overflow-y-auto max-h-[calc(90vh-200px)]">
        <form @submit.prevent="handleSubmit" class="space-y-8">
          <!-- Step 1: Basic Configuration -->
          <div v-show="currentStep === 0" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Username -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                  </svg>
                  Username
                  <span class="text-red-500 ml-1">*</span>
                </label>
                <input
                  v-model="form.username"
                  type="text"
                  required
                  :class="[
                    'w-full px-4 py-3 rounded-xl border-2 transition-all duration-300 bg-white/80 backdrop-blur-sm',
                    formErrors.username 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-200' 
                      : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300'
                  ]"
                  placeholder="Enter username"
                >
                <p v-if="formErrors.username" class="text-red-500 text-xs flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  {{ formErrors.username }}
                </p>
              </div>

              <!-- API Key -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd"/>
                  </svg>
                  API Key
                  <span class="text-red-500 ml-1">*</span>
                </label>
                <div class="relative">
                  <input
                    v-model="form.api_key"
                    :type="showApiKey ? 'text' : 'password'"
                    required
                    :class="[
                      'w-full px-4 py-3 pr-12 rounded-xl border-2 transition-all duration-300 bg-white/80 backdrop-blur-sm',
                      formErrors.api_key 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-200' 
                        : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300'
                    ]"
                    placeholder="Enter API key"
                  >
                  <button
                    type="button"
                    @click="showApiKey = !showApiKey"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                  >
                    <svg v-if="showApiKey" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z" clip-rule="evenodd"/>
                      <path d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z"/>
                    </svg>
                    <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                      <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                </div>
                <p v-if="formErrors.api_key" class="text-red-500 text-xs flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  {{ formErrors.api_key }}
                </p>
              </div>

              <!-- Phone Number -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                  </svg>
                  Phone Number
                  <span class="text-red-500 ml-1">*</span>
                </label>
                <input
                  v-model="form.phone"
                  type="tel"
                  required
                  :class="[
                    'w-full px-4 py-3 rounded-xl border-2 transition-all duration-300 bg-white/80 backdrop-blur-sm',
                    formErrors.phone 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-200' 
                      : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300'
                  ]"
                  placeholder="+254700000000"
                >
                <p v-if="formErrors.phone" class="text-red-500 text-xs flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  {{ formErrors.phone }}
                </p>
              </div>

              <!-- Country ID -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"/>
                  </svg>
                  Country ID
                  <span class="text-red-500 ml-1">*</span>
                </label>
                <input
                  v-model.number="form.country_id"
                  type="number"
                  required
                  min="1"
                  :class="[
                    'w-full px-4 py-3 rounded-xl border-2 transition-all duration-300 bg-white/80 backdrop-blur-sm',
                    formErrors.country_id 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-200' 
                      : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300'
                  ]"
                  placeholder="11"
                >
                <p v-if="formErrors.country_id" class="text-red-500 text-xs flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  {{ formErrors.country_id }}
                </p>
              </div>
            </div>
          </div>

          <!-- Step 2: Voice & Call Settings -->
          <div v-show="currentStep === 1" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              <!-- Default Voice -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.707.707L4.586 13H2a1 1 0 01-1-1V8a1 1 0 011-1h2.586l3.707-3.707a1 1 0 011.09-.217zM15.657 6.343a1 1 0 011.414 0A9.972 9.972 0 0119 12a9.972 9.972 0 01-1.929 5.657 1 1 0 11-1.414-1.414A7.971 7.971 0 0017 12c0-1.594-.471-3.078-1.343-4.343a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 12a5.984 5.984 0 01-.757 2.828 1 1 0 11-1.415-1.414A3.987 3.987 0 0013 12a3.988 3.988 0 00-.172-1.414 1 1 0 010-1.415z" clip-rule="evenodd"/>
                  </svg>
                  Default Voice
                </label>
                <select
                  v-model="form.default_voice"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300 transition-all duration-300 bg-white/80 backdrop-blur-sm"
                >
                  <option v-for="option in voiceOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>

              <!-- Timeout -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                  </svg>
                  Timeout (seconds)
                </label>
                <input
                  v-model.number="form.timeout"
                  type="number"
                  min="1"
                  max="300"
                  :class="[
                    'w-full px-4 py-3 rounded-xl border-2 transition-all duration-300 bg-white/80 backdrop-blur-sm',
                    formErrors.timeout 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-200' 
                      : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300'
                  ]"
                  placeholder="30"
                >
                <p v-if="formErrors.timeout" class="text-red-500 text-xs flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  {{ formErrors.timeout }}
                </p>
              </div>

              <!-- Log Level -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293A1 1 0 0111.293 7.293L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 011.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 011.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"/>
                  </svg>
                  Log Level
                </label>
                <select
                  v-model="form.log_level"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300 transition-all duration-300 bg-white/80 backdrop-blur-sm"
                >
                  <option v-for="option in logLevelOptions" :key="option.value" :value="option.value">
                    {{ option.label }}
                  </option>
                </select>
              </div>
            </div>

            <!-- Toggle Settings -->
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/>
                </svg>
                Advanced Settings
              </h4>
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-gray-200 hover:border-indigo-300 transition-colors">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                      <span class="block text-sm font-medium text-gray-900">Sandbox Mode</span>
                      <span class="text-xs text-gray-500">Test environment</span>
                    </div>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input
                      v-model="form.sandbox"
                      type="checkbox"
                      class="sr-only peer"
                    >
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                  </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-gray-200 hover:border-indigo-300 transition-colors">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                      <span class="block text-sm font-medium text-gray-900">Recording Enabled</span>
                      <span class="text-xs text-gray-500">Record calls</span>
                    </div>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input
                      v-model="form.recording_enabled"
                      type="checkbox"
                      class="sr-only peer"
                    >
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                  </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-gray-200 hover:border-indigo-300 transition-colors">
                  <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                      <span class="block text-sm font-medium text-gray-900">Debug Mode</span>
                      <span class="text-xs text-gray-500">Detailed logging</span>
                    </div>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input
                      v-model="form.debug_mode"
                      type="checkbox"
                      class="sr-only peer"
                    >
                    <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Phone Numbers -->
          <div v-show="currentStep === 2" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Fallback Number -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                  </svg>
                  Fallback Number
                </label>
                <input
                  v-model="form.fallback_number"
                  type="tel"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300 transition-all duration-300 bg-white/80 backdrop-blur-sm"
                  placeholder="+254700000000"
                >
              </div>

              <!-- Default Forward Number -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.707 3.293a1 1 0 010 1.414L5.414 7H11a7 7 0 017 7v2a1 1 0 11-2 0v-2a5 5 0 00-5-5H5.414l2.293 2.293a1 1 0 11-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Default Forward Number
                </label>
                <input
                  v-model="form.default_forward_number"
                  type="tel"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300 transition-all duration-300 bg-white/80 backdrop-blur-sm"
                  placeholder="+254700000001"
                >
              </div>
            </div>
          </div>

          <!-- Step 4: Voice Messages -->
          <div v-show="currentStep === 3" class="space-y-6">
            <!-- Welcome Message -->
            <div class="space-y-2">
              <label class="flex items-center text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z" clip-rule="evenodd"/>
                </svg>
                Welcome Message
              </label>
              <textarea
                v-model="form.welcome_message"
                rows="3"
                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300 transition-all duration-300 bg-white/80 backdrop-blur-sm resize-none"
                placeholder="Welcome to your company. How can we help you today?"
              ></textarea>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- No Input Message -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  No Input Message
                </label>
                <textarea
                  v-model="form.no_input_message"
                  rows="3"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300 transition-all duration-300 bg-white/80 backdrop-blur-sm resize-none"
                  placeholder="We did not receive any input. Please try again."
                ></textarea>
              </div>

              <!-- Invalid Option Message -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                  </svg>
                  Invalid Option Message
                </label>
                <textarea
                  v-model="form.invalid_option_message"
                  rows="3"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300 transition-all duration-300 bg-white/80 backdrop-blur-sm resize-none"
                  placeholder="Invalid option selected. Please choose a valid option."
                ></textarea>
              </div>

              <!-- Connecting Agent Message -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                  </svg>
                  Connecting Agent Message
                </label>
                <textarea
                  v-model="form.connecting_agent_message"
                  rows="3"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300 transition-all duration-300 bg-white/80 backdrop-blur-sm resize-none"
                  placeholder="Please hold while we connect you to an agent."
                ></textarea>
              </div>

              <!-- Agents Busy Message -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                  </svg>
                  Agents Busy Message
                </label>
                <textarea
                  v-model="form.agents_busy_message"
                  rows="3"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300 transition-all duration-300 bg-white/80 backdrop-blur-sm resize-none"
                  placeholder="All agents are currently busy. Please try again later."
                ></textarea>
              </div>
            </div>

            <!-- Voicemail Prompt -->
            <div class="space-y-2">
              <label class="flex items-center text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"/>
                </svg>
                Voicemail Prompt
              </label>
              <textarea
                v-model="form.voicemail_prompt"
                rows="3"
                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300 transition-all duration-300 bg-white/80 backdrop-blur-sm resize-none"
                placeholder="Please leave a message after the tone. We will get back to you shortly."
              ></textarea>
            </div>
          </div>

          <!-- Step 5: URLs & Callbacks -->
          <div v-show="currentStep === 4" class="space-y-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <!-- Callback URL -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                  </svg>
                  Callback URL
                </label>
                <input
                  v-model="form.callback_url"
                  type="url"
                  :class="[
                    'w-full px-4 py-3 rounded-xl border-2 transition-all duration-300 bg-white/80 backdrop-blur-sm',
                    formErrors.callback_url 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-200' 
                      : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300'
                  ]"
                  placeholder="https://yourdomain.com/api/callback"
                >
                <p v-if="formErrors.callback_url" class="text-red-500 text-xs flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  {{ formErrors.callback_url }}
                </p>
              </div>

              <!-- Event Callback URL -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                  </svg>
                  Event Callback URL
                </label>
                <input
                  v-model="form.event_callback_url"
                  type="url"
                  :class="[
                    'w-full px-4 py-3 rounded-xl border-2 transition-all duration-300 bg-white/80 backdrop-blur-sm',
                    formErrors.event_callback_url 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-200' 
                      : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300'
                  ]"
                  placeholder="https://yourdomain.com/api/events"
                >
                <p v-if="formErrors.event_callback_url" class="text-red-500 text-xs flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  {{ formErrors.event_callback_url }}
                </p>
              </div>

              <!-- Ringback Tone URL -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"/>
                  </svg>
                  Ringback Tone URL
                </label>
                <input
                  v-model="form.ringback_tone"
                  type="url"
                  :class="[
                    'w-full px-4 py-3 rounded-xl border-2 transition-all duration-300 bg-white/80 backdrop-blur-sm',
                    formErrors.ringback_tone 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-200' 
                      : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300'
                  ]"
                  placeholder="https://yourdomain.com/storage/ringtones/tone.mp3"
                >
                <p v-if="formErrors.ringback_tone" class="text-red-500 text-xs flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  {{ formErrors.ringback_tone }}
                </p>
              </div>

              <!-- Voicemail Callback URL -->
              <div class="space-y-2">
                <label class="flex items-center text-sm font-semibold text-gray-700">
                  <svg class="w-4 h-4 mr-2 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"/>
                  </svg>
                  Voicemail Callback URL
                </label>
                <input
                  v-model="form.voicemail_callback"
                  type="url"
                  :class="[
                    'w-full px-4 py-3 rounded-xl border-2 transition-all duration-300 bg-white/80 backdrop-blur-sm',
                    formErrors.voicemail_callback 
                      ? 'border-red-300 focus:border-red-500 focus:ring-red-200' 
                      : 'border-gray-200 focus:border-indigo-500 focus:ring-indigo-200 hover:border-gray-300'
                  ]"
                  placeholder="https://yourdomain.com/api/voicemail"
                >
                <p v-if="formErrors.voicemail_callback" class="text-red-500 text-xs flex items-center">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  {{ formErrors.voicemail_callback }}
                </p>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Footer with Navigation -->
      <div class="sticky bottom-0 bg-white/90 backdrop-blur-md border-t border-gray-200/50 px-6 py-4 z-10">
        <div class="flex items-center justify-between">
          <!-- Previous Button -->
          <button
            type="button"
            @click="previousStep"
            :disabled="currentStep === 0"
            :class="[
              'flex items-center px-6 py-3 rounded-xl font-medium transition-all duration-300',
              currentStep === 0
                ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                : 'bg-gray-200 text-gray-700 hover:bg-gray-300 hover:shadow-md'
            ]"
          >
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            Previous
          </button>

          <!-- Step Indicator -->
          <div class="hidden sm:flex items-center space-x-2">
            <span class="text-sm text-gray-500">
              Step {{ currentStep + 1 }} of {{ steps.length }}
            </span>
          </div>

          <!-- Next/Submit Button -->
          <button
            type="button"
            @click="nextStep"
            :disabled="isSubmitting"
            class="flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-300 shadow-lg hover:shadow-xl"
          >
            <span v-if="isSubmitting && currentStep === steps.length - 1" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ mode === 'create' ? 'Creating...' : 'Updating...' }}
            </span>
            <span v-else-if="currentStep === steps.length - 1">
              {{ mode === 'create' ? 'Create Setting' : 'Update Setting' }}
              <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
              </svg>
            </span>
            <span v-else>
              Next
              <svg class="w-4 h-4 ml-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
              </svg>
            </span>
          </button>
        </div>

        <!-- Mobile Step Indicator -->
        <div class="sm:hidden mt-3 text-center">
          <span class="text-sm text-gray-500">
            Step {{ currentStep + 1 }} of {{ steps.length }}: {{ steps[currentStep].title }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import { useCallCenterSettingsComposable } from '@/stores/callCenterSettings'

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  mode: {
    type: String,
    default: 'create',
    validator: (value) => ['create', 'edit'].includes(value)
  },
  setting: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'submit'])

const {
  getDefaultFormData,
  validateForm,
  voiceOptions,
  logLevelOptions
} = useCallCenterSettingsComposable()

// Steps configuration
const steps = [
  { title: 'Basic Info', icon: 'user' },
  { title: 'Voice Settings', icon: 'microphone' },
  { title: 'Phone Numbers', icon: 'phone' },
  { title: 'Messages', icon: 'chat' },
  { title: 'URLs & Callbacks', icon: 'link' }
]

// Form state
const form = reactive(getDefaultFormData())
const formErrors = ref({})
const isSubmitting = ref(false)
const currentStep = ref(0)
const showApiKey = ref(false)

// Step navigation
const nextStep = async () => {
  if (currentStep.value < steps.length - 1) {
    // Validate current step before proceeding
    const stepValid = await validateCurrentStep()
    if (stepValid) {
      currentStep.value++
    }
  } else {
    // Final step - submit form
    await handleSubmit()
  }
}

const previousStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const goToStep = (stepIndex) => {
  if (stepIndex >= 0 && stepIndex < steps.length) {
    currentStep.value = stepIndex
  }
}

// Validate current step
const validateCurrentStep = async () => {
  const validation = validateForm(form)
  formErrors.value = validation.errors

  // Check if current step has any errors
  const currentStepFields = getFieldsForStep(currentStep.value)
  const stepHasErrors = currentStepFields.some(field => validation.errors[field])

  if (stepHasErrors) {
    // Scroll to first error in current step
    const firstErrorField = currentStepFields.find(field => validation.errors[field])
    const errorElement = document.querySelector(`[name="${firstErrorField}"]`)
    if (errorElement) {
      errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' })
      errorElement.focus()
    }
    return false
  }

  return true
}

// Get fields for each step
const getFieldsForStep = (stepIndex) => {
  const stepFields = {
    0: ['username', 'api_key', 'phone', 'country_id'],
    1: ['default_voice', 'timeout', 'log_level'],
    2: ['fallback_number', 'default_forward_number'],
    3: ['welcome_message', 'no_input_message', 'invalid_option_message', 'connecting_agent_message', 'agents_busy_message', 'voicemail_prompt'],
    4: ['callback_url', 'event_callback_url', 'ringback_tone', 'voicemail_callback']
  }
  return stepFields[stepIndex] || []
}

// Fill form when editing
watch(() => props.setting, (newSetting) => {
  if (newSetting && props.mode === 'edit') {
    Object.assign(form, {
      country_id: newSetting.country_id,
      username: newSetting.username,
      api_key: newSetting.api_key || '',
      phone: newSetting.phone,
      sandbox: Boolean(newSetting.sandbox),
      default_voice: newSetting.default_voice,
      timeout: newSetting.timeout,
      recording_enabled: Boolean(newSetting.recording_enabled),
      welcome_message: newSetting.welcome_message || '',
      no_input_message: newSetting.no_input_message || '',
      invalid_option_message: newSetting.invalid_option_message || '',
      connecting_agent_message: newSetting.connecting_agent_message || '',
      agents_busy_message: newSetting.agents_busy_message || '',
      voicemail_prompt: newSetting.voicemail_prompt || '',
      callback_url: newSetting.callback_url || '',
      event_callback_url: newSetting.event_callback_url || '',
      ringback_tone: newSetting.ringback_tone || '',
      voicemail_callback: newSetting.voicemail_callback || '',
      fallback_number: newSetting.fallback_number || '',
      default_forward_number: newSetting.default_forward_number || '',
      debug_mode: Boolean(newSetting.debug_mode),
      log_level: newSetting.log_level
    })
  }
}, { immediate: true })

// Reset form when modal opens in create mode
watch(() => props.show, (show) => {
  if (show && props.mode === 'create') {
    Object.assign(form, getDefaultFormData())
    formErrors.value = {}
    isSubmitting.value = false
    currentStep.value = 0
    showApiKey.value = false
  }
})

// Handle form submission
const handleSubmit = async () => {
  // Clear previous errors
  formErrors.value = {}
  
  // Validate entire form
  const validation = validateForm(form)
  formErrors.value = validation.errors

  if (!validation.isValid) {
    // Find the step with the first error and navigate to it
    for (let stepIndex = 0; stepIndex < steps.length; stepIndex++) {
      const stepFields = getFieldsForStep(stepIndex)
      const stepHasErrors = stepFields.some(field => validation.errors[field])
      
      if (stepHasErrors) {
        currentStep.value = stepIndex
        
        // Scroll to first error
        const firstErrorField = stepFields.find(field => validation.errors[field])
        setTimeout(() => {
          const errorElement = document.querySelector(`[name="${firstErrorField}"]`)
          if (errorElement) {
            errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' })
            errorElement.focus()
          }
        }, 100)
        
        return
      }
    }
    return
  }

  isSubmitting.value = true

  try {
    // Create a clean copy of form data
    const formData = { ...form }
    
    // Convert string numbers to actual numbers
    formData.country_id = Number(formData.country_id)
    formData.timeout = Number(formData.timeout)
    
    // Clean up empty strings
    Object.keys(formData).forEach(key => {
      if (formData[key] === '') {
        formData[key] = null
      }
    })

    // Emit the form data to parent component
    await emit('submit', formData)
  } catch (error) {
    console.error('Form submission error:', error)
  } finally {
    isSubmitting.value = false
  }
}

// Computed property to check if form has changes (for edit mode)
const hasChanges = computed(() => {
  if (props.mode !== 'edit' || !props.setting) return true
  
  const original = props.setting
  return Object.keys(form).some(key => {
    const formValue = form[key]
    const originalValue = original[key]
    
    // Handle boolean conversion
    if (typeof formValue === 'boolean') {
      return formValue !== Boolean(originalValue)
    }
    
    // Handle null/empty string equivalence
    if ((formValue === null || formValue === '') && (originalValue === null || originalValue === '')) {
      return false
    }
    
    return formValue !== originalValue
  })
})

</script>

<style scoped>
/* Enhanced scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: transparent;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #6366f1, #8b5cf6);
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #4f46e5, #7c3aed);
}

/* Focus ring enhancements */
input:focus, select:focus, textarea:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  transform: translateY(-1px);
}

/* Button hover effects */
button:hover:not(:disabled) {
  transform: translateY(-1px);
}

/* Error field shake animation */
.border-red-300 {
  animation: shake 0.6s ease-in-out;
}

@keyframes shake {
  0%, 20%, 40%, 60%, 80% {
    transform: translateX(0);
  }
  10%, 30%, 50%, 70%, 90% {
    transform: translateX(-3px);
  }
}

/* Backdrop blur enhancement */
.backdrop-blur-sm {
  backdrop-filter: blur(4px);
}

.backdrop-blur-md {
  backdrop-filter: blur(8px);
}

/* Glassmorphism effects */
.bg-white\/95 {
  background: rgba(255, 255, 255, 0.95);
}

.bg-white\/90 {
  background: rgba(255, 255, 255, 0.90);
}

.bg-white\/80 {
  background: rgba(255, 255, 255, 0.80);
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .max-w-6xl {
    max-width: calc(100vw - 1rem);
  }
  
  .px-6 {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  .grid-cols-1 {
    gap: 1rem;
  }
}

/* Step transition animations */
.step-transition-enter-active,
.step-transition-leave-active {
  transition: all 0.3s ease-in-out;
}

.step-transition-enter-from {
  opacity: 0;
  transform: translateX(30px);
}

.step-transition-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

/* Progress bar animations */
.step-progress {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Form field enhancements */
.form-field-group {
  transition: all 0.2s ease-in-out;
}

.form-field-group:hover {
  transform: translateY(-1px);
}

/* Toggle switch enhancements */
.peer:checked + div {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
}

/* Responsive grid improvements */
@media (min-width: 768px) {
  .grid-cols-1.md\\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .grid-cols-1.lg\\:grid-cols-2 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  
  .grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

/* Text gradient effects */
.bg-gradient-to-r.from-indigo-600.to-purple-600 {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
}

/* Shadow enhancements */
.shadow-2xl {
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.shadow-lg {
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
}

.hover\\:shadow-xl:hover {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

/* Loading spinner enhancement */
@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

/* Border radius consistency */
.rounded-xl {
  border-radius: 0.75rem;
}

.rounded-2xl {
  border-radius: 1rem;
}

/* Enhanced focus states */
.focus\\:ring-4 {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(4px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}

.focus\\:ring-indigo-300 {
  --tw-ring-color: rgb(165 180 252 / 0.5);
}

/* Improved transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

.duration-300 {
  transition-duration: 300ms;
}

/* Enhanced hover states */
.hover\\:border-gray-300:hover {
  border-color: rgb(209 213 219);
}

.hover\\:bg-gray-50:hover {
  background-color: rgb(249 250 251);
}

.hover\\:bg-gray-100:hover {
  background-color: rgb(243 244 246);
}

.hover\\:bg-gray-300:hover {
  background-color: rgb(209 213 219);
}

.hover\\:from-indigo-700:hover {
  background-image: linear-gradient(to right, rgb(67 56 202), var(--tw-gradient-to));
}

.hover\\:to-purple-700:hover {
  --tw-gradient-to: rgb(126 34 206);
}
</style>