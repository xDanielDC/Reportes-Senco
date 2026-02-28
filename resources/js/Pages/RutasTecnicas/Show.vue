<script setup>
import { usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AppLayout.vue';

const page = usePage();

const props = defineProps({
    ruta: Object
});

const goBack = () => {
    router.visit(route('rutas-tecnicas.index'));
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Ruta Técnica: {{ ruta.NumeroRuta }}
                    </h2>
                    <span 
                        v-if="ruta.cerrada"
                        class="ml-3 px-2 py-1 bg-red-100 text-red-800 text-xs font-semibold rounded"
                    >
                        Cerrada
                    </span>
                    <span 
                        v-else
                        class="ml-3 px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded"
                    >
                        Abierta
                    </span>
                </div>
                <button
                    @click="goBack"
                    class="ml-2 inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Volver
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Warning when route is closed -->
                <div v-if="ruta.cerrada" class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                Esta ruta técnica está <strong>cerrada</strong>. Ya no puedes agregar, editar o eliminar visitas.
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Información de la Ruta -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Información de la Ruta</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Número de Ruta</label>
                                <p class="mt-1 text-sm text-gray-900 font-semibold">{{ ruta.NumeroRuta }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Código Vendedor</label>
                                <p class="mt-1 text-sm text-gray-900">{{ ruta.CodVendedor }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                                <p class="mt-1 text-sm text-gray-900">{{ ruta.FechaInicio }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha Fin</label>
                                <p class="mt-1 text-sm text-gray-900">{{ ruta.FechaFin }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Total Visitas</label>
                                <p class="mt-1 text-sm text-gray-900 font-semibold">{{ ruta.visitas?.length || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Visitas -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Visitas Programadas</h3>
                        
                        <div v-if="!ruta.visitas || ruta.visitas.length === 0" class="text-center py-8 text-gray-500">
                            No hay visitas programadas para esta ruta.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            #
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Fecha Visita
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Cliente
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nit
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Dirección
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Contacto
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Teléfono
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Técnico
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Observaciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(visita, index) in ruta.visitas" :key="visita.idVisita">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ index + 1 }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ visita.FechaVisita }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <div class="flex items-center justify-between gap-2">
                                                <span>{{ visita.NombreCliente }}</span>
                                                <span v-if="visita.CodVendedor && visita.CodVendedor !== page.props.auth?.user?.codigo_vendedor" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 whitespace-nowrap">
                                                    Asesor: {{ visita.CodVendedor }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ visita.Nit }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                            {{ visita.DireccionCompleta }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ visita.NomContacto || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ visita.TelContacto || '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ visita.CodTecnico || '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                            {{ visita.Observaciones || '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
