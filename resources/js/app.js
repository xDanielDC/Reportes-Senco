import './bootstrap';
import '../css/app.css';

import {createApp, h, markRaw} from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import VueGates from 'vue-gates'
import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import {fas} from '@fortawesome/free-solid-svg-icons';
import {far} from '@fortawesome/free-regular-svg-icons';
import {library} from '@fortawesome/fontawesome-svg-core';
import {FontAwesomeIcon} from '@fortawesome/vue-fontawesome';
import {ClientTable} from '@dcorrea-estrav/vue3-datatables';
import SortControl from "@/Components/Datatables/SortControl.vue";
import GenericFilter from "@/Components/Datatables/GenericFilter.vue";
import PerPageSelector from "@/Components/Datatables/PerPageSelector.vue";
import VtChildRowToggler from "@/Components/Datatables/VtChildRowToggler.vue";
import VtColumnsDropdown from "@/Components/Datatables/VtColumnsDropdown.vue";
import tailwindTheme from "@/Components/Datatables/themes/tailwind.js";
import helper from '@/Utils/helper.js'
import userAbilities from "@/Utils/userAbilities.js";
import VueFullscreen from 'vue-fullscreen'
import TomSelect from "@/CustomComponents/TomSelect/Main.vue";

library.add(fas, far);

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'GB App';

createInertiaApp({
    title: (title) => `${title} | ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(userAbilities)
            .use(VueFullscreen)
            .use(ZiggyVue, Ziggy)
            .use(VueSweetalert2, {
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
            })
            .use(helper)
            .use(ClientTable, {}, tailwindTheme, {
                sortControl: markRaw(SortControl),
                genericFilter: markRaw(GenericFilter),
                perPageSelector: markRaw(PerPageSelector),
                childRowToggler: markRaw(VtChildRowToggler),
                columnsDropdown: markRaw(VtColumnsDropdown)
            })
            .use(VueGates, {superRole: 'super-admin'})
            .component("font-awesome-icon", FontAwesomeIcon)
            .component("TomSelect", TomSelect)
            .mount(el);
    },
    progress: {
        delay: 250,
        color: '#29d',
        includeCSS: true,
        showSpinner: true,
    },
});
