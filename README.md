# SISTEF - Sistema Web de Ventas y Telefonía

Sistema integral para el control de ventas, inventarios, cierres de caja y servicios digitales (recargas, chips, etc.). Desarrollado con Laravel 11, Vue 3, Inertia.js y Element Plus.

## 🚀 Requisitos del Servidor (Entorno)

Para que el sistema funcione correctamente, el servidor debe cumplir con los siguientes requisitos:

- **PHP**: >= 8.2
- **Base de datos**: MySQL >= 8.0 o MariaDB >= 10.3
- **Node.js**: >= 18.x (Para compilar los assets en desarrollo/producción)
- **Extensiones de PHP requeridas**:
  - `curl` (Necesario para generación de PDFs con imágenes/TCPDF y peticiones externas)
  - `gd` o `imagick` (Para procesamiento de imágenes)
  - `pdo_mysql`
  - `mbstring`
  - `xml`
  - `zip`

## ⚙️ Pasos de Instalación y Despliegue

Sigue estos pasos cuidadosamente cuando instales el proyecto en un nuevo entorno o servidor:

1. **Clonar o descomprimir el proyecto**
2. **Instalar dependencias de PHP (Composer)**:
   ```bash
   composer install
   ```
3. **Instalar dependencias de Frontend (NPM)**:
   ```bash
   npm install
   ```
4. **Configurar las variables de entorno**:
   - Copia el archivo `.env.example` y renómbralo a `.env`.
   - Configura tus credenciales de base de datos (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
   - Genera la clave de la aplicación:
     ```bash
     php artisan key:generate
     ```
5. **Ejecutar las migraciones y seeders (Base de datos)**:
   ```bash
   php artisan migrate --seed
   ```
   *(Esto creará las tablas y poblará el usuario administrador por defecto y configuraciones).*
6. **⚠️ IMPORTANTE: Crear el enlace simbólico del Storage**:
   ```bash
   php artisan storage:link
   ```
   *Nota: Los enlaces simbólicos (symlinks) dependen del sistema operativo local y **NO** se transfieren en archivos comprimidos (.zip o .rar) por cuestiones de seguridad y arquitectura. Por lo tanto, este comando debe ejecutarse siempre en la máquina de destino para que las imágenes y logos carguen correctamente.*
7. **Compilar los assets de Vue/Tailwind**:
   - Para producción: `npm run build`
   - Para desarrollo: `npm run dev`
8. **Iniciar el servidor (Desarrollo)**:
   ```bash
   php artisan serve
   ```

## 🛠️ Notas para Administradores de Sistemas
- Asegúrese de habilitar la extensión `extension=curl` en su archivo `php.ini`.
- Verifique que los permisos de las carpetas `storage/` y `bootstrap/cache/` tengan permisos de escritura (ej. `chmod -R 775 storage`).
