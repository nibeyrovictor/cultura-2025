<?php
// Incluir archivos necesarios
require 'db.php';
require 'auth.php';

// Verificar si el usuario está autenticado y es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Obtener el ID del usuario a editar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$user_id = $_GET['id'];

// Consultar los datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: dashboard.php");
    exit;
}

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar datos del formulario
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $rol = $_POST['rol'];
    $password = $_POST['password'] ?? null;

    // Validaciones básicas
    if (empty($nombre) || empty($email)) {
        $error = 'Todos los campos obligatorios deben ser completados.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } else {
        // Verificar si el correo ya existe en otro usuario
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND id != :id");
        $stmt->execute(['email' => $email, 'id' => $user_id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error = 'El correo electrónico ya está registrado por otro usuario.';
        } else {
            // Actualizar los datos del usuario
            $sql = "UPDATE usuarios SET nombre = :nombre, email = :email, rol = :rol";
            $params = ['nombre' => $nombre, 'email' => $email, 'rol' => $rol];

            // Si se proporciona una nueva contraseña, actualizarla
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                $sql .= ", password = :password";
                $params['password'] = $hashed_password;
            }

            $sql .= " WHERE id = :id";
            $params['id'] = $user_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $success = '¡Usuario actualizado exitosamente!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap @5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid mt-5">
        <h2>Editar Usuario</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre Completo</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Correo Electrónico</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="rol" class="form-label">Rol</label>
                <select class="form-select" id="rol" name="rol" required>
                    <option value="admin" <?php echo $user['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="usuario" <?php echo $user['rol'] === 'usuario' ? 'selected' : ''; ?>>Usuario Normal</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Nueva Contraseña (opcional)</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Deja en blanco para mantener la contraseña actual">
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>