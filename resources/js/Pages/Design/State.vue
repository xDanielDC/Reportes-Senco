<template>
    <AppLayout title="Diseño - Estados">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Diseño - Estados
                </h2>
            </div>
        </template>

        <template #actions>
            <PrimaryButton type="button" class="ml-auto" @click="modal.open = true" v-permission="'design.state.create'">
                <font-awesome-icon icon="plus" class="mr-2"/>
                Nuevo
            </PrimaryButton>
        </template>

        <div class="py-12">
            <div class="max-w-8xl mx-6 sm:px-6 lg:px-8">
                <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nombre
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Creado el
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Creado por
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actualizado el
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actualizado por
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="row in table.data" v-if="table.data.length > 0">
                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ row.name }}
                                    </td>
                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ row.created_at }}
                                    </td>
                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ row.created_by.name }}
                                    </td>
                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ row.updated_at }}
                                    </td>
                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ row.updated_by.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <SecondaryButton class="mr-2" @click="edit(row)" v-permission="'report.edit'">
                                            <font-awesome-icon :icon="['far', 'pen-to-square']" />
                                        </SecondaryButton>
                                        <DangerButton @click="destroy(row.id)" v-permission="'report.destroy'">
                                            <font-awesome-icon :icon="['far', 'trash-can']" />
                                        </DangerButton>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DialogModal :show="modal.open" @close="closeModal">
            <template #title>
                {{ modal.editMode ? 'Editar' : 'Crear' }}
            </template>

            <template #content>
                <div class="mt-4">
                    <InputLabel value="Nombre" />
                    <TextInput
                        v-model="modal.form.name"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{'border-red-500': v$.modal.form.name.$error}"
                        required
                        autocomplete="Nombre"
                    />
                    <template v-if="v$.modal.form.name.$error">
                        <ul class="mt-1">
                            <li class="text-red-500"
                                v-for="(error, index) of v$.modal.form.name.$errors" :key="index">
                                {{ error.$message }}
                            </li>
                        </ul>
                    </template>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>

                <PrimaryButton class="ml-3" @click="update" v-if="modal.editMode">
                    Actualizar
                </PrimaryButton>

                <PrimaryButton class="ml-3" @click="store" v-else>
                    Guardar
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>

<script>
import {useVuelidate} from "@vuelidate/core";
import {required, requiredIf} from "@/Utils/i18n-validators.js";
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {Link} from "@inertiajs/vue3";
import DangerButton from "@/Components/DangerButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import DialogModal from "@/Components/DialogModal.vue";
import TextInput from "@/Components/TextInput.vue";

export default {
    components: {
        TextInput, DialogModal, InputLabel,
        SecondaryButton,
        DangerButton,
        Link,
        PrimaryButton,
        AppLayout
    },

    props: {
        states: Array
    },

    setup () {
        return { v$: useVuelidate() }
    },

    validations(){
        return {
            modal: {
                form: {
                    id: {
                        requiredIf: requiredIf(this.modal.editMode)
                    },
                    name: {
                        required
                    }
                }
            }
        }
    },

    data(){
        return {
            table: {
                data: this.states
            },

            modal: {
                open: false,
                editMode: false,
                form: {
                    id: '',
                    name: ''
                },
            }
        }
    },

    methods: {
        closeModal(){
            this.modal = {
                open: false,
                editMode: false,
                form: {
                    id: '',
                    name: ''
                },
            }
        },

        edit(row){
            this.modal = {
                open: true,
                editMode: true,
                form: {
                    id: row.id,
                    name: row.name,
                },
            }
        },

        store(){
            this.v$.modal.form.$touch()

            if (this.v$.modal.form.$invalid){
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: 'Verifica que toda la información este correctamente diligenciada'
                });
            }else {
                axios.post(route('state.store'), this.modal.form).then(resp => {
                    this.closeModal()
                    this.table.data = resp.data
                }).catch(err => {
                    this.$swal({
                        icon: 'error',
                        title: 'Error',
                        text: err.response.data
                    });
                })
            }

        },

        update(){
            this.v$.modal.form.$touch()

            if (this.v$.modal.form.$invalid){
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: 'Verifica que toda la información este correctamente diligenciada'
                });
            }else {
                axios.put(route('state.update', this.modal.form.id), this.modal.form).then(resp => {
                    this.closeModal()
                    this.table.data = resp.data
                }).catch(err => {
                    this.$swal({
                        icon: 'error',
                        title: 'Error',
                        text: err.response.data
                    });
                })
            }

        },

        destroy(id){
            this.$swal({
                icon: 'question',
                title: '¿Eliminar registro?',
                text: "¡Esta acción no es reversible!",
                showCancelButton: true,
                confirmButtonText: '¡Si, Eliminar!'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(route('state.destroy', id)).then(resp => {
                        this.table.data = resp.data
                    }).catch(err => {
                        this.$swal({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data
                        });
                    })
                }
            })
        }
    }
}
</script>
