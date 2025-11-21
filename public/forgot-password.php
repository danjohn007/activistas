<?php
require_once __DIR__ . '/../controllers/userController.php';

$controller = new UserController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->processForgotPassword();
} else {
    $controller->showForgotPassword();
}
?>
