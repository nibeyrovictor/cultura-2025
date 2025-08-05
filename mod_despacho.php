<?php
// Incluir archivos necesarios
require 'db.php';
require 'auth.php';

// Verificar si el usuario está autenticado
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Obtener el grupo del usuario actual
$grupo_usuario = isLoggedIn() && isset($_SESSION['user_grupo']) ? $_SESSION['user_grupo'] : null;

// Filtrar submenús según los permisos del usuario
$submenus_filtrados = [];
foreach ($submenus as $submenu) {
    if ($grupo_usuario && tienePermiso($submenu['url'], $pdo)) {
        $submenus_filtrados[] = $submenu;
    }
}

// Verificar si el usuario tiene permiso para acceder a este módulo
$modulo_actual = 'mod_despacho.php';
if (!tienePermiso($modulo_actual, $pdo)) {
    die("Acceso denegado. No tienes permiso para acceder a este módulo.");
}

// Contenido del módulo
require 'includes/header.php';
?>

<div class="container-fluid mt-4">
    <h1>Módulo de Despacho</h1>
    <p>Bienvenido al módulo exclusivo para el grupo Despacho.</p>
</div>

</body>
</html>