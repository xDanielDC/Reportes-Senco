<template>
    <AppLayout title="Reportes">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Reportes
                </h2>
            </div>
        </template>

        <template #actions>
            <PrimaryButton v-permission="'report.create'" class="ml-auto" type="button" @click="create">
                <font-awesome-icon class="mr-2" icon="plus"/>
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
                            <Link :href="route('report.view', [row.group_id, row.report_id])"
                                  class="mr-2 inline-flex items-center px-2 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                <font-awesome-icon :icon="['fas', 'arrow-up-right-from-square']"/>
                            </Link>
                            <CustomButton v-permission="'report.edit'"
                                          class="mr-2"
                                          @click="edit(row)">
                                <font-awesome-icon :icon="['far', 'pen-to-square']"/>
                            </CustomButton>

                            <CustomButton v-permission="'report.destroy'"
                                          @click="destroy(row.id)">
                                <font-awesome-icon :icon="['far', 'trash-can']"/>
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
                    <InputLabel value="Nombre"/>
                    <TextInput
                        v-model="modal.form.name"
                        :class="{'border-red-500': v$.form.name.$error}"
                        autocomplete="Nombre"
                        class="mt-1 block w-full"
                        required
                        type="text"
                    />
                    <template v-if="v$.form.name.$error">
                        <ul class="mt-1">
                            <li v-for="(error, index) of v$.form.name.$errors"
                                :key="index" class="text-red-500">
                                {{ error.$message }}
                            </li>
                        </ul>
                    </template>
                </div>

                <div class="mt-4">
                    <InputLabel value="Grupo"/>
                    <TextInput
                        v-model="modal.form.group_id"
                        :class="{'border-red-500': v$.form.group_id.$error}"
                        autocomplete="Grupo"
                        class="mt-1 block w-full"
                        required
                        type="text"
                    />
                    <template v-if="v$.form.group_id.$error">
                        <ul class="mt-1">
                            <li v-for="(error, index) of v$.form.group_id.$errors"
                                :key="index" class="text-red-500">
                                {{ error.$message }}
                            </li>
                        </ul>
                    </template>
                </div>

                <div class="mt-4">
                    <InputLabel value="Reporte"/>
                    <TextInput
                        v-model="modal.form.report_id"
                        :class="{'border-red-500': v$.form.report_id.$error}"
                        autocomplete="Reporte"
                        class="mt-1 block w-full"
                        required
                        type="text"
                    />
                    <template v-if="v$.form.report_id.$error">
                        <ul class="mt-1">
                            <li v-for="(error, index) of v$.form.report_id.$errors"
                                :key="index" class="text-red-500">
                                {{ error.$message }}
                            </li>
                        </ul>
                    </template>
                </div>

                <div class="mt-4">
                    <InputLabel value="Dataset"/>
                    <TextInput
                        v-model="modal.form.dataset_id"
                        :class="{'border-red-500': v$.form.dataset_id.$error}"
                        autocomplete="Dataset"
                        class="mt-1 block w-full"
                        required
                        type="text"
                    />
                    <template v-if="v$.form.dataset_id.$error">
                        <ul class="mt-1">
                            <li v-for="(error, index) of v$.form.dataset_id.$errors"
                                :key="index" class="text-red-500">
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

                <PrimaryButton v-if="modal.editMode" class="ml-3" @click="update">
                    Actualizar
                </PrimaryButton>

                <PrimaryButton v-else class="ml-3" @click="store">
                    Guardar
                </PrimaryButton>
            </template>
        </DialogModal>
    </AppLayout>
</template>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputLabel from "@/Components/InputLabel.vue";
import DialogModal from "@/Components/DialogModal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import {required, requiredIf} from "@/Utils/i18n-validators.js";
import {useVuelidate} from "@vuelidate/core";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import {reactive, toRefs} from "vue";
import CustomButton from "@/Components/CustomButton.vue";
import {Link} from "@inertiajs/vue3";

const props = defineProps({
    reports: {
        type: Array,
        default: []
    }
});

const table = reactive({
    data: props.reports,
    columns: [
        'actions',
        'name',
        'updated_at'
    ],
    options: {
        headings: {
            actions: '',
            name: 'NOMBRE',
            updated_at: 'ACTUALIZADO EL'
        },
        clientSorting: false,
        uniqueKey: 'id',
        sortable: ['name'],
    }
});

const modal = reactive({
    open: false,
    editMode: false,
    title: '',
    form: {
        id: '',
        name: '',
        group_id: '',
        report_id: '',
        dataset_id: '',
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
        group_id: {
            required,
        },
        report_id: {
            required,
        },
        dataset_id: {
            required,
        },
    }
});

const v$ = useVuelidate(rules, toRefs(modal));

const create = () => {
    modal.open = true
    modal.title = 'Crear Reporte'
}

const edit = (row) => {
    modal.open = true
    modal.editMode = true
    modal.title = 'Editar Reporte'
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
        axios.post(route('report.store'), modal.form).then(resp => {
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
        axios.put(route('report.update', modal.form.id), modal.form).then(resp => {
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
            axios.delete(route('report.destroy', id)).then(resp => {
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

