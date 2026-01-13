<template>
    <AppLayout title="Roles">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Roles
                </h2>
            </div>
        </template>

        <template #actions>
            <PrimaryButton v-permission="'role.create'" type="button" @click="create">
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
                            <CustomButton v-permission="'role.edit'" class="mr-2" @click="edit(row)">
                                <font-awesome-icon :icon="['far', 'pen-to-square']"/>
                            </CustomButton>
                            <CustomButton v-permission="'role.destroy'" @click="destroy(row.id)">
                                <font-awesome-icon :icon="['far', 'trash-can']"/>
                            </CustomButton>
                        </div>
                    </template>

                    <template v-slot:permissions="{row}">
                                <span v-for="(permission, index) in row.permissions"
                                      class="badge badge-success">
                                    {{ permission.name }}
                                </span>
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
                    <InputLabel value="Permisos Disponibles"/>
                    <div class="grid grid-cols-3 gap-5 mt-2">
                        <div v-for="permission in permissions" class="flex items-center">
                            <Checkbox v-model:checked="modal.form.permissions" :value="permission.name"/>
                            <div class="ml-2">
                                {{ permission.name }}
                            </div>
                        </div>
                    </div>

                    <template v-if="v$.form.permissions.$error">
                        <ul class="mt-1">
                            <li v-for="(error, index) of v$.form.permissions.$errors"
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
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import TextInput from "@/Components/TextInput.vue";
import {useVuelidate} from '@vuelidate/core'
import {minLength, required, requiredIf} from '@/Utils/i18n-validators.js'
import InputLabel from "@/Components/InputLabel.vue";
import Checkbox from "@/Components/Checkbox.vue";
import CustomButton from "@/Components/CustomButton.vue";
import {reactive, toRefs} from "vue";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import {helper} from "@/Utils/helper.js";

const props = defineProps({
    roles: {
        type: Array,
        default: []
    },
    permissions: {
        type: Array,
        default: []
    }
});

const table = reactive({
    data: props.roles,
    columns: [
        'actions',
        'name',
        'permissions',
        'created_at',
        'updated_at'
    ],
    options: {
        headings: {
            actions: '',
            name: 'NOMBRE',
            permissions: 'PERMISOS',
            created_at: 'CREADO EL',
            updated_at: 'ACTUALIZADO EL'
        },
        clientSorting: false,
        uniqueKey: 'id',
        sortable: ['description'],
        templates: {
            created_at(h, row) {
                return helper.formatDate(row.created_at)
            },
            updated_at(h, row) {
                return helper.formatDate(row.updated_at)
            },
        }
    }
});

const modal = reactive({
    open: false,
    editMode: false,
    title: '',
    form: {
        id: '',
        name: '',
        permissions: []
    }
});

const rules = {
    form: {
        id: {
            requiredIf: requiredIf(modal.editMode)
        },
        name: {
            required
        },
        permissions: {
            required,
            minLength: minLength(1)
        },
    }
}

const v$ = useVuelidate(rules, toRefs(modal));

const create = () => {
    modal.open = true
    modal.title = 'Crear Rol'
}

const edit = (row) => {
    modal.open = true
    modal.editMode = true
    modal.title = 'Editar Rol'
    modal.form = {
        id: row.id,
        name: row.name,
        permissions: row.permissions.map(row => row.name)
    }
}

const closeModal = () => {
    modal.open = false
    modal.editMode = false
    modal.title = ''
    modal.form = {
        id: '',
        name: '',
        permissions: []
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
        axios.post(route('roles.store'), modal.form).then(resp => {
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
        axios.put(route('roles.update', modal.form.id), modal.form).then(resp => {
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
        title: '¿Eliminar Rol?',
        text: "¡Esta acción no es reversible!",
        showCancelButton: true,
        confirmButtonText: '¡Si, Eliminar!'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('roles.destroy', id)).then(resp => {
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
