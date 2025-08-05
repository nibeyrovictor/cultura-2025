<?php
require_once 'session_init.php';
require_once 'db.php';
require_once 'auth.php';

// Protect the page for admin access only
if (!isLoggedIn() || !isAdmin()) {
    header("Location: acceso_denegado.php");
    exit;
}

$success = '';
$error = '';

// Get the year from the form or the current year by default
$ano_seleccionado = isset($_POST['ano_certificado']) ? intval($_POST['ano_certificado']) : date('Y');
$nombre_tabla = "cert_349-" . $ano_seleccionado;

// If the form to create the table has been submitted
if (isset($_POST['crear_tabla_certificado'])) {
    try {
        // Use backticks for the table name in case it contains special characters
        $sql = "CREATE TABLE IF NOT EXISTS `$nombre_tabla` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `fecha_cert_venc` DATE NULL DEFAULT NULL,
            `id_expediente` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_general_ci',
            `estado` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $pdo->exec($sql);
        $success = "The table <strong>" . htmlspecialchars($nombre_tabla) . "</strong> has been created successfully (or already existed).";
    } catch (PDOException $e) {
        $error = "Error creating table: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Create Certificate Table</title>
    <?php include 'includes/header.php'; ?>
</head>

<body>
    <div class="container mt-5">
        <h2>Create Certificate Table</h2>
        <p>Select the year for which you want to create the certificate table.</p>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label for="ano_certificado" class="form-label">Certificate Year:</label>
                <input type="number" class="form-control" id="ano_certificado" name="ano_certificado" value="<?php echo htmlspecialchars($ano_seleccionado); ?>" min="1900" max="2100" required>
            </div>
            <button type="submit" name="crear_tabla_certificado" class="btn btn-primary">Create Table for Selected Year</button>
        </form>
    </div>
</body>
</html>