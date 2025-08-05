<?php
// Bienvenida dinámica basada en los archivos del sistema
require_once 'session_init.php';
include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
</head>
<body>

    <div class="container-fluid mt-4">
        <h1>¡Bienvenido al Sistema de Gestión!</h1>
        <p>Este sistema cuenta con las siguientes funcionalidades principales:</p>
        <ul>
            <li>Autenticación de usuarios (login, registro, logout)</li>
            <li>Gestión y edición de usuarios</li>
            <li>Menú dinámico y permisos</li>
            <li>Panel de control (dashboard)</li>
            <li>Gestión de despachos</li>
        </ul>
        <p>Utiliza el menú para navegar por las diferentes secciones.</p>
    </div>
</body>
</html>
