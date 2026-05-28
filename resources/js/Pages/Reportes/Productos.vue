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

const filtros = ref({
    tipo_registro: new URLSearchParams(window.location.search).get('tipo_registro') || '',
    operador: new URLSearchParams(window.location.search).get('operador') || '',
    estado: new URLSearchParams(window.location.search).get('estado') || ''
});

const buscar = debounce(() => {
    let params = {};
    if (filtros.value.tipo_registro) params.tipo_registro = filtros.value.tipo_registro;
    if (filtros.value.operador) params.operador = filtros.value.operador;
    if (filtros.value.estado) params.estado = filtros.value.estado;

    router.get(route('reportes.productos'), params, { preserveState: true, replace: true });
}, 500);

watch(filtros, buscar, { deep: true });

const limpiarFiltros = () => {
    filtros.value = {
        tipo_registro: '',
        operador: '',
        estado: ''
    };
};

const orientacionPdf = ref('L');

const exportarPdf = () => {
    const params = new URLSearchParams();
    if (filtros.value.tipo_registro) params.append('tipo_registro', filtros.value.tipo_registro);
    if (filtros.value.operador) params.append('operador', filtros.value.operador);
    if (filtros.value.estado) params.append('estado', filtros.value.estado);
    params.append('orientacion', orientacionPdf.value);

    window.open(route('reportes.exportar-pdf', 'productos') + '?' + params.toString(), '_blank');
};

const exportarExcel = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('reportes.exportar-excel', 'productos');
    
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken || page.props.csrf_token || '';
    form.appendChild(csrfInput);

    if (filtros.value.tipo_registro) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'tipo_registro'; input.value = filtros.value.tipo_registro;
        form.appendChild(input);
    }
    if (filtros.value.operador) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'operador'; input.value = filtros.value.operador;
        form.appendChild(input);
    }
    if (filtros.value.estado) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'estado'; input.value = filtros.value.estado;
        form.appendChild(input);
    }

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
};

const cambiarPagina = (pagina) => {
    const params = new URLSearchParams(window.location.search);
    params.set('page', pagina);
    router.get(`${route('reportes.productos')}?${params.toString()}`, {}, { preserveState: true });
};
</script>

<template>
    <Head title="Reporte de Productos y Servicios" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Reporte de Productos y Servicios</h2>
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
                <el-form-item label="Tipo" class="mb-0">
                    <el-select v-model="filtros.tipo_registro" placeholder="Todos" clearable style="width: 160px;">
                        <el-option label="Producto" value="producto" />
                        <el-option label="Servicio" value="servicio" />
                    </el-select>
                </el-form-item>

                <el-form-item label="Operador" class="mb-0">
                    <el-select v-model="filtros.operador" placeholder="Todos" clearable style="width: 160px;">
                        <el-option v-for="o in filtrosGlobales.operadores" :key="o" :label="o" :value="o" />
                    </el-select>
                </el-form-item>

                <el-form-item label="Estado" class="mb-0">
                    <el-select v-model="filtros.estado" placeholder="Todos" clearable style="width: 160px;">
                        <el-option label="Activo" value="activo" />
                        <el-option label="Inactivo" value="inactivo" />
                    </el-select>
                </el-form-item>
                
                <el-form-item class="mb-0">
                    <el-button type="info" plain :icon="Refresh" @click="limpiarFiltros">Limpiar</el-button>
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
                <el-table-column label="Img" width="70" align="center">
                    <template #default="scope">
                        <el-image
                            v-if="scope.row.imagen"
                            :src="'/storage/' + scope.row.imagen"
                            :preview-src-list="['/storage/' + scope.row.imagen]"
                            fit="cover"
                            style="width: 40px; height: 40px; border-radius: 6px;"
                            preview-teleported
                        />
                        <div v-else class="w-10 h-10 bg-gray-100 rounded-md flex items-center justify-center">
                            <span class="text-gray-300 text-xs">—</span>
                        </div>
                    </template>
                </el-table-column>
                <el-table-column type="index" label="Nº" width="60" align="center" />
                <el-table-column prop="nombre" label="Nombre" min-width="200" />
                <el-table-column label="Tipo" width="100" align="center">
                    <template #default="scope">
                        <el-tag :type="scope.row.tipo === 'producto' ? 'primary' : 'warning'" size="small">
                            {{ scope.row.tipo.toUpperCase() }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="operador" label="Operador" width="120" />
                <el-table-column label="Precio Bs" width="100" align="right">
                    <template #default="scope">
                        <span class="font-bold text-blue-600">{{ Number(scope.row.precio_venta).toFixed(2) }}</span>
                    </template>
                </el-table-column>
                <el-table-column prop="stock" label="Stock" width="100" align="center">
                    <template #default="scope">
                        <span v-if="scope.row.tipo === 'producto'" :class="scope.row.stock < 10 ? 'text-red-500 font-bold' : ''">
                            {{ scope.row.stock }}
                        </span>
                        <span v-else class="text-gray-400">N/A</span>
                    </template>
                </el-table-column>
                <el-table-column label="Estado" width="100" align="center">
                    <template #default="scope">
                        <el-tag :type="scope.row.estado === 'activo' ? 'success' : 'danger'" size="small">
                            {{ scope.row.estado.toUpperCase() }}
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
