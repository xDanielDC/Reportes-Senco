<template>
    <AppLayout title="Ordenes de compra">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Ordenes de compra
                </h2>
            </div>
        </template>

        <div class="py-10">
            <div class="max-w-8xl sm:px-4 lg:px-6">
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

                            <CustomButton type="button" class="ml-2" @click="sendMail(row)">
                                <font-awesome-icon class="mr-2" icon="envelope"/>
                                Enviar Notificación
                            </CustomButton>
                        </div>
                    </template>
                </v-client-table>

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
    </AppLayout>
</template>
<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import CustomButton from "@/Components/CustomButton.vue";
import {reactive, toRefs} from "vue";
import {useVuelidate} from "@vuelidate/core";
import {helper} from "@/Utils/helper.js";
import {helpers, minLength, required} from "@/Utils/i18n-validators.js";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import DialogModal from "@/Components/DialogModal.vue";
import Checkbox from "@/Components/Checkbox.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import LitePicker from "@/CustomComponents/LitePicker/Main.vue";

const props = defineProps({
    orders: Array
});

const table = reactive({
    data: props.orders,
    columns: [
        'oc',
        'name',
        'document',
        'actions',
    ],
    options: {
        headings: {
            oc: 'OC',
            name: 'PROVEEDOR',
            document: 'NIT',
            actions: '',
        },
        clientSorting: false,
        uniqueKey: 'id',
        sortable: ['oc', 'name', 'document'],
    }
});

const showModal = reactive({
    open: false,
    row: {}
})

const rules = reactive({
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

const closeModal = () => {
    showModal.open = false
    showModal.row = {}
}

const openModal = (row) => {
    showModal.open = true
    showModal.row = row
}

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

const sendMail = (row) => {
    Swal.fire({
        icon: 'question',
        title: '¿Enviar Notificación?',
        text: "Se le enviara una notificación al proveedor para que confirme la cantidad y fecha de entrega",
        showCancelButton: true,
        confirmButtonText: '¡Si, Enviar!',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            helper.Loading()

            axios.post(route('supplier-delivery.send-mail'), row).then(resp => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: 'Notificación enviada con éxito',
                    confirmButtonText: 'Aceptar'
                });
            }).catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: err.response.data
                });
            })
        }
    })
}


</script>
