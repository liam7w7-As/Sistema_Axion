<script setup>
import { ref, watch } from 'vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { Download, Document, Search } from '@element-plus/icons-vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    datos: Object
});

const page = usePage();
const authUser = page.props.auth.user;
const filtrosGlobales = page.props.filtros_globales || {};

// Restricción de acceso estricta
const tienePermiso = authUser.permisos.includes('visualizar_ganancias') || authUser.roles.includes('administrador');

const searchParams = new URLSearchParams(window.location.search);
const initFechaRango = (searchParams.get('fecha_inicio') && searchParams.get('fecha_fin'))
    ? [searchParams.get('fecha_inicio'), searchParams.get('fecha_fin')]
    : [];

const filtros = ref({
    vendedor_id: searchParams.get('vendedor_id') || '',
    operador: searchParams.get('operador') || '',
    fecha_rango: initFechaRango
});

const buscar = debounce(() => {
    let params = {};
    if (filtros.value.vendedor_id) params.vendedor_id = filtros.value.vendedor_id;
    if (filtros.value.operador) params.operador = filtros.value.operador;
    if (filtros.value.fecha_rango?.[0]) {
        params.fecha_inicio = filtros.value.fecha_rango[0];
        params.fecha_fin = filtros.value.fecha_rango[1];
    }

    router.get(route('reportes.ganancias'), params, { preserveState: true, replace: true });
}, 500);

watch(filtros, buscar, { deep: true });

const orientacionPdf = ref('L');

const exportarPdf = () => {
    const params = new URLSearchParams();
    if (filtros.value.vendedor_id) params.append('vendedor_id', filtros.value.vendedor_id);
    if (filtros.value.operador) params.append('operador', filtros.value.operador);
    if (filtros.value.fecha_rango?.[0]) params.append('fecha_inicio', filtros.value.fecha_rango[0]);
    if (filtros.value.fecha_rango?.[1]) params.append('fecha_fin', filtros.value.fecha_rango[1]);
    params.append('orientacion', orientacionPdf.value);

    window.open(route('reportes.exportar-pdf', 'ganancias') + '?' + params.toString(), '_blank');
};

const exportarExcel = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('reportes.exportar-excel', 'ganancias');
    
    // CSRF
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
    if (filtros.value.operador) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'operador'; input.value = filtros.value.operador;
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
    router.get(`${route('reportes.ganancias')}?${params.toString()}`, {}, { preserveState: true });
};

const calcularTotales = (param) => {
    const { columns, data } = param;
    const sums = [];
    columns.forEach((column, index) => {
        if (index === 0) {
            sums[index] = 'TOTALES';
            return;
        }
        if (column.property === 'cantidad_total') {
            sums[index] = data.reduce((sum, row) => sum + Number(row.cantidad_total || 0), 0);
        } else if (column.label === 'Ingresos Bs') {
            sums[index] = data.reduce((sum, row) => sum + Number(row.ingresos_totales || 0), 0).toFixed(2) + ' Bs';
        } else if (column.label === 'Ganancia Neta Bs') {
            sums[index] = data.reduce((sum, row) => sum + Number(row.ganancia_neta || 0), 0).toFixed(2) + ' Bs';
        } else {
            sums[index] = '';
        }
    });
    return sums;
};
</script>

<template>
    <Head title="Reporte de Ganancias" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Reporte de Ganancias</h2>
        </template>

        <!-- Bloqueo de Acceso -->
        <el-alert
            v-if="!tienePermiso"
            title="Acceso Denegado"
            type="error"
            description="No tienes el permiso 'visualizar_ganancias' necesario para ver este reporte."
            show-icon
            :closable="false"
            class="mb-6"
        />

        <template v-else>
            <!-- Filtros -->
            <el-card class="mb-6 shadow-sm border-t-4 border-t-blue-500">
                <template #header>
                    <div class="flex items-center gap-2">
                        <el-icon><Search /></el-icon>
                        <span class="font-bold text-gray-700">Filtros de Búsqueda</span>
                    </div>
                </template>
                <el-form :inline="true" :model="filtros" class="flex flex-wrap gap-4 items-end mb-0">
                    <el-form-item label="Vendedor" class="mb-0">
                        <el-select v-model="filtros.vendedor_id" placeholder="Todos" clearable class="w-48">
                            <el-option v-for="v in filtrosGlobales.vendedores" :key="v.id" :label="v.nombre_completo" :value="v.id" />
                        </el-select>
                    </el-form-item>

                    <el-form-item label="Operador" class="mb-0">
                        <el-select v-model="filtros.operador" placeholder="Todos" clearable class="w-32">
                            <el-option v-for="o in filtrosGlobales.operadores" :key="o" :label="o" :value="o" />
                        </el-select>
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

                <el-table :data="datos?.data || []" border stripe style="width: 100%" v-if="datos && datos.data.length > 0" show-summary :summary-method="calcularTotales">
                    <el-table-column prop="producto" label="Producto / Servicio" min-width="220" />
                    <el-table-column prop="operador" label="Operador" width="120" align="center">
                        <template #default="scope">
                            {{ scope.row.operador || '—' }}
                        </template>
                    </el-table-column>
                    <el-table-column label="P. Compra Bs" width="130" align="right">
                        <template #default="scope">{{ Number(scope.row.precio_compra).toFixed(2) }}</template>
                    </el-table-column>
                    <el-table-column prop="cantidad_total" label="Cant. Vendida" width="120" align="center" />
                    <el-table-column label="Ingresos Bs" width="140" align="right">
                        <template #default="scope">
                            <span class="font-semibold text-blue-600">{{ Number(scope.row.ingresos_totales).toFixed(2) }}</span>
                        </template>
                    </el-table-column>
                    <el-table-column label="Ganancia Neta Bs" width="160" align="right">
                        <template #default="scope">
                            <span class="font-bold text-green-600">
                                {{ Number(scope.row.ganancia_neta).toFixed(2) }}
                            </span>
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
        </template>
    </PanelLayout>
</template>
