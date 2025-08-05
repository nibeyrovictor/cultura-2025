<?php
// --- CONFIGURACIÓN ---
// Ruta base a la carpeta donde está 'node_modules'.
// Corresponde a la URL: /css/boot5.37/
require_once 'session_init.php';
require_once 'db.php';
require_once 'auth.php';

$base_path = 'css/boot5.37/';

// La sincronización ahora solo se ejecuta si se pasa un parámetro en la URL
$mensaje_sincronizacion = '';
if (isset($_GET['sincronizar'])) {
    $mensaje_sincronizacion = sincronizarModulos($pdo);
}

// Verificar si el usuario está autenticado y es administrador
if (!isLoggedIn() || !isAdmin()) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

// Revisar si hay un mensaje de éxito en la sesión (Flash Message)
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Ruta al archivo CSS de los iconos de Bootstrap (para la web)
$icons_css_path = $base_path . 'node_modules/bootstrap-icons/font/bootstrap-icons.css';

// Ruta en el sistema de archivos para leer el listado de iconos.
// Nota: Usamos la ruta del sistema porque PHP necesita leer un archivo del disco.
$icons_json_path = __DIR__ . '/' . $base_path . 'node_modules/bootstrap-icons/font/bootstrap-icons.json';

$iconos = [];
$error = '';

if (file_exists($icons_json_path)) {
    $json_content = file_get_contents($icons_json_path);
    $data = json_decode($json_content, true);
    // Extraemos solo los nombres (las claves del JSON)
    $iconos = array_keys($data);
} else {
    $error = "Error: No se pudo encontrar el archivo <code>bootstrap-icons.json</code>. Verifica que la ruta de configuración en el script sea correcta. Ruta buscada: <strong>" . htmlspecialchars($icons_json_path) . "</strong>";
}


// --- 3. PRESENTACIÓN (HTML) ---
include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Íconos Bootstrap</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="<?php echo htmlspecialchars($icons_css_path); ?>">

    <style>
        :root {
            /* Variable CSS para el color de los íconos. Por defecto, hereda el color del texto. */
            --icon-color: currentColor; 
            /* Variable CSS para el color de fondo de los íconos. Por defecto, es transparente. */
            --icon-bg-color: transparent;
        }
        body {
            font-family: sans-serif;
        }
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
        }
        .icon-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            border-radius: 0.5rem;
            background-color: var(--bs-tertiary-bg);
            cursor: pointer;
            transition: transform 0.2s ease, background-color 0.2s ease;
            text-align: center;
            border: 1px solid var(--bs-border-color);
        }
        .icon-card:hover {
            transform: translateY(-5px);
            background-color: var(--bs-secondary-bg);
        }
        .icon-card i {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            color: var(--icon-color); /* Usa la variable CSS para el color */
            background-color: var(--icon-bg-color); /* Variable para el fondo */
            transition: color 0.2s ease, background-color 0.2s ease; /* Transición suave */
            padding: 0.5rem; /* Padding para que el fondo sea visible */
            border-radius: 0.375rem; /* Bordes redondeados para el fondo */
        }
        .icon-card span {
            font-size: 0.8rem;
            word-break: break-all;
        }
        .sticky-top {
            background: var(--bs-dark);
            padding-top: 1rem;
            padding-bottom: 1rem;
            z-index: 1000;
        }
        .toast-container {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            z-index: 1100;
        }
        .customization-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
            align-items: center;
        }
    </style>
</head>
<body>

    <div class="container my-4">
        <header class="text-center mb-4">
            <h1 class="display-5">Visor de Íconos de Bootstrap</h1>
            <p class="lead">Haz clic en un ícono para copiar su código HTML o personaliza su apariencia.</p>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php else: ?>
            <div class="sticky-top">
                <input type="text" id="searchInput" class="form-control form-control-lg" placeholder="🔍 Buscar más de <?php echo count($iconos); ?> íconos...">
                
                <div class="customization-bar">
                    <label for="iconColorPicker" class="form-label mb-0">Color Ícono:</label>
                    <input type="color" class="form-control form-control-color" id="iconColorPicker" value="#ffffff" title="Elige un color para los íconos">
                    <button id="resetColorBtn" class="btn btn-outline-secondary btn-sm">Reset</button>
                    
                    <label for="iconBgColorPicker" class="form-label mb-0 ms-md-3">Color Fondo:</label>
                    <input type="color" class="form-control form-control-color" id="iconBgColorPicker" value="#1a1a1a" title="Elige un color de fondo para los íconos">
                    <button id="resetBgColorBtn" class="btn btn-outline-secondary btn-sm">Reset</button>
                </div>
            </div>

            <div id="iconGrid" class="icon-grid mt-4">
                <?php foreach ($iconos as $nombre_icono): ?>
                    <div class="icon-card" data-name="<?php echo htmlspecialchars($nombre_icono); ?>" onclick="copyToClipboard('<?php echo htmlspecialchars($nombre_icono); ?>')">
                        <i class="bi bi-<?php echo htmlspecialchars($nombre_icono); ?>"></i>
                        <span><?php echo htmlspecialchars($nombre_icono); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="toast-container">
        <div id="copyToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ¡Código del ícono copiado!
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

  

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const iconGrid = document.getElementById('iconGrid');
            const iconCards = iconGrid.getElementsByClassName('icon-card');
            const toastElement = document.getElementById('copyToast');
            const toast = new bootstrap.Toast(toastElement);
            const root = document.documentElement;

            // --- Lógica de personalización de color de ícono ---
            const colorPicker = document.getElementById('iconColorPicker');
            const resetColorBtn = document.getElementById('resetColorBtn');

            colorPicker.addEventListener('input', function() {
                root.style.setProperty('--icon-color', this.value);
            });

            resetColorBtn.addEventListener('click', function() {
                root.style.removeProperty('--icon-color');
                colorPicker.value = '#ffffff'; 
            });

            // --- Lógica de personalización de color de fondo ---
            const bgColorPicker = document.getElementById('iconBgColorPicker');
            const resetBgColorBtn = document.getElementById('resetBgColorBtn');

            bgColorPicker.addEventListener('input', function() {
                root.style.setProperty('--icon-bg-color', this.value);
            });

            resetBgColorBtn.addEventListener('click', function() {
                root.style.removeProperty('--icon-bg-color');
                // El valor por defecto de un input[type=color] es #000000, 
                // pero lo ajustamos a uno más acorde al tema oscuro inicial.
                bgColorPicker.value = '#1a1a1a';
            });
            
            // --- Función de búsqueda ---
            searchInput.addEventListener('keyup', function () {
                const filter = searchInput.value.toLowerCase();
                for (let card of iconCards) {
                    const name = card.dataset.name.toLowerCase();
                    if (name.includes(filter)) {
                        card.style.display = "";
                    } else {
                        card.style.display = "none";
                    }
                }
            });

            // --- Función de copiado global ---
            window.copyToClipboard = function(iconName) {
                const iconColor = root.style.getPropertyValue('--icon-color').trim();
                const iconBgColor = root.style.getPropertyValue('--icon-bg-color').trim();
                
                const styles = [];

                // Añadir color de ícono si está personalizado
                if (iconColor && iconColor !== 'currentColor') {
                    styles.push(`color: ${iconColor};`);
                }

                // Añadir color de fondo si está definido
                if (iconBgColor && iconBgColor !== 'transparent') {
                    styles.push(`background-color: ${iconBgColor};`);
                    // Añadir estilos complementarios para que el fondo se vea bien
                    styles.push('padding: 0.5rem;');
                    styles.push('border-radius: 0.375rem;');
                }

                let styleAttribute = '';
                if (styles.length > 0) {
                    styleAttribute = ` style="${styles.join(' ')}"`;
                }
                
                const textToCopy = `<i class="bi bi-${iconName}"${styleAttribute}></i>`;
                
                navigator.clipboard.writeText(textToCopy).then(function() {
                    toast.show();
                }, function(err) {
                    console.error('Error al copiar: ', err);
                });
            }
        });
    </script>

</body>
</html>