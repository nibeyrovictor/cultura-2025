<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    
    // Rutas relativas al script actual
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . uniqid() . '-' . basename($_FILES['image']['name']);
    $resultUrl = '';
    $errorUrl = '';

    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
        
        // ¡¡¡IMPORTANTE!!!
        // Cambia esta ruta a la ubicación de tu python.exe. 
        // Ejecuta "where python" en tu terminal (cmd) para encontrarla.
        $pythonPath = 'C:\Users\TuUsuario\AppData\Local\Programs\Python\Python39\python.exe'; 
        
        $scriptPath = 'recognize_faces.py'; // El script de Python está en la misma carpeta
        
        $imagePath = escapeshellarg($uploadFile);
        $command = escapeshellcmd("$pythonPath $scriptPath $imagePath");

        // Ejecutar el script y capturar la salida
        $output = shell_exec($command);
        $recognizedName = trim($output);

        // Preparar la URL para el resultado
        $imageUrlParam = urlencode($uploadFile);
        if ($recognizedName) {
            $resultUrl = 'formulario.php?result=' . urlencode($recognizedName) . '&image=' . $imageUrlParam;
        } else {
            $errorUrl = 'formulario.php?error=' . urlencode('No se pudo obtener una respuesta del script de reconocimiento.');
        }

    } else {
        $errorUrl = 'formulario.php?error=' . urlencode('Error al subir la imagen.');
    }

    // Redirigir de vuelta al formulario
    if (!empty($resultUrl)) {
        header('Location: ' . $resultUrl);
    } else {
        header('Location: ' . $errorUrl);
    }
    exit();
}
?>