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

const filtros = ref({
    rol: new URLSearchParams(window.location.search).get('rol') || '',
    estado: new URLSearchParams(window.location.search).get('estado') || ''
});

const buscar = debounce(() => {
    let params = {};
    if (filtros.value.rol) params.rol = filtros.value.rol;
    if (filtros.value.estado) params.estado = filtros.value.estado;

    router.get(route('reportes.usuarios'), params, { preserveState: true, replace: true });
}, 500);

watch(filtros, buscar, { deep: true });

const orientacionPdf = ref('L');

const exportarPdf = () => {
    const params = new URLSearchParams();
    if (filtros.value.rol) params.append('rol', filtros.value.rol);
    if (filtros.value.estado) params.append('estado', filtros.value.estado);
    params.append('orientacion', orientacionPdf.value);

    window.open(route('reportes.exportar-pdf', 'usuarios') + '?' + params.toString(), '_blank');
};

const exportarExcel = () => {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = route('reportes.exportar-excel', 'usuarios');
    
    const csrfToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = csrfToken || page.props.csrf_token || '';
    form.appendChild(csrfInput);

    if (filtros.value.rol) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'rol'; input.value = filtros.value.rol;
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
    router.get(`${route('reportes.usuarios')}?${params.toString()}`, {}, { preserveState: true });
};
</script>

<template>
    <Head title="Reporte de Usuarios" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Reporte de Usuarios</h2>
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
                <el-form-item label="Rol" class="mb-0">
                    <el-select v-model="filtros.rol" placeholder="Todos" clearable class="w-40">
                        <el-option label="Administrador" value="administrador" />
                        <el-option label="Vendedor" value="vendedor" />
                    </el-select>
                </el-form-item>

                <el-form-item label="Estado" class="mb-0">
                    <el-select v-model="filtros.estado" placeholder="Todos" clearable class="w-32">
                        <el-option label="Activo" value="activo" />
                        <el-option label="Inactivo" value="inactivo" />
                    </el-select>
                </el-form-item>
            </el-form>
        </el-card>

        <!-- Tabla de Datos -->
        <el-card class="shadow-sm">
            <template #header>
                <div class="flex items-center justify-between">
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

            <el-table :data="datos.data" border stripe style="width: 100%" v-if="datos && datos.data.length > 0">
                <el-table-column prop="codigo" label="Código" width="120" />
                <el-table-column prop="nombre_completo" label="Nombre Completo" min-width="200" />
                <el-table-column label="Rol" width="150">
                    <template #default="scope">
                        <el-tag :type="scope.row.roles[0]?.name === 'administrador' ? 'primary' : 'info'" size="small">
                            {{ (scope.row.roles[0]?.name || 'N/A').toUpperCase() }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column label="Estado" width="100" align="center">
                    <template #default="scope">
                        <el-tag :type="scope.row.estado === 'activo' ? 'success' : 'danger'" size="small">
                            {{ scope.row.estado.toUpperCase() }}
                        </el-tag>
                    </template>
                </el-table-column>
                <el-table-column prop="created_at" label="Fecha Registro" width="160">
                    <template #default="scope">
                        {{ new Date(scope.row.created_at).toLocaleString() }}
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
