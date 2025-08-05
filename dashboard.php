<?php
// Incluir archivos necesarios
require_once 'session_init.php';
require 'db.php';
require_once 'auth.php';
include 'includes/header.php';


if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$archivo_actual = basename(__FILE__);
if (!tienePermiso($archivo_actual, $pdo)) {
    header("Location: acceso_denegado.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    
    <title>Dashboard</title>
    
</head>
<body>
    <?php require_once 'menu_dinamico.php'; ?>
    <div class="container-fluid mt-5">
        <?php
        $genero = $_SESSION['genero'] ?? '';
        if ($genero === 'Masculino') {
            $saludo = 'Bienvenido';
        } elseif ($genero === 'Femenino') {
            $saludo = 'Bienvenida';
        } elseif ($genero === 'con @') {
            $saludo = 'Bienvenid@';
        } else {
            $saludo = 'Bienvenid@s';
        }
        ?>
        <h1><?php echo $saludo; ?>, <?php echo htmlspecialchars($_SESSION['nombre_completo']); ?>!</h1>
        <p>Tu rol es: <strong><?php echo htmlspecialchars($_SESSION['user_rol']); ?></strong></p>

        <?php if (isAdmin()): ?>
            <div class="alert alert-success">
                Tienes acceso de administrador.
            </div>
            <a href="admin_sesiones.php" class="btn btn-outline-primary mt-3">Administrar sesiones</a>
            <a href="editor_permisos.php" class="btn btn-outline-secondary mt-3">Editor de permisos</a>
            <a href="admin_permisos_menu.php" class="btn btn-outline-info mt-3">Permisos de menú</a>
            <a href="admin_grupos.php" class="btn btn-outline-dark mt-3">Administrar grupos</a>
            <a href="crear_tabla_expedientes.php" class="btn btn-outline-dark mt-3">Crear tabla Expedientes</a>
            <a href="editar_proveedores.php" class="btn btn-outline-dark mt-3">Proveedores</a>
            <a href="gestionar_exptes.php" class="btn btn-outline-dark mt-3">Tabla de expedientes</a>
            <a href="./includes/favicon/favicon_editor.php" class="btn btn-outline-dark mt-3">Favicons</a>
            <a href="crear_tabla_certificados.php" class="btn btn-outline-dark mt-3">cREACIÓN DE TABLA DE CERTIFICADOS 349</a>
            <a href="gestionar_certificados.php" class="btn btn-outline-dark mt-3">TABLA DE CERTIFICADOS 349</a>
            <a href="ocr_tesseract_windows.php" class="btn btn-outline-dark mt-3">Reconocimiento de texto</a>

        <?php else: ?>
            <div class="alert alert-info">
                Tienes acceso de usuario normal.
            </div>
        <?php endif; ?>
    </div>

</body>
</html>