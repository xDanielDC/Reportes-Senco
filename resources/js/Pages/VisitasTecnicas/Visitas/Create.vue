<script setup>
import { ref, computed } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    id_visita:      { type: String, required: true },
    ruta_tecnica:   { type: Object, required: true },
    tipos_servicio: { type: Array,  default: () => [] },
})

const form = useForm({
    id_visita:        props.id_visita,
    id_tipo_servicio: '',
    correo_cliente:   '',
    fecha_inicio:     '',
    fecha_fin:        '',
    hora_inicio:      '',
    hora_fin:         '',
    // Capacitación
    titulo:           '',
    temas:            '',
    observaciones:    '',
    fotos:            [],
})

// Detectar si es capacitación
const tipoSeleccionado = computed(() =>
    props.tipos_servicio.find(t => t.ID === form.id_tipo_servicio)
)
const esCapacitacion = computed(() =>
    tipoSeleccionado.value
        ? tipoSeleccionado.value.TIPO_SERVICIO.toLowerCase().includes('capacit')
        : false
)

// Modal confirmación
const modalConfirmar = ref(false)

// Canvas firma
const canvasFirma = ref(null)
let dibujando = false

const iniciarFirma = (e) => {
    dibujando = true
    const ctx  = canvasFirma.value.getContext('2d')
    const rect = canvasFirma.value.getBoundingClientRect()
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top
    ctx.beginPath()
    ctx.moveTo(x, y)
}
const dibujarFirma = (e) => {
    if (!dibujando) return
    e.preventDefault()
    const ctx  = canvasFirma.value.getContext('2d')
    const rect = canvasFirma.value.getBoundingClientRect()
    const x = (e.touches ? e.touches[0].clientX : e.clientX) - rect.left
    const y = (e.touches ? e.touches[0].clientY : e.clientY) - rect.top
    ctx.lineWidth   = 2
    ctx.lineCap     = 'round'
    ctx.strokeStyle = '#1e293b'
    ctx.lineTo(x, y)
    ctx.stroke()
}
const terminarFirma = () => { dibujando = false }
const limpiarFirma  = () => {
    const c = canvasFirma.value
    c.getContext('2d').clearRect(0, 0, c.width, c.height)
}

// Fotos
const fotosPreview = ref([])
const agregarFotos = (event) => {
    const archivos = Array.from(event.target.files)
    archivos.forEach(f => {
        fotosPreview.value.push({
            file: f,
            url:  URL.createObjectURL(f),
        })
    })
    form.fotos = fotosPreview.value.map(f => f.file)
}
const eliminarFotoPreview = (index) => {
    fotosPreview.value.splice(index, 1)
    form.fotos = fotosPreview.value.map(f => f.file)
}

// Submit normal (no capacitación)
const submitNormal = () => {
    form.post(route('visitastecnicas.visitas.store'))
}

// Submit capacitación — todo en uno
const submitCapacitacion = () => {
    if (!form.titulo) return

    const data = new FormData()
    data.append('id_visita',        form.id_visita)
    data.append('id_tipo_servicio', form.id_tipo_servicio)
    data.append('correo_cliente',   form.correo_cliente)
    data.append('fecha_inicio',     form.fecha_inicio)
    data.append('fecha_fin',        form.fecha_fin)
    data.append('hora_inicio',      form.hora_inicio)
    data.append('hora_fin',         form.hora_fin)
    data.append('titulo',           form.titulo)
    data.append('temas',            form.temas)
    data.append('firma',            canvasFirma.value.toDataURL('image/png'))
    data.append('observaciones',    form.observaciones)
    data.append('es_capacitacion',  '1')
    fotosPreview.value.forEach(f => data.append('fotos[]', f.file))

    router.post(route('visitastecnicas.visitas.store'), data, {
        forceFormData: true,
    })
}

const puedeFinalizarCapacitacion = computed(() =>
    form.id_tipo_servicio &&
    form.titulo &&
    form.fecha_inicio &&
    form.fecha_fin &&
    form.hora_inicio
)
</script>

<template>
    <AppLayout title="Iniciar Visita">
        <Head title="Iniciar Visita" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Iniciar Visita Técnica
            </h2>
        </template>

        <div class="py-4 sm:py-8">
            <div class="mx-auto max-w-2xl px-3 sm:px-6 lg:px-8 space-y-4">

                <!-- Info cliente -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm border border-gray-100">
                    <div class="border-b border-gray-100 bg-gray-50 px-5 py-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">
                            Datos de la visita
                        </h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-gray-400">Cliente</p>
                                <p class="text-sm font-medium text-gray-800">{{ ruta_tecnica.cliente }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">NIT</p>
                                <p class="text-sm font-medium text-gray-800">{{ ruta_tecnica.nit }}</p>
                            </div>
                            <div class="col-span-2">
                                <p class="text-xs text-gray-400">Dirección</p>
                                <p class="text-sm font-medium text-gray-800">{{ ruta_tecnica.direccion }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Fecha programada</p>
                                <p class="text-sm font-medium text-gray-800">{{ ruta_tecnica.fecha_visita ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">N° Ruta</p>
                                <p class="text-sm font-medium text-gray-800">{{ ruta_tecnica.numero_ruta ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Nombre Contacto</p>
                                <p class="text-sm font-medium text-gray-800">{{ ruta_tecnica.nom_contacto ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Teléfono Contacto</p>
                                <p class="text-sm font-medium text-gray-800">{{ ruta_tecnica.tel_contacto ?? '—' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de servicio -->
                    <div class="px-5 py-4 border-b border-gray-100">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Tipo de servicio <span class="text-red-500">*</span>
                        </label>
                        <select v-model="form.id_tipo_servicio"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                            <option value="" disabled>Seleccione un tipo</option>
                            <option v-for="tipo in tipos_servicio" :key="tipo.ID" :value="tipo.ID"
                                :disabled="tipo.TIPO_SERVICIO.toLowerCase().includes('garantí') || tipo.TIPO_SERVICIO.toLowerCase().includes('garantia')">
                                {{ tipo.TIPO_SERVICIO }}
                                {{ (tipo.TIPO_SERVICIO.toLowerCase().includes('garantí') || tipo.TIPO_SERVICIO.toLowerCase().includes('garantia')) ? '(No disponible)' : '' }}
                            </option>
                        </select>
                        <p v-if="form.errors.id_tipo_servicio" class="mt-1 text-xs text-red-600">
                            {{ form.errors.id_tipo_servicio }}
                        </p>
                    </div>

                    <!-- Correo (solo capacitación) -->
                    <div v-if="esCapacitacion" class="px-5 py-4 border-b border-gray-100">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Correo para informe técnico de la visita
                        </label>
                        <input type="email" v-model="form.correo_cliente"
                            placeholder="cliente@empresa.com"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                        <p v-if="form.errors.correo_cliente" class="mt-1 text-xs text-red-600">
                            {{ form.errors.correo_cliente }}
                        </p>
                    </div>

                    <!-- ── CAPACITACIÓN: campos adicionales ── -->
                    <template v-if="esCapacitacion">

                        <!-- Título -->
                        <div class="px-5 py-4 border-b border-gray-100">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Título de la capacitación <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.titulo"
                                placeholder="Ej: Introducción al nuevo producto XYZ"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                        </div>

                        <!-- Temas tratados -->
                        <div class="px-5 py-4 border-b border-gray-100">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Temas tratados
                            </label>
                            <textarea v-model="form.temas" rows="4"
                                placeholder="Describe los temas tratados en la capacitación..."
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                        </div>

                        <!-- Fotos -->
                        <div class="px-5 py-4 border-b border-gray-100">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fotos</label>
                            <div v-if="fotosPreview.length > 0" class="grid grid-cols-3 gap-2 mb-3">
                                <div v-for="(foto, i) in fotosPreview" :key="i" class="relative group">
                                    <img :src="foto.url"
                                        class="w-full h-24 object-cover rounded-lg border border-gray-200" />
                                    <button type="button" @click="eliminarFotoPreview(i)"
                                        class="absolute top-1 right-1 hidden group-hover:flex items-center justify-center w-6 h-6 rounded-full bg-red-500 text-white text-xs font-bold shadow">
                                        ✕
                                    </button>
                                </div>
                            </div>
                            <label class="flex items-center justify-center gap-2 w-full rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 py-4 cursor-pointer hover:bg-gray-100 transition">
                                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span class="text-sm text-gray-400">Agregar fotos</span>
                                <input type="file" accept="image/*" multiple class="hidden" @change="agregarFotos" />
                            </label>
                        </div>

                        <!-- Fechas y horas (al final en capacitación) -->
                        <div class="px-5 py-4 border-b border-gray-100">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Fecha inicio <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" v-model="form.fecha_inicio"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                                    <p v-if="form.errors.fecha_inicio" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.fecha_inicio }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Fecha fin <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" v-model="form.fecha_fin"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                                    <p v-if="form.errors.fecha_fin" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.fecha_fin }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Hora inicio <span class="text-red-500">*</span>
                                    </label>
                                    <input type="time" v-model="form.hora_inicio"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                                    <p v-if="form.errors.hora_inicio" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.hora_inicio }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hora fin <span class="text-red-500">*</span>
                                    </label>
                                    <input type="time" v-model="form.hora_fin"
                                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                                    <p v-if="form.errors.hora_fin" class="mt-1 text-xs text-red-600">
                                        {{ form.errors.hora_fin }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Botones capacitación -->
                        <div class="px-5 py-4 flex gap-3">
                            <a :href="route('visitastecnicas.visitas.index')"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancelar
                            </a>
                            <button type="button"
                                @click="modalConfirmar = true"
                                :disabled="!puedeFinalizarCapacitacion || form.processing"
                                class="flex-1 inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50 transition">
                                Finalizar visita
                            </button>
                        </div>

                    </template>

                    <!-- ── OTROS SERVICIOS: flujo normal ── -->
                    <template v-else-if="form.id_tipo_servicio">

                        <!-- Botones normal -->
                        <div class="px-5 py-4 flex gap-3">
                            <a :href="route('visitastecnicas.visitas.index')"
                                class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Cancelar
                            </a>
                            <button type="button" @click="submitNormal"
                                :disabled="form.processing || !form.id_tipo_servicio"
                                class="flex-1 inline-flex items-center justify-center rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition">
                                <svg v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                Iniciar Visita
                            </button>
                        </div>

                    </template>

                </div>
            </div>
        </div>

        <!-- Modal confirmación finalizar capacitación -->
        <div v-if="modalConfirmar"
            class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="mb-1 text-base font-semibold text-gray-900">Confirmar finalización</h3>
                <p class="mb-4 text-xs text-gray-500">
                    {{ ruta_tecnica.cliente }} — {{ ruta_tecnica.numero_ruta }}
                </p>
                <!-- Observaciones finales -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Observaciones finales
                    </label>
                    <textarea v-model="form.observaciones" rows="3"
                        placeholder="Observaciones al finalizar la capacitación..."
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <!-- Firma -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">
                            Firma del cliente <span class="text-red-500">*</span>
                        </label>
                        <button type="button" @click="limpiarFirma"
                            class="text-xs text-gray-400 hover:text-gray-600 underline">
                            Limpiar
                        </button>
                    </div>
                    <canvas ref="canvasFirma" width="600" height="150"
                        class="w-full rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 touch-none cursor-crosshair"
                        @mousedown="iniciarFirma" @mousemove="dibujarFirma"
                        @mouseup="terminarFirma" @mouseleave="terminarFirma"
                        @touchstart.prevent="iniciarFirma" @touchmove.prevent="dibujarFirma"
                        @touchend="terminarFirma" />
                    <p class="mt-1 text-xs text-gray-400">Firme en el recuadro</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="modalConfirmar = false"
                        class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button"
                        @click="submitCapacitacion"
                        :disabled="form.processing"
                        class="flex-1 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50">
                        <svg v-if="form.processing" class="inline mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        Confirmar y finalizar
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
