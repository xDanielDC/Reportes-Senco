<script setup>
import {computed} from 'vue'
import {Head, Link, router} from "@inertiajs/vue3";

const props = defineProps({status: Number})

const title = computed(() => {
    return {
        503: '503: Servicio en mantenimiento',
        500: '500: Server Error',
        404: '404: Página no encontrada',
        403: '403: Acceso restringido',
    }[props.status]
})

const description = computed(() => {
    return {
        503: 'Lo sentimos, estamos realizando tareas de mantenimiento. Por favor, vuelva mas tarde.',
        500: 'Whoops, algo salió mal en nuestros servidores..',
        404: 'Lo sentimos, no se pudo encontrar la página que estás buscando.',
        403: 'Lo sentimos, tienes prohibido acceder a esta página.',
    }[props.status]
})

const goToHome = () => {
    router.visit(route('dashboard'))
}
</script>

<template>
    <div>
        <Head :title="title"/>

            <!-- component -->
            <div class="lg:px-24 lg:py-24 md:py-20 md:px-44 px-4 py-24 items-center flex justify-center flex-col-reverse lg:flex-row md:gap-28 gap-16 h-screen">
                <div class="xl:pt-24 w-full xl:w-1/2 relative pb-12 lg:pb-0">
                    <div>
                        <h1 class="my-2 text-gray-800 font-bold text-3xl">
                            {{ title }}
                        </h1>
                        <h1 class="my-2 text-gray-800 font-bold text-2xl">
                            {{ description }}
                        </h1>
                        <p class="my-2 text-gray-800">¡Lo sentimos! Visite nuestra página de inicio para llegar a donde necesita ir.</p>
                        <button @click="goToHome" class="sm:w-full lg:w-auto my-2 border rounded md py-4 px-8 text-center bg-indigo-600 text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-700 focus:ring-opacity-50">
                            ¡Llévame allí!
                        </button>
                    </div>
                </div>
                <div>
                    <img src="https://i.ibb.co/ck1SGFJ/Group.png" />
                </div>
            </div>

    </div>
</template>
