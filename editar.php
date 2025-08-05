<?php
// At the very top of your PHP script, for development only
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session_init.php';
require 'db.php'; // Asegúrate de que este archivo define $pdo
require_once 'auth.php';

// 1. Verificar si el usuario está logueado y es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: acceso_denegado.php"); // Redirigir si no tiene permisos
    exit;
}

$error = '';
$success_message = ''; // Renamed for clarity
$registro = null; // Almacenará los datos del registro a editar
$tabla_seleccionada = $_GET['tabla'] ?? '';
$id_registro = $_GET['id'] ?? null;
$año_para_redireccion = $_GET['año'] ?? ''; // Store the year for redirection

$columnas_omitidas_form = ['id', 'fecha_creacion', 'usuario', 'id_expediente'];

// --- Fetch providers for the dropdown ---
$proveedores_list = [];
try {
    $stmt_proveedores = $pdo->query("SELECT nombre_proveedor FROM `proveedores` ORDER BY nombre_proveedor ASC");
    $proveedores_list = $stmt_proveedores->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Error al cargar la lista de proveedores: " . $e->getMessage());
}

// =================== MODIFICADO: OBTENER ORGANISMOS ===================
$organismos_list = [];
try {
    // Se cambia a FETCH_ASSOC para tener un array de arrays asociativos
    $stmt_organismos = $pdo->query("SELECT org_num, org_nombre FROM `organismos` ORDER BY org_nombre ASC");
    $organismos_list = $stmt_organismos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar la lista de organismos: " . $e->getMessage());
}
// =================== FIN MODIFICACIÓN ===================


// Array de meses para los desplegables
$meses_espanol = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];
// Año base para el desplegable de año. Usa el año del expediente o el actual.
$anio_base = $año_para_redireccion ?: date('Y');

// 2. Validar tabla e ID
if (empty($tabla_seleccionada) || !preg_match('/^exptes-\d{4}$/', $tabla_seleccionada)) {
    $error = "Nombre de tabla inválido.";
} elseif (!is_numeric($id_registro) || $id_registro <= 0) {
    $error = "ID de registro inválido.";
} else {
    // 3. PROCESAR EL FORMULARIO (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = $_POST;
        $id_registro_post = $data['id'] ?? null;
        $tabla_post = $data['tabla'] ?? '';
        $año_para_redireccion_post = $data['año_redirect'] ?? '';

        $expediente_proveedor_monto_data = [];
        if (isset($data['proveedor_nombre']) && is_array($data['proveedor_nombre'])) {
            foreach ($data['proveedor_nombre'] as $index => $nombre) {
                if (!empty($nombre) && isset($data['proveedor_tipo_periodo'][$index]) && isset($data['proveedor_monto'][$index])) {
                    $tipo_periodo = $data['proveedor_tipo_periodo'][$index];
                    
                    if ($tipo_periodo === 'mes') {
                        $mes_num = $data['proveedor_mes_mes'][$index] ?? null;
                        $anio_num = $data['proveedor_mes_anio'][$index] ?? null;
                        // Reconstruir el formato YYYY-MM para la base de datos
                        $mes = (!empty($mes_num) && !empty($anio_num)) ? sprintf('%04d-%02d', $anio_num, $mes_num) : null;
                    } else {
                        $mes = null;
                    }
                    
                    $fecha_exacta = $tipo_periodo === 'fecha' ? ($data['proveedor_fecha'][$index] ?? null) : null;
                    $periodo_desde = $tipo_periodo === 'periodo' ? ($data['proveedor_periodo_desde'][$index] ?? null) : null;
                    $periodo_hasta = $tipo_periodo === 'periodo' ? ($data['proveedor_periodo_hasta'][$index] ?? null) : null;
                    
                    $expediente_proveedor_monto_data[] = [
                        'nombre_proveedor' => trim($nombre),
                        'tipo_periodo' => $tipo_periodo,
                        'mes' => $mes,
                        'fecha_exacta' => $fecha_exacta,
                        'periodo_desde' => $periodo_desde,
                        'periodo_hasta' => $periodo_hasta,
                        'monto' => floatval(str_replace(',', '.', trim($data['proveedor_monto'][$index])))
                    ];
                }
            }
        }
        
        // Remove processed provider data from $data to avoid column mismatch errors
        unset($data['id'], $data['tabla'], $data['año_redirect']);
        unset($data['proveedor_nombre'], $data['proveedor_tipo_periodo'], $data['proveedor_mes_mes'], $data['proveedor_mes_anio']);
        unset($data['proveedor_fecha'], $data['proveedor_periodo_desde'], $data['proveedor_periodo_hasta'], $data['proveedor_monto']);

        if ($id_registro_post != $id_registro || $tabla_post != $tabla_seleccionada) {
            $error = "Error de seguridad: los datos del formulario no coinciden con la solicitud.";
        } else {
            try {
                $pdo->beginTransaction();

                $stmt_check = $pdo->prepare("SELECT * FROM `" . $tabla_seleccionada . "` WHERE `id` = :id");
                $stmt_check->bindParam(':id', $id_registro, PDO::PARAM_INT);
                $stmt_check->execute();
                $registro_original = $stmt_check->fetch(PDO::FETCH_ASSOC);

                if ($registro_original) {
                    $id_expediente_value = $registro_original['id_expediente'] ?? null;
                    
                    $set_clauses = [];
                    $params = [];
                    foreach ($data as $columna_post => $valor_post) {
                        if (array_key_exists($columna_post, $registro_original) && !in_array(strtolower($columna_post), $columnas_omitidas_form)) {
                            $placeholder = preg_replace('/[^a-zA-Z0-9_]/', '_', $columna_post);
                            $set_clauses[] = "`" . $columna_post . "` = :" . $placeholder;
                            $params[":" . $placeholder] = $valor_post;
                        }
                    }

                    if (!empty($set_clauses)) {
                        $sql = "UPDATE `" . $tabla_seleccionada . "` SET " . implode(', ', $set_clauses) . " WHERE `id` = :id";
                        $params[':id'] = $id_registro;
                        $stmt = $pdo->prepare($sql);
                        if (!$stmt->execute($params)) {
                            throw new PDOException("Error al actualizar el registro principal.");
                        }
                    }

                    if ($id_expediente_value) {
                        $año_tabla_relacion = $año_para_redireccion_post ?: substr($tabla_seleccionada, -4);
                        $tabla_relacion = "expediente_proveedor_monto-" . $año_tabla_relacion;
                        
                        $stmt_delete_prov_monto = $pdo->prepare("DELETE FROM `$tabla_relacion` WHERE id_expediente = ?");
                        $stmt_delete_prov_monto->execute([$id_expediente_value]);

                        if (!empty($expediente_proveedor_monto_data)) {
                            $insert_sql = "INSERT INTO `$tabla_relacion` (id_expediente, nombre_proveedor, tipo_periodo, mes, fecha_exacta, periodo_desde, periodo_hasta, monto) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                            $stmt_insert_prov_monto = $pdo->prepare($insert_sql);
                            foreach ($expediente_proveedor_monto_data as $row) {
                                $stmt_insert_prov_monto->execute([
                                    $id_expediente_value, $row['nombre_proveedor'], $row['tipo_periodo'],
                                    $row['mes'], $row['fecha_exacta'], $row['periodo_desde'],
                                    $row['periodo_hasta'], $row['monto']
                                ]);
                            }
                        }
                    }
                    
                    $pdo->commit();
                    $success_message = "Registro y datos de proveedores actualizados correctamente. ✅";
                    header("Location: gestionar_exptes.php?success=" . urlencode($success_message) . "&año=" . urlencode($año_para_redireccion_post));
                    exit;
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Error de base de datos al actualizar: " . $e->getMessage();
            }
        }
    }

    // 4. Obtener los datos del registro para mostrar en el formulario
    if (empty($error) && empty($success_message)) {
        try {
            $stmt_select = $pdo->prepare("SELECT * FROM `" . $tabla_seleccionada . "` WHERE `id` = :id");
            $stmt_select->bindParam(':id', $id_registro, PDO::PARAM_INT);
            $stmt_select->execute();
            $registro = $stmt_select->fetch(PDO::FETCH_ASSOC);

            if (!$registro) {
                $error = "Registro no encontrado en la tabla " . htmlspecialchars($tabla_seleccionada) . ".";
            } else {
                $id_expediente_value = $registro['id_expediente'] ?? null;
                $existing_prov_monto_data = [];
                if ($id_expediente_value) {
                    $año_tabla_relacion = $año_para_redireccion ?: substr($tabla_seleccionada, -4);
                    $tabla_relacion = "expediente_proveedor_monto-" . $año_tabla_relacion;
                    $stmt_fetch_prov_monto = $pdo->prepare("SELECT id, nombre_proveedor, tipo_periodo, mes, fecha_exacta, periodo_desde, periodo_hasta, monto FROM `$tabla_relacion` WHERE id_expediente = ? ORDER BY id ASC");
                    $stmt_fetch_prov_monto->execute([$id_expediente_value]);
                    $existing_prov_monto_data = $stmt_fetch_prov_monto->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        } catch (PDOException $e) {
            $error = "Error al obtener el registro: " . $e->getMessage();
        }
    }
}

$columnas_tabla = [];
if (empty($error) && $registro) {
    $columnas_tabla = array_keys($registro);
}

include 'includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Expediente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h1 class="mb-4">Editar Expediente <small class="text-muted">(<?= htmlspecialchars($tabla_seleccionada) ?>)</small></h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <a href="gestionar_exptes.php?año=<?= htmlspecialchars($año_para_redireccion) ?>" class="btn btn-secondary mt-3">Volver a Gestión de Expedientes</a>
    <?php else: ?>
        <?php if ($registro): ?>
            <form action="editar.php?tabla=<?= urlencode($tabla_seleccionada) ?>&id=<?= htmlspecialchars($id_registro) ?>&año=<?= htmlspecialchars($año_para_redireccion) ?>" method="post">
                <input type="hidden" name="tabla" value="<?= htmlspecialchars($tabla_seleccionada) ?>">
                <input type="hidden" name="id" value="<?= htmlspecialchars($id_registro) ?>">
                <input type="hidden" name="año_redirect" value="<?= htmlspecialchars($año_para_redireccion) ?>">
                
                <div class="row g-3 mb-4">
                    <?php foreach ($columnas_tabla as $columna): ?>
                        <?php if (in_array(strtolower($columna), $columnas_omitidas_form)) continue; ?>
                        <div class="col-md-4">
                            <label for="<?= htmlspecialchars($columna) ?>" class="form-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $columna))) ?></label>
                            
                            <?php 
                            // =================== MODIFICACIÓN PARA ORGANISMO ===================
                            if (strtolower($columna) === 'organismo'): ?>
                                <select name="<?= htmlspecialchars($columna) ?>" id="<?= htmlspecialchars($columna) ?>" class="form-select">
                                    <option value="">-- Seleccionar Organismo --</option>
                                    <?php foreach ($organismos_list as $organismo): ?>
                                        <option value="<?= htmlspecialchars($organismo['org_num']) ?>" <?= ($registro[$columna] == $organismo['org_num']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($organismo['org_num'] . ' - ' . $organismo['org_nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="<?= htmlspecialchars($columna) ?>" id="<?= htmlspecialchars($columna) ?>" class="form-control" value="<?= htmlspecialchars($registro[$columna] ?? '') ?>">
                            <?php endif; 
                            // =================== FIN MODIFICACIÓN ===================
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h3 class="mb-3">Proveedores y Montos</h3>
                <div id="proveedorMontosContainer" class="mb-3">
                    <?php if (!empty($existing_prov_monto_data)): ?>
                        <?php foreach ($existing_prov_monto_data as $index => $prov_monto_row): 
                            $tipo_periodo = $prov_monto_row['tipo_periodo'] ?? 'mes';
                        ?>
                            <div class="row g-3 mb-3 p-2 border rounded proveedor-monto-row">
                                <div class="col-md-4">
                                    <label class="form-label">Proveedor</label>
                                    <select name="proveedor_nombre[]" class="form-select">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($proveedores_list as $proveedor_name): ?>
                                            <option value="<?= htmlspecialchars($proveedor_name) ?>" <?= ($prov_monto_row['nombre_proveedor'] === $proveedor_name) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($proveedor_name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tipo de Periodo</label>
                                    <select name="proveedor_tipo_periodo[]" class="form-select tipo-periodo-select">
                                        <option value="mes" <?= $tipo_periodo === 'mes' ? 'selected' : '' ?>>Mes</option>
                                        <option value="fecha" <?= $tipo_periodo === 'fecha' ? 'selected' : '' ?>>Fecha exacta</option>
                                        <option value="periodo" <?= $tipo_periodo === 'periodo' ? 'selected' : '' ?>>Rango de fechas</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                        <label class="form-label">Monto</label>
                                    <input type="number" step="0.01" name="proveedor_monto[]" class="form-control monto-input" value="<?= htmlspecialchars($prov_monto_row['monto'] ?? '') ?>">
                                    <small class="form-text text-muted monto-display">$ <?= number_format($prov_monto_row['monto'] ?? 0, 2, ',', '.') ?></small>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger remove-prov-monto-row">🗑️</button>
                                </div>

                                <div class="col-12 tipo-periodo-campos">
                                    <div class="tipo-mes" style="display:<?= $tipo_periodo === 'mes' ? 'block' : 'none' ?>;">
                                        <label class="form-label">Mes y Año</label>
                                        <?php
                                            // Obtener mes y año seleccionados del valor 'YYYY-MM'
                                            $mes_actual_valor = $prov_monto_row['mes'] ?? '';
                                            list($anio_seleccionado, $mes_seleccionado) = !empty($mes_actual_valor) ? explode('-', $mes_actual_valor) : [null, null];
                                        ?>
                                        <div class="input-group">
                                            <select name="proveedor_mes_mes[]" class="form-select">
                                                <option value="">-- Mes --</option>
                                                <?php foreach ($meses_espanol as $num => $nombre): ?>
                                                    <option value="<?= $num ?>" <?= ($num == $mes_seleccionado) ? 'selected' : '' ?>>
                                                        <?= $nombre ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <select name="proveedor_mes_anio[]" class="form-select">
                                                <option value="">-- Año --</option>
                                                <?php for ($y = $anio_base - 5; $y <= $anio_base + 5; $y++): ?>
                                                    <option value="<?= $y ?>" <?= ($y == $anio_seleccionado) ? 'selected' : '' ?>>
                                                        <?= $y ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="tipo-fecha" style="display:<?= $tipo_periodo === 'fecha' ? 'block' : 'none' ?>;">
                                        <label class="form-label">Fecha exacta</label>
                                        <input type="date" name="proveedor_fecha[]" class="form-control" value="<?= htmlspecialchars($prov_monto_row['fecha_exacta'] ?? '') ?>">
                                    </div>
                                    <div class="tipo-periodo" style="display:<?= $tipo_periodo === 'periodo' ? 'block' : 'none' ?>;">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label">Desde</label>
                                                <input type="date" name="proveedor_periodo_desde[]" class="form-control" value="<?= htmlspecialchars($prov_monto_row['periodo_desde'] ?? '') ?>">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Hasta</label>
                                                <input type="date" name="proveedor_periodo_hasta[]" class="form-control" value="<?= htmlspecialchars($prov_monto_row['periodo_hasta'] ?? '') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" id="addProveedorMonto" class="btn btn-sm btn-outline-info mb-4">➕ Agregar Proveedor/Monto</button>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    <a href="gestionar_exptes.php?año=<?= htmlspecialchars($año_para_redireccion) ?>" class="btn btn-secondary">Cancelar y Volver</a>
                </div>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const proveedorMontosContainer = document.getElementById('proveedorMontosContainer');
    const addProveedorMontoButton = document.getElementById('addProveedorMonto');
    const proveedoresList = <?= json_encode($proveedores_list) ?>;
    
    // Pasar datos de PHP a JS para construir los desplegables
    const mesesEspanol = <?= json_encode($meses_espanol) ?>;
    const anioBase = parseInt(<?= json_encode($anio_base) ?>);

    /**
     * Formatea un número al estilo de moneda solicitado.
     * Ejemplo: 12345.67 -> "$ 12.345,67"
     */
    function formatCurrency(value) {
        const number = parseFloat(value) || 0;
        return '$ ' + number.toLocaleString('es-AR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }


    /**
     * Configura el formateo en tiempo real para un campo de monto.
     */
    function setupMontoFormatting(rowElement) {
        const montoInput = rowElement.querySelector('.monto-input');
        const montoDisplay = rowElement.querySelector('.monto-display');

        if (montoInput && montoDisplay) {
            montoInput.addEventListener('input', function() {
                montoDisplay.textContent = formatCurrency(this.value);
            });
             // Formateo inicial en caso de que el valor venga de la DB pero con otro formato
            montoDisplay.textContent = formatCurrency(montoInput.value);
        }
    }

    function setupTipoPeriodoToggle(rowElement) {
        const tipoPeriodoSelect = rowElement.querySelector('.tipo-periodo-select');
        const camposContainer = rowElement.querySelector('.tipo-periodo-campos');
        if (!tipoPeriodoSelect || !camposContainer) return;

        const updateVisibility = () => {
            const tipo = tipoPeriodoSelect.value;
            camposContainer.querySelector('.tipo-mes').style.display = tipo === 'mes' ? 'block' : 'none';
            camposContainer.querySelector('.tipo-fecha').style.display = tipo === 'fecha' ? 'block' : 'none';
            camposContainer.querySelector('.tipo-periodo').style.display = tipo === 'periodo' ? 'block' : 'none';
        };
        tipoPeriodoSelect.addEventListener('change', updateVisibility);
    }

    function setupRemoveButton(rowElement) {
        const removeButton = rowElement.querySelector('.remove-prov-monto-row');
        if (removeButton) {
            removeButton.addEventListener('click', () => rowElement.remove());
        }
    }

    // --- Aplicar la lógica a las filas existentes al cargar la página ---
    document.querySelectorAll('.proveedor-monto-row').forEach(row => {
        setupTipoPeriodoToggle(row);
        setupRemoveButton(row);
        setupMontoFormatting(row); // <-- APLICAR FORMATEO A FILAS EXISTENTES
    });

    // --- Lógica para agregar una nueva fila ---
    addProveedorMontoButton.addEventListener('click', function() {
        const newRow = document.createElement('div');
        newRow.className = 'row g-3 mb-3 p-2 border rounded proveedor-monto-row';
        
        // Construir los <options> para los desplegables
        let mesOptions = '<option value="">-- Mes --</option>';
        for (const [num, nombre] of Object.entries(mesesEspanol)) {
            mesOptions += `<option value="${num}">${nombre}</option>`;
        }

        let anioOptions = '<option value="">-- Año --</option>';
        for (let y = anioBase - 5; y <= anioBase + 5; y++) {
            // Seleccionar por defecto el año base del expediente
            const selected = y === anioBase ? 'selected' : '';
            anioOptions += `<option value="${y}" ${selected}>${y}</option>`;
        }
        
        newRow.innerHTML = `
            <div class="col-md-4">
                <label class="form-label">Proveedor</label>
                <select name="proveedor_nombre[]" class="form-select">
                    <option value="">-- Seleccionar --</option>
                    ${proveedoresList.map(prov => `<option value="${prov}">${prov}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipo de Periodo</label>
                <select name="proveedor_tipo_periodo[]" class="form-select tipo-periodo-select">
                    <option value="mes" selected>Mes</option>
                    <option value="fecha">Fecha exacta</option>
                    <option value="periodo">Rango de fechas</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Monto</label>
                <input type="number" step="0.01" name="proveedor_monto[]" class="form-control monto-input" value="">
                <small class="form-text text-muted monto-display">$ 0,00</small>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger remove-prov-monto-row">🗑️</button>
            </div>
            <div class="col-12 tipo-periodo-campos">
                <div class="tipo-mes" style="display:block;">
                    <label class="form-label">Mes y Año</label>
                    <div class="input-group">
                         <select name="proveedor_mes_mes[]" class="form-select">${mesOptions}</select>
                         <select name="proveedor_mes_anio[]" class="form-select">${anioOptions}</select>
                    </div>
                </div>
                <div class="tipo-fecha" style="display:none;">
                    <label class="form-label">Fecha exacta</label>
                    <input type="date" name="proveedor_fecha[]" class="form-control">
                </div>
                <div class="tipo-periodo" style="display:none;">
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label">Desde</label>
                            <input type="date" name="proveedor_periodo_desde[]" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Hasta</label>
                            <input type="date" name="proveedor_periodo_hasta[]" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        `;

        proveedorMontosContainer.appendChild(newRow);
        
        // Aplicar toda la lógica a la nueva fila recién creada
        setupTipoPeriodoToggle(newRow);
        setupRemoveButton(newRow);
        setupMontoFormatting(newRow); // <-- APLICAR FORMATEO A NUEVA FILA
    });
});
</script>
</body>
</html>