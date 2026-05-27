<script setup>
import { useForm, usePage } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const usuario = usePage().props.auth.user;

const form = useForm({
    nombre_completo: usuario.nombre_completo,
});

const actualizarPerfil = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <section>
        <header>
            <h3 class="text-lg font-medium text-gray-900">
                Información del Perfil
            </h3>
            <p class="mt-1 text-sm text-gray-600">
                Actualice su nombre completo. El código de usuario no puede ser modificado.
            </p>
        </header>

        <el-form
            @submit.prevent="actualizarPerfil"
            label-position="top"
            class="mt-6 max-w-xl"
        >
            <!-- Código de usuario (solo lectura) -->
            <el-form-item label="Código de Usuario">
                <el-input
                    :model-value="usuario.codigo"
                    disabled
                    prefix-icon="Key"
                />
            </el-form-item>

            <!-- Rol (solo lectura) -->
            <el-form-item label="Rol">
                <el-tag :type="usuario.role === 'admin' ? 'primary' : 'success'" size="large">
                    {{ usuario.role === 'admin' ? 'Administrador' : 'Vendedor' }}
                </el-tag>
            </el-form-item>

            <!-- Nombre completo -->
            <el-form-item
                label="Nombre Completo"
                :error="form.errors.nombre_completo"
            >
                <el-input
                    v-model="form.nombre_completo"
                    prefix-icon="User"
                    placeholder="Ingrese su nombre completo"
                />
            </el-form-item>

            <div class="flex items-center gap-4">
                <el-button
                    type="primary"
                    :loading="form.processing"
                    @click="actualizarPerfil"
                    native-type="submit"
                >
                    Guardar Cambios
                </el-button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-green-600"
                    >
                        Guardado correctamente.
                    </p>
                </Transition>
            </div>
        </el-form>
    </section>
</template>
