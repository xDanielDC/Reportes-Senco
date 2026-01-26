<script setup>
import { ref, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import SearchBar from './Partials/SearchBar.vue';
import ProductTable from './Partials/ProductTable.vue';
import ProductFilters from './Partials/ProductFilters.vue';

// Props recibidos desde el controller
const props = defineProps({
    productos: Object,
    tipos: Array,
    clases: Array,
    grupos: Array,
    estadisticas: Object,
    filters: Object,
});

const productosArray = computed(() => {
    // Si viene paginado (Laravel paginator)
    if (props.productos?.data) {
        return props.productos.data;
    }

    // Si viene como array plano
    if (Array.isArray(props.productos)) {
        return props.productos;
    }

    return [];
});

// Estado local
const search = ref(props.filters.search || '');
const selectedTipo = ref(props.filters.tipo || '');
const selectedClase = ref(props.filters.clase || '');
const selectedGrupo = ref(props.filters.grupo || '');
const soloConStock = ref(props.filters.solo_con_stock || false);
const orderBy = ref(props.filters.order_by || 'Tipo');
const orderDirection = ref(props.filters.order_direction || 'asc');
const perPage = ref(props.filters.per_page || 15);
const isLoading = ref(false);

// Computed
const hasFilters = computed(() => {
    return search.value || selectedTipo.value || selectedClase.value || 
           selectedGrupo.value || soloConStock.value;
});

// Métodos
const performSearch = debounce(() => {
    isLoading.value = true;
    router.get(
        route('lista-precios.index'),
        {
            search: search.value,
            tipo: selectedTipo.value,
            clase: selectedClase.value,
            grupo: selectedGrupo.value,
            solo_con_stock: soloConStock.value,
            order_by: orderBy.value,
            order_direction: orderDirection.value,
            per_page: perPage.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            },
        }
    );
}, 500);

const clearFilters = () => {
    search.value = '';
    selectedTipo.value = '';
    selectedClase.value = '';
    selectedGrupo.value = '';
    soloConStock.value = false;
    orderBy.value = 'Tipo';
    orderDirection.value = 'asc';
    performSearch();
};

const handleSort = (field) => {
    if (orderBy.value === field) {
        orderDirection.value = orderDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        orderBy.value = field;
        orderDirection.value = 'asc';
    }
    performSearch();
};

const exportToCSV = () => {
    const params = new URLSearchParams({
        search: search.value,
        tipo: selectedTipo.value,
        clase: selectedClase.value,
        grupo: selectedGrupo.value,
        solo_con_stock: soloConStock.value,
        order_by: orderBy.value,
        order_direction: orderDirection.value,
    });
    window.location.href = route('lista-precios.export') + '?' + params.toString();
};

// Watchers
watch([selectedTipo, selectedClase, selectedGrupo, soloConStock], () => {
    performSearch();
});

watch(perPage, () => {
    performSearch();
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    
                    <div class="flex items-center space-x-4">
                        <!-- Botón Volver al Dashboard -->
                            <Link
                                :href="route('dashboard')"
                                class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                                title="Volver al inicio"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                </svg>
                                Volver
                            </Link>
                        
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900">
                                Lista de Precios
                            </h1>
                            <p class="mt-2 text-sm text-gray-600">
                                Consulta productos, precios y disponibilidad en inventario
                            </p>
                        </div>
                    </div>
                    <button
                        @click="exportToCSV"
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar CSV
                    </button>
                </div>

                <!-- Estadísticas -->
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-600">Total Productos</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ estadisticas.total_productos?.toLocaleString() }}
                        </p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-600">Con Stock</p>
                        <p class="text-2xl font-bold text-green-600">
                            {{ estadisticas.productos_con_stock?.toLocaleString() }}
                        </p>
                    </div>
                    <!-- <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-600">Tipos</p>
                        <p class="text-2xl font-bold text-blue-600">
                            {{ estadisticas.total_tipos }}
                        </p>
                    </div> -->
                    <!-- <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-600">Precio Promedio</p>
                        <p class="text-2xl font-bold text-purple-600">
                            ${{ Number(estadisticas.precio_promedio || 0).toFixed(0) }}
                        </p>
                    </div> -->
                    <!-- <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-600">Inventario Total</p>
                        <p class="text-2xl font-bold text-indigo-600">
                            {{ Math.round(Number(estadisticas.inventario_total || 0)).toLocaleString('es-CO') }}
                        </p>
                    </div> -->
                </div>
            </div>

            <!-- Filtros y Búsqueda -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6 space-y-4">
                    <SearchBar
                        v-model="search"
                        :is-loading="isLoading"
                        placeholder="Buscar por código, referencia, descripción, clase o grupo..."
                        @search="performSearch"
                        @clear="search = ''"
                    />

                    <ProductFilters
                        v-model:tipo="selectedTipo"
                        v-model:clase="selectedClase"
                        v-model:grupo="selectedGrupo"
                        v-model:solo-con-stock="soloConStock"
                        v-model:per-page="perPage"
                        :tipos="tipos"
                        :clases="clases"
                        :grupos="grupos"
                        :has-filters="hasFilters"
                        @clear="clearFilters"
                    />
                </div>
            </div>

            <!-- Tabla de Productos -->
            <ProductTable
                :productos="productosArray"
                :order-by="orderBy"
                :order-direction="orderDirection"
                :is-loading="isLoading"
                @sort="handleSort"
            />

            <!-- Estado vacío -->
            <div
                v-if="!isLoading && productosArray.length === 0"
                class="bg-white rounded-lg shadow p-12 text-center"
            >
                <svg
                    class="mx-auto h-12 w-12 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>
                <h3 class="mt-2 text-lg font-medium text-gray-900">
                    No se encontraron productos
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Intenta ajustar tus filtros de búsqueda
                </p>
                <button
                    v-if="hasFilters"
                    @click="clearFilters"
                    class="mt-4 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                >
                    Limpiar filtros
                </button>
            </div>
        </div>
        
    </div>
</template>