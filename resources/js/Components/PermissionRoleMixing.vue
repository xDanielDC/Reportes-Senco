<script>
    export default {
        async beforeMount() {
            const {data: roles} = await axios.get(route('roles.list'));
            const {data: permissions} = await axios.get(route('permissions.list'));

            this.$gates.setRoles(roles);
            this.$gates.setPermissions(permissions);
        }
    }
</script>
<template>
    <div></div>
</template>
