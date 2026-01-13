<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
</script>

<template>
    <div>
        <div class="p-6 lg:p-8 bg-white border-b border-gray-200">
            <ApplicationLogo class="block h-12 w-auto"/>

            <h1 class="mt-8 text-2xl font-medium text-gray-900">
                ¡Bienvenido {{ $page.props.auth.user.name }}!
            </h1>

            <p class="mt-6 text-gray-500 leading-relaxed">
                Aplicación para gestion y visualización de reportes de Microsoft PowerBI. En el menu superior de
                reportes puede acceder a la lista de reportes a los que tiene acceso si no puede ver el menu o no
                encuentra ningún reporte en la lista, comuníquese con un administrador.
            </p>
        </div>
    </div>
</template>
