# Product Scheduler for WooCommerce

Plugin para WordPress/WooCommerce que permite programar la publicación y despublicación automática de productos.

## Características

- ✅ Pestaña "Programación" en la ficha de cada producto
- ✅ Programar fecha y hora de **despublicación** (producto pasa a borrador)
- ✅ Programar fecha y hora de **republicación** (producto vuelve a publicado)
- ✅ Compatible con cron del servidor (WP-Cron desactivado)
- ✅ Notificaciones por email al administrador
- ✅ Usa la zona horaria configurada en WordPress
- ✅ Interfaz intuitiva con datepicker
- ✅ Columna en el listado de productos mostrando programaciones
- ✅ Validación de fechas en tiempo real
- ✅ Registro de acciones (log)

## Requisitos

- WordPress 5.8 o superior
- WooCommerce 5.0 o superior
- PHP 7.4 o superior
- Cron del servidor configurado (recomendado)

## Instalación

1. Descarga el plugin o clona este repositorio
2. Sube la carpeta `Plugin programación productos` al directorio `/wp-content/plugins/` de tu WordPress
3. Activa el plugin desde el menú 'Plugins' en WordPress
4. Verifica que WooCommerce esté instalado y activo

## Configuración del Cron

### Opción 1: Cron del servidor (Recomendado)

Ya que tienes el WP-Cron desactivado en tu `wp-config.php`, puedes configurar el cron del servidor para ejecutar las tareas programadas.

Añade esta línea a tu crontab:

```bash
*/15 * * * * wp --path=/ruta/a/wordpress cron event run wc_product_scheduler_check
```

O alternativamente, ejecuta mediante URL:

```bash
*/15 * * * * curl -s "https://tu-sitio.com/?wc_scheduler_cron=1&key=TU_CLAVE_SECRETA"
```

**Nota:** La clave secreta se genera automáticamente al activar el plugin. Puedes obtenerla desde la base de datos en la opción `wc_product_scheduler_cron_key`.

### Opción 2: Ejecutar manualmente mediante WP-CLI

```bash
wp cron event run wc_product_scheduler_check
```

### Opción 3: Activar WP-Cron temporalmente

Si prefieres usar WP-Cron, comenta o elimina esta línea de tu `wp-config.php`:

```php
// define('DISABLE_WP_CRON', true);
```

## Uso

### Programar un producto

1. Ve a **Productos** > **Editar producto**
2. Haz clic en la pestaña **"Programación"**
3. **Activa el toggle** "Activar despublicación" para programar la despublicación
   - Aparecerán los campos de fecha y hora
   - Configura la fecha y hora de despublicación
4. **Activa el toggle** "Activar republicación" para programar la republicación (opcional)
   - Aparecerán los campos de fecha y hora
   - Configura la fecha y hora de republicación
5. Guarda el producto

**Nota:** Los toggles te permiten activar/desactivar la programación de forma explícita. Si ambos están desactivados, el producto permanecerá sin programación.

### Campos disponibles

#### Despublicar producto
- **Activar despublicación**: Toggle ON/OFF para activar esta funcionalidad
- **Fecha de despublicación**: Formato YYYY-MM-DD (visible solo si está activado)
- **Hora de despublicación**: Formato HH:MM 24 horas (visible solo si está activado)

#### Republicar producto
- **Activar republicación**: Toggle ON/OFF para activar esta funcionalidad
- **Fecha de republicación**: Formato YYYY-MM-DD (visible solo si está activado)
- **Hora de republicación**: Formato HH:MM 24 horas (visible solo si está activado)

### Ejemplo de uso

**Caso: Producto de temporada**

1. Tienes un producto que solo quieres vender durante el verano
2. Ve a la pestaña "Programación" del producto
3. **Activa** el toggle "Activar despublicación"
4. Configuras:
   - Fecha de despublicación: 2025-09-21
   - Hora: 00:00
5. **Activa** el toggle "Activar republicación"
6. Configuras:
   - Fecha de republicación: 2026-06-21
   - Hora: 00:00
7. Guardas el producto
8. El plugin se encargará automáticamente de ocultar y mostrar el producto en las fechas programadas

## Funcionamiento

### Proceso de despublicación

1. El cron verifica cada X minutos si hay productos pendientes
2. Cuando llega la fecha/hora programada:
   - El producto cambia a estado "Borrador"
   - Se elimina la programación de despublicación
   - Se mantiene la programación de republicación (si existe)
   - Se envía email de notificación al administrador
   - Se registra la acción en el log

### Proceso de republicación

1. El cron verifica cada X minutos si hay productos pendientes
2. Cuando llega la fecha/hora programada:
   - El producto cambia a estado "Publicado"
   - Se elimina la programación de republicación
   - Se envía email de notificación al administrador
   - Se registra la acción en el log

## Notificaciones

El plugin envía automáticamente emails al administrador cuando:

- Un producto se despublica automáticamente
- Un producto se republica automáticamente

Los emails incluyen:
- Nombre del producto
- ID del producto
- Fecha y hora de la acción
- Enlace directo para editar el producto
- Información sobre el estado actual

## Zona horaria

El plugin usa la zona horaria configurada en **Ajustes > General** de WordPress. Asegúrate de que esté correctamente configurada.

## Logs y seguimiento

El plugin mantiene un registro de las últimas 100 acciones ejecutadas. Estos logs se guardan en la opción `wc_product_scheduler_logs` de WordPress.

Si tienes `WP_DEBUG` activado, también se escribirán entradas en el log de errores de PHP.

## Columna en listado de productos

En el listado de productos de WooCommerce verás una nueva columna "Programación" que muestra:
- Fecha y hora de despublicación (si está programada)
- Fecha y hora de republicación (si está programada)

## Estructura de archivos

```
Plugin programación productos/
├── wc-product-scheduler.php          # Archivo principal del plugin
├── includes/
│   ├── class-product-tab.php         # Gestión de la pestaña en productos
│   ├── class-scheduler.php           # Lógica de programación y cron
│   └── class-notifications.php       # Sistema de notificaciones
├── assets/
│   ├── css/
│   │   └── admin.css                 # Estilos del panel de admin
│   └── js/
│       └── admin.js                  # JavaScript del panel de admin
└── README.md                         # Este archivo
```

## Desinstalación

Al desactivar el plugin:
- Se eliminan los eventos cron programados
- Los metadatos de los productos se mantienen (por seguridad)

Si deseas eliminar completamente todos los datos:
1. Desactiva el plugin
2. Elimina manualmente las siguientes opciones de la base de datos:
   - `wc_product_scheduler_cron_key`
   - `wc_product_scheduler_logs`
3. Elimina los metadatos de productos:
   - `_scheduler_unpublish_date`
   - `_scheduler_unpublish_time`
   - `_scheduler_unpublish_timestamp`
   - `_scheduler_republish_date`
   - `_scheduler_republish_time`
   - `_scheduler_republish_timestamp`
   - `_scheduler_previous_status`

## Preguntas frecuentes

### ¿Qué precisión tiene el sistema de programación?

Depende de la frecuencia con la que se ejecute el cron. Si configuras el cron cada 15 minutos, la precisión será de ±15 minutos.

### ¿Puedo programar solo la despublicación sin republicación?

Sí, ambos campos son opcionales e independientes.

### ¿Funciona con productos variables?

Sí, funciona con productos simples y variables.

### ¿Se pueden ver los productos programados en un listado?

Actualmente no hay una página dedicada, pero puedes ver la programación en la columna del listado de productos.

### ¿Qué pasa si cambio la zona horaria de WordPress?

Las fechas almacenadas se recalcularán según la nueva zona horaria. Se recomienda no cambiar la zona horaria si ya tienes productos programados.

## Soporte

Para reportar problemas o solicitar nuevas características, contacta con el desarrollador del plugin.

## Licencia

Este plugin es software libre. Puedes redistribuirlo y/o modificarlo bajo los términos que consideres oportunos.

## Changelog

### Versión 1.0.3 (Correcciones de seguridad y funcionalidad)
- 🔒 **SEGURIDAD**: Añadida verificación de nonce para prevenir ataques CSRF
- 🕐 **CRÍTICO**: Timestamps ahora usan la zona horaria de WordPress correctamente
- ⚠️ **VALIDACIÓN**: Las fechas pasadas se rechazan automáticamente con aviso al usuario
- 🔧 Detección automática de WP-Cron desactivado (no crea eventos innecesarios)
- 🎨 jQuery UI CSS ahora se carga desde WordPress (sin dependencias externas)
- 🔐 Escapado seguro de todos los outputs (prevención XSS)
- 🚫 Prevención de guardado durante autosave
- ✅ Verificación de tipo de post antes de guardar
- 🧹 Limpieza de caché de objeto después de actualizar metadatos
- 📝 Mejores mensajes de error para el usuario

### Versión 1.0.2 (CRÍTICA - Corrección de bugs)
- ⚠️ **CORRECCIÓN CRÍTICA**: Solucionado problema que causaba que la web dejara de cargar
- Añadido return temprano en `maybe_check_via_request()` para evitar ejecución en cada petición
- Añadido sistema de locks con transients para prevenir ejecución múltiple
- Optimizadas consultas SQL con LIMIT 50 y verificación de toggle activado
- Añadido manejo robusto de errores con try/catch
- Protección contra procesamiento simultáneo del mismo producto
- Desactivación automática del toggle después de procesar
- **IMPORTANTE**: Si tu web no carga, lee CORRECCION-CRITICA.md

### Versión 1.0.1
- Añadidos toggles (interruptores) para activar/desactivar programación explícitamente
- Mejora en la UX: los campos se muestran/ocultan según el estado del toggle
- Animaciones suaves al mostrar/ocultar campos
- Mayor claridad sobre qué productos tienen programación activa
- El botón "Limpiar programación" ahora desactiva ambos toggles

### Versión 1.0.0
- Lanzamiento inicial
- Programación de despublicación de productos
- Programación de republicación de productos
- Notificaciones por email
- Compatible con cron de servidor
- Interfaz de usuario con datepicker
- Sistema de logs
