<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    repuestos:           { type: Array,   default: () => [] },
    estados_disponibles: { type: Array,   default: () => [] },
    transiciones_estado: { type: Object,  default: () => ({}) },
    es_asesor:           { type: Boolean, default: false },
    es_asistente:        { type: Boolean, default: false },
})

const filtroBuscarVisita = ref('')
const visitaSeleccionadaId = ref(null)
const esMovil = ref(false)
const detalleMovilActivo = ref(false)

const seleccionarVisitaDesdeQuery = () => {
    if (typeof window === 'undefined') return

    const visitaId = Number(new URLSearchParams(window.location.search).get('visita_id'))
    if (visitaId > 0) {
        visitaSeleccionadaId.value = visitaId
        if (window.innerWidth < 768) {
            detalleMovilActivo.value = true
        }
    }
}
const ESTADOS_CERRADOS = [15, 19]
const ESTADOS_ASESOR = [13, 27]
const ESTADOS_ASISTENTE = [14, 16, 17]

const modalEstado      = ref(false)
const repuestoActual   = ref(null)
const nuevoEstadoId    = ref('')
const nuevaObservacion = ref('')
const modalEstadoMasivo       = ref(false)
const nuevoEstadoMasivoId     = ref('')
const nuevaObservacionMasiva  = ref('')
const repuestosSeleccionados  = ref([])
const guardandoEstado         = ref(false)
const guardandoEstadoMasivo   = ref(false)

const normalizar = (valor) => String(valor ?? '').toLowerCase().trim()

const estadoChipLabel = (estado) => {
    return estado
}

const estadosDisponiblesMap = computed(() => {
    return props.estados_disponibles.reduce((acc, estado) => {
        acc[Number(estado.ID)] = estado
        return acc
    }, {})
})

const transicionesEstado = computed(() => {
    return Object.entries(props.transiciones_estado ?? {}).reduce((acc, [estadoOrigen, destinos]) => {
        acc[Number(estadoOrigen)] = (destinos ?? []).map((estadoId) => Number(estadoId))
        return acc
    }, {})
})

const repuestosSeleccionadosSet = computed(() => new Set(repuestosSeleccionados.value))

const repuestosDeLaVisitaSeleccionada = computed(() => {
    if (!visitaSeleccionada.value) return []
    return visitaSeleccionada.value.equipos.flatMap((equipo) => equipo.repuestos)
})

const repuestosGestionablesEnVista = computed(() => {
    return repuestosDeLaVisitaSeleccionada.value.filter((repuesto) => puedeActualizar(repuesto))
})

const primerRepuestoGestionableEnVista = computed(() => {
    return repuestosGestionablesEnVista.value[0] ?? null
})

const estadoBaseSeleccion = computed(() => {
    if (!repuestosSeleccionados.value.length) return null

    const primerSeleccionado = repuestosDeLaVisitaSeleccionada.value.find((repuesto) =>
        repuestosSeleccionadosSet.value.has(repuesto.id)
    )

    return primerSeleccionado ? Number(primerSeleccionado.estado_id) : null
})

const nombreEstadoBaseSeleccion = computed(() => {
    if (!estadoBaseSeleccion.value) return ''
    return estadosDisponiblesMap.value[estadoBaseSeleccion.value]?.ESTADO ?? `Estado ${estadoBaseSeleccion.value}`
})

const hayGestionablesEnVista = computed(() => repuestosGestionablesEnVista.value.length > 0)

const repuestosDelEstadoBase = computed(() => {
    if (!estadoBaseSeleccion.value) return []

    return repuestosGestionablesEnVista.value.filter((repuesto) => {
        return Number(repuesto.estado_id) === estadoBaseSeleccion.value
    })
})

const todosSeleccionadosEstadoBase = computed(() => {
    if (!estadoBaseSeleccion.value || !repuestosDelEstadoBase.value.length) return false

    return repuestosDelEstadoBase.value.every((repuesto) => repuestosSeleccionadosSet.value.has(repuesto.id))
})

const estadosDestinoMasivos = computed(() => {
    if (!estadoBaseSeleccion.value) return []
    return obtenerEstadosDestinoValidos(estadoBaseSeleccion.value)
})

const coincideFiltro = (visita) => {
    const terminoVisita = normalizar(filtroBuscarVisita.value)

    const porBuscarVisita = terminoVisita
        ? [
            visita.cliente,
            visita.nit,
            visita.tecnico,
            visita.direccion,
            visita.visita_id,
        ].some((campo) => normalizar(campo).includes(terminoVisita))
        : true

    return porBuscarVisita
}

const visitas = computed(() => {
    const grupos = {}

    props.repuestos.forEach(r => {
        const key = r.visita_id ?? 'sin-visita'
        if (!grupos[key]) {
            grupos[key] = {
                visita_id: r.visita_id,
                cliente:   r.cliente,
                nit:       r.nit,
                tecnico:   r.tecnico,
                direccion: r.direccion,
                repuestos: [],
            }
        }
        grupos[key].repuestos.push(r)
    })

    return Object.values(grupos)
        .map((visita) => {
            const activos = visita.repuestos.filter(r => !ESTADOS_CERRADOS.includes(Number(r.estado_id)))
            const cerrados = visita.repuestos.filter(r => ESTADOS_CERRADOS.includes(Number(r.estado_id)))

            const repuestosAsesor = visita.repuestos.filter(r => ESTADOS_ASESOR.includes(Number(r.estado_id)))
            const repuestosAsistente = visita.repuestos.filter(r => ESTADOS_ASISTENTE.includes(Number(r.estado_id)))

            return {
                ...visita,
                tienePendientes: activos.length > 0,
                cantidadCerrados: cerrados.length,
                totalRepuestos: visita.repuestos.length,
                tienePendientesAsesor: repuestosAsesor.some(r => !ESTADOS_CERRADOS.includes(Number(r.estado_id))),
                tienePendientesAsistente: repuestosAsistente.some(r => !ESTADOS_CERRADOS.includes(Number(r.estado_id))),
            }
        })
        .sort((a, b) => Number(b.visita_id ?? 0) - Number(a.visita_id ?? 0))
})

const visitasFiltradas = computed(() => {
    return visitas.value.filter(v => {
        const tienePendientesParaRol = props.es_asesor ? v.tienePendientesAsesor : v.tienePendientesAsistente
        return tienePendientesParaRol && coincideFiltro(v)
    }).map(v => ({
        ...v,
        repuestos: v.repuestos,
        equipos: agruparRepuestosPorEquipo(v.repuestos),
    }))
})

const visitaSeleccionada = computed(() => {
    if (!visitaSeleccionadaId.value) return null
    if (!visitasFiltradas.value.length) return null

    return visitasFiltradas.value.find((visita) => visita.visita_id === visitaSeleccionadaId.value) ?? null
})

const limpiarSeleccionMasiva = () => {
    repuestosSeleccionados.value = []
}

const estaSeleccionado = (repuestoId) => {
    return repuestosSeleccionadosSet.value.has(repuestoId)
}

const puedeSeleccionarse = (repuesto) => {
    if (!puedeActualizar(repuesto)) return false
    if (!estadoBaseSeleccion.value) return true
    return Number(repuesto.estado_id) === estadoBaseSeleccion.value
}

const checkboxDeshabilitado = (repuesto) => {
    return !puedeSeleccionarse(repuesto)
}

const alternarSeleccionRepuesto = (repuesto) => {
    if (!puedeSeleccionarse(repuesto)) return

    if (estaSeleccionado(repuesto.id)) {
        repuestosSeleccionados.value = repuestosSeleccionados.value.filter((id) => id !== repuesto.id)
        return
    }

    repuestosSeleccionados.value = [...repuestosSeleccionados.value, repuesto.id]
}

const alternarSeleccionTodos = () => {
    if (!hayGestionablesEnVista.value) return

    if (todosSeleccionadosEstadoBase.value) {
        limpiarSeleccionMasiva()
        return
    }

    const estadoObjetivo = estadoBaseSeleccion.value ?? Number(primerRepuestoGestionableEnVista.value?.estado_id)

    if (!estadoObjetivo) return

    repuestosSeleccionados.value = repuestosGestionablesEnVista.value
        .filter((repuesto) => Number(repuesto.estado_id) === estadoObjetivo)
        .map((repuesto) => repuesto.id)
}

const obtenerEstadosDestinoValidos = (estadoOrigenId) => {
    const estadosDestinoIds = transicionesEstado.value[Number(estadoOrigenId)] ?? []

    return estadosDestinoIds
        .map((estadoId) => estadosDisponiblesMap.value[estadoId])
        .filter(Boolean)
}

const estadosDestinoRepuestoActual = computed(() => {
    if (!repuestoActual.value) return []
    return obtenerEstadosDestinoValidos(repuestoActual.value.estado_id)
})

const mostrarErrores = (errors) => {
    const mensajes = Object.values(errors ?? {})
        .flat()
        .filter(Boolean)

    if (!mensajes.length || typeof window === 'undefined') return

    window.alert(mensajes.join('\n'))
}

const abrirModalEstado = (repuesto) => {
    repuestoActual.value   = repuesto
    nuevoEstadoId.value    = ''
    nuevaObservacion.value = repuesto.observacion ?? ''
    modalEstado.value      = true
}

const guardarEstado = () => {
    if (!nuevoEstadoId.value || guardandoEstado.value) return
    guardandoEstado.value = true

    const url = `/visitas-tecnicas/repuestos/${repuestoActual.value.id}/estado`

    router.put(url, {
        estado_id:   nuevoEstadoId.value,
        observacion: nuevaObservacion.value,
    }, {
        onError: mostrarErrores,
        onSuccess: () => {
            modalEstado.value = false
            limpiarSeleccionMasiva()
        },
        onFinish: () => {
            guardandoEstado.value = false
        },
    })
}

const abrirModalEstadoMasivo = () => {
    if (!repuestosSeleccionados.value.length) return

    modalEstadoMasivo.value = true
    nuevoEstadoMasivoId.value = ''
    nuevaObservacionMasiva.value = ''
}

const guardarEstadoMasivo = () => {
    if (!nuevoEstadoMasivoId.value || !repuestosSeleccionados.value.length || guardandoEstadoMasivo.value) return
    guardandoEstadoMasivo.value = true

    router.put('/visitas-tecnicas/repuestos/estado/masivo', {
        repuesto_ids: repuestosSeleccionados.value,
        estado_id: nuevoEstadoMasivoId.value,
        observacion: nuevaObservacionMasiva.value,
    }, {
        onError: mostrarErrores,
        onSuccess: () => {
            modalEstadoMasivo.value = false
            limpiarSeleccionMasiva()
        },
        onFinish: () => {
            guardandoEstadoMasivo.value = false
        },
    })
}

const puedeActualizar = (repuesto) => {
    const estadoId = Number(repuesto.estado_id)
    if (props.es_asesor && [13, 27].includes(estadoId)) return true
    if (props.es_asistente && [14, 16, 17].includes(estadoId)) return true
    return false
}

const estadoGeneralVisita = (visita) => {
    const tienePendientesParaRol = props.es_asesor ? visita.tienePendientesAsesor : visita.tienePendientesAsistente
    if (!tienePendientesParaRol) {
        return 'Visita cerrada'
    }

    return 'Repuestos pendientes'
}

const colorEstadoGeneralVisita = (visita) => {
    const tienePendientesParaRol = props.es_asesor ? visita.tienePendientesAsesor : visita.tienePendientesAsistente
    return tienePendientesParaRol
        ? 'border-amber-200 bg-amber-50 text-amber-800'
        : 'border-lime-200 bg-lime-50 text-lime-800'
}

const colorChipVisita = () => {
    return 'border-stone-200 bg-stone-100 text-stone-700'
}

const estadoChipClasses = {
    13: 'border-amber-200 bg-amber-50 text-amber-800',
    14: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    15: 'border-rose-200 bg-rose-50 text-rose-800',
    16: 'border-violet-200 bg-violet-50 text-violet-800',
    17: 'border-stone-200 bg-stone-100 text-stone-700',
    18: 'border-orange-200 bg-orange-50 text-orange-800',
    19: 'border-lime-200 bg-lime-50 text-lime-800',
    27: 'border-sky-200 bg-sky-50 text-sky-800',
}

const colorResumenEstado = (estadoId) => {
    return estadoChipClasses[Number(estadoId)] ?? 'border-stone-200 bg-stone-50 text-stone-700'
}

const colorSeccionEquipo = (equipo) => {
    const tienePendientes = equipo.repuestos.some(r => !ESTADOS_CERRADOS.includes(Number(r.estado_id)))

    return tienePendientes
        ? 'border-stone-200'
        : 'border-lime-200'
}

function agruparRepuestosPorEquipo(repuestos) {
    const grupos = {}

    repuestos.forEach((repuesto) => {
        const codigoEquipo = repuesto.equipo ?? 'Sin equipo'
        const nombreEquipo = repuesto.nombre_equipo || codigoEquipo || 'Equipo sin descripción'
        const key = `${codigoEquipo}::${nombreEquipo}`

        if (!grupos[key]) {
            grupos[key] = {
                key,
                codigo: codigoEquipo,
                nombre: nombreEquipo,
                repuestos: [],
            }
        }

        grupos[key].repuestos.push(repuesto)
    })

    return Object.values(grupos)
}

const tonoEstadoFila = (repuesto) => {
    const estadoId = Number(repuesto.estado_id)
    const chip = colorResumenEstado(estadoId)
    const isClosed = ESTADOS_CERRADOS.includes(estadoId)

    return {
        borde: isClosed ? 'border-l-[#639922]' : 'border-l-[#C8102E]',
        texto: isClosed ? 'text-[#639922]' : 'text-amber-800',
        punto: isClosed ? 'bg-[#639922]' : 'bg-amber-500',
        chip,
    }
}

const esGestionado = (repuesto) => ESTADOS_CERRADOS.includes(Number(repuesto.estado_id))

const seleccionarVisita = (visitaId) => {
    limpiarSeleccionMasiva()
    visitaSeleccionadaId.value = visitaId

    if (esMovil.value) {
        detalleMovilActivo.value = true
    }
}

const volverListadoMovil = () => {
    limpiarSeleccionMasiva()
    detalleMovilActivo.value = false
}

const actualizarPantalla = () => {
    if (typeof window === 'undefined') return
    esMovil.value = window.innerWidth < 768
    if (!esMovil.value) {
        detalleMovilActivo.value = false
    }
}

onMounted(() => {
    actualizarPantalla()
    seleccionarVisitaDesdeQuery()
    window.addEventListener('resize', actualizarPantalla)
})

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('resize', actualizarPantalla)
    }
})
</script>

<template>
    <AppLayout title="Gestión de Repuestos">
        <Head title="Gestión de Repuestos" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-stone-900">
                {{ es_asesor ? 'Repuestos — Cotización' : 'Repuestos — Facturación' }}
            </h2>
        </template>

        <div class="bg-stone-100 px-3 py-4 sm:px-4 sm:py-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="rounded-xl border border-stone-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
                        <input
                            v-model="filtroBuscarVisita"
                            type="text"
                            placeholder="Buscar cliente o visita..."
                            class="min-h-11 flex-1 rounded-2xl border border-stone-300 bg-stone-50 px-4 text-sm text-stone-800 placeholder:text-stone-400 focus:border-[#C8102E] focus:bg-white focus:ring-2 focus:ring-red-100"
                        />
                    </div>
                </div>

                <div
                    class="mt-3 overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm"
                    :class="esMovil ? 'overflow-visible' : ''"
                    :style="{ height: esMovil ? 'auto' : 'calc(100vh - 14.5rem)' }"
                >
                    <div v-if="visitasFiltradas.length === 0" :class="esMovil ? 'flex items-center justify-center p-8 text-center' : 'flex h-full items-center justify-center p-8 text-center'">
                        <p class="text-sm text-stone-500">No hay repuestos gestionar</p>
                    </div>

                    <template v-else>
                        <div v-if="esMovil">
                            <div v-if="!detalleMovilActivo" class="bg-stone-50 p-2.5">
                                <button
                                    v-for="visita in visitasFiltradas"
                                    :key="visita.visita_id"
                                    type="button"
                                    @click="seleccionarVisita(visita.visita_id)"
                                    class="mb-2.5 block w-full rounded-xl border border-stone-200 bg-white p-4 text-left shadow-sm last:mb-0"
                                    :class="visitaSeleccionada?.visita_id === visita.visita_id ? 'border-l-[3px] border-l-[#C8102E] bg-red-50/30' : ''"
                                >
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="min-w-0 flex-1 truncate text-sm font-bold text-stone-900">{{ visita.cliente ?? '—' }}</p>
                                        <span class="rounded-full border px-2 py-0.5 text-xs font-semibold" :class="colorEstadoGeneralVisita(visita)">{{ estadoGeneralVisita(visita) }}</span>
                                    </div>
                                    <div class="mt-1.5 space-y-0.5 text-xs text-stone-500">
                                        <p class="truncate"><span class="font-semibold text-stone-600">NIT:</span> {{ visita.nit ?? '—' }}</p>
                                        <p class="truncate"><span class="font-semibold text-stone-600">Técnico:</span> {{ visita.tecnico ?? '—' }}</p>
                                        <p class="truncate"><span class="font-semibold text-stone-600">Dirección:</span> {{ visita.direccion ?? '—' }}</p>
                                    </div>
                                    <div class="mt-2.5">
                                        <span class="rounded-full border px-2 py-0.5 text-xs font-semibold" :class="colorChipVisita()">Visita #{{ visita.visita_id }}</span>
                                    </div>
                                </button>
                            </div>

                            <div v-else class="flex flex-col">
                                <div class="border-b border-stone-200 bg-white px-4 py-3">
                                    <button
                                        type="button"
                                        @click="volverListadoMovil"
                                        class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-stone-300 px-3 text-sm font-semibold text-stone-700"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                        Volver
                                    </button>
                                </div>

                                <div v-if="visitaSeleccionada" class="bg-stone-50">
                                    <div class="border-b border-stone-200 bg-white px-4 py-3.5">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-bold text-stone-900">{{ visitaSeleccionada.cliente ?? '—' }}</h3>
                                            <span class="rounded-full border px-2 py-0.5 text-xs font-semibold" :class="colorChipVisita()">Visita #{{ visitaSeleccionada.visita_id }}</span>
                                        </div>
                                        <div class="mt-1.5 space-y-0.5 text-xs text-stone-500">
                                            <p><span class="font-semibold text-stone-600">NIT:</span> {{ visitaSeleccionada.nit ?? '—' }}</p>
                                            <p><span class="font-semibold text-stone-600">Técnico:</span> {{ visitaSeleccionada.tecnico ?? '—' }}</p>
                                            <p><span class="font-semibold text-stone-600">Dirección:</span> {{ visitaSeleccionada.direccion ?? '—' }}</p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="repuestosSeleccionados.length || hayGestionablesEnVista"
                                        class="sticky top-0 z-10 border-b border-stone-200 bg-white px-4 py-3 shadow-sm"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-sm font-semibold text-stone-900">
                                                {{ repuestosSeleccionados.length }} seleccionados
                                            </span>
                                            <button
                                                type="button"
                                                @click="abrirModalEstadoMasivo"
                                                :disabled="!repuestosSeleccionados.length"
                                                class="min-h-11 rounded-xl bg-[#639922] px-4 text-sm font-semibold text-white transition hover:bg-[#4f7a1d] disabled:cursor-not-allowed disabled:opacity-50"
                                            >
                                                Gestión masiva
                                            </button>
                                        </div>
                                    </div>

                                    <div class="space-y-3 p-2.5">
                                        <section
                                            v-for="(equipo, equipoIndex) in visitaSeleccionada.equipos"
                                            :key="equipo.key"
                                            class="overflow-hidden rounded-xl border bg-white shadow-sm"
                                            :class="colorSeccionEquipo(equipo)"
                                        >
                                            <div class="border-b border-stone-200 bg-stone-50 px-4 py-2.5">
                                                <div class="min-w-0">
                                                    <p class="text-xs font-medium uppercase tracking-[0.16em] text-stone-500">{{ equipo.codigo }}</p>
                                                    <div class="mt-1 flex flex-wrap items-center gap-2">
                                                        <h4 class="text-sm font-medium text-stone-900">{{ equipo.nombre || 'Equipo sin descripción' }}</h4>
                                                        <span class="rounded-full border border-stone-200 bg-stone-100 px-2 py-0.5 text-xs font-semibold text-stone-700">
                                                            {{ equipo.repuestos.length }} {{ equipo.repuestos.length === 1 ? 'repuesto' : 'repuestos' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="space-y-2.5 p-2.5">
                                                <div
                                                    v-if="equipoIndex === 0 && (repuestosSeleccionados.length || hayGestionablesEnVista)"
                                                    class="flex items-center gap-2 rounded-lg border border-stone-200 bg-stone-50 px-3.5 py-3"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        :checked="todosSeleccionadosEstadoBase"
                                                        :disabled="!hayGestionablesEnVista"
                                                        class="h-4 w-4 rounded border-stone-300 text-[#639922] focus:ring-[#639922] disabled:cursor-not-allowed"
                                                        @change="alternarSeleccionTodos"
                                                    />
                                                    <span class="text-sm text-stone-700">Seleccionar todos los repuestos</span>
                                                </div>

                                                <article
                                                    v-for="r in equipo.repuestos"
                                                    :key="r.id"
                                                    class="rounded-lg border border-stone-200 bg-white p-3.5 transition"
                                                    :class="checkboxDeshabilitado(r) ? 'opacity-60' : ''"
                                                >
                                                    <div class="flex items-start gap-3">
                                                        <input
                                                            type="checkbox"
                                                            :checked="estaSeleccionado(r.id)"
                                                            :disabled="checkboxDeshabilitado(r)"
                                                            class="mt-1 h-4 w-4 rounded border-stone-300 text-[#639922] focus:ring-[#639922] disabled:cursor-not-allowed"
                                                            @change="alternarSeleccionRepuesto(r)"
                                                        />

                                                        <div class="min-w-0 flex-1">
                                                            <div class="flex items-center gap-2">
                                                                <p class="text-sm font-semibold text-stone-900">{{ r.nombre_repuesto ?? '—' }}</p>
                                                                <span v-if="r.es_urgente" class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700">URGENTE</span>
                                                            </div>
                                                            <div class="mt-1.5 space-y-0.5 text-xs text-stone-500">
                                                                <p><span class="font-semibold text-stone-600">Codigo Max:</span> {{ r.codigo ?? '—' }}</p>
                                                                <p><span class="font-semibold text-stone-600">Codigo Comodidad:</span> {{ r.proveedor ?? '—' }}</p>
                                                                <p><span class="font-semibold text-stone-600">Cantidad:</span> {{ r.cantidad }}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2.5 flex items-center justify-between gap-3">
                                                        <span class="rounded-full border px-2.5 py-1 text-xs font-semibold" :class="tonoEstadoFila(r).chip">
                                                            {{ estadoChipLabel(r.estado) }}
                                                        </span>
                                                        <button
                                                            v-if="puedeActualizar(r)"
                                                            type="button"
                                                            @click="abrirModalEstado(r)"
                                                            :class="esGestionado(r) ? 'bg-[#639922] hover:bg-[#4f7a1d]' : 'bg-[#C8102E] hover:bg-[#a50d26]'"
                                                            class="min-h-11 rounded-xl px-4 text-xs font-semibold text-white transition"
                                                        >
                                                            {{ esGestionado(r) ? 'Listo' : 'Gestionar' }}
                                                        </button>
                                                        <span
                                                            v-else
                                                            class="text-xs font-semibold text-stone-500"
                                                        >
                                                            --
                                                        </span>
                                                    </div>
                                                </article>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="grid h-full min-h-0 grid-cols-[260px_minmax(0,1fr)]">
                            <aside class="min-h-0 border-r border-stone-200 bg-stone-50">
                                <div class="h-full min-h-0 overflow-y-auto p-2.5">
                                    <button
                                        v-for="visita in visitasFiltradas"
                                        :key="visita.visita_id"
                                        type="button"
                                        @click="seleccionarVisita(visita.visita_id)"
                                        class="mb-2.5 block w-full rounded-xl border border-stone-200 bg-white p-4 text-left shadow-sm last:mb-0"
                                        :class="visitaSeleccionada?.visita_id === visita.visita_id ? 'border-l-[3px] border-l-[#C8102E] bg-red-50/30' : ''"
                                    >
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="min-w-0 flex-1 truncate text-sm font-bold text-stone-900">{{ visita.cliente ?? '—' }}</p>
                                        </div>
                                        <div class="mt-1.5 space-y-0.5 text-xs text-stone-500">
                                            <p class="truncate"><span class="font-semibold text-stone-600">NIT:</span> {{ visita.nit ?? '—' }}</p>
                                            <p class="truncate"><span class="font-semibold text-stone-600">Técnico:</span> {{ visita.tecnico ?? '—' }}</p>
                                            <p class="truncate"><span class="font-semibold text-stone-600">Dirección:</span> {{ visita.direccion ?? '—' }}</p>
                                        </div>
                                        <div class="mt-2.5">
                                            <span class="rounded-full border px-2 py-0.5 text-xs font-semibold" :class="colorEstadoGeneralVisita(visita)">{{ estadoGeneralVisita(visita) }}</span>
                                        </div>
                                        <div class="mt-2.5">
                                            <span class="rounded-full border px-2 py-0.5 text-xs font-semibold" :class="colorChipVisita()">Visita #{{ visita.visita_id }}</span>
                                        </div>
                                    </button>
                                </div>
                            </aside>

                            <section class="h-full min-h-0 bg-white">
                                <div v-if="visitaSeleccionada" class="flex h-full min-h-0 flex-col">
                                    <div class="border-b border-stone-200 px-5 py-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-xl font-bold text-stone-900">{{ visitaSeleccionada.cliente ?? '—' }}</h3>
                                        </div>
                                        <div class="mt-2.5">
                                            <span class="rounded-full border px-2.5 py-1 text-xs font-semibold" :class="colorChipVisita()">
                                                Visita #{{ visitaSeleccionada.visita_id }}
                                            </span>
                                        </div>
                                        <div class="mt-1.5 grid gap-0.5 text-xs text-stone-500 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                                            <p><span class="font-semibold text-stone-600">NIT:</span> {{ visitaSeleccionada.nit ?? '—' }}</p>
                                            <p><span class="font-semibold text-stone-600">Técnico:</span> {{ visitaSeleccionada.tecnico ?? '—' }}</p>
                                            <p class="lg:col-span-2"><span class="font-semibold text-stone-600">Dirección:</span> {{ visitaSeleccionada.direccion ?? '—' }}</p>
                                        </div>
                                    </div>

                                    <div class="min-h-0 flex-1 overflow-y-auto bg-stone-50 p-4">
                                        <section
                                            v-for="(equipo, equipoIndex) in visitaSeleccionada.equipos"
                                            :key="equipo.key"
                                            class="mb-4 overflow-hidden rounded-xl border bg-white shadow-sm last:mb-0"
                                            :class="colorSeccionEquipo(equipo)"
                                        >
                                            <div class="border-b border-stone-200 bg-stone-50 px-4 py-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="text-xs font-medium uppercase tracking-[0.16em] text-stone-500">{{ equipo.codigo }}</p>
                                                    <h4 class="text-sm font-medium text-stone-900">{{ equipo.nombre || 'Equipo sin descripción' }}</h4>
                                                    <span class="rounded-full border border-stone-200 bg-stone-100 px-2 py-0.5 text-xs font-semibold text-stone-700">
                                                        {{ equipo.repuestos.length }} {{ equipo.repuestos.length === 1 ? 'repuesto' : 'repuestos' }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="min-w-full text-left">
                                                    <thead class="bg-white">
                                                        <tr
                                                            v-if="equipoIndex === 0 && (repuestosSeleccionados.length || hayGestionablesEnVista)"
                                                            class="border-b border-stone-200"
                                                        >
                                                            <th colspan="6" class="px-2 py-2 text-left align-middle">
                                                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                                                    <span class="font-semibold text-stone-900">
                                                                        {{ repuestosSeleccionados.length }} repuestos seleccionados
                                                                    </span>
                                                                </div>
                                                            </th>
                                                            <th class="w-[140px] px-2 py-2 text-right align-middle">
                                                                <button
                                                                    type="button"
                                                                    @click="abrirModalEstadoMasivo"
                                                                    :disabled="!repuestosSeleccionados.length"
                                                                    class="inline-flex min-h-8 items-center rounded-lg bg-[#639922] px-3 text-xs font-semibold text-white transition hover:bg-[#4f7a1d] disabled:cursor-not-allowed disabled:opacity-50"
                                                                >
                                                                    Gestión masiva
                                                                </button>
                                                            </th>
                                                        </tr>
                                                        <tr class="border-b border-stone-200 text-xs uppercase tracking-[0.12em] text-stone-500">
                                                            <th class="w-[48px] px-2 py-1.5 font-semibold text-center">
                                                                <input
                                                                    type="checkbox"
                                                                    :checked="todosSeleccionadosEstadoBase"
                                                                    :disabled="!hayGestionablesEnVista"
                                                                    class="h-4 w-4 rounded border-stone-300 text-[#639922] focus:ring-[#639922] disabled:cursor-not-allowed"
                                                                    @change="alternarSeleccionTodos"
                                                                />
                                                            </th>
                                                            <th class="px-2 py-1.5 font-semibold">Repuesto</th>
                                                            <th class="px-2 py-1.5 w-[120px] font-semibold">Codigo Max</th>
                                                            <th class="px-2 py-1.5 w-[120px] font-semibold">Codigo Comodidad</th>
                                                            <th class="px-2 py-1.5 w-[120px] font-semibold">Cantidad</th>
                                                            <th class="px-2 py-1.5 font-semibold">Estado</th>
                                                            <th class="px-2 py-1.5 w-[120px] font-semibold text-right">Acción</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr
                                                            v-for="r in equipo.repuestos"
                                                            :key="r.id"
                                                            class="border-b border-stone-100 last:border-b-0"
                                                            :class="checkboxDeshabilitado(r) ? 'opacity-60' : ''"
                                                        >
                                                            <td class="px-2 py-2 text-center">
                                                                <input
                                                                    type="checkbox"
                                                                    :checked="estaSeleccionado(r.id)"
                                                                    :disabled="checkboxDeshabilitado(r)"
                                                                    class="h-4 w-4 rounded border-stone-300 text-[#639922] focus:ring-[#639922] disabled:cursor-not-allowed"
                                                                    @change="alternarSeleccionRepuesto(r)"
                                                                />
                                                            </td>
                                                             <td class="px-2 py-2 text-sm font-medium text-stone-900">
                                                                 <div class="flex items-center gap-2">
                                                                     <span>{{ r.nombre_repuesto ?? '—' }}</span>
                                                                     <span v-if="r.es_urgente" class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700">URGENTE</span>
                                                                 </div>
                                                             </td>
                                                            <td class="px-2 py-2 text-xs text-stone-700">{{ r.codigo ?? '—' }}</td>
                                                            <td class="px-2 py-2 text-xs text-stone-700">{{ r.proveedor ?? '—' }}</td>
                                                            <td class="w-[70px] px-2 py-2 text-center text-sm font-medium text-stone-900">{{ r.cantidad }}</td>
                                                            <td class="px-2 py-2">
                                                                <span class="rounded-full border px-2 py-0.5 text-xs font-semibold" :class="tonoEstadoFila(r).chip">
                                                                    {{ estadoChipLabel(r.estado) }}
                                                                </span>
                                                            </td>
                                                            <td class="px-2 py-2 text-right w-[120px] align-middle">
                                                                <div class="flex h-full items-center justify-end">
                                                                    <button
                                                                        v-if="puedeActualizar(r)"
                                                                        type="button"
                                                                        @click="abrirModalEstado(r)"
                                                                        :class="esGestionado(r) ? 'bg-[#639922] hover:bg-[#4f7a1d]' : 'bg-[#C8102E] hover:bg-[#a50d26]'"
                                                                        class="inline-flex h-8 items-center rounded-lg px-3 text-xs font-semibold text-white transition"
                                                                    >
                                                                        {{ esGestionado(r) ? 'Listo' : 'Gestionar' }}
                                                                    </button>
                                                                    <span
                                                                        v-else
                                                                        class="inline-flex h-8 items-center rounded-lg px-3 text-xs font-semibold text-stone-500"
                                                                    >
                                                                        --
                                                                    </span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                                <div v-else class="flex h-full items-center justify-center p-6 text-sm text-stone-500">
                                    Seleccione una visita para ver sus repuestos.
                                </div>
                            </section>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div v-if="modalEstado" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4">
            <div class="w-full rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-3xl sm:p-6 animate-in slide-in-from-bottom-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-stone-900 sm:text-xl">Actualizar estado</h3>
                    <button @click="modalEstado = false" class="min-h-11 min-w-11 text-2xl text-stone-400 hover:text-stone-600">×</button>
                </div>

                <div class="mb-4 space-y-1 rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm">
                    <p><span class="font-semibold text-stone-600">Código:</span> <span class="font-semibold text-stone-900">{{ repuestoActual?.codigo }}</span></p>
                    <p><span class="font-semibold text-stone-600">Repuesto:</span> <span class="text-stone-900">{{ repuestoActual?.nombre_repuesto ?? '—' }}</span></p>
                    <p><span class="font-semibold text-stone-600">Cliente:</span> <span class="text-stone-900">{{ repuestoActual?.cliente ?? '—' }}</span></p>
                </div>

                <div class="mb-5 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-stone-700">
                            Nuevo estado <span class="text-red-500">*</span>
                        </label>
                        <select v-model="nuevoEstadoId"
                            class="block min-h-11 w-full rounded-xl border border-stone-300 bg-stone-50 px-3 py-3 text-base text-stone-700 focus:border-stone-500 focus:bg-white focus:ring-2 focus:ring-stone-200">
                            <option value="" disabled>Seleccione estado</option>
                            <option v-for="e in estadosDestinoRepuestoActual" :key="e.ID" :value="e.ID">
                                {{ e.ESTADO }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-stone-700">Observación (opcional)</label>
                        <textarea v-model="nuevaObservacion" rows="3"
                            class="block w-full rounded-xl border border-stone-300 bg-stone-50 px-3 py-2 text-base text-stone-700 focus:border-stone-500 focus:bg-white focus:ring-2 focus:ring-stone-200"
                            placeholder="Agregue una observación..." />
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="modalEstado = false"
                        class="min-h-11 flex-1 rounded-xl border border-stone-300 px-4 py-3 text-base font-semibold text-stone-700 transition hover:bg-stone-50 active:bg-stone-100">
                        Cancelar
                    </button>
                    <button @click="guardarEstado" :disabled="!nuevoEstadoId || guardandoEstado"
                        class="min-h-11 flex-1 rounded-xl bg-[#639922] px-4 py-3 text-base font-semibold text-white transition hover:bg-[#4f7a1d] active:bg-[#3d6115] disabled:cursor-not-allowed disabled:opacity-50">
                        <span v-if="guardandoEstado" class="inline-flex items-center justify-center gap-2">
                            <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Procesando...
                        </span>
                        <span v-else>Guardar</span>
                    </button>
                </div>
            </div>
        </div>

        <div v-if="modalEstadoMasivo" class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-4">
            <div class="w-full rounded-t-3xl bg-white p-5 shadow-2xl sm:max-w-md sm:rounded-3xl sm:p-6 animate-in slide-in-from-bottom-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-stone-900 sm:text-xl">Gestión masiva de repuestos</h3>
                    <button @click="modalEstadoMasivo = false" class="min-h-11 min-w-11 text-2xl text-stone-400 hover:text-stone-600">×</button>
                </div>

                <div class="mb-4 space-y-2 rounded-2xl border border-stone-200 bg-stone-50 p-4 text-sm">
                    <p><span class="font-semibold text-stone-600">Estado actual:</span> <span class="font-semibold text-stone-900">{{ nombreEstadoBaseSeleccion }}</span></p>
                    <p><span class="font-semibold text-stone-600">Repuestos afectados:</span> <span class="font-semibold text-stone-900">{{ repuestosSeleccionados.length }}</span></p>
                </div>

                <div class="mb-5 space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-stone-700">
                            Nuevo estado <span class="text-red-500">*</span>
                        </label>
                        <select v-model="nuevoEstadoMasivoId"
                            class="block min-h-11 w-full rounded-xl border border-stone-300 bg-stone-50 px-3 py-3 text-base text-stone-700 focus:border-stone-500 focus:bg-white focus:ring-2 focus:ring-stone-200">
                            <option value="" disabled>Seleccione estado</option>
                            <option v-for="e in estadosDestinoMasivos" :key="e.ID" :value="e.ID">
                                {{ e.ESTADO }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-stone-700">Observación (opcional)</label>
                        <textarea v-model="nuevaObservacionMasiva" rows="3"
                            class="block w-full rounded-xl border border-stone-300 bg-stone-50 px-3 py-2 text-base text-stone-700 focus:border-stone-500 focus:bg-white focus:ring-2 focus:ring-stone-200"
                            placeholder="Agregue una observación para todos los repuestos seleccionados..." />
                    </div>
                </div>

                <div class="flex gap-3">
                    <button @click="modalEstadoMasivo = false"
                        class="min-h-11 flex-1 rounded-xl border border-stone-300 px-4 py-3 text-base font-semibold text-stone-700 transition hover:bg-stone-50 active:bg-stone-100">
                        Cancelar
                    </button>
                    <button @click="guardarEstadoMasivo" :disabled="!nuevoEstadoMasivoId || !repuestosSeleccionados.length || guardandoEstadoMasivo"
                        class="min-h-11 flex-1 rounded-xl bg-[#639922] px-4 py-3 text-base font-semibold text-white transition hover:bg-[#4f7a1d] active:bg-[#3d6115] disabled:cursor-not-allowed disabled:opacity-50">
                        <span v-if="guardandoEstadoMasivo" class="inline-flex items-center justify-center gap-2">
                            <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Procesando...
                        </span>
                        <span v-else>Guardar</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
