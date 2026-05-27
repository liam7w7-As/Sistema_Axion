<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { Upload, Select, Document } from '@element-plus/icons-vue';

const props = defineProps({
    configuracion: Object
});

const form = useForm({
    nombre_sistema: props.configuracion.nombre_sistema || '',
    alias: props.configuracion.alias || '',
    logo: null,
    actividad: props.configuracion.actividad || '',
    moneda: props.configuracion.moneda || 'Bs',
    hora_inicio_admin: props.configuracion.hora_inicio_admin || '08:00',
    hora_fin_admin: props.configuracion.hora_fin_admin || '20:00',
    hora_inicio_vendedor: props.configuracion.hora_inicio_vendedor || '08:00',
    hora_fin_vendedor: props.configuracion.hora_fin_vendedor || '20:00',
    formato_impresion: props.configuracion.formato_impresion || 'termico',
    tamano_papel_thermal: props.configuracion.tamano_papel_thermal || '58mm'
});

const uploadRef = ref(null);
const logoPreview = ref(props.configuracion.logo ? `/storage/${props.configuracion.logo}` : null);

const handleLogoChange = (file) => {
    form.logo = file.raw;
    logoPreview.value = URL.createObjectURL(file.raw);
};

const submit = () => {
    form.post(route('configuracion.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // El layout se actualizará automáticamente gracias a HandleInertiaRequests
            form.logo = null;
        }
    });
};
</script>

<template>
    <Head title="Configuración del Sistema" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Configuración del Sistema</h2>
        </template>

        <div class="max-w-4xl mx-auto">
            <el-card class="shadow-sm">
                <template #header>
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-gray-700">Ajustes Generales (ERS 2.1.1)</span>
                    </div>
                </template>

                <el-form :model="form" label-position="top" @submit.prevent="submit" v-loading="form.processing">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Columna Izquierda: Datos Básicos -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-600 border-b pb-2">Información del Negocio</h3>
                            
                            <el-form-item label="Nombre del Sistema" :error="form.errors.nombre_sistema" required>
                                <el-input v-model="form.nombre_sistema" placeholder="Ej: Sistema de Telefonía Central" />
                            </el-form-item>

                            <el-form-item label="Alias (Nombre Corto)" :error="form.errors.alias" required>
                                <el-input v-model="form.alias" placeholder="Ej: SISTEF" maxlength="20" show-word-limit />
                            </el-form-item>

                            <el-form-item label="Actividad Comercial" :error="form.errors.actividad">
                                <el-input v-model="form.actividad" placeholder="Ej: Venta de accesorios y servicios de telefonía" />
                            </el-form-item>

                            <el-form-item label="Moneda" :error="form.errors.moneda" required>
                                <el-input v-model="form.moneda" placeholder="Ej: Bs o USD" class="w-1/2" />
                            </el-form-item>

                            <el-form-item label="Logo del Sistema" :error="form.errors.logo">
                                <div class="flex flex-col items-start gap-4">
                                    <div v-if="logoPreview" class="h-24 w-24 border rounded overflow-hidden shadow bg-white flex items-center justify-center">
                                        <img :src="logoPreview" alt="Logo preview" class="max-h-full max-w-full object-contain" />
                                    </div>
                                    <el-upload
                                        ref="uploadRef"
                                        action=""
                                        :auto-upload="false"
                                        :show-file-list="false"
                                        accept="image/jpeg,image/png,image/jpg"
                                        @change="handleLogoChange"
                                    >
                                        <template #trigger>
                                            <el-button type="primary" plain>Seleccionar Nuevo Logo</el-button>
                                        </template>
                                        <template #tip>
                                            <div class="el-upload__tip text-xs text-gray-500">Solo JPG/PNG. Máx 2MB. Se recomienda formato cuadrado.</div>
                                        </template>
                                    </el-upload>
                                </div>
                            </el-form-item>
                        </div>

                        <!-- Columna Derecha: Horarios y Formatos -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-medium text-gray-600 border-b pb-2">Horarios y Formatos</h3>

                            <div class="bg-blue-50 p-4 rounded-md mb-4 border border-blue-100">
                                <h4 class="font-semibold text-blue-800 text-sm mb-2">Horario Administrador</h4>
                                <div class="flex items-center gap-4">
                                    <el-form-item label="Inicio" class="mb-0 w-full" :error="form.errors.hora_inicio_admin">
                                        <el-time-select v-model="form.hora_inicio_admin" start="00:00" step="00:30" end="23:30" placeholder="Hora de inicio" class="w-full" />
                                    </el-form-item>
                                    <el-form-item label="Fin" class="mb-0 w-full" :error="form.errors.hora_fin_admin">
                                        <el-time-select v-model="form.hora_fin_admin" start="00:00" step="00:30" end="23:30" placeholder="Hora de fin" class="w-full" />
                                    </el-form-item>
                                </div>
                            </div>

                            <div class="bg-green-50 p-4 rounded-md mb-4 border border-green-100">
                                <h4 class="font-semibold text-green-800 text-sm mb-2">Horario Vendedor (Bloqueo de Caja)</h4>
                                <div class="flex items-center gap-4">
                                    <el-form-item label="Inicio" class="mb-0 w-full" :error="form.errors.hora_inicio_vendedor">
                                        <el-time-select v-model="form.hora_inicio_vendedor" start="00:00" step="00:30" end="23:30" placeholder="Hora de inicio" class="w-full" />
                                    </el-form-item>
                                    <el-form-item label="Fin" class="mb-0 w-full" :error="form.errors.hora_fin_vendedor">
                                        <el-time-select v-model="form.hora_fin_vendedor" start="00:00" step="00:30" end="23:30" placeholder="Hora de fin" class="w-full" />
                                    </el-form-item>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mt-6">
                                <el-form-item label="Formato de Impresión" :error="form.errors.formato_impresion">
                                    <el-select v-model="form.formato_impresion" class="w-full">
                                        <el-option label="Térmico (Ticket)" value="termico" />
                                        <el-option label="Estándar Carta" value="estandar_carta" />
                                    </el-select>
                                </el-form-item>

                                <el-form-item label="Ancho Papel (Térmico)" :error="form.errors.tamano_papel_thermal">
                                    <el-select v-model="form.tamano_papel_thermal" class="w-full" :disabled="form.formato_impresion !== 'termico'">
                                        <el-option label="58mm" value="58mm" />
                                        <el-option label="80mm" value="80mm" />
                                    </el-select>
                                </el-form-item>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end border-t pt-4">
                        <el-button type="primary" size="large" @click="submit" :loading="form.processing">
                            Guardar Configuración
                        </el-button>
                    </div>
                </el-form>
            </el-card>
        </div>
    </PanelLayout>
</template>
