<template>
    <div>
        <label class="block text-sm font-medium text-red-600 mb-1">
            {{ label }}
        </label>

        <v-menu
            v-model="menu"
            :close-on-content-click="false"
            transition="scale-transition"
            location="bottom right"
            max-width="360px"
            min-width="290px"
        >
            <template #activator="{ props }">
                <v-text-field
                    v-bind="props"
                    :model-value="displayValue"
                    prepend-inner-icon="mdi-calendar"
                    readonly
                    density="compact"
                    hide-details
                    variant="outlined"
                />
            </template>

            <v-date-picker
                v-model="internalValue"
                range
                color="primary"
                @update:model-value="onPickerChange"
            />
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
});

const emit = defineEmits(["update:modelValue"]);

const menu = ref(false);
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

// Format the display text
const displayValue = computed(() => {
    if (
        !Array.isArray(internalValue.value) ||
        internalValue.value.length === 0
    ) {
        return "";
    }

    const dates = internalValue.value.map((d) => {
        if (d instanceof Date) {
            return d.toISOString().split("T")[0];
        }
        return d;
    });

    const [start, end] = dates;

    if (start && end) return `${start} — ${end}`;
    if (start) return `${start} —`;

    return "";
});

// Handle user selecting dates
const onPickerChange = (val) => {
    // Ensure val is always an array
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

    // Close menu when both dates are selected
    if (dates.length >= 2) {
        menu.value = false;
    }
};
</script>
