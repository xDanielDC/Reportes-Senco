<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    productos: Object,
    orderBy: String,
    orderDirection: String,
    isLoading: Boolean,
});

import { computed } from 'vue';

const productosArray = computed(() => {
    if (props.productos?.data) {
        return props.productos.data;
    }

    if (Array.isArray(props.productos)) {
        return props.productos;
    }

    return [];
});

const emit = defineEmits(['sort']);

const getSortIcon = (field) => {
    if (props.orderBy !== field) {
        return 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4';
    }
    return props.orderDirection === 'asc'
        ? 'M5 15l7-7 7 7'
        : 'M19 9l-7 7-7-7';
};

const formatPrice = (price) => {
    if (!price) return '$0';
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
};

const getStockBadgeClass = (stock) => {
    if (!stock || stock === 0) {
        return 'bg-red-100 text-red-800';
    } else if (stock < 10) {
        return 'bg-yellow-100 text-yellow-800';
    } else if (stock < 50) {
        return 'bg-blue-100 text-blue-800';
    }
    return 'bg-green-100 text-green-800';
};

const getStockLabel = (stock) => {
    if (!stock || stock === 0) return 'Sin stock';
    if (stock < 10) return 'Stock bajo';
    if (stock < 50) return 'Stock medio';
    return 'Stock alto';
};
</script>

<template>
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <!-- Tabla -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <!-- <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                            @click="$emit('sort', 'Tipo')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>Tipo</span>
                                <svg
                                    class="w-4 h-4"
                                    :class="{
                                        'text-blue-600': orderBy === 'Tipo',
                                        'text-gray-400': orderBy !== 'Tipo',
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="getSortIcon('Tipo')"
                                    />
                                </svg>
                            </div>
                        </th> -->
                        <!-- <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                            @click="$emit('sort', 'Clase')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>Clase</span>
                                <svg
                                    class="w-4 h-4"
                                    :class="{
                                        'text-blue-600': orderBy === 'Clase',
                                        'text-gray-400': orderBy !== 'Clase',
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="getSortIcon('Clase')"
                                    />
                                </svg>
                            </div>
                        </th> -->
                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                            @click="$emit('sort', 'Cod Max')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>Código</span>
                                <svg
                                    class="w-4 h-4"
                                    :class="{
                                        'text-blue-600': orderBy === 'Cod Max',
                                        'text-gray-400': orderBy !== 'Cod Max',
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="getSortIcon('Cod Max')"
                                    />
                                </svg>
                            </div>
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                            @click="$emit('sort', 'Referencia')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>Referencia</span>
                                <svg
                                    class="w-4 h-4"
                                    :class="{
                                        'text-blue-600': orderBy === 'Referencia',
                                        'text-gray-400': orderBy !== 'Referencia',
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="getSortIcon('Referencia')"
                                    />
                                </svg>
                            </div>
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            Descripción
                        </th>

                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            ML/Caja
                        </th>

                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                            CJ/CRTN
                        </th>

                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                            @click="$emit('sort', 'Precio')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>Precio</span>
                                <svg
                                    class="w-4 h-4"
                                    :class="{
                                        'text-blue-600': orderBy === 'Precio',
                                        'text-gray-400': orderBy !== 'Precio',
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="getSortIcon('Precio')"
                                    />
                                </svg>
                            </div>
                        </th>
                        <th
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors"
                            @click="$emit('sort', 'Inventario')"
                        >
                            <div class="flex items-center space-x-1">
                                <span>Stock</span>
                                <svg
                                    class="w-4 h-4"
                                    :class="{
                                        'text-blue-600': orderBy === 'Inventario',
                                        'text-gray-400': orderBy !== 'Inventario',
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        :d="getSortIcon('Inventario')"
                                    />
                                </svg>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Loading State -->
                    <tr v-if="isLoading">
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex justify-center items-center space-x-2">
                                <svg
                                    class="animate-spin h-8 w-8 text-blue-600"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                <span class="text-gray-600">Cargando productos...</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Productos -->
                    <tr
                        v-for="producto in productosArray"
                        :key="producto['Cod Max']"
                        class="hover:bg-gray-50 transition-colors"
                    >
                        <!-- <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ producto.Tipo }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            {{ producto.Clase }}
                        </td> -->
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                            <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                {{ producto['Cod Max'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            {{ producto.Referencia }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <div class="max-w-xs truncate" :title="producto.Descripcion">
                                {{ producto.Descripcion }}
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                            <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                {{ Number(producto['ML/Caja']).toFixed(1) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                            <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                {{ Number(producto['CJ/CRTN']).toFixed(1) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                            {{ formatPrice(producto.Precio) }}
                            <div v-if="producto.Minimo" class="text-xs text-gray-500 font-normal">
                                Min: {{ formatPrice(producto.Minimo) }}
                            </div>
                            <div v-if="producto['30CJ']" class="text-xs text-gray-500 font-normal">
                                30CJ: {{ formatPrice(producto['30CJ']) }}
                            </div>
                        
                            <div v-if="producto['60CJ']" class="text-xs text-gray-500 font-normal">
                                60CJ: {{ formatPrice(producto['60CJ']) }}
                            </div>
                        
                            <div v-if="producto['100CJ']" class="text-xs text-gray-500 font-normal">
                                100CJ: {{ formatPrice(producto['100CJ']) }}
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="flex flex-col space-y-1">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="getStockBadgeClass(producto.Inventario)"
                                >
                                    {{ producto.Inventario || 0 }} unidades
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ getStockLabel(producto.Inventario) }}
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div
            v-if="productosArray.length > 0"
            class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6"
        >
            <div class="flex-1 flex justify-between sm:hidden">
                <Link
                    v-if="productos.prev_page_url"
                    :href="productos.prev_page_url"
                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    preserve-state
                    preserve-scroll
                >
                    Anterior
                </Link>
                <Link
                    v-if="productos.next_page_url"
                    :href="productos.next_page_url"
                    class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                    preserve-state
                    preserve-scroll
                >
                    Siguiente
                </Link>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm text-gray-700">
                        Mostrando
                        <span class="font-medium">{{ productos.from }}</span>
                        a
                        <span class="font-medium">{{ productos.to }}</span>
                        de
                        <span class="font-medium">{{ productos.total }}</span>
                        resultados
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <Link
                            v-for="link in productos.links"
                            :key="link.label"
                            :href="link.url"
                            :class="[
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                link.active
                                    ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                !link.url ? 'cursor-not-allowed opacity-50' : '',
                            ]"
                            preserve-state
                            preserve-scroll
                        >
                            <span v-html="link.label"></span>
                        </Link>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</template>