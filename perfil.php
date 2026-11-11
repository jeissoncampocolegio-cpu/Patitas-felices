<?php
/* =========================================================
   PERFIL DEL USUARIO · PATITAS FELICES
   Muestra los datos del adoptante y el seguimiento de cada
   solicitud de adopción.

   Orden:
   1. Sesión y acceso
   2. Funciones auxiliares
   3. Acción: cancelar solicitud
   4. Cargar datos
   5. Vista
   ========================================================= */

require_once __DIR__ . '/includes/db.php';

/* ---------------------------------------------------------
   1. SESIÓN Y ACCESO
--------------------------------------------------------- */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$idUsuario = (int) ($_SESSION['user_id'] ?? 0);

if ($idUsuario <= 0) {
    header('Location: login.php?redirect=' . urlencode('perfil.php'));
    exit;
}

$pageTitle = 'Mi perfil';

/* Token contra CSRF para el botón de cancelar */
if (empty($_SESSION['csrf_perfil'])) {
    $_SESSION['csrf_perfil'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_perfil'];

/* ---------------------------------------------------------
   2. FUNCIONES AUXILIARES
--------------------------------------------------------- */

if (!function_exists('e')) {
    function e($texto): string
    {
        return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
    }
}

function fechaLarga(?string $fecha): string
{
    if (!$fecha) {
        return '—';
    }

    $t = strtotime($fecha);
    if (!$t) {
        return '—';
    }

    $meses = [
        1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
    ];

    return date('j', $t) . ' de ' . $meses[(int) date('n', $t)] . ' de ' . date('Y', $t);
}

/* Etapas del proceso, en orden */
const ETAPAS = [
    1 => ['Solicitud enviada',  'Recibimos tu información.'],
    2 => ['En revisión',        'El equipo revisa tu perfil y tu motivo.'],
    3 => ['Entrevista y visita', 'Coordinamos una conversación contigo.'],
    4 => ['Decisión final',     'Te informamos el resultado.'],
];

/* Etapa en la que está cada estado */
function etapaActual(string $estado): int
{
    switch ($estado) {
        case 'Pendiente':   return 2;
        case 'En revisión': return 2;
        case 'Entrevista':  return 3;
        case 'Aprobada':    return 4;
        case 'Rechazada':   return 4;
        default:            return 0; // Cancelada u otro
    }
}

/* Clase de color para la insignia de estado */
function claseEstado(string $estado): string
{
    switch ($estado) {
        case 'Aprobada':    return 'ok';
        case 'Rechazada':   return 'no';
        case 'Cancelada':   return 'off';
        case 'Entrevista':  return 'info';
        default:            return 'espera';
    }
}

/* ---------------------------------------------------------
   3. ACCIÓN: CANCELAR SOLICITUD
--------------------------------------------------------- */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['accion'] ?? '') === 'cancelar'
) {
    $tokenOk = hash_equals($csrf, (string) ($_POST['csrf'] ?? ''));
    $idSol   = (int) ($_POST['id_solicitud'] ?? 0);

    if ($tokenOk && $idSol > 0) {
        /* Solo se puede cancelar una solicitud propia que aún no avanzó */
        $c = $pdo->prepare("
            UPDATE solicitudes_adopcion
            SET estado = 'Cancelada'
            WHERE id_solicitud = ?
              AND id_usuario = ?
              AND estado IN ('Pendiente', 'En revisión')
        ");
        $c->execute([$idSol, $idUsuario]);

        header('Location: perfil.php?msg=' . ($c->rowCount() ? 'cancelada' : 'nocancelable'));
        exit;
    }

    header('Location: perfil.php?msg=error');
    exit;
}

$mensajes = [
    'cancelada'    => ['ok',  'Tu solicitud fue cancelada.'],
    'nocancelable' => ['err', 'Esa solicitud ya no se puede cancelar porque está avanzada.'],
    'error'        => ['err', 'No se pudo completar la acción. Inténtalo de nuevo.'],
];
$aviso = $mensajes[$_GET['msg'] ?? ''] ?? null;

/* ---------------------------------------------------------
   4. CARGAR DATOS
--------------------------------------------------------- */

/* Usuario */
$u = $pdo->prepare("
    SELECT id_usuario, nombre, correo, rol
    FROM usuarios
    WHERE id_usuario = ? AND activo = 1
    LIMIT 1
");
$u->execute([$idUsuario]);
$usuario = $u->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    session_destroy();
    header('Location: login.php');
    exit;
}

/* Solicitudes del usuario, la más reciente primero */
$s = $pdo->prepare("
    SELECT s.*,
           m.nombre AS mascota_nombre,
           m.estado AS mascota_estado
    FROM solicitudes_adopcion s
    LEFT JOIN mascotas m ON m.id_mascota = s.id_mascota
    WHERE s.id_usuario = ?
    ORDER BY s.fecha_solicitud DESC
");
$s->execute([$idUsuario]);
$solicitudes = $s->fetchAll(PDO::FETCH_ASSOC);

/* Resumen */
$total      = count($solicitudes);
$enProceso  = 0;
$aprobadas  = 0;

foreach ($solicitudes as $fila) {
    $est = $fila['estado'] ?? 'Pendiente';

    if (in_array($est, ['Pendiente', 'En revisión', 'Entrevista'], true)) {
        $enProceso++;
    }
    if ($est === 'Aprobada') {
        $aprobadas++;
    }
}

/* Datos de contacto: los de la solicitud más reciente */
$telefono = $solicitudes[0]['telefono'] ?? '';
$ciudad   = $solicitudes[0]['ciudad'] ?? '';

$rolTexto  = $usuario['rol'] === 'admin' ? 'Administrador' : 'Adoptante';
$inicial   = mb_strtoupper(mb_substr($usuario['nombre'], 0, 1, 'UTF-8'), 'UTF-8');

/* ---------------------------------------------------------
   5. VISTA
--------------------------------------------------------- */

require __DIR__ . '/includes/header.php';
?>

<div class="wrap">

    <section class="pag">
        <span class="k">MI CUENTA</span>
        <h1>Mi perfil</h1>
        <p>
            Aquí puedes ver tus datos y seguir paso a paso
            el proceso de adopción de tu mascota.
        </p>
    </section>

    <?php if ($aviso): ?>
        <div class="pf-aviso pf-aviso-<?= e($aviso[0]) ?>" role="status">
            <?= e($aviso[1]) ?>
        </div>
    <?php endif; ?>

    <div class="pf-layout">

        <!-- =================================================
             COLUMNA IZQUIERDA · DATOS DEL ADOPTANTE
        ================================================== -->
        <aside class="pf-lado">

            <div class="pf-card pf-usuario">

                <div class="pf-avatar" aria-hidden="true"><?= e($inicial) ?></div>

                <h2><?= e($usuario['nombre']) ?></h2>

                <span class="pf-rol"><?= e($rolTexto) ?></span>

                <dl class="pf-datos">
                    <div>
                        <dt>Correo electrónico</dt>
                        <dd><?= e($usuario['correo']) ?></dd>
                    </div>

                    <div>
                        <dt>Teléfono</dt>
                        <dd><?= $telefono !== '' ? e($telefono) : 'Aún no registrado' ?></dd>
                    </div>

                    <div>
                        <dt>Ciudad</dt>
                        <dd><?= $ciudad !== '' ? e($ciudad) : 'Aún no registrada' ?></dd>
                    </div>
                </dl>

                <p class="pf-nota">
                    Teléfono y ciudad se toman de tu última solicitud.
                </p>

                <div class="pf-acciones-lado">
                    <?php if ($usuario['rol'] === 'admin'): ?>
                        <a href="admin/dashboard.php" class="btn">Ir al panel</a>
                    <?php endif; ?>
                    <a href="logout.php" class="out">Cerrar sesión</a>
                </div>

            </div>

            <div class="pf-resumen">
                <div class="pf-card pf-cifra">
                    <strong><?= (int) $total ?></strong>
                    <span>Solicitudes</span>
                </div>
                <div class="pf-card pf-cifra">
                    <strong><?= (int) $enProceso ?></strong>
                    <span>En proceso</span>
                </div>
                <div class="pf-card pf-cifra">
                    <strong><?= (int) $aprobadas ?></strong>
                    <span>Aprobadas</span>
                </div>
            </div>

        </aside>

        <!-- =================================================
             COLUMNA DERECHA · PROCESO DE ADOPCIÓN
        ================================================== -->
        <section class="pf-principal">

            <h2 class="pf-titulo">Mis solicitudes de adopción</h2>

            <?php if (!$solicitudes): ?>

                <div class="pf-card pf-vacio">
                    <span class="pf-vacio-icono" aria-hidden="true">🐾</span>
                    <h3>Todavía no tienes solicitudes</h3>
                    <p>
                        Cuando elijas una mascota y envíes tu solicitud,
                        aquí verás cómo avanza el proceso.
                    </p>
                    <a href="mascotas.php" class="btn">Ver mascotas disponibles</a>
                </div>

            <?php else: ?>

                <?php foreach ($solicitudes as $sol):
                    $estado   = $sol['estado'] ?? 'Pendiente';
                    $etapa    = etapaActual($estado);
                    $cancelada = ($estado === 'Cancelada');
                    $puedeCancelar = in_array($estado, ['Pendiente', 'En revisión'], true);
                    $nombreMascota = $sol['mascota_nombre'] ?? 'Mascota';
                ?>

                    <article class="pf-card pf-solicitud">

                        <header class="pf-sol-cab">
                            <div>
                                <h3><?= e($nombreMascota) ?></h3>
                                <p>
                                    Solicitud enviada el
                                    <?= e(fechaLarga($sol['fecha_solicitud'] ?? null)) ?>
                                </p>
                            </div>

                            <span class="pf-estado pf-estado-<?= e(claseEstado($estado)) ?>">
                                <?= e($estado) ?>
                            </span>
                        </header>

                        <?php if ($cancelada): ?>

                            <p class="pf-cancelada">
                                Cancelaste esta solicitud. Si cambias de opinión,
                                puedes enviar una nueva desde el catálogo.
                            </p>

                        <?php else: ?>

                            <!-- LÍNEA DE TIEMPO -->
                            <ol class="pf-pasos">
                                <?php foreach (ETAPAS as $n => [$titulo, $detalle]):

                                    if ($estado === 'Aprobada') {
                                        $clase = 'hecho';
                                    } elseif ($n < $etapa) {
                                        $clase = 'hecho';
                                    } elseif ($n === $etapa) {
                                        $clase = ($estado === 'Rechazada') ? 'rechazado' : 'actual';
                                    } else {
                                        $clase = 'pendiente';
                                    }

                                    $tituloPaso = $titulo;
                                    if ($n === 4 && $estado === 'Aprobada') {
                                        $tituloPaso = '¡Adopción aprobada!';
                                    } elseif ($n === 4 && $estado === 'Rechazada') {
                                        $tituloPaso = 'Solicitud no aprobada';
                                    }
                                ?>
                                    <li class="pf-paso pf-paso-<?= $clase ?>">
                                        <span class="pf-punto" aria-hidden="true">
                                            <?= $clase === 'hecho' ? '✓' : ($clase === 'rechazado' ? '!' : $n) ?>
                                        </span>
                                        <div>
                                            <strong><?= e($tituloPaso) ?></strong>
                                            <small><?= e($detalle) ?></small>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ol>

                            <?php if ($estado === 'Aprobada'): ?>
                                <p class="pf-mensaje pf-mensaje-ok">
                                    ¡Felicidades! El equipo se pondrá en contacto
                                    contigo para coordinar la entrega de <?= e($nombreMascota) ?>.
                                </p>
                            <?php elseif ($estado === 'Rechazada'): ?>
                                <p class="pf-mensaje pf-mensaje-no">
                                    Esta vez no fue posible continuar con la adopción.
                                    Puedes conocer a otras mascotas que buscan hogar.
                                </p>
                            <?php endif; ?>

                        <?php endif; ?>

                        <?php if (!empty($sol['observaciones'])): ?>
                            <div class="pf-obs">
                                <strong>Mensaje del equipo</strong>
                                <p><?= nl2br(e($sol['observaciones'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <details class="pf-detalle">
                            <summary>Ver lo que enviaste</summary>

                            <dl>
                                <div>
                                    <dt>Nombre</dt>
                                    <dd><?= e($sol['nombre_solicitante'] ?? '') ?></dd>
                                </div>
                                <div>
                                    <dt>Correo</dt>
                                    <dd><?= e($sol['correo'] ?? '') ?></dd>
                                </div>
                                <div>
                                    <dt>Teléfono</dt>
                                    <dd><?= e($sol['telefono'] ?? '') ?></dd>
                                </div>
                                <div>
                                    <dt>Ciudad</dt>
                                    <dd><?= e($sol['ciudad'] ?? '') ?></dd>
                                </div>
                                <div class="pf-ancho">
                                    <dt>Motivo</dt>
                                    <dd><?= nl2br(e($sol['motivo'] ?? '')) ?></dd>
                                </div>
                            </dl>
                        </details>

                        <footer class="pf-sol-pie">
                            <a href="mascotas.php" class="out">Ver mascotas</a>

                            <?php if ($puedeCancelar): ?>
                                <form
                                    method="post"
                                    action="perfil.php"
                                    onsubmit="return confirm('¿Seguro que quieres cancelar esta solicitud?');"
                                >
                                    <input type="hidden" name="accion" value="cancelar">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="id_solicitud" value="<?= (int) ($sol['id_solicitud'] ?? 0) ?>">

                                    <button type="submit" class="pf-cancelar">
                                        Cancelar solicitud
                                    </button>
                                </form>
                            <?php endif; ?>
                        </footer>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>