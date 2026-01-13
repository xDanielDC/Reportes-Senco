<template>
    <AppLayout title="Filtros">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Filtros
                </h2>
            </div>
        </template>

        <template #actions>
            <PrimaryButton type="button" class="ml-auto" @click="create" v-permission="'report.filter.create'">
                <font-awesome-icon icon="plus" class="mr-2"/>
                Crear
            </PrimaryButton>
        </template>

        <div class="py-10">
            <div class="max-w-8xl sm:px-4 lg:px-6">
                <v-client-table :columns="table.columns"
                                :data="table.data"
                                :options="table.options"
                                class="overflow-y-auto ">

                    <template v-slot:actions="{row}">
                        <div class="text-center flex flex-row">
                            <CustomButton class="mr-2"
                                          @click="edit(row)"
                                          v-permission="'report.filter.update'">
                                <font-awesome-icon :icon="['far', 'pen-to-square']" />
                            </CustomButton>
                            <CustomButton @click="destroy(row.id)"
                                          v-permission="'report.filter.destroy'">
                                <font-awesome-icon :icon="['far', 'trash-can']" />
                            </CustomButton>
                        </div>
                    </template>
                </v-client-table>
            </div>
        </div>

        <DialogModal :show="modal.open" @close="closeModal">
            <template #title>
                {{ modal.title }}
            </template>

            <template #content>
                <div class="mt-4">
                    <InputLabel value="Nombre" />
                    <TextInput
                        v-model="modal.form.name"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{'border-red-500': v$.form.name.$error}"
                        required
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

                <div class="mt-4">
                    <InputLabel value="Tabla" />
                    <TextInput
                        v-model="modal.form.table"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{'border-red-500': v$.form.table.$error}"
                        required
                    />
                    <template v-if="v$.form.table.$error">
                        <ul class="mt-1">
                            <li class="text-red-500"
                                v-for="(error, index) of v$.form.table.$errors" :key="index">
                                {{ error.$message }}
                            </li>
                        </ul>
                    </template>
                </div>

                <div class="mt-4">
                    <InputLabel value="Columna" />
                    <TextInput
                        v-model="modal.form.column"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{'border-red-500': v$.form.column.$error}"
                        required
                    />
                    <template v-if="v$.form.column.$error">
                        <ul class="mt-1">
                            <li class="text-red-500"
                                v-for="(error, index) of v$.form.column.$errors" :key="index">
                                {{ error.$message }}
                            </li>
                        </ul>
                    </template>
                </div>

                <div class="mt-4">
                    <InputLabel value="Operador" />
                    <TextInput
                        v-model="modal.form.operator"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{'border-red-500': v$.form.operator.$error}"
                        required
                    />
                    <template v-if="v$.form.operator.$error">
                        <ul class="mt-1">
                            <li class="text-red-500"
                                v-for="(error, index) of v$.form.operator.$errors" :key="index">
                                {{ error.$message }}
                            </li>
                        </ul>
                    </template>
                </div>

                <div class="mt-4">
                    <InputLabel value="Valor" />
                    <TextInput
                        v-model="modal.form.values"
                        type="text"
                        class="mt-1 block w-full"
                        :class="{'border-red-500': v$.form.values.$error}"
                        required
                    />
                    <template v-if="v$.form.values.$error">
                        <ul class="mt-1">
                            <li class="text-red-500"
                                v-for="(error, index) of v$.form.values.$errors" :key="index">
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


<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import {Link} from "@inertiajs/vue3";
import CustomButton from "@/Components/CustomButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import DialogModal from "@/Components/DialogModal.vue";
import {useVuelidate} from "@vuelidate/core";
import {required, requiredIf} from "@/Utils/i18n-validators.js";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import {reactive, toRefs} from "vue";

const props = defineProps({
    filters: {
        type: Array,
        default: []
    }
});

const table = reactive({
    data: props.filters,
    columns: [
        'actions',
        'name',
        'table',
        'column',
        'operator',
        'values',
        'created_at'
    ],
    options: {
        headings: {
            actions: '',
            name: 'NOMBRE',
            table: 'TABLA',
            column: 'COLUMNA',
            operator: 'OPERADOR',
            values: 'VALOR',
            created_at: 'CREADO EL'
        },
        clientSorting: false,
        uniqueKey: 'id',
        sortable: ['name', 'table', 'column', 'operator'],
    }
});

const modal = reactive({
    open: false,
    editMode: false,
    title: '',
    form: {
        id: '',
        name: '',
        table: '',
        column: '',
        operator: '',
        values: '',
    }
});

const rules = reactive({
    form: {
        id: {
            requiredIf: requiredIf(modal.editMode)
        },
        name: {
            required
        },
        table: {
            required
        },
        column: {
            required,
        },
        operator: {
            required,
        },
        values: {
            required,
        },
    }
});

const v$ = useVuelidate(rules, toRefs(modal));

const create = () => {
    modal.open = true
    modal.title = 'Crear Filtro'
}

const edit = (row) => {
    modal.open = true
    modal.editMode = true
    modal.title = 'Editar Filtro'
    modal.form = {
        id: row.id,
        name: row.name,
        group_id: row.group_id,
        report_id: row.report_id,
        dataset_id: row.dataset_id,
    }
}

const closeModal = () => {
    modal.open = false
    modal.editMode = false
    modal.title = ''
    modal.form = {
        id: '',
        name: '',
        group_id: '',
        report_id: '',
        dataset_id: '',
    }
    v$.value.form.$reset()
}

const store = () => {
    v$.value.form.$touch()

    if (v$.value.form.$invalid) {
        Swal.fire({
            icon: 'error',
            title: '¡Whoops!',
            text: 'Verifica que toda la información este correctamente diligenciada',
            confirmButtonText: 'Aceptar'
        });
    } else {
        axios.post(route('report.filter.store'), modal.form).then(resp => {
            closeModal()
            table.data = resp.data
        }).catch(err => {
            Swal.fire({
                icon: 'error',
                title: '¡Whoops!',
                text: err.response.data,
                confirmButtonText: 'Aceptar'
            });
        })
    }
}

const update = () => {
    v$.value.form.$touch()

    if (v$.value.form.$invalid) {
        Swal.fire({
            icon: 'error',
            title: '¡Whoops!',
            text: 'Verifica que toda la información este correctamente diligenciada',
            confirmButtonText: 'Aceptar'
        });
    } else {
        axios.put(route('report.filter.update', modal.form.id), modal.form).then(resp => {
            closeModal()
            table.data = resp.data
        }).catch(err => {
            Swal.fire({
                icon: 'error',
                title: '¡Whoops!',
                text: err.response.data,
                confirmButtonText: 'Aceptar'
            });
        })
    }
}

const destroy = (id) => {
    Swal.fire({
        icon: 'question',
        title: '¿Eliminar Reporte?',
        text: "¡Esta acción no es reversible!",
        showCancelButton: true,
        confirmButtonText: '¡Si, Eliminar!'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('report.filter.destroy', id)).then(resp => {
                table.data = resp.data
            }).catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: '¡Whoops!',
                    text: err.response.data,
                    confirmButtonText: 'Aceptar'
                });
            })
        }
    })
}

</script>
