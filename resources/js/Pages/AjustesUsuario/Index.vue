<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import PanelLayout from '@/Layouts/PanelLayout.vue';
import { Lock, User as UserIcon } from '@element-plus/icons-vue';
import { ElMessage } from 'element-plus';

const page = usePage();
const usuario = computed(() => page.props.auth.user);

const form = useForm({
    contrasena_actual: '',
    nueva_contrasena: '',
    nueva_contrasena_confirmation: '',
    foto: null
});

const uploadRef = ref(null);
const fotoPreview = ref(usuario.value.foto ? `/storage/${usuario.value.foto}` : null);

const handleFotoChange = (file) => {
    form.foto = file.raw;
    fotoPreview.value = URL.createObjectURL(file.raw);
};

const submit = () => {
    // Validación manual rápida frontend antes de enviar
    if (form.nueva_contrasena && form.nueva_contrasena !== form.nueva_contrasena_confirmation) {
        ElMessage.warning('La nueva contraseña y la confirmación no coinciden.');
        return;
    }

    form.post(route('ajustes-usuario.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('contrasena_actual', 'nueva_contrasena', 'nueva_contrasena_confirmation');
            form.foto = null;
        }
    });
};
</script>

<template>
    <Head title="Mi Perfil" />

    <PanelLayout>
        <template #titulo-pagina>
            <h2 class="text-xl font-semibold text-gray-800">Ajustes de Perfil</h2>
        </template>

        <div class="max-w-2xl mx-auto mt-6">
            <el-card class="shadow-sm">
                <template #header>
                    <div class="flex items-center gap-2 text-gray-700">
                        <el-icon><UserIcon /></el-icon>
                        <span class="font-bold">Información Personal</span>
                    </div>
                </template>

                <div class="text-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-800">{{ usuario.nombre_completo }}</h3>
                    <p class="text-sm text-gray-500">Rol: <span class="capitalize">{{ usuario.roles[0] || 'N/A' }}</span></p>
                    <p class="text-sm text-gray-500 font-mono mt-1">Código: {{ usuario.codigo }}</p>
                </div>

                <el-form :model="form" label-position="top" @submit.prevent="submit" v-loading="form.processing">
                    
                    <div class="border-b pb-6 mb-6">
                        <h4 class="font-medium text-gray-600 mb-4">Foto de Perfil</h4>
                        <div class="flex flex-col sm:flex-row items-center gap-6">
                            <el-avatar :size="100" :src="fotoPreview" class="bg-gray-200 text-3xl">
                                <span v-if="!fotoPreview">{{ usuario.nombre_completo.charAt(0).toUpperCase() }}</span>
                            </el-avatar>
                            
                            <div class="flex flex-col items-center sm:items-start gap-2">
                                <el-upload
                                    ref="uploadRef"
                                    action=""
                                    :auto-upload="false"
                                    :show-file-list="false"
                                    accept="image/jpeg,image/png,image/jpg"
                                    @change="handleFotoChange"
                                >
                                    <el-button type="primary" plain>Cambiar Foto</el-button>
                                </el-upload>
                                <span class="text-xs text-gray-400">JPG o PNG. Tamaño máximo 2MB.</span>
                                <div v-if="form.errors.foto" class="text-red-500 text-xs mt-1">{{ form.errors.foto }}</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-600 mb-4 flex items-center gap-2">
                            <el-icon><Lock /></el-icon> Cambiar Contraseña
                        </h4>
                        
                        <el-form-item label="Contraseña Actual" :error="form.errors.contrasena_actual">
                            <el-input v-model="form.contrasena_actual" type="password" show-password placeholder="Requerida para autorizar el cambio" />
                        </el-form-item>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <el-form-item label="Nueva Contraseña" :error="form.errors.nueva_contrasena">
                                <el-input v-model="form.nueva_contrasena" type="password" show-password placeholder="Mínimo 8 caracteres" />
                            </el-form-item>

                            <el-form-item label="Confirmar Nueva Contraseña">
                                <el-input v-model="form.nueva_contrasena_confirmation" type="password" show-password placeholder="Debe coincidir con la nueva" />
                            </el-form-item>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <el-button type="primary" size="large" @click="submit" :loading="form.processing" :disabled="!form.isDirty">
                            Guardar Cambios
                        </el-button>
                    </div>

                </el-form>
            </el-card>
        </div>
    </PanelLayout>
</template>
