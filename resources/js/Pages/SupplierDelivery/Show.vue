<template>
    <div>
        <Head title="Registro"/>
        <div class="min-h-screen bg-gray-100 ">
            <div class="py-10 content-center">
                <div class="max-w-7xl mx-auto">
                    <div class="grid grid-cols-2 gap-5 mb-10">
                        <div class="flex items-center px-4 py-4 box">
                            <div class="flex-none overflow-hidden rounded-md image-fit items-center justify-center text-slate-700">
                                <font-awesome-icon icon="user" size="2xl"/>
                            </div>
                            <div class="ml-4 mr-auto">
                                <div class="font-medium">{{ registry.supplier.name }}</div>
                                <div class="text-slate-500 text-xs mt-0.5">Razon Social</div>
                            </div>
                        </div>

                        <div class="flex items-center px-4 py-4 box">
                            <div class="flex-none overflow-hidden rounded-md image-fit items-center justify-center text-slate-700">
                                <font-awesome-icon icon="user" size="2xl"/>
                            </div>
                            <div class="ml-4 mr-auto">
                                <div class="font-medium">{{ registry.supplier.document }}</div>
                                <div class="text-slate-500 text-xs mt-0.5">NIT</div>
                            </div>
                        </div>
                    </div>

                    <v-client-table :columns="table.columns"
                                    :data="table.data"
                                    :options="table.options"
                                    class="overflow-y-auto w-full">

                        <template v-slot:actions="{row}">
                            <div class="text-center">
                                <CustomButton type="button" @click="openModal(row)">
                                    <font-awesome-icon class="mr-2" icon="plus"/>
                                    Registrar entrega
                                </CustomButton>

                            </div>
                        </template>
                    </v-client-table>
                </div>
            </div>

            <DialogModal :show="showModal.open" @close="closeModal" max-width="7xl">
                <template #title>
                    ORDEN DE COMPRA #{{ showModal.row.oc }}
                </template>

                <template #content>
                    <div class="overflow-y-auto">
                        <table class="table table-sm table-bordered">
                            <thead>
                            <tr class="text-center">
                                <th colspan="3" class="border-b">
                                    Producto
                                </th>
                                <th colspan="4" class="border-b">
                                    Cantidades
                                </th>
                                <th class="border-b" rowspan="2">
                                    Fecha entrega
                                </th>
                            </tr>
                            <tr>
                                <th></th>
                                <th>Código</th>
                                <th>Descripción</th>
                                <th>Solicitada</th>
                                <th>Entregada</th>
                                <th>Pendiente</th>
                                <th>Compromiso</th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr v-for="(item, index) in showModal.row.registries">
                                <td>
                                    <Checkbox v-model:checked="item.SELECTED" name="remember" />
                                </td>
                                <td>{{ item.ITEM }}</td>
                                <td>{{ item.PRODUCT_DESCRIPTION }} – {{ item.MEASUREMENT }}</td>
                                <td class="text-right">{{ parseFloat(item.REQUESTED_AMOUNT) }}</td>
                                <td class="text-right">{{ parseFloat(item.RECEIVED_AMOUNT) }}</td>
                                <td class="text-right">{{ parseFloat(item.PENDING_AMOUNT) }}</td>
                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           :class="{'border-red-500': f$.row.registries.$each.$response.$errors[index].COMMITTED_AMOUNT.length > 0}"
                                           v-model="item.COMMITTED_AMOUNT">

                                    <template v-if="f$.row.registries.$each.$response.$errors[index].COMMITTED_AMOUNT.length > 0">
                                        <ul class="mt-1">
                                            <li v-for="(error, index) of f$.row.registries.$each.$response.$errors[index].COMMITTED_AMOUNT" :key="index"
                                                class="text-red-600">
                                                {{ error.$message }}
                                            </li>
                                        </ul>
                                    </template>
                                </td>
                                <td>
                                    <LitePicker
                                        v-model="item.DELIVERY_DATE"
                                        :options="{
                                            autoApply: true,
                                            singleMode: true,
                                            numberOfColumns: 1,
                                            numberOfMonths: 1,
                                            showWeekNumbers: true,
                                            format: 'DD-MM-YYYY',
                                            lang: 'es-ES',
                                            minDate: item.DELIVERY_DATE
                                        }"
                                        class="form-control form-control-sm"
                                    />

                                    <template v-if="f$.row.registries.$each.$response.$errors[index].DELIVERY_DATE.length > 0">
                                        <ul class="mt-1">
                                            <li v-for="(error, index) of f$.row.registries.$each.$response.$errors[index].DELIVERY_DATE" :key="index"
                                                class="text-red-600">
                                                {{ error.$message }}
                                            </li>
                                        </ul>
                                    </template>
                                </td>
                            </tr>
                            </tbody>
                        </table>
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
        </div>
    </div>
</template>

<script setup>
import {reactive, toRefs} from "vue";
import {Head} from "@inertiajs/vue3";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import {
    helpers,
    maxLength,
    minLength,
    numeric,
    required,
} from "@/Utils/i18n-validators.js";
import {useVuelidate} from "@vuelidate/core";
import LitePicker from "@/CustomComponents/LitePicker/Main.vue"
import CustomButton from "@/Components/CustomButton.vue";
import DialogModal from "@/Components/DialogModal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import {helper} from "@/Utils/helper.js";
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    registry: Object
});

const supplier = reactive({
    isCorrect: false,
    document: ''
})

const rules = reactive({
    document: {
        numeric,
        required,
        minLength: minLength(9),
        maxLength: maxLength(9)
    },

    row: {
        registries: {
            required,
            minLength: minLength(1),
            $each: helpers.forEach({
                DELIVERY_DATE: {
                    required
                },
                COMMITTED_AMOUNT: {
                    required,
                    minValue: helpers.withMessage(
                        () => `No puede ser menor o igual a 0`,
                        ((COMMITTED_AMOUNT) => 0 <= COMMITTED_AMOUNT)
                    ),
                    maxValue: helpers.withMessage(
                        () => `No puede ser mayor a la cantidad pendiente`,
                        ((COMMITTED_AMOUNT, {PENDING_AMOUNT}) => COMMITTED_AMOUNT <= PENDING_AMOUNT)
                    )
                }
            })
        }
    }

});

const table = reactive({
    data: props.registry.orders,
    columns: [
        'oc',
        'delivery_date',
        'actions',
    ],
    options: {
        headings: {
            oc: 'OC',
            delivery_date: 'FECHA ENTREGA',
            actions: '',
        },
        clientSorting: false,
        uniqueKey: 'id',
        sortable: ['oc', 'delivery_date'],
    }
});

const showModal = reactive({
    open: false,
    row: {}
})

const closeModal = () => {
    showModal.open = false
    showModal.row = {}
}

const openModal = (row) => {
    showModal.open = true
    showModal.row = row
}

const v$ = useVuelidate(rules, toRefs(supplier));
const f$ = useVuelidate(rules, toRefs(showModal))

const store = () => {
    f$.value.$touch()

    if (f$.value.$invalid || !showModal.row.registries.some(obj => obj.SELECTED === true)) {
        Swal.fire({
            icon: 'error',
            title: '¡Whoops!',
            text: 'Verifica que toda la información este correctamente diligenciada',
            confirmButtonText: 'Aceptar'
        });
    }else {
        helper.Loading()

        axios.post(route('supplier-delivery.update'), showModal.row).then(resp => {
            table.data = resp.data

            Swal.fire({
                icon: 'error',
                title: '¡Éxito!',
                text: 'Registro actualizado con éxito',
                confirmButtonText: 'Aceptar'
            });
        }).catch(error => {
            Swal.fire({
                icon: 'error',
                title: '¡Whoops!',
                text: error.response.data,
                confirmButtonText: 'Aceptar'
            });
        })
    }
}

const checkSupplier = () => {
    v$.value.$touch()

    if (v$.value.$invalid) {
        Swal.fire({
            icon: 'error',
            title: '¡Whoops!',
            text: 'Verifica que toda la información este correctamente diligenciada',
            confirmButtonText: 'Aceptar'
        });
    } else {
        if (parseInt(props.registry.supplier.document) === parseInt(supplier.document)){
                supplier.isCorrect = true
        }else {
            Swal.fire({
                icon: 'error',
                title: '¡Whoops!',
                text: 'Información incorrecta',
                confirmButtonText: 'Aceptar'
            });
        }
    }


}
</script>
