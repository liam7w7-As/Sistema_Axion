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
const isAdmin = authUser.roles.includes('administrador');

const searchParams = new URLSearchParams(window.location.search);
const initFechaRango = (searchParams.get('fecha_inicio') && searchParams.get('fecha_fin'))
    ? [searchParams.get('fecha_inicio'), searchParams.get('fecha_fin')]
    : [];

const filtros = ref({
    vendedor_id: searchParams.get('vendedor_id') || '',
    fecha_rango: initFechaRango
});

const buscar = debounce(() => {
    let params = {};
    if (filtros.value.vendedor_id) params.vendedor_id = filtros.value.vendedor_id;
    if (filtros.value.fecha_rango?.[0]) {
        params.fecha_inicio = filtros.value.fecha_rango[0];
        params.fecha_fin = filtros.value.fecha_rango[1];
    }

    router.get(route('reportes.saldo-servicios'), params, { preserveState: true, replace: true });
}, 500);

watch(filtros, buscar, { deep: true });

const orientacionPdf = ref('L');

const exportarPdf = () => {
    const params = new URLSearchParams();
    if (filtros.value.vendedor_id) params.append('vendedor_id', filtros.value.vendedor_id);
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
                        <el-select v-model="filtros.vendedor_id" placeholder="Todos" clearable class="w-48">
                            <el-option v-for="v in filtrosGlobales.vendedores" :key="v.id" :label="v.nombre_completo" :value="v.id" />
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

                <div class="overflow-x-auto" v-if="datos && datos.data.length > 0">
                    <el-table :data="datos?.data || []" border stripe style="width: 100%">
                        <el-table-column prop="created_at" label="Fecha" width="120">
                            <template #default="scope">
                                {{ new Date(scope.row.created_at).toLocaleDateString() }}
                            </template>
                        </el-table-column>
                        <el-table-column prop="usuario.nombre_completo" label="Vendedor" min-width="160" />
                        
                        <!-- 10 Secciones ERS -->
                        <el-table-column label="Tarj. Unidad" width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.tarjetas_unidad).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Tarj. Mayor" width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.tarjetas_mayor).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Recuperac." width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.recuperaciones).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Chips" width="80" align="right">
                            <template #default="scope">{{ Number(scope.row.chips).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Recargas" width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.recargas).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Megas" width="80" align="right">
                            <template #default="scope">{{ Number(scope.row.megas).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Serv. Dig." width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.servicios_digitales).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Banca Dig." width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.banca_digital).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Serv. Téc." width="100" align="right">
                            <template #default="scope">{{ Number(scope.row.servicio_tecnico).toFixed(2) }}</template>
                        </el-table-column>
                        <el-table-column label="Efectivo" width="100" align="right">
                            <template #default="scope">
                                <span :class="scope.row.efectivo_monedas < 0 ? 'text-red-500 font-bold' : ''">
                                    {{ Number(scope.row.efectivo_monedas).toFixed(2) }}
                                </span>
                            </template>
                        </el-table-column>
                    </el-table>
                </div>

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
