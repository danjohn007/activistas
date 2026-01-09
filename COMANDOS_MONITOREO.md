# 📊 Comandos de Monitoreo y Debugging

## Verificar Estado de Índices

### Ver índices de la tabla actividades
```sql
SHOW INDEX FROM actividades;
```

### Ver índices de la tabla usuarios
```sql
SHOW INDEX FROM usuarios;
```

### Ver si un índice se está usando
```sql
EXPLAIN SELECT a.id, a.titulo 
FROM actividades a 
WHERE fecha_actividad >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
AND estado = 'completada';
```

Buscar en el resultado:
- `key`: Muestra qué índice se usó
- `type: ref` o `range` = ✅ Bueno (usa índice)
- `type: ALL` = ❌ Malo (escaneo completo)
- `rows`: Menor es mejor

---

## Consultas de Diagnóstico

### Ver tamaño de las tablas
```sql
SELECT 
    table_name AS 'Tabla',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Tamaño (MB)'
FROM information_schema.TABLES 
WHERE table_schema = 'nombre_de_tu_bd'
ORDER BY (data_length + index_length) DESC;
```

### Ver consultas lentas (si están habilitadas)
```sql
-- Habilitar log de consultas lentas
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Ubicación del log
SHOW VARIABLES LIKE 'slow_query_log_file';
```

### Ver estadísticas de índices
```sql
SELECT 
    TABLE_NAME,
    INDEX_NAME,
    SEQ_IN_INDEX,
    COLUMN_NAME,
    CARDINALITY
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = 'nombre_de_tu_bd'
  AND TABLE_NAME = 'actividades'
ORDER BY TABLE_NAME, INDEX_NAME, SEQ_IN_INDEX;
```

---

## Optimizar Tablas

### Analizar tablas (después de crear índices)
```sql
ANALYZE TABLE usuarios;
ANALYZE TABLE actividades;
ANALYZE TABLE tipos_actividades;
ANALYZE TABLE evidencias;
```

### Optimizar tablas (si hay muchos DELETE/UPDATE)
```sql
OPTIMIZE TABLE actividades;
OPTIMIZE TABLE usuarios;
```

---

## Comandos de AWS CLI

### Ver uso de CPU de RDS
```bash
aws cloudwatch get-metric-statistics \
  --namespace AWS/RDS \
  --metric-name CPUUtilization \
  --dimensions Name=DBInstanceIdentifier,Value=TU_INSTANCIA \
  --start-time 2026-01-09T00:00:00Z \
  --end-time 2026-01-09T23:59:59Z \
  --period 3600 \
  --statistics Average
```

### Ver conexiones a la base de datos
```bash
aws cloudwatch get-metric-statistics \
  --namespace AWS/RDS \
  --metric-name DatabaseConnections \
  --dimensions Name=DBInstanceIdentifier,Value=TU_INSTANCIA \
  --start-time 2026-01-09T00:00:00Z \
  --end-time 2026-01-09T23:59:59Z \
  --period 3600 \
  --statistics Maximum
```

---

## Monitoreo de Caché (PHP)

### Ver estado del caché
```bash
# Contar archivos de caché
ls -l cache/dashboard/ | wc -l

# Ver archivos recientes
ls -lth cache/dashboard/ | head -10

# Ver tamaño del caché
du -sh cache/
```

### En PHP (crear un archivo monitor_cache.php)
```php
<?php
$cacheDir = __DIR__ . '/cache/dashboard';
$files = glob($cacheDir . '/*.cache');
$totalSize = 0;
$count = 0;
$expired = 0;
$now = time();

foreach ($files as $file) {
    $count++;
    $totalSize += filesize($file);
    if ($now - filemtime($file) > 300) {
        $expired++;
    }
}

echo "Archivos en caché: $count\n";
echo "Tamaño total: " . round($totalSize / 1024, 2) . " KB\n";
echo "Archivos expirados: $expired\n";
echo "Hit rate estimado: " . round(($count - $expired) / max($count, 1) * 100, 2) . "%\n";
?>
```

---

## Logs del Sistema

### Ver logs de actividad
```bash
tail -f logs/activity.log
```

### Filtrar consultas lentas
```bash
grep "Consulta lenta" logs/activity.log
```

### Ver errores
```bash
grep "ERROR" logs/activity.log | tail -20
```

---

## Performance Testing

### Test de carga con Apache Bench
```bash
# Primera carga (sin caché)
ab -n 1 -c 1 https://tu-dominio.com/public/dashboards/admin.php

# Con caché
ab -n 10 -c 2 https://tu-dominio.com/public/dashboards/admin.php
```

### Test con curl y timing
```bash
curl -w "@curl-format.txt" -o /dev/null -s https://tu-dominio.com/public/dashboards/admin.php
```

Crear `curl-format.txt`:
```
     time_namelookup:  %{time_namelookup}\n
        time_connect:  %{time_connect}\n
     time_appconnect:  %{time_appconnect}\n
    time_pretransfer:  %{time_pretransfer}\n
       time_redirect:  %{time_redirect}\n
  time_starttransfer:  %{time_starttransfer}\n
                     ----------\n
          time_total:  %{time_total}\n
           size_download:  %{size_download} bytes\n
```

---

## Queries Útiles para Debug

### Ver actividades más consultadas
```sql
SELECT 
    a.id,
    a.titulo,
    COUNT(*) as veces_consultada
FROM actividades a
-- Necesitarías agregar logging en tu app
GROUP BY a.id
ORDER BY veces_consultada DESC
LIMIT 10;
```

### Ver usuarios más activos
```sql
SELECT 
    u.nombre_completo,
    COUNT(a.id) as total_actividades
FROM usuarios u
LEFT JOIN actividades a ON u.id = a.usuario_id
GROUP BY u.id
ORDER BY total_actividades DESC
LIMIT 10;
```

### Ver distribución de actividades por mes
```sql
SELECT 
    DATE_FORMAT(fecha_actividad, '%Y-%m') as mes,
    COUNT(*) as cantidad
FROM actividades
WHERE fecha_actividad >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
GROUP BY mes
ORDER BY mes;
```

---

## Comandos de Mantenimiento

### Backup antes de optimizar
```bash
mysqldump -u usuario -p nombre_bd > backup_$(date +%Y%m%d).sql
```

### Restaurar si algo sale mal
```bash
mysql -u usuario -p nombre_bd < backup_20260109.sql
```

### Verificar integridad de tablas
```sql
CHECK TABLE usuarios;
CHECK TABLE actividades;
```

---

## Scripts Automáticos

### Cron para limpiar caché expirado (cada hora)
```bash
0 * * * * cd /ruta/a/activistas && php clear_cache.php >> logs/cache_cleanup.log 2>&1
```

### Cron para analizar tablas (diario a las 3am)
```bash
0 3 * * * mysql -u usuario -p'password' nombre_bd -e "ANALYZE TABLE usuarios, actividades, tipos_actividades, evidencias;"
```

---

## Métricas a Monitorear

| Métrica | Comando | Valor Objetivo |
|---------|---------|----------------|
| Tiempo de carga dashboard | curl + timing | < 2s (primera), < 0.5s (caché) |
| Tamaño de respuesta | curl -w '%{size_download}' | < 200KB |
| Consultas por página | Logs de app | < 5 |
| Hit rate de caché | Script PHP | > 80% |
| CPU de RDS | CloudWatch | < 50% |
| Conexiones DB | CloudWatch | < 50 |

---

## Alertas Recomendadas

### CloudWatch Alarms (AWS)
```bash
# CPU de RDS > 70%
aws cloudwatch put-metric-alarm \
  --alarm-name rds-high-cpu \
  --alarm-description "RDS CPU alta" \
  --metric-name CPUUtilization \
  --namespace AWS/RDS \
  --statistic Average \
  --period 300 \
  --threshold 70 \
  --comparison-operator GreaterThanThreshold

# Conexiones DB > 80
aws cloudwatch put-metric-alarm \
  --alarm-name rds-high-connections \
  --alarm-description "Muchas conexiones DB" \
  --metric-name DatabaseConnections \
  --namespace AWS/RDS \
  --statistic Average \
  --period 300 \
  --threshold 80 \
  --comparison-operator GreaterThanThreshold
```

---

**Tip:** Guarda estos comandos en un archivo para consulta rápida durante el monitoreo.
