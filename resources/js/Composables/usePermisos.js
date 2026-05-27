import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermisos() {
    const page = usePage();
    
    /**
     * Verifica si el usuario actual tiene un permiso específico.
     * 
     * @param {String} permiso Nombre del permiso (ej: 'gestionar_configuracion')
     * @returns {Boolean}
     */
    const tienePermiso = (permiso) => {
        const usuario = page.props.auth?.user;
        if (!usuario || !usuario.permisos) return false;
        
        return usuario.permisos.includes(permiso);
    };

    /**
     * Verifica si el usuario actual tiene un rol específico.
     * 
     * @param {String} rol Nombre del rol (ej: 'administrador')
     * @returns {Boolean}
     */
    const tieneRol = (rol) => {
        const usuario = page.props.auth?.user;
        if (!usuario || !usuario.roles) return false;
        
        return usuario.roles.includes(rol);
    };

    /**
     * Verifica si el usuario actual tiene permiso para ver las ganancias.
     * Combina el rol con el permiso explícito en la base de datos.
     * 
     * @returns {Boolean}
     */
    const puedeVerGanancias = () => {
        const usuario = page.props.auth?.user;
        if (!usuario) return false;
        
        // El administrador o el que tenga el permiso directo
        if (tienePermiso('visualizar_ganancias') || usuario.visualizar_ganancias) {
            return true;
        }
        
        return false;
    };

    return {
        tienePermiso,
        tieneRol,
        puedeVerGanancias,
        // Computed helpers para templates
        esAdmin: computed(() => tieneRol('administrador')),
        esVendedor: computed(() => tieneRol('vendedor'))
    };
}
