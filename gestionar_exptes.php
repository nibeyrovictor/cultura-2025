<?php
// En la parte superior de tu script PHP, solo para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session_init.php';
require 'db.php';
require_once 'auth.php';

// Verificación de seguridad: solo los administradores pueden acceder a esta página
if (!isLoggedIn() || !isAdmin()) {
    header("Location: acceso_denegado.php");
    exit;
}

$error = '';
$success_message = '';
$tablas = [];
$tabla_seleccionada = '';
$año_seleccionado = $_GET['año'] ?? ''; // This is for selecting the main table view

// Handle messages from redirects (if any)
if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

/**
 * Creates a multi-byte safe version of ucfirst().
 * @param string $string The input string.
 * @param string $encoding The encoding.
 * @return string String with the first character capitalized.
 */
function mb_ucfirst($string, $encoding = 'UTF-8')
{
    $firstChar = mb_substr($string, 0, 1, $encoding);
    $then = mb_substr($string, 1, null, $encoding);
    return mb_strtoupper($firstChar, $encoding) . $then;
}

// Function to create the related 'expediente_proveedor_monto-YYYY' table if it doesn't exist
function createExpedienteProveedorMontoTable($pdo, $year)
{
    $tableName = "expediente_proveedor_monto-" . $year;
    $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `id_expediente` VARCHAR(255) NOT NULL,
        `nombre_proveedor` VARCHAR(255) NOT NULL,
        `tipo_periodo` VARCHAR(20) DEFAULT 'mes',
        `mes` VARCHAR(7) NULL, 
        `fecha_exacta` DATE NULL,
        `periodo_desde` DATE NULL,
        `periodo_hasta` DATE NULL,
        `monto` DECIMAL(15, 2) NOT NULL,
        INDEX (`id_expediente`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        error_log("Error creating table $tableName: " . $e->getMessage());
    }
}

// Función para crear tabla de relaciones
function createRelExpteTable($pdo, $year)
{
    $tableName = "rel_expte-" . $year;
    $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `id_expediente` VARCHAR(255) NOT NULL,
        `id_expediente_rel` VARCHAR(255) NOT NULL,
        `observaciones` TEXT NULL,
        UNIQUE KEY `relacion_unica` (`id_expediente`, `id_expediente_rel`),
        INDEX (`id_expediente`),
        INDEX (`id_expediente_rel`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        error_log("Error creating table $tableName: " . $e->getMessage());
    }
}

// --- LÓGICA DE PROCESAMIENTO DE ACCIONES (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'agregar':
                $tabla = $_POST['tabla'] ?? '';
                $current_year_for_redirect = $_POST['current_year'] ?? '';
                $columns_to_insert = [];
                $values_to_insert = [];

                if (empty($tabla) || !preg_match('/^exptes-(\d{4})$/', $tabla, $matches)) {
                    $error = '❌ Error: Nombre de tabla inválido.';
                    break;
                }
                $year_of_expediente = $matches[1];

                createExpedienteProveedorMontoTable($pdo, $year_of_expediente);

                $stmt_check_cols = $pdo->query("DESCRIBE `{$tabla}`");
                $table_columns_meta = $stmt_check_cols->fetchAll(PDO::FETCH_ASSOC);
                $table_column_names = array_column($table_columns_meta, 'Field');

                foreach ($_POST as $key => $value) {
                    if ($key !== 'action' && $key !== 'tabla' && $key !== 'current_year' && in_array($key, $table_column_names)) {
                        $columns_to_insert[] = "`" . $key . "`";
                        $values_to_insert[] = $value;
                    }
                }

                if (in_array('usuario', $table_column_names) && isset($_SESSION['user_id']) && !in_array('`usuario`', $columns_to_insert)) {
                    $columns_to_insert[] = "`usuario`";
                    $values_to_insert[] = $_SESSION['user_id'];
                }
                if (in_array('fecha_creacion', $table_column_names) && !in_array('`fecha_creacion`', $columns_to_insert)) {
                    $columns_to_insert[] = "`fecha_creacion`";
                    $values_to_insert[] = date('Y-m-d H:i:s');
                }

                if (!empty($columns_to_insert)) {
                    $pdo->beginTransaction();

                    $placeholders = implode(', ', array_fill(0, count($columns_to_insert), '?'));
                    $column_names_sql = implode(', ', $columns_to_insert);

                    $sql = "INSERT INTO `{$tabla}` ({$column_names_sql}) VALUES ({$placeholders})";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($values_to_insert);

                    $new_expediente_id = $pdo->lastInsertId();

                    $expediente_proveedor_monto_data = [];
                    if (isset($_POST['proveedor_nombre']) && is_array($_POST['proveedor_nombre'])) {
                        foreach ($_POST['proveedor_nombre'] as $index => $nombre) {
                            if (!empty($nombre) && isset($_POST['proveedor_mes'][$index]) && isset($_POST['proveedor_monto'][$index])) {
                                $expediente_proveedor_monto_data[] = [
                                    'nombre_proveedor' => trim($nombre),
                                    'mes' => trim($_POST['proveedor_mes'][$index]),
                                    'monto' => floatval(str_replace(',', '.', trim($_POST['proveedor_monto'][$index])))
                                ];
                            }
                        }
                    }

                    if (!empty($expediente_proveedor_monto_data)) {
                        $related_table_name = "expediente_proveedor_monto-" . $year_of_expediente;
                        $insert_sql_related = "INSERT INTO `$related_table_name` (id_expediente, nombre_proveedor, mes, monto) VALUES (?, ?, ?, ?)";
                        $stmt_insert_related = $pdo->prepare($insert_sql_related);
                        foreach ($expediente_proveedor_monto_data as $row) {
                            $stmt_insert_related->execute([
                                $new_expediente_id,
                                $row['nombre_proveedor'],
                                $row['mes'],
                                $row['monto']
                            ]);
                        }
                    }

                    $pdo->commit();
                    header("Location: gestionar_exptes.php?año=" . urlencode($current_year_for_redirect) . "&success=" . urlencode('✅ Registro y proveedores/montos agregados exitosamente.'));
                    exit();
                } else {
                    $error = '❌ Error: No se encontraron datos para agregar.';
                }
                break;

            case 'relacionar_expte':
                $id_expediente_origen = $_POST['id_expediente_origen'] ?? null;
                $id_expediente_relacionado = $_POST['id_expediente_relacionado'] ?? null;
                $observaciones = $_POST['observaciones'] ?? '';
                $current_year_for_redirect = $_POST['current_year'] ?? '';

                if (!$id_expediente_origen || !$id_expediente_relacionado || !$current_year_for_redirect) {
                    $error = '❌ Error: Faltan datos para crear la relación.';
                    break;
                }

                $partes_rel = explode('-', $id_expediente_relacionado);
                $año_rel = isset($partes_rel[2]) && is_numeric($partes_rel[2]) ? $partes_rel[2] : null;

                if (!$año_rel) {
                    $error = '❌ Error: No se pudo determinar el año del expediente a relacionar.';
                    break;
                }

                $tabla_relacion = 'rel_expte-' . $current_year_for_redirect;
                createRelExpteTable($pdo, $current_year_for_redirect);

                $sql = "INSERT INTO `{$tabla_relacion}` (id_expediente, id_expediente_rel, observaciones) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id_expediente_origen, $id_expediente_relacionado, $observaciones]);

                header("Location: gestionar_exptes.php?año=" . urlencode($current_year_for_redirect) . "&success=" . urlencode('✅ Expediente relacionado exitosamente.'));
                exit();

            case 'borrar_relacion':
                $relacion_id = $_POST['relacion_id'] ?? null;
                $current_year_for_redirect = $_POST['current_year'] ?? '';
                $tabla_relacion_year = $_POST['tabla_relacion_year'] ?? '';

                if (!$relacion_id || !$current_year_for_redirect || !$tabla_relacion_year) {
                    header("Location: gestionar_exptes.php?año=" . urlencode($current_year_for_redirect) . "&error=" . urlencode('❌ Error: Faltan datos para eliminar la relación.'));
                    exit();
                }

                $tabla_relacion = 'rel_expte-' . $tabla_relacion_year;

                $sql = "DELETE FROM `{$tabla_relacion}` WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$relacion_id]);

                header("Location: gestionar_exptes.php?año=" . urlencode($current_year_for_redirect) . "&success=" . urlencode('✅ Relación entre expedientes eliminada exitosamente.'));
                exit();

            case 'borrar':
                $tabla_a_borrar = $_POST['tabla_a_borrar'] ?? null;
                $id_a_borrar = $_POST['id_a_borrar'] ?? null;
                $current_year_for_redirect = $_POST['current_year'] ?? '';

                if ($tabla_a_borrar && $id_a_borrar && preg_match('/^exptes-(\d{4})$/', $tabla_a_borrar, $matches)) {
                    $year_of_expediente = $matches[1];
                    $pdo->beginTransaction();

                    $stmt_get_id_expediente = $pdo->prepare("SELECT id_expediente FROM `{$tabla_a_borrar}` WHERE id = ?");
                    $stmt_get_id_expediente->execute([$id_a_borrar]);
                    $id_expediente_to_delete = $stmt_get_id_expediente->fetchColumn();

                    if ($id_expediente_to_delete) {
                        $related_table_name = "expediente_proveedor_monto-" . $year_of_expediente;
                        $check_table_stmt = $pdo->query("SHOW TABLES LIKE '{$related_table_name}'");
                        if ($check_table_stmt->rowCount() > 0) {
                            $stmt_delete_related = $pdo->prepare("DELETE FROM `$related_table_name` WHERE id_expediente = ?");
                            $stmt_delete_related->execute([$id_expediente_to_delete]);
                        }

                        foreach($tablas as $año => $nombre_tabla_rel) {
                           $rel_table_name = "rel_expte-" . $año;
                           $check_rel_table_stmt = $pdo->query("SHOW TABLES LIKE '{$rel_table_name}'");
                            if ($check_rel_table_stmt->rowCount() > 0) {
                                $stmt_delete_rel_all = $pdo->prepare("DELETE FROM `{$rel_table_name}` WHERE id_expediente = ? OR id_expediente_rel = ?");
                                $stmt_delete_rel_all->execute([$id_expediente_to_delete, $id_expediente_to_delete]);
                            }
                        }
                    }

                    $sql = "DELETE FROM `{$tabla_a_borrar}` WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$id_a_borrar]);

                    $pdo->commit();
                    header("Location: gestionar_exptes.php?año=" . urlencode($current_year_for_redirect) . "&success=" . urlencode('✅ Registro y sus datos asociados eliminados exitosamente.'));
                    exit();
                } else {
                    $error = '❌ Error: No se proporcionaron datos válidos para eliminar el registro.';
                }
                break;
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = '❌ Error de base de datos: ' . $e->getMessage();
    }
}

// Buscar todas las tablas "exptes-AAAA"
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'exptes-%'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        if (preg_match('/^exptes-(\d{4})$/', $row[0], $match)) {
            $tablas[$match[1]] = $row[0];
        }
    }
    krsort($tablas); // Sort by year in descending order

} catch (PDOException $e) {
    $error = "Error al buscar tablas: " . $e->getMessage();
}

// 🔽🔽🔽 INICIA MODIFICACIÓN #1: Cargar los alias de las columnas 🔽🔽🔽
$aliases_guardados = [];
try {
    // Consultamos la tabla de alias y la guardamos en un array asociativo.
    $stmt_aliases = $pdo->query("SELECT nombre_columna, alias_columna FROM `columnas_alias_global`");
    // El resultado será como: ['nombre_columna_db' => 'Mi Alias Guardado']
    $aliases_guardados = $stmt_aliases->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    // Es buena práctica registrar el error, pero no es necesario detener la página si falla.
    error_log("ADVERTENCIA: No se pudieron cargar los alias de columnas. " . $e->getMessage());
}
// 🔼🔼🔼 TERMINA MODIFICACIÓN #1 🔼🔼🔼

// If no year selected, default to the latest year found
if (empty($año_seleccionado) && !empty($tablas)) {
    $año_seleccionado = array_key_first($tablas);
}

// If a year was selected (or defaulted to), get the table name
if ($año_seleccionado && isset($tablas[$año_seleccionado])) {
    $tabla_seleccionada = $tablas[$año_seleccionado];
    // Ensure the related tables for the selected year exist
    createExpedienteProveedorMontoTable($pdo, $año_seleccionado);
    createRelExpteTable($pdo, $año_seleccionado); 
}

// Fetch providers for the dropdown
$proveedores_list = [];
try {
    $stmt_proveedores = $pdo->query("SELECT nombre_proveedor FROM `proveedores` ORDER BY nombre_proveedor ASC");
    $proveedores_list = $stmt_proveedores->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error al cargar la lista de proveedores: " . $e->getMessage());
}

// OBTENER ORGANISMOS
$organismos_list = [];
try {
    $stmt_organismos = $pdo->query("SELECT org_num, org_nombre FROM `organismos` ORDER BY org_nombre ASC");
    $organismos_list = $stmt_organismos->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    error_log("Error al cargar la lista de organismos: " . $e->getMessage());
}

// Pagination calculations
$itemsPerPage = 200;
$totalItemsQuery = 0;
if (!empty($tabla_seleccionada)) {
    try {
        $totalItemsQuery = $pdo->query("SELECT COUNT(*) FROM `{$tabla_seleccionada}`")->fetchColumn();
    } catch (PDOException $e) {
        $totalItemsQuery = 0;
    }
}

$totalPages = ceil($totalItemsQuery / $itemsPerPage);
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($current_page < 1)
    $current_page = 1;
if ($current_page > $totalPages && $totalPages > 0)
    $current_page = $totalPages;
if ($totalPages === 0)
    $current_page = 1;

$start = ($current_page - 1) * $itemsPerPage;

// Fetch data for the selected table
$res = null;
$columnas = [];
$columnas_omitidas = ['id', 'fecha_creacion', 'usuario', 'id_expediente'];

if (!empty($tabla_seleccionada)) {
    try {
        $res = $pdo->prepare("SELECT * FROM `{$tabla_seleccionada}` ORDER BY id DESC LIMIT :start, :itemsPerPage");
        $res->bindParam(':start', $start, PDO::PARAM_INT);
        $res->bindParam(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
        $res->execute();

        for ($i = 0; $i < $res->columnCount(); $i++) {
            $meta = $res->getColumnMeta($i);
            $columnas[] = $meta['name'];
        }
    } catch (PDOException $e) {
        $error = '<div class="alert alert-danger mt-3">Error al leer la tabla <strong>' . htmlspecialchars($tabla_seleccionada) . '</strong>: ' . htmlspecialchars($e->getMessage()) . '</div>';
        $res = null;
    }
}

include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Gestión de Expedientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .small-select {
            max-width: 150px;
        }
    </style>
</head>

<body>
    <!-- con este div ocupa todo el ancho de pantalla -->
    <div class="container-fluid py-5">
        <h1 class="mb-4">Gestión de Expedientes <small class="text-muted">(solo admin)</small></h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (empty($tablas)): ?>
            <div class="alert alert-warning">No se encontraron tablas de expedientes con formato
                <strong>exptes-año</strong>.
            </div>
        <?php else: ?>
            <form method="get" class="mb-4">
                <label for="año" class="form-label">Seleccionar año:</label>
                <div class="input-group" style="max-width: 300px;">
                    <select name="año" id="año" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Elegir año --</option>
                        <?php foreach ($tablas as $año => $nombre_tabla): ?>
                            <option value="<?= $año ?>" <?= ($año == $año_seleccionado) ? 'selected' : '' ?>><?= $año ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>

            <?php if ($tabla_seleccionada): ?>
                <section>
                    <h2 class="h4 text-primary"><?= htmlspecialchars($tabla_seleccionada) ?></h2>

                    <div class="mb-3">
                        <button class="btn btn-outline-success" type="button" data-bs-toggle="collapse"
                            data-bs-target="#formulario-<?= $año_seleccionado ?>">
                            + Agregar nuevo registro
                        </button>
                    </div>

                    <div class="collapse mb-4" id="formulario-<?= $año_seleccionado ?>">
                        <div class="card card-body border border-success">
                            <form id="form-expte" action="gestionar_exptes.php" method="post">
                                <input type="hidden" name="action" value="agregar">
                                <input type="hidden" name="tabla" value="<?= htmlspecialchars($tabla_seleccionada) ?>">
                                <input type="hidden" name="current_year" value="<?= htmlspecialchars($año_seleccionado) ?>">
                                <div class="row g-3">
                                    <?php
                                    if (!empty($columnas)) {
                                        foreach ($columnas as $columna):
                                            if (in_array(strtolower($columna), $columnas_omitidas))
                                                continue;
                                            ?>
                                            <div class="col-md-4">
                                                
                                                <?php
                                                // Buscar el alias para la columna actual. Si no existe o está vacío, usar el nombre por defecto.
                                                $nombre_mostrado = !empty(trim($aliases_guardados[$columna] ?? '')) 
                                                    ? $aliases_guardados[$columna] 
                                                    : mb_ucfirst(str_replace('_', ' ', $columna));
                                                ?>
                                                <label for="<?= htmlspecialchars($columna) ?>" class="form-label"><?= htmlspecialchars($nombre_mostrado) ?></label>
                                                <?php
                                                if (strtolower($columna) === 'año'):
                                                    ?>
                                                    <input type="text" name="<?= htmlspecialchars($columna) ?>"
                                                        id="<?= htmlspecialchars($columna) ?>" class="form-control"
                                                        value="<?= htmlspecialchars($año_seleccionado) ?>" readonly>

                                                <?php elseif (strtolower($columna) === 'proveedor'): ?>
                                                    <select name="<?= htmlspecialchars($columna) ?>" id="<?= htmlspecialchars($columna) ?>"
                                                        class="form-select">
                                                        <option value="">-- Seleccionar proveedor --</option>
                                                        <?php foreach ($proveedores_list as $proveedor_name): ?>
                                                            <option value="<?= htmlspecialchars($proveedor_name) ?>">
                                                                <?= htmlspecialchars($proveedor_name) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php elseif (strtolower($columna) === 'organismo'): ?>
                                                    <select name="<?= htmlspecialchars($columna) ?>" id="<?= htmlspecialchars($columna) ?>" class="form-select" required>
                                                        <option value="">-- Seleccionar organismo --</option>
                                                        <?php foreach ($organismos_list as $org_num => $org_nombre): ?>
                                                            <option value="<?= htmlspecialchars($org_num) ?>">
                                                                <?= htmlspecialchars($org_num . ' - ' . $org_nombre) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                <?php elseif (strtolower($columna) === 'fecha'): ?>
                                                    <input type="date" name="<?= htmlspecialchars($columna) ?>"
                                                        id="<?= htmlspecialchars($columna) ?>" class="form-control">
                                                <?php else: ?>
                                                    <input type="text" name="<?= htmlspecialchars($columna) ?>"
                                                        id="<?= htmlspecialchars($columna) ?>" class="form-control">
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach;
                                    } else {
                                        echo '<div class="col-12 text-muted">No se pudieron cargar las columnas de la tabla.</div>';
                                    }
                                    ?>
                                </div>
                                <h4 class="mt-4 mb-3">Proveedores y Montos por Mes para este Expediente</h4>
                                <div id="newProveedorMontosContainer">
                                </div>
                                <button type="button" id="addProveedorMontoNew" class="btn btn-sm btn-outline-info mb-4">➕
                                    Agregar Proveedor/Monto</button>

                                <div class="mt-3 d-flex gap-2">
                                    <button type="submit" class="btn btn-success">Agregar registro</button>
                                    <button type="button" class="btn btn-secondary"
                                        onclick="document.getElementById('form-expte').reset(); ocultarFormulario('formulario-<?= $año_seleccionado ?>')">Limpiar
                                        y Cerrar</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <?php
                                    if (!empty($columnas)) {
                                        foreach ($columnas as $columna) {
                                            if (!in_array(strtolower($columna), $columnas_omitidas)) {
                                                // 🔽🔽🔽 INICIA MODIFICACIÓN #2.2: Usar el alias para el ENCABEZADO DE LA TABLA (<th>) 🔽🔽🔽
                                                // Buscar el alias. Si no existe o está vacío, formatear el nombre original.
                                                $nombre_encabezado = !empty(trim($aliases_guardados[$columna] ?? '')) 
                                                    ? $aliases_guardados[$columna] 
                                                    : mb_ucfirst(str_replace('_', ' ', $columna));
                                                echo '<th>' . htmlspecialchars($nombre_encabezado) . '</th>';
                                                // 🔼🔼🔼 TERMINA MODIFICACIÓN #2.2 🔼🔼🔼
                                            }
                                        }
                                    }
                                    ?>
                                    <th>Proveedores/Montos por mes</th>
                                    <th>Total</th>
                                    <th>Exp. Relacionados</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($res && $res->rowCount() > 0): ?>
                                    <?php while ($fila = $res->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <?php foreach ($columnas as $columna): ?>
                                                <?php if (in_array(strtolower($columna), $columnas_omitidas)) continue; ?>
                                                <td>
                                                    <?php
                                                    $value = $fila[$columna] ?? null;
                                                    switch (strtolower($columna)) {
                                                        case 'organismo':
                                                            $nombre_organismo = $organismos_list[$value] ?? null;
                                                            if ($nombre_organismo) {
                                                                echo htmlspecialchars($value . ' - ' . $nombre_organismo);
                                                            } else {
                                                                echo htmlspecialchars('ORG#' . $value); 
                                                            }
                                                            break;
                                                        case 'fecha':
                                                            if (!empty($value)) {
                                                                try {
                                                                    $date = new DateTime($value);
                                                                    echo $date->format('d-m-Y');
                                                                } catch (Exception $e) {
                                                                    echo htmlspecialchars(strtoupper($value));
                                                                }
                                                            } else {
                                                                echo '';
                                                            }
                                                            break;
                                                        case 'estado':
                                                            $status = strtoupper($value);
                                                            $badge_class = 'text-bg-secondary';
                                                            if ($status == 'APROBADO') $badge_class = 'text-bg-success';
                                                            elseif ($status == 'PENDIENTE') $badge_class = 'text-bg-warning';
                                                            elseif ($status == 'RECHAZADO') $badge_class = 'text-bg-danger';
                                                            echo '<span class="badge ' . $badge_class . '">' . htmlspecialchars($status) . '</span>';
                                                            break;
                                                        case 'monto':
                                                        case 'importe':
                                                            if (is_numeric($value)) {
                                                                echo '$&nbsp;' . number_format($value, 2, ',', '.');
                                                            } else {
                                                                echo htmlspecialchars(strtoupper($value));
                                                            }
                                                            break;
                                                        case 'archivo_adjunto':
                                                            if (!empty($value)) {
                                                                $file_path = 'uploads/' . htmlspecialchars($value);
                                                                echo '<a href="' . $file_path . '" target="_blank" class="btn btn-sm btn-outline-primary">Ver Archivo</a>';
                                                            } else {
                                                                echo '<span class="text-muted">N/A</span>';
                                                            }
                                                            break;
                                                        default:
                                                            echo ($value !== null) ? htmlspecialchars(strtoupper((string) $value)) : '';
                                                            break;
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td>
                                                <?php
                                                $id_expediente_val = $fila['id_expediente'] ?? null;
                                                if ($id_expediente_val) {
                                                    try {
                                                        $tabla_relacion_display = "expediente_proveedor_monto-" . $año_seleccionado;
                                                        $stmt_provs = $pdo->prepare("SELECT nombre_proveedor, mes, monto, tipo_periodo, fecha_exacta, periodo_desde, periodo_hasta FROM `$tabla_relacion_display` WHERE id_expediente = ? ORDER BY nombre_proveedor,mes DESC, nombre_proveedor ASC");
                                                        $stmt_provs->execute([$id_expediente_val]);
                                                        $provs = $stmt_provs->fetchAll(PDO::FETCH_ASSOC);

                                                        if ($provs && count($provs) > 0) {
                                                            echo '<ul class="mb-0 small">';
                                                            foreach ($provs as $prov) {
                                                                $output = '<li><strong>' . htmlspecialchars($prov['nombre_proveedor'] ?? '') . '</strong>: $' . number_format($prov['monto'] ?? 0, 2, ',', '.');
                                                                $periodo_texto = '';
                                                                if (isset($prov['tipo_periodo']) && !empty($prov['tipo_periodo'])) {
                                                                    switch ($prov['tipo_periodo']) {
                                                                        case 'fecha':
                                                                            if (isset($prov['fecha_exacta']) && !empty($prov['fecha_exacta'])) {
                                                                                $timestamp = strtotime($prov['fecha_exacta']);
                                                                                $periodo_texto = $timestamp !== false ? 'Fecha: ' . date('d-m-Y', $timestamp) : 'Fecha inválida';
                                                                            }
                                                                            break;
                                                                        case 'periodo':
                                                                            if (isset($prov['periodo_desde'], $prov['periodo_hasta']) && !empty($prov['periodo_desde']) && !empty($prov['periodo_hasta'])) {
                                                                                $ts_desde = strtotime($prov['periodo_desde']);
                                                                                $ts_hasta = strtotime($prov['periodo_hasta']);
                                                                                $desde = $ts_desde !== false ? date('d-m-Y', $ts_desde) : 'inválida';
                                                                                $hasta = $ts_hasta !== false ? date('d-m-Y', $ts_hasta) : 'inválida';
                                                                                $periodo_texto = 'Desde: ' . $desde . ' Hasta: ' . $hasta;
                                                                            }
                                                                            break;
                                                                        case 'mes':
                                                                        default: 
                                                                            if (isset($prov['mes']) && !empty($prov['mes'])) {
                                                                                $meses = ['01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];
                                                                                $mes_num = $prov['mes'];
                                                                                $año_parte = substr($mes_num, 0, 4);
                                                                                $mes_parte = substr($mes_num, -2);
                                                                                $mes_nombre = $meses[$mes_parte] ?? $mes_parte;
                                                                                $periodo_texto = $año_parte . '-' . $mes_nombre;
                                                                            }
                                                                            break;
                                                                    }
                                                                } else {
                                                                    if (isset($prov['mes']) && !empty($prov['mes'])) {
                                                                        $meses = ['01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo', '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio', '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre', '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre'];
                                                                        $mes_num = $prov['mes'];
                                                                        $mes_nombre = $meses[substr($mes_num, -2)] ?? substr($mes_num, -2);
                                                                        if (strlen($mes_num) > 2) $periodo_texto = substr($mes_num, 0, 4) . '-' . $mes_nombre;
                                                                        else $periodo_texto = $mes_nombre;
                                                                    }
                                                                }

                                                                if (!empty($periodo_texto)) {
                                                                    $output .= '<br><small class="text-muted">(' . htmlspecialchars($periodo_texto) . ')</small>';
                                                                }
                                                                $output .= '</li>';
                                                                echo $output;
                                                            }
                                                            echo '</ul>';
                                                        }
                                                    } catch (PDOException $e) {
                                                        echo '<span class="text-danger small">Error al cargar proveedores.</span>';
                                                        error_log("Error al cargar proveedores para id_expediente {$id_expediente_val}: " . $e->getMessage());
                                                    }
                                                } else {
                                                    echo '<span class="text-muted small">N/A</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if ($id_expediente_val) {
                                                    try {
                                                        $tabla_relacion_display = "expediente_proveedor_monto-" . $año_seleccionado;
                                                        $stmt_sum = $pdo->prepare("SELECT SUM(monto) as total FROM `$tabla_relacion_display` WHERE id_expediente = ?");
                                                        $stmt_sum->execute([$id_expediente_val]);
                                                        $row_sum = $stmt_sum->fetch(PDO::FETCH_ASSOC);
                                                        if ($row_sum && $row_sum['total'] !== null) {
                                                            echo "$" . number_format($row_sum['total'], 2, ',', '.');
                                                        } else {
                                                            echo '<span class="text-muted">-</span>';
                                                        }
                                                    } catch (PDOException $e) {
                                                        echo '<span class="text-danger">Error</span>';
                                                    }
                                                } else {
                                                    echo '<span class="text-muted">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary mb-2" data-bs-toggle="modal" data-bs-target="#relacionarExpteModal" data-id-origen="<?= htmlspecialchars($fila['id_expediente']) ?>">
                                                    🔗 Relacionar
                                                </button>
                                                <?php
                                                $id_expediente_actual = $fila['id_expediente'] ?? null;
                                                if ($id_expediente_actual) {
                                                    $relaciones = [];
                                                    foreach (array_keys($tablas) as $año_tabla_rel) {
                                                        $tabla_relacion_display = "rel_expte-" . $año_tabla_rel;
                                                         $check_rel_table_stmt = $pdo->query("SHOW TABLES LIKE '{$tabla_relacion_display}'");
                                                        if ($check_rel_table_stmt->rowCount() > 0) {
                                                             $sql_rel = "(SELECT id, id_expediente_rel AS relacionado_id, observaciones, 'origen' AS tipo, '{$año_tabla_rel}' as tabla_año FROM `{$tabla_relacion_display}` WHERE id_expediente = :id_expediente1)
                                                                         UNION
                                                                         (SELECT id, id_expediente AS relacionado_id, observaciones, 'relacionado' AS tipo, '{$año_tabla_rel}' as tabla_año FROM `{$tabla_relacion_display}` WHERE id_expediente_rel = :id_expediente2)";
                                                            $stmt_rel = $pdo->prepare($sql_rel);
                                                            $stmt_rel->execute([':id_expediente1' => $id_expediente_actual, ':id_expediente2' => $id_expediente_actual]);
                                                            $relaciones = array_merge($relaciones, $stmt_rel->fetchAll(PDO::FETCH_ASSOC));
                                                        }
                                                    }

                                                    if ($relaciones) {
                                                        echo '<ul class="list-unstyled mb-0 small">';
                                                        foreach ($relaciones as $rel) {
                                                            $id_relacionado = $rel['relacionado_id'];
                                                            $observaciones_rel = $rel['observaciones'];
                                                            $tipo_rel = $rel['tipo'];
                                                            $relacion_id = $rel['id'];
                                                            $tabla_relacion_year = $rel['tabla_año'];

                                                            $partes = explode('-', $id_relacionado);
                                                            $año_relacionado = isset($partes[2]) && is_numeric($partes[2]) ? $partes[2] : null;

                                                            echo '<li><div class="d-flex justify-content-between align-items-center">';
                                                            
                                                            $direccion_flecha = ($tipo_rel === 'origen') ? '→' : '←';

                                                            if ($año_relacionado) {
                                                                $link = 'gestionar_exptes.php?año=' . urlencode($año_relacionado) . '#expte-' . urlencode($id_relacionado);
                                                                echo '<a href="' . $link . '" class="badge text-bg-info text-decoration-none" title="Ir al expediente ' . htmlspecialchars($id_relacionado) . '"> ' . $direccion_flecha . ' ' . htmlspecialchars($id_relacionado) . '</a>';
                                                            } else {
                                                                echo '<span class="badge text-bg-secondary">' . $direccion_flecha . ' ' . htmlspecialchars($id_relacionado) . '</span>';
                                                            }
                                                            
                                                            echo '<button type="button" class="btn btn-sm btn-outline-danger p-0 ms-1" style="line-height: 1; width: 1.2rem; height: 1.2rem;" data-bs-toggle="modal"
                                                                    data-bs-target="#deleteRelacionModal"
                                                                    data-relacion-id="' . htmlspecialchars($relacion_id) . '"
                                                                    data-tabla-relacion-year="' . htmlspecialchars($tabla_relacion_year) . '"
                                                                    data-origen-display="' . htmlspecialchars($id_expediente_actual) . '"
                                                                    data-relacionado-display="' . htmlspecialchars($id_relacionado) . '"
                                                                    data-current-year="' . htmlspecialchars($año_seleccionado) . '">
                                                                    &times;
                                                                  </button>';

                                                            echo '</div>'; 
                                                            if (!empty($observaciones_rel)) {
                                                                echo '<small class="text-muted d-block" style="padding-left: 15px;">' . htmlspecialchars($observaciones_rel) . '</small>';
                                                            }
                                                            echo '</li>';
                                                        }
                                                        echo '</ul>';
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <a class="btn btn-sm btn-outline-primary"
                                                    href="editar.php?tabla=<?= urlencode($tabla_seleccionada) ?>&id=<?= htmlspecialchars($fila['id']) ?>&año=<?= htmlspecialchars($año_seleccionado) ?>">Editar</a>

                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                                    data-bs-target="#deleteConfirmModal"
                                                    data-record-id="<?= htmlspecialchars($fila['id']) ?>"
                                                    data-table-name="<?= htmlspecialchars($tabla_seleccionada) ?>"
                                                    data-current-year="<?= htmlspecialchars($año_seleccionado) ?>">
                                                    Borrar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php elseif ($res && $res->rowCount() === 0): ?>
                                    <tr>
                                        <td colspan="<?= count($columnas) - count($columnas_omitidas) + 4 ?>"
                                            class="text-center text-muted">No hay registros en esta tabla.</td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?= count($columnas) - count($columnas_omitidas) + 4 ?>"
                                            class="text-center text-muted">No se pudieron cargar los datos. Por favor, intente
                                            seleccionar un año diferente.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mt-4">
                                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?= $current_page == $i ? 'active' : '' ?>">
                                        <a class="page-link"
                                            href="?año=<?= htmlspecialchars($año_seleccionado) ?>&page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </section>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que quieres borrar este registro (ID: <span id="modalRecordId"></span>) de la tabla
                    <span id="modalTableName"></span>? Esta acción también eliminará todas sus relaciones y datos asociados. Esta acción no se puede deshacer.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Borrar</button>
                </div>
            </div>
        </div>
    </div>
    <form id="confirmDeleteForm" action="gestionar_exptes.php" method="POST" style="display: none;">
        <input type="hidden" name="action" value="borrar">
        <input type="hidden" name="tabla_a_borrar" value="">
        <input type="hidden" name="id_a_borrar" value="">
        <input type="hidden" name="current_year" value="">
    </form>

    <div class="modal fade" id="deleteRelacionModal" tabindex="-1" aria-labelledby="deleteRelacionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteRelacionModalLabel">Confirmar Eliminación de Relación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar la relación entre:</p>
                    <p><span id="modalRelacionOrigenId"></span></p>
                    <p>y</p>
                    <p><span id="modalRelacionRelacionadoId"></span>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteRelacionBtn">Sí, Eliminar Relación</button>
                </div>
            </div>
        </div>
    </div>
    <form id="confirmDeleteRelacionForm" action="gestionar_exptes.php" method="POST" style="display: none;">
        <input type="hidden" name="action" value="borrar_relacion">
        <input type="hidden" name="relacion_id" value="">
        <input type="hidden" name="tabla_relacion_year" value="">
        <input type="hidden" name="current_year" value="">
    </form>

    <div class="modal fade" id="relacionarExpteModal" tabindex="-1" aria-labelledby="relacionarExpteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="relacionarExpteForm" action="gestionar_exptes.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="relacionarExpteModalLabel">Relacionar Expediente</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="relacionar_expte">
                        <input type="hidden" id="id_expediente_origen_input" name="id_expediente_origen" value="">
                        <input type="hidden" name="current_year" value="<?= htmlspecialchars($año_seleccionado) ?>">
                        <p>Relacionando desde: <strong id="id_origen_display"></strong></p>
                        <div class="mb-3">
                            <label for="relacionar_año_select" class="form-label">Año del Expediente a Relacionar</label>
                            <select id="relacionar_año_select" class="form-select">
                                <option value="">-- Seleccione un año --</option>
                                <?php foreach ($tablas as $año => $nombre_tabla): ?>
                                    <option value="<?= $año ?>"><?= $año ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="id_expediente_relacionado_select" class="form-label">Expediente a Relacionar</label>
                            <select id="id_expediente_relacionado_select" name="id_expediente_relacionado" class="form-select" required>
                                <option value="">-- Primero seleccione un año --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="observaciones_relacion" class="form-label">Observaciones</label>
                            <textarea id="observaciones_relacion" name="observaciones" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Relación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        function ocultarFormulario(id) {
            const el = document.getElementById(id);
            const collapse = bootstrap.Collapse.getInstance(el) || new bootstrap.Collapse(el, { toggle: false });
            collapse.hide();
            document.getElementById('form-expte').reset();
            document.getElementById('newProveedorMontosContainer').innerHTML = '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const deleteConfirmModal = document.getElementById('deleteConfirmModal');
            if (deleteConfirmModal) {
                deleteConfirmModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const recordId = button.getAttribute('data-record-id');
                    const tableName = button.getAttribute('data-table-name');
                    const currentYear = button.getAttribute('data-current-year');
                    
                    const modalRecordId = deleteConfirmModal.querySelector('#modalRecordId');
                    const modalTableName = deleteConfirmModal.querySelector('#modalTableName');
                    modalRecordId.textContent = recordId;
                    modalTableName.textContent = tableName;

                    const confirmDeleteForm = document.getElementById('confirmDeleteForm');
                    confirmDeleteForm.querySelector('[name="id_a_borrar"]').value = recordId;
                    confirmDeleteForm.querySelector('[name="tabla_a_borrar"]').value = tableName;
                    confirmDeleteForm.querySelector('[name="current_year"]').value = currentYear;
                });

                document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
                    document.getElementById('confirmDeleteForm').submit();
                });
            }

            const newProveedorMontosContainer = document.getElementById('newProveedorMontosContainer');
            const addProveedorMontoNewButton = document.getElementById('addProveedorMontoNew');
            const proveedoresList = <?= json_encode($proveedores_list) ?>;

            let newRowIndex = 0;

            function createNewProveedorMontoRow() {
                const rowDiv = document.createElement('div');
                rowDiv.classList.add('row', 'g-3', 'mb-2', 'align-items-end', 'proveedor-monto-new-row');
                rowDiv.innerHTML = `
                    <div class="col-md-4"><label class="form-label">Proveedor</label><select name="proveedor_nombre[]" class="form-select"><option value="">-- Seleccionar --</option>${proveedoresList.map(p => `<option value="${p}">${p}</option>`).join('')}</select></div>
                    <div class="col-md-3"><label class="form-label">Mes (YYYY-MM)</label><input type="month" name="proveedor_mes[]" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Monto</label><input type="number" step="0.01" name="proveedor_monto[]" class="form-control"></div>
                    <div class="col-md-2 d-flex align-items-end"><button type="button" class="btn btn-outline-danger remove-new-prov-monto-row">🗑️</button></div>`;
                newProveedorMontosContainer.appendChild(rowDiv);
                rowDiv.querySelector('.remove-new-prov-monto-row').addEventListener('click', () => rowDiv.remove());
            }

            if(addProveedorMontoNewButton) {
                addProveedorMontoNewButton.addEventListener('click', createNewProveedorMontoRow);
            }
            
            const deleteRelacionModal = document.getElementById('deleteRelacionModal');
            if (deleteRelacionModal) {
                 deleteRelacionModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const relacionId = button.getAttribute('data-relacion-id');
                    const tablaRelacionYear = button.getAttribute('data-tabla-relacion-year');
                    const currentYear = button.getAttribute('data-current-year');
                    const origenDisplay = button.getAttribute('data-origen-display');
                    const relacionadoDisplay = button.getAttribute('data-relacionado-display');

                    deleteRelacionModal.querySelector('#modalRelacionOrigenId').textContent = origenDisplay;
                    deleteRelacionModal.querySelector('#modalRelacionRelacionadoId').textContent = relacionadoDisplay;

                    const form = document.getElementById('confirmDeleteRelacionForm');
                    form.querySelector('[name="relacion_id"]').value = relacionId;
                    form.querySelector('[name="tabla_relacion_year"]').value = tablaRelacionYear;
                    form.querySelector('[name="current_year"]').value = currentYear;
                });
                
                document.getElementById('confirmDeleteRelacionBtn').addEventListener('click', function() {
                    document.getElementById('confirmDeleteRelacionForm').submit();
                });
            }

            const relacionarModal = document.getElementById('relacionarExpteModal');
            if (relacionarModal) {
                const idOrigenInput = document.getElementById('id_expediente_origen_input');
                const idOrigenDisplay = document.getElementById('id_origen_display');
                const añoSelect = document.getElementById('relacionar_año_select');
                const expteSelect = document.getElementById('id_expediente_relacionado_select');

                relacionarModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const idOrigen = button.getAttribute('data-id-origen');
                    
                    idOrigenInput.value = idOrigen;
                    idOrigenDisplay.textContent = idOrigen;
                    document.getElementById('relacionarExpteForm').reset();
                    añoSelect.value = '';
                    expteSelect.innerHTML = '<option value="">-- Primero seleccione un año --</option>';
                });

                añoSelect.addEventListener('change', function() {
                    const selectedYear = this.value;
                    expteSelect.innerHTML = '<option value="">Cargando...</option>';

                    if (!selectedYear) {
                        expteSelect.innerHTML = '<option value="">-- Primero seleccione un año --</option>';
                        return;
                    }

                    fetch(`ajax_get_expedientes.php?año=${selectedYear}`)
                        .then(response => response.json())
                        .then(data => {
                            expteSelect.innerHTML = '';
                            if (data.error) {
                                expteSelect.innerHTML = `<option value="">${data.error}</option>`;
                            } else if (data.length === 0) {
                                expteSelect.innerHTML = '<option value="">No hay expedientes en este año</option>';
                            } else {
                                expteSelect.innerHTML = '<option value="">-- Seleccione un expediente --</option>';
                                const idOrigen = idOrigenInput.value;
                                data.forEach(expedienteId => {
                                    if (expedienteId !== idOrigen) {
                                        const option = document.createElement('option');
                                        option.value = expedienteId;
                                        option.textContent = expedienteId;
                                        expteSelect.appendChild(option);
                                    }
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error en AJAX:', error);
                            expteSelect.innerHTML = '<option value="">Error al cargar datos</option>';
                        });
                });
            }
        });
    </script>
</body>
</html>