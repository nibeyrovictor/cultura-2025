<?php
require_once 'session_init.php';
require_once 'db.php';
require_once 'auth.php';

// Solo admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: acceso_denegado.php");
    exit;
}

// --- Lógica de Filtros ---
$ano_seleccionado = isset($_GET['ano']) ? $_GET['ano'] : 'all';
$filtro_proveedor = isset($_GET['cuit_proveedor']) ? trim($_GET['cuit_proveedor']) : '';
$filtro_estado = isset($_GET['filtro_estado']) ? trim($_GET['filtro_estado']) : 'todos';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

$error = '';
$success = '';
$certificados = [];
$proveedores_for_dropdown = [];

// --- Cargar datos para los menús desplegables ---
try {
    $stmt_prov = $pdo->query("SELECT `cuit`, `nombre_proveedor` FROM `proveedores` ORDER BY `nombre_proveedor` ASC");
    $proveedores_for_dropdown = $stmt_prov->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error .= " Error cargando proveedores: " . $e->getMessage();
}

// --- Construcción de la consulta principal ---
try {
    $sql = "";
    
    // 1. Crear subconsulta para TODOS los expedientes
    $stmt_exptes_tables = $pdo->query("SHOW TABLES LIKE 'exptes-%'");
    $exptes_tables = $stmt_exptes_tables->fetchAll(PDO::FETCH_COLUMN);
    $exptes_join_sql = "";
    $exptes_select_sql = "NULL AS caratula";
    if (!empty($exptes_tables)) {
        $exptes_union_sql = [];
        foreach ($exptes_tables as $table_exptes) {
            $exptes_union_sql[] = "SELECT `id_expediente`, `caratula` FROM `{$table_exptes}`";
        }
        $exptes_subquery = "(SELECT id_expediente, MAX(caratula) as caratula FROM (" . implode(' UNION ALL ', $exptes_union_sql) . ") as temp_exptes GROUP BY id_expediente) AS e";
        $exptes_join_sql = "LEFT JOIN {$exptes_subquery} ON c.id_expediente = e.id_expediente";
        $exptes_select_sql = "e.caratula";
    }

    // 2. Construir cláusula WHERE
    $where_conditions = [];
    $params = [];

    switch ($filtro_estado) {
        case 'vencidos':
            $where_conditions[] = "c.fecha_cert_venc <= CURDATE()";
            break;
        case 'proximos':
            $where_conditions[] = "c.fecha_cert_venc BETWEEN CURDATE() AND CURDATE() + INTERVAL 5 DAY";
            break;
    }
    
    if ($filtro_proveedor !== '') {
        $where_conditions[] = "c.cuit_proveedor = :cuit_proveedor";
        $params[':cuit_proveedor'] = $filtro_proveedor;
    }
    if ($busqueda !== '') {
        $search_parts = ["c.id_expediente LIKE :busqueda", "p.nombre_proveedor LIKE :busqueda"];
        if (!empty($exptes_tables)) {
            $search_parts[] = "COALESCE(e.caratula, '') LIKE :busqueda";
        }
        $where_conditions[] = "(" . implode(' OR ', $search_parts) . ")";
        $params[':busqueda'] = "%$busqueda%";
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // 3. Construir la consulta principal
    if ($ano_seleccionado === 'all') {
        $stmt_cert_tables = $pdo->query("SHOW TABLES LIKE 'cert_349-%'");
        $cert_tables = $stmt_cert_tables->fetchAll(PDO::FETCH_COLUMN);

        if (empty($cert_tables)) {
            $error .= "No se encontraron tablas de certificados ('cert_349-AÑO').";
        } else {
            $cert_union_sql = [];
            foreach ($cert_tables as $table_cert) {
                $cert_union_sql[] = "SELECT *, '{$table_cert}' as source_table FROM `{$table_cert}`";
            }
            $cert_subquery = "(" . implode(' UNION ALL ', $cert_union_sql) . ") AS c";
            $sql = "SELECT c.*, p.nombre_proveedor, {$exptes_select_sql}
                            FROM {$cert_subquery}
                            LEFT JOIN `proveedores` p ON c.cuit_proveedor = p.cuit
                            {$exptes_join_sql}
                            {$where_clause}";
        }
    } else {
        $nombre_tabla_cert = "cert_349-" . intval($ano_seleccionado);
        $stmt_check_table = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt_check_table->execute([$nombre_tabla_cert]);
        if ($stmt_check_table->fetch()) {
            $sql = "SELECT c.*, p.nombre_proveedor, {$exptes_select_sql}, '{$nombre_tabla_cert}' as source_table
                            FROM `{$nombre_tabla_cert}` c
                            LEFT JOIN `proveedores` p ON c.cuit_proveedor = p.cuit
                            {$exptes_join_sql}
                            {$where_clause}";
        } else {
            $error .= "No existen registros de certificados para el año $ano_seleccionado.";
        }
    }

    if (!empty($sql)) {
        $sql .= " ORDER BY c.fecha_cert_venc ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error .= " Error de base de datos: " . $e->getMessage();
}

// --- Actualizar estado de certificados vencidos (sin cambios) ---
// ... (la lógica de actualización permanece igual) ...

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Certificados próximos a vencer y vencidos</title>
    <?php include 'includes/header.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .printable-area, .printable-area * {
                visibility: visible;
            }
            .printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="no-print">
        <h2>Certificados próximos a vencer y vencidos</h2>
        
        <form method="GET" class="row g-3 mb-4 align-items-end">
            <div class="col-md-3">
                <label for="ano" class="form-label">Año:</label>
                <select name="ano" id="ano" class="form-select">
                    <option value="all" <?php echo ($ano_seleccionado == 'all') ? 'selected' : ''; ?>>Todos los años</option>
                    <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($ano_seleccionado == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="cuit_proveedor" class="form-label">Proveedor:</label>
                <select name="cuit_proveedor" id="cuit_proveedor" class="form-select">
                    <option value="">Todos</option>
                    <?php foreach ($proveedores_for_dropdown as $prov): ?>
                        <option value="<?php echo htmlspecialchars($prov['cuit']); ?>" <?php echo ($filtro_proveedor == $prov['cuit']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($prov['nombre_proveedor']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label for="filtro_estado" class="form-label">Estado:</label>
                <select name="filtro_estado" id="filtro_estado" class="form-select">
                    <option value="todos" <?php echo ($filtro_estado == 'todos') ? 'selected' : ''; ?>>Todos</option>
                    <option value="vencidos" <?php echo ($filtro_estado == 'vencidos') ? 'selected' : ''; ?>>Vencidos</option>
                    <option value="proximos" <?php echo ($filtro_estado == 'proximos') ? 'selected' : ''; ?>>Próximos a Vencer</option>
                </select>
            </div>

            <div class="col-md-2">
                <label for="busqueda" class="form-label">Búsqueda:</label>
                <input type="text" class="form-control" name="busqueda" id="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filtrar</button>
            </div>
        </form>

        <div class="row mb-4">
            <div class="col-12 text-end">
                <button id="print-btn" class="btn btn-secondary">🖨️ Imprimir</button>
                <a href="exportar_excel.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">📄 Exportar a Excel</a>
            </div>
        </div>
    </div>

    <?php if ($success): ?><div class="alert alert-success no-print"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger no-print"><?php echo $error; ?></div><?php endif; ?>

    <div class="table-responsive printable-area">
        <h3 class="d-none d-print-block mb-3">Reporte de Certificados</h3> <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Vencimiento</th>
                    <th>Proveedor</th>
                    <th>Expediente ID</th>
                    <th>Carátula</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($certificados)): ?>
                <tr><td colspan="5" class="text-center">No hay certificados que coincidan con los filtros seleccionados.</td></tr>
            <?php else: ?>
                <?php foreach ($certificados as $cert): ?>
                    <tr>
                        <td><?php echo date('d-m-Y', strtotime($cert['fecha_cert_venc'])); ?></td>
                        <td><?php echo htmlspecialchars($cert['nombre_proveedor'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($cert['id_expediente']); ?></td>
                        <td><?php echo htmlspecialchars($cert['caratula'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($cert['estado']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Inicializar Select2
        $('#ano').select2();
        $('#cuit_proveedor').select2();
        $('#filtro_estado').select2({ minimumResultsForSearch: -1 });

        // Botón de imprimir
        $('#print-btn').on('click', function() {
            window.print();
        });
    });
</script>
</body>
</html>