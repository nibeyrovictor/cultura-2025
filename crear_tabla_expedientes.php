<?php
require_once 'session_init.php';
require_once 'db.php';
require_once 'auth.php';

// Proteger la página para que solo administradores puedan acceder
if (!isLoggedIn() || !isAdmin()) {
    header("Location: acceso_denegado.php");
    exit;
}

$success = '';
$error = '';
$created_tables = [];

// Obtener el año del formulario o el año actual por defecto
$ano_seleccionado = isset($_POST['ano_expediente']) ? intval($_POST['ano_expediente']) : date('Y');

// Si se ha enviado el formulario para crear las tablas
if (isset($_POST['crear_tablas_anuales'])) {
    // Nombres de las tablas a crear
    $nombre_tabla_exptes = "exptes-" . $ano_seleccionado;
    $nombre_tabla_relacion = "expediente_proveedor_monto-" . $ano_seleccionado;
    $nombre_tabla_cert = "cert_349-" . $ano_seleccionado;
    $nombre_tabla_rel_expte = "rel_expte-" . $ano_seleccionado; // <-- Nueva tabla de relación

    try {
        // NOTE: Removed transaction logic (beginTransaction, commit, rollBack)
        // DDL statements like CREATE TABLE cause implicit commits in MySQL,
        // which makes an explicit transaction wrapper problematic and causes the error.

        // 1. Tabla central de Proveedores (siempre se verifica)
        $sql_proveedores = "CREATE TABLE IF NOT EXISTS `proveedores` (
            `id_proveedor` INT AUTO_INCREMENT PRIMARY KEY,
            `nombre_proveedor` VARCHAR(255) NOT NULL,
            `cuit` VARCHAR(20) UNIQUE NOT NULL,
            `domicilio` TEXT,
            `estado` ENUM('Activo', 'Inactivo') DEFAULT 'Activo',
            `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql_proveedores);
        $created_tables[] = 'proveedores';

        // 2. Tabla de Expedientes para el año seleccionado
        $sql_exptes = "CREATE TABLE IF NOT EXISTS `$nombre_tabla_exptes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `organismo` VARCHAR(255) NULL,
            `numero` VARCHAR(50) NOT NULL,
            `año` YEAR(4) NOT NULL,
            `cuerpo` TEXT NULL,
            `dependencia` TEXT NULL,
            `id_expediente` VARCHAR(255) GENERATED ALWAYS AS (CONCAT_WS('-', `organismo`, `numero`, `año`, `cuerpo`)) STORED,
            `caratula` TEXT NULL,
            `observaciones` TEXT NULL,
            `resolucion` VARCHAR(100) NULL,
            `decreto` VARCHAR(100) NULL,
            `usuario` TEXT NULL,
            `estado` VARCHAR(50) NULL,
            `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql_exptes);
        $created_tables[] = $nombre_tabla_exptes;

        // 3. Tabla de Relación Expediente-Proveedor-Monto para el año seleccionado
        $sql_exp_prov_monto = "CREATE TABLE IF NOT EXISTS `{$nombre_tabla_relacion}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `id_expediente` VARCHAR(255) NOT NULL,
            `nombre_proveedor` VARCHAR(255) NOT NULL,
            `tipo_periodo` VARCHAR(20) DEFAULT 'mes',
            `mes` VARCHAR(7) NULL, /* Formato YYYY-MM */
            `fecha_exacta` DATE NULL,
            `periodo_desde` DATE NULL,
            `periodo_hasta` DATE NULL,
            `monto` DECIMAL(15, 2) NOT NULL,
            UNIQUE KEY `expediente_proveedor_mes` (`id_expediente`, `nombre_proveedor`, `mes`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql_exp_prov_monto);
        $created_tables[] = $nombre_tabla_relacion;

        // 4. Tabla de Certificados 349 para el año seleccionado (con cuit_proveedor)
        $sql_cert = "CREATE TABLE IF NOT EXISTS `{$nombre_tabla_cert}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `fecha_cert_venc` DATE NOT NULL,
            `id_expediente` VARCHAR(255) NOT NULL,
            `cuit_proveedor` VARCHAR(20) NULL,
            `estado` VARCHAR(100) NULL,
            `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql_cert);
        $created_tables[] = $nombre_tabla_cert;

        // 5. NUEVA: Tabla de Relación entre Expedientes para el año seleccionado
        $sql_rel_expte = "CREATE TABLE IF NOT EXISTS `{$nombre_tabla_rel_expte}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `id_expediente` VARCHAR(255) NOT NULL,
            `id_expediente_rel` VARCHAR(255) NOT NULL,
            `observaciones` TEXT NULL,
            UNIQUE KEY `relacion_unica` (`id_expediente`, `id_expediente_rel`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql_rel_expte);
        $created_tables[] = $nombre_tabla_rel_expte;


        $success = "Proceso completado. Las siguientes tablas fueron creadas o verificadas exitosamente: <strong>" . implode(', ', array_map('htmlspecialchars', $created_tables)) . "</strong>.";

    } catch (PDOException $e) {
        $error = "Error durante la creación de tablas: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Tablas de la Base de Datos</title>
    <?php include 'includes/header.php'; // Asumiendo que aquí cargas Bootstrap y otros estilos ?>
</head>

<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h2>Gestión de Tablas de la Base de Datos</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="post" class="mb-4">
                            <h4 class="mb-3">Configurar Tablas para un Año</h4>
                            <p>
                                Selecciona un año y presiona el botón para crear o verificar todas las tablas necesarias
                                (<code>proveedores</code>, <code>exptes-AÑO</code>, <code>expediente_proveedor_monto-AÑO</code>, <code>cert_349-AÑO</code> y <code>rel_expte-AÑO</code>)
                                para ese período.
                            </p>
                            <div class="mb-3">
                                <label for="ano_expediente" class="form-label">Año a Configurar:</label>
                                <input type="number" class="form-control" id="ano_expediente" name="ano_expediente" value="<?php echo htmlspecialchars($ano_seleccionado); ?>" min="2000" max="2100" required>
                            </div>
                            <button type="submit" name="crear_tablas_anuales" class="btn btn-primary w-100">
                                Crear/Verificar Tablas para el Año <?php echo htmlspecialchars($ano_seleccionado); ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>