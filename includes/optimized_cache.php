<?php
/**
 * Sistema de Caché Mejorado para AWS
 * Reduce carga de BD y mejora velocidad de respuesta
 */

class OptimizedCache {
    private static $instance = null;
    private $cacheDir;
    private $defaultTTL = 300; // 5 minutos por defecto
    
    private function __construct() {
        $this->cacheDir = __DIR__ . '/../cache';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        
        // Limpiar caché viejo al inicializar (1% de probabilidad)
        if (rand(1, 100) === 1) {
            $this->cleanExpiredCache();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtener valor del caché
     * @param string $key Clave única
     * @return mixed|null Valor o null si no existe/expiró
     */
    public function get($key) {
        $file = $this->getCacheFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = @file_get_contents($file);
        if ($data === false) {
            return null;
        }
        
        $data = @unserialize($data);
        if ($data === false || !isset($data['expires']) || !isset($data['value'])) {
            @unlink($file);
            return null;
        }
        
        // Verificar expiración
        if (time() > $data['expires']) {
            @unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Guardar valor en caché
     * @param string $key Clave única
     * @param mixed $value Valor a guardar
     * @param int $ttl Tiempo de vida en segundos (default 5 min)
     * @return bool Éxito
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $file = $this->getCacheFilePath($key);
        
        $data = [
            'expires' => time() + $ttl,
            'value' => $value,
            'created' => time()
        ];
        
        return @file_put_contents($file, serialize($data), LOCK_EX) !== false;
    }
    
    /**
     * Eliminar valor del caché
     */
    public function delete($key) {
        $file = $this->getCacheFilePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
    
    /**
     * Limpiar todo el caché
     */
    public function flush() {
        $files = glob($this->cacheDir . '/*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
    }
    
    /**
     * Limpiar caché expirado
     */
    private function cleanExpiredCache() {
        $files = glob($this->cacheDir . '/*.cache');
        $now = time();
        $cleaned = 0;
        
        foreach ($files as $file) {
            // Limpiar archivos muy viejos sin leer contenido
            if ($now - filemtime($file) > 3600) { // > 1 hora
                @unlink($file);
                $cleaned++;
                continue;
            }
            
            // Verificar expiración leyendo el archivo
            $data = @file_get_contents($file);
            if ($data !== false) {
                $data = @unserialize($data);
                if (isset($data['expires']) && $now > $data['expires']) {
                    @unlink($file);
                    $cleaned++;
                }
            }
        }
        
        if ($cleaned > 0) {
            error_log("OptimizedCache: Limpiados $cleaned archivos de caché expirados");
        }
    }
    
    /**
     * Obtener ruta del archivo de caché
     */
    private function getCacheFilePath($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
    
    /**
     * Wrapper para caché con callback
     * Obtiene del caché o ejecuta callback y guarda resultado
     * 
     * @param string $key Clave del caché
     * @param callable $callback Función a ejecutar si no hay caché
     * @param int $ttl Tiempo de vida
     * @return mixed Resultado
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
}

// Funciones helper globales
function getOptimizedCache() {
    return OptimizedCache::getInstance();
}

function cacheRemember($key, $callback, $ttl = 300) {
    return OptimizedCache::getInstance()->remember($key, $callback, $ttl);
}
