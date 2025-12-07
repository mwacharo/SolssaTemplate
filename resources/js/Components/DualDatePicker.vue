<template>
    <div>
        <label class="block text-sm font-medium mb-1" :class="labelClass">
            {{ label }}
            <span v-if="required" class="text-red-600 ml-1">*</span>
        </label>

        <v-menu
            v-model="menu"
            :close-on-content-click="false"
            transition="scale-transition"
            location="bottom"
            max-width="auto"
            min-width="600px"
        >
            <template #activator="{ props: menuProps }">
                <v-text-field
                    v-bind="menuProps"
                    :model-value="displayValue"
                    prepend-inner-icon="mdi-calendar"
                    readonly
                    density="compact"
                    variant="outlined"
                    :error="showError"
                    :error-messages="errorMessage"
                    :hide-details="!showError"
                    :placeholder="placeholder"
                />
            </template>

            <v-card>
                <v-date-picker
                    v-model="internalValue"
                    :multiple="2"
                    show-adjacent-months
                    color="primary"
                    @update:model-value="onPickerChange"
                />
                <v-card-actions>
                    <v-spacer />
                    <v-btn text @click="clearDates">Clear</v-btn>
                    <v-btn text color="primary" @click="menu = false"
                        >Close</v-btn
                    >
                </v-card-actions>
            </v-card>
        </v-menu>
    </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";

const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
    label: String,
    required: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: "Select date range",
    },
    validateOnMount: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:modelValue", "validation"]);

const menu = ref(false);
const touched = ref(props.validateOnMount);
const internalValue = ref(
    props.modelValue?.length ? [...props.modelValue] : []
);

// Watch for parent changes
watch(
    () => props.modelValue,
    (val) => {
        if (Array.isArray(val)) {
            internalValue.value = val.length ? [...val] : [];
        } else {
            internalValue.value = [];
        }
    }
);

// Validation logic
const isValid = computed(() => {
    if (!props.required) return true;
    return (
        Array.isArray(internalValue.value) && internalValue.value.length === 2
    );
});

const showError = computed(() => {
    return props.required && touched.value && !isValid.value;
});

const errorMessage = computed(() => {
    if (!showError.value) return "";
    if (!internalValue.value || internalValue.value.length === 0) {
        return "Date range is required";
    }
    if (internalValue.value.length === 1) {
        return "Please select an end date";
    }
    return "";
});

const labelClass = computed(() => {
    return props.required && !isValid.value && touched.value
        ? "text-red-600"
        : "text-gray-700";
});

// Format the display text
const displayValue = computed(() => {
    if (
        !Array.isArray(internalValue.value) ||
        internalValue.value.length === 0
    ) {
        return "";
    }

    const sortedDates = [...internalValue.value].sort((a, b) => {
        const dateA = a instanceof Date ? a : new Date(a);
        const dateB = b instanceof Date ? b : new Date(b);
        return dateA - dateB;
    });

    const dates = sortedDates.map((d) => {
        const date = d instanceof Date ? d : new Date(d);
        return date.toLocaleDateString("en-US", {
            month: "short",
            day: "2-digit",
            default: "Select date range",
            year: "numeric",
        });
    });

    const [start, end] = dates;

    if (start && end) return `${start} → ${end}`;
    if (start) return `${start} → ...`;

    return "";
});

// Handle user selecting dates
const onPickerChange = (val) => {
    touched.value = true;

    let dates = [];
    if (Array.isArray(val)) {
        dates = val;
    } else if (val instanceof Date) {
        dates = [val];
    } else if (val) {
        dates = [val];
    }

    internalValue.value = dates;
    emit("update:modelValue", dates);

    // Emit validation state
    const valid = props.required ? dates.length === 2 : true;
    emit("validation", valid);

    // Auto-close when both dates are selected
    if (dates.length >= 2) {
        setTimeout(() => {
            menu.value = false;
        }, 300);
    }
};

const clearDates = () => {
    internalValue.value = [];
    emit("update:modelValue", []);
    emit("validation", !props.required);
};

// Watch menu close to mark as touched
watch(menu, (isOpen) => {
    if (!isOpen && !touched.value) {
        touched.value = true;
    }
});

// Expose validate method for parent components
const validate = () => {
    touched.value = true;
    return isValid.value;
};

defineExpose({
    validate,
    isValid,
});
</script>

<style scoped>
/* Optional: Add custom styles if needed */
</style>
