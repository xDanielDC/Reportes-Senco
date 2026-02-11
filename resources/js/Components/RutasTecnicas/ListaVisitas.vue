<script setup>
import { computed } from 'vue';

const props = defineProps({
    visitas: {
        type: Array,
        required: true
    }
});

const emit = defineEmits(['editar', 'eliminar']);

const visitasOrdenadas = computed(() => {
    return [...props.visitas].sort((a, b) => {
        return new Date(a.fecha_visita) - new Date(b.fecha_visita);
    });
});

const clientesUnicos = computed(() => {
    const nits = new Set(props.visitas.map(v => v.nit));
    return nits.size;
});

const asesoresUnicos = computed(() => {
    const asesores = new Set(
        props.visitas.map(v => v.cod_asesor).filter(a => a)
    );
    return asesores.size;
});

const formatearFecha = (fecha) => {
    const date = new Date(fecha + 'T00:00:00');
    
    // Opción 1: Día corto (Lun, Mar, Mié, etc.)
    const diaSemana = date.toLocaleDateString('es-ES', { weekday: 'short' });
    
    // Opción 2: Día completo (si prefieres "Lunes, Martes", etc.)
    // const diaSemana = date.toLocaleDateString('es-ES', { weekday: 'long' });
    
    const fechaCompleta = date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
    
    // Capitalizar primera letra del día
    const diaCapitalizado = diaSemana.charAt(0).toUpperCase() + diaSemana.slice(1);
    
    return `${diaCapitalizado}, ${fechaCompleta}`;
};
</script>

<template>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <!-- Tabla -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            #
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Fecha
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            NIT
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dirección
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Contacto
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Asesor
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Técnico Asignado
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(visita, index) in visitasOrdenadas" :key="index" class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ index + 1 }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatearFecha(visita.fecha_visita) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="font-medium text-gray-900">{{ visita.nombre_cliente }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-mono bg-gray-100 text-gray-800">
                                {{ visita.nit }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                            <div class="flex items-start">
                                <svg class="w-4 h-4 mr-1 text-gray-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span>{{ visita.direccion_completa || 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div v-if="visita.nom_contacto">{{ visita.nom_contacto }}</div>
                            <div v-if="visita.tel_contacto" class="flex items-center text-xs text-gray-400 mt-1">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ visita.tel_contacto }}
                            </div>
                            <div v-if="!visita.nom_contacto && !visita.tel_contacto" class="text-gray-400">-</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span v-if="visita.cod_asesor" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                {{ visita.cod_asesor }}
                            </span>
                            <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="visita.tecnico_nombre || visita.cod_tecnico" class="text-sm text-gray-900">
                                <div class="font-medium">{{ visita.tecnico_nombre || 'Técnico' }}</div>
                                <div class="text-xs text-gray-500">Cod: {{ visita.cod_tecnico || 'N/A' }}</div>
                            </div>
                            <span v-else class="text-gray-400">-</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                            <div class="flex items-center justify-center space-x-2">
                                <button
                                    @click="emit('editar', index)"
                                    class="text-indigo-600 hover:text-indigo-900 p-1 rounded hover:bg-indigo-50 transition-colors"
                                    title="Editar"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button
                                    @click="emit('eliminar', index)"
                                    class="text-red-600 hover:text-red-900 p-1 rounded hover:bg-red-50 transition-colors"
                                    title="Eliminar"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
