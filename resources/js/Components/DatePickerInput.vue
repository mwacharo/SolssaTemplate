<template>
    <div>
        <label class="block text-sm font-medium text-red-600 mb-1">
            {{ label }}
        </label>

        <v-menu
            v-model="menu"
            :close-on-content-click="false"
            location="bottom right"
            max-width="360px"
            min-width="290px"
        >
            <template #activator="{ props }">
                <v-text-field
                    v-bind="props"
                    :model-value="displayValue"
                    :label="label"
                    prepend-inner-icon="mdi-calendar"
                    readonly
                    density="compact"
                    hide-details
                    class="w-full"
                    variant="outlined"
                />
            </template>

            <!-- RANGE MODE ENABLED -->
            <v-date-picker
                v-model="internalValue"
                range
                color="blue"
                elevation="20"
                @update:model-value="onPickerChange"
            />
        </v-menu>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    modelValue: Array,
    label: String,
});

const emit = defineEmits(["update:modelValue"]);
const menu = ref(false);

// internal array used by v-date-picker
const internalValue = ref(props.modelValue ?? []);

// update internal when parent changes
watch(
    () => props.modelValue,
    (val) => {
        internalValue.value = val ?? [];
    }
);

// text displayed in the text field
// const displayValue = computed(() => {
//     const [start, end] = internalValue.value || [];
//     if (start && end) return `${start} — ${end}`;
//     if (start) return `${start} —`;
//     return "";
// });

const displayValue = computed(() => {
    const v = internalValue.value;

    // RANGE (array)
    if (Array.isArray(v)) {
        const [start, end] = v;
        if (start && end) return `${formatDate(start)} — ${formatDate(end)}`;
        if (start) return `${formatDate(start)} —`;
        return "";
    }

    // SINGLE DATE (string or Date)
    if (v) return formatDate(v);

    // EMPTY
    return "";
});

function formatDate(value) {
    if (!value) return "";

    if (value instanceof Date) {
        const y = value.getFullYear();
        const m = String(value.getMonth() + 1).padStart(2, "0");
        const d = String(value.getDate()).padStart(2, "0");
        return `${y}-${m}-${d}`;
    }

    // assume ISO string
    return String(value).slice(0, 10);
}

// when user picks dates
const onPickerChange = (val) => {
    emit("update:modelValue", val);

    // close only when full range selected
    if (val && val.length === 2 && val[0] && val[1]) {
        menu.value = false;
    }
};
</script>
