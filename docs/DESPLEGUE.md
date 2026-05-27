# Guía de Despliegue a Producción

Este documento provee los pasos exactos para subir el sistema a un entorno de producción (VPS, cPanel, Servidor Dedicado) garantizando seguridad, rendimiento y compatibilidad con Inertia.js.

## 1. Requisitos del Servidor
- **SO:** Ubuntu 20.04/22.04 LTS o equivalente.
- **Servidor Web:** Nginx o Apache.
- **PHP:** Versión 8.2 o superior (Extensiones: ctype, curl, dom, fileinfo, filter, hash, mbstring, openssl, pcre, pdo, session, tokenizer, xml, gd).
- **Base de Datos:** MySQL 8+ o MariaDB 10.3+.
- **Node.js:** Versión 18 o superior.
- **Composer:** Instalado globalmente.

## 2. Preparación del Código y Entorno

1. **Clonar el repositorio** en la carpeta destino (`/var/www/sistef` o `public_html`):
   ```bash
   git clone <URL_DEL_REPOSITORIO> sistef
   cd sistef
   ```

2. **Configurar el archivo de entorno**:
   ```bash
   cp .env.example .env
   ```
   Edite el `.env` con los datos de producción:
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tudominio.com

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nombre_bd
   DB_USERNAME=usuario_bd
   DB_PASSWORD=contraseña_bd
   ```

## 3. Instalación de Dependencias

1. **Instalar paquetes PHP (Sin herramientas de desarrollo):**
   ```bash
   composer install --optimize-autoloader --no-dev
   ```

2. **Instalar paquetes Node y compilar assets (Inertia/Vue):**
   ```bash
   npm install
   npm run build
   ```

## 4. Configuración de Laravel

1. **Generar la Key de la aplicación:**
   ```bash
   php artisan key:generate
   ```

2. **Ejecutar Migraciones y Seeders:**
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   ```
   *(El flag `--force` es necesario en producción).*

3. **Crear el enlace simbólico del Storage (Imágenes, PDF, Exportaciones):**
   ```bash
   php artisan storage:link
   ```

4. **Caché y Optimización (CRÍTICO PARA PRODUCCIÓN):**
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan event:cache
   ```

## 5. Permisos de Directorios
Es indispensable que el servidor web (ej. `www-data` en nginx/apache) tenga permisos de escritura en carpetas específicas:
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 6. Configuración del Servidor Web (Nginx Ejemplo)
Asegúrese de apuntar el `root` a la carpeta `/public` de Laravel.
```nginx
server {
    listen 80;
    server_name tudominio.com;
    root /var/www/sistef/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 7. Mantenimiento y Backups (Opcional)
Se recomienda configurar una tarea Cron (`crontab -e`) para limpiar logs rotados y copias de seguridad de la base de datos:
```bash
* * * * * cd /var/www/sistef && php artisan schedule:run >> /dev/null 2>&1
```
