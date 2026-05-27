<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { ElMessage } from 'element-plus';
import { Plus, Search, View } from '@element-plus/icons-vue';
import { usePermisos } from '@/Composables/usePermisos';
import dayjs from 'dayjs';

const { tienePermiso, tieneRol } = usePermisos();

const props = defineProps({
    aperturas: Object,
    vendedores: Array,
    vendedoresConCaja: Array,
    vendedoresSinCaja: Array,
    filtros: Object
});

// Filtros
const search = ref(props.filtros?.search || '');
const filtroVendedor = ref(props.filtros?.vendedor_id || '');
const filtroEstado = ref(props.filtros?.estado || '');

const aplicarFiltros = () => {
    router.get(route('aperturas-caja.index'), {
        search: search.value,
        vendedor_id: filtroVendedor.value,
        estado: filtroEstado.value
    }, { preserveState: true, replace: true });
};

const cambiarPagina = (url) => {
    if (url) {
        router.get(url, {}, { preserveState: true });
    }
};

// Formulario
const dialogVisible = ref(false);

const form = useForm({
    user_id: '',
    saldo_inicial: null,
    limite_venta: null,
    servicios_asignados_json: [],
    observacion: ''
});

const serviciosDisponibles = [
    { value: 'tarjetas', label: 'Tarjetas' },
    { value: 'recuperaciones', label: 'Recuperaciones' },
    { value: 'chips', label: 'Chips' },
    { value: 'recargas', label: 'Recargas' },
    { value: 'megas', label: 'Megas/Paquetes' },
    { value: 'servicios_digitales', label: 'Servicios Digitales' },
    { value: 'banca_digital', label: 'Banca Digital' },
    { value: 'servicio_tecnico', label: 'Servicio Técnico' }
];

const abrirDialogo = (vendedor_id = null) => {
    form.reset();
    form.clearErrors();
    if (vendedor_id) {
        form.user_id = vendedor_id;
    }
    // Pre-seleccionar todos por defecto, suele ser lo común
    form.servicios_asignados_json = serviciosDisponibles.map(s => s.value);
    dialogVisible.value = true;
};

const guardarApertura = () => {
    form.post(route('aperturas-caja.store'), {
        preserveScroll: true,
        onSuccess: () => {
            dialogVisible.value = false;
            ElMessage.success('Apertura de caja registrada exitosamente');
        },
        onError: (errors) => {
            if (errors.error) {
                ElMessage.error(errors.error);
            }
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
    <Head title="Aperturas de Caja" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Aperturas de Caja</h2>
        </template>

        <div class="mb-6 flex">
            <el-button type="primary" :icon="Plus" @click="abrirDialogo()">
                Nueva Apertura
            </el-button>
        </div>

        <!-- Cards de Estado de Vendedores -->
        <div class="mb-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" v-if="tieneRol('administrador')">
            <!-- Vendedores CON Caja Abierta -->
            <el-card v-for="vendedor in vendedoresConCaja" :key="`open-${vendedor.id}`" shadow="hover" class="border-l-4 border-l-green-500">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="font-bold text-gray-800">{{ vendedor.nombre_completo }}</div>
                        <div class="text-xs text-gray-500">Cod: {{ vendedor.codigo }}</div>
                    </div>
                    <el-tag type="success" size="small" effect="dark">CAJA ABIERTA</el-tag>
                </div>
                <div class="text-sm mt-3" v-if="vendedor.aperturas_caja && vendedor.aperturas_caja.length > 0">
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500">Apertura:</span>
                        <span class="font-medium">{{ formatearFecha(vendedor.aperturas_caja[0].fecha_hora_apertura) }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-t border-gray-100 mt-1">
                        <span class="text-gray-500">Saldo Inicial:</span>
                        <span class="font-bold text-green-600">{{ formatearMoneda(vendedor.aperturas_caja[0].saldo_inicial) }}</span>
                    </div>
                </div>
            </el-card>

            <!-- Vendedores SIN Caja Abierta -->
            <el-card v-for="vendedor in vendedoresSinCaja" :key="`closed-${vendedor.id}`" shadow="hover" class="border-l-4 border-l-gray-400 bg-gray-50">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="font-bold text-gray-700">{{ vendedor.nombre_completo }}</div>
                        <div class="text-xs text-gray-500">Cod: {{ vendedor.codigo }}</div>
                    </div>
                    <el-tag type="info" size="small" effect="plain">SIN CAJA</el-tag>
                </div>
                <div class="text-sm mt-3 mb-3">
                    <div class="flex justify-between py-1 text-gray-400" v-if="vendedor.aperturas_caja && vendedor.aperturas_caja.length > 0">
                        <span>Último saldo inicial:</span>
                        <span>{{ formatearMoneda(vendedor.aperturas_caja[0].saldo_inicial) }}</span>
                    </div>
                    <div v-else class="text-xs text-gray-400 italic py-1">Nunca ha abierto caja</div>
                </div>
                <div class="mt-auto pt-2 border-t border-gray-200">
                    <el-button type="primary" size="small" class="w-full" plain @click="abrirDialogo(vendedor.id)">
                        Abrir Caja
                    </el-button>
                </div>
            </el-card>
        </div>

        <!-- Filtros -->
        <el-card class="mb-6 shadow-sm">
            <div class="flex flex-wrap gap-4">
                <el-input
                    v-model="search"
                    placeholder="Buscar por código o nombre..."
                    :prefix-icon="Search"
                    clearable
                    @input="aplicarFiltros"
                    class="w-full md:w-64"
                />
                
                <el-select v-model="filtroVendedor" placeholder="Vendedor" clearable @change="aplicarFiltros" class="w-full md:w-64">
                    <el-option 
                        v-for="vend in vendedores" 
                        :key="vend.id" 
                        :label="`${vend.codigo} - ${vend.nombre_completo}`" 
                        :value="vend.id" 
                    />
                </el-select>

                <el-select v-model="filtroEstado" placeholder="Estado" clearable @change="aplicarFiltros" class="w-full md:w-40">
                    <el-option label="Abierta" value="abierta" />
                    <el-option label="Cerrada" value="cerrada" />
                </el-select>
            </div>
        </el-card>

        <!-- Tabla -->
        <el-card class="shadow-sm">
            <el-table :data="aperturas.data" border stripe style="width: 100%" empty-text="No hay registros de aperturas">
                <el-table-column prop="id" label="ID" width="70" align="center" />
                
                <el-table-column label="Vendedor" min-width="180">
                    <template #default="scope">
                        <span class="font-medium">{{ scope.row.usuario?.nombre_completo }}</span>
                        <div class="text-xs text-gray-500">{{ scope.row.usuario?.codigo }}</div>
                    </template>
                </el-table-column>

                <el-table-column prop="fecha_hora_apertura" label="Apertura" width="160" align="center">
                    <template #default="scope">{{ formatearFecha(scope.row.fecha_hora_apertura) }}</template>
                </el-table-column>
                
                <el-table-column prop="saldo_inicial" label="Saldo Inicial" width="120" align="right">
                    <template #default="scope">{{ formatearMoneda(scope.row.saldo_inicial) }}</template>
                </el-table-column>

                <el-table-column prop="limite_venta" label="Límite Venta" width="120" align="right">
                    <template #default="scope">{{ scope.row.limite_venta ? formatearMoneda(scope.row.limite_venta) : 'Sin Límite' }}</template>
                </el-table-column>

                <el-table-column prop="status" label="Estado" width="110" align="center">
                    <template #default="scope">
                        <el-tag :type="scope.row.status === 'abierta' ? 'success' : 'info'" effect="dark">
                            {{ scope.row.status.toUpperCase() }}
                        </el-tag>
                    </template>
                </el-table-column>

                <el-table-column label="Acciones" width="80" align="center" fixed="right">
                    <template #default="scope">
                        <el-button type="info" :icon="View" circle size="small" title="Ver Detalles" />
                    </template>
                </el-table-column>
            </el-table>

            <!-- Paginación -->
            <div class="mt-4 flex justify-end">
                <el-pagination
                    v-if="aperturas.total > aperturas.per_page"
                    background
                    layout="prev, pager, next"
                    :total="aperturas.total"
                    :page-size="aperturas.per_page"
                    :current-page="aperturas.current_page"
                    @current-change="(val) => cambiarPagina(aperturas.links[val].url)"
                />
            </div>
        </el-card>

        <!-- Diálogo Formulario -->
        <el-dialog
            v-model="dialogVisible"
            title="Nueva Apertura de Caja"
            width="600px"
            destroy-on-close
        >
            <el-form :model="form" label-position="top">
                <el-form-item label="Vendedor" :error="form.errors.user_id" required>
                    <el-select v-model="form.user_id" filterable placeholder="Seleccione un vendedor" class="w-full">
                        <el-option
                            v-for="vend in vendedores"
                            :key="vend.id"
                            :label="`${vend.codigo} - ${vend.nombre_completo}`"
                            :value="vend.id"
                        />
                    </el-select>
                </el-form-item>

                <div class="grid grid-cols-2 gap-4">
                    <el-form-item label="Saldo Inicial (Efectivo/Sencillo)" :error="form.errors.saldo_inicial" required>
                        <el-input v-model="form.saldo_inicial" type="number" step="any" min="0" placeholder="Ej: 150.00" class="w-full">
                            <template #prefix>Bs</template>
                        </el-input>
                    </el-form-item>

                    <el-form-item label="Límite de Venta Físico" :error="form.errors.limite_venta">
                        <el-input v-model="form.limite_venta" type="number" step="any" min="0" placeholder="Sin límite" class="w-full">
                            <template #prefix>Bs</template>
                        </el-input>
                    </el-form-item>
                </div>

                <el-form-item label="Servicios Asignados" :error="form.errors.servicios_asignados_json">
                    <el-select
                        v-model="form.servicios_asignados_json"
                        multiple
                        collapse-tags
                        collapse-tags-tooltip
                        placeholder="Seleccione servicios"
                        class="w-full"
                    >
                        <el-option
                            v-for="srv in serviciosDisponibles"
                            :key="srv.value"
                            :label="srv.label"
                            :value="srv.value"
                        />
                    </el-select>
                </el-form-item>

                <el-form-item label="Observación" :error="form.errors.observacion">
                    <el-input v-model="form.observacion" type="textarea" rows="2" placeholder="Nota opcional..." />
                </el-form-item>
            </el-form>

            <template #footer>
                <span class="dialog-footer">
                    <el-button @click="dialogVisible = false">Cancelar</el-button>
                    <el-button type="primary" :loading="form.processing" @click="guardarApertura">
                        Abrir Caja
                    </el-button>
                </span>
            </template>
        </el-dialog>

    </PanelLayout>
</template>
