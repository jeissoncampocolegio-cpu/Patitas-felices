<?php
session_start();

require_once __DIR__ . '/includes/db.php';

/* ==========================================
   CONFIGURACIÓN
========================================== */

$baseUrl = '/patitas-felices';
$error   = '';
$correo  = '';

/* ==========================================
   FUNCIONES
========================================== */

function limpiarTexto($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Devuelve una ruta interna segura a la que volver después de iniciar sesión.
 * Solo acepta cosas como "solicitar.php?id=5". Cualquier otra cosa
 * (http://..., //sitio.com, ../) se descarta.
 */
function destinoSeguro(string $destino): string
{
    $destino = trim($destino);

    if (preg_match('/^[a-zA-Z0-9_\-]+\.php(\?[a-zA-Z0-9_=&\-]*)?$/', $destino)) {
        return $destino;
    }

    return '';
}

$redirect = destinoSeguro($_POST['redirect'] ?? $_GET['redirect'] ?? '');

/* ==========================================
   SI YA HAY SESIÓN, REDIRIGIR
========================================== */

if (
    isset($_SESSION['user_id'], $_SESSION['user_role']) &&
    (int) $_SESSION['user_id'] > 0
) {
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: {$baseUrl}/admin/dashboard.php");
        exit;
    }

    $destino = $redirect !== '' ? $redirect : 'index.php';
    header("Location: {$baseUrl}/{$destino}");
    exit;
}

/* ==========================================
   PROCESAR INICIO DE SESIÓN
========================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $clave  = $_POST['clave'] ?? '';

    if ($correo === '' || $clave === '') {

        $error = 'Por favor, completa todos los campos.';

    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

        $error = 'Ingresa un correo electrónico válido.';

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT id_usuario, nombre, correo, contrasena, rol, activo
                FROM usuarios
                WHERE correo = :correo
                LIMIT 1
            ");

            $stmt->execute([':correo' => $correo]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {

                $error = 'El correo o la contraseña son incorrectos.';

            } elseif ((int) $usuario['activo'] !== 1) {

                $error = 'Esta cuenta se encuentra inactiva.';

            } elseif (!password_verify($clave, $usuario['contrasena'])) {

                $error = 'El correo o la contraseña son incorrectos.';

            } else {

                $rol = strtolower(trim($usuario['rol']));

                if (!in_array($rol, ['admin', 'usuario', 'user'], true)) {

                    $error = 'La cuenta no tiene un rol válido.';

                } else {

                    /* Renovar sesión */
                    session_regenerate_id(true);

                    /* Guardar información */
                    $_SESSION['user_id']    = (int) $usuario['id_usuario'];
                    $_SESSION['user_name']  = $usuario['nombre'];
                    $_SESSION['user_email'] = $usuario['correo'];
                    $_SESSION['user_role']  = $rol;

                    /* Administrador */
                    if ($rol === 'admin') {

                        $_SESSION['admin_id']   = (int) $usuario['id_usuario'];
                        $_SESSION['admin_name'] = $usuario['nombre'];
                        $_SESSION['admin_role'] = 'admin';

                        header("Location: {$baseUrl}/admin/dashboard.php");
                        exit;
                    }

                    /* Usuario normal */
                    unset(
                        $_SESSION['admin_id'],
                        $_SESSION['admin_name'],
                        $_SESSION['admin_role']
                    );

                    $destino = $redirect !== '' ? $redirect : 'index.php';
                    header("Location: {$baseUrl}/{$destino}");
                    exit;
                }
            }

        } catch (PDOException $e) {

            error_log('Error de login: ' . $e->getMessage());
            $error = 'Ocurrió un problema al iniciar sesión. Inténtalo nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Inicia sesión en Patitas Felices.">
    <title>Iniciar sesión | Patitas Felices</title>

    <link rel="icon" href="<?= $baseUrl ?>/assets/img/logo.png">
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/login.css?v=14">
</head>

<body class="login-body">
    <main class="login-page">
        <div class="login-wrapper">

            <section class="login-card">

                <!-- PANEL DE MARCA -->
                <aside class="login-brand">
                    <div class="brand-content">

                        <a href="<?= $baseUrl ?>/index.php" class="login-logo-container" aria-label="Volver al inicio">
                            <img
                                src="<?= $baseUrl ?>/assets/img/logo.png"
                                alt="Logo de Patitas Felices"
                                class="login-logo"
                            >
                        </a>

                        <span class="brand-name">PATITAS FELICES</span>

                        <h1>¡Qué bueno tenerte de vuelta!</h1>

                        <p>
                            Ingresa a tu cuenta y continúa conociendo a nuestros
                            peluditos. Una adopción responsable puede cambiar una vida.
                        </p>

                        <div class="brand-decoration" aria-hidden="true">♥</div>
                    </div>

                    <div class="brand-circle brand-circle-one" aria-hidden="true"></div>
                    <div class="brand-circle brand-circle-two" aria-hidden="true"></div>
                </aside>

                <!-- PANEL DE INICIO DE SESIÓN -->
                <div class="login-form-section">

                    <div class="login-heading">
                        <span class="eyebrow">TU ESPACIO PERSONAL</span>
                        <h2>Iniciar sesión</h2>
                        <p>
                            <?= $redirect !== ''
                                ? 'Inicia sesión para continuar con tu solicitud.'
                                : 'Ingresa tus datos para acceder a tu cuenta.' ?>
                        </p>
                    </div>

                    <?php if ($error !== ''): ?>
                        <div class="login-error" role="alert">
                            <?= limpiarTexto($error) ?>
                        </div>
                    <?php endif; ?>

                    <form
                        method="POST"
                        action="<?= $baseUrl ?>/login.php"
                        class="login-form"
                        autocomplete="on"
                    >
                        <input
                            type="hidden"
                            name="redirect"
                            value="<?= limpiarTexto($redirect) ?>"
                        >

                        <div class="form-group">
                            <label for="correo">Correo electrónico</label>
                            <input
                                type="email"
                                id="correo"
                                name="correo"
                                placeholder="ejemplo@correo.com"
                                value="<?= limpiarTexto($correo) ?>"
                                autocomplete="username"
                                maxlength="150"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="clave">Contraseña</label>
                            <input
                                type="password"
                                id="clave"
                                name="clave"
                                placeholder="Ingresa tu contraseña"
                                autocomplete="current-password"
                                required
                            >
                        </div>

                        <button type="submit" class="login-btn">
                            Iniciar sesión <span aria-hidden="true">→</span>
                        </button>
                    </form>

                    <p class="login-note">
                        ¿Todavía no tienes una cuenta?
                        <a href="<?= $baseUrl ?>/registro.php">Regístrate aquí</a>.
                    </p>

                    <a href="<?= $baseUrl ?>/index.php" class="login-back">
                        ← Volver al inicio
                    </a>

                    <div class="login-copy">
                        © <?= date('Y') ?> Patitas Felices. Todos los derechos reservados.
                    </div>

                </div>
            </section>

        </div>
    </main>
</body>
</html>
