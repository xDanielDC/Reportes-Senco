<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
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

const tipoEsCapacitacion = (tipo) => tipo?.TIPO_SERVICIO?.toLowerCase().includes('capacit')
const tipoEsGarantia = (tipo) => {
    const nombre = tipo?.TIPO_SERVICIO?.toLowerCase() || ''
    return nombre.includes('garantí') || nombre.includes('garantia')
}

const esVisitaPropia = computed(() => Boolean(props.ruta_tecnica?.es_propia))
const tipoCapacitacion = computed(() => props.tipos_servicio.find(tipoEsCapacitacion))
const tiposServicioDisponibles = computed(() =>
    esVisitaPropia.value
        ? props.tipos_servicio.filter(tipoEsCapacitacion)
        : props.tipos_servicio
)

watch(tipoCapacitacion, (tipo) => {
    if (esVisitaPropia.value && tipo) {
        form.id_tipo_servicio = tipo.ID
    }
}, { immediate: true })

// Detectar si es capacitación
const tipoSeleccionado = computed(() =>
    props.tipos_servicio.find(t => t.ID === form.id_tipo_servicio)
)
const esCapacitacion = computed(() => tipoEsCapacitacion(tipoSeleccionado.value))

// Modal confirmación
const modalConfirmar = ref(false)
const modalInformeEnviado = ref(false)
const enviandoCapacitacion = ref(false)

// Canvas firma
const canvasFirma = ref(null)
const firmaDibujada = ref(false)
const firmaCanvasAlto = 180
let dibujando = false

const configurarContextoFirma = (ctx) => {
    ctx.lineWidth = 2.4
    ctx.lineCap = 'round'
    ctx.lineJoin = 'round'
    ctx.strokeStyle = '#1e293b'
}

const prepararCanvasFirma = () => {
    const canvas = canvasFirma.value
    if (!canvas) return

    const rect = canvas.getBoundingClientRect()
    const ratio = window.devicePixelRatio || 1
    const ancho = Math.max(Math.round(rect.width * ratio), 1)
    const alto = Math.round(firmaCanvasAlto * ratio)

    canvas.style.height = `${firmaCanvasAlto}px`

    if (canvas.width !== ancho || canvas.height !== alto) {
        canvas.width = ancho
        canvas.height = alto
    }

    const ctx = canvas.getContext('2d')
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0)
    configurarContextoFirma(ctx)
}

const puntoFirma = (e) => {
    const rect = canvasFirma.value.getBoundingClientRect()
    return {
        x: e.clientX - rect.left,
        y: e.clientY - rect.top,
    }
}

const iniciarFirma = (e) => {
    if (e.button !== undefined && e.button !== 0) return

    e.preventDefault()
    prepararCanvasFirma()
    dibujando = true
    e.currentTarget?.setPointerCapture?.(e.pointerId)

    const ctx = canvasFirma.value.getContext('2d')
    const punto = puntoFirma(e)
    ctx.beginPath()
    ctx.moveTo(punto.x, punto.y)
}

const dibujarFirma = (e) => {
    if (!dibujando) return

    e.preventDefault()
    const ctx = canvasFirma.value.getContext('2d')
    const punto = puntoFirma(e)
    ctx.lineTo(punto.x, punto.y)
    ctx.stroke()
    firmaDibujada.value = true
}

const terminarFirma = (e) => {
    if (!dibujando) return

    dibujando = false
    e?.currentTarget?.releasePointerCapture?.(e.pointerId)
}

const limpiarFirma = () => {
    const canvas = canvasFirma.value
    if (!canvas) return

    const ctx = canvas.getContext('2d')
    ctx.save()
    ctx.setTransform(1, 0, 0, 1, 0, 0)
    ctx.clearRect(0, 0, canvas.width, canvas.height)
    ctx.restore()
    configurarContextoFirma(ctx)
    firmaDibujada.value = false
}

watch(modalConfirmar, (abierto) => {
    if (abierto) nextTick(prepararCanvasFirma)
})

onMounted(() => window.addEventListener('resize', prepararCanvasFirma))
onUnmounted(() => window.removeEventListener('resize', prepararCanvasFirma))

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

const emailValido = (email) => !email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)

const abrirModalConfirmar = () => {
    form.clearErrors()

    const errores = { }

    if (!form.id_tipo_servicio) errores.id_tipo_servicio = 'El tipo de servicio es obligatorio.'
    if (!form.titulo) errores.titulo = 'El título de la capacitación es obligatorio.'
    if (!form.fecha_inicio) errores.fecha_inicio = 'La fecha de inicio es obligatoria.'
    if (!form.fecha_fin) errores.fecha_fin = 'La fecha fin es obligatoria.'
    if (form.fecha_inicio && form.fecha_fin && form.fecha_fin < form.fecha_inicio) {
        errores.fecha_fin = 'La fecha fin debe ser igual o posterior a la fecha de inicio.'
    }
    if (!form.hora_inicio) errores.hora_inicio = 'La hora de inicio es obligatoria.'
    if (!form.hora_fin) errores.hora_fin = 'La hora fin es obligatoria.'
    if (!emailValido(form.correo_cliente)) {
        errores.correo_cliente = 'El correo para informe técnico debe ser una dirección válida.'
    }

    if (Object.keys(errores).length) {
        Object.entries(errores).forEach(([campo, mensaje]) => form.setError(campo, mensaje))
        setTimeout(() => window.scrollTo({ top: 0, behavior: 'smooth' }), 0)
        return
    }

    modalConfirmar.value = true
}

// Submit capacitación — todo en uno
const submitCapacitacion = async () => {
    form.clearErrors()
    enviandoCapacitacion.value = true

    const data = new FormData()
    data.append('id_visita', form.id_visita)
    data.append('id_tipo_servicio', form.id_tipo_servicio)
    data.append('correo_cliente', form.correo_cliente || '')
    data.append('fecha_inicio', form.fecha_inicio)
    data.append('fecha_fin', form.fecha_fin)
    data.append('hora_inicio', form.hora_inicio)
    data.append('hora_fin', form.hora_fin)
    data.append('titulo', form.titulo)
    data.append('temas', form.temas || '')
    data.append('observaciones', form.observaciones || '')
    data.append('es_capacitacion', '1')
    data.append('firma', firmaDibujada.value && canvasFirma.value ? canvasFirma.value.toDataURL('image/png') : '')
    form.fotos.forEach((foto) => data.append('fotos[]', foto))

    try {
        await axios.post(route('visitastecnicas.visitas.store'), data, {
            headers: {
                Accept: 'application/json',
                'Content-Type': 'multipart/form-data',
            },
        })

        modalConfirmar.value = false
        modalInformeEnviado.value = true
    } catch (error) {
        if (error.response?.status === 422 && error.response.data?.errors) {
            Object.entries(error.response.data.errors).forEach(([key, messages]) => {
                form.setError(key, Array.isArray(messages) ? messages[0] : messages)
            })
            return
        }

        alert(error.response?.data?.message || 'No fue posible finalizar la capacitación.')
    } finally {
        enviandoCapacitacion.value = false
    }
}

const cerrarModalInformeEnviado = () => {
    modalInformeEnviado.value = false
    router.visit(route('visitastecnicas.visitas.index'))
}

const puedeFinalizarCapacitacion = computed(() =>
    form.id_tipo_servicio &&
    form.titulo &&
    form.fecha_inicio &&
    form.fecha_fin &&
    form.hora_inicio &&
    form.hora_fin
)

const erroresFormulario = computed(() => Object.values(form.errors).filter(Boolean))
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

                <div v-if="erroresFormulario.length" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-semibold">Revisa los campos del formulario:</p>
                    <ul class="mt-1 list-disc pl-5">
                        <li v-for="(error, index) in erroresFormulario" :key="index">{{ error }}</li>
                    </ul>
                </div>

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
                            <option v-for="tipo in tiposServicioDisponibles" :key="tipo.ID" :value="tipo.ID"
                                :disabled="tipoEsGarantia(tipo)">
                                {{ tipo.TIPO_SERVICIO }}
                                {{ tipoEsGarantia(tipo) ? '(No disponible)' : '' }}
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
                            <p v-if="form.errors.titulo" class="mt-1 text-xs text-red-600">
                                {{ form.errors.titulo }}
                            </p>
                        </div>

                        <!-- Temas tratados -->
                        <div class="px-5 py-4 border-b border-gray-100">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Temas tratados
                            </label>
                            <textarea v-model="form.temas" rows="4"
                                placeholder="Describe los temas tratados en la capacitación..."
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                            <p v-if="form.errors.temas" class="mt-1 text-xs text-red-600">
                                {{ form.errors.temas }}
                            </p>
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
                            <p v-if="form.errors.fotos" class="mt-1 text-xs text-red-600">
                                {{ form.errors.fotos }}
                            </p>
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
                                @click="abrirModalConfirmar"
                                :disabled="enviandoCapacitacion"
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

        <div v-if="modalInformeEnviado" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center">
            <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="text-base font-semibold text-stone-900">Informe enviado exitosamente</h3>
                <p class="mt-1 text-sm text-stone-500">El informe técnico fue enviado al correo diligenciado.</p>
                <div class="mt-5">
                    <button
                        @click="cerrarModalInformeEnviado"
                        class="w-full rounded-xl bg-[#3b82f6] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#265aad]"
                    >
                        Entendido
                    </button>
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
                <div v-if="erroresFormulario.length" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                    <p class="font-semibold">No fue posible finalizar todavía:</p>
                    <ul class="mt-1 list-disc pl-4">
                        <li v-for="(error, index) in erroresFormulario" :key="index">{{ error }}</li>
                    </ul>
                </div>
                <!-- Observaciones finales -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Observaciones finales
                    </label>
                    <textarea v-model="form.observaciones" rows="3"
                        placeholder="Observaciones al finalizar la capacitación..."
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    <p v-if="form.errors.observaciones" class="mt-1 text-xs text-red-600">
                        {{ form.errors.observaciones }}
                    </p>
                </div>
                <!-- Firma -->
                <div class="mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">
                            Firma del cliente
                        </label>
                        <button type="button" @click="limpiarFirma"
                            class="text-xs text-gray-400 hover:text-gray-600 underline">
                            Limpiar
                        </button>
                    </div>
                    <canvas ref="canvasFirma"
                        class="block w-full rounded-lg border border-gray-300 bg-white shadow-inner touch-none cursor-crosshair"
                        @pointerdown="iniciarFirma"
                        @pointermove="dibujarFirma"
                        @pointerup="terminarFirma"
                        @pointercancel="terminarFirma"
                        @pointerleave="terminarFirma" />
                    <p class="mt-1 text-xs text-gray-400">Firma opcional</p>
                    <p v-if="form.errors.firma" class="mt-1 text-xs text-red-600">
                        {{ form.errors.firma }}
                    </p>
                </div>
                <div class="flex gap-3">
                    <button type="button" @click="modalConfirmar = false"
                        class="flex-1 rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button"
                        @click="submitCapacitacion"
                        :disabled="enviandoCapacitacion"
                        class="flex-1 rounded-xl bg-green-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <span v-if="enviandoCapacitacion" class="inline-flex items-center justify-center gap-2">
                            <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Procesando...
                        </span>
                        <span v-else>Confirmar y finalizar</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
