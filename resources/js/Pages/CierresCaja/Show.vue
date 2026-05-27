<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { usePermisos } from '@/Composables/usePermisos';
import { ElMessage, ElMessageBox } from 'element-plus';
import { ArrowLeft, Check, Printer } from '@element-plus/icons-vue';
import dayjs from 'dayjs';

const props = defineProps({
    cierre: Object,
    resumen_secciones: Object,
});

const { tienePermiso } = usePermisos();

const formatearFecha = (fecha) => dayjs(fecha).format('DD/MM/YYYY HH:mm');
const formatearMoneda = (valor) => Number(valor || 0).toFixed(2) + ' Bs';

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

// Formulario de Aprobación rápida desde Show
const formAprobar = useForm({
    observacion_aprobacion: ''
});

const confirmarAprobacion = () => {
    ElMessageBox.prompt('Puede añadir una observación (opcional):', 'Aprobar Cierre', {
        confirmButtonText: 'Sí, Aprobar Definitivamente',
        cancelButtonText: 'Cancelar',
        inputType: 'textarea',
        inputPlaceholder: 'Nota sobre la aprobación...',
        inputErrorMessage: 'Máximo 500 caracteres',
        inputValidator: (val) => val && val.length > 500 ? 'Máximo 500 caracteres' : true
    }).then(({ value }) => {
        formAprobar.observacion_aprobacion = value || '';
        formAprobar.post(route('cierres-caja.aprobar', props.cierre.id), {
            onSuccess: () => {
                ElMessage.success('Cierre de caja aprobado exitosamente.');
            }
        });
    }).catch(() => {});
};
</script>

<template>
    <Head :title="'Cierre de Caja #' + cierre.id" />

    <PanelLayout>
        <template #titulo-pagina>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Detalle de Cierre de Caja</h2>
                <el-tag :type="cierre.status === 'aprobado' ? 'success' : 'warning'" effect="dark">
                    {{ cierre.status.toUpperCase() }}
                </el-tag>
            </div>
        </template>

        <div class="mb-6 flex justify-between items-center">
            <el-button :icon="ArrowLeft" @click="router.visit(route('cierres-caja.index'))" plain>Volver</el-button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Columna Izquierda: Datos del Cierre -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Info General -->
                <el-card class="shadow-sm">
                    <template #header>
                        <span class="font-semibold text-gray-700">Información General</span>
                    </template>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Cierre N°</p>
                            <p class="font-bold text-gray-800">#{{ cierre.id }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Apertura N°</p>
                            <p class="font-medium text-gray-800">#{{ cierre.cash_opening_id }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Vendedor</p>
                            <p class="font-medium text-gray-800">{{ cierre.apertura_caja.usuario.nombre_completo }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Apertura (Fecha)</p>
                            <p class="font-medium text-gray-800">{{ formatearFecha(cierre.apertura_caja.fecha_hora_apertura) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Cierre (Fecha)</p>
                            <p class="font-bold text-gray-800">{{ formatearFecha(cierre.fecha_hora_cierre) }}</p>
                        </div>
                        <div v-if="cierre.status === 'aprobado'">
                            <p class="text-sm text-gray-500">Aprobado Por</p>
                            <p class="font-bold text-green-600">{{ cierre.aprobado_por?.nombre_completo || 'Administrador' }}</p>
                        </div>
                    </div>
                </el-card>

                <!-- Resumen por Secciones -->
                <el-card class="shadow-sm">
                    <template #header>
                        <span class="font-semibold text-gray-700">Resumen por Secciones (Consolidado)</span>
                    </template>
                    <el-table :data="Object.entries(resumen_secciones).map(([k, v]) => ({seccion: formatearEtiquetaSeccion(k), monto: v}))" border stripe size="small">
                        <el-table-column prop="seccion" label="Sección / Categoría" />
                        <el-table-column label="Monto Consolidado" align="right" width="180">
                            <template #default="scope">
                                <span class="font-semibold" :class="scope.row.monto >= 0 ? 'text-gray-800' : 'text-red-600'">
                                    {{ formatearMoneda(scope.row.monto) }}
                                </span>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-card>

                <!-- Observaciones -->
                <div v-if="cierre.observacion" class="bg-yellow-50 p-4 border border-yellow-200 rounded-md">
                    <p class="text-xs font-bold text-yellow-800 uppercase mb-1">Observaciones</p>
                    <p class="text-sm text-yellow-900 whitespace-pre-wrap">{{ cierre.observacion }}</p>
                </div>

            </div>

            <!-- Columna Derecha: Totales y Acciones -->
            <div class="space-y-6">
                <!-- Tarjeta de Saldos -->
                <el-card class="shadow-sm" body-class="!p-4">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-gray-600 border-b pb-2">
                            <span>Saldo Inicial:</span>
                            <span>{{ formatearMoneda(cierre.saldo_inicial) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-gray-600 border-b pb-2">
                            <span>Total Ventas:</span>
                            <span class="text-green-600">+ {{ formatearMoneda(cierre.total_ventas) }}</span>
                        </div>
                        <div class="flex justify-between items-center text-gray-600 border-b pb-2">
                            <span>Total Movimientos:</span>
                            <span class="text-blue-600">{{ cierre.total_movimientos >= 0 ? '+' : '' }} {{ formatearMoneda(cierre.total_movimientos) }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-2">
                            <span class="font-bold text-gray-800">SALDO ESPERADO:</span>
                            <span class="text-xl font-black text-gray-800">{{ formatearMoneda(cierre.saldo_esperado) }}</span>
                        </div>
                        <div class="flex justify-between items-center bg-gray-100 p-2 rounded-md mt-2">
                            <span class="font-bold text-gray-800">SALDO ENTREGADO:</span>
                            <span class="text-xl font-black text-blue-600">{{ formatearMoneda(cierre.saldo_entregado) }}</span>
                        </div>

                        <!-- Diferencias -->
                        <div class="mt-4 pt-4 border-t border-dashed">
                            <p class="text-xs text-center text-gray-500 mb-2 font-bold tracking-widest">DIFERENCIAS</p>
                            <div v-if="cierre.sobrante == 0 && cierre.faltante == 0" class="text-center text-green-600 font-bold bg-green-50 p-2 rounded">
                                <el-icon><Check /></el-icon> Cuadre Exacto
                            </div>
                            <div v-else class="space-y-2">
                                <div v-if="cierre.sobrante > 0" class="flex justify-between items-center bg-green-50 p-2 rounded">
                                    <span class="text-green-800 font-bold">Sobrante:</span>
                                    <span class="text-green-600 font-bold">+ {{ formatearMoneda(cierre.sobrante) }}</span>
                                </div>
                                <div v-if="cierre.faltante > 0" class="flex justify-between items-center bg-red-50 p-2 rounded">
                                    <span class="text-red-800 font-bold">Faltante:</span>
                                    <span class="text-red-600 font-bold">- {{ formatearMoneda(cierre.faltante) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </el-card>

                <!-- Acciones -->
                <el-card class="shadow-sm">
                    <template #header>
                        <span class="text-sm font-semibold text-gray-700">Acciones</span>
                    </template>
                    
                    <div class="space-y-3">
                        <a :href="route('cierres-caja.imprimir', cierre.id)" target="_blank" class="block w-full">
                            <el-button type="primary" class="w-full" :icon="Printer">
                                Imprimir Reporte PDF
                            </el-button>
                        </a>

                        <el-button 
                            v-if="tienePermiso('aprobar_cierre_caja') && cierre.status === 'pendiente'" 
                            type="success" 
                            class="w-full" 
                            :icon="Check"
                            :loading="formAprobar.processing"
                            @click="confirmarAprobacion"
                        >
                            Aprobar Cierre (Admin)
                        </el-button>

                        <el-alert
                            v-if="cierre.status === 'aprobado'"
                            title="Cierre Aprobado"
                            type="success"
                            description="Este cierre ya fue aprobado y auditado. No se pueden realizar cambios."
                            show-icon
                            :closable="false"
                        />
                    </div>
                </el-card>

            </div>

        </div>
    </PanelLayout>
</template>
