<?php
/**
 * Logout - Cerrar sesión
 */

require_once __DIR__ . '/../controllers/userController.php';

$controller = new UserController();
$controller->logout();
?>