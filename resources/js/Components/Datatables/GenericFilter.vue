<template>
    <div class="VueTables__search-field">
        <input class="form-control w-64 VueTables__search__input"
               type="text"
               placeholder="Buscar..."
               @keyup="e=>props.search(props.debounce)(e)"
               :id="`VueTables__search_${props.id}`"
               autocomplete="off"
        />
    </div>
</template>

<script lang="jsx">
export default {
    name: "VtGenericFilter",
    props: ['props']
}
</script>
