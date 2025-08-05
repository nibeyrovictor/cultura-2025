<?php
require 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tabla = $_POST['tabla']; // ← CORREGIDO
    $columnas = array_keys($_POST);

    // Quitar "tabla" de las columnas porque es solo para control
    $columnas = array_filter($columnas, fn($col) => $col !== 'tabla');

    $query = 'INSERT INTO `' . $tabla . '` (';
    $values = '';
    foreach ($columnas as $columna) {
        $query .= '`' . $columna . '`,';
        $values .= $pdo->quote($_POST[$columna]) . ',';
    }

    $query = rtrim($query, ',') . ') VALUES (' . rtrim($values, ',') . ')';

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        header('Location: gestionar_exptes.php?success=Registro agregado exitosamente');
    } catch (PDOException $e) {
        echo '<div class="alert alert-danger">Error al agregar el registro: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    header('Location: gestionar_exptes.php');
}
?>