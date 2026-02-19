<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AppLayout.vue';
import AppLayout from '@/Layouts/AppLayout.vue';


const props = defineProps({
    rutas: Object,
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

                <!-- Lista de Rutas -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="!rutas?.data || rutas.data.length === 0" class="text-center py-8 text-gray-500">
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
                                            Código Vendedor
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
                                            Estado
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="ruta in rutas.data" :key="ruta.NumeroRuta">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ ruta.NumeroRuta }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ruta.CodVendedor }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ruta.FechaInicio }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ruta.FechaFin }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ruta.totalVisitas }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span 
                                                :class="ruta.cerrada ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                            >
                                                {{ ruta.cerrada ? 'Cerrada' : 'Abierta' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                            <!-- Ver Detalle -->
                                            <button
                                                @click="router.visit(route('rutas-tecnicas.show', ruta.NumeroRuta))"
                                                class="text-indigo-600 hover:text-indigo-900"
                                            >
                                                Ver
                                            </button>
                                            
                                            <!-- XXX Visitas - Solo si está abierta -->
                                            <button
                                                v-if="!ruta.cerrada"
                                                @click="router.visit(route('rutas-tecnicas.show', ruta.NumeroRuta))"
                                                class="text-green-600 hover:text-green-900 ml-2"
                                            >
                                                
                                            </button>
                                            
                                            <!-- Editar - Solo si está abierta -->
                                            <button
                                                v-if="!ruta.cerrada"
                                                @click="router.visit(route('rutas-tecnicas.edit', ruta.NumeroRuta))"
                                                class="text-yellow-600 hover:text-yellow-900 ml-2"
                                            >
                                                Editar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <div v-if="rutas.links" class="mt-4 flex justify-center">
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <template v-for="(link, index) in rutas.links" :key="index">
                                    <button
                                        v-if="link.url"
                                        @click="router.visit(link.url)"
                                        :class="[
                                            link.active ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === rutas.links.length - 1 ? 'rounded-r-md' : '',
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                        ]"
                                        v-html="link.label"
                                    />
                                    <span
                                        v-else
                                        :class="[
                                            link.active ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === rutas.links.length - 1 ? 'rounded-r-md' : '',
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                        ]"
                                        v-html="link.label"
                                    />
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>