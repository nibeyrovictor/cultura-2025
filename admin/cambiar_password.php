<?php
// Incluir archivos necesarios
require_once '../session_init.php';
require_once '../db.php';
require_once '../auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: index.php");
    exit;
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    die('ID de usuario no válido.');
}

// Obtener usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die('Usuario no encontrado.');
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_password = $_POST['nueva_password'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    if (strlen($nueva_password) < 6) {
        $mensaje = '<div class="alert alert-danger">La contraseña debe tener al menos 6 caracteres.</div>';
    } elseif ($nueva_password !== $confirmar_password) {
        $mensaje = '<div class="alert alert-danger">Las contraseñas no coinciden.</div>';
    } else {
        $hash = password_hash($nueva_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE usuarios SET password = :password WHERE id = :id");
        $stmt->execute(['password' => $hash, 'id' => $user_id]);
        $mensaje = '<div class="alert alert-success">Contraseña actualizada correctamente.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <div class="container mt-5">
        <h2>Cambiar contraseña de <?php echo htmlspecialchars($usuario['nombre_completo'] ?: $usuario['nombre']); ?></h2>
        <?php echo $mensaje; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="nueva_password" class="form-label">Nueva contraseña</label>
                <input type="password" class="form-control" id="nueva_password" name="nueva_password" required minlength="6">
            </div>
            <div class="mb-3">
                <label for="confirmar_password" class="form-label">Confirmar contraseña</label>
                <input type="password" class="form-control" id="confirmar_password" name="confirmar_password" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary">Actualizar contraseña</button>
            <a href="lista_usuarios.php" class="btn btn-secondary ms-2">Volver</a>
        </form>
    </div>
</body>
</html>
