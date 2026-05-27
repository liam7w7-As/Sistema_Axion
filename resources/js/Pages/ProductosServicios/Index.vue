<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import ProductoFormModal from '@/Components/ProductoFormModal.vue';
import { usePermisos } from '@/Composables/usePermisos';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Search, Plus, Edit, Delete } from '@element-plus/icons-vue';

const props = defineProps({
    productos: Array,
    filtros: Object
});

const { tienePermiso, puedeVerGanancias } = usePermisos();

// Filtros de búsqueda
const search = ref(props.filtros?.search || '');
const filtroTipo = ref(props.filtros?.tipo || '');
const filtroOperador = ref(props.filtros?.operador || '');
const filtroEstado = ref(props.filtros?.estado || '');

const aplicarFiltros = () => {
    router.get(route('productos-servicios.index'), {
        search: search.value,
        tipo: filtroTipo.value,
        operador: filtroOperador.value,
        estado: filtroEstado.value
    }, { preserveState: true, replace: true });
};

// Diálogo y formulario
const dialogVisible = ref(false);
const modoEdicion = ref(false);
const itemEditarId = ref(null);
const fileList = ref([]); // Para el componente Upload de Element Plus

const form = useForm({
    tipo: 'producto',
    nombre: '',
    descripcion: '',
    operador: '',
    categoria: '',
    seccion_reporte: '',
    estado: 'activo',
    unidad_venta: '',
    precio_compra: null,
    precio_venta: null,
    ganancia: null,
    comision: null,
    imagen: null
});

const abrirDialogo = (item = null) => {
    form.clearErrors();
    fileList.value = [];
    
    if (item) {
        modoEdicion.value = true;
        itemEditarId.value = item.id;
        form.tipo = item.tipo;
        form.nombre = item.nombre;
        form.descripcion = item.descripcion || '';
        form.operador = item.operador || '';
        form.categoria = item.categoria || '';
        form.seccion_reporte = item.seccion_reporte || '';
        form.estado = item.estado;
        form.unidad_venta = item.unidad_venta || '';
        form.precio_compra = item.precio_compra;
        form.precio_venta = item.precio_venta;
        form.ganancia = item.ganancia;
        form.comision = item.comision;
        // No pre-cargamos la imagen en el form file para no re-enviarla si no cambia
    } else {
        modoEdicion.value = false;
        itemEditarId.value = null;
        form.reset();
        form.tipo = 'producto';
    }
    dialogVisible.value = true;
};

const confirmarEliminacion = (id) => {
    ElMessageBox.confirm(
        '¿Está seguro de eliminar este registro? Esta acción no se puede deshacer.',
        'Confirmar Eliminación',
        {
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            type: 'warning',
        }
    ).then(() => {
        router.delete(route('productos-servicios.destroy', id), {
            preserveScroll: true,
            onError: (errors) => {
                if (errors.error) {
                    ElMessage.error(errors.error);
                }
            }
        });
    }).catch(() => {
        // Cancelado
    });
};

const formatearMoneda = (valor) => {
    return Number(valor).toFixed(2) + ' Bs';
};
</script>

<template>
    <Head title="Productos y Servicios" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Productos y Servicios</h2>
        </template>

        <div class="mb-6 flex">
            <el-button v-if="tienePermiso('gestionar_productos_servicios')" type="primary" :icon="Plus" @click="abrirDialogo()">
                Nuevo Registro
            </el-button>
        </div>

        <!-- Filtros -->
        <el-card class="mb-6 shadow-sm">
            <div class="flex flex-wrap gap-4">
                <el-input
                    v-model="search"
                    placeholder="Buscar por nombre..."
                    :prefix-icon="Search"
                    clearable
                    @input="aplicarFiltros"
                    class="w-full md:w-64"
                />
                
                <el-select v-model="filtroTipo" placeholder="Tipo" clearable @change="aplicarFiltros" class="w-full md:w-40">
                    <el-option label="Producto" value="producto" />
                    <el-option label="Servicio" value="servicio" />
                </el-select>

                <el-select v-model="filtroOperador" placeholder="Operador" clearable @change="aplicarFiltros" class="w-full md:w-40">
                    <el-option label="Entel" value="Entel" />
                    <el-option label="Viva" value="Viva" />
                    <el-option label="Tigo" value="Tigo" />
                    <el-option label="Otro" value="Otro" />
                </el-select>

                <el-select v-model="filtroEstado" placeholder="Estado" clearable @change="aplicarFiltros" class="w-full md:w-40">
                    <el-option label="Activo" value="activo" />
                    <el-option label="Inactivo" value="inactivo" />
                </el-select>
            </div>
        </el-card>

        <!-- Tabla -->
        <el-card class="shadow-sm">
            <el-table :data="productos" border stripe style="width: 100%" empty-text="No se encontraron registros">
                <el-table-column prop="tipo" label="Tipo" width="100">
                    <template #default="scope">
                        <el-tag :type="scope.row.tipo === 'producto' ? 'success' : 'warning'" size="small">
                            {{ scope.row.tipo.toUpperCase() }}
                        </el-tag>
                    </template>
                </el-table-column>
                
                <el-table-column prop="nombre" label="Nombre" min-width="180" />
                <el-table-column prop="operador" label="Operador" width="100" />
                <el-table-column prop="categoria" label="Categoría" width="120" />
                <el-table-column prop="stock_actual" label="Stock" width="80" align="center">
                    <template #default="scope">
                        <span v-if="scope.row.tipo === 'servicio'" class="text-gray-400">N/A</span>
                        <span v-else class="font-bold">{{ scope.row.stock_actual }}</span>
                    </template>
                </el-table-column>
                
                <!-- Solo visibles si puede ver ganancias -->
                <el-table-column v-if="puedeVerGanancias()" prop="precio_compra" label="P. Compra" width="110" align="right">
                    <template #default="scope">{{ formatearMoneda(scope.row.precio_compra) }}</template>
                </el-table-column>
                
                <el-table-column prop="precio_venta" label="P. Venta" width="110" align="right">
                    <template #default="scope">{{ formatearMoneda(scope.row.precio_venta) }}</template>
                </el-table-column>
                
                <el-table-column v-if="puedeVerGanancias()" prop="ganancia" label="Ganancia" width="100" align="right">
                    <template #default="scope">
                        <span class="text-green-600 font-bold">{{ formatearMoneda(scope.row.ganancia) }}</span>
                    </template>
                </el-table-column>

                <el-table-column v-if="puedeVerGanancias()" prop="comision" label="Comisión" width="100" align="right">
                    <template #default="scope">
                        <span v-if="scope.row.tipo === 'producto' || scope.row.tipo_ganancia === 'ninguna'" class="text-gray-400">-</span>
                        <span v-else-if="scope.row.tipo_ganancia === 'porcentaje'" class="text-blue-600">{{ Number(scope.row.comision).toFixed(2) }} %</span>
                        <span v-else class="text-blue-600">{{ formatearMoneda(scope.row.comision) }}</span>
                    </template>
                </el-table-column>

                <el-table-column prop="estado" label="Estado" width="100" align="center">
                    <template #default="scope">
                        <el-tag :type="scope.row.estado === 'activo' ? 'success' : 'danger'" size="small" effect="dark">
                            {{ scope.row.estado === 'activo' ? 'ACTIVO' : 'INACTIVO' }}
                        </el-tag>
                    </template>
                </el-table-column>

                <el-table-column label="Acciones" width="120" align="center" fixed="right" v-if="tienePermiso('gestionar_productos_servicios')">
                    <template #default="scope">
                        <el-button type="primary" :icon="Edit" circle size="small" @click="abrirDialogo(scope.row)" />
                        <el-button type="danger" :icon="Delete" circle size="small" @click="confirmarEliminacion(scope.row.id)" />
                    </template>
                </el-table-column>
            </el-table>
        </el-card>

        <ProductoFormModal 
            v-model="dialogVisible" 
            :producto-editar="itemEditarId ? productos.find(p => p.id === itemEditarId) : null"
            @saved="aplicarFiltros"
        />

    </PanelLayout>
</template>
