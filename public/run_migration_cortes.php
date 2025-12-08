<?php
/**
 * Script para ejecutar la migración de cortes_periodo
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Ejecutando migración: Cortes de Periodo</h2>\n";
    echo "<pre>\n";
    
    // 1. Verificar si las tablas ya existen
    $checkTable = $db->query("SHOW TABLES LIKE 'cortes_periodo'");
    if ($checkTable->rowCount() > 0) {
        echo "⚠️  La tabla 'cortes_periodo' ya existe.\n";
        echo "¿Desea recrearla? Esto eliminará todos los datos existentes.\n";
        echo "Comente la línea DROP TABLE si desea mantener los datos.\n\n";
        
        // Descomentar estas líneas solo si quieres recrear las tablas
        // $db->exec("DROP TABLE IF EXISTS cortes_detalle");
        // $db->exec("DROP TABLE IF EXISTS cortes_periodo");
        // echo "✓ Tablas eliminadas\n\n";
    }
    
    // 2. Crear tabla cortes_periodo
    echo "Creando tabla cortes_periodo...\n";
    $sqlCortesPeriodo = "
    CREATE TABLE IF NOT EXISTS cortes_periodo (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL,
        descripcion TEXT,
        fecha_inicio DATE NOT NULL,
        fecha_fin DATE NOT NULL,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        creado_por INT NOT NULL,
        grupo_id INT NULL,
        actividad_id INT NULL,
        estado ENUM('activo', 'cerrado') DEFAULT 'activo',
        total_activistas INT DEFAULT 0,
        promedio_cumplimiento DECIMAL(5,2) DEFAULT 0.00,
        FOREIGN KEY (creado_por) REFERENCES usuarios(id),
        FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE SET NULL,
        FOREIGN KEY (actividad_id) REFERENCES tipos_actividades(id) ON DELETE SET NULL,
        INDEX idx_fechas (fecha_inicio, fecha_fin),
        INDEX idx_estado (estado),
        INDEX idx_grupo (grupo_id),
        INDEX idx_actividad (actividad_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Cortes de periodo para snapshots históricos de cumplimiento'
    ";
    
    $db->exec($sqlCortesPeriodo);
    echo "✓ Tabla cortes_periodo creada\n\n";
    
    // 3. Crear tabla cortes_detalle
    echo "Creando tabla cortes_detalle...\n";
    $sqlCortesDetalle = "
    CREATE TABLE IF NOT EXISTS cortes_detalle (
        id INT AUTO_INCREMENT PRIMARY KEY,
        corte_id INT NOT NULL,
        usuario_id INT NOT NULL,
        nombre_completo VARCHAR(255) NOT NULL,
        tareas_asignadas INT NOT NULL DEFAULT 0,
        tareas_entregadas INT NOT NULL DEFAULT 0,
        porcentaje_cumplimiento DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        ranking_posicion INT,
        fecha_calculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (corte_id) REFERENCES cortes_periodo(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
        UNIQUE KEY unique_corte_usuario (corte_id, usuario_id),
        INDEX idx_corte (corte_id),
        INDEX idx_usuario (usuario_id),
        INDEX idx_ranking (corte_id, porcentaje_cumplimiento DESC)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Detalle congelado del cumplimiento de cada activista por corte'
    ";
    
    $db->exec($sqlCortesDetalle);
    echo "✓ Tabla cortes_detalle creada\n\n";
    
    // 4. Verificar creación
    echo "Verificando tablas creadas...\n";
    $tables = $db->query("SHOW TABLES LIKE 'cortes_%'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "  ✓ $table\n";
    }
    
    echo "\n";
    echo "========================================\n";
    echo "✅ MIGRACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "========================================\n";
    echo "\n";
    echo "Ahora puedes usar el sistema de Cortes de Periodo.\n";
    echo "Accede a: Sidebar → Cortes de Periodo\n";
    
} catch (Exception $e) {
    echo "\n";
    echo "========================================\n";
    echo "❌ ERROR EN LA MIGRACIÓN\n";
    echo "========================================\n";
    echo "\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Posibles soluciones:\n";
    echo "1. Verifica que las tablas 'usuarios', 'grupos' y 'tipos_actividades' existan\n";
    echo "2. Verifica los permisos de la base de datos\n";
    echo "3. Revisa el archivo de configuración config/database.php\n";
}

echo "</pre>\n";
?>
