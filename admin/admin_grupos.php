<?php
require_once '../session_init.php';
require_once '../db.php';
require_once '../auth.php';
include '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

// Crear grupo
if (isset($_POST['crear']) && !empty($_POST['nombre'])) {
    $nombre = trim($_POST['nombre']);
    $stmt = $pdo->prepare("INSERT INTO grupos (nombre) VALUES (?)");
    $stmt->execute([$nombre]);
    $success = 'Grupo creado correctamente.';
}

// Editar grupo
if (isset($_POST['editar']) && !empty($_POST['id']) && !empty($_POST['nombre'])) {
    $id = intval($_POST['id']);
    $nombre = trim($_POST['nombre']);
    $stmt = $pdo->prepare("UPDATE grupos SET nombre = ? WHERE id = ?");
    $stmt->execute([$nombre, $id]);
    $success = 'Grupo editado correctamente.';
}

// Eliminar grupo
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $stmt = $pdo->prepare("DELETE FROM grupos WHERE id = ?");
    $stmt->execute([$id]);
    $success = 'Grupo eliminado correctamente.';
}

// Obtener todos los grupos
$stmt = $pdo->query("SELECT * FROM grupos ORDER BY nombre ASC");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>

    <title>Administrar Grupos</title>

</head>
<body>

    <div class="container mt-5">
        <h2>Administrar Grupos</h2>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" class="mb-4">
            <div class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nuevo grupo</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" name="crear" class="btn btn-success w-100">Crear</button>
                </div>
            </div>
        </form>
        <h4>Grupos existentes</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grupos as $grupo): ?>
                    <tr>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="id" value="<?php echo $grupo['id']; ?>">
                            <td>
                                <input type="text" name="nombre" value="<?php echo htmlspecialchars($grupo['nombre']); ?>" class="form-control" required>
                            </td>
                            <td>
                                <button type="submit" name="editar" class="btn btn-primary btn-sm">Editar</button>
                                <a href="?eliminar=<?php echo $grupo['id']; ?>" class="btn btn-danger btn-sm ms-2" onclick="return confirm('¿Eliminar este grupo?')">Eliminar</a>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
