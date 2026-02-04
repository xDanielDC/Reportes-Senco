<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    rutas: Object,
    filtros: Object,
    permisos: {
        type: Object,
        default: () => ({
            crear: false,
            editar: false,
            eliminar: false
        })
    }
});

const filtrosForm = ref({
    fecha_inicio: props.filtros?.fecha_inicio || '',
    fecha_fin: props.filtros?.fecha_fin || '',
    codigo_vendedor: props.filtros?.codigo_vendedor || ''
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
        fecha_fin: '',
        codigo_vendedor: ''
    };
    aplicarFiltros();
};

const eliminarRuta = (id) => {
    if (!props.permisos.eliminar) {
        alert('No tienes permisos para eliminar rutas técnicas');
        return;
    }

    if (confirm('¿Estás seguro de eliminar esta ruta técnica?')) {
        router.delete(route('rutas-tecnicas.destroy', id));
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Rutas Técnicas
                </h2>
                
                <!-- Botón Crear - Solo si tiene permiso -->
                <button
                    v-if="permisos.crear"
                    @click="router.visit(route('rutas-tecnicas.create'))"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
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
                <!-- Filtros -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Filtros</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Código Vendedor</label>
                                <input
                                    type="text"
                                    v-model="filtrosForm.codigo_vendedor"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Código"
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

                <!-- Lista de Rutas -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="rutas.data.length === 0" class="text-center py-8 text-gray-500">
                            No hay rutas técnicas registradas
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Número Ruta
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha Inicio
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha Fin
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Visitas
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="ruta in rutas.data" :key="ruta.IdTransaccion">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ ruta.NumeroRuta }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ruta.FechaInicio }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ruta.FechaFin }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ruta.visitas?.length || 0 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <!-- Ver Detalle
                                            <button
                                                @click="router.visit(route('rutas-tecnicas.show', ruta.IdTransaccion))"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Ver
                                            </button>

                                            Editar - Solo si tiene permiso 
                                            <button
                                                v-if="permisos.editar"
                                                @click="router.visit(route('rutas-tecnicas.edit', ruta.IdTransaccion))"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                Editar
                                            </button> -->

                                            <!-- Eliminar - Solo si tiene permiso -->
                                            <button
                                                v-if="permisos.eliminar"
                                                @click="eliminarRuta(ruta.IdTransaccion)"
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div v-if="rutas.links" class="mt-4">
                            <!-- Implementar paginación según tu sistema -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>