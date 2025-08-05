<?php
// db.php


$host = 'localhost';
$dbname = 'sistema_login';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Create the sessions table if it doesn't exist
$pdo->exec("
    CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(255) NOT NULL PRIMARY KEY,
        data TEXT NOT NULL,
        expires INT(11) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Optional: Create users, groups, and permissions_groups tables for the login functions
$pdo->exec("
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        rol ENUM('admin', 'user') DEFAULT 'user',
        grupo_id INT,
        nombre_completo VARCHAR(255),
        genero VARCHAR(50)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS grupos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(255) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS permisos_grupos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grupo_id INT NOT NULL,
        modulo VARCHAR(255) NOT NULL,
        UNIQUE KEY (grupo_id, modulo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Example: Add a user, group, and permission if tables are empty (for testing)
// Check if users table is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
if ($stmt->fetchColumn() == 0) {
    $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
    $pdo->exec("INSERT INTO usuarios (usuario, password, rol, nombre_completo, genero) VALUES ('adminuser', '$hashedPassword', 'admin', 'Admin User', 'Male')");
    $pdo->exec("INSERT INTO usuarios (usuario, password, rol, nombre_completo, genero) VALUES ('testuser', '$hashedPassword', 'user', 'Test User', 'Female')");
}

// Check if groups table is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM grupos");
if ($stmt->fetchColumn() == 0) {
    $pdo->exec("INSERT INTO grupos (nombre) VALUES ('Administrators')");
    $pdo->exec("INSERT INTO grupos (nombre) VALUES ('Users')");
}

// Update existing users with a group_id (assuming 'adminuser' is group 1, 'testuser' is group 2)
$stmt = $pdo->prepare("UPDATE usuarios SET grupo_id = (SELECT id FROM grupos WHERE nombre = 'Administrators') WHERE usuario = 'adminuser'");
$stmt->execute();
$stmt = $pdo->prepare("UPDATE usuarios SET grupo_id = (SELECT id FROM grupos WHERE nombre = 'Users') WHERE usuario = 'testuser'");
$stmt->execute();

// Check if permisos_grupos table is empty
$stmt = $pdo->query("SELECT COUNT(*) FROM permisos_grupos");
if ($stmt->fetchColumn() == 0) {
    $adminGroupId = $pdo->query("SELECT id FROM grupos WHERE nombre = 'Administrators'")->fetchColumn();
    $userGroupId = $pdo->query("SELECT id FROM grupos WHERE nombre = 'Users'")->fetchColumn();

    if ($adminGroupId) {
        $pdo->exec("INSERT INTO permisos_grupos (grupo_id, modulo) VALUES ($adminGroupId, 'dashboard')");
        $pdo->exec("INSERT INTO permisos_grupos (grupo_id, modulo) VALUES ($adminGroupId, 'users')");
        $pdo->exec("INSERT INTO permisos_grupos (grupo_id, modulo) VALUES ($adminGroupId, 'settings')");
    }
    if ($userGroupId) {
        $pdo->exec("INSERT INTO permisos_grupos (grupo_id, modulo) VALUES ($userGroupId, 'dashboard')");
    }
}
?>
