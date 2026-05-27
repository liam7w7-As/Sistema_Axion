<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { usePermisos } from '@/Composables/usePermisos';
import { ArrowLeft, DataBoard } from '@element-plus/icons-vue';
import dayjs from 'dayjs';

const props = defineProps({
    venta: Object
});

const { puedeVerGanancias } = usePermisos();

const formatearFecha = (fecha) => {
    return dayjs(fecha).format('DD/MM/YYYY HH:mm:ss');
};

const formatearMoneda = (valor) => {
    return Number(valor).toFixed(2) + ' Bs';
};

const volverAlListado = () => {
    router.visit(route('ventas.index'));
};

const volverAlDashboard = () => {
    router.visit(route('dashboard'));
};
</script>

<template>
    <Head :title="'Venta ' + venta.codigo" />

    <PanelLayout>
        <template #titulo-pagina>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Detalle de Venta</h2>
                <el-tag :type="venta.status === 'completada' ? 'success' : 'danger'" effect="dark">
                    {{ venta.status.toUpperCase() }}
                </el-tag>
            </div>
        </template>

        <div class="mb-6 flex gap-3">
            <el-button :icon="ArrowLeft" @click="volverAlListado" plain>Volver a Ventas</el-button>
            <el-button :icon="DataBoard" @click="volverAlDashboard" type="primary" plain>Volver al Dashboard</el-button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Info Principal -->
            <div class="lg:col-span-2 space-y-4">
                <el-card class="shadow-sm">
                    <template #header>
                        <span class="font-semibold text-lg">{{ venta.codigo }}</span>
                    </template>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-500">Fecha y Hora</p>
                            <p class="font-medium">{{ formatearFecha(venta.fecha_hora) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Vendedor</p>
                            <p class="font-medium">{{ venta.usuario?.nombre_completo }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Tipo de Pago</p>
                            <el-tag>{{ venta.tipo_pago.toUpperCase() }}</el-tag>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Apertura de Caja</p>
                            <p class="font-medium">#{{ venta.cash_opening_id }}</p>
                        </div>
                    </div>

                    <div v-if="venta.observacion" class="mb-4">
                        <p class="text-sm text-gray-500">Observación</p>
                        <p class="font-medium text-gray-700">{{ venta.observacion }}</p>
                    </div>

                    <div v-if="venta.motivo_anulacion" class="mb-4">
                        <el-alert
                            title="Motivo de Anulación"
                            type="error"
                            :description="venta.motivo_anulacion"
                            show-icon
                            :closable="false"
                        />
                    </div>
                </el-card>

                <!-- Tabla de Ítems -->
                <el-card class="shadow-sm">
                    <template #header>
                        <span class="font-semibold">Detalle de Productos</span>
                    </template>
                    <el-table :data="venta.items" border stripe style="width: 100%">
                        <el-table-column label="Producto / Servicio" min-width="200">
                            <template #default="scope">
                                <span class="font-medium">{{ scope.row.producto_servicio?.nombre }}</span>
                                <span v-if="scope.row.producto_servicio?.operador" class="text-xs text-gray-500 ml-1">({{ scope.row.producto_servicio?.operador }})</span>
                            </template>
                        </el-table-column>
                        <el-table-column prop="cantidad" label="Cant." width="80" align="center" />
                        <el-table-column label="P. Unit." width="110" align="right">
                            <template #default="scope">{{ formatearMoneda(scope.row.precio_venta) }}</template>
                        </el-table-column>
                        <el-table-column label="Subtotal" width="120" align="right">
                            <template #default="scope">
                                <span class="font-bold text-green-600">{{ formatearMoneda(scope.row.subtotal) }}</span>
                            </template>
                        </el-table-column>
                    </el-table>
                </el-card>
            </div>

            <!-- Resumen lateral -->
            <div>
                <el-card class="shadow-sm" body-class="!p-4">
                    <div class="text-center mb-4">
                        <p class="text-sm text-gray-500">Total de la Venta</p>
                        <h2 class="text-3xl font-bold text-green-600">{{ formatearMoneda(venta.total) }}</h2>
                    </div>
                    <el-divider />
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Ítems:</span>
                            <span class="font-medium">{{ venta.items?.length }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Pago:</span>
                            <span class="font-medium">{{ venta.tipo_pago.toUpperCase() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Estado:</span>
                            <el-tag :type="venta.status === 'completada' ? 'success' : 'danger'" size="small" effect="dark">
                                {{ venta.status.toUpperCase() }}
                            </el-tag>
                        </div>
                    </div>
                </el-card>

                <!-- Botón de Impresión TCPDF -->
                <el-card class="shadow-sm mt-4">
                    <template #header>
                        <span class="text-sm text-gray-500">Impresión</span>
                    </template>
                    <a :href="route('ventas.imprimir', venta.id)" target="_blank" class="block w-full">
                        <el-button type="primary" class="w-full">
                            🖨️ Imprimir Nota de Entrega
                        </el-button>
                    </a>
                    <p class="text-xs text-gray-400 mt-2 text-center">Genera PDF de 58mm para impresión térmica.</p>
                </el-card>
            </div>
        </div>

    </PanelLayout>
</template>
