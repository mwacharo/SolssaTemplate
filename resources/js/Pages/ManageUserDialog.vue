<template>
  <v-dialog v-model="dialog" max-width="800px" persistent>
    <v-card>
      <v-card-title class="text-h5 pa-4">
        <span>{{ isEditing ? 'Edit User' : 'Create New User' }}</span>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" variant="text" @click="closeDialog"></v-btn>
      </v-card-title>

      <v-divider></v-divider>

      <v-card-text class="pa-4">
        <v-form ref="form" v-model="valid" @submit.prevent="handleSubmit">
          <v-row>
            <!-- Basic Information -->
            <v-col cols="12">
              <h6 class="text-h6 mb-3">Basic Information</h6>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.name"
                label="Full Name *"
                :rules="nameRules"
                prepend-inner-icon="mdi-account"
                variant="outlined"
                density="compact"
                required
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.username"
                label="Username"
                prepend-inner-icon="mdi-account-circle"
                variant="outlined"
                density="compact"
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.email"
                label="Email Address *"
                :rules="emailRules"
                prepend-inner-icon="mdi-email"
                variant="outlined"
                density="compact"
                type="email"
                required
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6" v-if="!isEditing">
              <v-text-field
                v-model="formData.password"
                label="Password *"
                :rules="passwordRules"
                prepend-inner-icon="mdi-lock"
                :append-inner-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                :type="showPassword ? 'text' : 'password'"
                @click:append-inner="showPassword = !showPassword"
                variant="outlined"
                density="compact"
                required
              ></v-text-field>
            </v-col>

            <!-- Client & Contact Information -->
            <v-col cols="12">
              <v-divider class="my-3"></v-divider>
              <h6 class="text-h6 mb-3">Client & Contact Information</h6>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.client_name"
                label="Client Name"
                prepend-inner-icon="mdi-domain"
                variant="outlined"
                density="compact"
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.phone_number"
                label="Phone Number"
                prepend-inner-icon="mdi-phone"
                variant="outlined"
                density="compact"
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.alt_number"
                label="Alternative Number"
                prepend-inner-icon="mdi-phone-plus"
                variant="outlined"
                density="compact"
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.country_code"
                label="Country Code"
                :items="countryCodes"
                prepend-inner-icon="mdi-flag"
                variant="outlined"
                density="compact"
              ></v-select>
            </v-col>

            <!-- Address Information -->
            <v-col cols="12">
              <v-divider class="my-3"></v-divider>
              <h6 class="text-h6 mb-3">Address Information</h6>
            </v-col>

            <v-col cols="12">
              <v-textarea
                v-model="formData.address"
                label="Address"
                prepend-inner-icon="mdi-map-marker"
                variant="outlined"
                density="compact"
                rows="2"
              ></v-textarea>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.city"
                label="City"
                prepend-inner-icon="mdi-city"
                variant="outlined"
                density="compact"
              ></v-text-field>
            </v-col>

            <v-col cols="12" md="6">
              <v-text-field
                v-model="formData.state"
                label="State/Province"
                prepend-inner-icon="mdi-map"
                variant="outlined"
                density="compact"
              ></v-text-field>
            </v-col>

            <!-- Settings -->
            <v-col cols="12">
              <v-divider class="my-3"></v-divider>
              <h6 class="text-h6 mb-3">Settings</h6>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.timezone"
                label="Timezone"
                :items="timezones"
                prepend-inner-icon="mdi-clock"
                variant="outlined"
                density="compact"
              ></v-select>
            </v-col>

            <v-col cols="12" md="6">
              <v-select
                v-model="formData.language"
                label="Language"
                :items="languages"
                prepend-inner-icon="mdi-translate"
                variant="outlined"
                density="compact"
              ></v-select>
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.is_active"
                label="Active User"
                color="primary"
                inset
              ></v-switch>
            </v-col>

            <v-col cols="12" md="6">
              <v-switch
                v-model="formData.two_factor_enabled"
                label="Two Factor Authentication"
                color="primary"
                inset
              ></v-switch>
            </v-col>

            <v-col cols="12" v-if="isEditing">
              <v-text-field
                v-model="formData.token"
                label="Token"
                prepend-inner-icon="mdi-key"
                variant="outlined"
                density="compact"
                readonly
              ></v-text-field>
            </v-col>
          </v-row>
        </v-form>
      </v-card-text>

      <v-divider></v-divider>

      <v-card-actions class="pa-4">
        <v-spacer></v-spacer>
        <v-btn
          color="grey"
          variant="text"
          @click="closeDialog"
          :disabled="usersStore.loading"
        >
          Cancel
        </v-btn>
        <v-btn
          color="primary"
          variant="elevated"
          @click="handleSubmit"
          :loading="usersStore.loading"
          :disabled="!valid"
        >
          {{ isEditing ? 'Update User' : 'Create User' }}
        </v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { useUsersStore } from '@/stores/users'

// Props
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false
  },
  user: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['update:modelValue', 'user-saved'])

// Store
const usersStore = useUsersStore()

// Reactive data
const dialog = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})

const form = ref(null)
const valid = ref(false)
const showPassword = ref(false)

const isEditing = computed(() => props.user && props.user.id)

// Form data
const formData = reactive({
  name: '',
  email: '',
  password: '',
  client_name: '',
  address: '',
  city: '',
  state: '',
  token: '',
  username: '',
  phone_number: '',
  alt_number: '',
  country_code: '+1',
  time_zone: '',
  language: 'en',
  is_active: true,
  two_factor_enabled: false,
  timezone: 'UTC'
})

// Validation rules
const nameRules = [
  v => !!v || 'Name is required',
  v => (v && v.length >= 2) || 'Name must be at least 2 characters'
]

const emailRules = [
  v => !!v || 'Email is required',
  v => /.+@.+\..+/.test(v) || 'Email must be valid'
]

const passwordRules = computed(() => {
  if (isEditing.value) return []
  return [
    v => !!v || 'Password is required',
    v => (v && v.length >= 8) || 'Password must be at least 8 characters'
  ]
})

// Options data
const countryCodes = [
  { title: '+1 (US/Canada)', value: '+1' },
  { title: '+44 (UK)', value: '+44' },
  { title: '+91 (India)', value: '+91' },
  { title: '+86 (China)', value: '+86' },
  { title: '+49 (Germany)', value: '+49' },
  { title: '+33 (France)', value: '+33' },
  { title: '+81 (Japan)', value: '+81' },
  { title: '+61 (Australia)', value: '+61' },
  { title: '+55 (Brazil)', value: '+55' },
  { title: '+7 (Russia)', value: '+7' },
  { title: '+254 (Kenya)', value: '+254' }
]

const timezones = [
  { title: 'UTC', value: 'UTC' },
  { title: 'America/New_York (EST)', value: 'America/New_York' },
  { title: 'America/Los_Angeles (PST)', value: 'America/Los_Angeles' },
  { title: 'Europe/London (GMT)', value: 'Europe/London' },
  { title: 'Europe/Paris (CET)', value: 'Europe/Paris' },
  { title: 'Asia/Tokyo (JST)', value: 'Asia/Tokyo' },
  { title: 'Asia/Shanghai (CST)', value: 'Asia/Shanghai' },
  { title: 'Asia/Kolkata (IST)', value: 'Asia/Kolkata' },
  { title: 'Australia/Sydney (AEDT)', value: 'Australia/Sydney' },
  { title: 'Africa/Nairobi (EAT)', value: 'Africa/Nairobi' }
]

const languages = [
  { title: 'English', value: 'en' },
  { title: 'Spanish', value: 'es' },
  { title: 'French', value: 'fr' },
  { title: 'German', value: 'de' },
  { title: 'Italian', value: 'it' },
  { title: 'Portuguese', value: 'pt' },
  { title: 'Russian', value: 'ru' },
  { title: 'Chinese', value: 'zh' },
  { title: 'Japanese', value: 'ja' },
  { title: 'Arabic', value: 'ar' },
  { title: 'Swahili', value: 'sw' }
]

// Methods
const resetForm = () => {
  Object.assign(formData, {
    name: '',
    email: '',
    password: '',
    client_name: '',
    address: '',
    city: '',
    state: '',
    token: '',
    username: '',
    phone_number: '',
    alt_number: '',
    country_code: '+1',
    time_zone: '',
    language: 'en',
    is_active: true,
    two_factor_enabled: false,
    timezone: 'UTC'
  })

  if (form.value) {
    form.value.resetValidation()
  }
}

const populateForm = (user) => {
  if (user) {
    Object.keys(formData).forEach(key => {
      if (user[key] !== undefined) {
        formData[key] = user[key]
      }
    })
    // Handle boolean values
    formData.is_active = Boolean(user.is_active)
    formData.two_factor_enabled = Boolean(user.two_factor_enabled)
  }
}

const handleSubmit = async () => {
  if (!form.value) return

  const { valid: isValid } = await form.value.validate()
  if (!isValid) return

  try {
    let result
    const userData = { ...formData }

    // Remove password from update if editing and not provided
    if (isEditing.value && !userData.password) {
      delete userData.password
    }

    if (isEditing.value) {
      result = await usersStore.updateUser(props.user.id, userData)
    } else {
      result = await usersStore.createUser(userData)
    }

    emit('user-saved', result)
    closeDialog()
  } catch (error) {
    console.error('Error saving user:', error)
    // Error is handled by the store
  }
}

const closeDialog = () => {
  dialog.value = false
  resetForm()
}

// Watchers
watch(() => props.user, (newUser) => {
  if (newUser && dialog.value) {
    nextTick(() => {
      populateForm(newUser)
    })
  }
}, { immediate: true })

watch(dialog, (newVal) => {
  if (newVal) {
    if (props.user) {
      populateForm(props.user)
    } else {
      resetForm()
    }
  }
})
</script>

<style scoped>
.v-card-title {
  background-color: rgb(var(--v-theme-surface));
  border-bottom: 1px solid rgb(var(--v-theme-on-surface), 0.12);
}

.v-divider {
  margin: 16px 0;
}

.text-h6 {
  color: rgb(var(--v-theme-primary));
  font-weight: 600;
}
</style>