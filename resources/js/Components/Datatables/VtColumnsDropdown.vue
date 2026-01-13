<template>
    <div class="VueTables__columns-dropdown-wrapper">
        <div class="dropdown VueTables__columns-dropdown">
            <button class="dropdown-toggle btn btn-secondary">
                {{ props.display('columns') }}
                <font-awesome-icon icon="angle-down" class="ml-2"/>
            </button>
            <div class="dropdown-menu w-40">
                <div class="dropdown-menu__content box dark:bg-dark-1 p-2">
                    <a v-for="column in props.origColumns"
                       href="#"
                       @click="props.toggleColumn(column)"
                       class="flex items-center p-2 transition duration-300 ease-in-out bg-white dark:bg-dark-1 hover:bg-gray-200 dark:hover:bg-dark-2 rounded-md">
                        <input type="checkbox" class="form-check-input mr-2" :value="column"
                               :disabled="props.onlyColumn(column)"
                               :checked="props.columns.includes(column)"/>
                        {{ props.getHeading(column) }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="jsx">
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";

export default {
    name: "MyColumnsDropdown",
    components: {FontAwesomeIcon},
    props: ['props']
}
</script>
