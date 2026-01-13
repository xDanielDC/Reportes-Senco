<template>
    <AppLayout :title="`Diseño - Solicitud # ${designRequest.id}`">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Diseño - Solicitud # {{ designRequest.id }}
                </h2>
            </div>
        </template>

        <template #actions>
            <Link :href="route('request.index')"
                  class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 ml-auto">
                <font-awesome-icon icon="arrow-left" class="mr-2"/>
                Atrás
            </Link>
        </template>

        <div class="py-12">
            <div class="max-w-8xl mx-6 sm:px-6 lg:px-8">
                <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full">
                        <div class="grid grid-cols-6 gap-5">
                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Prioridad
                                                </p>
                                                <h5 class="mb-0 font-bold hover:text-blue-800 cursor-pointer" @click="updatePriority">
                                                    {{ designRequest.priority.name }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="turn-up"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Diseñador
                                                </p>
                                                <h5 class="mb-0 font-bold hover:text-blue-800 cursor-pointer" @click="updateDesigner">
                                                    {{ designRequest.designer.name }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="user"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Vendedor(a)
                                                </p>
                                                <h5 class="mb-0 font-bold hover:text-blue-800 cursor-pointer" @click="updateSeller">
                                                    {{ designRequest.seller.name }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="user"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Cliente
                                                </p>
                                                <h5 class="mb-0 font-bold hover:text-blue-800 cursor-pointer" @click="updateCustomer">
                                                    {{ designRequest.customer.name }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="user"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Fecha recepción
                                                </p>
                                                <h5 class="mb-0 font-bold">
                                                    {{ designRequest.reception_date }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="calendar"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Fecha tentativa
                                                </p>
                                                <h5 class="mb-0 font-bold">
                                                    {{ designRequest.tentative_date }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="calendar"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Fecha real
                                                </p>
                                                <h5 class="mb-0 font-bold">
                                                    {{ designRequest.real_date }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="calendar"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Fecha entrega
                                                </p>
                                                <h5 class="mb-0 font-bold">
                                                    {{ designRequest.delivery_date }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="calendar"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Fecha aprobación cliente
                                                </p>
                                                <h5 class="mb-0 font-bold">
                                                    {{ designRequest.customer_approved_date }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="calendar"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Fecha sherpa
                                                </p>
                                                <h5 class="mb-0 font-bold">
                                                    {{ designRequest.estimated_arrival_sherpa_date }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="calendar"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Estado de tiempo
                                                </p>
                                                <h5 class="mb-0 font-bold hover:text-blue-800 cursor-pointer" @click="updateTimeState">
                                                    {{ designRequest.time_state.name }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="clock"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Estado
                                                </p>
                                                <h5 class="mb-0 font-bold hover:text-blue-800 cursor-pointer" @click="updateState">
                                                    {{ designRequest.state.name }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="circle-check"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Creado por
                                                </p>
                                                <h5 class="mb-0 font-bold">
                                                    {{ designRequest.created_by.name }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="user"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Actualizado por
                                                </p>
                                                <h5 class="mb-0 font-bold">
                                                    {{ designRequest.updated_by.name }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="user"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border col-span-2">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Observaciones
                                                </p>
                                                <h5 class="mb-0 font-bold">
                                                    {{ designRequest.observations }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="comments"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <div
                                class="relative flex flex-col min-w-0 break-words bg-white shadow-soft-xl rounded-2xl bg-clip-border col-span-2">
                                <div class="flex-auto p-4">
                                    <div class="flex flex-wrap -mx-3">
                                        <div class="flex-none w-2/3 max-w-full px-3">
                                            <div>
                                                <p class="mb-0 font-sans font-semibold leading-normal text-sm">
                                                    Comentarios
                                                </p>
                                                <h5 class="mb-0 font-bold">
                                                    {{ designRequest.comments }}
                                                </h5>
                                            </div>
                                        </div>
                                        <div class="w-4/12 max-w-full px-3 ml-auto text-right flex-0">
                                            <font-awesome-icon icon="comments"
                                                               class="text-3xl relative top-2.5 text-gray-900"/>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-8xl mx-6 sm:px-6 lg:px-8">
                <div class="overflow-x-auto sm:-mx-6 lg:-mx-8">
                    <div class="py-2 align-middle inline-block min-w-full">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr class="border-b">
                                    <th colspan="5" scope="col"
                                        class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        TAREAS
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">
                                        <PrimaryButton @click="openModal">
                                            <font-awesome-icon icon="plus" class="mr-2"/>
                                            Agregar
                                        </PrimaryButton>
                                    </th>
                                </tr>
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Descripción
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Creado por
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Creado el
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actualizado por
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actualizado el
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">

                                    </th>
                                </tr>
                                </thead>

                                <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="task in designRequest.tasks" v-if="designRequest.tasks.length > 0">
                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ task.description }}
                                    </td>

                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ task.created_by.name }}
                                    </td>

                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ task.created_at }}
                                    </td>

                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ task.updated_by.name }}
                                    </td>

                                    <td class="px-6 py-4 text-left text-sm font-medium">
                                        {{ task.updated_at }}
                                    </td>

                                    <td class="px-6 py-4 text-center text-sm font-medium">
                                        <div class="flex flex-row">

                                            <SecondaryButton class="mr-2" @click="editTask(task)">
                                                <font-awesome-icon :icon="['far', 'pen-to-square']"/>
                                            </SecondaryButton>

                                            <DangerButton @click="destroyTask(task.design_request_id, task.id)">
                                                <font-awesome-icon :icon="['far', 'trash-can']"/>
                                            </DangerButton>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-else>
                                    <td class="px-6 py-4 text-center text-red-500 text-sm font-medium" colspan="6">
                                        Sin registros…
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <DialogModal :show="taskModal.open" @close="closeModal" max-width="2xl">
            <template #title>
                Tarea
            </template>

            <template #content>
                <div>
                    <InputLabel value="Descripción"/>

                    <textarea
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full resize-none"
                        v-model="taskModal.form.description"
                        :class="{'border-red-500': v$.taskModal.form.description.$error}"
                        required cols="30" rows="2"></textarea>

                    <template v-if="v$.taskModal.form.description.$error">
                        <ul class="mt-1">
                            <li class="text-red-500"
                                v-for="(error, index) of v$.taskModal.form.description.$errors" :key="index">
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

                <PrimaryButton class="ml-3" @click="updateTask(taskModal.form)" v-if="taskModal.editMode">
                    Actualizar
                </PrimaryButton>

                <PrimaryButton class="ml-3" @click="storeTask(taskModal.form)" v-else>
                    Guardar
                </PrimaryButton>
            </template>
        </DialogModal>

    </AppLayout>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {Link} from "@inertiajs/vue3";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import {useVuelidate} from "@vuelidate/core";
import {required, requiredIf} from "@/Utils/i18n-validators.js";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DialogModal from "@/Components/DialogModal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import DangerButton from "@/Components/DangerButton.vue";

export default {
    components: {
        DangerButton,
        InputLabel,
        DialogModal,
        SecondaryButton,
        FontAwesomeIcon,
        PrimaryButton,
        AppLayout,
        Link
    },

    setup() {
        return {v$: useVuelidate()}
    },

    props: {
        design_request: Object,
        priorities: Array,
        time_states: Array,
        states: Array,
        designers: Array,
        customers: Array,
        psl_customers: Array,
        sellers: Array
    },

    validations() {
        return {
            taskModal: {
                form: {
                    id: {requiredIf: requiredIf(this.taskModal.editMode)},
                    design_request_id: {required},
                    description: {required},
                }
            }
        }
    },

    data() {
        return {
            designRequest: this.design_request,
            taskModal: {
                open: false,
                editMode: false,
                form: {
                    id: '',
                    design_request_id: this.design_request.id,
                    description: ''
                }
            }
        }
    },

    methods: {
        storeTask(form) {
            this.v$.taskModal.form.$touch()

            if (this.v$.taskModal.form.$invalid) {
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: 'Verifica que toda la información este correctamente diligenciada'
                });
            } else {
                axios.post(route('task.store'), form).then(resp => {
                    this.closeModal()
                    this.designRequest.tasks = resp.data
                }).catch(err => {
                    this.$swal({
                        icon: 'error',
                        title: 'Error',
                        text: err.response.data.message
                    });
                })
            }
        },

        editTask(row) {
            this.taskModal = {
                open: true,
                editMode: true,
                form: {
                    id: row.id,
                    design_request_id: this.design_request.id,
                    description: row.description
                }
            }
        },

        updateTask(form) {
            this.v$.taskModal.form.$touch()

            if (this.v$.taskModal.form.$invalid) {
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: 'Verifica que toda la información este correctamente diligenciada'
                });
            } else {
                axios.put(route('task.update', form.id), form).then(resp => {
                    this.closeModal()
                    this.designRequest.tasks = resp.data
                }).catch(err => {
                    this.$swal({
                        icon: 'error',
                        title: 'Error',
                        text: err.response.data.message
                    });
                })
            }
        },

        destroyTask(request_id, id) {
            this.$swal({
                icon: 'question',
                title: '¿Eliminar registro?',
                text: "¡Esta acción no es reversible!",
                showCancelButton: true,
                confirmButtonText: '¡Si, Eliminar!'
            }).then((result) => {
                if (result.isConfirmed) {
                    axios.delete(route('task.destroy', [request_id, id])).then(resp => {
                        this.designRequest.tasks = resp.data
                    }).catch(err => {
                        this.$swal({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data.message
                        });
                    })
                }
            })
        },

        openModal() {
            this.taskModal = {
                open: true,
                editMode: false,
                form: {
                    id: '',
                    design_request_id: this.design_request.id,
                    description: ''
                }
            }
        },

        closeModal() {
            this.taskModal = {
                open: false,
                editMode: false,
                form: {
                    id: '',
                    design_request_id: this.design_request.id,
                    description: ''
                }
            }

            this.v$.taskModal.form.$reset();
        },

        updateState(){
            let list = this.states.map((value) =>  [value.id, value.name])
            list = Object.fromEntries(list)

            this.$swal({
                icon: 'info',
                title: 'Estado',
                text: `Por favor, seleccione una opción`,
                input: 'select',
                inputOptions: list,
                inputValue: this.designRequest.state.id,
                showCancelButton: true,
                inputAttributes: {
                    required: true
                },
                customClass: {
                    input: 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm '
                },
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                inputValidator: (inputValue) => {
                    if (inputValue === '0') return 'Debes seleccionar una opción...'
                }
            }).then((inputValue) => {
                if (inputValue.value) {
                    axios.post(route('design.request.update-state'), {
                        id: this.designRequest.id,
                        property: 'state_id',
                        state_id: inputValue.value
                    }).then(resp => {
                        this.designRequest = resp.data

                        this.$swal({
                            icon: 'success',
                            title: '¡Registro actualizado!',
                            text: 'Estado actualizado con éxito',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            reverseButtons: false,
                        });
                    }).catch(err => {
                        this.$swal({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data.message
                        });
                    })
                }
            })
        },

        updateTimeState(){
            let list = this.time_states.map((value) =>  [value.id, value.name])
            list = Object.fromEntries(list)

            this.$swal({
                icon: 'info',
                title: 'Estado de tiempo',
                text: `Por favor, seleccione una opción`,
                input: 'select',
                inputOptions: list,
                inputValue: this.designRequest.time_state.id,
                showCancelButton: true,
                inputAttributes: {
                    required: true
                },
                customClass: {
                    input: 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm '
                },
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                inputValidator: (inputValue) => {
                    if (inputValue === '0') return 'Debes seleccionar una opción...'
                }
            }).then((inputValue) => {
                if (inputValue.value) {
                    axios.post(route('design.request.update-state'), {
                        id: this.designRequest.id,
                        property: 'time_state_id',
                        state_id: inputValue.value
                    }).then(resp => {
                        this.designRequest = resp.data

                        this.$swal({
                            icon: 'success',
                            title: '¡Registro actualizado!',
                            text: 'Estado de tiempo actualizado con éxito',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            reverseButtons: false,
                        });
                    }).catch(err => {
                        this.$swal({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data.message
                        });
                    })
                }
            })
        },

        updatePriority(){
            let list = this.priorities.map((value) =>  [value.id, value.name])
            list = Object.fromEntries(list)

            this.$swal({
                icon: 'info',
                title: 'Prioridad',
                text: `Por favor, seleccione una opción`,
                input: 'select',
                inputOptions: list,
                inputValue: this.designRequest.priority.id,
                showCancelButton: true,
                inputAttributes: {
                    required: true
                },
                customClass: {
                    input: 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm '
                },
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                inputValidator: (inputValue) => {
                    if (inputValue === '0') return 'Debes seleccionar una opción...'
                }
            }).then((inputValue) => {
                if (inputValue.value) {
                    axios.post(route('design.request.update-state'), {
                        id: this.designRequest.id,
                        property: 'priority_id',
                        state_id: inputValue.value
                    }).then(resp => {
                        this.designRequest = resp.data

                        this.$swal({
                            icon: 'success',
                            title: '¡Registro actualizado!',
                            text: 'Prioridad actualizada con éxito',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            reverseButtons: false,
                        });
                    }).catch(err => {
                        this.$swal({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data.message
                        });
                    })
                }
            })
        },

        updateDesigner(){
            let list = this.designers.map((value) =>  [value.id, value.name])
            list = Object.fromEntries(list)

            this.$swal({
                icon: 'info',
                title: 'Diseñador(a)',
                text: `Por favor, seleccione una opción`,
                input: 'select',
                inputOptions: list,
                inputValue: this.designRequest.designer.id,
                showCancelButton: true,
                inputAttributes: {
                    required: true
                },
                customClass: {
                    input: 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm '
                },
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                inputValidator: (inputValue) => {
                    if (inputValue === '0') return 'Debes seleccionar una opción...'
                }
            }).then((inputValue) => {
                if (inputValue.value) {
                    axios.post(route('design.request.update-state'), {
                        id: this.designRequest.id,
                        property: 'designer_id',
                        state_id: inputValue.value
                    }).then(resp => {
                        this.designRequest = resp.data

                        this.$swal({
                            icon: 'success',
                            title: '¡Registro actualizado!',
                            text: 'Diseñador actualizado con éxito',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            reverseButtons: false,
                        });
                    }).catch(err => {
                        this.$swal({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data.message
                        });
                    })
                }
            })
        },

        updateSeller(){
            let list = this.sellers.map((value) =>  [value.id, value.name])
            list = Object.fromEntries(list)

            this.$swal({
                icon: 'info',
                title: 'Diseñador(a)',
                text: `Por favor, seleccione una opción`,
                input: 'select',
                inputOptions: list,
                inputValue: this.designRequest.seller.id,
                showCancelButton: true,
                inputAttributes: {
                    required: true
                },
                customClass: {
                    input: 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm '
                },
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                inputValidator: (inputValue) => {
                    if (inputValue === '0') return 'Debes seleccionar una opción...'
                }
            }).then((inputValue) => {
                if (inputValue.value) {
                    axios.post(route('design.request.update-state'), {
                        id: this.designRequest.id,
                        property: 'seller_id',
                        state_id: inputValue.value
                    }).then(resp => {
                        this.designRequest = resp.data

                        this.$swal({
                            icon: 'success',
                            title: '¡Registro actualizado!',
                            text: 'Vendedor actualizado con éxito',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            reverseButtons: false,
                        });
                    }).catch(err => {
                        this.$swal({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data.message
                        });
                    })
                }
            })
        },

        updateCustomer(){
            let list = this.customers.map((value) =>  [value.id, value.name])
            list = Object.fromEntries(list)

            this.$swal({
                icon: 'info',
                title: 'Diseñador(a)',
                text: `Por favor, seleccione una opción`,
                input: 'select',
                inputOptions: list,
                inputValue: this.designRequest.customer.id,
                showCancelButton: true,
                inputAttributes: {
                    required: true
                },
                customClass: {
                    input: 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm '
                },
                confirmButtonText: 'Actualizar',
                cancelButtonText: 'Cancelar',
                inputValidator: (inputValue) => {
                    if (inputValue === '0') return 'Debes seleccionar una opción...'
                }
            }).then((inputValue) => {
                if (inputValue.value) {
                    axios.post(route('design.request.update-state'), {
                        id: this.designRequest.id,
                        property: 'customer_id',
                        state_id: inputValue.value
                    }).then(resp => {
                        this.designRequest = resp.data

                        this.$swal({
                            icon: 'success',
                            title: '¡Registro actualizado!',
                            text: 'Cliente actualizado con éxito',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                            reverseButtons: false,
                        });
                    }).catch(err => {
                        this.$swal({
                            icon: 'error',
                            title: 'Error',
                            text: err.response.data.message
                        });
                    })
                }
            })
        },


    }
}
</script>
