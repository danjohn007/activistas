<?php
require_once __DIR__ . '/../controllers/activityController.php';

$controller = new ActivityController();
$controller->listProposals();
?>