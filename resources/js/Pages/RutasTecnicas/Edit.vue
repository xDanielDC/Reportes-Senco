<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import BuscadorClientes from '@/Components/RutasTecnicas/BuscadorClientes.vue';
import SelectorDireccion from '@/Components/RutasTecnicas/SelectorDireccion.vue';
import SugResultadoContactos from '@/Components/RutasTecnicas/SugResultadoContactos.vue';

const page = usePage();

const props = defineProps({
    numeroRuta: String,
    fecha_inicio: String,
    fecha_fin: String,
    fecha_inicio_raw: String,
    fecha_fin_raw: String,
    visitas: {
        type: Array,
        default: () => []
    },
    technicalUsers: {
        type: Array,
        default: () => []
    }
});

const form = useForm({
    fecha_inicio: props.fecha_inicio_raw || props.fecha_inicio || '',
    fecha_fin: props.fecha_fin_raw || props.fecha_fin || '',
    visitas: props.visitas || [],
    visitas_ids: props.visitas?.map(v => v.idVisita) || []
});

const mostrarModal = ref(false);
const visitaEditando = ref(null);
const clienteSeleccionado = ref(null);
const direccionSeleccionada = ref(null);

const visitaForm = ref({
    id: null,
    cliente_id: '',
    nit: '',
    nombre_cliente: '',
    direccion_id: '',
    direccion_completa: '',
    fecha_visita: '',
    tel_contacto: '',
    nom_contacto: '',
    observaciones: '',
    cod_asesor: '',
    tecnico_user_id: null,
    tecnico_nombre: '',
    cod_tecnico: '',
});

const tecnicosAsociados = computed(() => props.technicalUsers || []);
const tieneUnTecnico = computed(() => tecnicosAsociados.value.length === 1);
const puedeEliminar = computed(() => page.props.auth?.user?.permissions?.includes('rutas-tecnicas.eliminar'));

const puedeGuardar = computed(() => {
    return form.fecha_inicio && form.fecha_fin && form.visitas.length > 0;
});

const prepararTecnicoPorDefecto = () => {
    if (tieneUnTecnico.value) {
        const tecnico = tecnicosAsociados.value[0];
        visitaForm.value.tecnico_user_id = tecnico.id;
        visitaForm.value.tecnico_nombre = tecnico.name;
        visitaForm.value.cod_tecnico = tecnico.codigo_vendedor;
    }
};

// Verificar si el asesor actual puede usar "Taller Senco"
const asesoresToaller = ['097', '070', '094'];
const puedeUsarTaller = computed(() => {
    const userCodVendedor = page.props.auth?.user?.codigo_vendedor || '';
    return asesoresToaller.includes(userCodVendedor);
});

const usarTallerSenco = () => {
    clienteSeleccionado.value = {
        ClienteId: '8110008316',
        Nit: '8110008316',
        NombreCliente: 'Taller Senco',
        CodAsesor: page.props.auth?.user?.codigo_vendedor || ''
    };
    visitaForm.value.cliente_id = '8110008316';
    visitaForm.value.nit = '8110008316';
    visitaForm.value.nombre_cliente = 'Taller Senco';
    visitaForm.value.cod_asesor = page.props.auth?.user?.codigo_vendedor || '';
    
    // Establecer automáticamente la dirección de Senco
    direccionSeleccionada.value = {
        DireccionId: 'TALLER-SENCO-LA',
        DireccionCompleta: 'Autopista Medellín-Bogotá #Km 38, Marinilla, Antioquia',
        NombreContacto: ''
    };
    visitaForm.value.direccion_id = 'TALLER-SENCO-LA';
    visitaForm.value.direccion_completa = 'Autopista Medellín-Bogotá #Km 38, Marinilla, Antioquia';
};

const tecnicoSeleccionadoActual = computed(() => {
    if (!visitaForm.value.cod_tecnico && !visitaForm.value.tecnico_user_id) {
        return null;
    }

    const porId = tecnicosAsociados.value.find((row) => String(row.id) === String(visitaForm.value.tecnico_user_id));
    if (porId) {
        return porId;
    }

    const porCodigo = tecnicosAsociados.value.find((row) => row.codigo_vendedor === visitaForm.value.cod_tecnico);
    if (porCodigo) {
        return porCodigo;
    }

    return {
        id: visitaForm.value.tecnico_user_id || 'manual',
        name: visitaForm.value.tecnico_nombre || 'Tecnico',
        codigo_vendedor: visitaForm.value.cod_tecnico || 'N/A',
    };
});

const asignarTecnico = (tecnico) => {
    if (!tecnico) {
        visitaForm.value.tecnico_user_id = null;
        visitaForm.value.tecnico_nombre = '';
        visitaForm.value.cod_tecnico = '';
        return;
    }

    visitaForm.value.tecnico_user_id = tecnico.id;
    visitaForm.value.tecnico_nombre = tecnico.name;
    visitaForm.value.cod_tecnico = tecnico.codigo_vendedor;
};

const abrirModalEditar = (visita, index) => {
    visitaEditando.value = index;
    visitaForm.value = {
        id: visita.idVisita,
        cliente_id: visita.cliente_id || visita.nit,
        nit: visita.nit,
        nombre_cliente: visita.nombre_cliente,
        direccion_id: visita.direccion_id || '',
        direccion_completa: visita.direccion_completa,
        fecha_visita: visita.fecha_visita_raw || visita.fecha_visita,
        tel_contacto: visita.tel_contacto || '',
        nom_contacto: visita.nom_contacto || '',
        observaciones: visita.observaciones || '',
        cod_asesor: visita.cod_asesor || '',
        tecnico_user_id: null,
        tecnico_nombre: '',
        cod_tecnico: visita.cod_tecnico || '',
    };
    
    // Buscar técnico por código
    if (visita.cod_tecnico) {
        const tecnico = tecnicosAsociados.value.find(t => t.codigo_vendedor === visita.cod_tecnico);
        if (tecnico) {
            visitaForm.value.tecnico_user_id = tecnico.id;
            visitaForm.value.tecnico_nombre = tecnico.name;
        }
    }
    
    mostrarModal.value = true;
};

const abrirModalNueva = () => {
    visitaEditando.value = null;
    resetearVisitaForm();
    mostrarModal.value = true;
};

const cerrarModal = () => {
    mostrarModal.value = false;
    resetearVisitaForm();
};

const resetearVisitaForm = () => {
    visitaForm.value = {
        id: null,
        cliente_id: '',
        nit: '',
        nombre_cliente: '',
        direccion_id: '',
        direccion_completa: '',
        fecha_visita: '',
        tel_contacto: '',
        nom_contacto: '',
        observaciones: '',
        cod_asesor: '',
        tecnico_user_id: null,
        tecnico_nombre: '',
        cod_tecnico: '',
    };
    prepararTecnicoPorDefecto();
};

const seleccionarCliente = (cliente) => {
    clienteSeleccionado.value = cliente;
    visitaForm.value.cliente_id = cliente.ClienteId;
    visitaForm.value.nit = cliente.Nit;
    visitaForm.value.nombre_cliente = cliente.NombreCliente;
    visitaForm.value.cod_asesor = cliente.CodAsesor;
};

const seleccionarDireccion = (direccion) => {
    direccionSeleccionada.value = direccion;
    visitaForm.value.direccion_id = direccion.DireccionId;
    visitaForm.value.direccion_completa = direccion.DireccionCompleta;
};

const guardarVisita = () => {
    if (!visitaForm.value.nit || !visitaForm.value.nombre_cliente || !visitaForm.value.direccion_completa || !visitaForm.value.fecha_visita || !visitaForm.value.cod_tecnico) {
        alert('Por favor complete todos los campos requeridos');
        return;
    }

    // Formatear fecha con día de la semana para mostrar en la tabla
    const fecha = new Date(visitaForm.value.fecha_visita);
    const dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
    const diaSemana = dias[fecha.getDay()];
    const fechaFormateada = `${visitaForm.value.fecha_visita} ${diaSemana}`;

    // Usar fecha cruda (YYYY-MM-DD) para guardar en la base de datos
    const visitaData = {
        id: visitaForm.value.id,
        nit: visitaForm.value.nit,
        nombre_cliente: visitaForm.value.nombre_cliente,
        direccion_completa: visitaForm.value.direccion_completa,
        fecha_visita: visitaForm.value.fecha_visita, // Fecha cruda para guardar
        fecha_visita_raw: visitaForm.value.fecha_visita,
        tel_contacto: visitaForm.value.tel_contacto || '',
        nom_contacto: visitaForm.value.nom_contacto || '',
        observaciones: visitaForm.value.observaciones || '',
        cod_tecnico: visitaForm.value.cod_tecnico,
    };

    if (visitaEditando.value !== null) {
        // Actualizar visita existente - mantener la fecha formateada para display
        form.visitas[visitaEditando.value] = {
            ...visitaData,
            fecha_visita: form.visitas[visitaEditando.value]?.fecha_visita_raw 
                ? `${form.visitas[visitaEditando.value].fecha_visita_raw} ${diaSemana}` 
                : fechaFormateada
        };
    } else {
        // Agregar nueva visita - agregar fecha formateada para display
        form.visitas.push({
            ...visitaData,
            fecha_visita: fechaFormateada
        });
    }

    cerrarModal();
};

const eliminarVisita = (index) => {
    if (!puedeEliminar.value) {
        alert('No tienes permisos para eliminar visitas.');
        return;
    }
    if (confirm('¿Está seguro de eliminar esta visita?')) {
        form.visitas.splice(index, 1);
    }
};

const guardarRuta = () => {
    if (!puedeGuardar.value) {
        alert('Por favor complete las fechas y agregue al menos una visita');
        return;
    }

    form.put(route('rutas-tecnicas.update', props.numeroRuta), {
        onSuccess: () => {
            // Success is handled by the controller redirect
        },
        onError: (errors) => {
            console.error('Error al guardar:', errors);
            alert('Error al guardar la ruta: ' + Object.values(errors).join(', '));
        }
    });
};

onMounted(() => {
    prepararTecnicoPorDefecto();
});
</script>

<template>
    <AppLayout :title="'Editar Ruta ' + numeroRuta">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Editar Ruta Técnica: {{ numeroRuta }}
                </h2>
                <a 
                    :href="route('rutas-tecnicas.show', numeroRuta)"
                    class="ml-2 inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                >
                    Volver
                </a>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Notificación de Error -->
                <div v-if="$page.props.flash?.error" class="mb-6">
                    <div class="rounded-md bg-red-50 p-4 border-l-4 border-red-400">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-red-800">
                                    {{ $page.props.flash.error }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notificación de Éxito -->
                <div v-if="$page.props.flash?.success" class="mb-6">
                    <div class="rounded-md bg-green-50 p-4 border-l-4 border-green-400">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-green-800">
                                    {{ $page.props.flash.success }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fechas -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4">Fechas de la Ruta</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                                <input
                                    type="date"
                                    v-model="form.fecha_inicio"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                                <input
                                    type="date"
                                    v-model="form.fecha_fin"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visitas -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Visitas ({{ form.visitas.length }})</h3>
                            <button
                                type="button"
                                @click="abrirModalNueva"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
                            >
                                + Agregar Visita
                            </button>
                        </div>

                        <div v-if="form.visitas.length === 0" class="text-center py-8 text-gray-500">
                            No hay visitas agregadas. Haga clic en "Agregar Visita" para comenzar.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dirección</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Técnico</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(visita, index) in form.visitas" :key="index">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ index + 1 }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ visita.fecha_visita }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ visita.nombre_cliente }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ visita.direccion_completa }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ visita.cod_tecnico }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button
                                                type="button"
                                                @click="abrirModalEditar(visita, index)"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                type="button"
                                                @click="eliminarVisita(index)"
                                                class="text-red-600 hover:text-red-900"
                                                v-if="puedeEliminar"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Botón Guardar -->
                <div class="flex justify-end">
                    <button
                        type="button"
                        @click="guardarRuta"
                        :disabled="!puedeGuardar"
                        class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-md font-semibold text-base text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Guardar Ruta Técnica
                    </button>
                </div>

            </div>
        </div>

        <!-- Modal para agregar/editar visita -->
        <div v-if="mostrarModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="cerrarModal"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            {{ visitaEditando !== null ? 'Editar Visita' : 'Agregar Visita' }}
                        </h3>
                        
                        <!-- Buscador de Cliente -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>
                            <div class="flex gap-2">
                                <div class="flex-1 relative">
                                    <BuscadorClientes @seleccionar="seleccionarCliente" />

                                    <!-- Botón compacto embebido en el buscador -->
                                    <button
                                        v-if="puedeUsarTaller"
                                        @click="usarTallerSenco"
                                        type="button"
                                        title="Usar Taller Senco"
                                        aria-label="Usar Taller Senco"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center justify-center px-2 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300"
                                    >
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 7l7-4 7 4v6a1 1 0 01-1 1h-1v-3l-3 2-3-2v3H4a1 1 0 01-1-1V7z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Selector de Dirección -->
                        <div v-if="visitaForm.nit" class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dirección</label>
                            <SelectorDireccion 
                                :cliente-id="visitaForm.nit" 
                                @seleccionar="seleccionarDireccion" 
                            />
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Visita *</label>
                            <input
                                type="date"
                                v-model="visitaForm.fecha_visita"
                                :min="form.fecha_inicio"
                                :max="form.fecha_fin"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Técnico *</label>
                            <select
                                v-model="visitaForm.tecnico_user_id"
                                @change="asignarTecnico(tecnicosAsociados.find(t => t.id === visitaForm.tecnico_user_id))"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Seleccione un técnico</option>
                                <option v-for="tecnico in tecnicosAsociados" :key="tecnico.id" :value="tecnico.id">
                                    {{ tecnico.name }} ({{ tecnico.codigo_vendedor }})
                                </option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <SugResultadoContactos
                                :model-value-nombre="visitaForm.nom_contacto"
                                :model-value-telefono="visitaForm.tel_contacto"
                                :cliente-id="visitaForm.nit"
                                :direccion="visitaForm.direccion_completa"
                                @update:model-value-nombre="visitaForm.nom_contacto = $event"
                                @update:model-value-telefono="visitaForm.tel_contacto = $event"
                            />
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                            <textarea
                                v-model="visitaForm.observaciones"
                                rows="3"
                                placeholder="Observaciones de la visita"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            ></textarea>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            @click="guardarVisita"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Guardar
                        </button>
                        <button
                            type="button"
                            @click="cerrarModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
