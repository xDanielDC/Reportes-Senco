<template>
    <AppLayout :title="`Editar usuario ${user.name}`">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Editar usuario {{ user.name }}
                </h2>
            </div>
        </template>

        <template #actions>
            <Link :href="route('users.index')" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" >
                <font-awesome-icon class="mr-2" icon="arrow-left"/>
                Atrás
            </Link>
        </template>

        <div class="py-10">
            <div class="max-w-8xl mx-10 sm:px-8 lg:px-8">
                <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg bg-white p-5">
                            <div class="grid grid-cols-3 gap-5">
                                <div>
                                    <InputLabel value="Nombre"/>
                                    <TextInput
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        :class="{'border-red-500': v$.form.name.$error}"
                                        required
                                        autocomplete="off"
                                    />
                                    <template v-if="v$.form.name.$error">
                                        <ul class="mt-1">
                                            <li class="text-red-500"
                                                v-for="(error, index) of v$.form.name.$errors" :key="index">
                                                {{ error.$message }}
                                            </li>
                                        </ul>
                                    </template>
                                </div>

                                <div>
                                    <InputLabel value="Usuario"/>
                                    <TextInput
                                        v-model="form.username"
                                        type="text"
                                        class="mt-1 block w-full"
                                        :class="{'border-red-500': v$.form.username.$error}"
                                        required
                                        autocomplete="off"
                                    />
                                    <template v-if="v$.form.username.$error">
                                        <ul class="mt-1">
                                            <li class="text-red-500"
                                                v-for="(error, index) of v$.form.username.$errors" :key="index">
                                                {{ error.$message }}
                                            </li>
                                        </ul>
                                    </template>
                                </div>

                                <div>
                                    <InputLabel value="Correo electrónico"/>
                                    <TextInput
                                        v-model="form.email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        :class="{'border-red-500': v$.form.email.$error}"
                                        required
                                        autocomplete="off"
                                    />
                                    <template v-if="v$.form.email.$error">
                                        <ul class="mt-1">
                                            <li class="text-red-500"
                                                v-for="(error, index) of v$.form.email.$errors" :key="index">
                                                {{ error.$message }}
                                            </li>
                                        </ul>
                                    </template>
                                </div>

                                <div class="block">
                                    <label class="flex items-center">
                                        <Checkbox v-model:checked="form.change_password" name="change_password"/>
                                        <span class="ml-2 text-sm text-gray-600">Cambiar contraseña</span>
                                    </label>
                                </div>

                                <template v-if="form.change_password">
                                    <div>
                                        <InputLabel value="Contraseña"/>
                                        <TextInput
                                            v-model="form.password"
                                            type="password"
                                            class="mt-1 block w-full"
                                            :class="{'border-red-500': v$.form.password.$error}"
                                            required
                                            autocomplete="off"
                                        />
                                        <template v-if="v$.form.password.$error">
                                            <ul class="mt-1">
                                                <li class="text-red-500"
                                                    v-for="(error, index) of v$.form.password.$errors"
                                                    :key="index">
                                                    {{ error.$message }}
                                                </li>
                                            </ul>
                                        </template>
                                    </div>

                                    <div>
                                        <InputLabel value="Confirmar Contraseña"/>
                                        <TextInput
                                            v-model="form.confirm_password"
                                            type="password"
                                            class="mt-1 block w-full"
                                            :class="{'border-red-500': v$.form.confirm_password.$error}"
                                            required
                                            autocomplete="off"
                                        />
                                        <template v-if="v$.form.confirm_password.$error">
                                            <ul class="mt-1">
                                                <li class="text-red-500"
                                                    v-for="(error, index) of v$.form.confirm_password.$errors"
                                                    :key="index">
                                                    {{ error.$message }}
                                                </li>
                                            </ul>
                                        </template>
                                    </div>
                                </template>

                                <fieldset class="border rounded-lg p-4 col-span-3">
                                    <legend>Roles disponibles</legend>
                                    <div class="grid grid-cols-5 gap-5">
                                        <div class="flex items-center" v-for="role in roles">
                                            <Checkbox v-model:checked="form.roles" :value="role.name"/>
                                            <div class="ml-2">
                                                {{ role.name }}
                                            </div>
                                        </div>
                                    </div>

                                    <template v-if="v$.form.roles.$error">
                                        <ul class="mt-1">
                                            <li class="text-red-500"
                                                v-for="(error, index) of v$.form.roles.$errors" :key="index">
                                                {{ error.$message }}
                                            </li>
                                        </ul>
                                    </template>
                                </fieldset>

                                <fieldset class="border rounded-lg p-4 col-span-3">
                                    <legend>Permisos disponibles</legend>
                                    <div class="grid grid-cols-5 gap-5">
                                        <div class="flex items-center" v-for="permission in permissions">
                                            <Checkbox v-model:checked="form.permissions" :value="permission.name"/>
                                            <div class="ml-2">
                                                {{ permission.name }}
                                            </div>
                                        </div>
                                    </div>

                                    <template v-if="v$.form.permissions.$error">
                                        <ul class="mt-1">
                                            <li class="text-red-500"
                                                v-for="(error, index) of v$.form.permissions.$errors" :key="index">
                                                {{ error.$message }}
                                            </li>
                                        </ul>
                                    </template>
                                </fieldset>
                            </div>

                            <PrimaryButton @click="updateUser" class="mt-5">
                                Actualizar
                            </PrimaryButton>
                        </div>

                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg mt-5 bg-white">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr class="border-b">
                                    <th scope="col"
                                        colspan="4"
                                        class="px-6 py-3 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">
                                        Reportes
                                    </th>
                                </tr>
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <CustomButton class="mr-2" @click="openReportModal">
                                            <font-awesome-icon :icon="['far', 'pen-to-square']" class="mr-1"/>
                                            Modificar reportes
                                        </CustomButton>
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Mostrar en dashboard
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Reporte
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Filtros
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="report in user.reports" v-if="user.reports.length > 0">
                                    <td class="px-6 py-4 text-center text-sm font-medium">
                                        <div class="flex flex-row">
                                            <CustomButton class="mr-2"
                                                          @click="openFilterModal(report.id, report.filters)">
                                                <font-awesome-icon :icon="['fas', 'filter']" class="mr-1"/>
                                                Filtros
                                            </CustomButton>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        <Checkbox v-on:change="updateShowDashboard(report.id, !report.pivot.show)" :value="report.pivot.show" :checked="report.pivot.show === 1"/>
                                    </td>

                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ report.name }}
                                    </td>

                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        <span
                                            class="text-xs font-semibold inline-block py-1 px-2 rounded text-green-600 bg-green-200 uppercase last:mr-0 mr-1"
                                            v-if="report.filters.length > 0"
                                            :class="{'ml-1': index > 0}"
                                            v-for="(filter, index) in report.filters">
                                                {{ filter.name }}
                                        </span>

                                        <span
                                            class="text-xs font-semibold inline-block py-1 px-2 rounded text-red-600 bg-red-200 uppercase last:mr-0 mr-1"
                                            v-else>
                                            Ningún filtro agregado
                                        </span>
                                    </td>
                                </tr>
                                <tr v-else>
                                    <td class="px-6 py-4 text-sm font-medium text-center text-red-500" colspan="4">
                                        Ningún registro agregado…
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DialogModal :show="filterModal.open" @close="closeModal">
            <template #title>
                Filtros {{ reports.find(row => row.id === filterModal.form.report_id).name }}
            </template>

            <template #content>
                <div class="grid grid-cols-2 gap-5">
                    <div class="flex items-center" v-for="filter in filters">
                        <Checkbox v-model:checked="filterModal.form.filters" :value="filter.id"/>
                        <div class="ml-2">
                            {{ filter.name }}
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton class="ml-3" @click="updateFilter">
                    Actualizar
                </PrimaryButton>
            </template>
        </DialogModal>

        <DialogModal :show="reportModal.open" @close="closeModal">
            <template #title>
                Modificar reportes
            </template>

            <template #content>
                <div class="grid grid-cols-2 gap-5">
                    <div class="flex items-center" v-for="report in reports">
                        <Checkbox v-model:checked="reportModal.form.reports" :value="report.id"/>
                        <div class="ml-2">
                            {{ report.name }}
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton class="ml-3" @click="updateReports">
                    Actualizar
                </PrimaryButton>
            </template>
        </DialogModal>

    </AppLayout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import TextInput from "@/Components/TextInput.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputLabel from "@/Components/InputLabel.vue";
import {useVuelidate} from "@vuelidate/core";
import {email, minLength, required, requiredIf, sameAs} from "@/Utils/i18n-validators.js";
import {Link, router} from "@inertiajs/vue3";
import CustomButton from "@/Components/CustomButton.vue";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import DialogModal from "@/Components/DialogModal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import report from "../Report/Index.vue";

export default {
    computed: {
        report() {
            return report
        }
    },
    setup() {
        return {v$: useVuelidate()}
    },

    components: {
        PrimaryButton, SecondaryButton,
        DialogModal,
        FontAwesomeIcon,
        CustomButton, Link,
        InputLabel,
        Checkbox,
        TextInput,
        AppLayout
    },

    props: {
        user: Object,
        roles: Array,
        permissions: Array,
        reports: Array,
        filters: Array
    },

    validations() {
        return {
            form: {
                id: {
                    required
                },
                name: {
                    required
                },
                username: {
                    required
                },
                email: {
                    required,
                    email
                },
                password: {
                    requiredIf: requiredIf(this.form.change_password),
                    minLength: minLength(8)
                },
                confirm_password: {
                    requiredIf: requiredIf(this.form.change_password),
                    sameAs: sameAs(this.form.password),
                    minLength: minLength(8)
                },
                reports: {
                    minLength: minLength(1)
                },
                permissions: {
                    requiredIf: requiredIf(this.form.roles.length < 1),
                    minLength: minLength(1)
                },
                roles: {
                    requiredIf: requiredIf(this.form.permissions.length < 1),
                    minLength: minLength(1)
                },
            },
        }
    },

    data() {
        return {
            form: {
                id: this.user.id,
                type: this.user.type,
                name: this.user.name,
                username: this.user.username,
                email: this.user.email,
                change_password: false,
                password: '',
                confirm_password: '',
                reports: [],
                permissions: this.user.permissions.map(row => row.name),
                roles: this.user.roles.map(row => row.name),
            },

            filterModal: {
                open: false,
                form: {
                    user_id: '',
                    report_id: '',
                    filters: []
                }
            },

            reportModal: {
                open: false,
                form: {
                    user_id: '',
                    reports: []
                }
            }
        }
    },

    methods: {
        openFilterModal(report_id, filters) {
            this.filterModal = {
                open: true,
                form: {
                    user_id: this.user.id,
                    report_id: report_id,
                    filters: filters.map((row) => row.id)
                }
            }
        },

        closeModal() {
            this.filterModal = {
                open: false,
                form: {
                    user_id: '',
                    report_id: '',
                    filters: []
                }
            }

            this.reportModal = {
                open: false,
                form: {
                    user_id: '',
                    reports: []
                }
            }
        },

        updateFilter() {
            axios.post(route('user.report.update-filters'), this.filterModal.form).then(() => {
                this.$swal({
                    icon: 'success',
                    title: 'Actualizado',
                    text: 'Filtros actualizados con éxito',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                });

                this.closeModal()
                router.visit(route('users.edit', this.user.id))
            }).catch(err => {
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: err.response.data
                });
            })
        },

        openReportModal() {
            this.reportModal = {
                open: true,
                form: {
                    user_id: this.user.id,
                    reports: this.user.reports.map(row => row.id)
                }
            }
        },

        updateReports() {
            axios.post(route('user.update-reports'), this.reportModal.form).then(() => {
                this.$swal({
                    icon: 'success',
                    title: 'Actualizado',
                    text: 'Reportes actualizados con éxito',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                });

                this.closeModal()
                router.visit(route('users.edit', this.user.id))
            }).catch(err => {
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: err.response.data
                });
            })
        },

        updateUser() {
            this.v$.form.$touch()

            if (this.v$.form.$invalid) {
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: 'Verifica que toda la información este correctamente diligenciada'
                });
            } else {
                axios.put(route('users.update', this.form.id), this.form).then(() => {
                    this.$swal({
                        icon: 'success',
                        title: 'Usuario Actualizado',
                        text: 'Usuario actualizado con éxito',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 5000,
                        timerProgressBar: true,
                    });

                    router.visit(route('users.edit', this.user.id))
                }).catch(err => {
                    this.$swal({
                        icon: 'error',
                        title: 'ERROR',
                        text: err.response.data
                    });
                })
            }
        },

        updateShowDashboard(id, state) {
            axios.post(route('user.report.set-default'), {
                user_id: this.user.id,
                report_id: id,
                state: state
            }).then(() => {
                this.$swal({
                    icon: 'success',
                    title: 'Actualizado',
                    text: 'Reporte actualizado con exito',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                });

                router.visit(route('users.edit', this.user.id))
            }).catch(err => {
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: err.response.data
                });
            })

        }
    }
}
</script>
