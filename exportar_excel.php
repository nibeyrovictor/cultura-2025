<?php
// Requerir archivos y verificar sesión
require_once 'session_init.php';
require_once 'db.php';
require_once 'auth.php';

// --- Seguridad: Solo Admin ---
if (!isLoggedIn() || !isAdmin()) {
    die("Acceso denegado.");
}

// --- Recrear la lógica de filtros (esta parte no cambia) ---
$ano_seleccionado = isset($_GET['ano']) ? $_GET['ano'] : 'all';
$filtro_proveedor = isset($_GET['cuit_proveedor']) ? trim($_GET['cuit_proveedor']) : '';
$filtro_estado = isset($_GET['filtro_estado']) ? trim($_GET['filtro_estado']) : 'todos';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

$certificados = [];

try {
    // --- Reconstrucción idéntica de la consulta para obtener los datos ---
    // (Todo el bloque try/catch para construir la consulta SQL permanece aquí,
    // es idéntico al código anterior y no es necesario mostrarlo de nuevo por brevedad.
    // Simplemente asegúrate de que toda tu lógica de consulta SQL esté aquí).
    
    // --- Lógica de consulta (la misma que ya tienes) ---
    $sql = "";
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
    if ($ano_seleccionado === 'all') {
        $stmt_cert_tables = $pdo->query("SHOW TABLES LIKE 'cert_349-%'");
        $cert_tables = $stmt_cert_tables->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($cert_tables)) {
            $cert_union_sql = [];
            foreach ($cert_tables as $table_cert) {
                $cert_union_sql[] = "SELECT *, '{$table_cert}' as source_table FROM `{$table_cert}`";
            }
            $cert_subquery = "(" . implode(' UNION ALL ', $cert_union_sql) . ") AS c";
            $sql = "SELECT c.*, p.nombre_proveedor, {$exptes_select_sql} FROM {$cert_subquery} LEFT JOIN `proveedores` p ON c.cuit_proveedor = p.cuit {$exptes_join_sql} {$where_clause}";
        }
    } else {
        $nombre_tabla_cert = "cert_349-" . intval($ano_seleccionado);
        $stmt_check_table = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt_check_table->execute([$nombre_tabla_cert]);
        if ($stmt_check_table->fetch()) {
            $sql = "SELECT c.*, p.nombre_proveedor, {$exptes_select_sql}, '{$nombre_tabla_cert}' as source_table FROM `{$nombre_tabla_cert}` c LEFT JOIN `proveedores` p ON c.cuit_proveedor = p.cuit {$exptes_join_sql} {$where_clause}";
        }
    }
    if (!empty($sql)) {
        $sql .= " ORDER BY c.fecha_cert_venc ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    die("Error de base de datos al generar el reporte: " . $e->getMessage());
}


// --- NUEVA LÓGICA PARA GENERAR CSV ---

// 1. Definir los encabezados para el archivo CSV y el nombre del archivo
$filename = "reporte-certificados-" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 2. Crear un puntero de archivo conectado a la salida de PHP
$output = fopen('php://output', 'w');

// 3. Escribir el BOM de UTF-8 para asegurar la compatibilidad de caracteres en Excel
fwrite($output, "\xEF\xBB\xBF");

// 4. Escribir la fila de encabezados en el CSV
$headers = ['Vencimiento', 'Proveedor', 'CUIT Proveedor', 'Expediente ID', 'Caratula', 'Estado'];
fputcsv($output, $headers, ';'); // Usamos punto y coma como separador, común en configuraciones en español

// 5. Recorrer los datos y escribirlos en el CSV
if (!empty($certificados)) {
    foreach ($certificados as $cert) {
        $row = [
            date('d-m-Y', strtotime($cert['fecha_cert_venc'])),
            $cert['nombre_proveedor'] ?? 'N/A',
            $cert['cuit_proveedor'] ?? 'N/A',
            $cert['id_expediente'],
            $cert['caratula'] ?? 'N/A',
            $cert['estado']
        ];
        fputcsv($output, $row, ';');
    }
}

// 6. Cerrar el puntero del archivo
fclose($output);
exit;

?>