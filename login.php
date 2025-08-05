<?php
// Incluir archivos necesarios
require_once 'db.php';
require_once 'auth.php';
require_once 'session_init.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $password = $_POST['password'];

    if (login($usuario, $password, $pdo)) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        body {
            background: linear-gradient(to right, #f8f9fa, #e9ecef);
        }
        .login-container {
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 1rem;
        }
        .logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center login-container">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg p-4">
                    <div class="card-body">
                        <div class="text-center">
                            <img src="https://via.placeholder.com/100" alt="Logo" class="logo">
                            <h3 class="mb-2">Bienvenido</h3>
                            <p class="text-muted mb-4">Inicia sesión para continuar</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <div><?php echo $error; ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Usuario" required>
                                    <label for="usuario">Usuario</label>
                                </div>
                            </div>

                            <div class="input-group mb-3">
                                <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                <div class="form-floating">
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                                    <label for="password">Contraseña</label>
                                </div>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bi bi-eye-slash-fill"></i>
                                </button>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">
                                        Recordarme
                                    </label>
                                </div>
                                <a href="#" class="text-decoration-none">¿Olvidaste tu contraseña?</a>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Iniciar Sesión</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
    // Script para mostrar/ocultar contraseña
    const togglePassword = document.querySelector('#togglePassword');
    const password = document.querySelector('#password');
    const eyeIcon = togglePassword.querySelector('i');

    togglePassword.addEventListener('click', function (e) {
        // Cambiar tipo de input
        const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
        password.setAttribute('type', type);
        
        // Cambiar ícono del ojo
        eyeIcon.classList.toggle('bi-eye-fill');
        eyeIcon.classList.toggle('bi-eye-slash-fill');
    });
</script>

</body>
</html>