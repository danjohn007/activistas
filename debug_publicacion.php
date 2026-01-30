<?php
/**
 * DIAGNÓSTICO DE PUBLICACIÓN DE ACTIVIDADES
 * Ejecuta este archivo directamente para ver por qué no se muestran las actividades
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Diagnóstico de Publicación de Actividades</h1>";
echo "<pre>";

// 1. Verificar timezone de PHP
echo "\n=== 1. CONFIGURACIÓN PHP ===\n";
echo "Timezone PHP: " . date_default_timezone_get() . "\n";
echo "Hora actual PHP: " . date('Y-m-d H:i:s') . "\n";
echo "NOW() según PHP: " . date('Y-m-d H:i:s') . "\n";

// 2. Verificar timezone de MySQL
echo "\n=== 2. CONFIGURACIÓN MYSQL ===\n";
try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->query("SELECT @@session.time_zone as session_tz, @@global.time_zone as global_tz, NOW() as mysql_now");
    $result = $stmt->fetch();
    echo "Timezone sesión MySQL: " . $result['session_tz'] . "\n";
    echo "Timezone global MySQL: " . $result['global_tz'] . "\n";
    echo "NOW() según MySQL: " . $result['mysql_now'] . "\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// 3. Ver actividades programadas recientes
echo "\n=== 3. ACTIVIDADES PROGRAMADAS RECIENTES ===\n";
try {
    $stmt = $db->prepare("
        SELECT 
            a.id,
            a.titulo,
            a.fecha_publicacion,
            a.hora_publicacion,
            CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) as datetime_publicacion,
            NOW() as ahora_mysql,
            CASE 
                WHEN a.fecha_publicacion IS NULL THEN 'SIN_FECHA_VISIBLE_INMEDIATO'
                WHEN CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) <= NOW() THEN 'DEBERÍA_MOSTRARSE'
                ELSE 'NO_DEBERÍA_MOSTRARSE_AÚN'
            END as estado_visibilidad,
            a.usuario_id,
            u.nombre_completo,
            u.rol,
            a.estado
        FROM actividades a
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
        ORDER BY a.fecha_creacion DESC
        LIMIT 10
    ");
    $stmt->execute();
    $actividades = $stmt->fetchAll();
    
    if (empty($actividades)) {
        echo "No hay actividades creadas en las últimas 2 horas.\n";
    } else {
        echo "Total encontradas: " . count($actividades) . "\n\n";
        foreach ($actividades as $act) {
            echo "-----------------------------------\n";
            echo "ID: {$act['id']}\n";
            echo "Título: {$act['titulo']}\n";
            echo "Usuario: {$act['nombre_completo']} (ID: {$act['usuario_id']}, Rol: {$act['rol']})\n";
            echo "Estado: {$act['estado']}\n";
            echo "Fecha publicación: {$act['fecha_publicacion']}\n";
            echo "Hora publicación: {$act['hora_publicacion']}\n";
            echo "DATETIME combinado: {$act['datetime_publicacion']}\n";
            echo "Hora actual MySQL: {$act['ahora_mysql']}\n";
            echo "VISIBILIDAD: {$act['estado_visibilidad']}\n";
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

// 4. Probar query de activista específico
echo "\n=== 4. PRUEBA QUERY ACTIVISTA (con tu usuario ID) ===\n";
echo "Ingresa el ID de tu usuario activista: ";

// Si se pasa por GET
if (isset($_GET['user_id'])) {
    $userId = intval($_GET['user_id']);
    
    try {
        $stmt = $db->prepare("
            SELECT 
                a.id,
                a.titulo,
                a.fecha_publicacion,
                a.hora_publicacion,
                a.fecha_cierre,
                a.hora_cierre,
                CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) as datetime_publicacion,
                NOW() as ahora,
                CASE 
                    WHEN a.fecha_publicacion IS NULL THEN 'VISIBLE'
                    WHEN CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) <= NOW() THEN 'VISIBLE'
                    ELSE 'OCULTA'
                END as visible
            FROM actividades a
            WHERE a.usuario_id = ?
                AND a.autorizada = 1
                AND (a.fecha_cierre IS NULL OR a.fecha_cierre > CURDATE() 
                    OR (a.fecha_cierre = CURDATE() AND (a.hora_cierre IS NULL OR a.hora_cierre > CURTIME())))
                AND (a.fecha_publicacion IS NULL 
                    OR CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) <= NOW())
            ORDER BY a.fecha_actividad DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $resultados = $stmt->fetchAll();
        
        echo "\nResultados para usuario ID $userId:\n";
        echo "Total visible: " . count($resultados) . "\n\n";
        
        foreach ($resultados as $act) {
            echo "ID: {$act['id']} - {$act['titulo']}\n";
            echo "  Publicación: {$act['datetime_publicacion']}\n";
            echo "  Visible: {$act['visible']}\n";
        }
        
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n⚠️ Ejecuta este script con ?user_id=TU_ID para probar\n";
    echo "Ejemplo: debug_publicacion.php?user_id=1396\n";
}

// 5. Ver actividades que NO deberían mostrarse pero tienen fecha/hora válida
echo "\n=== 5. ACTIVIDADES OCULTAS QUE DEBERÍAN MOSTRARSE ===\n";
try {
    $stmt = $db->query("
        SELECT 
            a.id,
            a.titulo,
            a.fecha_publicacion,
            a.hora_publicacion,
            a.autorizada,
            a.estado,
            u.rol,
            CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) as datetime_publicacion,
            NOW() as ahora
        FROM actividades a
        JOIN usuarios u ON a.usuario_id = u.id
        WHERE a.fecha_publicacion IS NOT NULL
            AND CONCAT(DATE(a.fecha_publicacion), ' ', COALESCE(a.hora_publicacion, '00:00:00')) <= NOW()
            AND a.fecha_creacion >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
            AND (a.autorizada = 0 OR a.estado != 'programada')
        ORDER BY a.fecha_creacion DESC
        LIMIT 5
    ");
    $ocultas = $stmt->fetchAll();
    
    if (empty($ocultas)) {
        echo "✅ No hay actividades problemáticas.\n";
    } else {
        echo "⚠️ Encontradas " . count($ocultas) . " actividades que podrían no mostrarse:\n\n";
        foreach ($ocultas as $act) {
            echo "ID: {$act['id']} - {$act['titulo']}\n";
            echo "  Autorizada: " . ($act['autorizada'] ? 'SÍ' : 'NO ❌') . "\n";
            echo "  Estado: {$act['estado']}\n";
            echo "  Rol usuario: {$act['rol']}\n";
            echo "  Fecha/hora publicación: {$act['datetime_publicacion']}\n";
            echo "  Hora actual: {$act['ahora']}\n";
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNÓSTICO COMPLETO ===\n";
echo "Si ves 'DEBERÍA_MOSTRARSE' pero no aparece, el problema está en:\n";
echo "1. Caché de navegador/aplicación\n";
echo "2. Campo 'autorizada' = 0 (necesita autorización)\n";
echo "3. Estado diferente a 'programada'\n";
echo "4. Filtros en la interfaz\n";
echo "\n";
echo "</pre>";
?>
