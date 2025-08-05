<?php
require_once 'session_init.php';
require_once 'db.php';
require_once 'auth.php';

// Protect the page for admin access only
if (!isLoggedIn() || !isAdmin()) {
    header("Location: acceso_denegado.php");
    exit;
}

$ano_seleccionado = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
$nombre_tabla_cert = "cert_349-" . $ano_seleccionado;
$nombre_tabla_exptes = "exptes-" . $ano_seleccionado; // Assuming 'exptes-YEAR' table for linking

$error = '';
$success = '';
$edit_data = null;

// --- Handle Form Submissions (Add/Edit) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_edit_certificado'])) {
        $fecha_cert_venc = $_POST['fecha_cert_venc'];
        $id_expediente = $_POST['id_expediente'];
        $estado = $_POST['estado'];
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id) {
            // Edit existing record
            $sql = "UPDATE `$nombre_tabla_cert` SET `fecha_cert_venc` = :fecha_cert_venc, `id_expediente` = :id_expediente, `estado` = :estado WHERE `id` = :id";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([':fecha_cert_venc' => $fecha_cert_venc, ':id_expediente' => $id_expediente, ':estado' => $estado, ':id' => $id])) {
                $success = "Certificate updated successfully!";
            } else {
                $error = "Error updating certificate.";
            }
        } else {
            // Add new record
            $sql = "INSERT INTO `$nombre_tabla_cert` (`fecha_cert_venc`, `id_expediente`, `estado`) VALUES (:fecha_cert_venc, :id_expediente, :estado)";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([':fecha_cert_venc' => $fecha_cert_venc, ':id_expediente' => $id_expediente, ':estado' => $estado])) {
                $success = "Certificate added successfully!";
            } else {
                $error = "Error adding certificate.";
            }
        }
    } elseif (isset($_POST['delete_certificado'])) {
        $id_to_delete = intval($_POST['id_to_delete']);
        $sql = "DELETE FROM `$nombre_tabla_cert` WHERE `id` = :id";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute([':id' => $id_to_delete])) {
            $success = "Certificate deleted successfully!";
        } else {
            $error = "Error deleting certificate.";
        }
    }
}

// --- Fetch data for editing ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_to_edit = intval($_GET['id']);
    $sql = "SELECT * FROM `$nombre_tabla_cert` WHERE `id` = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_to_edit]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$edit_data) {
        $error = "Certificate not found.";
    }
}

// --- Fetch existing certificates for the selected year, joining with expedientes for display ---
$certificados = [];
try {
    // Check if the 'exptes-YEAR' table exists before joining
    $table_exptes_exists = $pdo->query("SHOW TABLES LIKE '$nombre_tabla_exptes'")->fetch();

    if ($table_exptes_exists) {
        $sql = "SELECT c.*, e.proveedor, e.caratula
                FROM `$nombre_tabla_cert` c
                LEFT JOIN `$nombre_tabla_exptes` e ON c.id_expediente = e.id_expediente
                ORDER BY c.fecha_cert_venc DESC";
        $stmt = $pdo->query($sql);
        $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // If expedientes table doesn't exist, just fetch cert data
        $stmt = $pdo->query("SELECT * FROM `$nombre_tabla_cert` ORDER BY `fecha_cert_venc` DESC");
        $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $error .= " The expediente table " . htmlspecialchars($nombre_tabla_exptes) . " does not exist. Expediente details (Proveedor, Caratula) cannot be displayed.";
    }
} catch (PDOException $e) {
    $error = "Error loading certificates: " . $e->getMessage() . ". Ensure the table " . htmlspecialchars($nombre_tabla_cert) . " exists for this year.";
}

// --- Fetch id_expediente, proveedor, and caratula from the 'exptes-YEAR' table for dropdown ---
$expedientes_for_dropdown = [];
try {
    // Check if the 'exptes-YEAR' table exists before querying it
    $table_exists_check = $pdo->query("SHOW TABLES LIKE '$nombre_tabla_exptes'")->fetch();
    if ($table_exists_check) {
        $stmt = $pdo->query("SELECT `id_expediente`, `proveedor`, `caratula` FROM `$nombre_tabla_exptes` ORDER BY `id_expediente` ASC");
        $expedientes_for_dropdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error .= " The expediente table " . htmlspecialchars($nombre_tabla_exptes) . " does not exist. Please create it first.";
    }
} catch (PDOException $e) {
    // If there's an error during the query (e.g., table structure issue), handle gracefully
    $error .= " Could not load expediente IDs for dropdown. Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Gestor de certificados</title>
    <?php include 'includes/header.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>

<body>
    <div class="container mt-5">
        <h2>Manejar certificados del: <?php echo htmlspecialchars($ano_seleccionado); ?></h2>
        <p>
            <a href="crear_tabla_certificados.php" class="btn btn-info btn-sm">Crear Tabla de Certificados 349 para otro año</a>
            <form method="GET" class="d-inline-block ms-3">
                <label for="ano_select" class="form-label">Cambiar año:</label>
                <select name="ano" id="ano_select" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                    <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $ano_seleccionado) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </p>

        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <h3><?php echo $edit_data ? 'Editar certificado' : 'Agregar nuevo certificado'; ?></h3>
        <form method="post">
            <?php if ($edit_data): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_data['id']); ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label for="fecha_cert_venc" class="form-label">Fecha de vencimiento:</label>
                <input type="date" class="form-control" id="fecha_cert_venc" name="fecha_cert_venc" value="<?php echo htmlspecialchars($edit_data['fecha_cert_venc'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="id_expediente" class="form-label">Expediente ID:</label>
                <select class="form-control" id="id_expediente" name="id_expediente" required>
                    <option value="">Select Expediente ID</option>
                    <?php foreach ($expedientes_for_dropdown as $exp):
                        $display_text = htmlspecialchars($exp['id_expediente']);
                        if (!empty($exp['proveedor']) || !empty($exp['caratula'])) {
                            $display_text .= ' - ' . htmlspecialchars($exp['proveedor'] ?? '') . ' - ' . htmlspecialchars($exp['caratula'] ?? '');
                        }
                    ?>
                        <option value="<?php echo htmlspecialchars($exp['id_expediente']); ?>" <?php echo ($edit_data['id_expediente'] ?? '') == $exp['id_expediente'] ? 'selected' : ''; ?>>
                            <?php echo $display_text; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="estado" class="form-label">Estado:</label>
                <input type="text" class="form-control" id="estado" name="estado" value="<?php echo htmlspecialchars($edit_data['estado'] ?? ''); ?>">
            </div>
            <button type="submit" name="add_edit_certificado" class="btn btn-success">
                <?php echo $edit_data ? 'Update Certificate' : 'Crear Certificado'; ?>
            </button>
            <?php if ($edit_data): ?>
                <a href="gestionar_certificados.php?ano=<?php echo htmlspecialchars($ano_seleccionado); ?>" class="btn btn-secondary ms-2">Cancelar</a>
            <?php endif; ?>
        </form>

        <hr>

        <h3>Certificados existentes</h3>
        <?php if (empty($certificados)): ?>
            <p>No certificates found for this year. Add one above!</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha de Vencimiento</th>
                            <th>Expediente ID</th>
                            <th>Estado</th>
                            <th>Creado el</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificados as $cert):
                            $display_expediente = htmlspecialchars($cert['id_expediente']);
                            // Check if proveedor and caratula exist from the JOIN
                            if (isset($cert['proveedor']) || isset($cert['caratula'])) {
                                $display_expediente .= ' - ' . htmlspecialchars($cert['proveedor'] ?? '') . ' - ' . htmlspecialchars($cert['caratula'] ?? '');
                            }
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cert['id']); ?></td>
                                <td><?php echo date('d-m-y', strtotime($cert['fecha_cert_venc'])); ?></td>
                                <td><?php echo $display_expediente; ?></td>
                                <td><?php echo htmlspecialchars($cert['estado']); ?></td>
                                <td><?php echo date('d-m-y H:i:s', strtotime($cert['fecha_creacion'])); ?></td>
                                <td>
                                    <a href="gestionar_certificados.php?ano=<?php echo htmlspecialchars($ano_seleccionado); ?>&action=edit&id=<?php echo htmlspecialchars($cert['id']); ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <form method="post" class="d-inline-block" onsubmit="return confirm('Are you sure you want to delete this certificate?');">
                                        <input type="hidden" name="id_to_delete" value="<?php echo htmlspecialchars($cert['id']); ?>">
                                        <button type="submit" name="delete_certificado" class="btn btn-danger btn-sm">Borrar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#id_expediente').select2();
        });
    </script>
</body>

</html>