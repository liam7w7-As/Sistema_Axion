<script setup>
import { computed, ref, watch, reactive } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import ProductoFormModal from '@/Components/ProductoFormModal.vue';
import { ElMessage } from 'element-plus';
import { DataAnalysis, Money, InfoFilled, Wallet, Warning, Plus } from '@element-plus/icons-vue';

const props = defineProps({
    apertura_activa: Boolean,
    datos_jornada: Object,
    movimientos_por_seccion: Object,
    vendedores_activos: Array,
    vendedores_activos_datos: Array,
    vendedor_seleccionado_id: [Number, String],
    cierre_aprobado: Boolean,
    productos_dashboard: {
        type: Array,
        default: () => []
    },
    saldos_servicios: {
        type: Object,
        default: () => ({})
    }
});

const page = usePage();
const esAdmin = computed(() => page.props.auth.user.roles.includes('administrador'));

// Cambiar de vendedor (solo admin)
const cambiarVendedor = (val) => {
    router.get(route('dashboard'), { vendedor_id: val }, { preserveState: true });
};

const formsMovimiento = reactive({});
const formsVenta = reactive({});

const seccionesInfo = [
    { key: 'tarjetas_unidad', titulo: 'Tarjetas por Unidad', permiteNegativo: false, usaProductos: true },
    { key: 'tarjetas_mayor', titulo: 'Tarjetas por Mayor', permiteNegativo: false, usaProductos: true },
    { key: 'recuperaciones', titulo: 'Recuperaciones', permiteNegativo: false, usaProductos: true },
    { key: 'chips', titulo: 'Chips', permiteNegativo: false, usaProductos: true },
    { key: 'recargas', titulo: 'Recargas al Paso', permiteNegativo: false, usaProductos: false, requiereOperador: true },
    { key: 'megas', titulo: 'Megas', permiteNegativo: false, usaProductos: false, requiereOperador: true },
    { key: 'servicios_digitales', titulo: 'Servicios Digitales', permiteNegativo: false, usaProductos: false },
    { key: 'banca_digital', titulo: 'Banca Digital', permiteNegativo: false, usaProductos: false },
    { key: 'servicio_tecnico', titulo: 'Servicio Técnico', permiteNegativo: false, usaProductos: false },
    { key: 'efectivo_monedas', titulo: 'Efectivo/Monedas', permiteNegativo: true, usaProductos: false, extra: 'Para salidas ingresar monto con signo negativo (-)' }
];

seccionesInfo.forEach(sec => {
    if (sec.usaProductos) {
        formsVenta[sec.key] = useForm({
            product_service_id: null,
            cantidad: null
        });
    } else {
        formsMovimiento[sec.key] = useForm({
            cash_opening_id: props.datos_jornada?.apertura_id || null,
            seccion: sec.key,
            operador: '',
            cantidad: null,
            monto: null,
            observacion: ''
        });
    }
});

// Modal de productos
const modalProductoVisible = ref(false);
const seccionParaModal = ref('');

const abrirModalProducto = (seccionKey) => {
    seccionParaModal.value = seccionKey;
    modalProductoVisible.value = true;
};

// Computed para filtrar productos por sección
const getProductosPorSeccion = (seccionKey) => {
    return props.productos_dashboard.filter(p => p.seccion_reporte === seccionKey);
};

// Obtener precio de un producto seleccionado
const getPrecioProducto = (seccionKey) => {
    const productId = formsVenta[seccionKey].product_service_id;
    if (!productId) return 0;
    const producto = props.productos_dashboard.find(p => p.id === productId);
    return producto ? parseFloat(producto.precio_venta) : 0;
};

// Obtener stock de un producto seleccionado
const getStockProducto = (seccionKey) => {
    const productId = formsVenta[seccionKey].product_service_id;
    if (!productId) return null;
    const producto = props.productos_dashboard.find(p => p.id === productId);
    return producto && producto.tipo === 'producto' ? producto.stock_actual : null;
};

const operadoresDisponibles = computed(() => {
    const ops = new Set();
    props.productos_dashboard.forEach(p => {
        if (p.operador) ops.add(p.operador);
    });
    ['Entel', 'Viva', 'Tigo'].forEach(o => ops.add(o));
    return Array.from(ops);
});

const getSaldoActivo = (seccionKey) => {
    if (!props.saldos_servicios) return null;
    
    const secInfo = seccionesInfo.find(s => s.key === seccionKey);
    let key = seccionKey;
    if (secInfo && secInfo.requiereOperador) {
        const form = formsMovimiento[seccionKey];
        if (form && form.operador) {
            key = `${seccionKey}_${form.operador}`;
        } else {
            return null;
        }
    }
    
    return props.saldos_servicios[key] || null;
};

// Manejo de flash messages nativo de Inertia
watch(() => page.props.flash, (flash) => {
    if (flash.ticket_url) {
        window.open(flash.ticket_url, '_blank');
    }
}, { deep: true });

const guardarSeccionManual = (seccionKey) => {
    const data = formsMovimiento[seccionKey];
    
    // Convertir comas a puntos y limpiar espacios si es un string
    let montoValor = data.monto;
    if (typeof montoValor === 'string') {
        montoValor = montoValor.replace(',', '.').trim();
    }
    
    if (montoValor === null || montoValor === '' || isNaN(montoValor)) {
        ElMessage.warning('El monto es obligatorio y debe ser un número válido.');
        return;
    }
    
    // Asignar el valor numérico procesado
    data.monto = parseFloat(montoValor);
    
    data.processing = true;
    data.post(route('dashboard.guardar_movimiento'), {
        preserveScroll: true,
        onSuccess: () => {
            data.cantidad = null;
            data.monto = null;
            data.observacion = '';
            data.processing = false;
        },
        onError: (err) => {
            ElMessage.error(err.monto || err.operador || 'Error al guardar el movimiento.');
            data.processing = false;
        },
        onFinish: () => {
            data.processing = false;
        }
    });
};

const guardarVentaRapida = (seccionKey) => {
    const datos = formsVenta[seccionKey];
    if (!datos.product_service_id) {
        ElMessage.warning('Debe seleccionar un producto.');
        return;
    }
    if (!datos.cantidad || datos.cantidad < 1) {
        ElMessage.warning('La cantidad debe ser al menos 1.');
        return;
    }

    const stock = getStockProducto(seccionKey);
    if (stock !== null && datos.cantidad > stock) {
        ElMessage.warning(`Stock insuficiente. Disponible: ${stock}`);
        return;
    }

    datos.processing = true;
    datos.transform((d) => ({
        cash_opening_id: props.datos_jornada?.apertura_id,
        product_service_id: d.product_service_id,
        cantidad: d.cantidad
    })).post(route('dashboard.guardar_venta_rapida'), {
        preserveScroll: true,
        onSuccess: () => {
            datos.processing = false;
            datos.product_service_id = null;
            datos.cantidad = null;
        },
        onError: (err) => {
            datos.processing = false;
            ElMessage.error(err.error || 'Error al registrar la venta rápida.');
        }
    });
};

// Cálculo de la diferencia entre saldo actual y esperado si quisiéramos mostrarlo,

// Cálculo de la diferencia entre saldo actual y esperado si quisiéramos mostrarlo,
// por ahora el ERS pide "Efectivo que debe tener en mano ahora".
</script>

<template>
    <Head title="Dashboard de Movimientos" />

    <PanelLayout>
        <template #titulo-pagina>
            <div class="flex items-center justify-between w-full">
                <h2 class="text-xl font-semibold text-gray-800">Dashboard de Movimientos y Caja</h2>
            </div>
        </template>

        <!-- Tarjetas de Resumen Financiero por Vendedor (Admin) -->
        <div v-if="esAdmin && vendedores_activos_datos && vendedores_activos_datos.length > 0" class="mt-6 mb-6">
            <h3 class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-3">Vendedores Activos en Caja</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <el-card 
                    v-for="vendedor in vendedores_activos_datos" 
                    :key="vendedor.id"
                    shadow="hover"
                    class="cursor-pointer transition-all duration-300 border-2"
                    :class="vendedor.id == vendedor_seleccionado_id ? 'border-blue-500 bg-blue-50/10 shadow-md ring-2 ring-blue-100' : 'border-gray-200'"
                    @click="cambiarVendedor(vendedor.id)"
                >
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <div class="font-bold text-gray-800 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></span>
                                {{ vendedor.nombre_completo }}
                            </div>
                            <div class="text-xs text-gray-500">Cod: {{ vendedor.codigo }}</div>
                        </div>
                        <el-tag :type="vendedor.id == vendedor_seleccionado_id ? 'primary' : 'info'" size="small" effect="dark">
                            {{ vendedor.id == vendedor_seleccionado_id ? 'ACTIVO' : 'SELECCIONAR' }}
                        </el-tag>
                    </div>
                    
                    <!-- Desglose rápido -->
                    <div class="text-xs text-gray-600 mt-2 space-y-1">
                        <div class="flex justify-between">
                            <span>Inicial:</span>
                            <span class="font-medium text-gray-800">Bs {{ Number(vendedor.saldo_inicial).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Ventas:</span>
                            <span class="font-medium text-green-600">+ Bs {{ Number(vendedor.total_ventas).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Movimientos:</span>
                            <span class="font-medium" :class="vendedor.total_movimientos < 0 ? 'text-red-600' : 'text-blue-600'">
                                {{ vendedor.total_movimientos > 0 ? '+' : '' }} Bs {{ Number(vendedor.total_movimientos).toFixed(2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Saldo Esperado en Mano -->
                    <div class="mt-3 pt-2 border-t border-gray-100 flex justify-between items-center">
                        <span class="text-xs text-gray-500 font-bold uppercase">En Mano:</span>
                        <span class="font-black text-blue-700 text-base">Bs {{ Number(vendedor.saldo_esperado).toFixed(2) }}</span>
                    </div>
                    
                    <!-- Última venta -->
                    <div class="mt-2 pt-1.5 border-t border-dashed border-gray-200 text-xs text-gray-500 truncate" :title="vendedor.ultima_venta">
                        <span class="font-semibold text-gray-600">Última venta:</span> {{ vendedor.ultima_venta }}
                    </div>
                </el-card>
            </div>
        </div>

        <!-- Bloqueo si no hay apertura -->
        <div v-if="!apertura_activa" class="mt-6">
            <el-alert
                title="Apertura de Caja Requerida"
                type="warning"
                :closable="false"
                show-icon
                class="mb-6 shadow-sm border border-yellow-200"
            >
                <template #default>
                    <p class="text-sm mt-1">
                        {{ esAdmin && vendedor_seleccionado_id 
                            ? 'El vendedor seleccionado no tiene una caja abierta actualmente.' 
                            : 'No tienes una apertura de caja activa. Contacta al administrador para iniciar tu jornada.' 
                        }}
                    </p>
                </template>
            </el-alert>
        </div>

        <!-- Dashboard Activo -->
        <div v-else class="flex flex-col gap-6 mt-4">
            
            <!-- Badge Cierre Aprobado -->
            <div v-if="cierre_aprobado" class="bg-red-50 border-l-4 border-red-500 p-4 rounded shadow-sm">
                <div class="flex items-center">
                    <el-icon class="text-red-500 text-xl mr-2"><Warning /></el-icon>
                    <h3 class="text-red-800 font-bold text-lg">Jornada Cerrada y Aprobada</h3>
                </div>
                <p class="text-red-700 text-sm mt-1">No se permiten más movimientos ni alteraciones en los saldos.</p>
            </div>

            <!-- Datos de Jornada (Resumen Financiero) -->
            <el-card class="shadow-sm border-t-4 border-t-blue-500">
                <template #header>
                    <div class="flex items-center gap-2">
                        <el-icon class="text-blue-500 text-lg"><DataAnalysis /></el-icon>
                        <span class="font-bold text-gray-700">Resumen de la Jornada - {{ datos_jornada.vendedor }}</span>
                    </div>
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Detalle Administrativo -->
                    <div class="flex flex-col gap-1 col-span-1 md:col-span-2 lg:col-span-1">
                        <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider">Fecha Apertura</span>
                        <span class="text-sm font-medium text-gray-800">{{ datos_jornada.fecha }}</span>
                        
                        <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider mt-3">Estado Caja</span>
                        <el-tag :type="cierre_aprobado ? 'danger' : (datos_jornada.estado_caja === 'Abierta' ? 'success' : 'warning')" size="small">
                            {{ datos_jornada.estado_caja }}
                        </el-tag>
                    </div>

                    <!-- Saldos -->
                    <div class="bg-gray-50 p-4 rounded-lg border flex flex-col justify-center">
                        <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider flex items-center gap-1">
                            <el-icon><Money /></el-icon> Saldo Inicial (Entregado al inicio)
                        </span>
                        <span class="text-2xl font-bold text-gray-700 mt-1">Bs {{ Number(datos_jornada.saldo_inicial).toFixed(2) }}</span>
                        
                        <div class="mt-4 flex justify-between text-sm">
                            <span class="text-gray-600">Total Ventas:</span>
                            <span class="font-medium text-green-600">+ Bs {{ Number(datos_jornada.total_ventas).toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between text-sm mt-1">
                            <span class="text-gray-600">Movs. Manuales:</span>
                            <span class="font-medium" :class="datos_jornada.total_movimientos < 0 ? 'text-red-600' : 'text-blue-600'">
                                {{ datos_jornada.total_movimientos > 0 ? '+' : '' }} Bs {{ Number(datos_jornada.total_movimientos).toFixed(2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Saldo Esperado -->
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 flex flex-col justify-center lg:col-span-2">
                        <span class="text-sm text-blue-700 font-bold uppercase tracking-wider flex items-center gap-1">
                            <el-icon><Wallet /></el-icon> Efectivo que debe tener en mano ahora
                        </span>
                        <span class="text-4xl font-extrabold text-blue-900 mt-2">Bs {{ Number(datos_jornada.saldo_actual_esperado).toFixed(2) }}</span>
                        <p class="text-xs text-blue-600 mt-2 flex items-center gap-1">
                            <el-icon><InfoFilled /></el-icon> 
                            Suma del inicial, más ventas completadas, más/menos movimientos manuales.
                        </p>
                    </div>
                </div>
                
                <div class="mt-4 border-t pt-4" v-if="datos_jornada.servicios_asignados && datos_jornada.servicios_asignados.length > 0">
                    <span class="text-xs text-gray-500 font-semibold uppercase tracking-wider mr-2">Servicios Autorizados:</span>
                    <el-tag v-for="srv in datos_jornada.servicios_asignados" :key="srv" size="small" class="mr-1 mb-1 bg-gray-100 text-gray-700 border-gray-300">
                        {{ srv }}
                    </el-tag>
                </div>
            </el-card>

            <!-- 10 Secciones de Movimientos -->
            <div class="mt-4">
                <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Registro de Movimientos (ERS)</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                    <el-card 
                        v-for="seccion in seccionesInfo" 
                        :key="seccion.key"
                        class="shadow-sm hover:shadow-md transition-shadow"
                        :body-style="{ padding: '15px' }"
                    >
                        <div class="font-bold text-gray-700 text-sm mb-3 border-b pb-2 text-center h-10 flex items-center justify-center relative">
                            {{ seccion.titulo }}
                            <el-tooltip v-if="!seccion.usaProductos && !seccion.permiteNegativo" content="Los servicios no generan ticket. El registro queda auditado en movimientos y cierre." placement="top" :hide-after="0">
                                <el-icon class="ml-1 text-blue-400 cursor-pointer text-xs"><InfoFilled /></el-icon>
                            </el-tooltip>
                        </div>
                        
                        <div class="mb-3 text-center">
                            <span class="text-xs text-gray-500">Subtotal Acumulado:</span><br>
                            <span class="font-bold text-lg" :class="movimientos_por_seccion[seccion.key] < 0 ? 'text-red-600' : 'text-blue-600'">
                                Bs {{ Number(movimientos_por_seccion[seccion.key] || 0).toFixed(2) }}
                            </span>
                        </div>

                        <div class="flex flex-col gap-2 relative">
                            <!-- Overlay de carga -->
                            <div v-if="(seccion.usaProductos ? formsVenta[seccion.key].processing : formsMovimiento[seccion.key].processing)" class="absolute inset-0 bg-white bg-opacity-70 z-10 flex items-center justify-center">
                                <el-icon class="is-loading text-blue-500 text-2xl"><Loading /></el-icon>
                            </div>

                            <!-- MODO VENTA RÁPIDA (Productos) -->
                            <template v-if="seccion.usaProductos">
                                <div class="flex gap-2">
                                    <el-select 
                                        v-model="formsVenta[seccion.key].product_service_id" 
                                        placeholder="Seleccione..." 
                                        class="flex-1"
                                        size="small"
                                        filterable
                                        :disabled="cierre_aprobado"
                                    >
                                        <el-option 
                                            v-for="prod in getProductosPorSeccion(seccion.key)" 
                                            :key="prod.id" 
                                            :label="prod.nombre" 
                                            :value="prod.id" 
                                        />
                                    </el-select>
                                    <el-button 
                                        type="success" 
                                        plain 
                                        size="small" 
                                        :icon="Plus" 
                                        @click="abrirModalProducto(seccion.key)"
                                        :disabled="cierre_aprobado"
                                    />
                                </div>
                                
                                <div class="flex gap-2">
                                    <el-input 
                                        v-model="formsVenta[seccion.key].cantidad" 
                                        type="number" 
                                        :min="1"
                                        placeholder="Cant." 
                                        size="small" 
                                        class="w-1/3"
                                        :disabled="cierre_aprobado"
                                    >
                                        <template #prefix>#</template>
                                    </el-input>

                                    <!-- Total calculado -->
                                    <el-input 
                                        :model-value="formsVenta[seccion.key].cantidad ? getPrecioProducto(seccion.key) * formsVenta[seccion.key].cantidad : ''" 
                                        type="number" 
                                        readonly
                                        placeholder="Total" 
                                        size="small" 
                                        class="flex-1"
                                    >
                                        <template #prefix>Bs</template>
                                    </el-input>
                                </div>
                                <div v-if="getStockProducto(seccion.key) !== null" class="text-right text-[10px] text-gray-500 -mt-1">
                                    Stock: {{ getStockProducto(seccion.key) }}
                                </div>

                                <el-button 
                                    type="primary" 
                                    size="small" 
                                    class="w-full mt-1" 
                                    :disabled="cierre_aprobado || formsVenta[seccion.key].processing"
                                    @click="guardarVentaRapida(seccion.key)"
                                >
                                    Vender
                                </el-button>
                            </template>

                            <!-- MODO MOVIMIENTO MANUAL (Dinero puro) -->
                            <template v-else>
                                <el-select v-if="seccion.requiereOperador"
                                    v-model="formsMovimiento[seccion.key].operador"
                                    placeholder="Operador"
                                    size="small"
                                    class="w-full mb-2"
                                    :disabled="cierre_aprobado"
                                >
                                    <el-option v-for="op in operadoresDisponibles" :key="op" :label="op" :value="op" />
                                </el-select>

                                <!-- Badges de Límite -->
                                <div v-if="getSaldoActivo(seccion.key)" class="text-[10px] bg-gray-100 p-1.5 rounded mb-2 border border-gray-200">
                                    <div class="flex justify-between text-gray-500 mb-0.5">
                                        <span>Límite:</span> <span class="font-bold">{{ getSaldoActivo(seccion.key).limite.toFixed(2) }} Bs</span>
                                    </div>
                                    <div class="flex justify-between text-gray-500 mb-0.5">
                                        <span>Usado:</span> <span class="font-bold">{{ getSaldoActivo(seccion.key).usado.toFixed(2) }} Bs</span>
                                    </div>
                                    <div class="flex justify-between mt-1 border-t border-gray-200 pt-1" :class="getSaldoActivo(seccion.key).disponible <= 0 ? 'text-red-600 font-bold' : 'text-green-600 font-bold'">
                                        <span>Disponible:</span> <span>{{ getSaldoActivo(seccion.key).disponible.toFixed(2) }} Bs</span>
                                    </div>
                                </div>
                                
                                <el-alert v-if="getSaldoActivo(seccion.key) && getSaldoActivo(seccion.key).disponible <= 0"
                                    title="Límite Agotado" type="error" :closable="false" show-icon class="mb-2 py-1 px-2 text-xs" />

                                <div class="flex gap-2">
                                    <el-input 
                                        v-model="formsMovimiento[seccion.key].cantidad" 
                                        type="number" 
                                        placeholder="Cant." 
                                        size="small" 
                                        class="w-1/3"
                                        :disabled="cierre_aprobado || (getSaldoActivo(seccion.key) && getSaldoActivo(seccion.key).disponible <= 0)"
                                    >
                                        <template #prefix>#</template>
                                    </el-input>
                                    
                                    <el-input 
                                        v-model="formsMovimiento[seccion.key].monto" 
                                        placeholder="Monto" 
                                        size="small"
                                        class="flex-1"
                                        :disabled="cierre_aprobado || (getSaldoActivo(seccion.key) && getSaldoActivo(seccion.key).disponible <= 0)"
                                    >
                                        <template #prefix>Bs</template>
                                    </el-input>
                                </div>

                                <el-button 
                                    type="primary" 
                                    size="small" 
                                    class="w-full mt-2" 
                                    :disabled="cierre_aprobado || formsMovimiento[seccion.key].processing || (getSaldoActivo(seccion.key) && getSaldoActivo(seccion.key).disponible <= 0) || (seccion.requiereOperador && !formsMovimiento[seccion.key].operador)"
                                    @click="guardarSeccionManual(seccion.key)"
                                >
                                    Registrar
                                </el-button>
                            </template>
                            
                            <p v-if="seccion.extra" class="text-[10px] text-gray-400 text-center leading-tight mt-1">
                                {{ seccion.extra }}
                            </p>
                        </div>
                    </el-card>
                </div>
            </div>

        </div>
        
        <ProductoFormModal 
            v-model="modalProductoVisible" 
            :seccion-predefinida="seccionParaModal"
            @saved="() => router.reload({ only: ['productos_dashboard'] })"
        />
    </PanelLayout>
</template>

<script>
import { Loading } from '@element-plus/icons-vue';
export default {
    components: { Loading }
}
</script>
