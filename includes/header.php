<?php
// Incluir archivo de autenticación para verificar si el usuario está logeado
// La ruta __DIR__ . '/../auth.php' es una excelente práctica para inclusiones del lado del servidor.
require_once __DIR__ . '/../auth.php'; 
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <title><?php echo $page_title ?? 'Sistema de Login'; ?></title> -->
    
    <link rel="icon" type="image/x-icon" href="../includes/favicon/favicon.ico"> <link href="../css/boot5.37/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/styles.css">
    <link href="../css/boot5.37/node_modules/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

</head>
<body>
    <?php
    // Incluir el menú dinámico
    require_once __DIR__ . '/../menu_dinamico.php';
    ?>

    <script src="../css/boot5.37/js/bootstrap.bundle.min.js"></script>
</body>
</html>