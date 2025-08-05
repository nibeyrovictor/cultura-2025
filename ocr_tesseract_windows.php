<?php
// 1. --- LOGIC FIRST ---
// Always start with session and authentication logic.
require_once 'session_init.php'; // Initializes the session
require_once 'db.php';
require_once 'auth.php';
include 'includes/header.php';

// Check if the user is logged in and redirect if they aren't.
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// Check if the user has permission for this page.
$archivo_actual = basename(__FILE__);
if (!tienePermiso($archivo_actual, $pdo)) {
    header("Location: acceso_denegado.php");
    exit;
}

// Initialize variables for the OCR processing.
$texto_extraido = '';
$error = '';
$nombre_imagen = '';
$imagen_data_uri = ''; // Variable para la imagen codificada

// Process the form submission (this is also logic, so it stays at the top).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['imagen'])) {
    $archivo_imagen = $_FILES['imagen'];

    if ($archivo_imagen['error'] !== UPLOAD_ERR_OK) {
        $error = 'Error al subir el archivo. Código: ' . $archivo_imagen['error'];
    } else {
        $directorio_subidas = 'uploads-ocr/';
        if (!is_dir($directorio_subidas)) {
            mkdir($directorio_subidas, 0777, true);
        }

        $nombre_temporal = $archivo_imagen['tmp_name'];
        $extension = pathinfo($archivo_imagen['name'], PATHINFO_EXTENSION) ?: 'png';
        $nombre_unico = uniqid('img_', true) . '.' . $extension;
        $ruta_destino = $directorio_subidas . $nombre_unico;
        $nombre_imagen = $nombre_unico;

        if (move_uploaded_file($nombre_temporal, $ruta_destino)) {
            try {
                $tesseract_executable = '"C:\Program Files\Tesseract-OCR\tesseract.exe"';
                $ruta_escapada = escapeshellarg($ruta_destino);
                $comando = "{$tesseract_executable} {$ruta_escapada} stdout -l spa";
                $texto_extraido = shell_exec($comando);

                if ($texto_extraido === null) {
                    $error = "Error al ejecutar Tesseract. Verifique la ruta y los permisos de 'shell_exec'.";
                    // Aunque haya error con Tesseract, la imagen debe borrarse
                    if (file_exists($ruta_destino)) {
                        unlink($ruta_destino);
                    }
                } else {
                    // Si todo fue bien, lee la imagen para mostrarla y luego bórrala.
                    if (file_exists($ruta_destino)) {
                        $mime_type = mime_content_type($ruta_destino);
                        $imagen_contenido = file_get_contents($ruta_destino);
                        $imagen_data_uri = 'data:' . $mime_type . ';base64,' . base64_encode($imagen_contenido);
                        unlink($ruta_destino); // ¡Aquí se borra el archivo del servidor!
                    }
                }
            } catch (Exception $e) {
                $error = 'Error al procesar la imagen con Tesseract: ' . $e->getMessage();
                // Asegurarse de borrar el archivo también si hay una excepción
                if (file_exists($ruta_destino)) {
                    unlink($ruta_destino);
                }
            }
        } else {
            $error = 'Error al mover el archivo subido.';
        }
    }
}

// 2. --- HTML OUTPUT SECOND ---
// Now that all logic is done, you can safely include headers and start the HTML.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>OCR con Tesseract en Windows</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Estilos para el contenedor de la cámara */
        #camera-container {
            display: none; /* Oculto por defecto */
            margin-top: 15px;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
        }
        #video {
            width: 100%;
            height: auto;
            border-radius: 5px;
        }
        #click-photo {
            margin-top: 10px;
        }
        #canvas {
            display: none;
        }
    </style>
</head>
<body>


    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h2>Subir Imagen para OCR con Tesseract</h2>
            </div>
            <div class="card-body">
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="imagen" class="form-label">Selecciona una imagen:</label>
                        <input type="file" class="form-control" name="imagen" id="imagen" accept="image/*" required>
                    </div>
                    
                    <button type="button" id="start-camera" class="btn btn-secondary">Usar Cámara</button>
                    <button type="submit" class="btn btn-primary">Extraer Texto</button>

                    <div id="camera-container">
                        <video id="video" playsinline autoplay></video>
                        <button type="button" id="click-photo" class="btn btn-info w-100">Capturar Foto</button>
                    </div>
                    <canvas id="canvas"></canvas>
                </form>
            </div>
        </div>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <hr class="my-4">
            <h3>Resultado del OCR</h3>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($imagen_data_uri): ?>
                <div class="row">
                    <div class="col-md-6">
                        <h4>Imagen Procesada:</h4>
                        <img src="<?php echo $imagen_data_uri; ?>" class="img-fluid rounded" alt="Imagen procesada">
                    </div>
                    <div class="col-md-6">
                         <?php if ($texto_extraido): ?>
                            <div class="card">
                                <div class="card-header">
                                    Texto extraído de <strong><?php echo htmlspecialchars($_FILES['imagen']['name']); ?></strong>
                                </div>
                                <div class="card-body">
                                    <pre><?php echo htmlspecialchars($texto_extraido); ?></pre>
                                </div>
                            </div>
                        <?php elseif (!$error): ?>
                            <div class="alert alert-info mt-3">No se pudo extraer texto de la imagen.</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

<script>
    // Your JavaScript for the camera remains the same.
    document.addEventListener('DOMContentLoaded', function() {
        const startCameraButton = document.getElementById('start-camera');
        const clickPhotoButton = document.getElementById('click-photo');
        const video = document.getElementById('video');
        const canvas = document.getElementById('canvas');
        const cameraContainer = document.getElementById('camera-container');
        const fileInput = document.getElementById('imagen');
        let stream;

        startCameraButton.addEventListener('click', async () => {
            cameraContainer.style.display = 'block';
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                video.srcObject = stream;
            } catch (err) {
                console.error("Error al acceder a la cámara: ", err);
                alert("No se pudo acceder a la cámara. Asegúrate de dar permisos.");
                cameraContainer.style.display = 'none';
            }
        });

        clickPhotoButton.addEventListener('click', () => {
            if (!stream) return;

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob((blob) => {
                const file = new File([blob], "camera_capture.png", { type: "image/png" });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                stopCamera();
                alert('Foto capturada. Ahora puedes hacer clic en "Extraer Texto".');
            }, 'image/png');
        });

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            cameraContainer.style.display = 'none';
        }
    });
</script>
</body>
</html>