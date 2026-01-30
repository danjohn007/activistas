<?php
/**
 * Funciones auxiliares para manejo de evidencias
 */

/**
 * Buscar archivo de evidencia real en el directorio
 * La BD guarda nombres parciales como: task_2263_1758235478
 * Los archivos reales son: activity_134744_user_1120_1768452717_f2d6d1ec.jpg
 * Esta función busca archivos que contengan el nombre de la BD
 * 
 * @param string $dbFileName Nombre guardado en la base de datos
 * @return string Nombre del archivo real encontrado o el nombre original si no existe
 */
function findEvidenceFile($dbFileName) {
    if (empty($dbFileName)) {
        return $dbFileName;
    }
    
    // Directorio donde se guardan las evidencias
    $evidencesDir = __DIR__ . '/../public/assets/uploads/evidencias/';
    
    // Si el archivo existe tal cual, devolverlo
    if (file_exists($evidencesDir . $dbFileName)) {
        return $dbFileName;
    }
    
    // Buscar archivos que contengan el nombre de la BD
    if (is_dir($evidencesDir)) {
        $files = glob($evidencesDir . '*');
        foreach ($files as $file) {
            $fileName = basename($file);
            // Buscar si el nombre de BD está contenido en el nombre del archivo
            if (strpos($fileName, $dbFileName) !== false) {
                return $fileName;
            }
        }
    }
    
    // No se encontró, devolver el nombre original
    return $dbFileName;
}
?>
