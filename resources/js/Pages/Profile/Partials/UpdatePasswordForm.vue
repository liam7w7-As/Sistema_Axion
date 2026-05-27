<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const contrasenaActualRef = ref(null);
const nuevaContrasenaRef = ref(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const actualizarContrasena = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
            }
            if (form.errors.current_password) {
                form.reset('current_password');
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h3 class="text-lg font-medium text-gray-900">
                Cambiar Contraseña
            </h3>
            <p class="mt-1 text-sm text-gray-600">
                Asegúrese de usar una contraseña segura y única para proteger su cuenta.
            </p>
        </header>

        <el-form
            @submit.prevent="actualizarContrasena"
            label-position="top"
            class="mt-6 max-w-xl"
        >
            <!-- Contraseña actual -->
            <el-form-item
                label="Contraseña Actual"
                :error="form.errors.current_password"
            >
                <el-input
                    ref="contrasenaActualRef"
                    v-model="form.current_password"
                    type="password"
                    show-password
                    prefix-icon="Lock"
                    placeholder="Ingrese su contraseña actual"
                />
            </el-form-item>

            <!-- Nueva contraseña -->
            <el-form-item
                label="Nueva Contraseña"
                :error="form.errors.password"
            >
                <el-input
                    ref="nuevaContrasenaRef"
                    v-model="form.password"
                    type="password"
                    show-password
                    prefix-icon="Lock"
                    placeholder="Ingrese la nueva contraseña"
                />
            </el-form-item>

            <!-- Confirmar contraseña -->
            <el-form-item
                label="Confirmar Contraseña"
                :error="form.errors.password_confirmation"
            >
                <el-input
                    v-model="form.password_confirmation"
                    type="password"
                    show-password
                    prefix-icon="Lock"
                    placeholder="Repita la nueva contraseña"
                />
            </el-form-item>

            <div class="flex items-center gap-4">
                <el-button
                    type="primary"
                    :loading="form.processing"
                    @click="actualizarContrasena"
                    native-type="submit"
                >
                    Cambiar Contraseña
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
                        Contraseña actualizada correctamente.
                    </p>
                </Transition>
            </div>
        </el-form>
    </section>
</template>
