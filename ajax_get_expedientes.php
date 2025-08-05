<?php
// ajax_get_expedientes.php

header('Content-Type: application/json');

require 'db.php'; // Conexión a la base de datos

$año = $_GET['año'] ?? null;

if (!$año || !preg_match('/^\d{4}$/', $año)) {
    echo json_encode(['error' => 'Año inválido o no proporcionado.']);
    exit;
}

$tableName = 'exptes-' . $año;
$expedientes = [];

try {
    // Comprobar si la tabla existe para evitar errores
    $stmt_check = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
    if ($stmt_check->rowCount() > 0) {
        $stmt = $pdo->query("SELECT id_expediente FROM `{$tableName}` ORDER BY id_expediente ASC");
        $expedientes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        echo json_encode(['error' => 'No existe tabla para este año.']);
        exit;
    }

} catch (PDOException $e) {
    // En un entorno de producción, sería mejor loguear el error que mostrarlo
    echo json_encode(['error' => 'Error de base de datos.']);
    error_log("AJAX Error: " . $e->getMessage());
    exit;
}

echo json_encode($expedientes);