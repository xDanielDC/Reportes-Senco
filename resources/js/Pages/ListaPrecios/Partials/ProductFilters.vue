<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';

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

// Opciones dinámicas
const clasesDisponibles = ref([...props.clases]);
const gruposDisponibles = ref([...props.grupos]);
const isLoadingClases = ref(false);
const isLoadingGrupos = ref(false);

// Cargar clases cuando cambia el tipo
const cargarClases = async (tipo) => {
    isLoadingClases.value = true;
    
    try {
        const params = new URLSearchParams();
        if (tipo) params.append('tipo', tipo);
        
        const response = await axios.get(`/lista-precios/api/filtros-dinamicos?${params}`);
        clasesDisponibles.value = response.data.clases || [];
        gruposDisponibles.value = response.data.grupos || [];
        
        // Si la clase seleccionada ya no está disponible, limpiarla
        if (localClase.value && !clasesDisponibles.value.includes(localClase.value)) {
            localClase.value = '';
            localGrupo.value = '';
        }
    } catch (error) {
        console.error('Error cargando clases:', error);
    } finally {
        isLoadingClases.value = false;
    }
};

// Cargar grupos cuando cambia el tipo o clase
const cargarGrupos = async (tipo, clase) => {
    isLoadingGrupos.value = true;
    
    try {
        const params = new URLSearchParams();
        if (tipo) params.append('tipo', tipo);
        if (clase) params.append('clase', clase);
        
        const response = await axios.get(`/lista-precios/api/filtros-dinamicos?${params}`);
        gruposDisponibles.value = response.data.grupos || [];
        
        // Si el grupo seleccionado ya no está disponible, limpiarlo
        if (localGrupo.value && !gruposDisponibles.value.includes(localGrupo.value)) {
            localGrupo.value = '';
        }
    } catch (error) {
        console.error('Error cargando grupos:', error);
    } finally {
        isLoadingGrupos.value = false;
    }
};

// Watchers para sincronizar con props
watch(() => props.tipo, (newVal) => { localTipo.value = newVal; });
watch(() => props.clase, (newVal) => { localClase.value = newVal; });
watch(() => props.grupo, (newVal) => { localGrupo.value = newVal; });
watch(() => props.soloConStock, (newVal) => { localSoloConStock.value = newVal; });
watch(() => props.perPage, (newVal) => { localPerPage.value = newVal; });

// Watchers para filtros dependientes
watch(localTipo, (newTipo) => {
    emit('update:tipo', newTipo);
    
    // Limpiar clase y grupo cuando cambia el tipo
    if (localClase.value || localGrupo.value) {
        localClase.value = '';
        localGrupo.value = '';
        emit('update:clase', '');
        emit('update:grupo', '');
    }
    
    // Cargar clases del tipo seleccionado
    if (newTipo) {
        cargarClases(newTipo);
    } else {
        // Si no hay tipo, mostrar todas las clases
        clasesDisponibles.value = [...props.clases];
        gruposDisponibles.value = [...props.grupos];
    }
});

watch(localClase, (newClase) => {
    emit('update:clase', newClase);
    
    // Limpiar grupo cuando cambia la clase
    if (localGrupo.value) {
        localGrupo.value = '';
        emit('update:grupo', '');
    }
    
    // Cargar grupos de la clase seleccionada
    cargarGrupos(localTipo.value, newClase);
});

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

const handleClear = () => {
    localTipo.value = '';
    localClase.value = '';
    localGrupo.value = '';
    clasesDisponibles.value = [...props.clases];
    gruposDisponibles.value = [...props.grupos];
    emit('clear');
};
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
                    <span v-if="isLoadingClases" class="text-xs text-gray-500">(cargando...)</span>
                </label>
                <select
                    v-model="localClase"
                    :disabled="isLoadingClases || (!localTipo && clasesDisponibles.length === 0)"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                    <option value="">
                        {{ localTipo ? 'Todas las clases' : 'Selecciona un tipo primero' }}
                    </option>
                    <option v-for="clase in clasesDisponibles" :key="clase" :value="clase">
                        {{ clase }}
                    </option>
                </select>
            </div>

            <!-- Filtro por Grupo -->
            <div class="flex-1 min-w-[180px]">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Grupo
                    <span v-if="isLoadingGrupos" class="text-xs text-gray-500">(cargando...)</span>
                </label>
                <select
                    v-model="localGrupo"
                    :disabled="isLoadingGrupos || (!localClase && gruposDisponibles.length === 0)"
                    class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed"
                >
                    <option value="">
                        {{ localClase ? 'Todos los grupos' : 'Selecciona una clase primero' }}
                    </option>
                    <option v-for="grupo in gruposDisponibles" :key="grupo" :value="grupo">
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
                    @click="handleClear"
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

        <!-- Indicador de filtros activos -->
        <div v-if="hasFilters" class="flex flex-wrap gap-2">
            <span class="text-xs text-gray-500">Filtros activos:</span>
            <span v-if="localTipo" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                Tipo: {{ localTipo }}
            </span>
            <span v-if="localClase" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                Clase: {{ localClase }}
            </span>
            <span v-if="localGrupo" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                Grupo: {{ localGrupo }}
            </span>
            <span v-if="localSoloConStock" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                Con stock
            </span>
        </div>
    </div>
</template>