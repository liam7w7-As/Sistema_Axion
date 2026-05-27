<script setup>
import PanelLayout from '@/Layouts/PanelLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Mi Perfil" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-lg font-semibold text-gray-800">
                Mi Perfil
            </h2>
        </template>

        <div class="max-w-3xl space-y-6">
            <!-- Información del perfil -->
            <el-card shadow="never">
                <UpdateProfileInformationForm
                    :status="status"
                />
            </el-card>

            <!-- Cambiar contraseña -->
            <el-card shadow="never">
                <UpdatePasswordForm />
            </el-card>
        </div>
    </PanelLayout>
</template>
