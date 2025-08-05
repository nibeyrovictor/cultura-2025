<?php
// --- 1. INICIALIZACIÓN Y SEGURIDAD ---
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
$sync_output = '';

// Revisar si hay un mensaje flash en la sesión y limpiarlo
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// --- 2. LÓGICA DE PROCESAMIENTO ---

// A. Procesar la actualización de un nombre personalizado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_modulo'])) {
    $modulo_id = $_POST['modulo_id'] ?? '';
    // Permitir guardar un nombre vacío para limpiarlo
    $nombre_personalizado = trim($_POST['nombre_personalizado'] ?? ''); 

    if (!empty($modulo_id)) {
        try {
            $stmt = $pdo->prepare("UPDATE modulos SET nombre_personalizado = :nombre WHERE id = :id");
            $stmt->execute(['nombre' => $nombre_personalizado, 'id' => $modulo_id]);
            
            $_SESSION['success_message'] = "Nombre para '$modulo_id' actualizado correctamente.";
            header("Location: gestionar_modulos.php"); // Redirigir para evitar reenvío de formulario
            exit;

        } catch (PDOException $e) {
            $error = "Error al actualizar el módulo: " . $e->getMessage();
        }
    }
}

// B. Procesar la solicitud de sincronización
if (isset($_GET['sincronizar'])) {
    ob_start(); // Inicia el buffer de salida para capturar el echo de 'poblar_modulos.php'
    include 'poblar_modulos.php';
    $sync_output = ob_get_clean(); // Captura la salida y la limpia
    $success = "Sincronización de módulos ejecutada.";
}


// --- 3. OBTENER DATOS PARA MOSTRAR ---
$stmt = $pdo->query("SELECT id, ruta, nombre_personalizado FROM modulos ORDER BY ruta ASC");
$modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Incluir el encabezado HTML y el menú dinámico
include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h2 class="mb-0 h4"><i class="bi bi-box-seam-fill"></i> Gestor de Módulos del Sistema</h2>
            <a href="gestionar_modulos.php?sincronizar=1" class="btn btn-light" 
               onclick="return confirm('¿Estás seguro de que quieres sincronizar los módulos?\nEsto añadirá archivos nuevos y eliminará los obsoletos de la lista.');">
                <i class="bi bi-arrow-repeat"></i> Sincronizar Módulos
            </a>
        </div>
        <div class="card-body">

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($sync_output): ?>
                <div class="alert alert-info">
                    <h5 class="alert-heading">Resultado de la Sincronización:</h5>
                    <pre class="mb-0" style="white-space: pre-wrap;"><?php echo htmlspecialchars(strip_tags($sync_output)); ?></pre>
                </div>
            <?php endif; ?>

            <p class="text-muted">Aquí puedes asignar un nombre amigable a cada módulo del sistema. Este nombre se podrá usar en el futuro para mostrar los permisos de una forma más clara.</p>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Ruta del Módulo (ID)</th>
                            <th>Nombre Personalizado (Editable)</th>
                            <th style="width: 120px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($modulos)): ?>
                            <tr>
                                <td colspan="3" class="text-center">No hay módulos registrados. Por favor, haz clic en "Sincronizar Módulos".</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($modulos as $modulo): ?>
                                <tr>
                                    <td>
                                        <code><?php echo htmlspecialchars($modulo['ruta']); ?></code>
                                    </td>
                                    <form method="POST" action="gestionar_modulos.php">
                                        <td>
                                            <input type="hidden" name="modulo_id" value="<?php echo htmlspecialchars($modulo['id']); ?>">
                                            <input type="text" class="form-control" name="nombre_personalizado" 
                                                   value="<?php echo htmlspecialchars($modulo['nombre_personalizado'] ?? ''); ?>"
                                                   placeholder="(Sin nombre personalizado)">
                                        </td>
                                        <td>
                                            <button type="submit" name="guardar_modulo" class="btn btn-primary btn-sm w-100">
                                                <i class="bi bi-save-fill"></i> Guardar
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir scripts de JS si es necesario
// Ejemplo: <script src="..."></script>
?>
</body>
</html>