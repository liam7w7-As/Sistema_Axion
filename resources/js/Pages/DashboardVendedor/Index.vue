<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { usePermisos } from '@/Composables/usePermisos';
import { ElMessage } from 'element-plus';
import { Plus, Wallet, TrendCharts, Money, DocumentChecked, CircleCloseFilled } from '@element-plus/icons-vue';
import dayjs from 'dayjs';

const props = defineProps({
    apertura_activa: Object,
    vendedor_nombre: String,
    resumen: Object,
    movimientos_detalle: Array,
    total_movimientos: Number,
    saldo_actual: Number,
    cierre_aprobado: Boolean
});

const page = usePage();
const { tieneRol } = usePermisos();

// Formulario Modal para un Movimiento Individual
const dialogVisible = ref(false);
const form = useForm({
    cash_opening_id: props.apertura_activa?.id || null,
    seccion: '',
    cantidad: null,
    monto: 0,
    observacion: ''
});

const tituloSeccion = ref('');

const secciones = [
    { key: 'tarjetas_unidad', label: 'Tarjetas por Unidad', desc: 'Ventas unitarias de tarjetas' },
    { key: 'tarjetas_mayor', label: 'Tarjetas por Mayor', desc: 'Venta por paquetes' },
    { key: 'recuperaciones', label: 'Recuperaciones', desc: 'Recuperación de líneas/chips' },
    { key: 'chips', label: 'Chips', desc: 'Venta de chips' },
    { key: 'recargas', label: 'Recargas al Paso', desc: 'Recargas de saldo' },
    { key: 'megas', label: 'Megas / Paquetes', desc: 'Carga de paquetes de datos' },
    { key: 'servicios_digitales', label: 'Servicios Digitales', desc: 'Netflix, Spotify, etc.' },
    { key: 'banca_digital', label: 'Banca Digital', desc: 'Depósitos y retiros' },
    { key: 'servicio_tecnico', label: 'Servicio Técnico', desc: 'Reparaciones y accesorios' },
    { key: 'efectivo_monedas', label: 'Efectivo / Monedas', desc: 'Sencillo o ingresos extra' },
];

const abrirRegistroMovimiento = (seccionKey, seccionLabel) => {
    if (props.cierre_aprobado) return;
    
    tituloSeccion.value = seccionLabel;
    form.reset();
    form.clearErrors();
    form.cash_opening_id = props.apertura_activa?.id;
    form.seccion = seccionKey;
    form.cantidad = 1;
    form.monto = 0;
    dialogVisible.value = true;
};

const guardarMovimiento = () => {
    form.post(route('dashboard-vendedor.store_movimiento'), {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            ElMessage.success('Movimiento registrado correctamente');
        }
    });
};

const formatearFecha = (fecha) => {
    return dayjs(fecha).format('DD/MM/YYYY HH:mm');
};

const formatearMoneda = (valor) => {
    return Number(valor).toFixed(2) + ' Bs';
};
</script>

<template>
    <Head title="Dashboard Vendedor" />

    <PanelLayout>
        <template #titulo-pagina>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Dashboard de Movimientos</h2>
                <el-tag v-if="vendedor_nombre" type="info" effect="plain">{{ vendedor_nombre }}</el-tag>
            </div>
        </template>

        <!-- Bloqueo si no hay apertura activa -->
        <template v-if="!apertura_activa">
            <el-alert
                title="Apertura de Caja Requerida"
                type="error"
                description="No tienes una apertura de caja activa asignada a tu usuario. Contacta al administrador para que realice la apertura de caja."
                show-icon
                :closable="false"
                class="mb-6"
            >
                <template #icon>
                    <el-icon :size="24"><CircleCloseFilled /></el-icon>
                </template>
            </el-alert>
            
            <div class="flex justify-center items-center h-64 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                <div class="text-center text-gray-500">
                    <el-icon :size="48" class="mb-2 text-gray-300"><Wallet /></el-icon>
                    <p>Módulos bloqueados hasta nueva apertura.</p>
                </div>
            </div>
        </template>

        <!-- Contenido principal si hay apertura activa -->
        <template v-else>
            <!-- Alerta si ya está cerrado -->
            <el-alert
                v-if="cierre_aprobado"
                title="Caja Cerrada y Aprobada"
                type="warning"
                description="El cierre de caja ha sido aprobado. El panel se encuentra en modo Solo Lectura y no puedes registrar más movimientos."
                show-icon
                :closable="false"
                class="mb-6"
            />

            <!-- Tarjetas de Resumen (Top Metrics) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Saldo Inicial -->
                <el-card shadow="hover" body-class="!p-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                            <el-icon :size="24"><Money /></el-icon>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Saldo Inicial (Efectivo)</p>
                            <h3 class="text-2xl font-bold text-gray-900">{{ formatearMoneda(apertura_activa.saldo_inicial) }}</h3>
                        </div>
                    </div>
                </el-card>

                <!-- Total Movimientos -->
                <el-card shadow="hover" body-class="!p-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg bg-purple-100 text-purple-600">
                            <el-icon :size="24"><TrendCharts /></el-icon>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Movimientos</p>
                            <h3 class="text-2xl font-bold text-gray-900">{{ formatearMoneda(total_movimientos) }}</h3>
                        </div>
                    </div>
                </el-card>

                <!-- Saldo Actual Calculado -->
                <el-card shadow="hover" body-class="!p-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg bg-green-100 text-green-600">
                            <el-icon :size="24"><Wallet /></el-icon>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Saldo Actual Estimado</p>
                            <h3 class="text-2xl font-bold text-green-600">{{ formatearMoneda(saldo_actual) }}</h3>
                        </div>
                    </div>
                </el-card>

                <!-- Estado -->
                <el-card shadow="hover" body-class="!p-4">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-lg bg-gray-100 text-gray-600">
                            <el-icon :size="24"><DocumentChecked /></el-icon>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-500">Apertura</p>
                            <h3 class="text-sm font-bold text-gray-900">{{ formatearFecha(apertura_activa.fecha_hora_apertura) }}</h3>
                            <span class="text-xs text-green-600 font-semibold" v-if="!cierre_aprobado">CAJA ACTIVA</span>
                            <span class="text-xs text-red-600 font-semibold" v-else>CERRADA</span>
                        </div>
                    </div>
                </el-card>
            </div>

            <!-- Grid de Secciones de Ingreso de Movimientos -->
            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-800">Registrar Movimientos por Sección</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
                <el-card 
                    v-for="sec in secciones" 
                    :key="sec.key" 
                    shadow="hover" 
                    body-class="!p-4 flex flex-col h-full cursor-pointer hover:bg-gray-50 transition-colors"
                    @click="abrirRegistroMovimiento(sec.key, sec.label)"
                    :class="{'opacity-75 cursor-not-allowed': cierre_aprobado}"
                >
                    <div class="flex-grow">
                        <h4 class="font-semibold text-gray-800 mb-1 leading-tight">{{ sec.label }}</h4>
                        <p class="text-xs text-gray-500 mb-3">{{ sec.desc }}</p>
                    </div>
                    <div class="mt-auto border-t pt-3">
                        <div class="flex justify-between items-end">
                            <span class="text-xs text-gray-500">Acumulado:</span>
                            <span class="font-bold text-indigo-600">{{ formatearMoneda(resumen[sec.key] || 0) }}</span>
                        </div>
                        <el-button 
                            type="primary" 
                            plain 
                            size="small" 
                            class="w-full mt-3" 
                            :icon="Plus"
                            :disabled="cierre_aprobado"
                        >
                            Añadir
                        </el-button>
                    </div>
                </el-card>
            </div>

            <!-- Historial Reciente de Movimientos Detallados -->
            <el-card class="shadow-sm" header="Historial de Movimientos de la Sesión">
                <el-table :data="movimientos_detalle" border stripe style="width: 100%" height="300" empty-text="Aún no has registrado movimientos hoy.">
                    <el-table-column prop="created_at" label="Hora" width="100">
                        <template #default="scope">{{ dayjs(scope.row.created_at).format('HH:mm') }}</template>
                    </el-table-column>
                    <el-table-column prop="seccion" label="Sección" width="180">
                        <template #default="scope">
                            <span class="capitalize">{{ scope.row.seccion.replace('_', ' ') }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="cantidad" label="Cantidad" width="90" align="center" />
                    <el-table-column prop="monto" label="Monto (Bs)" width="120" align="right">
                        <template #default="scope">
                            <span class="font-medium text-green-600">+ {{ formatearMoneda(scope.row.monto) }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column prop="observacion" label="Observación" min-width="150" show-overflow-tooltip />
                </el-table>
            </el-card>

            <!-- Diálogo Formulario Nuevo Movimiento -->
            <el-dialog
                v-model="dialogVisible"
                :title="`Registrar en: ${tituloSeccion}`"
                width="450px"
                destroy-on-close
            >
                <el-form :model="form" label-position="top" @submit.prevent="guardarMovimiento">
                    <div class="grid grid-cols-2 gap-4">
                        <el-form-item label="Cantidad (Opcional)" :error="form.errors.cantidad">
                            <el-input-number v-model="form.cantidad" :min="1" class="w-full" controls-position="right" placeholder="Ej. 1" />
                        </el-form-item>

                        <el-form-item label="Monto Total a Sumar" :error="form.errors.monto" required>
                            <el-input-number v-model="form.monto" :min="0" :precision="2" :step="1" class="w-full" controls-position="right" />
                        </el-form-item>
                    </div>

                    <el-form-item label="Observación" :error="form.errors.observacion">
                        <el-input v-model="form.observacion" type="textarea" rows="2" placeholder="Detalle adicional..." />
                    </el-form-item>
                </el-form>

                <template #footer>
                    <span class="dialog-footer">
                        <el-button @click="dialogVisible = false">Cancelar</el-button>
                        <el-button type="primary" :loading="form.processing" @click="guardarMovimiento">
                            Registrar Monto
                        </el-button>
                    </span>
                </template>
            </el-dialog>

        </template>
    </PanelLayout>
</template>
