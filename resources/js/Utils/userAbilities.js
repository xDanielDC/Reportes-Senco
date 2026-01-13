export default {
    install: (app) => {
        app.mixin({
            mounted() {
                if (this.$page.props.auth.user) {
                    this.$gates.setRoles(this.$page.props.auth.user.role_names);
                    this.$gates.setPermissions(this.$page.props.auth.user.permission_names);
                }else {
                    this.$gates.setRoles([])
                    this.$gates.setPermissions([])
                }
            }
        })
    }
}
