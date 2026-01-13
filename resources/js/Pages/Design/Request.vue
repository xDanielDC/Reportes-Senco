<template>
    <AppLayout title="Diseño - Solicitudes">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Diseño - Solicitudes
                </h2>
            </div>
        </template>

        <template #actions>
            <PrimaryButton type="button" class="ml-auto" @click="modal.open = true">
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
                                        #
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Prioridad
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Diseñador
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Vendedor
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cliente
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Comentarios
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha de recepción
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha tentativa
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha real
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha entrega
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha aprobación cliente
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Fecha Sherpa
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado tiempo
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Estado
                                    </th>

                                     <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Observaciones
                                    </th>

                                    <th scope="col"
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    </th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="row in table.data" v-if="table.data.length > 0">
                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.id }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.priority.name }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.designer.name }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.seller.name }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.customer.name }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.comments }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.reception_date }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.tentative_date }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.real_date }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.delivery_date }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.customer_approved_date }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.estimated_arrival_sherpa_date }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.time_state.name }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.state.name }}
                                        </td>

                                        <td class="px-6 py-4 text-left text-sm font-medium">
                                            {{ row.observations }}
                                        </td>

                                        <td class="px-6 py-4 text-center text-sm font-medium">
                                            <div class="flex flex-row">
                                                <Link :href="route('request.show', row.id)" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 mr-2" @click="">
                                                    <font-awesome-icon :icon="['far', 'eye']" />
                                                </Link>

                                                <DangerButton @click="">
                                                    <font-awesome-icon :icon="['far', 'trash-can']" />
                                                </DangerButton>
                                            </div>
                                        </td>
                                    </tr>

                                    <tr v-else>
                                        <td class="px-6 py-4 text-center text-red-500 text-sm font-medium" colspan="16">
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

        <DialogModal :show="modal.open" @close="closeModal" max-width="4xl">
            <template #title>
                Solicitud
            </template>

            <template #content>
                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <InputLabel value="Prioridad" />

                        <TomSelect v-model="modal.form.priority_id"
                                   class="w-full"
                                   :class="{'border-red-500': v$.modal.form.priority_id.$error}">
                            <option value="">Seleccione…</option>
                            <option v-for="priority in priorities" :value="priority.id">{{ priority.name }}</option>
                        </TomSelect>

                        <template v-if="v$.modal.form.priority_id.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.priority_id.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Diseñador" />

                        <TomSelect v-model="modal.form.designer_id"
                                   class="w-full"
                                   :class="{'border-red-500': v$.modal.form.designer_id.$error}">
                            <option value="">Seleccione…</option>
                            <option v-for="designer in designers" :value="designer.id">{{ designer.name }}</option>
                        </TomSelect>

                        <template v-if="v$.modal.form.designer_id.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.designer_id.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Vendedor" />

                        <TomSelect v-model="modal.form.seller_document"
                                   class="w-full"
                                   :class="{'border-red-500': v$.modal.form.seller_document.$error}">
                            <option value="">Seleccione…</option>
                            <option v-for="seller in sellers" :value="seller.tgecodigo">{{ seller.tgenombcomp }}</option>
                        </TomSelect>

                        <template v-if="v$.modal.form.seller_document.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.seller_document.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Cliente" />

                        <TomSelect v-model="modal.form.customer_id"
                                   class="w-full"
                                   :class="{'border-red-500': v$.modal.form.customer_id.$error}">
                            <option value="">Seleccione…</option>

                            <optgroup v-for="(group, key) in customers" :label="key === 'local' ? 'LOCALES' : 'PSL'">
                                <option v-for="customer in group" :value="customer.document">{{ customer.name }}</option>
                            </optgroup>

                        </TomSelect>

                        <template v-if="v$.modal.form.customer_id.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.customer_id.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Estado" />

                        <TomSelect v-model="modal.form.state_id"
                                   class="w-full"
                                   :class="{'border-red-500': v$.modal.form.state_id.$error}">
                            <option value="">Seleccione…</option>
                            <option v-for="state in states" :value="state.id">{{ state.name }}</option>
                        </TomSelect>

                        <template v-if="v$.modal.form.state_id.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.state_id.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Estado de tiempo" />

                        <TomSelect v-model="modal.form.time_state_id"
                                   class="w-full"
                                   :class="{'border-red-500': v$.modal.form.time_state_id.$error}">
                            <option value="">Seleccione…</option>
                            <option v-for="time_state in time_states" :value="time_state.id">{{ time_state.name }}</option>
                        </TomSelect>

                        <template v-if="v$.modal.form.time_state_id.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.time_state_id.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Fecha de recepción" />

                        <VueDatePicker
                            :class="{'border-red-500': v$.modal.form.reception_date.$error}"
                            v-model="modal.form.reception_date"
                            locale="es"
                            cancelText="Cancelar"
                            selectText="Seleccionar"
                            :enable-time-picker="false"
                            :teleport="true"
                            format="dd-MM-yyyy"/>

                        <template v-if="v$.modal.form.reception_date.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.reception_date.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Fecha tentativa" />

                        <VueDatePicker
                            :class="{'border-red-500': v$.modal.form.tentative_date.$error}"
                            v-model="modal.form.tentative_date"
                            locale="es"
                            cancelText="Cancelar"
                            selectText="Seleccionar"
                            :enable-time-picker="false"
                            :teleport="true"
                            format="dd-MM-yyyy"/>

                        <template v-if="v$.modal.form.tentative_date.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.tentative_date.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Fecha real" />

                        <VueDatePicker
                            :class="{'border-red-500': v$.modal.form.real_date.$error}"
                            v-model="modal.form.real_date"
                            locale="es"
                            cancelText="Cancelar"
                            selectText="Seleccionar"
                            :enable-time-picker="false"
                            :teleport="true"
                            format="dd-MM-yyyy"/>

                        <template v-if="v$.modal.form.real_date.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.real_date.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Fecha de entrega" />

                        <VueDatePicker
                            :class="{'border-red-500': v$.modal.form.delivery_date.$error}"
                            v-model="modal.form.delivery_date"
                            locale="es"
                            cancelText="Cancelar"
                            selectText="Seleccionar"
                            :enable-time-picker="false"
                            :teleport="true"
                            format="dd-MM-yyyy"/>

                        <template v-if="v$.modal.form.delivery_date.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.delivery_date.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Fecha aprobación cliente" />

                        <VueDatePicker
                            :class="{'border-red-500': v$.modal.form.customer_approved_date.$error}"
                            v-model="modal.form.customer_approved_date"
                            locale="es"
                            cancelText="Cancelar"
                            selectText="Seleccionar"
                            :enable-time-picker="false"
                            :teleport="true"
                            format="dd-MM-yyyy"/>

                        <template v-if="v$.modal.form.customer_approved_date.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.customer_approved_date.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Fecha aproximada sherpa" />

                        <VueDatePicker
                            :class="{'border-red-500': v$.modal.form.estimated_arrival_sherpa_date.$error}"
                            v-model="modal.form.estimated_arrival_sherpa_date"
                            locale="es"
                            cancelText="Cancelar"
                            selectText="Seleccionar"
                            :enable-time-picker="false"
                            :teleport="true"
                            format="dd-MM-yyyy"/>

                        <template v-if="v$.modal.form.estimated_arrival_sherpa_date.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.estimated_arrival_sherpa_date.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Comentarios" />

                        <textarea class="form-control resize-none"
                                  v-model="modal.form.comments"
                                  :class="{'border-red-500': v$.modal.form.comments.$error}"
                                  required cols="30" rows="2" ></textarea>

                        <template v-if="v$.modal.form.comments.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.comments.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div>
                        <InputLabel value="Observaciones" />

                        <textarea class="form-control resize-none"
                                  v-model="modal.form.observations"
                                  :class="{'border-red-500': v$.modal.form.observations.$error}"
                                  required cols="30" rows="2" ></textarea>

                        <template v-if="v$.modal.form.observations.$error">
                            <ul class="mt-1">
                                <li class="text-red-500"
                                    v-for="(error, index) of v$.modal.form.observations.$errors" :key="index">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>
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
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DialogModal from "@/Components/DialogModal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import {useVuelidate} from "@vuelidate/core";
import {required} from "@/Utils/i18n-validators.js";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import DangerButton from "@/Components/DangerButton.vue";
import {Link} from "@inertiajs/vue3";

export default {
    components: {
        DangerButton,
        InputLabel,
        TextInput,
        FontAwesomeIcon,
        SecondaryButton,
        DialogModal,
        PrimaryButton,
        AppLayout,
        VueDatePicker,
        Link
    },

    setup () {
        return { v$: useVuelidate() }
    },

    props: {
        requests: Array,
        priorities: Array,
        time_states: Array,
        states: Array,
        designers: Array,
        customers: Array,
        psl_customers: Array,
        sellers: Array
    },

    validations(){
        return {
            modal: {
                form: {
                    priority_id: {required},
                    designer_id: {required},
                    seller_document: {required},
                    customer_id: {required},
                    comments: {required},
                    reception_date: {required},
                    tentative_date: {required},
                    real_date: {required},
                    delivery_date: {required},
                    customer_approved_date: {required},
                    estimated_arrival_sherpa_date: {required},
                    time_state_id: {required},
                    state_id: {required},
                    observations: {required},
                }
            }
        }
    },

    data(){
        return {
            table: {
                data: this.requests
            },

            modal: {
                open: false,
                editMode: false,
                form: {
                    priority_id: '',
                    designer_id: '',
                    seller_document: '',
                    customer_id: '',
                    comments: '',
                    reception_date: '',
                    tentative_date: '',
                    real_date: '',
                    delivery_date: '',
                    customer_approved_date: '',
                    estimated_arrival_sherpa_date: '',
                    time_state_id: '',
                    state_id: '',
                    observations: ''
                }
            }
        }
    },

    methods: {
        store(){
            this.v$.modal.form.$touch()

            if (this.v$.modal.form.$invalid){
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: 'Verifica que toda la información este correctamente diligenciada'
                });
            }else {
                axios.post(route('request.store'), this.modal.form).then(resp => {
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
                axios.put(route('request.update', this.modal.form.id), this.modal.form).then(resp => {
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
                    axios.delete(route('request.destroy', id)).then(resp => {
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
        },

        closeModal(){
            this.modal = {
                open: false,
                editMode: false,
                form: {
                    priority_id: '',
                    designer_id: '',
                    seller_document: '',
                    customer_id: '',
                    comments: '',
                    reception_date: '',
                    tentative_date: '',
                    real_date: '',
                    delivery_date: '',
                    customer_approved_date: '',
                    estimated_arrival_sherpa_date: '',
                    time_state_id: '',
                    state_id: '',
                    observations: ''
                }
            }

            this.v$.modal.form.$reset()
        }
    }

}
</script>
