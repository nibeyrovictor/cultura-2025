<?php

/**
 * Sincroniza los archivos .php del proyecto con la tabla `modulos` de la base de datos.
 *
 * @param PDO $pdo La conexión a la base de datos.
 * @return string El resultado de la operación en formato HTML.
 */
function sincronizarModulos(PDO $pdo)
{
    // 1. Asegurar que la tabla tenga la estructura correcta
    $pdo->exec("CREATE TABLE IF NOT EXISTS modulos (
        id VARCHAR(500) PRIMARY KEY,
        ruta VARCHAR(500) NOT NULL,
        nombre_personalizado VARCHAR(255) NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Función interna para buscar archivos .php recursivamente
    $buscar_php = function($dir) use (&$buscar_php) {
        $archivos = [];
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $ruta = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($ruta)) {
                $archivos = array_merge($archivos, $buscar_php($ruta));
            } elseif (is_file($ruta) && strtolower(pathinfo($ruta, PATHINFO_EXTENSION)) === 'php') {
                $archivos[] = $ruta;
            }
        }
        return $archivos;
    };

    // 2. Obtener módulos del sistema de archivos
    $base = realpath(__DIR__);
    $archivos_en_disco_raw = $buscar_php($base);
    $archivos_en_disco = [];
    foreach ($archivos_en_disco_raw as $file) {
        $ruta_rel = ltrim(str_replace($base, '', $file), DIRECTORY_SEPARATOR . '/');
        $ruta_rel = str_replace(DIRECTORY_SEPARATOR, '/', $ruta_rel);
        $archivos_en_disco[$ruta_rel] = true;
    }

    // 3. Obtener módulos de la base de datos
    $stmt = $pdo->query("SELECT id, id FROM modulos");
    $modulos_en_db = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 4. Encontrar y agregar módulos nuevos
    $nuevos = array_diff_key($archivos_en_disco, $modulos_en_db);
    $count_nuevos = 0;
    if (!empty($nuevos)) {
        $insert = $pdo->prepare("INSERT INTO modulos (id, ruta, nombre_personalizado) VALUES (:id, :ruta, NULL)");
        foreach (array_keys($nuevos) as $ruta_rel) {
            $insert->execute(['id' => $ruta_rel, 'ruta' => $ruta_rel]);
            $count_nuevos++;
        }
    }

    // 5. Encontrar y eliminar módulos obsoletos
    $obsoletos = array_diff_key($modulos_en_db, $archivos_en_disco);
    $count_obsoletos = 0;
    if (!empty($obsoletos)) {
        $delete = $pdo->prepare("DELETE FROM modulos WHERE id = :id");
        foreach (array_keys($obsoletos) as $ruta_rel) {
            $delete->execute(['id' => $ruta_rel]);
            $count_obsoletos++;
        }
    }

    // 6. Devolver el resultado como una cadena de texto HTML
    $resultado  = "<strong>Sincronización completada.</strong><br>";
    $resultado .= "<span style='color: green;'>✔ $count_nuevos módulos nuevos fueron añadidos.</span><br>";
    $resultado .= "<span style='color: red;'>✖ $count_obsoletos módulos obsoletos fueron eliminados.</span><br>";
    $resultado .= "<span style='color: blue;'>ℹ️ Los módulos existentes se han conservado.</span>";

    return $resultado;
}