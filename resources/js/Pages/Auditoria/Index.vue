<script setup>
import { ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { Search, Refresh, Download, Document } from '@element-plus/icons-vue';
import debounce from 'lodash/debounce';

const props = defineProps({
    logs: Object
});

const page = usePage();
const filtrosGlobales = page.props.filtros_globales || {};

const filtros = ref({
    usuario_id: new URLSearchParams(window.location.search).get('usuario_id') || '',
    accion: new URLSearchParams(window.location.search).get('accion') || '',
    modelo: new URLSearchParams(window.location.search).get('modelo') || '',
    fecha_rango: [
        new URLSearchParams(window.location.search).get('fecha_inicio') || '',
        new URLSearchParams(window.location.search).get('fecha_fin') || ''
    ].filter(Boolean)
});

const modelosOpciones = [
    'User', 'ProductService', 'Sale', 'CashOpening', 'CashClosure', 'SellerMovement'
];

const accionesOpciones = [
    'creado', 'actualizado', 'eliminado', 'inicio_sesion', 'cierre_sesion'
];

const buscar = debounce(() => {
    let params = {};
    if (filtros.value.usuario_id) params.usuario_id = filtros.value.usuario_id;
    if (filtros.value.accion) params.accion = filtros.value.accion;
    if (filtros.value.modelo) params.modelo = filtros.value.modelo;
    if (filtros.value.fecha_rango?.length === 2) {
        params.fecha_inicio = filtros.value.fecha_rango[0];
        params.fecha_fin = filtros.value.fecha_rango[1];
    }

    router.get(route('auditoria.index'), params, { preserveState: true, replace: true });
}, 500);

watch(filtros, buscar, { deep: true });

const limpiarFiltros = () => {
    filtros.value = { usuario_id: '', accion: '', modelo: '', fecha_rango: [] };
    router.get(route('auditoria.index'));
};

const cambiarPagina = (pagina) => {
    const params = new URLSearchParams(window.location.search);
    params.set('page', pagina);
    router.get(`${route('auditoria.index')}?${params.toString()}`);
};

const orientacionPdf = ref('L');

const exportarPdf = () => {
    const params = new URLSearchParams(window.location.search);
    params.append('orientacion', orientacionPdf.value);
    window.open(route('reportes.exportar-pdf', 'auditoria') + '?' + params.toString(), '_blank');
};

const exportarExcel = () => {
    const params = new URLSearchParams(window.location.search);
    params.append('export', 'excel');
    window.open(route('auditoria.index') + '?' + params.toString(), '_blank');
};
</script>

<template>
    <Head title="Auditoría del Sistema" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Auditoría y Traza de Operaciones</h2>
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
                <el-form-item label="Usuario" class="mb-0">
                    <el-select v-model="filtros.usuario_id" placeholder="Todos" clearable class="w-48">
                        <el-option v-for="v in filtrosGlobales.vendedores" :key="v.id" :label="v.nombre_completo" :value="v.id" />
                    </el-select>
                </el-form-item>

                <el-form-item label="Módulo" class="mb-0">
                    <el-select v-model="filtros.modelo" placeholder="Todos" clearable class="w-40">
                        <el-option v-for="op in modelosOpciones" :key="op" :label="op" :value="op" />
                    </el-select>
                </el-form-item>

                <el-form-item label="Acción" class="mb-0">
                    <el-select v-model="filtros.accion" placeholder="Todas" clearable class="w-36">
                        <el-option v-for="op in accionesOpciones" :key="op" :label="op" :value="op" />
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

                <el-form-item class="mb-0">
                    <el-button @click="limpiarFiltros" :icon="Refresh">Limpiar</el-button>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- Tabla de Datos -->
        <el-card class="shadow-sm">
            <template #header>
                <div class="flex items-center justify-between w-full">
                    <span class="font-bold text-gray-700">Registro de Auditoría</span>
                    <div class="flex gap-2 items-center">
                        <el-radio-group v-model="orientacionPdf" size="small" class="mr-2 hidden sm:block">
                            <el-radio-button label="P">Vertical</el-radio-button>
                            <el-radio-button label="L">Horizontal</el-radio-button>
                        </el-radio-group>
                        <!-- Botones listos visualmente según requerimiento UI general -->
                        <el-button type="danger" :icon="Document" @click="exportarPdf" size="small">Exportar PDF</el-button>
                        <el-button type="success" :icon="Download" @click="exportarExcel" size="small">Exportar Excel</el-button>
                    </div>
                </div>
            </template>

            <el-table :data="logs?.data || []" border stripe style="width: 100%" v-if="logs && logs.data.length > 0">
                <el-table-column prop="id" label="ID" width="80" align="center" />
                <el-table-column label="Fecha y Hora" width="160">
                    <template #default="scope">
                        {{ new Date(scope.row.created_at).toLocaleString('es-ES') }}
                    </template>
                </el-table-column>
                <el-table-column label="Usuario" min-width="150">
                    <template #default="scope">
                        {{ scope.row.user ? scope.row.user.nombre_completo : 'Sistema / No Auth' }}
                    </template>
                </el-table-column>
                <el-table-column prop="accion" label="Acción" width="120">
                    <template #default="scope">
                        <el-tag :type="scope.row.accion === 'eliminado' ? 'danger' : (scope.row.accion === 'creado' ? 'success' : 'info')">
                            {{ scope.row.accion.toUpperCase() }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="Módulo Afectado" width="180">
                    <template #default="scope">
                        {{ scope.row.modelo }} (ID: {{ scope.row.modelo_id || 'N/A' }})
                    </template>
                </el-table-column>
                <el-table-column prop="ip_address" label="Dirección IP" width="130" align="center" />
                
                <el-table-column type="expand">
                    <template #default="scope">
                        <div class="p-4 bg-gray-50 border-t">
                            <h4 class="font-semibold text-gray-700 mb-2">Detalles del Cambio:</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div v-if="scope.row.valores_antiguos">
                                    <span class="text-sm font-bold text-red-600">Valores Anteriores:</span>
                                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-x-auto mt-1">{{ JSON.stringify(scope.row.valores_antiguos, null, 2) }}</pre>
                                </div>
                                <div v-if="scope.row.valores_nuevos">
                                    <span class="text-sm font-bold text-green-600">Valores Nuevos:</span>
                                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-x-auto mt-1">{{ JSON.stringify(scope.row.valores_nuevos, null, 2) }}</pre>
                                </div>
                                <div v-if="!scope.row.valores_antiguos && !scope.row.valores_nuevos" class="text-sm text-gray-500 italic col-span-2">
                                    No hay detalles de valores registrados para esta acción.
                                </div>
                            </div>
                        </div>
                    </template>
                </el-table-column>
            </el-table>
            
            <el-empty v-else description="No se encontraron registros" />

            <div class="mt-4 flex justify-end" v-if="logs && logs.total > logs.per_page">
                <el-pagination
                    background
                    layout="prev, pager, next, total"
                    :total="logs.total"
                    :page-size="logs.per_page"
                    :current-page="logs.current_page"
                    @current-change="cambiarPagina"
                />
            </div>
        </el-card>
    </PanelLayout>
</template>
