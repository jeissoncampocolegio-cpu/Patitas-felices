
<?php
require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Mascotas';
$baseUrl = '/patitas-felices';

/*
|--------------------------------------------------------------------------
| FUNCIÓN PARA ENCONTRAR LAS IMÁGENES
|--------------------------------------------------------------------------
*/

if (!function_exists('obtenerImagenMascota')) {

    function obtenerImagenMascota($foto, $baseUrl)
    {
        $foto = trim((string) $foto);

        // Imagen vacía
        if ($foto === '') {
            return $baseUrl . '/assets/img/mascota-default.png';
        }

        // Si la base de datos guarda una URL completa
        if (filter_var($foto, FILTER_VALIDATE_URL)) {
            return $foto;
        }

        // Normalizar las barras de las rutas
        $foto = str_replace('\\', '/', $foto);

        // Quitar ./ y ../ del comienzo
        $foto = preg_replace('#^(\.\./|\.\/)+#', '', $foto);

        // Si guarda la ruta completa del proyecto, extraer la ruta relativa
        $posicionAssets = strpos($foto, 'assets/');
        $posicionUploads = strpos($foto, 'uploads/');

        if ($posicionAssets !== false) {
            $foto = substr($foto, $posicionAssets);
        } elseif ($posicionUploads !== false) {
            $foto = substr($foto, $posicionUploads);
        }

        // Si ya tiene una ruta dentro del proyecto
        if (
            strpos($foto, 'assets/') === 0 ||
            strpos($foto, 'uploads/') === 0
        ) {
            $candidatas = [$foto];
        } else {
            // Si solamente guarda el nombre de la imagen
            $candidatas = [
                'assets/img/mascotas/' . $foto,
                'uploads/mascotas/' . $foto,
                'uploads/' . $foto,
                'assets/img/' . $foto,
                'img/mascotas/' . $foto,
                'mascotas/' . $foto
            ];
        }

        // Buscar el archivo físicamente
        foreach ($candidatas as $ruta) {

            $rutaFisica = __DIR__ . '/' . $ruta;

            if (is_file($rutaFisica)) {

                $segmentos = explode('/', $ruta);
                $segmentos = array_map('rawurlencode', $segmentos);

                return $baseUrl . '/' . implode('/', $segmentos);
            }
        }

        // Si no se encuentra, mostrar imagen predeterminada
        return $baseUrl . '/assets/img/mascota-default.png';
    }
}

/*
|--------------------------------------------------------------------------
| CONSULTAR MASCOTAS
|--------------------------------------------------------------------------
*/

$mascotas = [];
$errorConsulta = false;

try {

    $stmt = $pdo->query("
        SELECT
            id_mascota,
            nombre,
            especie,
            edad,
            sexo,
            tamano,
            descripcion,
            foto,
            estado
        FROM mascotas
        ORDER BY fecha_registro DESC
    ");

    $mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $errorConsulta = true;
}

require __DIR__ . '/includes/header.php';
?>

<style>
.mascotas-pagina {
    width: 100%;
    max-width: 1180px;
    margin: auto;
    padding: 45px 20px 65px;
    color: #f5f8ff;
}

.mascotas-encabezado {
    text-align: center;
    margin-bottom: 35px;
}

.mascotas-encabezado h1 {
    font-size: 36px;
    margin-bottom: 12px;
    color: #ffffff;
}

.mascotas-encabezado p {
    color: #b8c7dc;
    font-size: 16px;
    line-height: 1.6;
}

.mascotas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 25px;
}

.mascota-card {
    background: #111f35;
    border: 1px solid #2b4262;
    border-radius: 16px;
    overflow: hidden;
    transition: .25s;
}

.mascota-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,.25);
}

.mascota-foto {
    width: 100%;
    height: 230px;
    object-fit: cover;
    display: block;
    background: #0b1729;
}

.mascota-info {
    padding: 20px;
}

.mascota-info h2 {
    color: #fff;
    font-size: 23px;
    margin: 0 0 12px;
}

.mascota-info p {
    color: #b8c7dc;
    font-size: 14px;
    line-height: 1.6;
    margin: 7px 0;
}

.mascota-estado {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-top: 12px;
    background: #174d36;
    color: #8df0b8;
}

.mascota-estado.no-disponible {
    background: #51252d;
    color: #ffabb2;
}

.mascota-boton {
    display: block;
    text-align: center;
    background: #2a86f5;
    color: #fff;
    text-decoration: none;
    padding: 12px 16px;
    border-radius: 9px;
    font-weight: bold;
    margin-top: 18px;
    transition: .2s;
}

.mascota-boton:hover {
    background: #66adff;
    color: #fff;
}

.mascota-boton.deshabilitado {
    background: #40516a;
    pointer-events: none;
}

.mascotas-vacio,
.mascotas-error {
    background: #111f35;
    border: 1px solid #2b4262;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    color: #b8c7dc;
    grid-column: 1 / -1;
}

@media (max-width: 600px) {
    .mascotas-encabezado h1 {
        font-size: 28px;
    }

    .mascotas-pagina {
        padding: 30px 15px 45px;
    }
}
</style>

<section class="mascotas-pagina">

    <div class="mascotas-encabezado">
        <h1>Encuentra a tu nuevo mejor amigo</h1>

        <p>
            Conoce nuestras mascotas y brinda un hogar lleno de amor
            a quien más lo necesita.
        </p>
    </div>

    <div class="mascotas-grid">

        <?php if ($errorConsulta): ?>

            <div class="mascotas-error">
                <h3>No se pudieron cargar las mascotas</h3>
                <p>Ocurrió un problema al consultar la base de datos.</p>
            </div>

        <?php elseif (empty($mascotas)): ?>

            <div class="mascotas-vacio">
                <h3>No hay mascotas registradas</h3>
                <p>Pronto tendremos nuevos amigos esperando un hogar.</p>
            </div>

        <?php else: ?>

            <?php foreach ($mascotas as $m): ?>

                <?php
                $estado = strtolower(trim($m['estado'] ?? ''));
                $disponible = ($estado === 'disponible');

                $imagen = obtenerImagenMascota(
                    $m['foto'] ?? '',
                    $baseUrl
                );
                ?>

                <article class="mascota-card">

                    <img
                        src="<?= htmlspecialchars($imagen, ENT_QUOTES, 'UTF-8') ?>"
                        alt="<?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                        class="mascota-foto"
                        onerror="this.onerror=null;this.src='<?= $baseUrl ?>/assets/img/mascota-default.png';"
                    >

                    <div class="mascota-info">

                        <h2>
                            <?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </h2>

                        <p>
                            <strong>Especie:</strong>
                            <?= htmlspecialchars($m['especie'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p>
                            <strong>Edad:</strong>
                            <?= htmlspecialchars($m['edad'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p>
                            <strong>Sexo:</strong>
                            <?= htmlspecialchars($m['sexo'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p>
                            <strong>Tamaño:</strong>
                            <?= htmlspecialchars($m['tamano'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <p>
                            <?= nl2br(htmlspecialchars($m['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')) ?>
                        </p>

                        <span class="mascota-estado <?= !$disponible ? 'no-disponible' : '' ?>">
                            <?= htmlspecialchars($m['estado'], ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <?php if ($disponible): ?>

                            <a
                                href="<?= $baseUrl ?>/solicitar.php?id=<?= (int) $m['id_mascota'] ?>"
                                class="mascota-boton"
                            >
                                Solicitar adopción →
                            </a>

                        <?php else: ?>

                            <span class="mascota-boton deshabilitado">
                                No disponible
                            </span>

                        <?php endif; ?>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
