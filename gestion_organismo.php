<?php
// -----------------------------------------------------------------------------
// 1. INICIALIZACIÓN Y AUTENTICACIÓN
// -----------------------------------------------------------------------------
require_once 'session_init.php';
require 'db.php'; // Asumo que este archivo establece la conexión en una variable $pdo
require_once 'auth.php';
include 'includes/header.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: acceso_denegado.php");
    exit;
}

$message = '';
$message_type = '';

// -----------------------------------------------------------------------------
// 2. CREACIÓN DE LA TABLA (SI NO EXISTE) - MODIFICADO
// -----------------------------------------------------------------------------
try {
    // MODIFICADO: 'identificador' es ahora un VARCHAR y la PRIMARY KEY. No es autoincremental.
    $sql_create_table = "
    CREATE TABLE IF NOT EXISTS organismos (
        identificador VARCHAR(512) PRIMARY KEY,
        org_num INT NOT NULL UNIQUE,
        org_nombre VARCHAR(255) NOT NULL,
        fecha DATE,
        estado VARCHAR(50),
        direccion VARCHAR(255) NULL,
        telefonos VARCHAR(100) NULL,
        observaciones TEXT NULL
    );";
    $pdo->exec($sql_create_table);
} catch (PDOException $e) {
    die("Error al crear la tabla de organismos: " . $e->getMessage());
}


// -----------------------------------------------------------------------------
// 3. LÓGICA DE NEGOCIO (AGREGAR, MODIFICAR, ELIMINAR) - MODIFICADO
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- Acción de AGREGAR o MODIFICAR ---
    if (isset($_POST['action'])) {
        
        $org_num = filter_input(INPUT_POST, 'org_num', FILTER_VALIDATE_INT);
        $org_nombre = filter_input(INPUT_POST, 'org_nombre', FILTER_SANITIZE_STRING);
        $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
        $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING);
        $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING);
        $telefonos = filter_input(INPUT_POST, 'telefonos', FILTER_SANITIZE_STRING);
        $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING);
        
        if ($org_num === false || empty($org_nombre) || empty($fecha) || empty($estado)) {
            $message = "Error: Número, Nombre, Fecha y Estado son obligatorios.";
            $message_type = 'danger';
        } else {
            try {
                // AÑADIDO: Se genera el identificador concatenando número y nombre.
                $nuevo_identificador = $org_num . '-' . $org_nombre;

                // --- Lógica para MODIFICAR ---
                if ($_POST['action'] == 'edit') {
                    $original_identificador = filter_input(INPUT_POST, 'original_identificador', FILTER_SANITIZE_STRING);
                    
                    // MODIFICADO: La consulta actualiza el 'identificador' y busca por el original.
                    $sql = "UPDATE organismos SET 
                                identificador = :nuevo_identificador, 
                                org_num = :org_num, 
                                org_nombre = :org_nombre, 
                                fecha = :fecha, 
                                estado = :estado, 
                                direccion = :direccion, 
                                telefonos = :telefonos, 
                                observaciones = :observaciones 
                            WHERE identificador = :original_identificador";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':original_identificador', $original_identificador, PDO::PARAM_STR);
                    $message = "Organismo modificado con éxito.";
                
                // --- Lógica para AGREGAR ---
                } else { // action == 'add'
                    // MODIFICADO: Se incluye el 'identificador' generado en la consulta INSERT.
                    $sql = "INSERT INTO organismos (identificador, org_num, org_nombre, fecha, estado, direccion, telefonos, observaciones) 
                            VALUES (:nuevo_identificador, :org_num, :org_nombre, :fecha, :estado, :direccion, :telefonos, :observaciones)";
                    $stmt = $pdo->prepare($sql);
                    $message = "Organismo agregado con éxito.";
                }

                $stmt->bindParam(':nuevo_identificador', $nuevo_identificador, PDO::PARAM_STR);
                $stmt->bindParam(':org_num', $org_num, PDO::PARAM_INT);
                $stmt->bindParam(':org_nombre', $org_nombre, PDO::PARAM_STR);
                $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
                $stmt->bindParam(':estado', $estado, PDO::PARAM_STR);
                $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
                $stmt->bindParam(':telefonos', $telefonos, PDO::PARAM_STR);
                $stmt->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
                
                $stmt->execute();
                $message_type = 'success';

            } catch (PDOException $e) {
                if ($e->getCode() == 23000) { 
                    // Este error puede saltar por la PRIMARY KEY (identificador) o por la UNIQUE KEY (org_num)
                    $message = "Error: El número de organismo o la combinación de número y nombre ya existe.";
                } else {
                    $message = "Error en la base de datos: " . $e->getMessage();
                }
                $message_type = 'danger';
            }
        }
    }

    // --- Acción de ELIMINAR ---
    if (isset($_POST['action_delete'])) {
        $id_to_delete = filter_input(INPUT_POST, 'identificador_delete', FILTER_SANITIZE_STRING);
        if ($id_to_delete) {
            try {
                $sql = "DELETE FROM organismos WHERE identificador = :identificador";
                $stmt = $pdo->prepare($sql);
                // MODIFICADO: El parámetro ahora es de tipo string.
                $stmt->bindParam(':identificador', $id_to_delete, PDO::PARAM_STR);
                $stmt->execute();
                $message = "Organismo eliminado con éxito.";
                $message_type = 'success';
            } catch (PDOException $e) {
                $message = "Error al eliminar el organismo: " . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}


// -----------------------------------------------------------------------------
// 4. PREPARACIÓN DE DATOS PARA LA VISTA
// -----------------------------------------------------------------------------
$edit_mode = false;
$organismo_a_editar = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['identificador'])) {
    // MODIFICADO: Se sanitiza el identificador como string.
    $id_get = filter_input(INPUT_GET, 'identificador', FILTER_SANITIZE_STRING);
    if ($id_get) {
        try {
            $sql = "SELECT * FROM organismos WHERE identificador = :identificador";
            $stmt = $pdo->prepare($sql);
            // MODIFICADO: El parámetro ahora es de tipo string.
            $stmt->bindParam(':identificador', $id_get, PDO::PARAM_STR);
            $stmt->execute();
            $organismo_a_editar = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($organismo_a_editar) {
                $edit_mode = true;
            }
        } catch (PDOException $e) {
            $message = "Error al buscar el organismo para editar: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// --- Listado de todos los organismos ---
try {
    $stmt_list = $pdo->query("SELECT * FROM organismos ORDER BY org_nombre ASC");
    $organismos = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error al obtener la lista de organismos: " . $e->getMessage();
    $message_type = 'danger';
    $organismos = [];
}

$pdo = null;

// -----------------------------------------------------------------------------
// 5. VISTA (HTML) - MODIFICADO PARA MOSTRAR MÁS CAMPOS
// -----------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Organismos</title>
    <style>
        body { background-color: #f8f9fa; }
        .container { max-width: 1200px; }
        .card-header-custom { background-color: #0d6efd; color: white; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4 text-center">Gestión de Organismos</h1>

        <div class="card mb-5">
            <div class="card-header card-header-custom">
                <h5 class="mb-0">
                    <?php if ($edit_mode) : ?><i class="bi bi-pencil-square"></i> Editar Organismo<?php else : ?><i class="bi bi-plus-circle"></i> Agregar Nuevo Organismo<?php endif; ?>
                </h5>
            </div>
            <div class="card-body">
                <?php if ($message) : ?>
                    <div class="alert alert-<?php echo htmlspecialchars($message_type); ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="gestion_organismo.php" method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit' : 'add'; ?>">
                    <?php if ($edit_mode) : ?>
                        <input type="hidden" name="original_identificador" value="<?php echo htmlspecialchars($organismo_a_editar['identificador']); ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="org_num" class="form-label">Número de Organismo <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="org_num" name="org_num" value="<?php echo $edit_mode ? htmlspecialchars($organismo_a_editar['org_num']) : ''; ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label for="org_nombre" class="form-label">Nombre del Organismo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="org_nombre" name="org_nombre" value="<?php echo $edit_mode ? htmlspecialchars($organismo_a_editar['org_nombre']) : ''; ?>" required>
                        </div>
                         <div class="col-md-2">
                            <label for="fecha" class="form-label">Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fecha" name="fecha" value="<?php echo $edit_mode ? htmlspecialchars($organismo_a_editar['fecha']) : ''; ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                            <select class="form-select" id="estado" name="estado" required>
                                <option value="" disabled <?php echo !$edit_mode ? 'selected' : ''; ?>>Seleccione...</option>
                                <option value="Activo" <?php echo ($edit_mode && $organismo_a_editar['estado'] == 'Activo') ? 'selected' : ''; ?>>Activo</option>
                                <option value="Inactivo" <?php echo ($edit_mode && $organismo_a_editar['estado'] == 'Inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                <option value="Pendiente" <?php echo ($edit_mode && $organismo_a_editar['estado'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="direccion" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo $edit_mode ? htmlspecialchars($organismo_a_editar['direccion']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="telefonos" class="form-label">Teléfonos</label>
                            <input type="text" class="form-control" id="telefonos" name="telefonos" value="<?php echo $edit_mode ? htmlspecialchars($organismo_a_editar['telefonos']) : ''; ?>">
                        </div>
                        <div class="col-12">
                             <label for="observaciones" class="form-label">Observaciones</label>
                             <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?php echo $edit_mode ? htmlspecialchars($organismo_a_editar['observaciones']) : ''; ?></textarea>
                        </div>
                    </div>
                    <div class="mt-4 text-end">
                        <?php if ($edit_mode) : ?>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Cambios</button>
                            <a href="gestion_organismo.php" class="btn btn-secondary">Cancelar Edición</a>
                        <?php else : ?>
                            <button type="submit" class="btn btn-success"><i class="bi bi-plus-lg"></i> Agregar Organismo</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-list-ul"></i> Lista de Organismos</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Num. Org - Nombre</th> 
                                <th>Fecha</th>
                                <th>Estado</th>
                                <th>Dirección</th>
                                <th>Teléfonos</th>
                                <th>Observaciones</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($organismos)) : ?>
                                <tr><td colspan="7" class="text-center text-muted">No hay organismos registrados.</td></tr>
                                <?php else : ?>
                                <?php foreach ($organismos as $org) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($org['identificador']); ?></td>
                                        <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($org['fecha']))); ?></td>
                                        <td>
                                            <?php 
                                                $badge_class = 'bg-secondary';
                                                if ($org['estado'] == 'Activo') $badge_class = 'bg-success';
                                                if ($org['estado'] == 'Inactivo') $badge_class = 'bg-danger';
                                                if ($org['estado'] == 'Pendiente') $badge_class = 'bg-warning text-dark';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($org['estado']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($org['direccion']); ?></td>
                                        <td><?php echo htmlspecialchars($org['telefonos']); ?></td>
                                        <td><?php echo htmlspecialchars($org['observaciones']); ?></td>
                                        <td class="text-center">
                                            <a href="?action=edit&identificador=<?php echo urlencode($org['identificador']); ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="gestion_organismo.php" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este organismo?');">
                                                <input type="hidden" name="action_delete" value="true">
                                                <input type="hidden" name="identificador_delete" value="<?php echo htmlspecialchars($org['identificador']); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <footer class="text-center text-muted py-4">
            <p>&copy; <?php echo date('Y'); ?> Mi Aplicación. Todos los derechos reservados.</p>
        </footer>
    </div>
</body>
</html>