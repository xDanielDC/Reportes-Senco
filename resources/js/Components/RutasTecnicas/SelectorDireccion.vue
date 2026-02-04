<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    clienteId: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['seleccionar']);

const direcciones = ref([]);
const direccionSeleccionada = ref(null);
const cargando = ref(false);
const error = ref(null);

// Función para cargar direcciones - DEFINIDA PRIMERO
const cargarDirecciones = async (clienteId) => {
    if (!clienteId) {
        direcciones.value = [];
        direccionSeleccionada.value = null;
        return;
    }

    cargando.value = true;
    error.value = null;
    
    try {
        const response = await axios.get(route('rutas-tecnicas.direcciones', clienteId));
        direcciones.value = response.data;
        
        console.log('Direcciones cargadas:', response.data); // Debug
        
        // Si solo hay una dirección, seleccionarla automáticamente
        if (direcciones.value.length === 1) {
            seleccionar(direcciones.value[0]);
        } else {
            // Limpiar selección si hay múltiples direcciones
            direccionSeleccionada.value = null;
        }
    } catch (err) {
        console.error('Error al cargar direcciones:', err);
        if (err.response) {
            console.error('Respuesta del servidor:', err.response.data);
        }
        error.value = 'Error al cargar las direcciones del cliente';
        direcciones.value = [];
    } finally {
        cargando.value = false;
    }
};

// Función para seleccionar dirección - INCLUYE CIUDAD EN DIRECCIÓN COMPLETA
const seleccionar = (direccion) => {
    direccionSeleccionada.value = direccion.DireccionId;
    
    // Construir dirección completa incluyendo ciudad y departamento
    let direccionCompleta = direccion.DireccionCompleta;
    
    // Agregar ciudad y departamento al final
    if (direccion.Ciudad || direccion.Departamento) {
        direccionCompleta += ' - ';
        if (direccion.Ciudad) {
            direccionCompleta += direccion.Ciudad;
        }
        if (direccion.Departamento) {
            direccionCompleta += direccion.Ciudad ? ', ' + direccion.Departamento : direccion.Departamento;
        }
    }
    
    // Emitir con la dirección completa mejorada
    emit('seleccionar', {
        ...direccion,
        DireccionCompleta: direccionCompleta
    });
    
    console.log('Dirección seleccionada:', direccionCompleta); // Debug
};

// Watch DESPUÉS de definir las funciones
watch(() => props.clienteId, (nuevoClienteId) => {
    cargarDirecciones(nuevoClienteId);
}, { immediate: true });
</script>

<template>
    <div>
        <!-- Estado de carga -->
        <div v-if="cargando" class="text-center py-4">
            <svg class="animate-spin h-8 w-8 text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="mt-2 text-sm text-gray-500">Cargando direcciones...</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ error }}</p>
                </div>
            </div>
        </div>

        <!-- Sin direcciones -->
        <div v-else-if="direcciones.length === 0 && !cargando" class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">No se encontraron direcciones para este cliente</p>
                </div>
            </div>
        </div>

        <!-- Lista de direcciones CON SCROLL -->
        <div v-else-if="direcciones.length > 0">
            <!-- Header con contador -->
            <div v-if="direcciones.length > 3" class="mb-2 pb-2 border-b border-gray-200 flex items-center justify-between text-xs text-gray-600">
                <span>{{ direcciones.length }} direcciones disponibles</span>
                <span class="text-indigo-600 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                    Scroll para ver más
                </span>
            </div>

            <!-- Lista con scroll limitado -->
            <div class="space-y-2 max-h-96 overflow-y-auto pr-1 custom-scrollbar">
                <div
                    v-for="direccion in direcciones"
                    :key="direccion.DireccionId"
                    @click="seleccionar(direccion)"
                    :class="[
                        'p-4 border-2 rounded-lg cursor-pointer transition-all duration-200',
                        direccionSeleccionada === direccion.DireccionId
                            ? 'border-indigo-600 bg-indigo-50 shadow-md'
                            : 'border-gray-200 hover:border-indigo-300 hover:bg-gray-50 hover:shadow-sm'
                    ]"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0">
                            <!-- Sede -->
                            <div class="flex items-center mb-2">
                                <svg class="w-5 h-5 text-indigo-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                                <span class="font-semibold text-gray-900 truncate">{{ direccion.Sede }}</span>
                            </div>

                            <!-- Dirección -->
                            <div class="flex items-start mb-2">
                                <svg class="w-5 h-5 text-gray-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-sm text-gray-700 break-words">{{ direccion.DireccionCompleta }}</span>
                            </div>

                            <!-- Ciudad y Departamento -->
                            <div class="flex items-center text-xs text-gray-500">
                                <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                </svg>
                                <span class="truncate">{{ direccion.Ciudad }}, {{ direccion.Departamento }}</span>
                            </div>

                            <!-- Nombre de contacto si existe -->
                            <div v-if="direccion.NombreContacto" class="flex items-center text-xs text-gray-500 mt-1">
                                <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <span class="truncate">Contacto: {{ direccion.NombreContacto }}</span>
                            </div>
                        </div>

                        <!-- Indicador de selección -->
                        <div v-if="direccionSeleccionada === direccion.DireccionId" class="ml-3 flex-shrink-0">
                            <div class="w-6 h-6 rounded-full bg-indigo-600 flex items-center justify-center">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contador de direcciones al final -->
            <div class="mt-3 pt-2 border-t border-gray-200 text-xs text-gray-500 text-center">
                {{ direcciones.length }} {{ direcciones.length === 1 ? 'dirección disponible' : 'direcciones disponibles' }}
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Scroll personalizado */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>