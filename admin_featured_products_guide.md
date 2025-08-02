# Administración de Productos Destacados

## Descripción del Sistema Actualizado

El sistema ahora funciona de la siguiente manera:

1. **index.php**: Muestra únicamente productos con `destacado = 1` (máximo 6)
2. **catalogo.php**: Muestra todos los productos EXCEPTO los destacados (`destacado = 0` or `NULL`)
3. **Vendedores**: Ya no pueden marcar sus propios productos como destacados
4. **Administrador**: Controla los productos destacados directamente desde la base de datos

## Comandos SQL para Administrar Productos Destacados

### Ver todos los productos destacados actuales
```sql
SELECT p.id, p.nombre, p.precio, p.destacado, v.nombre_empresa as vendedor
FROM productos p 
JOIN vendedores v ON p.id_vendedor = v.id 
WHERE p.destacado = 1
ORDER BY p.nombre;
```

### Ver todos los productos disponibles para destacar
```sql
SELECT p.id, p.nombre, p.precio, p.destacado, v.nombre_empresa as vendedor
FROM productos p 
JOIN vendedores v ON p.id_vendedor = v.id 
WHERE p.activo = 1
ORDER BY p.nombre;
```

### Marcar un producto como destacado (reemplazar X con el ID del producto)
```sql
UPDATE productos SET destacado = 1 WHERE id = X;
```

### Quitar un producto de destacados (reemplazar X con el ID del producto)
```sql
UPDATE productos SET destacado = 0 WHERE id = X;
```

### Limpiar todos los productos destacados
```sql
UPDATE productos SET destacado = 0 WHERE destacado = 1;
```

### Marcar múltiples productos como destacados (reemplazar X,Y,Z con los IDs)
```sql
UPDATE productos SET destacado = 1 WHERE id IN (X,Y,Z);
```

### Contar productos destacados actuales
```sql
SELECT COUNT(*) as total_destacados FROM productos WHERE destacado = 1;
```

## Recomendaciones

1. **Límite sugerido**: Mantener máximo 6 productos destacados para mejor rendimiento y presentación
2. **Rotación**: Cambiar productos destacados periódicamente para dar oportunidad a diferentes vendedores
3. **Calidad**: Seleccionar productos con buenas imágenes y descripciones
4. **Stock**: Verificar que los productos destacados tengan stock disponible

## Ejemplo de Uso

Para destacar 6 productos específicos:
```sql
-- Primero limpiar destacados actuales
UPDATE productos SET destacado = 0 WHERE destacado = 1;

-- Luego marcar los nuevos productos destacados
UPDATE productos SET destacado = 1 WHERE id IN (1, 5, 10, 15, 20, 25);
```

## Verificar Cambios

Después de hacer cambios, puedes verificar visitando:
- **Página principal**: https://tu-dominio.com/vista/index.php (debe mostrar solo productos destacados)
- **Catálogo**: https://tu-dominio.com/vista/catalogo.php (debe mostrar todos excepto destacados)
