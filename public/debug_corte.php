<?php
/**
 * Debug para creación de cortes
 */

session_start();

echo "<h2>Debug: Crear Corte</h2>";
echo "<pre>";

echo "=== DATOS POST ===\n";
print_r($_POST);

echo "\n=== DATOS SESSION ===\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'NO DEFINIDO') . "\n";
echo "User Role: " . ($_SESSION['user_role'] ?? 'NO DEFINIDO') . "\n";
echo "CSRF Token: " . ($_SESSION['csrf_token'] ?? 'NO DEFINIDO') . "\n";

echo "\n=== VALIDACIONES ===\n";

// Verificar método
echo "Método: " . $_SERVER['REQUEST_METHOD'] . "\n";
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "❌ Error: No es POST\n";
}

// Verificar CSRF
if (!empty($_POST)) {
    echo "CSRF Token enviado: " . ($_POST['csrf_token'] ?? 'NO ENVIADO') . "\n";
    if (isset($_SESSION['csrf_token']) && isset($_POST['csrf_token'])) {
        if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            echo "✓ CSRF válido\n";
        } else {
            echo "❌ CSRF inválido\n";
        }
    } else {
        echo "❌ CSRF falta en sesión o POST\n";
    }
}

// Verificar campos requeridos
echo "\n=== CAMPOS REQUERIDOS ===\n";
$campos = ['nombre', 'fecha_inicio', 'fecha_fin'];
foreach ($campos as $campo) {
    $valor = $_POST[$campo] ?? '';
    echo "$campo: " . ($valor ? "✓ '$valor'" : "❌ VACÍO") . "\n";
}

echo "\n=== PRUEBA DE INSERCIÓN ===\n";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['nombre'])) {
    try {
        require_once __DIR__ . '/../config/database.php';
        require_once __DIR__ . '/../includes/functions.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        echo "✓ Conexión a BD establecida\n";
        
        // Intentar inserción directa
        $stmt = $db->prepare("
            INSERT INTO cortes_periodo (nombre, descripcion, fecha_inicio, fecha_fin, creado_por, grupo_id, actividad_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $nombre = $_POST['nombre'];
        $descripcion = $_POST['descripcion'] ?? '';
        $fecha_inicio = $_POST['fecha_inicio'];
        $fecha_fin = $_POST['fecha_fin'];
        $creado_por = $_SESSION['user_id'] ?? 1;
        $grupo_id = !empty($_POST['grupo_id']) ? intval($_POST['grupo_id']) : null;
        $actividad_id = !empty($_POST['actividad_id']) ? intval($_POST['actividad_id']) : null;
        
        echo "\nDatos a insertar:\n";
        echo "  nombre: $nombre\n";
        echo "  descripcion: $descripcion\n";
        echo "  fecha_inicio: $fecha_inicio\n";
        echo "  fecha_fin: $fecha_fin\n";
        echo "  creado_por: $creado_por\n";
        echo "  grupo_id: " . ($grupo_id ?? 'NULL') . "\n";
        echo "  actividad_id: " . ($actividad_id ?? 'NULL') . "\n";
        
        $result = $stmt->execute([
            $nombre,
            $descripcion,
            $fecha_inicio,
            $fecha_fin,
            $creado_por,
            $grupo_id,
            $actividad_id
        ]);
        
        if ($result) {
            $id = $db->lastInsertId();
            echo "\n✅ INSERCIÓN EXITOSA\n";
            echo "ID del corte: $id\n";
            
            // Eliminar el registro de prueba
            $db->exec("DELETE FROM cortes_periodo WHERE id = $id");
            echo "✓ Registro de prueba eliminado\n";
        } else {
            echo "\n❌ ERROR EN INSERCIÓN\n";
            print_r($stmt->errorInfo());
        }
        
    } catch (Exception $e) {
        echo "\n❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

echo "</pre>";

echo "<hr>";
echo "<h3>Formulario de Prueba</h3>";
echo '<form method="POST">';
echo '<input type="hidden" name="csrf_token" value="' . ($_SESSION['csrf_token'] ?? '') . '">';
echo '<label>Nombre: <input type="text" name="nombre" value="Test Corte" required></label><br>';
echo '<label>Fecha Inicio: <input type="date" name="fecha_inicio" value="2025-12-01" required></label><br>';
echo '<label>Fecha Fin: <input type="date" name="fecha_fin" value="2025-12-05" required></label><br>';
echo '<label>Descripción: <textarea name="descripcion">Corte de prueba</textarea></label><br>';
echo '<button type="submit">Enviar Prueba</button>';
echo '</form>';
?>
