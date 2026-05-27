<script setup>
import { computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { ElMessage } from 'element-plus';
import { ArrowLeft, Wallet, Document, Money } from '@element-plus/icons-vue';
import dayjs from 'dayjs';

const props = defineProps({
    error_mensaje: String,
    apertura: Object,
    total_ventas: Number,
    total_movimientos: Number,
    saldo_esperado: Number,
    resumen_secciones: Object,
});

const formatearFecha = (fecha) => dayjs(fecha).format('DD/MM/YYYY HH:mm');
const formatearMoneda = (valor) => Number(valor || 0).toFixed(2) + ' Bs';

const form = useForm({
    cash_opening_id: props.apertura?.id || null,
    saldo_entregado: '',
    observacion: ''
});

// Cálculos visuales reactivos en el frontend
const diferenciaCalculada = computed(() => {
    const entregado = parseFloat(form.saldo_entregado) || 0;
    return entregado - (props.saldo_esperado || 0);
});

const sobranteVisual = computed(() => diferenciaCalculada.value > 0 ? diferenciaCalculada.value : 0);
const faltanteVisual = computed(() => diferenciaCalculada.value < 0 ? Math.abs(diferenciaCalculada.value) : 0);

const registrarCierre = () => {
    if (form.saldo_entregado === '' || parseFloat(form.saldo_entregado) < 0) {
        ElMessage.warning('Debe ingresar un saldo entregado válido.');
        return;
    }

    form.post(route('cierres-caja.store'), {
        onSuccess: () => {
            ElMessage.success('Cierre de caja registrado exitosamente.');
        }
    });
};

const formatearEtiquetaSeccion = (clave) => {
    const nombres = {
        tarjetas_unidad: 'Tarjetas por Unidad',
        tarjetas_mayor: 'Tarjetas por Mayor',
        recuperaciones: 'Recuperaciones',
        chips: 'Chips',
        recargas: 'Recargas',
        megas: 'Megas',
        servicios_digitales: 'Servicios Digitales',
        banca_digital: 'Banca Digital',
        servicio_tecnico: 'Servicio Técnico',
        efectivo_monedas: 'Efectivo / Monedas'
    };
    return nombres[clave] || clave.replace('_', ' ');
};
</script>

<template>
    <Head title="Nuevo Cierre de Caja" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Registrar Cierre de Caja</h2>
        </template>

        <div class="mb-6">
            <el-button :icon="ArrowLeft" @click="router.visit(route('cierres-caja.index'))" plain>Volver</el-button>
        </div>

        <template v-if="error_mensaje">
            <el-alert
                :title="error_mensaje"
                type="error"
                description="Consulte con su administrador si considera que esto es un error."
                show-icon
                :closable="false"
                class="mt-4"
            />
        </template>

        <template v-else>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Columna Izquierda: Información de Apertura y Totales Computados -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Datos de la Apertura -->
                    <el-card class="shadow-sm">
                        <template #header>
                            <div class="flex items-center gap-2 text-gray-700 font-semibold">
                                <el-icon><Document /></el-icon> Información de Jornada
                            </div>
                        </template>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Apertura N°</p>
                                <p class="font-bold text-gray-800">#{{ apertura.id }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Vendedor</p>
                                <p class="font-bold text-gray-800">{{ apertura.usuario.nombre_completo }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Fecha de Apertura</p>
                                <p class="font-medium text-gray-800">{{ formatearFecha(apertura.fecha_hora_apertura) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Saldo Inicial</p>
                                <p class="font-bold text-green-600">{{ formatearMoneda(apertura.saldo_inicial) }}</p>
                            </div>
                        </div>
                    </el-card>

                    <!-- Resumen por Secciones (Generado a partir de seller_movements) -->
                    <el-card class="shadow-sm">
                        <template #header>
                            <div class="flex items-center gap-2 text-gray-700 font-semibold">
                                <el-icon><Wallet /></el-icon> Resumen por Secciones (Movimientos)
                            </div>
                        </template>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div v-for="(monto, clave) in resumen_secciones" :key="clave" class="p-3 border rounded-md bg-gray-50">
                                <p class="text-xs text-gray-500 mb-1 capitalize">{{ formatearEtiquetaSeccion(clave) }}</p>
                                <p class="font-bold" :class="monto >= 0 ? 'text-gray-800' : 'text-red-500'">
                                    {{ formatearMoneda(monto) }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-md flex justify-between items-center">
                            <span class="text-sm font-semibold text-blue-800">NETO MOVIMIENTOS:</span>
                            <span class="text-lg font-bold text-blue-800">{{ formatearMoneda(total_movimientos) }}</span>
                        </div>
                    </el-card>

                    <!-- Resumen Ventas -->
                    <el-card class="shadow-sm">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-gray-700">TOTAL VENTAS COMPLETADAS:</span>
                            <span class="text-xl font-bold text-green-600">{{ formatearMoneda(total_ventas) }}</span>
                        </div>
                    </el-card>
                </div>

                <!-- Columna Derecha: Formulario de Cierre y Diferencias -->
                <div>
                    <el-card class="shadow-sm border-2 border-blue-200">
                        <template #header>
                            <div class="text-center">
                                <p class="text-sm text-gray-500 uppercase tracking-widest">Saldo Esperado en Caja</p>
                                <h2 class="text-4xl font-black text-gray-800 my-2">{{ formatearMoneda(saldo_esperado) }}</h2>
                                <p class="text-xs text-gray-400">Incluye: Saldo Inicial + Movimientos + Ventas</p>
                            </div>
                        </template>

                        <el-form :model="form" label-position="top">
                            
                            <el-form-item label="Saldo Físico Entregado (Obligatorio)" :error="form.errors.saldo_entregado" required>
                                <el-input 
                                    v-model="form.saldo_entregado" 
                                    type="number" 
                                    step="0.01" 
                                    min="0"
                                    size="large"
                                    placeholder="0.00"
                                >
                                    <template #prefix><el-icon><Money /></el-icon></template>
                                    <template #append>Bs</template>
                                </el-input>
                            </el-form-item>

                            <!-- Cuadro Visual de Diferencias -->
                            <div class="bg-gray-50 p-4 rounded-md mb-4 border">
                                <p class="text-xs text-center text-gray-500 mb-2">DIFERENCIA CALCULADA</p>
                                
                                <div v-if="form.saldo_entregado === ''" class="text-center text-gray-400 text-sm">
                                    Ingrese el saldo entregado
                                </div>
                                <div v-else-if="diferenciaCalculada === 0" class="text-center text-green-600 font-bold">
                                    <el-icon class="mr-1"><Check /></el-icon> Cuadre Exacto
                                </div>
                                <div v-else class="space-y-2">
                                    <div class="flex justify-between items-center text-green-600 font-bold opacity-50" :class="{'opacity-100': sobranteVisual > 0}">
                                        <span>Sobrante:</span>
                                        <span>+ {{ formatearMoneda(sobranteVisual) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-red-600 font-bold opacity-50" :class="{'opacity-100': faltanteVisual > 0}">
                                        <span>Faltante:</span>
                                        <span>- {{ formatearMoneda(faltanteVisual) }}</span>
                                    </div>
                                </div>
                            </div>

                            <el-form-item label="Observaciones (Opcional)" :error="form.errors.observacion">
                                <el-input 
                                    v-model="form.observacion" 
                                    type="textarea" 
                                    rows="3" 
                                    placeholder="Detalles sobre faltantes, sobrantes u otros incidentes..." 
                                />
                            </el-form-item>

                            <el-button 
                                type="primary" 
                                size="large" 
                                class="w-full !font-bold" 
                                :loading="form.processing"
                                @click="registrarCierre"
                            >
                                Registrar Cierre
                            </el-button>

                        </el-form>
                    </el-card>
                </div>

            </div>
        </template>
    </PanelLayout>
</template>
