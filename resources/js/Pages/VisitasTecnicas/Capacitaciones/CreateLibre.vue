<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import BuscadorClientes from '@/Components/RutasTecnicas/BuscadorClientes.vue'
import SelectorDireccion from '@/Components/RutasTecnicas/SelectorDireccion.vue'

const props = defineProps({
    tipo_capacitacion_id: { type: Number, required: true },
})

const form = useForm({
    nit:            '',
    nombre_cliente: '',
    direccion:      '',
    nom_contacto:   '',
    tel_contacto:   '',
    fecha_visita:   '',
    correo_cliente: '',
    titulo:         '',
    temas:          '',
    observaciones:  '',
    fecha_inicio:   '',
    fecha_fin:      '',
    hora_inicio:    '',
    hora_fin:       '',
    fotos:          [],
})

const clienteSeleccionado = ref(null)
const direccionSeleccionada = ref(null)

const seleccionarCliente = (cliente) => {
    form.nit = cliente.Nit ?? cliente.ClienteId ?? ''
    form.nombre_cliente = cliente.NombreCliente ?? ''
    clienteSeleccionado.value = cliente
}

const seleccionarDireccion = (direccion) => {
    form.direccion = direccion.DireccionCompleta ?? ''
    direccionSeleccionada.value = direccion
}

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
    archivos.forEach((f) => {
        fotosPreview.value.push({
            file: f,
            url: URL.createObjectURL(f),
        })
    })
    form.fotos = fotosPreview.value.map((f) => f.file)
}
const eliminarFotoPreview = (index) => {
    fotosPreview.value.splice(index, 1)
    form.fotos = fotosPreview.value.map((f) => f.file)
}

const emailValido = (email) => !email || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)

const abrirModalConfirmar = () => {
    form.clearErrors()

    const errores = {}

    if (!form.nit) errores.nit = 'El NIT del cliente es obligatorio.'
    if (!form.nombre_cliente) errores.nombre_cliente = 'El nombre del cliente es obligatorio.'
    if (!form.direccion) errores.direccion = 'La dirección es obligatoria.'
    if (!form.fecha_visita) errores.fecha_visita = 'La fecha de la capacitación es obligatoria.'
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

const submitCapacitacion = async () => {
    form.clearErrors()
    enviandoCapacitacion.value = true

    const data = new FormData()
    data.append('nit', form.nit)
    data.append('nombre_cliente', form.nombre_cliente)
    data.append('direccion', form.direccion)
    data.append('nom_contacto', form.nom_contacto || '')
    data.append('tel_contacto', form.tel_contacto || '')
    data.append('fecha_visita', form.fecha_visita)
    data.append('correo_cliente', form.correo_cliente || '')
    data.append('titulo', form.titulo)
    data.append('temas', form.temas || '')
    data.append('observaciones', form.observaciones || '')
    data.append('fecha_inicio', form.fecha_inicio)
    data.append('fecha_fin', form.fecha_fin)
    data.append('hora_inicio', form.hora_inicio)
    data.append('hora_fin', form.hora_fin)
    data.append('firma', firmaDibujada.value && canvasFirma.value ? canvasFirma.value.toDataURL('image/png') : '')
    form.fotos.forEach((foto) => data.append('fotos[]', foto))

    try {
        await axios.post(route('visitastecnicas.capacitaciones.store-libre'), data, {
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

        alert(error.response?.data?.message || error.response?.data?.error || 'No fue posible registrar la capacitación.')
    } finally {
        enviandoCapacitacion.value = false
    }
}

const cerrarModalInformeEnviado = () => {
    modalInformeEnviado.value = false
    router.visit(route('visitastecnicas.visitas.index'))
}

const erroresFormulario = computed(() => Object.values(form.errors).filter(Boolean))
</script>

<template>
    <AppLayout title="Registrar Capacitación">
        <Head title="Registrar Capacitación" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Registrar Capacitación
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

                <div class="overflow-hidden rounded-xl bg-white shadow-sm border border-gray-100">
                    <div class="border-b border-gray-100 bg-gray-50 px-5 py-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400 mb-3">
                            Datos del cliente
                        </h3>
                    </div>

                    <!-- Cliente -->
                    <div class="px-5 py-4 border-b border-gray-100 relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Cliente <span class="text-red-500">*</span>
                        </label>
                        <BuscadorClientes @seleccionar="seleccionarCliente" />
                        <p v-if="form.errors.nit" class="mt-1 text-xs text-red-600">{{ form.errors.nit }}</p>
                        <p v-if="form.errors.nombre_cliente" class="mt-1 text-xs text-red-600">{{ form.errors.nombre_cliente }}</p>
                    </div>

                    <!-- Dirección -->
                    <div class="px-5 py-4 border-b border-gray-100 relative" v-if="form.nit">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Dirección <span class="text-red-500">*</span>
                        </label>
                        <SelectorDireccion
                            :cliente-id="form.nit"
                            @seleccionar="seleccionarDireccion"
                        />
                        <p v-if="form.errors.direccion" class="mt-1 text-xs text-red-600">{{ form.errors.direccion }}</p>
                    </div>

                    <!-- Info Cliente seleccionado -->
                    <div v-if="clienteSeleccionado" class="px-5 py-3 bg-gray-50 border-b border-gray-100">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div><strong>NIT:</strong> {{ form.nit }}</div>
                            <div><strong>Asesor:</strong> {{ clienteSeleccionado.CodAsesor }}</div>
                            <div class="col-span-2"><strong>Cliente:</strong> {{ form.nombre_cliente }}</div>
                        </div>
                    </div>

                    <!-- Fecha visita -->
                    <div class="px-5 py-4 border-b border-gray-100">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha de la capacitación <span class="text-red-500">*</span>
                        </label>
                        <input type="date" v-model="form.fecha_visita"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                        <p v-if="form.errors.fecha_visita" class="mt-1 text-xs text-red-600">{{ form.errors.fecha_visita }}</p>
                    </div>

                    <!-- Contacto -->
                    <div class="px-5 py-4 border-b border-gray-100 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nombre contacto</label>
                            <input type="text" v-model="form.nom_contacto"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono contacto</label>
                            <input type="text" v-model="form.tel_contacto"
                                class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                        </div>
                    </div>

                    <!-- Correo -->
                    <div class="px-5 py-4 border-b border-gray-100">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Correo para informe técnico
                        </label>
                        <input type="email" v-model="form.correo_cliente"
                            placeholder="cliente@empresa.com"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                        <p v-if="form.errors.correo_cliente" class="mt-1 text-xs text-red-600">{{ form.errors.correo_cliente }}</p>
                    </div>

                    <!-- Título -->
                    <div class="px-5 py-4 border-b border-gray-100">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Título de la capacitación <span class="text-red-500">*</span>
                        </label>
                        <input type="text" v-model="form.titulo"
                            placeholder="Ej: Introducción al nuevo producto XYZ"
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                        <p v-if="form.errors.titulo" class="mt-1 text-xs text-red-600">{{ form.errors.titulo }}</p>
                    </div>

                    <!-- Temas -->
                    <div class="px-5 py-4 border-b border-gray-100">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Temas tratados
                        </label>
                        <textarea v-model="form.temas" rows="4"
                            placeholder="Describe los temas tratados en la capacitación..."
                            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" />
                        <p v-if="form.errors.temas" class="mt-1 text-xs text-red-600">{{ form.errors.temas }}</p>
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
                        <p v-if="form.errors.fotos" class="mt-1 text-xs text-red-600">{{ form.errors.fotos }}</p>
                    </div>

                    <!-- Fechas y horas -->
                    <div class="px-5 py-4 border-b border-gray-100">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha inicio <span class="text-red-500">*</span>
                                </label>
                                <input type="date" v-model="form.fecha_inicio"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                                <p v-if="form.errors.fecha_inicio" class="mt-1 text-xs text-red-600">{{ form.errors.fecha_inicio }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Fecha fin <span class="text-red-500">*</span>
                                </label>
                                <input type="date" v-model="form.fecha_fin"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                                <p v-if="form.errors.fecha_fin" class="mt-1 text-xs text-red-600">{{ form.errors.fecha_fin }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    Hora inicio <span class="text-red-500">*</span>
                                </label>
                                <input type="time" v-model="form.hora_inicio"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                                <p v-if="form.errors.hora_inicio" class="mt-1 text-xs text-red-600">{{ form.errors.hora_inicio }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hora fin <span class="text-red-500">*</span>
                                </label>
                                <input type="time" v-model="form.hora_fin"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5" />
                                <p v-if="form.errors.hora_fin" class="mt-1 text-xs text-red-600">{{ form.errors.hora_fin }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="px-5 py-4 flex gap-3">
<Link :href="route('visitastecnicas.visitas.index')"
                             class="flex-1 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">
                             Cancelar
                         </Link>
                        <button type="button"
                            @click="abrirModalConfirmar"
                            :disabled="enviandoCapacitacion"
                            class="flex-1 inline-flex items-center justify-center rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-50 transition">
                            Finalizar capacitación
                        </button>
                    </div>
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
                    {{ form.nombre_cliente }}
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
                    <p v-if="form.errors.observaciones" class="mt-1 text-xs text-red-600">{{ form.errors.observaciones }}</p>
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
                    <p v-if="form.errors.firma" class="mt-1 text-xs text-red-600">{{ form.errors.firma }}</p>
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
