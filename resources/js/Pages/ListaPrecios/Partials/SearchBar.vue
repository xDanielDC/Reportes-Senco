<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: String,
    isLoading: Boolean,
    placeholder: {
        type: String,
        default: 'Buscar por nombre, código, referencia o palabras clave...',
    },
});

const emit = defineEmits(['update:modelValue', 'clear', 'search']);

const localValue = ref(props.modelValue);

watch(
    () => props.modelValue,
    (newVal) => {
        localValue.value = newVal;
    }
);

watch(localValue, (newVal) => {
    emit('update:modelValue', newVal);
});

const handleClear = () => {
    localValue.value = '';
    emit('clear');
};

const handleSearch = () => {
    emit('search');
};

</script>

<template>
    <div class="relative">
        <button
            @click="handleSearch"
            class="absolute inset-y-0 left-0 pl-3 flex items-center"
            type="button"
        >
            <svg
                class="h-5 w-5 text-blue-600"
                :class="{ 'animate-pulse': isLoading }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
            </svg>
        </button>
        <input
            v-model="localValue"
            type="text"
            :placeholder="placeholder"
            class="block w-full pl-10 pr-20 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
            :disabled="isLoading"
            @keyup.enter="handleSearch"
        />
        <button
            v-if="localValue"
            @click="handleClear"
            type="button"
            class="absolute inset-y-0 right-0 pr-3 flex items-center"
        >
            <svg
                class="h-5 w-5 text-gray-400 hover:text-gray-600 transition-colors"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                />
            </svg>
        </button>
    </div>
</template>