# Changelog v1.4.0 - Mejoras de UX y Flexibilidad

## 🎯 Resumen

Esta versión mejora significativamente la experiencia de usuario y añade flexibilidad total en la programación de productos:

1. **Cambio de terminología**: "Republicación" → "Publicación" (más intuitivo)
2. **Sin restricciones de orden**: Puedes programar publicación ANTES o DESPUÉS de despublicación
3. **Lógica mejorada**: Publicación funciona desde cualquier estado del producto

---

## ✅ Cambios Implementados

### 1. **Terminología Mejorada: "Publicación" en lugar de "Republicación"**

**Problema:**
- El término "Republicar" era confuso
- Los usuarios no entendían que podían programar la publicación inicial
- Asumían que solo servía para volver a publicar algo despublicado

**Solución:**
Cambiado en todos los lugares:

#### UI del Admin:
- ✅ "Republicar producto" → "Publicar producto"
- ✅ "Fecha de republicación" → "Fecha de publicación"
- ✅ "Hora de republicación" → "Hora de publicación"

#### Listado de Productos:
- ✅ "Republicar:" → "Publicar:"

#### Notificaciones por Email:
- ✅ "Producto republicado automáticamente" → "Producto publicado automáticamente"
- ✅ "Producto Republicado" → "Producto Publicado"
- ✅ "Se ha republicado..." → "Se ha publicado..."
- ✅ "El producto ha vuelto a estado Publicado..." → "El producto ha sido publicado..."

---

### 2. **Eliminada Validación de Orden de Fechas**

**Problema:**
- La validación obligaba a que publicación fuera POSTERIOR a despublicación
- Esto impedía casos de uso válidos como:
  - Programar publicación de un producto en borrador
  - Programar publicación Y despublicación posterior del mismo producto
  - Tener fechas de publicación y despublicación independientes

**Código Anterior:**
```javascript
// JavaScript (admin.js)
if (republishDateTime <= unpublishDateTime) {
    $('#_scheduler_republish_date').addClass('scheduler-field-error');
    showMessage('La fecha de republicación debe ser posterior a la de despublicación.', 'warning');
}
```

**Código Actual:**
```javascript
// NOTA: Ya no validamos que publicación deba ser posterior a despublicación
// Se permite cualquier orden de fechas
```

**Resultado:**
✅ Puedes programar publicación ANTES de despublicación
✅ Puedes programar publicación DESPUÉS de despublicación
✅ Ambas programaciones funcionan independientemente

---

### 3. **Lógica de Publicación Mejorada**

**Problema:**
La función `republish_product()` solo funcionaba si el producto estaba en estado `draft`:

```php
// ANTES (v1.3.0)
if (!$product || $product->get_status() !== 'draft') {
    return false; // ❌ Solo funcionaba con productos en borrador
}
```

Esto causaba que:
- ❌ Publicación programada NO funcionaba en productos pendientes
- ❌ Publicación programada NO funcionaba en productos privados
- ❌ Si programabas publicación ANTES que despublicación, no se ejecutaba

**Solución:**

```php
// AHORA (v1.4.0)
if (!$product) {
    return false;
}

// Si ya está publicado, limpiar programación y salir
if ($product->get_status() === 'publish') {
    // Limpiar la programación aunque ya esté publicado
    delete_post_meta($product_id, '_scheduler_republish_date');
    delete_post_meta($product_id, '_scheduler_republish_time');
    delete_post_meta($product_id, '_scheduler_republish_timestamp');
    update_post_meta($product_id, '_scheduler_republish_enabled', 'no');
    return true;
}

// Continuar con publicación desde CUALQUIER estado
```

**Resultado:**
✅ Funciona desde estado `draft` (borrador)
✅ Funciona desde estado `pending` (pendiente de revisión)
✅ Funciona desde estado `private` (privado)
✅ Si ya está publicado, limpia la programación sin error

---

### 4. **Consulta SQL Actualizada**

**Problema:**
La consulta SQL solo buscaba productos en estado `draft`:

```sql
-- ANTES (v1.3.0)
WHERE p.post_type = 'product' AND p.post_status = 'draft'
```

**Solución:**

```sql
-- AHORA (v1.4.0)
WHERE p.post_type = 'product' AND p.post_status != 'publish'
```

**Resultado:**
✅ Busca productos en TODOS los estados excepto ya publicados
✅ Permite programar publicación desde cualquier estado

---

## 📊 Casos de Uso Soportados

### Caso 1: Publicación Programada de Borrador
```
Estado inicial: Draft
Programación: Publicar el 01/02/2025 a las 10:00
Resultado: ✅ Se publica automáticamente
```

### Caso 2: Publicación y Despublicación (Publicación ANTES)
```
Estado inicial: Draft
Programación 1: Publicar el 01/02/2025 a las 10:00
Programación 2: Despublicar el 05/02/2025 a las 18:00
Resultado: ✅ Se publica el 01/02, luego se despublica el 05/02
```

### Caso 3: Despublicación y Publicación (Despublicación ANTES)
```
Estado inicial: Publish
Programación 1: Despublicar el 01/02/2025 a las 18:00
Programación 2: Publicar el 05/02/2025 a las 10:00
Resultado: ✅ Se despublica el 01/02, luego se publica el 05/02
```

### Caso 4: Solo Publicación (Sin Despublicación)
```
Estado inicial: Draft
Programación: Publicar el 01/02/2025 a las 10:00
Resultado: ✅ Se publica automáticamente
```

### Caso 5: Solo Despublicación (Sin Publicación)
```
Estado inicial: Publish
Programación: Despublicar el 01/02/2025 a las 18:00
Resultado: ✅ Se despublica automáticamente
```

---

## 🔧 Archivos Modificados

### 1. **wc-product-scheduler.php**
- Versión actualizada a 1.4.0

### 2. **includes/class-product-tab.php**
- Cambiado "Republicar producto" → "Publicar producto"
- Cambiado "Fecha/Hora de republicación" → "Fecha/Hora de publicación"
- Cambiado "Republicar:" → "Publicar:" en columna de listado

### 3. **includes/class-scheduler.php**
- Función `republish_product()` renombrada a "Publicar un producto" (comentario)
- Lógica mejorada para aceptar productos en cualquier estado
- Consulta SQL actualizada: `p.post_status = 'draft'` → `p.post_status != 'publish'`
- Añadido manejo para productos ya publicados

### 4. **includes/class-notifications.php**
- Cambiado "Producto republicado automáticamente" → "Producto publicado automáticamente"
- Cambiado "Producto Republicado" → "Producto Publicado"
- Cambiado "Se ha republicado..." → "Se ha publicado..."
- Cambiado "ha vuelto a estado Publicado" → "ha sido publicado"

### 5. **assets/js/admin.js**
- Eliminada función `validateRepublishDate()`
- Eliminados eventos de validación de orden de fechas
- Añadido comentario explicativo

---

## ⚠️ Notas de Migración

### Desde v1.3.0 a v1.4.0:

1. **Sin cambios en la base de datos**
   - Los meta_keys siguen siendo los mismos (`_scheduler_republish_*`)
   - Solo cambian los textos en la UI

2. **Sin cambios en funcionalidad existente**
   - Las programaciones actuales siguen funcionando
   - La despublicación funciona exactamente igual

3. **Nueva funcionalidad**
   - Ahora puedes programar en cualquier orden
   - Publicación funciona desde cualquier estado

4. **Compatibilidad hacia atrás**
   - 100% compatible con programaciones creadas en v1.3.0
   - No requiere reconfigurar productos ya programados

---

## 🐛 Bugs Corregidos

### Bug #1: Publicación no funcionaba en ciertos estados
**Problema:** Si el producto no estaba en `draft`, la publicación no se ejecutaba

**Solución:** Ahora funciona desde cualquier estado (draft, pending, private, etc.)

### Bug #2: No se podía programar publicación antes de despublicación
**Problema:** Validación en JavaScript impedía fechas en orden "incorrecto"

**Solución:** Eliminada validación, se permite cualquier orden

### Bug #3: Consulta SQL excluía productos válidos
**Problema:** Solo buscaba productos en `draft` para publicar

**Solución:** Ahora busca productos en cualquier estado excepto ya publicados

---

## ✅ Testing Realizado

- ✅ Publicación de producto en draft
- ✅ Publicación de producto en pending
- ✅ Publicación de producto en private
- ✅ Publicación ANTES de despublicación
- ✅ Publicación DESPUÉS de despublicación
- ✅ Solo publicación sin despublicación
- ✅ Solo despublicación sin publicación
- ✅ Notificaciones por email con nuevo texto
- ✅ Columna en listado con nuevo texto
- ✅ UI actualizada con nuevos textos

---

## 📝 Instrucciones de Actualización

1. **Hacer backup de la base de datos** (recomendado)
2. Desactivar el plugin actual
3. Subir los archivos actualizados
4. Reactivar el plugin
5. Verificar que las programaciones existentes sigan funcionando
6. ✅ Listo! Ya puedes programar en cualquier orden

---

## 🎉 Conclusión

La versión 1.4.0 hace el plugin más intuitivo y flexible:

- 📝 **Terminología más clara**: "Publicación" es más fácil de entender que "Republicación"
- 🔓 **Sin restricciones**: Programa en el orden que necesites
- 🚀 **Más potente**: Funciona desde cualquier estado del producto
- ✅ **100% compatible**: No rompe nada existente

**Casos de uso nuevos habilitados:**
- Programar lanzamiento de productos (publicación desde borrador)
- Promociones temporales (publicación + despublicación)
- Ciclos de disponibilidad (despublicación + publicación recurrente)
- Revisión y aprobación automática (publicación desde pending)
