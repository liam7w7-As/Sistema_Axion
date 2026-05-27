<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePermisos } from '@/Composables/usePermisos';
import { ElMessage } from 'element-plus';

const props = defineProps({
    modelValue: Boolean,
    productoEditar: {
        type: Object,
        default: null
    },
    // Opcional: Para forzar una sección de reporte cuando se crea desde el Dashboard
    seccionPredefinida: {
        type: String,
        default: ''
    }
});

const emit = defineEmits(['update:modelValue', 'saved']);

const { puedeVerGanancias } = usePermisos();

const dialogVisible = ref(props.modelValue);
const modoEdicion = ref(false);
const itemEditarId = ref(null);
const fileList = ref([]);

const form = useForm({
    tipo: 'producto',
    nombre: '',
    descripcion: '',
    operador: '',
    categoria: '',
    unidad_venta: '',
    seccion_reporte: '',
    estado: 'activo',
    precio_compra: null,
    precio_venta: null,
    tipo_ganancia: 'fija',
    ganancia: 0,
    comision: null,
    imagen: null,
});

// Watcher para calcular ganancia reactivamente
watch([() => form.tipo, () => form.precio_compra, () => form.precio_venta, () => form.tipo_ganancia, () => form.comision], () => {
    if (form.tipo === 'producto') {
        const venta = parseFloat(form.precio_venta) || 0;
        const compra = parseFloat(form.precio_compra) || 0;
        form.ganancia = Math.max(0, venta - compra);
    } else {
        const venta = parseFloat(form.precio_venta) || 0;
        const comision = parseFloat(form.comision) || 0;
        
        if (form.tipo_ganancia === 'porcentaje') {
            form.ganancia = venta * (comision / 100);
        } else if (form.tipo_ganancia === 'fija') {
            form.ganancia = comision;
        } else {
            form.ganancia = 0;
        }
    }
});

// Sincronizar v-model:modelValue con dialogVisible
watch(() => props.modelValue, (val) => {
    dialogVisible.value = val;
    if (val) {
        if (props.productoEditar) {
            modoEdicion.value = true;
            itemEditarId.value = props.productoEditar.id;
            Object.assign(form, {
                tipo: props.productoEditar.tipo || 'producto',
                nombre: props.productoEditar.nombre || '',
                descripcion: props.productoEditar.descripcion || '',
                operador: props.productoEditar.operador || '',
                categoria: props.productoEditar.categoria || '',
                unidad_venta: props.productoEditar.unidad_venta || '',
                seccion_reporte: props.productoEditar.seccion_reporte || '',
                estado: props.productoEditar.estado || 'activo',
                precio_compra: props.productoEditar.precio_compra,
                precio_venta: props.productoEditar.precio_venta,
                tipo_ganancia: props.productoEditar.tipo_ganancia || 'fija',
                ganancia: props.productoEditar.ganancia,
                comision: props.productoEditar.comision,
                imagen: null
            });
        } else {
            modoEdicion.value = false;
            itemEditarId.value = null;
            form.reset();
            form.clearErrors();
            if (props.seccionPredefinida) {
                form.categoria = props.seccionPredefinida;
            }
        }
        fileList.value = [];
    }
});

watch(dialogVisible, (val) => {
    emit('update:modelValue', val);
});

const handleFileChange = (uploadFile) => {
    form.imagen = uploadFile.raw;
};

const handleFileRemove = () => {
    form.imagen = null;
};

const guardarProducto = () => {
    if (!form.nombre) {
        ElMessage.warning('El nombre es obligatorio.');
        return;
    }
    if (!form.categoria) {
        ElMessage.warning('La categoría es obligatoria.');
        return;
    }
    if (form.precio_venta === null || form.precio_venta === '' || form.precio_venta === undefined) {
        ElMessage.warning('El precio de venta es obligatorio.');
        return;
    }

    if (form.tipo === 'producto') {
        if (form.precio_compra === null || form.precio_compra === '' || form.precio_compra === undefined) {
            ElMessage.warning('El precio de compra es obligatorio para productos.');
            return;
        }
    }

    const datos = {
        ...form.data(),
        precio_compra: form.precio_compra ?? 0,
        precio_venta: form.precio_venta ?? 0,
        ganancia: form.ganancia ?? 0,
        comision: form.comision ?? 0,
    };

    if (modoEdicion.value) {
        form.transform(() => ({
            ...datos,
            _method: 'PUT',
        })).post(route('productos-servicios.update', itemEditarId.value), {
            preserveScroll: true,
            onSuccess: (page) => {
                dialogVisible.value = false;
                emit('saved');
            }
        });
    } else {
        form.transform(() => datos).post(route('productos-servicios.store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                dialogVisible.value = false;
                emit('saved');
            }
        });
    }
};
</script>

<template>
    <el-dialog
        v-model="dialogVisible"
        :title="modoEdicion ? 'Editar Registro' : 'Nuevo Registro'"
        width="600px"
        destroy-on-close
    >
        <el-form :model="form" label-position="top">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <el-form-item label="Tipo" :error="form.errors.tipo" required>
                    <el-radio-group v-model="form.tipo">
                        <el-radio-button label="producto">Producto</el-radio-button>
                        <el-radio-button label="servicio">Servicio</el-radio-button>
                    </el-radio-group>
                </el-form-item>

                <el-form-item label="Estado" :error="form.errors.estado" required>
                    <el-switch
                        v-model="form.estado"
                        active-value="activo"
                        inactive-value="inactivo"
                        active-text="Activo"
                        inactive-text="Inactivo"
                    />
                </el-form-item>
            </div>

            <el-form-item label="Nombre" :error="form.errors.nombre" required>
                <el-input v-model="form.nombre" placeholder="Ej. Tarjeta $10, Chip 4G..." />
            </el-form-item>

            <el-form-item label="Descripción" :error="form.errors.descripcion">
                <el-input v-model="form.descripcion" type="textarea" rows="2" placeholder="Detalles adicionales..." />
            </el-form-item>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <el-form-item label="Operador" :error="form.errors.operador">
                    <el-select v-model="form.operador" placeholder="Seleccione" clearable class="w-full">
                        <el-option label="Entel" value="Entel" />
                        <el-option label="Viva" value="Viva" />
                        <el-option label="Tigo" value="Tigo" />
                        <el-option label="Otro" value="Otro" />
                    </el-select>
                </el-form-item>

                <el-form-item label="Categoría (Sección ERS)" :error="form.errors.categoria" required>
                    <el-select v-model="form.categoria" placeholder="Seleccione" class="w-full">
                        <template v-if="form.tipo === 'producto'">
                            <el-option label="Tarjetas por Unidad" value="tarjetas_unidad" />
                            <el-option label="Tarjetas por Mayor" value="tarjetas_mayor" />
                            <el-option label="Chips" value="chips" />
                            <el-option label="Efectivo / Monedas" value="efectivo_monedas" />
                            <el-option label="Accesorios" value="accesorios" />
                        </template>
                        <template v-else>
                            <el-option label="Recuperaciones" value="recuperaciones" />
                            <el-option label="Recargas al Paso" value="recargas" />
                            <el-option label="Megas" value="megas" />
                            <el-option label="Servicios Digitales" value="servicios_digitales" />
                            <el-option label="Banca Digital" value="banca_digital" />
                            <el-option label="Servicio Técnico" value="servicio_tecnico" />
                        </template>
                    </el-select>
                </el-form-item>

                <el-form-item label="Unidad Venta" :error="form.errors.unidad_venta" v-if="form.tipo === 'producto'">
                    <el-input v-model="form.unidad_venta" placeholder="Ej. Pza, Paquete..." />
                </el-form-item>
            </div>

            <!-- Sección Financiera -->
            <el-divider content-position="left">Precios y Ganancias</el-divider>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <el-form-item label="Precio Venta (Bs)" :error="form.errors.precio_venta" required>
                    <el-input v-model="form.precio_venta" type="number" step="any" min="0" placeholder="Ej. 10.00" class="w-full">
                        <template #prefix>Bs</template>
                    </el-input>
                </el-form-item>

                <!-- Si es Producto -->
                <template v-if="form.tipo === 'producto'">
                    <el-form-item label="Precio Compra (Bs)" :error="form.errors.precio_compra" required>
                        <el-input v-model="form.precio_compra" type="number" step="any" min="0" placeholder="Ej. 8.50" class="w-full">
                            <template #prefix>Bs</template>
                        </el-input>
                    </el-form-item>
                    
                    <el-form-item label="Ganancia Calculada" :error="form.errors.ganancia" v-if="puedeVerGanancias()">
                        <el-input :model-value="form.ganancia" type="number" disabled class="w-full">
                            <template #prefix>Bs</template>
                        </el-input>
                    </el-form-item>
                </template>

                <!-- Si es Servicio -->
                <template v-else>
                    <el-form-item label="Tipo Ganancia" :error="form.errors.tipo_ganancia" v-if="puedeVerGanancias()">
                        <el-select v-model="form.tipo_ganancia" placeholder="Seleccione" class="w-full">
                            <el-option label="Comisión Fija" value="fija" />
                            <el-option label="Porcentaje (%)" value="porcentaje" />
                            <el-option label="Ninguna" value="ninguna" />
                        </el-select>
                    </el-form-item>

                    <el-form-item label="Comisión" :error="form.errors.comision" v-if="puedeVerGanancias() && form.tipo_ganancia !== 'ninguna'">
                        <el-input v-model="form.comision" type="number" step="any" min="0" :placeholder="form.tipo_ganancia === 'porcentaje' ? 'Ej. 15' : 'Ej. 5.00'" class="w-full">
                            <template #prefix>{{ form.tipo_ganancia === 'porcentaje' ? '%' : 'Bs' }}</template>
                        </el-input>
                    </el-form-item>

                    <el-form-item label="Ganancia Calculada" v-if="puedeVerGanancias()">
                        <el-input :model-value="form.ganancia" type="number" disabled class="w-full">
                            <template #prefix>Bs</template>
                        </el-input>
                    </el-form-item>
                </template>
            </div>

            <el-form-item label="Imagen del Producto" :error="form.errors.imagen">
                <el-upload
                    class="upload-demo"
                    action="#"
                    :auto-upload="false"
                    :on-change="handleFileChange"
                    :on-remove="handleFileRemove"
                    :limit="1"
                    :file-list="fileList"
                    accept="image/jpeg,image/png,image/jpg"
                >
                    <el-button type="primary" plain>Seleccionar Archivo</el-button>
                    <template #tip>
                        <div class="el-upload__tip text-gray-500 text-xs mt-1">
                            JPG/PNG menor a 2MB.
                        </div>
                    </template>
                </el-upload>
            </el-form-item>
        </el-form>

        <template #footer>
            <span class="dialog-footer">
                <el-button @click="dialogVisible = false">Cancelar</el-button>
                <el-button type="primary" :loading="form.processing" @click="guardarProducto">
                    Guardar
                </el-button>
            </span>
        </template>
    </el-dialog>
</template>
