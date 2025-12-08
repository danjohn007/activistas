<?php
define('INCLUDED', true);
require_once __DIR__ . '/../../controllers/corteController.php';

$controller = new CorteController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->createCorte();
} else {
    $controller->showCreateForm();
}
