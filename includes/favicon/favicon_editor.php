<?php
// Define the upload directory and the target favicon path
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('FAVICON_PATH', __DIR__ . '/favicon.ico');
define('UPLOAD_URL', 'uploads/');

$message = '';
$uploaded_image_path = '';
$temp_filename = '';

// Ensure the upload directory exists
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// --- Handle Image Deletion ---
if (isset($_GET['delete'])) {
    $file_to_delete = basename($_GET['delete']);
    $file_path_to_delete = UPLOAD_DIR . $file_to_delete;
    if (file_exists($file_path_to_delete)) {
        if (unlink($file_path_to_delete)) {
            $message = '<div class="alert alert-warning">Imagen eliminada exitosamente.</div>';
        }
    }
}

// --- Handle Favicon.ico Deletion ---
if (isset($_GET['delete_favicon'])) {
    if (file_exists(FAVICON_PATH)) {
        if (unlink(FAVICON_PATH)) {
            $message = '<div class="alert alert-warning">El archivo favicon.ico ha sido eliminado.</div>';
        }
    }
}

// --- Handle Image Rename ---
if (isset($_GET['rename_old']) && isset($_GET['rename_new'])) {
    $old_name = basename($_GET['rename_old']);
    $new_name = basename($_GET['rename_new']);
    $old_path = UPLOAD_DIR . $old_name;
    $new_path = UPLOAD_DIR . $new_name;
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $new_extension = strtolower(pathinfo($new_name, PATHINFO_EXTENSION));

    if (empty($new_name)) {
        $message = '<div class="alert alert-danger">El nuevo nombre no puede estar vacío.</div>';
    } elseif (!in_array($new_extension, $allowed_extensions)) {
        $message = '<div class="alert alert-danger">El nuevo nombre tiene una extensión no permitida.</div>';
    } elseif (!file_exists($old_path)) {
        $message = '<div class="alert alert-danger">La imagen que intentas renombrar no existe.</div>';
    } elseif (file_exists($new_path)) {
        $message = '<div class="alert alert-danger">Ya existe una imagen con ese nombre.</div>';
    } else {
        if (rename($old_path, $new_path)) {
            $message = '<div class="alert alert-success">Imagen renombrada de <strong>' . htmlspecialchars($old_name) . '</strong> a <strong>' . htmlspecialchars($new_name) . '</strong>.</div>';
        } else {
            $message = '<div class="alert alert-danger">Ocurrió un error al renombrar la imagen.</div>';
        }
    }
}

// --- Handle Image Upload ---
if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
    $file_tmp_name = $_FILES['image_upload']['tmp_name'];
    $file_name = basename($_FILES['image_upload']['name']);
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($file_extension, $allowed_extensions)) {
        $message = '<div class="alert alert-danger">Error: Solo se permiten archivos JPG, JPEG, PNG y GIF.</div>';
    } else {
        $temp_filename = uniqid('img_', true) . '.' . $file_extension;
        $uploaded_image_path = UPLOAD_DIR . $temp_filename;
        if (move_uploaded_file($file_tmp_name, $uploaded_image_path)) {
            $message = '<div class="alert alert-success">Imagen subida. Ahora puedes recortarla.</div>';
        } else {
            $message = '<div class="alert alert-danger">Error al subir la imagen.</div>';
        }
    }
}

// --- Handle Image Cropping ---
if (isset($_POST['crop_image']) && !empty($_POST['image_path'])) {
    $source_image_path = $_POST['image_path'];
    $x = (int)$_POST['x'];
    $y = (int)$_POST['y'];
    $width = (int)$_POST['width'];
    $height = (int)$_POST['height'];
    $shape = $_POST['favicon_shape'] ?? 'square';

    if (!file_exists($source_image_path) || strpos($source_image_path, UPLOAD_DIR) !== 0) {
        $message = '<div class="alert alert-danger">Error: Ruta de imagen inválida.</div>';
    } else if ($width <= 0 || $height <= 0) {
        $message = '<div class="alert alert-danger">Error: El recorte debe tener un tamaño válido.</div>';
    } else {
        list(, , $image_type) = getimagesize($source_image_path);
        $source_image = null;
        switch ($image_type) {
            case IMAGETYPE_JPEG: $source_image = imagecreatefromjpeg($source_image_path); break;
            case IMAGETYPE_PNG: $source_image = imagecreatefrompng($source_image_path); break;
            case IMAGETYPE_GIF: $source_image = imagecreatefromgif($source_image_path); break;
        }

        if ($source_image) {
            $favicon_size = 64;
            $cropped_image = imagecreatetruecolor($favicon_size, $favicon_size);
            
            imagealphablending($cropped_image, false);
            imagesavealpha($cropped_image, true);
            $transparent = imagecolorallocatealpha($cropped_image, 255, 255, 255, 127);
            imagefilledrectangle($cropped_image, 0, 0, $favicon_size, $favicon_size, $transparent);
            
            imagecopyresampled($cropped_image, $source_image, 0, 0, $x, $y, $favicon_size, $favicon_size, $width, $height);
            
            applyShapeMask($cropped_image, $shape, $favicon_size, $favicon_size);

            if (imagepng($cropped_image, FAVICON_PATH)) {
                $message = '<div class="alert alert-success">¡Favicon creado exitosamente!</div>';
                if (file_exists($source_image_path)) { unlink($source_image_path); }
                $uploaded_image_path = '';
            } else {
                $message = '<div class="alert alert-danger">Error al guardar el favicon.</div>';
            }
            imagedestroy($source_image);
            imagedestroy($cropped_image);
        } else {
            $message = '<div class="alert alert-danger">Tipo de imagen no soportado para recorte.</div>';
        }
    }
}

/**
 * Aplica una máscara alfa a una imagen para crear transparencias.
 */
function imagealphamask(&$picture, $mask) {
    $mask_width = imagesx($mask);
    $mask_height = imagesy($mask);
    for ($x = 0; $x < $mask_width; $x++) {
        for ($y = 0; $y < $mask_height; $y++) {
            $alpha = imagecolorsforindex($mask, imagecolorat($mask, $x, $y));
            $alpha = 127 - floor($alpha['red'] / 2);
            $color = imagecolorsforindex($picture, imagecolorat($picture, $x, $y));
            $new_color = imagecolorallocatealpha($picture, $color['red'], $color['green'], $color['blue'], $alpha);
            imagesetpixel($picture, $x, $y, $new_color);
        }
    }
}

/**
 * Crea la forma deseada (círculo, etc.) y la aplica como máscara.
 */
function applyShapeMask(&$image, $shape, $width, $height) {
    if ($shape === 'square') {
        return;
    }
    $mask = imagecreatetruecolor($width, $height);
    $black = imagecolorallocate($mask, 0, 0, 0);
    $white = imagecolorallocate($mask, 255, 255, 255);
    imagefill($mask, 0, 0, $black);

    switch ($shape) {
        case 'rounded':
            $radius = $width / 5;
            imagefilledrectangle($mask, $radius, 0, $width - $radius - 1, $height - 1, $white);
            imagefilledrectangle($mask, 0, $radius, $width - 1, $height - $radius - 1, $white);
            imagefilledellipse($mask, $radius, $radius, $radius*2, $radius*2, $white);
            imagefilledellipse($mask, $width - $radius - 1, $radius, $radius*2, $radius*2, $white);
            imagefilledellipse($mask, $radius, $height - $radius - 1, $radius*2, $radius*2, $white);
            imagefilledellipse($mask, $width - $radius - 1, $height - $radius - 1, $radius*2, $radius*2, $white);
            break;
        case 'circle':
            imagefilledellipse($mask, $width / 2, $height / 2, $width, $height, $white);
            break;
        case 'oval':
            imagefilledellipse($mask, $width / 2, $height / 2, $width * 0.8, $height, $white);
            break;
    }
    imagealphamask($image, $mask);
    imagedestroy($mask);
}

$image_files = glob(UPLOAD_DIR . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
$favicon_exists = file_exists(FAVICON_PATH);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editor de Favicon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { box-shadow: 0 2px 4px rgba(0,0,0,.1); }
        #imageContainer { position: relative; max-width: 600px; margin-top: 20px; border: 1px solid #ddd; overflow: hidden; user-select: none; }
        #imageContainer img { display: block; max-width: 100%; height: auto; }
        #cropHandle { position: absolute; border: 2px dashed #007bff; cursor: move; resize: both; overflow: hidden; background-color: rgba(0, 123, 255, 0.1); transition: border-radius 0.3s ease-in-out; }
        #cropHandle::after { content: ''; position: absolute; right: 0; bottom: 0; width: 10px; height: 10px; background: #007bff; opacity: 0.75; cursor: nwse-resize; }
        .manager-thumbnail { width: 60px; height: 60px; object-fit: cover; margin-right: 15px; border-radius: 4px; border: 1px solid #ddd; }
        #cropHandle.shape-rounded { border-radius: 15%; }
        #cropHandle.shape-circle { border-radius: 50%; }
        #cropHandle.shape-oval { border-radius: 50% / 40%; }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <h1><i class="bi bi-gem"></i> Editor de Favicon</h1>
        <p>Sube una imagen, recórtala, elige una forma y crea el `favicon.ico` para tu sitio.</p>

        <?php echo $message; ?>

        <div class="card mb-4">
            <div class="card-header">1. Subir Nueva Imagen</div>
            <div class="card-body">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="mb-3"><label for="imageUpload" class="form-label">Seleccionar Imagen:</label><input class="form-control" type="file" id="imageUpload" name="image_upload" accept="image/jpeg,image/png,image/gif" required></div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-upload"></i> Subir Imagen</button>
                </form>
            </div>
        </div>

        <?php if ($uploaded_image_path && file_exists($uploaded_image_path)): ?>
            <div class="card mb-4">
                <div class="card-header">2. Recortar y Dar Forma a la Imagen</div>
                <div class="card-body">
                    <div id="imageContainer">
                        <img id="imageToCrop" src="<?php echo htmlspecialchars(UPLOAD_URL . $temp_filename); ?>" alt="Imagen para recortar">
                        <div id="cropHandle"></div>
                    </div>
                    <form action="" method="post" class="mt-3">
                        <div class="my-3">
                            <label class="form-label fw-bold">Elige la forma del Favicon:</label>
                            <div class="d-flex flex-wrap">
                                <div class="form-check form-check-inline me-3"><input class="form-check-input" type="radio" name="favicon_shape" id="shape_square" value="square" checked><label class="form-check-label" for="shape_square">Cuadrado</label></div>
                                <div class="form-check form-check-inline me-3"><input class="form-check-input" type="radio" name="favicon_shape" id="shape_rounded" value="rounded"><label class="form-check-label" for="shape_rounded">Redondeado</label></div>
                                <div class="form-check form-check-inline me-3"><input class="form-check-input" type="radio" name="favicon_shape" id="shape_circle" value="circle"><label class="form-check-label" for="shape_circle">Circular</label></div>
                                <div class="form-check form-check-inline"><input class="form-check-input" type="radio" name="favicon_shape" id="shape_oval" value="oval"><label class="form-check-label" for="shape_oval">Ovalado</label></div>
                            </div>
                        </div>
                        <input type="hidden" name="image_path" value="<?php echo htmlspecialchars($uploaded_image_path); ?>">
                        <input type="hidden" id="cropX" name="x"> <input type="hidden" id="cropY" name="y"> <input type="hidden" id="cropWidth" name="width"> <input type="hidden" id="cropHeight" name="height">
                        <button type="submit" name="crop_image" class="btn btn-success"><i class="bi bi-scissors"></i> Crear Favicon con esta Selección</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($favicon_exists): ?>
        <div class="card mb-4">
            <div class="card-header">Favicon Actual</div>
            <div class="card-body">
                <ul class="list-group"><li class="list-group-item d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <img src="favicon.ico?v=<?php echo time(); ?>" alt="Favicon Actual" class="manager-thumbnail">
                        <span class="ms-2">favicon.ico</span>
                    </div>
                    <div>
                        <a href="favicon.ico" class="btn btn-secondary btn-sm" download><i class="bi bi-download"></i> Descargar</a>
                        <a href="?delete_favicon=true" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro?');"><i class="bi bi-trash-fill"></i> Eliminar</a>
                    </div>
                </li></ul>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">Gestor de Imágenes Subidas</div>
            <div class="card-body">
                <?php if (!empty($image_files)): ?>
                    <ul class="list-group">
                        <?php foreach ($image_files as $image): 
                            $filename = basename($image);
                            $file_url = UPLOAD_URL . $filename;
                            $file_parts = pathinfo($filename);
                            $basename = $file_parts['filename'];
                            $extension = isset($file_parts['extension']) ? '.' . $file_parts['extension'] : '';
                        ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                                <div class="d-flex align-items-center me-3 mb-2 mb-md-0">
                                    <img src="<?php echo htmlspecialchars($file_url); ?>" alt="<?php echo htmlspecialchars($filename); ?>" class="manager-thumbnail">
                                    <span class="ms-2 text-break"><?php echo htmlspecialchars($basename); ?><small class="text-muted"><?php echo htmlspecialchars($extension); ?></small></span>
                                </div>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-info btn-sm" onclick="renameImage('<?php echo htmlspecialchars($filename, ENT_QUOTES); ?>')"><i class="bi bi-pencil"></i> Renombrar</button>
                                    <a href="<?php echo htmlspecialchars($file_url); ?>" class="btn btn-secondary btn-sm" download><i class="bi bi-download"></i> Descargar</a>
                                    <a href="?delete=<?php echo urlencode($filename); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro?');"><i class="bi bi-trash-fill"></i> Eliminar</a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-center text-muted">No hay imágenes temporales subidas.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function renameImage(currentName) {
            const dotIndex = currentName.lastIndexOf(".");
            const baseName = (dotIndex === -1) ? currentName : currentName.substring(0, dotIndex);
            const extension = (dotIndex === -1) ? '' : currentName.substring(dotIndex);
            const newBaseName = prompt("Ingresa el nuevo nombre (sin la extensión):", baseName);
            if (newBaseName && newBaseName.trim() !== '' && newBaseName.trim() !== baseName) {
                const newName = newBaseName.trim() + extension;
                window.location.href = `?rename_old=${encodeURIComponent(currentName)}&rename_new=${encodeURIComponent(newName)}`;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const imageToCrop = document.getElementById('imageToCrop');
            if (!imageToCrop) return;

            const cropHandle = document.getElementById('cropHandle');
            const shapeRadios = document.querySelectorAll('input[name="favicon_shape"]');

            shapeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    cropHandle.classList.remove('shape-rounded', 'shape-circle', 'shape-oval');
                    if (this.value !== 'square') {
                        cropHandle.classList.add('shape-' + this.value);
                    }
                });
            });

            const cropX = document.getElementById('cropX'), cropY = document.getElementById('cropY');
            const cropWidth = document.getElementById('cropWidth'), cropHeight = document.getElementById('cropHeight');
            let isDragging = false, startX, startY, originalCropX, originalCropY;

            const onImageLoad = () => {
                const imgWidth = imageToCrop.offsetWidth, imgHeight = imageToCrop.offsetHeight;
                let initialSize = Math.min(imgWidth, imgHeight, 128);
                cropHandle.style.width = `${initialSize}px`;
                cropHandle.style.height = `${initialSize}px`;
                cropHandle.style.left = `${(imgWidth - initialSize) / 2}px`;
                cropHandle.style.top = `${(imgHeight - initialSize) / 2}px`;
                setTimeout(updateCropValues, 50);
            };
            
            if (imageToCrop.complete && imageToCrop.naturalHeight > 0) onImageLoad();
            else imageToCrop.addEventListener('load', onImageLoad);

            new ResizeObserver(updateCropValues).observe(cropHandle);

            cropHandle.addEventListener('mousedown', (e) => {
                if (e.offsetX > cropHandle.clientWidth - 15 && e.offsetY > cropHandle.clientHeight - 15) return;
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                originalCropX = cropHandle.offsetLeft;
                originalCropY = cropHandle.offsetTop;
                document.body.style.cursor = 'move';
                e.preventDefault();
            });

            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                let newX = originalCropX + (e.clientX - startX);
                let newY = originalCropY + (e.clientY - startY);
                newX = Math.max(0, Math.min(newX, imageToCrop.offsetWidth - cropHandle.offsetWidth));
                newY = Math.max(0, Math.min(newY, imageToCrop.offsetHeight - cropHandle.offsetHeight));
                cropHandle.style.left = `${newX}px`;
                cropHandle.style.top = `${newY}px`;
                updateCropValues();
            });

            document.addEventListener('mouseup', () => {
                isDragging = false;
                document.body.style.cursor = 'default';
            });

            function updateCropValues() {
                if (!imageToCrop.naturalWidth || imageToCrop.naturalWidth === 0) return;
                const scaleX = imageToCrop.naturalWidth / imageToCrop.offsetWidth;
                const scaleY = imageToCrop.naturalHeight / imageToCrop.offsetHeight;
                cropX.value = Math.round(cropHandle.offsetLeft * scaleX);
                cropY.value = Math.round(cropHandle.offsetTop * scaleY);
                cropWidth.value = Math.round(cropHandle.offsetWidth * scaleX);
                cropHeight.value = Math.round(cropHandle.offsetHeight * scaleY);
            }
        });
    </script>
</body>
</html>