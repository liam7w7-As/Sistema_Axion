<script setup>
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-lg font-semibold text-gray-800">
                Panel Principal
            </h2>
        </template>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <!-- Tarjeta de ejemplo: Ventas del día -->
            <el-card shadow="hover" class="app-tarjeta-resumen">
                <div class="flex items-center gap-4">
                    <div class="app-tarjeta-resumen__icono app-tarjeta-resumen__icono--azul">
                        <el-icon :size="24"><ShoppingCart /></el-icon>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Ventas del día</p>
                        <p class="text-2xl font-bold text-gray-800">0</p>
                    </div>
                </div>
            </el-card>

            <!-- Tarjeta de ejemplo: Productos -->
            <el-card shadow="hover" class="app-tarjeta-resumen">
                <div class="flex items-center gap-4">
                    <div class="app-tarjeta-resumen__icono app-tarjeta-resumen__icono--verde">
                        <el-icon :size="24"><ShoppingBag /></el-icon>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Productos</p>
                        <p class="text-2xl font-bold text-gray-800">0</p>
                    </div>
                </div>
            </el-card>

            <!-- Tarjeta de ejemplo: Caja -->
            <el-card shadow="hover" class="app-tarjeta-resumen">
                <div class="flex items-center gap-4">
                    <div class="app-tarjeta-resumen__icono app-tarjeta-resumen__icono--naranja">
                        <el-icon :size="24"><Wallet /></el-icon>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Caja</p>
                        <p class="text-2xl font-bold text-gray-800">Bs. 0.00</p>
                    </div>
                </div>
            </el-card>

            <!-- Tarjeta de ejemplo: Usuarios -->
            <el-card shadow="hover" class="app-tarjeta-resumen">
                <div class="flex items-center gap-4">
                    <div class="app-tarjeta-resumen__icono app-tarjeta-resumen__icono--morado">
                        <el-icon :size="24"><User /></el-icon>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Usuarios</p>
                        <p class="text-2xl font-bold text-gray-800">0</p>
                    </div>
                </div>
            </el-card>
        </div>

        <!-- Sección de bienvenida -->
        <el-card shadow="never" class="mt-6">
            <div class="p-4 text-center">
                <el-icon :size="48" color="#3b82f6" class="mb-4">
                    <Headset />
                </el-icon>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">
                    ¡Bienvenido al Sistema de Telefonía!
                </h3>
                <p class="text-gray-500">
                    Gestione ventas, inventario, caja y servicios de telefonía desde un solo lugar.
                </p>
            </div>
        </el-card>
    </PanelLayout>
</template>

<style scoped>
.app-tarjeta-resumen__icono {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    flex-shrink: 0;
}

.app-tarjeta-resumen__icono--azul {
    background-color: #eff6ff;
    color: #3b82f6;
}

.app-tarjeta-resumen__icono--verde {
    background-color: #f0fdf4;
    color: #22c55e;
}

.app-tarjeta-resumen__icono--naranja {
    background-color: #fff7ed;
    color: #f97316;
}

.app-tarjeta-resumen__icono--morado {
    background-color: #faf5ff;
    color: #a855f7;
}
</style>
