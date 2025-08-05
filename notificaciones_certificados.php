<?php
// includes/notificaciones_certificados.php

// Ensure session, db, and auth are initialized if this file is included directly
// In a typical setup, these would already be included by the main page.
// Adding conditional includes to prevent re-declaration errors if already included.
if (!function_exists('isLoggedIn')) {
    // Correct paths for files in the parent directory
    require_once 'session_init.php';
    require_once 'db.php';
    require_once 'auth.php';
    echo ""; // <-- NEW DEBUG
} else {
    echo ""; // <-- NEW DEBUG
}

// --- DEBUGGING START ---
echo ""; // <-- NEW DEBUG
echo "";
echo "";
// --- DEBUGGING END ---

// Only show notifications if the user is logged in and is an admin
if (isLoggedIn() && isAdmin()) {
    echo ""; // <-- NEW DEBUG
    $expiring_certificates_global = [];
    $current_year = date('Y');

    $years_to_check = [
        $current_year - 1,
        $current_year,
        $current_year + 1
    ];

    foreach ($years_to_check as $year) {
        $nombre_tabla_cert = "cert_349-" . $year;
        $nombre_tabla_exptes = "exptes-" . $year;

        echo ""; // <-- NEW DEBUG

        try {
            $table_cert_exists = $pdo->query("SHOW TABLES LIKE '$nombre_tabla_cert'")->fetch();
            echo ""; // <-- NEW DEBUG

            if (!$table_cert_exists) {
                echo ""; // <-- NEW DEBUG
                continue;
            }

            $table_exptes_exists = $pdo->query("SHOW TABLES LIKE '$nombre_tabla_exptes'")->fetch();
            echo ""; // <-- NEW DEBUG

            $sql_notification = "SELECT c.id, c.fecha_cert_venc, c.id_expediente";
            if ($table_exptes_exists) {
                $sql_notification .= ", e.proveedor, e.caratula";
            }
            $sql_notification .= " FROM `$nombre_tabla_cert` c";
            if ($table_exptes_exists) {
                $sql_notification .= " LEFT JOIN `$nombre_tabla_exptes` e ON c.id_expediente = e.id_expediente";
            }
            $sql_notification .= " WHERE (
                                        (c.fecha_cert_venc >= CURDATE() AND c.fecha_cert_venc <= CURDATE() + INTERVAL 5 DAY)
                                        OR
                                        (c.fecha_cert_venc < CURDATE() AND (c.estado = '' OR c.estado = 'vencido'))
                                      )
                                      ORDER BY c.fecha_cert_venc ASC";

            echo ""; // <-- NEW DEBUG
            $stmt_notification = $pdo->query($sql_notification);
            $year_expiring_certs = $stmt_notification->fetchAll(PDO::FETCH_ASSOC);

            echo ""; // <-- NEW DEBUG

            $expiring_certificates_global = array_merge($expiring_certificates_global, $year_expiring_certs);

        } catch (PDOException $e) {
            error_log("Error checking for expiring certificates in " . htmlspecialchars($nombre_tabla_cert) . ": " . $e->getMessage());
            echo ""; // <-- NEW DEBUG
        }
    }
    echo ""; // <-- NEW DEBUG

    if (!empty($expiring_certificates_global)):
        echo ""; // <-- NEW DEBUG
        ?>
        <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
            <h4>¡Atención! Certificados próximos a vencer o vencidos:</h4>

            <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.min.css">
            <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/3.0.2/css/buttons.dataTables.min.css">

            <table id="expiringCertsTable" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>ID Certificado</th>
                        <th>Expediente ID</th>
                        <th>Proveedor</th>
                        <th>Carátula</th>
                        <th>Vence el</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expiring_certificates_global as $exp_cert):
                        $display_proveedor = htmlspecialchars($exp_cert['proveedor'] ?? 'N/A');
                        $display_caratula = htmlspecialchars($exp_cert['caratula'] ?? 'N/A');
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exp_cert['id']); ?></td>
                            <td><?php echo htmlspecialchars($exp_cert['id_expediente']); ?></td>
                            <td><?php echo $display_proveedor; ?></td>
                            <td><?php echo $display_caratula; ?></td>
                            <td><?php echo date('d-m-Y', strtotime($exp_cert['fecha_cert_venc'])); ?></td>
                            <td>
                                <a href="gestionar_certificados.php?ano=<?php echo date('Y', strtotime($exp_cert['fecha_cert_venc'])); ?>&action=edit&id=<?php echo htmlspecialchars($exp_cert['id']); ?>" class="btn btn-sm btn-outline-warning">Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <script type="text/javascript" src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/dataTables.buttons.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.html5.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
        <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/buttons/3.0.2/js/buttons.print.min.js"></script>


        <script>
            $(document).ready(function() {
                $('#expiringCertsTable').DataTable({
                    "paging": true,
                    "ordering": true,
                    "searching": true,
                    "info": true,
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                    },
                    layout: {
                        topStart: {
                            buttons: ['copy', 'csv', 'excel', 'pdf', 'print']
                        }
                    }
                });
            });
        </script>
    <?php endif;
} else {
    echo ""; // <-- NEW DEBUG
}
?>