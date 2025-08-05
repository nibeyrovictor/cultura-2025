<?php
//session_start();

function login($usuario, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT usuarios.*, grupos.id AS grupo_id, grupos.nombre AS grupo_nombre 
                           FROM usuarios 
                           JOIN grupos ON usuarios.grupo_id = grupos.id 
                           WHERE usuarios.usuario = :usuario");
    $stmt->execute(['usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_usuario'] = $user['usuario'];
        $_SESSION['user_rol'] = $user['rol'];
        $_SESSION['user_grupo_id'] = $user['grupo_id']; // Guardar el ID del grupo
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['genero'] = $user['genero'];
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['inicio'] = date('Y-m-d H:i:s');
        return true;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin';
}

function logout() {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Función para verificar permisos de acceso a un módulo
function tienePermiso($modulo, $pdo) {
    if (!isLoggedIn()) {
        return false; // El usuario no está autenticado
    }

    $grupo_id = $_SESSION['user_grupo_id'];

    // Consultar si el grupo tiene acceso al módulo
    $stmt = $pdo->prepare("SELECT COUNT(*) AS count FROM permisos_grupos WHERE grupo_id = :grupo_id AND modulo = :modulo");
    $stmt->execute(['grupo_id' => $grupo_id, 'modulo' => $modulo]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['count'] > 0; // Retorna true si tiene permiso
}
?>