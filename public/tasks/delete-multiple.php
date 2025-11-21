<?php
require_once __DIR__ . '/../../controllers/taskController.php';

$controller = new TaskController();
$controller->deleteMultipleTasks();
