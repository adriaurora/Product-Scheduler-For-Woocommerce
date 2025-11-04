# Cómo Funciona el Sistema de Cron

El plugin **WooCommerce Product Scheduler** está diseñado para funcionar en **dos escenarios diferentes**:

## 📋 Escenario 1: Servidor con Cron Configurado

### Configuración típica:
- `DISABLE_WP_CRON` = `true` en `wp-config.php`
- Crontab del servidor ejecuta: `*/5 * * * * wget -q -O - https://tuweb.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1`

### Cómo funciona:
1. El servidor ejecuta `wp-cron.php` cada 5 minutos
2. WordPress verifica si hay eventos pendientes
3. Si el timestamp del evento `wc_product_scheduler_check` ha llegado, se ejecuta
4. El plugin verifica productos programados y los publica/despublica

### Ventajas:
- ✅ Ejecución precisa cada 5 minutos
- ✅ No depende del tráfico del sitio
- ✅ Mejor rendimiento (no ralentiza visitas de usuarios)

---

## 📋 Escenario 2: WP-Cron Automático (Sin Cron de Servidor)

### Configuración típica:
- `DISABLE_WP_CRON` = `false` (o no definido)
- No hay crontab configurado en el servidor

### Cómo funciona:
1. **Sistema principal**: WordPress intenta ejecutar cron en cada visita al sitio
2. **Sistema de fallback** (añadido en v1.2.0): El plugin verifica cada 5 minutos usando transients
3. Si han pasado más de 5 minutos desde la última ejecución, se ejecuta automáticamente

### Sistema de auto-reparación:
- Detecta si el evento cron está "atascado" (programado en el pasado)
- Si detecta que han pasado más de 10 minutos desde la ejecución programada, lo reprograma automáticamente
- Usa transients para evitar ejecuciones múltiples

### Desventajas:
- ⚠️ Depende de que haya visitas al sitio
- ⚠️ En sitios con poco tráfico puede haber retrasos
- ⚠️ Ejecuta código adicional en cada visita (mínimo impacto)

---

## 🔍 Sistema de Fallback (Nuevo en v1.2.0)

El plugin ahora incluye un **sistema de fallback inteligente** que funciona así:

### Funcionamiento:
```php
1. En cada visita al sitio (hook: wp_loaded)
2. Verifica: ¿Han pasado 5+ minutos desde la última ejecución?
3. Si SÍ:
   - Marca como "ejecutando" (evita duplicados)
   - Ejecuta check_scheduled_products()
   - Guarda timestamp de ejecución
   - Libera el lock
4. Si NO: Sale inmediatamente (sin impacto)
```

### Protecciones:
- ✅ No se ejecuta en el admin (solo en frontend)
- ✅ Usa transient lock para evitar ejecuciones simultáneas
- ✅ Timeout de 5 minutos en el lock (auto-liberación)
- ✅ Solo se ejecuta cada 5 minutos mínimo

---

## 🛠️ Verificación y Diagnóstico

### Ver estado del cron:
Sube el archivo `verificar-cron-wp.php` a la raíz de tu web y accede a:
```
https://tuweb.com/verificar-cron-wp.php
```

### Ejecutar manualmente:
En el mismo archivo, hay un botón "Ejecutar ahora" que fuerza la ejecución.

### Logs:
Si tienes `WP_DEBUG` activo, verás logs en `wp-content/debug.log`:
```
[WC Scheduler] ✅ Evento cron registrado
[WC Scheduler] ✅ Fallback check ejecutado a las 2025-10-26 21:30:00
[WC Scheduler] Buscando productos para despublicar
[WC Scheduler] Despublicación producto #123: ÉXITO
```

---

## ⚙️ Recomendación

**Para producción, siempre es mejor usar cron de servidor (Escenario 1)**:
1. Añade a `wp-config.php`:
   ```php
   define('DISABLE_WP_CRON', true);
   ```

2. Configura crontab:
   ```bash
   */5 * * * * wget -q -O - https://tuweb.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
   ```

Pero **el plugin funcionará en ambos casos** gracias al sistema de fallback.

---

## 🐛 Solución de Problemas

### El cron no se ejecuta:
1. Verifica que el evento esté registrado (verificar-cron-wp.php)
2. Si está en el pasado, recarga cualquier página (se auto-reparará)
3. Verifica los logs para ver si hay errores
4. Prueba ejecución manual desde verificar-cron-wp.php

### Productos no se despublican:
1. Verifica que el toggle esté activado (verde)
2. Verifica que la fecha/hora estén en el futuro al guardar
3. Revisa la columna "Programación" en el listado de productos
4. Ejecuta manualmente el cron para verificar que funciona

### Diferencias de zona horaria:
- El plugin usa la zona horaria configurada en WordPress (Ajustes → Generales)
- Todas las fechas/horas se guardan en esa zona horaria
- El cron también usa esa zona horaria para comparar
