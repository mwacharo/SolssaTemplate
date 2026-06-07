<template>
  <div>
    <label class="block text-sm font-medium mb-1" :class="labelClass">
      {{ label }}
      <span v-if="required" class="text-red-600 ml-1">*</span>
    </label>

    <v-autocomplete
      v-model="internalValue"
      :items="merchants"
      :loading="loading"
      :error="showError"
      :error-messages="errorMessage"
      :hide-details="!showError"
      :placeholder="placeholder"
      :multiple="multiple"
      :clearable="clearable"
      item-title="name"
      item-value="id"
      density="compact"
      variant="outlined"
      prepend-inner-icon="mdi-store"
      no-data-text="No merchants found"
      @update:model-value="onValueChange"
      @update:search="onSearch"
      @blur="touched = true"
    >
      <!-- Selected chip display for multiple mode -->
      <template v-if="multiple" #selection="{ item, index }">
        <v-chip
          v-if="index < maxChips"
          size="small"
          closable
          @click:close="removeItem(item.raw.id)"
        >
          {{ item.title }}
        </v-chip>
        <span
          v-if="index === maxChips"
          class="text-caption text-grey"
        >
          +{{ internalValue.length - maxChips }} more
        </span>
      </template>

      <!-- Item slot -->
      <template #item="{ item, props: itemProps }">
        <v-list-item v-bind="itemProps">
          <template #prepend>
            <v-icon size="small" class="mr-2">mdi-store-outline</v-icon>
          </template>
          <template #subtitle>
            <span class="text-xs text-grey">{{ item.raw.email ?? item.raw.code ?? '' }}</span>
          </template>
        </v-list-item>
      </template>

      <!-- No data -->
      <template #no-data>
        <v-list-item>
          <v-list-item-title>
            {{ loading ? 'Searching...' : 'No merchants found' }}
          </v-list-item-title>
        </v-list-item>
      </template>
    </v-autocomplete>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: [String, Number, Array],
    default: null,
  },
  label: {
    type: String,
    default: 'Merchant',
  },
  required: {
    type: Boolean,
    default: false,
  },
  placeholder: {
    type: String,
    default: 'Search merchant...',
  },
  multiple: {
    type: Boolean,
    default: false,
  },
  clearable: {
    type: Boolean,
    default: true,
  },
  validateOnMount: {
    type: Boolean,
    default: false,
  },
  maxChips: {
    type: Number,
    default: 2,
  },
  // Optional: pre-filter by some param e.g. country
  params: {
    type: Object,
    default: () => ({}),
  },
})

const emit = defineEmits(['update:modelValue', 'validation', 'change'])

const merchants = ref([])
const loading = ref(false)
const touched = ref(props.validateOnMount)
const searchQuery = ref('')
const internalValue = ref(props.modelValue ?? (props.multiple ? [] : null))

let searchTimeout = null

// ── Fetch ─────────────────────────────────────────────────────────────────────

const fetchMerchants = async (search = '') => {
  loading.value = true
  try {
    const { data } = await axios.get('/api/v1/vendors', {
      params: { search, per_page: 20, ...props.params },
    })
    merchants.value = data.data ?? []
  } catch (e) {
    console.error('Failed to load merchants', e)
    merchants.value = []
  } finally {
    loading.value = false
  }
}

// ── Search debounce ───────────────────────────────────────────────────────────

const onSearch = (val) => {
  clearTimeout(searchTimeout)
  searchQuery.value = val ?? ''
  searchTimeout = setTimeout(() => fetchMerchants(searchQuery.value), 350)
}

// ── Value change ──────────────────────────────────────────────────────────────

const onValueChange = (val) => {
  touched.value = true
  internalValue.value = val
  emit('update:modelValue', val)
  emit('change', val)
  emit('validation', isValid.value)
}

const removeItem = (id) => {
  if (!Array.isArray(internalValue.value)) return
  const updated = internalValue.value.filter((v) => v !== id)
  internalValue.value = updated
  emit('update:modelValue', updated)
  emit('validation', isValid.value)
}

// ── Sync parent → internal ────────────────────────────────────────────────────

watch(
  () => props.modelValue,
  (val) => { internalValue.value = val ?? (props.multiple ? [] : null) },
)

// ── Validation ────────────────────────────────────────────────────────────────

const isValid = computed(() => {
  if (!props.required) return true
  if (props.multiple) return Array.isArray(internalValue.value) && internalValue.value.length > 0
  return internalValue.value !== null && internalValue.value !== undefined && internalValue.value !== ''
})

const showError = computed(() => props.required && touched.value && !isValid.value)

const errorMessage = computed(() => {
  if (!showError.value) return ''
  return props.multiple ? 'Please select at least one merchant' : 'Merchant is required'
})

const labelClass = computed(() =>
  props.required && !isValid.value && touched.value ? 'text-red-600' : 'text-gray-700'
)

// ── Lifecycle ─────────────────────────────────────────────────────────────────

onMounted(() => fetchMerchants())

// ── Expose (mirrors your date picker pattern) ─────────────────────────────────

const validate = () => {
  touched.value = true
  return isValid.value
}

defineExpose({ validate, isValid })
</script>