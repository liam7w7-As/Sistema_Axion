<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { usePermisos } from '@/Composables/usePermisos';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Search, View, Printer, CircleCloseFilled } from '@element-plus/icons-vue';
import dayjs from 'dayjs';

const props = defineProps({
    ventas: Object,
    filtros: Object
});

const { tienePermiso } = usePermisos();

// Filtros
const search = ref(props.filtros?.search || '');
const filtroEstado = ref(props.filtros?.estado || '');
const filtroTipoPago = ref(props.filtros?.tipo_pago || '');
const filtroFecha = ref(props.filtros?.fecha || '');

const aplicarFiltros = () => {
    router.get(route('ventas.index'), {
        search: search.value,
        estado: filtroEstado.value,
        tipo_pago: filtroTipoPago.value,
        fecha: filtroFecha.value,
    }, { preserveState: true, replace: true });
};

// Formulario de Anulación
const dialogAnularVisible = ref(false);
const ventaAnularId = ref(null);
const formAnular = useForm({
    motivo_anulacion: ''
});

const abrirAnulacion = (ventaId) => {
    ventaAnularId.value = ventaId;
    formAnular.reset();
    formAnular.clearErrors();
    dialogAnularVisible.value = true;
};

const confirmarAnulacion = () => {
    ElMessageBox.confirm(
        '¿Está completamente seguro de anular esta venta? Se revertirá el stock de los productos.',
        'Confirmar Anulación',
        {
            confirmButtonText: 'Sí, anular venta',
            cancelButtonText: 'Cancelar',
            type: 'warning',
        }
    ).then(() => {
        formAnular.post(route('ventas.anular', ventaAnularId.value), {
            preserveScroll: true,
            onSuccess: () => {
                dialogAnularVisible.value = false;
                ElMessage.success('Venta anulada correctamente');
            }
        });
    }).catch(() => {});
};

const verDetalle = (ventaId) => {
    router.visit(route('ventas.show', ventaId));
};

const formatearFecha = (fecha) => {
    return dayjs(fecha).format('DD/MM/YYYY HH:mm');
};

const formatearMoneda = (valor) => {
    return Number(valor).toFixed(2) + ' Bs';
};
</script>

<template>
    <Head title="Ventas" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Registro de Ventas</h2>
        </template>

        <div class="mb-6 flex">
            <el-button type="primary" :icon="Plus" @click="router.visit(route('ventas.create'))">
                Nueva Venta
            </el-button>
        </div>

        <!-- Filtros -->
        <el-card class="mb-6 shadow-sm">
            <div class="flex flex-wrap gap-4">
                <el-input
                    v-model="search"
                    placeholder="Buscar por código (V-000001)..."
                    :prefix-icon="Search"
                    clearable
                    @input="aplicarFiltros"
                    class="w-full md:w-64"
                />

                <el-select v-model="filtroEstado" placeholder="Estado" clearable @change="aplicarFiltros" class="w-full md:w-40">
                    <el-option label="Completada" value="completada" />
                    <el-option label="Anulada" value="anulada" />
                    <el-option label="Pendiente" value="pendiente" />
                </el-select>

                <el-select v-model="filtroTipoPago" placeholder="Tipo Pago" clearable @change="aplicarFiltros" class="w-full md:w-40">
                    <el-option label="Efectivo" value="efectivo" />
                    <el-option label="Transferencia" value="transferencia" />
                    <el-option label="QR" value="qr" />
                    <el-option label="Mixto" value="mixto" />
                </el-select>

                <el-date-picker
                    v-model="filtroFecha"
                    type="date"
                    placeholder="Filtrar por fecha"
                    format="DD/MM/YYYY"
                    value-format="YYYY-MM-DD"
                    clearable
                    @change="aplicarFiltros"
                    class="w-full md:w-48"
                />
            </div>
        </el-card>

        <!-- Tabla -->
        <el-card class="shadow-sm">
            <el-table :data="ventas.data" border stripe style="width: 100%" empty-text="No hay ventas registradas">
                <el-table-column prop="codigo" label="Código" width="120" />
                
                <el-table-column prop="fecha_hora" label="Fecha / Hora" width="160">
                    <template #default="scope">{{ formatearFecha(scope.row.fecha_hora) }}</template>
                </el-table-column>

                <el-table-column label="Vendedor" width="160">
                    <template #default="scope">
                        <span class="font-medium">{{ scope.row.usuario?.nombre_completo }}</span>
                    </template>
                </el-table-column>

                <el-table-column label="Ítems" width="80" align="center">
                    <template #default="scope">
                        <el-tag size="small" type="info">{{ scope.row.items?.length || 0 }}</el-tag>
                    </template>
                </el-table-column>

                <el-table-column label="Detalle" min-width="220" show-overflow-tooltip>
                    <template #default="scope">
                        <span class="text-xs text-gray-600">
                            <template v-for="(item, index) in scope.row.items" :key="index">
                                <strong>{{ item.cantidad }}x</strong> {{ item.producto_servicio?.nombre || 'Desconocido' }}<template v-if="index < scope.row.items.length - 1">, </template>
                            </template>
                        </span>
                    </template>
                </el-table-column>

                <el-table-column prop="total" label="Total" width="120" align="right">
                    <template #default="scope">
                        <span class="font-bold text-green-600">{{ formatearMoneda(scope.row.total) }}</span>
                    </template>
                </el-table-column>

                <el-table-column prop="tipo_pago" label="Tipo Pago" width="120" align="center">
                    <template #default="scope">
                        <el-tag size="small">{{ scope.row.tipo_pago.toUpperCase() }}</el-tag>
                    </template>
                </el-table-column>

                <el-table-column prop="status" label="Estado" width="120" align="center">
                    <template #default="scope">
                        <el-tag 
                            :type="scope.row.status === 'completada' ? 'success' : (scope.row.status === 'anulada' ? 'danger' : 'warning')" 
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
                        <el-button 
                            v-if="tienePermiso('anular_ventas') && scope.row.status === 'completada'" 
                            type="danger" 
                            :icon="CircleCloseFilled" 
                            circle 
                            size="small" 
                            title="Anular Venta" 
                            @click="abrirAnulacion(scope.row.id)" 
                        />
                    </template>
                </el-table-column>
            </el-table>

            <!-- Paginación -->
            <div class="mt-4 flex justify-end">
                <el-pagination
                    v-if="ventas.total > ventas.per_page"
                    background
                    layout="prev, pager, next"
                    :total="ventas.total"
                    :page-size="ventas.per_page"
                    :current-page="ventas.current_page"
                    @current-change="(val) => { if (ventas.links[val]) router.get(ventas.links[val].url, {}, { preserveState: true }) }"
                />
            </div>
        </el-card>

        <!-- Diálogo de Anulación -->
        <el-dialog
            v-model="dialogAnularVisible"
            title="Anular Venta"
            width="500px"
            destroy-on-close
        >
            <el-alert
                title="Atención"
                type="warning"
                description="Al anular esta venta, el stock de los productos físicos será revertido automáticamente."
                show-icon
                :closable="false"
                class="mb-4"
            />
            <el-form :model="formAnular" label-position="top">
                <el-form-item label="Motivo de Anulación (mínimo 10 caracteres)" :error="formAnular.errors.motivo_anulacion" required>
                    <el-input
                        v-model="formAnular.motivo_anulacion"
                        type="textarea"
                        rows="3"
                        placeholder="Describa el motivo por el cual se anula esta venta..."
                        maxlength="255"
                        show-word-limit
                    />
                </el-form-item>
            </el-form>

            <template #footer>
                <el-button @click="dialogAnularVisible = false">Cancelar</el-button>
                <el-button 
                    type="danger" 
                    :loading="formAnular.processing" 
                    :disabled="formAnular.motivo_anulacion.length < 10"
                    @click="confirmarAnulacion"
                >
                    Anular Venta
                </el-button>
            </template>
        </el-dialog>

    </PanelLayout>
</template>
