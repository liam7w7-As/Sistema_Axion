# Documentacion breve del sistema SISTEF

## 1. Resumen general

SISTEF es un sistema web orientado a la gestion de ventas de articulos y servicios de telefonia. Su objetivo principal es centralizar las operaciones diarias del negocio: administracion de usuarios, productos, servicios, inventario, caja, ventas, reportes y auditoria.

El sistema permite que los vendedores registren aperturas de caja, movimientos y ventas, mientras que el administrador puede supervisar usuarios, productos, inventario, cierres de caja, ganancias y reportes generales. Tambien incorpora controles de acceso por rol, horario de trabajo y trazabilidad de acciones.

## 2. Objetivo del sistema

Automatizar el control operativo de un negocio de telefonia, reduciendo el manejo manual de ventas, movimientos de caja e inventario. Con esto se busca mejorar el seguimiento de las operaciones, facilitar la toma de decisiones y contar con informacion organizada para revision administrativa.

## 3. Tecnologias utilizadas

| Area | Tecnologia |
| --- | --- |
| Backend | Laravel |
| Lenguaje backend | PHP |
| Frontend | Vue 3 con Inertia.js |
| Estilos e interfaz | Tailwind CSS y Element Plus |
| Base de datos | MySQL / SQLite en desarrollo |
| Control de roles y permisos | Spatie Laravel Permission |
| Exportacion de reportes | TCPDF y Maatwebsite Excel |
| Compilacion frontend | Vite |

## 4. Modulos principales

### Gestion de usuarios

Permite crear, modificar, habilitar o deshabilitar usuarios del sistema. Cada usuario puede tener un rol definido, principalmente administrador o vendedor, con permisos asociados segun sus responsabilidades.

### Configuracion del sistema

Permite administrar datos generales como nombre del sistema, moneda, horarios de acceso, formato de impresion y otros parametros operativos.

### Productos y servicios

Administra los productos fisicos y servicios ofrecidos por el negocio. Cada registro incluye informacion como nombre, tipo, precio, estado y datos relacionados al control de stock o ganancias.

### Inventario

Controla las entradas, salidas y ajustes de productos. Este modulo ayuda a mantener actualizado el stock disponible y a relacionar los movimientos con productos especificos.

### Apertura de caja

Registra el inicio de operaciones de caja para un vendedor. La apertura permite asociar ventas y movimientos a una jornada o turno de trabajo.

### Dashboard de vendedor y movimientos

Presenta al vendedor un panel operativo para registrar movimientos, revisar saldos y realizar ventas rapidas. Este modulo concentra las operaciones frecuentes del dia.

### Ventas

Permite registrar ventas con uno o varios items, asociarlas a una apertura de caja y generar comprobantes de impresion. Tambien contempla la anulacion de ventas bajo permisos definidos.

### Cierre de caja

Consolida la informacion registrada durante la apertura de caja. El vendedor puede generar el cierre y el administrador puede revisarlo o aprobarlo.

### Reportes

Incluye reportes de usuarios, productos, ventas, movimientos, caja, saldo de servicios, inventario y ganancias. Los reportes pueden filtrarse y exportarse en PDF o Excel.

### Auditoria

Registra acciones importantes realizadas dentro del sistema, como creaciones, actualizaciones, eliminaciones e inicios o cierres de sesion. Esto permite revisar que usuario realizo una accion y en que momento.

## 5. Roles del sistema

| Rol | Descripcion |
| --- | --- |
| Administrador | Tiene acceso a la configuracion general, usuarios, productos, inventario, reportes, auditoria, ganancias y aprobacion de cierres. |
| Vendedor | Puede operar caja, registrar ventas, movimientos, aperturas y cierres relacionados con su jornada. |

El acceso a las funciones se controla mediante permisos, evitando que usuarios sin autorizacion ingresen a modulos administrativos o sensibles.

## 6. Flujo general de operacion

1. El usuario inicia sesion con su codigo y contrasena.
2. El sistema valida su estado, horario permitido y permisos.
3. El vendedor realiza una apertura de caja.
4. Durante la jornada registra ventas y movimientos.
5. El sistema actualiza informacion relacionada con caja, ventas e inventario.
6. Al finalizar, el vendedor genera el cierre de caja.
7. El administrador revisa y aprueba el cierre.
8. Los reportes quedan disponibles para consulta o exportacion.

## 7. Seguridad y control

El sistema cuenta con varios controles basicos de seguridad:

- Autenticacion de usuarios.
- Roles y permisos por modulo.
- Bloqueo de usuarios deshabilitados.
- Restriccion de acceso por horario.
- Registro de auditoria de acciones.
- Eliminacion logica en registros importantes.
- Validaciones en formularios mediante clases Request de Laravel.

## 8. Estructura tecnica resumida

El sistema sigue una arquitectura web basada en Laravel e Inertia:

- Las rutas se definen en `routes/web.php`.
- Los controladores se encuentran en `app/Http/Controllers`.
- Las validaciones estan en `app/Http/Requests`.
- Los modelos principales estan en `app/Models`.
- Las vistas del frontend estan en `resources/js/Pages`.
- Los componentes reutilizables estan en `resources/js/Components`.
- Las tablas de base de datos se crean mediante migraciones en `database/migrations`.

Esta estructura permite separar responsabilidades y mantener el sistema organizado por modulos.

## 9. Entidades principales

| Entidad | Uso principal |
| --- | --- |
| User | Usuarios del sistema y sus roles. |
| ProductService | Productos y servicios disponibles. |
| Inventory | Movimientos y control de inventario. |
| CashOpening | Aperturas de caja. |
| Sale | Ventas registradas. |
| SaleItem | Detalle de productos o servicios vendidos. |
| CashClosure | Cierres de caja. |
| SellerMovement | Movimientos manuales del vendedor. |
| SystemConfig | Configuracion general del sistema. |
| Log | Registro de auditoria. |

## 10. Reportes disponibles

El sistema permite consultar y exportar reportes de:

- Usuarios.
- Productos y servicios.
- Ventas.
- Movimientos.
- Caja.
- Saldo de servicios.
- Inventario.
- Ganancias.
- Auditoria.

Estos reportes apoyan el seguimiento administrativo y la revision de operaciones del negocio.

## 11. Estado actual

El sistema cuenta con los modulos principales implementados y organizados para su uso en entorno local o despliegue. Existen documentos complementarios en la carpeta `docs` relacionados con despliegue, validacion de requisitos y estructura de informe.

## 12. Recomendaciones

- Realizar pruebas con datos reales antes de usar el sistema en produccion.
- Definir politicas claras para anulacion de ventas y aprobacion de cierres.
- Mantener respaldos periodicos de la base de datos.
- Revisar los permisos de cada usuario antes de entregar el sistema a operacion.
- Complementar esta documentacion con capturas de pantalla de los modulos principales.

