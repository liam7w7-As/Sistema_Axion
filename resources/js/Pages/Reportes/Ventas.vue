<script setup>
import { ref, watch } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { Download, Document, Search, Refresh } from '@element-plus/icons-vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    datos: Object
});

const page = usePage();
const filtrosGlobales = page.props.filtros_globales || {};
const authUser = page.props.auth.user;
const isVendedor = authUser.roles.includes('vendedor');

// Obtener fecha inicial y final del query si existe
const searchParams = new URLSearchParams(window.location.search);
const initFechaRango = (searchParams.get('fecha_inicio') && searchParams.get('fecha_fin'))
    ? [searchParams.get('fecha_inicio'), searchParams.get('fecha_fin')]
    : [];

const filtros = ref({
    vendedor_id: isVendedor ? authUser.id : (searchParams.get('vendedor_id') || ''),
    tipo_pago: searchParams.get('tipo_pago') || '',
    estado: searchParams.get('estado') || 'completada',
    fecha_rango: initFechaRango
});

const buscar = debounce(() => {
    let params = {};
    if (filtros.value.vendedor_id) params.vendedor_id = filtros.value.vendedor_id;
    if (filtros.value.tipo_pago) params.tipo_pago = filtros.value.tipo_pago;
    if (filtros.value.estado) params.estado = filtros.value.estado;
    if (filtros.value.fecha_rango?.[0]) {
        params.fecha_inicio = filtros.value.fecha_rango[0];
        params.fecha_fin = filtros.value.fecha_rango[1];
    }

    router.get(route('reportes.ventas'), params, { preserveState: true, replace: true });
}, 500);

// Usar watch para aplicar filtros automáticamente o un botón. Si usamos watch (como en Usuarios), 
// para date-picker a veces es molesto que recargue al primer clic, pero lo dejamos manual con botón como estaba
// u opcionalmente watch en inputs simples y botón para buscar explícitamente.
// Como el user tenía un @click="buscar", lo dejamos sin watch o con watch selectivo.
// Lo mantendremos en los selects pero datepicker requiere botón, o watch total. Haremos watch total.
watch(filtros, buscar, { deep: true });

const limpiarFiltros = () => {
    filtros.value = {
        vendedor_id: isVendedor ? authUser.id : '',
        tipo_pago: '',
        estado: 'completada',
        fecha_rango: []
    };
};

const orientacionPdf = ref('L');

const exportarPdf = () => {
    const params = new URLSearchParams();
    if (filtros.value.vendedor_id) params.append('vendedor_id', filtros.value.vendedor_id);
    if (filtros.value.tipo_pago) params.append('tipo_pago', filtros.value.tipo_pago);
    if (filtros.value.estado) params.append('estado', filtros.value.estado);
    if (filtros.value.fecha_rango?.[0]) params.append('fecha_inicio', filtros.value.fecha_rango[0]);
    if (filtros.value.fecha_rango?.[1]) params.append('fecha_fin', filtros.value.fecha_rango[1]);
    params.append('orientacion', orientacionPdf.value);

    window.open(route('reportes.exportar-pdf', 'ventas') + '?' + params.toString(), '_blank');
};

const exportarExcel = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('reportes.exportar-excel', 'ventas');
    
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken || page.props.csrf_token || '';
    form.appendChild(csrfInput);

    if (filtros.value.vendedor_id) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'vendedor_id'; input.value = filtros.value.vendedor_id;
        form.appendChild(input);
    }
    if (filtros.value.tipo_pago) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'tipo_pago'; input.value = filtros.value.tipo_pago;
        form.appendChild(input);
    }
    if (filtros.value.estado) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'estado'; input.value = filtros.value.estado;
        form.appendChild(input);
    }
    if (filtros.value.fecha_rango?.[0]) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'fecha_inicio'; input.value = filtros.value.fecha_rango[0];
        form.appendChild(input);
        
        const input2 = document.createElement('input');
        input2.type = 'hidden'; input2.name = 'fecha_fin'; input2.value = filtros.value.fecha_rango[1];
        form.appendChild(input2);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const cambiarPagina = (pagina) => {
    const params = new URLSearchParams(window.location.search);
    params.set('page', pagina);
    router.get(`${route('reportes.ventas')}?${params.toString()}`, {}, { preserveState: true });
};
</script>

<template>
    <Head title="Reporte de Ventas" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Reporte de Ventas</h2>
        </template>

        <!-- Filtros -->
        <el-card class="mb-6 shadow-sm border-t-4 border-t-blue-500">
            <template #header>
                <div class="flex items-center gap-2">
                    <el-icon><Search /></el-icon>
                    <span class="font-bold text-gray-700">Filtros de Búsqueda</span>
                </div>
            </template>
            
            <el-form :inline="true" :model="filtros" class="flex flex-wrap gap-4 items-end mb-0">
                <el-form-item label="Vendedor" v-if="!isVendedor" class="mb-0">
                    <el-select v-model="filtros.vendedor_id" placeholder="Todos" clearable style="width: 192px;">
                        <el-option v-for="v in filtrosGlobales.vendedores" :key="v.id" :label="v.nombre_completo" :value="v.id" />
                    </el-select>
                </el-form-item>

                <el-form-item label="Tipo Pago" class="mb-0">
                    <el-select v-model="filtros.tipo_pago" placeholder="Todos" clearable style="width: 160px;">
                        <el-option label="Efectivo" value="efectivo" />
                        <el-option label="QR" value="qr" />
                        <el-option label="Transferencia" value="transferencia" />
                    </el-select>
                </el-form-item>

                <el-form-item label="Estado" class="mb-0">
                    <el-select v-model="filtros.estado" placeholder="Todos" clearable style="width: 160px;">
                        <el-option label="Completada" value="completada" />
                        <el-option label="Anulada" value="anulada" />
                    </el-select>
                </el-form-item>

                <el-form-item class="mb-0">
                    <el-button type="info" plain :icon="Refresh" @click="limpiarFiltros">Limpiar</el-button>
                </el-form-item>

                <el-form-item label="Rango de Fechas" class="mb-0">
                    <el-date-picker
                        v-model="filtros.fecha_rango"
                        type="daterange"
                        range-separator="A"
                        start-placeholder="Inicio"
                        end-placeholder="Fin"
                        value-format="YYYY-MM-DD"
                        class="!w-64"
                    />
                </el-form-item>
            </el-form>
        </el-card>

        <!-- Tabla de Datos -->
        <el-card class="shadow-sm">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <span class="font-bold text-gray-700">Resultados del Reporte</span>
                    <div class="flex gap-2 items-center">
                        <el-radio-group v-model="orientacionPdf" size="small" class="mr-2 hidden sm:block">
                            <el-radio-button label="P">Vertical</el-radio-button>
                            <el-radio-button label="L">Horizontal</el-radio-button>
                        </el-radio-group>
                        <el-button type="danger" :icon="Document" @click="exportarPdf" size="small">Exportar PDF</el-button>
                        <el-button type="success" :icon="Download" @click="exportarExcel" size="small">Exportar Excel</el-button>
                    </div>
                </div>
            </template>

            <el-table :data="datos?.data || []" border stripe style="width: 100%" v-if="datos && datos.data.length > 0">
                <el-table-column prop="fecha_hora" label="Fecha" width="160">
                    <template #default="scope">
                        {{ new Date(scope.row.fecha_hora).toLocaleString() }}
                    </template>
                </el-table-column>
                <el-table-column prop="codigo" label="Código" width="130" />
                <el-table-column prop="usuario.nombre_completo" label="Vendedor" min-width="160" />
                <el-table-column label="Cliente" min-width="140">
                    <template #default="scope">
                        {{ scope.row.cliente_nombre || 'S/N' }}
                    </template>
                </el-table-column>
                <el-table-column label="Tipo Pago" width="110" align="center">
                    <template #default="scope">
                        <span class="uppercase text-xs font-semibold">{{ scope.row.tipo_pago }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="Total Bs" width="110" align="right">
                    <template #default="scope">
                        <span class="font-bold text-blue-600">{{ Number(scope.row.total).toFixed(2) }}</span>
                    </template>
                </el-table-column>
                <el-table-column label="Estado" width="100" align="center">
                    <template #default="scope">
                        <el-tag :type="scope.row.status === 'completada' ? 'success' : 'danger'" size="small">
                            {{ scope.row.status.toUpperCase() }}
                        </el-tag>
                    </template>
                </el-table-column>
            </el-table>

            <el-empty v-else description="No se encontraron registros" />

            <div class="mt-4 flex justify-end" v-if="datos && datos.total > datos.per_page">
                <el-pagination
                    background
                    layout="prev, pager, next, total"
                    :total="datos.total"
                    :page-size="datos.per_page"
                    :current-page="datos.current_page"
                    @current-change="cambiarPagina"
                />
            </div>
        </el-card>
    </PanelLayout>
</template>
