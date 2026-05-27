<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import CalculadoraAuxiliar from '@/Components/CalculadoraAuxiliar.vue';
import { usePermisos } from '@/Composables/usePermisos';
import { ElMessage } from 'element-plus';
import { Plus, Delete, ShoppingCart, CircleCloseFilled, ArrowLeft } from '@element-plus/icons-vue';

const props = defineProps({
    apertura_activa: Object,
    cierre_aprobado: Boolean,
    productos: Array,
});

const { puedeVerGanancias } = usePermisos();

// Estado del carrito (reactivo, local)
const carrito = ref([]);
const buscadorProducto = ref('');

// Productos filtrados por búsqueda
const productosFiltrados = computed(() => {
    if (!buscadorProducto.value) return props.productos;
    const termino = buscadorProducto.value.toLowerCase();
    return props.productos.filter(p =>
        p.nombre.toLowerCase().includes(termino) ||
        (p.operador && p.operador.toLowerCase().includes(termino)) ||
        (p.categoria && p.categoria.toLowerCase().includes(termino))
    );
});

// Agregar producto al carrito
const agregarAlCarrito = (producto) => {
    // Verificar si ya está en el carrito
    const existente = carrito.value.find(item => item.product_service_id === producto.id);

    if (existente) {
        // Validar stock si es producto físico
        if (producto.tipo === 'producto' && existente.cantidad >= producto.stock_actual) {
            ElMessage.warning(`Stock insuficiente para "${producto.nombre}". Disponible: ${producto.stock_actual}`);
            return;
        }
        existente.cantidad++;
        existente.subtotal = existente.cantidad * existente.precio_venta;
    } else {
        // Verificar stock para primer item
        if (producto.tipo === 'producto' && producto.stock_actual < 1) {
            ElMessage.warning(`"${producto.nombre}" no tiene stock disponible.`);
            return;
        }
        carrito.value.push({
            product_service_id: producto.id,
            nombre: producto.nombre,
            tipo: producto.tipo,
            operador: producto.operador,
            stock_actual: producto.stock_actual,
            cantidad: 1,
            precio_venta: parseFloat(producto.precio_venta),
            subtotal: parseFloat(producto.precio_venta),
        });
    }
    buscadorProducto.value = '';
};

// Actualizar subtotal al cambiar cantidad
const actualizarSubtotal = (item) => {
    // Validar stock
    if (item.tipo === 'producto' && item.cantidad > item.stock_actual) {
        ElMessage.warning(`Stock máximo para "${item.nombre}": ${item.stock_actual}`);
        item.cantidad = item.stock_actual;
    }
    item.subtotal = item.cantidad * item.precio_venta;
};

// Eliminar del carrito
const eliminarDelCarrito = (index) => {
    carrito.value.splice(index, 1);
};

// Total general del carrito (computed)
const totalGeneral = computed(() => {
    return carrito.value.reduce((sum, item) => sum + item.subtotal, 0);
});

// Formulario para enviar la venta
const form = useForm({
    cash_opening_id: props.apertura_activa?.id || null,
    tipo_pago: 'efectivo',
    cliente_nombre: '',
    observacion: '',
    items: [],
});

const registrarVenta = () => {
    if (carrito.value.length === 0) {
        ElMessage.warning('Debe agregar al menos un producto al carrito.');
        return;
    }

    // Transformar carrito al formato del request
    form.items = carrito.value.map(item => ({
        product_service_id: item.product_service_id,
        cantidad: item.cantidad,
        precio_venta: item.precio_venta,
    }));

    form.post(route('ventas.store'), {
        onSuccess: () => {
            carrito.value = [];
            ElMessage.success('¡Venta registrada exitosamente!');
        },
        onError: (errors) => {
            // Mostrar el primer error disponible
            const primerError = Object.values(errors)[0];
            if (primerError) ElMessage.error(primerError);
        }
    });
};

const formatearMoneda = (valor) => {
    return Number(valor).toFixed(2) + ' Bs';
};
</script>

<template>
    <Head title="Nueva Venta" />

    <PanelLayout>
        <template #titulo-pagina>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-gray-800">Nueva Venta</h2>
                <el-tag v-if="apertura_activa" type="success" effect="plain" size="small">Caja Abierta</el-tag>
            </div>
        </template>

        <div class="mb-6">
            <el-button :icon="ArrowLeft" @click="router.visit(route('ventas.index'))" plain>Volver</el-button>
        </div>

        <!-- Bloqueo si no hay apertura -->
        <template v-if="!apertura_activa">
            <el-alert
                title="No tienes una apertura de caja activa"
                type="error"
                description="No puedes registrar ventas sin una caja abierta. Contacta al administrador."
                show-icon
                :closable="false"
            />
        </template>

        <!-- Bloqueo si cierre aprobado -->
        <template v-else-if="cierre_aprobado">
            <el-alert
                title="Caja Cerrada"
                type="warning"
                description="El cierre de caja fue aprobado. No puedes registrar más ventas en esta sesión."
                show-icon
                :closable="false"
            />
        </template>

        <!-- Formulario de venta activo -->
        <template v-else>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- ===== COLUMNA IZQUIERDA: Buscador + Carrito ===== -->
                <div class="lg:col-span-2 space-y-4">

                    <!-- Buscador de productos -->
                    <el-card class="shadow-sm">
                        <template #header>
                            <span class="font-semibold">Buscar Producto o Servicio</span>
                        </template>
                        <el-input
                            v-model="buscadorProducto"
                            placeholder="Escriba nombre, operador o categoría..."
                            clearable
                            size="large"
                        />
                        
                        <!-- Resultados de búsqueda -->
                        <div v-if="buscadorProducto" class="mt-3 max-h-48 overflow-y-auto border rounded-md">
                            <div
                                v-for="prod in productosFiltrados"
                                :key="prod.id"
                                class="flex items-center justify-between px-3 py-2 hover:bg-blue-50 cursor-pointer border-b last:border-b-0 transition-colors"
                                @click="agregarAlCarrito(prod)"
                            >
                                <div>
                                    <span class="font-medium text-gray-800">{{ prod.nombre }}</span>
                                    <span v-if="prod.operador" class="ml-2 text-xs text-gray-500">({{ prod.operador }})</span>
                                    <span v-if="prod.tipo === 'producto'" class="ml-2 text-xs text-blue-500">Stock: {{ prod.stock_actual }}</span>
                                    <el-tag v-else size="small" type="warning" class="ml-2">Servicio</el-tag>
                                </div>
                                <div class="text-right">
                                    <span class="font-bold text-green-600">{{ formatearMoneda(prod.precio_venta) }}</span>
                                    <el-icon class="ml-2 text-blue-500"><Plus /></el-icon>
                                </div>
                            </div>
                            <div v-if="productosFiltrados.length === 0" class="p-4 text-center text-gray-400">
                                No se encontraron productos.
                            </div>
                        </div>
                    </el-card>

                    <!-- Carrito -->
                    <el-card class="shadow-sm">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <el-icon><ShoppingCart /></el-icon>
                                <span class="font-semibold">Carrito de Venta</span>
                                <el-tag size="small" type="info">{{ carrito.length }} ítem(s)</el-tag>
                            </div>
                        </template>
                        <el-table :data="carrito" border style="width: 100%" empty-text="El carrito está vacío. Busque y agregue productos.">
                            <el-table-column label="Producto" min-width="180">
                                <template #default="scope">
                                    <span class="font-medium">{{ scope.row.nombre }}</span>
                                    <span v-if="scope.row.operador" class="text-xs text-gray-500 ml-1">({{ scope.row.operador }})</span>
                                </template>
                            </el-table-column>

                            <el-table-column label="Cant." width="120" align="center">
                                <template #default="scope">
                                    <el-input-number 
                                        v-model="scope.row.cantidad" 
                                        :min="1" 
                                        :max="scope.row.tipo === 'producto' ? scope.row.stock_actual : 9999"
                                        size="small"
                                        controls-position="right"
                                        @change="actualizarSubtotal(scope.row)"
                                    />
                                </template>
                            </el-table-column>

                            <el-table-column label="P. Unit." width="110" align="right">
                                <template #default="scope">{{ formatearMoneda(scope.row.precio_venta) }}</template>
                            </el-table-column>

                            <el-table-column label="Subtotal" width="120" align="right">
                                <template #default="scope">
                                    <span class="font-bold text-green-600">{{ formatearMoneda(scope.row.subtotal) }}</span>
                                </template>
                            </el-table-column>

                            <el-table-column label="" width="60" align="center">
                                <template #default="scope">
                                    <el-button type="danger" :icon="Delete" circle size="small" @click="eliminarDelCarrito(scope.$index)" />
                                </template>
                            </el-table-column>
                        </el-table>
                    </el-card>
                </div>

                <!-- ===== COLUMNA DERECHA: Resumen + Pago + Calculadora ===== -->
                <div class="space-y-4">
                    
                    <!-- Resumen de la venta -->
                    <el-card class="shadow-sm">
                        <template #header>
                            <span class="font-semibold">Resumen de Venta</span>
                        </template>

                        <div class="space-y-3">
                            <div class="flex justify-between text-gray-600">
                                <span>Productos:</span>
                                <span>{{ carrito.length }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Ítems totales:</span>
                                <span>{{ carrito.reduce((s, i) => s + i.cantidad, 0) }}</span>
                            </div>
                            <el-divider />
                            <div class="flex justify-between text-xl font-bold">
                                <span>TOTAL:</span>
                                <span class="text-green-600">{{ formatearMoneda(totalGeneral) }}</span>
                            </div>
                        </div>

                        <el-divider />

                        <el-form label-position="top">
                            <el-form-item label="Tipo de Pago" :error="form.errors.tipo_pago">
                                <el-select v-model="form.tipo_pago" class="w-full">
                                    <el-option label="Efectivo" value="efectivo" />
                                    <el-option label="Transferencia" value="transferencia" />
                                    <el-option label="QR" value="qr" />
                                    <el-option label="Mixto" value="mixto" />
                                </el-select>
                            </el-form-item>

                            <el-form-item label="Cliente (Opcional)" :error="form.errors.cliente_nombre">
                                <el-input v-model="form.cliente_nombre" placeholder="Nombre del cliente..." />
                            </el-form-item>

                            <el-form-item label="Observación (Opcional)" :error="form.errors.observacion">
                                <el-input v-model="form.observacion" type="textarea" rows="2" placeholder="Nota sobre la venta..." />
                            </el-form-item>
                        </el-form>

                        <el-button
                            type="success"
                            size="large"
                            class="w-full !font-bold"
                            :loading="form.processing"
                            :disabled="carrito.length === 0"
                            @click="registrarVenta"
                        >
                            🛒 Registrar Venta
                        </el-button>
                    </el-card>

                    <!-- Calculadora Auxiliar (NOTA 13 ERS) -->
                    <CalculadoraAuxiliar />

                </div>
            </div>
        </template>
    </PanelLayout>
</template>
