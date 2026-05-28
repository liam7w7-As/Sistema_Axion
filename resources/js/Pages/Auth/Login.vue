<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    codigo: '',
    password: '',
    remember: false,
});

const cargando = ref(false);

const iniciarSesion = () => {
    cargando.value = true;
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            cargando.value = false;
        },
    });
};
</script>

<template>
    <Head title="Iniciar Sesión" />

    <div class="app-login">
        <div class="app-login__contenedor">
            <!-- Logo y título -->
            <div class="app-login__cabecera">
                <div class="app-login__logo">
                    <el-icon :size="48" color="#3b82f6">
                        <Headset />
                    </el-icon>
                </div>
                <h1 class="app-login__titulo">Sistema Telefonía</h1>
                <p class="app-login__subtitulo">Ingrese sus credenciales para acceder</p>
            </div>

            <!-- Formulario -->
            <el-form
                @submit.prevent="iniciarSesion"
                label-position="top"
                class="app-login__formulario"
            >
                <!-- Campo código -->
                <el-form-item
                    label="Código de Usuario"
                    :error="form.errors.codigo"
                >
                    <el-input
                        v-model="form.codigo"
                        placeholder="Usuario"
                        prefix-icon="User"
                        size="large"
                        @keyup.enter="iniciarSesion"
                        autofocus
                    />
                </el-form-item>

                <!-- Campo contraseña -->
                <el-form-item
                    label="Contraseña"
                    :error="form.errors.password"
                >
                    <el-input
                        v-model="form.password"
                        type="password"
                        placeholder="Ingrese su contraseña"
                        prefix-icon="Lock"
                        show-password
                        size="large"
                        @keyup.enter="iniciarSesion"
                    />
                </el-form-item>

                <!-- Recordarme -->
                <!--div class="app-login__recordar">
                    <el-checkbox v-model="form.remember">
                        Recordar sesión
                    </el-checkbox>
                </div-->

                <!-- Botón de inicio de sesión -->
                <el-button
                    type="primary"
                    size="large"
                    :loading="cargando"
                    class="app-login__boton"
                    @click="iniciarSesion"
                    native-type="submit"
                >
                    {{ cargando ? 'Iniciando sesión...' : 'Iniciar Sesión' }}
                </el-button>
            </el-form>
        </div>

        <!-- Footer -->
        <p class="app-login__footer">
            Sistema de Ventas de Artículos de Telefonía &copy; {{ new Date().getFullYear() }}
        </p>
    </div>
</template>

<style scoped>
.app-login {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #1e3a5f 0%, #1f2937 50%, #111827 100%);
    padding: 20px;
}

.app-login__contenedor {
    width: 100%;
    max-width: 420px;
    background-color: #ffffff;
    border-radius: 16px;
    padding: 40px 36px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.app-login__cabecera {
    text-align: center;
    margin-bottom: 32px;
}

.app-login__logo {
    display: flex;
    justify-content: center;
    margin-bottom: 16px;
}

.app-login__titulo {
    font-size: 22px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 6px;
}

.app-login__subtitulo {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

.app-login__formulario {
    margin-top: 8px;
}

.app-login__recordar {
    margin-bottom: 24px;
}

.app-login__boton {
    width: 100%;
    font-weight: 600;
    letter-spacing: 0.3px;
}

.app-login__footer {
    margin-top: 24px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.5);
    text-align: center;
}
</style>
