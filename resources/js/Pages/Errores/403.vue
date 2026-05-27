<script setup>
import { Head, router } from '@inertiajs/vue3';

defineProps({
    codigo: Number,
    mensaje: String
});

const volver = () => {
    router.visit(route('dashboard'));
};
</script>

<template>
    <Head title="Acceso Denegado" />
    
    <div class="flex items-center justify-center min-h-screen bg-gray-50 p-4">
        <el-result icon="warning" :title="`${codigo} - Acceso Denegado`" :sub-title="mensaje">
            <template #extra>
                <el-button type="primary" @click="volver">Volver al Dashboard</el-button>
            </template>
        </el-result>
    </div>
</template>
