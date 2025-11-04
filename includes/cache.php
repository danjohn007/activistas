<?php
/**
 * Sistema de caché simple para optimizar peticiones API
 * Evita saturación del servidor mediante caché en memoria y archivos
 */

class SimpleCache {
    private static $instance = null;
    private $memoryCache = [];
    private $cacheDir;
    private $defaultTTL = 60; // 60 segundos por defecto
    
    private function __construct() {
        // Usar directorio temporal del sistema
        $this->cacheDir = sys_get_temp_dir() . '/activistas_cache';
        
        // Crear directorio de caché si no existe
        if (!file_exists($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Obtener instancia singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Generar clave de caché
     */
    private function getCacheKey($key, $userId = null) {
        $prefix = $userId ? "user_{$userId}_" : "global_";
        return $prefix . md5($key);
    }
    
    /**
     * Obtener valor del caché
     * 
     * @param string $key Clave del caché
     * @param int $userId ID del usuario (opcional, para caché por usuario)
     * @return mixed|null Valor del caché o null si no existe o expiró
     */
    public function get($key, $userId = null) {
        $cacheKey = $this->getCacheKey($key, $userId);
        
        // Primero verificar caché en memoria
        if (isset($this->memoryCache[$cacheKey])) {
            $cached = $this->memoryCache[$cacheKey];
            if ($cached['expires'] > time()) {
                return $cached['data'];
            } else {
                unset($this->memoryCache[$cacheKey]);
            }
        }
        
        // Verificar caché en archivo
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.cache';
        if (file_exists($cacheFile)) {
            $cached = @unserialize(file_get_contents($cacheFile));
            if ($cached && isset($cached['expires']) && $cached['expires'] > time()) {
                // Guardar en memoria para siguientes accesos
                $this->memoryCache[$cacheKey] = $cached;
                return $cached['data'];
            } else {
                // Eliminar archivo expirado
                @unlink($cacheFile);
            }
        }
        
        return null;
    }
    
    /**
     * Guardar valor en caché
     * 
     * @param string $key Clave del caché
     * @param mixed $data Datos a guardar
     * @param int $ttl Tiempo de vida en segundos (por defecto 60)
     * @param int $userId ID del usuario (opcional, para caché por usuario)
     * @return bool Éxito de la operación
     */
    public function set($key, $data, $ttl = null, $userId = null) {
        if ($ttl === null) {
            $ttl = $this->defaultTTL;
        }
        
        $cacheKey = $this->getCacheKey($key, $userId);
        $expires = time() + $ttl;
        
        $cached = [
            'data' => $data,
            'expires' => $expires,
            'created' => time()
        ];
        
        // Guardar en memoria
        $this->memoryCache[$cacheKey] = $cached;
        
        // Guardar en archivo
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.cache';
        return @file_put_contents($cacheFile, serialize($cached)) !== false;
    }
    
    /**
     * Eliminar valor del caché
     * 
     * @param string $key Clave del caché
     * @param int $userId ID del usuario (opcional)
     * @return bool Éxito de la operación
     */
    public function delete($key, $userId = null) {
        $cacheKey = $this->getCacheKey($key, $userId);
        
        // Eliminar de memoria
        unset($this->memoryCache[$cacheKey]);
        
        // Eliminar archivo
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.cache';
        if (file_exists($cacheFile)) {
            return @unlink($cacheFile);
        }
        
        return true;
    }
    
    /**
     * Limpiar todo el caché
     * 
     * @return bool Éxito de la operación
     */
    public function clear() {
        // Limpiar memoria
        $this->memoryCache = [];
        
        // Limpiar archivos
        if (file_exists($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
            return true;
        }
        
        return false;
    }
    
    /**
     * Limpiar caché expirado (garbage collection)
     */
    public function cleanExpired() {
        if (!file_exists($this->cacheDir)) {
            return;
        }
        
        $files = glob($this->cacheDir . '/*.cache');
        $now = time();
        
        foreach ($files as $file) {
            $cached = @unserialize(file_get_contents($file));
            if (!$cached || !isset($cached['expires']) || $cached['expires'] < $now) {
                @unlink($file);
            }
        }
    }
    
    /**
     * Obtener o ejecutar función con caché
     * Patrón: cache-aside / lazy loading
     * 
     * @param string $key Clave del caché
     * @param callable $callback Función a ejecutar si no hay caché
     * @param int $ttl Tiempo de vida en segundos
     * @param int $userId ID del usuario (opcional)
     * @return mixed Resultado de la función o caché
     */
    public function remember($key, $callback, $ttl = null, $userId = null) {
        $cached = $this->get($key, $userId);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $data = $callback();
        $this->set($key, $data, $ttl, $userId);
        
        return $data;
    }
}

/**
 * Función helper para acceso rápido al caché
 * 
 * @return SimpleCache
 */
function cache() {
    return SimpleCache::getInstance();
}
