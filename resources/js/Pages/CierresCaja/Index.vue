<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { usePermisos } from '@/Composables/usePermisos';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, View, Printer, Check } from '@element-plus/icons-vue';
import dayjs from 'dayjs';

const props = defineProps({
    cierres: Object,
    vendedores: Array,
    vendedoresConCajaAbierta: Array,
    filtros: Object
});

const { tienePermiso, tieneRol } = usePermisos();

// Filtros
const filtroEstado = ref(props.filtros?.estado || '');
const filtroVendedor = ref(props.filtros?.vendedor_id || '');

const aplicarFiltros = () => {
    router.get(route('cierres-caja.index'), {
        estado: filtroEstado.value,
        vendedor_id: filtroVendedor.value,
    }, { preserveState: true, replace: true });
};

// Formulario de Aprobación
const dialogAprobarVisible = ref(false);
const cierreAprobarId = ref(null);
const formAprobar = useForm({
    observacion_aprobacion: ''
});

const abrirAprobacion = (cierreId) => {
    cierreAprobarId.value = cierreId;
    formAprobar.reset();
    formAprobar.clearErrors();
    dialogAprobarVisible.value = true;
};

const confirmarAprobacion = () => {
    formAprobar.post(route('cierres-caja.aprobar', cierreAprobarId.value), {
        preserveScroll: true,
        onSuccess: () => {
            dialogAprobarVisible.value = false;
            ElMessage.success('Cierre de caja aprobado exitosamente.');
        }
    });
};

const verDetalle = (cierreId) => {
    router.visit(route('cierres-caja.show', cierreId));
};

const formatearFecha = (fecha) => {
    return dayjs(fecha).format('DD/MM/YYYY HH:mm');
};

const formatearMoneda = (valor) => {
    return Number(valor).toFixed(2) + ' Bs';
};
</script>

<template>
    <Head title="Cierres de Caja" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Cierres de Caja</h2>
        </template>

        <div class="mb-6 flex">
            <el-button v-if="tienePermiso('gestionar_dashboard_movimientos')" type="primary" :icon="Plus" @click="router.visit(route('cierres-caja.create'))">
                Nuevo Cierre
            </el-button>
        </div>

        <!-- Cards de Vendedores con Caja Abierta (Pendientes de Cierre) -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" v-if="tieneRol('administrador') && vendedoresConCajaAbierta && vendedoresConCajaAbierta.length > 0">
            <el-card v-for="vendedor in vendedoresConCajaAbierta" :key="`open-${vendedor.id}`" shadow="hover" class="border-l-4 border-l-yellow-500 bg-yellow-50/30">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="font-bold text-gray-800">{{ vendedor.nombre_completo }}</div>
                        <div class="text-xs text-gray-500">Cod: {{ vendedor.codigo }}</div>
                    </div>
                    <el-tag type="warning" size="small" effect="dark">PENDIENTE DE CIERRE</el-tag>
                </div>
                <div class="text-sm mt-3 mb-3" v-if="vendedor.aperturas_caja && vendedor.aperturas_caja.length > 0">
                    <div class="flex justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-500">Saldo Inicial:</span>
                        <span class="font-medium text-gray-700">{{ formatearMoneda(vendedor.aperturas_caja[0].saldo_inicial) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-500">Total Ventas:</span>
                        <span class="font-medium text-gray-700">{{ formatearMoneda(vendedor.total_ventas) }}</span>
                    </div>
                    <div class="flex justify-between py-1 mt-1 bg-gray-50 px-2 rounded font-bold text-gray-800">
                        <span>Saldo Esperado:</span>
                        <span>{{ formatearMoneda(vendedor.saldo_esperado) }}</span>
                    </div>
                </div>
                <div class="mt-auto pt-2 border-t border-gray-200">
                    <el-button type="warning" size="small" class="w-full" plain @click="router.visit(route('cierres-caja.create', { vendedor_id: vendedor.id }))">
                        Cerrar Caja
                    </el-button>
                </div>
            </el-card>
        </div>

        <!-- Filtros -->
        <el-card class="mb-6 shadow-sm">
            <div class="flex flex-wrap gap-4">
                <el-select v-model="filtroEstado" placeholder="Estado" clearable @change="aplicarFiltros" class="w-full md:w-48">
                    <el-option label="Pendiente" value="pendiente" />
                    <el-option label="Aprobado" value="aprobado" />
                </el-select>

                <el-select 
                    v-if="tieneRol('administrador')" 
                    v-model="filtroVendedor" 
                    placeholder="Vendedor" 
                    clearable 
                    @change="aplicarFiltros" 
                    class="w-full md:w-64"
                >
                    <el-option v-for="vend in vendedores" :key="vend.id" :label="vend.nombre_completo" :value="vend.id" />
                </el-select>
            </div>
        </el-card>

        <!-- Tabla -->
        <el-card class="shadow-sm">
            <el-table :data="cierres.data" border stripe style="width: 100%" empty-text="No hay cierres registrados">
                <el-table-column label="Apertura N°" width="100" align="center">
                    <template #default="scope">
                        <span class="font-bold">#{{ scope.row.cash_opening_id }}</span>
                    </template>
                </el-table-column>
                
                <el-table-column label="Vendedor" min-width="160">
                    <template #default="scope">
                        <span class="font-medium">{{ scope.row.apertura_caja?.usuario?.nombre_completo }}</span>
                    </template>
                </el-table-column>

                <el-table-column label="Fecha Cierre" width="150" align="center">
                    <template #default="scope">{{ formatearFecha(scope.row.fecha_hora_cierre) }}</template>
                </el-table-column>

                <el-table-column label="Esperado" width="120" align="right">
                    <template #default="scope">
                        <span class="font-bold text-gray-700">{{ formatearMoneda(scope.row.saldo_esperado) }}</span>
                    </template>
                </el-table-column>

                <el-table-column label="Entregado" width="120" align="right">
                    <template #default="scope">
                        <span class="font-bold text-blue-600">{{ formatearMoneda(scope.row.saldo_entregado) }}</span>
                    </template>
                </el-table-column>

                <el-table-column label="Diferencia" width="160" align="center">
                    <template #default="scope">
                        <div v-if="scope.row.sobrante > 0" class="text-green-600 font-semibold text-xs">
                            Sobrante: +{{ formatearMoneda(scope.row.sobrante) }}
                        </div>
                        <div v-if="scope.row.faltante > 0" class="text-red-600 font-semibold text-xs">
                            Faltante: -{{ formatearMoneda(scope.row.faltante) }}
                        </div>
                        <div v-if="scope.row.sobrante == 0 && scope.row.faltante == 0" class="text-gray-400 text-xs">
                            Cuadre Exacto
                        </div>
                    </template>
                </el-table-column>

                <el-table-column label="Estado" width="120" align="center">
                    <template #default="scope">
                        <el-tag 
                            :type="scope.row.status === 'aprobado' ? 'success' : 'warning'" 
                            effect="dark" 
                            size="small"
                        >
                            {{ scope.row.status.toUpperCase() }}
                        </el-tag>
                    </template>
                </el-table-column>

                <el-table-column label="Acciones" width="150" align="center" fixed="right">
                    <template #default="scope">
                        <el-button type="info" :icon="View" circle size="small" title="Ver Detalle" @click="verDetalle(scope.row.id)" />
                        
                        <a :href="route('cierres-caja.imprimir', scope.row.id)" target="_blank" class="mx-1">
                            <el-button type="primary" :icon="Printer" circle size="small" title="Imprimir" />
                        </a>

                        <el-button 
                            v-if="tienePermiso('aprobar_cierre_caja') && scope.row.status === 'pendiente'" 
                            type="success" 
                            :icon="Check" 
                            circle 
                            size="small" 
                            title="Aprobar Cierre" 
                            @click="abrirAprobacion(scope.row.id)" 
                        />
                    </template>
                </el-table-column>
            </el-table>

            <!-- Paginación -->
            <div class="mt-4 flex justify-end">
                <el-pagination
                    v-if="cierres.total > cierres.per_page"
                    background
                    layout="prev, pager, next"
                    :total="cierres.total"
                    :page-size="cierres.per_page"
                    :current-page="cierres.current_page"
                    @current-change="(val) => { if (cierres.links[val]) router.get(cierres.links[val].url, {}, { preserveState: true }) }"
                />
            </div>
        </el-card>

        <!-- Diálogo de Aprobación -->
        <el-dialog
            v-model="dialogAprobarVisible"
            title="Aprobar Cierre de Caja"
            width="500px"
            destroy-on-close
        >
            <el-alert
                title="Atención"
                type="warning"
                description="Al aprobar el cierre, la apertura de caja quedará bloqueada permanentemente. Ninguna venta o anulación adicional podrá realizarse en esta jornada."
                show-icon
                :closable="false"
                class="mb-4"
            />
            <el-form :model="formAprobar" label-position="top">
                <el-form-item label="Observación (Opcional)" :error="formAprobar.errors.observacion_aprobacion">
                    <el-input
                        v-model="formAprobar.observacion_aprobacion"
                        type="textarea"
                        rows="3"
                        placeholder="Nota o comentario sobre la aprobación..."
                        maxlength="500"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>

            <template #footer>
                <el-button @click="dialogAprobarVisible = false">Cancelar</el-button>
                <el-button 
                    type="success" 
                    :loading="formAprobar.processing"
                    @click="confirmarAprobacion"
                >
                    Aprobar Cierre
                </el-button>
            </template>
        </el-dialog>

    </PanelLayout>
</template>
