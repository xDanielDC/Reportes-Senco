<template>
    <AppLayout title="Importación de reportes">
        <template #header>
            <div class="flex flex-row">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Importación de reportes
                </h2>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-5">
                        <div class="grid grid-cols-2 gap-5">
                            <div>
                                <TextInput
                                    v-model="form.group_id"
                                    type="text"
                                    class="mt-1 block w-full"
                                    :class="{'border-red-500': v$.form.group_id.$error}"
                                    placeholder="ID de grupo"
                                    autocomplete="off"
                                />
                            </div>
                            <PrimaryButton @click="getReports" class="text-center">
                                <font-awesome-icon icon="search" class="mr-2"/>
                                Consultar reportes
                            </PrimaryButton>
                        </div>

                        <div class="border-t mt-5">
                            <div class="overflow-hidden border-b border-gray-200 sm:rounded-lg mt-5">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        </th>

                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Nombre
                                        </th>

                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Reporte
                                        </th>

                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Dataset
                                        </th>
                                    </tr>
                                    </thead>

                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="item in reports">
                                            <td class="px-6 py-4 text-center text-sm font-medium">
                                                <Checkbox v-model:checked="form.selected" :value="item" :disabled="checkExist(item.id)"/>
                                            </td>

                                            <td class="px-6 py-4 text-left text-sm font-medium">
                                                {{ item.name }}
                                            </td>

                                            <td class="px-6 py-4 text-left text-sm font-medium">
                                                {{ item.id }}
                                            </td>
                                            <td class="px-6 py-4 text-left text-sm font-medium">
                                                {{ item.datasetId }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="border-t">
                        <div class="p-4">
                            <PrimaryButton @click="importReports" class="text-center" :disabled="form.selected.length === 0">
                                <font-awesome-icon icon="download" class="mr-2"/>
                                Importar reportes
                            </PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
<script>

import PrimaryButton from "@/Components/PrimaryButton.vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import {useVuelidate} from "@vuelidate/core";
import {minLength, required} from "@/Utils/i18n-validators.js";
import Checkbox from "@/Components/Checkbox.vue";
export default {
    setup () {
        return { v$: useVuelidate() }
    },

    props: {
        current_reports: Array
    },

    components: {
        Checkbox,
        PrimaryButton,
        AppLayout,
        InputLabel,
        TextInput
    },

    validations() {
        return {
            form: {
                group_id: {
                    required
                },
                selected: {
                    required,
                    minLength: minLength(1)
                }
            }
        }
    },

    data(){
        return {
            form: {
                group_id: '',
                selected: []
            },
            reports: []
        }
    },

    methods: {
        getReports(){
            this.v$.form.group_id.$touch()

            if (this.v$.form.group_id.$invalid){
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: 'Verifica que toda la información este correctamente diligenciada'
                });
            }else {
                axios.get(route('report.import.get-reports'), {
                    params: {
                        group_id: this.form.group_id
                    }
                }).then(resp => {
                    this.reports = resp.data
                }).catch(err => {
                    this.$swal({
                        icon: 'error',
                        title: 'Error',
                        text: err.response.data
                    });
                })
            }
        },

        importReports(){
            this.v$.form.selected.$touch()

            if (this.v$.form.selected.$invalid){
                this.$swal({
                    icon: 'error',
                    title: 'ERROR',
                    text: 'Verifica que toda la información este correctamente diligenciada'
                });
            }else {
                axios.post(route('report.import.store'), this.form).then(resp => {
                    this.$swal({
                        icon: 'success',
                        title: 'Importación exitosa',
                        text: "¡Los reportes seleccionados han importados correctamente!",
                        showCancelButton: false,
                        confirmButtonText: 'Aceptar'
                    })

                    this.form = {
                        group_id: '',
                        selected: []
                    }
                    this.reports = []
                    this.v$.form.$reset()
                }).catch(err => {
                    this.$swal({
                        icon: 'error',
                        title: 'Error',
                        text: err.response.data
                    });
                })
            }
        },

        checkExist(report_id){
            return this.current_reports.find(element => element === report_id) !== undefined
        }
    }
}

</script>
