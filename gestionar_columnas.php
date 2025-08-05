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

/**
 * Asegura que la tabla de alias globales exista sin destruir sus datos.
 * El comando `CREATE TABLE IF NOT EXISTS` es suficiente y seguro.
 */
function createGlobalAliasTable($pdo) {
    try {
        // ¡NO BORRAR LA TABLA AQUÍ! Esto destruiría los datos guardados.
        // La instrucción `CREATE TABLE IF NOT EXISTS` se encarga de crearla solo si es necesario.
        $sql = "CREATE TABLE IF NOT EXISTS `columnas_alias_global` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `nombre_columna` VARCHAR(100) NOT NULL,
            `alias_columna` VARCHAR(255) NOT NULL,
            UNIQUE KEY `columna_unique` (`nombre_columna`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sql);
    } catch (PDOException $e) {
        die("Error crítico de base de datos al crear la tabla de alias: " . $e->getMessage());
    }
}

// Asegurar que la tabla de alias global exista
createGlobalAliasTable($pdo);

// --- Variables ---
$error = '';
$success_message = '';
$columnas = [];
$aliases_guardados = [];

// --- Lógica para guardar los datos del formulario (cuando se envía con POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aliases_recibidos = $_POST['aliases'] ?? [];

    if (!empty($aliases_recibidos)) {
        try {
            $pdo->beginTransaction();

            // Usamos INSERT ... ON DUPLICATE KEY UPDATE.
            // Esto inserta una nueva fila si no existe, o actualiza la existente si ya hay un alias para esa columna.
            $sql = "INSERT INTO `columnas_alias_global` (nombre_columna, alias_columna)
                    VALUES (:nombre_columna, :alias_columna)
                    ON DUPLICATE KEY UPDATE alias_columna = VALUES(alias_columna)";
            
            $stmt = $pdo->prepare($sql);

            foreach ($aliases_recibidos as $nombre_columna => $alias_columna) {
                $stmt->execute([
                    ':nombre_columna' => $nombre_columna,
                    ':alias_columna' => trim($alias_columna)
                ]);
            }
            
            $pdo->commit();
            // Redirigimos con un parámetro de éxito para mostrar el mensaje de confirmación
            header("Location: gestionar_columnas.php?success=1");
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Error al guardar los cambios: " . $e->getMessage();
        }
    } else {
        $error = "No se recibieron datos válidos para guardar.";
    }
}

// --- Lógica para mostrar mensajes de éxito ---
if(isset($_GET['success'])) {
    $success_message = "✅ Nombres de columnas actualizados exitosamente.";
}

// --- Lógica de carga de datos para mostrar en el formulario ---

// 1. Encontrar la tabla de expedientes más reciente para usar como plantilla de columnas
$tabla_plantilla = '';
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'exptes-%'");
    $tablas_expedientes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if ($tablas_expedientes) {
        rsort($tablas_expedientes); // Ordenar de más reciente a más antiguo
        $tabla_plantilla = $tablas_expedientes[0];
    }
} catch (PDOException $e) {
    $error = "Error al buscar tablas de expedientes: " . $e->getMessage();
}

// 2. Si encontramos una tabla, obtenemos sus columnas y los alias ya guardados
if ($tabla_plantilla) {
    try {
        // Obtener las columnas de la tabla plantilla
        $stmt_cols = $pdo->query("DESCRIBE `{$tabla_plantilla}`");
        $columnas_raw = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);
        $columnas_omitidas = ['id', 'id_expediente', 'usuario', 'fecha_creacion'];
        $columnas = array_diff($columnas_raw, $columnas_omitidas);

        // **PUNTO CLAVE**: Cargar todos los alias globales que ya están en la base de datos
        $stmt_aliases = $pdo->query("SELECT nombre_columna, alias_columna FROM `columnas_alias_global`");
        // Los guardamos en un array asociativo: ['nombre_de_columna' => 'alias_guardado']
        $aliases_guardados = $stmt_aliases->fetchAll(PDO::FETCH_KEY_PAIR);

    } catch (PDOException $e) {
        $error = "Error al leer la estructura de la base de datos: " . $e->getMessage();
        $columnas = [];
    }
}

// Función de ayuda para formatear nombres por defecto (cuando no hay alias)
function format_default_name($name) {
    return ucfirst(str_replace('_', ' ', $name));
}

// Incluir el encabezado de la página
include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Nombres de Columnas Globales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid py-5">
        <h1 class="mb-4">Gestionar Nombres de Columnas Globales</h1>
        <p class="lead">Los nombres que definas aquí se aplicarán a **todas** las tablas de expedientes (`exptes-año`).</p>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($columnas)): ?>
            <div class="alert alert-warning">No se encontró una tabla de expedientes (ej: `exptes-2025`) para usar como plantilla. Por favor, crea una primero.</div>
        <?php else: ?>
            <p class="text-muted">Mostrando columnas basadas en la tabla: <code><?= htmlspecialchars($tabla_plantilla) ?></code></p>
            <form action="gestionar_columnas.php" method="post">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th style="width: 30%;">Nombre Original (en DB)</th>
                                <th>Nombre a Mostrar (Alias Global)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($columnas as $columna): ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($columna) ?></code></td>
                                    <td>
                                        <!-- **PUNTO CLAVE PARA LA EDICIÓN** -->
                                        <!-- El atributo `value` se rellena con el alias guardado. -->
                                        <!-- `($aliases_guardados[$columna] ?? '')` busca el alias para la columna actual. -->
                                        <!-- Si lo encuentra, lo muestra. Si no, muestra un campo vacío. -->
                                        <input type="text" 
                                               name="aliases[<?= htmlspecialchars($columna) ?>]"
                                               class="form-control"
                                               value="<?= htmlspecialchars($aliases_guardados[$columna] ?? '') ?>"
                                               placeholder="<?= htmlspecialchars(format_default_name($columna)) ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">💾 Guardar Cambios</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
