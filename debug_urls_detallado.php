<?php
require_once 'config/database.php';

$actividad_id = 144431;

echo "=== DEBUG URLS DETALLADO ===\n\n";

$db = Database::getInstance()->getConnection();

// Obtener evidencias
$sql = "SELECT id, actividad_id, tipo_evidencia, archivo, bloqueada, contenido 
        FROM evidencias 
        WHERE actividad_id = :id
        ORDER BY id ASC";

$stmt = $db->prepare($sql);
$stmt->execute([':id' => $actividad_id]);
$evidencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "TOTAL EVIDENCIAS ENCONTRADAS: " . count($evidencias) . "\n\n";

foreach ($evidencias as $ev) {
    echo "--- Evidencia ID: {$ev['id']} ---\n";
    echo "Bloqueada: {$ev['bloqueada']} (0=referencia admin, 1=evidencia usuario)\n";
    echo "Tipo: {$ev['tipo_evidencia']}\n";
    echo "Archivo original: '{$ev['archivo']}'\n";
    
    // Simular lógica del view
    $archivoOriginal = $ev['archivo'];
    
    // Si ya es URL completa
    if (strpos($archivoOriginal, 'http://') === 0 || strpos($archivoOriginal, 'https://') === 0) {
        echo "  → Es URL completa, usar directamente\n";
        $archivoUrl = $archivoOriginal;
    } else {
        // Construir path
        $archivo = 'public/assets/uploads/evidencias/' . basename($archivoOriginal);
        echo "  → basename: " . basename($archivoOriginal) . "\n";
        echo "  → Path construido: $archivo\n";
        
        // Simular url() helper
        $baseUrl = 'https://ejercitodigital.com.mx/';
        $archivoUrl = $baseUrl . $archivo;
    }
    
    echo "  → URL final: $archivoUrl\n";
    
    // Verificar si el archivo existe físicamente
    $fullPath = __DIR__ . '/public/assets/uploads/evidencias/' . basename($archivoOriginal);
    echo "  → Path físico: $fullPath\n";
    echo "  → ¿Existe?: " . (file_exists($fullPath) ? "✅ SÍ" : "❌ NO") . "\n";
    
    echo "\n";
}
