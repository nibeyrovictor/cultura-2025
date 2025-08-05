<?php
// Incluir archivos necesarios
require_once '../session_init.php';
require '../db.php';
require '../auth.php';

// Verificar si el usuario está autenticado y es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$success = '';

// Obtener el ID del usuario a editar
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: lista_usuarios.php");
    exit;
}

$user_id = $_GET['id'];

// Consultar los datos del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: lista_usuarios.php");
    exit;
}

// Consultar todos los grupos disponibles
$stmt = $pdo->query("SELECT * FROM grupos ORDER BY nombre ASC");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $nombre_completo = trim($_POST['nombre_completo']);
    $apellido = trim($_POST['apellido']);
    $genero = $_POST['genero'];
    $usuario = trim($_POST['usuario']);
    $email = trim($_POST['email']);
    $rol = $_POST['rol'];
    $grupo_id = $_POST['grupo_id'];
    $password = $_POST['password'] ?? null;

    // Validaciones básicas
    if (empty($nombre) || empty($usuario) || empty($email)) {
        $error = 'Todos los campos obligatorios deben ser completados.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } else {
        // Verificar si el correo o usuario ya existen en otro usuario
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE (email = :email OR usuario = :usuario) AND id != :id");
        $stmt->execute(['email' => $email, 'usuario' => $usuario, 'id' => $user_id]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error = 'El correo electrónico o el nombre de usuario ya están registrados por otro usuario.';
        } else {
            // Actualizar los datos del usuario
            $sql = "UPDATE usuarios SET nombre = :nombre, nombre_completo = :nombre_completo, apellido = :apellido, genero = :genero, usuario = :usuario, email = :email, rol = :rol, grupo_id = :grupo_id";
            $params = [
                'nombre' => $nombre,
                'nombre_completo' => $nombre_completo,
                'apellido' => $apellido,
                'genero' => $genero,
                'usuario' => $usuario,
                'email' => $email,
                'rol' => $rol,
                'grupo_id' => $grupo_id
            ];

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

            // Volver a consultar los datos del usuario para reflejar los cambios
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-5">
        <h2>Editar Usuario</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="nombre_completo" class="form-label">Nombre Completo</label>
                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" value="<?php echo htmlspecialchars($user['nombre_completo']); ?>">
            </div>
            <div class="mb-3">
                <label for="apellido" class="form-label">Apellido</label>
                <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($user['apellido']); ?>">
            </div>
            <div class="mb-3">
                <label for="genero" class="form-label">Género</label>
                <select class="form-select" id="genero" name="genero">
                    <option value="Masculino" <?php echo $user['genero'] === 'Masculino' ? 'selected' : ''; ?>>Masculino</option>
                    <option value="Femenino" <?php echo $user['genero'] === 'Femenino' ? 'selected' : ''; ?>>Femenino</option>
                    <option value="@" <?php echo $user['genero'] === '@' ? 'selected' : ''; ?>>@</option>
                    <option value="Otro" <?php echo $user['genero'] === 'Otro' ? 'selected' : ''; ?>>Otro</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="usuario" class="form-label">Nombre de Usuario</label>
                <input type="text" class="form-control" id="usuario" name="usuario" value="<?php echo htmlspecialchars($user['usuario']); ?>" required>
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
                <label for="grupo_id" class="form-label">Grupo</label>
                <select class="form-select" id="grupo_id" name="grupo_id" required>
                    <?php foreach ($grupos as $grupo): ?>
                        <option value="<?php echo $grupo['id']; ?>" <?php echo $user['grupo_id'] == $grupo['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($grupo['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Nueva Contraseña (opcional)</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Deja en blanco para mantener la contraseña actual">
            </div>
            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            <a href="lista_usuarios.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>