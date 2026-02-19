<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    rutas: {
        type: Array,
        default: () => []
    },
    filtros: Object,
    permisos: {
        type: Object,
        default: () => ({
            crear: false,
            editar: false,
            eliminar: false,
            ver: false
        })
    },
    error: {
        type: String,
        default: null
    }
});

const filtrosForm = ref({
    fecha_inicio: props.filtros?.fecha_inicio || '',
    fecha_fin: props.filtros?.fecha_fin || ''
});

const aplicarFiltros = () => {
    router.get(route('rutas-tecnicas.index'), filtrosForm.value, {
        preserveState: true,
        preserveScroll: true
    });
};

const limpiarFiltros = () => {
    filtrosForm.value = {
        fecha_inicio: '',
        fecha_fin: ''
    };
    aplicarFiltros();
};

const table = {
    columns: ['NumeroRuta', 'CodVendedor', 'FechaInicio', 'FechaFin', 'totalVisitas', 'estado', 'acciones'],
    options: {
        headings: {
            NumeroRuta: 'Número Ruta',
            CodVendedor: 'Cód. Vendedor',
            FechaInicio: 'Fecha Inicio',
            FechaFin: 'Fecha Fin',
            totalVisitas: 'Visitas',
            estado: 'Estado',
            acciones: 'Acciones'
        },
        sortable: ['NumeroRuta', 'CodVendedor', 'FechaInicio', 'FechaFin', 'totalVisitas'],
        filterable: ['NumeroRuta', 'CodVendedor', 'FechaInicio', 'FechaFin'],
        perPage: 15,
        perPageValues: [10, 15, 25, 50],
        texts: {
            count: 'Mostrando {from} a {to} de {count} rutas|{count} rutas|Una ruta',
            first: 'Primera',
            last: 'Última',
            filter: 'Buscar:',
            filterPlaceholder: 'Buscar...',
            limit: 'Registros:',
            page: 'Página:',
            noResults: 'No hay rutas técnicas registradas',
            filterBy: 'Filtrar por {column}',
            loading: 'Cargando...',
            defaultOption: 'Seleccionar {column}',
            columns: 'Columnas'
        }
    }
};
</script>

<template>
    <AppLayout :title="'Rutas Técnicas'">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Rutas Técnicas
                </h2>
                
                <!-- Botón Crear - Solo si tiene permiso -->
                <button
                    v-if="permisos.crear"
                    @click="router.visit(route('rutas-tecnicas.create'))"
                    class="ml-2 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nueva Ruta Técnica
                </button>

                <!-- Mensaje si no tiene permiso para crear -->
                <div v-else class="text-sm text-gray-500 italic">
                    No tienes permisos para crear rutas técnicas
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Mensaje de error -->
                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6">
                    <strong class="font-bold">Error: </strong>
                    <span class="block sm:inline">{{ error }}</span>
                </div>

                <!-- Filtros -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Filtros</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                                <input
                                    type="date"
                                    v-model="filtrosForm.fecha_inicio"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                                <input
                                    type="date"
                                    v-model="filtrosForm.fecha_fin"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-4">
                            <button
                                @click="limpiarFiltros"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300"
                            >
                                Limpiar
                            </button>
                            <button
                                @click="aplicarFiltros"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                            >
                                Aplicar Filtros
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Lista de Rutas con v-client-table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <v-client-table
                                :columns="table.columns"
                                :data="rutas"
                                :options="table.options"
                                class="min-w-max"
                            >
                            <template v-slot:estado="{ row }">
                                <span
                                    :class="row.cerrada ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                >
                                    {{ row.cerrada ? 'Cerrada' : 'Abierta' }}
                                </span>
                            </template>

                            <template v-slot:acciones="{ row }">
                                <div class="flex space-x-2">
                                    <!-- Ver Detalle -->
                                    <button
                                        v-if="permisos.ver"
                                        @click="router.visit(route('rutas-tecnicas.show', row.NumeroRuta))"
                                        class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
                                    >
                                        Ver
                                    </button>
                                    
                                    <!-- Editar - Solo si está abierta -->
                                    <button
                                        v-if="!row.cerrada && permisos.editar"
                                        @click="router.visit(route('rutas-tecnicas.edit', row.NumeroRuta))"
                                        class="text-yellow-600 hover:text-yellow-900 text-sm font-medium ml-2"
                                    >
                                        Editar
                                    </button>
                                </div>
                            </template>
                            </v-client-table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
