<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { usePermisos } from '@/Composables/usePermisos';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Plus, Delete, Search } from '@element-plus/icons-vue';
import dayjs from 'dayjs';

const props = defineProps({
    movimientos: Object, // Viene paginado
    productos: Array,
    filtros: Object
});

const { tienePermiso, puedeVerGanancias } = usePermisos();

// Filtros
const tabActivo = ref(props.filtros?.tipo_inventario || 'fisico');
const search = ref(props.filtros?.search || '');

const aplicarFiltros = () => {
    router.get(route('inventario.index'), {
        search: search.value,
        tipo_inventario: tabActivo.value,
    }, { preserveState: true, replace: true });
};

// Paginación
const cambiarPagina = (url) => {
    if (url) {
        router.get(url, {}, { preserveState: true });
    }
};

// Formulario de Movimiento
const dialogVisible = ref(false);

const form = useForm({
    tipo_inventario: tabActivo.value,
    product_service_id: '',
    cantidad_ingreso: null,
    precio_compra: null,
    tipo_movimiento: 'ingreso',
    observacion: ''
});

// Cuando selecciona producto, auto-llenar el precio de compra referencial si puede ver ganancias
const alSeleccionarProducto = (id) => {
    const prod = props.productos.find(p => p.id === id);
    if (prod && puedeVerGanancias()) {
        form.precio_compra = prod.precio_compra;
    }
};

const abrirDialogo = (tipoMovimiento = 'ingreso') => {
    form.reset();
    form.clearErrors();
    form.tipo_inventario = tabActivo.value;
    form.tipo_movimiento = tipoMovimiento;
    form.cantidad_ingreso = null;
    form.precio_compra = null;
    dialogVisible.value = true;
};

const registrarMovimiento = () => {
    form.post(route('inventario.store'), {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            ElMessage.success('Movimiento registrado correctamente');
        }
    });
};

const confirmarEliminacion = (id) => {
    ElMessageBox.confirm(
        '¿Está seguro de eliminar este registro? Esto NO restaurará el stock del producto de forma automática.',
        'Eliminar Registro',
        {
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            type: 'danger',
        }
    ).then(() => {
        router.delete(route('inventario.destroy', id), {
            preserveScroll: true,
            onSuccess: () => ElMessage.success('Registro eliminado')
        });
    }).catch(() => {});
};

const formatearFecha = (fecha) => {
    return dayjs(fecha).format('DD/MM/YYYY HH:mm');
};

const formatearMoneda = (valor) => {
    return Number(valor).toFixed(2) + ' Bs';
};
</script>

<template>
    <Head title="Control de Inventario" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Control de Inventario</h2>
        </template>

        <div v-if="tienePermiso('gestionar_inventario')" class="mb-6 flex gap-2">
            <el-button type="success" :icon="Plus" @click="abrirDialogo('ingreso')">Ingreso</el-button>
            <el-button type="danger" :icon="Plus" @click="abrirDialogo('egreso')">Egreso</el-button>
            <el-button type="warning" :icon="Plus" @click="abrirDialogo('ajuste')">Ajuste</el-button>
        </div>

        <!-- Pestañas de Tipo de Inventario -->
        <el-card class="mb-4 shadow-sm">
            <el-tabs v-model="tabActivo" @tab-change="aplicarFiltros">
                <el-tab-pane label="Inventario Físico (Telefonía y Accesorios)" name="fisico"></el-tab-pane>
                <el-tab-pane label="Inventario Digital (Recargas, etc.)" name="digital"></el-tab-pane>
            </el-tabs>

            <div class="mt-2 mb-4">
                <el-input
                    v-model="search"
                    placeholder="Buscar producto..."
                    :prefix-icon="Search"
                    clearable
                    @input="aplicarFiltros"
                    class="w-full md:w-80"
                />
            </div>

            <!-- Tabla de Historial -->
            <el-table :data="movimientos.data" border stripe style="width: 100%" empty-text="No hay movimientos registrados">
                <el-table-column prop="fecha_hora" label="Fecha/Hora" width="150">
                    <template #default="scope">{{ formatearFecha(scope.row.fecha_hora) }}</template>
                </el-table-column>
                
                <el-table-column label="Producto/Servicio" min-width="180">
                    <template #default="scope">
                        <span class="font-medium">{{ scope.row.producto_servicio?.nombre }}</span>
                        <el-tag size="small" type="info" class="ml-2">{{ scope.row.producto_servicio?.operador }}</el-tag>
                    </template>
                </el-table-column>

                <el-table-column prop="tipo_movimiento" label="Movimiento" width="110" align="center">
                    <template #default="scope">
                        <el-tag v-if="scope.row.tipo_movimiento === 'ingreso'" type="success" effect="dark">INGRESO</el-tag>
                        <el-tag v-else-if="scope.row.tipo_movimiento === 'egreso'" type="danger" effect="dark">EGRESO</el-tag>
                        <el-tag v-else type="warning" effect="dark">AJUSTE</el-tag>
                    </template>
                </el-table-column>
                
                <el-table-column prop="cantidad_ingreso" label="Cant." width="80" align="center" />
                <el-table-column prop="stock_actual" label="Stock Act." width="100" align="center" />
                
                <el-table-column v-if="puedeVerGanancias()" prop="precio_compra" label="P. Compra" width="110" align="right">
                    <template #default="scope">{{ formatearMoneda(scope.row.precio_compra) }}</template>
                </el-table-column>

                <el-table-column prop="observacion" label="Observación" min-width="150" show-overflow-tooltip />

                <el-table-column label="Acciones" width="80" align="center" fixed="right" v-if="tienePermiso('gestionar_inventario')">
                    <template #default="scope">
                        <el-button type="danger" :icon="Delete" circle size="small" @click="confirmarEliminacion(scope.row.id)" />
                    </template>
                </el-table-column>
            </el-table>

            <!-- Paginación -->
            <div class="mt-4 flex justify-end">
                <el-pagination
                    v-if="movimientos.total > movimientos.per_page"
                    background
                    layout="prev, pager, next"
                    :total="movimientos.total"
                    :page-size="movimientos.per_page"
                    :current-page="movimientos.current_page"
                    @current-change="(val) => cambiarPagina(movimientos.links[val].url)"
                />
            </div>
        </el-card>

        <!-- Diálogo Formulario Movimiento -->
        <el-dialog
            v-model="dialogVisible"
            :title="form.tipo_movimiento === 'ingreso' ? 'Registrar Ingreso' : (form.tipo_movimiento === 'egreso' ? 'Registrar Egreso' : 'Ajuste de Inventario')"
            width="500px"
            destroy-on-close
        >
            <el-form :model="form" label-position="top">
                <el-form-item label="Producto/Servicio" :error="form.errors.product_service_id" required>
                    <el-select 
                        v-model="form.product_service_id" 
                        filterable 
                        placeholder="Buscar producto..." 
                        class="w-full"
                        @change="alSeleccionarProducto"
                    >
                        <el-option
                            v-for="prod in productos"
                            :key="prod.id"
                            :label="`${prod.nombre} (${prod.operador || 'Sin Operador'}) - Stock: ${prod.stock_actual}`"
                            :value="prod.id"
                        />
                    </el-select>
                </el-form-item>

                <div class="grid grid-cols-2 gap-4">
                    <el-form-item 
                        :label="form.tipo_movimiento === 'ajuste' ? 'Nuevo Stock Real' : 'Cantidad'" 
                        :error="form.errors.cantidad_ingreso" 
                        required
                    >
                        <el-input v-model="form.cantidad_ingreso" type="number" min="1" placeholder="Ej: 10" class="w-full">
                            <template #prefix>#</template>
                        </el-input>
                    </el-form-item>

                    <el-form-item label="Precio de Compra" :error="form.errors.precio_compra" v-if="puedeVerGanancias() && form.tipo_movimiento === 'ingreso'">
                        <el-input v-model="form.precio_compra" type="number" step="any" min="0" placeholder="Ej: 5.50" class="w-full">
                            <template #prefix>Bs</template>
                        </el-input>
                    </el-form-item>
                </div>

                <el-form-item label="Observaciones" :error="form.errors.observacion">
                    <el-input v-model="form.observacion" type="textarea" rows="3" placeholder="Motivo del movimiento, factura, etc." />
                </el-form-item>
            </el-form>

            <template #footer>
                <span class="dialog-footer">
                    <el-button @click="dialogVisible = false">Cancelar</el-button>
                    <el-button type="primary" :loading="form.processing" @click="registrarMovimiento">
                        Confirmar Movimiento
                    </el-button>
                </span>
            </template>
        </el-dialog>

    </PanelLayout>
</template>
