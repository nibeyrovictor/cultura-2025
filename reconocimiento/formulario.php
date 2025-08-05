<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reconocimiento Facial - Cultura 2025</title>
    <link rel="stylesheet" href="../css/estilos.css"> 
    <style>
        body { text-align: center; margin-top: 50px; }
        .container { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        .result { margin-top: 20px; font-size: 1.5em; font-weight: bold; }
        img.preview { max-width: 300px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Módulo de Reconocimiento Facial</h1>
        <p>Sube una imagen para identificar a una persona registrada.</p>
        
        <form action="procesar.php" method="post" enctype="multipart/form-data">
            <input type="file" name="image" accept="image/*" required>
            <button type="submit" style="padding: 10px 20px; font-size: 1em; margin-top: 10px;">Reconocer</button>
        </form>

        <?php if (isset($_GET['result'])): ?>
            <div class="result">
                <h3>Resultado: <span style="color: #007bff;"><?php echo htmlspecialchars(urldecode($_GET['result'])); ?></span></h3>
                <?php if (isset($_GET['image'])): ?>
                    <img src="<?php echo htmlspecialchars(urldecode($_GET['image'])); ?>" alt="Imagen analizada" class="preview">
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="result" style="color: red;">
                <h3>Error:</h3>
                <p><?php echo htmlspecialchars(urldecode($_GET['error'])); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>