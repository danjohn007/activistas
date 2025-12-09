<?php
/**
 * Crear cortes masivos para todos los grupos
 */

define('INCLUDED', true);

require_once __DIR__ . '/../../controllers/corteController.php';

$controller = new CorteController();
$controller->createMassiveCortes();
