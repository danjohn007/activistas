<?php
/**
 * CONFIGURACIÓN DE OPTIMIZACIÓN DEL SISTEMA
 * 
 * Este archivo contiene ajustes para mejorar el rendimiento
 * del sistema en ambientes de producción como AWS
 */

// =====================================================
// CONFIGURACIÓN DE CACHÉ
// =====================================================

// Tiempo de caché para dashboards (en segundos)
define('CACHE_DASHBOARD_TTL', 300); // 5 minutos

// Tiempo de caché para datos de usuarios (en segundos)
define('CACHE_USER_TTL', 1800); // 30 minutos

// Tiempo de caché para datos estáticos (en segundos)
define('CACHE_STATIC_TTL', 3600); // 1 hora

// Directorio de caché
define('CACHE_DIR', __DIR__ . '/../cache');

// =====================================================
// CONFIGURACIÓN DE LÍMITES
// =====================================================

// Límite de actividades recientes a mostrar
define('DASHBOARD_RECENT_ACTIVITIES_LIMIT', 10);

// Límite de ranking de equipos
define('DASHBOARD_TEAM_RANKING_LIMIT', 5);

// Límite de usuarios pendientes a mostrar
define('DASHBOARD_PENDING_USERS_LIMIT', 20);

// Límite de meses para gráficas
define('DASHBOARD_MONTHS_CHART', 6);

// Límite de actividades por tipo
define('DASHBOARD_ACTIVITIES_BY_TYPE_LIMIT', 10);

// =====================================================
// CONFIGURACIÓN DE BASE DE DATOS
// =====================================================

// Tiempo de timeout para consultas (en segundos)
define('DB_QUERY_TIMEOUT', 30);

// Usar conexión persistente
define('DB_PERSISTENT', false); // Activar solo si es necesario

// =====================================================
// OPTIMIZACIÓN DE CONSULTAS
// =====================================================

// Activar logs de consultas lentas
define('LOG_SLOW_QUERIES', true);

// Umbral para considerar una consulta lenta (en segundos)
define('SLOW_QUERY_THRESHOLD', 2.0);

// =====================================================
// COMPRESIÓN Y OUTPUT
// =====================================================

// Activar compresión de salida
if (!ini_get('zlib.output_compression')) {
    ini_set('zlib.output_compression', '1');
    ini_set('zlib.output_compression_level', '6');
}

// Buffer de salida
if (!ini_get('output_buffering')) {
    ini_set('output_buffering', '4096');
}

// =====================================================
// LÍMITES DE MEMORIA Y TIEMPO
// =====================================================

// Límite de memoria para scripts (solo si no está configurado)
// ini_set('memory_limit', '256M'); // Descomentar si es necesario

// Tiempo máximo de ejecución
// ini_set('max_execution_time', '60'); // Descomentar si es necesario

// =====================================================
// FUNCIONES DE UTILIDAD PARA OPTIMIZACIÓN
// =====================================================

/**
 * Obtener datos del caché
 */
function getCacheData($key, $ttl = CACHE_DASHBOARD_TTL) {
    $cacheFile = CACHE_DIR . '/' . md5($key) . '.cache';
    
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $ttl)) {
        $data = @file_get_contents($cacheFile);
        if ($data !== false) {
            return unserialize($data);
        }
    }
    
    return null;
}

/**
 * Guardar datos en caché
 */
function setCacheData($key, $data) {
    $cacheDir = CACHE_DIR;
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    
    $cacheFile = $cacheDir . '/' . md5($key) . '.cache';
    @file_put_contents($cacheFile, serialize($data), LOCK_EX);
}

/**
 * Limpiar caché expirado
 */
function clearExpiredCache($ttl = CACHE_DASHBOARD_TTL) {
    $cacheDir = CACHE_DIR;
    if (!is_dir($cacheDir)) {
        return;
    }
    
    $files = glob($cacheDir . '/*.cache');
    $now = time();
    $count = 0;
    
    foreach ($files as $file) {
        if ($now - filemtime($file) >= $ttl) {
            @unlink($file);
            $count++;
        }
    }
    
    return $count;
}

/**
 * Limpiar todo el caché
 */
function clearAllCache() {
    $cacheDir = CACHE_DIR;
    if (!is_dir($cacheDir)) {
        return 0;
    }
    
    $files = glob($cacheDir . '/*');
    $count = 0;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
            $count++;
        }
    }
    
    return $count;
}

/**
 * Medir tiempo de ejecución de una función
 */
function measureExecutionTime($callback, $label = '') {
    $start = microtime(true);
    $result = $callback();
    $end = microtime(true);
    $time = $end - $start;
    
    if (LOG_SLOW_QUERIES && $time > SLOW_QUERY_THRESHOLD) {
        logActivity("Consulta lenta detectada" . ($label ? " ($label)" : "") . ": {$time}s", 'WARNING');
    }
    
    return $result;
}

// =====================================================
// INICIALIZACIÓN
// =====================================================

// Crear directorio de caché si no existe
if (!is_dir(CACHE_DIR)) {
    @mkdir(CACHE_DIR, 0755, true);
}

// Crear subdirectorios
$subdirs = ['dashboard', 'users', 'activities', 'reports'];
foreach ($subdirs as $subdir) {
    $path = CACHE_DIR . '/' . $subdir;
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
}

?>
