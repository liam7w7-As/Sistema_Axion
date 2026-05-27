/**
 * Composable centralizado para notificaciones del sistema.
 * Usa ElNotification (más grande y visible) en vez de ElMessage (pequeño).
 * Todas las vistas deben usar este composable para mantener consistencia.
 */
import { ElNotification, ElMessageBox } from 'element-plus';

export function useNotificacion() {

    /**
     * Notificación de éxito (verde, esquina superior derecha, 3.5s)
     */
    const notificarExito = (mensaje, titulo = '¡Operación Exitosa!') => {
        ElNotification({
            title: titulo,
            message: mensaje,
            type: 'success',
            duration: 3500,
            position: 'top-right',
            showClose: true
        });
    };

    /**
     * Notificación de error (roja, esquina superior derecha, 5s)
     */
    const notificarError = (mensaje, titulo = 'Error') => {
        ElNotification({
            title: titulo,
            message: mensaje,
            type: 'error',
            duration: 5000,
            position: 'top-right',
            showClose: true
        });
    };

    /**
     * Notificación de advertencia (amarilla, esquina superior derecha, 4s)
     */
    const notificarAdvertencia = (mensaje, titulo = 'Atención') => {
        ElNotification({
            title: titulo,
            message: mensaje,
            type: 'warning',
            duration: 4000,
            position: 'top-right',
            showClose: true
        });
    };

    /**
     * Notificación informativa (azul, esquina superior derecha, 3s)
     */
    const notificarInfo = (mensaje, titulo = 'Información') => {
        ElNotification({
            title: titulo,
            message: mensaje,
            type: 'info',
            duration: 3000,
            position: 'top-right',
            showClose: true
        });
    };

    /**
     * Confirmación para acciones destructivas (modal centrado obligatorio).
     * Retorna una Promesa que se resuelve si confirma y se rechaza si cancela.
     */
    const confirmarAccion = (mensaje, titulo = '¿Está seguro?', opciones = {}) => {
        return ElMessageBox.confirm(mensaje, titulo, {
            confirmButtonText: opciones.textoConfirmar || 'Sí, continuar',
            cancelButtonText: opciones.textoCancelar || 'Cancelar',
            type: opciones.tipo || 'warning',
            draggable: true,
            center: true,
            ...opciones
        });
    };

    return {
        notificarExito,
        notificarError,
        notificarAdvertencia,
        notificarInfo,
        confirmarAccion
    };
}
