<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { ElNotification } from 'element-plus';

import { usePermisos } from '@/Composables/usePermisos';

// --- Estado reactivo ---
const page = usePage();
const sidebarColapsado = ref(false);
const drawerVisible = ref(false);
const esPantallaPequena = ref(false);

const { tienePermiso, tieneRol, puedeVerGanancias } = usePermisos();

// --- Datos del usuario autenticado ---
const usuario = computed(() => page.props.auth?.user || {});
const nombreUsuario = computed(() => {
    return usuario.value.nombre_completo || usuario.value.name || 'Usuario';
});

// --- Flash messages (auto-dismiss con ElNotification) ---
const flashExito = computed(() => page.props.flash?.exito || page.props.flash?.success || null);
const flashError = computed(() => page.props.flash?.error || null);
const flashEliminado = computed(() => page.props.flash?.eliminado || null);

watch(flashExito, (msg) => {
    if (msg) {
        ElNotification({ title: '¡Operación Exitosa!', message: msg, type: 'success', duration: 4000, position: 'top-right' });
    }
});

watch(flashError, (msg) => {
    if (msg) {
        ElNotification({ title: 'Error', message: msg, type: 'error', duration: 5000, position: 'top-right' });
    }
});

watch(flashEliminado, (msg) => {
    if (msg) {
        ElNotification({ title: 'Registro Eliminado', message: msg, type: 'error', duration: 4000, position: 'top-right' });
    }
});

// --- Nombre del sistema ---
const nombreSistema = computed(() => {
    return page.props.configuracion_sistema?.alias || page.props.app_name || 'Sistema Telefonía';
});

// --- Breadcrumbs dinámicos ---
const breadcrumbs = computed(() => {
    const url = page.url;
    const mapa = {
        '/dashboard': [{ label: 'Dashboard' }],
        '/configuracion': [{ label: 'Configuración' }],
        '/usuarios': [{ label: 'Usuarios' }],
        '/productos-servicios': [{ label: 'Productos y Servicios' }],
        '/inventario': [{ label: 'Inventario' }],
        '/aperturas-caja': [{ label: 'Caja', ruta: null }, { label: 'Aperturas' }],
        '/cierres-caja/create': [{ label: 'Caja', ruta: null }, { label: 'Cierres', ruta: 'cierres-caja.index' }, { label: 'Nuevo Cierre' }],
        '/cierres-caja': [{ label: 'Caja', ruta: null }, { label: 'Cierres' }],
        '/ventas/create': [{ label: 'Ventas', ruta: 'ventas.index' }, { label: 'Nueva Venta' }],
        '/ventas': [{ label: 'Ventas' }],
        '/ajustes-usuario': [{ label: 'Mi Perfil' }],
        '/auditoria': [{ label: 'Reportes', ruta: null }, { label: 'Auditoría' }],
        '/reportes/usuarios': [{ label: 'Reportes', ruta: null }, { label: 'Usuarios' }],
        '/reportes/productos': [{ label: 'Reportes', ruta: null }, { label: 'Productos' }],
        '/reportes/ventas': [{ label: 'Reportes', ruta: null }, { label: 'Ventas' }],
        '/reportes/movimientos': [{ label: 'Reportes', ruta: null }, { label: 'Movimientos' }],
        '/reportes/caja': [{ label: 'Reportes', ruta: null }, { label: 'Caja' }],
        '/reportes/saldo-servicios': [{ label: 'Reportes', ruta: null }, { label: 'Saldo Servicios' }],
        '/reportes/ganancias': [{ label: 'Reportes', ruta: null }, { label: 'Ganancias' }],
        '/reportes/inventario': [{ label: 'Reportes', ruta: null }, { label: 'Inventario' }],
    };

    // Buscar coincidencia exacta primero, luego parcial
    const path = url.split('?')[0]; // sin query string
    if (mapa[path]) return mapa[path];

    // Buscar parcial (ej. /ventas/5 => Ventas > Detalle)
    if (path.startsWith('/ventas/')) return [{ label: 'Ventas', ruta: 'ventas.index' }, { label: 'Detalle de Venta' }];
    if (path.startsWith('/cierres-caja/')) return [{ label: 'Caja', ruta: null }, { label: 'Cierres', ruta: 'cierres-caja.index' }, { label: 'Detalle de Cierre' }];
    if (path.startsWith('/productos-servicios/')) return [{ label: 'Productos y Servicios', ruta: 'productos-servicios.index' }, { label: 'Detalle' }];
    if (path.startsWith('/inventario/')) return [{ label: 'Inventario', ruta: 'inventario.index' }, { label: 'Detalle' }];
    if (path.startsWith('/usuarios/')) return [{ label: 'Usuarios', ruta: 'usuarios.index' }, { label: 'Editar Usuario' }];

    return [{ label: 'Inicio' }];
});

const navegarBreadcrumb = (ruta) => {
    if (ruta) {
        try {
            router.visit(route(ruta));
        } catch (e) { /* ruta no válida */ }
    }
};

// --- Ítems del menú de navegación filtrados por permisos ---
const itemsMenu = computed(() => {
    const items = [
        { indice: 'dashboard', titulo: 'Dashboard', icono: 'Odometer', ruta: 'dashboard', mostrar: tienePermiso('gestionar_dashboard_movimientos') },
        { indice: 'configuracion', titulo: 'Configuración', icono: 'Setting', ruta: 'configuracion.index', mostrar: tienePermiso('gestionar_configuracion') },
        { indice: 'usuarios', titulo: 'Usuarios', icono: 'User', ruta: 'usuarios.index', mostrar: tienePermiso('gestionar_usuarios') },
        { indice: 'productos', titulo: 'Productos y Servicios', icono: 'ShoppingBag', ruta: 'productos-servicios.index', mostrar: tienePermiso('gestionar_productos_servicios') },
        { indice: 'inventario', titulo: 'Inventario', icono: 'Box', ruta: 'inventario.index', mostrar: tienePermiso('gestionar_inventario') },
        { 
            indice: 'caja', 
            titulo: 'Caja', 
            icono: 'Wallet', 
            mostrar: tieneRol('administrador'),
            subitems: [
                { indice: 'aperturas-caja', titulo: 'Aperturas', icono: 'Unlock', ruta: 'aperturas-caja.index', mostrar: true },
                { indice: 'cierres-caja', titulo: 'Cierres', icono: 'Lock', ruta: 'cierres-caja.index', mostrar: true }
            ]
        },
        { indice: 'ventas', titulo: 'Ventas', icono: 'ShoppingCart', ruta: 'ventas.index', mostrar: tienePermiso('gestionar_ventas') },
        { 
            indice: 'reportes', 
            titulo: 'Reportes', 
            icono: 'DataAnalysis', 
            mostrar: tienePermiso('ver_reportes_generales'),
            subitems: [
                { indice: 'reportes-usuarios', titulo: 'Usuarios', icono: 'User', ruta: 'reportes.usuarios', mostrar: tieneRol('administrador') },
                { indice: 'reportes-productos', titulo: 'Productos', icono: 'ShoppingBag', ruta: 'reportes.productos', mostrar: true },
                { indice: 'reportes-ventas', titulo: 'Ventas', icono: 'ShoppingCart', ruta: 'reportes.ventas', mostrar: true },
                { indice: 'reportes-movimientos', titulo: 'Movimientos', icono: 'DataLine', ruta: 'reportes.movimientos', mostrar: true },
                { indice: 'reportes-caja', titulo: 'Caja', icono: 'Wallet', ruta: 'reportes.caja', mostrar: true },
                { indice: 'reportes-saldos', titulo: 'Saldo Servicios', icono: 'Document', ruta: 'reportes.saldo-servicios', mostrar: tieneRol('administrador') },
                { indice: 'reportes-ganancias', titulo: 'Ganancias', icono: 'Money', ruta: 'reportes.ganancias', mostrar: tienePermiso('visualizar_ganancias') || tieneRol('administrador') },
                { indice: 'reportes-inventario', titulo: 'Inventario', icono: 'Box', ruta: 'reportes.inventario', mostrar: tieneRol('administrador') },
                { indice: 'auditoria', titulo: 'Auditoría Logs', icono: 'List', ruta: 'auditoria.index', mostrar: tieneRol('administrador') }
            ]
        },
        { indice: 'perfil', titulo: 'Mi Perfil', icono: 'UserFilled', ruta: 'ajustes-usuario.index', mostrar: tienePermiso('gestionar_ajustes_usuario') || true },
    ];
    
    return items.filter(item => item.mostrar);
});

// --- Ruta activa actual ---
const rutaActiva = computed(() => {
    const itemActivo = itemsMenu.value.find(item => {
        if (item.ruta) {
            try {
                return route().current(item.ruta);
            } catch {
                return false;
            }
        }
        return false;
    });
    return itemActivo ? itemActivo.indice : 'dashboard';
});

// --- Detectar tamaño de pantalla ---
const verificarTamanoPantalla = () => {
    esPantallaPequena.value = window.innerWidth < 768;
    if (!esPantallaPequena.value) {
        drawerVisible.value = false;
    }
};

onMounted(() => {
    verificarTamanoPantalla();
    window.addEventListener('resize', verificarTamanoPantalla);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', verificarTamanoPantalla);
});

// --- Navegación del menú ---
const manejarSeleccionMenu = (indice) => {
    let itemSeleccionado = null;
    
    for (const item of itemsMenu.value) {
        if (item.indice === indice) {
            itemSeleccionado = item;
            break;
        }
        if (item.subitems) {
            const sub = item.subitems.find(s => s.indice === indice);
            if (sub) {
                itemSeleccionado = sub;
                break;
            }
        }
    }

    if (itemSeleccionado && itemSeleccionado.ruta) {
        // En el caso de cierres-caja.index, si la ruta aún no existe, prevengamos el error 500
        try {
            router.visit(route(itemSeleccionado.ruta));
        } catch (e) {
            ElMessage.warning('El módulo aún está en desarrollo.');
        }
        drawerVisible.value = false;
    }
};

// --- Cerrar sesión ---
const cerrarSesion = () => {
    router.post(route('logout'));
};

// --- Toggle sidebar ---
const alternarSidebar = () => {
    if (esPantallaPequena.value) {
        drawerVisible.value = !drawerVisible.value;
    } else {
        sidebarColapsado.value = !sidebarColapsado.value;
    }
};

// --- Manejar comandos del dropdown de usuario ---
const manejarComandoUsuario = (comando) => {
    switch (comando) {
        case 'perfil':
            router.visit(route('profile.edit'));
            break;
        case 'cerrar-sesion':
            cerrarSesion();
            break;
    }
};
</script>

<template>
    <div class="app-contenedor-principal">
        <!-- ==================== SIDEBAR DESKTOP ==================== -->
        <aside
            v-if="!esPantallaPequena"
            class="app-sidebar"
            :class="{ 'app-sidebar--colapsado': sidebarColapsado }"
        >
            <!-- Logo / Nombre del sistema -->
            <div class="app-sidebar__logo">
                <div class="app-sidebar__logo-icono">
                    <img v-if="$page.props.configuracion_sistema?.logo" :src="`/storage/${$page.props.configuracion_sistema.logo}`" alt="Logo" class="h-8 w-8 object-contain rounded" />
                    <el-icon v-else :size="28" color="#3b82f6">
                        <Headset />
                    </el-icon>
                </div>
                <transition name="fade-texto">
                    <span v-if="!sidebarColapsado" class="app-sidebar__logo-texto">
                        {{ nombreSistema }}
                    </span>
                </transition>
            </div>

            <!-- Menú de navegación -->
            <el-menu
                :default-active="rutaActiva"
                :collapse="sidebarColapsado"
                :collapse-transition="true"
                background-color="#1f2937"
                text-color="#e5e7eb"
                active-text-color="#3b82f6"
                class="app-sidebar__menu"
                @select="manejarSeleccionMenu"
            >
                <template v-for="item in itemsMenu" :key="item.indice">
                    <el-sub-menu v-if="item.subitems" :index="item.indice">
                        <template #title>
                            <el-icon>
                                <component :is="item.icono" />
                            </el-icon>
                            <span>{{ item.titulo }}</span>
                        </template>
                        <el-menu-item 
                            v-for="sub in item.subitems.filter(s => s.mostrar)" 
                            :key="sub.indice" 
                            :index="sub.indice"
                        >
                            <el-icon>
                                <component :is="sub.icono" />
                            </el-icon>
                            <template #title>{{ sub.titulo }}</template>
                        </el-menu-item>
                    </el-sub-menu>
                    
                    <el-menu-item v-else :index="item.indice" :title="item.titulo">
                        <el-icon>
                            <component :is="item.icono" />
                        </el-icon>
                        <template #title>{{ item.titulo }}</template>
                    </el-menu-item>
                </template>
            </el-menu>

            <!-- Versión / Footer del sidebar -->
            <div v-if="!sidebarColapsado" class="app-sidebar__footer">
                <span class="app-sidebar__version">v1.0.0</span>
            </div>
        </aside>

        <!-- ==================== DRAWER MOBILE ==================== -->
        <el-drawer
            v-model="drawerVisible"
            direction="ltr"
            :size="280"
            :show-close="false"
            :with-header="false"
        >
            <div class="app-sidebar app-sidebar--mobile">
                <!-- Logo mobile -->
                <div class="app-sidebar__logo">
                    <div class="app-sidebar__logo-icono">
                        <img v-if="$page.props.configuracion_sistema?.logo" :src="`/storage/${$page.props.configuracion_sistema.logo}`" alt="Logo" class="h-8 w-8 object-contain rounded" />
                        <el-icon v-else :size="28" color="#3b82f6">
                            <Headset />
                        </el-icon>
                    </div>
                    <span class="app-sidebar__logo-texto">
                        {{ nombreSistema }}
                    </span>
                </div>

                <!-- Menú mobile -->
                <el-menu
                    :default-active="rutaActiva"
                    background-color="#1f2937"
                    text-color="#e5e7eb"
                    active-text-color="#3b82f6"
                    class="app-sidebar__menu"
                    @select="manejarSeleccionMenu"
                >
                    <template v-for="item in itemsMenu" :key="item.indice">
                        <el-sub-menu v-if="item.subitems" :index="item.indice">
                            <template #title>
                                <el-icon>
                                    <component :is="item.icono" />
                                </el-icon>
                                <span>{{ item.titulo }}</span>
                            </template>
                            <el-menu-item 
                                v-for="sub in item.subitems.filter(s => s.mostrar)" 
                                :key="sub.indice" 
                                :index="sub.indice"
                            >
                                <el-icon>
                                    <component :is="sub.icono" />
                                </el-icon>
                                <template #title>{{ sub.titulo }}</template>
                            </el-menu-item>
                        </el-sub-menu>
                        
                        <el-menu-item v-else :index="item.indice" :title="item.titulo">
                            <el-icon>
                                <component :is="item.icono" />
                            </el-icon>
                            <template #title>{{ item.titulo }}</template>
                        </el-menu-item>
                    </template>
                </el-menu>
            </div>
        </el-drawer>

        <!-- ==================== CONTENIDO PRINCIPAL ==================== -->
        <div
            class="app-contenido"
            :class="{ 'app-contenido--expandido': sidebarColapsado && !esPantallaPequena }"
        >
            <!-- Header superior -->
            <header class="app-header">
                <div class="app-header__izquierda">
                    <!-- Botón hamburguesa -->
                    <button
                        class="app-header__boton-menu"
                        @click="alternarSidebar"
                        :title="esPantallaPequena ? 'Abrir menú' : (sidebarColapsado ? 'Expandir menú' : 'Colapsar menú')"
                    >
                        <el-icon :size="22">
                            <Fold v-if="!sidebarColapsado && !esPantallaPequena" />
                            <Expand v-else />
                        </el-icon>
                    </button>

                    <!-- Breadcrumbs + Título de página -->
                    <div class="app-header__titulo">
                        <div class="app-breadcrumbs">
                            <span 
                                v-for="(crumb, idx) in breadcrumbs" 
                                :key="idx" 
                                class="app-breadcrumbs__item"
                            >
                                <span 
                                    v-if="crumb.ruta && idx < breadcrumbs.length - 1" 
                                    class="app-breadcrumbs__link" 
                                    @click="navegarBreadcrumb(crumb.ruta)"
                                >
                                    {{ crumb.label }}
                                </span>
                                <span v-else-if="idx < breadcrumbs.length - 1" class="app-breadcrumbs__text">
                                    {{ crumb.label }}
                                </span>
                                <span v-else class="app-breadcrumbs__current">
                                    {{ crumb.label }}
                                </span>
                                <span v-if="idx < breadcrumbs.length - 1" class="app-breadcrumbs__separator">/</span>
                            </span>
                        </div>
                        <slot name="titulo-pagina" />
                    </div>
                </div>

                <div class="app-header__derecha">
                    <!-- Saludo al usuario -->
                    <span class="app-header__saludo">
                        Hola, <strong>{{ nombreUsuario }}</strong>
                    </span>

                    <!-- Menú desplegable del usuario -->
                    <el-dropdown trigger="click" @command="manejarComandoUsuario">
                        <div class="app-header__avatar-contenedor">
                            <el-avatar
                                :size="36"
                                class="app-header__avatar"
                                :src="usuario.foto ? `/storage/${usuario.foto}` : ''"
                            >
                                <span v-if="!usuario.foto">{{ nombreUsuario.charAt(0).toUpperCase() }}</span>
                            </el-avatar>
                            <el-icon class="app-header__avatar-flecha" :size="14">
                                <ArrowDown />
                            </el-icon>
                        </div>
                        <template #dropdown>
                            <el-dropdown-menu>
                                <el-dropdown-item command="perfil">
                                    <el-icon><UserFilled /></el-icon>
                                    Mi Perfil
                                </el-dropdown-item>
                                <el-dropdown-item divided command="cerrar-sesion">
                                    <el-icon><SwitchButton /></el-icon>
                                    Cerrar sesión
                                </el-dropdown-item>
                            </el-dropdown-menu>
                        </template>
                    </el-dropdown>
                </div>
            </header>

            <!-- Flash messages ahora se manejan via ElNotification (watch arriba) -->

            <!-- Área de contenido principal -->
            <main class="app-main">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* ==========================================================================
   CONTENEDOR PRINCIPAL
   ========================================================================== */
.app-contenedor-principal {
    display: flex;
    min-height: 100vh;
    background-color: #f3f4f6;
}

/* ==========================================================================
   SIDEBAR
   ========================================================================== */
.app-sidebar {
    width: 260px;
    height: 100vh;
    background-color: #1f2937;
    display: flex;
    flex-direction: column;
    transition: width 0.3s ease;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 100;
    overflow: hidden;
}

.app-sidebar--colapsado {
    width: 64px;
}

.app-sidebar--mobile {
    position: relative;
    min-height: 100%;
    width: 100%;
}

/* Logo del sidebar */
.app-sidebar__logo {
    display: flex;
    align-items: center;
    padding: 16px 20px;
    height: 60px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    gap: 12px;
    flex-shrink: 0;
}

.app-sidebar__logo-icono {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.app-sidebar__logo-texto {
    font-size: 15px;
    font-weight: 600;
    color: #ffffff;
    white-space: nowrap;
    overflow: hidden;
    letter-spacing: 0.3px;
}

/* Menú del sidebar */
.app-sidebar__menu {
    border-right: none !important;
    flex: 1;
    overflow-y: auto;
    padding-top: 8px;
}

/* Footer del sidebar */
.app-sidebar__footer {
    padding: 12px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    flex-shrink: 0;
}

.app-sidebar__version {
    font-size: 11px;
    color: #6b7280;
    letter-spacing: 0.5px;
}

/* ==========================================================================
   CONTENIDO PRINCIPAL
   ========================================================================== */
.app-contenido {
    flex: 1;
    margin-left: 260px;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    transition: margin-left 0.3s ease;
}

.app-contenido--expandido {
    margin-left: 64px;
}

/* ==========================================================================
   HEADER
   ========================================================================== */
.app-header {
    height: 60px;
    background-color: #ffffff;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    position: sticky;
    top: 0;
    z-index: 90;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.app-header__izquierda {
    display: flex;
    align-items: center;
    gap: 16px;
}

.app-header__boton-menu {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: none;
    background-color: transparent;
    border-radius: 8px;
    cursor: pointer;
    color: #6b7280;
    transition: all 0.2s ease;
}

.app-header__boton-menu:hover {
    background-color: #f3f4f6;
    color: #374151;
}

.app-header__titulo {
    font-size: 16px;
    font-weight: 500;
    color: #374151;
}

.app-header__derecha {
    display: flex;
    align-items: center;
    gap: 16px;
}

.app-header__saludo {
    font-size: 14px;
    color: #6b7280;
}

.app-header__saludo strong {
    color: #374151;
    font-weight: 600;
}

.app-header__avatar-contenedor {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 8px;
    transition: background-color 0.2s ease;
}

.app-header__avatar-contenedor:hover {
    background-color: #f3f4f6;
}

.app-header__avatar {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
    font-weight: 600;
    font-size: 14px;
}

.app-header__avatar-flecha {
    color: #9ca3af;
}

/* ==========================================================================
   ÁREA PRINCIPAL DE CONTENIDO
   ========================================================================== */
.app-main {
    flex: 1;
    padding: 24px;
    background-color: #f3f4f6;
    min-height: calc(100vh - 60px);
    overflow-x: hidden;
}

/* ==========================================================================
   BREADCRUMBS
   ========================================================================== */
.app-breadcrumbs {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-bottom: 2px;
    flex-wrap: wrap;
}

.app-breadcrumbs__item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.app-breadcrumbs__link {
    font-size: 12px;
    color: #3b82f6;
    cursor: pointer;
    transition: color 0.2s;
}

.app-breadcrumbs__link:hover {
    color: #2563eb;
    text-decoration: underline;
}

.app-breadcrumbs__text {
    font-size: 12px;
    color: #9ca3af;
}

.app-breadcrumbs__current {
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
}

.app-breadcrumbs__separator {
    font-size: 11px;
    color: #d1d5db;
    margin: 0 2px;
}

/* ==========================================================================
   FLASH MESSAGES
   ========================================================================== */
.app-flash {
    margin: 16px 24px 0;
}

/* ==========================================================================
   TRANSICIONES
   ========================================================================== */
.fade-texto-enter-active,
.fade-texto-leave-active {
    transition: opacity 0.2s ease;
}

.fade-texto-enter-from,
.fade-texto-leave-to {
    opacity: 0;
}

.fade-alerta-enter-active {
    transition: all 0.3s ease;
}

.fade-alerta-leave-active {
    transition: all 0.2s ease;
}

.fade-alerta-enter-from {
    opacity: 0;
    transform: translateY(-10px);
}

.fade-alerta-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}

/* ==========================================================================
   RESPONSIVE
   ========================================================================== */
@media (max-width: 767px) {
    .app-contenido {
        margin-left: 0;
    }

    .app-header {
        padding: 0 16px;
    }

    .app-header__saludo {
        display: none;
    }

    .app-main {
        padding: 16px;
    }

    .app-flash {
        margin: 12px 16px 0;
    }
}
</style>
