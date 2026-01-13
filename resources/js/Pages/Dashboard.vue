<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <tabs variant="underline" v-model="activeTab" class="p-5" v-if="reports.length > 0">
                        <tab v-for="report in reports" :name="report.id" :title="report.name">
                            <report-viewer :report="report"/>
                        </tab>
                    </tabs>

                    <welcome v-else/>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import Welcome from '@/Components/Welcome.vue';
import { Tabs, Tab } from 'flowbite-vue'
import reportViewer from "@/Components/ReportViewer.vue";

export  default {
    props: {
        reports: Array
    },

    components: {
        AppLayout,
        Welcome,
        Tabs,
        Tab,
        reportViewer
    },

    data(){
        return {
            activeTab: 1
        }
    },

    mounted() {
        if (this.reports.length > 0){
            this.activeTab = this.reports[0].id
        }
    }
}

</script>
