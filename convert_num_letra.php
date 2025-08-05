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

class NumeroALetras
{
    private static $unidades = [
        0 => 'cero', 1 => 'uno', 2 => 'dos', 3 => 'tres', 4 => 'cuatro', 5 => 'cinco', 6 => 'seis', 7 => 'siete', 8 => 'ocho', 9 => 'nueve'
    ];

    private static $decenas = [
        10 => 'diez', 20 => 'veinte', 30 => 'treinta', 40 => 'cuarenta', 50 => 'cincuenta', 60 => 'sesenta', 70 => 'setenta', 80 => 'ochenta', 90 => 'noventa'
    ];

    private static $dieci = [
        11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince', 16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve'
    ];

    private static $centenas = [
        100 => 'cien', 200 => 'doscientos', 300 => 'trescientos', 400 => 'cuatrocientos', 500 => 'quinientos', 600 => 'seiscientos', 700 => 'setecientos', 800 => 'ochocientos', 900 => 'novecientos'
    ];

    public static function convertir($numero)
    {
        $numero = (int)$numero;
        if ($numero < 0 || $numero > 999999999) {
            return 'Número fuera de rango (0 - 999,999,999).';
        }

        if ($numero === 0) {
            return self::$unidades[0];
        }

        $partes = self::separarEnGrupos($numero);
        $resultado = self::procesarGrupos($partes);

        return trim($resultado);
    }

    private static function separarEnGrupos($numero)
    {
        $partes = [];
        $longitud = strlen((string)$numero);
        $inicio = $longitud % 3;

        if ($inicio > 0) {
            $partes[] = (int)substr((string)$numero, 0, $inicio);
        }

        for ($i = $inicio; $i < $longitud; $i += 3) {
            $partes[] = (int)substr((string)$numero, $i, 3);
        }
        return $partes;
    }

    private static function procesarGrupos($partes)
    {
        $resultado = '';
        $num_grupos = count($partes);
        $escala = ['', 'mil', 'millón']; // Corrected 'millón'

        foreach ($partes as $i => $grupo) {
            if ($grupo === 0) continue;

            $indice_escala = $num_grupos - $i - 1;
            $texto_grupo = self::convertirGrupo($grupo);
            
            if ($indice_escala > 0) {
                $texto_escala = $escala[$indice_escala];
                
                if ($grupo == 1) {
                    $resultado .= ($indice_escala == 2 ? ' un' : '') . ' ' . $texto_escala;
                } else {
                    $resultado .= ' ' . $texto_grupo;
                    if ($indice_escala == 2) {
                        $resultado .= ' millones'; // Se usa la palabra "millones" directamente
                    } else {
                        $resultado .= ' ' . $texto_escala;
                    }
                }
            } else {
                $resultado .= ' ' . $texto_grupo;
            }
        }
        return $resultado;
    }

    private static function convertirGrupo($n)
    {
        if ($n < 10) return self::$unidades[$n];
        if ($n >= 11 && $n <= 19) return self::$dieci[$n];
        if ($n % 100 === 0 && $n > 0) return self::$centenas[$n];
        if ($n === 100) return 'cien';

        $texto = '';
        $c = floor($n / 100);
        $d = floor(($n % 100) / 10);
        $u = $n % 10;

        if ($c > 0) {
            $texto .= self::$centenas[$c * 100] . ($c == 1 ? 'to' : '') . ' ';
        }

        $decena = $d * 10;
        if ($decena > 0) {
            // --- BLOCK REPLACED ---
            // Handles numbers from 20-29, which have special spellings and accents.
            if ($decena == 20) {
                if ($u === 0) {
                    $texto .= 'veinte';
                } else {
                    switch ($u) {
                        case 1: $texto .= 'veintiuno'; break;
                        case 2: $texto .= 'veintidós'; break;
                        case 3: $texto .= 'veintitrés'; break;
                        case 6: $texto .= 'veintiséis'; break;
                        default: $texto .= 'veinti' . self::$unidades[$u]; break;
                    }
                }
                return $texto;
            }
            // --- END BLOCK ---

            if ($decena == 10) {
                $texto .= self::$dieci[10 + $u];
                return $texto;
            }

            $texto .= self::$decenas[$decena];
            if ($u > 0) {
                $texto .= ' y ';
            }
        }

        if ($u > 0) {
            $texto .= self::$unidades[$u];
        }

        return trim($texto);
    }
}

// --- Lógica para procesar el formulario ---
$numero_ingresado = '';
$resultado_texto = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['numero']) && is_numeric($_POST['numero'])) {
        $numero_ingresado = $_POST['numero'];
        $resultado_texto = NumeroALetras::convertir($numero_ingresado);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convertidor de Número a Letras</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .resultado-area {
            background-color: #e9ecef;
            border-radius: .375rem;
            padding: 1rem;
            font-weight: 500;
            min-height: 60px;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card">
                <div class="card-header text-center bg-primary text-white">
                    <h3>🔢 Convertidor de Número a Letras</h3>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="numero" class="form-label">Ingresa un número (0 - 999,999,999):</label>
                            <input type="number" class="form-control form-control-lg" id="numero" name="numero"
                                   value="<?php echo htmlspecialchars($numero_ingresado); ?>"
                                   min="0" max="999999999" required>
                        </div>
                        <div class="d-grid">
                           <button type="submit" class="btn btn-primary btn-lg">Convertir</button>
                        </div>
                    </form>

                    <?php if (!empty($resultado_texto)): ?>
                        <hr class="my-4">
                        <h5 class="text-center">Resultado</h5>
                        <div id="resultadoTexto" class="resultado-area text-center fs-5 mb-3">
                            <?php echo htmlspecialchars(ucfirst($resultado_texto)); ?>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <button id="btnCopiarMayus" class="btn btn-secondary"><i class="bi bi-clipboard-fill"></i> Copiar en MAYÚSCULA</button>
                            <button id="btnCopiarMinus" class="btn btn-outline-secondary"><i class="bi bi-clipboard"></i> Copiar en minúscula</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const resultadoDiv = document.getElementById('resultadoTexto');
    const btnMayus = document.getElementById('btnCopiarMayus');
    const btnMinus = document.getElementById('btnCopiarMinus');

    // Función genérica para copiar y dar feedback al usuario
    const copiarTexto = (elemento, texto, formato) => {
        let textoAFormatear = texto;
        if (formato === 'mayus') {
            textoAFormatear = texto.toUpperCase();
        } else {
            textoAFormatear = texto.toLowerCase();
        }

        navigator.clipboard.writeText(textoAFormatear).then(() => {
            const textoOriginal = elemento.innerHTML;
            elemento.textContent = '¡Copiado!';
            elemento.classList.add('btn-success');
            setTimeout(() => {
                elemento.innerHTML = textoOriginal;
                elemento.classList.remove('btn-success');
            }, 2000);
        }).catch(err => {
            console.error('Error al copiar: ', err);
            alert('No se pudo copiar el texto.');
        });
    };

    if (resultadoDiv) {
        const textoParaCopiar = resultadoDiv.textContent.trim();
        
        btnMayus.addEventListener('click', () => {
            copiarTexto(btnMayus, textoParaCopiar, 'mayus');
        });

        btnMinus.addEventListener('click', () => {
            copiarTexto(btnMinus, textoParaCopiar, 'minus');
        });
    }
});
</script>

</body>
</html>