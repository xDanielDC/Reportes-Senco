<template>
    <AppLayout title="Usuarios del sistema">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Usuarios del sistema
                </h2>

            </div>
        </template>

        <template #actions>
            <PrimaryButton v-permission="'user.create'" class="ml-auto" type="button" @click="create">
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
                            <Link v-permission="'user.edit'"
                                  :href="route('users.show', row.id)"
                                  class="mr-2 inline-flex items-center px-2 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                <font-awesome-icon :icon="['far', 'pen-to-square']"/>
                            </Link>

                            <CustomButton v-permission="'user.destroy'" @click="destroy(row.id)">
                                <font-awesome-icon :icon="['far', 'trash-can']"/>
                            </CustomButton>
                        </div>
                    </template>

                    <template v-slot:roles="{row}">
                        <span v-for="role in row.roles"
                              class="badge badge-success">
                            {{ role.name }}
                        </span>
                    </template>

                    <template v-slot:permissions="{row}">
                        <span v-for="permission in row.permission_names"
                              class="badge badge-success">
                            {{ permission }}
                        </span>
                    </template>

                    <template v-slot:reports="{row}">
                        <span v-for="report in row.reports"
                              class="badge badge-success">
                            {{ report.name }}
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
                    <InputLabel value="Tipo"/>
                    <select
                        v-model="modal.form.type"
                        class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm mt-1 block w-full"
                        required>
                        <option disabled selected value="">Seleccione…</option>
                        <option value="customer">Cliente</option>
                        <option value="designer">Diseñador</option>
                    </select>
                </div>

                <div class="mt-4">
                    <InputLabel value="Nombre"/>
                    <TextInput
                        v-model="modal.form.name"
                        :class="{'border-red-500': v$.form.name.$error}"
                        autocomplete="off"
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
                    <InputLabel value="Usuario"/>
                    <TextInput
                        v-model="modal.form.username"
                        :class="{'border-red-500': v$.form.username.$error}"
                        autocomplete="off"
                        class="mt-1 block w-full"
                        required
                        type="text"
                    />
                    <template v-if="v$.form.username.$error">
                        <ul class="mt-1">
                            <li v-for="(error, index) of v$.form.username.$errors"
                                :key="index" class="text-red-500">
                                {{ error.$message }}
                            </li>
                        </ul>
                    </template>
                </div>

                <div class="mt-4">
                    <InputLabel value="Correo electrónico"/>
                    <TextInput
                        v-model="modal.form.email"
                        :class="{'border-red-500': v$.form.email.$error}"
                        autocomplete="off"
                        class="mt-1 block w-full"
                        required
                        type="email"
                    />
                    <template v-if="v$.form.email.$error">
                        <ul class="mt-1">
                            <li v-for="(error, index) of v$.form.email.$errors"
                                :key="index" class="text-red-500">
                                {{ error.$message }}
                            </li>
                        </ul>
                    </template>
                </div>

                <div v-if="modal.editMode" class="block mt-4">
                    <label class="flex items-center">
                        <Checkbox v-model:checked="modal.form.change_password" name="change_password"/>
                        <span class="ml-2 text-sm text-gray-600">Cambiar contraseña</span>
                    </label>
                </div>

                <template v-if="modal.form.change_password || !modal.editMode ">
                    <div class="mt-4">
                        <InputLabel value="Contraseña"/>
                        <TextInput
                            v-model="modal.form.password"
                            :class="{'border-red-500': v$.form.password.$error}"
                            autocomplete="off"
                            class="mt-1 block w-full"
                            required
                            type="password"
                        />
                        <template v-if="v$.form.password.$error">
                            <ul class="mt-1">
                                <li v-for="(error, index) of v$.form.password.$errors"
                                    :key="index" class="text-red-500">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>

                    <div class="mt-4">
                        <InputLabel value="Confirmar Contraseña"/>
                        <TextInput
                            v-model="modal.form.confirm_password"
                            :class="{'border-red-500': v$.form.confirm_password.$error}"
                            autocomplete="off"
                            class="mt-1 block w-full"
                            required
                            type="password"
                        />
                        <template v-if="v$.form.confirm_password.$error">
                            <ul class="mt-1">
                                <li v-for="(error, index) of v$.form.confirm_password.$errors"
                                    :key="index" class="text-red-500">
                                    {{ error.$message }}
                                </li>
                            </ul>
                        </template>
                    </div>
                </template>

                <div class="mt-4">
                    <InputLabel value="Reportes disponibles"/>
                    <div class="grid grid-cols-3 gap-5 mt-2">
                        <div v-for="report in reports" class="flex items-center">
                            <Checkbox v-model:checked="modal.form.reports" :value="report.id"/>
                            <div class="ml-2">
                                {{ report.name }}
                            </div>
                        </div>
                    </div>

                    <template v-if="v$.form.reports.$error">
                        <ul class="mt-1">
                            <li v-for="(error, index) of v$.form.reports.$errors"
                                :key="index" class="text-red-500">
                                {{ error.$message }}
                            </li>
                        </ul>
                    </template>
                </div>

                <div class="mt-4">
                    <InputLabel value="Roles Disponibles"/>
                    <div class="grid grid-cols-3 gap-5 mt-2">
                        <div v-for="role in roles" class="flex items-center">
                            <Checkbox v-model:checked="modal.form.roles" :value="role.name"/>
                            <div class="ml-2">
                                {{ role.name }}
                            </div>
                        </div>
                    </div>

                    <template v-if="v$.form.roles.$error">
                        <ul class="mt-1">
                            <li v-for="(error, index) of v$.form.roles.$errors"
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

                <PrimaryButton class="ml-3" @click="store">
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
import {email, minLength, required, requiredIf, sameAs} from '@/Utils/i18n-validators.js'
import InputLabel from "@/Components/InputLabel.vue";
import Checkbox from "@/Components/Checkbox.vue";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import CustomButton from "@/Components/CustomButton.vue";
import {Link} from "@inertiajs/vue3";
import {reactive, toRefs} from "vue";
import {helper} from "@/Utils/helper.js";

const props = defineProps({
    users: {
        type: Array,
        default: []
    },
    roles: {
        type: Array,
        default: []
    },
    permissions: {
        type: Array,
        default: []
    },
    reports: {
        type: Array,
        default: []
    },
});

const table = reactive({
    data: props.users,
    columns: [
        'actions',
        'name',
        'username',
        'email',
        'roles',
        'permissions',
        'reports',
        'created_at',
        'updated_at'
    ],
    options: {
        headings: {
            actions: '',
            name: 'NOMBRE',
            username: 'USUARIO',
            email: 'CORREO ELECTRÓNICO',
            roles: 'ROLES',
            permissions: 'PERMISOS',
            reports: 'REPORTES',
            created_at: 'CREADO EL',
            updated_at: 'ACTUALIZADO EL'
        },
        clientSorting: false,
        uniqueKey: 'id',
        sortable: ['name', 'username', 'email'],
    }
});

const modal = reactive({
    open: false,
    editMode: false,
    title: '',
    form: {
        id: '',
        type: '',
        name: '',
        username: '',
        email: '',
        change_password: false,
        password: '',
        confirm_password: '',
        reports: [],
        permissions: [],
        roles: []
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
        username: {
            required
        },
        email: {
            required,
            email
        },
        password: {
            requiredIf: requiredIf(modal.form.change_password || !modal.editMode),
            minLength: minLength(8)
        },
        confirm_password: {
            requiredIf: requiredIf(modal.form.change_password || !modal.editMode),
            sameAs: sameAs(modal.form.password),
            minLength: minLength(8)
        },
        reports: {
            minLength: minLength(1)
        },
        permissions: {
            requiredIf: requiredIf(modal.form.roles.length < 1),
            minLength: minLength(1)
        },
        roles: {
            requiredIf: requiredIf(modal.form.permissions.length < 1),
            minLength: minLength(1)
        },
    }
}

const v$ = useVuelidate(rules, toRefs(modal));

const create = () => {
    modal.open = true
    modal.title = 'Crear Usuario'
}

const closeModal = () => {
    modal.open = false
    modal.title = ''
    modal.form = {
        id: '',
        type: '',
        name: '',
        username: '',
        email: '',
        change_password: false,
        password: '',
        confirm_password: '',
        reports: [],
        permissions: [],
        roles: []
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
        axios.post(route('users.store'), modal.form).then(resp => {
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
        title: '¿Eliminar Usuario?',
        text: "¡Esta acción no es reversible!",
        showCancelButton: true,
        confirmButtonText: '¡Si, Eliminar!'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.delete(route('users.destroy', id)).then(resp => {
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
