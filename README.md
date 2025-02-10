# Sistema de Analíticas Web

Este sistema proporciona una solución completa para el seguimiento y análisis de visitas web, ofreciendo visualizaciones detalladas y métricas en tiempo real.

## 📋 Características Principales

- Dashboard interactivo en tiempo real
- Seguimiento de múltiples métricas:
  - Visitas totales y usuarios únicos
  - Tiempo promedio de sesión
  - Tasa de rebote
  - Dispositivos utilizados
  - Distribución geográfica
  - Fuentes de tráfico
  - Horarios de mayor actividad

## 🛠️ Requisitos Técnicos

- PHP 7.4 o superior
- Servidor web (Apache/Nginx)
- Permisos de escritura en directorio `/inc/analytics/`
- Navegador moderno con JavaScript habilitado

## 📁 Estructura de Archivos

```
/pcpro/
├── util/
│   ├── analytic/
│   │   ├── index.html         # Dashboard principal
│   │   ├── get_analytics_data.php  # API de datos
│   ├── index.html             # Panel de gráficas estáticas
├── inc/
│   ├── analytics/             # Directorio de datos    @@@ permisos de escritura 
│   │   ├── events.json       # Registro de eventos     @@@ permisos de escritura 
│   │   ├── visits.csv        # Registro de visitas   @@@ permisos de escritura 
│   │   ├── cache.json        # Caché de datos      @@@ permisos de escritura 
│   ├── registro.php          # Script de registro de visitas
```

## 🚀 Instalación

1. Copiar todos los archivos manteniendo la estructura de directorios
2. Asegurar permisos de escritura en `/inc/analytics/`:
   ```bash
   chmod 777 /inc/analytics/
   ```
3. Incluir el script de registro en tu aplicación:
   ```php
   require_once('/inc/registro.php');
   ```

## 💡 Uso

### Dashboard en Tiempo Real
- Acceder a `/util/analytic/index.html`
- Métricas actualizadas cada minuto
- Filtros de tiempo personalizables

### Panel de Gráficas Estáticas
- Acceder a `/util/index.html`
- Visualización de gráficas históricas
- Modal para vista ampliada

## 🔒 Seguridad

El sistema implementa:
- Sanitización de datos
- Control de acceso a archivos
- Caché para optimizar rendimiento
- Protección contra XSS
- Validación de IP y User-Agent

## ⚙️ Configuración

Principales variables configurables en `get_analytics_data.php`:
```php
$cache_time = 300; // Tiempo de caché en segundos
$dataDir = $baseDir . '/inc/analytics'; // Directorio de datos
```

## 📊 API de Datos

Endpoint: `get_analytics_data.php`

Parámetros:
- `refresh=true` : Forzar actualización de datos
- `periodo=24h|7d|30d` : Filtrar por período
- `debug=true` : Modo depuración

## 🤝 Contribución

1. Fork del repositorio
2. Crear rama para feature: `git checkout -b nueva-feature`
3. Commit cambios: `git commit -am 'Añadir nueva feature'`
4. Push a la rama: `git push origin nueva-feature`
5. Crear Pull Request

## 📝 Notas

- Los datos se almacenan localmente
- Se recomienda backup periódico de `/inc/analytics/`
- Revisar logs del servidor para errores
- Monitorear tamaño de archivos JSON/CSV

## 🚨 Solución de Problemas

1. **Error de permisos**: Verificar permisos en directorio analytics
2. **Datos no actualizan**: Limpiar caché del navegador
3. **Gráficas no cargan**: Verificar consola JavaScript
4. **Rendimiento lento**: Ajustar `$cache_time`
