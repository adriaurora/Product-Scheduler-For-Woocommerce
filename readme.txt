=== WooCommerce Product Scheduler ===
Contributors: dismaconsultores
Tags: woocommerce, products, scheduler, automation, publishing
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Programa la publicación y despublicación automática de productos de WooCommerce.

== Description ==

WooCommerce Product Scheduler te permite programar cuándo tus productos se publican y despublican automáticamente. Ideal para:

* Lanzamientos programados de productos
* Promociones temporales con inicio y fin automático
* Productos estacionales
* Ciclos de disponibilidad automáticos
* Gestión de inventario temporal

= Características Principales =

* **Publicación Programada**: Programa cuándo un producto debe publicarse automáticamente
* **Despublicación Programada**: Programa cuándo un producto debe despublicarse automáticamente
* **Flexibilidad Total**: Programa ambas acciones independientemente, en cualquier orden
* **Notificaciones por Email**: Recibe emails automáticos cuando se ejecutan las acciones
* **Interfaz Intuitiva**: Switches iOS-style y datepickers integrados en la ficha de producto
* **Alto Rendimiento**: Optimizado para no afectar la velocidad de tu tienda
* **Compatible con WP-Cron**: Funciona con el sistema cron nativo de WordPress
* **Zona Horaria de WordPress**: Respeta la configuración de zona horaria de tu sitio

= Casos de Uso =

**Lanzamiento de Producto**
Crea un producto en borrador y programa su publicación para una fecha específica.

**Promoción Temporal**
Publica un producto automáticamente al inicio de una promoción y despublícalo cuando termine.

**Disponibilidad Estacional**
Programa productos que solo están disponibles en ciertas temporadas del año.

**Eventos Especiales**
Habilita productos para eventos específicos y desactívalos automáticamente después.

= Rendimiento y Optimización =

Este plugin ha sido diseñado con el rendimiento en mente:

* Consultas SQL optimizadas con índices apropiados
* Sistema de caché para evitar consultas repetidas
* Uso de archivos en lugar de transients para cero impacto en base de datos
* Pre-carga de metadatos en listados de productos
* Logs fuera de la tabla de opciones

= Soporte Técnico =

Para reportar bugs o solicitar nuevas funcionalidades, contacta con Disma Consultores.

== Installation ==

1. Sube la carpeta `wc-product-scheduler` al directorio `/wp-content/plugins/`
2. Activa el plugin desde el menú 'Plugins' en WordPress
3. Asegúrate de que WooCommerce está instalado y activo
4. Ve a cualquier producto y encontrarás la nueva pestaña "Programación"

= Requisitos =

* WordPress 5.8 o superior
* WooCommerce 5.0 o superior
* PHP 7.4 o superior
* WP-Cron activo (o cron del servidor configurado)

= Configuración del Cron =

El plugin funciona automáticamente con WP-Cron de WordPress. Si tu servidor ejecuta wp-cron.php mediante crontab, el plugin funcionará sin configuración adicional.

Si deseas mayor precisión, puedes desactivar WP-Cron automático y configurar un cron real en tu servidor:

En wp-config.php:
`define('DISABLE_WP_CRON', true);`

En crontab:
`*/5 * * * * /usr/bin/php /ruta/a/tu/sitio/wp-cron.php`

== Frequently Asked Questions ==

= ¿Necesito configurar algo después de la instalación? =

No, el plugin funciona automáticamente después de la activación. Solo ve a un producto y usa la pestaña "Programación".

= ¿Funciona con productos variables de WooCommerce? =

Sí, funciona con productos simples, variables, agrupados y cualquier tipo de producto de WooCommerce.

= ¿Puedo programar tanto publicación como despublicación? =

Sí, puedes programar ambas acciones de forma totalmente independiente, en cualquier orden.

= ¿Qué pasa si programo una fecha pasada? =

El plugin no permite seleccionar fechas pasadas en el datepicker. Si guardas una fecha pasada manualmente, el sistema la procesará inmediatamente.

= ¿Recibiré notificaciones? =

Sí, el plugin envía un email al administrador cada vez que publica o despublica un producto automáticamente.

= ¿Afecta el rendimiento de mi tienda? =

No, el plugin está altamente optimizado. Las verificaciones se ejecutan cada 5 minutos en segundo plano sin afectar las peticiones de usuarios.

= ¿Puedo ver qué productos están programados? =

Sí, en el listado de productos hay una columna "Programación" que muestra qué productos tienen acciones programadas y para cuándo.

= ¿Qué zona horaria usa? =

El plugin usa la zona horaria configurada en WordPress (Ajustes > Generales > Zona horaria).

= ¿Funciona con otros plugins de caché? =

Sí, es compatible con los principales plugins de caché de WordPress.

== Screenshots ==

1. Pestaña de programación en la edición de producto
2. Switches para activar publicación/despublicación programada
3. Columna en el listado de productos mostrando programaciones activas
4. Email de notificación cuando un producto se publica automáticamente

== Changelog ==

= 1.4.0 =
* Mejorado: Cambio de terminología "Republicación" a "Publicación" para mayor claridad
* Mejorado: Eliminada restricción de orden entre fechas de publicación y despublicación
* Mejorado: Publicación funciona desde cualquier estado del producto (borrador, pendiente, privado)
* Mejorado: Consulta SQL actualizada para buscar productos en cualquier estado no publicado
* Corregido: Publicación no funcionaba si el producto estaba en estado pendiente o privado
* Actualizado: Emails de notificación con nueva terminología

= 1.3.0 =
* Mejorado: Optimización masiva de rendimiento (99.5% menos consultas SQL en listados)
* Mejorado: Sistema de fallback con archivos en lugar de transients (0 consultas SQL)
* Mejorado: Pre-carga de metadatos en listado de productos
* Mejorado: Consultas SQL optimizadas con CAST y mejor orden de JOIN
* Mejorado: Logs movidos a archivo, fuera de la tabla options
* Mejorado: Caché de propiedades (timezone, cron_key)
* Mejorado: Logging condicional solo en modo debug
* Rendimiento: De 200+ consultas a 1 consulta en listado de productos

= 1.2.0 =
* Nuevo: Integración completa con WordPress Cron
* Nuevo: Intervalo personalizado de 5 minutos
* Nuevo: Sistema de fallback para garantizar ejecución
* Mejorado: Compatible con wp-cron.php ejecutado por servidor
* Mejorado: Scripts de diagnóstico para verificar configuración

= 1.1.9 =
* Corregido: CSS no se cargaba en listado de productos
* Corregido: Columna "Programación" aparecía con texto vertical

= 1.1.5 =
* Mejorado: Switches iOS-style con tamaño y posicionamiento optimizado
* Mejorado: CSS con !important para evitar conflictos con otros plugins
* Mejorado: Diseño responsive de la interfaz

= 1.0.0 =
* Versión inicial
* Publicación y despublicación programada de productos
* Notificaciones por email
* Interfaz integrada en WooCommerce
* Soporte para zona horaria de WordPress

== Upgrade Notice ==

= 1.4.0 =
Mejoras importantes de UX: nueva terminología más clara y total flexibilidad en programación de fechas. Actualización recomendada.

= 1.3.0 =
Actualización crítica de rendimiento. Reduce el uso de base de datos en un 99.5%. Altamente recomendado para sitios con muchos productos.

= 1.2.0 =
Ahora funciona con WordPress Cron nativo. Compatible con configuraciones de servidor estándar.

== Developer Notes ==

= Hooks Disponibles =

**Actions:**
* `wc_product_scheduler_unpublished` - Se dispara cuando un producto es despublicado automáticamente
* `wc_product_scheduler_republished` - Se dispara cuando un producto es publicado automáticamente
* `wc_product_scheduler_log` - Se dispara cuando se registra una acción

**Filters:**
* `cron_schedules` - El plugin añade el intervalo 'every_5_minutes'

= Meta Keys =

El plugin usa los siguientes meta keys en productos:
* `_scheduler_unpublish_enabled` - yes/no
* `_scheduler_unpublish_date` - DD-MM-YYYY
* `_scheduler_unpublish_time` - HH:MM
* `_scheduler_unpublish_timestamp` - UNIX timestamp
* `_scheduler_republish_enabled` - yes/no
* `_scheduler_republish_date` - DD-MM-YYYY
* `_scheduler_republish_time` - HH:MM
* `_scheduler_republish_timestamp` - UNIX timestamp

= Estructura de Archivos =

```
wc-product-scheduler/
├── wc-product-scheduler.php          (Main plugin file)
├── readme.txt                         (WordPress plugin readme)
├── includes/
│   ├── class-product-tab.php         (Admin UI and meta box)
│   ├── class-scheduler.php           (Cron logic)
│   └── class-notifications.php       (Email notifications)
└── assets/
    ├── css/
    │   └── admin.css                 (Admin styles)
    └── js/
        └── admin.js                  (Admin JavaScript)
```
