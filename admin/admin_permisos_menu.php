<?php
// --- 1. INICIALIZACIÓN Y LÓGICA DE PROCESAMIENTO ---
require_once '../session_init.php';
require_once '../db.php';
require_once '../auth.php';
require_once '../poblar_modulos.php'; // Define la función sincronizarModulos()

// AÑADIDO: Función de ayuda para asegurar que las URLs sean absolutas.
function normalizarUrl($url) {
    $url = trim($url);
    if (empty($url) || $url === '#') {
        return '#';
    }
    if (strpos($url, '/') !== 0) {
        $url = '/' . $url;
    }
    return $url;
}

// La sincronización ahora solo se ejecuta si se pasa un parámetro en la URL
$mensaje_sincronizacion = '';
if (isset($_GET['sincronizar'])) {
    $mensaje_sincronizacion = sincronizarModulos($pdo);
}

// Verificar si el usuario está autenticado y es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Revisar si hay un mensaje de éxito en la sesión (Flash Message)
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// --- LÓGICA DE GESTIÓN (CRUD) ---

// Eliminar Menú
if (isset($_GET['eliminar_menu'])) {
    $menu_id = intval($_GET['eliminar_menu']);
    $pdo->prepare("DELETE FROM menus WHERE id = ?")->execute([$menu_id]);
    $pdo->prepare("DELETE FROM permisos_menu_grupo WHERE menu_id = ?")->execute([$menu_id]);
    $_SESSION['success_message'] = 'Menú y sus submenús asociados eliminados correctamente.';
    header("Location: admin_permisos_menu.php?grupo_id=" . ($_GET['grupo_id'] ?? ''));
    exit;
}

// Eliminar Submenú
if (isset($_GET['eliminar_submenu'])) {
    $submenu_id = intval($_GET['eliminar_submenu']);
    $pdo->prepare("DELETE FROM submenus WHERE id = ?")->execute([$submenu_id]);
    $pdo->prepare("DELETE FROM permisos_menu_grupo WHERE submenu_id = ?")->execute([$submenu_id]);
    $_SESSION['success_message'] = 'Submenú eliminado correctamente.';
    header("Location: admin_permisos_menu.php?grupo_id=" . ($_GET['grupo_id'] ?? ''));
    exit;
}

// Actualizar Menú (desde el modal)
if (isset($_POST['actualizar_menu'])) {
    $menu_id = intval($_POST['menu_id']);
    $nombre = trim($_POST['nombre']);
    $url = normalizarUrl($_POST['url']); // MODIFICADO: Se normaliza la URL
    $orden = intval($_POST['orden']);
    $stmt = $pdo->prepare("UPDATE menus SET nombre = ?, url = ?, orden = ? WHERE id = ?");
    $stmt->execute([$nombre, $url, $orden, $menu_id]);
    $_SESSION['success_message'] = 'Menú actualizado correctamente.';
    header("Location: admin_permisos_menu.php?grupo_id=" . ($_POST['grupo_id'] ?? ''));
    exit;
}

// Actualizar Submenú (desde el modal)
if (isset($_POST['actualizar_submenu'])) {
    $submenu_id = intval($_POST['submenu_id']);
    $nombre = trim($_POST['nombre']);
    $url = normalizarUrl($_POST['url']); // MODIFICADO: Se normaliza la URL
    $orden = intval($_POST['orden']);
    $stmt = $pdo->prepare("UPDATE submenus SET nombre = ?, url = ?, orden = ? WHERE id = ?");
    $stmt->execute([$nombre, $url, $orden, $submenu_id]);
    $_SESSION['success_message'] = 'Submenú actualizado correctamente.';
    header("Location: admin_permisos_menu.php?grupo_id=" . ($_POST['grupo_id'] ?? ''));
    exit;
}

// Crear nuevo menú
if (isset($_POST['nuevo_menu']) && !empty($_POST['nombre_menu'])) {
    $nombre = trim($_POST['nombre_menu']);
    $url = normalizarUrl($_POST['url_menu']); // MODIFICADO: Se normaliza la URL
    $orden = intval($_POST['orden_menu']);
    $admin_grupo_id = $_SESSION['user_grupo_id'];

    $stmt = $pdo->prepare("INSERT INTO menus (nombre, url, orden) VALUES (?, ?, ?)");
    $stmt->execute([$nombre, $url, $orden]);

    $menu_id = $pdo->lastInsertId();
    $permiso_stmt = $pdo->prepare("INSERT INTO permisos_menu_grupo (grupo_id, menu_id) VALUES (?, ?)");
    $permiso_stmt->execute([$admin_grupo_id, $menu_id]);
    
    $_SESSION['success_message'] = 'Nuevo menú creado y permiso asignado a tu grupo.';
    header("Location: admin_permisos_menu.php?grupo_id=" . ($_POST['grupo_id'] ?? $admin_grupo_id));
    exit;
}

// Crear nuevo submenú
if (isset($_POST['nuevo_submenu']) && !empty($_POST['nombre_submenu'])) {
    $nombre = trim($_POST['nombre_submenu']);
    $url = normalizarUrl($_POST['url_submenu']); // MODIFICADO: Se normaliza la URL
    $orden = intval($_POST['orden_submenu']);
    $menu_id = intval($_POST['menu_id_submenu']);
    $admin_grupo_id = $_SESSION['user_grupo_id'];

    $stmt = $pdo->prepare("INSERT INTO submenus (menu_id, nombre, url, orden) VALUES (?, ?, ?, ?)");
    $stmt->execute([$menu_id, $nombre, $url, $orden]);

    $submenu_id = $pdo->lastInsertId();
    $permiso_stmt = $pdo->prepare("INSERT INTO permisos_menu_grupo (grupo_id, menu_id, submenu_id) VALUES (?, ?, ?)");
    $permiso_stmt->execute([$admin_grupo_id, $menu_id, $submenu_id]);
    
    $_SESSION['success_message'] = 'Nuevo submenú creado y permiso asignado a tu grupo.';
    header("Location: admin_permisos_menu.php?grupo_id=" . ($_POST['grupo_id'] ?? $admin_grupo_id));
    exit;
}

// --- El resto del código permanece exactamente igual, ya que la lógica de guardado de permisos
// y obtención de datos es correcta y se beneficia de tener URLs consistentes. ---

// --- OBTENCIÓN DE DATOS PARA MOSTRAR ---
// (Esta sección no necesita cambios)
$stmt = $pdo->query("SELECT id, nombre FROM grupos ORDER BY nombre ASC");
$grupos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT * FROM menus ORDER BY orden ASC");
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT * FROM submenus ORDER BY menu_id ASC, orden ASC");
$submenus = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->query("SELECT id, ruta, nombre_personalizado FROM modulos ORDER BY ruta ASC");
$modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$submenus_by_menu = [];
foreach ($submenus as $sm) { $submenus_by_menu[$sm['menu_id']][] = $sm; }
$grupo_id = isset($_GET['grupo_id']) ? intval($_GET['grupo_id']) : ($grupos[0]['id'] ?? 0);
$stmt = $pdo->prepare("SELECT menu_id, submenu_id FROM permisos_menu_grupo WHERE grupo_id = ?");
$stmt->execute([$grupo_id]);
$permisos_actuales = $stmt->fetchAll(PDO::FETCH_ASSOC);
$menus_permitidos = array_column(array_filter($permisos_actuales, fn($p) => is_null($p['submenu_id'])), 'menu_id');
$submenus_permitidos = array_column(array_filter($permisos_actuales, fn($p) => !is_null($p['submenu_id'])), 'submenu_id');


// Lógica para guardar permisos
// (Esta sección no necesita cambios)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_permisos'])) {
    $grupo_id = intval($_POST['grupo_id']);
    $menus_seleccionados = $_POST['menus'] ?? [];
    $submenus_seleccionados = $_POST['submenus'] ?? [];
    
    // 1. Actualizar permisos de VISIBILIDAD del menú
    $pdo->prepare("DELETE FROM permisos_menu_grupo WHERE grupo_id = ?")->execute([$grupo_id]);
    
    $insert_menu = $pdo->prepare("INSERT INTO permisos_menu_grupo (grupo_id, menu_id) VALUES (?, ?)");
    foreach ($menus_seleccionados as $menu_id) { $insert_menu->execute([$grupo_id, $menu_id]); }
    
    $insert_submenu = $pdo->prepare("INSERT INTO permisos_menu_grupo (grupo_id, menu_id, submenu_id) VALUES (?, ?, ?)");
    foreach ($submenus_seleccionados as $submenu_id) {
        $menu_id_padre = null;
        foreach ($submenus as $sm) {
            if ($sm['id'] == $submenu_id) {
                $menu_id_padre = $sm['menu_id'];
                break;
            }
        }
        if ($menu_id_padre) {
            $insert_submenu->execute([$grupo_id, $menu_id_padre, $submenu_id]);
        }
    }

    // 2. Sincronizar con permisos de ACCESO a módulos
    $todas_las_urls_de_menus = [];
    foreach ($menus as $menu) {
        if (!empty($menu['url']) && $menu['url'] !== '#') { $todas_las_urls_de_menus[] = $menu['url']; }
    }
    foreach ($submenus as $sm) {
        if (!empty($sm['url'])) { $todas_las_urls_de_menus[] = $sm['url']; }
    }
    $todas_las_urls_de_menus = array_unique($todas_las_urls_de_menus);

    if (!empty($todas_las_urls_de_menus)) {
        $placeholders = str_repeat('?,', count($todas_las_urls_de_menus) - 1) . '?';
        $params = array_merge([$grupo_id], $todas_las_urls_de_menus);
        $pdo->prepare("DELETE FROM permisos_grupos WHERE grupo_id = ? AND modulo IN ($placeholders)")->execute($params);
    }

    $urls_permitidas = [];
    foreach ($menus as $menu) {
        if (in_array($menu['id'], $menus_seleccionados) && !empty($menu['url']) && $menu['url'] !== '#') {
            $urls_permitidas[] = $menu['url'];
        }
    }
    foreach ($submenus as $sm) {
        if (in_array($sm['id'], $submenus_seleccionados) && !empty($sm['url'])) {
            $urls_permitidas[] = $sm['url'];
        }
    }
    $urls_permitidas = array_unique($urls_permitidas);

    if (!empty($urls_permitidas)) {
        $insert_permiso_grupo = $pdo->prepare("INSERT INTO permisos_grupos (grupo_id, modulo) VALUES (?, ?)");
        foreach ($urls_permitidas as $url) {
            $insert_permiso_grupo->execute([$grupo_id, $url]);
        }
    }
    
    $_SESSION['success_message'] = 'Permisos de menú y de acceso a módulos actualizados correctamente.';
    header("Location: admin_permisos_menu.php?grupo_id=$grupo_id");
    exit;
}

// --- 3. PRESENTACIÓN (HTML) ---
// (El HTML y JavaScript no necesitan cambios)
include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Menús y Permisos</title>
    
    <style>
        #mensaje-sincronizacion { transition: opacity 1s ease-out; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Gestión de Menús y Permisos</h2>
            <a href="admin_permisos_menu.php?sincronizar=1&grupo_id=<?php echo $grupo_id; ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-repeat"></i> Sincronizar Módulos
            </a>
        </div>
        
        <?php if ($mensaje_sincronizacion): ?><div id="mensaje-sincronizacion" class="alert alert-info"><?php echo $mensaje_sincronizacion; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        
        <form method="get" class="mb-4">
            <label for="grupo_id" class="form-label"><b>Selecciona un grupo para gestionar sus permisos:</b></label>
            <select name="grupo_id" id="grupo_id" class="form-select" onchange="this.form.submit()"><?php foreach ($grupos as $g): ?><option value="<?php echo $g['id']; ?>" <?php if ($g['id'] == $grupo_id) echo 'selected'; ?>><?php echo htmlspecialchars($g['nombre']); ?></option><?php endforeach; ?></select>
        </form>

        <form method="post" action="admin_permisos_menu.php?grupo_id=<?php echo $grupo_id; ?>">
            <input type="hidden" name="grupo_id" value="<?php echo $grupo_id; ?>">
            <div class="card shadow-sm mb-5">
                <div class="card-header"><h5>Permisos de Menú para el grupo seleccionado</h5></div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light"><tr><th>Menú</th><th>URL</th><th>Permitir</th><th>Submenús</th><th style="width: 150px;">Acciones</th></tr></thead>
                        <tbody>
                            <?php foreach ($menus as $menu): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($menu['nombre']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($menu['url']); ?></code></td>
                                    <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" name="menus[]" value="<?php echo $menu['id']; ?>" <?php if (in_array($menu['id'], $menus_permitidos)) echo 'checked'; ?>></div></td>
                                    <td>
                                        <?php if (!empty($submenus_by_menu[$menu['id']])): foreach ($submenus_by_menu[$menu['id']] as $sm): ?>
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div class="form-check"><input class="form-check-input" type="checkbox" name="submenus[]" value="<?php echo $sm['id']; ?>" id="sm_<?php echo $sm['id']; ?>" <?php if (in_array($sm['id'], $submenus_permitidos)) echo 'checked'; ?>><label class="form-check-label" for="sm_<?php echo $sm['id']; ?>"><?php echo htmlspecialchars($sm['nombre']); ?> (<code><?php echo htmlspecialchars($sm['url']); ?></code>)</label></div>
                                                <div class="ms-3">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editSubmenuModal" data-id="<?php echo $sm['id']; ?>" data-nombre="<?php echo htmlspecialchars($sm['nombre']); ?>" data-url="<?php echo htmlspecialchars($sm['url']); ?>" data-orden="<?php echo $sm['orden']; ?>"><i class="bi bi-pencil-fill"></i></button>
                                                    <a href="?eliminar_submenu=<?php echo $sm['id']; ?>&grupo_id=<?php echo $grupo_id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Seguro que quieres eliminar este submenú?');"><i class="bi bi-trash-fill"></i></a>
                                                </div>
                                            </div>
                                        <?php endforeach; endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editMenuModal" data-id="<?php echo $menu['id']; ?>" data-nombre="<?php echo htmlspecialchars($menu['nombre']); ?>" data-url="<?php echo htmlspecialchars($menu['url']); ?>" data-orden="<?php echo $menu['orden']; ?>"><i class="bi bi-pencil-fill"></i> Editar</button>
                                        <a href="?eliminar_menu=<?php echo $menu['id']; ?>&grupo_id=<?php echo $grupo_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que quieres eliminar este menú y TODOS sus submenús?');"><i class="bi bi-trash-fill"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer text-end"><button type="submit" name="guardar_permisos" class="btn btn-primary">Guardar Permisos</button></div>
            </div>
        </form>
        
        <hr class="my-5">
        
        <div class="row">
            <div class="col-md-6 mb-4"><div class="card shadow-sm"><div class="card-header"><h4>Crear Nuevo Menú</h4></div><div class="card-body"><form method="post" action="admin_permisos_menu.php?grupo_id=<?php echo $grupo_id; ?>"><input type="hidden" name="grupo_id" value="<?php echo $grupo_id; ?>"><div class="mb-3"><label for="nombre_menu" class="form-label">Nombre del Menú</label><input type="text" id="nombre_menu" name="nombre_menu" class="form-control" required></div><div class="mb-3"><label for="url_menu" class="form-label">URL o Módulo</label><input type="text" id="url_menu" name="url_menu" class="form-control" list="modulos-lista" required></div><div class="mb-3"><label for="orden_menu" class="form-label">Orden</label><input type="number" id="orden_menu" name="orden_menu" class="form-control" value="10" min="1" required></div><button type="submit" name="nuevo_menu" class="btn btn-success w-100">Crear Menú</button></form></div></div></div>
            <div class="col-md-6 mb-4"><div class="card shadow-sm"><div class="card-header"><h4>Crear Nuevo Submenú</h4></div><div class="card-body"><form method="post" action="admin_permisos_menu.php?grupo_id=<?php echo $grupo_id; ?>"><input type="hidden" name="grupo_id" value="<?php echo $grupo_id; ?>"><div class="mb-3"><label for="menu_id_submenu" class="form-label">Asignar a Menú Principal</label><select id="menu_id_submenu" name="menu_id_submenu" class="form-select" required><option value="">Seleccione...</option><?php foreach ($menus as $menu): ?><option value="<?php echo $menu['id']; ?>"><?php echo htmlspecialchars($menu['nombre']); ?></option><?php endforeach; ?></select></div><div class="mb-3"><label for="nombre_submenu" class="form-label">Nombre del Submenú</label><input type="text" id="nombre_submenu" name="nombre_submenu" class="form-control" required></div><div class="mb-3"><label for="url_submenu" class="form-label">URL o Módulo</label><input type="text" id="url_submenu" name="url_submenu" class="form-control" list="modulos-lista" required></div><div class="mb-3"><label for="orden_submenu" class="form-label">Orden</label><input type="number" id="orden_submenu" name="orden_submenu" class="form-control" value="10" min="1" required></div><button type="submit" name="nuevo_submenu" class="btn btn-info w-100">Crear Submenú</button></form></div></div></div>
        </div>
    </div>

    <div class="modal fade" id="editMenuModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Editar Menú Principal</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="post" action="admin_permisos_menu.php"><div class="modal-body"><input type="hidden" name="menu_id" id="edit_menu_id"><input type="hidden" name="grupo_id" value="<?php echo $grupo_id; ?>"><div class="mb-3"><label for="edit_menu_nombre" class="form-label">Nombre</label><input type="text" id="edit_menu_nombre" name="nombre" class="form-control" required></div><div class="mb-3"><label for="edit_menu_url" class="form-label">URL o Módulo</label><input type="text" id="edit_menu_url" name="url" class="form-control" list="modulos-lista" required></div><div class="mb-3"><label for="edit_menu_orden" class="form-label">Orden</label><input type="number" id="edit_menu_orden" name="orden" class="form-control" min="1" required></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" name="actualizar_menu" class="btn btn-primary">Guardar Cambios</button></div></form></div></div></div>
    <div class="modal fade" id="editSubmenuModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Editar Submenú</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><form method="post" action="admin_permisos_menu.php"><div class="modal-body"><input type="hidden" name="submenu_id" id="edit_submenu_id"><input type="hidden" name="grupo_id" value="<?php echo $grupo_id; ?>"><div class="mb-3"><label for="edit_submenu_nombre" class="form-label">Nombre</label><input type="text" id="edit_submenu_nombre" name="nombre" class="form-control" required></div><div class="mb-3"><label for="edit_submenu_url" class="form-label">URL o Módulo</label><input type="text" id="edit_submenu_url" name="url" class="form-control" list="modulos-lista" required></div><div class="mb-3"><label for="edit_submenu_orden" class="form-label">Orden</label><input type="number" id="edit_submenu_orden" name="orden" class="form-control" min="1" required></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" name="actualizar_submenu" class="btn btn-primary">Guardar Cambios</button></div></form></div></div></div>

    <datalist id="modulos-lista"><option value="#"># (Menú sin enlace)</option><?php foreach ($modulos as $modulo): ?><option value="<?php echo htmlspecialchars($modulo['ruta']); ?>"><?php echo htmlspecialchars($modulo['nombre_personalizado'] ?: $modulo['ruta']); ?></option><?php endforeach; ?></datalist>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editMenuModal = document.getElementById('editMenuModal');
    if (editMenuModal) {
        editMenuModal.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            this.querySelector('#edit_menu_id').value = btn.getAttribute('data-id');
            this.querySelector('#edit_menu_nombre').value = btn.getAttribute('data-nombre');
            this.querySelector('#edit_menu_url').value = btn.getAttribute('data-url');
            this.querySelector('#edit_menu_orden').value = btn.getAttribute('data-orden');
        });
    }

    const editSubmenuModal = document.getElementById('editSubmenuModal');
    if (editSubmenuModal) {
        editSubmenuModal.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            this.querySelector('#edit_submenu_id').value = btn.getAttribute('data-id');
            this.querySelector('#edit_submenu_nombre').value = btn.getAttribute('data-nombre');
            this.querySelector('#edit_submenu_url').value = btn.getAttribute('data-url');
            this.querySelector('#edit_submenu_orden').value = btn.getAttribute('data-orden');
        });
    }

    const mensajeDiv = document.getElementById('mensaje-sincronizacion');
    if (mensajeDiv) {
        setTimeout(() => {
            mensajeDiv.style.opacity = '0';
            setTimeout(() => { mensajeDiv.style.display = 'none'; }, 1000);
        }, 5000);
    }
});
</script>

</body>
</html>