<script setup>
import { ref, watch, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { Search, Plus, Edit, Delete } from '@element-plus/icons-vue';
import { ElMessageBox, ElMessage } from 'element-plus';
import debounce from 'lodash/debounce';

const props = defineProps({
    usuarios: Object
});

// Filtros de búsqueda
const filtros = ref({
    buscar: new URLSearchParams(window.location.search).get('buscar') || '',
    rol: new URLSearchParams(window.location.search).get('rol') || '',
    estado: new URLSearchParams(window.location.search).get('estado') || '',
});

const buscar = debounce(() => {
    let params = {};
    if (filtros.value.buscar) params.buscar = filtros.value.buscar;
    if (filtros.value.rol) params.rol = filtros.value.rol;
    if (filtros.value.estado) params.estado = filtros.value.estado;

    router.get(route('usuarios.index'), params, { preserveState: true, replace: true });
}, 500);

watch(filtros, buscar, { deep: true });

// Modal Formulario
const dialogVisible = ref(false);
const modoEdicion = ref(false);
const usuarioIdEdicion = ref(null);
const uploadRef = ref(null);
const fotoPreview = ref(null);
const cambiarPassword = ref(false);

const serviciosOpciones = [
    'Recargas al Paso', 'Chips Nuevos', 'Internet (Megas)', 
    'Tigo Money / Yape', 'Pago de Servicios Básicos', 'Servicio Técnico'
];

const form = useForm({
    nombre_completo: '',
    codigo: '',
    password: '',
    rol: '',
    foto: null,
    estado: 'habilitado',
    servicios_asignados_json: [],
    visualizar_ganancias: false,
    omitir_bloqueo_horario: false,
    modulos_habilitados: [],
});

const rules = {
    nombre_completo: [{ required: true, message: 'Nombre completo es obligatorio', trigger: 'blur' }],
    codigo: [{ required: true, message: 'Código de acceso es obligatorio', trigger: 'blur' }],
    rol: [{ required: true, message: 'Rol es obligatorio', trigger: 'change' }],
    estado: [{ required: true, message: 'Estado es obligatorio', trigger: 'change' }],
};

const abrirModalCrear = () => {
    modoEdicion.value = false;
    usuarioIdEdicion.value = null;
    form.reset();
    form.clearErrors();
    fotoPreview.value = null;
    cambiarPassword.value = true;
    dialogVisible.value = true;
};

const abrirModalEditar = (usuario) => {
    modoEdicion.value = true;
    usuarioIdEdicion.value = usuario.id;
    form.clearErrors();
    
    form.nombre_completo = usuario.nombre_completo;
    form.codigo = usuario.codigo;
    form.password = '';
    form.rol = usuario.roles[0]?.name || '';
    form.estado = usuario.estado;
    form.servicios_asignados_json = usuario.servicios_asignados_json || [];
    form.visualizar_ganancias = usuario.visualizar_ganancias;
    form.omitir_bloqueo_horario = usuario.omitir_bloqueo_horario;
    form.modulos_habilitados = usuario.permissions ? usuario.permissions.map(p => p.name) : [];
    form.foto = null;

    fotoPreview.value = usuario.foto ? `/storage/${usuario.foto}` : null;
    cambiarPassword.value = false;
    dialogVisible.value = true;
};

const handleFotoChange = (file) => {
    form.foto = file.raw;
    fotoPreview.value = URL.createObjectURL(file.raw);
};

const guardarUsuario = () => {
    if (modoEdicion.value) {
        // En Inertia con archivos, update debe hacerse vía POST usando _method: 'put'
        const url = route('usuarios.update', usuarioIdEdicion.value);
        form.transform((data) => ({
            ...data,
            _method: 'put',
        })).post(url, {
            preserveScroll: true,
            onSuccess: () => dialogVisible.value = false
        });
    } else {
        form.post(route('usuarios.store'), {
            preserveScroll: true,
            onSuccess: () => dialogVisible.value = false
        });
    }
};

const eliminarUsuario = (usuario) => {
    ElMessageBox.confirm(
        `¿Está seguro de eliminar al usuario ${usuario.nombre_completo}?`,
        'Advertencia',
        {
            confirmButtonText: 'Sí, Eliminar',
            cancelButtonText: 'Cancelar',
            type: 'warning',
        }
    ).then(() => {
        router.delete(route('usuarios.destroy', usuario.id), {
            preserveScroll: true,
            onError: (err) => {
                if (err.error) ElMessage.error(err.error);
            }
        });
    }).catch(() => {});
};

const cambiarPagina = (pagina) => {
    const params = new URLSearchParams(window.location.search);
    params.set('page', pagina);
    router.get(`${route('usuarios.index')}?${params.toString()}`);
};

// --- Automatización de permisos al cambiar el rol ---
watch(() => form.rol, (nuevoRol) => {
    // Si estamos editando y los módulos ya están cargados, podríamos no querer sobreescribirlos 
    // automáticamente a menos que el usuario cambie de rol manualmente durante la edición.
    // Para simplificar: si el rol cambia, asignamos los permisos por defecto de ese rol.
    if (nuevoRol === 'administrador') {
        form.modulos_habilitados = [
            'gestionar_configuracion', 'gestionar_usuarios', 'gestionar_productos_servicios',
            'gestionar_inventario', 'gestionar_dashboard_movimientos', 'gestionar_apertura_caja',
            'gestionar_cierre_caja', 'aprobar_cierre_caja', 'gestionar_ventas', 'anular_ventas',
            'ver_reportes_generales', 'visualizar_ganancias'
        ];
        form.visualizar_ganancias = true;
    } else if (nuevoRol === 'vendedor') {
        form.modulos_habilitados = [
            'gestionar_dashboard_movimientos', 'gestionar_apertura_caja', 'gestionar_cierre_caja',
            'gestionar_ventas'
        ];
        form.visualizar_ganancias = false;
        form.omitir_bloqueo_horario = false;
    }
});

</script>

<template>
    <Head title="Gestión de Usuarios" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Gestión de Usuarios del Sistema</h2>
        </template>

        <!-- Filtros y Acciones -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
            <div class="flex flex-wrap gap-3">
                <el-input v-model="filtros.buscar" placeholder="Buscar nombre o código" :prefix-icon="Search" clearable class="w-64" />
                
                <el-select v-model="filtros.rol" placeholder="Filtrar por Rol" clearable class="w-40">
                    <el-option label="Administrador" value="administrador" />
                    <el-option label="Vendedor" value="vendedor" />
                </el-select>

                <el-select v-model="filtros.estado" placeholder="Estado" clearable class="w-32">
                    <el-option label="Habilitado" value="habilitado" />
                    <el-option label="Deshabilitado" value="deshabilitado" />
                </el-select>
            </div>
            
            <el-button type="primary" :icon="Plus" @click="abrirModalCrear">
                Nuevo Usuario
            </el-button>
        </div>

        <!-- Tabla -->
        <el-card class="shadow-sm">
            <el-table :data="usuarios.data" border stripe style="width: 100%">
                <el-table-column label="Perfil" width="80" align="center">
                    <template #default="scope">
                        <el-avatar :size="40" :src="scope.row.foto ? `/storage/${scope.row.foto}` : ''">
                            <span v-if="!scope.row.foto">{{ scope.row.nombre_completo.charAt(0).toUpperCase() }}</span>
                        </el-avatar>
                    </template>
                </el-table-column>

                <el-table-column prop="nombre_completo" label="Nombre Completo" min-width="180" />
                <el-table-column prop="codigo" label="Código Acceso" width="130" align="center">
                    <template #default="scope">
                        <span class="font-mono bg-gray-100 px-2 py-1 rounded text-sm">{{ scope.row.codigo }}</span>
                    </template>
                </el-table-column>
                
                <el-table-column label="Rol" width="130">
                    <template #default="scope">
                        <el-tag :type="scope.row.roles[0]?.name === 'administrador' ? 'primary' : 'info'">
                            {{ (scope.row.roles[0]?.name || 'N/A').toUpperCase() }}
                        </el-tag>
                    </template>
                </el-table-column>

                <el-table-column label="Estado" width="110" align="center">
                    <template #default="scope">
                        <el-tag :type="scope.row.estado === 'habilitado' ? 'success' : 'danger'">
                            {{ scope.row.estado.toUpperCase() }}
                        </el-tag>
                    </template>
                </el-table-column>

                <el-table-column label="Permisos Especiales" min-width="180">
                    <template #default="scope">
                        <div class="flex flex-col gap-1">
                            <el-tag v-if="scope.row.visualizar_ganancias" size="small" type="warning">Ve Ganancias</el-tag>
                            <el-tag v-if="scope.row.omitir_bloqueo_horario" size="small" type="danger">Salta Horario</el-tag>
                            <span v-if="!scope.row.visualizar_ganancias && !scope.row.omitir_bloqueo_horario" class="text-gray-400 text-xs">Ninguno</span>
                        </div>
                    </template>
                </el-table-column>

                <el-table-column label="Acciones" width="120" align="center" fixed="right">
                    <template #default="scope">
                        <el-button-group>
                            <el-button type="primary" :icon="Edit" size="small" @click="abrirModalEditar(scope.row)" />
                            <el-button type="danger" :icon="Delete" size="small" @click="eliminarUsuario(scope.row)" />
                        </el-button-group>
                    </template>
                </el-table-column>
            </el-table>

            <div class="mt-4 flex justify-end">
                <el-pagination
                    background
                    layout="prev, pager, next, total"
                    :total="usuarios.total"
                    :page-size="usuarios.per_page"
                    :current-page="usuarios.current_page"
                    @current-change="cambiarPagina"
                />
            </div>
        </el-card>

        <!-- Modal Crear/Editar -->
        <el-dialog
            v-model="dialogVisible"
            :title="modoEdicion ? 'Editar Usuario' : 'Crear Nuevo Usuario'"
            width="600px"
            destroy-on-close
        >
            <el-form :model="form" :rules="rules" ref="formRef" label-width="140px" label-position="left">
                
                <el-form-item label="Foto Perfil" :error="form.errors.foto">
                    <div class="flex items-center gap-4">
                        <el-avatar :size="60" :src="fotoPreview" class="bg-gray-200">
                            <el-icon :size="24"><User /></el-icon>
                        </el-avatar>
                        <el-upload
                            ref="uploadRef"
                            action=""
                            :auto-upload="false"
                            :show-file-list="false"
                            accept="image/jpeg,image/png,image/jpg"
                            @change="handleFotoChange"
                        >
                            <el-button size="small">Cambiar Foto</el-button>
                        </el-upload>
                    </div>
                </el-form-item>

                <el-form-item label="Nombre Completo" prop="nombre_completo" :error="form.errors.nombre_completo">
                    <el-input v-model="form.nombre_completo" placeholder="Nombre completo del empleado" />
                </el-form-item>

                <el-form-item label="Código de Acceso" prop="codigo" :error="form.errors.codigo">
                    <el-input v-model="form.codigo" placeholder="Ej: VEND-01" class="w-full sm:w-1/2" />
                </el-form-item>

                <div v-if="modoEdicion" class="mb-4 ml-[140px]">
                    <el-checkbox v-model="cambiarPassword">Cambiar contraseña</el-checkbox>
                </div>

                <el-form-item v-if="cambiarPassword || !modoEdicion" :label="modoEdicion ? 'Nueva Contraseña' : 'Contraseña'" :required="!modoEdicion" :error="form.errors.password">
                    <el-input v-model="form.password" type="password" show-password placeholder="Mínimo 8 caracteres" />
                </el-form-item>

                <el-form-item label="Rol en Sistema" prop="rol" :error="form.errors.rol">
                    <el-select v-model="form.rol" placeholder="Seleccione un rol" class="w-full">
                        <el-option label="Administrador" value="administrador" />
                        <el-option label="Vendedor" value="vendedor" />
                    </el-select>
                </el-form-item>

                <el-form-item label="Estado" prop="estado" :error="form.errors.estado">
                    <el-select v-model="form.estado" class="w-full sm:w-1/2">
                        <el-option label="Habilitado" value="habilitado" />
                        <el-option label="Deshabilitado" value="deshabilitado" />
                    </el-select>
                </el-form-item>

                <!-- Módulos Habilitados -->
                <div class="bg-gray-50 p-4 rounded mt-4 border">
                    <h4 class="font-semibold text-gray-700 mb-3 text-sm">Módulos y Permisos Habilitados</h4>
                    <el-checkbox-group v-model="form.modulos_habilitados">
                        <div class="grid grid-cols-2 gap-2">
                            <el-checkbox label="gestionar_configuracion">Configuración</el-checkbox>
                            <el-checkbox label="gestionar_usuarios">Usuarios</el-checkbox>
                            <el-checkbox label="gestionar_productos_servicios">Productos y Servicios</el-checkbox>
                            <el-checkbox label="gestionar_inventario">Inventario</el-checkbox>
                            <el-checkbox label="gestionar_dashboard_movimientos">Dashboard y Movs.</el-checkbox>
                            <el-checkbox label="gestionar_apertura_caja">Apertura Caja</el-checkbox>
                            <el-checkbox label="gestionar_cierre_caja">Cierre Caja</el-checkbox>
                            <el-checkbox label="aprobar_cierre_caja">Aprobar Cierres</el-checkbox>
                            <el-checkbox label="gestionar_ventas">Ventas</el-checkbox>
                            <el-checkbox label="anular_ventas">Anular Ventas</el-checkbox>
                            <el-checkbox label="ver_reportes_generales">Reportes Generales</el-checkbox>
                            <el-checkbox label="visualizar_ganancias">Ver Ganancias</el-checkbox>
                        </div>
                    </el-checkbox-group>
                </div>

                <!-- Configuración específica para Vendedores -->
                <div v-if="form.rol === 'vendedor'" class="bg-gray-50 p-4 rounded mt-4 border">
                    <h4 class="font-semibold text-gray-700 mb-3 text-sm">Opciones para Vendedor</h4>
                    
                    <el-form-item label="Servicios Autorizados" label-width="160px" :error="form.errors.servicios_asignados_json">
                        <el-select v-model="form.servicios_asignados_json" multiple placeholder="Seleccione los servicios" class="w-full">
                            <el-option v-for="srv in serviciosOpciones" :key="srv" :label="srv" :value="srv" />
                        </el-select>
                    </el-form-item>
                </div>

                <!-- Configuración específica para Administradores -->
                <div v-if="form.rol === 'administrador'" class="bg-red-50 p-4 rounded mt-4 border border-red-100">
                    <h4 class="font-semibold text-red-800 mb-3 text-sm">Privilegios Especiales</h4>
                    
                    <el-form-item label="Saltar Bloqueo Horario" label-width="160px">
                        <el-switch v-model="form.omitir_bloqueo_horario" active-text="Sí" inactive-text="No" />
                        <div class="text-xs text-red-500 w-full mt-1">Permite iniciar sesión fuera del horario configurado para admins.</div>
                    </el-form-item>
                </div>

            </el-form>
            
            <template #footer>
                <div class="dialog-footer flex justify-end gap-2">
                    <el-button @click="dialogVisible = false">Cancelar</el-button>
                    <el-button type="primary" @click="guardarUsuario" :loading="form.processing">
                        {{ modoEdicion ? 'Actualizar Usuario' : 'Crear Usuario' }}
                    </el-button>
                </div>
            </template>
        </el-dialog>
    </PanelLayout>
</template>
