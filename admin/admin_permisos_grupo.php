<?php
require_once '../session_init.php';
require_once '../db.php';
require_once '../auth.php';
include '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../index.php");
    exit;
}

// Obtener todos los grupos
$stmt = $pdo->query("SELECT id, nombre FROM grupos ORDER BY nombre ASC");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los módulos
$stmt = $pdo->query("SELECT id, ruta FROM modulos ORDER BY id ASC");
$modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grupo_id = isset($_GET['grupo_id']) ? intval($_GET['grupo_id']) : ($grupos[0]['id'] ?? 0);
$success = '';
$error = '';

// Guardar permisos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modulos'])) {
    $grupo_id = intval($_POST['grupo_id']);
    $modulos_seleccionados = $_POST['modulos'];
    // Eliminar permisos actuales
    $pdo->prepare("DELETE FROM permisos_grupos WHERE grupo_id = ?")->execute([$grupo_id]);
    // Insertar nuevos permisos
    $insert = $pdo->prepare("INSERT INTO permisos_grupos (grupo_id, modulo) VALUES (?, ?)");
    foreach ($modulos_seleccionados as $modulo) {
        $insert->execute([$grupo_id, $modulo]);
    }
    $success = 'Permisos actualizados correctamente.';
}

// Obtener permisos actuales del grupo
$stmt = $pdo->prepare("SELECT modulo FROM permisos_grupos WHERE grupo_id = ?");
$stmt->execute([$grupo_id]);
$permisos_actuales = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'modulo');
?>
<!DOCTYPE html>
<html lang="es">
<head>

    <title>Permisos por Grupo</title>

</head>
<body>

    <div class="container mt-5">
        <h2>Permisos por Grupo</h2>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="get" class="mb-4">
            <label for="grupo_id" class="form-label">Selecciona un grupo:</label>
            <select name="grupo_id" id="grupo_id" class="form-select" onchange="this.form.submit()">
                <?php foreach ($grupos as $g): ?>
                    <option value="<?php echo $g['id']; ?>" <?php if ($g['id'] == $grupo_id) echo 'selected'; ?>><?php echo htmlspecialchars($g['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <form method="post">
            <input type="hidden" name="grupo_id" value="<?php echo $grupo_id; ?>">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Módulo</th>
                        <th>Ruta</th>
                        <th>Permitir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($modulos as $mod): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mod['id']); ?></td>
                            <td><?php echo htmlspecialchars($mod['ruta']); ?></td>
                            <td>
                                <input type="checkbox" name="modulos[]" value="<?php echo htmlspecialchars($mod['id']); ?>" <?php if (in_array($mod['id'], $permisos_actuales)) echo 'checked'; ?>>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">Guardar Permisos</button>
        </form>
    </div>
</body>
</html>
