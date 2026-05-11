<script setup>
import { ref, computed, nextTick, onMounted, watch } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    visita:           { type: Object, required: true },
    equipos:          { type: Array,  default: () => [] },
    tipos_solucion:   { type: Array,  default: () => [] },
    historial:        { type: Array,  default: () => [] },
})

// Cargar descripciones para equipos y repuestos
const equiposConDescripcion = ref({})
const repuestosConDescripcion = ref({})

const obtenerDescripcionRepuesto = async (codigo) => {
    if (!codigo) {
        return {
            descripcion: null,
            codigo_proveedor: null,
            inventario: null,
        }
    }

    if (repuestosConDescripcion.value[codigo]) {
        return repuestosConDescripcion.value[codigo]
    }

    const response = await fetch(
        `/visitas-tecnicas/senco360/repuestos/descripcion?codigo=${encodeURIComponent(codigo)}`,
        { credentials: 'include' }
    )
    const data = await response.json()

    const detalle = {
        descripcion: data.descripcion,
        codigo_proveedor: data.codigo_proveedor,
        inventario: data.inventario,
    }

    repuestosConDescripcion.value[codigo] = detalle

    return detalle
}

const cargarDescripciones = async () => {
    try {
        // Cargar descripciones de equipos
        const codigosEquipos = props.equipos.map(e => e.id_cod_max).filter(Boolean)
        for (const codigo of codigosEquipos) {
            // Skip if already loaded
            if (equiposConDescripcion.value[codigo]) continue
            
            const response = await fetch(
                `/visitas-tecnicas/senco360/herramientas/descripcion?codigo=${encodeURIComponent(codigo)}`,
                { credentials: 'include' }
            )
            const data = await response.json()
            equiposConDescripcion.value[codigo] = {
                descripcion: data.descripcion,
                codigo_proveedor: data.codigo_proveedor,
            }
        }

        // Cargar descripciones de repuestos
        const codigosRepuestos = props.equipos.flatMap(e => 
            (e.repuestos || []).map(r => r.id_cod_max)
        ).filter(Boolean)
        const codigosUnicos = [...new Set(codigosRepuestos)]
        
        for (const codigo of codigosUnicos) {
            // Skip if already loaded
            if (repuestosConDescripcion.value[codigo]) continue

            await obtenerDescripcionRepuesto(codigo)
        }
    } catch (error) {
    }
}

onMounted(() => {
    cargarDescripciones()
})

// Recargar descripciones cuando cambian los equipos
watch(() => props.equipos, () => {
    cargarDescripciones()
}, { deep: true })

// Constantes - estados
const ESTADO_PENDIENTE_REPUESTOS = 6
const ESTADO_REPUESTO_RECHAZADO = 15
const ESTADO_REPUESTO_INSTALADO = 19
const ID_SOLUCION_CAMBIO_REPUESTOS = 4

const completado = computed(() => {
    return props.visita?.estado === 'Completado'
})

const esPendienteRepuestos = computed(() => {
    return Number(props.visita?.estado_id) === ESTADO_PENDIENTE_REPUESTOS
})

const puedeEditar = computed(() => !!props.visita?.puede_editar_detalle)
const puedeGuardarBorrador = computed(() => !!props.visita?.puede_guardar_borrador)
const puedeGestionarEvidenciaFinal = computed(() => !!props.visita?.puede_gestionar_evidencia_final)

// Verificar si hay repuestos pendientes (no instalados ni rechazados)
const tieneRepuestosPendientes = computed(() => {
    for (const equipo of props.equipos) {
        for (const repuesto of (equipo.repuestos || [])) {
            // Si el repuesto no está instalado (19) ni rechazado (15), está pendiente
            const estadoId = Number(repuesto.estado_id)
            if (estadoId !== 19 && estadoId !== 15) {
                return true
            }
        }
    }
    return false
})

// Finalizar solo habilitado si no tiene repuestos pendientes
const puedeFinalizar = computed(() => {
    return props.visita?.puede_finalizar && !tieneRepuestosPendientes.value
})

const puedeSolicitarCotizacion = computed(() => {
    return !!props.visita?.puede_solicitar_cotizacion && tieneRepuestosPendientes.value
})

const buttonBaseClass = 'inline-flex items-center justify-center gap-1.5 rounded-lg font-semibold transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50'

const buttonSizeClass = {
    xs: 'px-2.5 py-1.5 text-[11px]',
    sm: 'px-3 py-1.5 text-xs',
    md: 'px-4 py-2.5 text-sm',
    lg: 'px-4 py-3 text-sm',
}

const buttonVariantClass = {
    primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-300',
    neutral: 'bg-slate-100 text-slate-700 hover:bg-slate-200 focus:ring-slate-300',
    success: 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-300',
    danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-300',
    warning: 'bg-orange-600 text-white hover:bg-orange-700 focus:ring-orange-300',
    emerald: 'bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-300',
}

const buttonClass = (variant = 'primary', size = 'md', extra = '') => {
    return [buttonBaseClass, buttonSizeClass[size] ?? buttonSizeClass.md, buttonVariantClass[variant] ?? buttonVariantClass.primary, extra]
}

// ── Estado local ───────────────────────────────────────────
const equipoExpandido    = ref(null)
const seccionesExpandidas = ref({})
const modalEquipo        = ref(false)
const modalRepuesto      = ref(false)
const modalFinalizar     = ref(false)
const modalCotizacion    = ref(false)
const modalInstalacion   = ref(false)
const modalSolucionesComplementarias = ref(false)
const equipoEditandoId   = ref(null)
const equipoSeleccionado = ref(null)
const repuestoInstalacion = ref(null)
const repuestoEditandoId = ref(null)
const fotoAmpliada = ref(null)
const repuestosTemporal  = ref([])
const repuestoTemporalEditandoIndex = ref(null)
const repuestoEditorRef = ref(null)
const fotosDespuesInstalacion = ref([])
const equipoTieneRepuestosPrevios = ref(false)
const modalGuardadoBorrador = ref(false)

const beforeExpandEnter = (el) => {
    el.style.height = '0'
    el.style.opacity = '0'
    el.style.overflow = 'hidden'
}

const expandEnter = (el) => {
    el.style.willChange = 'height, opacity'

    requestAnimationFrame(() => {
        el.style.height = `${el.scrollHeight}px`
        el.style.opacity = '1'
    })
}

const afterExpandEnter = (el) => {
    el.style.height = 'auto'
    el.style.overflow = ''
    el.style.willChange = ''
}

const beforeExpandLeave = (el) => {
    el.style.height = `${el.scrollHeight}px`
    el.style.opacity = '1'
    el.style.overflow = 'hidden'
    el.style.willChange = 'height, opacity'
}

const expandLeave = (el) => {
    requestAnimationFrame(() => {
        el.style.height = '0'
        el.style.opacity = '0'
    })
}

const afterExpandLeave = (el) => {
    el.style.height = ''
    el.style.opacity = ''
    el.style.overflow = ''
    el.style.willChange = ''
}

// Helper para toggle secciones internas
const toggleSeccion = (equipoId, seccion) => {
    const clave = `${equipoId}-${seccion}`
    seccionesExpandidas.value[clave] = !seccionesExpandidas.value[clave]
}

const esSeccionExpandida = (equipoId, seccion) => {
    return seccionesExpandidas.value[`${equipoId}-${seccion}`] ?? false
}

// ── Búsqueda de herramientas y repuestos ───────────────────
const herramientasBusqueda = ref('')
const herramientasResultados = ref([])
const herramientasCargando = ref(false)
const herramientaSeleccionada = ref(null)

const repuestosBusqueda = ref('')
const repuestosResultados = ref([])
const repuestosCargando = ref(false)
const repuestoSeleccionado = ref(null)

let herramientaTimeout = null
let repuestoTimeout = null

const limpiarBusquedaHerramientas = () => {
    herramientasBusqueda.value = ''
    herramientasResultados.value = []
    herramientasCargando.value = false
}

const limpiarBusquedaRepuestos = () => {
    repuestosBusqueda.value = ''
    repuestosResultados.value = []
    repuestosCargando.value = false
}

// Canvas firma
const canvasFirma = ref(null)
let dibujando = false

// ── Forms ──────────────────────────────────────────────────
const formEquipo = useForm({
    visita_id: props.visita?.id ?? null,
    id_cod_max:        '',
    serial:            '',
    descripcion_falla: '',
    id_solucion:       [],
    observaciones:     '',
    repuestos:         [],
})

const formRepuesto = useForm({
    equipo_id:   '',
    id_cod_max:  '',
    cantidad:    1,
    observacion: '',
    resolver_en_campo: false,
})

// Referencias para fotos del equipo
const fotosAntesEquipo = ref([])

const formServicio = useForm({
    correo_cliente: props.visita?.correo_cliente ?? '',
    fecha_inicio: props.visita?.fecha_inicio ?? '',
    hora_inicio: props.visita?.hora_inicio ? String(props.visita.hora_inicio).slice(0, 5) : '',
})

const formFinalizar = useForm({
    fecha_fin: props.visita?.fecha_fin ?? '',
    hora_fin: props.visita?.hora_fin ? String(props.visita.hora_fin).slice(0, 5) : '',
    firma: '',
    observaciones: '',
})

const formInstalacion = useForm({
    fotos_despues: [],
    soluciones_adicionales: [],
})

const mostrarAlertaFinalizacion = ref(false)
const mostrarAlertaCotizacion = ref(false)
const enviandoCotizacion = ref(false)

// ── Helpers ────────────────────────────────────────────────
const estadoColor = computed(() => {
    const colores = {
        'En proceso':          'bg-blue-100 text-blue-700',
        'Completado':          'bg-green-100 text-green-700',
        'Reprogramado':        'bg-yellow-100 text-yellow-700',
        'Cancelado':           'bg-red-100 text-red-700',
        'Pendiente Repuestos': 'bg-orange-100 text-orange-700',
    }
    return colores[props.visita?.estado] ?? 'bg-gray-100 text-gray-600'
})

const estadoRepuestoColor = (estado) => {
    if (!estado) return 'bg-gray-100 text-gray-600'
    const colores = {
        'Solicitud Cotización': 'bg-gray-100 text-gray-600',
        'Repuesto Solicitado':  'bg-blue-100 text-blue-700',
        'Rechazo Cotización':   'bg-red-100 text-red-700',
        'Repuesto Facturado':   'bg-purple-100 text-purple-700',
        'Repuesto En Espera':   'bg-yellow-100 text-yellow-700',
        'Repuesto Despachado':  'bg-orange-100 text-orange-700',
        'Repuesto Instalado':   'bg-green-100 text-green-700',
    }
    return colores[estado] ?? 'bg-gray-100 text-gray-600'
}

const historialEstadoColor = (estado) => {
    const colores = {
        'En proceso':          'bg-blue-500',
        'Completado':          'bg-green-500',
        'Reprogramado':        'bg-yellow-500',
        'Cancelado':           'bg-red-500',
        'Pendiente Repuestos': 'bg-orange-500',
    }
    return colores[estado] ?? 'bg-gray-400'
}

function requiereRepuestos(idsSolucion) {
    const ids = Array.isArray(idsSolucion)
        ? idsSolucion
        : (idsSolucion ? [idsSolucion] : [])

    return ids.map(id => Number(id)).includes(ID_SOLUCION_CAMBIO_REPUESTOS)
}

const esSolucionCambioRepuestos = (solucionId) => {
    return Number(solucionId) === ID_SOLUCION_CAMBIO_REPUESTOS
}

const cumpleRepuestoObligatorio = computed(() => {
    if (!requiereRepuestos(formEquipo.id_solucion)) {
        return true
    }

    if (!puedeGestionarRepuestosEnModalEquipo.value) {
        return true
    }

    return repuestosTemporal.value.length > 0 || equipoTieneRepuestosPrevios.value
})

const puedeGuardarEquipo = computed(() => {
    return Boolean(
        formEquipo.id_cod_max &&
        String(formEquipo.serial || '').trim() &&
        String(formEquipo.descripcion_falla || '').trim() &&
        formEquipo.id_solucion.length > 0 &&
        cumpleRepuestoObligatorio.value
    )
})

const mensajeGuardarEquipo = computed(() => {
    if (formEquipo.processing) return 'Guardando cambios...'
    if (!formEquipo.id_cod_max) return 'Selecciona un equipo para continuar.'
    if (!String(formEquipo.serial || '').trim()) return 'Falta el serial del equipo.'
    if (!String(formEquipo.descripcion_falla || '').trim()) return 'Describe la falla antes de guardar.'
    if (formEquipo.id_solucion.length === 0) return 'Selecciona al menos un tipo de solucion.'
    if (!cumpleRepuestoObligatorio.value) return 'Agrega al menos un repuesto para habilitar el guardado.'
    return ''
})

const pasoEquipoActual = ref(1)

const pasoEquipo1Completo = computed(() => {
    return Boolean(
        formEquipo.id_cod_max &&
        String(formEquipo.serial || '').trim()
    )
})

const pasoEquipo2Completo = computed(() => {
    return Boolean(
        String(formEquipo.descripcion_falla || '').trim() &&
        formEquipo.id_solucion.length > 0
    )
})

const puedeGestionarRepuestosEnModalEquipo = computed(() => !equipoEditandoId.value)
const requierePasoRepuestos = computed(() => {
    return puedeGestionarRepuestosEnModalEquipo.value && requiereRepuestos(formEquipo.id_solucion)
})

const pasosModalEquipo = computed(() => {
    const pasos = [
        { id: 1, titulo: 'Equipo', completo: pasoEquipo1Completo.value },
        { id: 2, titulo: 'Diagnóstico', completo: pasoEquipo2Completo.value },
    ]

    if (requierePasoRepuestos.value) {
        pasos.push({ id: 3, titulo: 'Repuestos', completo: cumpleRepuestoObligatorio.value })
    }

    return pasos
})

const pasosStepperEquipo = computed(() => {
    const pasos = [
        { id: 1, titulo: 'Equipo', completo: pasoEquipo1Completo.value, condicional: false, habilitado: true },
        { id: 2, titulo: 'Diagnóstico', completo: pasoEquipo2Completo.value, condicional: false, habilitado: true },
    ]

    if (requierePasoRepuestos.value) {
        pasos.push({
            id: 3,
            titulo: 'Repuestos',
            completo: cumpleRepuestoObligatorio.value,
            condicional: false,
            habilitado: true,
        })
    }

    return pasos
})

const totalPasosEquipo = computed(() => pasosModalEquipo.value.length)
const esUltimoPasoEquipo = computed(() => pasoEquipoActual.value === totalPasosEquipo.value)
const puedeAvanzarPasoEquipo = computed(() => {
    if (pasoEquipoActual.value === 1) return pasoEquipo1Completo.value
    if (pasoEquipoActual.value === 2) return pasoEquipo2Completo.value
    return cumpleRepuestoObligatorio.value
})

watch(modalEquipo, (abierto) => {
    if (abierto) {
        pasoEquipoActual.value = 1
    }
})

watch(requierePasoRepuestos, (requiere) => {
    if (!requiere && pasoEquipoActual.value > 2) {
        pasoEquipoActual.value = 2
    }
})

const irPasoEquipoSiguiente = () => {
    if (!puedeAvanzarPasoEquipo.value || esUltimoPasoEquipo.value) return
    pasoEquipoActual.value += 1
}

const irPasoEquipoAnterior = () => {
    if (pasoEquipoActual.value <= 1) return
    pasoEquipoActual.value -= 1
}

const solucionSeleccionada = (solucionId) => {
    return formEquipo.id_solucion.includes(solucionId)
}

const solucionBloqueada = (solucionId) => {
    const hayCambioRepuestosSeleccionado = formEquipo.id_solucion.some(id => esSolucionCambioRepuestos(id))
    const hayOtraSolucionSeleccionada = formEquipo.id_solucion.some(id => !esSolucionCambioRepuestos(id))

    if (solucionSeleccionada(solucionId)) {
        return false
    }

    if (esSolucionCambioRepuestos(solucionId)) {
        return hayOtraSolucionSeleccionada
    }

    return hayCambioRepuestosSeleccionado
}

const toggleSolucion = (solucionId) => {
    if (solucionBloqueada(solucionId)) {
        return
    }

    if (solucionSeleccionada(solucionId)) {
        formEquipo.id_solucion = formEquipo.id_solucion.filter(id => id !== solucionId)
        return
    }

    if (esSolucionCambioRepuestos(solucionId)) {
        formEquipo.id_solucion = [solucionId]
        return
    }

    formEquipo.id_solucion = [...formEquipo.id_solucion, solucionId]
}

const tiposSolucionOrdenados = computed(() => {
    return [...props.tipos_solucion].sort((a, b) => {
        const aSeleccionada = solucionSeleccionada(a.ID) ? 1 : 0
        const bSeleccionada = solucionSeleccionada(b.ID) ? 1 : 0

        if (aSeleccionada !== bSeleccionada) {
            return bSeleccionada - aSeleccionada
        }

        return String(a.TIPO_SOLUCION).localeCompare(String(b.TIPO_SOLUCION))
    })
})

const repuestoEstaInstalado = (repuesto) => Number(repuesto?.estado_id) === ESTADO_REPUESTO_INSTALADO
const repuestoEstaRechazado = (repuesto) => Number(repuesto?.estado_id) === ESTADO_REPUESTO_RECHAZADO

const puedeAgregarSolucionComplementariaEquipo = (equipo) => {
    if (!equipo || !requiereRepuestos(equipo.soluciones_ids)) {
        return false
    }

    const repuestosEquipo = Array.isArray(equipo.repuestos) ? equipo.repuestos : []

    if (repuestosEquipo.length === 0) {
        return false
    }

    return repuestosEquipo.every((repuesto) =>
        repuestoEstaInstalado(repuesto) || repuestoEstaRechazado(repuesto)
    )
}

const equipoQuedariaListoParaSolucionesComplementarias = (equipo, repuestoObjetivo) => {
    if (!equipo || !repuestoObjetivo) {
        return false
    }

    const repuestosActualizados = (Array.isArray(equipo.repuestos) ? equipo.repuestos : []).map((repuesto) => {
        if (Number(repuesto.id) !== Number(repuestoObjetivo.id)) {
            return repuesto
        }

        return {
            ...repuesto,
            estado_id: ESTADO_REPUESTO_INSTALADO,
        }
    })

    return puedeAgregarSolucionComplementariaEquipo({
        ...equipo,
        repuestos: repuestosActualizados,
    })
}

const solucionComplementariaSeleccionada = (solucionId) => {
    return formInstalacion.soluciones_adicionales.includes(solucionId)
}

const toggleSolucionComplementaria = (solucionId) => {
    if (solucionComplementariaSeleccionada(solucionId)) {
        formInstalacion.soluciones_adicionales = formInstalacion.soluciones_adicionales.filter(id => id !== solucionId)
        return
    }

    formInstalacion.soluciones_adicionales = [...formInstalacion.soluciones_adicionales, solucionId]
}

const tiposSolucionComplementarios = computed(() => {
    const solucionesActuales = Array.isArray(equipoSeleccionado.value?.soluciones_ids)
        ? equipoSeleccionado.value.soluciones_ids.map(id => Number(id))
        : []

    return props.tipos_solucion
        .filter(solucion => !esSolucionCambioRepuestos(solucion.ID) && !solucionesActuales.includes(Number(solucion.ID)))
        .sort((a, b) => {
            const aSeleccionada = solucionComplementariaSeleccionada(a.ID) ? 1 : 0
            const bSeleccionada = solucionComplementariaSeleccionada(b.ID) ? 1 : 0

            if (aSeleccionada !== bSeleccionada) {
                return bSeleccionada - aSeleccionada
            }

            return String(a.TIPO_SOLUCION).localeCompare(String(b.TIPO_SOLUCION))
        })
})

// ── Firma ──────────────────────────────────────────────────
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

const limpiarFirma = () => {
    const c = canvasFirma.value
    if (!c) return
    c.getContext('2d').clearRect(0, 0, c.width, c.height)
}

const firmaTieneContenido = () => {
    const canvas = canvasFirma.value
    if (!canvas) return false

    const canvasVacio = document.createElement('canvas')
    canvasVacio.width = canvas.width
    canvasVacio.height = canvas.height

    return canvas.toDataURL() !== canvasVacio.toDataURL()
}

const camposFaltantesFinalizacion = computed(() => {
    if (props.visita?.es_capacitacion) {
        const faltantes = []

        if (!formServicio.fecha_inicio) {
            faltantes.push('Fecha inicio')
        }

        if (!formFinalizar.fecha_fin) {
            faltantes.push('Fecha fin')
        }

        return faltantes
    }

    const faltantes = []

    if (!formServicio.fecha_inicio) {
        faltantes.push('Fecha inicio')
    }

    if (!formServicio.hora_inicio) {
        faltantes.push('Hora inicio')
    }

    if (!formFinalizar.fecha_fin) {
        faltantes.push('Fecha fin')
    }

    if (!formFinalizar.hora_fin) {
        faltantes.push('Hora fin')
    }

    return faltantes
})

// ── Equipos ────────────────────────────────────────────────
const abrirModalEquipo = () => {
    equipoEditandoId.value = null
    formEquipo.id_cod_max = ''
    formEquipo.serial = ''
    formEquipo.descripcion_falla = ''
    formEquipo.id_solucion = []
    formEquipo.observaciones = ''
    formEquipo.repuestos = []
    formEquipo.visita_id = props.visita.id
    herramientaSeleccionada.value = null
    limpiarBusquedaHerramientas()
    limpiarFormularioRepuestoTemporal()
    repuestosTemporal.value = []
    equipoTieneRepuestosPrevios.value = false
    formEquipo.clearErrors()
    modalEquipo.value = true
}

const abrirModalEditarEquipo = (equipo) => {
    equipoEditandoId.value = equipo.id
    formEquipo.visita_id = props.visita.id
    formEquipo.id_cod_max = equipo.id_cod_max ?? ''
    formEquipo.serial = equipo.serial ?? ''
    formEquipo.descripcion_falla = equipo.descripcion_falla ?? ''
    formEquipo.id_solucion = Array.isArray(equipo.soluciones_ids)
        ? equipo.soluciones_ids.map(id => Number(id))
        : []
    formEquipo.observaciones = equipo.observaciones ?? ''
    formEquipo.repuestos = []

    const descripcion = equiposConDescripcion.value[equipo.id_cod_max]?.descripcion
    const codigoProveedor = equiposConDescripcion.value[equipo.id_cod_max]?.codigo_proveedor

    herramientaSeleccionada.value = {
        codigo: equipo.id_cod_max,
        descripcion: descripcion ?? equipo.id_cod_max,
        proveedor: codigoProveedor ?? null,
    }
    herramientasBusqueda.value = descripcion ?? equipo.id_cod_max ?? ''
    herramientasResultados.value = []
    repuestosTemporal.value = []
    equipoTieneRepuestosPrevios.value = Array.isArray(equipo.repuestos) && equipo.repuestos.length > 0
    fotosAntesEquipo.value = []
    formEquipo.clearErrors()
    modalEquipo.value = true
}

const buscarHerramientas = () => {
    clearTimeout(herramientaTimeout)
    if (herramientasBusqueda.value.length < 2) {
        herramientasResultados.value = []
        herramientasCargando.value = false
        return
    }
    herramientaTimeout = setTimeout(async () => {
        herramientasCargando.value = true
        try {
            const response = await fetch(
                `/visitas-tecnicas/senco360/herramientas/buscar?q=${encodeURIComponent(herramientasBusqueda.value)}`,
                { credentials: 'include' }
            )
            herramientasResultados.value = await response.json()
        } catch (error) {
            herramientasResultados.value = []
        } finally {
            herramientasCargando.value = false
        }
    }, 300)
}

const seleccionarHerramienta = (item) => {
    herramientaSeleccionada.value = item
    formEquipo.id_cod_max = item.codigo
    herramientasBusqueda.value = item.descripcion
    herramientasResultados.value = []
}

const guardarEquipo = async () => {
    if (requierePasoRepuestos.value && !cumpleRepuestoObligatorio.value) {
        formEquipo.setError('repuestos', 'Debes agregar al menos un repuesto para esta solucion.')
        return
    }

    formEquipo.repuestos = repuestosTemporal.value.map(repuesto => ({
        id_cod_max: repuesto.id_cod_max,
        cantidad: repuesto.cantidad,
        observacion: repuesto.observacion,
        resolver_en_campo: !!repuesto.resolver_en_campo,
    }))

    try {
        formEquipo.processing = true
        
        // Crear FormData para enviar equipo + foto
        const formData = new FormData()
        
        Object.keys(formEquipo.data()).forEach(key => {
            const value = formEquipo[key]
            
            // id_solucion se envía como array[] para Laravel
            if (key === 'id_solucion' && Array.isArray(value)) {
                value.forEach(id => {
                    formData.append('id_solucion[]', id)
                })
            } 
            // repuestos se envía como JSON
            else if (key === 'repuestos' && Array.isArray(value)) {
                formData.append(key, JSON.stringify(value))
            } 
            // Otros campos normales
            else {
                formData.append(key, value || '')
            }
        })
        
        // Agregar evidencia inicial si existe
        if (fotosAntesEquipo.value.length > 0) {
            fotosAntesEquipo.value.forEach((foto) => {
                formData.append('fotos_antes[]', foto.file)
            })
        }

        const esEdicion = !!equipoEditandoId.value
        const url = esEdicion
            ? route('visitastecnicas.equipos.update', equipoEditandoId.value)
            : route('visitastecnicas.equipos.store')

        if (esEdicion) {
            formData.append('_method', 'PUT')
        }

        await axios.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            }
        })

        modalEquipo.value = false
        equipoEditandoId.value = null
        repuestosTemporal.value = []
        fotosAntesEquipo.value = []
        formEquipo.reset()
        router.reload({ only: ['visita', 'equipos'] })
        
    } catch (error) {
        if (error.response?.data?.errors) {
            Object.keys(error.response.data.errors).forEach(key => {
                formEquipo.setError(key, error.response.data.errors[key])
            })
        }
    } finally {
        formEquipo.processing = false
    }
}

const agregarRepuestoTemporal = () => {
    if (!repuestoSeleccionado.value || !formRepuesto.id_cod_max) return
    const cantidad = Number(formRepuesto.cantidad)

    if (!Number.isFinite(cantidad) || cantidad <= 0) {
        formRepuesto.setError('cantidad', 'La cantidad debe ser mayor a 0.')
        return
    }

    const repuestoTemporalPayload = {
        id_cod_max: formRepuesto.id_cod_max,
        cantidad,
        observacion: formRepuesto.observacion,
        resolver_en_campo: !!formRepuesto.resolver_en_campo,
        descripcion: repuestoSeleccionado.value.descripcion,
        codigo: repuestoSeleccionado.value.codigo,
        inventario: repuestoSeleccionado.value.inventario,
    }

    if (repuestoTemporalEditandoIndex.value !== null) {
        repuestosTemporal.value.splice(repuestoTemporalEditandoIndex.value, 1, repuestoTemporalPayload)
    } else {
        repuestosTemporal.value.push(repuestoTemporalPayload)
    }

    limpiarFormularioRepuestoTemporal()
    formEquipo.clearErrors('repuestos')
    formRepuesto.clearErrors('cantidad')
}

const resetearFormularioRepuesto = () => {
    formRepuesto.reset()
    formRepuesto.equipo_id = ''
    formRepuesto.id_cod_max = ''
    formRepuesto.cantidad = 1
    formRepuesto.observacion = ''
    formRepuesto.resolver_en_campo = false
}

const limpiarFormularioRepuestoTemporal = () => {
    repuestoTemporalEditandoIndex.value = null
    repuestoSeleccionado.value = null
    limpiarBusquedaRepuestos()
    resetearFormularioRepuesto()
    formRepuesto.clearErrors('cantidad')
}

const editarRepuestoTemporal = async (index) => {
    const repuesto = repuestosTemporal.value[index]

    if (!repuesto) return

    repuestoTemporalEditandoIndex.value = index
    repuestoSeleccionado.value = {
        codigo: repuesto.codigo,
        descripcion: repuesto.descripcion,
        inventario: repuesto.inventario,
    }
    repuestosBusqueda.value = repuesto.descripcion ?? repuesto.codigo ?? ''
    repuestosResultados.value = []
    formRepuesto.id_cod_max = repuesto.id_cod_max
    formRepuesto.cantidad = repuesto.cantidad
    formRepuesto.observacion = repuesto.observacion ?? ''
    formRepuesto.resolver_en_campo = !!repuesto.resolver_en_campo
    formRepuesto.clearErrors('cantidad')

    await nextTick()
    repuestoEditorRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const seleccionarFotoAntes = (event) => {
    const archivos = Array.from(event.target.files || [])
    archivos.forEach((archivo) => {
        fotosAntesEquipo.value.push({
            file: archivo,
            url: URL.createObjectURL(archivo),
            nombre: archivo.name,
        })
    })
}

const eliminarFotoAntes = (index) => {
    fotosAntesEquipo.value.splice(index, 1)
}

const eliminarRepuestoTemporal = (index) => {
    if (repuestoTemporalEditandoIndex.value === index) {
        limpiarFormularioRepuestoTemporal()
    } else if (repuestoTemporalEditandoIndex.value !== null && repuestoTemporalEditandoIndex.value > index) {
        repuestoTemporalEditandoIndex.value -= 1
    }

    repuestosTemporal.value.splice(index, 1)

    if (cumpleRepuestoObligatorio.value) {
        formEquipo.clearErrors('repuestos')
    }
}

const eliminarEquipo = (id) => {
    if (!confirm('¿Eliminar este equipo y sus repuestos?')) return
    router.delete(route('visitastecnicas.equipos.destroy', id), {
        onSuccess: () => router.reload({ only: ['equipos', 'visita'] }),
    })
}

// ── Repuestos ──────────────────────────────────────────────
const abrirModalRepuesto = (equipo) => {
    repuestoEditandoId.value = null
    equipoSeleccionado.value = equipo
    resetearFormularioRepuesto()
    formRepuesto.equipo_id = equipo.id
    repuestoSeleccionado.value = null
    limpiarBusquedaRepuestos()
    modalRepuesto.value = true
}

const abrirModalEditarRepuesto = async (equipo, repuesto) => {
    repuestoEditandoId.value = repuesto.id
    equipoSeleccionado.value = equipo
    resetearFormularioRepuesto()
    formRepuesto.equipo_id = equipo.id
    formRepuesto.id_cod_max = repuesto.id_cod_max ?? ''
    formRepuesto.cantidad = Number(repuesto.cantidad) || 1
    formRepuesto.observacion = repuesto.observacion ?? ''
    formRepuesto.resolver_en_campo = Number(repuesto.estado_id) === ESTADO_REPUESTO_INSTALADO
    let detalleRepuesto = {
        descripcion: repuestosConDescripcion.value[repuesto.id_cod_max]?.descripcion ?? null,
        codigo_proveedor: repuestosConDescripcion.value[repuesto.id_cod_max]?.codigo_proveedor ?? null,
        inventario: repuestosConDescripcion.value[repuesto.id_cod_max]?.inventario ?? null,
    }

    try {
        detalleRepuesto = await obtenerDescripcionRepuesto(repuesto.id_cod_max)
    } catch (error) {
    }

    repuestoSeleccionado.value = {
        codigo: repuesto.id_cod_max,
        descripcion: detalleRepuesto.descripcion ?? repuesto.id_cod_max,
        proveedor: detalleRepuesto.codigo_proveedor ?? null,
        inventario: detalleRepuesto.inventario ?? null,
    }
    repuestosBusqueda.value = detalleRepuesto.descripcion ?? repuesto.id_cod_max ?? ''
    repuestosResultados.value = []
    modalRepuesto.value = true
}

const buscarRepuestos = () => {
    clearTimeout(repuestoTimeout)
    if (repuestosBusqueda.value.length < 2) {
        repuestosResultados.value = []
        repuestosCargando.value = false
        return
    }
    repuestoTimeout = setTimeout(async () => {
        repuestosCargando.value = true
        try {
            const response = await fetch(
                `/visitas-tecnicas/senco360/repuestos/buscar?q=${encodeURIComponent(repuestosBusqueda.value)}`,
                { credentials: 'include' }
            )
            repuestosResultados.value = await response.json()
        } catch (error) {
            repuestosResultados.value = []
        } finally {
            repuestosCargando.value = false
        }
    }, 300)
}

const seleccionarRepuesto = (item) => {
    repuestoSeleccionado.value = item
    formRepuesto.id_cod_max = item.codigo
    repuestosBusqueda.value = item.descripcion
    repuestosResultados.value = []
}

const formatearInventario = (inventario) => {
    if (inventario === null || inventario === undefined || inventario === '') return 'N/D'

    const valor = Number(inventario)
    if (Number.isNaN(valor)) return inventario

    return new Intl.NumberFormat('es-CO', {
        maximumFractionDigits: 0,
    }).format(valor)
}

const textoStock = (inventario) => {
    const valor = Number(inventario)
    if (inventario === null || inventario === undefined || inventario === '' || Number.isNaN(valor)) {
        return 'Stock N/D'
    }

    if (valor <= 0) return 'Sin stock'
    return `Stock: ${formatearInventario(valor)}`
}

const claseStock = (inventario) => {
    const valor = Number(inventario)

    if (inventario === null || inventario === undefined || inventario === '' || Number.isNaN(valor)) {
        return 'bg-gray-100 text-gray-500'
    }

    if (valor <= 0) return 'bg-gray-100 text-gray-600'
    return 'bg-emerald-100 text-emerald-800'
}

const guardarRepuesto = () => {
    if (repuestoEditandoId.value) {
        formRepuesto.transform((data) => ({
            cantidad: data.cantidad,
            observacion: data.observacion,
            id_estado: data.resolver_en_campo ? 19 : 13,
            resolver_en_campo: !!data.resolver_en_campo,
        })).put(route('visitastecnicas.solicitudes-partes.update', repuestoEditandoId.value), {
            onSuccess: () => {
                modalRepuesto.value = false
                repuestoEditandoId.value = null
                router.reload({ only: ['equipos'] })
            },
            onFinish: () => formRepuesto.transform((data) => data),
        })
        return
    }

    formRepuesto.post(route('visitastecnicas.solicitudes-partes.store'), {
        onSuccess: () => {
            modalRepuesto.value = false
            repuestoEditandoId.value = null
            router.reload({ only: ['equipos'] })
        },
    })
}

const eliminarRepuesto = (id) => {
    if (!confirm('¿Eliminar este repuesto?')) return
    router.delete(route('visitastecnicas.solicitudes-partes.destroy', id), {
        preserveState: false,
        onSuccess: () => router.reload(),
    })
}

const abrirModalInstalacion = (equipo, repuesto) => {
    equipoSeleccionado.value = equipo
    repuestoInstalacion.value = repuesto
    fotosDespuesInstalacion.value = []
    formInstalacion.fotos_despues = []
    formInstalacion.soluciones_adicionales = []
    modalInstalacion.value = true
}

const abrirModalSolucionesComplementarias = (equipo) => {
    equipoSeleccionado.value = equipo
    repuestoInstalacion.value = null
    formInstalacion.soluciones_adicionales = []
    modalSolucionesComplementarias.value = true
}

const cerrarModalSolucionesComplementarias = () => {
    modalSolucionesComplementarias.value = false
    formInstalacion.soluciones_adicionales = []
}

const agregarFotosDespuesInstalacion = (event) => {
    const archivos = Array.from(event.target.files || [])
    archivos.forEach((archivo) => {
        fotosDespuesInstalacion.value.push({
            file: archivo,
            url: URL.createObjectURL(archivo),
        })
    })
    formInstalacion.fotos_despues = fotosDespuesInstalacion.value.map(foto => foto.file)
}

const eliminarFotoDespuesInstalacion = (index) => {
    fotosDespuesInstalacion.value.splice(index, 1)
    formInstalacion.fotos_despues = fotosDespuesInstalacion.value.map(foto => foto.file)
}

const confirmarInstalacion = async () => {
    if (!repuestoInstalacion.value || !equipoSeleccionado.value) return

    const debeAbrirSolucionesComplementarias = equipoQuedariaListoParaSolucionesComplementarias(
        equipoSeleccionado.value,
        repuestoInstalacion.value
    )

    try {
        formInstalacion.processing = true

        // Paso 1: Subir fotos si existen
        if (fotosDespuesInstalacion.value.length > 0) {
            const formData = new FormData()
            formData.append('equipo_id', equipoSeleccionado.value.id)
            formData.append('tipo', 'DESPUES')
            
            // Agregar cada archivo al formData
            fotosDespuesInstalacion.value.forEach((foto) => {
                formData.append('fotos[]', foto.file)
            })

            await axios.post(route('visitastecnicas.fotos.store'), formData, {
                headers: { 
                    'Content-Type': 'multipart/form-data',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            })
        }

        // Paso 2: Actualizar estado del repuesto a instalado
        await axios.put(route('visitastecnicas.repuestos.estado', repuestoInstalacion.value.id), {
            estado_id: 19,
            observacion: 'Instalado durante visita técnica',
            soluciones_adicionales: formInstalacion.soluciones_adicionales,
        }, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        })

        // Paso 3: Limpiar y cerrar modal
        modalInstalacion.value = false
        fotosDespuesInstalacion.value = []
        formInstalacion.fotos_despues = []
        formInstalacion.soluciones_adicionales = []
        
        // Paso 4: Recargar datos necesarios sin navegar manualmente
        router.reload({
            only: ['visita', 'equipos', 'historial'],
            onSuccess: async (page) => {
                if (!debeAbrirSolucionesComplementarias) {
                    return
                }

                const equiposActualizados = page?.props?.equipos ?? props.equipos
                const equipoActualizado = Array.isArray(equiposActualizados)
                    ? equiposActualizados.find((equipo) => Number(equipo.id) === Number(equipoSeleccionado.value?.id))
                    : null

                if (!equipoActualizado || !puedeAgregarSolucionComplementariaEquipo(equipoActualizado)) {
                    return
                }

                await nextTick()
                abrirModalSolucionesComplementarias(equipoActualizado)
            },
        })

    } catch (error) {
        let mensaje = error.response?.data?.message || error.message
        if (error.response?.status === 422) {
            mensaje = 'Error de validación: ' + mensaje
        } else if (error.response?.status === 403) {
            mensaje = 'No permitido: ' + mensaje
        }
        
        alert('❌ ' + mensaje)
    }
    finally {
        formInstalacion.processing = false
    }
}

const guardarSolucionesComplementarias = async () => {
    if (!equipoSeleccionado.value || formInstalacion.soluciones_adicionales.length === 0) {
        return
    }

    try {
        formInstalacion.processing = true

        await axios.put(route('visitastecnicas.equipos.soluciones-complementarias', equipoSeleccionado.value.id), {
            soluciones_adicionales: formInstalacion.soluciones_adicionales.map(id => Number(id)),
        }, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        })

        cerrarModalSolucionesComplementarias()
        router.reload({ only: ['visita', 'equipos', 'historial'] })
    } catch (error) {
        const mensaje = error.response?.data?.message || 'No fue posible guardar las soluciones complementarias.'
        alert(mensaje)
    } finally {
        formInstalacion.processing = false
    }
}

// ── Finalizar ────────────────────────────────────────────
const abrirModalFinalizar = () => {
    modalFinalizar.value = true
    mostrarAlertaFinalizacion.value = false
    formFinalizar.firma = ''
    nextTick(() => limpiarFirma())
}

const abrirModalCotizacion = () => {
    modalCotizacion.value = true
    mostrarAlertaCotizacion.value = false
}

const finalizar = () => {
    mostrarAlertaFinalizacion.value = camposFaltantesFinalizacion.value.length > 0

    if (mostrarAlertaFinalizacion.value) {
        return
    }

    if (!props.visita?.es_capacitacion && firmaTieneContenido()) {
        formFinalizar.firma = canvasFirma.value.toDataURL('image/png')
    } else {
        formFinalizar.firma = ''
    }

    formFinalizar.transform((data) => ({
        ...data,
        correo_cliente: formServicio.correo_cliente,
        fecha_inicio: formServicio.fecha_inicio,
        hora_inicio: formServicio.hora_inicio,
    })).post(route('visitastecnicas.visitas.finalizar', props.visita.id), {
        onFinish: () => formFinalizar.transform((payload) => payload),
    })
}

const solicitarCotizacion = async () => {
    enviandoCotizacion.value = true
    mostrarAlertaCotizacion.value = false
    formServicio.clearErrors()

    try {
        // Usar directamente la URL en lugar de route()
        const url = `/visitas-tecnicas/visitas/${props.visita.id}/solicitar-cotizacion`
        
        await axios.post(url, {
            correo_cliente: formServicio.correo_cliente,
            fecha_inicio: formServicio.fecha_inicio,
            hora_inicio: formServicio.hora_inicio,
        }, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })

        modalCotizacion.value = false
        router.reload({ only: ['visita', 'equipos', 'historial'] })
    } catch (error) {
        if (error.response) {
            if (error.response.status === 422 && error.response.data?.errors) {
                formServicio.errors = error.response.data.errors
                mostrarAlertaCotizacion.value = true
                return
            }
            
            if (error.response.status === 403) {
                mostrarAlertaCotizacion.value = true
                return
            }
        }

        alert('Error al enviar a cotización: ' + (error.response?.data?.message || error.message))
    } finally {
        enviandoCotizacion.value = false
    }
}

// ── Capacitación ───────────────────────────────────────────
const formCapacitacion   = ref({ titulo: '', temas: '' })
const guardandoCapacitacion = ref(false)

// Inicializar form con datos existentes
if (props.equipos.length > 0 && props.visita?.es_capacitacion) {
    formCapacitacion.value.titulo = props.equipos[0].titulo ?? ''
    formCapacitacion.value.temas  = props.equipos[0].descripcion_falla ?? ''
}

const crearCapacitacion = () => {
    guardandoCapacitacion.value = true
    router.post(route('visitastecnicas.equipos.store'), {
        visita_id:         props.visita.id,
        id_cod_max:        'CAP',
        titulo:            formCapacitacion.value.titulo,
        descripcion_falla: formCapacitacion.value.temas,
        id_solucion:       null,
    }, {
        onSuccess: () => { guardandoCapacitacion.value = false },
        onError:   () => { guardandoCapacitacion.value = false },
    })
}

const guardarCapacitacion = (equipoId) => {
    guardandoCapacitacion.value = true
    router.put(route('visitastecnicas.equipos.update', equipoId), {
        id_cod_max:        props.equipos[0]?.id_cod_max || 'CAP',
        titulo:            formCapacitacion.value.titulo,
        descripcion_falla: formCapacitacion.value.temas,
    }, {
        onSuccess: () => { guardandoCapacitacion.value = false },
        onError:   () => { guardandoCapacitacion.value = false },
    })
}

const subirFotos = (event, equipoId, tipo, visitaId = null) => {
    const archivos = Array.from(event.target.files)
    if (!archivos.length) return

    const formData = new FormData()
    if (equipoId) {
        formData.append('equipo_id', equipoId)
    }
    if (visitaId) {
        formData.append('visita_id', visitaId)
    }
    formData.append('tipo', tipo)
    archivos.forEach((archivo) => {
        formData.append('fotos[]', archivo)
    })

    axios.post(route('visitastecnicas.fotos.store'), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
    })
    .then(() => {
        router.reload({ only: ['equipos', 'visita'] })
    })
    .catch(() => {
    })
}

const eliminarFoto = (fotoId) => {
    if (!confirm('¿Eliminar esta foto?')) return

    axios.delete(route('visitastecnicas.fotos.destroy', fotoId))
    .then(() => {
        router.reload({ only: ['equipos', 'visita'] })
    })
    .catch(() => {
    })
}

const guardarBorrador = () => {
    formServicio.clearErrors()

    axios.post(route('visitastecnicas.visitas.guardar-borrador', props.visita.id), {
        correo_cliente: formServicio.correo_cliente,
        fecha_inicio: formServicio.fecha_inicio,
        hora_inicio: formServicio.hora_inicio,
    }, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    }).then(() => {
        modalGuardadoBorrador.value = true
        router.reload({ only: ['visita'] })
    }).catch((error) => {
        if (error.response?.status === 422 && error.response.data?.errors) {
            formServicio.errors = error.response.data.errors
            return
        }

        alert(error.response?.data?.message || 'No fue posible guardar los cambios.')
    })
}
</script>

<template>
    <AppLayout v-if="visita" :title="`Visita #${visita.id}`">
        <Head :title="`Visita #${visita.id}`" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Visita #{{ visita.id }}</h2>
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="estadoColor">
                    {{ visita.estado }}
                </span>
            </div>
        </template>

        <div class="py-4 sm:py-6">
            <div class="mx-auto max-w-3xl space-y-4 px-3 sm:px-6">

                <!-- Banner completado -->
                <div v-if="completado"
                    class="flex items-center gap-3 rounded-xl bg-green-50 border border-green-200 px-4 py-3">
                    <svg class="h-5 w-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium text-green-700">Visita completada — solo lectura</p>
                </div>

                <!-- ── Sección 1: Cliente ── -->
                <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 border-b border-gray-100 px-4 py-2.5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Cliente</h3>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-2 gap-3">
                        <div class="col-span-2">
                            <p class="text-xs text-gray-400">Empresa</p>
                            <p class="text-sm font-semibold text-gray-900">{{ visita.cliente?.nombre ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">NIT</p>
                            <p class="text-sm text-gray-700">{{ visita.cliente?.nit ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">N° Ruta</p>
                            <p class="text-sm text-gray-700">{{ visita.numero_ruta ?? '—' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-400">Dirección</p>
                            <p class="text-sm text-gray-700">{{ visita.direccion ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Fecha programada</p>
                            <p class="text-sm text-gray-700">{{ visita.fecha_visita ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Nombre Contacto</p>
                            <p class="text-sm text-gray-700">{{ visita.nom_contacto ?? '—' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Teléfono Contacto</p>
                            <p class="text-sm text-gray-700">{{ visita.tel_contacto ?? '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- ── Sección 2: Servicio ── -->
                <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 border-b border-gray-100 px-4 py-2.5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Servicio</h3>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-xs text-gray-400">Tipo</p>
                            <p class="text-sm font-medium text-gray-900">{{ visita.tipo_servicio ?? '—' }}</p>
                        </div>
                        <div v-if="visita.es_capacitacion || completado">
                            <p class="text-xs text-gray-400">Correo para Informe técnico de visita</p>
                            <p class="text-sm text-gray-700">{{ visita.correo_cliente ?? '—' }}</p>
                        </div>
                        <div v-else>
                            <label class="block text-xs text-gray-400 mb-1">Correo para informe técnico de la visita</label>
                            <input v-model="formServicio.correo_cliente" type="email"
                                placeholder="cliente@empresa.com"
                                class="block w-full rounded-lg border-gray-300 text-sm py-2.5 focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="formServicio.errors.correo_cliente" class="mt-1 text-xs text-red-600">
                                {{ formServicio.errors.correo_cliente }}
                            </p>
                        </div>
                        <div v-if="visita.es_capacitacion || completado">
                            <p class="text-xs text-gray-400">Fecha inicio</p>
                            <p class="text-sm text-gray-700">{{ visita.fecha_inicio ?? '—' }}</p>
                        </div>
                        <div v-else>
                            <label class="block text-xs text-gray-400 mb-1">Fecha inicio <span class="text-red-500">*</span></label>
                            <input v-model="formServicio.fecha_inicio" type="date" required
                                :disabled="esPendienteRepuestos"
                                class="block w-full rounded-lg border-gray-300 text-sm py-2.5 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-500" />
                            <p v-if="esPendienteRepuestos" class="mt-1 text-xs text-gray-400">
                                No se puede modificar después de enviar a cotización
                            </p>
                            <p v-if="formServicio.errors.fecha_inicio" class="mt-1 text-xs text-red-600">
                                {{ formServicio.errors.fecha_inicio }}
                            </p>
                        </div>
                        <div v-if="visita.es_capacitacion || completado">
                            <p class="text-xs text-gray-400">Hora inicio</p>
                            <p class="text-sm text-gray-700">{{ visita.hora_inicio ?? '—' }}</p>
                        </div>
                        <div v-else>
                            <label class="block text-xs text-gray-400 mb-1">Hora inicio <span class="text-red-500">*</span></label>
                            <input v-model="formServicio.hora_inicio" type="time" required
                                :disabled="esPendienteRepuestos"
                                class="block w-full rounded-lg border-gray-300 text-sm py-2.5 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-500" />
                            <p v-if="esPendienteRepuestos" class="mt-1 text-xs text-gray-400">
                                No se puede modificar después de enviar a cotización
                            </p>
                            <p v-if="formServicio.errors.hora_inicio" class="mt-1 text-xs text-red-600">
                                {{ formServicio.errors.hora_inicio }}
                            </p>
                        </div>
                        <div v-if="visita.es_capacitacion || completado">
                            <p class="text-xs text-gray-400">Fecha fin</p>
                            <p class="text-sm text-gray-700">{{ visita.fecha_fin ?? '—' }}</p>
                        </div>
                        <div v-if="visita.es_capacitacion || completado">
                            <p class="text-xs text-gray-400">Hora fin</p>
                            <p class="text-sm text-gray-700">{{ visita.hora_fin ?? '—' }}</p>
                        </div>
                        <div v-if="visita.observaciones" class="col-span-2">
                            <p class="text-xs text-gray-400">Observaciones</p>
                            <p class="text-sm text-gray-700">{{ visita.observaciones }}</p>
                        </div>
                    </div>
                </div>

                <!-- ── Sección 3: Capacitación ── -->
<div v-if="visita?.es_capacitacion"
    class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gray-50 border-b border-gray-100 px-4 py-2.5">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Capacitación</h3>
    </div>
    <div class="px-4 py-3 space-y-3">

        <!-- Formulario (crear o editar) -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">
                Título <span class="text-red-500">*</span>
            </label>
            <p v-if="completado" class="text-sm text-gray-900 font-medium">
                {{ equipos[0]?.titulo ?? '—' }}
            </p>
            <input v-else v-model="formCapacitacion.titulo" type="text"
                placeholder="Título de la capacitación"
                class="block w-full rounded-lg border-gray-300 text-sm py-2.5 focus:border-blue-500 focus:ring-blue-500" />
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Temas tratados</label>
            <p v-if="completado" class="text-sm text-gray-700 whitespace-pre-wrap">
                {{ equipos[0]?.descripcion_falla ?? '—' }}
            </p>
            <textarea v-else v-model="formCapacitacion.temas" rows="5"
                placeholder="Describe los temas tratados en la capacitación..."
                class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
        </div>

        <!-- Fotos -->
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-2">Evidencia Capacitación</label>
            <!-- Fotos existentes -->
            <div v-if="equipos[0]?.fotos_despues?.length > 0"
                class="grid grid-cols-3 gap-2 mb-3">
                <div v-for="foto in equipos[0].fotos_despues" :key="foto.id"
                    class="relative group">
                    <img :src="foto.url"
                        class="w-full h-24 object-cover rounded-lg border border-gray-200 cursor-pointer"
                        @click="fotoAmpliada = foto.url" />
                    <button v-if="!completado"
                        @click="eliminarFoto(foto.id)"
                        :class="buttonClass('danger', 'xs', 'absolute top-1 right-1 hidden h-6 w-6 px-0 py-0 group-hover:flex shadow')">
                        ✕
                    </button>
                </div>
            </div>
            <!-- Subir fotos -->
            <label v-if="!completado"
                class="flex items-center justify-center gap-2 w-full rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 py-4 cursor-pointer hover:bg-gray-100 transition">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="text-sm text-gray-400">Agregar fotos</span>
                <input type="file" accept="image/*" multiple class="hidden"
                    @change="subirFotos($event, equipos[0]?.id, 'DESPUES')" />
            </label>
        </div>

        <!-- Botón guardar -->
        <div v-if="!completado">
            <button
                @click="equipos.length > 0 ? guardarCapacitacion(equipos[0].id) : crearCapacitacion()"
                :disabled="!formCapacitacion.titulo || guardandoCapacitacion"
                :class="buttonClass('primary', 'md', 'w-full')">
                {{ guardandoCapacitacion ? 'Guardando...' : (equipos.length > 0 ? 'Guardar cambios' : 'Guardar') }}
            </button>
        </div>

    </div>
</div>

                <!-- ── Sección 3: Equipos (Mantenimiento) ── -->
                <div v-else-if="visita && !visita.es_capacitacion" class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div class="flex items-center justify-between bg-gray-50 border-b border-gray-100 px-4 py-2.5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Equipos atendidos</h3>
                        <button v-if="puedeEditar"
                            @click="abrirModalEquipo"
                            :class="buttonClass('primary', 'sm')">
                            + Agregar Equipo
                        </button>
                    </div>

                    <div v-if="equipos.length === 0" class="px-4 py-8 text-center text-sm text-gray-400">
                        No hay equipos registrados.
                    </div>

                    <div v-else class="space-y-2 bg-gray-50/60 px-3 py-3">
                        <div v-for="(equipo, indice) in equipos" :key="equipo.id" class="rounded-md border border-gray-200 bg-white overflow-hidden shadow-sm">
                            <!-- ENCABEZADO DESPLEGABLE DEL EQUIPO -->
                            <button @click="equipoExpandido = equipoExpandido === equipo.id ? null : equipo.id"
                                class="w-full px-3 py-2.5 flex items-start justify-between gap-3 hover:bg-gray-50 transition text-left border-b border-gray-200 bg-gray-50">
                                <div class="flex items-start gap-3 flex-1 min-w-0">
                                    <span class="w-4 shrink-0 mt-0.5 text-center text-lg leading-none font-bold text-gray-600">
                                        {{ equipoExpandido === equipo.id ? '-' : '+' }}
                                    </span>
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <p class="text-[13px] font-semibold text-gray-900 break-words">
                                            Equipo {{ indice + 1 }}
                                            <span v-if="equiposConDescripcion[equipo.id_cod_max]?.descripcion"> - {{ equiposConDescripcion[equipo.id_cod_max].descripcion }}</span>
                                            <span v-if="equipo.id_cod_max"> - {{ equipo.id_cod_max }}</span>
                                        </p>
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-gray-600">
                                            <span v-if="equipo.serial"><span class="font-medium text-gray-700">Serial:</span> {{ equipo.serial }}</span>
                                            <span v-if="equiposConDescripcion[equipo.id_cod_max]?.codigo_proveedor"><span class="font-medium text-gray-700">Código Comodidad:</span> {{ equiposConDescripcion[equipo.id_cod_max].codigo_proveedor }}</span>
                                        </div>
                                        <p v-if="equipo.descripcion_falla" class="text-[11px] text-gray-600 line-clamp-2">
                                            <span class="font-medium text-gray-700">Falla:</span> {{ equipo.descripcion_falla }}
                                        </p>
                                    </div>
                                </div>
                                <div class="ml-2 flex shrink-0 flex-col gap-1.5 sm:flex-row">
                                    <button v-if="puedeEditar && requiereRepuestos(equipo.soluciones_ids)"
                                        @click.stop="abrirModalRepuesto(equipo)"
                                        :class="buttonClass('success', 'xs')">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Repuesto
                                    </button>
                                    <button v-if="puedeEditar"
                                        @click.stop="abrirModalEditarEquipo(equipo)"
                                        :class="buttonClass('primary', 'xs')">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 8.586-8.586z" />
                                        </svg>
                                        Editar
                                    </button>
                                    <button v-if="puedeEditar"
                                        @click.stop="eliminarEquipo(equipo.id)"
                                        :class="buttonClass('danger', 'xs')">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Eliminar
                                    </button>
                                </div>
                            </button>

                            <!-- CONTENIDO DEL EQUIPO (expandible) -->
                            <Transition
                                name="slide-fade"
                                @before-enter="beforeExpandEnter"
                                @enter="expandEnter"
                                @after-enter="afterExpandEnter"
                                @before-leave="beforeExpandLeave"
                                @leave="expandLeave"
                                @after-leave="afterExpandLeave">
                                <div v-if="equipoExpandido === equipo.id" class="overflow-hidden">
                                <div class="bg-gray-50/70 px-2.5 py-2 space-y-2">
                                <!-- SECCIÓN 1: Información de servicio -->
                                <div class="rounded-md border border-gray-200 bg-white px-3 py-2">
                                    <div class="space-y-2">
                                        <div v-if="equipo.soluciones?.length || (puedeGestionarEvidenciaFinal && puedeAgregarSolucionComplementariaEquipo(equipo))">
                                            <div class="mb-1 flex items-center justify-between gap-2">
                                                <p class="text-[11px] text-gray-500 font-semibold uppercase tracking-wide">Soluciones aplicadas</p>
                                                <button
                                                    v-if="puedeGestionarEvidenciaFinal && puedeAgregarSolucionComplementariaEquipo(equipo)"
                                                    @click.stop="abrirModalSolucionesComplementarias(equipo)"
                                                    :class="buttonClass('emerald', 'xs', 'w-auto self-start px-2 py-1 text-[10px] leading-tight sm:px-2.5 sm:py-1.5 sm:text-[11px]')"
                                                >
                                                    <span class="sm:hidden"><b>+</b> Solución complementaria</span>
                                                    <span class="hidden sm:inline">Agregar solución complementaria</span>
                                                </button>
                                            </div>
                                            <div class="flex flex-wrap gap-1.5">
                                                <span v-for="sol in equipo.soluciones" :key="sol" class="inline-flex items-center px-2 py-1 rounded text-[11px] font-medium border border-gray-300 bg-white text-gray-900">
                                                    {{ sol }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SECCIÓN 2: Repuestos requeridos -->
                                <div v-if="requiereRepuestos(equipo.soluciones_ids)" class="ml-3 rounded-md border border-gray-200 bg-white overflow-hidden">
                                    <button @click="toggleSeccion(equipo.id, 'repuestos')"
                                        class="w-full px-3 py-2 flex items-center justify-between hover:bg-gray-50 transition text-left border-l-2 border-l-amber-300 bg-amber-50/40">
                                        <div class="flex items-center gap-3">
                                            <span class="w-3.5 text-center text-lg leading-none font-bold text-amber-700">
                                                {{ esSeccionExpandida(equipo.id, 'repuestos') ? '-' : '+' }}
                                            </span>
                                            <p class="text-[11px] font-semibold text-amber-800 uppercase tracking-wide">Repuestos requeridos</p>
                                        </div>
                                        <span v-if="equipo.repuestos?.length" class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">{{ equipo.repuestos.length }}</span>
                                    </button>
                                    <Transition
                                        name="slide-fade"
                                        @before-enter="beforeExpandEnter"
                                        @enter="expandEnter"
                                        @after-enter="afterExpandEnter"
                                        @before-leave="beforeExpandLeave"
                                        @leave="expandLeave"
                                        @after-leave="afterExpandLeave">
                                        <div v-if="esSeccionExpandida(equipo.id, 'repuestos')">
                                            <div class="px-3 py-2 bg-white space-y-1.5 border-t border-gray-100">
                                                <div v-if="!equipo.repuestos?.length" class="text-xs text-gray-500 py-1.5 text-center">
                                                    No hay repuestos agregados
                                                </div>
                                                <div v-else class="space-y-1.5">
                                                    <div v-for="repuesto in equipo.repuestos" :key="repuesto.id"
                                                        class="flex flex-col sm:flex-row sm:items-start justify-between rounded border border-gray-200 bg-gray-50/60 px-3 py-2 text-xs hover:bg-gray-50 transition gap-2">
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-1">
                                                                <p class="font-semibold text-gray-900">Código Max: {{ repuesto.id_cod_max }}</p>
                                                                <span v-if="repuestosConDescripcion[repuesto.id_cod_max]?.codigo_proveedor" class="text-gray-500 hidden sm:inline">|</span>
                                                                <span v-if="repuestosConDescripcion[repuesto.id_cod_max]?.codigo_proveedor" class="text-gray-700 font-medium">Código Comodidad: {{ repuestosConDescripcion[repuesto.id_cod_max].codigo_proveedor }}</span>
                                                            </div>
                                                            <p v-if="repuestosConDescripcion[repuesto.id_cod_max]?.descripcion" class="text-gray-600 mb-1">{{ repuestosConDescripcion[repuesto.id_cod_max].descripcion }}</p>
                                                            <span class="text-gray-700">Cantidad: <span class="font-semibold">{{ repuesto.cantidad }}</span></span>
                                                        </div>
                                                        <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-start sm:ml-2">
                                                            <span class="text-xs font-semibold px-2 py-1 rounded border"
                                                                :class="estadoRepuestoColor(repuesto.estado ?? '')">
                                                                {{ repuesto.estado ?? 'Sin estado' }}
                                                            </span>
                                                            <button v-if="!completado && Number(repuesto.estado_id) === 18"
                                                                @click="abrirModalInstalacion(equipo, repuesto)"
                                                                :class="buttonClass('success', 'xs')">
                                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                                Instalar
                                                            </button>
                                                            <button v-if="puedeEditar && [13, 19].includes(Number(repuesto.estado_id))"
                                                                @click="abrirModalEditarRepuesto(equipo, repuesto)"
                                                                :class="buttonClass('primary', 'xs', 'h-8 w-8 px-0 py-0 shadow-sm ring-1 ring-blue-200')"
                                                                title="Editar repuesto"
                                                                aria-label="Editar repuesto">
                                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 8.586-8.586z" />
                                                                </svg>
                                                            </button>
                                                            <button v-if="puedeEditar && [13, 19].includes(Number(repuesto.estado_id))"
                                                                @click="eliminarRepuesto(repuesto.id)"
                                                                :class="buttonClass('danger', 'xs')">X</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- SECCIÓN: Evidencia Inicial -->
                                <div class="ml-3 rounded-md border border-gray-200 bg-white overflow-hidden">
                                    <button @click="toggleSeccion(equipo.id, 'fotos-antes')"
                                        class="w-full px-3 py-2 flex items-center justify-between hover:bg-gray-50 transition text-left border-l-2 border-l-sky-300 bg-sky-50/40">
                                        <div class="flex items-center gap-3">
                                            <span class="w-3.5 text-center text-lg leading-none font-bold text-sky-700">
                                                {{ esSeccionExpandida(equipo.id, 'fotos-antes') ? '-' : '+' }}
                                            </span>
                                            <p class="text-[11px] font-semibold text-sky-800 uppercase tracking-wide">Evidencia inicial</p>
                                        </div>
                                        <span v-if="equipo.fotos_antes?.length" class="rounded-full bg-sky-100 px-2 py-0.5 text-[11px] font-semibold text-sky-800">{{ equipo.fotos_antes.length }}</span>
                                    </button>
                                    <Transition
                                        name="slide-fade"
                                        @before-enter="beforeExpandEnter"
                                        @enter="expandEnter"
                                        @after-enter="afterExpandEnter"
                                        @before-leave="beforeExpandLeave"
                                        @leave="expandLeave"
                                        @after-leave="afterExpandLeave">
                                        <div v-if="esSeccionExpandida(equipo.id, 'fotos-antes')">
                                            <div class="px-3 py-2 bg-white space-y-1.5 border-t border-gray-100">
                                                <div v-if="equipo.fotos_antes?.length > 0" class="space-y-1.5">
                                                    <div v-for="(foto, idx) in equipo.fotos_antes" :key="'antes-' + idx"
                                                        class="flex items-center justify-between text-xs bg-gray-50/60 px-3 py-2 rounded border border-gray-200 hover:bg-gray-50 transition">
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            <span class="text-gray-700 font-medium">Foto {{ idx + 1 }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <button @click="fotoAmpliada = foto.url"
                                                                :class="buttonClass('primary', 'xs')">
                                                                Ver
                                                            </button>
                                                            <button v-if="!completado && puedeEditar"
                                                                @click="eliminarFoto(foto.id)"
                                                                :class="buttonClass('danger', 'xs')">
                                                                Eliminar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="text-xs text-gray-500 py-1.5 text-center">
                                                    Sin evidencia inicial registrada
                                                </div>
                                                <label v-if="!completado && puedeEditar"
                                                    class="flex items-center justify-center gap-2 w-full rounded border border-dashed border-gray-300 bg-gray-50 py-2 px-3 cursor-pointer hover:bg-gray-100 transition mt-1">
                                                    <svg class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    <span class="text-xs font-medium text-gray-700">Agregar evidencia</span>
                                                    <input type="file" accept="image/*" multiple class="hidden"
                                                        @change="subirFotos($event, equipo.id, 'ANTES')" />
                                                </label>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>

                                <!-- SECCIÓN: Evidencia Final -->
                                <div class="ml-3 rounded-md border border-gray-200 bg-white overflow-hidden">
                                    <button @click="toggleSeccion(equipo.id, 'fotos-despues')"
                                        class="w-full px-3 py-2 flex items-center justify-between hover:bg-gray-50 transition text-left border-l-2 border-l-emerald-300 bg-emerald-50/40">
                                        <div class="flex items-center gap-3">
                                            <span class="w-3.5 text-center text-lg leading-none font-bold text-emerald-700">
                                                {{ esSeccionExpandida(equipo.id, 'fotos-despues') ? '-' : '+' }}
                                            </span>
                                            <p class="text-[11px] font-semibold text-emerald-800 uppercase tracking-wide">Evidencia final</p>
                                        </div>
                                        <span v-if="equipo.fotos_despues?.length" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">{{ equipo.fotos_despues.length }}</span>
                                    </button>
                                    <Transition
                                        name="slide-fade"
                                        @before-enter="beforeExpandEnter"
                                        @enter="expandEnter"
                                        @after-enter="afterExpandEnter"
                                        @before-leave="beforeExpandLeave"
                                        @leave="expandLeave"
                                        @after-leave="afterExpandLeave">
                                        <div v-if="esSeccionExpandida(equipo.id, 'fotos-despues')">
                                            <div class="px-3 py-2 bg-white space-y-1.5 border-t border-gray-100">
                                                <div v-if="equipo.fotos_despues?.length > 0" class="space-y-1">
                                                    <div v-for="(foto, idx) in equipo.fotos_despues" :key="'despues-' + idx"
                                                        class="flex items-center justify-between text-xs bg-gray-50/60 px-3 py-1.5 rounded border border-gray-200 hover:bg-gray-50 transition">
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                            </svg>
                                                            <span class="text-gray-700 font-medium">Foto {{ idx + 1 }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <button @click="fotoAmpliada = foto.url"
                                                                :class="buttonClass('primary', 'xs')">
                                                                Ver
                                                            </button>
                                                            <button v-if="!completado && puedeGestionarEvidenciaFinal"
                                                                @click="eliminarFoto(foto.id)"
                                                                :class="buttonClass('danger', 'xs')">
                                                                Eliminar
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div v-else class="text-xs text-gray-500 py-1.5 text-center">
                                                    Sin evidencia final registrada
                                                </div>
                                                <label v-if="!completado && puedeGestionarEvidenciaFinal"
                                                    class="flex items-center justify-center gap-2 w-full rounded border border-dashed border-gray-300 bg-gray-50 py-2 px-3 cursor-pointer hover:bg-gray-100 transition mt-1">
                                                    <svg class="h-4 w-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    <span class="text-xs font-medium text-gray-700">Agregar evidencia final</span>
                                                    <input type="file" accept="image/*" multiple class="hidden"
                                                        @change="subirFotos($event, equipo.id, 'DESPUES')" />
                                                </label>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                                </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
                <div v-if="historial.length > 0"
                    class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 border-b border-gray-100 px-4 py-2.5">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Historial</h3>
                    </div>
                    <div class="px-4 py-3">
                        <div class="relative">
                            <!-- Línea vertical -->
                            <div class="absolute left-2 top-2 bottom-2 w-0.5 bg-gray-100"></div>
                            <div class="space-y-4">
                                <div v-for="(item, index) in historial" :key="index"
                                    class="relative flex gap-3 pl-7">
                                    <!-- Punto -->
                                    <div class="absolute left-0 mt-1 h-4 w-4 rounded-full border-2 border-white shadow-sm"
                                        :class="historialEstadoColor(item.estado)"></div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-medium text-gray-900">{{ item.estado }}</p>
                                            <p class="text-xs text-gray-400 shrink-0">{{ item.fecha }}</p>
                                        </div>
                                        <p v-if="item.observaciones" class="text-xs text-gray-500 mt-0.5">
                                            {{ item.observaciones }}
                                        </p>
                                        <p v-if="item.usuario" class="text-xs text-gray-400">
                                            {{ item.usuario }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Sección: Firma del cliente ── -->
<div v-if="completado" class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
    <div class="bg-gray-50 border-b border-gray-100 px-4 py-2.5 flex items-center justify-between">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Firma del cliente</h3>
        <span v-if="completado && visita.tiene_firma"
            class="inline-flex items-center gap-1 text-xs text-green-600 font-medium">
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Firmado
        </span>
    </div>
    <div class="px-4 py-4">

        <!-- Visita completada — mostrar firma -->
        <div v-if="completado && visita.firma_url">
            <div class="rounded-xl border border-gray-100 bg-gray-50 p-3 flex items-center justify-center">
                <img :src="visita.firma_url"
                    class="max-h-32 w-auto rounded-lg cursor-pointer"
                    @click="fotoAmpliada = visita.firma_url" />
            </div>
            <p class="mt-2 text-xs text-gray-400 text-center">Firma registrada el {{ visita.fecha_fin ?? visita.fecha_inicio }}</p>
        </div>

        <!-- Visita completada sin firma -->
        <div v-else-if="completado && !visita.firma_url"
            class="rounded-xl border border-dashed border-gray-200 bg-gray-50 py-6 text-center">
            <p class="text-sm text-gray-400">Sin firma registrada</p>
        </div>
    </div>
</div>

                <!-- ── Sección 6: Acciones ── -->
                <div v-if="!completado" class="flex gap-3">
                    <a :href="route('visitastecnicas.visitas.index')"
                        :class="buttonClass('neutral', 'lg', 'flex-1 text-center')">
                        Volver
                    </a>
                    <button v-if="puedeGuardarBorrador"
                        @click="guardarBorrador"
                        :class="buttonClass('primary', 'lg', 'flex-1')">
                        Guardar
                    </button>
                    <button v-if="puedeSolicitarCotizacion"
                        @click="abrirModalCotizacion"
                        :class="buttonClass('warning', 'lg', 'flex-1')">
                        Solicitar Cotización al Asesor
                    </button>
                    <button v-if="puedeFinalizar"
                        @click="abrirModalFinalizar"
                        :class="buttonClass('success', 'lg', 'flex-1')">
                        Finalizar
                    </button>
                </div>
                <div v-else class="flex">
                    <a :href="route('visitastecnicas.visitas.index')"
                        :class="buttonClass('neutral', 'lg', 'flex-1 text-center')">
                        Volver al listado
                    </a>
                </div>

            </div>
        </div>

        <!-- Modal Agregar Equipo con Repuestos integrado -->
        <div v-if="modalEquipo" class="fixed inset-0 z-50 flex items-start justify-center bg-slate-950/45 p-3 pt-5 backdrop-blur-[2px] sm:items-center sm:p-4">
            <div class="flex h-[92lvh] max-h-[92lvh] w-full max-w-2xl flex-col overflow-hidden rounded-[30px] border border-slate-200 bg-slate-50 shadow-[0_24px_80px_-24px_rgba(15,23,42,0.45)] sm:h-auto sm:max-h-[96vh]">
                <div class="shrink-0 border-b border-slate-200 bg-white px-4 py-3.5 sm:px-5">
                    <h3 class="text-lg font-semibold text-slate-900">
                        {{ equipoEditandoId ? 'Editar Equipo' : 'Agregar Equipo' }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">Completa los datos principales y agrega repuestos solo si la solucion lo requiere.</p>
                    <div class="mt-3">
                        <div class="flex items-center gap-2 sm:gap-3">
                            <div
                                v-for="(paso, index) in pasosStepperEquipo"
                                :key="paso.id"
                                class="flex min-w-0 items-center gap-2"
                                :class="pasoEquipoActual === paso.id ? 'flex-[1.35]' : 'shrink-0'"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <div
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold transition"
                                        :class="pasoEquipoActual === paso.id
                                            ? 'bg-blue-600 text-white shadow-sm'
                                            : paso.completo
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : paso.condicional
                                                    ? 'border border-dashed border-slate-300 bg-white text-slate-400'
                                                    : 'bg-slate-100 text-slate-500'"
                                    >
                                        {{ paso.id }}
                                    </div>
                                    <div
                                        class="min-w-0"
                                        :class="pasoEquipoActual === paso.id ? 'block' : 'hidden sm:block'"
                                    >
                                        <p
                                            class="truncate text-xs font-semibold uppercase tracking-[0.14em]"
                                            :class="pasoEquipoActual === paso.id ? 'text-blue-700' : paso.completo ? 'text-emerald-700' : 'text-slate-400'"
                                        >
                                            {{ paso.titulo }}
                                        </p>
                                        <p v-if="paso.condicional" class="truncate text-[10px] text-slate-400">
                                            Si aplica
                                        </p>
                                    </div>
                                </div>
                                <div
                                    v-if="index < pasosStepperEquipo.length - 1"
                                    class="h-px min-w-3 flex-1 rounded-full sm:min-w-6"
                                    :class="paso.completo ? 'bg-emerald-300' : paso.condicional ? 'border-t border-dashed border-slate-200 bg-transparent' : 'bg-slate-200'"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3.5 sm:px-5">
                    <div class="space-y-3">
                        <!-- EQUIPO FORM -->
                        <section v-if="pasoEquipoActual === 1" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm shadow-slate-200/50">
                            <div class="space-y-1">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Equipo</p>
                                <label class="block text-xs font-medium text-slate-700">Buscar Equipo <span class="text-red-500">*</span></label>
                            </div>
                            <div class="relative">
                                <input v-model="herramientasBusqueda" @input="buscarHerramientas" type="text" placeholder="Buscar por código, descripción, referencia o codigo de comodidad..."
                                    class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                                <div v-if="herramientasCargando" class="absolute right-3 top-3.5">
                                    <svg class="h-4 w-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div v-if="herramientasResultados.length > 0" class="max-h-48 overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <div v-for="item in herramientasResultados" :key="item.codigo"
                                    @click="seleccionarHerramienta(item)"
                                    class="cursor-pointer border-b border-slate-100 px-4 py-2.5 transition hover:bg-slate-50 last:border-b-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-slate-900">{{ item.descripcion }}</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                                                <span>Cod. {{ item.codigo }}</span>
                                                <span v-if="item.referencia">Ref. {{ item.referencia }}</span>
                                                <span v-if="item.proveedor">Comod. {{ item.proveedor }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="herramientaSeleccionada" class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-2.5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-blue-600">Seleccionado</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ herramientaSeleccionada.descripcion }}</p>
                                <p class="mt-1 text-xs text-slate-600">Cod. {{ herramientaSeleccionada.codigo }}</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-700">Serial <span class="text-red-500">*</span></label>
                                    <input
                                        v-model="formEquipo.serial"
                                        type="text"
                                        placeholder="Serial del equipo"
                                        class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                                    <p v-if="formEquipo.errors.serial" class="mt-1.5 text-xs text-red-600">
                                        {{ formEquipo.errors.serial }}
                                    </p>
                                </div>
                            </div>
                        </section>

                        <!-- Foto ANTES -->
                        <section v-if="pasoEquipoActual === 1 && !equipoEditandoId" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm shadow-slate-200/50">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Evidencia inicial</p>
                                <p class="mt-1 text-xs text-slate-500">Adjunta una o varias fotos del equipo antes de intervenirlo.</p>
                            </div>
                            <div v-if="fotosAntesEquipo.length > 0" class="space-y-2">
                                <div v-for="(foto, index) in fotosAntesEquipo" :key="`${foto.nombre}-${index}`" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <svg class="h-4 w-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <span class="truncate font-medium text-slate-700">{{ foto.nombre }}</span>
                                        </div>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <button type="button"
                                                @click="fotoAmpliada = foto.url"
                                                :class="buttonClass('primary', 'xs')">
                                                Ver
                                            </button>
                                            <button type="button"
                                                @click="eliminarFotoAntes(index)"
                                                :class="buttonClass('danger', 'xs')">
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <label
                                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-3.5 transition hover:border-slate-400 hover:bg-slate-100">
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <div class="text-center">
                                    <p class="text-xs font-medium text-slate-700">
                                        {{ fotosAntesEquipo.length > 0 ? 'Agregar más fotos' : 'Captura del equipo' }}
                                    </p>
                                    <p class="text-[10px] text-slate-500">(PNG, JPG, máx 5MB)</p>
                                </div>
                                <input type="file" accept="image/*" multiple class="hidden" @change="seleccionarFotoAntes" />
                            </label>
                        </section>

                        <section v-if="pasoEquipoActual === 2" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm shadow-slate-200/50">
                            <div class="space-y-2.5 rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Descripcion de falla</p>
                                    <p class="mt-1 text-xs text-slate-500">Describe con claridad el problema identificado en campo.</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-700">Descripcion de falla <span class="text-red-500">*</span></label>
                                    <textarea v-model="formEquipo.descripcion_falla" rows="3"
                                        class="block w-full resize-none rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                                    <p v-if="formEquipo.errors.descripcion_falla" class="mt-1.5 text-xs text-red-600">
                                        {{ formEquipo.errors.descripcion_falla }}
                                    </p>
                                </div>
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Tipo de solucion</p>
                                <p class="mt-1 text-xs text-slate-500">Selecciona la accion realizada sobre el equipo.</p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-white p-2.5">
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="s in tiposSolucionOrdenados"
                                        :key="s.ID"
                                        type="button"
                                        @click="toggleSolucion(s.ID)"
                                        :disabled="solucionBloqueada(s.ID)"
                                        :class="[
                                            solucionSeleccionada(s.ID)
                                                ? 'border-blue-200 bg-blue-600 text-white shadow-sm shadow-blue-200/70 scale-[1.02]'
                                                : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700',
                                            solucionBloqueada(s.ID)
                                                ? 'cursor-not-allowed opacity-45'
                                                : 'cursor-pointer',
                                        ]"
                                        class="inline-flex items-center rounded-full border px-3 py-1.5 text-[11px] font-semibold leading-none transition-all duration-200 ease-out"
                                    >
                                        {{ s.TIPO_SOLUCION }}
                                    </button>
                                </div>
                                <p class="mt-3 text-[11px] text-slate-500">
                                    {{ formEquipo.id_solucion.length }} seleccionada<span v-if="formEquipo.id_solucion.length !== 1">s</span>
                                </p>
                                <p
                                    v-if="!equipoEditandoId && formEquipo.id_solucion.some(id => esSolucionCambioRepuestos(id))"
                                    class="mt-1 text-[11px] text-blue-600"
                                >
                                    Cambio de repuestos activa el paso 3.
                                </p>
                                <p
                                    v-else-if="tiposSolucionOrdenados.some(s => solucionBloqueada(s.ID))"
                                    class="mt-1 text-[11px] text-slate-500"
                                >
                                    Cambio de repuestos no se puede combinar con otras soluciones.
                                </p>
                            </div>

                            <p v-if="formEquipo.errors.id_solucion" class="mt-2 text-xs text-red-600">
                                {{ formEquipo.errors.id_solucion }}
                            </p>

                            <div
                                v-if="equipoEditandoId && requiereRepuestos(formEquipo.id_solucion)"
                                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-800"
                            >
                                Los repuestos de este equipo se gestionan desde el boton <span class="font-semibold">Repuesto</span>.
                            </div>

                            <div class="space-y-1.5 rounded-2xl border border-slate-200 bg-slate-50 p-3.5">
                                <label class="block text-xs font-medium text-slate-700">Observaciones</label>
                                <textarea v-model="formEquipo.observaciones" rows="2"
                                    class="block w-full resize-none rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                            </div>
                        </section>

                        <!-- Repuestos requeridos -->
                        <section v-if="pasoEquipoActual === 3 && requiereRepuestos(formEquipo.id_solucion)" class="space-y-3 rounded-2xl border border-blue-200 bg-blue-50/60 p-3.5 shadow-sm shadow-blue-100/50">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">Repuestos requeridos</h4>
                                    <p class="mt-1 text-xs text-slate-600">
                                        Debes agregar al menos un repuesto para guardar este equipo.
                                    </p>
                                </div>
                                <span class="rounded-full border border-blue-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-blue-700">
                                    {{ repuestosTemporal.length }} agregado<span v-if="repuestosTemporal.length !== 1">s</span>
                                </span>
                            </div>

                            <p v-if="formEquipo.errors.repuestos" class="text-xs text-red-600">
                                {{ formEquipo.errors.repuestos }}
                            </p>

                            <div class="space-y-3">
                                <div ref="repuestoEditorRef">
                                    <label class="mb-1.5 block text-xs font-medium text-slate-700">Buscar repuesto</label>
                                    <div class="relative">
                                        <input v-model="repuestosBusqueda" @input="buscarRepuestos" type="text" placeholder="Codigo, descripcion, referencia o codigo de comodidad..."
                                            class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                                        <div v-if="repuestosCargando" class="absolute right-3 top-3.5">
                                            <svg class="h-4 w-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div v-if="repuestosResultados.length > 0" class="mt-2 max-h-56 overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                                        <div v-for="item in repuestosResultados" :key="item.codigo"
                                            @click="seleccionarRepuesto(item)"
                                            class="cursor-pointer border-b border-slate-100 px-4 py-2.5 transition hover:bg-slate-50 last:border-b-0">
                                            <div class="flex items-start justify-between gap-3">
                                                <div class="min-w-0 flex-1">
                                                    <p class="truncate text-sm font-semibold text-slate-900">{{ item.descripcion }}</p>
                                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                                                        <span>Cod. {{ item.codigo }}</span>
                                                        <span v-if="item.referencia">Ref. {{ item.referencia }}</span>
                                                        <span v-if="item.proveedor">Comod. {{ item.proveedor }}</span>
                                                    </div>
                                                </div>
                                                <div class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="claseStock(item.inventario)">
                                                    {{ textoStock(item.inventario) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="repuestoSeleccionado" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs text-emerald-800">
                                    Inventario disponible: <span class="font-semibold">{{ formatearInventario(repuestoSeleccionado.inventario) }}</span>
                                </div>

                                <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-[120px_minmax(0,1fr)]">
                                    <div>
                                        <label class="mb-1.5 block text-xs font-medium text-slate-700">Cantidad</label>
                                        <input v-model.number="formRepuesto.cantidad" type="number" min="1"
                                            class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                                        <p v-if="formRepuesto.errors.cantidad" class="mt-1.5 text-xs text-red-600">
                                            {{ formRepuesto.errors.cantidad }}
                                        </p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-xs font-medium text-slate-700">Observacion (opcional)</label>
                                            <textarea v-model="formRepuesto.observacion" rows="1" placeholder="Nota adicional..."
                                            class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                                    </div>
                                </div>

                                <label class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-900">
                                    <input
                                        v-model="formRepuesto.resolver_en_campo"
                                        type="checkbox"
                                        class="mt-0.5 h-4 w-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
                                    />
                                    <span>
                                        Marcar como resuelto en campo.
                                        <span class="block text-emerald-700">El repuesto quedará instalado y no pasará por cotización.</span>
                                    </span>
                                </label>

                                <button @click="agregarRepuestoTemporal" :disabled="!repuestoSeleccionado || Number(formRepuesto.cantidad) <= 0"
                                    :class="buttonClass('primary', 'lg', 'w-full')">
                                    {{ repuestoTemporalEditandoIndex !== null ? 'Actualizar repuesto' : '+ Agregar Repuesto' }}
                                </button>

                                <button
                                    v-if="repuestoTemporalEditandoIndex !== null"
                                    @click="limpiarFormularioRepuestoTemporal"
                                    :class="buttonClass('neutral', 'md', 'w-full')"
                                >
                                    Cancelar edición
                                </button>
                            </div>

                            <div v-if="repuestosTemporal.length > 0" class="rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm">
                                <p class="mb-3 text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Repuestos agregados</p>
                                <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                                    <div v-for="(r, idx) in repuestosTemporal" :key="idx" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs">
                                        <div class="min-w-0 flex-1">
                                            <p class="font-medium text-slate-900">{{ r.descripcion }}</p>
                                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                                                <span>Cod. {{ r.codigo }}</span>
                                                <span>Cantidad: {{ r.cantidad }}</span>
                                            </div>
                                            <p class="mt-1 text-emerald-700">Stock: {{ formatearInventario(r.inventario) }}</p>
                                            <p v-if="r.resolver_en_campo" class="mt-1 text-emerald-700">Resuelto en campo</p>
                                        </div>
                                        <div class="ml-2 flex shrink-0 items-center gap-2">
                                            <button
                                                @click="editarRepuestoTemporal(idx)"
                                                :class="buttonClass('primary', 'xs', 'h-8 w-8 px-0 py-0 shadow-sm ring-1 ring-blue-200')"
                                                title="Editar repuesto"
                                                aria-label="Editar repuesto"
                                            >
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L12 15l-4 1 1-4 8.586-8.586z" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="eliminarRepuestoTemporal(idx)"
                                                :class="buttonClass('danger', 'xs', 'h-8 w-8 px-0 py-0 shadow-sm')"
                                                title="Eliminar repuesto"
                                                aria-label="Eliminar repuesto"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                    </div>
                </div>

                <div class="shrink-0 border-t border-slate-200 bg-white px-4 py-3.5 sm:px-5">
                    <p v-if="mensajeGuardarEquipo" class="mb-2.5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-600">
                        {{ mensajeGuardarEquipo }}
                    </p>
                    <div class="flex gap-3">
                        <button @click="modalEquipo = false"
                            :class="buttonClass('neutral', 'lg', 'flex-1')">
                            Cancelar
                        </button>
                        <button
                            v-if="pasoEquipoActual > 1"
                            @click="irPasoEquipoAnterior"
                            :class="buttonClass('neutral', 'lg', 'flex-1')"
                        >
                            Atrás
                        </button>
                        <button
                            v-if="!esUltimoPasoEquipo"
                            @click="irPasoEquipoSiguiente"
                            :disabled="!puedeAvanzarPasoEquipo"
                            :class="buttonClass('primary', 'lg', 'flex-1')"
                        >
                            Continuar
                        </button>
                        <button
                            v-else
                            @click="guardarEquipo"
                            :disabled="formEquipo.processing || !puedeGuardarEquipo"
                            :class="buttonClass('primary', 'lg', 'flex-1')"
                        >
                            {{ formEquipo.processing ? (equipoEditandoId ? 'Actualizando...' : 'Guardando...') : (equipoEditandoId ? 'Actualizar' : 'Guardar') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Agregar Repuesto a Equipo existente -->
        <div v-if="modalRepuesto" class="fixed inset-0 z-50 flex items-start justify-center bg-slate-950/45 p-3 pt-5 backdrop-blur-[2px] sm:items-center sm:p-4">
            <div class="flex max-h-[78svh] w-full max-w-md flex-col overflow-hidden rounded-[30px] border border-slate-200 bg-slate-50 shadow-[0_24px_80px_-24px_rgba(15,23,42,0.45)] sm:max-h-[85vh]">
                <div class="border-b border-slate-200 bg-white px-4 py-4 sm:px-5">
                    <h3 class="text-base font-semibold text-slate-900">{{ repuestoEditandoId ? 'Editar Repuesto' : 'Agregar Repuesto' }}</h3>
                </div>

                <div class="overflow-y-auto px-4 py-4 sm:px-5">
                <div class="mb-3 rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Equipo</p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ equipoSeleccionado?.id_cod_max }}</p>
                    <p v-if="equiposConDescripcion[equipoSeleccionado?.id_cod_max]?.descripcion" class="mt-1 text-xs text-slate-500">{{ equiposConDescripcion[equipoSeleccionado?.id_cod_max].descripcion }}</p>
                    <p v-if="equiposConDescripcion[equipoSeleccionado?.id_cod_max]?.codigo_proveedor" class="mt-1 text-xs text-blue-600">Cod. Comodidad: {{ equiposConDescripcion[equipoSeleccionado?.id_cod_max].codigo_proveedor }}</p>
                </div>

                <div class="space-y-3">
                    <div class="relative">
                        <label class="mb-1.5 block text-xs font-medium text-slate-700">{{ repuestoEditandoId ? 'Repuesto' : 'Buscar repuesto' }}</label>
                        <div class="relative">
                            <input v-model="repuestosBusqueda" @input="buscarRepuestos" type="text" placeholder="Codigo, descripcion, referencia o codigo de comodidad..."
                                :disabled="!!repuestoEditandoId"
                                class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500" />
                            <div v-if="repuestosCargando" class="absolute right-3 top-3.5">
                                <svg class="h-4 w-4 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </div>
                        </div>
                        <div v-if="!repuestoEditandoId && repuestosResultados.length > 0" class="mt-2 max-h-56 overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                            <div v-for="item in repuestosResultados" :key="item.codigo"
                                @click="seleccionarRepuesto(item)"
                                class="cursor-pointer border-b border-slate-100 px-4 py-2.5 transition hover:bg-slate-50 last:border-b-0">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ item.descripcion }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                                            <span>Cod. {{ item.codigo }}</span>
                                            <span v-if="item.referencia">Ref. {{ item.referencia }}</span>
                                            <span v-if="item.proveedor">Comod. {{ item.proveedor }}</span>
                                        </div>
                                    </div>
                                    <div class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold" :class="claseStock(item.inventario)">
                                        {{ textoStock(item.inventario) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="repuestoSeleccionado" class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs text-emerald-800">
                        Inventario disponible: <span class="font-semibold">{{ formatearInventario(repuestoSeleccionado.inventario) }}</span>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-700">Cantidad</label>
                        <input v-model.number="formRepuesto.cantidad" type="number" min="1"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-700">Observacion (opcional)</label>
                        <textarea v-model="formRepuesto.observacion" rows="2" placeholder="Nota adicional..."
                            class="block w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-800 focus:border-blue-500 focus:ring-4 focus:ring-blue-100" />
                    </div>
                    <label class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs text-emerald-900">
                        <input
                            v-model="formRepuesto.resolver_en_campo"
                            type="checkbox"
                            class="mt-0.5 h-4 w-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500"
                        />
                        <span>
                            Marcar como resuelto en campo.
                            <span class="block text-emerald-700">El repuesto quedará instalado y no pasará por cotización.</span>
                        </span>
                    </label>
                </div>
                </div>

                <div class="border-t border-slate-200 bg-white px-4 py-4 sm:px-5">
                <div class="flex gap-3">
                    <button @click="modalRepuesto = false"
                        :class="buttonClass('neutral', 'md', 'flex-1')">
                        Cancelar
                    </button>
                    <button @click="guardarRepuesto" :disabled="formRepuesto.processing || !repuestoSeleccionado"
                        :class="buttonClass('success', 'md', 'flex-1')">
                        {{ formRepuesto.processing ? 'Guardando...' : (repuestoEditandoId ? 'Actualizar' : 'Agregar') }}
                    </button>
                </div>
                </div>
            </div>
        </div>

        <!-- ── Modal: Finalizar ── -->
        <div v-if="modalFinalizar" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="mb-2 text-base font-semibold text-gray-900">Finalizar visita</h3>
                <p class="mb-4 text-sm text-gray-500">¿Confirmas que el servicio fue realizado?</p>
                <div v-if="mostrarAlertaFinalizacion && camposFaltantesFinalizacion.length"
                    class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3">
                    <p class="text-sm font-semibold text-red-700">Faltan campos obligatorios para finalizar la visita:</p>
                    <ul class="mt-2 list-disc pl-5 text-sm text-red-600">
                        <li v-for="campo in camposFaltantesFinalizacion" :key="campo">
                            {{ campo }}
                        </li>
                    </ul>
                </div>
                <div v-if="!visita.es_capacitacion" class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-3 space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Correo para informe técnico de la visita
</label>
                        <input v-model="formServicio.correo_cliente" type="email"
                            class="block w-full rounded-lg border-gray-300 text-sm py-2.5 focus:border-blue-500 focus:ring-blue-500" />
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Fecha fin <span class="text-red-500">*</span></label>
                            <input v-model="formFinalizar.fecha_fin" type="date" required
                                class="block w-full rounded-lg border-gray-300 text-sm py-2.5 focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="formFinalizar.errors.fecha_fin" class="mt-1 text-xs text-red-600">
                                {{ formFinalizar.errors.fecha_fin }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Hora fin <span class="text-red-500">*</span></label>
                            <input v-model="formFinalizar.hora_fin" type="time" required
                                class="block w-full rounded-lg border-gray-300 text-sm py-2.5 focus:border-blue-500 focus:ring-blue-500" />
                            <p v-if="formFinalizar.errors.hora_fin" class="mt-1 text-xs text-red-600">
                                {{ formFinalizar.errors.hora_fin }}
                            </p>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Observaciones finales</label>
                    <textarea v-model="formFinalizar.observaciones" rows="3"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                </div>
                <div v-if="!visita.es_capacitacion" class="mt-4">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Firma del cliente</label>
                        <button type="button" @click="limpiarFirma"
                            :class="buttonClass('neutral', 'xs')">
                            Limpiar
                        </button>
                    </div>
                    <canvas ref="canvasFirma" width="600" height="150"
                        class="w-full rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 touch-none cursor-crosshair"
                        @mousedown="iniciarFirma" @mousemove="dibujarFirma"
                        @mouseup="terminarFirma" @mouseleave="terminarFirma"
                        @touchstart.prevent="iniciarFirma" @touchmove.prevent="dibujarFirma"
                        @touchend="terminarFirma" />
                    <p v-if="formFinalizar.errors.firma" class="mt-1 text-xs text-red-600">
                        {{ formFinalizar.errors.firma }}
                    </p>
                    <p v-else class="mt-1 text-xs text-gray-400">Puedes firmar en el recuadro si deseas adjuntar la evidencia del cierre.</p>
                </div>
                <div class="mt-5 flex gap-3">
                    <button @click="modalFinalizar = false"
                        :class="buttonClass('neutral', 'md', 'flex-1')">
                        Cancelar
                    </button>
                    <button @click="finalizar" :disabled="formFinalizar.processing"
                        :class="buttonClass('success', 'md', 'flex-1')">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>

        <div v-if="modalGuardadoBorrador" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-sm rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="text-base font-semibold text-gray-900">Los cambios han sido guardados</h3>
                <div class="mt-5">
                    <button
                        @click="modalGuardadoBorrador = false"
                        :class="buttonClass('primary', 'md', 'w-full')"
                    >
                        Entendido
                    </button>
                </div>
            </div>
        </div>

        <div v-if="modalCotizacion" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="mb-2 text-base font-semibold text-gray-900">Enviar a cotización</h3>
                <p class="mb-4 text-sm text-gray-500">
                    Se confirmarán los equipos y repuestos agregados. Después de esto, la visita quedará en espera de repuestos y ya no podrás editar, agregar o eliminar detalles.
                </p>
                <div v-if="mostrarAlertaCotizacion" class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3">
                    <p class="text-sm font-semibold text-red-700">No fue posible enviar la visita a cotización.</p>
                    <p class="mt-1 text-sm text-red-600">Revisa la información registrada de la visita e inténtalo nuevamente.</p>
                </div>
                <div class="mt-5 flex gap-3">
                    <button @click="modalCotizacion = false"
                        :class="buttonClass('neutral', 'md', 'flex-1')">
                        Cancelar
                    </button>
                    <button @click="solicitarCotizacion" :disabled="enviandoCotizacion"
                        :class="buttonClass('warning', 'md', 'flex-1')">
                        {{ enviandoCotizacion ? 'Enviando...' : 'Confirmar envío' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="modalInstalacion" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl">
                <h3 class="mb-4 text-base font-semibold text-gray-900">Confirmar instalación</h3>

                <!-- Repuesto -->
                <div class="mb-4 rounded-lg bg-blue-50 border border-blue-200 p-4">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-600">Repuesto a instalar</p>
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ repuestoInstalacion?.id_cod_max }}</p>
                            <p v-if="repuestosConDescripcion[repuestoInstalacion?.id_cod_max]?.descripcion" class="text-xs text-gray-600">{{ repuestosConDescripcion[repuestoInstalacion?.id_cod_max].descripcion }}</p>
                            <p v-if="repuestosConDescripcion[repuestoInstalacion?.id_cod_max]?.codigo_proveedor" class="text-xs text-blue-600">Cod Comodidad: {{ repuestosConDescripcion[repuestoInstalacion?.id_cod_max].codigo_proveedor }}</p>
                            <p class="text-xs text-gray-600 mt-1">Cantidad: {{ repuestoInstalacion?.cantidad }}</p>
                            <p v-if="repuestoInstalacion?.observacion" class="text-xs text-gray-600">{{ repuestoInstalacion.observacion }}</p>
                            <p v-if="repuestoInstalacion?.fecha" class="text-xs text-blue-600 mt-1">📅 {{ repuestoInstalacion.fecha }}</p>
                            <span v-if="repuestoInstalacion?.estado" class="inline-flex mt-2 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="estadoRepuestoColor(repuestoInstalacion.estado)">
                                {{ repuestoInstalacion.estado }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Evidencia Final -->
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-700 mb-2">Evidencia Final</label>
                    <!-- Fotos cargadas -->
                    <div v-if="fotosDespuesInstalacion.length > 0" class="grid grid-cols-3 gap-2 mb-3">
                        <div v-for="(foto, index) in fotosDespuesInstalacion" :key="index" class="relative group">
                            <img :src="foto.url" class="w-full h-24 object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-80" @click="fotoAmpliada = foto.url" />
                            <button type="button"
                                @click="eliminarFotoDespuesInstalacion(index)"
                                :class="buttonClass('danger', 'xs', 'absolute top-1 right-1 hidden h-6 w-6 px-0 py-0 group-hover:flex shadow')">
                                ✕
                            </button>
                        </div>
                    </div>
                    <div v-else class="text-xs text-gray-500 py-1.5 text-center mb-2">
                        Sin evidencia final registrada
                    </div>
                    <!-- Subir fotos -->
                    <label for="inputFotosDespues"
                        class="flex items-center justify-center gap-2 w-full rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 py-4 cursor-pointer transition hover:bg-gray-100">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="text-sm text-gray-600">Agregar evidencia</span>
                        <input id="inputFotosDespues" type="file" accept="image/*" multiple class="hidden" @change="agregarFotosDespuesInstalacion" />
                    </label>
                </div>

                <div class="mt-5 flex gap-3">
                    <button @click="modalInstalacion = false"
                        :class="buttonClass('neutral', 'md', 'flex-1')">
                        Cancelar
                    </button>
                    <button @click="confirmarInstalacion" :disabled="formInstalacion.processing"
                        :class="buttonClass('success', 'md', 'flex-1')">
                        {{ formInstalacion.processing ? 'Confirmando...' : 'Confirmar instalación' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="modalSolucionesComplementarias" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-4 sm:items-center">
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl">
                <div class="mb-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Soluciones complementarias</p>
                    <h3 class="mt-1 text-lg font-semibold text-gray-900">
                        {{ equiposConDescripcion[equipoSeleccionado?.id_cod_max]?.descripcion || equipoSeleccionado?.id_cod_max }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-600">Selecciona las soluciones aplicadas luego de la instalación de los repuestos.</p>
                </div>

                <div v-if="tiposSolucionComplementarios.length" class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3">
                    <div class="space-y-1">
                        <label
                            v-for="solucion in tiposSolucionComplementarios"
                            :key="solucion.ID"
                            class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 transition hover:bg-emerald-100/70"
                        >
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                :checked="solucionComplementariaSeleccionada(solucion.ID)"
                                @change="toggleSolucionComplementaria(solucion.ID)"
                            />
                            <span
                                class="text-sm"
                                :class="solucionComplementariaSeleccionada(solucion.ID) ? 'font-semibold text-gray-900' : 'font-medium text-gray-700'"
                            >
                                {{ solucion.TIPO_SOLUCION }}
                            </span>
                        </label>
                    </div>

                    <p class="mt-2 text-[11px] text-gray-500">
                        {{ formInstalacion.soluciones_adicionales.length }} seleccionada<span v-if="formInstalacion.soluciones_adicionales.length !== 1">s</span>
                    </p>
                </div>

                <div v-else class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600">
                    Este equipo ya tiene todas las soluciones complementarias disponibles.
                </div>

                <div class="mt-5 flex gap-3">
                    <button
                        @click="cerrarModalSolucionesComplementarias"
                        :class="buttonClass('neutral', 'md', 'flex-1')"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="guardarSolucionesComplementarias"
                        :disabled="formInstalacion.processing || !formInstalacion.soluciones_adicionales.length"
                        :class="buttonClass('emerald', 'md', 'flex-1')"
                    >
                        {{ formInstalacion.processing ? 'Guardando...' : 'Guardar soluciones' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Visor foto ampliada -->
        <div v-if="fotoAmpliada"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
            @click="fotoAmpliada = null">
            <img :src="fotoAmpliada" class="max-w-full max-h-full rounded-xl shadow-2xl" />
        </div>
    </AppLayout>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: height 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.24s ease;
    overflow: hidden;
}
</style>
