<?php
// 1. PHP Configuration and Session/DB Setup (NO OUTPUT HERE)
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session_init.php'; // This should start session_start()
require 'db.php';
require_once 'auth.php'; // This should handle authentication and potential redirects

$mensaje = '';
$proveedor_a_editar = null;

// Handle messages from redirects (before any POST processing)
if (isset($_GET['mensaje'])) {
    $mensaje = htmlspecialchars($_GET['mensaje']);
}

// --- LÓGICA DE PROCESAMIENTO DE ACCIONES (POST) ---
// This block MUST run before any HTML output.
// If a redirect happens here, it happens before HTML.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

     try {
        switch ($action) {
            case 'crear':
                $nombre = $_POST['nombre_proveedor'] ?? '';
                $cuit = $_POST['cuit'] ?? '';
                $domicilio = $_POST['domicilio'] ?? '';
                $estado = $_POST['estado'] ?? 'Activo';

                if (!empty($nombre) && !empty($cuit)) {
                    $sql = "INSERT INTO proveedores (nombre_proveedor, cuit, domicilio, estado) VALUES (?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nombre, $cuit, $domicilio, $estado]);
                    // Redirect after successful creation
                    header("Location: editar_proveedores.php?mensaje=" . urlencode('✅ Proveedor creado exitosamente.'));
                    exit(); // IMPORTANT: Always exit after a header redirect
                } else {
                    $mensaje = '❌ Error: El nombre y CUIT son obligatorios.';
                }
                break;

            case 'editar':
                $original_cuit = $_POST['original_cuit'] ?? null;
                $nombre = $_POST['nombre_proveedor'] ?? '';
                $cuit = $_POST['cuit'] ?? '';
                $domicilio = $_POST['domicilio'] ?? '';
                $estado = $_POST['estado'] ?? 'Activo';

                if ($original_cuit && !empty($nombre) && !empty($cuit)) {
                    $sql = "UPDATE proveedores SET nombre_proveedor = ?, cuit = ?, domicilio = ?, estado = ? WHERE cuit = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$nombre, $cuit, $domicilio, $estado, $original_cuit]);
                    header("Location: editar_proveedores.php?mensaje=" . urlencode('✅ Proveedor actualizado exitosamente.'));
                    exit(); // IMPORTANT: Always exit after a header redirect
                } else {
                    $mensaje = '❌ Error: Faltan datos para actualizar o CUIT original no proporcionado.';
                }
                break;

            case 'borrar':
                $cuit_a_borrar = $_POST['cuit_proveedor'] ?? null;

                if ($cuit_a_borrar) {
                    $sql = "DELETE FROM proveedores WHERE cuit = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$cuit_a_borrar]);
                    header("Location: editar_proveedores.php?mensaje=" . urlencode('✅ Proveedor eliminado exitosamente.'));
                    exit(); // IMPORTANT: Always exit after a header redirect
                } else {
                    $mensaje = '❌ Error: No se proporcionó un CUIT para eliminar.';
                }
                break;
            case 'borrar_proveedor_y_relacionados':
                $cuit_a_borrar = $_GET['cuit_proveedor'] ?? null;

                if ($cuit_a_borrar) {
                    try {
                        // Begin a transaction to ensure atomicity
                        $pdo->beginTransaction();

                        // 1. Delete from the proveedores table
                        $sql_proveedores = "DELETE FROM proveedores WHERE cuit = ?";
                        $stmt_proveedores = $pdo->prepare($sql_proveedores);
                        $stmt_proveedores->execute([$cuit_a_borrar]);

                        // 2. Delete from expediente_proveedor_monto
                        $sql_expediente_proveedor_monto = "DELETE FROM expediente_proveedor_monto WHERE nombre_proveedor = (SELECT nombre_proveedor FROM proveedores WHERE cuit = ?)";
                        $stmt_expediente_proveedor_monto = $pdo->prepare($sql_expediente_proveedor_monto);
                        $stmt_expediente_proveedor_monto->execute([$cuit_a_borrar]);

                        // Commit the transaction
                        $pdo->commit();

                        header("Location: editar_proveedores.php?mensaje=" . urlencode('✅ Proveedor y registros relacionados eliminados exitosamente.'));
                        exit(); // Ensure that no further processing occurs
                    } catch (PDOException $e) {
                        $pdo->rollBack(); // Rollback the transaction if any error occurs
                        $mensaje = '❌ Error de base de datos al eliminar: ' . $e->getMessage();
                    }
                } else {
                    $mensaje = '❌ Error: No se proporcionó un CUIT para eliminar.';
                }
                break;
            }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $mensaje = '❌ Error: El CUIT ingresado ya existe en la base de datos o hubo un problema de unicidad.';
        } else {
            $mensaje = '❌ Error de base de datos: ' . $e->getMessage();
        }
    }
}

// --- LÓGICA PARA PREPARAR LA EDICIÓN (GET) ---
if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['cuit'])) {
    $cuit_para_editar = $_GET['cuit'];
    $sql = "SELECT * FROM proveedores WHERE cuit = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cuit_para_editar]);
    $proveedor_a_editar = $stmt->fetch();
    if (!$proveedor_a_editar) {
        $mensaje = '❌ Error: Proveedor no encontrado para edición con el CUIT proporcionado.';
        $proveedor_a_editar = null;
    }
}

// --- LÓGICA PARA OBTENER TODOS LOS PROVEEDORES ---
$sql_todos = "SELECT * FROM proveedores ORDER BY nombre_proveedor ASC";
$todos_los_proveedores = $pdo->query($sql_todos)->fetchAll();

// 2. NOW, AND ONLY NOW, include the header file that contains HTML output
include 'includes/header.php'; // This file should output the <head>, <body>, and navigation etc.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores</title>
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 900px; }
        .card { margin-top: 2rem; }
        .alert { margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4><?= $proveedor_a_editar ? '📝 Editar Proveedor' : '✨ Nuevo Proveedor' ?></h4>
            </div>
            <div class="card-body">
                <form action="editar_proveedores.php" method="POST">
                    <input type="hidden" name="action" value="<?= $proveedor_a_editar ? 'editar' : 'crear' ?>">
                    <?php if ($proveedor_a_editar): ?>
                        <input type="hidden" name="original_cuit" value="<?= htmlspecialchars($proveedor_a_editar['cuit']) ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre_proveedor" class="form-label">Nombre del Proveedor</label>
                            <input type="text" class="form-control" id="nombre_proveedor" name="nombre_proveedor" value="<?= htmlspecialchars($proveedor_a_editar['nombre_proveedor'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cuit" class="form-label">CUIT</label>
                            <input type="text" class="form-control" id="cuit" name="cuit" value="<?= htmlspecialchars($proveedor_a_editar['cuit'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="domicilio" class="form-label">Domicilio</label>
                        <input type="text" class="form-control" id="domicilio" name="domicilio" value="<?= htmlspecialchars($proveedor_a_editar['domicilio'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="Activo" <?= (isset($proveedor_a_editar['estado']) && $proveedor_a_editar['estado'] === 'Activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= (isset($proveedor_a_editar['estado']) && $proveedor_a_editar['estado'] === 'Inactivo') ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary"><?= $proveedor_a_editar ? 'Guardar Cambios' : 'Crear Proveedor' ?></button>
                    <?php if ($proveedor_a_editar): ?>
                        <a href="editar_proveedores.php" class="btn btn-secondary">Cancelar Edición</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert <?= strpos($mensaje, '✅') !== false ? 'alert-success' : 'alert-danger' ?> mt-3">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header">
                <h4>📋 Lista de Proveedores</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>CUIT</th>
                                <th>Domicilio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($todos_los_proveedores)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay proveedores registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($todos_los_proveedores as $proveedor): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($proveedor['nombre_proveedor'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($proveedor['cuit'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($proveedor['domicilio'] ?? '') ?></td>
                                        <td>
                                            <?php
                                            $estado_texto = htmlspecialchars($proveedor['estado'] ?? '');
                                            $badge_class = '';
                                            if ($estado_texto === 'Activo') {
                                                $badge_class = 'bg-success'; // Green for Active
                                            } elseif ($estado_texto === 'Inactivo') {
                                                $badge_class = 'bg-danger'; // Red for Inactive
                                            } else {
                                                $badge_class = 'bg-secondary'; // Default for other states or if not set
                                            }
                                            ?>
                                            <span class="badge <?= $badge_class ?>">
                                                <?= $estado_texto ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="editar_proveedores.php?action=editar&cuit=<?= htmlspecialchars($proveedor['cuit'] ?? '') ?>" class="btn btn-sm btn-warning">Editar</a>
                                            
                                            <form action="editar_proveedores.php" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este proveedor?');">
                                                <input type="hidden" name="action" value="borrar">
                                                <input type="hidden" name="cuit_proveedor" value="<?= htmlspecialchars($proveedor['cuit'] ?? '') ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Borrar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <footer class="text-center text-muted py-4">
            Gestión de Proveedores &copy; <?= date('Y') ?>
        </footer>
    </div>
</body>
</html>