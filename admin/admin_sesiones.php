<?php
// Solo accesible para administradores
require_once '../session_init.php';
require_once '../db.php';
require_once '../auth.php';
include '../includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../index.php");
    exit;
}

// Bloquear usuario
if (isset($_GET['bloquear']) && is_numeric($_GET['bloquear'])) {
    $uid = intval($_GET['bloquear']);
    $pdo->prepare("UPDATE usuarios SET rol = 'bloqueado' WHERE id = ?")->execute([$uid]);
    echo '<div class="alert alert-warning">Usuario bloqueado.</div>';
}

// Cerrar sesión (eliminar registro de la tabla sesiones)
if (isset($_GET['cerrar']) && !empty($_GET['cerrar'])) {
    $sid = $_GET['cerrar'];
    $pdo->prepare("DELETE FROM sesiones WHERE id = ?")->execute([$sid]);
    echo '<div class="alert alert-success">Sesión cerrada.</div>';
}

// Listar sesiones desde la base de datos
$sesiones = [];
// Asegúrate de que la columna 'timestamp' de 'sesiones' se selecciona.
// La unión (JOIN) parece correcta si 'sessions.id' se relaciona con 'sesiones.id'
// y 'sessions' es la tabla que contiene la columna 'expires'.
$stmt = $pdo->query("
    SELECT
        s.id,
        s.data,
        s.timestamp, -- Asegúrate de seleccionar el timestamp de 'sesiones'
        e.expires AS expires_timestamp_value
    FROM
        sesiones s
    JOIN
        sessions e ON s.id = e.id
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sid = $row['id'];
    $data = $row['data'];
    
    // Formatear la marca de tiempo de expiración
    $expires = isset($row['expires_timestamp_value']) ? date('d-m-Y H:i:s', $row['expires_timestamp_value']) : 'N/A';
    
    // Obtener y formatear la marca de tiempo de inicio desde el resultado de la consulta
    $raw_timestamp_inicio = $row['timestamp'] ?? null; // Usar null coalescing para seguridad
    $inicio = ($raw_timestamp_inicio !== null) ? date('d-m-Y H:i:s', $raw_timestamp_inicio) : 'N/A';
    
    $user_id = $user_usuario = $nombre = $ip = '';
    // Extraer otros datos de la sesión serializada
    if (preg_match('/user_id\|i:(\d+)/', $data, $m)) $user_id = $m[1];
    if (preg_match('/user_usuario\|s:\d+:"([^"]+)"/', $data, $m)) $user_usuario = $m[1];
    if (preg_match('/nombre_completo\|s:\d+:"([^"]+)"/', $data, $m)) $nombre = $m[1];
    if (preg_match('/ip\|s:\d+:"([^"]+)"/', $data, $m)) $ip = $m[1];
    // No necesitamos extraer 'inicio' de $data si ya lo tenemos de $row['timestamp']
    
    if ($user_id) {
        $sesiones[] = [
            'sid' => $sid,
            'user_id' => $user_id,
            'user_usuario' => $user_usuario,
            'nombre' => $nombre,
            'ip' => $ip,
            'inicio' => $inicio, // Usar el valor formateado
            'expires' => $expires
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Administrar Sesiones</title>
</head>
<body>
    <div class="container mt-5">
        <h2>Sesiones Activas</h2>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID Usuario</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>IP</th>
                    <th>Inicio</th>
                    <th>Expiración</th>
                    <th>ID Sesión</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sesiones as $s): ?>
                <tr>
                    <td><?php echo htmlspecialchars($s['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($s['user_usuario']); ?></td>
                    <td><?php echo htmlspecialchars($s['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($s['ip']); ?></td>
                    <td><?php echo htmlspecialchars($s['inicio']); ?></td> <td><?php echo htmlspecialchars($s['expires']); ?></td>
                    <td><?php echo htmlspecialchars($s['sid']); ?></td>
                    <td>
                        <a href="?cerrar=<?php echo urlencode($s['sid']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Cerrar esta sesión?')">Cerrar sesión</a>
                        <a href="?bloquear=<?php echo urlencode($s['user_id']); ?>" class="btn btn-warning btn-sm ms-2" onclick="return confirm('¿Bloquear este usuario?')">Bloquear usuario</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($sesiones)): ?>
            <div class="alert alert-info">No hay sesiones activas.</div>
        <?php endif; ?>
    </div>
</body>
</html>