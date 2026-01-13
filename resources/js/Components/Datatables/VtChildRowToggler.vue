<template>
    <td :tabindex="props.tabIndex" @keypress.enter="props.toggle" @click="props.toggle" class="text-center">
            <span :class="`VueTables__child-row-toggler ${props.class()}`">
                <font-awesome-icon :icon="['far', 'square-plus']"
                                   class="cursor-pointer"
                                   v-if="props.class() === 'VueTables__child-row-toggler--closed'"/>

                <font-awesome-icon :icon="['far', 'square-minus']"
                                   class="cursor-pointer"
                                   v-else/>
            </span>
    </td>
</template>
<script lang="jsx">
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";

export default {
    name: "MyChildRowToggler",
    components: {FontAwesomeIcon},
    props: ['props']
}
</script>
