<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const emit = defineEmits(['seleccionar']);

const searchTerm = ref('');
const resultados = ref([]);
const buscando = ref(false);
const inputFocused = ref(false);
let timeoutId = null;

const mostrarResultados = computed(() => {
    return inputFocused.value && (buscando.value || searchTerm.value.length > 0);
});

const buscar = async () => {
    if (timeoutId) clearTimeout(timeoutId);

    if (!searchTerm.value || searchTerm.value.length < 2) {
        resultados.value = [];
        return;
    }

    timeoutId = setTimeout(async () => {
        buscando.value = true;
        try {
            const response = await axios.get(route('rutas-tecnicas.buscar-clientes'), {
                params: { q: searchTerm.value }
            });
            resultados.value = response.data;
            console.log('Resultados de búsqueda:', response.data);
        } catch (error) {
            console.error('Error al buscar clientes:', error);
            if (error.response) {
                console.error('Respuesta del servidor:', error.response.data);
            }
            resultados.value = [];
        } finally {
            buscando.value = false;
        }
    }, 500);
};

const seleccionar = (cliente) => {
    searchTerm.value = `${cliente.NombreCliente} (${cliente.Nit})`;
    inputFocused.value = false;
    resultados.value = [];
    
    emit('seleccionar', {
        ClienteId: cliente.Nit,
        Nit: cliente.Nit,
        NombreCliente: cliente.NombreCliente,
        CodAsesor: cliente.CodAsesor,
        NombreAsesor: cliente.NombreAsesor,
        Zona: cliente.Zona
    });
};

const handleBlur = () => {
    setTimeout(() => {
        inputFocused.value = false;
    }, 200);
};
</script>

<template>
    <div class="relative">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input
                type="text"
                v-model="searchTerm"
                @input="buscar"
                @focus="inputFocused = true"
                @blur="handleBlur"
                placeholder="Buscar por NIT o nombre..."
                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            />
            <div v-if="buscando" class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                <svg class="animate-spin h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>

        <!-- Dropdown de resultados con SCROLL LIMITADO -->
        <div
            v-if="mostrarResultados"
            class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm max-h-80 overflow-y-auto"
        >
            <!-- Buscando -->
            <div v-if="buscando" class="px-4 py-3 text-sm text-gray-500 text-center">
                <div class="flex items-center justify-center">
                    <svg class="animate-spin h-5 w-5 text-indigo-600 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Buscando...
                </div>
            </div>

            <!-- Sin resultados -->
            <div v-else-if="!buscando && searchTerm && resultados.length === 0" class="px-4 py-3 text-sm text-gray-500 text-center">
                <div class="flex flex-col items-center">
                    <svg class="h-12 w-12 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="font-medium">No se encontraron clientes</p>
                    <p class="text-xs text-gray-400 mt-1">Intenta con otro término de búsqueda</p>
                </div>
            </div>

            <!-- Resultados -->
            <div v-else-if="resultados.length > 0">
                <!-- Indicador de cantidad de resultados -->
                <div class="sticky top-0 bg-gray-50 px-4 py-2 border-b border-gray-200 text-xs text-gray-600 flex items-center justify-between z-10">
                    <span>{{ resultados.length }} resultado(s) encontrado(s)</span>
                    <span v-if="resultados.length >= 4" class="text-indigo-600 flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                        Scroll para ver más
                    </span>
                </div>

                <!-- Lista de clientes con scroll -->
                <div
                    v-for="cliente in resultados"
                    :key="cliente.Nit"
                    @mousedown="seleccionar(cliente)"
                    class="cursor-pointer select-none relative py-3 pl-3 pr-9 hover:bg-indigo-600 hover:text-white transition-colors border-b border-gray-100 last:border-b-0"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                            <!-- Nombre del cliente -->
                            <p class="font-semibold block truncate text-sm">
                                {{ cliente.NombreCliente }}
                            </p>
                            
                            <!-- Información adicional -->
                            <div class="flex items-center gap-3 mt-1 text-xs opacity-75">
                                <!-- NIT -->
                                <span class="inline-flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                    </svg>
                                    {{ cliente.Nit }}
                                </span>
                                
                                <!-- Asesor -->
                                <span v-if="cliente.CodAsesor" class="inline-flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ cliente.CodAsesor }}
                                </span>
                                
                                <!-- Zona -->
                                <span v-if="cliente.Zona" class="inline-flex items-center">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ cliente.Zona }}
                                </span>
                            </div>
                        </div>

                        <!-- Icono de selección -->
                        <div class="ml-3 flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Mensaje al final si hay muchos resultados -->
                <div v-if="resultados.length >= 10" class="sticky bottom-0 bg-yellow-50 px-4 py-2 border-t border-yellow-200 text-xs text-yellow-800 text-center">
                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    Mostrando los primeros 10 resultados. Sé más específico en la búsqueda.
                </div>
            </div>

            <!-- Ayuda inicial -->
            <div v-else-if="!buscando && !searchTerm" class="px-4 py-8 text-sm text-gray-500 text-center">
                <svg class="h-12 w-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <p class="font-medium text-gray-700">Buscar clientes</p>
                <p class="text-xs text-gray-500 mt-1">Escribe al menos 2 caracteres para buscar</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Scroll personalizado para el dropdown */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 10px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Animación suave para hover */
.cursor-pointer {
    transition: all 0.2s ease;
}
</style>