
<?php
/* =========================================================
   SOLICITUD DE ADOPCIÓN - PATITAS FELICES
========================================================= */

/* 1. CONFIGURACIÓN Y SESIÓN */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/db.php';

$baseUrl = '/patitas-felices';
$pageTitle = 'Solicitud de adopción';

$error = '';
$ok = '';

$id = (int) ($_GET['id'] ?? $_POST['id_mascota'] ?? 0);

/* IMPORTANTE: usar los mismos nombres que login.php */
$idUsuario = (int) ($_SESSION['user_id'] ?? 0);
$nombreSesion = $_SESSION['user_name'] ?? '';
$correoSesion = $_SESSION['user_email'] ?? '';

/* 2. VERIFICAR SESIÓN */

if ($idUsuario <= 0) {

    $volver = 'solicitar.php?id=' . $id;

    header(
        'Location: ' . $baseUrl . '/login.php?redirect=' . urlencode($volver)
    );

    exit;
}

/* 3. FUNCIÓN PARA ESCAPAR TEXTO */

if (!function_exists('e')) {
    function e($texto)
    {
        return htmlspecialchars(
            (string) $texto,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

/* 4. FUNCIÓN PARA OBTENER LA IMAGEN */

if (!function_exists('obtenerImagenSolicitud')) {

    function obtenerImagenSolicitud($foto, $baseUrl)
    {
        $foto = trim((string) $foto);

        if ($foto === '') {
            return $baseUrl . '/assets/img/mascota-default.png';
        }

        if (filter_var($foto, FILTER_VALIDATE_URL)) {
            return $foto;
        }

        $foto = str_replace('\\', '/', $foto);

        $posAssets = strpos($foto, 'assets/');
        $posUploads = strpos($foto, 'uploads/');

        if ($posAssets !== false) {
            $foto = substr($foto, $posAssets);
        } elseif ($posUploads !== false) {
            $foto = substr($foto, $posUploads);
        }

        if (
            strpos($foto, 'assets/') === 0 ||
            strpos($foto, 'uploads/') === 0
        ) {
            $rutas = [$foto];
        } else {
            $rutas = [
                'assets/img/mascotas/' . $foto,
                'uploads/mascotas/' . $foto,
                'uploads/' . $foto,
                'assets/img/' . $foto,
                'img/mascotas/' . $foto,
                'mascotas/' . $foto
            ];
        }

        foreach ($rutas as $ruta) {

            if (is_file(__DIR__ . '/' . $ruta)) {

                $partes = explode('/', $ruta);
                $partes = array_map('rawurlencode', $partes);

                return $baseUrl . '/' . implode('/', $partes);
            }
        }

        return $baseUrl . '/assets/img/mascota-default.png';
    }
}

/* 5. CONSULTAR MASCOTA */

$mascota = null;

if ($id > 0) {

    try {

        $consulta = $pdo->prepare("
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
            WHERE id_mascota = ?
            LIMIT 1
        ");

        $consulta->execute([$id]);

        $mascota = $consulta->fetch(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {

        $error = 'No se pudo consultar la mascota.';
        error_log('Error consultando mascota: ' . $e->getMessage());
    }
}

/* 6. VALORES DEL FORMULARIO */

$val = [
    'nombre' => $_POST['nombre'] ?? $nombreSesion,
    'correo' => $_POST['correo'] ?? $correoSesion,
    'telefono' => $_POST['telefono'] ?? '',
    'ciudad' => $_POST['ciudad'] ?? '',
    'motivo' => $_POST['motivo'] ?? ''
];

/* 7. PROCESAR SOLICITUD */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $mascota) {

    $nombre = trim($val['nombre']);
    $correo = trim($val['correo']);
    $telefono = trim($val['telefono']);
    $ciudad = trim($val['ciudad']);
    $motivo = trim($val['motivo']);

    if (strcasecmp(trim($mascota['estado']), 'Disponible') !== 0) {

        $error = 'Esta mascota ya no está disponible para adopción.';

    } elseif (
        $nombre === '' ||
        !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
        $telefono === '' ||
        $ciudad === '' ||
        $motivo === ''
    ) {

        $error = 'Completa todos los campos correctamente.';

    } else {

        try {

            /* Evitar solicitudes duplicadas */

            $duplicada = $pdo->prepare("
                SELECT COUNT(*)
                FROM solicitudes_adopcion
                WHERE id_mascota = ?
                AND id_usuario = ?
            ");

            $duplicada->execute([$id, $idUsuario]);

            if ((int) $duplicada->fetchColumn() > 0) {

                $error = 'Ya enviaste una solicitud para esta mascota.';

            } else {

                /* Guardar solicitud */

                $guardar = $pdo->prepare("
                    INSERT INTO solicitudes_adopcion
                    (
                        id_mascota,
                        id_usuario,
                        nombre_solicitante,
                        correo,
                        telefono,
                        ciudad,
                        motivo
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $guardar->execute([
                    $id,
                    $idUsuario,
                    $nombre,
                    $correo,
                    $telefono,
                    $ciudad,
                    $motivo
                ]);

                $ok = 'Tu solicitud fue recibida correctamente. Nuestro equipo revisará la información.';
            }

        } catch (PDOException $e) {

            error_log('Error guardando solicitud: ' . $e->getMessage());

            $error = 'No se pudo guardar la solicitud. Verifica la estructura de la tabla solicitudes_adopcion.';
        }
    }
}

/* 8. CARGAR ENCABEZADO */

require __DIR__ . '/includes/header.php';
?>

<style>
.solicitud-contenedor {
    max-width: 1050px;
    margin: auto;
    padding: 45px 20px 65px;
    color: #f5f8ff;
}

.solicitud-presentacion {
    text-align: center;
    margin-bottom: 35px;
}

.solicitud-presentacion h1 {
    font-size: 36px;
    margin: 12px 0;
    color: #fff;
}

.solicitud-presentacion p {
    color: #b8c7dc;
    line-height: 1.7;
}

.solicitud-etiqueta {
    color: #75b6ff;
    font-size: 12px;
    font-weight: bold;
    letter-spacing: 2px;
}

.solicitud-contenido {
    display: grid;
    grid-template-columns: .9fr 1.1fr;
    gap: 25px;
    align-items: start;
}

.solicitud-mascota,
.solicitud-formulario {
    background: #111f35;
    border: 1px solid #2b4262;
    border-radius: 17px;
    overflow: hidden;
}

.solicitud-foto {
    width: 100%;
    height: 290px;
    object-fit: cover;
    display: block;
    background: #0b1729;
}

.solicitud-datos {
    padding: 23px;
}

.solicitud-datos h2 {
    color: #fff;
    font-size: 25px;
}

.solicitud-datos p {
    color: #b8c7dc;
    line-height: 1.6;
}

.solicitud-estado {
    display: inline-block;
    padding: 7px 13px;
    border-radius: 20px;
    background: #174d36;
    color: #8df0b8;
    font-size: 12px;
    font-weight: bold;
}

.solicitud-formulario {
    padding: 25px;
}

.solicitud-formulario h2 {
    color: #fff;
    font-size: 23px;
    margin-top: 0;
}

.solicitud-formulario label {
    display: block;
    margin: 15px 0 7px;
    color: #dce7f7;
    font-size: 14px;
    font-weight: 600;
}

.solicitud-campo {
    width: 100%;
    padding: 13px 14px;
    background: #0b1729;
    border: 1px solid #304866;
    border-radius: 9px;
    color: #fff;
    font-family: inherit;
    font-size: 14px;
    box-sizing: border-box;
}

.solicitud-campo:focus {
    outline: none;
    border-color: #2a86f5;
}

textarea.solicitud-campo {
    resize: vertical;
    min-height: 120px;
}

.solicitud-acciones {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 22px;
}

.solicitud-btn {
    display: inline-block;
    border: none;
    padding: 13px 20px;
    border-radius: 9px;
    background: #2a86f5;
    color: white;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
}

.solicitud-btn:hover {
    background: #66adff;
}

.solicitud-cancelar {
    padding: 13px 18px;
    color: #b8c7dc;
    text-decoration: none;
    border: 1px solid #304866;
    border-radius: 9px;
}

.solicitud-mensaje {
    padding: 14px;
    border-radius: 9px;
    margin-bottom: 18px;
    line-height: 1.5;
}

.solicitud-error {
    background: #51252d;
    color: #ffbdc4;
}

.solicitud-exito {
    background: #174d36;
    color: #a3f2c2;
}

.solicitud-vacio {
    background: #111f35;
    border: 1px solid #2b4262;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
}

@media (max-width: 750px) {
    .solicitud-contenido {
        grid-template-columns: 1fr;
    }

    .solicitud-presentacion h1 {
        font-size: 29px;
    }
}
</style>

<main class="solicitud-contenedor">

    <section class="solicitud-presentacion">
        <span class="solicitud-etiqueta">ADOPCIÓN RESPONSABLE</span>

        <h1>Solicitud de adopción</h1>

        <p>
            Cuéntanos un poco sobre ti y el hogar que puedes ofrecer.
            Adoptar es un compromiso de amor, tiempo y responsabilidad.
        </p>
    </section>

    <?php if (!$mascota): ?>

        <section class="solicitud-vacio">

            <h2>No encontramos esta mascota</h2>

            <p>
                El identificador recibido no corresponde a una mascota registrada.
                Regresa al catálogo y selecciona una mascota.
            </p>

            <a href="<?= $baseUrl ?>/mascotas.php" class="solicitud-btn">
                Ver mascotas
            </a>

        </section>

    <?php else: ?>

        <div class="solicitud-contenido">

            <!-- INFORMACIÓN DE LA MASCOTA -->

            <section class="solicitud-mascota">

                <?php
                $imagen = obtenerImagenSolicitud(
                    $mascota['foto'] ?? '',
                    $baseUrl
                );
                ?>

                <img
                    src="<?= e($imagen) ?>"
                    alt="<?= e($mascota['nombre']) ?>"
                    class="solicitud-foto"
                    onerror="this.onerror=null;this.src='<?= $baseUrl ?>/assets/img/mascota-default.png';"
                >

                <div class="solicitud-datos">

                    <span class="solicitud-etiqueta">
                        CONOCE A TU NUEVO AMIGO
                    </span>

                    <h2><?= e($mascota['nombre']) ?></h2>

                    <p><strong>Especie:</strong> <?= e($mascota['especie']) ?></p>
                    <p><strong>Edad:</strong> <?= e($mascota['edad']) ?></p>
                    <p><strong>Sexo:</strong> <?= e($mascota['sexo']) ?></p>
                    <p><strong>Tamaño:</strong> <?= e($mascota['tamano']) ?></p>

                    <p>
                        <?= nl2br(e($mascota['descripcion'] ?? '')) ?>
                    </p>

                    <span class="solicitud-estado">
                        <?= e($mascota['estado']) ?>
                    </span>

                </div>

            </section>

            <!-- FORMULARIO -->

            <section class="solicitud-formulario">

                <h2>Completa tu solicitud</h2>

                <?php if ($ok): ?>

                    <div class="solicitud-mensaje solicitud-exito">
                        <strong>¡Solicitud recibida!</strong>
                        <p><?= e($ok) ?></p>
                    </div>

                    <a href="<?= $baseUrl ?>/mascotas.php" class="solicitud-btn">
                        Volver a las mascotas
                    </a>

                <?php else: ?>

                    <?php if ($error): ?>
                        <div class="solicitud-mensaje solicitud-error">
                            <?= e($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (strcasecmp(trim($mascota['estado']), 'Disponible') === 0): ?>

                        <form
                            method="POST"
                            action="<?= $baseUrl ?>/solicitar.php?id=<?= (int) $id ?>"
                        >

                            <input
                                type="hidden"
                                name="id_mascota"
                                value="<?= (int) $id ?>"
                            >

                            <label for="nombre">Nombre completo</label>

                            <input
                                class="solicitud-campo"
                                type="text"
                                id="nombre"
                                name="nombre"
                                maxlength="120"
                                value="<?= e($val['nombre']) ?>"
                                required
                            >

                            <label for="correo">Correo electrónico</label>

                            <input
                                class="solicitud-campo"
                                type="email"
                                id="correo"
                                name="correo"
                                maxlength="150"
                                value="<?= e($val['correo']) ?>"
                                required
                            >

                            <label for="telefono">Teléfono</label>

                            <input
                                class="solicitud-campo"
                                type="tel"
                                id="telefono"
                                name="telefono"
                                maxlength="30"
                                value="<?= e($val['telefono']) ?>"
                                required
                            >

                            <label for="ciudad">Ciudad</label>

                            <input
                                class="solicitud-campo"
                                type="text"
                                id="ciudad"
                                name="ciudad"
                                maxlength="100"
                                value="<?= e($val['ciudad']) ?>"
                                required
                            >

                            <label for="motivo">
                                ¿Por qué deseas adoptar esta mascota?
                            </label>

                            <textarea
                                class="solicitud-campo"
                                id="motivo"
                                name="motivo"
                                rows="5"
                                required
                            ><?= e($val['motivo']) ?></textarea>

                            <div class="solicitud-acciones">

                                <button type="submit" class="solicitud-btn">
                                    Enviar solicitud
                                </button>

                                <a
                                    href="<?= $baseUrl ?>/mascotas.php"
                                    class="solicitud-cancelar"
                                >
                                    Cancelar
                                </a>

                            </div>

                        </form>

                    <?php else: ?>

                        <div class="solicitud-mensaje solicitud-error">
                            Esta mascota ya no está disponible para nuevas solicitudes.
                        </div>

                        <a href="<?= $baseUrl ?>/mascotas.php" class="solicitud-btn">
                            Ver otras mascotas
                        </a>

                    <?php endif; ?>

                <?php endif; ?>

            </section>

        </div>

    <?php endif; ?>

</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
