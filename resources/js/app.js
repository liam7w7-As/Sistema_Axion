import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

// Element Plus - Componentes UI
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import esLocale from 'element-plus/es/locale/lang/es';
import { ElMessage } from 'element-plus';

// Iconos de Element Plus
import * as ElementPlusIconsVue from '@element-plus/icons-vue';

const appName = import.meta.env.VITE_APP_NAME || 'Sistema Telefonía';

// Interceptor global para errores de Inertia/Red
router.on('exception', (event) => {
    event.preventDefault();
    ElMessage.error(event.detail?.message || 'Error inesperado en la solicitud al servidor.');
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });

        // Registrar plugins
        app.use(plugin);
        app.use(ZiggyVue);
        app.use(ElementPlus, { locale: esLocale });

        // Registrar todos los iconos de Element Plus globalmente
        for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
            app.component(key, component);
        }

        app.mount(el);
    },
    progress: {
        color: '#3b82f6',
    },
});
