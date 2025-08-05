<?php
require_once 'session_init.php';
require_once 'db.php';
require_once 'auth.php';

// Protect the page for admin access only
if (!isLoggedIn() || !isAdmin()) {
    header("Location: acceso_denegado.php");
    exit;
}

// --- MODIFIED: Get the selected year from the URL, or default to 'todos' ---
$ano_seleccionado = isset($_GET['ano']) ? $_GET['ano'] : 'todos';

$error = '';
$success = '';
$edit_data = null;
$expiring_certificates_notification = [];

// --- Handle Form Submissions (Add/Edit/Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_edit_certificado'])) {
        // --- MODIFICACIÓN: Limpiar datos de entrada con trim() ---
        $fecha_cert_venc = $_POST['fecha_cert_venc']; // La fecha no necesita trim
        $id_expediente = trim($_POST['id_expediente']);
        $cuit_proveedor = trim($_POST['cuit_proveedor']);
        $estado = trim($_POST['estado']);
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (empty($cuit_proveedor) || empty($id_expediente)) {
            $error = "Error: Debe seleccionar un Proveedor y un Expediente.";
        } else {
            if ($id) {
                // Update existing record: Use the year from the URL, which is guaranteed to be specific
                $nombre_tabla_cert_op = "cert_349-" . intval($ano_seleccionado);
                $sql = "UPDATE `$nombre_tabla_cert_op` SET `fecha_cert_venc` = :fecha_cert_venc, `id_expediente` = :id_expediente, `cuit_proveedor` = :cuit_proveedor, `estado` = :estado WHERE `id` = :id";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([':fecha_cert_venc' => $fecha_cert_venc, ':id_expediente' => $id_expediente, ':cuit_proveedor' => $cuit_proveedor, ':estado' => $estado, ':id' => $id])) {
                    $success = "Certificado actualizado exitosamente.";
                } else {
                    $error = "Error actualizando el certificado.";
                }
            } else {
                // Add new record: Always add to the CURRENT year's table
                $ano_for_insert = date('Y');
                $nombre_tabla_cert_op = "cert_349-" . $ano_for_insert;
                $sql = "INSERT INTO `$nombre_tabla_cert_op` (`fecha_cert_venc`, `id_expediente`, `cuit_proveedor`, `estado`) VALUES (:fecha_cert_venc, :id_expediente, :cuit_proveedor, :estado)";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([':fecha_cert_venc' => $fecha_cert_venc, ':id_expediente' => $id_expediente, ':cuit_proveedor' => $cuit_proveedor, ':estado' => $estado])) {
                    $success = "Certificado agregado exitosamente al año $ano_for_insert.";
                } else {
                    $error = "Error agregando el certificado. Verifique que la tabla '$nombre_tabla_cert_op' exista.";
                }
            }
        }
    } elseif (isset($_POST['delete_certificado'])) {
        // --- MODIFIED: Use the specific year passed from the delete form ---
        $id_to_delete = intval($_POST['id_to_delete']);
        $ano_to_delete_from = intval($_POST['ano_to_delete_from']);

        if ($ano_to_delete_from > 0) {
            $nombre_tabla_cert_del = "cert_349-" . $ano_to_delete_from;
            $sql = "DELETE FROM `$nombre_tabla_cert_del` WHERE `id` = :id";
            $stmt = $pdo->prepare($sql);
            if ($stmt->execute([':id' => $id_to_delete])) {
                $success = "Certificado eliminado exitosamente.";
            } else {
                $error = "Error eliminando el certificado de la tabla $nombre_tabla_cert_del.";
            }
        } else {
            $error = "Error: No se pudo determinar el año del certificado a eliminar.";
        }
    }
}

// --- Fetch data for editing (requires a specific year) ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']) && $ano_seleccionado != 'todos') {
    $id_to_edit = intval($_GET['id']);
    $nombre_tabla_cert = "cert_349-" . intval($ano_seleccionado);
    $sql = "SELECT * FROM `$nombre_tabla_cert` WHERE `id` = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_to_edit]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$edit_data) {
        $error = "Certificado no encontrado.";
    }
}

// --- Fetch data for the main list ---
$certificados = [];
try {
    $sql_parts = [];
    $table_list = [];

    if ($ano_seleccionado == 'todos') {
        // Find all certificate tables
        $stmt_tables = $pdo->query("SHOW TABLES LIKE 'cert_349-%'");
        $cert_tables = $stmt_tables->fetchAll(PDO::FETCH_COLUMN);
        foreach ($cert_tables as $table_name) {
            // Extract year from table name
            if (preg_match('/cert_349-(\d{4})/', $table_name, $matches)) {
                $table_list[] = ['cert_table' => $table_name, 'year' => $matches[1]];
            }
        }
    } else {
        // Use only the selected year's table
        $year = intval($ano_seleccionado);
        $table_list[] = ['cert_table' => "cert_349-$year", 'year' => $year];
    }

    foreach ($table_list as $tables) {
        $cert_table = $tables['cert_table'];
        $exptes_table = "exptes-" . $tables['year'];
        $year = $tables['year'];

        // Check if certificate table exists before adding to union
        $table_cert_exists = $pdo->query("SHOW TABLES LIKE '$cert_table'")->fetch();
        if($table_cert_exists) {
            $table_exptes_exists = $pdo->query("SHOW TABLES LIKE '$exptes_table'")->fetch();

            $join_sql = $table_exptes_exists
                ? "LEFT JOIN `$exptes_table` e ON c.id_expediente = e.id_expediente"
                : "";
            
            // --- MODIFIED: Select the year of the record for action links ---
            $sql_parts[] = "
                SELECT c.*, p.nombre_proveedor, e.caratula, '{$year}' as cert_year
                FROM `$cert_table` c
                LEFT JOIN `proveedores` p ON c.cuit_proveedor = p.cuit
                $join_sql
            ";
        }
    }

    if (!empty($sql_parts)) {
        $sql = implode(" UNION ALL ", $sql_parts);
        $sql .= " ORDER BY fecha_cert_venc DESC";
        $stmt = $pdo->query($sql);
        $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error = "Error cargando certificados: " . $e->getMessage();
}

// --- Fetch data for dropdowns (suppliers and all expedientes) ---
// This part remains unchanged as it already fetches all expedientes correctly.
$expedientes_for_dropdown = [];
try {
    $stmt_tables = $pdo->query("SHOW TABLES LIKE 'exptes-%'");
    $expediente_tables = $stmt_tables->fetchAll(PDO::FETCH_COLUMN);
    foreach ($expediente_tables as $table_name) {
        $stmt_exp = $pdo->query("SELECT `id_expediente`, `caratula` FROM `$table_name`");
        $expedientes_for_dropdown = array_merge($expedientes_for_dropdown, $stmt_exp->fetchAll(PDO::FETCH_ASSOC));
    }
    if (!empty($expedientes_for_dropdown)) {
        usort($expedientes_for_dropdown, fn($a, $b) => strcmp($a['id_expediente'], $b['id_expediente']));
    }
} catch (PDOException $e) { $error .= " No se pudieron cargar los expedientes de todos los años. Error: " . $e->getMessage(); }

$proveedores_for_dropdown = [];
try {
    $stmt = $pdo->query("SELECT `cuit`, `nombre_proveedor` FROM `proveedores` ORDER BY `nombre_proveedor` ASC");
    $proveedores_for_dropdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $error .= " No se pudieron cargar los proveedores."; }

// --- Fetch expiring certificates notifications ---
// The logic for fetching notifications is similar to fetching the main list
try {
    $sql_parts_notif = [];
    // Use the same list of tables generated for the main certificate list
    foreach ($table_list as $tables) {
        $cert_table = $tables['cert_table'];
        $exptes_table = "exptes-" . $tables['year'];
        $year = $tables['year'];

        $table_cert_exists = $pdo->query("SHOW TABLES LIKE '$cert_table'")->fetch();
        if($table_cert_exists) {
            $table_exptes_exists = $pdo->query("SHOW TABLES LIKE '$exptes_table'")->fetch();
            $join_sql = $table_exptes_exists ? "LEFT JOIN `$exptes_table` e ON c.id_expediente = e.id_expediente" : "";
            
            $sql_parts_notif[] = "
                SELECT c.id, c.fecha_cert_venc, c.id_expediente, e.caratula, p.nombre_proveedor, '{$year}' as cert_year
                FROM `$cert_table` c
                LEFT JOIN `proveedores` p ON c.cuit_proveedor = p.cuit
                $join_sql
                WHERE c.fecha_cert_venc >= CURDATE()
                  AND c.fecha_cert_venc <= CURDATE() + INTERVAL 5 DAY
                  AND (c.estado IS NULL OR c.estado = 'vencido')
            ";
        }
    }
    if (!empty($sql_parts_notif)) {
        $sql_notification = implode(" UNION ALL ", $sql_parts_notif);
        $sql_notification .= " ORDER BY fecha_cert_venc ASC";
        $stmt_notification = $pdo->query($sql_notification);
        $expiring_certificates_notification = $stmt_notification->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) { $error .= " Error al verificar certificados por vencer."; }

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
        <h2>Manejar certificados del año: <?php echo htmlspecialchars(ucfirst($ano_seleccionado)); ?></h2>
        
        <p>
            <a href="crear_tabla_expedientes.php" class="btn btn-info btn-sm">Crear/Verificar Tablas Anuales</a>
            <form method="GET" class="d-inline-block ms-3">
                <label for="ano_select" class="form-label">Cambiar año:</label>
                <select name="ano" id="ano_select" class="form-select d-inline-block w-auto" onchange="this.form.submit()">
                    <option value="todos" <?php echo ($ano_seleccionado == 'todos') ? 'selected' : ''; ?>>Todos</option>
                    <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y == $ano_seleccionado) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </p>

        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <?php if (!empty($expiring_certificates_notification)): ?>
            <div class="alert alert-warning mt-3">
                <h4>¡Atención! Certificados próximos a vencer o vencidos (próximos 5 días):</h4>
                <ul>
                    <?php foreach ($expiring_certificates_notification as $exp_cert): ?>
                        <li>
                            <strong>ID Certificado:</strong> <?php echo htmlspecialchars($exp_cert['id']); ?>,
                            <strong>Proveedor:</strong> <?php echo htmlspecialchars($exp_cert['nombre_proveedor'] ?? 'N/A'); ?>,
                            <strong>Expediente:</strong> <?php echo htmlspecialchars($exp_cert['id_expediente']); ?>,
                            <strong>Vence el:</strong> <?php echo date('d-m-Y', strtotime($exp_cert['fecha_cert_venc'])); ?>
                            <a href="gestionar_certificados.php?ano=<?php echo htmlspecialchars($exp_cert['cert_year']); ?>&action=edit&id=<?php echo htmlspecialchars($exp_cert['id']); ?>" class="btn btn-sm btn-outline-warning ms-2">Editar</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h3><?php echo $edit_data ? 'Editar certificado' : 'Agregar nuevo certificado'; ?></h3>
        <p class="text-muted small"><?php if (!$edit_data) { echo 'Nota: Los nuevos certificados se agregan al año actual (' . date('Y') . ').'; } ?></p>
        <form method="post">
            <?php if ($edit_data): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($edit_data['id']); ?>">
            <?php endif; ?>
            
            <div class="mb-3"><label for="fecha_cert_venc" class="form-label">Fecha de vencimiento:</label><input type="date" class="form-control" id="fecha_cert_venc" name="fecha_cert_venc" value="<?php echo htmlspecialchars($edit_data['fecha_cert_venc'] ?? ''); ?>" required></div>
            <div class="mb-3"><label for="cuit_proveedor" class="form-label">Proveedor:</label><select class="form-control" id="cuit_proveedor" name="cuit_proveedor" required><option value="">Seleccionar Proveedor</option><?php foreach ($proveedores_for_dropdown as $prov): ?><option value="<?php echo htmlspecialchars($prov['cuit']); ?>" <?php echo ($edit_data['cuit_proveedor'] ?? '') == $prov['cuit'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($prov['nombre_proveedor']); ?></option><?php endforeach; ?></select></div>
            
            <div class="mb-3"><label for="id_expediente" class="form-label">Expediente:</label><select class="form-control" id="id_expediente" name="id_expediente" required><option value="">Seleccionar Expediente</option><?php foreach ($expedientes_for_dropdown as $exp): ?><option value="<?php echo htmlspecialchars($exp['id_expediente']); ?>" <?php echo trim($edit_data['id_expediente'] ?? '') == trim($exp['id_expediente']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($exp['id_expediente'] . ' - ' . $exp['caratula']); ?></option><?php endforeach; ?></select></div>
            
            <div class="mb-3"><label for="estado" class="form-label">Estado:</label><input type="text" class="form-control" id="estado" name="estado" value="<?php echo htmlspecialchars($edit_data['estado'] ?? ''); ?>"></div>
            
            <button type="submit" name="add_edit_certificado" class="btn btn-success"><?php echo $edit_data ? 'Actualizar Certificado' : 'Crear Certificado'; ?></button>
            <?php if ($edit_data): ?>
                <a href="gestionar_certificados.php?ano=todos" class="btn btn-secondary ms-2">Cancelar</a>
            <?php endif; ?>
        </form>

        <hr>

        <h3>Certificados existentes</h3>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>ID Cert.</th>
                        <th>Año</th>
                        <th>Vencimiento</th>
                        <th>Proveedor</th>
                        <th>Expediente</th>
                        <th>Carátula</th>
                        <th>Estado</th>
                        <th>Creado el</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($certificados as $cert): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cert['id']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($cert['cert_year']); ?></span></td>
                            <td><?php echo date('d-m-Y', strtotime($cert['fecha_cert_venc'])); ?></td>
                            <td><?php echo htmlspecialchars($cert['nombre_proveedor'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($cert['id_expediente']); ?></td>
                            <td><?php echo htmlspecialchars($cert['caratula'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($cert['estado']); ?></td>
                            <td><?php echo date('d-m-Y H:i:s', strtotime($cert['fecha_creacion'])); ?></td>
                            <td>
                                <a href="gestionar_certificados.php?ano=<?php echo htmlspecialchars($cert['cert_year']); ?>&action=edit&id=<?php echo htmlspecialchars($cert['id']); ?>" class="btn btn-warning btn-sm">Editar</a>
                                
                                <form method="post" class="d-inline-block" onsubmit="return confirm('¿Está seguro de que desea eliminar este certificado?');">
                                    <input type="hidden" name="id_to_delete" value="<?php echo htmlspecialchars($cert['id']); ?>">
                                    <input type="hidden" name="ano_to_delete_from" value="<?php echo htmlspecialchars($cert['cert_year']); ?>">
                                    <input type="hidden" name="delete_certificado" value="1">
                                    <button type="submit" class="btn btn-danger btn-sm">Borrar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#id_expediente').select2({ placeholder: "Seleccionar Expediente" });
            $('#cuit_proveedor').select2({ placeholder: "Seleccionar Proveedor" });
        });
    </script>
</body>
</html>