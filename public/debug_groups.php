<?php
/**
 * DEBUG: Test groups query
 * ELIMINAR DESPUÉS DE USAR
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h2>Debug: Grupos en la base de datos</h2>";
    echo "<pre>";
    
    // 1. Verificar que la tabla existe
    echo "1. Verificando tabla grupos...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'grupos'");
    $tableExists = $stmt->rowCount() > 0;
    echo "   Tabla 'grupos' existe: " . ($tableExists ? "SÍ" : "NO") . "\n\n";
    
    if (!$tableExists) {
        die("❌ La tabla 'grupos' no existe. Ejecuta install_groups.php primero.\n");
    }
    
    // 2. Contar grupos totales
    echo "2. Contando grupos totales...\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM grupos");
    $totalGrupos = $stmt->fetch()['total'];
    echo "   Total de grupos: $totalGrupos\n\n";
    
    // 3. Contar grupos activos
    echo "3. Contando grupos activos...\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM grupos WHERE activo = 1");
    $totalActivos = $stmt->fetch()['total'];
    echo "   Total de grupos activos: $totalActivos\n\n";
    
    // 4. Listar todos los grupos
    echo "4. Listando todos los grupos:\n";
    $stmt = $db->query("SELECT id, nombre, descripcion, activo FROM grupos ORDER BY id");
    $grupos = $stmt->fetchAll();
    
    foreach ($grupos as $grupo) {
        $activo = $grupo['activo'] ? 'ACTIVO' : 'INACTIVO';
        echo "   - ID: {$grupo['id']} | {$grupo['nombre']} | [$activo]\n";
        if (!empty($grupo['descripcion'])) {
            echo "     Descripción: {$grupo['descripcion']}\n";
        }
    }
    echo "\n";
    
    // 5. Probar la query del modelo
    echo "5. Probando query del modelo (con LIMIT 5)...\n";
    $groupsQuery = "
        SELECT g.id, g.nombre, g.descripcion
        FROM grupos g
        WHERE g.activo = 1
        ORDER BY g.nombre
        LIMIT 5
    ";
    $stmt = $db->query($groupsQuery);
    $gruposModelo = $stmt->fetchAll();
    echo "   Grupos encontrados: " . count($gruposModelo) . "\n";
    foreach ($gruposModelo as $g) {
        echo "   - {$g['id']}: {$g['nombre']}\n";
    }
    echo "\n";
    
    // 6. Verificar usuarios con grupo_id
    echo "6. Verificando usuarios asignados a grupos...\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE grupo_id IS NOT NULL");
    $usuariosConGrupo = $stmt->fetch()['total'];
    echo "   Usuarios asignados a grupos: $usuariosConGrupo\n\n";
    
    // 7. Listar usuarios por grupo
    echo "7. Usuarios por grupo:\n";
    $stmt = $db->query("
        SELECT g.nombre as grupo, COUNT(u.id) as total_usuarios
        FROM grupos g
        LEFT JOIN usuarios u ON g.id = u.grupo_id AND u.estado = 'activo'
        WHERE g.activo = 1
        GROUP BY g.id, g.nombre
        ORDER BY total_usuarios DESC
    ");
    $stats = $stmt->fetchAll();
    
    foreach ($stats as $stat) {
        echo "   - {$stat['grupo']}: {$stat['total_usuarios']} usuarios\n";
    }
    
    echo "\n✅ Debug completado\n";
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "❌ Error:\n";
    echo $e->getMessage();
    echo "</pre>";
}
?>
