<?php
// Incluir archivos necesarios
require_once 'session_init.php';
require_once 'db.php';
require_once 'auth.php';
include 'includes/header.php';

// Verificar si el usuario está autenticado y es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Consultar todos los permisos actuales
$stmt = $pdo->query("SELECT * FROM permisos_grupos ORDER BY grupo_id ASC, modulo ASC");
$permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consultar todos los grupos para el select
$stmt = $pdo->query("SELECT id, nombre FROM grupos ORDER BY nombre ASC");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consultar todos los módulos para el select
$stmt = $pdo->query("SELECT id, ruta FROM modulos ORDER BY id ASC");
$modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar el formulario para agregar un nuevo permiso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agregar'])) {
    $grupo = trim($_POST['grupo']);
    $modulo = trim($_POST['modulo']);

    // Validaciones básicas
    if (empty($grupo) || empty($modulo)) {
        $error = 'Todos los campos son obligatorios.';
    } else {
        // Verificar si el permiso ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM permisos_grupos WHERE grupo_id = :grupo_id AND modulo = :modulo");
        $stmt->execute(['grupo_id' => $grupo, 'modulo' => $modulo]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            $error = 'El permiso ya existe.';
        } else {
            // Insertar el nuevo permiso
            $stmt = $pdo->prepare("INSERT INTO permisos_grupos (grupo_id, modulo) VALUES (:grupo_id, :modulo)");
            $stmt->execute(['grupo_id' => $grupo, 'modulo' => $modulo]);

            $success = 'Permiso agregado exitosamente.';
        }
    }
}

// Procesar la creación de un nuevo menú
if (isset($_POST['nuevo_menu']) && !empty($_POST['nombre_menu'])) {
    $nombre = trim($_POST['nombre_menu']);
    $url = trim($_POST['url_menu']);
    $orden = intval($_POST['orden_menu']);
    $stmt = $pdo->prepare("INSERT INTO menus (nombre, url, orden) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $url, $orden]);
    $success = 'Nuevo menú creado.';
    header("Location: editor_permisos.php");
    exit;
}
// Procesar la creación de un nuevo submenú
if (isset($_POST['nuevo_submenu']) && !empty($_POST['nombre_submenu'])) {
    $nombre = trim($_POST['nombre_submenu']);
    $url = trim($_POST['url_submenu']);
    $orden = intval($_POST['orden_submenu']);
    $menu_id = intval($_POST['menu_id_submenu']);
    $stmt = $pdo->prepare("INSERT INTO submenus (menu_id, nombre, url, orden) VALUES (?, ?, ?, ?)");
    $stmt->execute([$menu_id, $nombre, $url, $orden]);
    $success = 'Nuevo submenú creado.';
    header("Location: editor_permisos.php");
    exit;
}

// Procesar la eliminación de un permiso
if (isset($_GET['eliminar']) && !empty($_GET['eliminar'])) {
    $id = $_GET['eliminar'];

    // Eliminar el permiso
    $stmt = $pdo->prepare("DELETE FROM permisos_grupos WHERE id = :id");
    $stmt->execute(['id' => $id]);

    $success = 'Permiso eliminado exitosamente.';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <title>Editor de Permisos</title>

</head>
<body>

    <div class="container-fluid mt-5">
        <h2>Editor de Permisos</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Formulario para agregar nuevos permisos -->
        <h4>Agregar Nuevo Permiso</h4>
        <form method="POST" class="mb-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="grupo" class="form-label">Grupo</label>
                    <select class="form-control" id="grupo" name="grupo" required>
                        <option value="">Seleccione un grupo</option>
                        <?php foreach ($grupos as $grupo): ?>
                            <option value="<?php echo htmlspecialchars($grupo['id']); ?>">
                                <?php echo htmlspecialchars($grupo['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="modulo" class="form-label">Módulo</label>
                    <select class="form-control" id="modulo" name="modulo" required>
                        <option value="">Seleccione un módulo</option>
                        <?php foreach ($modulos as $modulo): ?>
                            <option value="<?php echo htmlspecialchars($modulo['id']); ?>">
                                <?php echo htmlspecialchars($modulo['id']); ?> (<?php echo htmlspecialchars($modulo['ruta']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" name="agregar" class="btn btn-primary mt-3">Agregar Permiso</button>
        </form>

        <hr>
        <h4>Crear Nuevo Menú</h4>
        <form method="post" class="mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="nombre_menu" class="form-control" placeholder="Nombre del menú" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="url_menu" class="form-control" placeholder="URL (ej: dashboard.php)" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="orden_menu" class="form-control" placeholder="Orden" value="1" min="1" required>
                </div>
                <div class="col-md-3">
                    <button type="submit" name="nuevo_menu" class="btn btn-success w-100">Crear Menú</button>
                </div>
            </div>
        </form>
        <h4>Crear Nuevo Submenú</h4>
        <form method="post">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="nombre_submenu" class="form-control" placeholder="Nombre del submenú" required>
                </div>
                <div class="col-md-4">
                    <input type="text" name="url_submenu" class="form-control" placeholder="URL (ej: lista_usuarios.php)" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="orden_submenu" class="form-control" placeholder="Orden" value="1" min="1" required>
                </div>
                <div class="col-md-3">
                    <select name="menu_id_submenu" class="form-select" required>
                        <option value="">Menú principal</option>
                        <?php foreach ($menus as $menu): ?>
                            <option value="<?php echo $menu['id']; ?>"><?php echo htmlspecialchars($menu['nombre']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12 mt-2">
                    <button type="submit" name="nuevo_submenu" class="btn btn-success w-100">Crear Submenú</button>
                </div>
            </div>
        </form>
        <!-- Lista de permisos actuales -->
        <h4>Permisos Actuales</h4>
        <?php if (empty($permisos)): ?>
            <div class="alert alert-info">No hay permisos registrados.</div>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Grupo</th>
                        <th>Módulo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permisos as $permiso): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($permiso['id']); ?></td>
                            <td><?php echo htmlspecialchars($permiso['grupo_id']); ?></td>
                            <td><?php echo htmlspecialchars($permiso['modulo']); ?></td>
                            <td>
                                <a href="?eliminar=<?php echo $permiso['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este permiso?')">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>