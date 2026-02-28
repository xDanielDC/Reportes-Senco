<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import BuscadorClientes from '@/Components/RutasTecnicas/BuscadorClientes.vue';
import SelectorDireccion from '@/Components/RutasTecnicas/SelectorDireccion.vue';
import ListaVisitas from '@/Components/RutasTecnicas/ListaVisitas.vue';
import SugResultadoContactos from '@/Components/RutasTecnicas/SugResultadoContactos.vue';

const page = usePage();

const props = defineProps({
    technicalUsers: {
        type: Array,
        default: () => []
    },
    rutaAbierta: {
        type: Object,
        default: null
    },
    mensaje: {
        type: String,
        default: null
    }
});

const form = useForm({
    fecha_inicio: '',
    fecha_fin: '',
    visitas: []
});

const mostrarModal = ref(false);
const visitaEditando = ref(null);
const clienteSeleccionado = ref(null);
const direccionSeleccionada = ref(null);

const mostrarNotificacionExito = ref(false);
const mensajeExito = ref('');

const visitaForm = ref({
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

const hoy = computed(() => new Date().toISOString().split('T')[0]);

const fechasValidas = computed(() => {
    return form.fecha_inicio && form.fecha_fin;
});

const puedeGuardar = computed(() => {
    return fechasValidas.value && form.visitas.length > 0;
});

const tecnicosAsociados = computed(() => props.technicalUsers || []);
const tieneUnTecnico = computed(() => tecnicosAsociados.value.length === 1);
const tieneTecnicosAsociados = computed(() => tecnicosAsociados.value.length > 0);
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
    visitaForm.value.cod_tecnico = tecnico.codigo_vendedor || '';
};

const seleccionarTecnicoPorId = (tecnicoId) => {
    const tecnico = tecnicosAsociados.value.find((row) => String(row.id) === String(tecnicoId));
    asignarTecnico(tecnico);
};

const prepararTecnicoPorDefecto = () => {
    if (tieneUnTecnico.value) {
        asignarTecnico(tecnicosAsociados.value[0]);
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

watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        mensajeExito.value = flash.success;
        mostrarNotificacionExito.value = true;
        
        setTimeout(() => {
            mostrarNotificacionExito.value = false;
        }, 5000);
    }
}, { deep: true, immediate: true });

const abrirModal = () => {
    resetearVisitaForm();
    visitaEditando.value = null;
    clienteSeleccionado.value = null;
    direccionSeleccionada.value = null;
    mostrarModal.value = true;
};

const cerrarModal = () => {
    mostrarModal.value = false;
    resetearVisitaForm();
};

const resetearVisitaForm = () => {
    visitaForm.value = {
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
    clienteSeleccionado.value = null;
    direccionSeleccionada.value = null;
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

    if (tieneUnTecnico.value) {
        asignarTecnico(tecnicosAsociados.value[0]);
    }
};

const agregarVisita = () => {
    if (!visitaForm.value.cliente_id || !visitaForm.value.fecha_visita || !visitaForm.value.direccion_completa) {
        alert('Por favor completa todos los campos requeridos');
        return;
    }

    if (!visitaForm.value.cod_tecnico) {
        alert('Debes seleccionar un técnico para la visita');
        return;
    }

    if (visitaForm.value.fecha_visita < form.fecha_inicio || visitaForm.value.fecha_visita > form.fecha_fin) {
        alert(`La fecha de visita debe estar entre ${form.fecha_inicio} y ${form.fecha_fin}`);
        return;
    }

    if (visitaEditando.value !== null) {
        form.visitas[visitaEditando.value] = { ...visitaForm.value };
    } else {
        form.visitas.push({ ...visitaForm.value });
    }

    cerrarModal();
};

const editarVisita = (index) => {
    visitaEditando.value = index;
    const visita = form.visitas[index];
    visitaForm.value = { ...visita };

    if (!visitaForm.value.tecnico_user_id && visitaForm.value.cod_tecnico) {
        const tecnico = tecnicosAsociados.value.find((row) => row.codigo_vendedor === visitaForm.value.cod_tecnico);
        if (tecnico) {
            visitaForm.value.tecnico_user_id = tecnico.id;
            visitaForm.value.tecnico_nombre = tecnico.name;
        }
    }

    if (!visitaForm.value.cod_tecnico && tieneUnTecnico.value) {
        asignarTecnico(tecnicosAsociados.value[0]);
    }
    
    // Necesitamos recrear el objeto cliente para que el componente funcione
    clienteSeleccionado.value = {
        ClienteId: visita.cliente_id,
        Nit: visita.nit,
        NombreCliente: visita.nombre_cliente,
        CodAsesor: visita.cod_asesor
    };
    
    mostrarModal.value = true;
};

const eliminarVisita = (index) => {
    if (confirm('¿Estás seguro de eliminar esta visita?')) {
        form.visitas.splice(index, 1);
    }
};

const guardarRuta = () => {
    if (!puedeGuardar.value) {
        alert('Por favor completa todos los campos requeridos y agrega al menos una visita');
        return;
    }

    form.post(route('rutas-tecnicas.store'), {
        preserveScroll: false,
        onSuccess: () => {
            form.reset();
            form.clearErrors();
        },
        onError: (errors) => {
            console.error('Errores:', errors);
        }
    });
};

const cerrarNotificacion = () => {
    mostrarNotificacionExito.value = false;
};
</script>

<template>
    <AppLayout title="Creacion RT">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Nueva Ruta Técnica
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Notificación de Éxito -->
                <transition
                    enter-active-class="transform ease-out duration-300 transition"
                    enter-from-class="translate-y-2 opacity-0"
                    enter-to-class="translate-y-0 opacity-100"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="opacity-100"
                    leave-to-class="opacity-0"
                >
                    <div v-if="mostrarNotificacionExito" class="mb-6">
                        <div class="rounded-md bg-green-50 p-4 border-l-4 border-green-400 shadow-lg">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-1">
                                    <p class="text-sm font-medium text-green-800">
                                        {{ mensajeExito }}
                                    </p>
                                </div>
                                <div class="ml-auto pl-3">
                                    <button
                                        @click="cerrarNotificacion"
                                        type="button"
                                        class="inline-flex rounded-md bg-green-50 p-1.5 text-green-500 hover:bg-green-100"
                                    >
                                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Warning when there's an open route - Propia o Compartida -->
                <div v-if="rutaAbierta" class="mb-6">
                    <!-- RUTA PROPIA: Amarillo -->
                    <div v-if="rutaAbierta.CodVendedor === page.props.auth?.user?.codigo_vendedor" class="rounded-md bg-yellow-50 p-4 border-l-4 border-yellow-400">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-yellow-800">
                                    Ya tienes una ruta abierta. Agrega las visitas allí.
                                </p>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Ruta #{{ rutaAbierta.NumeroRuta }}
                                </p>
                                <a 
                                    :href="route('rutas-tecnicas.edit', rutaAbierta.NumeroRuta)" 
                                    class="mt-2 inline-flex items-center text-sm font-medium text-yellow-800 hover:text-yellow-900 underline"
                                >
                                    Ir a la ruta
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- RUTA COMPARTIDA: Azul -->
                    <div v-else class="rounded-md bg-blue-50 p-4 border-l-4 border-blue-400">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z" />
                                </svg>
                            </div>
                            <div class="ml-3 flex-1">
                                <p class="text-sm font-medium text-blue-800">
                                El asesor <span class="font-semibold">{{ rutaAbierta.NombreVendedor }} ({{ rutaAbierta.CodVendedor }})</span> 
                                ya inició la ruta del <span class="font-semibold">{{ rutaAbierta.FechaInicio }}</span> 
                                al <span class="font-semibold">{{ rutaAbierta.FechaFin }}</span>.
                            </p>
                                <p class="text-sm text-blue-700 mt-1">
                                    Agrega tus visitas a esa ruta.
                                </p>
                                <a 
                                    :href="route('rutas-tecnicas.edit', rutaAbierta.NumeroRuta)" 
                                    class="mt-2 inline-flex items-center text-sm font-medium text-blue-800 hover:text-blue-900 underline"
                                >
                                    Ir a la ruta #{{ rutaAbierta.NumeroRuta }}
                                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notificación de Error -->
                <div v-if="$page.props.flash?.error" class="mb-6">
                    <div class="rounded-md bg-red-50 p-4 border-l-4 border-red-400">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    {{ $page.props.flash.error }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card Principal -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Rango de Fechas -->
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold mb-4 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Periodo de la Ruta
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio *</label>
                                    <input
                                        type="date"
                                        v-model="form.fecha_inicio"
                                        :min="hoy"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin *</label>
                                    <input
                                        type="date"
                                        v-model="form.fecha_fin"
                                        :min="form.fecha_inicio || hoy"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        required
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Visitas -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Visitas Programadas ({{ form.visitas.length }})
                                </h3>
                                <button
                                    type="button"
                                    @click="abrirModal"
                                    :disabled="!fechasValidas"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50 transition"
                                >
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Agregar Visita
                                </button>
                            </div>

                            <div v-if="!fechasValidas" class="bg-blue-50 border-l-4 border-blue-400 p-4">
                                <p class="text-sm text-blue-700">
                                    Por favor, selecciona las fechas de inicio y fin antes de agregar visitas.
                                </p>
                            </div>

                            <div v-else-if="form.visitas.length === 0" class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                <p class="text-sm text-yellow-700">
                                    No hay visitas programadas. Agrega al menos una visita para continuar.
                                </p>
                            </div>

                            <ListaVisitas
                                v-else
                                :visitas="form.visitas"
                                @editar="editarVisita"
                                @eliminar="eliminarVisita"
                            />
                        </div>

                        <!-- Botón Guardar -->
                        <div class="flex justify-end pt-6 border-t">
                            <button
                                type="button"
                                @click="guardarRuta"
                                :disabled="!puedeGuardar || form.processing"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 disabled:opacity-50 transition"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <svg v-else class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ form.processing ? 'Guardando...' : 'Guardar Ruta Técnica' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <teleport to="body">
            <div v-if="mostrarModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="cerrarModal"></div>
                    
                    <div class="relative bg-white rounded-lg shadow-xl max-w-2xl w-full">
                        <div class="p-6">
                            <h3 class="text-lg font-medium mb-4">
                                {{ visitaEditando !== null ? 'Editar Visita' : 'Agregar Nueva Visita' }}
                            </h3>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar Cliente *</label>
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

                            <div v-if="clienteSeleccionado" class="mb-4 p-4 bg-gray-50 rounded">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div><strong>NIT:</strong> {{ visitaForm.nit }}</div>
                                    <div><strong>Asesor:</strong> {{ visitaForm.cod_asesor || 'N/A' }}</div>
                                    <div class="col-span-2"><strong>Cliente:</strong> {{ visitaForm.nombre_cliente }}</div>
                                </div>
                            </div>

                            <div v-if="clienteSeleccionado && visitaForm.cliente_id" class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Dirección *</label>
                                <SelectorDireccion
                                    :cliente-id="visitaForm.cliente_id"
                                    :key="visitaForm.cliente_id"
                                    @seleccionar="seleccionarDireccion"
                                />
                            </div>

                            <div v-if="visitaForm.direccion_id" class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Técnico asignado *</label>

                                <div v-if="!tieneTecnicosAsociados" class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                                    No tienes técnicos asociados. Contacta a un administrador para asignar técnicos a tu usuario.
                                </div>

                                <div v-else-if="tieneUnTecnico" class="rounded-md bg-blue-50 border border-blue-200 p-3 text-sm text-blue-700">
                                    Técnico asignado automáticamente:
                                    <strong>{{ tecnicosAsociados[0].name }}</strong>
                                    (Cod: {{ tecnicosAsociados[0].codigo_vendedor || 'N/A' }})
                                </div>

                                <select
                                    v-else
                                    v-model="visitaForm.tecnico_user_id"
                                    @change="seleccionarTecnicoPorId(visitaForm.tecnico_user_id)"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option :value="null" disabled>Seleccione un técnico...</option>
                                    <option
                                        v-for="tecnico in tecnicosAsociados"
                                        :key="tecnico.id"
                                        :value="tecnico.id"
                                    >
                                        {{ tecnico.name }} | Cod: {{ tecnico.codigo_vendedor || 'N/A' }}
                                    </option>
                                </select>

                                <div v-if="tecnicoSeleccionadoActual" class="mt-3 rounded-md border border-indigo-200 bg-indigo-50 p-3">
                                    <div class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                        Tecnico seleccionado
                                    </div>
                                    <div class="mt-2 text-sm text-indigo-900">
                                        <strong>{{ tecnicoSeleccionadoActual.name }}</strong>
                                        <span class="ml-2">Cod: {{ tecnicoSeleccionadoActual.codigo_vendedor || visitaForm.cod_tecnico || 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Visita *</label>
                                <input
                                    type="date"
                                    v-model="visitaForm.fecha_visita"
                                    :min="form.fecha_inicio"
                                    :max="form.fecha_fin"
                                    class="w-full rounded-md border-gray-300"
                                />
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
                                    class="w-full rounded-md border-gray-300"
                                    placeholder="Observaciones..."
                                ></textarea>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-6 py-3 flex justify-end space-x-3">
                            <button
                                @click="cerrarModal"
                                class="px-4 py-2 border rounded-md text-gray-700 hover:bg-gray-100"
                            >
                                Cancelar
                            </button>
                            <button
                                @click="agregarVisita"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700"
                            >
                                {{ visitaEditando !== null ? 'Actualizar' : 'Agregar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </teleport>
    </AppLayout>
</template>
