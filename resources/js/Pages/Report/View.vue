<template>
    <AppLayout :title="props.report.name">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ props.report.name }}
                </h2>
            </div>
        </template>

        <template #actions>
            <PrimaryButton class="mr-2" type="button" @click="toggle">
                <font-awesome-icon class="mr-2" icon="expand"/>
                Pantalla completa
            </PrimaryButton>

            <Link :href="route('report.index')"
                  class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                <font-awesome-icon class="mr-2" icon="arrow-left"/>
                Atrás
            </Link>
        </template>

        <fullscreen v-model="fullScreen">
            <div class="py-12">
                <div class="max-w-8xl mx-6 sm:px-6 lg:px-8">
                    <PowerBIReportEmbed :embed-config="embedConfig" css-class-name="h-screen"/>
                </div>
            </div>
        </fullscreen>
    </AppLayout>
</template>

<script setup>
import {PowerBIReportEmbed} from 'powerbi-client-vue-js';
import {models} from "powerbi-client";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import {Link} from "@inertiajs/vue3";
import {reactive, ref} from "vue";

const props = defineProps({
    report: {
        type: Object,
        default: {}
    }
});

const embedConfig = reactive({
    type: "report",
    id: props.report.report_id,
    embedUrl: props.report.embedUrl,
    accessToken: props.report.token,
    tokenType: models.TokenType.Embed,
    pageView: 'fitToWidth',
    settings: {
        panes: {
            filters: {
                expanded: false,
                visible: false
            }
        },
        background: models.BackgroundType.Transparent,
    },
    filters: JSON.parse(props.report.filter_array),
});

const fullScreen = ref(false);

const toggle = () => {
    fullScreen.value = !fullScreen.value
}
</script>
