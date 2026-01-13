<template>
    <div class="VueTables__limit-field">
        <select :id="props.selectAttrs.id"
                class="form-select"
                @change="props.selectEvents.change">
            <option v-for="val in props.perPageValues"
                    :key="val"
                    :value="val"
                    :selected="val===props.selectAttrs.value">{{ val }}
            </option>
        </select>
    </div>
</template>

<script lang="jsx">
export default {
    name: "MyPerPageSelector",
    props: ['props']
}
</script>
