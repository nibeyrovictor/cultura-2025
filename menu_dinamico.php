<?php
// Incluir archivos necesarios
require_once './db.php';
require_once './auth.php';

// Verificar si el usuario está autenticado
if (!isLoggedIn()) {
    // Menú para usuarios no autenticados
    echo '<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">';
    echo '  <div class="container-fluid">';
    echo '    <a class="navbar-brand" href="index.php">';
    echo '      <i class="fas fa-home me-2"></i> Sistema de Login';
    echo '    </a>';
    echo '    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">';
    echo '      <span class="navbar-toggler-icon"></span>';
    echo '    </button>';
    echo '    <div class="collapse navbar-collapse" id="navbarNav">';
    echo '      <ul class="navbar-nav ms-auto">';
    echo '        <li class="nav-item">';
    echo '          <a class="btn btn-light" href="login.php">Iniciar sesión</a>';
    echo '        </li>';
    echo '      </ul>';
    echo '    </div>';
    echo '  </div>';
    echo '</nav>';
    return;
}

// Obtener el grupo del usuario actual
$grupo_usuario = isset($_SESSION['user_grupo_id']) ? $_SESSION['user_grupo_id'] : null;

if ($grupo_usuario) {
    $stmt = $pdo->prepare("SELECT nombre FROM grupos WHERE id = :id");
    $stmt->execute(['id' => $grupo_usuario]);
    $grupo_nombre = $stmt->fetchColumn();

    if (!$grupo_nombre) {
        echo "Grupo no encontrado.<br>";
        $grupo_nombre = 'Sin grupo'; // Valor predeterminado
    }
} else {
    echo "Grupo del usuario no definido.<br>";
    $grupo_nombre = 'Sin grupo'; // Valor predeterminado
}

// Consultar los menús principales
$stmt = $pdo->query("SELECT * FROM menus ORDER BY orden ASC");
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($menus)) {
    echo "No hay menús disponibles.<br>";
    exit;
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-home me-2"></i> Sistema de Login
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <span id="digital-clock" class="ms-3 text-light fw-bold" style="font-family:monospace;"></span>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($menus as $menu): ?>
                    <?php
                    // Consultar los submenús asociados a este menú
                    $stmt = $pdo->prepare("SELECT * FROM submenus WHERE menu_id = :menu_id ORDER BY orden ASC");
                    $stmt->execute(['menu_id' => $menu['id']]);
                    $submenus = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Filtrar submenús según los permisos del usuario
                    $submenus_filtrados = [];
                    foreach ($submenus as $submenu) {
                        if ($grupo_usuario && tienePermiso($submenu['url'], $pdo)) {
                            $submenus_filtrados[] = $submenu;
                        }
                    }
                    ?>

                    <?php if (empty($submenus_filtrados)): ?>
                        <!-- Menú sin submenús -->
                        <?php if (!$grupo_usuario || tienePermiso($menu['url'], $pdo)): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo htmlspecialchars($menu['url']); ?>">
                                    <?php echo htmlspecialchars($menu['nombre']); ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Menú con submenús -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown<?php echo $menu['id']; ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <?php echo htmlspecialchars($menu['nombre']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end" aria-labelledby="navbarDropdown<?php echo $menu['id']; ?>">
                                <?php foreach ($submenus_filtrados as $submenu): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo htmlspecialchars($submenu['url']); ?>">
                                            <?php echo htmlspecialchars($submenu['nombre']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center ms-auto">
                <span id="digital-clock" class="me-4 text-light fw-bold" style="font-family:monospace;"></span>
                <?php if (isLoggedIn()): ?>
                    <span class="me-3 text-muted">Hola, <?php echo htmlspecialchars($_SESSION['user_usuario']); ?></span>
                    <a href="logout.php" class="btn btn-danger">Cerrar Sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<script>
function updateClock() {
    const now = new Date();
    const h = String(now.getHours()).padStart(2, '0');
    const m = String(now.getMinutes()).padStart(2, '0');
    const s = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('digital-clock').textContent = `${h}:${m}:${s}`;
}
setInterval(updateClock, 1000);
updateClock();
</script>