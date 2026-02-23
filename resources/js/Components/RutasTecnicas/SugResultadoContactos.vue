<template>
    <div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nombre Contacto</label>
                <div class="relative">
                    <input
                        type="text"
                        :value="searchTerm"
                        @input="searchTerm = $event.target.value"
                        @focus="mostrarSugerencias = true"
                        @blur="cerrarSugerenciasDelay"
                        placeholder="Escribir o seleccionar..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    />
                    
                    <!-- Dropdown de sugerencias -->
                    <div v-if="mostrarSugerencias && sugerencias.length > 0" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                        <div
                            v-for="(sugerencia, index) in sugerenciasFiltradas"
                            :key="index"
                            @click="seleccionar(sugerencia)"
                            class="px-4 py-2 hover:bg-indigo-50 cursor-pointer border-b last:border-b-0 text-sm"
                        >
                            <div class="font-medium text-gray-900">{{ sugerencia.nomContacto }}</div>
                            <div class="text-xs text-gray-500">{{ sugerencia.telContacto }}</div>
                        </div>
                    </div>
                    
                    <!-- Mensaje cuando no hay sugerencias -->
                    <div v-else-if="mostrarSugerencias && sugerencias.length === 0 && !cargando" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                        <div class="px-4 py-3 text-sm text-gray-500 text-center">
                            No hay contactos anteriores
                        </div>
                    </div>
                    
                    <!-- Loading -->
                    <div v-if="cargando" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg">
                        <div class="px-4 py-3 text-sm text-gray-500 text-center">
                            Cargando...
                        </div>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Teléfono</label>
                <input
                    type="tel"
                    :value="telefono"
                    @input="emit('update:modelValueTelefono', $event.target.value)"
                    placeholder="Teléfono"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    modelValueNombre: {
        type: String,
        default: ''
    },
    modelValueTelefono: {
        type: String,
        default: ''
    },
    clienteId: {
        type: String,
        required: true
    },
    direccion: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['update:modelValueNombre', 'update:modelValueTelefono', 'seleccionar']);

const searchTerm = ref(props.modelValueNombre);
const telefono = ref(props.modelValueTelefono);
const sugerencias = ref([]);
const mostrarSugerencias = ref(false);
const cargando = ref(false);

const sugerenciasFiltradas = computed(() => {
    if (!searchTerm.value) {
        return sugerencias.value;
    }
    
    return sugerencias.value.filter(s => 
        s.nomContacto?.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
        s.telContacto?.includes(searchTerm.value)
    );
});

const cargarSugerencias = async () => {
    if (!props.direccion || !props.clienteId) {
        sugerencias.value = [];
        return;
    }
    
    cargando.value = true;
    
    try {
        const url = route('rutas-tecnicas.contacto-recomendado', props.clienteId) + 
                   `?direccion=${encodeURIComponent(props.direccion)}`;
        
        const response = await axios.get(url);
        sugerencias.value = response.data.sugerencias || [];
    } catch (error) {
        console.error('Error al cargar sugerencias:', error);
        sugerencias.value = [];
    } finally {
        cargando.value = false;
    }
};

const seleccionar = (sugerencia) => {
    emit('update:modelValueNombre', sugerencia.nomContacto);
    emit('update:modelValueTelefono', sugerencia.telContacto);
    emit('seleccionar', sugerencia);
    searchTerm.value = sugerencia.nomContacto;
    telefono.value = sugerencia.telContacto;
    mostrarSugerencias.value = false;
};

const cerrarSugerenciasDelay = () => {
    setTimeout(() => {
        mostrarSugerencias.value = false;
    }, 200);
};

// Cargar sugerencias cuando cambia la dirección
watch(() => props.direccion, () => {
    cargarSugerencias();
}, { immediate: true });

// Sincronizar los valores del input con los props
watch(() => props.modelValueNombre, (newVal) => {
    searchTerm.value = newVal;
});

watch(() => props.modelValueTelefono, (newVal) => {
    telefono.value = newVal;
});

// Emitir cambios del search term
watch(searchTerm, (newVal) => {
    emit('update:modelValueNombre', newVal);
});
</script>
