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
const authUser = page.props.auth.user;
const filtrosGlobales = page.props.filtros_globales || {};
const isAdmin = authUser.roles.includes('administrador');

const searchParams = new URLSearchParams(window.location.search);
const initFechaRango = (searchParams.get('fecha_inicio') && searchParams.get('fecha_fin'))
    ? [searchParams.get('fecha_inicio'), searchParams.get('fecha_fin')]
    : [];

const filtros = ref({
    vendedor_id: searchParams.get('vendedor_id') || '',
    seccion: searchParams.get('seccion') || '',
    operador: searchParams.get('operador') || '',
    fecha_rango: initFechaRango
});
const seccionesOpciones = [
    { value: 'recargas', label: 'Recargas al Paso' },
    { value: 'megas', label: 'Megas' },
    { value: 'servicios_digitales', label: 'Servicios Digitales' },
    { value: 'banca_digital', label: 'Banca Digital' },
    { value: 'servicio_tecnico', label: 'Servicio Técnico' },
    { value: 'efectivo_monedas', label: 'Efectivo/Monedas' }
];

const buscar = debounce(() => {
    let params = {};
    if (filtros.value.vendedor_id) params.vendedor_id = filtros.value.vendedor_id;
    if (filtros.value.seccion) params.seccion = filtros.value.seccion;
    if (filtros.value.operador) params.operador = filtros.value.operador;
    if (filtros.value.fecha_rango?.[0]) {
        params.fecha_inicio = filtros.value.fecha_rango[0];
        params.fecha_fin = filtros.value.fecha_rango[1];
    }

    router.get(route('reportes.saldo-servicios'), params, { preserveState: true, replace: true });
}, 500);

watch(filtros, buscar, { deep: true });

const limpiarFiltros = () => {
    filtros.value = {
        vendedor_id: '',
        seccion: '',
        operador: '',
        fecha_rango: []
    };
};

const orientacionPdf = ref('L');

const exportarPdf = () => {
    const params = new URLSearchParams();
    if (filtros.value.vendedor_id) params.append('vendedor_id', filtros.value.vendedor_id);
    if (filtros.value.seccion) params.append('seccion', filtros.value.seccion);
    if (filtros.value.operador) params.append('operador', filtros.value.operador);
    if (filtros.value.fecha_rango?.[0]) params.append('fecha_inicio', filtros.value.fecha_rango[0]);
    if (filtros.value.fecha_rango?.[1]) params.append('fecha_fin', filtros.value.fecha_rango[1]);
    params.append('orientacion', orientacionPdf.value);

    window.open(route('reportes.exportar-pdf', 'saldos') + '?' + params.toString(), '_blank');
};

const exportarExcel = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('reportes.exportar-excel', 'saldos');
    
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
    if (filtros.value.seccion) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'seccion'; input.value = filtros.value.seccion;
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
    router.get(`${route('reportes.saldo-servicios')}?${params.toString()}`, {}, { preserveState: true });
};
</script>

<template>
    <Head title="Reporte de Saldos de Servicios" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Reporte de Saldos de Servicios</h2>
        </template>

        <el-alert
            v-if="!isAdmin"
            title="Acceso Denegado"
            type="error"
            description="Solo los administradores tienen acceso a este reporte."
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
                        <el-select v-model="filtros.vendedor_id" placeholder="Todos" clearable style="width: 192px;">
                            <el-option v-for="v in filtrosGlobales.vendedores" :key="v.id" :label="v.nombre_completo" :value="v.id" />
                        </el-select>
                    </el-form-item>
                    
                    <el-form-item label="Servicio" class="mb-0">
                        <el-select v-model="filtros.seccion" placeholder="Todos" clearable style="width: 192px;">
                            <el-option v-for="op in seccionesOpciones" :key="op.value" :label="op.label" :value="op.value" />
                        </el-select>
                    </el-form-item>

                    <el-form-item label="Operador" class="mb-0">
                        <el-select v-model="filtros.operador" placeholder="Todos" clearable style="width: 150px;">
                            <el-option v-for="op in filtrosGlobales.operadores" :key="op" :label="op" :value="op" />
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

                <div class="overflow-x-auto" v-if="datos && Object.keys(datos).length > 0">
                    <el-table :data="Object.values(datos)" border stripe style="width: 100%">
                        <el-table-column prop="fecha" label="Fecha" width="120" />
                        <el-table-column prop="vendedor" label="Vendedor" min-width="160" />
                        <el-table-column prop="servicio" label="Servicio" min-width="130">
                            <template #default="scope">
                                {{ String(scope.row.servicio).toUpperCase() }}
                            </template>
                        </el-table-column>
                        <el-table-column prop="operador" label="Operador" min-width="100">
                            <template #default="scope">
                                {{ String(scope.row.operador).toUpperCase() }}
                            </template>
                        </el-table-column>
                        <el-table-column label="Límite" width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.limite_asignado).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Operado" width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.total_operado).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Disponible" width="100" align="right">
                            <template #default="scope">
                                <span :class="scope.row.disponible <= 0 ? 'text-red-500 font-bold' : 'text-green-600 font-bold'">
                                    {{ Number(scope.row.disponible).toFixed(2) }}
                                </span>
                            </template>
                        </el-table-column>
                        <el-table-column label="Inicial (Bs)" width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.saldo_inicial).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Final Esp." width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.saldo_esperado).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Entregado" width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.entregado).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Diferencia" width="100" align="right">
                            <template #default="scope">
                                <span v-if="scope.row.diferencia > 0" class="text-green-600 font-bold">+{{ Number(scope.row.diferencia).toFixed(2) }} (Sob)</span>
                                <span v-else-if="scope.row.diferencia < 0" class="text-red-600 font-bold">{{ Number(scope.row.diferencia).toFixed(2) }} (Fal)</span>
                                <span v-else class="text-gray-500">0.00</span>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>

                <el-empty v-else description="No se encontraron registros" />

            </el-card>
        </template>
    </PanelLayout>
</template>
