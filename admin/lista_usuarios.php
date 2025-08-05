<?php
// Incluir archivos necesarios
require_once '../session_init.php';
require_once '../db.php';
require_once '../auth.php';

// Verificar si el usuario está autenticado
if (!isLoggedIn()) {
    header("Location: ../index.php");
    exit;
}

// Verificar si el usuario tiene permiso de administrador
if (!isAdmin()) {
    header("Location: ../dashboard.php");
    exit;
}

// Consultar todos los usuarios de la tabla
$stmt = $pdo->query("SELECT * FROM usuarios");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuarios</title>
    </head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-5">
        <h2>Lista de Usuarios</h2>

        <?php if (empty($usuarios)): ?>
            <div class="alert alert-info">No hay usuarios registrados.</div>
        <?php else: ?>
            <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Nombre Completo</th>
                        <th>Apellido</th>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Password</th>
                        <th>Rol</th>
                        <th>Grupo</th>
                        <th>Grupo ID</th>
                        <th>Género</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['id']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre_completo']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td>
                                <a href="cambiar_password.php?id=<?php echo $usuario['id']; ?>" class="btn btn-warning btn-sm">Cambiar contraseña</a>
                            </td>
                            <td><?php echo htmlspecialchars($usuario['rol']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['grupo']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['grupo_id']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['genero']); ?></td>
                            <td>
                                <a href="editar_usuario.php?id=<?php echo $usuario['id']; ?>" class="btn btn-sm btn-primary">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>