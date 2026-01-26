<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    tipo: String,
    clase: String,
    grupo: String,
    soloConStock: Boolean,
    perPage: Number,
    tipos: Array,
    clases: Array,
    grupos: Array,
    hasFilters: Boolean,
});

const emit = defineEmits([
    'update:tipo',
    'update:clase',
    'update:grupo',
    'update:soloConStock',
    'update:perPage',
    'clear'
]);

const localTipo = ref(props.tipo);
const localClase = ref(props.clase);
const localGrupo = ref(props.grupo);
const localSoloConStock = ref(props.soloConStock);
const localPerPage = ref(props.perPage);

watch(() => props.tipo, (newVal) => { localTipo.value = newVal; });
watch(() => props.clase, (newVal) => { localClase.value = newVal; });
watch(() => props.grupo, (newVal) => { localGrupo.value = newVal; });
watch(() => props.soloConStock, (newVal) => { localSoloConStock.value = newVal; });
watch(() => props.perPage, (newVal) => { localPerPage.value = newVal; });

watch(localTipo, (newVal) => { emit('update:tipo', newVal); });
watch(localClase, (newVal) => { emit('update:clase', newVal); });
watch(localGrupo, (newVal) => { emit('update:grupo', newVal); });
watch(localSoloConStock, (newVal) => { emit('update:soloConStock', newVal); });
watch(localPerPage, (newVal) => { emit('update:perPage', newVal); });

const perPageOptions = [
    { value: 10, label: '10' },
    { value: 15, label: '15' },
    { value: 25, label: '25' },
    { value: 50, label: '50' },
    { value: 100, label: '100' },
];
</script>

<template>
    <div class="space-y-4">
        <!-- Fila 1: Filtros principales -->
        <div class="flex flex-wrap gap-4 items-end">
            <!-- Filtro por Tipo -->
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Tipo
                </label>
                <select
                    v-model="localTipo"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Todos los tipos</option>
                    <option v-for="tipo in tipos" :key="tipo" :value="tipo">
                        {{ tipo }}
                    </option>
                </select>
            </div>

            <!-- Filtro por Clase -->
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Clase
                </label>
                <select
                    v-model="localClase"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Todas las clases</option>
                    <option v-for="clase in clases" :key="clase" :value="clase">
                        {{ clase }}
                    </option>
                </select>
            </div>

            <!-- Filtro por Grupo -->
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Grupo
                </label>
                <select
                    v-model="localGrupo"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Todos los grupos</option>
                    <option v-for="grupo in grupos" :key="grupo" :value="grupo">
                        {{ grupo }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Fila 2: Opciones adicionales -->
        <div class="flex flex-wrap gap-4 items-center">
            <!-- Filtro Solo con Stock -->
            <div class="flex items-center">
                <label class="flex items-center cursor-pointer">
                    <input
                        v-model="localSoloConStock"
                        type="checkbox"
                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                    />
                    <span class="ml-2 text-sm font-medium text-gray-700">
                        Solo con stock disponible
                    </span>
                </label>
            </div>

            <!-- Items por página -->
            <div class="w-32">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Mostrar
                </label>
                <select
                    v-model.number="localPerPage"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                    <option
                        v-for="option in perPageOptions"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <!-- Botón limpiar filtros -->
            <div v-if="hasFilters" class="flex items-end ml-auto">
                <button
                    @click="$emit('clear')"
                    type="button"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                    <svg
                        class="w-4 h-4 mr-2"
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
                    Limpiar filtros
                </button>
            </div>
        </div>
    </div>
</template>