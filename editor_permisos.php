<?php
// --- 1. LÓGICA Y PROCESAMIENTO DE FORMULARIOS ---
require_once 'session_init.php';
require_once 'db.php';
require_once 'auth.php';

// Verificar si el usuario está autenticado y es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Revisar si hay un mensaje flash en la sesión
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Procesar la creación de un nuevo menú
if (isset($_POST['nuevo_menu']) && !empty($_POST['nombre_menu'])) {
    $nombre = trim($_POST['nombre_menu']);
    $url = trim($_POST['url_menu']);
    $orden = intval($_POST['orden_menu']);
    $stmt = $pdo->prepare("INSERT INTO menus (nombre, url, orden) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $url, $orden]);
    
    $_SESSION['success_message'] = 'Nuevo menú creado exitosamente.';
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
    
    $_SESSION['success_message'] = 'Nuevo submenú creado exitosamente.';
    header("Location: editor_permisos.php");
    exit;
}

// Procesar el formulario para actualizar permisos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions'])) {
    $target_grupo_id = $_POST['target_grupo_id'] ?? null;
    $selected_modules = $_POST['modulos'] ?? [];

    if ($target_grupo_id === null) {
        $error = 'Error: No se especificó el grupo para actualizar permisos.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM permisos_grupos WHERE grupo_id = :grupo_id");
            $stmt->execute(['grupo_id' => $target_grupo_id]);

            if (!empty($selected_modules)) {
                $insert_sql = "INSERT INTO permisos_grupos (grupo_id, modulo) VALUES (:grupo_id, :modulo)";
                $stmt_insert = $pdo->prepare($insert_sql);
                foreach ($selected_modules as $modulo_id) {
                    $stmt_insert->execute(['grupo_id' => $target_grupo_id, 'modulo' => $modulo_id]);
                }
            }
            $pdo->commit();
            $_SESSION['success_message'] = 'Permisos actualizados exitosamente para el grupo.';
            header("Location: editor_permisos.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error al actualizar permisos: ' . $e->getMessage();
        }
    }
}

// --- 2. OBTENCIÓN DE DATOS PARA MOSTRAR ---
$stmt = $pdo->query("SELECT id, nombre FROM grupos ORDER BY nombre ASC");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, ruta, nombre_personalizado FROM modulos ORDER BY ruta ASC");
$modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, nombre FROM menus ORDER BY orden ASC, nombre ASC");
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

$permisos_por_grupo = [];
$stmt = $pdo->query("SELECT grupo_id, modulo FROM permisos_grupos");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $permisos_por_grupo[$row['grupo_id']][$row['modulo']] = true;
}

// --- 3. PRESENTACIÓN (HTML) ---
include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Editor de Permisos y Menús</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-5">
        <h2>Editor de Permisos y Menús</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="row mb-5">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header"><h4>Crear Nuevo Menú Principal</h4></div>
                    <div class="card-body">
                        <form method="post" action="editor_permisos.php">
                            <div class="mb-3"><input type="text" name="nombre_menu" class="form-control" placeholder="Nombre del menú" required></div>
                            <div class="mb-3"><input type="text" name="url_menu" class="form-control" placeholder="URL (ej: dashboard.php)" required></div>
                            <div class="mb-3"><input type="number" name="orden_menu" class="form-control" placeholder="Orden" value="10" min="1" required></div>
                            <button type="submit" name="nuevo_menu" class="btn btn-success w-100">Crear Menú</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header"><h4>Crear Nuevo Submenú</h4></div>
                    <div class="card-body">
                        <form method="post" action="editor_permisos.php">
                            <div class="mb-3"><input type="text" name="nombre_submenu" class="form-control" placeholder="Nombre del submenú" required></div>
                            <div class="mb-3"><input type="text" name="url_submenu" class="form-control" placeholder="URL (ej: lista_usuarios.php)" required></div>
                            <div class="mb-3"><input type="number" name="orden_submenu" class="form-control" placeholder="Orden" value="10" min="1" required></div>
                            <div class="mb-3">
                                <select name="menu_id_submenu" class="form-select" required>
                                    <option value="">Seleccione Menú Principal</option>
                                    <?php foreach ($menus as $menu): ?>
                                        <option value="<?php echo htmlspecialchars($menu['id']); ?>"><?php echo htmlspecialchars($menu['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="nuevo_submenu" class="btn btn-info w-100">Crear Submenú</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <hr/>

        <h3>Gestión de Permisos por Grupo</h3>
        <?php if (empty($grupos)): ?>
            <div class="alert alert-info">No hay grupos de usuarios registrados.</div>
        <?php else: ?>
            <div class="accordion" id="accordionPermisos">
                <?php foreach ($grupos as $grupo): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo htmlspecialchars($grupo['id']); ?>">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo htmlspecialchars($grupo['id']); ?>" aria-expanded="false" aria-controls="collapse<?php echo htmlspecialchars($grupo['id']); ?>">
                                Permisos para el grupo: <strong><?php echo htmlspecialchars($grupo['nombre']); ?></strong>
                            </button>
                        </h2>
                        <div id="collapse<?php echo htmlspecialchars($grupo['id']); ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo htmlspecialchars($grupo['id']); ?>" data-bs-parent="#accordionPermisos">
                            <div class="accordion-body">
                                <form method="POST" action="editor_permisos.php">
                                    <input type="hidden" name="target_grupo_id" value="<?php echo htmlspecialchars($grupo['id']); ?>">
                                    <div class="row">
                                        <?php if (empty($modulos)): ?>
                                            <div class="alert alert-warning">No hay módulos registrados.</div>
                                        <?php else: ?>
                                            <?php foreach ($modulos as $modulo): ?>
                                                <div class="col-md-4 mb-2">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox"
                                                            name="modulos[]"
                                                            value="<?php echo htmlspecialchars($modulo['id']); ?>"
                                                            id="modulo_<?php echo htmlspecialchars($grupo['id']); ?>_<?php echo htmlspecialchars(str_replace('/', '_', $modulo['id'])); ?>"
                                                            <?php echo (isset($permisos_por_grupo[$grupo['id']][$modulo['id']])) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="modulo_<?php echo htmlspecialchars($grupo['id']); ?>_<?php echo htmlspecialchars(str_replace('/', '_', $modulo['id'])); ?>">
                                                            <?php
                                                                if (!empty($modulo['nombre_personalizado'])) {
                                                                    echo '<strong>' . htmlspecialchars($modulo['nombre_personalizado']) . '</strong>';
                                                                    echo ' <small class="text-muted">(' . htmlspecialchars($modulo['ruta']) . ')</small>';
                                                                } else {
                                                                    echo htmlspecialchars($modulo['ruta']);
                                                                }
                                                            ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                    <button type="submit" name="update_permissions" class="btn btn-primary mt-3">Guardar Permisos para <?php echo htmlspecialchars($grupo['nombre']); ?></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>